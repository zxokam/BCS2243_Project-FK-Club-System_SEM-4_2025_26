<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "admin") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$search = isset($_GET["search"]) ? esc($conn, $_GET["search"]) : "";
$role = isset($_GET["role"]) ? esc($conn, $_GET["role"]) : "";
$status = isset($_GET["status"]) ? esc($conn, $_GET["status"]) : "";

$sql = "SELECT * FROM student WHERE 1";

if ($search != "") {
    $sql .= " AND (student_name LIKE '%$search%' OR studentID LIKE '%$search%' OR student_email LIKE '%$search%')";
}

if ($role != "") {
    $sql .= " AND student_role = '$role'";
}

if ($status != "") {
    $sql .= " AND student_status = '$status'";
}

$sql .= " ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

page_start("Manage Users", "users");
?>
<div class="page-header">
    <div>
        <h1>Manage Users</h1>
        <p class="subtitle">Admin can view, register, update, deactivate and delete inactive student accounts.</p>
    </div>
    <a class="btn" href="user_add.php">+ Register New User</a>
</div>

<?php if (isset($_GET["error"]) && $_GET["error"] == "active_delete") { ?>
    <div class="alert alert-error">Active account cannot be deleted. Deactivate the account first, then delete it.</div>
<?php } ?>

<form class="toolbar" method="GET">
    <input class="form-control" style="margin-bottom:0;" type="text" name="search" value="<?php echo clean($search); ?>" placeholder="Search by name, student ID or email...">
    <select class="form-control" style="margin-bottom:0;max-width:180px;" name="role">
        <option value="">All Roles</option>
        <option value="student" <?php if ($role == "student") echo "selected"; ?>>Student</option>
        <option value="committee" <?php if ($role == "committee") echo "selected"; ?>>Club Committee</option>
    </select>
    <select class="form-control" style="margin-bottom:0;max-width:180px;" name="status">
        <option value="">All Status</option>
        <option value="Active" <?php if ($status == "Active") echo "selected"; ?>>Active</option>
        <option value="Inactive" <?php if ($status == "Inactive") echo "selected"; ?>>Inactive</option>
    </select>
    <button class="btn btn-light" type="submit">Search</button>
</form>

<div class="table-box">
    <table>
        <tr>
            <th>#</th>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Program</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            $student_status = $row["student_status"] ?? "Active";
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td class="user-cell">
                <span class="mini-avatar"><?php echo clean(initials($row["student_name"])); ?></span>
                <div>
                    <b><?php echo clean($row["student_name"]); ?></b><br>
                    <small><?php echo clean($row["studentID"]); ?></small>
                </div>
            </td>
            <td><?php echo clean($row["student_email"]); ?></td>
            <td>
                <span class="badge <?php echo $row["student_role"] == "committee" ? "badge-orange" : "badge-green"; ?>">
                    <?php echo clean(ucfirst($row["student_role"])); ?>
                </span>
            </td>
            <td><?php echo clean($row["program"]); ?></td>
            <td>
                <span class="badge <?php echo $student_status == "Active" ? "badge-green" : "badge-red"; ?>">
                    <?php echo clean($student_status); ?>
                </span>
            </td>
            <td>
                <a class="btn btn-light btn-small" href="user_edit.php?id=<?php echo clean($row["studentID"]); ?>">Edit</a>

                <?php if ($student_status == "Active") { ?>
                    <a class="btn btn-light btn-small" href="user_toggle_status.php?id=<?php echo clean($row["studentID"]); ?>" onclick="return confirm('Deactivate this user account?')">Deactivate</a>
                <?php } else { ?>
                    <a class="btn btn-light btn-small" href="user_toggle_status.php?id=<?php echo clean($row["studentID"]); ?>" onclick="return confirm('Activate this user account?')">Activate</a>
                    <a class="btn btn-red btn-small" href="user_delete.php?id=<?php echo clean($row["studentID"]); ?>" onclick="return confirm('Delete this inactive user permanently?')">Delete</a>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>
    <div class="footer-note">Data from STUDENT table. Inactive accounts are hidden from login and may be deleted by administrator.</div>
</div>
<?php page_end(); ?>
