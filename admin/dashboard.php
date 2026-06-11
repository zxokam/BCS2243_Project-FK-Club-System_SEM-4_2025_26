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

$total_admins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM admin"))["total"];
$total_committees = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM student WHERE student_role='committee'"))["total"];
$total_students_only = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM student WHERE student_role='student'"))["total"];

$distribution_total = $total_students_only + $total_committees + $total_admins;

if ($distribution_total > 0) {
    $student_pct = round(($total_students_only / $distribution_total) * 100, 1);
    $committee_pct = round(($total_committees / $distribution_total) * 100, 1);
    $admin_pct = round(100 - $student_pct - $committee_pct, 1);
} else {
    $student_pct = 0;
    $committee_pct = 0;
    $admin_pct = 0;
}

$monthly_data = [];
for ($i = 4; $i >= 0; $i--) {
    $key = date("Y-m", strtotime("-$i month"));
    $monthly_data[$key] = [
        "label" => date("M", strtotime("-$i month")),
        "total" => 0
    ];
}

$monthly_query = mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key,
           DATE_FORMAT(created_at, '%b') AS month_label,
           COUNT(*) AS total
    FROM student
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 4 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b')
    ORDER BY month_key ASC
");

while ($row = mysqli_fetch_assoc($monthly_query)) {
    if (isset($monthly_data[$row["month_key"]])) {
        $monthly_data[$row["month_key"]]["total"] = (int)$row["total"];
        $monthly_data[$row["month_key"]]["label"] = $row["month_label"];
    }
}

$max_month_total = 1;
foreach ($monthly_data as $item) {
    if ($item["total"] > $max_month_total) {
        $max_month_total = $item["total"];
    }
}

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

<div style="display:grid; grid-template-columns:2fr 1fr; gap:18px;">
    <div class="panel">
        <h3>Monthly Registrations</h3>

        <div style="height:240px; display:flex; align-items:flex-end; gap:16px; padding:14px 8px 10px; border-bottom:1px solid #e6edf5;">
            <?php foreach ($monthly_data as $item): 
                $height = ($item["total"] / $max_month_total) * 100;
                if ($height < 8) {
                    $height = 8;
                }
            ?>
                <div style="flex:1; text-align:center;">
                    <div style="font-size:11px; color:#718096; margin-bottom:6px; font-weight:600;">
                        <?php echo clean($item["total"]); ?>
                    </div>
                    <div style="height:165px; display:flex; align-items:flex-end; justify-content:center;">
                        <div style="width:100%; max-width:72px; height:<?php echo clean($height); ?>%; background:#2563eb; border-radius:9px 9px 0 0;"></div>
                    </div>
                    <div style="font-size:12px; color:#718096; margin-top:9px; font-weight:600;">
                        <?php echo clean($item["label"]); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="subtitle">Student registration trend for the last 5 months.</p>
    </div>

    <div class="panel">
        <h3>User Role Distribution</h3>

        <div style="display:flex; flex-direction:column; align-items:center; gap:16px; padding:10px 0 4px;">
            <div style="
                width:175px;
                height:175px;
                border-radius:50%;
                background:conic-gradient(
                    #22c55e 0% <?php echo clean($student_pct); ?>%,
                    #f97316 <?php echo clean($student_pct); ?>% <?php echo clean($student_pct + $committee_pct); ?>%,
                    #2563eb <?php echo clean($student_pct + $committee_pct); ?>% 100%
                );
                display:grid;
                place-items:center;
            ">
                <div style="
                    width:108px;
                    height:108px;
                    background:white;
                    border-radius:50%;
                    display:grid;
                    place-items:center;
                    text-align:center;
                    border:1px solid #e6edf5;
                ">
                    <div>
                        <div style="font-size:28px; font-weight:bold; color:#172033; line-height:1;">
                            <?php echo clean($distribution_total); ?>
                        </div>
                        <div style="font-size:12px; color:#718096; margin-top:4px;">Users</div>
                    </div>
                </div>
            </div>

            <div style="width:100%; max-width:260px;">
                <div style="display:grid; grid-template-columns:14px 1fr auto; align-items:center; gap:9px; margin-bottom:10px; font-size:13px;">
                    <span style="width:10px; height:10px; background:#22c55e; border-radius:50%; display:inline-block;"></span>
                    <span>Student</span>
                    <strong><?php echo clean($student_pct); ?>%</strong>
                </div>

                <div style="display:grid; grid-template-columns:14px 1fr auto; align-items:center; gap:9px; margin-bottom:10px; font-size:13px;">
                    <span style="width:10px; height:10px; background:#f97316; border-radius:50%; display:inline-block;"></span>
                    <span>Committee</span>
                    <strong><?php echo clean($committee_pct); ?>%</strong>
                </div>

                <div style="display:grid; grid-template-columns:14px 1fr auto; align-items:center; gap:9px; margin-bottom:10px; font-size:13px;">
                    <span style="width:10px; height:10px; background:#2563eb; border-radius:50%; display:inline-block;"></span>
                    <span>Admin</span>
                    <strong><?php echo clean($admin_pct); ?>%</strong>
                </div>
            </div>
        </div>

        <p class="subtitle">Distribution of users by role in the system.</p>
    </div>
</div>

<br>

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
            <td>
                <?php $student_status = $user["student_status"] ?? "Active"; ?>
                <span class="badge <?php echo $student_status == "Active" ? "badge-green" : "badge-red"; ?>">
                    <?php echo clean($student_status); ?>
                </span>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>
<?php page_end(); ?>
