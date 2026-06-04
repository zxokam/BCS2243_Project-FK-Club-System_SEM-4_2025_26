<?php
include("../config/session_check.php");
include("../config/dbconnect.php");

if ($_SESSION["role"] != "committee") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$committeeID = $_SESSION["user_id"];
$eventID = mysqli_real_escape_string($conn, $_GET["id"]);

mysqli_query($conn, "DELETE FROM event WHERE eventID='$eventID' AND created_by='$committeeID'");

header("Location: manage_events.php");
exit();
?>
