<?php
include("../config/session_check.php");
include("../config/dbconnect.php");
include("../config/layout.php");

if ($_SESSION["role"] != "admin") {
    fk_redirect_dashboard_by_role($_SESSION["role"]);
    exit();
}

$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM student"))["total"];
$total_clubs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM club"))["total"];
$total_events = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM event"))["total"];
$total_members = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM membership WHERE membership_status='Approved'"))["total"];

page_start("Admin Dashboard", "dashboard");
?>
<div class="page-header">
    <div>
        <h1>Admin Dashboard</h1>
        <p class="subtitle">Overview of users, clubs and event activities.</p>
    </div>
</div>

<div class="grid grid-4">
    <?php stat_card("Total Users", $total_students, "👥", "blue"); ?>
    <?php stat_card("Active Members", $total_members, "✅", "green"); ?>
    <?php stat_card("Total Clubs", $total_clubs, "🏛️", "orange"); ?>
    <?php stat_card("Total Events", $total_events, "📅", "purple"); ?>
</div>

<br>

<div class="grid grid-2">
    <div class="panel">
        <h3>Monthly Registrations</h3>
        <div class="fake-chart">
            <div style="height:45%;"></div>
            <div style="height:55%;"></div>
            <div style="height:72%;"></div>
            <div style="height:80%;"></div>
            <div style="height:90%;"></div>
        </div>
        <p class="subtitle">Graphical layout for Progress 1 dashboard design.</p>
    </div>

    <div class="panel">
        <h3>Recent Users</h3>
        <table>
            <tr>
                <th>User</th>
                <th>Role</th>
                <th>Status</th>
            </tr>
            <?php
            $users = mysqli_query($conn, "SELECT * FROM student ORDER BY created_at DESC LIMIT 5");
            while ($user = mysqli_fetch_assoc($users)) {
            ?>
            <tr>
                <td class="user-cell">
                    <span class="mini-avatar"><?php echo clean(initials($user["student_name"])); ?></span>
                    <?php echo clean($user["student_name"]); ?>
                </td>
                <td>
                    <span class="badge <?php echo $user["student_role"] == "committee" ? "badge-orange" : "badge-green"; ?>">
                        <?php echo clean(ucfirst($user["student_role"])); ?>
                    </span>
                </td>
                <td><span class="badge badge-green">Active</span></td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>
<?php page_end(); ?>
