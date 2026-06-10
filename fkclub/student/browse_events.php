<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "student") {
    header("Location: ../auth/login.php");
    exit();
}

$studentID = $_SESSION["user_id"];

$sql = "SELECT event.*, club.club_name, COUNT(event_registration.registrationID) AS total_registered
        FROM event
        JOIN club ON event.clubID = club.clubID
        LEFT JOIN event_registration ON event.eventID = event_registration.eventID
        WHERE event.event_status = 'Open'
        GROUP BY event.eventID
        ORDER BY event.event_date ASC";
$result = mysqli_query($conn, $sql);

page_start("Browse Events", "browse_events");
?>
<div class="page-header">
    <div>
        <h1>Browse Events</h1>
        <p class="subtitle">Search and register for available club events.</p>
    </div>
</div>

<div class="toolbar">
    <input class="form-control" style="margin-bottom:0;" placeholder="Search by event name, club or description...">
    <button class="btn btn-light">Filters</button>
</div>

<div class="grid grid-3">
    <?php while ($row = mysqli_fetch_assoc($result)) {
        $full = $row["max_participants"] > 0 && $row["total_registered"] >= $row["max_participants"];
        $eventID = $row["eventID"];
        $registered = mysqli_fetch_assoc(mysqli_query($conn, "SELECT registrationID, registration_status FROM event_registration WHERE studentID='$studentID' AND eventID='$eventID'"));
    ?>
    <div class="event-card <?php if ($full) echo 'full'; ?>">
        <span class="badge <?php echo $full ? 'badge-red' : 'badge-green'; ?>"><?php echo $full ? 'FULL' : 'OPEN'; ?></span>
        <h3><?php echo clean($row["event_title"]); ?></h3>
        <p class="subtitle"><?php echo clean($row["event_description"]); ?></p>
        <div class="event-meta">
            📅 <?php echo clean($row["event_date"]); ?><br>
            ⏰ <?php echo clean(substr($row["start_time"], 0, 5)); ?> - <?php echo clean(substr($row["end_time"], 0, 5)); ?><br>
            📍 <?php echo clean($row["venue"]); ?><br>
            👥 <?php echo clean($row["total_registered"]); ?> / <?php echo clean($row["max_participants"]); ?> registered
        </div>
        <br>

        <?php if ($registered) { ?>
            <a class="btn btn-light" style="width:100%;" href="my_registrations.php">Already Registered</a>
        <?php } else { ?>
            <a class="btn" style="width:100%;" 
               href="register_event.php?eventID=<?php echo clean($row["eventID"]); ?>"
               onclick="return confirm('Confirm registration for this event?')">
               Register / Confirm
            </a>
        <?php } ?>
    </div>
    <?php } ?>
</div>
<?php page_end(); ?>
