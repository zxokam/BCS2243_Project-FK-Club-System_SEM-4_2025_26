<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/functions.php");

if ($_SESSION["role"] != "student") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

ensure_waiting_list_schema($conn);

$studentID = $_SESSION["user_id"];
$eventID = mysqli_real_escape_string($conn, $_GET["eventID"]);

$event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT eventID, max_participants, event_status, waiting_list_status FROM event WHERE eventID = '$eventID' LIMIT 1"));

if (!$event || $event["event_status"] != "Open") {
    header("Location: browse_events.php");
    exit();
}

$check = mysqli_query($conn, "SELECT * FROM event_registration 
                              WHERE studentID = '$studentID' 
                              AND eventID = '$eventID'
                              ORDER BY registrationID DESC
                              LIMIT 1");
$existing = mysqli_fetch_assoc($check);

if ($existing && !in_array($existing["registration_status"], ["Cancelled", "Rejected"])) {
    header("Location: my_registrations.php");
    exit();
}

$summary = registration_summary($conn, $eventID);
$max = (int)$event["max_participants"];
$full = $max > 0 && $summary["registered"] >= $max;
$waitingOpen = waiting_list_is_open($event);

if ($full && !$waitingOpen) {
    header("Location: browse_events.php?notice=waiting_closed");
    exit();
}

if ($full) {
    $status = "Waiting List";
    $confirmation = "Pending";
    $queue = $summary["waiting"] + 1;
} else {
    $status = "Registered";
    $confirmation = "Confirmed";
    $queue = 0;
}

if ($existing && in_array($existing["registration_status"], ["Cancelled", "Rejected"])) {
    $registrationID = mysqli_real_escape_string($conn, $existing["registrationID"]);
    $sql = "UPDATE event_registration
            SET registration_status = '$status',
                confirmation_status = '$confirmation',
                queue_number = '$queue',
                registration_date = NOW()
            WHERE registrationID = '$registrationID'
            AND studentID = '$studentID'";
} else {
    $sql = "INSERT INTO event_registration
            (studentID, eventID, registration_status, confirmation_status, queue_number)
            VALUES
            ('$studentID', '$eventID', '$status', '$confirmation', '$queue')";
}

mysqli_query($conn, $sql);
resequence_waiting_list($conn, $eventID);

header("Location: my_registrations.php");
exit();
?>
