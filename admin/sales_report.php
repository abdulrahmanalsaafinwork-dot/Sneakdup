<?php
session_start();
include '../db_conn.php';

// 1. Security Check: Admin Only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 2. Fetch Aggregated Sales Data
// Today
$today_q = $conn->query("SELECT SUM(total_price) as total FROM sales WHERE DATE(sale_date) = CURDATE()");
$sales_today = $today_q->fetch_assoc()['total'] ?? 0;

// This Week
$week_q = $conn->query("SELECT SUM(total_price) as total FROM sales WHERE YEARWEEK(sale_date, 1) = YEARWEEK(CURDATE(), 1)");
$sales_week = $week_q->fetch_assoc()['total'] ?? 0;

// This Month
$month_q = $conn->query("SELECT SUM(total_price) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())");
$sales_month = $month_q->fetch_assoc()['total'] ?? 0;

// All Time
$all_q = $conn->query("SELECT SUM(total_price) as total FROM sales");
$sales_all = $all_q->fetch_assoc()['total'] ?? 0;

// Top Selling Product (NEW FEATURE)
$top_prod_q = $conn->query("SELECT product_name, SUM(quantity) as total_sold FROM sales GROUP BY product_name ORDER BY total_sold DESC LIMIT 1");
$top_product = $top_prod_q->fetch_assoc();
$best_seller = $top_product ? $top_product['product_name'] : "No sales yet";

// --- CHART DATA LOGIC: Monthly Revenue for Current Year ---
$monthly_sales_data = array_fill(1, 12, 0); 
$chart_sql = "SELECT MONTH(sale_date) as month, SUM(total_price) as total 
              FROM sales 
              WHERE YEAR(sale_date) = YEAR(CURDATE()) 
              GROUP BY MONTH(sale_date)";
$chart_result = $conn->query($chart_sql);

if($chart_result) {
    while($row = $chart_result->fetch_assoc()) {
        $monthly_sales_data[(int)$row['month']] = (float)$row['total'];
    }
}
$chart_values = implode(',', $monthly_sales_data);


