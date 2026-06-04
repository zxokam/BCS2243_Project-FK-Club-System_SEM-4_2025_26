<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

ensure_waiting_list_schema($conn);

if ($_SESSION["role"] != "committee") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$id = $_SESSION["user_id"];
$error = "";

if (isset($_POST["save"])) {
    $clubID = esc($conn, $_POST["clubID"]);
    $title = esc($conn, $_POST["event_title"]);
    $description = esc($conn, $_POST["event_description"]);
    $date = esc($conn, $_POST["event_date"]);
    $start = esc($conn, $_POST["start_time"]);
    $end = esc($conn, $_POST["end_time"]);
    $venue = esc($conn, $_POST["venue"]);
    $max = esc($conn, $_POST["max_participants"]);

    $committee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT eventCommitteeID FROM event_committee WHERE studentID = '$id' AND clubID = '$clubID' LIMIT 1"));
    $committeeID = $committee ? $committee["eventCommitteeID"] : "NULL";

    $sql = "INSERT INTO event
            (committeeID, clubID, created_by, event_title, event_description, event_date, start_time, end_time, venue, max_participants, event_status, waiting_list_status)
            VALUES
            ($committeeID, '$clubID', '$id', '$title', '$description', '$date', '$start', '$end', '$venue', '$max', 'Open', 'Closed')";

    if (mysqli_query($conn, $sql)) {
        header("Location: manage_events.php");
        exit();
    } else {
        $error = mysqli_error($conn);
    }
}

$clubs = mysqli_query($conn, "SELECT club.* FROM club JOIN event_committee ON club.clubID = event_committee.clubID WHERE event_committee.studentID = '$id'");

page_start("Create Event", "create_event");
?>
<div class="page-header">
    <div>
        <h1>Create Event</h1>
        <p class="subtitle">Committee creates event for assigned club.</p>
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
                <label>Club</label>
                <select class="form-control" name="clubID" required>
                    <?php while ($club = mysqli_fetch_assoc($clubs)) { ?>
                        <option value="<?php echo clean($club["clubID"]); ?>"><?php echo clean($club["club_name"]); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label>Event Title</label>
                <input class="form-control" name="event_title" required>
            </div>
            <div>
                <label>Event Date</label>
                <input class="form-control" type="date" name="event_date" required>
            </div>
            <div>
                <label>Venue</label>
                <input class="form-control" name="venue" required>
            </div>
            <div>
                <label>Start Time</label>
                <input class="form-control" type="time" name="start_time">
            </div>
            <div>
                <label>End Time</label>
                <input class="form-control" type="time" name="end_time">
            </div>
            <div>
                <label>Max Participants</label>
                <input class="form-control" type="number" name="max_participants" value="50">
            </div>
        </div>
        <label>Description</label>
        <textarea class="form-control" name="event_description" rows="4"></textarea>
    </div>

    <div class="actions">
        <a class="btn btn-light" href="manage_events.php">Cancel</a>
        <button class="btn" name="save">Create Event</button>
    </div>
</form>
<?php page_end(); ?>
