<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "admin") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$error = "";

if (isset($_POST["save"])) {
    $studentID = strtoupper(esc($conn, $_POST["studentID"]));
    $student_name = esc($conn, $_POST["student_name"]);
    $student_email = esc($conn, $_POST["student_email"]);
    $student_phone = esc($conn, $_POST["student_phone"]);
    $program = esc($conn, $_POST["program"]);
    $intake_year = esc($conn, $_POST["intake_year"]);
    $semester = esc($conn, $_POST["enrollment_semester"]);
    $role = esc($conn, $_POST["student_role"]);
    $student_status = esc($conn, $_POST["student_status"]);
    $password = esc($conn, $_POST["student_password"]);

    $sql = "INSERT INTO student
            (studentID, recognitionID, program, intake_year, enrollment_semester, student_name, student_phone, student_email, student_password, student_role, student_status)
            VALUES
            ('$studentID', 1, '$program', '$intake_year', '$semester', '$student_name', '$student_phone', '$student_email', '$password', '$role', '$student_status')";

    if (mysqli_query($conn, $sql)) {
        if ($role == "committee" && $_POST["clubID"] != "" && $_POST["positionID"] != "") {
            $clubID = esc($conn, $_POST["clubID"]);
            $positionID = esc($conn, $_POST["positionID"]);
            $date = esc($conn, $_POST["date_assigned"]);

            mysqli_query($conn, "INSERT INTO membership (studentID, clubID, positionID, date_assigned, membership_status)
                                 VALUES ('$studentID', '$clubID', '$positionID', '$date', 'Approved')");

            mysqli_query($conn, "INSERT INTO event_committee (studentID, clubID, positionID, committee_tenure, start_date, committee_status, date_appointed)
                                 VALUES ('$studentID', '$clubID', '$positionID', '2025/2026', '$date', 'Active', '$date')");
        }

        header("Location: users.php");
        exit();
    } else {
        $error = mysqli_error($conn);
    }
}

$clubs = mysqli_query($conn, "SELECT * FROM club ORDER BY club_name");
$positions = mysqli_query($conn, "SELECT * FROM `position` ORDER BY positionID");

page_start("Register User", "users");
?>
<div class="page-header">
    <div>
        <h1>Register New User</h1>
        <p class="subtitle">User registration by administrator.</p>
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
                <label>Full Name <span class="required">*</span></label>
                <input class="form-control" name="student_name" required>
            </div>
            <div>
                <label>Student ID <span class="required">*</span></label>
                <input class="form-control" name="studentID" placeholder="CB24070" required>
            </div>
            <div>
                <label>Email Address <span class="required">*</span></label>
                <input class="form-control" type="email" name="student_email" required>
            </div>
            <div>
                <label>Phone Number</label>
                <input class="form-control" name="student_phone">
            </div>
            <div>
                <label>Password <span class="required">*</span></label>
                <input class="form-control" type="password" name="student_password" required>
            </div>
            <div>
                <label>Role <span class="required">*</span></label>
                <select class="form-control" name="student_role" required>
                    <option value="student">Student</option>
                    <option value="committee">Club Committee</option>
                </select>
            </div>
            <div>
                <label>Account Status <span class="required">*</span></label>
                <select class="form-control" name="student_status" required>
                    <option value="Active" selected>Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>Student Details</h3>
        <div class="form-grid">
            <div>
                <label>Program</label>
                <select class="form-control" name="program">
                    <option>Bachelor of Computer Science</option>
                    <option>Bachelor of Software Engineering</option>
                    <option>Bachelor of Cyber Security</option>
                    <option>Bachelor of Data Science</option>
                </select>
            </div>
            <div>
                <label>Intake Year</label>
                <select class="form-control" name="intake_year">
                    <option>2023</option>
                    <option selected>2024</option>
                    <option>2025</option>
                </select>
            </div>
            <div>
                <label>Enrollment Semester</label>
                <select class="form-control" name="enrollment_semester">
                    <option>Semester 1</option>
                    <option selected>Semester 2</option>
                </select>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>Committee Details</h3>
        <div class="form-grid">
            <div>
                <label>Club</label>
                <select class="form-control" name="clubID">
                    <option value="">Select Club</option>
                    <?php while ($club = mysqli_fetch_assoc($clubs)) { ?>
                        <option value="<?php echo clean($club["clubID"]); ?>"><?php echo clean($club["club_name"]); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label>Position</label>
                <select class="form-control" name="positionID">
                    <option value="">Select Position</option>
                    <?php while ($position = mysqli_fetch_assoc($positions)) { ?>
                        <option value="<?php echo clean($position["positionID"]); ?>"><?php echo clean($position["position_name"]); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label>Assigned Date</label>
                <input class="form-control" type="date" name="date_assigned" value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
    </div>

    <div class="actions">
        <a class="btn btn-light" href="users.php">Cancel</a>
        <button class="btn" name="save">Register User</button>
    </div>
</form>
<?php page_end(); ?>
