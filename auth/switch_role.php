<?php
session_start();

// Prevent browser cache / Back button from showing the wrong view after switching role.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (!isset($_SESSION["role"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION["account_role"]) || $_SESSION["account_role"] != "committee") {
    if ($_SESSION["role"] == "admin") {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../student/dashboard.php");
    }
    exit();
}

$targetRole = $_POST["target_role"] ?? $_GET["target_role"] ?? "";

if ($targetRole == "student") {
    $_SESSION["role"] = "student";
    header("Location: ../student/dashboard.php");
    exit();
}

if ($targetRole == "committee") {
    $_SESSION["role"] = "committee";
    header("Location: ../committee/dashboard.php");
    exit();
}

header("Location: ../committee/dashboard.php");
exit();
?>
