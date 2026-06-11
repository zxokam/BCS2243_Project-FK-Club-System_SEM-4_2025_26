<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "admin") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$id = esc($conn, $_GET["id"]);
$result = mysqli_query($conn, "SELECT * FROM student WHERE studentID = '$id'");
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header("Location: users.php");
    exit();
}

$error = "";

if (isset($_POST["save"])) {
    $student_name = esc($conn, $_POST["student_name"]);
    $student_email = esc($conn, $_POST["student_email"]);
    $student_phone = esc($conn, $_POST["student_phone"]);
    $program = esc($conn, $_POST["program"]);
    $intake_year = esc($conn, $_POST["intake_year"]);
    $semester = esc($conn, $_POST["enrollment_semester"]);
    $role = esc($conn, $_POST["student_role"]);
    $student_status = esc($conn, $_POST["student_status"]);

    $sql = "UPDATE student SET
            student_name = '$student_name',
            student_email = '$student_email',
            student_phone = '$student_phone',
            program = '$program',
            intake_year = '$intake_year',
            enrollment_semester = '$semester',
            student_role = '$role',
            student_status = '$student_status'
            WHERE studentID = '$id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: users.php");
        exit();
    } else {
        $error = mysqli_error($conn);
    }
}

page_start("Edit User", "users");
?>
<div class="page-header">
    <div>
        <h1>Edit User Profile</h1>
        <p class="subtitle"><?php echo clean($user["student_name"]); ?></p>
    </div>
</div>

<?php if ($error != "") { ?>
    <div class="alert alert-error"><?php echo clean($error); ?></div>
<?php } ?>

<form class="form-box" method="POST">
    <div class="section">
        <h3>Basic Information</h3>
        <div class="form-grid">
            <div>
                <label>Full Name</label>
                <input class="form-control" name="student_name" value="<?php echo clean($user["student_name"]); ?>">
            </div>
            <div>
                <label>Email Address</label>
                <input class="form-control" type="email" name="student_email" value="<?php echo clean($user["student_email"]); ?>">
            </div>
            <div>
                <label>Phone Number</label>
                <input class="form-control" name="student_phone" value="<?php echo clean($user["student_phone"]); ?>">
            </div>
            <div>
                <label>Role</label>
                <select class="form-control" name="student_role">
                    <option value="student" <?php if ($user["student_role"] == "student") echo "selected"; ?>>Student</option>
                    <option value="committee" <?php if ($user["student_role"] == "committee") echo "selected"; ?>>Club Committee</option>
                </select>
            </div>
            <div>
                <label>Account Status</label>
                <select class="form-control" name="student_status">
                    <option value="Active" <?php if (($user["student_status"] ?? "Active") == "Active") echo "selected"; ?>>Active</option>
                    <option value="Inactive" <?php if (($user["student_status"] ?? "Active") == "Inactive") echo "selected"; ?>>Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>Student Details</h3>
        <div class="form-grid">
            <div>
                <label>Student ID</label>
                <input class="form-control" value="<?php echo clean($user["studentID"]); ?>" disabled>
            </div>
            <div>
                <label>Program</label>
                <input class="form-control" name="program" value="<?php echo clean($user["program"]); ?>">
            </div>
            <div>
                <label>Intake Year</label>
                <input class="form-control" name="intake_year" value="<?php echo clean($user["intake_year"]); ?>">
            </div>
            <div>
                <label>Enrollment Semester</label>
                <input class="form-control" name="enrollment_semester" value="<?php echo clean($user["enrollment_semester"]); ?>">
            </div>
        </div>
    </div>

    <div class="actions">
        <a class="btn btn-light" href="users.php">Cancel</a>
        <button class="btn" name="save">Save Changes</button>
    </div>
</form>
<?php page_end(); ?>
