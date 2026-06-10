<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/functions.php");

ensure_waiting_list_schema($conn);

if ($_SESSION["role"] != "student") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$studentID = $_SESSION["user_id"];
$registrationID = mysqli_real_escape_string($conn, $_GET["id"]);

$registration = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM event_registration WHERE registrationID = '$registrationID' AND studentID = '$studentID' LIMIT 1"));

if ($registration) {
    $eventID = mysqli_real_escape_string($conn, $registration["eventID"]);
    $oldStatus = $registration["registration_status"];

    $sql = "UPDATE event_registration 
            SET registration_status = 'Cancelled',
                confirmation_status = 'Rejected',
                queue_number = 0
            WHERE registrationID = '$registrationID'
            AND studentID = '$studentID'";

    mysqli_query($conn, $sql);

    // Waiting list is now controlled by the committee.
    // Cancelling a confirmed slot will not automatically promote another student.
    resequence_waiting_list($conn, $eventID);
}

header("Location: my_registrations.php");
exit();
?>
