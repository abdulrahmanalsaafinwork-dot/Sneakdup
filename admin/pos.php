<?php
session_start();
include __DIR__ . '/../db_conn.php';

// 1. Security Check
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'employee')) {
    header("Location: /login.php");
    exit();
}

$msg = "";
$msg_type = "";
$show_receipt = false;

// --- CART CHECKOUT LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data'])) {
    $cart = json_decode($_POST['cart_data'], true);
    $cash_tendered = floatval($_POST['cash_tendered']);
    $user_id = $_SESSION['user_id'];
    $cashier_name = $_SESSION['fullname'];
    
    // Generate a unique Receipt Number
    $receipt_no = "REC-" . strtoupper(substr(uniqid(), -6)); 
    
    $grand_total = 0;
    foreach ($cart as $item) {
        $grand_total += ($item['price'] * $item['qty']);
    }
    
    if ($cash_tendered >= $grand_total && count($cart) > 0) {
        // Process each item in the cart
        foreach ($cart as $item) {
            $v_id = $item['variant_id'];
            $qty = $item['qty'];
            $p_name = $item['name'];
            $v_info = $item['variant_info'];
            $line_total = $item['price'] * $qty;
            
            // Deduct stock
            $conn->query("UPDATE product_variants SET stock = stock - $qty WHERE id = $v_id");
            
            // Log to sales table (NOW USING receipt_no!)
            $stmt = $conn->prepare("INSERT INTO sales (user_id, product_name, variant_info, quantity, total_price, receipt_no) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issids", $user_id, $p_name, $v_info, $qty, $line_total, $receipt_no);
            $stmt->execute();
        }
        
        $change = $cash_tendered - $grand_total;
        
        // Prepare Receipt Data
        $show_receipt = true;
        $r_no = $receipt_no;
        $r_date = date('M d, Y h:i A');
        $r_cashier = $cashier_name;
        $r_total = $grand_total;
        $r_cash = $cash_tendered;
        $r_change = $change;
        $r_items = $cart;
        
        $msg = "Transaction Successful!";
        $msg_type = "success";
    } else {
        $msg = "Transaction Failed: Insufficient cash or empty cart.";
        $msg_type = "error";
    }
}

// 2. Fetch Products
$sql = "SELECT * FROM products ORDER BY name ASC";
$result = $conn->query($sql);

