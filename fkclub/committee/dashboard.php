<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "committee") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$id = $_SESSION["user_id"];

$total_events = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM event WHERE created_by = '$id'"))["total"];
$total_participants = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(event_registration.registrationID) AS total
                            FROM event_registration
                            JOIN event ON event_registration.eventID = event.eventID
                            WHERE event.created_by = '$id'
                            AND event_registration.registration_status = 'Registered'"))["total"];
$total_waiting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(event_registration.registrationID) AS total
                            FROM event_registration
                            JOIN event ON event_registration.eventID = event.eventID
                            WHERE event.created_by = '$id'
                            AND event_registration.registration_status = 'Waiting List'"))["total"];

page_start("Committee Dashboard", "dashboard");
?>
<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p class="subtitle">Overview of your club events and activities.</p>
    </div>
</div>

<div class="grid grid-4">
    <?php stat_card("Total Events", $total_events, "📅", "blue"); ?>
    <?php stat_card("Confirmed Participants", $total_participants, "👥", "green"); ?>
    <?php stat_card("Waiting List", $total_waiting, "🧾", "purple"); ?>
    <?php stat_card("Attendance", "View", "✅", "orange"); ?>
</div>

<br>

<div class="panel">
    <h3>Recent Events</h3>
    <p class="subtitle">Create and manage club events using the menu.</p>
    <br>
    <a class="btn" href="create_event.php">Create Event</a>
    <a class="btn btn-light" href="manage_events.php">Manage Events</a>
</div>
<?php page_end(); ?>
