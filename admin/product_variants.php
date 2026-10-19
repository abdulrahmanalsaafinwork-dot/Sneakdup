<?php
session_start();
include __DIR__ . '/../db_conn.php'; 

// 1. Check if Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php");
    exit();
}

// 2. Get the Product ID from the URL (e.g. ?id=1)
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
} else {
    header("Location: dashboard.php"); // Go back if no ID provided
    exit();
}

// 3. Fetch Product Details (Just to show the name)
$stmt = $conn->prepare("SELECT name, image FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: dashboard.php");
    exit();
}

$msg = "";
$msg_type = "";

// --- 🔥 NEW: VARIANT DELETION LOGIC 🔥 ---
if (isset($_GET['delete_variant_id'])) {
    $delete_id = intval($_GET['delete_variant_id']);
    
    // Safely delete only the specific variant that belongs to this product
    $del_stmt = $conn->prepare("DELETE FROM product_variants WHERE id = ? AND product_id = ?");
    $del_stmt->bind_param("ii", $delete_id, $product_id);
    
    if ($del_stmt->execute()) {
        $msg = "Variant successfully removed.";
        $msg_type = "success";
    } else {
        $msg = "Error removing variant.";
        $msg_type = "error";
    }
    
    // Redirect to clear the URL parameters so refreshing doesn't trigger another delete
    header("Location: product_variants.php?id=" . $product_id . "&msg=" . urlencode($msg) . "&msg_type=" . $msg_type);
    exit();
}

// Catch the success/error message after a deletion redirect
if (isset($_GET['msg']) && isset($_GET['msg_type'])) {
    $msg = htmlspecialchars($_GET['msg']);
    $msg_type = htmlspecialchars($_GET['msg_type']);
}

// --- 4. SMART STOCK MANAGEMENT LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // trim() removes accidental spaces so "10 " and "10" aren't seen as different sizes
    $size = trim($_POST['size']);
    $color = trim($_POST['color']);
    $stock_to_add = intval($_POST['stock']);

    // --- VARIANT IMAGE UPLOAD LOGIC ---
    $variant_image_name = NULL; // Default to NULL if no image is uploaded
    if (isset($_FILES["variant_image"]) && $_FILES["variant_image"]["error"] == 0) {
        $target_dir = "../uploads/";
        $variant_image_name = basename($_FILES["variant_image"]["name"]);
        $target_file = $target_dir . $variant_image_name;
        
        // Move the uploaded file
        if (!move_uploaded_file($_FILES["variant_image"]["tmp_name"], $target_file)) {
            $msg = "Error uploading variant image. Stock not added.";
            $msg_type = "error";
            $variant_image_name = NULL; // Reset on failure
        }
    }

    // Only proceed if there wasn't an image upload error
    if ($msg_type !== "error") {
        // Step A: Check if this exact Size and Color already exists for this specific product
        $check_stmt = $conn->prepare("SELECT id, stock FROM product_variants WHERE product_id = ? AND size = ? AND color = ?");
        $check_stmt->bind_param("iss", $product_id, $size, $color);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            // Step B: It EXISTS! UPDATE the stock instead of duplicating the row
            $existing_variant = $result->fetch_assoc();
            $new_total_stock = $existing_variant['stock'] + $stock_to_add;
            $variant_id = $existing_variant['id'];

            // We update stock, and IF a new image was uploaded, we update that too!
            if ($variant_image_name) {
                $update_stmt = $conn->prepare("UPDATE product_variants SET stock = ?, image = ? WHERE id = ?");
                $update_stmt->bind_param("isi", $new_total_stock, $variant_image_name, $variant_id);
            } else {
                $update_stmt = $conn->prepare("UPDATE product_variants SET stock = ? WHERE id = ?");
                $update_stmt->bind_param("ii", $new_total_stock, $variant_id);
            }

            if ($update_stmt->execute()) {
                $msg = "Stock updated! Added $stock_to_add units to existing variant.";
                $msg_type = "success";
            } else {
                $msg = "Error updating stock.";
                $msg_type = "error";
            }
        } else {
            // Step C: It DOES NOT EXIST! INSERT a brand new row with the image
            $insert_stmt = $conn->prepare("INSERT INTO product_variants (product_id, size, color, image, stock) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("isssi", $product_id, $size, $color, $variant_image_name, $stock_to_add);

            if ($insert_stmt->execute()) {
                $msg = "New variant size added successfully!";
                $msg_type = "success";
            } else {
                $msg = "Error adding variant.";
                $msg_type = "error";
            }
        }
    }
}

