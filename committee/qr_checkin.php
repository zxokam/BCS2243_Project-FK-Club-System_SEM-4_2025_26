<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

ensure_waiting_list_schema($conn);

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS student_qr_code (
        qrID INT AUTO_INCREMENT PRIMARY KEY,
        studentID VARCHAR(30) NOT NULL UNIQUE,
        qr_token VARCHAR(100) NOT NULL UNIQUE,
        qr_content VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

if ($_SESSION["role"] != "committee") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$message = "";
$error = "";
$eventID = isset($_GET["eventID"]) ? mysqli_real_escape_string($conn, $_GET["eventID"]) : "";

function resolveStudentFromQrPayload($conn, $payload) {
    $raw = trim($payload ?? "");

    if ($raw == "") {
        return ["success" => false, "message" => "QR code is empty."];
    }

    $candidate = strtoupper($raw);

    if (strpos($candidate, "STU:") === 0) {
        $candidate = substr($candidate, 4);
    }

    if (strpos($candidate, "ID:") === 0) {
        $candidate = substr($candidate, 3);
    }

    if (preg_match('/^CB[0-9]{5}$/i', $candidate)) {
        return ["success" => true, "studentID" => strtoupper($candidate)];
    }

    $token = "";

    if (preg_match('/[?&]token=([^&]+)/i', $raw, $matches)) {
        $token = urldecode($matches[1]);
    } elseif (stripos($raw, "FKCLUB:") === 0) {
        $parts = explode(":", $raw);
        $token = end($parts);
    } else {
        $token = $raw;
    }

    $safeToken = mysqli_real_escape_string($conn, trim($token));
    $safeRaw = mysqli_real_escape_string($conn, $raw);

    $qr = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT studentID
        FROM student_qr_code
        WHERE qr_token = '$safeToken'
        OR qr_content = '$safeRaw'
        LIMIT 1
    "));

    if ($qr) {
        return ["success" => true, "studentID" => $qr["studentID"]];
    }

    return ["success" => false, "message" => "Invalid QR code. The QR code is not saved in the database."];
}

function processCheckin($conn, $eventID, $studentID) {
    $eventID = mysqli_real_escape_string($conn, $eventID);
    $studentID = strtoupper(mysqli_real_escape_string($conn, $studentID));

    if ($eventID == "") {
        return ["success" => false, "message" => "Please select an event first."];
    }

    $student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM student WHERE studentID='$studentID'"));

    if (!$student) {
        return ["success" => false, "message" => "Student ID not found."];
    }

    $registration = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT *
        FROM event_registration
        WHERE studentID='$studentID'
        AND eventID='$eventID'
        ORDER BY registrationID DESC
        LIMIT 1
    "));

    $registrationID = null;

    if (!$registration) {
        if (event_has_available_slot($conn, $eventID)) {
            mysqli_query($conn, "
                INSERT INTO event_registration (studentID, eventID, registration_status, confirmation_status, queue_number)
                VALUES ('$studentID', '$eventID', 'Registered', 'Confirmed', 0)
            ");
            $registrationID = mysqli_insert_id($conn);
        } else {
            return ["success" => false, "message" => "Event is full. Student must join waiting list first.", "student" => $student];
        }
    } else {
        $registrationID = $registration["registrationID"];

        if ($registration["registration_status"] == "Waiting List") {
            return ["success" => false, "message" => "Student is in waiting list. Accept the student first before check-in.", "student" => $student];
        }

        if ($registration["registration_status"] == "Cancelled") {
            if (!event_has_available_slot($conn, $eventID)) {
                return ["success" => false, "message" => "Event is full. Cancelled registration cannot be checked in.", "student" => $student];
            }
        }

        mysqli_query($conn, "
            UPDATE event_registration
            SET registration_status='Registered', confirmation_status='Confirmed', queue_number=0
            WHERE registrationID='$registrationID'
        ");
    }

    $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT attendanceID FROM attendance WHERE registrationID='$registrationID'"));

    if ($existing) {
        return ["success" => false, "message" => $student["student_name"] . " has already checked in.", "student" => $student];
    }

    $sql = "INSERT INTO attendance (registrationID, studentID, eventID, status_pointID, attendance_status, checkin_time, point_earned)
            VALUES ('$registrationID', '$studentID', '$eventID', 1, 'Present On Time', NOW(), 10)";

    if (mysqli_query($conn, $sql)) {
        return ["success" => true, "message" => $student["student_name"] . " checked in successfully. +10 points awarded.", "student" => $student];
    }

    return ["success" => false, "message" => "Database error: " . mysqli_error($conn), "student" => $student];
}

if (isset($_POST["checkin"])) {
    $eventID = mysqli_real_escape_string($conn, $_POST["eventID"]);
    $resolved = resolveStudentFromQrPayload($conn, $_POST["studentID"]);

    if ($resolved["success"]) {
        $result = processCheckin($conn, $eventID, $resolved["studentID"]);
        if ($result["success"]) {
            $message = $result["message"];
        } else {
            $error = $result["message"];
        }
    } else {
        $error = $resolved["message"];
    }
}

if (isset($_POST["qr_checkin"])) {
    header('Content-Type: application/json');
    $eventID = mysqli_real_escape_string($conn, $_POST["eventID"]);
    $payload = $_POST["qr_payload"] ?? ($_POST["studentID"] ?? "");
    $resolved = resolveStudentFromQrPayload($conn, $payload);

    if (!$resolved["success"]) {
        echo json_encode($resolved);
        exit();
    }

    echo json_encode(processCheckin($conn, $eventID, $resolved["studentID"]));
    exit();
}

$events = mysqli_query($conn, "SELECT * FROM event ORDER BY event_date DESC");

if ($eventID == "") {
    $first = mysqli_fetch_assoc(mysqli_query($conn, "SELECT eventID FROM event ORDER BY event_date DESC LIMIT 1"));
    $eventID = $first ? $first["eventID"] : 0;
}

$recent = mysqli_query($conn, "
    SELECT a.*, s.student_name, s.studentID, e.event_title
    FROM attendance a
    JOIN student s ON a.studentID = s.studentID
    JOIN event e ON a.eventID = e.eventID
    WHERE a.eventID='$eventID'
    ORDER BY a.checkin_time DESC
    LIMIT 10
");

$todayStats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total_checked, COUNT(DISTINCT studentID) AS unique_students
    FROM attendance
    WHERE DATE(checkin_time) = CURDATE()
"));

page_start("QR Check-In", "qr_checkin");
?>

<style>
    .qr-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        padding: 18px;
    }
    .qr-modal-content {
        width: 100%;
        max-width: 560px;
        background: white;
        border-radius: 18px;
        padding: 20px;
        border: 1px solid var(--border);
    }
    .qr-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
    }
    .qr-modal-header button {
        border: none;
        background: transparent;
        font-size: 28px;
        cursor: pointer;
        color: var(--muted);
    }
    #qr-reader {
        width: 100%;
        border-radius: 14px;
        overflow: hidden;
        background: #000;
    }
    .qr-result {
        display: none;
        margin-top: 14px;
        padding: 12px;
        border-radius: 10px;
        text-align: center;
    }
    .qr-result.success {
        background: var(--green-light);
        color: #15803d;
    }
    .qr-result.error {
        background: var(--red-light);
        color: #b91c1c;
    }
    .stat-card-small {
        background: white;
        border: 1px solid var(--border);
        border-radius: 13px;
        padding: 16px;
        text-align: center;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
    }
    .stat-number {
        font-size: 26px;
        font-weight: bold;
        color: var(--blue);
        margin-top: 6px;
    }
    .qr-icon-btn {
        border: 1px solid var(--border);
        background: var(--blue-light);
        color: var(--blue);
        padding: 14px 18px;
        border-radius: 12px;
        cursor: pointer;
        font-weight: bold;
        width: 100%;
    }
    .qr-icon-btn:hover {
        background: var(--blue);
        color: white;
    }
</style>

<div class="page-header">
    <div>
        <h1>QR Code Check-In System</h1>
        <p class="subtitle">Scan a saved student QR code or enter Student ID manually for event attendance.</p>
    </div>
</div>

<?php if ($message != "") { ?>
    <div class="alert alert-success"><?php echo clean($message); ?></div>
<?php } ?>

<?php if ($error != "") { ?>
    <div class="alert alert-error"><?php echo clean($error); ?></div>
<?php } ?>

<div class="grid grid-4" style="margin-bottom:20px;">
    <div class="stat-card-small">
        <div>Today's Check-Ins</div>
        <div class="stat-number"><?php echo clean($todayStats["total_checked"] ?? 0); ?></div>
    </div>
    <div class="stat-card-small">
        <div>Unique Students</div>
        <div class="stat-number"><?php echo clean($todayStats["unique_students"] ?? 0); ?></div>
    </div>
    <div class="stat-card-small">
        <div>Points per Check-In</div>
        <div class="stat-number">+10</div>
    </div>
    <div class="stat-card-small">
        <div>QR Database</div>
        <div class="stat-number">Saved</div>
    </div>
</div>

<div class="grid grid-2">
    <form class="form-box" method="POST" id="checkinForm" style="margin:0;">
        <div class="section">
            <h3>Manual Check-In</h3>

            <label>Select Event <span class="required">*</span></label>
            <select class="form-control" name="eventID" id="manualEventID" required onchange="updateEventID(this.value)">
                <?php
                mysqli_data_seek($events, 0);
                while ($event = mysqli_fetch_assoc($events)) {
                ?>
                    <option value="<?php echo clean($event["eventID"]); ?>" <?php if ($eventID == $event["eventID"]) echo "selected"; ?>>
                        <?php echo clean($event["event_title"]); ?> (<?php echo clean($event["event_date"]); ?>)
                    </option>
                <?php } ?>
            </select>

            <label>Student ID / QR Token <span class="required">*</span></label>
            <input class="form-control" id="studentID" name="studentID" placeholder="Example: CB24070" required autocomplete="off">

            <div class="actions" style="margin-top:20px;">
                <button class="btn" name="checkin" type="submit">Check In Student</button>
                <button type="button" class="btn btn-light" onclick="document.getElementById('studentID').value = ''">Clear</button>
            </div>
        </div>
    </form>

    <div class="panel" style="display:flex; flex-direction:column; justify-content:center;">
        <div style="text-align:center; margin-bottom:20px;">
            <div style="font-size:46px; margin-bottom:12px;">▦</div>
            <h3>QR Code Scanner</h3>
            <p class="subtitle">Use camera to scan the student QR code generated from My QR Code page.</p>
        </div>

        <button class="qr-icon-btn" onclick="openQRScanner()">Scan QR Code</button>

        <div style="margin-top:18px; text-align:center;">
            <a href="attendance.php" class="btn btn-light">View Attendance</a>
        </div>
    </div>
</div>

<br>

<div class="panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h3>Recent Check-Ins</h3>
        <button class="btn btn-light btn-small" onclick="location.reload()">Refresh</button>
    </div>

    <div class="table-box">
        <table>
            <tr>
                <th>Student</th>
                <th>Event</th>
                <th>Status</th>
                <th>Check-In Time</th>
                <th>Points</th>
            </tr>
            <?php if ($recent && mysqli_num_rows($recent) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($recent)) { ?>
                    <tr>
                        <td class="user-cell">
                            <span class="mini-avatar"><?php echo clean(initials($row["student_name"])); ?></span>
                            <div>
                                <b><?php echo clean($row["student_name"]); ?></b><br>
                                <small><?php echo clean($row["studentID"]); ?></small>
                            </div>
                        </td>
                        <td><?php echo clean($row["event_title"]); ?></td>
                        <td><span class="badge badge-green"><?php echo clean($row["attendance_status"]); ?></span></td>
                        <td><?php echo clean(date("H:i:s d/m/Y", strtotime($row["checkin_time"]))); ?></td>
                        <td><b>+<?php echo clean($row["point_earned"]); ?></b></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:35px; color:var(--muted);">No check-ins yet.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<div id="qrModal" class="qr-modal">
    <div class="qr-modal-content">
        <div class="qr-modal-header">
            <h3>Scan Student QR Code</h3>
            <button onclick="closeQRScanner()">&times;</button>
        </div>
        <div id="qr-reader"></div>
        <div id="qrResult" class="qr-result"></div>
        <p class="subtitle" style="text-align:center; margin-top:12px;">The QR code must exist in the student_qr_code database table.</p>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode;
let isScanning = false;

function openQRScanner() {
    document.getElementById('qrModal').style.display = 'flex';
    startQrScanner();
}

function closeQRScanner() {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().then(function() {
            isScanning = false;
        }).catch(function() {});
    }
    document.getElementById('qrModal').style.display = 'none';
    const resultDiv = document.getElementById('qrResult');
    resultDiv.style.display = 'none';
    resultDiv.className = 'qr-result';
}

async function startQrScanner() {
    const qrReaderDiv = document.getElementById('qr-reader');
    qrReaderDiv.innerHTML = '';
    html5QrCode = new Html5Qrcode('qr-reader');

    try {
        await html5QrCode.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 280, height: 280 }, aspectRatio: 1.0 },
            onScanSuccess,
            function() {}
        );
        isScanning = true;
    } catch (err) {
        const resultDiv = document.getElementById('qrResult');
        resultDiv.innerHTML = 'Cannot access camera. Please check browser permission.';
        resultDiv.style.display = 'block';
        resultDiv.className = 'qr-result error';
    }
}

