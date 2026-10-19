<?php
session_start();
include __DIR__ . '/../db_conn.php';

// 1. Security Check: Admin Only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php");
    exit();
}

// 2. Filter Variables
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'All';

$year = date('Y', strtotime($filter_month));
$month_num = date('m', strtotime($filter_month));

// 3. MASTER QUERY: Combines Scheduled days AND Unscheduled days where they clocked in
$sql = "
    SELECT 
        COALESCE(a.date, s.work_date) as log_date,
        u.fullname,
        a.time_in,
        a.time_out
    FROM users u
    JOIN employee_schedules s ON u.id = s.user_id
    LEFT JOIN attendance a ON s.user_id = a.user_id AND s.work_date = a.date
    WHERE u.role = 'employee' AND YEAR(s.work_date) = '$year' AND MONTH(s.work_date) = '$month_num'
    
    UNION
    
    SELECT 
        a.date as log_date,
        u.fullname,
        a.time_in,
        a.time_out
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN employee_schedules s ON a.user_id = s.user_id AND a.date = s.work_date
    WHERE s.id IS NULL AND u.role = 'employee' AND YEAR(a.date) = '$year' AND MONTH(a.date) = '$month_num'
    
    ORDER BY log_date DESC, time_in DESC
";
$logs_query = $conn->query($sql);

// 4. Pre-process rows so we can filter by calculated status
$html_rows = '';
$today = date('Y-m-d');

