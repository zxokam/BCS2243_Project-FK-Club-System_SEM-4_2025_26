<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "student") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$id = mysqli_real_escape_string($conn, $_SESSION["user_id"]);
$message = "";
$error = "";

// Keep older databases compatible with the profile photo feature.
$photo_column_check = mysqli_query($conn, "SHOW COLUMNS FROM student LIKE 'profile_photo'");
if ($photo_column_check && mysqli_num_rows($photo_column_check) == 0) {
    mysqli_query($conn, "ALTER TABLE student ADD profile_photo VARCHAR(255) NULL");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_name = mysqli_real_escape_string($conn, $_POST["student_name"] ?? "");
    $student_email = mysqli_real_escape_string($conn, $_POST["student_email"] ?? "");
    $student_phone = mysqli_real_escape_string($conn, $_POST["student_phone"] ?? "");
    $program = mysqli_real_escape_string($conn, $_POST["program"] ?? "");

    $photo_sql = "";

    if (isset($_FILES["profile_photo"]) && $_FILES["profile_photo"]["error"] == 0) {
        $allowed = ["jpg", "jpeg", "png", "gif", "webp"];
        $file_name = $_FILES["profile_photo"]["name"];
        $file_tmp = $_FILES["profile_photo"]["tmp_name"];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            $upload_dir = __DIR__ . "/../uploads/profile_photos/";

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $new_name = $id . "_" . time() . "." . $file_ext;
            $target_file = $upload_dir . $new_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                $photo_path = "uploads/profile_photos/" . $new_name;
                $photo_sql = ", profile_photo = '" . mysqli_real_escape_string($conn, $photo_path) . "'";
                $_SESSION["profile_photo"] = $photo_path;
            } else {
                $error = "Profile photo could not be uploaded.";
            }
        } else {
            $error = "Only JPG, PNG, GIF and WEBP files are allowed.";
        }
    }

    if ($error == "") {
        $update = "UPDATE student SET
                    student_name = '$student_name',
                    student_email = '$student_email',
                    student_phone = '$student_phone',
                    program = '$program'
                    $photo_sql
                   WHERE studentID = '$id'";

        if (mysqli_query($conn, $update)) {
            $_SESSION["user_name"] = $student_name;
            $message = "Profile updated successfully.";
        } else {
            $error = "Profile update failed: " . mysqli_error($conn);
        }
    }
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM student WHERE studentID = '$id'"));
$photo = $user["profile_photo"] ?? "";
if ($photo != "") {
    $_SESSION["profile_photo"] = $photo;
}

