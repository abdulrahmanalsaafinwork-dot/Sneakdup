<?php
session_start();
include __DIR__ . '/../db_conn.php'; 

// --- SECURITY CHECK ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php"); 
    exit();
}

$msg = "";
$msg_type = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'employee'; 
    $status = 'active'; 

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $msg = "Error: That email is already taken!";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $fullname, $email, $password, $role, $status);
        
        if ($stmt->execute()) {
            $msg = "Success! New Employee registered.";
            $msg_type = "success";
        } else {
            $msg = "Error registering employee.";
            $msg_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Employee - SneakDup Admin</title>
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

        /* 4. FORM CARD */
        .content-wrapper { padding: 60px 20px; display: flex; justify-content: center; align-items: flex-start; }
        
        .form-card {
            background: white; padding: 40px; border-radius: 12px; width: 100%; max-width: 500px;
            border: 1px solid #eee; box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }

        .form-header { text-align: center; margin-bottom: 30px; }
        .icon-circle {
            width: 60px; height: 60px; background: #fff0f0; color: #cc0000; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;
        }
        .icon-circle i { width: 32px; height: 32px; stroke-width: 2; }
        .form-header h2 { font-family: 'Oswald', sans-serif; font-weight: 700; font-size: 24px; margin-bottom: 5px; text-transform: uppercase; }
        .subtitle { color: #777; font-size: 14px; font-weight: 500; }
        
        /* 5. INPUTS */
        .input-group { margin-bottom: 20px; }
        label { display: block; font-size: 11px; font-weight: 800; margin-bottom: 8px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        
        input { 
            width: 100%; padding: 14px; border: 1px solid #ddd; border-radius: 8px; 
            font-size: 14px; background: #fafafa; outline: none; transition: all 0.2s ease;
            font-family: 'Inter', sans-serif; font-weight: 600;
        }
        input:focus { border-color: #111; background: white; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }

        /* 6. BUTTON */
        .submit-btn { 
            width: 100%; padding: 16px; background: #111; color: white; border: none; border-radius: 8px; 
            font-size: 14px; font-weight: 800; text-transform: uppercase; cursor: pointer; 
            transition: all 0.2s ease; margin-top: 10px; letter-spacing: 0.5px;
            display: inline-flex; justify-content: center; align-items: center; gap: 8px;
        }
        .submit-btn i { width: 18px; height: 18px; }
        .submit-btn:hover { background: #cc0000; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(204,0,0,0.15); }

        /* 7. ALERTS */
        .alert { padding: 15px; border-radius: 8px; font-size: 13px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .alert i { width: 18px; height: 18px; }
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
        
        <a href="dashboard.php" class="menu-item">
            <i data-lucide="layout-dashboard"></i> Dashboard
        </a>
        <a href="pos.php" class="menu-item">
            <i data-lucide="monitor-smartphone"></i> POS Terminal
        </a>
        <a href="product_add.php" class="menu-item">
            <i data-lucide="package-plus"></i> Add Products
        </a>
        <a href="manage_users.php" class="menu-item active">
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
            <h1 class="page-title">Onboarding</h1>
            <a href="manage_users.php" class="back-btn"><i data-lucide="arrow-left"></i> Back to Staff List</a>
        </div>

        <div class="content-wrapper">
            <div class="form-card">
                
                <div class="form-header">
                    <div class="icon-circle"><i data-lucide="user-plus"></i></div>
                    <h2>New Employee</h2>
                    <p class="subtitle">Assign a new account to your warehouse or retail staff.</p>
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

                <form method="POST" action="">
                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname" placeholder="e.g. Dustin Perrera" required>
                    </div>

                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="employee@sneakdup.com" required>
                    </div>

                    <div class="input-group">
                        <label>Temporary Password</label>
                        <input type="text" name="password" placeholder="Set a secure password" required>
                    </div>

                    <button type="submit" class="submit-btn"><i data-lucide="user-check"></i> Create Account</button>
                </form>

            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>