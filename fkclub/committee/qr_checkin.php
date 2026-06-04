<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

ensure_waiting_list_schema($conn);

if ($_SESSION["role"] != "committee") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$message = "";
$error = "";

$eventID = isset($_GET["eventID"]) ? mysqli_real_escape_string($conn, $_GET["eventID"]) : "";

if (isset($_POST["checkin"])) {
    $eventID = mysqli_real_escape_string($conn, $_POST["eventID"]);
    $studentID = strtoupper(mysqli_real_escape_string($conn, $_POST["studentID"]));

    $student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM student WHERE studentID='$studentID'"));

    if (!$student) {
        $error = "Student ID not found.";
    } else {
        $registration = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM event_registration WHERE studentID='$studentID' AND eventID='$eventID' ORDER BY registrationID DESC LIMIT 1"));
        $canCheckIn = true;
        $promoted = false;

        if (!$registration) {
            if (event_has_available_slot($conn, $eventID)) {
                mysqli_query($conn, "INSERT INTO event_registration (studentID, eventID, registration_status, confirmation_status, queue_number)
                                     VALUES ('$studentID', '$eventID', 'Registered', 'Confirmed', 0)");
                $registrationID = mysqli_insert_id($conn);
            } else {
                $canCheckIn = false;
                $error = "Event is full. Student was not checked in. Please ask the student to register for the waiting list first.";
            }
        } else {
            $registrationID = $registration["registrationID"];

            if ($registration["registration_status"] == "Waiting List") {
                $canCheckIn = false;
                $error = "Student is still in the waiting list. Accept the student from Manage Events > Waiting List before check-in.";
            } elseif ($registration["registration_status"] == "Cancelled") {
                if (event_has_available_slot($conn, $eventID)) {
                    mysqli_query($conn, "UPDATE event_registration SET registration_status='Registered', confirmation_status='Confirmed', queue_number=0 WHERE registrationID='$registrationID'");
                } else {
                    $canCheckIn = false;
                    $error = "Event is full. Cancelled registration cannot be checked in.";
                }
            } else {
                mysqli_query($conn, "UPDATE event_registration SET registration_status='Registered', confirmation_status='Confirmed', queue_number=0 WHERE registrationID='$registrationID'");
            }
        }

        if ($canCheckIn) {
            $sql = "INSERT INTO attendance
                    (registrationID, studentID, eventID, status_pointID, attendance_status, checkin_time, point_earned)
                    VALUES
                    ('$registrationID', '$studentID', '$eventID', 1, 'Present On Time', NOW(), 10)
                    ON DUPLICATE KEY UPDATE
                    status_pointID=1,
                    attendance_status='Present On Time',
                    checkin_time=NOW(),
                    point_earned=10";

            if (mysqli_query($conn, $sql)) {
                $message = $student["student_name"] . " checked in successfully. +10 points awarded.";
            } else {
                $error = mysqli_error($conn);
            }
        }
    }
}

$events = mysqli_query($conn, "SELECT * FROM event ORDER BY event_date DESC");

if ($eventID == "") {
    $first = mysqli_fetch_assoc(mysqli_query($conn, "SELECT eventID FROM event ORDER BY event_date DESC LIMIT 1"));
    $eventID = $first ? $first["eventID"] : 0;
}

$recent = mysqli_query($conn, "
    SELECT a.*, s.student_name, e.event_title
    FROM attendance a
    JOIN student s ON a.studentID = s.studentID
    JOIN event e ON a.eventID = e.eventID
    WHERE a.eventID='$eventID'
    ORDER BY a.checkin_time DESC
    LIMIT 8
");

page_start("QR Check-In", "qr_checkin");
?>
<div class="page-header">
    <div>
        <h1>QR / Student ID Check-In</h1>
        <p class="subtitle">Extra attendance feature inspired by QR check-in. Waiting-list students must be accepted by committee before check-in.</p>
    </div>
</div>

<?php if ($message != "") { ?><div class="alert alert-success"><?php echo clean($message); ?></div><?php } ?>
<?php if ($error != "") { ?><div class="alert alert-error"><?php echo clean($error); ?></div><?php } ?>

<div class="grid grid-2">
    <form class="form-box" method="POST" id="checkinForm" onsubmit="return validateRequiredForm('checkinForm')">
        <div class="section">
            <h3>Check-In Form</h3>
            <label>Event <span class="required">*</span></label>
            <select class="form-control" name="eventID" required>
                <?php mysqli_data_seek($events, 0); while ($event = mysqli_fetch_assoc($events)) { ?>
                <option value="<?php echo clean($event["eventID"]); ?>" <?php if ($eventID == $event["eventID"]) echo "selected"; ?>>
                    <?php echo clean($event["event_title"]); ?>
                </option>
                <?php } ?>
            </select>

            <label>Student ID <span class="required">*</span></label>
            <input class="form-control" name="studentID" placeholder="Example: CB24070" required>

            <div class="actions">
                <button class="btn" name="checkin">Check In Student</button>
            </div>
        </div>
    </form>

    <div class="panel" style="text-align:center;">
        <h3>QR Placeholder</h3>
        <div style="width:190px;height:190px;margin:20px auto;background:#0f172a;color:white;border-radius:16px;display:grid;place-items:center;font-size:70px;letter-spacing:8px;">
            ▦▣
        </div>
        <p class="subtitle">In final prototype, this can be replaced with real QR camera scanning. For Progress 2, the Student ID input still updates attendance records in MySQL.</p>
    </div>
</div>

<br>

<div class="panel">
    <h3>Recent Check-Ins</h3>
    <table>
        <tr><th>Student</th><th>Event</th><th>Status</th><th>Time</th><th>Points</th></tr>
        <?php while ($row = mysqli_fetch_assoc($recent)) { ?>
        <tr>
            <td class="user-cell"><span class="mini-avatar"><?php echo clean(initials($row["student_name"])); ?></span><?php echo clean($row["student_name"]); ?> (<?php echo clean($row["studentID"]); ?>)</td>
            <td><?php echo clean($row["event_title"]); ?></td>
            <td><span class="badge badge-green"><?php echo clean($row["attendance_status"]); ?></span></td>
            <td><?php echo clean($row["checkin_time"]); ?></td>
            <td><?php echo clean($row["point_earned"]); ?></td>
        </tr>
        <?php } ?>
    </table>
</div>

<script src="../assets/script.js"></script>
<?php page_end(); ?>
