<?php
session_start();
include '../db_conn.php'; 

// Set timezone to Philippines to ensure accurate time logs
date_default_timezone_set('Asia/Manila');

// 1. Security Check: Is user logged in?
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// 2. Role Check: Is this user actually an EMPLOYEE?
if ($_SESSION['role'] !== 'employee') {
    header("Location: ../index.php"); 
    exit();
}

$employee_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- 3. ATTENDANCE LOGIC (Untouched) ---
// Check current attendance status for today
$att_query = $conn->query("SELECT * FROM attendance WHERE user_id = $employee_id AND date = '$today'");
$attendance = $att_query->fetch_assoc();

$is_clocked_in = false;
$is_clocked_out = false;
$time_in_display = "--:--";
$time_out_display = "--:--";

if ($attendance) {
    if (!empty($attendance['time_in'])) {
        $is_clocked_in = true;
        $time_in_display = date('h:i A', strtotime($attendance['time_in']));
    }
    if (!empty($attendance['time_out'])) {
        $is_clocked_out = true;
        $is_clocked_in = false; // Shift is over
        $time_out_display = date('h:i A', strtotime($attendance['time_out']));
    }
}

// Handle the Clock In / Clock Out button press
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['attendance_action'])) {
    if ($_POST['attendance_action'] == 'clock_in' && !$attendance) {
        $conn->query("INSERT INTO attendance (user_id, date, time_in) VALUES ($employee_id, '$today', NOW())");
        header("Location: orders.php"); 
        exit();
    } elseif ($_POST['attendance_action'] == 'clock_out' && $is_clocked_in) {
        $conn->query("UPDATE attendance SET time_out = NOW() WHERE user_id = $employee_id AND date = '$today'");
        header("Location: orders.php"); 
        exit();
    }
}

// 4. Get Employee's Sales for today
$sales_query = $conn->query("SELECT SUM(total_price) as total FROM sales WHERE user_id = $employee_id AND DATE(sale_date) = '$today'");
$sales_data = $sales_query->fetch_assoc();
$my_total_sales = $sales_data['total'] ? $sales_data['total'] : 0;

// 5. FETCH EMPLOYEE'S ASSIGNED SCHEDULE FOR THIS MONTH
$current_month = date('m');
$current_year = date('Y');
$month_name = date('F Y');

