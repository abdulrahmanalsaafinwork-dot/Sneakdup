<?php 
session_start();
include 'db_conn.php';

// 1. Check ID
if (!isset($_GET['id'])) {
    header("Location: shop.php");
    exit();
}

$product_id = intval($_GET['id']);

// 2. Role Logic
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : 'customer';
$is_staff = ($user_role === 'admin' || $user_role === 'employee');

// 3. Fetch Product Details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: shop.php");
    exit();
}

// 4. Fetch Variants (Sizes & Colors)
$v_stmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY size ASC");
$v_stmt->bind_param("i", $product_id);
$v_stmt->execute();
$variants_result = $v_stmt->get_result();

// 5. Build Dynamic Data for JavaScript
$color_data = [];
while ($v = $variants_result->fetch_assoc()) {
    $color = $v['color'];
    
    if (!isset($color_data[$color])) {
        $color_data[$color] = [
            'image' => !empty($v['image']) ? $v['image'] : $product['image'],
            'sizes' => []
        ];
    }
    
    $color_data[$color]['sizes'][] = [
        'size' => $v['size'],
        'stock' => $v['stock']
    ];
}

// 6. 🔥 UPGRADED: SMART RECOMMENDATION ENGINE 🔥
// Prioritizes matching categories first, shuffles them, and uses other products as a fallback.
$more_stmt = $conn->prepare("SELECT p.*, SUM(v.stock) as total_inventory 
                             FROM products p 
                             LEFT JOIN product_variants v ON p.id = v.product_id 
                             WHERE p.id != ? 
                             GROUP BY p.id 
                             ORDER BY (CASE WHEN p.category = ? THEN 1 ELSE 2 END), RAND() 
                             LIMIT 4");
$more_stmt->bind_param("is", $product_id, $product['category']);
$more_stmt->execute();
$more_products = $more_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($product['name']); ?> - SneakDup</title>
    <link rel="icon" type="image/jpeg" href="images/Sneakdup_logo.jpg">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <style>
        /* 1. BASE STYLES */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #fff; color: #1a1a1a; overflow-x: hidden;}

        /* 2. HEADER & NAVBAR */
        .top-bar { 
            background: #111; color: white; padding: 8px 0; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;
            overflow: hidden; white-space: nowrap; position: relative;
        }
        .top-bar a { color: white; text-decoration: none; transition: 0.2s; }
        .top-bar a:hover { color: #cc0000; } 
        
        .marquee-content {
            display: inline-block;
            padding-left: 100%; 
            animation: marquee 20s linear infinite; 
        }
        .marquee-content:hover { animation-play-state: paused; }
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }
        
        .navbar {
            background: white; padding: 20px 40px; display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid #e5e5e5; position: sticky; top: 0; z-index: 1000;
        }
        
        .brand { font-family: 'Oswald', sans-serif; font-size: 28px; text-transform: uppercase; color: #111; text-decoration: none; letter-spacing: -1px; display: flex; align-items: center; gap: 10px; }
        .brand span { color: #cc0000; }
        .brand img { height: 45px; width: auto; border-radius: 6px; }

        .nav-right { display: flex; align-items: center; gap: 20px; font-weight: 600; font-size: 14px; }
        .nav-link { text-decoration: none; color: #111; display: flex; align-items: center; gap: 6px; transition: 0.2s; }
        .nav-link:hover { color: #cc0000; }
        .nav-link .lucide { width: 18px; height: 18px; }
        
        .dashboard-btn { background: #cc0000; color: white; padding: 8px 15px; border-radius: 4px; border: 1px solid transparent; }
        .dashboard-btn:hover { background: #aa0000; color: white; }

        /* 3. MAIN PRODUCT LAYOUT */
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: flex; gap: 60px; }
        
        /* LEFT: IMAGE GALLERY */
        .product-gallery { flex: 1.5; display: flex; gap: 20px; }
        .thumbnails { display: flex; flex-direction: column; gap: 15px; }
        .thumb { 
            width: 80px; height: 80px; border: 1px solid #eee; 
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            border-radius: 8px; transition: 0.2s;
        }
        .thumb:hover, .thumb.active { border-color: #111; }
        .thumb img { width: 100%; height: 100%; object-fit: contain; }

        .main-image { 
            flex-grow: 1; background: #f9f9f9; border-radius: 12px; 
            height: 600px; display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        .main-image img { width: 90%; height: auto; object-fit: contain; mix-blend-mode: multiply; transition: opacity 0.3s ease; }

        .promo-tag {
            position: absolute; top: 20px; left: 20px; background: #cc0000; color: white; 
            padding: 6px 12px; font-weight: 800; text-transform: uppercase; font-size: 12px;
            display: flex; align-items: center; gap: 6px; border-radius: 4px; z-index: 10;
        }

        /* RIGHT: DETAILS */
        .product-info { flex: 1; padding-top: 20px; }
        
        .brand-label { color: #cc0000; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-size: 14px; }
        .product-title { font-family: 'Oswald', sans-serif; font-size: 42px; line-height: 1.1; margin-bottom: 15px; text-transform: uppercase; }
        .product-price { font-size: 24px; font-weight: 700; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; }
        
        .sku-badge { font-size: 12px; background: #eee; padding: 4px 8px; border-radius: 4px; color: #555; display: flex; align-items: center; gap: 4px; }

        /* COLORS */
        .color-section { margin-bottom: 25px; }
        .color-header { display: flex; gap: 8px; margin-bottom: 10px; font-size: 14px; font-weight: 700; }
        .color-name-display { color: #666; font-weight: 500; }
        
        .color-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .color-btn {
            border: 2px solid #ddd; border-radius: 6px; padding: 10px 18px;
            background: white; color: #333; font-weight: 600; font-size: 13px;
            cursor: pointer; transition: 0.2s; text-transform: capitalize;
        }
        .color-btn:hover { border-color: #111; }
        .color-btn.active { border-color: #111; background: #111; color: white; }

        /* SIZES */
        .size-section { margin-bottom: 30px; }
        .size-header { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px; font-weight: 700; }
        .size-header a { display: flex; align-items: center; gap: 4px; color: #111; text-decoration: underline; transition: 0.2s; cursor: pointer; }
        .size-header a:hover { color: #cc0000; }

        .size-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        
        .size-box {
            border: 1px solid #ddd; border-radius: 4px; padding: 12px; text-align: center;
            cursor: pointer; transition: 0.2s; position: relative; min-height: 60px;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
        }
        .size-box:hover { border-color: #111; }
        .size-box.out-of-stock { background: #f5f5f5; color: #aaa; border-color: #eee; cursor: not-allowed; text-decoration: line-through; }
        
        /* LOW STOCK & STAFF BADGES */
        .low-stock-alert { font-size: 10px; color: #e63946; font-weight: 800; text-transform: uppercase; margin-top: 4px; }
        .staff-stock-count { display: flex; align-items: center; justify-content: center; gap: 4px; font-size: 10px; color: #111; font-weight: 700; margin-top: 2px; border-top: 1px solid #eee; width: 100%; padding-top: 4px; }

        /* SIZE GUIDE MODAL STYLES */
        .sg-modal {
            display: none; 
            position: fixed;
            z-index: 2000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }

        .sg-modal-content {
            background-color: #fefefe;
            padding: 40px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
        }

        .sg-close {
            position: absolute;
            right: 20px; top: 10px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }
        .sg-close:hover { color: #cc0000; }

        /* ACTION BUTTONS */
        .action-area { margin-top: 40px; }
        .buy-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; 
            background: #111; color: white; padding: 18px; font-size: 16px; font-weight: 700; 
            text-transform: uppercase; border: none; cursor: pointer; border-radius: 4px; text-decoration: none; transition: 0.2s;
        }
        .buy-btn:hover { background: #cc0000; }
        
        .store-locator { margin-top: 15px; text-align: center; font-size: 13px; color: #555; display: flex; align-items: center; justify-content: center; gap: 8px; }
        
        .admin-edit-link {
            display: inline-flex; align-items: center; gap: 6px; margin-top: 20px; 
            color: #cc0000; font-weight: 700; text-decoration: none; transition: 0.2s;
        }
        .admin-edit-link:hover { text-decoration: underline; }

        /* 4. "MORE SHOES" SECTION STYLES */
        .more-section { max-width: 1200px; margin: 80px auto 60px; padding: 0 20px; }
        .more-header { margin-bottom: 25px; }
        .more-header h2 { font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; display: flex; align-items: center; gap: 10px; color: #111; }
        .more-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
        
        .product-card {
            background: white; border: 1px solid #eee; position: relative;
            text-decoration: none; color: inherit; transition: 0.2s; cursor: pointer;
            display: flex; flex-direction: column;
        }
        .product-card:hover { border-color: #111; transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

        .pc-img { height: 200px; background: #f9f9f9; display: flex; align-items: center; justify-content: center; position: relative; }
        .pc-img img { width: 100%; height: 100%; object-fit: cover; }
        .pc-details { padding: 15px; flex-grow: 1; display: flex; flex-direction: column;}
        .pc-brand { font-size: 11px; font-weight: 700; color: #777; text-transform: uppercase; margin-bottom: 4px; }
        .pc-name { font-size: 14px; font-weight: 700; color: #111; margin-bottom: 8px; line-height: 1.3; height: 38px; overflow: hidden; }
        .pc-price { font-size: 16px; font-weight: 800; color: #111; margin-top: auto;}
        
        .pc-promo-tag { position: absolute; top: 10px; left: 10px; background: #cc0000; color: white; font-size: 10px; font-weight: 800; padding: 4px 8px; text-transform: uppercase; display: flex; align-items: center; gap: 4px; z-index: 5;}
        
        .stock-overlay {
            position: absolute; bottom: 0; left: 0; width: 100%;
            background: rgba(255, 255, 255, 0.95); padding: 5px; text-align: center;
            border-top: 1px solid #eee; color: #cc0000; font-weight: 800; font-size: 11px;
            display: flex; align-items: center; justify-content: center; gap: 4px; z-index: 5;
        }

        /* 5. SHOP FOOTER */
        .footer { background: #111; color: white; padding: 60px 40px 30px; border-top: 1px solid #222; margin-top: 40px;}
        .footer-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 40px; }
        .footer-col { flex: 1; min-width: 250px; }
        .footer-col h3 { font-family: 'Oswald', sans-serif; font-size: 20px; text-transform: uppercase; margin-bottom: 20px; letter-spacing: 0.5px; }
        .footer-col p { color: #aaa; font-size: 14px; line-height: 1.6; margin-bottom: 20px; }
        .footer-links { list-style: none; padding: 0; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: #aaa; text-decoration: none; font-size: 14px; transition: 0.2s; font-weight: 500; }
        .footer-links a:hover { color: #cc0000; padding-left: 5px; }
        
        /* 🔥 UPGRADED FOOTER SOCIAL ICONS CSS 🔥 */
        .social-icons { display: flex; gap: 15px; }
        .social-icon-img { display: inline-block; width: 40px; height: 40px; transition: all 0.3s ease; border-radius: 50%; overflow: hidden; }
        .social-icon-img img { width: 100%; height: 100%; object-fit: cover; }
        .social-icon-img:hover { transform: translateY(-4px); filter: brightness(1.15); box-shadow: 0 5px 15px rgba(0,0,0,0.5); }
        
        .footer-bottom { text-align: center; padding-top: 30px; margin-top: 40px; border-top: 1px solid #333; color: #777; font-size: 12px; }

    </style>
</head>
<body>

    <div class="top-bar">
        <div class="marquee-content">
            Elevate Your Game. Step Up Your Style with SneakDup | Fresh Drops & Daily Discounts—Visit us for 20-30% OFF | <a href="https://maps.app.goo.gl/p1y98Pz4VJUivgHr5">STORE LOCATOR</a> | <a href="contact.php">HELP</a>
        </div>
    </div>

    <div class="navbar">
        <a href="shop.php" class="brand">
            <img src="images/Sneakdup_logo.jpg" alt="SneakDup Logo">
            <div>SNEAK<span>D</span>UP</div>
        </a>
        <div class="nav-right">
            <?php if($is_staff): ?>
                <a href="<?php echo ($user_role==='admin'?'admin/dashboard.php':'employee/orders.php'); ?>" class="nav-link dashboard-btn">
                    <i data-lucide="layout-dashboard"></i> Dashboard
                </a>
            <?php endif; ?>
            <a href="shop.php" class="nav-link"><i data-lucide="arrow-left"></i> Back to Shop</a>
        </div>
    </div>

    <div class="container">
        
        <div class="product-gallery">
            <div class="thumbnails">
                <div class="thumb active"><img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" id="thumb-img" alt="Thumbnail"></div>
            </div>
            <div class="main-image">
                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" id="main-product-image" alt="Product Image">
                
                <?php if($product['is_promo_eligible']): ?>
                    <div class="promo-tag">
                        <i data-lucide="tag" style="width: 14px; height: 14px;"></i> SALE
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="product-info">
            <div class="brand-label"><?php echo htmlspecialchars($product['category']); ?></div>
            <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="product-price">
                ₱<?php echo number_format($product['price'], 2); ?>
                <?php if($is_staff): ?>
                    <span class="sku-badge"><i data-lucide="barcode" style="width:14px; height:14px;"></i> SKU: <?php echo $product['id']; ?>00X</span>
                <?php endif; ?>
            </div>

            <div class="color-section">
                <div class="color-header">
                    <span>Colorway:</span> 
                    <span class="color-name-display" id="selected-color-text">Loading...</span>
                </div>
                <div class="color-grid" id="color-btn-container"></div>
            </div>

            <div class="size-section">
                <div class="size-header">
                    <span>Select Size</span>
                    
                    <?php if (strtolower($product['category']) !== 'accessories' && strtolower($product['category']) !== 'bundle'): ?>
                        <a onclick="openSizeGuide()"><i data-lucide="ruler" style="width: 16px; height: 16px;"></i> Size Guide</a>
                    <?php endif; ?>
                </div>
                
                <div class="size-grid" id="size-grid-container"></div>
            </div>

            <p style="color:#666; line-height:1.6; font-size:14px; margin-bottom:30px;">
                <?php 
                    if (!empty($product['description'])) {
                        echo nl2br(htmlspecialchars($product['description'])); 
                    } else {
                        echo "Designed for performance and style. This exclusive drop features premium materials and the signature comfort you expect. Available now at SneakDup.";
                    }
                ?>
            </p>

            <div class="action-area">
                <a href="https://m.me/sneakdupofficial" target="_blank" class="buy-btn">
                    <i data-lucide="message-circle"></i> Inquire via Messenger
                </a>
                <div class="store-locator">
                    <i data-lucide="map-pin" style="color: #cc0000; width: 16px; height: 16px;"></i> <span>Available at <strong>SneakDup Parañaque Branch</strong></span>
                </div>
            </div>

            <?php if($is_staff && $user_role === 'admin'): ?>
                <a href="admin/manage_products.php" class="admin-edit-link">
                    <i data-lucide="pencil" style="width: 16px; height: 16px;"></i> Edit Product Details
                </a>
            <?php endif; ?>

        </div>
    </div>

    <?php if ($more_products->num_rows > 0): ?>
    <div class="more-section">
        <div class="more-header">
            <h2><i data-lucide="shopping-bag" style="color: #cc0000;"></i> You Might Also Like</h2>
        </div>
        <div class="more-grid">
            <?php while($row = $more_products->fetch_assoc()): ?>
                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="product-card">
                    <div class="pc-img">
                        <img src="uploads/<?php echo $row['image']; ?>" alt="Product">
                        <?php if($row['is_promo_eligible']): ?>
                            <div class="pc-promo-tag"><i data-lucide="tag" style="width:10px; height:10px;"></i> SALE</div>
                        <?php endif; ?>
                    </div>
                    <?php if($is_staff): ?>
                        <div class="stock-overlay"><i data-lucide="package" style="width:12px; height:12px;"></i> STOCK: <?php echo $row['total_inventory'] ? $row['total_inventory'] : 0; ?></div>
                    <?php endif; ?>
                    <div class="pc-details">
                        <div class="pc-brand"><?php echo htmlspecialchars(strtoupper($row['category'])); ?></div>
                        <div class="pc-name"><?php echo htmlspecialchars($row['name']); ?></div>
                        <div class="pc-price">₱<?php echo number_format($row['price'], 2); ?></div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="footer">
        <div class="footer-content">
            <div class="footer-col">
                <h3>SNEAK<span style="color:#cc0000;">D</span>UP</h3>
                <p>Your ultimate destination for the latest drops, exclusive kicks, and fresh apparel. We bring the heat straight to your feet.</p>
            </div>
            <div class="footer-col">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="shop.php#popular">Popular Picks</a></li>
                    <li><a href="shop.php">New Arrivals</a></li>
                    <li><a href="https://maps.app.goo.gl/p1y98Pz4VJUivgHr5">Store Locator</a></li>
                    <li><a href="contact.php">Help / Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Stay Connected</h3>
                <p>Follow us for daily updates, exclusive promos, and behind-the-scenes video drops.</p>
                
                <!-- 🔥 UPGRADED FOOTER SOCIAL ICONS HTML 🔥 -->
                <div class="social-icons">
                    <a href="https://www.facebook.com/sneakdupofficial" class="social-icon-img" title="Facebook">
                        <img src="images/fb.png" alt="Facebook">
                    </a>
                    <a href="#" class="social-icon-img" title="YouTube">
                        <img src="images/yt.jpg" alt="YouTube">
                    </a>
                    <a href="#" class="social-icon-img" title="Instagram">
                        <img src="images/insta.jpg" alt="Instagram">
                    </a>
                </div>
                
            </div>
        </div>
        <div class="footer-bottom">
            © <?php echo date('Y'); ?> SneakDup. All Rights Reserved.
        </div>
    </div>

    <div id="sizeGuideModal" class="sg-modal">
        <div class="sg-modal-content">
            <span class="sg-close" onclick="closeSizeGuide()">×</span>
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px; text-transform: uppercase;">
                SneakDup <?php echo htmlspecialchars($product['category']); ?> Size Guide
            </h2>
            
            <?php if(strtolower($product['category']) === 'clothes'): ?>
                <img src="images/size_guideshirt.webp" alt="Clothing Size Guide" style="width: 100%; height: auto; border-radius: 4px;">
            <?php else: ?>
                <img src="images/size_guide.avif" alt="Shoe Size Guide" style="width: 100%; height: auto; border-radius: 4px;">
            <?php endif; ?>

            <p style="margin-top: 15px; font-size: 13px; color: #666;">Measurements are for reference. Fit may vary by style.</p>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // 1. Grab the dynamic data built by PHP
        const variantData = <?php echo json_encode($color_data); ?>;
        const isStaff = <?php echo $is_staff ? 'true' : 'false'; ?>;
        
        // 2. DOM Elements
        const colorBtnContainer = document.getElementById('color-btn-container');
        const sizeGridContainer = document.getElementById('size-grid-container');
        const mainImage = document.getElementById('main-product-image');
        const thumbImage = document.getElementById('thumb-img');
        const colorText = document.getElementById('selected-color-text');

        // 3. Initialize the Page
        function initProductPage() {
            const colors = Object.keys(variantData);
            
            if (colors.length === 0) {
                sizeGridContainer.innerHTML = '<p style="color: #aaa;">Out of stock</p>';
                colorText.textContent = 'None available';
                return;
            }

            colors.forEach(color => {
                const btn = document.createElement('button');
                btn.className = 'color-btn';
                btn.textContent = color;
                btn.onclick = () => selectColor(color);
                colorBtnContainer.appendChild(btn);
            });

            selectColor(colors[0]);
        }

        // 4. Handle Color Selection
        function selectColor(selectedColor) {
            const buttons = document.querySelectorAll('.color-btn');
            buttons.forEach(btn => {
                if (btn.textContent === selectedColor) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            colorText.textContent = selectedColor;

            mainImage.style.opacity = 0;
            setTimeout(() => {
                const newImgSrc = 'uploads/' + variantData[selectedColor].image;
                mainImage.src = newImgSrc;
                thumbImage.src = newImgSrc; 
                mainImage.style.opacity = 1;
            }, 150);

            renderSizes(variantData[selectedColor].sizes);
        }

        // 5. Render Sizes for the Chosen Color
        function renderSizes(sizesArray) {
            sizeGridContainer.innerHTML = ''; 

            sizesArray.forEach(variant => {
                const hasStock = variant.stock > 0;
                const isLowStock = variant.stock > 0 && variant.stock < 5;
                const boxClass = hasStock ? 'size-box' : 'size-box out-of-stock';
                
                let html = `<div class="${boxClass}">
                                <span style="font-weight: 700; font-size: 14px;">${variant.size}</span>`;
                
                if (isLowStock) {
                    html += `<span class="low-stock-alert">Low Stock</span>`;
                }
                
                if (isStaff) {
                    html += `<span class="staff-stock-count"><i data-lucide="package" style="width: 10px; height: 10px;"></i> ${variant.stock} Left</span>`;
                }
                
                html += `</div>`;
                sizeGridContainer.innerHTML += html;
            });

            lucide.createIcons();
        }

        // --- MODAL FUNCTIONS ---
        function openSizeGuide() {
            document.getElementById('sizeGuideModal').style.display = 'flex';
        }

        function closeSizeGuide() {
            document.getElementById('sizeGuideModal').style.none = 'none';
            document.getElementById('sizeGuideModal').style.display = 'none';
        }

        window.onclick = function(event) {
            let modal = document.getElementById('sizeGuideModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        initProductPage();
    </script>
</body>
</html>