<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "student") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

ensure_waiting_list_schema($conn);

$studentID = mysqli_real_escape_string($conn, $_SESSION["user_id"]);
$registrationID = mysqli_real_escape_string($conn, $_GET["id"] ?? ($_POST["id"] ?? ""));

$registration = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT er.*, e.event_title, e.event_description, e.event_date, e.start_time, e.end_time, e.venue, c.club_name
    FROM event_registration er
    JOIN event e ON er.eventID = e.eventID
    JOIN club c ON e.clubID = c.clubID
    WHERE er.registrationID = '$registrationID'
    AND er.studentID = '$studentID'
    LIMIT 1
"));

if (!$registration || in_array($registration["registration_status"], ["Cancelled", "Rejected"])) {
    header("Location: my_registrations.php");
    exit();
}

$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM student WHERE studentID = '$studentID' LIMIT 1"));

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_registration"])) {
    $confirmation = $registration["registration_status"] == "Waiting List" ? "Pending" : "Confirmed";

    mysqli_query($conn, "
        UPDATE event_registration
        SET confirmation_status = '$confirmation',
            updated_at = NOW()
        WHERE registrationID = '$registrationID'
        AND studentID = '$studentID'
    ");

    header("Location: my_registrations.php?notice=updated");
    exit();
}

page_start("Update Registration", "registrations");
?>
<div class="page-header">
    <div>
        <h1>Update Registration</h1>
        <p class="subtitle">Review your event registration and update the confirmation record.</p>
    </div>
    <a class="btn btn-light" href="my_registrations.php">Back</a>
</div>

<div class="grid grid-2">
    <div class="panel">
        <h3>Event Details</h3>
        <br>
        <p><b>Event</b><br><?php echo clean($registration["event_title"]); ?></p><br>
        <p><b>Club</b><br><?php echo clean($registration["club_name"]); ?></p><br>
        <p><b>Date</b><br><?php echo clean($registration["event_date"]); ?></p><br>
        <p><b>Time</b><br><?php echo clean(substr($registration["start_time"], 0, 5)); ?> - <?php echo clean(substr($registration["end_time"], 0, 5)); ?></p><br>
        <p><b>Venue</b><br><?php echo clean($registration["venue"]); ?></p>
    </div>

    <div class="panel">
        <h3>Your Registration</h3>
        <br>
        <p><b>Name</b><br><?php echo clean($student["student_name"]); ?></p><br>
        <p><b>Email</b><br><?php echo clean($student["student_email"]); ?></p><br>
        <p><b>Phone</b><br><?php echo clean($student["student_phone"]); ?></p><br>
        <p><b>Status</b><br>
            <?php if ($registration["registration_status"] == "Waiting List") { ?>
                <span class="badge badge-orange">Waiting List #<?php echo clean($registration["queue_number"] ?: "-"); ?></span>
            <?php } else { ?>
                <span class="badge badge-green"><?php echo clean($registration["registration_status"]); ?></span>
            <?php } ?>
        </p><br>
        <p><b>Confirmation</b><br><?php echo clean($registration["confirmation_status"]); ?></p>
    </div>
</div>

<br>

<div class="panel">
    <h3>Update Confirmation</h3>
    <p class="subtitle" style="margin-bottom:14px;">Click update to reconfirm that your registration details are correct.</p>
    <form method="POST">
        <input type="hidden" name="id" value="<?php echo clean($registrationID); ?>">
        <div class="actions">
            <a class="btn btn-red" href="cancel_registration.php?id=<?php echo clean($registrationID); ?>" onclick="return confirm('Cancel this registration?')">Cancel Registration</a>
            <button class="btn" type="submit" name="update_registration">Update Registration</button>
        </div>
    </form>
</div>
<?php page_end(); ?>
