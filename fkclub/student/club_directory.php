<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "student") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$studentID = $_SESSION["user_id"];

$clubs = mysqli_query($conn, "
    SELECT c.*, a.advisor_name AS real_advisor, COUNT(m.membershipID) AS total_members
    FROM club c
    LEFT JOIN advisor a ON c.advisorID = a.advisorID
    LEFT JOIN membership m ON c.clubID = m.clubID AND m.membership_status='Approved'
    GROUP BY c.clubID
    ORDER BY c.club_name
");

page_start("Club Directory", "club_directory");
?>
<div class="page-header">
    <div>
        <h1>Club Directory</h1>
        <p class="subtitle">Browse clubs, view details and join a club.</p>
    </div>
</div>

<div class="toolbar">
    <input class="form-control" style="margin-bottom:0;" id="clubSearch" onkeyup="filterTable('clubSearch','clubTable')" placeholder="Search club by name, category or advisor...">
</div>

<div class="grid grid-3" id="clubTable">
    <?php while ($club = mysqli_fetch_assoc($clubs)) { 
        $clubID = $club["clubID"];
        $membership = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM membership WHERE studentID='$studentID' AND clubID='$clubID'"));
    ?>
    <div class="event-card">
        <span class="badge <?php echo $club["club_status"] == "Active" ? "badge-green" : "badge-red"; ?>">
            <?php echo clean($club["club_status"]); ?>
        </span>
        <h3><?php echo clean($club["club_name"]); ?></h3>
        <p class="subtitle"><?php echo clean($club["club_description"]); ?></p>
        <div class="event-meta">
            🏷️ <?php echo clean($club["club_category"]); ?><br>
            👨‍🏫 <?php echo clean($club["real_advisor"] ?: $club["advisor_name"]); ?><br>
            👥 <?php echo clean($club["total_members"]); ?> members
        </div>
        <br>
        <a class="btn btn-light" style="width:100%;margin-bottom:8px;" href="club_details.php?id=<?php echo clean($clubID); ?>">View Details</a>
        <?php if ($membership) { ?>
            <button class="btn btn-light" style="width:100%;" disabled><?php echo clean($membership["membership_status"]); ?></button>
        <?php } else { ?>
            <a class="btn" style="width:100%;" href="join_club.php?id=<?php echo clean($clubID); ?>" onclick="return confirmAction('Apply to join this club?')">Join Club</a>
        <?php } ?>
    </div>
    <?php } ?>
</div>

<script src="../assets/script.js"></script>
<?php page_end(); ?>
