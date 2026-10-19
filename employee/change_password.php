<?php
session_start();
include '../db_conn.php';

// 1. Security Check: Must be logged in as Employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Attendance Check - Check if the employee is clocked in for today
$attendance_check = $conn->query("SELECT id FROM attendance WHERE user_id = '$user_id' AND date = CURDATE() AND time_out IS NULL");
$is_clocked_in = ($attendance_check->num_rows > 0);

$msg = "";
$msg_type = "";

// 3. Handle Password Update (Only processed if clocked in)
if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_clocked_in) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!password_verify($current_pass, $row['password'])) {
        $msg = "Incorrect current password.";
        $msg_type = "error";
    } 
    elseif ($new_pass !== $confirm_pass) {
        $msg = "New passwords do not match.";
        $msg_type = "error";
    } 
    else {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $new_hash, $user_id);
        
        if ($update->execute()) {
            $msg = "Password updated successfully!";
            $msg_type = "success";
        } else {
            $msg = "Error updating password.";
            $msg_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Change Password - SneakDup Staff</title>
    <link rel="icon" type="image/jpeg" href="../images/Sneakdup_logo.jpg">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f8; color: #1a1a1a; height: 100vh; overflow: hidden; display: flex; }

        /* SIDEBAR STYLES */
        .sidebar { width: 260px; background: #111; color: white; display: flex; flex-direction: column; padding: 25px; height: 100%; flex-shrink: 0; }
        .brand { font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; margin-bottom: 40px; color: white; letter-spacing: -1px; display: flex; align-items: center; gap: 10px; }
        .brand-text { white-space: nowrap; }
        .brand span { color: #cc0000; }
        .brand img { height: 35px; width: auto; border-radius: 6px; }
        .staff-badge { background: #333; color: #fff; font-size: 10px; padding: 4px 8px; border-radius: 4px; margin-left: auto; letter-spacing: 1px; }
        
        .menu-item { text-decoration: none; color: #aaa; padding: 12px 15px; margin-bottom: 8px; border-radius: 6px; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 12px; }
        .menu-item .lucide { width: 18px; height: 18px; }
        .menu-item.active { background: #cc0000; color: white; border-left: 3px solid #ff4d4d; }
        .menu-item:hover:not(.active) { background: #222; color: white; }
        
        .user-info { margin-top: auto; padding-top: 20px; border-top: 1px solid #333; font-size: 13px; color: #888; }
        .user-name { color: white; font-weight: 700; display: block; margin-bottom: 15px; }
        .logout-link { color: #cc0000; font-weight: 700; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .logout-link .lucide { width: 16px; height: 16px; }
        .logout-link:hover { color: #ff4d4d; }

        /* MAIN CONTENT AREA */
        .main-content { flex: 1; display: flex; flex-direction: column; height: 100%; overflow-y: auto; }
        .top-header { background: white; padding: 20px 40px; border-bottom: 1px solid #e5e5e5; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10; }
        .page-title { font-family: 'Oswald', sans-serif; font-size: 28px; text-transform: uppercase; margin: 0; }
        
        .content-wrapper { padding: 60px 20px; display: flex; justify-content: center; align-items: flex-start; height: 100%; }
        .form-card { background: white; padding: 40px; border-radius: 12px; width: 100%; max-width: 450px; border: 1px solid #eee; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .form-header { text-align: center; margin-bottom: 30px; }
        .icon-circle { width: 60px; height: 60px; background: #fff0f0; color: #cc0000; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; }
        .form-header h2 { font-family: 'Oswald', sans-serif; font-weight: 700; font-size: 24px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-header p { color: #777; font-size: 14px; font-weight: 500; margin: 0; }
        
        .input-group { margin-bottom: 20px; }
        label { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; margin-bottom: 8px; color: #666; text-transform: uppercase; }
        label .lucide { width: 14px; height: 14px; }
        input { width: 100%; padding: 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; background: #fafafa; outline: none; transition: all 0.2s ease; font-family: 'Inter', sans-serif; font-weight: 600; }
        input:focus { border-color: #111; background: white; }

        .submit-btn { width: 100%; padding: 16px; background: #111; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 800; text-transform: uppercase; cursor: pointer; transition: all 0.2s ease; margin-top: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .submit-btn:hover { background: #cc0000; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(204,0,0,0.15); }

        .alert { padding: 12px; border-radius: 8px; font-size: 13px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }

        /* Locked State Style */
        .locked-overlay { text-align: center; padding: 20px; }
        .locked-btn { display: inline-flex; align-items: center; gap: 8px; margin-top: 20px; background: #cc0000; color: white; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 700; font-size: 13px; text-transform: uppercase; transition: 0.2s; }
        .locked-btn:hover { background: #111; }
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
            <a href="orders.php" class="menu-item" onclick="alert('You must Clock In on the Dashboard first.');"><i data-lucide="lock"></i> POS Terminal</a>
        <?php endif; ?>

        <a href="my_sales.php" class="menu-item"><i data-lucide="receipt"></i> My Sales</a>
        <a href="../shop.php" target="_blank" class="menu-item"><i data-lucide="package-search"></i> Check Stock</a>
        
        <div style="margin-top: 20px;">
            <a href="change_password.php" class="menu-item active"><i data-lucide="key-round"></i> Change Password</a>
        </div>

        <div class="user-info">
            Logged in as:
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
            <a href="../logout.php" class="logout-link"><i data-lucide="log-out"></i> LOGOUT</a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1 class="page-title">Security Settings</h1>
        </div>

        <div class="content-wrapper">
            <div class="form-card">
                
                <?php if(!$is_clocked_in): ?>
                    <div class="locked-overlay">
                        <div class="icon-circle" style="background: #f4f4f4; color: #888;"><i data-lucide="lock-keyhole"></i></div>
                        <h2 style="font-family: 'Oswald', sans-serif;">Form Locked</h2>
                        <p style="color: #666; font-size: 14px; margin-top: 10px;">For security reasons, you must <strong>Clock In</strong> on your dashboard before you can change your password.</p>
                        <a href="orders.php" class="locked-btn"><i data-lucide="layout-dashboard"></i> Go to Dashboard</a>
                    </div>
                <?php else: ?>
                    <div class="form-header">
                        <div class="icon-circle"><i data-lucide="shield-check"></i></div>
                        <h2>Update Password</h2>
                        <p>Change your password regularly to stay secure.</p>
                    </div>

                    <?php if($msg): ?>
                        <div class="alert <?php echo $msg_type; ?>">
                            <i data-lucide="<?php echo ($msg_type == 'success') ? 'check-circle' : 'alert-triangle'; ?>"></i>
                            <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="input-group">
                            <label><i data-lucide="unlock"></i> Current Password</label>
                            <input type="password" name="current_password" placeholder="••••••••" required>
                        </div>
                        <div class="input-group">
                            <label><i data-lucide="lock"></i> New Password</label>
                            <input type="password" name="new_password" placeholder="••••••••" required>
                        </div>
                        <div class="input-group">
                            <label><i data-lucide="shield-alert"></i> Confirm New Password</label>
                            <input type="password" name="confirm_password" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="submit-btn">Save Changes <i data-lucide="save"></i></button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>