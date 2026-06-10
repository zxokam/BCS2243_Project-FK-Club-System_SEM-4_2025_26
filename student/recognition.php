<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "student") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$id = $_SESSION["user_id"];

$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COALESCE(SUM(point_earned),0) AS total_points,
        COUNT(attendanceID) AS events_attended,
        SUM(CASE WHEN attendance_status='Volunteer Helper' THEN 1 ELSE 0 END) AS volunteer_times
    FROM attendance
    WHERE studentID='$id'
"));

$totalPoints = (int)$stats["total_points"];

$level = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT * FROM recognition_level
    WHERE $totalPoints BETWEEN min_point AND max_point
    LIMIT 1
"));

$rank = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) + 1 AS ranking
    FROM (
        SELECT studentID, COALESCE(SUM(point_earned),0) AS total_points
        FROM attendance
        GROUP BY studentID
        HAVING total_points > $totalPoints
    ) higher
"))["ranking"];

$leaderboard = mysqli_query($conn, "
    SELECT s.studentID, s.student_name, COALESCE(SUM(a.point_earned),0) AS total_points, COUNT(a.attendanceID) AS events_attended
    FROM student s
    LEFT JOIN attendance a ON s.studentID = a.studentID
    WHERE s.student_role IN ('student','committee')
    GROUP BY s.studentID, s.student_name
    ORDER BY total_points DESC, events_attended DESC
    LIMIT 10
");

$next = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT * FROM recognition_level
    WHERE min_point > $totalPoints
    ORDER BY min_point ASC
    LIMIT 1
"));

$nextPoints = $next ? (int)$next["min_point"] : $totalPoints;
$progress = $nextPoints > 0 ? min(100, round(($totalPoints / $nextPoints) * 100)) : 100;

page_start("Recognition", "recognition");
?>
<div class="page-header">
    <div>
        <h1>Participation & Recognition</h1>
        <p class="subtitle">Track your points, ranking and recognition level.</p>
    </div>
</div>

<div class="grid grid-4">
    <?php stat_card("Total Points", $totalPoints, "⭐", "blue"); ?>
    <?php stat_card("Events Attended", $stats["events_attended"], "📅", "green"); ?>
    <?php stat_card("Volunteer Times", $stats["volunteer_times"] ?: 0, "🤝", "orange"); ?>
    <?php stat_card("Current Rank", "#" . $rank, "🏆", "purple"); ?>
</div>

<br>

<div class="grid grid-2">
    <div class="panel">
        <h3>Recognition Level</h3>
        <h2><?php echo clean($level["level_name"] ?? "No Level"); ?></h2>
        <p class="subtitle"><?php echo clean($level["description"] ?? "Continue participating to unlock recognition."); ?></p>
        <br>
        <div style="height:12px;background:#e6edf5;border-radius:20px;overflow:hidden;">
            <div style="height:12px;width:<?php echo clean($progress); ?>%;background:#2563eb;border-radius:20px;"></div>
        </div>
        <p class="subtitle" style="margin-top:10px;">
            <?php if ($next) { ?>
                <?php echo clean($totalPoints); ?> / <?php echo clean($nextPoints); ?> points to <?php echo clean($next["level_name"]); ?>
            <?php } else { ?>
                Maximum level achieved.
            <?php } ?>
        </p>
    </div>

    <div class="panel">
        <h3>Top Participants</h3>
        <table>
            <tr><th>Rank</th><th>Student</th><th>Points</th><th>Events</th></tr>
            <?php $no=1; while ($row = mysqli_fetch_assoc($leaderboard)) { ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td class="user-cell"><span class="mini-avatar"><?php echo clean(initials($row["student_name"])); ?></span><?php echo clean($row["student_name"]); ?></td>
                <td><b><?php echo clean($row["total_points"]); ?></b></td>
                <td><?php echo clean($row["events_attended"]); ?></td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>
<?php page_end(); ?>
