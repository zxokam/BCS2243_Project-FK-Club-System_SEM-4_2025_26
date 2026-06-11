<?php
include("../config/session_check.php");
include("../config/dbconnect.php");

if ($_SESSION["role"] != "admin") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET["id"] ?? "");
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT student_status FROM student WHERE studentID = '$id' LIMIT 1"));

if ($user) {
    $current_status = $user["student_status"] ?? "Active";
    $new_status = ($current_status == "Active") ? "Inactive" : "Active";
    mysqli_query($conn, "UPDATE student SET student_status = '$new_status', updated_at = NOW() WHERE studentID = '$id'");
}

header("Location: users.php");
exit();
?>
