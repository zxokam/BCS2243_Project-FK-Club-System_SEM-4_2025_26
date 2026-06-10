<?php
include("../config/session_check.php");
include("../config/dbconnect.php");

if ($_SESSION["role"] != "committee") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$attendanceID = mysqli_real_escape_string($conn, $_GET["id"]);
$eventID = mysqli_real_escape_string($conn, $_GET["eventID"]);

mysqli_query($conn, "DELETE FROM attendance WHERE attendanceID='$attendanceID'");

header("Location: attendance.php?eventID=" . $eventID);
exit();
?>
