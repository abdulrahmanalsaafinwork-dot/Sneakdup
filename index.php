<?php
session_start();
include 'db_conn.php'; 

// Check login status for the minimal navbar
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : 'customer';
$is_staff = ($user_role === 'admin' || $user_role === 'employee');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <title>SneakDup | Premium Streetwear & Footwear</title>
    <link rel="icon" type="image/jpeg" href="images/Sneakdup_logo.jpg">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #fff; color: #111; overflow-x: hidden; }

        /* 1. TOP BAR MARQUEE */
        .top-bar { 
            background: #111; color: white; padding: 8px 0; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;
            overflow: hidden; white-space: nowrap; position: relative;
        }
        .top-bar a { color: white; text-decoration: none; transition: 0.2s; }
        .top-bar a:hover { color: #cc0000; } 
        .marquee-content { display: inline-block; padding-left: 100%; animation: marquee 20s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-100%); } }

        /* 2. 🔥 ULTRA-MINIMAL NAVBAR 🔥 */
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            padding: 15px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        .brand { font-family: 'Oswald', sans-serif; font-size: 28px; text-transform: uppercase; color: #111; text-decoration: none; letter-spacing: -1px; display: flex; align-items: center; gap: 10px; }
        .brand span { color: #cc0000; }
        .brand img { height: 45px; width: auto; border-radius: 6px; }

        .nav-links { 
            position: absolute; left: 50%; transform: translateX(-50%);
            display: flex; gap: 35px; align-items: center; 
        }
        .nav-links a { text-decoration: none; color: #111; font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; transition: color 0.2s; }
        .nav-links a:hover { color: #cc0000; }

        .nav-icons { display: flex; align-items: center; gap: 20px; }
        .nav-icon-link { color: #111; text-decoration: none; display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 14px; transition: color 0.2s; }
        .nav-icon-link:hover { color: #cc0000; }
        .nav-icon-link .lucide { width: 20px; height: 20px; }
        .staff-pill { background: #cc0000; color: white; padding: 6px 12px; border-radius: 20px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 4px; }

        .mobile-menu-btn { 
            display: none; cursor: pointer; color: #111; align-items: center; 
            justify-content: center; width: 38px; height: 38px; border-radius: 6px; 
            background: #f4f6f8; border: 1px solid #eaeaea; transition: 0.2s; 
        }
        .mobile-menu-btn:hover { background: #111; color: white; border-color: #111; }
        .mobile-menu-btn .lucide { width: 20px; height: 20px; }

        /* 3. HERO BANNER */
        .hero {
            position: relative; height: 60vh; min-height: 450px; max-height: 600px; width: 100%;
            background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.6)), url('images/bgpp.jpg') no-repeat center center;
            background-size: cover; display: flex; align-items: center; justify-content: center; text-align: center;
            overflow: hidden; /* 🔥 Keeps the paint wipe contained perfectly inside the banner */
        }

        /* 🔥 BACKGROUND BRUSH/PAINT WIPE OVERLAY 🔥 */
        .hero::before {
            content: '';
            position: absolute;
            top: -20%; left: -150%; width: 150%; height: 140%;
            /* Creates a rough, multi-layered gradient to mimic a wide brush stroke */
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(255, 255, 255, 0.02) 15%, 
                rgba(255, 255, 255, 0.08) 35%, 
                rgba(255, 255, 255, 0.15) 50%, 
                rgba(255, 255, 255, 0.08) 65%, 
                rgba(255, 255, 255, 0.02) 85%, 
                transparent 100%
            );
            transform: skewX(-25deg);
            animation: bgPaintSweep 5s cubic-bezier(0.25, 0.8, 0.25, 1) infinite;
            z-index: 1; /* 🔥 Sits OVER the background image, but UNDER the text */
            pointer-events: none; /* Allows clicks to pass through */
        }

        @keyframes bgPaintSweep {
            0% { left: -150%; opacity: 0; }
            20% { opacity: 1; }
            80% { left: 150%; opacity: 0; }
            100% { left: 150%; opacity: 0; }
        }

        .hero-content { 
            color: white; max-width: 800px; padding: 0 20px; 
            position: relative; z-index: 2; /* 🔥 Keeps the text floating cleanly ABOVE the paint wipe */
        }
        
        .hero h1 { font-family: 'Oswald', sans-serif; font-size: 72px; text-transform: uppercase; margin-bottom: 15px; line-height: 1; text-shadow: 0 4px 20px rgba(0,0,0,0.4); font-weight: 700; letter-spacing: 1px; }
        .hero p { font-size: 18px; margin-bottom: 35px; font-weight: 500; text-shadow: 0 2px 10px rgba(0,0,0,0.4); letter-spacing: 0.5px; color: #f0f0f0; }
        
        .hero-btn { 
            background: #cc0000; color: white; padding: 18px 45px; font-size: 15px; 
            font-weight: 800; text-transform: uppercase; text-decoration: none; border: none; 
            cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 10px;
            border-radius: 50px; box-shadow: 0 10px 25px rgba(204, 0, 0, 0.3);
        }
        .hero-btn:hover { background: white; color: #111; transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.2); }

        /* 4. VISUAL CATEGORY GRID */
        .category-section { padding: 80px 40px; max-width: 1300px; margin: 0 auto; }
        .section-headline { text-align: center; font-family: 'Oswald', sans-serif; font-size: 36px; text-transform: uppercase; margin-bottom: 40px; letter-spacing: 0.5px; }
        .section-headline span { color: #cc0000; }
        
        .cat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .cat-card { 
            position: relative; height: 450px; background: #f4f6f8; overflow: hidden; border-radius: 16px; 
            text-decoration: none; display: flex; align-items: flex-end; padding: 30px;
        }
        
        /* UNIQUE CATEGORY BACKGROUNDS */
        .cat-shoes { background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.1)), url('images/shoee.jpg') no-repeat center center; background-size: cover; }
        .cat-apparel { background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.1)), url('images/apparil.jpg') no-repeat center center; background-size: cover; }
        .cat-acc { background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.1)), url('images/accesse.jpg') no-repeat center center; background-size: cover; }
        
        .cat-card-title { font-family: 'Oswald', sans-serif; font-size: 28px; color: white; text-transform: uppercase; font-weight: 700; z-index: 2; display: flex; align-items: center; gap: 8px; transition: transform 0.3s ease; }
        .cat-card::before { content: ''; position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.1); transition: background 0.3s; z-index: 1; }
        .cat-card:hover::before { background: rgba(204, 0, 0, 0.25); }
        .cat-card:hover .cat-card-title { transform: translateX(8px); }

        /* 4.5 CUSTOMER SPOTLIGHT MARQUEE */
        .spotlight-section { padding: 80px 0; background: #fff; overflow: hidden; border-top: 1px solid #f0f0f0; }
        
        .marquee-wrapper { display: flex; width: 100%; overflow: hidden; position: relative; }
        .marquee-track { 
            display: flex; gap: 20px; padding: 0 10px;
            animation: scrollSpotlight 30s linear infinite; 
            width: max-content;
        }
        .marquee-wrapper:hover .marquee-track { animation-play-state: paused; } 
        
        .spotlight-item { 
            width: 280px; height: 500px; 
            flex-shrink: 0; border-radius: 12px; overflow: hidden; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.06); cursor: pointer;
        }
        .spotlight-item img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
        .spotlight-item:hover img { transform: scale(1.05); }
        
        @keyframes scrollSpotlight {
            0% { transform: translateX(0); }
            100% { transform: translateX(calc(-50% - 10px)); } 
        }

        /* 5. SHOP FOOTER */
        .footer { background: #111; color: white; padding: 60px 40px 30px; margin-top: 40px; }
        .footer-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 40px; }
        .footer-col { flex: 1; min-width: 250px; }
        .footer-col h3 { font-family: 'Oswald', sans-serif; font-size: 20px; text-transform: uppercase; margin-bottom: 20px; letter-spacing: 0.5px; }
        .footer-col p { color: #aaa; font-size: 14px; line-height: 1.6; margin-bottom: 20px; }
        .footer-links { list-style: none; padding: 0; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: #aaa; text-decoration: none; font-size: 14px; transition: 0.2s; font-weight: 500; }
        .footer-links a:hover { color: #cc0000; padding-left: 5px; }
        
        /* CUSTOM IMAGE SOCIAL ICONS */
        .social-icons { display: flex; gap: 15px; }
        .social-icon-img { display: inline-block; width: 40px; height: 40px; transition: all 0.3s ease; border-radius: 50%; overflow: hidden; }
        .social-icon-img img { width: 100%; height: 100%; object-fit: cover; }
        .social-icon-img:hover { transform: translateY(-4px); filter: brightness(1.15); box-shadow: 0 5px 15px rgba(0,0,0,0.5); }
        
        .footer-bottom { text-align: center; padding-top: 30px; margin-top: 40px; border-top: 1px solid #333; color: #777; font-size: 12px; }

        /* 🔥 PREMIUM PAGE TRANSITION OVERLAY 🔥 */
        .page-transition {
            position: fixed; top: 0; left: -100%; width: 100%; height: 100%;
            background: #111; z-index: 9999; display: flex; align-items: center; justify-content: center;
            transition: left 0.5s cubic-bezier(0.77, 0, 0.175, 1);
        }
        .page-transition.active { left: 0; }
        
        .transition-brand {
            font-family: 'Oswald', sans-serif; font-size: 54px; text-transform: uppercase; color: white;
            letter-spacing: 2px; opacity: 0; transform: translateY(20px);
            transition: all 0.4s ease 0.4s;
        }
        .transition-brand span { color: #cc0000; }
        .page-transition.active .transition-brand { opacity: 1; transform: translateY(0); }
        .hero-btn .btn-arrow { transition: transform 0.3s ease; }
        .hero-btn:hover .btn-arrow { transform: translateX(6px); }

        /* 🔥 MOBILE SLIDE-OUT MENU STYLES 🔥 */
        .mobile-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
            z-index: 2000; opacity: 0; visibility: hidden; transition: 0.3s ease;
        }
        .mobile-overlay.active { opacity: 1; visibility: visible; }
        
        .mobile-menu {
            position: fixed; top: 0; right: -300px; width: 280px; height: 100vh;
            background: #fff; z-index: 2001; box-shadow: -10px 0 30px rgba(0,0,0,0.15);
            display: flex; flex-direction: column; padding: 30px 25px;
            transition: right 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .mobile-menu.active { right: 0; }
        
        .mm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .mm-brand { font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; color: #111; margin: 0; }
        .mm-brand span { color: #cc0000; }
        .mm-close { background: #f4f6f8; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; color: #111; }
        .mm-close:hover { background: #cc0000; color: white; transform: rotate(90deg); }
        
        .mm-links { display: flex; flex-direction: column; gap: 15px; }
        .mm-link { font-family: 'Inter', sans-serif; font-size: 18px; font-weight: 800; color: #111; text-decoration: none; text-transform: uppercase; display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f9f9f9; transition: 0.2s; }
        .mm-link:hover { color: #cc0000; padding-left: 10px; border-bottom-color: #eee; }
        .mm-link i { color: #cc0000; width: 18px; height: 18px; opacity: 0; transform: translateX(-10px); transition: 0.3s; }
        .mm-link:hover i { opacity: 1; transform: translateX(0); }

        .mm-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid #eee; }
        .mm-footer p { font-size: 11px; color: #888; font-weight: 700; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px; }

        /* 🔥 RESPONSIVE MEDIA QUERIES 🔥 */
        @media (max-width: 950px) { 
            .cat-grid { grid-template-columns: 1fr; } 
            .cat-card { height: 300px; } 
            
            /* Hide Desktop Links, Show Mobile Hamburger */
            .nav-links { display: none; }
            .mobile-menu-btn { display: flex; }
        }

        /* Portrait Phone Optimization */
        @media (max-width: 768px) {
            .navbar { padding: 15px 20px; }
            .category-section, .spotlight-section, .footer { padding-left: 20px; padding-right: 20px; }
            
            .brand { font-size: 22px; }
            .brand img { height: 35px; }
            .hero h1 { font-size: 42px; }
            .hero p { font-size: 15px; }
            .hero-btn { padding: 15px 30px; font-size: 13px; }
            .section-headline { font-size: 28px; margin-bottom: 25px; }

            .footer-content { flex-direction: column; gap: 30px; }
            .footer-col { width: 100%; min-width: 100%; }
        }
    </style>
</head>
<body>

    <!-- 🔥 PAGE TRANSITION OVERLAY 🔥 -->
    <div id="pageTransition" class="page-transition">
        <div class="transition-brand">SNEAK<span>D</span>UP</div>
    </div>

    <!-- 🔥 MOBILE SLIDE-OUT MENU 🔥 -->
    <div class="mobile-overlay" id="mobileOverlay" onclick="toggleMenu()"></div>
    <div class="mobile-menu" id="mobileMenu">
        <div class="mm-header">
            <h2 class="mm-brand">SNEAK<span>D</span>UP</h2>
            <div class="mm-close" onclick="toggleMenu()"><i data-lucide="x"></i></div>
        </div>
        <div class="mm-links">
            <a href="#" class="mm-link" onclick="triggerTransition(event, 'shop.php'); toggleMenu();">Shop <i data-lucide="chevron-right"></i></a>
            <a href="#" class="mm-link" onclick="triggerTransition(event, 'shop.php?search=sale'); toggleMenu();">Promos <i data-lucide="chevron-right"></i></a>
            <a href="#" class="mm-link" onclick="triggerTransition(event, 'contact.php'); toggleMenu();">Contact <i data-lucide="chevron-right"></i></a>
        </div>
        <div class="mm-footer">
            <p>Follow Us</p>
            <div class="social-icons">
                <a href="https://www.facebook.com/sneakdupofficial" class="social-icon-img"><img src="images/icon_fb.png" alt="Facebook"></a>
                <a href="#" class="social-icon-img"><img src="images/icon_yt.png" alt="YouTube"></a>
                <a href="#" class="social-icon-img"><img src="images/icon_ig.png" alt="Instagram"></a>
            </div>
        </div>
    </div>

    <!-- 1. TOP ANNOUNCEMENT BAR -->
    <div class="top-bar">
        <div class="marquee-content">
            Elevate Your Game. Step Up Your Style with SneakDup | Fresh Drops & Daily Discounts—Visit us for 20-30% OFF | <a href="https://maps.app.goo.gl/p1y98Pz4VJUivgHr5">STORE LOCATOR</a> | <a href="contact.php">HELP</a>
        </div>
    </div>

    <!-- 2. NEW ULTRA-MINIMAL NAVBAR -->
    <div class="navbar">
        <a href="index.php" class="brand">
            <img src="images/Sneakdup_logo.jpg" alt="SneakDup Logo">
            <div class="brand-text">SNEAK<span>D</span>UP</div>
        </a>

        <!-- PERFECTLY CENTERED Menu -->
        <div class="nav-links">
            <a href="#" onclick="triggerTransition(event, 'shop.php')">Shop</a>
            <a href="#" onclick="triggerTransition(event, 'shop.php?search=sale')">Promos</a>
            <a href="#" onclick="triggerTransition(event, 'contact.php')">Contact</a>
        </div>

        <!-- Sleek Right Icons Area -->
        <div class="nav-icons">
            <a href="#" onclick="triggerTransition(event, 'shop.php')" class="nav-icon-link" title="Search Catalog"><i data-lucide="search"></i></a>
            
            <?php if($is_logged_in): ?>
                <?php if($is_staff): ?>
                    <a href="<?php echo ($user_role === 'admin' ? 'admin/dashboard.php' : 'employee/orders.php'); ?>" class="nav-icon-link">
                        <span class="staff-pill"><i data-lucide="layout-dashboard" style="width:14px; height:14px;"></i> Panel</span>
                    </a>
                <?php endif; ?>
                <div class="nav-icon-link"><i data-lucide="user"></i> <?php echo htmlspecialchars(explode(' ', $_SESSION['fullname'])[0]); ?></div>
                <a href="logout.php" class="nav-icon-link" style="color:#cc0000;"><i data-lucide="log-out"></i></a>
            <?php else: ?>
                <a href="https://m.me/sneakdupofficial" target="_blank" class="nav-icon-link" title="Chat on Messenger"><i data-lucide="message-circle"></i></a>
            <?php endif; ?>

            <div class="mobile-menu-btn" onclick="toggleMenu()">
                <i data-lucide="menu"></i>
            </div>
        </div>
    </div>

    <!-- 3. HERO BANNER -->
    <div class="hero">
        <div class="hero-content">
            <h1>KICK UP THE HEAT</h1>
            <p>Check out the latest drops from Nike, Adidas, and New Balance.</p>
            <a href="#" onclick="triggerTransition(event, 'shop.php')" class="hero-btn">
                ENTER CATALOG <i data-lucide="arrow-right" class="btn-arrow" style="width: 20px; height: 20px;"></i>
            </a>
        </div>
    </div>

    <!-- 4. VISUAL NAVIGATION BLOCKS -->
    <div class="category-section">
        <h2 class="section-headline">Select Your <span>Collection</span></h2>
        
        <div class="cat-grid">
            <a href="#" onclick="triggerTransition(event, 'shop.php')" class="cat-card cat-shoes">
                <div class="cat-card-title">Shoes <i data-lucide="arrow-up-right"></i></div>
            </a>
            
            <a href="#" onclick="triggerTransition(event, 'shop.php')" class="cat-card cat-apparel">
                <div class="cat-card-title">Apparel <i data-lucide="arrow-up-right"></i></div>
            </a>
            
            <a href="#" onclick="triggerTransition(event, 'shop.php')" class="cat-card cat-acc">
                <div class="cat-card-title">Accessories <i data-lucide="arrow-up-right"></i></div>
            </a>
        </div>
    </div>

    <!-- 4.5. CUSTOMER SPOTLIGHT (AUTO-SCROLL) -->
    <div class="spotlight-section">
        <h2 class="section-headline">SneakDup <span>Community</span></h2>
        
        <div class="marquee-wrapper">
            <div class="marquee-track">
                <!-- Set 1 -->
                <div class="spotlight-item"><img src="images/cs1.jpg" alt="Customer Check"></div>
                <div class="spotlight-item"><img src="images/cs2.jpg" alt="Customer Check"></div>
                <div class="spotlight-item"><img src="images/cs3.jpg" alt="Customer Check"></div>
                <div class="spotlight-item"><img src="images/cs4.jpg" alt="Customer Check"></div>
                <div class="spotlight-item"><img src="images/cs5.jpg" alt="Customer Check"></div>
                <div class="spotlight-item"><img src="images/cs6.jpg" alt="Customer Check"></div>
                
                <!-- Set 2 -->
                <div class="spotlight-item"><img src="images/cs1.jpg" alt="Customer Check"></div>
                <div class="spotlight-item"><img src="images/cs2.jpg" alt="Customer Check"></div>
                <div class="spotlight-item"><img src="images/cs3.jpg" alt="Customer Check"></div>
                <div class="spotlight-item"><img src="images/cs4.jpg" alt="Customer Check"></div>
                <div class="spotlight-item"><img src="images/cs5.jpg" alt="Customer Check"></div>
                <div class="spotlight-item"><img src="images/cs6.jpg" alt="Customer Check"></div>
            </div>
        </div>
    </div>

    <!-- 5. SHARED PREMIUM FOOTER -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-col">
                <h3>SNEAK<span style="color:#cc0000;">D</span>UP</h3>
                <p>Your ultimate destination for the latest drops, exclusive kicks, and fresh apparel. We bring the heat straight to your feet.</p>
            </div>
            <div class="footer-col">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#" onclick="triggerTransition(event, 'shop.php')">Browse All Products</a></li>
                    <li><a href="https://maps.app.goo.gl/p1y98Pz4VJUivgHr5">Store Locator</a></li>
                    <li><a href="#" onclick="triggerTransition(event, 'contact.php')">Help / Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Stay Connected</h3>
                <p>Follow us for daily updates, exclusive promos, and behind-the-scenes video drops.</p>
                
                <!-- CUSTOM IMAGE SOCIAL ICONS -->
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

    <script>
        lucide.createIcons();

        // 🔥 MOBILE MENU TOGGLE LOGIC 🔥
        function toggleMenu() {
            document.getElementById('mobileMenu').classList.toggle('active');
            document.getElementById('mobileOverlay').classList.toggle('active');
        }

        // 🔥 PAGE TRANSITION LOGIC 🔥
        function triggerTransition(event, targetUrl) {
            event.preventDefault(); 
            const overlay = document.getElementById('pageTransition');
            
            overlay.classList.add('active');
            
            setTimeout(() => {
                window.location.href = targetUrl;
            }, 900); 
        }

        // Fix for the browser "Back" button
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.getElementById('pageTransition').classList.remove('active');
            }
        });
    </script>
</body>
</html>