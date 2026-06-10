<?php
include("../config/session_check.php");
include("../config/dbconnect.php");

if ($_SESSION["role"] != "student") {
    header("Location: ../auth/login.php");
    exit();
}

$studentID = $_SESSION["user_id"];
$eventID = mysqli_real_escape_string($conn, $_GET["eventID"]);

$check = mysqli_query($conn, "SELECT * FROM event_registration 
                              WHERE studentID = '$studentID' 
                              AND eventID = '$eventID'");

if (mysqli_num_rows($check) > 0) {
    header("Location: my_registrations.php");
    exit();
}

$event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT max_participants FROM event WHERE eventID = '$eventID'"));

$count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total 
                                                 FROM event_registration 
                                                 WHERE eventID = '$eventID'
                                                 AND registration_status = 'Registered'"));

if ($count["total"] >= $event["max_participants"]) {
    $status = "Waiting List";
    $confirmation = "Pending";
    $queue = $count["total"] + 1;
} else {
    $status = "Registered";
    $confirmation = "Confirmed";
    $queue = $count["total"] + 1;
}

$sql = "INSERT INTO event_registration
        (studentID, eventID, registration_status, confirmation_status, queue_number)
        VALUES
        ('$studentID', '$eventID', '$status', '$confirmation', '$queue')";

mysqli_query($conn, $sql);

header("Location: my_registrations.php");
exit();
?>
