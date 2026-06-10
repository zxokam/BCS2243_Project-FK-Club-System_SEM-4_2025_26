<?php
include_once("../config/functions.php");

function sidebar($current) {
    $role = $_SESSION["role"];

    echo '<div class="sidebar">';
    echo '<div class="brand">';
    echo '<div class="brand-icon">FK</div>';
    echo '<div><div class="brand-title">FK Club System</div><div class="brand-subtitle">Localhost Portal</div></div>';
    echo '</div>';
    echo '<div class="menu">';

    if ($role == "admin") {
        echo '<a class="' . is_active("dashboard", $current) . '" href="../admin/dashboard.php">📊 <span class="text">Dashboard</span></a>';
        echo '<a class="' . is_active("users", $current) . '" href="../admin/users.php">👥 <span class="text">Manage Users</span></a>';
        echo '<a class="' . is_active("clubs", $current) . '" href="../admin/clubs.php">🏛️ <span class="text">Manage Clubs</span></a>';
        echo '<a class="' . is_active("events", $current) . '" href="../admin/events.php">📅 <span class="text">Manage Events</span></a>';
        echo '<a class="' . is_active("reports", $current) . '" href="../admin/reports.php">📋 <span class="text">Reports</span></a>';
        echo '<a class="' . is_active("profile", $current) . '" href="../admin/profile.php">👤 <span class="text">Profile</span></a>';
    } elseif ($role == "committee") {
        echo '<a class="' . is_active("dashboard", $current) . '" href="../committee/dashboard.php">📊 <span class="text">Dashboard</span></a>';
        echo '<a class="' . is_active("create_event", $current) . '" href="../committee/create_event.php">➕ <span class="text">Create Event</span></a>';
        echo '<a class="' . is_active("manage_events", $current) . '" href="../committee/manage_events.php">📅 <span class="text">Manage Events</span></a>';
        echo '<a class="' . is_active("attendance", $current) . '" href="../committee/attendance.php">✅ <span class="text">Attendance</span></a>';
    } else {
        echo '<a class="' . is_active("dashboard", $current) . '" href="../student/dashboard.php">📊 <span class="text">Dashboard</span></a>';
        echo '<a class="' . is_active("browse_events", $current) . '" href="../student/browse_events.php">📅 <span class="text">Browse Events</span></a>';
        echo '<a class="' . is_active("registrations", $current) . '" href="../student/my_registrations.php">🧾 <span class="text">My Registrations</span></a>';
        echo '<a class="' . is_active("profile", $current) . '" href="../student/profile.php">👤 <span class="text">Profile</span></a>';
    }

    echo '</div>';
    echo '<a class="logout" href="../auth/logout.php">↪ <span class="text">Logout</span></a>';
    echo '</div>';
}

function topbar() {
    echo '<div class="topbar">';
    echo '<div class="welcome">Welcome, <b>' . clean($_SESSION["user_name"]) . '</b></div>';
    echo '<div class="avatar">' . clean(initials($_SESSION["user_name"])) . '</div>';
    echo '</div>';
}

function page_start($title, $current) {
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . clean($title) . ' - FK Club System</title>';
    echo '<link rel="stylesheet" href="../assets/style.css">';
    echo '</head>';
    echo '<body>';
    sidebar($current);
    echo '<div class="main">';
    topbar();
    echo '<div class="page">';
}

function page_end() {
    echo '</div></div>';
    echo '</body></html>';
}

function stat_card($label, $value, $icon, $color) {
    echo '<div class="card">';
    echo '<div><span>' . clean($label) . '</span><h2>' . clean($value) . '</h2></div>';
    echo '<div class="card-icon icon-' . clean($color) . '">' . $icon . '</div>';
    echo '</div>';
}
?>
