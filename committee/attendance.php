<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "committee") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$events = mysqli_query($conn, "SELECT * FROM event ORDER BY event_date DESC");
$eventID = isset($_GET["eventID"]) ? mysqli_real_escape_string($conn, $_GET["eventID"]) : "";

if ($eventID == "") {
    $first = mysqli_fetch_assoc(mysqli_query($conn, "SELECT eventID FROM event ORDER BY event_date DESC LIMIT 1"));
    $eventID = $first ? $first["eventID"] : 0;
}

$summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
        COUNT(event_registration.registrationID) AS registered,
        SUM(CASE WHEN attendance.attendance_status = 'Present On Time' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN attendance.attendance_status = 'Late Arrival' THEN 1 ELSE 0 END) AS late,
        SUM(CASE WHEN attendance.attendance_status = 'Absent Without Notice' THEN 1 ELSE 0 END) AS absent,
        SUM(CASE WHEN attendance.attendance_status = 'Volunteer Helper' THEN 1 ELSE 0 END) AS volunteer
        FROM event_registration
        LEFT JOIN attendance ON event_registration.registrationID = attendance.registrationID
        WHERE event_registration.eventID = '$eventID'
        AND event_registration.registration_status = 'Registered'"));

$participants = mysqli_query($conn, "SELECT 
        event_registration.registrationID,
        student.student_name,
        student.studentID,
        attendance.attendanceID,
        attendance.checkin_time,
        attendance.attendance_status,
        attendance.point_earned
        FROM event_registration
        JOIN student ON event_registration.studentID = student.studentID
        LEFT JOIN attendance ON event_registration.registrationID = attendance.registrationID
        WHERE event_registration.eventID = '$eventID'
        AND event_registration.registration_status = 'Registered'
        ORDER BY student.student_name");

page_start("Attendance", "attendance");
?>
<div class="page-header">
    <div>
        <h1>Attendance Management</h1>
        <p class="subtitle">Insert, view, update and delete attendance records.</p>
    </div>
</div>

<form class="toolbar" method="GET">
    <select class="form-control" style="margin-bottom:0;" name="eventID" onchange="this.form.submit()">
        <?php while ($event = mysqli_fetch_assoc($events)) { ?>
            <option value="<?php echo clean($event["eventID"]); ?>" <?php if ($eventID == $event["eventID"]) echo "selected"; ?>>
                <?php echo clean($event["event_title"]); ?>
            </option>
        <?php } ?>
    </select>
</form>

<div class="grid grid-4">
    <?php stat_card("Total Registered", $summary["registered"] ?? 0, "📅", "blue"); ?>
    <?php stat_card("Present", $summary["present"] ?? 0, "✅", "green"); ?>
    <?php stat_card("Late", $summary["late"] ?? 0, "⏰", "orange"); ?>
    <?php stat_card("Absent", $summary["absent"] ?? 0, "❌", "purple"); ?>
    <?php stat_card("Volunteer", $summary["volunteer"] ?? 0, "⭐", "yellow"); ?>
</div>

<br>

<div class="table-box">
    <table>
        <tr>
            <th>#</th>
            <th>Student Name</th>
            <th>Student ID</th>
            <th>Check-in Time</th>
            <th>Status</th>
            <th>Point</th>
            <th>Action</th>
        </tr>
        <?php $no = 1; while ($row = mysqli_fetch_assoc($participants)) { ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td class="user-cell">
                <span class="mini-avatar"><?php echo clean(initials($row["student_name"])); ?></span>
                <?php echo clean($row["student_name"]); ?>
            </td>
            <td><?php echo clean($row["studentID"]); ?></td>
            <td><?php echo $row["checkin_time"] ? clean(date("H:i", strtotime($row["checkin_time"]))) : "-"; ?></td>
            <td>
                <span class="badge <?php echo $row["attendance_status"] ? "badge-green" : "badge-gray"; ?>">
                    <?php echo clean($row["attendance_status"] ?: "Not marked"); ?>
                </span>
            </td>
            <td><?php echo clean($row["point_earned"] ?? "0"); ?></td>
            <td>
                <a class="btn btn-small" href="attendance_mark.php?registrationID=<?php echo clean($row["registrationID"]); ?>&eventID=<?php echo clean($eventID); ?>&status=Present On Time">Present</a>
                <a class="btn btn-light btn-small" href="attendance_mark.php?registrationID=<?php echo clean($row["registrationID"]); ?>&eventID=<?php echo clean($eventID); ?>&status=Late Arrival">Late</a>
                <a class="btn btn-light btn-small" href="attendance_mark.php?registrationID=<?php echo clean($row["registrationID"]); ?>&eventID=<?php echo clean($eventID); ?>&status=Volunteer Helper">Volunteer</a>

                <?php if ($row["attendanceID"]) { ?>
                    <a class="btn btn-light btn-small" href="attendance_delete.php?id=<?php echo clean($row["attendanceID"]); ?>&eventID=<?php echo clean($eventID); ?>" onclick="return confirm('Delete attendance record?')">Reset</a>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>
<?php page_end(); ?>