// 5. Fetch Existing Variants (To display in the table)
$variants = $conn->query("SELECT * FROM product_variants WHERE product_id = $product_id ORDER BY color ASC, size ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Inventory - SneakDup Admin</title>
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
            background: #333; color: #fff; font-size: 10px; padding: 4px 8px; 
            border-radius: 4px; margin-left: 5px; letter-spacing: 1px;
        }
        
        /* Minimalist Icon Styling */
        .menu-item {
            text-decoration: none; color: #aaa; padding: 12px 15px; margin-bottom: 8px;
            border-radius: 6px; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 12px;
        }
        .menu-item i { width: 18px; height: 18px; opacity: 0.7; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background: #222; color: white; }
        .menu-item.active { border-left: 3px solid #cc0000; background: #222; }
        .menu-item.active i, .menu-item:hover i { opacity: 1; color: #fff; }
        
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
        .back-btn { 
            background: #f4f4f4; border: 1px solid #ddd; padding: 8px 16px; 
            border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 13px; color: #111; transition: 0.2s;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .back-btn i { width: 16px; height: 16px; }
        .back-btn:hover { background: #e5e5e5; }

        /* 4. CONTENT WRAPPER & CARDS */
        .content-wrapper { padding: 40px; max-width: 1000px; margin: 0 auto; width: 100%; }
        
        .main-card {
            background: white; border-radius: 12px; border: 1px solid #eee;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); padding: 40px;
        }

        /* Product Header */
        .product-header { 
            display: flex; align-items: center; gap: 20px; border-bottom: 2px solid #eee; 
            padding-bottom: 25px; margin-bottom: 30px; 
        }
        .product-img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; background: #f9f9f9; mix-blend-mode: multiply; }
        .product-header h2 { font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; margin: 0 0 5px 0; color: #111; }
        .product-header p { margin: 0; color: #666; font-size: 14px; font-weight: 500; }

        /* The Form */
        .variant-form { 
            display: grid; grid-template-columns: 1fr 1fr 1fr 1.5fr auto; gap: 15px; 
            background: #fafafa; padding: 20px; border-radius: 8px; border: 1px solid #eee; margin-bottom: 30px;
            align-items: center;
        }
        input[type="text"], input[type="number"] { 
            padding: 14px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; 
            font-weight: 600; outline: none; background: #fafafa; transition: 0.2s; width: 100%;
        }
        input:focus { border-color: #111; background: white; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
        
        /* File Input Styling */
        input[type="file"] { padding: 8px; font-size: 12px; width: 100%; }
        input[type="file"]::file-selector-button {
            background: #fff; color: #333; border: 1px solid #ddd; padding: 6px 10px; border-radius: 4px; font-weight: 600; cursor: pointer; margin-right: 10px;
        }

        .add-btn { 
            background: #111; color: white; border: none; padding: 14px 20px; height: 100%;
            border-radius: 6px; cursor: pointer; font-weight: 800; text-transform: uppercase; transition: 0.2s;
            display: inline-flex; justify-content: center; align-items: center; gap: 6px;
        }
        .add-btn i { width: 16px; height: 16px; stroke-width: 2.5; }
        .add-btn:hover { background: #cc0000; }

        /* Alerts */
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert i { width: 20px; height: 20px; }
        .alert.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* The Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid #eee; color: #888; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f9f9f9; color: #333; font-size: 14px; font-weight: 500; vertical-align: middle; }
        tr:hover { background: #fcfcfc; }
        
        .variant-thumb { width: 40px; height: 40px; border-radius: 4px; border: 1px solid #eee; object-fit: cover; }
        .badge { padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .badge-good { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-low { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* 🔥 DELETE BUTTON STYLING 🔥 */
        .btn-delete {
            background: none; border: none; color: #cc0000; cursor: pointer; transition: 0.2s;
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px; border-radius: 6px; text-decoration: none;
        }
        .btn-delete:hover { background: #fef2f2; color: #991b1b; }
        .btn-delete i { width: 18px; height: 18px; }

        /* Done Button */
        .done-btn { 
            display: flex; justify-content: center; align-items: center; gap: 8px; width: 100%; padding: 18px; 
            background: #f4f4f4; border: 1px solid #ddd; color: #111; text-decoration: none; 
            border-radius: 8px; font-weight: 700; text-transform: uppercase; transition: 0.2s;
        }
        .done-btn i { width: 18px; height: 18px; }
        .done-btn:hover { background: #e5e5e5; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">
            <img src="../images/Sneakdup_logo.jpg" alt="Logo" style="width: 35px; height: 35px; border-radius: 6px; object-fit: cover;">
            <div style="display: flex; align-items: center;">SNEAK<span>D</span>UP</div>
            <div class="admin-badge">ADMIN</div>
        </div>
        
        <a href="dashboard.php" class="menu-item">
            <i data-lucide="layout-dashboard"></i> Dashboard
        </a>
        <a href="pos.php" class="menu-item">
            <i data-lucide="monitor-smartphone"></i> POS Terminal
        </a>
        <a href="product_add.php" class="menu-item active">
            <i data-lucide="package-plus"></i> Add Products
        </a>
        <a href="manage_users.php" class="menu-item">
            <i data-lucide="users"></i> Manage Staff
        </a>
        <a href="attendance_logs.php" class="menu-item">
            <i data-lucide="clock"></i> Attendance Logs
        </a>
        <a href="sales_report.php" class="menu-item">
            <i data-lucide="bar-chart-3"></i> Sales Reports
        </a>
        <a href="inbox.php" class="menu-item">
            <i data-lucide="inbox"></i> Customer Inbox
        </a>
        
        <a href="../logout.php" class="logout-link">
            <i data-lucide="log-out"></i> LOGOUT
        </a>
    </div>

    <div class="main-content">
        
        <div class="top-header">
            <h1 class="page-title">Manage Inventory</h1>
            <a href="dashboard.php" class="back-btn"><i data-lucide="arrow-left"></i> Back to Dashboard</a>
        </div>

        <div class="content-wrapper">
            <div class="main-card">
                
                <div class="product-header">
                    <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" class="product-img" alt="Product">
                    <div>
                        <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                        <p>Add specific sizes, colors, and stock quantities below.</p>
                    </div>
                </div>

                <?php if($msg): ?>
                    <div class="alert <?php echo $msg_type; ?>">
                        <?php if($msg_type == 'success'): ?>
                            <i data-lucide="check-circle-2"></i>
                        <?php else: ?>
                            <i data-lucide="alert-circle"></i>
                        <?php endif; ?>
                        <?php echo $msg; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="variant-form">
                    <input type="text" name="size" placeholder="Size" required>
                    <input type="text" name="color" placeholder="Color" required>
                    <input type="number" name="stock" placeholder="Qty" required min="1">
                    
                    <div>
                        <div style="font-size: 10px; color: #888; margin-bottom: 2px; text-transform: uppercase; font-weight: 700;">Variants Image</div>
                        <input type="file" name="variant_image" accept="image/*">
                    </div>

                    <button type="submit" class="add-btn"><i data-lucide="plus"></i> Add</button>
                </form>

                <?php if ($variants->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Color</th>
                                <th>Size</th>
                                <th>Stock Count</th>
                                <th>Status</th>
                                <th style="text-align:right;">Action</th> </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $variants->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php 
                                        // Show variant image if it exists, otherwise fallback to product image
                                        $img_to_show = !empty($row['image']) ? $row['image'] : $product['image'];
                                    ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($img_to_show); ?>" class="variant-thumb" alt="Color Variant">
                                </td>
                                <td><?php echo htmlspecialchars($row['color']); ?></td>
                                <td style="font-weight: 700;"><?php echo htmlspecialchars($row['size']); ?></td>
                                <td style="font-family: 'Courier New', monospace; font-weight: bold;"><?php echo $row['stock']; ?> Units</td>
                                <td>
                                    <?php if($row['stock'] < 5): ?>
                                        <span class="badge badge-low">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge badge-good">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <a href="product_variants.php?id=<?php echo $product_id; ?>&delete_variant_id=<?php echo $row['id']; ?>" 
                                       class="btn-delete" 
                                       onclick="return confirm('Are you sure you want to completely remove this variant? This action cannot be undone.');" 
                                       title="Remove Variant">
                                        <i data-lucide="trash-2"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="padding: 20px; text-align: center; color: #888; font-weight: 500; margin-bottom: 20px;">
                        No variants added yet. Use the form above to add your first size.
                    </div>
                <?php endif; ?>

                <a href="dashboard.php" class="done-btn"><i data-lucide="check"></i> Finish & Return to Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>