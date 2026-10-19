<?php
session_start();
include __DIR__ . '/../db_conn.php'; 

// 1. Security Check: Admin Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php"); 
    exit();
}

// 2. Search Logic Context Capture
$search_query = "";
$search_condition = "";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    $safe_search = $conn->real_escape_string($search_query);
    // Filters products by name or category based on the search input
    $search_condition = " WHERE p.name LIKE '%$safe_search%' OR p.category LIKE '%$safe_search%' ";
}

// 🔥 NEW: SELF-CONTAINED POPULAR TOGGLE CONTROLLER 🔥
if (isset($_GET['toggle_popular'])) {
    $product_id = intval($_GET['toggle_popular']);
    
    // Fetch the current popular state to reverse it cleanly
    $check_state = $conn->query("SELECT is_popular FROM products WHERE id = $product_id");
    if ($check_state && $check_state->num_rows > 0) {
        $current_state = $check_state->fetch_assoc()['is_popular'];
        $new_state = ($current_state == 1) ? 0 : 1;
        
        // Execute the state update query safely
        $conn->query("UPDATE products SET is_popular = $new_state WHERE id = $product_id");
    }
    
    // Redirect back to eliminate duplicate submissions while maintaining active search terms
    $redirect_url = "inventory.php";
    if (!empty($search_query)) {
        $redirect_url .= "?search=" . urlencode($search_query);
    }
    header("Location: $redirect_url");
    exit();
}

// 3. Fetch Master Inventory List
// This query joins products with their variants to show the total stock for each shoe
$sql = "SELECT p.*, SUM(v.stock) as total_stock 
        FROM products p 
        LEFT JOIN product_variants v ON p.id = v.product_id 
        $search_condition
        GROUP BY p.id 
        ORDER BY p.id DESC";
