<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "committee") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

ensure_waiting_list_schema($conn);

$committeeID = $_SESSION["user_id"];
$eventID = isset($_POST["eventID"]) ? mysqli_real_escape_string($conn, $_POST["eventID"]) : mysqli_real_escape_string($conn, $_GET["id"] ?? "");

function redirect_waiting($eventID, $msg) {
    header("Location: waiting_list.php?id=" . urlencode($eventID) . "&msg=" . urlencode($msg));
    exit();
}

if (isset($_POST["action"])) {
    $action = $_POST["action"];

    if ($action == "open") {
        update_waiting_list_status($conn, $eventID, $committeeID, "Open");
        redirect_waiting($eventID, "opened");
    }

    if ($action == "close") {
        update_waiting_list_status($conn, $eventID, $committeeID, "Closed");
        redirect_waiting($eventID, "closed");
    }

    if ($action == "accept_next") {
        $next = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT er.registrationID
            FROM event_registration er
            JOIN event e ON er.eventID = e.eventID
            WHERE er.eventID = '$eventID'
            AND e.created_by = '$committeeID'
            AND er.registration_status = 'Waiting List'
            ORDER BY er.queue_number ASC, er.registration_date ASC, er.registrationID ASC
            LIMIT 1
        "));

        if ($next && accept_waiting_registration($conn, $next["registrationID"], $committeeID)) {
            redirect_waiting($eventID, "accepted");
        }
        redirect_waiting($eventID, "notfound");
    }

    if ($action == "accept_selected") {
        $registrationID = $_POST["registrationID"] ?? "";
        if (accept_waiting_registration($conn, $registrationID, $committeeID)) {
            redirect_waiting($eventID, "accepted");
        }
        redirect_waiting($eventID, "notfound");
    }

    if ($action == "reject_selected") {
        $registrationID = $_POST["registrationID"] ?? "";
        if (reject_waiting_registration($conn, $registrationID, $committeeID)) {
            redirect_waiting($eventID, "rejected");
        }
        redirect_waiting($eventID, "notfound");
    }

    if ($action == "reject_all") {
        reject_all_waiting_list($conn, $eventID, $committeeID);
        redirect_waiting($eventID, "rejectedall");
    }
}

$event = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT event.*, club.club_name
    FROM event
    JOIN club ON event.clubID = club.clubID
    WHERE event.eventID = '$eventID'
    AND event.created_by = '$committeeID'
    LIMIT 1
"));

if (!$event) {
    header("Location: manage_events.php");
    exit();
}

$summary = registration_summary($conn, $eventID);
$maxParticipants = (int)$event["max_participants"];
$full = $maxParticipants > 0 && $summary["registered"] >= $maxParticipants;
$waitingOpen = waiting_list_is_open($event);

