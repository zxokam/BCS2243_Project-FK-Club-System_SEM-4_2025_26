<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "student") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$id = $_SESSION["user_id"];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM student WHERE studentID = '$id'"));

page_start("Student Profile", "profile");
?>
<div class="page-header">
    <div>
        <h1>My Profile</h1>
        <p class="subtitle">Student profile details.</p>
    </div>
</div>

<div class="panel" style="max-width:700px;text-align:center;">
    <div class="avatar" style="width:80px;height:80px;margin:auto;font-size:24px;"><?php echo clean(initials($user["student_name"])); ?></div>
    <h2 style="margin-top:15px;"><?php echo clean($user["student_name"]); ?></h2>
    <span class="badge badge-green">Student</span>
    <br><br>
    <p><b>Student ID:</b> <?php echo clean($user["studentID"]); ?></p>
    <p><b>Email:</b> <?php echo clean($user["student_email"]); ?></p>
    <p><b>Phone:</b> <?php echo clean($user["student_phone"]); ?></p>
    <p><b>Program:</b> <?php echo clean($user["program"]); ?></p>
</div>
<?php page_end(); ?>
