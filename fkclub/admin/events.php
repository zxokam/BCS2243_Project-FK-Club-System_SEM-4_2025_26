<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "admin") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$sql = "SELECT event.*, club.club_name,
        SUM(CASE WHEN event_registration.registration_status = 'Registered' THEN 1 ELSE 0 END) AS total_registered,
        SUM(CASE WHEN event_registration.registration_status = 'Waiting List' THEN 1 ELSE 0 END) AS total_waiting
        FROM event
        JOIN club ON event.clubID = club.clubID
        LEFT JOIN event_registration ON event.eventID = event_registration.eventID
        GROUP BY event.eventID
        ORDER BY event.event_date DESC";
$result = mysqli_query($conn, $sql);

page_start("Manage Events", "events");
?>
<div class="page-header">
    <div>
        <h1>Event Management Dashboard</h1>
        <p class="subtitle">View events created by club committee members, including waiting list totals.</p>
    </div>
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
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><b><?php echo clean($row["event_title"]); ?></b><br><small><?php echo clean($row["venue"]); ?></small></td>
            <td><?php echo clean($row["club_name"]); ?></td>
            <td><?php echo clean($row["event_date"]); ?></td>
            <td><?php echo clean($row["total_registered"] ?? 0); ?> / <?php echo clean($row["max_participants"]); ?></td>
            <td><span class="badge <?php echo ($row["total_waiting"] ?? 0) > 0 ? "badge-orange" : "badge-gray"; ?>"><?php echo clean($row["total_waiting"] ?? 0); ?> waiting</span></td>
            <td><span class="badge badge-green"><?php echo clean($row["event_status"]); ?></span></td>
        </tr>
        <?php } ?>
    </table>
</div>
<?php page_end(); ?>
