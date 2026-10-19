<?php
include 'db_conn.php';
include 'send_email.php'; // Load the email sender

$msg = "";
$msg_type = ""; 
$redirect_script = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $raw_password = $_POST['password'];
    $role = 'customer'; 
    $status = 'pending'; // IMPORTANT: They are not active yet!

    // 1. Validation
    if (strlen($raw_password) < 4 || strlen($raw_password) > 6) {
        $msg = "Password must be 4-6 characters.";
        $msg_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Invalid email format.";
        $msg_type = "error";
    } else {
        // 2. Check if email exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $msg = "Email already registered.";
            $msg_type = "error";
        } else {
            // 3. Generate Code & Insert User
            $otp = rand(100000, 999999);
            $password = password_hash($raw_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, status, verification_code) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $fullname, $email, $password, $role, $status, $otp);

            if ($stmt->execute()) {
                // 4. Send the Email
                if (sendVerificationEmail($email, $otp)) {
                    // Success! Redirect to verify page
                    header("Location: verify.php?email=" . $email);
                    exit();
                } else {
                    $msg = "Account created, but email failed to send. Check your internet.";
                    $msg_type = "error";
                }
            } else {
                $msg = "Database Error.";
                $msg_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register - SneakDup</title>
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
            min-height: 100vh;
        }

        /* 2. HEADER (Matches Login) */
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
        .main-content { flex: 1; display: flex; justify-content: center; align-items: center; padding: 40px 20px; }

        /* 4. CARD */
        .reg-card { 
            background: white; padding: 40px; 
            width: 100%; max-width: 450px; 
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
        <div class="reg-card">
            
            <h2>Create Account</h2>
            <p class="subtitle">Join the club. It only takes a minute.</p>
            
            <?php if($msg): ?>
                <div class="alert error">
                    <i data-lucide="alert-triangle"></i>
                    <span><?php echo $msg; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <label><i data-lucide="user"></i> Full Name</label>
                    <input type="text" name="fullname" placeholder="John Doe" required>
                </div>

                <div class="input-group">
                    <label><i data-lucide="mail"></i> Email Address</label>
                    <input type="email" name="email" placeholder="name@example.com" required>
                </div>

                <div class="input-group">
                    <label><i data-lucide="lock"></i> Password (4-6 Characters)</label>
                    <div class="password-container">
                        <input type="password" name="password" id="passwordInput" placeholder="••••••" required minlength="4" maxlength="6">
                        <span class="toggle-password" onclick="togglePassword()">
                            <i data-lucide="eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit">
                    Create Account <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i>
                </button>
            </form>

            <div class="links">
                Already have an account? <a href="login.php">Login here</a>
            </div>
            
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Password Toggle Logic
        function togglePassword() {
            var passwordInput = document.getElementById("passwordInput");
            var eyeIcon = document.getElementById("eyeIcon");
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.setAttribute("data-lucide", "eye-off");
            } else {
                passwordInput.type = "password";
                eyeIcon.setAttribute("data-lucide", "eye");
            }
            // Re-render the specific icon
            lucide.createIcons();
        }
    </script>

</body>
</html>