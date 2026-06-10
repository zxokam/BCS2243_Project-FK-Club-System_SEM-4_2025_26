<?php
include("../config/session_check.php");
include("../config/dbconnect.php");

if ($_SESSION["role"] != "admin") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$clubID = mysqli_real_escape_string($conn, $_GET["id"] ?? "");

$club = mysqli_fetch_assoc(mysqli_query($conn, "SELECT club_status FROM club WHERE clubID = '$clubID'"));

if ($club) {
    $new_status = ($club["club_status"] == "Active") ? "Inactive" : "Active";
    mysqli_query($conn, "UPDATE club SET club_status = '$new_status' WHERE clubID = '$clubID'");
}

header("Location: clubs.php");
exit();
?>
