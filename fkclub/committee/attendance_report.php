<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "committee") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$single = mysqli_query($conn, "
    SELECT 
        e.eventID,
        e.event_title,
        e.event_date,
        SUM(CASE WHEN er.registration_status='Registered' THEN 1 ELSE 0 END) AS total_registered,
        SUM(CASE WHEN a.attendance_status='Present On Time' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN a.attendance_status='Late Arrival' THEN 1 ELSE 0 END) AS late_count,
        SUM(CASE WHEN a.attendance_status='Absent Without Notice' THEN 1 ELSE 0 END) AS absent_count,
        COALESCE(SUM(a.point_earned),0) AS total_points
    FROM event e
    LEFT JOIN event_registration er ON e.eventID = er.eventID
    LEFT JOIN attendance a ON er.registrationID = a.registrationID
    GROUP BY e.eventID, e.event_title, e.event_date
    ORDER BY e.event_date DESC
");

$join = mysqli_query($conn, "
    SELECT 
        s.studentID,
        s.student_name,
        e.event_title,
        e.event_date,
        a.checkin_time,
        a.attendance_status,
        a.point_earned
    FROM attendance a
    JOIN student s ON a.studentID = s.studentID
    JOIN event e ON a.eventID = e.eventID
    ORDER BY e.event_date DESC, s.student_name
");

$totalEvents = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM event"))["total"];
$totalStudents = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM student"))["total"];
$totalAttendance = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance"))["total"];
$totalPoints = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(point_earned),0) AS total FROM attendance"))["total"];

page_start("Attendance Report", "attendance_report");
?>
<div class="page-header">
    <div>
        <h1>Attendance Reports & Analytics</h1>
        <p class="subtitle">Single-table and join-table reports for Progress 2.</p>
    </div>
    <button class="btn btn-light" onclick="window.print()">Print Report</button>
</div>

<div class="grid grid-4">
    <?php stat_card("Total Events", $totalEvents, "📅", "blue"); ?>
    <?php stat_card("Total Students", $totalStudents, "👥", "green"); ?>
    <?php stat_card("Attendance Records", $totalAttendance, "✅", "orange"); ?>
    <?php stat_card("Total Points", $totalPoints, "⭐", "purple"); ?>
</div>

<br>

<div class="panel">
    <h3>Report 1: Attendance Summary by Event</h3>
    <table>
        <tr>
            <th>Event</th>
            <th>Date</th>
            <th>Registered</th>
            <th>Present</th>
            <th>Late</th>
            <th>Absent</th>
            <th>Total Points</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($single)) { ?>
        <tr>
            <td><b><?php echo clean($row["event_title"]); ?></b></td>
            <td><?php echo clean($row["event_date"]); ?></td>
            <td><?php echo clean($row["total_registered"]); ?></td>
            <td><?php echo clean($row["present_count"] ?: 0); ?></td>
            <td><?php echo clean($row["late_count"] ?: 0); ?></td>
            <td><?php echo clean($row["absent_count"] ?: 0); ?></td>
            <td><b><?php echo clean($row["total_points"]); ?></b></td>
        </tr>
        <?php } ?>
    </table>
</div>

<br>

<div class="panel">
    <h3>Report 2: Student Attendance Details</h3>
    <table>
        <tr>
            <th>Student</th>
            <th>Event</th>
            <th>Date</th>
            <th>Check-In</th>
            <th>Status</th>
            <th>Points</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($join)) { ?>
        <tr>
            <td class="user-cell"><span class="mini-avatar"><?php echo clean(initials($row["student_name"])); ?></span><?php echo clean($row["student_name"]); ?> (<?php echo clean($row["studentID"]); ?>)</td>
            <td><?php echo clean($row["event_title"]); ?></td>
            <td><?php echo clean($row["event_date"]); ?></td>
            <td><?php echo clean($row["checkin_time"]); ?></td>
            <td><span class="badge badge-green"><?php echo clean($row["attendance_status"]); ?></span></td>
            <td><?php echo clean($row["point_earned"]); ?></td>
        </tr>
        <?php } ?>
    </table>
</div>
<?php page_end(); ?>
