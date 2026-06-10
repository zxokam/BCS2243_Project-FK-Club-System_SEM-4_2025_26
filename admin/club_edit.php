<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "admin") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$id = esc($conn, $_GET["id"]);
$club = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM club WHERE clubID = '$id'"));

if (!$club) {
    header("Location: clubs.php");
    exit();
}

$error = "";
$advisors = mysqli_query($conn, "SELECT * FROM advisor ORDER BY advisor_name");

if (isset($_POST["save"])) {
    $advisorID = esc($conn, $_POST["advisorID"]);
    $club_name = esc($conn, $_POST["club_name"]);
    $description = esc($conn, $_POST["club_description"]);
    $category = esc($conn, $_POST["club_category"]);
    $status = esc($conn, $_POST["club_status"]);

    $advisor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT advisor_name FROM advisor WHERE advisorID = '$advisorID'"));
    $advisor_name = $advisor ? esc($conn, $advisor["advisor_name"]) : "";

    $sql = "UPDATE club SET
            advisorID = '$advisorID',
            club_name = '$club_name',
            club_description = '$description',
            club_category = '$category',
            advisor_name = '$advisor_name',
            club_status = '$status'
            WHERE clubID = '$id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: clubs.php");
        exit();
    } else {
        $error = mysqli_error($conn);
    }
}

page_start("Edit Club", "clubs");
?>
<div class="page-header">
    <div>
        <h1>Edit Club</h1>
        <p class="subtitle"><?php echo clean($club["club_name"]); ?></p>
    </div>
</div>

<?php if ($error != "") { ?>
    <div class="alert alert-error"><?php echo clean($error); ?></div>
<?php } ?>

<form class="form-box" method="POST">
    <div class="section">
        <h3>Club Information</h3>
        <div class="form-grid">
            <div>
                <label>Club Name</label>
                <input class="form-control" name="club_name" value="<?php echo clean($club["club_name"]); ?>" required>
            </div>
            <div>
                <label>Advisor</label>
                <select class="form-control" name="advisorID" required>
                    <?php while ($a = mysqli_fetch_assoc($advisors)) { ?>
                    <option value="<?php echo clean($a["advisorID"]); ?>" <?php if ($club["advisorID"] == $a["advisorID"]) echo "selected"; ?>>
                        <?php echo clean($a["advisor_name"]); ?>
                    </option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label>Category</label>
                <input class="form-control" name="club_category" value="<?php echo clean($club["club_category"]); ?>">
            </div>
            <div>
                <label>Status</label>
                <select class="form-control" name="club_status">
                    <option <?php if ($club["club_status"] == "Active") echo "selected"; ?>>Active</option>
                    <option <?php if ($club["club_status"] == "Inactive") echo "selected"; ?>>Inactive</option>
                </select>
            </div>
        </div>
        <label>Description</label>
        <textarea class="form-control" name="club_description" rows="4"><?php echo clean($club["club_description"]); ?></textarea>
    </div>

    <div class="actions">
        <a class="btn btn-light" href="clubs.php">Cancel</a>
        <button class="btn" name="save">Save Changes</button>
    </div>
</form>
<?php page_end(); ?>
