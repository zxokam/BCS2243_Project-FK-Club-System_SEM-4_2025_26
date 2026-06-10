<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "admin") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$message = "";
$error = "";

$selected_club = isset($_GET["clubID"]) ? mysqli_real_escape_string($conn, $_GET["clubID"]) : "";

if (isset($_POST["assign"])) {
    $clubID = mysqli_real_escape_string($conn, $_POST["clubID"]);
    $studentID = mysqli_real_escape_string($conn, $_POST["studentID"]);
    $positionID = mysqli_real_escape_string($conn, $_POST["positionID"]);
    $tenure = mysqli_real_escape_string($conn, $_POST["committee_tenure"]);
    $start_date = mysqli_real_escape_string($conn, $_POST["start_date"]);

    $sql1 = "INSERT INTO membership (studentID, clubID, positionID, date_assigned, membership_status)
             VALUES ('$studentID', '$clubID', '$positionID', '$start_date', 'Approved')
             ON DUPLICATE KEY UPDATE
             positionID='$positionID',
             date_assigned='$start_date',
             membership_status='Approved'";

    $sql2 = "INSERT INTO event_committee (studentID, clubID, positionID, committee_tenure, start_date, committee_status, date_appointed)
             VALUES ('$studentID', '$clubID', '$positionID', '$tenure', '$start_date', 'Active', '$start_date')";

    $sql3 = "UPDATE student SET student_role='committee' WHERE studentID='$studentID'";

    if (mysqli_query($conn, $sql1) && mysqli_query($conn, $sql2) && mysqli_query($conn, $sql3)) {
        $message = "Committee member assigned successfully.";
        $selected_club = $clubID;
    } else {
        $error = mysqli_error($conn);
    }
}

if (isset($_GET["delete"])) {
    $id = mysqli_real_escape_string($conn, $_GET["delete"]);
    mysqli_query($conn, "DELETE FROM event_committee WHERE eventCommitteeID='$id'");
    $message = "Committee record removed.";
}

$clubs = mysqli_query($conn, "SELECT * FROM club ORDER BY club_name");
$students = mysqli_query($conn, "SELECT studentID, student_name, student_email FROM student ORDER BY student_name");
$positions = mysqli_query($conn, "SELECT * FROM `position` ORDER BY positionID");

$where = $selected_club != "" ? "WHERE ec.clubID='$selected_club'" : "";
$committee = mysqli_query($conn, "
    SELECT ec.*, s.student_name, s.student_email, c.club_name, p.position_name
    FROM event_committee ec
    JOIN student s ON ec.studentID = s.studentID
    JOIN club c ON ec.clubID = c.clubID
    LEFT JOIN `position` p ON ec.positionID = p.positionID
    $where
    ORDER BY c.club_name, p.positionID, s.student_name
");

page_start("Committee Roles", "committee_roles");
?>
<div class="page-header">
    <div>
        <h1>Committee Role Management</h1>
        <p class="subtitle">Assign and manage committee roles using STUDENT, CLUB, POSITION, MEMBERSHIP and EVENT_COMMITTEE tables.</p>
    </div>
</div>

<?php if ($message != "") { ?><div class="alert alert-success"><?php echo clean($message); ?></div><?php } ?>
<?php if ($error != "") { ?><div class="alert alert-error"><?php echo clean($error); ?></div><?php } ?>

<form class="form-box" method="POST" id="committeeForm" onsubmit="return validateRequiredForm('committeeForm')">
    <div class="section">
        <h3>Assign Committee Member</h3>
        <div class="form-grid">
            <div>
                <label>Club <span class="required">*</span></label>
                <select class="form-control" name="clubID" required>
                    <option value="">Select Club</option>
                    <?php mysqli_data_seek($clubs, 0); while ($club = mysqli_fetch_assoc($clubs)) { ?>
                    <option value="<?php echo clean($club["clubID"]); ?>" <?php if ($selected_club == $club["clubID"]) echo "selected"; ?>>
                        <?php echo clean($club["club_name"]); ?>
                    </option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label>Student <span class="required">*</span></label>
                <select class="form-control" name="studentID" required>
                    <option value="">Select Student</option>
                    <?php while ($student = mysqli_fetch_assoc($students)) { ?>
                    <option value="<?php echo clean($student["studentID"]); ?>">
                        <?php echo clean($student["student_name"]); ?> (<?php echo clean($student["studentID"]); ?>)
                    </option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label>Position <span class="required">*</span></label>
                <select class="form-control" name="positionID" required>
                    <option value="">Select Position</option>
                    <?php while ($position = mysqli_fetch_assoc($positions)) { ?>
                    <option value="<?php echo clean($position["positionID"]); ?>">
                        <?php echo clean($position["position_name"]); ?>
                    </option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label>Tenure</label>
                <input class="form-control" name="committee_tenure" value="2025/2026">
            </div>
            <div>
                <label>Start Date <span class="required">*</span></label>
                <input class="form-control" type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>
    </div>

    <div class="actions">
        <button class="btn" name="assign">Assign Role</button>
    </div>
</form>

<br>

<form class="toolbar" method="GET">
    <select class="form-control" style="margin-bottom:0;max-width:320px;" name="clubID" onchange="this.form.submit()">
        <option value="">All Clubs</option>
        <?php mysqli_data_seek($clubs, 0); while ($club = mysqli_fetch_assoc($clubs)) { ?>
        <option value="<?php echo clean($club["clubID"]); ?>" <?php if ($selected_club == $club["clubID"]) echo "selected"; ?>>
            <?php echo clean($club["club_name"]); ?>
        </option>
        <?php } ?>
    </select>
    <input class="form-control" style="margin-bottom:0;" id="committeeSearch" onkeyup="filterTable('committeeSearch','committeeTable')" placeholder="Search committee name, club or position...">
</form>

<div class="table-box">
    <table id="committeeTable">
        <thead>
        <tr>
            <th>#</th>
            <th>Club</th>
            <th>Committee Member</th>
            <th>Position</th>
            <th>Tenure</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php $no=1; while ($row = mysqli_fetch_assoc($committee)) { ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><b><?php echo clean($row["club_name"]); ?></b></td>
            <td class="user-cell">
                <span class="mini-avatar"><?php echo clean(initials($row["student_name"])); ?></span>
                <div>
                    <b><?php echo clean($row["student_name"]); ?></b><br>
                    <small><?php echo clean($row["studentID"]); ?> · <?php echo clean($row["student_email"]); ?></small>
                </div>
            </td>
            <td><span class="badge badge-blue"><?php echo clean($row["position_name"] ?: "Committee"); ?></span></td>
            <td><?php echo clean($row["committee_tenure"]); ?></td>
            <td><span class="badge badge-green"><?php echo clean($row["committee_status"]); ?></span></td>
            <td>
                <a class="btn btn-red btn-small" href="committee_roles.php?delete=<?php echo clean($row["eventCommitteeID"]); ?>&clubID=<?php echo clean($row["clubID"]); ?>" onclick="return confirmAction('Remove this committee member?')">Remove</a>
            </td>
        </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<script src="../assets/script.js"></script>
<?php page_end(); ?>