// 3. Fetch All Variants (So JS can load them instantly in the popup)
$variants_query = $conn->query("SELECT * FROM product_variants WHERE stock > 0");
$all_variants = [];
while($v = $variants_query->fetch_assoc()){
    $all_variants[$v['product_id']][] = $v;
}
$variants_json = json_encode($all_variants);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>POS Terminal - SneakDup</title>
    <link rel="icon" type="image/jpeg" href="../images/Sneakdup_logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        /* 1. APP LAYOUT RESET */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f8; color: #1a1a1a; height: 100vh; overflow: hidden; display: flex; }

        /* 2. LEFT SIDEBAR */
        .sidebar { width: 260px; background: #111; color: white; display: flex; flex-direction: column; padding: 25px; height: 100%; flex-shrink: 0; }
        .brand { font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; margin-bottom: 40px; color: white; letter-spacing: -1px; display: flex; align-items: center; gap: 8px; }
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
        .pos-badge { background: #333; color: #fff; font-size: 10px; padding: 4px 8px; border-radius: 4px; letter-spacing: 1px; margin-left: 5px; }
        .menu-item { text-decoration: none; color: #aaa; padding: 12px 15px; margin-bottom: 8px; border-radius: 6px; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 12px; }
 .menu-item i { width: 18px; height: 18px; opacity: 0.7; transition: 0.3s; }
 .menu-item i { width: 18px; height: 18px; opacity: 0.7; transition: 0.3s; }
.menu-item.active {
    background: #222;
    color: white;
    border-left: 3px solid #ff0000;
    border-radius: 6px;
}
        .menu-item.active i, .menu-item:hover i { opacity: 1; color: #fff; }
         .menu-item:hover { background: #222; color: white; }
        .user-info { margin-top: auto; padding-top: 20px; border-top: 1px solid #333; font-size: 13px; color: #888; }
        .user-name { color: white; font-weight: 700; display: block; margin-bottom: 12px; font-size: 14px; margin-top: 4px;}
        .logout-link { color: #cc0000; font-weight: 700; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 10px; transition: 0.2s;}
        .logout-link:hover { color: #ff4d4d; }

        /* 3. MAIN SPLIT SCREEN */
        .main-content { flex: 1; display: flex; height: 100%; overflow: hidden; }
        
        /* LEFT: CATALOG */
        .catalog-section { flex: 7; display: flex; flex-direction: column; height: 100%; border-right: 1px solid #ddd; }
        .top-header { background: white; padding: 15px 30px; border-bottom: 1px solid #e5e5e5; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; margin: 0; }
        .search-container { position: relative; width: 300px; display: flex; align-items: center; }
        .search-input { width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; background: #f9f9f9; outline: none; font-weight: 600; }
        .search-icon { position: absolute; left: 12px; color: #888; width: 18px; height: 18px; }

        .grid-container { padding: 30px; overflow-y: auto; flex: 1; background: #f4f6f8; }
        .pos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
        .pos-card { background: white; border-radius: 12px; overflow: hidden; border: 1px solid #eee; transition: all 0.2s ease; cursor: pointer; display: flex; flex-direction: column; position: relative; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .pos-card:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); border-color: #ccc; }
        .card-img { height: 140px; background: #f9f9f9; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 10px;}
        .card-img img { width: 100%; height: 100%; object-fit: contain; mix-blend-mode: multiply;}
        .card-body { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; }
        .category { font-size: 10px; font-weight: 800; color: #888; text-transform: uppercase; margin-bottom: 4px; }
        .title { font-weight: 700; font-size: 13px; margin-bottom: 8px; color: #111; line-height: 1.3; flex-grow: 1; }
        .price-row { display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
        .price { color: #111; font-weight: 800; font-size: 15px; font-family: 'Oswald', sans-serif; }
        .promo-badge { position: absolute; top: 10px; left: 10px; background: #cc0000; color: white; padding: 3px 6px; font-size: 9px; font-weight: 800; border-radius: 4px; z-index: 10; }

        /* RIGHT: LIVE CART */
        .cart-sidebar { flex: 3; background: white; display: flex; flex-direction: column; height: 100%; min-width: 320px;}
        .cart-header { padding: 20px; border-bottom: 1px solid #eee; background: #fafafa; font-family: 'Oswald', sans-serif; font-size: 20px; text-transform: uppercase; display: flex; align-items: center; gap: 8px;}
        
        .cart-items { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px; }
        .cart-item { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #eee; padding-bottom: 15px; }
        .cart-item-details { flex: 1; }
        .ci-name { font-size: 13px; font-weight: 700; color: #111; margin-bottom: 2px;}
        .ci-variant { font-size: 11px; color: #666; font-weight: 600; }
        .ci-price { font-size: 14px; font-weight: 800; font-family: 'Oswald', sans-serif; }
        .ci-remove { color: #cc0000; cursor: pointer; background: none; border: none; padding: 5px; }

        .cart-footer { padding: 20px; background: #fafafa; border-top: 1px solid #ddd; }
        .cf-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; font-weight: 700; color: #555;}
        .cf-total { font-family: 'Oswald', sans-serif; font-size: 24px; color: #111; }
        
        .cash-input-group { margin: 15px 0; }
        .cash-input-group label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 5px; color: #888;}
        .cash-input-group input { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 16px; font-weight: 700; outline: none; transition: 0.2s;}
        .cash-input-group input:focus { border-color: #111; }
        
        .change-row { display: flex; justify-content: space-between; font-weight: 800; color: #22c55e; font-size: 18px; margin-bottom: 15px; font-family: 'Oswald', sans-serif;}

        .checkout-btn { width: 100%; padding: 15px; background: #111; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: 800; text-transform: uppercase; cursor: pointer; transition: 0.2s; display: flex; justify-content: center; align-items: center; gap: 8px;}
        .checkout-btn:hover { background: #cc0000; }
        .checkout-btn:disabled { background: #ccc; cursor: not-allowed; }

        /* MODAL FOR VARIANT SELECTION */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: none; align-items: center; justify-content: center; z-index: 2000; backdrop-filter: blur(2px);}
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;}
        .modal-header h3 { font-family: 'Oswald', sans-serif; font-size: 20px; margin: 0; text-transform: uppercase;}
        .close-btn { background: none; border: none; cursor: pointer; color: #888; }
        
        .modal-body label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; color: #555;}
        .modal-body select, .modal-body input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: 600;}
        .modal-body select:focus, .modal-body input:focus { outline: none; border-color: #111; }
        
        .add-to-cart-btn { width: 100%; padding: 14px; background: #cc0000; color: white; border: none; border-radius: 6px; font-weight: 800; text-transform: uppercase; cursor: pointer; transition: 0.2s;}
        .add-to-cart-btn:hover { background: #aa0000; }

        /* ALERTS */
        .alert { padding: 15px; margin: 15px; border-radius: 8px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 10px; }
        .alert.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* RECEIPT PRINTING */
        #printable-receipt { display: none; }
        @media print {
            body * { visibility: hidden; }
            body { background: white; margin: 0; padding: 0; }
            #printable-receipt, #printable-receipt * { visibility: visible; }
            #printable-receipt { display: block; position: absolute; left: 0; top: 0; width: 80mm; font-family: 'Courier New', Courier, monospace; color: black; font-size: 12px; padding: 10px; }
            .r-header { text-align: center; margin-bottom: 15px; border-bottom: 1px dashed black; padding-bottom: 10px; }
            .r-brand { font-family: 'Oswald', sans-serif; font-size: 24px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
            .r-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; margin-top: 15px;}
            .r-table th { border-bottom: 1px dashed black; border-top: 1px dashed black; padding: 5px 0; text-align: left; }
            .r-table td { padding: 5px 0; vertical-align: top; }
            .r-total-row { border-top: 1px dashed black; font-weight: bold; font-size: 14px; }
            .r-footer { text-align: center; margin-top: 20px; font-size: 11px; }
        }

/* POS MAIN LAYOUT */
.admin-layout {
    display: flex;
    width: 100%;
    height: 100vh;
    overflow: hidden;
}

.pos-layout {
    display: flex;
    flex: 1;
    width: 100%;
    min-width: 0;
    height: 100%;
    overflow: hidden;
}

.product-panel {
    flex: 1;
    min-width: 0;
    overflow-y: auto;
}

.cart-panel {
    flex: 0 0 350px;
    width: 350px;
    min-width: 350px;
    box-sizing: border-box;
    height: 100%;
    display: flex;
    flex-direction: column;
    background: white;
    border-left: 1px solid #ddd;
}

    </style>
</head>
<body>

   <div class="admin-layout">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="catalog-section">
            <div class="top-header">
                <h1 class="page-title">Catalog</h1>
                <div class="search-container">
                    <i data-lucide="search" class="search-icon"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search product name..." onkeyup="filterProducts()">
                </div>
            </div>

            <?php if($msg): ?>
                <div class="alert <?php echo $msg_type; ?>">
                    <i data-lucide="<?php echo $msg_type == 'success' ? 'check-circle-2' : 'alert-circle'; ?>"></i>
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="grid-container">
                <div class="pos-grid" id="productGrid">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <div class="pos-card" data-name="<?php echo strtolower($row['name']); ?>" onclick="openVariantModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>', <?php echo $row['price']; ?>)">
                                <?php if($row['is_promo_eligible']): ?>
                                    <div class="promo-badge">PROMO</div>
                                <?php endif; ?>
                                
                                <div class="card-img">
                                    <img src="../uploads/<?php echo $row['image']; ?>" alt="Product">
                                </div>
                                
                                <div class="card-body">
                                    <div class="category"><?php echo htmlspecialchars($row['category']); ?></div>
                                    <div class="title"><?php echo htmlspecialchars($row['name']); ?></div>
                                    
                                    <div class="price-row">
                                        <div class="price">₱<?php echo number_format($row['price'], 2); ?></div>
                                        <div style="color: #cc0000; font-weight: bold; font-size: 12px;"><i data-lucide="mouse-pointer-click" style="width: 14px;"></i> Select</div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="grid-column: 1/-1; text-align: center; color: #999;">No products found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="cart-sidebar">
            <div class="cart-header"><i data-lucide="shopping-cart"></i> Current Order</div>
            
            <div class="cart-items" id="cartItemsList">
                <div style="text-align: center; color: #aaa; margin-top: 50px; font-size: 13px; font-weight: 600;">Cart is empty.<br>Click a product to add it.</div>
            </div>

            <div class="cart-footer">
                <div class="cf-row">
                    <span>Total Due:</span>
                    <span class="cf-total" id="cartTotalDisplay">₱0.00</span>
                </div>
                
                <div class="cash-input-group">
                    <label>Cash Received (₱)</label>
                    <input type="number" id="cashInput" placeholder="0.00" onkeyup="calculateChange()" disabled>
                </div>
                
                <div class="change-row">
                    <span>Change:</span>
                    <span id="changeDisplay">₱0.00</span>
                </div>

                <form method="POST" id="checkoutForm">
                    <input type="hidden" name="cart_data" id="cartDataInput">
                    <input type="hidden" name="cash_tendered" id="cashTenderedInput">
                    <button type="button" id="btnCheckout" class="checkout-btn" disabled onclick="validateAndSubmit()"><i data-lucide="printer"></i> Pay & Print</button>
                </form>
            </div>
        </div>

    </div>

    <div class="modal-overlay" id="variantModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalProductName">Product Name</h3>
                <button class="close-btn" onclick="closeModal()"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalProductId">
                <input type="hidden" id="modalProductPrice">
                
                <label>Select Size & Color</label>
                <select id="modalVariantSelect"></select>
                
                <label>Quantity</label>
                <input type="number" id="modalQty" value="1" min="1">
                
                <button class="add-to-cart-btn" onclick="addToCart()">Add to Cart <i data-lucide="arrow-right" style="width:16px; vertical-align: middle;"></i></button>
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
        
        <div style="margin-bottom: 15px;">
            <div><strong>Receipt No:</strong> <?php echo $r_no; ?></div>
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
                <?php foreach($r_items as $item): ?>
                <tr>
                    <td><?php echo $item['qty']; ?>x</td>
                    <td>
                        <?php echo htmlspecialchars($item['name']); ?><br>
                        <small>(<?php echo htmlspecialchars($item['variant_info']); ?>)</small>
                    </td>
                    <td style="text-align: right;"><?php echo number_format($item['price'] * $item['qty'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                
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
            <p>Returns/Exchanges allowed within 7 days.</p>
        </div>
    </div>
    
    <script>
        window.onload = function() { window.print(); };
    </script>
    <?php endif; ?>

    <script>
        lucide.createIcons();

        // Load all database variants into a JavaScript object!
        const allVariants = <?php echo $variants_json; ?>;
        let cart = [];
        let grandTotal = 0;

        // --- SEARCH FILTER ---
        function filterProducts() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let cards = document.getElementsByClassName('pos-card');
            for (let i = 0; i < cards.length; i++) {
                let productName = cards[i].getAttribute('data-name');
                cards[i].style.display = productName.includes(input) ? "flex" : "none";
            }
        }

        // --- MODAL LOGIC ---
        function openVariantModal(id, name, price) {
            document.getElementById('modalProductId').value = id;
            document.getElementById('modalProductName').innerText = name;
            document.getElementById('modalProductPrice').value = price;
            document.getElementById('modalQty').value = 1;

            let select = document.getElementById('modalVariantSelect');
            select.innerHTML = ''; // Clear old options

            let variants = allVariants[id];
            if (variants && variants.length > 0) {
                variants.forEach(v => {
                    let option = document.createElement('option');
                    option.value = v.id;
                    option.setAttribute('data-info', v.size + ' - ' + v.color);
                    option.setAttribute('data-stock', v.stock);
                    option.text = "Size " + v.size + " - " + v.color + " (" + v.stock + " left)";
                    select.appendChild(option);
                });
                document.querySelector('.add-to-cart-btn').disabled = false;
                document.querySelector('.add-to-cart-btn').innerText = "Add to Cart";
            } else {
                let option = document.createElement('option');
                option.text = "OUT OF STOCK";
                select.appendChild(option);
                document.querySelector('.add-to-cart-btn').disabled = true;
                document.querySelector('.add-to-cart-btn').innerText = "Unavailable";
            }

            document.getElementById('variantModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('variantModal').style.display = 'none';
        }

        // --- CART LOGIC ---
        function addToCart() {
            let select = document.getElementById('modalVariantSelect');
            let selectedOption = select.options[select.selectedIndex];
            
            let variantId = select.value;
            let variantInfo = selectedOption.getAttribute('data-info');
            let maxStock = parseInt(selectedOption.getAttribute('data-stock'));
            
            let name = document.getElementById('modalProductName').innerText;
            let price = parseFloat(document.getElementById('modalProductPrice').value);
            let qty = parseInt(document.getElementById('modalQty').value);

            if (qty > maxStock) {
                alert("Cannot add more than available stock (" + maxStock + ").");
                return;
            }

            // Check if item already exists in cart, just update qty
            let existingItem = cart.find(item => item.variant_id === variantId);
            if (existingItem) {
                if(existingItem.qty + qty > maxStock) {
                    alert("Adding this exceeds available stock!");
                    return;
                }
                existingItem.qty += qty;
            } else {
                cart.push({
                    variant_id: variantId,
                    name: name,
                    variant_info: variantInfo,
                    price: price,
                    qty: qty
                });
            }

            closeModal();
            updateCartUI();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartUI();
        }

        function updateCartUI() {
            let cartList = document.getElementById('cartItemsList');
            let cashInput = document.getElementById('cashInput');
            cartList.innerHTML = '';
            grandTotal = 0;

            if (cart.length === 0) {
                cartList.innerHTML = '<div style="text-align: center; color: #aaa; margin-top: 50px; font-size: 13px; font-weight: 600;">Cart is empty.<br>Click a product to add it.</div>';
                document.getElementById('cartTotalDisplay').innerText = "₱0.00";
                cashInput.disabled = true;
                cashInput.value = '';
                calculateChange();
                return;
            }

            cashInput.disabled = false;

            cart.forEach((item, index) => {
                let itemTotal = item.price * item.qty;
                grandTotal += itemTotal;

                cartList.innerHTML += `
                    <div class="cart-item">
                        <div class="cart-item-details">
                            <div class="ci-name">${item.name} <span style="color:#cc0000;">(x${item.qty})</span></div>
                            <div class="ci-variant">${item.variant_info}</div>
                        </div>
                        <div style="text-align: right;">
                            <div class="ci-price">₱${itemTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                            <button class="ci-remove" onclick="removeFromCart(${index})"><i data-lucide="trash-2" style="width: 14px;"></i></button>
                        </div>
                    </div>
                `;
            });

            document.getElementById('cartTotalDisplay').innerText = "₱" + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
            lucide.createIcons();
            calculateChange();
        }

        // --- MATH & VALIDATION ---
        function calculateChange() {
            let cash = parseFloat(document.getElementById('cashInput').value);
            let btn = document.getElementById('btnCheckout');
            let changeDisplay = document.getElementById('changeDisplay');

            if (isNaN(cash) || cart.length === 0) {
                changeDisplay.innerText = "₱0.00";
                changeDisplay.style.color = "#111";
                btn.disabled = true;
                return;
            }

            if (cash >= grandTotal) {
                let change = cash - grandTotal;
                changeDisplay.innerText = "₱" + change.toLocaleString('en-US', {minimumFractionDigits: 2});
                changeDisplay.style.color = "#22c55e"; // Green
                btn.disabled = false;
            } else {
                changeDisplay.innerText = "Insufficient";
                changeDisplay.style.color = "#ff4d4d"; // Red
                btn.disabled = true;
            }
        }

        // --- THE PROFESSOR'S ILLOGICAL TENDER CHECK ---
        function validateAndSubmit() {
            let cash = parseFloat(document.getElementById('cashInput').value);
            let change = cash - grandTotal;

            if (change > 0) {
                let illogicalBill = false;
                
                // Common Philippine Peso denominations
                let unnecessaryBills = [1000, 500, 200, 100, 50, 20];
                
                // Loop through standard bills to see if the cashier typed an exact extra bill
                for (let bill of unnecessaryBills) {
                    if (change === bill) {
                        // Check if the total could have been paid with a combination of LARGER bills perfectly
                        let largerBills = unnecessaryBills.filter(b => b > bill);
                        for (let larger of largerBills) {
                            if (grandTotal % larger === 0) {
                                illogicalBill = bill;
                                break;
                            }
                        }
                    }
                    if (illogicalBill) break;
                }

                // Catch cases where multiple massive bills were handed unnecessarily (e.g. 4000 cash for a 3000 total)
                if (!illogicalBill) {
                    if (change >= 1000 && grandTotal % 1000 === 0 && change % 1000 === 0) illogicalBill = 1000;
                    else if (change >= 500 && grandTotal % 500 === 0 && change % 500 === 0) illogicalBill = 500;
                    else if (change >= 100 && grandTotal % 100 === 0 && change % 100 === 0) illogicalBill = 100;
                }

                if (illogicalBill) {
                    let formattedTotal = grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
                    let formattedCash = cash.toLocaleString('en-US', {minimumFractionDigits: 2});
                    let properCash = (cash - change).toLocaleString('en-US', {minimumFractionDigits: 2});
                    
                    alert("⚠️ ILLOGICAL TENDER DETECTED!\n\nThe total is ₱" + formattedTotal + ".\nThe tendered amount typed is ₱" + formattedCash + ".\n\nThis implies the customer handed an unnecessary extra ₱" + illogicalBill + " bill, since ₱" + properCash + " perfectly covers the total.\n\nPlease verify the exact physical cash you received!");
                    return; // Stop the checkout process entirely!
                }
            }

            // If it passes validation, submit!
            document.getElementById('cartDataInput').value = JSON.stringify(cart);
            document.getElementById('cashTenderedInput').value = cash;
            document.getElementById('checkoutForm').submit();
        }
    </script>
</body>
</html>