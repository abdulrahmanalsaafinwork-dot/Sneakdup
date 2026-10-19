<?php
session_start();
include __DIR__ . '/../db_conn.php';

// Check if admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php");
    exit();
}

$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $desc = $_POST['description'];
    // Checkbox logic: if checked, value is 1, else 0
    $is_promo = isset($_POST['is_promo']) ? 1 : 0;

    // --- IMAGE UPLOAD LOGIC ---
    $target_dir = "../uploads/"; // Folder to store images
    $image_name = basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $image_name;
    $uploadOk = 1;

    // Create folder if it doesn't exist
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    // If user uploaded an image
    if (!empty($image_name)) {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Image uploaded successfully
        } else {
            $msg = "Error uploading image.";
            $msg_type = "error";
            $uploadOk = 0;
        }
    } else {
        $image_name = "default.jpg"; // Fallback image
    }

    if ($uploadOk) {
        $stmt = $conn->prepare("INSERT INTO products (name, category, price, description, image, is_promo_eligible) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssi", $name, $category, $price, $desc, $image_name, $is_promo);

        if ($stmt->execute()) {
            // SUCCESS! Get the ID of the product we just made
            $new_product_id = $stmt->insert_id;
            // Redirect to the "Add Sizes" page
            header("Location: product_variants.php?id=" . $new_product_id);
            exit();
        } else {
            $msg = "Database Error: " . $stmt->error;
            $msg_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Product - SneakDup Admin</title>
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
            border-radius: 4px; letter-spacing: 1px;
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

        /* 4. FORM CARD */
        .form-container { padding: 40px; display: flex; justify-content: center; }
        .form-card {
            background: white; border-radius: 12px; width: 100%; max-width: 650px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #eee; padding: 40px;
        }
        
        .form-title { font-size: 18px; font-weight: 800; text-transform: uppercase; margin-bottom: 25px; border-bottom: 2px solid #cc0000; padding-bottom: 10px; display: inline-block; }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 12px; text-transform: uppercase; color: #666; }
        
        input[type="text"], input[type="number"], select, textarea, input[type="file"] { 
            width: 100%; padding: 14px; border: 1px solid #ddd; border-radius: 8px; 
            font-size: 14px; font-weight: 600; outline: none; background: #fafafa; transition: 0.2s;
        }
        input:focus, select:focus, textarea:focus { border-color: #111; background: white; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
        
        textarea { resize: vertical; min-height: 100px; }

        /* File Input Styling */
        input[type="file"] { padding: 10px; background: white; font-weight: 400; color: #555; }
        input[type="file"]::file-selector-button {
            background: #111; color: white; border: none; padding: 8px 16px; border-radius: 4px; font-weight: 600; cursor: pointer; margin-right: 15px; transition: 0.2s;
        }
        input[type="file"]::file-selector-button:hover { background: #cc0000; }

        /* Promo Checkbox */
        .checkbox-group { 
            display: flex; align-items: center; gap: 12px; background: #fff1f2; 
            padding: 15px; border-radius: 8px; border: 1px solid #ffe4e6; margin-bottom: 25px;
        }
        .checkbox-group input { width: 18px; height: 18px; cursor: pointer; accent-color: #cc0000; }
        .checkbox-group label { margin: 0; color: #991b1b; font-size: 13px; cursor: pointer; }

        /* Buttons */
        .submit-btn { 
            width: 100%; padding: 18px; background: #111; color: white; border: none; 
            border-radius: 8px; font-size: 16px; font-weight: 800; text-transform: uppercase; cursor: pointer; transition: 0.2s;
            display: inline-flex; justify-content: center; align-items: center; gap: 8px;
        }
        .submit-btn i { width: 18px; height: 18px; }
        .submit-btn:hover { background: #cc0000; }

        /* Alerts */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 25px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert i { width: 20px; height: 20px; }
        .alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
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
            <h1 class="page-title">Catalog Management</h1>
            <a href="dashboard.php" class="back-btn"><i data-lucide="arrow-left"></i> Back to Dashboard</a>
        </div>

        <div class="form-container">
            <div class="form-card">
                <div class="form-title">Add New Product</div>
                
                <?php if($msg): ?>
                    <div class="alert error">
                        <i data-lucide="alert-circle"></i> <?php echo $msg; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="name" placeholder="e.g. Nike Dunk Low Retro" required>
                    </div>

                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Category</label>
                            <select name="category">
                                <option value="Shoes">Shoes</option>
                                <option value="Clothes">Clothes</option>
                                <option value="Bundle">Bundle</option>
                                <option value="Accessories">Accessories</option>
                            </select>
                        </div>

                        <div class="form-group" style="flex: 1;">
                            <label>Base Price (PHP)</label>
                            <input type="number" name="price" step="0.01" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Product Description</label>
                        <textarea name="description" placeholder="Enter product details, materials, and highlights..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Product Image</label>
                        <input type="file" name="image" accept="image/*" required>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" name="is_promo" id="promo">
                        <label for="promo">Mark this product as eligible for the "2 for 999" Promo</label>
                    </div>

                    <button type="submit" class="submit-btn">Save & Add Variants <i data-lucide="arrow-right"></i></button>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>