$products = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Inventory Management</title>
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

        /* TABLE CONTAINER */
        .section-box { background: white; border-radius: 12px; border: 1px solid #e5e5e5; padding: 0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .section-header { padding: 25px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #fafafa;}
        .header-title-group { display: flex; align-items: center; gap: 10px; }
        .header-title-group i { width: 22px; height: 22px; color: #111; }
        .section-title { font-family: 'Oswald', sans-serif; font-size: 20px; text-transform: uppercase; margin: 0; letter-spacing: 0.5px;}
        
        /* SEARCH CONTROLS */
        .inventory-controls { display: flex; justify-content: space-between; align-items: center; padding: 15px 25px; background: white; border-bottom: 1px solid #eee; }
        .search-form { display: flex; align-items: center; gap: 10px; }
        .search-input { padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; width: 300px; outline: none; transition: 0.2s; font-family: 'Inter', sans-serif; }
        .search-input:focus { border-color: #111; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
        .search-btn { background: #111; color: white; border: none; padding: 10px 15px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; text-transform: uppercase; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .search-btn:hover { background: #cc0000; }
        .clear-btn { color: #777; font-size: 12px; font-weight: 600; text-decoration: none; margin-left: 5px; display: inline-flex; align-items: center; gap: 4px; transition: 0.2s;}
        .clear-btn:hover { color: #111; text-decoration: underline; }

        .btn-black { background: #cc0000; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 700; text-transform: uppercase; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; border: none;}
        .btn-black:hover { background: #aa0000; }

        /* TABLE STYLES */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 25px; background: white; font-size: 11px; color: #888; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid #eee; }
        td { padding: 18px 25px; border-bottom: 1px solid #f9f9f9; font-size: 14px; color: #333; vertical-align: middle; }
        tr:hover { background-color: #fcfcfc; }
        .product-flex { display: flex; align-items: center; gap: 15px; }
        .product-img { width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; mix-blend-mode: multiply;}
        
        .stock-status { font-weight: 700; font-size: 13px; }
        .stock-good { color: #15803d; }
        .stock-low { color: #b91c1c; background: #fef2f2; padding: 4px 8px; border-radius: 4px; }

        /* 🔥 DYNAMIC INTERACTIVE PILL CONTROLS 🔥 */
        .popular-toggle-btn { text-decoration: none; display: inline-block; transition: transform 0.2s ease; }
        .popular-toggle-btn:hover { transform: translateY(-1px) scale(1.03); }
        .pop-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 800; padding: 6px 12px; border-radius: 50px; text-transform: uppercase; border: 1px solid transparent; letter-spacing: 0.5px;}
        .pop-pill.pill-standard { background: #f3f4f6; color: #6b7280; border-color: #e5e7eb; }
        .pop-pill.pill-active { background: #fef2f2; color: #cc0000; border-color: #fee2e2; box-shadow: 0 4px 10px rgba(204, 0, 0, 0.05); }
        
        .action-link { font-size: 12px; font-weight: 600; text-decoration: none; color: #666; margin-right: 15px; display: inline-flex; align-items: center; gap: 4px; transition: 0.2s;}
        .action-link:hover { color: #111; text-decoration: underline; }
        .delete-link { color: #cc0000; }
        .delete-link:hover { color: #aa0000; text-decoration: underline;}
    </style>
</head>
<body>
    
 <div class="admin-layout">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1 class="page-title">Inventory Management</h1>
        </div>

        <div class="section-box">
            <div class="section-header">
                <div class="header-title-group">
                    <i data-lucide="package-search"></i>
                    <h2 class="section-title">Master Catalog</h2>
                </div>
                <div class="btn-group">
                    <a href="product_add.php" class="btn-black"><i data-lucide="plus-circle"></i> Add New Product</a>
                </div>
            </div>
            
            <div class="inventory-controls">
                <form class="search-form" method="GET" action="inventory.php">
                    <input type="text" name="search" class="search-input" placeholder="Search by name or category..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="search-btn"><i data-lucide="search"></i> Search</button>
                    <?php if(!empty($search_query)): ?>
                        <a href="inventory.php" class="clear-btn"><i data-lucide="x"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock Level</th>
                        <th>Popular Status</th> <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products->num_rows > 0): ?>
                        <?php while($row = $products->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="product-flex">
                                        <img src="../uploads/<?php echo $row['image']; ?>" class="product-img">
                                        <div style="font-weight:700; color:#111;"><?php echo htmlspecialchars($row['name']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td style="font-family:'Oswald', sans-serif; font-size:16px;">₱<?php echo number_format($row['price'], 2); ?></td>
                                <td>
                                    <?php 
                                        $stock = $row['total_stock'] ? $row['total_stock'] : 0; 
                                        if($stock < 10):
                                    ?>
                                        <span class="stock-status stock-low">Only <?php echo $stock; ?> Left</span>
                                    <?php else: ?>
                                        <span class="stock-status stock-good"><?php echo $stock; ?> Units</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <a href="inventory.php?toggle_popular=<?php echo $row['id']; ?><?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?>" class="popular-toggle-btn" title="Click to Change Display Status">
                                        <?php if(isset($row['is_popular']) && $row['is_popular'] == 1): ?>
                                            <span class="pop-pill pill-active"><i data-lucide="flame" style="width:12px; height:12px; fill:#cc0000;"></i> Popular</span>
                                        <?php else: ?>
                                            <span class="pop-pill pill-standard"><i data-lucide="minus-circle" style="width:12px; height:12px;"></i> Standard</span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                
                                <td style="text-align:right;">
                                    <a href="manage_products.php?id=<?php echo $row['id']; ?>" class="action-link"><i data-lucide="edit"></i> Details</a>
                                    <a href="product_variants.php?id=<?php echo $row['id']; ?>" class="action-link"><i data-lucide="layers"></i> Variants</a>
                                    <a href="product_delete.php?id=<?php echo $row['id']; ?>" class="action-link delete-link" onclick="return confirm('Are you absolutely sure you want to delete this product? This action cannot be undone.')"><i data-lucide="trash-2"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:60px; color:#888;"> <i data-lucide="package-x" style="width: 40px; height: 40px; opacity: 0.3; margin-bottom: 10px;"></i><br>
                                No products found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>