<?php
session_start();
include '../db_conn.php'; 

// 1. Check Login & Admin Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); 
    exit();
}

// 🔥 QUICK RESTOCK BACKEND HANDLER 🔥
// This catches the form submission from the modal and updates the stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock_variant_id']) && isset($_POST['add_stock'])) {
    $variant_id = intval($_POST['restock_variant_id']);
    $add_stock = intval($_POST['add_stock']);
    
    if ($add_stock > 0) {
        $conn->query("UPDATE product_variants SET stock = stock + $add_stock WHERE id = $variant_id");
    }
    
    // Redirect back to avoid duplicate form submissions on refresh
    $redirect_url = "dashboard.php";
    if (isset($_GET['revenue_filter'])) {
        $redirect_url .= "?revenue_filter=" . urlencode($_GET['revenue_filter']);
    }
    header("Location: $redirect_url");
    exit();
}

// --- DASHBOARD DATA QUERIES ---

// 🔥 A. DYNAMIC REVENUE FILTERING 🔥
$revenue_filter = isset($_GET['revenue_filter']) ? $_GET['revenue_filter'] : 'today';
$revenue_condition = "DATE(sale_date) = CURDATE()"; // Default: Today
$revenue_label = "Today's Revenue";

if ($revenue_filter === 'week') {
    $revenue_condition = "YEARWEEK(sale_date, 1) = YEARWEEK(CURDATE(), 1)";
    $revenue_label = "This Week's Revenue";
} elseif ($revenue_filter === 'month') {
    $revenue_condition = "YEAR(sale_date) = YEAR(CURDATE()) AND MONTH(sale_date) = MONTH(CURDATE())";
    $revenue_label = "This Month's Revenue";
} elseif ($revenue_filter === 'year') {
    $revenue_condition = "YEAR(sale_date) = YEAR(CURDATE())";
    $revenue_label = "This Year's Revenue";
} elseif ($revenue_filter === 'all') {
    $revenue_condition = "1=1"; // Gets absolutely everything
    $revenue_label = "All-Time Revenue";
}

$sales_query = $conn->query("SELECT SUM(total_price) as total FROM sales WHERE $revenue_condition");
$total_sales = $sales_query->fetch_assoc()['total'] ?? 0;

// B. Get Total Products
$product_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];

// C. Get Low Stock Alerts Count (Variants < 10 stock)
$low_stock = $conn->query("SELECT COUNT(*) as count FROM product_variants WHERE stock < 10")->fetch_assoc()['count'];

// D. Get Active Staff
$active_staff = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = CURDATE() AND time_out IS NULL")->fetch_assoc()['count'];