$waiting = mysqli_query($conn, "
    SELECT er.*, s.student_name, s.student_email, s.program
    FROM event_registration er
    JOIN student s ON er.studentID = s.studentID
    WHERE er.eventID = '$eventID'
    AND er.registration_status = 'Waiting List'
    ORDER BY er.queue_number ASC, er.registration_date ASC, er.registrationID ASC
");

$history = mysqli_query($conn, "
    SELECT er.*, s.student_name
    FROM event_registration er
    JOIN student s ON er.studentID = s.studentID
    WHERE er.eventID = '$eventID'
    AND er.registration_status IN ('Registered', 'Rejected')
    ORDER BY er.registration_date DESC, er.registrationID DESC
    LIMIT 8
");

$messages = [
    "opened" => "Waiting list opened. Students can now join the waiting list when the event is full.",
    "closed" => "Waiting list closed. Students cannot join the waiting list while it is closed.",
    "accepted" => "Waiting-list student accepted successfully.",
    "rejected" => "Waiting-list student rejected successfully.",
    "rejectedall" => "All waiting-list students were rejected.",
    "notfound" => "No waiting-list record was found for that action."
];
$msgKey = $_GET["msg"] ?? "";

page_start("Waiting List", "manage_events");
?>
<div class="page-header">
    <div>
        <h1>Waiting List</h1>
        <p class="subtitle"><?php echo clean($event["event_title"]); ?> · <?php echo clean($event["club_name"]); ?></p>
    </div>
    <a class="btn btn-light" href="manage_events.php">Back to Events</a>
</div>

<?php if (isset($messages[$msgKey])) { ?>
    <div class="alert alert-success"><?php echo clean($messages[$msgKey]); ?></div>
<?php } ?>

<div class="grid grid-4" style="margin-bottom:18px;">
    <?php stat_card("Registered", $summary["registered"] . " / " . $event["max_participants"], "👥", "blue"); ?>
    <?php stat_card("Waiting", $summary["waiting"], "🧾", "orange"); ?>
    <?php stat_card("Waiting List", $waitingOpen ? "Open" : "Closed", "📋", "purple"); ?>
    <?php stat_card("Event Status", $event["event_status"], "📅", "green"); ?>
</div>

<div class="panel" style="margin-bottom:18px;">
    <h3>Committee Waiting List Control</h3>
    <p class="subtitle" style="margin-bottom:14px;">Create/open the waiting list when the event is full. Accepting a student from a full event will increase max participants by one, so the accepted student is counted properly.</p>
    <div class="actions" style="justify-content:flex-start;">
        <?php if (!$waitingOpen) { ?>
            <form method="POST">
                <input type="hidden" name="eventID" value="<?php echo clean($eventID); ?>">
                <input type="hidden" name="action" value="open">
                <button class="btn" <?php if (!$full) echo "disabled style='opacity:.55;cursor:not-allowed;'"; ?>><?php echo $full ? "Create Waiting List" : "Create Waiting List When Full"; ?></button>
            </form>
        <?php } else { ?>
            <form method="POST">
                <input type="hidden" name="eventID" value="<?php echo clean($eventID); ?>">
                <input type="hidden" name="action" value="close">
                <button class="btn btn-light" onclick="return confirm('Close waiting list for this event?')">Close Waiting List</button>
            </form>
        <?php } ?>

        <form method="POST">
            <input type="hidden" name="eventID" value="<?php echo clean($eventID); ?>">
            <input type="hidden" name="action" value="accept_next">
            <button class="btn" <?php if ($summary["waiting"] <= 0) echo "disabled style='opacity:.55;cursor:not-allowed;'"; ?>>Accept Next Student</button>
        </form>

        <form method="POST">
            <input type="hidden" name="eventID" value="<?php echo clean($eventID); ?>">
            <input type="hidden" name="action" value="reject_all">
            <button class="btn btn-red" <?php if ($summary["waiting"] <= 0) echo "disabled style='opacity:.55;cursor:not-allowed;'"; ?> onclick="return confirm('Reject all waiting-list students?')">Reject All</button>
        </form>
    </div>
</div>

<div class="table-box" style="margin-bottom:18px;">
    <table>
        <tr>
            <th>Queue</th>
            <th>Student</th>
            <th>Program</th>
            <th>Registration Date</th>
            <th>Action</th>
        </tr>
        <?php if (mysqli_num_rows($waiting) == 0) { ?>
            <tr><td colspan="5">No students in waiting list.</td></tr>
        <?php } ?>
        <?php while ($row = mysqli_fetch_assoc($waiting)) { ?>
            <tr>
                <td><span class="badge badge-orange">#<?php echo clean($row["queue_number"] ?: "-"); ?></span></td>
                <td><b><?php echo clean($row["student_name"]); ?></b><br><small><?php echo clean($row["studentID"]); ?> · <?php echo clean($row["student_email"]); ?></small></td>
                <td><?php echo clean($row["program"]); ?></td>
                <td><?php echo clean($row["registration_date"]); ?></td>
                <td>
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="eventID" value="<?php echo clean($eventID); ?>">
                        <input type="hidden" name="registrationID" value="<?php echo clean($row["registrationID"]); ?>">
                        <input type="hidden" name="action" value="accept_selected">
                        <button class="btn btn-small" onclick="return confirm('Accept this student from waiting list?')">Accept</button>
                    </form>
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="eventID" value="<?php echo clean($eventID); ?>">
                        <input type="hidden" name="registrationID" value="<?php echo clean($row["registrationID"]); ?>">
                        <input type="hidden" name="action" value="reject_selected">
                        <button class="btn btn-red btn-small" onclick="return confirm('Reject this waiting-list student?')">Reject</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Recent Decision / Registration</th>
            <th>Student</th>
            <th>Status</th>
            <th>Confirmation</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($history)) { ?>
        <tr>
            <td><?php echo clean($row["registration_date"]); ?></td>
            <td><?php echo clean($row["student_name"]); ?> <br><small><?php echo clean($row["studentID"]); ?></small></td>
            <td><span class="badge <?php echo $row["registration_status"] == "Rejected" ? "badge-red" : "badge-green"; ?>"><?php echo clean($row["registration_status"]); ?></span></td>
            <td><?php echo clean($row["confirmation_status"]); ?></td>
        </tr>
        <?php } ?>
    </table>
</div>
<?php page_end(); ?>
