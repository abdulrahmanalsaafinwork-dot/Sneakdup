<?php
session_start();
include '../db_conn.php';

// 1. Security Check: Admin Only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 2. Check if an ID was provided
if (!isset($_GET['id'])) {
    header("Location: inventory.php"); // Updated redirect
    exit();
}

$product_id = intval($_GET['id']);
$msg = "";
$msg_type = "";

// 3. Handle the Update Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $desc = $_POST['description'];
    $is_promo = isset($_POST['is_promo']) ? 1 : 0;

    // Check if a new image was uploaded
    if (!empty($_FILES["image"]["name"])) {
        $target_dir = "../uploads/";
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Update WITH new image
            $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, description=?, image=?, is_promo_eligible=? WHERE id=?");
            $stmt->bind_param("ssdssii", $name, $category, $price, $desc, $image_name, $is_promo, $product_id);
        } else {
            $msg = "Error uploading the new image.";
            $msg_type = "error";
        }
    } else {
        // Update WITHOUT changing the image
        $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, description=?, is_promo_eligible=? WHERE id=?");
        $stmt->bind_param("ssdsii", $name, $category, $price, $desc, $is_promo, $product_id);
    }

    // Execute the update if no image upload errors occurred
    if (isset($stmt) && $stmt->execute()) {
        $msg = "Product updated successfully!";
        $msg_type = "success";
    } elseif (!isset($stmt) && empty($msg)) {
         $msg = "Database Error.";
         $msg_type = "error";
    }
}

