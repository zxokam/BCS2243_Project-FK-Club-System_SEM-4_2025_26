<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "admin") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$sql = "SELECT club.*, advisor.advisor_name AS real_advisor, COUNT(membership.membershipID) AS total_members
        FROM club
        LEFT JOIN advisor ON club.advisorID = advisor.advisorID
        LEFT JOIN membership ON club.clubID = membership.clubID
        GROUP BY club.clubID
        ORDER BY club.club_name";
$result = mysqli_query($conn, $sql);

page_start("Manage Clubs", "clubs");
?>
<div class="page-header">
    <div>
        <h1>Manage Clubs</h1>
        <p class="subtitle">Create, view, update and delete club information. Extra action: manage committee roles.</p>
    </div>
    <a class="btn" href="club_add.php">+ Add New Club</a>
</div>

<div class="toolbar">
    <input class="form-control" style="margin-bottom:0;" id="clubAdminSearch" onkeyup="filterTable('clubAdminSearch','clubAdminTable')" placeholder="Search clubs...">
</div>

<div class="table-box">
    <table id="clubAdminTable">
        <thead>
        <tr>
            <th>Club Name</th>
            <th>Advisor</th>
            <th>Category</th>
            <th>Members</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><b><?php echo clean($row["club_name"]); ?></b><br><small><?php echo clean($row["club_description"]); ?></small></td>
            <td><?php echo clean($row["real_advisor"] ?: $row["advisor_name"]); ?></td>
            <td><?php echo clean($row["club_category"]); ?></td>
            <td><?php echo clean($row["total_members"]); ?></td>
            <td>
                <span class="badge <?php echo $row["club_status"] == "Active" ? "badge-green" : "badge-red"; ?>">
                    <?php echo clean($row["club_status"]); ?>
                </span>
            </td>
            <td>
                <a class="btn btn-light btn-small" href="club_edit.php?id=<?php echo clean($row["clubID"]); ?>">Edit</a>
                <a class="btn btn-light btn-small" href="committee_roles.php?clubID=<?php echo clean($row["clubID"]); ?>">Committee</a>

                <?php if ($row["club_status"] == "Active") { ?>
                    <a class="btn btn-red btn-small" href="club_toggle_status.php?id=<?php echo clean($row["clubID"]); ?>" onclick="return confirmAction('Deactivate this club? Students will no longer see it.')">Deactivate</a>
                <?php } else { ?>
                    <a class="btn btn-light btn-small" href="club_toggle_status.php?id=<?php echo clean($row["clubID"]); ?>" onclick="return confirmAction('Activate this club? Students will be able to see it.')">Activate</a>
                <?php } ?>

                <a class="btn btn-red btn-small" href="club_delete.php?id=<?php echo clean($row["clubID"]); ?>" onclick="return confirmAction('Delete this club?')">Delete</a>
            </td>
        </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<script src="../assets/script.js"></script>
<?php page_end(); ?>
