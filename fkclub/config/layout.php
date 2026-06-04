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
        echo '<a class="' . is_active("committee_roles", $current) . '" href="../admin/committee_roles.php">🎓 <span class="text">Committee Roles</span></a>';
        echo '<a class="' . is_active("events", $current) . '" href="../admin/events.php">📅 <span class="text">Manage Events</span></a>';
        echo '<a class="' . is_active("reports", $current) . '" href="../admin/reports.php">📋 <span class="text">Reports</span></a>';
        echo '<a class="' . is_active("profile", $current) . '" href="../admin/profile.php">👤 <span class="text">Profile</span></a>';
    } elseif ($role == "committee") {
        echo '<a class="' . is_active("dashboard", $current) . '" href="../committee/dashboard.php">📊 <span class="text">Dashboard</span></a>';
        echo '<a class="' . is_active("create_event", $current) . '" href="../committee/create_event.php">➕ <span class="text">Create Event</span></a>';
        echo '<a class="' . is_active("manage_events", $current) . '" href="../committee/manage_events.php">📅 <span class="text">Manage Events</span></a>';
        echo '<a class="' . is_active("attendance", $current) . '" href="../committee/attendance.php">✅ <span class="text">Attendance</span></a>';
        echo '<a class="' . is_active("qr_checkin", $current) . '" href="../committee/qr_checkin.php">🔳 <span class="text">QR Check-In</span></a>';
        echo '<a class="' . is_active("participants", $current) . '" href="../committee/participant_recognition.php">🏆 <span class="text">Participants</span></a>';
        echo '<a class="' . is_active("attendance_report", $current) . '" href="../committee/attendance_report.php">📋 <span class="text">Attendance Report</span></a>';
    } else {
        echo '<a class="' . is_active("dashboard", $current) . '" href="../student/dashboard.php">📊 <span class="text">Dashboard</span></a>';
        echo '<a class="' . is_active("club_directory", $current) . '" href="../student/club_directory.php">🏛️ <span class="text">Club Directory</span></a>';
        echo '<a class="' . is_active("browse_events", $current) . '" href="../student/browse_events.php">📅 <span class="text">Browse Events</span></a>';
        echo '<a class="' . is_active("registrations", $current) . '" href="../student/my_registrations.php">🧾 <span class="text">My Registrations</span></a>';
        echo '<a class="' . is_active("recognition", $current) . '" href="../student/recognition.php">🏆 <span class="text">Recognition</span></a>';
        echo '<a class="' . is_active("profile", $current) . '" href="../student/profile.php">👤 <span class="text">Profile</span></a>';
    }

    echo '</div>';
    echo '<a class="logout" href="../auth/logout.php">↪ <span class="text">Logout</span></a>';
    echo '</div>';
}

function topbar() {
    $roleLabel = ucfirst($_SESSION["role"]);
    if (isset($_SESSION["account_role"]) && $_SESSION["account_role"] == "committee" && $_SESSION["role"] == "student") {
        $roleLabel = "Student";
    }

    echo '<div class="topbar">';
    echo '<div class="welcome">';
    echo 'Welcome, <b>' . clean($_SESSION["user_name"]) . '</b> ';

    if (isset($_SESSION["account_role"]) && $_SESSION["account_role"] == "committee") {
        echo '<div class="role-switch">';
        echo '<button type="button" class="role-chip" onclick="toggleRoleMenu(event)" title="Switch account view">';
        echo clean($roleLabel) . ' ▾';
        echo '</button>';

        echo '<div class="role-menu">';
        echo '<form method="POST" action="../auth/switch_role.php">';
        echo '<input type="hidden" name="target_role" value="committee">';
        echo '<button type="submit" class="role-option ' . ($_SESSION["role"] == "committee" ? "active" : "") . '">Committee</button>';
        echo '</form>';

        echo '<form method="POST" action="../auth/switch_role.php">';
        echo '<input type="hidden" name="target_role" value="student">';
        echo '<button type="submit" class="role-option ' . ($_SESSION["role"] == "student" ? "active" : "") . '">Student</button>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<span class="badge badge-blue">' . clean($roleLabel) . '</span>';
    }

    echo '</div>';
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
    echo '<link rel="stylesheet" href="../assets/style.css?v=roleui2">';
    echo '<script src="../assets/script.js?v=roleui2" defer></script>';
    echo '<script>window.addEventListener("pageshow",function(event){var nav=performance.getEntriesByType?performance.getEntriesByType("navigation")[0]:null;if(event.persisted||(nav&&nav.type==="back_forward")){window.location.reload();}});</script>';
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
