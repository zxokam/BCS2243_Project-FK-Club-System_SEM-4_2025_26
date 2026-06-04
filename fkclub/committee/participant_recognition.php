<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "committee") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$leaderboard = mysqli_query($conn, "
    SELECT 
        s.studentID,
        s.student_name,
        s.program,
        COALESCE(SUM(a.point_earned),0) AS total_points,
        COUNT(a.attendanceID) AS events_attended,
        SUM(CASE WHEN a.attendance_status='Volunteer Helper' THEN 1 ELSE 0 END) AS volunteer_times
    FROM student s
    LEFT JOIN attendance a ON s.studentID = a.studentID
    WHERE s.student_role IN ('student','committee')
    GROUP BY s.studentID, s.student_name, s.program
    ORDER BY total_points DESC, events_attended DESC
");

function recognition_name($points) {
    if ($points >= 80) return "Outstanding Participant";
    if ($points >= 50) return "Active Student Award";
    if ($points >= 20) return "Participation Certificate";
    return "Warning / Reminder";
}

page_start("Participant Recognition", "participants");
?>
<div class="page-header">
    <div>
        <h1>Participant Recognition</h1>
        <p class="subtitle">Leaderboard and recognition level based on attendance points.</p>
    </div>
</div>

<div class="toolbar">
    <input class="form-control" style="margin-bottom:0;" id="leaderSearch" onkeyup="filterTable('leaderSearch','leaderTable')" placeholder="Search student name, ID or program...">
</div>

<div class="table-box">
    <table id="leaderTable">
        <thead>
        <tr>
            <th>Rank</th>
            <th>Student</th>
            <th>Program</th>
            <th>Events</th>
            <th>Volunteer</th>
            <th>Points</th>
            <th>Recognition</th>
        </tr>
        </thead>
        <tbody>
        <?php $rank=1; while ($row = mysqli_fetch_assoc($leaderboard)) { ?>
        <tr>
            <td><b>#<?php echo $rank++; ?></b></td>
            <td class="user-cell"><span class="mini-avatar"><?php echo clean(initials($row["student_name"])); ?></span><?php echo clean($row["student_name"]); ?> (<?php echo clean($row["studentID"]); ?>)</td>
            <td><?php echo clean($row["program"]); ?></td>
            <td><?php echo clean($row["events_attended"]); ?></td>
            <td><?php echo clean($row["volunteer_times"] ?: 0); ?></td>
            <td><b><?php echo clean($row["total_points"]); ?></b></td>
            <td><span class="badge badge-blue"><?php echo clean(recognition_name($row["total_points"])); ?></span></td>
        </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<script src="../assets/script.js"></script>
<?php page_end(); ?>