function onScanSuccess(decodedText) {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().then(function() {
            isScanning = false;
        }).catch(function() {});
    }

    const payload = decodedText.trim();
    const resultDiv = document.getElementById('qrResult');
    resultDiv.innerHTML = 'Processing QR code...';
    resultDiv.style.display = 'block';
    resultDiv.className = 'qr-result';

    const eventID = document.getElementById('manualEventID').value;

    if (!eventID) {
        resultDiv.innerHTML = 'Please select an event first.';
        resultDiv.className = 'qr-result error';
        return;
    }

    const formData = new FormData();
    formData.append('qr_checkin', '1');
    formData.append('eventID', eventID);
    formData.append('qr_payload', payload);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        if (result.success) {
            resultDiv.innerHTML = result.message;
            resultDiv.className = 'qr-result success';
            if (result.student && result.student.studentID) {
                document.getElementById('studentID').value = result.student.studentID;
            }
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            resultDiv.innerHTML = result.message;
            resultDiv.className = 'qr-result error';
        }
    })
    .catch(function() {
        resultDiv.innerHTML = 'Network error. Please try again.';
        resultDiv.className = 'qr-result error';
    });
}

function updateEventID(eventID) {
    if (eventID) {
        window.location.href = 'qr_checkin.php?eventID=' + eventID;
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('qrModal');
    if (event.target == modal) {
        closeQRScanner();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('studentID');
    if (input) input.focus();
});
</script>

<?php page_end(); ?>
