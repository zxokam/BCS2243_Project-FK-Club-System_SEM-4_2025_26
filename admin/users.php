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

$sql = "SELECT * FROM student WHERE 1";

if ($search != "") {
    $sql .= " AND (student_name LIKE '%$search%' OR studentID LIKE '%$search%' OR student_email LIKE '%$search%')";
}

if ($role != "") {
    $sql .= " AND student_role = '$role'";
}

$sql .= " ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

page_start("Manage Users", "users");
?>
<div class="page-header">
    <div>
        <h1>Manage Users</h1>
        <p class="subtitle">Admin can view, register, update and delete student accounts.</p>
    </div>
    <a class="btn" href="user_add.php">+ Register New User</a>
</div>

<form class="toolbar" method="GET">
    <input class="form-control" style="margin-bottom:0;" type="text" name="search" value="<?php echo clean($search); ?>" placeholder="Search by name, student ID or email...">
    <select class="form-control" style="margin-bottom:0;max-width:180px;" name="role">
        <option value="">All Roles</option>
        <option value="student" <?php if ($role == "student") echo "selected"; ?>>Student</option>
        <option value="committee" <?php if ($role == "committee") echo "selected"; ?>>Club Committee</option>
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
            <th>Actions</th>
        </tr>
        <?php
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) {
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
                <a class="btn btn-light btn-small" href="user_edit.php?id=<?php echo clean($row["studentID"]); ?>">Edit</a>
                <a class="btn btn-red btn-small" href="user_delete.php?id=<?php echo clean($row["studentID"]); ?>" onclick="return confirm('Delete this user?')">Delete</a>
            </td>
        </tr>
        <?php } ?>
    </table>
    <div class="footer-note">Data from STUDENT table. Committee users are students with role = committee.</div>
</div>
<?php page_end(); ?>