$schedule_query = $conn->query("
    SELECT work_date 
    FROM employee_schedules 
    WHERE user_id = $employee_id 
    AND MONTH(work_date) = '$current_month' 
    AND YEAR(work_date) = '$current_year'
    ORDER BY work_date ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Staff Dashboard - SneakDup</title>
    <link rel="icon" type="image/jpeg" href="../images/Sneakdup_logo.jpg">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    
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
            margin-bottom: 40px; color: white; letter-spacing: -1px; display: flex; align-items: center; gap: 10px;
        }
        .brand-text { white-space: nowrap; }
        .brand span { color: #cc0000; }
        .brand img { height: 35px; width: auto; border-radius: 6px; }

        .staff-badge {
            background: #333; color: #fff; font-size: 10px; padding: 4px 8px; 
            border-radius: 4px; margin-left: auto; letter-spacing: 1px; 
        }
        
        .menu-item {
            text-decoration: none; color: #aaa; padding: 12px 15px; margin-bottom: 8px;
            border-radius: 6px; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 12px;
        }
        .menu-item .lucide { width: 18px; height: 18px; }
        .menu-item.active { background: #cc0000; color: white; border-left: 3px solid #ff4d4d; }
        .menu-item:hover:not(.active) { background: #222; color: white; }
        
        .user-info { margin-top: auto; padding-top: 20px; border-top: 1px solid #333; font-size: 13px; color: #888; }
        .user-name { color: white; font-weight: 700; display: block; margin-bottom: 15px; }
        .logout-link { color: #cc0000; font-weight: 700; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .logout-link .lucide { width: 16px; height: 16px; }
        .logout-link:hover { color: #ff4d4d; }

        /* 3. MAIN CONTENT AREA */
        .main-content { flex: 1; display: flex; flex-direction: column; height: 100%; overflow-y: auto; }
        
        .top-header {
            background: white; padding: 20px 40px; border-bottom: 1px solid #e5e5e5;
            display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10;
        }
        .page-title { font-family: 'Oswald', sans-serif; font-size: 28px; text-transform: uppercase; margin: 0; }
        .date-display { display: flex; align-items: center; gap: 8px; font-size: 14px; color: #666; font-weight: 500; }
        .date-display .lucide { width: 16px; height: 16px; color: #cc0000; }

        .content-wrapper { padding: 40px; max-width: 1100px; margin: 0 auto; width: 100%; }

        /* 4. WELCOME & ATTENDANCE ROW */
        .header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 35px; }
        .welcome-section h1 { display: flex; align-items: center; gap: 10px; font-family: 'Oswald', sans-serif; font-size: 36px; text-transform: uppercase; margin-bottom: 5px; color: #111; }
        .welcome-section p { color: #666; font-size: 16px; font-weight: 500; }

        /* Attendance Widget */
        .attendance-card { background: white; border: 1px solid #eee; padding: 20px 25px; border-radius: 12px; display: flex; align-items: center; gap: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .time-block { display: flex; flex-direction: column; }
        .time-label { font-size: 11px; font-weight: 800; color: #888; text-transform: uppercase; margin-bottom: 4px; }
        .time-value { font-family: 'Oswald', sans-serif; font-size: 20px; color: #111; }
        
        .att-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 25px; border: none; border-radius: 6px; font-weight: 800; font-size: 14px; text-transform: uppercase; cursor: pointer; transition: 0.2s; color: white; }
        .att-btn .lucide { width: 18px; height: 18px; }
        .btn-in { background: #22c55e; } .btn-in:hover { background: #16a34a; }
        .btn-out { background: #cc0000; } .btn-out:hover { background: #aa0000; }
        .btn-done { background: #f4f4f4; color: #aaa; cursor: not-allowed; }

        /* 5. DASHBOARD CARDS GRID */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-bottom: 40px; }

        .card {
            background: white; padding: 30px; border-radius: 12px; border: 1px solid #eee;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); text-decoration: none; color: inherit;
            transition: all 0.2s ease; display: flex; flex-direction: column; justify-content: space-between; 
            min-height: 250px; position: relative; overflow: hidden;
        }
        .card:hover { transform: translateY(-4px); border-color: #ccc; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }

        .icon {
            margin-bottom: 15px; background: #f9f9f9; color: #111;
            width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 10px;
        }
        .icon .lucide { width: 26px; height: 26px; }

        .card h3 { font-family: 'Oswald', sans-serif; font-size: 22px; text-transform: uppercase; margin-bottom: 8px; color: #111; }
        .card p { color: #666; font-size: 14px; line-height: 1.5; font-weight: 500; }
        .stat-number { font-family: 'Oswald', sans-serif; font-size: 32px; font-weight: 700; color: #cc0000; margin-top: 15px; }
        
        .card-dark { background: #111; color: white; border: none; }
        .card-dark h3 { color: white; }
        .card-dark p { color: #aaa; }
        .card-dark .icon { background: #333; color: white; }
        .card-dark:hover { border-color: #333; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        
        .card-locked { background: #f9f9f9; border: 1px dashed #ccc; cursor: not-allowed; pointer-events: none; }
        .card-locked h3 { color: #888; }
        .card-locked p { color: #aaa; }
        .card-locked .icon { background: #eee; color: #aaa; }
        .card-primary { border-top: 4px solid #cc0000; }

        /* 6. SCHEDULE TABLE SECTION */
        .schedule-section { background: white; border-radius: 12px; border: 1px solid #eee; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .schedule-header { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; font-family: 'Oswald', sans-serif; font-size: 20px; text-transform: uppercase; color: #111;}
        .schedule-header .lucide { color: #cc0000; }
        
        .schedule-table { width: 100%; border-collapse: collapse; }
        .schedule-table th, .schedule-table td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #eee; }
        .schedule-table th { background: #f9f9f9; color: #555; font-size: 12px; text-transform: uppercase; font-weight: 600; border-radius: 4px;}
        .schedule-table td { font-size: 14px; font-weight: 500; color: #111; }
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block;}
        .status-upcoming { background: #e0f2fe; color: #0284c7; }
        .status-today { background: #fef08a; color: #b45309; }
        .status-past { background: #f1f5f9; color: #475569; text-decoration: line-through; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">
            <img src="../images/Sneakdup_logo.jpg" alt="SneakDup Logo">
            <div class="brand-text">SNEAK<span>D</span>UP</div> 
            <div class="staff-badge">STAFF</div>
        </div>
        
        <a href="orders.php" class="menu-item active"><i data-lucide="layout-dashboard"></i> Dashboard</a>
        
        <?php if($is_clocked_in): ?>
            <a href="../admin/pos.php" class="menu-item"><i data-lucide="calculator"></i> POS Terminal</a>
        <?php else: ?>
            <a href="#" class="menu-item" style="opacity: 0.5; cursor: not-allowed;" title="Clock in first to unlock"><i data-lucide="lock"></i> POS Locked</a>
        <?php endif; ?>

        <a href="my_sales.php" class="menu-item"><i data-lucide="receipt"></i> My Sales</a>
        <a href="../shop.php" target="_blank" class="menu-item"><i data-lucide="package-search"></i> Check Stock</a>
        
        <div style="margin-top: 20px;">
            <a href="change_password.php" class="menu-item" style="font-size: 13px; color: #888;"><i data-lucide="key-round"></i> Change Password</a>
        </div>

        <div class="user-info">
            Logged in as:
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
            <a href="../logout.php" class="logout-link"><i data-lucide="log-out"></i> LOGOUT</a>
        </div>
    </div>

    <div class="main-content">
        
        <div class="top-header">
            <h1 class="page-title">Staff Terminal</h1>
            <div class="date-display"><i data-lucide="calendar"></i> <?php echo date('l, F j, Y'); ?></div>
        </div>

        <div class="content-wrapper">
            
            <div class="header-row">
                <div class="welcome-section">
                    <h1>Welcome, <?php echo htmlspecialchars(explode(' ', trim($_SESSION['fullname']))[0]); ?>!</h1>
                    <p>Manage your shift, process transactions, and track your performance.</p>
                </div>

                <div class="attendance-card">
                    <div class="time-block">
                        <span class="time-label">Time In</span>
                        <span class="time-value" style="color: <?php echo $is_clocked_in || $is_clocked_out ? '#22c55e' : '#aaa'; ?>;"><?php echo $time_in_display; ?></span>
                    </div>
                    <div class="time-block">
                        <span class="time-label">Time Out</span>
                        <span class="time-value" style="color: <?php echo $is_clocked_out ? '#cc0000' : '#aaa'; ?>;"><?php echo $time_out_display; ?></span>
                    </div>
                    
                    <form method="POST" style="margin: 0;">
                        <?php if (!$attendance): ?>
                            <input type="hidden" name="attendance_action" value="clock_in">
                            <button type="submit" class="att-btn btn-in"><i data-lucide="timer"></i> Clock In</button>
                        
                        <?php elseif ($is_clocked_in): ?>
                            <input type="hidden" name="attendance_action" value="clock_out">
                            <button type="submit" class="att-btn btn-out" onclick="return confirm('Are you sure you want to end your shift? You cannot clock back in today.');"><i data-lucide="timer-off"></i> Clock Out</button>
                        
                        <?php else: ?>
                            <button type="button" class="att-btn btn-done" disabled><i data-lucide="check-circle"></i> Shift Ended</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="grid">
                
                <?php if($is_clocked_in): ?>
                    <a href="../admin/pos.php" class="card card-dark">
                        <div>
                            <div class="icon"><i data-lucide="shopping-cart"></i></div>
                            <h3>Launch POS</h3>
                            <p>Open the cashier terminal to process walk-in customer purchases and update inventory.</p>
                        </div>
                    </a>
                <?php else: ?>
                    <div class="card card-locked">
                        <div>
                            <div class="icon"><i data-lucide="lock"></i></div>
                            <h3>POS Locked</h3>
                            <p><?php echo $is_clocked_out ? "Your shift has ended for today." : "You must CLOCK IN above before you can access the cash register."; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <a href="my_sales.php" class="card card-primary">
                    <div>
                        <div class="icon"><i data-lucide="trending-up"></i></div>
                        <h3>Today's Performance</h3>
                        <p>Total revenue generated from your processed transactions today.</p>
                        <div class="stat-number">₱<?php echo number_format($my_total_sales, 2); ?></div>
                    </div>
                </a>

                <a href="../shop.php" target="_blank" class="card">
                    <div>
                        <div class="icon"><i data-lucide="shopping-bag"></i></div>
                        <h3>Live Catalog</h3>
                        <p>Browse the shop to verify shoe sizes, color availability, and stock levels for customers.</p>
                    </div>
                </a>
            </div>

            <div class="schedule-section">
                <div class="schedule-header">
                    <i data-lucide="calendar-check"></i>
                    My Assigned Schedule (<?php echo $month_name; ?>)
                </div>
                
                <?php if($schedule_query->num_rows > 0): ?>
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Assigned Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $schedule_query->fetch_assoc()): 
                                $w_date = $row['work_date'];
                                $formatted_date = date('l, F j, Y', strtotime($w_date));
                                
                                // Determine the status of the shift
                                if ($w_date < $today) {
                                    $status = "Past Shift";
                                    $badge = "status-past";
                                } elseif ($w_date == $today) {
                                    $status = "On Duty Today";
                                    $badge = "status-today";
                                } else {
                                    $status = "Upcoming";
                                    $badge = "status-upcoming";
                                }
                            ?>
                                <tr>
                                    <td><strong><?php echo $formatted_date; ?></strong></td>
                                    <td><span class="status-badge <?php echo $badge; ?>"><?php echo $status; ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #888; text-align: center; padding: 20px 0;">No shifts assigned to you for this month.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>