<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "student") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$id = $_SESSION["user_id"];
$my_reg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM event_registration WHERE studentID = '$id' AND registration_status = 'Registered'"))["total"];
$my_waiting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM event_registration WHERE studentID = '$id' AND registration_status = 'Waiting List'"))["total"];
$my_club = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM membership WHERE studentID = '$id'"))["total"];
$points = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(point_earned), 0) AS total FROM attendance WHERE studentID = '$id'"))["total"];

page_start("Student Dashboard", "dashboard");
?>
<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p class="subtitle">Overview of your club and event activities.</p>
    </div>
</div>

<div class="grid grid-4">
    <?php stat_card("My Clubs", $my_club, "🏛️", "blue"); ?>
    <?php stat_card("Registered Events", $my_reg, "📅", "green"); ?>
    <?php stat_card("Waiting List", $my_waiting, "🧾", "purple"); ?>
    <?php stat_card("Total Points", $points, "⭐", "orange"); ?>
</div>

<br>

<div class="panel">
    <h3>Upcoming Events</h3>
    <p class="subtitle">Browse club events and manage your event registrations.</p>
    <br>
    <a class="btn" href="browse_events.php">Browse Events</a>
</div>
<?php page_end(); ?>
