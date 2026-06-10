<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "student") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$studentID = $_SESSION["user_id"];
$clubID = mysqli_real_escape_string($conn, $_GET["id"]);

$club = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT c.*, a.advisor_name AS real_advisor, a.advisor_email
    FROM club c
    LEFT JOIN advisor a ON c.advisorID = a.advisorID
    WHERE c.clubID='$clubID'
    AND c.club_status = 'Active'
"));

if (!$club) {
    header("Location: club_directory.php");
    exit();
}

$committee = mysqli_query($conn, "
    SELECT s.student_name, s.studentID, p.position_name, ec.committee_tenure
    FROM event_committee ec
    JOIN student s ON ec.studentID = s.studentID
    LEFT JOIN `position` p ON ec.positionID = p.positionID
    WHERE ec.clubID='$clubID'
    ORDER BY p.positionID
");

$members = mysqli_query($conn, "
    SELECT s.student_name, s.studentID, m.membership_status, p.position_name
    FROM membership m
    JOIN student s ON m.studentID = s.studentID
    LEFT JOIN `position` p ON m.positionID = p.positionID
    WHERE m.clubID='$clubID'
    ORDER BY s.student_name
");

$membership = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM membership WHERE studentID='$studentID' AND clubID='$clubID'"));

page_start("Club Details", "club_directory");
?>
<div class="page-header">
    <div>
        <h1><?php echo clean($club["club_name"]); ?></h1>
        <p class="subtitle"><?php echo clean($club["club_category"]); ?> · <?php echo clean($club["club_status"]); ?></p>
    </div>
    <a class="btn btn-light" href="club_directory.php">← Back</a>
</div>

<div class="grid grid-2">
    <div class="panel">
        <h3>Club Information</h3>
        <p><?php echo clean($club["club_description"]); ?></p>
        <br>
        <p><b>Advisor:</b> <?php echo clean($club["real_advisor"] ?: $club["advisor_name"]); ?></p>
        <p><b>Advisor Email:</b> <?php echo clean($club["advisor_email"]); ?></p>
        <p><b>Status:</b> <span class="badge <?php echo $club["club_status"] == "Active" ? "badge-green" : "badge-red"; ?>"><?php echo clean($club["club_status"]); ?></span></p>
        <br>
        <?php if ($membership) { ?>
            <button class="btn btn-light" disabled>Membership: <?php echo clean($membership["membership_status"]); ?></button>
        <?php } else { ?>
            <a class="btn" href="join_club.php?id=<?php echo clean($clubID); ?>" onclick="return confirmAction('Apply to join this club?')">Join Club</a>
        <?php } ?>
    </div>

    <div class="panel">
        <h3>Current Committee</h3>
        <table>
            <tr><th>Name</th><th>Position</th><th>Tenure</th></tr>
            <?php while ($row = mysqli_fetch_assoc($committee)) { ?>
            <tr>
                <td><?php echo clean($row["student_name"]); ?><br><small><?php echo clean($row["studentID"]); ?></small></td>
                <td><span class="badge badge-blue"><?php echo clean($row["position_name"] ?: "Committee"); ?></span></td>
                <td><?php echo clean($row["committee_tenure"]); ?></td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>

<br>

<div class="panel">
    <h3>Club Members</h3>
    <table>
        <tr><th>#</th><th>Student</th><th>Position</th><th>Status</th></tr>
        <?php $no=1; while ($row = mysqli_fetch_assoc($members)) { ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td class="user-cell"><span class="mini-avatar"><?php echo clean(initials($row["student_name"])); ?></span><?php echo clean($row["student_name"]); ?> (<?php echo clean($row["studentID"]); ?>)</td>
            <td><?php echo clean($row["position_name"] ?: "Member"); ?></td>
            <td><span class="badge badge-green"><?php echo clean($row["membership_status"]); ?></span></td>
        </tr>
        <?php } ?>
    </table>
</div>

<script src="../assets/script.js"></script>
<?php page_end(); ?>