if ($logs_query && $logs_query->num_rows > 0) {
    while($row = $logs_query->fetch_assoc()) {
        $log_date = $row['log_date'];
        $time_in = $row['time_in'];
        $time_out = $row['time_out'];
        
        // SMART STATUS LOGIC
        if ($time_in && $time_out) {
            $status = "Completed";
            $badge = "badge-completed";
        } elseif ($time_in && !$time_out) {
            $status = "Active Shift";
            $badge = "badge-active";
        } elseif (!$time_in && $log_date < $today) {
            $status = "Absent";
            $badge = "badge-absent";
        } elseif (!$time_in && $log_date == $today) {
            $status = "Pending";
            $badge = "badge-pending";
        } else {
            $status = "Upcoming";
            $badge = "badge-upcoming";
        }

        // Apply Status Filter
        if ($filter_status !== 'All' && $status !== $filter_status) {
            continue; 
        }

        // Format times safely
        $disp_date = date('M d, Y', strtotime($log_date));
        $disp_in = $time_in ? date('h:i A', strtotime($time_in)) : '<span style="color:#ccc;">--:--</span>';
        $disp_out = $time_out ? date('h:i A', strtotime($time_out)) : '<span style="color:#ccc;">--:--</span>';
        $safe_name = htmlspecialchars($row['fullname']);

        $html_rows .= "
        <tr>
            <td class='date-col'>{$disp_date}</td>
            <td style='font-weight: 600;'>{$safe_name}</td>
            <td class='time-col'>{$disp_in}</td>
            <td class='time-col'>{$disp_out}</td>
            <td><span class='badge {$badge}'>{$status}</span></td>
        </tr>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Attendance Logs - SneakDup Admin</title>
    <link rel="icon" type="image/jpeg" href="../images/Sneakdup_logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* 1. APP LAYOUT RESET */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; background-color: #f4f6f8; color: #1a1a1a; 
            height: 100vh; overflow: hidden; display: flex;
        }

        /* 2. DARK SIDEBAR */
        .sidebar {
            width: 260px; background: #111; color: white; display: flex; flex-direction: column;
            padding: 25px; height: 100%; flex-shrink: 0;
        }
        .brand { 
            font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; 
            margin-bottom: 40px; color: white; letter-spacing: -1px; display: flex; align-items: center; gap: 8px;
        }
        .brand span { color: #cc0000; }
.admin-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    white-space: nowrap;
    box-sizing: border-box;

    background: #333;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 4px;
    margin-left: 5px;
    letter-spacing: 1px;
    line-height: 1;
}
        .menu-item {
            text-decoration: none; color: #aaa; padding: 12px 15px; margin-bottom: 8px;
            border-radius: 6px; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 12px;
        }
        .menu-item i { width: 18px; height: 18px; opacity: 0.7; transition: 0.3s; }
.menu-item.active {
    background: #222;
    color: white;
    border-left: 3px solid #ff0000;
    border-radius: 6px;
}
        .menu-item:hover:not(.active) { background: #222; color: white; }
        
        .logout-link { margin-top: auto; color: #cc0000; font-weight: 700; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 10px; padding: 12px 15px; border-radius: 6px; transition: 0.2s; }
        .logout-link i { width: 18px; height: 18px; }
        .logout-link:hover { background: rgba(204, 0, 0, 0.1); }

        /* 3. MAIN CONTENT AREA */
        .main-content { flex: 1; display: flex; flex-direction: column; height: 100%; overflow-y: auto; }
        
        .top-header {
            background: white; padding: 20px 40px; border-bottom: 1px solid #e5e5e5;
            display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10;
        }
        .page-title { font-family: 'Oswald', sans-serif; font-size: 28px; text-transform: uppercase; margin: 0; }
        
        .export-btn { 
            background: #111; color: white; border: none; padding: 10px 20px; 
            border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.2s; text-transform: uppercase;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .export-btn i { width: 16px; height: 16px; }
        .export-btn:hover { background: #cc0000; }

        .content-wrapper { padding: 40px; max-width: 1100px; margin: 0 auto; width: 100%; }

        /* FILTER BAR */
        .filter-bar {
            background: white; border: 1px solid #eee; border-radius: 12px; padding: 20px 25px;
            display: flex; gap: 20px; align-items: flex-end; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 12px; font-weight: 700; color: #666; text-transform: uppercase; }
        .filter-bar select, .filter-bar input { padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Inter'; font-size: 14px; outline: none; min-width: 180px; }
        .filter-submit { background: #cc0000; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 700; cursor: pointer; transition: 0.2s; height: 40px;}
        .filter-submit:hover { background: #aa0000; }

        /* 4. TABLE STYLES */
        .table-card { background: white; border-radius: 12px; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; }
        .card-header { padding: 20px 25px; border-bottom: 1px solid #eee; background: #fafafa; display: flex; justify-content: space-between; align-items: center; }
        .header-title-group { display: flex; align-items: center; gap: 10px; }
        .header-title-group i { width: 22px; height: 22px; color: #111; }
        .card-header h2 { font-family: 'Oswald', sans-serif; font-size: 18px; text-transform: uppercase; margin: 0; }

        table { width: 100%; border-collapse: collapse; }
        th { background: white; text-align: left; padding: 15px 25px; font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; border-bottom: 1px solid #eee; }
        td { padding: 15px 25px; font-size: 14px; color: #333; border-bottom: 1px solid #f9f9f9; font-weight: 500; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #fcfcfc; }

        .date-col { font-weight: 700; color: #111; }
        .time-col { font-family: 'Courier New', monospace; font-weight: bold; color: #555; }
        
        /* Dynamic Badges */
        .badge { padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; display: inline-block; letter-spacing: 0.5px;}
        .badge-active { background: #fef08a; color: #854d0e; }
        .badge-completed { background: #dcfce7; color: #166534; }
        .badge-absent { background: #fee2e2; color: #991b1b; }
        .badge-pending { background: #f1f5f9; color: #475569; }
        .badge-upcoming { background: #e0f2fe; color: #0284c7; }

        @media print {
            .sidebar, .export-btn, .filter-bar { display: none !important; }
            .main-content { overflow: visible; }
            .table-card { box-shadow: none; border: none; }
        }

       /* POS MAIN LAYOUT */
.admin-layout {
    display: flex;
    width: 100%;
    height: 100vh;
    overflow: hidden;
}

.pos-layout {
    display: flex;
    flex: 1;
    width: 100%;
    min-width: 0;
    height: 100%;
    overflow: hidden;
}

.product-panel {
    flex: 1;
    min-width: 0;
    overflow-y: auto;
}

.cart-panel {
    flex: 0 0 350px;
    width: 350px;
    min-width: 350px;
    box-sizing: border-box;
    height: 100%;
    display: flex;
    flex-direction: column;
    background: white;
    border-left: 1px solid #ddd;
}
    </style>
</head>
<body>

  <div class="admin-layout">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="top-header">
            <h1 class="page-title">Staff Attendance</h1>
            <button class="export-btn" onclick="window.print()">
                <i data-lucide="printer"></i> Print Log
            </button>
        </div>

        <div class="content-wrapper">
            
            <form class="filter-bar" method="GET" action="">
                <div class="filter-group">
                    <label>Filter by Month</label>
                    <input type="month" name="month" value="<?php echo htmlspecialchars($filter_month); ?>">
                </div>
                <div class="filter-group">
                    <label>Filter by Status</label>
                    <select name="status">
                        <option value="All" <?php if($filter_status == 'All') echo 'selected'; ?>>All Statuses</option>
                        <option value="Active Shift" <?php if($filter_status == 'Active Shift') echo 'selected'; ?>>Active Shift</option>
                        <option value="Completed" <?php if($filter_status == 'Completed') echo 'selected'; ?>>Completed</option>
                        <option value="Absent" <?php if($filter_status == 'Absent') echo 'selected'; ?>>Absent</option>
                        <option value="Pending" <?php if($filter_status == 'Pending') echo 'selected'; ?>>Pending Today</option>
                        <option value="Upcoming" <?php if($filter_status == 'Upcoming') echo 'selected'; ?>>Upcoming</option>
                    </select>
                </div>
                <button type="submit" class="filter-submit">Apply Filters</button>
            </form>

            <div class="table-card">
                <div class="card-header">
                    <div class="header-title-group">
                        <i data-lucide="clipboard-list"></i>
                        <h2>Shift History</h2>
                    </div>
                </div>
                
                <?php if ($html_rows != ''): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee Name</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $html_rows; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div style="padding:60px; text-align:center; color:#888; font-weight: 500;">
                        <div style="margin-bottom: 15px; opacity: 0.5; display: flex; justify-content: center;">
                            <i data-lucide="clipboard-x" style="width: 40px; height: 40px;"></i>
                        </div>
                        No records found for these filters.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>