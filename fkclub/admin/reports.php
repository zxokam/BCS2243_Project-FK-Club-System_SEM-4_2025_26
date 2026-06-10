<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$event_report = mysqli_query($conn, "SELECT eventID, event_title, event_date, venue, max_participants, event_status FROM event ORDER BY event_date");

$join_report = mysqli_query($conn, "SELECT 
    event.event_title,
    club.club_name,
    student.student_name,
    event_registration.registration_status,
    event_registration.confirmation_status
FROM event_registration
JOIN event ON event_registration.eventID = event.eventID
JOIN club ON event.clubID = club.clubID
JOIN student ON event_registration.studentID = student.studentID
ORDER BY event.event_date");

$count_report = mysqli_query($conn, "SELECT
    club.club_name,
    COUNT(DISTINCT event.eventID) AS total_events,
    COUNT(event_registration.registrationID) AS total_registrations,
    COALESCE(SUM(attendance.point_earned), 0) AS total_points
FROM club
LEFT JOIN event ON club.clubID = event.clubID
LEFT JOIN event_registration ON event.eventID = event_registration.eventID
LEFT JOIN attendance ON event_registration.registrationID = attendance.registrationID
GROUP BY club.clubID, club.club_name
ORDER BY total_registrations DESC");

page_start("Reports", "reports");
?>
<div class="page-header">
    <div>
        <h1>Reports</h1>
        <p class="subtitle">Progress 2 report page: single-table, join-table, COUNT and SUM SQL.</p>
    </div>
</div>

<div class="panel">
    <h3>Single Table Report: Event List</h3>
    <table>
        <tr><th>Event</th><th>Date</th><th>Venue</th><th>Max</th><th>Status</th></tr>
        <?php while ($row = mysqli_fetch_assoc($event_report)) { ?>
        <tr>
            <td><?php echo clean($row["event_title"]); ?></td>
            <td><?php echo clean($row["event_date"]); ?></td>
            <td><?php echo clean($row["venue"]); ?></td>
            <td><?php echo clean($row["max_participants"]); ?></td>
            <td><span class="badge badge-green"><?php echo clean($row["event_status"]); ?></span></td>
        </tr>
        <?php } ?>
    </table>
</div>

<br>

<div class="panel">
    <h3>Join Table Report: Registrations by Event</h3>
    <table>
        <tr><th>Event</th><th>Club</th><th>Student</th><th>Registration</th><th>Confirmation</th></tr>
        <?php while ($row = mysqli_fetch_assoc($join_report)) { ?>
        <tr>
            <td><?php echo clean($row["event_title"]); ?></td>
            <td><?php echo clean($row["club_name"]); ?></td>
            <td><?php echo clean($row["student_name"]); ?></td>
            <td><?php echo clean($row["registration_status"]); ?></td>
            <td><?php echo clean($row["confirmation_status"]); ?></td>
        </tr>
        <?php } ?>
    </table>
</div>

<br>

<div class="panel">
    <h3>COUNT / SUM Report: Club Activity</h3>
    <table>
        <tr><th>Club</th><th>Total Events</th><th>Total Registrations</th><th>Total Points</th></tr>
        <?php while ($row = mysqli_fetch_assoc($count_report)) { ?>
        <tr>
            <td><?php echo clean($row["club_name"]); ?></td>
            <td><?php echo clean($row["total_events"]); ?></td>
            <td><?php echo clean($row["total_registrations"]); ?></td>
            <td><?php echo clean($row["total_points"]); ?></td>
        </tr>
        <?php } ?>
    </table>
</div>
<?php page_end(); ?>
