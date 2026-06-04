<?php
include("../config/session_check.php");
include("../config/dbconnect.php");

if ($_SESSION["role"] != "committee") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$registrationID = mysqli_real_escape_string($conn, $_GET["registrationID"]);
$eventID = mysqli_real_escape_string($conn, $_GET["eventID"]);
$status = mysqli_real_escape_string($conn, $_GET["status"]);

$pointRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM status_point WHERE description LIKE '%$status%' LIMIT 1"));

if ($status == "Present On Time") {
    $status_pointID = 1;
    $point = 10;
} elseif ($status == "Late Arrival") {
    $status_pointID = 2;
    $point = 5;
} elseif ($status == "Absent Without Notice") {
    $status_pointID = 3;
    $point = -10;
} else {
    $status_pointID = 4;
    $point = 5;
}

$reg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM event_registration WHERE registrationID='$registrationID'"));

if ($reg) {
    $studentID = $reg["studentID"];
    $eventID = $reg["eventID"];

    $sql = "INSERT INTO attendance
            (registrationID, studentID, eventID, status_pointID, attendance_status, checkin_time, point_earned)
            VALUES
            ('$registrationID', '$studentID', '$eventID', '$status_pointID', '$status', NOW(), '$point')
            ON DUPLICATE KEY UPDATE
            status_pointID='$status_pointID',
            attendance_status='$status',
            checkin_time=NOW(),
            point_earned='$point'";

    mysqli_query($conn, $sql);
}

header("Location: attendance.php?eventID=" . $eventID);
exit();
?>
