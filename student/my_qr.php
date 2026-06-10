<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "student") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

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

$studentID = mysqli_real_escape_string($conn, $_SESSION["user_id"]);
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM student WHERE studentID = '$studentID'"));

if (!$student) {
    header("Location: dashboard.php");
    exit();
}

$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$host = $_SERVER["HTTP_HOST"];
$basePath = rtrim(dirname(dirname($_SERVER["SCRIPT_NAME"])), "/\\");

$qr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM student_qr_code WHERE studentID = '$studentID' LIMIT 1"));

if ($qr && $qr["qr_token"] != "") {
    $token = $qr["qr_token"];
} else {
    try {
        $token = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $token = md5(uniqid($studentID, true));
    }
}

$qrContent = $scheme . "://" . $host . $basePath . "/qr_view.php?token=" . urlencode($token);
$safeToken = mysqli_real_escape_string($conn, $token);
$safeContent = mysqli_real_escape_string($conn, $qrContent);

mysqli_query($conn, "
    INSERT INTO student_qr_code (studentID, qr_token, qr_content)
    VALUES ('$studentID', '$safeToken', '$safeContent')
    ON DUPLICATE KEY UPDATE
        qr_token = VALUES(qr_token),
        qr_content = VALUES(qr_content),
        updated_at = NOW()
");

page_start("My QR Code", "my_qr");
?>

<style>
    .qr-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 70vh;
    }
    .qr-card {
        background: white;
        border-radius: 18px;
        padding: 34px;
        text-align: center;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
        border: 1px solid var(--border);
        max-width: 480px;
        width: 100%;
    }
    .qr-code-container {
        background: white;
        padding: 18px;
        border-radius: 16px;
        display: inline-block;
        border: 1px solid var(--border);
        margin: 10px 0;
    }
    .student-name {
        font-size: 20px;
        font-weight: bold;
        margin: 18px 0 5px;
        color: var(--text);
    }
    .student-id {
        color: var(--muted);
        margin-bottom: 18px;
    }
    .qr-note {
        background: var(--blue-light);
        color: var(--blue);
        padding: 10px 12px;
        border-radius: 10px;
        font-size: 12px;
        margin-top: 15px;
    }
    .download-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 18px;
        flex-wrap: wrap;
    }
    @media print {
        .sidebar, .topbar, .page-header, .download-buttons, .menu, .logout {
            display: none !important;
        }
        .main { margin-left: 0 !important; }
        .page { padding: 0 !important; }
        .qr-card { box-shadow: none; border: none; }
    }
</style>

<div class="page-header">
    <div>
        <h1>My QR Code</h1>
        <p class="subtitle">This QR code is generated and saved in the database for attendance check-in.</p>
    </div>
</div>

<div class="qr-container">
    <div class="qr-card">
        <div class="qr-code-container" id="qrCodeContainer">
            <div id="qrcode"></div>
        </div>

        <div class="student-name"><?php echo clean($student["student_name"]); ?></div>
        <div class="student-id"><?php echo clean($student["studentID"]); ?></div>
        <span class="badge badge-green">Valid Student QR</span>

        <div class="qr-note">
            QR record saved into <b>student_qr_code</b> table.<br>
            Committee can scan this QR code during event check-in.
        </div>

        <div class="download-buttons">
            <button class="btn" onclick="downloadQRAsImage()">Download QR Code</button>
            <button class="btn btn-light" onclick="window.print()">Print QR Code</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<script>
function generateQRCode() {
    const qrText = <?php echo json_encode($qrContent); ?>;
    const qrDiv = document.getElementById('qrcode');
    qrDiv.innerHTML = '';

    new QRCode(qrDiv, {
        text: qrText,
        width: 250,
        height: 250,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });
}

function downloadQRAsImage() {
    const container = document.getElementById('qrCodeContainer');

    html2canvas(container, {
        scale: 2,
        backgroundColor: '#ffffff'
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'qr_code_<?php echo clean($studentID); ?>.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    }).catch(() => {
        alert('Failed to download QR code. Please try again.');
    });
}

document.addEventListener('DOMContentLoaded', generateQRCode);
</script>

<?php page_end(); ?>
