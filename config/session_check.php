<?php
session_start();

// Prevent browser cache / Back button from showing old protected pages after logout or account switch.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

function fk_redirect_dashboard_by_role($role) {
    if ($role == "admin") {
        header("Location: ../admin/dashboard.php");
    } elseif ($role == "committee") {
        header("Location: ../committee/dashboard.php");
    } elseif ($role == "student") {
        header("Location: ../student/dashboard.php");
    } else {
        header("Location: ../auth/login.php");
    }
    exit();
}

if (!isset($_SESSION["role"])) {
    header("Location: ../auth/login.php");
    exit();
}

// If the user changes account/role and then presses browser Back, do not show the old role page.
// Send the current session to the correct dashboard instead of the login page.
$currentPath = str_replace('\\', '/', $_SERVER["SCRIPT_NAME"] ?? "");
$currentArea = "";
if (strpos($currentPath, "/admin/") !== false) {
    $currentArea = "admin";
} elseif (strpos($currentPath, "/committee/") !== false) {
    $currentArea = "committee";
} elseif (strpos($currentPath, "/student/") !== false) {
    $currentArea = "student";
}

if ($currentArea != "" && $currentArea != $_SESSION["role"]) {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
}
?>
