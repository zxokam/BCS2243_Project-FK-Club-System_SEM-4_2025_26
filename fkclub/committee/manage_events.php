<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "committee") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

ensure_waiting_list_schema($conn);

$id = $_SESSION["user_id"];

$sql = "SELECT event.*, club.club_name,
        SUM(CASE WHEN event_registration.registration_status = 'Registered' THEN 1 ELSE 0 END) AS total_registered,
        SUM(CASE WHEN event_registration.registration_status = 'Waiting List' THEN 1 ELSE 0 END) AS total_waiting
        FROM event
        JOIN club ON event.clubID = club.clubID
        LEFT JOIN event_registration ON event.eventID = event_registration.eventID
        WHERE event.created_by = '$id'
        GROUP BY event.eventID
        ORDER BY event.event_date DESC";
$result = mysqli_query($conn, $sql);

page_start("Manage Events", "manage_events");
?>
<div class="page-header">
    <div>
        <h1>Manage Events</h1>
        <p class="subtitle">Insert, view, update and delete events. When an event is full, create and manage the waiting list here.</p>
    </div>
    <a class="btn" href="create_event.php">+ Create Event</a>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Event</th>
            <th>Club</th>
            <th>Date</th>
            <th>Participants</th>
            <th>Waiting List</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)) {
            $registeredCount = (int)($row["total_registered"] ?? 0);
            $waitingCount = (int)($row["total_waiting"] ?? 0);
            $maxParticipants = (int)$row["max_participants"];
            $full = $maxParticipants > 0 && $registeredCount >= $maxParticipants;
            $waitingOpen = waiting_list_is_open($row);
        ?>
        <tr>
            <td><b><?php echo clean($row["event_title"]); ?></b><br><small><?php echo clean($row["venue"]); ?></small></td>
            <td><?php echo clean($row["club_name"]); ?></td>
            <td><?php echo clean($row["event_date"]); ?></td>
            <td><?php echo clean($registeredCount); ?> / <?php echo clean($row["max_participants"]); ?></td>
            <td>
                <span class="badge <?php echo $waitingOpen ? "badge-orange" : "badge-gray"; ?>">
                    <?php echo $waitingOpen ? "Open" : "Closed"; ?>
                </span>
                <br><small><?php echo clean($waitingCount); ?> waiting</small>
            </td>
            <td><span class="badge badge-green"><?php echo clean($row["event_status"]); ?></span></td>
            <td>
                <?php if ($full && !$waitingOpen) { ?>
                    <a class="btn btn-small" href="waiting_list.php?id=<?php echo clean($row["eventID"]); ?>">Create Waiting List</a>
                <?php } else { ?>
                    <a class="btn btn-light btn-small" href="waiting_list.php?id=<?php echo clean($row["eventID"]); ?>">Manage Waiting List</a>
                <?php } ?>
                <a class="btn btn-light btn-small" href="event_edit.php?id=<?php echo clean($row["eventID"]); ?>">Edit</a>
                <a class="btn btn-red btn-small" href="event_delete.php?id=<?php echo clean($row["eventID"]); ?>" onclick="return confirm('Delete this event?')">Delete</a>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>
<?php page_end(); ?>