// E. Get Specific Low Stock Items for the Alert Table (Limit to 5)
// 🔥 Added v.id as variant_id so we can specifically target it for the restock!
$low_stock_items = $conn->query("
    SELECT p.name, p.image, v.id as variant_id, v.size, v.color, v.stock, p.id as product_id
    FROM product_variants v 
    JOIN products p ON v.product_id = p.id 
    WHERE v.stock < 10 
    ORDER BY v.stock ASC LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - SneakDup</title>
    <link rel="icon" type="image/jpeg" href="../images/Sneakdup_logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* BASE STYLES */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f8; color: #1a1a1a; display: flex; min-height: 100vh; }

        /* DARK SIDEBAR */
        .sidebar { width: 260px; background: #111; color: white; display: flex; flex-direction: column; padding: 25px; position: fixed; height: 100%; }
        .brand { font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; margin-bottom: 40px; color: white; letter-spacing: -1px; display: flex; align-items: center; gap: 8px;}
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

        .menu-item { text-decoration: none; color: #aaa; padding: 12px 15px; margin-bottom: 8px; border-radius: 6px; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 12px; }
        .menu-item i { width: 18px; height: 18px; opacity: 0.7; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background: #222; color: white; }
        .menu-item.active { border-left: 3px solid #cc0000; background: #222; }
        .menu-item.active i, .menu-item:hover i { opacity: 1; color: #fff; }
        
        .logout-link { margin-top: auto; color: #cc0000; font-weight: 700; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 10px; padding: 12px 15px; border-radius: 6px; transition: 0.2s; }
        .logout-link:hover { background: rgba(204, 0, 0, 0.1); }

        /* MAIN CONTENT AREA */
        .main-content { margin-left: 260px; flex: 1; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .page-title { font-family: 'Oswald', sans-serif; font-size: 36px; text-transform: uppercase; margin: 0; color: #111; }
        .date-display { color: #666; font-size: 14px; font-weight: 500; }

        /* KPI CARDS GRID */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .kpi-card { background: white; padding: 25px; border-radius: 12px; border: 1px solid #e5e5e5; box-shadow: 0 4px 15px rgba(0,0,0,0.03); transition: 0.2s; position: relative; overflow: hidden; }
        .kpi-card:hover { transform: translateY(-3px); border-color: #ccc; }
        
        .kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px; position: relative; z-index: 2; }
        .kpi-label { font-size: 12px; color: #666; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
        .kpi-value { font-family: 'Oswald', sans-serif; font-size: 32px; font-weight: 700; color: #111; line-height: 1; position: relative; z-index: 2; }
        .kpi-icon { position: absolute; bottom: -10px; right: -10px; opacity: 0.05; color: #111; }
        .kpi-icon i { width: 80px; height: 80px; stroke-width: 2; }

        /* 🔥 REVENUE DROPDOWN STYLES 🔥 */
        .revenue-select { 
            background: #f4f6f8; border: 1px solid #e5e5e5; padding: 4px 8px; border-radius: 4px; 
            font-size: 11px; font-weight: 600; color: #555; outline: none; cursor: pointer; transition: 0.2s;
        }
        .revenue-select:hover { border-color: #cc0000; color: #cc0000; }

        /* LOW STOCK TABLE */
        .section-box { background: white; border-radius: 12px; border: 1px solid #e5e5e5; padding: 0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .section-header { padding: 20px 25px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; background: #fff1f2;}
        .section-title { font-family: 'Oswald', sans-serif; font-size: 18px; text-transform: uppercase; margin: 0; color: #cc0000; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 25px; font-size: 11px; color: #888; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid #eee; }
        td { padding: 15px 25px; border-bottom: 1px solid #f9f9f9; font-size: 14px; color: #333; vertical-align: middle; }
        .product-flex { display: flex; align-items: center; gap: 15px; }
        .product-img { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }
        .stock-badge { background: #fef2f2; color: #b91c1c; padding: 4px 8px; border-radius: 4px; font-weight: 700; font-size: 12px; }
        
        /* 🔥 UPGRADED ACTION LINK BUTTON 🔥 */
        .action-link { 
            background: none; border: none; font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 700; 
            color: #111; transition: 0.2s; display: inline-flex; align-items: center; gap: 4px; cursor: pointer;
        }
        .action-link:hover { color: #cc0000; text-decoration: underline; }

        /* 🔥 MODAL STYLES 🔥 */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 2000; backdrop-filter: blur(4px); }
        .modal-content { background: white; width: 400px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: popIn 0.3s ease; }
        @keyframes popIn { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        .modal-header { background: #111; color: white; padding: 20px 25px; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { font-family: 'Oswald', sans-serif; font-size: 20px; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
        .modal-body { padding: 25px; }
        .modal-footer { padding: 20px 25px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; background: #fafafa; }
        
        .btn-cancel { padding: 10px 15px; border: 1px solid #ccc; background: white; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-cancel:hover { background: #f0f0f0; }
        .btn-save { padding: 10px 20px; border: none; background: #cc0000; color: white; border-radius: 6px; font-weight: 700; text-transform: uppercase; cursor: pointer; transition: 0.2s; }
        .btn-save:hover { background: #aa0000; }
    </style>
</head>
<body>
    
   <div class="admin-layout">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
      
    <div class="header">
            <div>
                <h1 class="page-title">Executive Dashboard</h1>
                <div class="date-display">Welcome back, <?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-label"><?php echo $revenue_label; ?></div>
                    <select class="revenue-select" onchange="window.location.href='dashboard.php?revenue_filter='+this.value">
                        <option value="today" <?php echo $revenue_filter=='today'?'selected':'';?>>Today</option>
                        <option value="week" <?php echo $revenue_filter=='week'?'selected':'';?>>This Week</option>
                        <option value="month" <?php echo $revenue_filter=='month'?'selected':'';?>>This Month</option>
                        <option value="year" <?php echo $revenue_filter=='year'?'selected':'';?>>This Year</option>
                        <option value="all" <?php echo $revenue_filter=='all'?'selected':'';?>>All-Time</option>
                    </select>
                </div>
                <div class="kpi-value">₱<?php echo number_format($total_sales, 2); ?></div>
                <div class="kpi-icon"><i data-lucide="wallet"></i></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-header"><div class="kpi-label">Active Staff</div></div>
                <div class="kpi-value"><?php echo $active_staff; ?></div>
                <div class="kpi-icon"><i data-lucide="user-check"></i></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-header"><div class="kpi-label">Total Products</div></div>
                <div class="kpi-value"><?php echo $product_count; ?></div>
                <div class="kpi-icon"><i data-lucide="box"></i></div>
            </div>
            <div class="kpi-card" style="<?php echo ($low_stock > 0) ? 'border-color: #fca5a5;' : ''; ?>">
                <div class="kpi-header"><div class="kpi-label">Low Stock Alerts</div></div>
                <div class="kpi-value" style="<?php echo ($low_stock > 0) ? 'color: #cc0000;' : ''; ?>"><?php echo $low_stock; ?></div>
                <div class="kpi-icon" style="<?php echo ($low_stock > 0) ? 'color: #cc0000;' : ''; ?>"><i data-lucide="alert-triangle"></i></div>
            </div>
        </div>

        <div class="section-box">
            <div class="section-header">
                <i data-lucide="alert-circle" style="color: #cc0000;"></i>
                <h2 class="section-title">Needs Attention (Low Stock)</h2>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Variant Details</th>
                        <th>Current Stock</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($low_stock_items->num_rows > 0): ?>
                        <?php while($row = $low_stock_items->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="product-flex">
                                        <img src="../uploads/<?php echo $row['image']; ?>" class="product-img">
                                        <div style="font-weight:700; color:#111;"><?php echo htmlspecialchars($row['name']); ?></div>
                                    </div>
                                </td>
                                <td>Size <?php echo htmlspecialchars($row['size']); ?> - <?php echo htmlspecialchars($row['color']); ?></td>
                                <td><span class="stock-badge">Only <?php echo $row['stock']; ?> Left</span></td>
                                <td style="text-align:right;">
                                    <button onclick="openRestockModal(<?php echo $row['variant_id']; ?>, '<?php echo addslashes(htmlspecialchars($row['name'])); ?>', 'Size <?php echo htmlspecialchars($row['size']); ?> - <?php echo htmlspecialchars($row['color']); ?>', <?php echo $row['stock']; ?>)" class="action-link">
                                        Restock <i data-lucide="plus-circle" style="width: 14px;"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding:40px; color:#888;">All products are well stocked!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="restockModal" class="modal-overlay" style="display:none;" onclick="closeRestockModal(event)">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Quick Restock</h2>
                <i data-lucide="x" style="cursor:pointer; color: #aaa;" onclick="document.getElementById('restockModal').style.display='none'"></i>
            </div>
            <form method="POST" action="dashboard.php<?php echo isset($_GET['revenue_filter']) ? '?revenue_filter='.$_GET['revenue_filter'] : ''; ?>">
                <div class="modal-body">
                    <input type="hidden" name="restock_variant_id" id="modalVariantId">
                    
                    <p id="modalProductName" style="font-weight:800; font-size:22px; color: #111; margin-bottom:5px; line-height: 1.2;"></p>
                    <p id="modalVariantDetails" style="color:#666; font-size:14px; margin-bottom: 20px; font-weight: 600;"></p>
                    
                    <div style="background: #fef2f2; border-left: 3px solid #cc0000; padding: 12px; margin-bottom: 25px; border-radius: 4px;">
                        <p style="font-size:13px; color: #cc0000; font-weight: 600;">Current Stock: <strong id="modalCurrentStock" style="font-size: 16px;"></strong> units</p>
                    </div>
                    
                    <label style="font-size:12px; font-weight:700; text-transform:uppercase; color:#888; letter-spacing: 0.5px;">Quantity to Add:</label>
                    <input type="number" name="add_stock" min="1" value="10" style="width:100%; padding:12px 15px; margin-top:8px; border:1px solid #ddd; border-radius:6px; font-size:16px; outline:none; font-weight: 600; font-family: 'Inter', sans-serif;" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('restockModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn-save">Confirm Restock</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // JS logic to catch the click and feed data to the modal
        function openRestockModal(variantId, productName, variantDetails, currentStock) {
            document.getElementById('restockModal').style.display = 'flex';
            document.getElementById('modalVariantId').value = variantId;
            document.getElementById('modalProductName').innerText = productName;
            document.getElementById('modalVariantDetails').innerText = variantDetails;
            document.getElementById('modalCurrentStock').innerText = currentStock;
        }

        function closeRestockModal(event) {
            // Close if clicking the dark overlay outside the box
            if (event.target.id === 'restockModal') {
                document.getElementById('restockModal').style.display = 'none';
            }
        }
    </script>
</body>
</html>