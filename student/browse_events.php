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

$clubFilterSql = "SELECT DISTINCT club.clubID, club.club_name
        FROM event
        JOIN club ON event.clubID = club.clubID
        WHERE event.event_status = 'Open'
        ORDER BY club.club_name ASC";
$clubFilterResult = mysqli_query($conn, $clubFilterSql);

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
    <input class="form-control" style="margin-bottom:0;" id="eventSearch" oninput="filterEventCards()" placeholder="Search by event name, club or description...">
    <button type="button" class="btn btn-light" onclick="toggleEventFilters()">Filters</button>
</div>

<div class="panel event-filter-panel" id="eventFilterPanel" style="display:none; margin-bottom:16px;">
    <div class="filter-grid">
        <div>
            <label><b>Club</b></label>
            <select class="form-control" id="eventClubFilter" onchange="filterEventCards()">
                <option value="all">All clubs</option>
                <?php while ($clubFilterRow = mysqli_fetch_assoc($clubFilterResult)) { ?>
                    <option value="<?php echo clean($clubFilterRow["clubID"]); ?>"><?php echo clean($clubFilterRow["club_name"]); ?></option>
                <?php } ?>
            </select>
        </div>
        <div>
            <label><b>Status</b></label>
            <select class="form-control" id="eventStatusFilter" onchange="filterEventCards()">
                <option value="all">All event status</option>
                <option value="open">Open only</option>
                <option value="full">Full only</option>
                <option value="waiting">Waiting list open</option>
            </select>
        </div>
        <div>
            <label><b>Date</b></label>
            <select class="form-control" id="eventDateFilter" onchange="filterEventCards()">
                <option value="all">All dates</option>
                <option value="upcoming">Upcoming / today</option>
                <option value="today">Today only</option>
                <option value="past">Past only</option>
            </select>
        </div>
        <div class="filter-actions">
            <button type="button" class="btn btn-light" onclick="clearEventFilters()">Clear Filters</button>
        </div>
    </div>
</div>

<div id="eventNoResults" class="alert alert-error" style="display:none;">No events match your search or filters.</div>

<div class="grid grid-3" id="eventList">
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
    <div class="event-card <?php echo $cardClass; ?>"
         data-search="<?php echo clean($row["event_title"] . " " . $row["club_name"] . " " . $row["event_description"] . " " . $row["venue"]); ?>"
         data-club="<?php echo clean($row["clubID"]); ?>"
         data-status="<?php echo clean($full && $waitingOpen ? "waiting" : ($full ? "full" : "open")); ?>"
         data-date="<?php echo clean($row["event_date"]); ?>">
        <span class="badge <?php echo $badgeClass; ?>"><?php echo clean($badgeText); ?></span>
        <h3><?php echo clean($row["event_title"]); ?></h3>
        <p class="subtitle"><?php echo clean($row["event_description"]); ?></p>
        <div class="event-meta">
            📅 <?php echo clean($row["event_date"]); ?><br>
            ⏰ <?php echo clean(substr($row["start_time"], 0, 5)); ?> - <?php echo clean(substr($row["end_time"], 0, 5)); ?><br>
            🏛️ <?php echo clean($row["club_name"]); ?><br>
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