// 4. Fetch Current Product Details
$fetch_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$fetch_stmt->bind_param("i", $product_id);
$fetch_stmt->execute();
$product = $fetch_stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: inventory.php"); // Updated redirect
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Product - SneakDup Admin</title>
    <link rel="icon" type="image/jpeg" href="../images/Sneakdup_logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
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

        /* 4. FORM CARD */
        .form-container { padding: 40px; display: flex; justify-content: center; }
        .form-card {
            background: white; border-radius: 12px; width: 100%; max-width: 650px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #eee; padding: 40px;
        }
        
        .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; border-bottom: 2px solid #cc0000; padding-bottom: 15px; }
        .form-title { font-size: 18px; font-weight: 800; text-transform: uppercase; margin: 0; }
        
        .manage-stock-btn { 
            font-size: 12px; font-weight: 700; color: #cc0000; text-decoration: underline; text-transform: uppercase; 
            display: inline-flex; align-items: center; gap: 4px; transition: 0.2s;
        }
        .manage-stock-btn i { width: 14px; height: 14px; }
        .manage-stock-btn:hover { color: #aa0000; }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 12px; text-transform: uppercase; color: #666; }
        
        input[type="text"], input[type="number"], select, textarea { 
            width: 100%; padding: 14px; border: 1px solid #ddd; border-radius: 8px; 
            font-size: 14px; font-weight: 600; outline: none; background: #fafafa; transition: 0.2s;
        }
        input:focus, select:focus, textarea:focus { border-color: #111; background: white; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
        
        textarea { resize: vertical; min-height: 100px; line-height: 1.5; }

        .image-preview-group { display: flex; gap: 20px; align-items: center; background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px dashed #ccc; margin-bottom: 20px; }
        .current-img { width: 70px; height: 70px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; mix-blend-mode: multiply; }
        .img-instructions { font-size: 12px; color: #888; margin-top: 5px; }
        
        input[type="file"] { font-size: 13px; }
        input[type="file"]::file-selector-button {
            background: #111; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; cursor: pointer; margin-right: 15px; transition: 0.2s;
        }
        input[type="file"]::file-selector-button:hover { background: #cc0000; }

        .checkbox-group { 
            display: flex; align-items: center; gap: 12px; background: #fff1f2; 
            padding: 15px; border-radius: 8px; border: 1px solid #ffe4e6; margin-bottom: 25px;
        }
        .checkbox-group input { width: 18px; height: 18px; cursor: pointer; accent-color: #cc0000; }
        .checkbox-group label { margin: 0; color: #991b1b; font-size: 13px; cursor: pointer; }

        .submit-btn { 
            width: 100%; padding: 18px; background: #111; color: white; border: none; 
            border-radius: 8px; font-size: 16px; font-weight: 800; text-transform: uppercase; cursor: pointer; transition: 0.2s;
            display: inline-flex; justify-content: center; align-items: center; gap: 8px;
        }
        .submit-btn i { width: 18px; height: 18px; }
        .submit-btn:hover { background: #cc0000; }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 25px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert i { width: 20px; height: 20px; }
        .alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">
            <img src="../images/Sneakdup_logo.jpg" alt="Logo" style="width: 35px; height: 35px; border-radius: 6px; object-fit: cover;">
            <div style="display: flex; align-items: center;">SNEAK<span>D</span>UP</div>
            <div class="admin-badge">ADMIN</div>
        </div>
        
        <a href="dashboard.php" class="menu-item"><i data-lucide="layout-dashboard"></i> Dashboard</a>
        <a href="pos.php" class="menu-item"><i data-lucide="monitor-smartphone"></i> POS Terminal</a>
        <a href="inventory.php" class="menu-item active"><i data-lucide="boxes"></i> Inventory</a>
        <a href="manage_users.php" class="menu-item"><i data-lucide="users"></i> Manage Staff</a>
        <a href="attendance_logs.php" class="menu-item"><i data-lucide="clock"></i> Attendance Logs</a>
        <a href="sales_report.php" class="menu-item"><i data-lucide="bar-chart-3"></i> Sales Reports</a>
        <a href="inbox.php" class="menu-item"><i data-lucide="inbox"></i> Customer Inbox</a>
        
        <a href="../logout.php" class="logout-link"><i data-lucide="log-out"></i> LOGOUT</a>
    </div>

    <div class="main-content">
        
        <div class="top-header">
            <h1 class="page-title">Product Details</h1>
            <a href="inventory.php" class="back-btn"><i data-lucide="arrow-left"></i> Back to Inventory</a>
        </div>

        <div class="form-container">
            <div class="form-card">
                
                <div class="form-header">
                    <div class="form-title">Edit Product Information</div>
                    <a href="product_variants.php?id=<?php echo $product_id; ?>" class="manage-stock-btn">
                        <i data-lucide="layers"></i> Edit Sizes & Colors
                    </a>
                </div>
                
                <?php if($msg): ?>
                    <div class="alert <?php echo $msg_type; ?>">
                        <i data-lucide="<?php echo $msg_type == 'success' ? 'check-circle-2' : 'alert-circle'; ?>"></i>
                        <?php echo $msg; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>

                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Category</label>
                            <select name="category">
                                <option value="Shoes" <?php echo ($product['category'] == 'Shoes') ? 'selected' : ''; ?>>Shoes</option>
                                <option value="Clothes" <?php echo ($product['category'] == 'Clothes') ? 'selected' : ''; ?>>Clothes</option>
                                <option value="Bundle" <?php echo ($product['category'] == 'Bundle') ? 'selected' : ''; ?>>Bundle</option>
                                <option value="Accessories" <?php echo ($product['category'] == 'Accessories') ? 'selected' : ''; ?>>Accessories</option>
                            </select>
                        </div>

                        <div class="form-group" style="flex: 1;">
                            <label>Base Price (PHP)</label>
                            <input type="number" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Product Description</label>
                        <textarea name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <label>Product Image</label>
                    <div class="image-preview-group">
                        <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" class="current-img" alt="Current Image">
                        <div>
                            <input type="file" name="image" accept="image/*">
                            <div class="img-instructions">Upload a new photo to change the main product image.</div>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" name="is_promo" id="promo" <?php echo ($product['is_promo_eligible'] == 1) ? 'checked' : ''; ?>>
                        <label for="promo">Mark this product as eligible for the "2 for 999" Promo</label>
                    </div>

                    <button type="submit" class="submit-btn"><i data-lucide="save"></i> Update Product Details</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>