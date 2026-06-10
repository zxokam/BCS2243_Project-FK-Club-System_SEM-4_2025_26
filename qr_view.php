<?php
include("config/dbconnect.php");
include("config/functions.php");

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

$token = isset($_GET["token"]) ? mysqli_real_escape_string($conn, $_GET["token"]) : "";
$record = null;

if ($token != "") {
    $record = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT q.*, s.student_name, s.studentID, s.program, s.student_role
        FROM student_qr_code q
        JOIN student s ON q.studentID = s.studentID
        WHERE q.qr_token = '$token'
        LIMIT 1
    "));
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FK Club QR Verification</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .verify-card {
            width: 100%;
            max-width: 520px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
            padding: 30px;
            text-align: center;
        }
        .verify-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: var(--green-light);
            color: #15803d;
            display: grid;
            place-items: center;
            margin: 0 auto 18px;
            font-size: 30px;
        }
        .verify-error {
            background: var(--red-light);
            color: #b91c1c;
        }
        .verify-info {
            margin-top: 20px;
            text-align: left;
            border-top: 1px solid var(--border);
            padding-top: 15px;
        }
        .verify-row {
            display: grid;
            grid-template-columns: 130px 1fr;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px solid #eef2f7;
        }
        .verify-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: bold;
        }
        .verify-value {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="verify-card">
        <?php if ($record) { ?>
            <div class="verify-icon">✓</div>
            <h1>Valid FK Club QR</h1>
            <p class="subtitle">This QR code is linked to a registered student account.</p>

            <div class="verify-info">
                <div class="verify-row">
                    <div class="verify-label">Student ID</div>
                    <div class="verify-value"><?php echo clean($record["studentID"]); ?></div>
                </div>
                <div class="verify-row">
                    <div class="verify-label">Name</div>
                    <div class="verify-value"><?php echo clean($record["student_name"]); ?></div>
                </div>
                <div class="verify-row">
                    <div class="verify-label">Program</div>
                    <div class="verify-value"><?php echo clean($record["program"]); ?></div>
                </div>
                <div class="verify-row">
                    <div class="verify-label">Role</div>
                    <div class="verify-value"><?php echo clean(ucfirst($record["student_role"])); ?></div>
                </div>
            </div>

            <p class="subtitle" style="margin-top:18px;">Committee members can use the QR Check-In page to record event attendance.</p>
        <?php } else { ?>
            <div class="verify-icon verify-error">!</div>
            <h1>Invalid QR Code</h1>
            <p class="subtitle">This QR code is not registered in the FK Club database.</p>
            <a class="btn" style="margin-top:18px;" href="auth/login.php">Go to Login</a>
        <?php } ?>
    </div>
</body>
</html>
