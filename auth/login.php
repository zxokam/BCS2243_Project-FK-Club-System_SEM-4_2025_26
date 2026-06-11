<?php
session_start();

// Prevent browser Back button from showing the login page while the session is still active.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

function redirect_dashboard_by_role($role) {
    if ($role == "admin") {
        header("Location: ../admin/dashboard.php");
    } elseif ($role == "committee") {
        header("Location: ../committee/dashboard.php");
    } else {
        header("Location: ../student/dashboard.php");
    }
    exit();
}

// Only auto-redirect on normal page load. If the login form is submitted from a cached/back page,
// allow the new login to replace the previous account session.
if ($_SERVER["REQUEST_METHOD"] != "POST" && isset($_SESSION["role"])) {
    redirect_dashboard_by_role($_SESSION["role"]);
}

include("../config/dbconnect.php");
include("../config/functions.php");

$error = "";

if (isset($_POST["login"])) {
    // Start every login attempt with a fresh session so another account will not reuse the last dashboard.
    $_SESSION = array();
    session_regenerate_id(true);

    $username = esc($conn, $_POST["username"]);
    $password = $_POST["password"];

    // No role selection is needed. The system checks admin first, then student.
    // Committee is still a student account, so committee users can switch between Committee and Student after login.
    $adminSql = "SELECT * FROM admin
                 WHERE admin_email = '$username'
                 OR admin_name = '$username'
                 LIMIT 1";
    $adminResult = mysqli_query($conn, $adminSql);

    if ($adminRow = mysqli_fetch_assoc($adminResult)) {
        if (password_match($password, $adminRow["admin_password"])) {
            $_SESSION["user_id"] = $adminRow["adminID"];
            $_SESSION["user_name"] = $adminRow["admin_name"];
            $_SESSION["role"] = "admin";
            $_SESSION["account_role"] = "admin";
            header("Location: ../admin/dashboard.php");
            exit();
        }
    }

    $studentSql = "SELECT * FROM student
                   WHERE studentID = '$username'
                   OR student_email = '$username'
                   OR student_name = '$username'
                   LIMIT 1";
    $studentResult = mysqli_query($conn, $studentSql);

    if ($studentRow = mysqli_fetch_assoc($studentResult)) {
        if (password_match($password, $studentRow["student_password"])) {
            if (($studentRow["student_status"] ?? "Active") == "Inactive") {
                $error = "This account is inactive. Please contact the administrator.";
            } else {
            $_SESSION["user_id"] = $studentRow["studentID"];
            $_SESSION["user_name"] = $studentRow["student_name"];
            $_SESSION["account_role"] = $studentRow["student_role"];
            $_SESSION["profile_photo"] = $studentRow["profile_photo"] ?? "";

            if ($studentRow["student_role"] == "committee") {
                $_SESSION["role"] = "committee";
                header("Location: ../committee/dashboard.php");
            } else {
                $_SESSION["role"] = "student";
                header("Location: ../student/dashboard.php");
            }
            exit();
            }
        }
    }

    if ($error == "") {
        $error = "Invalid username/student ID/email or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login - FK Club System</title>
    <link rel="stylesheet" href="../assets/style.css">
    <script>
        // When browser Back restores this page from memory, reload it so PHP can check the current session.
        window.addEventListener("pageshow", function(event) {
            var nav = performance.getEntriesByType ? performance.getEntriesByType("navigation")[0] : null;
            if (event.persisted || (nav && nav.type === "back_forward")) {
                window.location.reload();
            }
        });
    </script>
</head>
<body class="login-body">
    <div class="login-box">
        <div class="logo">FK</div>
        <h1>FK Student Club & Event Management System</h1>
        <p>Faculty of Computing, UMPSA</p>

        <?php if ($error != "") { ?>
            <div class="alert alert-error"><?php echo clean($error); ?></div>
        <?php } ?>

        <form method="POST">
            <input class="form-control" type="text" name="username" placeholder="Enter student ID, email, or name" required>
            <input class="form-control" type="password" name="password" placeholder="Enter password" required>
            <button class="btn" style="width:100%;" type="submit" name="login">Login</button>
        </form>

        <p style="font-size:12px;margin-top:20px;">Admin: admin@umpsa.edu.my / admin123<br>Student: CB24070 / student123<br>Committee: CB24071 / student123<br><b>Note:</b> Committee users can switch between Committee and Student view after login.</p>
    </div>
</body>
</html>
