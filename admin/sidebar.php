<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
.brand {
    display: flex;
    flex-direction: row;
    align-items: center;
    flex-wrap: nowrap;
    gap: 8px;
    width: 100%;
    margin-bottom: 40px;
    color: white;
}

.brand img {
    width: 35px;
    height: 35px;
    flex-shrink: 0;
    border-radius: 6px;
    object-fit: cover;
}

.brand > div:first-of-type {
    display: flex;
    align-items: center;
    white-space: nowrap;
    flex-shrink: 0;

    font-family: 'Oswald', sans-serif;
    font-size: 20px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: -1px;
    line-height: 1;
    
}

.brand > div:first-of-type span {
    color: #cc0000;
    font-family: 'Oswald', sans-serif;
    font-weight: 700;
}


.admin-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    white-space: nowrap;
    background: #333;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 4px;
    margin-left: 2px;
    line-height: 1;
}
</style>

<div class="sidebar">

    <div class="brand">
        <img src="../images/Sneakdup_logo.jpg"
             alt="Logo"
             style="width:35px;height:35px;border-radius:6px;object-fit:cover;">

        <div style="display:flex;align-items:center;">
            SNEAK<span>D</span>UP
        </div>

        <div class="admin-badge">ADMIN</div>
    </div>

    <a href="dashboard.php"
       class="menu-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
        <i data-lucide="layout-dashboard"></i> Dashboard
    </a>

    <a href="ai_analyze.php"
       class="menu-item <?= $currentPage === 'ai_analyze.php' ? 'active' : '' ?>">
        <i data-lucide="brain-circuit"></i> AI Analyze
    </a>

    <a href="pos.php"
       class="menu-item <?= $currentPage === 'pos.php' ? 'active' : '' ?>">
        <i data-lucide="monitor-smartphone"></i> POS Terminal
    </a>

    <a href="inventory.php"
       class="menu-item <?= $currentPage === 'inventory.php' ? 'active' : '' ?>">
        <i data-lucide="boxes"></i> Inventory
    </a>

    <a href="manage_users.php"
       class="menu-item <?= $currentPage === 'manage_users.php' ? 'active' : '' ?>">
        <i data-lucide="users"></i> Manage Staff
    </a>

    <a href="manage_schedule.php"
       class="menu-item <?= $currentPage === 'manage_schedule.php' ? 'active' : '' ?>">
        <i data-lucide="calendar-days"></i> Staff Schedule
    </a>

    <a href="attendance_logs.php"
       class="menu-item <?= $currentPage === 'attendance_logs.php' ? 'active' : '' ?>">
        <i data-lucide="clock"></i> Attendance Logs
    </a>

    <a href="sales_report.php"
       class="menu-item <?= $currentPage === 'sales_report.php' ? 'active' : '' ?>">
        <i data-lucide="bar-chart-3"></i> Sales Reports
    </a>

    <a href="inbox.php"
       class="menu-item <?= $currentPage === 'inbox.php' ? 'active' : '' ?>">
        <i data-lucide="inbox"></i> Customer Inbox
    </a>

    <a href="../shop.php" class="menu-item" style="margin-top:15px;">
        <i data-lucide="store"></i> View Shop
    </a>

    <a href="../logout.php" class="logout-link">
        <i data-lucide="log-out"></i> LOGOUT
    </a>

</div>