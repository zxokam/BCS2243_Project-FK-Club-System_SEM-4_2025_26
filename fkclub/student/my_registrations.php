<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "student") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$id = $_SESSION["user_id"];

$sql = "SELECT event_registration.*, event.event_title, event.event_description, event.event_date, event.start_time, event.venue, attendance.attendance_status
        FROM event_registration
        JOIN event ON event_registration.eventID = event.eventID
        LEFT JOIN attendance ON event_registration.registrationID = attendance.registrationID
        WHERE event_registration.studentID = '$id'
        ORDER BY event_registration.registration_date DESC";
$result = mysqli_query($conn, $sql);

page_start("My Registrations", "registrations");
?>
<div class="page-header">
    <div>
        <h1>My Registrations</h1>
        <p class="subtitle">View, confirm and cancel your event registrations or waiting list queue.</p>
    </div>
</div>

<div class="grid">
    <?php while ($row = mysqli_fetch_assoc($result)) { 
        $isWaiting = $row["registration_status"] == "Waiting List";
        $isCancelled = $row["registration_status"] == "Cancelled";
        $isRejected = $row["registration_status"] == "Rejected";
    ?>
    <div class="event-card <?php if ($isCancelled) echo 'full'; ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <h3><?php echo clean($row["event_title"]); ?></h3>
                <p class="subtitle"><?php echo clean($row["event_description"]); ?></p>
                <?php if ($isWaiting) { ?>
                    <p class="subtitle"><b>Waiting list queue:</b> #<?php echo clean($row["queue_number"] ?: "-"); ?>. The committee will confirm your registration if you are accepted.</p>
                <?php } ?>
            </div>

            <div style="text-align:right;">
                <?php if ($isWaiting) { ?>
                    <span class="badge badge-orange">Waiting List #<?php echo clean($row["queue_number"] ?: "-"); ?></span>
                <?php } elseif ($row["confirmation_status"] == "Confirmed") { ?>
                    <span class="badge badge-green">Confirmed</span>
                <?php } elseif ($row["confirmation_status"] == "Rejected") { ?>
                    <span class="badge badge-red">Rejected</span>
                <?php } else { ?>
                    <span class="badge badge-orange">Pending</span>
                <?php } ?>

                <br><br>

                <?php if (!$isCancelled && !$isRejected) { ?>
                    <a class="btn btn-red btn-small"
                       href="cancel_registration.php?id=<?php echo clean($row["registrationID"]); ?>"
                       onclick="return confirm('Cancel this registration?')">
                       Cancel
                    </a>
                <?php } elseif ($isCancelled) { ?>
                    <span class="badge badge-red">Cancelled</span>
                <?php } else { ?>
                    <span class="badge badge-red">Rejected</span>
                <?php } ?>
            </div>
        </div>

        <br>

        <div class="grid grid-4">
            <p><b>Registration Date</b><br><?php echo clean(date("M d, Y", strtotime($row["registration_date"]))); ?></p>
            <p><b>Registration</b><br><?php echo clean($row["registration_status"]); ?></p>
            <p><b>Attendance</b><br><?php echo clean($row["attendance_status"] ?: "Not marked"); ?></p>
            <p><b>Student ID</b><br><?php echo clean($id); ?></p>
        </div>
    </div>
    <?php } ?>
</div>
<?php page_end(); ?>
