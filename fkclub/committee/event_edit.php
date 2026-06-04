<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

ensure_waiting_list_schema($conn);

if ($_SESSION["role"] != "committee") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$committeeID = $_SESSION["user_id"];
$eventID = mysqli_real_escape_string($conn, $_GET["id"]);

$event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM event WHERE eventID='$eventID' AND created_by='$committeeID'"));

if (!$event) {
    header("Location: manage_events.php");
    exit();
}

$error = "";

if (isset($_POST["save"])) {
    $title = mysqli_real_escape_string($conn, $_POST["event_title"]);
    $description = mysqli_real_escape_string($conn, $_POST["event_description"]);
    $date = mysqli_real_escape_string($conn, $_POST["event_date"]);
    $start = mysqli_real_escape_string($conn, $_POST["start_time"]);
    $end = mysqli_real_escape_string($conn, $_POST["end_time"]);
    $venue = mysqli_real_escape_string($conn, $_POST["venue"]);
    $max = mysqli_real_escape_string($conn, $_POST["max_participants"]);
    $status = mysqli_real_escape_string($conn, $_POST["event_status"]);
    $waitingStatus = mysqli_real_escape_string($conn, $_POST["waiting_list_status"]);

    $sql = "UPDATE event SET
            event_title='$title',
            event_description='$description',
            event_date='$date',
            start_time='$start',
            end_time='$end',
            venue='$venue',
            max_participants='$max',
            event_status='$status',
            waiting_list_status='$waitingStatus'
            WHERE eventID='$eventID'
            AND created_by='$committeeID'";

    if (mysqli_query($conn, $sql)) {
        header("Location: manage_events.php");
        exit();
    } else {
        $error = mysqli_error($conn);
    }
}

page_start("Edit Event", "manage_events");
?>
<div class="page-header">
    <div>
        <h1>Edit Event</h1>
        <p class="subtitle"><?php echo clean($event["event_title"]); ?></p>
    </div>
</div>

<?php if ($error != "") { ?>
    <div class="alert alert-error"><?php echo clean($error); ?></div>
<?php } ?>

<form class="form-box" method="POST">
    <div class="section">
        <h3>Event Details</h3>
        <div class="form-grid">
            <div>
                <label>Event Title</label>
                <input class="form-control" name="event_title" value="<?php echo clean($event["event_title"]); ?>" required>
            </div>
            <div>
                <label>Event Date</label>
                <input class="form-control" type="date" name="event_date" value="<?php echo clean($event["event_date"]); ?>" required>
            </div>
            <div>
                <label>Start Time</label>
                <input class="form-control" type="time" name="start_time" value="<?php echo clean(substr($event["start_time"], 0, 5)); ?>">
            </div>
            <div>
                <label>End Time</label>
                <input class="form-control" type="time" name="end_time" value="<?php echo clean(substr($event["end_time"], 0, 5)); ?>">
            </div>
            <div>
                <label>Venue</label>
                <input class="form-control" name="venue" value="<?php echo clean($event["venue"]); ?>" required>
            </div>
            <div>
                <label>Max Participants</label>
                <input class="form-control" type="number" name="max_participants" value="<?php echo clean($event["max_participants"]); ?>">
            </div>
            <div>
                <label>Status</label>
                <select class="form-control" name="event_status">
                    <option <?php if ($event["event_status"] == "Open") echo "selected"; ?>>Open</option>
                    <option <?php if ($event["event_status"] == "Closed") echo "selected"; ?>>Closed</option>
                    <option <?php if ($event["event_status"] == "Cancelled") echo "selected"; ?>>Cancelled</option>
                    <option <?php if ($event["event_status"] == "Completed") echo "selected"; ?>>Completed</option>
                </select>
            </div>
            <div>
                <label>Waiting List</label>
                <select class="form-control" name="waiting_list_status">
                    <option value="Closed" <?php if (($event["waiting_list_status"] ?? "Closed") == "Closed") echo "selected"; ?>>Closed</option>
                    <option value="Open" <?php if (($event["waiting_list_status"] ?? "Closed") == "Open") echo "selected"; ?>>Open</option>
                </select>
            </div>
        </div>

        <label>Description</label>
        <textarea class="form-control" name="event_description" rows="4"><?php echo clean($event["event_description"]); ?></textarea>
    </div>

    <div class="actions">
        <a class="btn btn-light" href="manage_events.php">Cancel</a>
        <button class="btn" name="save">Save Changes</button>
    </div>
</form>
<?php page_end(); ?>
