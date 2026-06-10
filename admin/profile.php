<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "admin") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$id = $_SESSION["user_id"];
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admin WHERE adminID = '$id'"));

page_start("Profile", "profile");
?>
<div class="page-header">
    <div>
        <h1>My Profile</h1>
        <p class="subtitle">Administrator profile.</p>
    </div>
</div>

<div class="panel" style="max-width:700px;text-align:center;">
    <div class="avatar" style="width:80px;height:80px;margin:auto;font-size:24px;"><?php echo clean(initials($admin["admin_name"])); ?></div>
    <h2 style="margin-top:15px;"><?php echo clean($admin["admin_name"]); ?></h2>
    <p class="subtitle"><?php echo clean($admin["department"]); ?></p>
    <br>
    <p><b>Email:</b> <?php echo clean($admin["admin_email"]); ?></p>
    <p><b>Phone:</b> <?php echo clean($admin["admin_phone"]); ?></p>
</div>
<?php page_end(); ?>
