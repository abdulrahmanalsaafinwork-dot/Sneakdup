<?php
session_start();
include 'db_conn.php';

// If user is already logged in, redirect them
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') header("Location: admin/dashboard.php");
    else if ($_SESSION['role'] == 'employee') header("Location: employee/orders.php");
    else header("Location: shop.php");
    exit();
}

$msg = "";
$msg_type = ""; 
$redirect_script = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, fullname, password, role, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            
            // --- 1. CHECK STATUS ---
            if ($row['status'] == 'blocked') {
                $msg = "Access Denied: Your account is suspended.";
                $msg_type = "error";
            } 
            elseif ($row['status'] == 'pending') {
                // --- 2. REDIRECT IF PENDING ---
                header("Location: verify.php?email=" . $email);
                exit();
            } 
            else {
                // --- 3. LOGIN SUCCESS ---
                session_regenerate_id(true); 
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['fullname'] = $row['fullname'];
                $_SESSION['role'] = $row['role'];

                $msg = "Login Successful! Redirecting...";
                $msg_type = "success";

                $target_url = "shop.php"; 
                if ($row['role'] == 'admin') $target_url = "admin/dashboard.php";
                elseif ($row['role'] == 'employee') $target_url = "employee/orders.php";

                $redirect_script = "<script>
                    setTimeout(function() { window.location.href = '$target_url'; }, 1500);
                </script>";
            }
        } else {
            $msg = "Incorrect Password";
            $msg_type = "error";
        }
    } else {
        $msg = "User not found";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login - SneakDup</title>
    <link rel="icon" href="images/Sneakdup_logo.jpg" type="image/jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        /* 1. RESET & BASE STYLES */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #fafafa; 
            color: #1a1a1a;
            display: flex; flex-direction: column;
            height: 100vh;
        }

        /* 2. HEADER */
        .header {
            width: 100%; background: #111; padding: 15px 40px;
            display: flex; justify-content: center; align-items: center;
            text-decoration: none; transition: 0.2s;
        }
        .header:hover { background: #000; }
        .brand { 
            font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; 
            color: white; letter-spacing: -1px; display: flex; align-items: center; gap: 10px;
        }
        .brand-text { white-space: nowrap; }
        .brand span { color: #cc0000; }
        .brand img { height: 35px; width: auto; border-radius: 4px; }

        /* 3. MAIN CONTENT */
        .main-content { flex: 1; display: flex; justify-content: center; align-items: center; padding: 20px; }

        /* 4. CARD */
        .login-card { 
            background: white; padding: 40px; 
            width: 100%; max-width: 400px; 
            border-radius: 12px; border: 1px solid #eee;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03); 
            text-align: center;
        }

        h2 { font-family: 'Oswald', sans-serif; font-size: 28px; text-transform: uppercase; margin-bottom: 5px; }
        p.subtitle { color: #666; font-size: 14px; margin-bottom: 25px; font-weight: 500; }

        /* --- 5. ALERTS --- */
        .alert {
            padding: 16px; border-radius: 8px; font-size: 13px; font-weight: 600;
            margin-bottom: 25px; text-align: left; display: flex; align-items: center; gap: 12px; 
            animation: slideIn 0.4s ease-out; border: 1px solid transparent;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert.success { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
        .alert.error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
        .alert .lucide { width: 20px; height: 20px; flex-shrink: 0; }
        
        /* 6. INPUTS */
        .input-group { margin-bottom: 20px; text-align: left; }
        label { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; margin-bottom: 8px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; }
        label .lucide { width: 14px; height: 14px; }
        
        .password-container { position: relative; width: 100%; display: flex; align-items: center; }
        
        input { 
            width: 100%; padding: 14px 15px; border: 1px solid #ddd; border-radius: 8px; 
            font-size: 14px; background: #fafafa; outline: none; transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }
        input:focus { border-color: #111; background: white; }
        
        /* Specific padding for password input so text doesn't hide behind the eye icon */
        input[type="password"], input#passwordInput { padding-right: 45px; }

        .toggle-password {
            position: absolute; right: 15px; cursor: pointer; color: #888; transition: 0.2s;
            display: flex; align-items: center; justify-content: center;
        }
        .toggle-password:hover { color: #111; }
        .toggle-password .lucide { width: 20px; height: 20px; }

        button { 
            width: 100%; padding: 16px; background: #111; color: white; border: none; 
            border-radius: 8px; font-family: 'Oswald', sans-serif; font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px;
            cursor: pointer; transition: transform 0.2s ease, background 0.2s ease; margin-top: 10px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        button:hover { background: #cc0000; transform: translateY(-2px); }

        .links { margin-top: 30px; font-size: 13px; color: #666; font-weight: 500; }
        .links a { color: #cc0000; text-decoration: none; font-weight: 700; transition: 0.2s; }
        .links a:hover { color: #aa0000; text-decoration: underline; }
    </style>
</head>
<body>

    <a href="shop.php" class="header">
        <div class="brand">
            <img src="images/Sneakdup_logo.jpg" alt="Logo">
            <div class="brand-text">SNEAK<span>D</span>UP</div>
        </div>
    </a>

    <div class="main-content">
        <div class="login-card">
            
            <h2>Welcome Back</h2>
            <p class="subtitle">Please enter your details to sign in.</p>
            
            <?php if($msg): ?>
                <div class="alert <?php echo $msg_type; ?>">
                    <?php if($msg_type == 'success'): ?>
                        <i data-lucide="check-circle"></i>
                    <?php else: ?>
                        <i data-lucide="alert-triangle"></i>
                    <?php endif; ?>
                    <span><?php echo $msg; ?></span>
                </div>
            <?php endif; ?>

            <?php if($msg_type !== 'success'): ?>
            <form method="POST" action="">
                <div class="input-group">
                    <label><i data-lucide="mail"></i> Email Address</label>
                    <input type="email" name="email" placeholder="name@example.com" required>
                </div>

                <div class="input-group">
                    <label><i data-lucide="lock"></i> Password</label>
                    <div class="password-container">
                        <input type="password" name="password" id="passwordInput" placeholder="••••••••" required>
                        <span class="toggle-password" onclick="togglePassword()">
                            <i data-lucide="eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit">
                    Sign In <i data-lucide="log-in" style="width: 18px; height: 18px;"></i>
                </button>
            </form>

            <div class="links">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Password Toggle Logic (Updated for Lucide)
        function togglePassword() {
            var passwordInput = document.getElementById("passwordInput");
            var eyeIcon = document.getElementById("eyeIcon");
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                // With Lucide, we change the icon attribute directly
                eyeIcon.setAttribute("data-lucide", "eye-off");
            } else {
                passwordInput.type = "password";
                eyeIcon.setAttribute("data-lucide", "eye");
            }
            // Re-render the specific icon
            lucide.createIcons();
        }
    </script>

    <?php echo $redirect_script; ?>

</body>
</html>