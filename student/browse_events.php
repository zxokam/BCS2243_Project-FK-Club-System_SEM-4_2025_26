<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "student") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

ensure_waiting_list_schema($conn);

$studentID = $_SESSION["user_id"];

$sql = "SELECT event.*, club.club_name,
        SUM(CASE WHEN event_registration.registration_status = 'Registered' THEN 1 ELSE 0 END) AS total_registered,
        SUM(CASE WHEN event_registration.registration_status = 'Waiting List' THEN 1 ELSE 0 END) AS total_waiting
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
        <p class="subtitle">Search and register for available club events. Full events only accept waiting-list registration after the committee creates the waiting list.</p>
    </div>
</div>

<?php if (($_GET["notice"] ?? "") == "waiting_closed") { ?>
    <div class="alert alert-error">This event is full and the committee has not opened the waiting list yet.</div>
<?php } ?>

<div class="toolbar">
    <input class="form-control" style="margin-bottom:0;" placeholder="Search by event name, club or description...">
    <button class="btn btn-light">Filters</button>
</div>

<div class="grid grid-3">
    <?php while ($row = mysqli_fetch_assoc($result)) {
        $registeredCount = (int)($row["total_registered"] ?? 0);
        $waitingCount = (int)($row["total_waiting"] ?? 0);
        $full = $row["max_participants"] > 0 && $registeredCount >= $row["max_participants"];
        $waitingOpen = waiting_list_is_open($row);
        $eventID = $row["eventID"];
        $registered = mysqli_fetch_assoc(mysqli_query($conn, "SELECT registrationID, registration_status, confirmation_status, queue_number FROM event_registration WHERE studentID='$studentID' AND eventID='$eventID' ORDER BY registrationID DESC LIMIT 1"));
        $activeRegistration = $registered && !in_array($registered["registration_status"], ["Cancelled", "Rejected"]);

        if ($full && $waitingOpen) {
            $badgeText = "WAITING LIST OPEN";
            $badgeClass = "badge-orange";
            $cardClass = "full";
        } elseif ($full) {
            $badgeText = "FULL";
            $badgeClass = "badge-red";
            $cardClass = "full";
        } else {
            $badgeText = "OPEN";
            $badgeClass = "badge-green";
            $cardClass = "";
        }
    ?>
    <div class="event-card <?php echo $cardClass; ?>">
        <span class="badge <?php echo $badgeClass; ?>"><?php echo clean($badgeText); ?></span>
        <h3><?php echo clean($row["event_title"]); ?></h3>
        <p class="subtitle"><?php echo clean($row["event_description"]); ?></p>
        <div class="event-meta">
            📅 <?php echo clean($row["event_date"]); ?><br>
            ⏰ <?php echo clean(substr($row["start_time"], 0, 5)); ?> - <?php echo clean(substr($row["end_time"], 0, 5)); ?><br>
            📍 <?php echo clean($row["venue"]); ?><br>
            👥 <?php echo clean($registeredCount); ?> / <?php echo clean($row["max_participants"]); ?> registered<br>
            🧾 <?php echo clean($waitingCount); ?> waiting
        </div>
        <br>

        <?php if ($activeRegistration) { ?>
            <?php if ($registered["registration_status"] == "Waiting List") { ?>
                <a class="btn btn-light" style="width:100%;" href="my_registrations.php">Waiting List #<?php echo clean($registered["queue_number"] ?: "-"); ?></a>
            <?php } else { ?>
                <a class="btn btn-light" style="width:100%;" href="my_registrations.php">Already Registered</a>
            <?php } ?>
        <?php } elseif ($full && !$waitingOpen) { ?>
            <button class="btn btn-light" style="width:100%;opacity:.65;cursor:not-allowed;" disabled>Event Full</button>
        <?php } else { ?>
            <a class="btn" style="width:100%;" 
               href="register_event.php?eventID=<?php echo clean($row["eventID"]); ?>"
               onclick="return confirm('<?php echo $full ? 'Event is full. Join the waiting list?' : 'Confirm registration for this event?'; ?>')">
               <?php echo $full ? 'Join Waiting List' : 'Register / Confirm'; ?>
            </a>
        <?php } ?>
    </div>
    <?php } ?>
</div>
<?php page_end(); ?>
