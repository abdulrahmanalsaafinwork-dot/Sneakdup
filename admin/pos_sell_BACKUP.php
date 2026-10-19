<?php
session_start();
include '../db_conn.php';

// --- SECURITY CHECK ---
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'employee')) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) { header("Location: pos.php"); exit(); }

$product_id = $_GET['id'];
$msg = "";
$msg_type = "";

// Variables to hold receipt data if sale is successful
$show_receipt = false;
$r_no = $r_date = $r_item = $r_variant = $r_qty = $r_total = $r_cash = $r_change = $r_cashier = "";

// 1. Fetch Product Info
$p_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$p_stmt->bind_param("i", $product_id);
$p_stmt->execute();
$product = $p_stmt->get_result()->fetch_assoc();

if (!$product) { header("Location: pos.php"); exit(); }

// 2. Fetch Available Variants (Sizes)
$v_stmt = $conn->query("SELECT * FROM product_variants WHERE product_id = $product_id AND stock > 0 ORDER BY size ASC");

// 3. HANDLE THE SALE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $variant_id = $_POST['variant_id'];
    $qty = intval($_POST['quantity']);
    $cash_received = floatval($_POST['cash_received']);
    
    // Check Stock Level First
    $check = $conn->query("SELECT * FROM product_variants WHERE id = $variant_id");
    $variant = $check->fetch_assoc();

    if ($variant['stock'] >= $qty) {
        $total_price = $product['price'] * $qty;
        
        if ($cash_received >= $total_price) {
            $change_amount = $cash_received - $total_price;
            
            // Subtract Stock
            $update = $conn->query("UPDATE product_variants SET stock = stock - $qty WHERE id = $variant_id");

            // Record Sale in History
            $variant_info = $variant['size'] . " - " . $variant['color'];
            $user_id = $_SESSION['user_id'];
            $p_name = $product['name'];

            $log_sale = $conn->prepare("INSERT INTO sales (user_id, product_name, variant_info, quantity, total_price) VALUES (?, ?, ?, ?, ?)");
            $log_sale->bind_param("issid", $user_id, $p_name, $variant_info, $qty, $total_price);
            
            if ($log_sale->execute()) {
                $msg = "Transaction Successful! Printing receipt...";
                $msg_type = "success";
                
                // --- CAPTURE DATA FOR THE RECEIPT ---
                $show_receipt = true;
                $r_no = str_pad($conn->insert_id, 6, '0', STR_PAD_LEFT);
                $r_date = date('M d, Y h:i A');
                $r_item = $p_name;
                $r_variant = $variant_info;
                $r_qty = $qty;
                $r_total = $total_price;
                $r_cash = $cash_received;
                $r_change = $change_amount;
                $r_cashier = $_SESSION['fullname'];

                // Refresh variants to show new stock
                $v_stmt = $conn->query("SELECT * FROM product_variants WHERE product_id = $product_id AND stock > 0 ORDER BY size ASC");
            } else {
                $msg = "Error recording transaction.";
                $msg_type = "error";
            }
        } else {
            $msg = "Transaction Failed: Insufficient cash received.";
            $msg_type = "error";
        }
    } else {
        $msg = "Transaction Failed: Only " . $variant['stock'] . " units left in stock.";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Process Transaction - SneakDup</title>
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

        /* 2. LEFT SIDEBAR */
        .sidebar {
            width: 260px; background: #111; color: white; display: flex; flex-direction: column;
            padding: 25px; height: 100%; flex-shrink: 0;
        }
        .brand { 
            font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; 
            margin-bottom: 40px; color: white; letter-spacing: -1px; display: flex; align-items: center; gap: 8px;
        }
        .brand span { color: #cc0000; }
        .pos-badge {
            background: #333; color: #fff; font-size: 10px; padding: 4px 8px; 
            border-radius: 4px; margin-left: 5px; letter-spacing: 1px;
        }

        /* Minimalist Icon Styling */
        .menu-item {
            text-decoration: none; color: #aaa; padding: 12px 15px; margin-bottom: 8px;
            border-radius: 6px; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 12px;
        }
        .menu-item i { width: 18px; height: 18px; opacity: 0.7; transition: 0.3s; }
        .menu-item.active { background: #cc0000; color: white; }
        .menu-item.active i, .menu-item:hover i { opacity: 1; color: #fff; }
        .menu-item:hover:not(.active) { background: #222; color: white; }

        /* 3. MAIN CONTENT AREA */
        .main-content { flex: 1; display: flex; flex-direction: column; height: 100%; overflow-y: auto; }
        
        .top-header {
            background: white; padding: 15px 30px; border-bottom: 1px solid #e5e5e5;
            display: flex; justify-content: space-between; align-items: center;
        }
        .page-title { font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; margin: 0; }
        .back-btn { 
            background: #f4f4f4; border: 1px solid #ddd; padding: 8px 16px; 
            border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 13px; color: #111; transition: 0.2s;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .back-btn i { width: 16px; height: 16px; }
        .back-btn:hover { background: #e5e5e5; }

        /* 4. TRANSACTION CARD */
        .checkout-container { padding: 40px; display: flex; justify-content: center; }
        .checkout-card {
            background: white; border-radius: 12px; width: 100%; max-width: 800px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid #eee; display: flex; overflow: hidden;
        }
        
        .product-col { flex: 1; background: #f9f9f9; padding: 40px; border-right: 1px solid #eee; display: flex; flex-direction: column; align-items: center; text-align: center; }
        .product-col img { width: 100%; max-width: 200px; height: auto; object-fit: contain; margin-bottom: 20px; mix-blend-mode: multiply; }
        .category { font-size: 12px; font-weight: 800; color: #888; text-transform: uppercase; margin-bottom: 5px; }
        .product-name { font-family: 'Oswald', sans-serif; font-size: 28px; line-height: 1.2; margin-bottom: 15px; text-transform: uppercase; }
        .product-price { font-size: 24px; font-weight: 800; color: #cc0000; }

        .form-col { flex: 1.2; padding: 40px; }
        .form-title { font-size: 16px; font-weight: 800; text-transform: uppercase; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 12px; text-transform: uppercase; color: #666; }
        select, input { 
            width: 100%; padding: 14px; margin-bottom: 20px; border: 1px solid #ddd; 
            border-radius: 8px; font-size: 14px; font-weight: 600; outline: none; background: #fafafa; transition: 0.2s;
        }
        select:focus, input:focus { border-color: #111; background: white; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }

        .total-display { 
            background: #111; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;
            display: flex; flex-direction: column; gap: 10px;
        }
        .total-row { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .total-label { font-size: 14px; font-weight: 700; text-transform: uppercase; }
        .total-amount { font-family: 'Oswald', sans-serif; font-size: 24px; }
        .change-amount { font-family: 'Oswald', sans-serif; font-size: 20px; color: #22c55e; }

        .btn-confirm { 
            width: 100%; padding: 18px; background: #cc0000; color: white; border: none; 
            border-radius: 8px; font-size: 16px; font-weight: 800; text-transform: uppercase; cursor: pointer; transition: 0.2s;
            display: inline-flex; justify-content: center; align-items: center; gap: 8px;
        }
        .btn-confirm:hover:not(:disabled) { background: #aa0000; }
        .btn-confirm:disabled { background: #ccc; cursor: not-allowed; }

        .alert { padding: 15px; margin-bottom: 25px; border-radius: 8px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .alert i { width: 20px; height: 20px; }
        .success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* --- RECEIPT STYLES (Hidden on screen, visible on print) --- */
        #printable-receipt { display: none; }

        @media print {
            body * { visibility: hidden; }
            body { background: white; margin: 0; padding: 0; }
            
            #printable-receipt, #printable-receipt * { visibility: visible; }
            
            #printable-receipt { 
                display: block; position: absolute; left: 0; top: 0; 
                width: 80mm; /* Standard thermal printer width */
                font-family: 'Courier New', Courier, monospace; color: black; font-size: 12px; padding: 10px;
            }

            .r-header { text-align: center; margin-bottom: 15px; border-bottom: 1px dashed black; padding-bottom: 10px; }
            .r-brand { font-family: 'Oswald', sans-serif; font-size: 24px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
            .r-info { margin-bottom: 15px; }
            .r-info div { margin-bottom: 3px; }
            .r-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            .r-table th { border-bottom: 1px dashed black; border-top: 1px dashed black; padding: 5px 0; text-align: left; }
            .r-table td { padding: 5px 0; vertical-align: top; }
            .r-total-row { border-top: 1px dashed black; font-weight: bold; font-size: 14px; }
            .r-calc-row { display: flex; justify-content: space-between; margin-bottom: 3px; }
            .r-footer { text-align: center; margin-top: 20px; font-size: 11px; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">
            <img src="../images/Sneakdup_logo.jpg" alt="Logo" style="width: 30px; height: 30px; border-radius: 6px; object-fit: cover;">
            <div style="display: flex; align-items: center;">
                SNEAK<span style="color: #cc0000;">D</span>UP
            </div>
            <div class="pos-badge">POS</div>
        </div>
        
        <a href="pos.php" class="menu-item active">
            <i data-lucide="monitor-smartphone"></i> Terminal
        </a>
        <a href="<?php echo ($_SESSION['role'] === 'admin') ? 'dashboard.php' : '../employee/orders.php'; ?>" class="menu-item">
            <i data-lucide="layout-dashboard"></i> Dashboard
        </a>
    </div>

    <div class="main-content">
        
        <div class="top-header">
            <h1 class="page-title">Process Transaction</h1>
            <a href="pos.php" class="back-btn"><i data-lucide="arrow-left"></i> Back to Terminal</a>
        </div>

        <div class="checkout-container">
            <div class="checkout-card">
                
                <div class="product-col">
                    <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="Product">
                    <div class="category"><?php echo htmlspecialchars($product['category']); ?></div>
                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                    <input type="hidden" id="basePrice" value="<?php echo $product['price']; ?>">
                </div>

                <div class="form-col">
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

                    <div class="form-title">Order Details</div>

                    <form method="POST" id="checkoutForm">
                        <label>Select Variant / Size</label>
                        <select name="variant_id" required>
                            <?php 
                            if ($v_stmt->num_rows > 0) {
                                while($v = $v_stmt->fetch_assoc()) {
                                    echo "<option value='".$v['id']."'>Size ".$v['size']." - ".$v['color']." (".$v['stock']." available)</option>";
                                }
                            } else {
                                echo "<option value='' disabled selected>OUT OF STOCK</option>";
                            }
                            ?>
                        </select>

                        <div style="display: flex; gap: 15px;">
                            <div style="flex: 1;">
                                <label>Quantity</label>
                                <input type="number" name="quantity" id="qtyInput" value="1" min="1" max="20" required onchange="calculateTotal()" onkeyup="calculateTotal()">
                            </div>
                            <div style="flex: 1;">
                                <label>Cash Received (₱)</label>
                                <input type="number" name="cash_received" id="cashInput" min="0" step="0.01" placeholder="0.00" required onchange="calculateTotal()" onkeyup="calculateTotal()">
                            </div>
                        </div>

                        <div class="total-display">
                            <div class="total-row">
                                <span class="total-label">Total Due</span>
                                <span class="total-amount" id="totalAmount">₱<?php echo number_format($product['price'], 2); ?></span>
                            </div>
                            <div class="total-row" style="border-top: 1px solid #333; padding-top: 10px;">
                                <span class="total-label" style="color: #aaa;">Change</span>
                                <span class="change-amount" id="changeAmount">₱0.00</span>
                            </div>
                        </div>

                        <?php if ($v_stmt->num_rows > 0): ?>
                            <button type="submit" id="btnConfirm" class="btn-confirm" disabled>Enter Cash Amount</button>
                        <?php else: ?>
                            <button type="button" class="btn-confirm" disabled>Cannot Process</button>
                        <?php endif; ?>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <?php if($show_receipt): ?>
    <div id="printable-receipt">
        <div class="r-header">
            <div class="r-brand">SNEAKDUP</div>
            <div>Datamex College Branch</div>
            <div>Parañaque City</div>
        </div>
        
        <div class="r-info">
            <div><strong>Receipt No:</strong> #<?php echo $r_no; ?></div>
            <div><strong>Date:</strong> <?php echo $r_date; ?></div>
            <div><strong>Cashier:</strong> <?php echo $r_cashier; ?></div>
        </div>

        <table class="r-table">
            <thead>
                <tr>
                    <th>QTY</th>
                    <th>ITEM</th>
                    <th style="text-align: right;">AMT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $r_qty; ?>x</td>
                    <td>
                        <?php echo htmlspecialchars($r_item); ?><br>
                        <small>(<?php echo htmlspecialchars($r_variant); ?>)</small>
                    </td>
                    <td style="text-align: right;"><?php echo number_format($r_total, 2); ?></td>
                </tr>
                <tr><td colspan="3" style="padding-top: 10px;"></td></tr>
                
                <tr class="r-total-row">
                    <td colspan="2" style="padding-top: 10px;">TOTAL DUE:</td>
                    <td style="text-align: right; padding-top: 10px;">PHP <?php echo number_format($r_total, 2); ?></td>
                </tr>
                <tr>
                    <td colspan="2">CASH TENDERED:</td>
                    <td style="text-align: right;">PHP <?php echo number_format($r_cash, 2); ?></td>
                </tr>
                <tr>
                    <td colspan="2">CHANGE:</td>
                    <td style="text-align: right;">PHP <?php echo number_format($r_change, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="r-footer">
            <p>Thank you for shopping at SneakDup!</p>
            <p>Returns/Exchanges allowed within 7 days with this receipt.</p>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
    <?php endif; ?>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        function calculateTotal() {
            let basePrice = parseFloat(document.getElementById('basePrice').value);
            let qty = parseInt(document.getElementById('qtyInput').value);
            let cash = parseFloat(document.getElementById('cashInput').value);
            
            if (isNaN(qty) || qty < 1) { qty = 1; }
            if (isNaN(cash) || cash < 0) { cash = 0; }
            
            let total = basePrice * qty;
            let change = cash - total;
            
            // Update Total Due
            document.getElementById('totalAmount').innerText = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            let changeEl = document.getElementById('changeAmount');
            let btn = document.getElementById('btnConfirm');

            // Logic to disable button if not enough cash
            if (cash >= total) {
                changeEl.innerText = '₱' + change.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                changeEl.style.color = '#22c55e'; // Green
                if(btn) { 
                    btn.disabled = false; 
                    btn.innerText = "Complete Sale & Print"; 
                }
            } else {
                changeEl.innerText = 'Insufficient Cash';
                changeEl.style.color = '#ff4d4d'; // Red
                if(btn) { 
                    btn.disabled = true; 
                    btn.innerText = "Need More Cash"; 
                }
            }
        }
        
        // Run once on page load to initialize
        calculateTotal();

        // --- THE PROFESSOR'S FIX: SMART TENDER VALIDATION ---
        document.getElementById('checkoutForm').addEventListener('submit', function(event) {
            let basePrice = parseFloat(document.getElementById('basePrice').value);
            let qty = parseInt(document.getElementById('qtyInput').value);
            let cash = parseFloat(document.getElementById('cashInput').value);
            let total = basePrice * qty;

            // Rule 1: Did they encode an unnecessary 1,000 bill?
            // E.g., Total is 1700, they encode 3000. (3000 - 1000 = 2000, which is enough)
            if (cash % 1000 === 0 && (cash - 1000) >= total) {
                event.preventDefault(); // Stop the form!
                alert("⚠️ ENCODING ERROR DETECTED!\n\nThe tendered amount (₱" + cash + ") includes an unnecessary ₱1,000 bill.\n₱" + (cash - 1000) + " is sufficient to cover the ₱" + total + " total.\n\nPlease verify the cash received from the customer.");
                return;
            }

            // Rule 2: Did they encode an unnecessary 500 bill? (The Professor's Exact Example)
            // E.g., Total is 1700, they encode 2500. (2500 - 500 = 2000, which is enough)
            if (cash % 1000 !== 0 && cash % 500 === 0 && (cash - 500) >= total) {
                event.preventDefault(); // Stop the form!
                alert("⚠️ ENCODING ERROR DETECTED!\n\nThe tendered amount (₱" + cash + ") includes an unnecessary ₱500 bill.\n₱" + (cash - 500) + " is sufficient to cover the ₱" + total + " total.\n\nPlease verify the cash received from the customer.");
                return;
            }
        });
    </script>

</body>
</html>