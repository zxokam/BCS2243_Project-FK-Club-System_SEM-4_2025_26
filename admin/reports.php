<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "admin") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$keyword = "";
$status_filter = "";

if (isset($_GET["keyword"])) {
    $keyword = mysqli_real_escape_string($conn, trim($_GET["keyword"]));
}

if (isset($_GET["status_filter"])) {
    $status_filter = mysqli_real_escape_string($conn, trim($_GET["status_filter"]));
}

$event_where = "WHERE 1=1";
$join_where = "WHERE 1=1";

if ($keyword != "") {
    $event_where .= " AND (
        event.event_title LIKE '%$keyword%' OR
        event.venue LIKE '%$keyword%' OR
        club.club_name LIKE '%$keyword%'
    )";

    $join_where .= " AND (
        event.event_title LIKE '%$keyword%' OR
        club.club_name LIKE '%$keyword%' OR
        student.student_name LIKE '%$keyword%' OR
        event_registration.registration_status LIKE '%$keyword%'
    )";
}

if ($status_filter != "") {
    $event_where .= " AND event.event_status = '$status_filter'";
    $join_where .= " AND event.event_status = '$status_filter'";
}

$event_report = mysqli_query($conn, "
    SELECT event.eventID, event.event_title, event.event_date, event.venue,
           event.max_participants, event.event_status, club.club_name
    FROM event
    LEFT JOIN club ON event.clubID = club.clubID
    $event_where
    ORDER BY event.event_date DESC
");

$join_report = mysqli_query($conn, "
    SELECT
        event.event_title,
        event.event_date,
        club.club_name,
        student.student_name,
        event_registration.registration_status,
        event_registration.confirmation_status,
        event_registration.queue_number
    FROM event_registration
    JOIN event ON event_registration.eventID = event.eventID
    JOIN club ON event.clubID = club.clubID
    JOIN student ON event_registration.studentID = student.studentID
    $join_where
    ORDER BY event.event_date DESC, event_registration.registration_date DESC
");

$count_report = mysqli_query($conn, "
    SELECT
        club.club_name,
        COUNT(DISTINCT event.eventID) AS total_events,
        SUM(CASE WHEN event_registration.registration_status = 'Registered' THEN 1 ELSE 0 END) AS total_registrations,
        SUM(CASE WHEN event_registration.registration_status = 'Waiting List' THEN 1 ELSE 0 END) AS total_waiting,
        COALESCE(SUM(attendance.point_earned), 0) AS total_points
    FROM club
    LEFT JOIN event ON club.clubID = event.clubID
    LEFT JOIN event_registration ON event.eventID = event_registration.eventID
    LEFT JOIN attendance ON event_registration.registrationID = attendance.registrationID
    GROUP BY club.clubID, club.club_name
    ORDER BY total_registrations DESC
");

$overall = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(DISTINCT event.eventID) AS total_events,
        COUNT(DISTINCT event_registration.registrationID) AS total_registration_records,
        SUM(CASE WHEN event_registration.registration_status = 'Registered' THEN 1 ELSE 0 END) AS confirmed_participants,
        SUM(CASE WHEN event_registration.registration_status = 'Waiting List' THEN 1 ELSE 0 END) AS waiting_list_total,
        COALESCE(SUM(attendance.point_earned), 0) AS total_points
    FROM event
    LEFT JOIN event_registration ON event.eventID = event_registration.eventID
    LEFT JOIN attendance ON event_registration.registrationID = attendance.registrationID
"));

page_start("Reports", "reports");
?>

<div class="page-header">
    <div>
        <h1>Reports</h1>
        <p class="subtitle">Report page with search, single-table data, join-table data and SQL COUNT / SUM summaries.</p>
    </div>
</div>

<div class="grid grid-4">
    <?php stat_card("Events", $overall["total_events"] ?? 0, "📅", "purple"); ?>
    <?php stat_card("Registrations", $overall["total_registration_records"] ?? 0, "🧾", "blue"); ?>
    <?php stat_card("Participants", $overall["confirmed_participants"] ?? 0, "👥", "green"); ?>
    <?php stat_card("Total Points", $overall["total_points"] ?? 0, "⭐", "orange"); ?>
</div>

<br>

<div class="panel">
    <h3>Search Reports</h3>
    <form method="GET" style="display:grid; grid-template-columns:2fr 1fr auto auto; gap:12px; align-items:end;">
        <div>
            <label>Keyword</label>
            <input class="form-control" type="text" name="keyword" placeholder="Search event, club, student or venue" value="<?php echo clean($keyword); ?>">
        </div>

        <div>
            <label>Event Status</label>
            <select class="form-control" name="status_filter">
                <option value="">All Status</option>
                <option value="Open" <?php echo $status_filter == "Open" ? "selected" : ""; ?>>Open</option>
                <option value="Closed" <?php echo $status_filter == "Closed" ? "selected" : ""; ?>>Closed</option>
                <option value="Cancelled" <?php echo $status_filter == "Cancelled" ? "selected" : ""; ?>>Cancelled</option>
            </select>
        </div>

        <button class="btn" type="submit">Search</button>
        <a class="btn btn-light" href="reports.php">Reset</a>
    </form>
</div>

<br>

<div class="panel">
    <h3>Single Table Report: Event List</h3>
    <table>
        <tr>
            <th>Event</th>
            <th>Club</th>
            <th>Date</th>
            <th>Venue</th>
            <th>Max</th>
            <th>Status</th>
        </tr>
        <?php if (mysqli_num_rows($event_report) > 0) { ?>
            <?php while ($row = mysqli_fetch_assoc($event_report)) { ?>
            <tr>
                <td><?php echo clean($row["event_title"]); ?></td>
                <td><?php echo clean($row["club_name"]); ?></td>
                <td><?php echo clean($row["event_date"]); ?></td>
                <td><?php echo clean($row["venue"]); ?></td>
                <td><?php echo clean($row["max_participants"]); ?></td>
                <td><span class="badge badge-green"><?php echo clean($row["event_status"]); ?></span></td>
            </tr>
            <?php } ?>
        <?php } else { ?>
            <tr><td colspan="6" style="text-align:center;color:#718096;">No event records found.</td></tr>
        <?php } ?>
    </table>
</div>

<br>

<div class="panel">
    <h3>Join Table Report: Registrations by Event</h3>
    <table>
        <tr>
            <th>Event</th>
            <th>Date</th>
            <th>Club</th>
            <th>Student</th>
            <th>Registration</th>
            <th>Confirmation</th>
            <th>Queue</th>
        </tr>
        <?php if (mysqli_num_rows($join_report) > 0) { ?>
            <?php while ($row = mysqli_fetch_assoc($join_report)) { ?>
            <tr>
                <td><?php echo clean($row["event_title"]); ?></td>
                <td><?php echo clean($row["event_date"]); ?></td>
                <td><?php echo clean($row["club_name"]); ?></td>
                <td><?php echo clean($row["student_name"]); ?></td>
                <td><?php echo clean($row["registration_status"]); ?></td>
                <td><?php echo clean($row["confirmation_status"]); ?></td>
                <td><?php echo clean($row["queue_number"] ?? "-"); ?></td>
            </tr>
            <?php } ?>
        <?php } else { ?>
            <tr><td colspan="7" style="text-align:center;color:#718096;">No registration records found.</td></tr>
        <?php } ?>
    </table>
</div>

<br>

<div class="panel">
    <h3>COUNT / SUM Report: Club Activity</h3>
    <table>
        <tr>
            <th>Club</th>
            <th>Total Events</th>
            <th>Registered</th>
            <th>Waiting List</th>
            <th>Total Points</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($count_report)) { ?>
        <tr>
            <td><?php echo clean($row["club_name"]); ?></td>
            <td><?php echo clean($row["total_events"]); ?></td>
            <td><?php echo clean($row["total_registrations"] ?? 0); ?></td>
            <td><?php echo clean($row["total_waiting"] ?? 0); ?></td>
            <td><?php echo clean($row["total_points"]); ?></td>
        </tr>
        <?php } ?>
    </table>
</div>

<?php page_end(); ?>
