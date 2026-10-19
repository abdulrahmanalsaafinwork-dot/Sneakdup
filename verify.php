<?php
include 'db_conn.php';
$msg = "";
$msg_type = "";

// Check if email is in the link (e.g., verify.php?email=john@gmail.com)
if (isset($_GET['email'])) {
    $email = $_GET['email'];
} else {
    header("Location: register.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_code = trim($_POST['otp_code']);
    $email = $_POST['email'];

    // Check if the code matches the database
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND verification_code = ?");
    $stmt->bind_param("ss", $email, $entered_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // MATCH! Activate the user
        $update = $conn->prepare("UPDATE users SET status = 'active', verification_code = NULL WHERE email = ?");
        $update->bind_param("s", $email);
        
        if ($update->execute()) {
            $msg = "Account Verified! Redirecting to login...";
            $msg_type = "success";
            echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 1500);</script>";
        }
    } else {
        $msg = "Invalid Code. Please try again.";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Verify Account - SneakDup</title>
    <link rel="icon" href="images/Sneakdup_logo.jpg" type="image/jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #fafafa; color: #1a1a1a; display: flex; flex-direction: column; height: 100vh; }
        .header { width: 100%; background: white; padding: 15px 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; align-items: center; }
        .header-logo { height: 40px; width: auto; }
        .main-content { flex: 1; display: flex; justify-content: center; align-items: center; }
        .card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); text-align: center; width: 100%; max-width: 400px; }
        h2 { font-weight: 700; margin-bottom: 10px; }
        p { color: #666; font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
        input { width: 100%; padding: 15px; margin: 15px 0; border: 1px solid #ddd; border-radius: 8px; text-align: center; letter-spacing: 5px; font-size: 24px; font-weight: bold; }
        button { width: 100%; padding: 15px; background: #111; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        button:hover { background: #000; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: left; }
        .success { background: #ecfdf5; color: #065f46; border-left: 4px solid #10b981; }
        .error { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>
    <div class="header">
        <img src="images/Sneakdup_logo.jpg" alt="SneakDup Logo" class="header-logo">
    </div>
    <div class="main-content">
        <div class="card">
            <h2>Verify Your Email</h2>
            <p>We sent a 6-digit code to <br><strong><?php echo htmlspecialchars($email); ?></strong></p>
            
            <?php if($msg): ?>
                <div class="alert <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <?php if($msg_type !== 'success'): ?>
            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="text" name="otp_code" placeholder="000000" maxlength="6" required autofocus>
                <button type="submit">Verify Account</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>