$memberships = mysqli_query($conn, "
    SELECT membership.*, club.club_name, club.club_category, position.position_name
    FROM membership
    LEFT JOIN club ON membership.clubID = club.clubID
    LEFT JOIN position ON membership.positionID = position.positionID
    WHERE membership.studentID = '$id'
    ORDER BY membership.date_assigned DESC
");

$edit_mode = isset($_GET["edit"]);

page_start("Student Profile", "profile");
?>
<style>
.profile-container {
    max-width: 900px;
    margin: 0 auto;
}

.profile-hero {
    text-align: center;
    padding: 28px 22px;
    margin-bottom: 18px;
}

.profile-photo-wrap {
    position: relative;
    display: inline-block;
}

.profile-photo,
.profile-photo-initials {
    width: 95px;
    height: 95px;
    border-radius: 50%;
    object-fit: cover;
    display: grid;
    place-items: center;
    margin: 0 auto;
    background: var(--green);
    color: white;
    font-size: 28px;
    font-weight: bold;
}


.profile-name {
    margin-top: 14px;
    font-size: 21px;
}

.profile-contact {
    display: flex;
    justify-content: center;
    gap: 22px;
    flex-wrap: wrap;
    color: var(--muted);
    font-size: 12px;
    margin-top: 14px;
}

.detail-row {
    display: grid;
    grid-template-columns: 190px 1fr;
    padding: 13px 0;
    border-top: 1px solid #eef2f7;
}

.detail-label {
    color: var(--muted);
    font-size: 12px;
    font-weight: 600;
}

.detail-value {
    color: var(--text);
    font-weight: 600;
}

.profile-panel-title {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eef2f7;
    margin-bottom: 12px;
}

@media (max-width: 700px) {
    .detail-row {
        grid-template-columns: 1fr;
        gap: 6px;
    }
}
</style>

<div class="page-header">
    <div>
        <h1>My Profile</h1>
        <p class="subtitle">View and update your contact details, profile photo and club membership information.</p>
    </div>
    <div>
        <?php if ($edit_mode) { ?>
            <a class="btn btn-light" href="profile.php">Cancel</a>
        <?php } else { ?>
            <a class="btn btn-light" href="profile.php?edit=1">✏️ Edit Profile</a>
        <?php } ?>
    </div>
</div>

<div class="profile-container">
    <?php if ($message != "") { ?>
        <div class="alert alert-success"><?php echo clean($message); ?></div>
    <?php } ?>

    <?php if ($error != "") { ?>
        <div class="alert alert-error"><?php echo clean($error); ?></div>
    <?php } ?>

    <div class="panel profile-hero">
        <div class="profile-photo-wrap">
            <?php if ($photo != "" && file_exists(__DIR__ . "/../" . $photo)) { ?>
                <img class="profile-photo" src="../<?php echo clean($photo); ?>" alt="Profile Photo">
            <?php } else { ?>
                <div class="profile-photo-initials"><?php echo clean(initials($user["student_name"])); ?></div>
            <?php } ?>
        </div>

        <h2 class="profile-name"><?php echo clean($user["student_name"]); ?></h2>

        <span class="badge <?php echo ($user["student_role"] ?? "student") == "committee" ? "badge-orange" : "badge-green"; ?>">
            <?php echo clean(ucfirst($user["student_role"] ?? "student")); ?>
        </span>

        <div class="profile-contact">
            <span>✉️ <?php echo clean($user["student_email"]); ?></span>
            <span>📞 <?php echo clean($user["student_phone"]); ?></span>
        </div>
    </div>

    <?php if ($edit_mode) { ?>
        <div class="form-box" style="max-width:900px; margin-bottom:18px;">
            <h3 style="margin-bottom:15px;">Edit Profile</h3>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div>
                        <label>Full Name</label>
                        <input class="form-control" type="text" name="student_name" value="<?php echo clean($user["student_name"]); ?>" required>
                    </div>

                    <div>
                        <label>Email</label>
                        <input class="form-control" type="email" name="student_email" value="<?php echo clean($user["student_email"]); ?>" required>
                    </div>

                    <div>
                        <label>Phone</label>
                        <input class="form-control" type="text" name="student_phone" value="<?php echo clean($user["student_phone"]); ?>" required>
                    </div>

                    <div>
                        <label>Program</label>
                        <input class="form-control" type="text" name="program" value="<?php echo clean($user["program"]); ?>" required>
                    </div>

                    <div>
                        <label>Profile Photo</label>
                        <input class="form-control" type="file" name="profile_photo" accept="image/*">
                    </div>
                </div>

                <div class="actions">
                    <button class="btn" type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    <?php } ?>

    <div class="panel" style="margin-bottom:18px;">
        <div class="profile-panel-title">
            <b>📋 Profile Details</b>
        </div>

        <div class="detail-row">
            <div class="detail-label">Student ID</div>
            <div class="detail-value"><?php echo clean($user["studentID"]); ?></div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Full Name</div>
            <div class="detail-value"><?php echo clean($user["student_name"]); ?></div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Email</div>
            <div class="detail-value"><?php echo clean($user["student_email"]); ?></div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Phone</div>
            <div class="detail-value"><?php echo clean($user["student_phone"]); ?></div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Program</div>
            <div class="detail-value"><?php echo clean($user["program"]); ?></div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Intake / Semester</div>
            <div class="detail-value"><?php echo clean(($user["intake_year"] ?? "-") . " / " . ($user["enrollment_semester"] ?? "-")); ?></div>
        </div>
    </div>

    <div class="panel">
        <div class="profile-panel-title">
            <b>🏛️ Club Membership Information</b>
        </div>

        <table>
            <tr>
                <th>Club</th>
                <th>Category</th>
                <th>Position</th>
                <th>Status</th>
                <th>Date Joined</th>
            </tr>

            <?php if ($memberships && mysqli_num_rows($memberships) > 0) { ?>
                <?php while ($membership = mysqli_fetch_assoc($memberships)) { ?>
                    <tr>
                        <td><?php echo clean($membership["club_name"]); ?></td>
                        <td><?php echo clean($membership["club_category"]); ?></td>
                        <td><?php echo clean($membership["position_name"] ?? "Member"); ?></td>
                        <td>
                            <span class="badge <?php echo ($membership["membership_status"] ?? "") == "Approved" ? "badge-green" : "badge-gray"; ?>">
                                <?php echo clean($membership["membership_status"] ?? "Pending"); ?>
                            </span>
                        </td>
                        <td><?php echo clean($membership["date_assigned"] ?? "-"); ?></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="5" style="text-align:center;color:#718096;">No club membership record found.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<?php page_end(); ?>
