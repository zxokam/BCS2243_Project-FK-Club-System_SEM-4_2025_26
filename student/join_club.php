<?php
include("../config/session_check.php");
include("../config/dbconnect.php");

if ($_SESSION["role"] != "student") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$studentID = $_SESSION["user_id"];
$clubID = mysqli_real_escape_string($conn, $_GET["id"] ?? "");

$club = mysqli_fetch_assoc(mysqli_query($conn, "SELECT club_status FROM club WHERE clubID='$clubID'"));

if (!$club || $club["club_status"] != "Active") {
    header("Location: club_directory.php");
    exit();
}

$sql = "INSERT INTO membership (studentID, clubID, positionID, date_assigned, membership_status)
        VALUES ('$studentID', '$clubID', 6, CURDATE(), 'Pending')
        ON DUPLICATE KEY UPDATE membership_status='Pending'";

mysqli_query($conn, $sql);

header("Location: club_details.php?id=" . $clubID);
exit();
?>
