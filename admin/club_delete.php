<?php
include("../config/session_check.php");
include("../config/dbconnect.php");

if ($_SESSION["role"] != "admin") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET["id"]);
mysqli_query($conn, "DELETE FROM club WHERE clubID = '$id'");

header("Location: clubs.php");
exit();
?>
