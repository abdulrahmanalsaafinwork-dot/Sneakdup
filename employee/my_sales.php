<?php
session_start();
include __DIR__ . '/../db_conn.php';

// 1. Security Check: Employee Only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header("Location: /login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Attendance Check - Prevent bypassing the clock-in requirement
$attendance_check = $conn->query("SELECT id FROM attendance WHERE user_id = '$user_id' AND date = CURDATE() AND time_out IS NULL");
$is_clocked_in = ($attendance_check->num_rows > 0);

// 3. Fetch Data (Only if clocked in)
$result = null;
$shift_total = 0;

if ($is_clocked_in) {
    // Fetch only sales made by THIS employee TODAY
    $today = date('Y-m-d');
    $sql = "SELECT * FROM sales WHERE user_id = ? AND DATE(sale_date) = ? ORDER BY sale_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();

    // Calculate total for the shift
    $total_shift_query = $conn->query("SELECT SUM(total_price) as total FROM sales WHERE user_id = $user_id AND DATE(sale_date) = '$today'");
    $shift_total = $total_shift_query->fetch_assoc()['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Sales Today - SneakDup Staff</title>
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
        .user-name { color: white; font-weight: 700; display: block; margin-bottom: 10px; }
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
        .back-btn { 
            background: #f4f4f4; border: 1px solid #ddd; padding: 8px 16px; 
            border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 13px; color: #111; 
            transition: 0.2s; display: flex; align-items: center; gap: 8px;
        }
        .back-btn:hover { background: #e5e5e5; }
        .back-btn .lucide { width: 16px; height: 16px; }

        .content-wrapper { padding: 40px; max-width: 1000px; margin: 0 auto; width: 100%; }

        /* 4. SUMMARY CARD */
        .summary-card { 
            background: #111; color: white; padding: 35px; border-radius: 12px; 
            margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-left: 5px solid #cc0000;
        }
        .summary-label { color: #aaa; font-size: 12px; margin-bottom: 5px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
        .summary-total { font-family: 'Oswald', sans-serif; font-size: 42px; margin: 0; line-height: 1; }
        .summary-date { display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 16px; }
        .summary-date .lucide { width: 18px; height: 18px; color: #cc0000; }

        /* 5. TABLE STYLES */
        .table-card { background: white; border-radius: 12px; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; }
        .card-header { padding: 20px 25px; border-bottom: 1px solid #eee; background: #fafafa; display: flex; align-items: center; gap: 10px; }
        .card-header h2 { font-family: 'Oswald', sans-serif; font-size: 18px; text-transform: uppercase; margin: 0; }
        .card-header .lucide { width: 20px; height: 20px; color: #cc0000; }

        table { width: 100%; border-collapse: collapse; }
        th { background: white; text-align: left; padding: 15px 25px; font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; border-bottom: 1px solid #eee; }
        td { padding: 15px 25px; font-size: 14px; color: #333; border-bottom: 1px solid #f9f9f9; font-weight: 500; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #fcfcfc; }

        .time-col { color: #888; font-size: 13px; display: flex; align-items: center; gap: 6px; }
        .time-col .lucide { width: 14px; height: 14px; }
        .price-col { font-family: 'Oswald', sans-serif; font-size: 16px; font-weight: 700; color: #111; }

        /* Locked State Style */
        .locked-card { background: white; padding: 50px; border-radius: 12px; text-align: center; border: 1px solid #eee; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .icon-circle { width: 70px; height: 70px; background: #fff0f0; color: #cc0000; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; }
        .icon-circle .lucide { width: 32px; height: 32px; }
        .locked-btn { display: inline-flex; align-items: center; gap: 8px; margin-top: 25px; background: #111; color: white; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 700; font-size: 13px; text-transform: uppercase; transition: 0.2s; }
        .locked-btn:hover { background: #cc0000; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">
            <img src="../images/Sneakdup_logo.jpg" alt="SneakDup Logo">
            <div class="brand-text">SNEAK<span>D</span>UP</div>
            <div class="staff-badge">STAFF</div>
        </div>
        
        <a href="orders.php" class="menu-item"><i data-lucide="layout-dashboard"></i> Dashboard</a>
        
        <?php if($is_clocked_in): ?>
            <a href="../admin/pos.php" class="menu-item"><i data-lucide="calculator"></i> POS Terminal</a>
        <?php else: ?>
            <a href="orders.php" class="menu-item" onclick="alert('Clock in required on the Dashboard.');"><i data-lucide="lock"></i> POS Terminal</a>
        <?php endif; ?>

        <a href="my_sales.php" class="menu-item active"><i data-lucide="receipt"></i> My Sales</a>
        <a href="../shop.php" target="_blank" class="menu-item"><i data-lucide="package-search"></i> Check Stock</a>
        
        <div style="margin-top: 20px;">
            <a href="change_password.php" class="menu-item" style="font-size: 13px;"><i data-lucide="key-round"></i> Change Password</a>
        </div>

        <div class="user-info">
            Logged in as:
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
            <a href="../logout.php" class="logout-link"><i data-lucide="log-out"></i> LOGOUT</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1 class="page-title">Shift Performance</h1>
            <a href="orders.php" class="back-btn"><i data-lucide="arrow-left"></i> Back to Dashboard</a>
        </div>

        <div class="content-wrapper">
            
            <?php if(!$is_clocked_in): ?>
                <div class="locked-card">
                    <div class="icon-circle"><i data-lucide="lock-keyhole"></i></div>
                    <h2 style="font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 28px;">Sales Log Locked</h2>
                    <p style="color: #666; font-size: 15px; margin-top: 10px; max-width: 400px; margin-left: auto; margin-right: auto;">
                        Please <strong>Clock In</strong> on your dashboard to view your transaction history and performance for this shift.
                    </p>
                    <a href="orders.php" class="locked-btn"><i data-lucide="layout-dashboard"></i> Return to Dashboard</a>
                </div>
            <?php else: ?>
                <div class="summary-card">
                    <div>
                        <div class="summary-label">Current Shift Total Revenue</div>
                        <h2 class="summary-total">₱<?php echo number_format($shift_total, 2); ?></h2>
                    </div>
                    <div style="text-align: right;">
                        <div class="summary-label">Date</div>
                        <div class="summary-date"><i data-lucide="calendar"></i> <?php echo date('F j, Y'); ?></div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="card-header">
                        <i data-lucide="history"></i>
                        <h2>Transactions Today</h2>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Product Name</th>
                                <th>Variant (Size/Color)</th>
                                <th>Qty</th>
                                <th style="text-align: right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="time-col">
                                            <i data-lucide="clock"></i>
                                            <?php echo date('h:i A', strtotime($row['sale_date'])); ?>
                                        </td>
                                        <td style="font-weight: 700; color: #111;">
                                            <?php echo htmlspecialchars($row['product_name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['variant_info']); ?></td>
                                        <td><?php echo $row['quantity']; ?>x</td>
                                        <td class="price-col" style="text-align: right;">
                                            ₱<?php echo number_format($row['total_price'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #999; font-weight: 500;">
                                        No sales recorded yet today.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>