// 3. Fetch Transaction History (WITH NEW DATE FILTER)
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$history_sql = "SELECT * FROM sales";
if (!empty($filter_date)) {
    $safe_date = $conn->real_escape_string($filter_date);
    $history_sql .= " WHERE DATE(sale_date) = '$safe_date'";
}
$history_sql .= " ORDER BY id DESC LIMIT 100";
$history_query = $conn->query($history_sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sales Report - SneakDup Admin</title>
    <link rel="icon" type="image/jpeg" href="../images/Sneakdup_logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        .brand-text { white-space: nowrap; }
        .brand span { color: #cc0000; }
        .brand img { height: 35px; width: auto; border-radius: 6px; }
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
        .menu-item.active i, .menu-item:hover i { opacity: 1; color: #fff; }
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

        .content-wrapper { padding: 40px; max-width: 1200px; margin: 0 auto; width: 100%; }

        /* 4. KPI CARDS GRID */
        .kpi-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 40px; }
        
        .kpi-card {
            background: white; padding: 25px; border-radius: 12px; border: 1px solid #eee;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); position: relative; overflow: hidden; transition: 0.2s;
        }
        .kpi-card:hover { transform: translateY(-3px); border-color: #ccc; }
        .kpi-label { font-size: 11px; color: #888; text-transform: uppercase; font-weight: 800; margin-bottom: 8px; letter-spacing: 0.5px; }
        .kpi-value { font-family: 'Oswald', sans-serif; font-size: 28px; font-weight: 700; color: #111; line-height: 1.1; position: relative; z-index: 2;}
        .kpi-highlight { color: #cc0000; }
        
        .kpi-icon { position: absolute; top: 20px; right: 20px; opacity: 0.10; color: #111; z-index: 1;}
        .kpi-icon i { width: 45px; height: 45px; stroke-width: 1.5; }

        /* CHART BOX */
        .chart-box { background: white; padding: 30px; border-radius: 12px; border: 1px solid #e5e5e5; margin-bottom: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .chart-header { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .chart-header h2 { font-family: 'Oswald', sans-serif; font-size: 20px; text-transform: uppercase; }
        .canvas-wrapper { height: 250px; position: relative; width: 100%; }

        /* 5. TABLE STYLES & FILTERS */
        .table-card { background: white; border-radius: 12px; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; }
        .card-header { padding: 20px 25px; border-bottom: 1px solid #eee; background: #fafafa; display: flex; align-items: center; justify-content: space-between;}
        .header-title-group { display: flex; align-items: center; gap: 10px; }
        .header-title-group i { width: 22px; height: 22px; color: #111; }
        .card-header h2 { font-family: 'Oswald', sans-serif; font-size: 18px; text-transform: uppercase; margin: 0; }

        /* Form Filter */
        .filter-form { display: flex; gap: 10px; }
        .filter-input { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-family: 'Inter'; font-size: 13px;}
        .filter-btn { background: #111; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 12px;}
        .filter-btn:hover { background: #cc0000; }

        table { width: 100%; border-collapse: collapse; }
        th { background: white; text-align: left; padding: 15px 25px; font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; border-bottom: 1px solid #eee; }
        td { padding: 15px 25px; font-size: 14px; color: #333; border-bottom: 1px solid #f9f9f9; font-weight: 500; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #fcfcfc; }

        .date-col { font-size: 13px; color: #666; }
        .price-col { font-family: 'Oswald', sans-serif; font-size: 16px; font-weight: 700; }
        .receipt-badge { background: #f0f0f0; padding: 4px 8px; border-radius: 4px; font-size: 12px; color: #555; }

        @media print {
            .sidebar, .export-btn, .chart-box, .filter-form { display: none !important; }
            .main-content { overflow: visible; padding: 0; margin: 0; width: 100%;}
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
            <h1 class="page-title">Financial Overview</h1>
            <button class="export-btn" onclick="window.print()">
                <i data-lucide="printer"></i> Print Report
            </button>
        </div>

        <div class="content-wrapper">
            
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Today's Sales</div>
                    <div class="kpi-value">₱<?php echo number_format($sales_today, 2); ?></div>
                    <div class="kpi-icon"><i data-lucide="wallet"></i></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">This Week</div>
                    <div class="kpi-value">₱<?php echo number_format($sales_week, 2); ?></div>
                    <div class="kpi-icon"><i data-lucide="calendar-days"></i></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">This Month</div>
                    <div class="kpi-value">₱<?php echo number_format($sales_month, 2); ?></div>
                    <div class="kpi-icon"><i data-lucide="calendar-range"></i></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Top Seller</div>
                    <div class="kpi-value" style="font-size: 18px; line-height: 1.2; padding-top: 10px;"><?php echo htmlspecialchars($best_seller); ?></div>
                    <div class="kpi-icon"><i data-lucide="star"></i></div>
                </div>
                <div class="kpi-card" style="border-bottom: 4px solid #cc0000;">
                    <div class="kpi-label">All-Time Revenue</div>
                    <div class="kpi-value kpi-highlight">₱<?php echo number_format($sales_all, 2); ?></div>
                    <div class="kpi-icon" style="color: #cc0000;"><i data-lucide="banknote"></i></div>
                </div>
            </div>

            <div class="chart-box">
                <div class="chart-header">
                    <i data-lucide="trending-up" style="color:#cc0000;"></i>
                    <h2>Revenue Trend (<?php echo date('Y'); ?>)</h2>
                </div>
                <div class="canvas-wrapper">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="table-card">
                <div class="card-header">
                    <div class="header-title-group">
                        <i data-lucide="receipt"></i>
                        <h2>Transaction History</h2>
                    </div>
                    
                    <form method="GET" class="filter-form">
                        <input type="date" name="filter_date" class="filter-input" value="<?php echo htmlspecialchars($filter_date); ?>">
                        <button type="submit" class="filter-btn">Filter</button>
                        <?php if(!empty($filter_date)): ?>
                            <a href="sales_report.php" class="filter-btn" style="background:#eee; color:#111; text-decoration:none;">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <?php if ($history_query->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Receipt No.</th>
                            <th>Date & Time</th>
                            <th>Product Name</th>
                            <th>Variant (Size/Color)</th>
                            <th>Qty</th>
                            <th style="text-align: right;">Total Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $history_query->fetch_assoc()): ?>
                        <tr>
                            <td style="font-family:'Courier New', monospace; font-weight: bold;">
                                <?php 
                                    if(!empty($row['receipt_no'])) {
                                        echo "<span class='receipt-badge'>" . htmlspecialchars($row['receipt_no']) . "</span>";
                                    } else {
                                        // Fallback for older transactions before the cart upgrade
                                        echo "<span class='receipt-badge'>#00" . str_pad($row['id'], 4, '0', STR_PAD_LEFT) . "</span>";
                                    }
                                ?>
                            </td>
                            <td class="date-col">
                                <?php echo date('M d, Y - h:i A', strtotime($row['sale_date'] ?? 'now')); ?>
                            </td>
                            <td style="font-weight:700; color:#111;"><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['variant_info']); ?></td>
                            <td><?php echo $row['quantity']; ?>x</td>
                            <td class="price-col" style="text-align: right;">₱<?php echo number_format($row['total_price'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div style="padding:40px; text-align:center; color:#888; font-weight: 500;">
                        <div style="margin-bottom: 10px; opacity: 0.5; display: flex; justify-content: center;">
                            <i data-lucide="receipt" style="width: 40px; height: 40px;"></i>
                        </div>
                        No transactions recorded for this date.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        lucide.createIcons();

        // REVENUE CHART IMPLEMENTATION
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Monthly Revenue',
                    data: [<?php echo $chart_values; ?>],
                    borderColor: '#cc0000',
                    backgroundColor: 'rgba(204, 0, 0, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#111',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return ' Revenue: ₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    </script>
</body>
</html>