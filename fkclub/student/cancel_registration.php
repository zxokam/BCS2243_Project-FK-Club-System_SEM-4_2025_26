<?php
include("../config/session_check.php");
include("../config/dbconnect.php");

if ($_SESSION["role"] != "student") {
    header("Location: ../auth/login.php");
    exit();
}

$studentID = $_SESSION["user_id"];
$registrationID = mysqli_real_escape_string($conn, $_GET["id"]);

$sql = "UPDATE event_registration 
        SET registration_status = 'Cancelled',
            confirmation_status = 'Rejected'
        WHERE registrationID = '$registrationID'
        AND studentID = '$studentID'";

mysqli_query($conn, $sql);

header("Location: my_registrations.php");
exit();
?>
