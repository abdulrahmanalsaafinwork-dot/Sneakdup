<?php
session_start();
include 'db_conn.php'; 

// 1. View Mode Logic
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : 'customer';
$is_staff = ($user_role === 'admin' || $user_role === 'employee');

// 2. Search & Sort Logic
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort_query = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'newest';
$auto_open_modal = '';

// 🔥 SMART INTERCEPT: If they click "Promos" from the index page
if (strtolower($search_query) === 'sale') {
    $search_query = ''; // Clear the text search so the background catalog stays full
    $auto_open_modal = 'Bundle'; // Tell Javascript to pop the modal open
}

// 3. Helper function to fetch products (UPGRADED FOR DUAL IMAGES)
function getProducts($conn, $category, $search_query, $sort_query, $limit = 10, $is_bundle = false) {
    // 🔥 PULLS MAX(v.image) TO GET THE LIFESTYLE PHOTO FROM VARIANTS TABLE
    $sql = "SELECT p.*, SUM(v.stock) as total_inventory, MAX(v.image) as variant_image 
            FROM products p 
            LEFT JOIN product_variants v ON p.id = v.product_id 
            WHERE 1=1";
    
    if ($category === 'Popular') {
        $sql .= " AND p.is_popular = 1";
    } elseif ($category !== 'All') {
        $sql .= " AND p.category = '$category'";
    }
    
    if ($search_query) {
        $sql .= " AND p.name LIKE '%$search_query%'";
    }
    
    if ($is_bundle) {
        $sql .= " AND p.is_promo_eligible = 1";
    }

    $sql .= " GROUP BY p.id";

    if ($sort_query === 'price_low') {
        $sql .= " ORDER BY p.price ASC";
    } elseif ($sort_query === 'price_high') {
        $sql .= " ORDER BY p.price DESC";
    } else {
        $sql .= " ORDER BY p.id DESC"; // Default: Newest
    }

    $sql .= " LIMIT $limit";
    return $conn->query($sql);
}

// Fetch Data for Home Sections (Limited to 8 per section)
$popular_picks = getProducts($conn, 'Popular', $search_query, $sort_query, 8); 
$shoes = getProducts($conn, 'Shoes', $search_query, $sort_query, 8);
$clothes = getProducts($conn, 'Clothes', $search_query, $sort_query, 8);
$accessories = getProducts($conn, 'Accessories', $search_query, $sort_query, 8);
$bundles = getProducts($conn, 'All', $search_query, $sort_query, 8, true); 

// --- Fetch ALL Products for the Pop-up Catalog ---
$all_for_js = getProducts($conn, 'All', '', 'newest', 1000); 
$all_products_array = [];
while($row = $all_for_js->fetch_assoc()) {
    $all_products_array[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <title>Shop - SneakDup</title>
    <link rel="icon" type="image/jpeg" href="images/Sneakdup_logo.jpg">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    
    <style>
        /* 1. RESET & BASE */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #fcfcfc; color: #1a1a1a; overflow-x: hidden; }
        
        /* 2. HEADER & NAVBAR */
        .top-bar { 
            background: #111; color: white; padding: 8px 0; font-size: 12px; font-weight: 600; letter-spacing: 0.5px;
            overflow: hidden; white-space: nowrap; position: relative;
        }
        .top-bar a { color: white; text-decoration: none; transition: 0.2s; }
        .top-bar a:hover { color: #cc0000; } 
        
        .marquee-content { display: inline-block; padding-left: 100%; animation: marquee 20s linear infinite; }
        .marquee-content:hover { animation-play-state: paused; }
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-100%); } }
        
        .navbar {
            background: rgba(255, 255, 255, 0.98); 
            backdrop-filter: blur(10px); padding: 15px 40px; 
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 1000; gap: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); border-bottom: none;
        }

        .brand { font-family: 'Oswald', sans-serif; font-size: 28px; text-transform: uppercase; color: #111; text-decoration: none; letter-spacing: -1px; white-space: nowrap; display: flex; align-items: center; gap: 10px; }
        .brand span { color: #cc0000; }
        .brand img { height: 45px; width: auto; border-radius: 6px; }

        .nav-links { 
            position: absolute; left: 50%; transform: translateX(-50%);
            display: flex; gap: 35px; align-items: center; 
        }
        .nav-links a { text-decoration: none; color: #111; font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; transition: color 0.2s; }
        .nav-links a:hover { color: #cc0000; }
        .nav-links a.active { color: #cc0000; border-bottom: 2px solid #cc0000; padding-bottom: 2px; }

        /* 🔥 UNIFIED NAVBAR ICONS CSS 🔥 */
        .nav-icons { display: flex; align-items: center; gap: 20px; font-size: 14px; font-weight: 700; }
        
        .nav-icon-btn { 
            color: #111 !important; text-decoration: none; display: flex; 
            align-items: center; gap: 6px; transition: 0.2s; cursor: pointer; 
        }
        .nav-icon-btn:visited { color: #111 !important; }
        .nav-icon-btn:hover { color: #cc0000 !important; }
        .nav-icon-btn .lucide { width: 18px; height: 18px; }

        .search-container { flex-grow: 1; max-width: 250px; position: relative; margin-right: 10px;}
        .search-form { display: flex; align-items: center; }
        .search-input-hidden {
            width: 0; padding: 0; border: none; background: transparent; outline: none;
            font-size: 13px; font-weight: 600; transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            opacity: 0; pointer-events: none; margin-left: 0;
        }
        .search-input-hidden.active {
            width: 200px; padding: 8px 15px; border: 1px solid #ddd; border-radius: 50px;
            background: #f9f9f9; opacity: 1; pointer-events: auto; margin-left: 10px;
        }
        .search-input-hidden.active:focus { background: white; border-color: #111; }

        .staff-pill { background: #cc0000; color: white; padding: 6px 12px; border-radius: 20px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 4px; }

        .mobile-menu-btn { 
            display: none; cursor: pointer; color: #111; align-items: center; 
            justify-content: center; width: 38px; height: 38px; border-radius: 6px; 
            background: #f4f6f8; border: 1px solid #eaeaea; transition: 0.2s; 
        }
        .mobile-menu-btn:hover { background: #111; color: white; border-color: #111; }
        .mobile-menu-btn .lucide { width: 20px; height: 20px; }

        /* 3. CATALOG HEADER & FILTERS */
        .catalog-header { padding: 50px 40px 0; max-width: 1300px; margin: 0 auto; border-bottom: 1px solid #eaeaea; margin-bottom: 30px; }
        .header-top { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
        .catalog-header h1 { font-family: 'Oswald', sans-serif; font-size: 42px; text-transform: uppercase; color: #111; letter-spacing: 0.5px; line-height: 1;}
        .catalog-meta { font-size: 14px; color: #777; font-weight: 600; margin-top: 8px;}
        
        .filter-bar { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .brand-pills { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; scrollbar-width: none; }
        .brand-pills::-webkit-scrollbar { display: none; }
        
        .brand-pill { text-decoration: none; color: #555; background: #fff; border: 1px solid #ddd; padding: 8px 20px; border-radius: 50px; font-size: 13px; font-weight: 700; text-transform: uppercase; transition: all 0.2s ease; white-space: nowrap; }
        .brand-pill:hover, .brand-pill.active { background: #111; color: white; border-color: #111; }
        
        .sort-dropdown { padding: 10px 15px; border-radius: 8px; border: 1px solid #ddd; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; color: #111; background: #f9f9f9; outline: none; cursor: pointer; }
        .sort-dropdown:focus { border-color: #111; background: white; }

        /* 4. PRODUCT SECTIONS & SLIDERS */
        .section { padding: 30px 40px 50px; max-width: 1300px; margin: 0 auto; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .section-title { display: flex; align-items: center; gap: 8px; font-size: 24px; font-weight: 800; text-transform: uppercase; font-family: 'Oswald', sans-serif; letter-spacing: 0.5px; }
        .section-title .lucide { color: #cc0000; } 
        
        .view-all { color: #111; text-decoration: none; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 4px; cursor: pointer; background: #f0f0f0; padding: 8px 16px; border-radius: 50px; transition: all 0.2s ease; }
        .view-all:hover { background: #111; color: white; }

        .slider-container { display: flex; overflow-x: auto; gap: 20px; padding-bottom: 20px; scroll-behavior: smooth; }
        .slider-container::-webkit-scrollbar { height: 6px; }
        .slider-container::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
        
        .product-card { min-width: 240px; width: 240px; flex-shrink: 0; background: white; border: 1px solid #eaeaea; border-radius: 12px; overflow: hidden; position: relative; text-decoration: none; color: inherit; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); cursor: pointer; display: flex; flex-direction: column; }
        .product-card:hover { border-color: transparent; transform: translateY(-5px); box-shadow: 0 12px 24px rgba(0,0,0,0.08); }

        .pc-img { position: relative; overflow: hidden; height: 200px; background: #f4f6f8; display: flex; align-items: center; justify-content: center; }
        .pc-img img { width: 90%; height: 90%; object-fit: contain; mix-blend-mode: multiply; transition: opacity 0.4s cubic-bezier(0.25, 0.8, 0.25, 1), transform 0.4s ease; }
        
        .img-clean { position: relative; z-index: 1; }
        .img-lifestyle { position: absolute; top: 5%; left: 5%; z-index: 2; opacity: 0; }
        
        .product-card:hover .img-lifestyle { opacity: 1; transform: scale(1.05); }
        .product-card:hover .img-clean { opacity: 0; }

        .pc-details { padding: 18px 15px; flex-grow: 1; display: flex; flex-direction: column; }
        .pc-brand { font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; }
        .pc-name { font-size: 15px; font-weight: 800; color: #111; margin-bottom: 8px; line-height: 1.3; height: 40px; overflow: hidden; }
        .pc-price { font-size: 17px; font-weight: 800; color: #111; margin-top: auto; }
        .promo-tag { position: absolute; top: 10px; left: 10px; background: #cc0000; color: white; font-size: 10px; font-weight: 800; padding: 4px 8px; text-transform: uppercase; display: flex; align-items: center; gap: 4px; z-index: 3;}
        .stock-overlay { position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(255, 255, 255, 0.95); padding: 5px; text-align: center; border-top: 1px solid #eee; color: #cc0000; font-weight: 800; font-size: 11px; display: flex; align-items: center; justify-content: center; gap: 4px; z-index: 3;}

        /* 5. CATEGORY GRID MODAL */
        .category-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 3000; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .category-modal-content { background: #f4f6f8; width: 95%; max-width: 1200px; height: 90vh; display: flex; flex-direction: column; border-radius: 12px; overflow: hidden; position: relative; box-shadow: 0 15px 50px rgba(0,0,0,0.3); }
        .category-modal-header { background: white; padding: 25px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; z-index: 10; }
        .category-modal-header h2 { font-family: 'Oswald', sans-serif; font-size: 28px; text-transform: uppercase; margin: 0; color: #111; }
        .category-modal-close { cursor: pointer; color: #111; display: flex; align-items: center; justify-content: center; background: #eee; border-radius: 50%; width: 40px; height: 40px; transition: 0.2s; }
        .category-modal-close:hover { background: #cc0000; color: white; transform: rotate(90deg); }
        .category-modal-body { padding: 40px; overflow-y: auto; flex: 1; }
        .category-modal-body .pos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 25px; }
        .category-modal-body .product-card { width: auto; min-width: auto; }

        /* 6. SYNCED CUSTOM IMAGE FOOTER */
        .footer { background: #111; color: white; padding: 60px 40px 30px; margin-top: 60px; }
        .footer-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 40px; }
        .footer-col { flex: 1; min-width: 250px; }
        .footer-col h3 { font-family: 'Oswald', sans-serif; font-size: 20px; text-transform: uppercase; margin-bottom: 20px; letter-spacing: 0.5px; }
        .footer-col p { color: #aaa; font-size: 14px; line-height: 1.6; margin-bottom: 20px; }
        .footer-links { list-style: none; padding: 0; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: #aaa; text-decoration: none; font-size: 14px; transition: 0.2s; font-weight: 500; }
        .footer-links a:hover { color: #cc0000; padding-left: 5px; }
        .social-icons { display: flex; gap: 15px; }
        .social-icon-img { display: inline-block; width: 40px; height: 40px; transition: all 0.3s ease; border-radius: 50%; overflow: hidden; }
        .social-icon-img img { width: 100%; height: 100%; object-fit: cover; }
        .social-icon-img:hover { transform: translateY(-4px); filter: brightness(1.15); box-shadow: 0 5px 15px rgba(0,0,0,0.5); }
        .footer-bottom { text-align: center; padding-top: 30px; margin-top: 40px; border-top: 1px solid #333; color: #777; font-size: 12px; }

        /* 7. AI CHATBOT WIDGET */
        .chat-btn { position: fixed; bottom: 30px; right: 30px; background: #cc0000; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(204, 0, 0, 0.4); cursor: pointer; transition: 0.3s; z-index: 9995; }
        .chat-btn:hover { transform: scale(1.1); background: #aa0000; }
        .chat-window { position: fixed; bottom: 100px; right: 30px; width: 350px; height: 500px; background: white; border-radius: 12px; box-shadow: 0 15px 40px rgba(0,0,0,0.15); display: none; flex-direction: column; z-index: 9996; overflow: hidden; border: 1px solid #eee; animation: slideUpChat 0.3s ease; }
        @keyframes slideUpChat { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .chat-header { background: #111; color: white; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
        .chat-header-title { display: flex; align-items: center; gap: 10px; font-family: 'Oswald', sans-serif; font-size: 16px; text-transform: uppercase;}
        .chat-header-title i { color: #cc0000; }
        .chat-close { cursor: pointer; color: #aaa; transition: 0.2s; display: flex;}
        .chat-close:hover { color: white; }
        .chat-body { flex: 1; padding: 20px; overflow-y: auto; background: #fafafa; display: flex; flex-direction: column; gap: 15px; }
        .chat-msg { max-width: 80%; padding: 12px 16px; border-radius: 8px; font-size: 13px; line-height: 1.5; font-weight: 500;}
        .msg-ai { background: white; color: #111; border: 1px solid #eee; align-self: flex-start; border-bottom-left-radius: 2px;}
        .msg-user { background: #111; color: white; align-self: flex-end; border-bottom-right-radius: 2px;}
        .chat-footer { padding: 15px; background: white; border-top: 1px solid #eee; display: flex; gap: 10px; align-items: center; }
        .chat-input { flex: 1; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; outline: none; }
        .chat-input:focus { border-color: #111; }
        .chat-send { background: #cc0000; color: white; border: none; width: 40px; height: 40px; border-radius: 6px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; padding: 0; flex-shrink: 0; }
        .chat-send:hover { background: #111; }
        .chat-suggestions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .suggestion-btn { background: #fff; border: 1px solid #ccc; border-radius: 20px; padding: 6px 12px; font-size: 11px; font-family: 'Inter', sans-serif; color: #111; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .suggestion-btn:hover { background: #cc0000; color: white; border-color: #cc0000; }

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

        /* 🔥 ENTRY INTENT PROMO POP-UP CSS 🔥 */
        .promo-popup-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); backdrop-filter: blur(5px);
            z-index: 4000; display: none; align-items: center; justify-content: center;
        }
        .promo-popup-content {
            background: transparent; position: relative;
            max-width: 500px; width: 90%; text-align: center;
            /* The scale animation trick */
            animation: popIn 0.5s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
        }
        @keyframes popIn {
            0% { transform: scale(0.85); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .promo-poster {
            width: 100%; height: auto; border-radius: 12px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5); border: 2px solid #333;
        }
        .promo-close {
            position: absolute; top: -15px; right: -15px;
            background: #111; color: white; width: 36px; height: 36px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: 0.2s; border: 2px solid #333; z-index: 10;
        }
        .promo-close:hover { background: #cc0000; border-color: #cc0000; transform: rotate(90deg); }
        .promo-claim-btn {
            background: #cc0000; color: white; padding: 15px 30px; border-radius: 50px;
            font-family: 'Oswald', sans-serif; font-size: 18px; text-transform: uppercase; letter-spacing: 1px;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
            margin-top: -20px; position: relative; z-index: 5;
            box-shadow: 0 10px 25px rgba(204, 0, 0, 0.4); transition: 0.2s; border: none; cursor: pointer;
        }
        .promo-claim-btn:hover { background: #111; transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.5); }

        /* 🔥 RESPONSIVE MEDIA QUERIES 🔥 */
        @media (max-width: 950px) { 
            .nav-links { display: none; }
            .mobile-menu-btn { display: flex; }
        }

        @media (max-width: 768px) {
            .navbar { padding: 15px 20px; }
            .catalog-header { padding: 30px 20px 0; }
            .catalog-header h1 { font-size: 32px; }
            .section { padding: 20px 20px 40px; }
            .section-title { font-size: 20px; }
            
            .product-card { min-width: 180px; width: 180px; }
            .pc-img { height: 160px; }
            .pc-name { font-size: 14px; height: 38px; }
            
            .category-modal-content { width: 100%; height: 100vh; border-radius: 0; }
            .category-modal-header { padding: 20px; }
            .category-modal-header h2 { font-size: 22px; }
            .category-modal-body .pos-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; }
            
            .footer { padding: 40px 20px 30px; }
            .footer-content { flex-direction: column; gap: 30px; }
            .footer-col { width: 100%; min-width: 100%; }
        }
    </style>
</head>
<body>

    <div id="pageTransition" class="page-transition">
        <div class="transition-brand">SNEAK<span>D</span>UP</div>
    </div>

    <div class="promo-popup-overlay" id="promoPopup">
        <div class="promo-popup-content">
            <div class="promo-close" onclick="closePromoPopup()"><i data-lucide="x" style="width: 20px; height: 20px;"></i></div>
            
            <img src="images/sdwa.jpg" class="promo-poster" alt="SneakDup Promo">
            
            
        </div>
    </div>

    <div class="mobile-overlay" id="mobileOverlay" onclick="toggleMenu()"></div>
    <div class="mobile-menu" id="mobileMenu">
        <div class="mm-header">
            <h2 class="mm-brand">SNEAK<span>D</span>UP</h2>
            <div class="mm-close" onclick="toggleMenu()"><i data-lucide="x"></i></div>
        </div>
        <div class="mm-links">
            <a href="shop.php" class="mm-link">Shop <i data-lucide="chevron-right"></i></a>
            <a href="#" class="mm-link" onclick="openCategoryModal('Bundle'); toggleMenu();">Promos <i data-lucide="chevron-right"></i></a>
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

    <div class="top-bar">
        <div class="marquee-content">
            Elevate Your Game. Step Up Your Style with SneakDup | Fresh Drops & Daily Discounts—Visit us for 20-30% OFF | <a href="https://maps.app.goo.gl/p1y98Pz4VJUivgHr5">STORE LOCATOR</a> | <a href="contact.php">HELP</a>
        </div>
    </div>

    <div class="navbar">
        <a href="index.php" class="brand">
            <img src="images/Sneakdup_logo.jpg" alt="SneakDup Logo">
            <div class="brand-text">SNEAK<span>D</span>UP</div>
        </a>

        <div class="nav-links">
            <a href="shop.php" class="active">Shop</a>
            <a href="#" onclick="openCategoryModal('Bundle')">Promos</a>
            <a href="#" onclick="triggerTransition(event, 'contact.php')">Contact</a>
        </div>

        <div class="nav-icons">
            
            <div class="search-container">
                <form action="shop.php" method="GET" class="search-form">
                    <a href="#" class="nav-icon-btn" onclick="toggleSearch(event)" title="Search Catalog">
                        <i data-lucide="search"></i>
                    </a>
                    <input type="text" name="search" id="searchInput" class="search-input-hidden" placeholder="Search catalog..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_query); ?>"> 
                </form>
            </div>
            
            <?php if($is_logged_in): ?>
                <?php if($is_staff): ?>
                    <a href="<?php echo ($user_role==='admin'?'admin/dashboard.php':'employee/orders.php'); ?>" class="nav-icon-btn">
                        <span class="staff-pill"><i data-lucide="layout-dashboard" style="width:14px; height:14px;"></i> Panel</span>
                    </a>
                <?php endif; ?>
                <div class="nav-icon-btn"><i data-lucide="user"></i> <?php echo htmlspecialchars(explode(' ', $_SESSION['fullname'])[0]); ?></div>
                <a href="logout.php" class="nav-icon-btn" style="color:#cc0000 !important;"><i data-lucide="log-out"></i></a>
            <?php else: ?>
                <a href="https://m.me/sneakdupofficial" target="_blank" class="nav-icon-btn" title="Chat on Messenger">
                    <i data-lucide="message-circle"></i>
                </a>
            <?php endif; ?>

            <div class="mobile-menu-btn" onclick="toggleMenu()">
                <i data-lucide="menu"></i>
            </div>
        </div>
    </div>

    <div class="catalog-header">
        <div class="header-top">
            <div>
                <h1>The Catalog</h1>
                <?php if($search_query): ?>
                    <div class="catalog-meta">Showing results for "<?php echo htmlspecialchars($search_query); ?>"</div>
                <?php endif; ?>
            </div>
            
            <select class="sort-dropdown" onchange="window.location.href='shop.php?search=<?php echo urlencode($search_query); ?>&sort='+this.value">
                <option value="newest" <?php echo $sort_query === 'newest' ? 'selected' : ''; ?>>Sort: Newest Drops</option>
                <option value="price_low" <?php echo $sort_query === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_high" <?php echo $sort_query === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
            </select>
        </div>
        
        <div class="filter-bar">
            <div class="brand-pills">
                <a href="shop.php" class="brand-pill <?php echo empty($search_query) ? 'active' : ''; ?>">All Drops</a>
                <a href="shop.php?search=Nike" class="brand-pill <?php echo strtolower($search_query) === 'nike' ? 'active' : ''; ?>">Nike</a>
                <a href="shop.php?search=Adidas" class="brand-pill <?php echo strtolower($search_query) === 'adidas' ? 'active' : ''; ?>">Adidas</a>
                <a href="shop.php?search=New Balance" class="brand-pill <?php echo strtolower($search_query) === 'new balance' ? 'active' : ''; ?>">New Balance</a>
                <a href="shop.php?search=Vans" class="brand-pill <?php echo strtolower($search_query) === 'vans' ? 'active' : ''; ?>">Vans</a>
            </div>
        </div>
    </div>

    <div class="section" id="popular">
        <div class="section-header">
            <div class="section-title">Popular Picks <i data-lucide="flame"></i></div>
            <a onclick="openCategoryModal('Popular')" class="view-all">See All <i data-lucide="chevron-right" style="width: 16px;"></i></a>
        </div>
        
        <div class="slider-container">
            <?php if ($popular_picks->num_rows > 0): ?>
                <?php while($row = $popular_picks->fetch_assoc()): ?>
                    <a href="product_details.php?id=<?php echo $row['id']; ?>" class="product-card">
                        
                        <div class="pc-img">
                            <img src="uploads/<?php echo $row['image']; ?>" class="img-clean" alt="Product">
                            <?php if(!empty($row['variant_image'])): ?>
                                <img src="uploads/<?php echo $row['variant_image']; ?>" class="img-lifestyle" alt="Lifestyle">
                            <?php else: ?>
                                <img src="uploads/<?php echo $row['image']; ?>" class="img-lifestyle" alt="Lifestyle">
                            <?php endif; ?>
                            
                            <?php if($row['is_promo_eligible']): ?>
                                <div class="promo-tag"><i data-lucide="tag" style="width:10px; height:10px;"></i> SALE</div>
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
            <?php else: ?>
                <p style="color:#777;">No popular products selected yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($bundles->num_rows > 0): ?>
    <div class="section" style="background-color: #f4f6f8; border-radius: 20px;">
        <div class="section-header">
            <div class="section-title">2 For 999 Bundles <i data-lucide="gift" style="color:#cc0000;"></i></div>
            <a onclick="openCategoryModal('Bundle')" class="view-all">Shop Promos <i data-lucide="chevron-right" style="width: 16px;"></i></a>
        </div>
        <div class="slider-container">
            <?php while($row = $bundles->fetch_assoc()): ?>
                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="product-card">
                    <div class="pc-img">
                        <img src="uploads/<?php echo $row['image']; ?>" class="img-clean" alt="Bundle">
                        <?php if(!empty($row['variant_image'])): ?>
                            <img src="uploads/<?php echo $row['variant_image']; ?>" class="img-lifestyle" alt="Lifestyle">
                        <?php else: ?>
                            <img src="uploads/<?php echo $row['image']; ?>" class="img-lifestyle" alt="Lifestyle">
                        <?php endif; ?>
                        <div class="promo-tag"><i data-lucide="tag" style="width:10px; height:10px;"></i> SALE</div>
                    </div>
                    <?php if($is_staff): ?>
                        <div class="stock-overlay"><i data-lucide="package" style="width:12px; height:12px;"></i> STOCK: <?php echo $row['total_inventory'] ? $row['total_inventory'] : 0; ?></div>
                    <?php endif; ?>
                    <div class="pc-details">
                        <div class="pc-brand">BUNDLE DEAL</div>
                        <div class="pc-name"><?php echo htmlspecialchars($row['name']); ?></div>
                        <div class="pc-price" style="color:#cc0000;">₱<?php echo number_format($row['price'], 2); ?></div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="section">
        <div class="section-header">
            <div class="section-title">New In Shoes <i data-lucide="shopping-bag"></i></div>
            <a onclick="openCategoryModal('Shoes')" class="view-all">Shop Shoes <i data-lucide="chevron-right" style="width: 16px;"></i></a>
        </div>
        <div class="slider-container">
            <?php while($row = $shoes->fetch_assoc()): ?>
                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="product-card">
                    <div class="pc-img">
                        <img src="uploads/<?php echo $row['image']; ?>" class="img-clean" alt="Shoe">
                        <?php if(!empty($row['variant_image'])): ?>
                            <img src="uploads/<?php echo $row['variant_image']; ?>" class="img-lifestyle" alt="Lifestyle">
                        <?php else: ?>
                            <img src="uploads/<?php echo $row['image']; ?>" class="img-lifestyle" alt="Lifestyle">
                        <?php endif; ?>
                        <?php if($row['is_promo_eligible']): ?>
                            <div class="promo-tag"><i data-lucide="tag" style="width:10px; height:10px;"></i> SALE</div>
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

    <div class="section">
        <div class="section-header">
            <div class="section-title">Apparel </div>
            <a onclick="openCategoryModal('Clothes')" class="view-all">Shop Clothing <i data-lucide="chevron-right" style="width: 16px;"></i></a>
        </div>
        <div class="slider-container">
            <?php while($row = $clothes->fetch_assoc()): ?>
                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="product-card">
                    <div class="pc-img">
                        <img src="uploads/<?php echo $row['image']; ?>" class="img-clean" alt="Clothes">
                        <?php if(!empty($row['variant_image'])): ?>
                            <img src="uploads/<?php echo $row['variant_image']; ?>" class="img-lifestyle" alt="Lifestyle">
                        <?php else: ?>
                            <img src="uploads/<?php echo $row['image']; ?>" class="img-lifestyle" alt="Lifestyle">
                        <?php endif; ?>
                        <?php if($row['is_promo_eligible']): ?>
                            <div class="promo-tag"><i data-lucide="tag" style="width:10px; height:10px;"></i> SALE</div>
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

    <?php if ($accessories->num_rows > 0): ?>
    <div class="section">
        <div class="section-header">
            <div class="section-title">Accessories </div>
            <a onclick="openCategoryModal('Accessories')" class="view-all">Shop Accessories <i data-lucide="chevron-right" style="width: 16px;"></i></a>
        </div>
        <div class="slider-container">
            <?php while($row = $accessories->fetch_assoc()): ?>
                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="product-card">
                    <div class="pc-img">
                        <img src="uploads/<?php echo $row['image']; ?>" class="img-clean" alt="Accessories">
                        <?php if(!empty($row['variant_image'])): ?>
                            <img src="uploads/<?php echo $row['variant_image']; ?>" class="img-lifestyle" alt="Lifestyle">
                        <?php else: ?>
                            <img src="uploads/<?php echo $row['image']; ?>" class="img-lifestyle" alt="Lifestyle">
                        <?php endif; ?>
                        <?php if($row['is_promo_eligible']): ?>
                            <div class="promo-tag"><i data-lucide="tag" style="width:10px; height:10px;"></i> SALE</div>
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

    <div id="categoryModal" class="category-modal-overlay" onclick="closeCategoryModal(event)">
        <div class="category-modal-content">
            <div class="category-modal-header">
                <h2 id="catModalTitle">Category Name</h2>
                <div class="category-modal-close" onclick="document.getElementById('categoryModal').style.display='none'"><i data-lucide="x"></i></div>
            </div>
            <div class="category-modal-body">
                <div class="pos-grid" id="catModalGrid"></div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-content">
            <div class="footer-col">
                <h3>SNEAK<span style="color:#cc0000;">D</span>UP</h3>
                <p>Your ultimate destination for the latest drops, exclusive kicks, and fresh apparel. We bring the heat straight to your feet.</p>
            </div>
            <div class="footer-col">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#popular">Popular Picks</a></li>
                    <li><a href="https://maps.app.goo.gl/p1y98Pz4VJUivgHr5">Store Locator</a></li>
                    <li><a href="#" onclick="triggerTransition(event, 'contact.php')">Help / Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Stay Connected</h3>
                <p>Follow us for daily updates, exclusive promos, and behind-the-scenes video drops.</p>
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

    <div class="chat-btn" onclick="toggleChat()">
        <i data-lucide="message-square" style="width: 28px; height: 28px;"></i>
    </div>

    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <div class="chat-header-title">
                <i data-lucide="bot"></i> SNEAKDUP AI
            </div>
            <div class="chat-close" onclick="toggleChat()"><i data-lucide="x" style="width: 20px;"></i></div>
        </div>
        <div class="chat-body" id="chatBody">
            <div class="chat-msg msg-ai">
                Yo! Welcome to SNEAKDUP. How can I help you today?
                <div class="chat-suggestions" id="chatSuggestions">
                    <button class="suggestion-btn" onclick="sendSuggestion('Where is your store located?')">📍 Store Location</button>
                    <button class="suggestion-btn" onclick="sendSuggestion('What shoe brands do you have?')">👟 Shop Shoes</button>
                    <button class="suggestion-btn" onclick="sendSuggestion('What are your store hours?')">🕒 Store Hours</button>
                </div>
            </div>
        </div>
        <div class="chat-footer">
            <input type="text" class="chat-input" id="chatInput" placeholder="Ask me anything..." onkeypress="handleEnter(event)">
            <button class="chat-send" onclick="sendMessage()"><i data-lucide="send" style="width: 18px;"></i></button>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // 1. DATA FROM PHP: Array of all products
        const allShopProducts = <?php echo json_encode($all_products_array); ?>;
        const isStaffCheck = <?php echo $is_staff ? 'true' : 'false'; ?>;

        // 🔥 EXPANDING SEARCH LOGIC 🔥
        function toggleSearch(event) {
            event.preventDefault();
            const input = document.getElementById('searchInput');
            input.classList.toggle('active');
            if(input.classList.contains('active')) {
                input.focus();
            }
        }

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

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.getElementById('pageTransition').classList.remove('active');
            }
        });

        // --- CATEGORY MODAL LOGIC (UPGRADED) ---
        function openCategoryModal(category) {
            document.getElementById('categoryModal').style.display = 'flex';
            
            let title = "All Products";
            if(category === 'Shoes') title = "Sneakers & Footwear";
            if(category === 'Clothes') title = "Apparel & Clothing";
            if(category === 'Accessories') title = "Accessories & Gear";
            if(category === 'Bundle') title = "Exclusive Promos & Bundles";
            if(category === 'Popular') title = "Trending & Popular Picks";
            
            document.getElementById('catModalTitle').innerText = title;
            const grid = document.getElementById('catModalGrid');
            grid.innerHTML = ''; 

            const filteredProducts = allShopProducts.filter(p => {
                if (category === 'All') return true;
                if (category === 'Popular') return p.is_popular == 1; 
                if (category === 'Bundle') return p.is_promo_eligible == 1;
                return p.category === category;
            });

            const displayProducts = category === 'Popular' ? filteredProducts.slice(0, 12) : filteredProducts;

            if(displayProducts.length === 0) {
                grid.innerHTML = '<p style="grid-column:1/-1; text-align:center; padding: 60px; color:#888; font-size:18px;">No items found in this category.</p>';
            } else {
                let html = '';
                displayProducts.forEach(row => {
                    let promoHTML = row.is_promo_eligible == 1 ? '<div class="promo-tag"><i data-lucide="tag" style="width:10px; height:10px;"></i> SALE</div>' : '';
                    let stockHTML = isStaffCheck ? `<div class="stock-overlay"><i data-lucide="package" style="width:12px; height:12px;"></i> STOCK: ${row.total_inventory || 0}</div>` : '';
                    
                    // 🔥 JS DUAL IMAGE RENDERING 🔥
                    let lifestyleImg = row.variant_image ? row.variant_image : row.image;
                    
                    let formattedPrice = parseFloat(row.price).toLocaleString('en-US', {minimumFractionDigits: 2});
                    let clickAction = `href="product_details.php?id=${row.id}"`;

                    html += `
                        <a ${clickAction} class="product-card">
                            <div class="pc-img">
                                <img src="uploads/${row.image}" class="img-clean" alt="Product">
                                <img src="uploads/${lifestyleImg}" class="img-lifestyle" alt="Lifestyle">
                                ${promoHTML}
                            </div>
                            ${stockHTML}
                            <div class="pc-details">
                                <div class="pc-brand">${category === 'Bundle' ? 'BUNDLE DEAL' : row.category.toUpperCase()}</div>
                                <div class="pc-name">${row.name}</div>
                                <div class="pc-price" style="${category === 'Bundle' ? 'color:#cc0000;' : ''}">₱${formattedPrice}</div>
                            </div>
                        </a>
                    `;
                });
                
                grid.innerHTML = html;
                lucide.createIcons(); 
            }
        }

        function closeCategoryModal(event) {
            if (event.target.id === 'categoryModal') {
                document.getElementById('categoryModal').style.display = 'none';
            }
        }

        // --- CHATBOT UI LOGIC ---
        function toggleChat() {
            const chat = document.getElementById('chatWindow');
            chat.style.display = (chat.style.display === 'flex') ? 'none' : 'flex';
        }

        function handleEnter(event) {
            if (event.key === 'Enter') { sendMessage(); }
        }

        function sendSuggestion(text) {
            document.getElementById('chatSuggestions').style.display = 'none';
            const input = document.getElementById('chatInput');
            input.value = text;
            sendMessage();
        }

        async function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            if(!message) return;

            const suggestions = document.getElementById('chatSuggestions');
            if (suggestions) { suggestions.style.display = 'none'; }

            const chatBody = document.getElementById('chatBody');
            
            chatBody.innerHTML += `<div class="chat-msg msg-user">${message}</div>`;
            input.value = '';
            chatBody.scrollTop = chatBody.scrollHeight;

            const typingId = 'typing-' + Date.now();
            chatBody.innerHTML += `<div class="chat-msg msg-ai" id="${typingId}">...</div>`;
            chatBody.scrollTop = chatBody.scrollHeight;

            try {
                const response = await fetch('chatbot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: message })
                });
                const data = await response.json();
                
                const typingElement = document.getElementById(typingId);
                if (typingElement) typingElement.remove();
                
                let formattedReply = data.reply ? data.reply.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') : "I didn't quite get that.";
                chatBody.innerHTML += `<div class="chat-msg msg-ai">${formattedReply}</div>`;
                chatBody.scrollTop = chatBody.scrollHeight;
                lucide.createIcons();

            } catch (error) {
                const typingElement = document.getElementById(typingId);
                if (typingElement) typingElement.remove();
                chatBody.innerHTML += `<div class="chat-msg msg-ai" style="color: #cc0000;">Connection error. Please try again.</div>`;
                chatBody.scrollTop = chatBody.scrollHeight;
            }
        }

        // 🔥 ENTRY INTENT PROMO LOGIC 🔥
        function showPromoPopup() {
            // Check if they've already seen it this session
            if (!sessionStorage.getItem('sneakdup_promo_seen')) {
                // Wait 1.2 seconds so the black screen transition finishes first
                setTimeout(() => {
                    const popup = document.getElementById('promoPopup');
                    popup.style.display = 'flex';
                }, 1200);
            }
        }

        function closePromoPopup() {
            document.getElementById('promoPopup').style.display = 'none';
            // Mark it as seen so it doesn't annoy them again
            sessionStorage.setItem('sneakdup_promo_seen', 'true');
        }

        function claimPromo() {
            closePromoPopup();
            // Instantly open the promos modal
            setTimeout(() => { openCategoryModal('Bundle'); }, 150);
        }

        // 🔥 INITIALIZATION SEQUENCE 🔥
        window.addEventListener('DOMContentLoaded', () => {
            const autoOpen = "<?php echo $auto_open_modal; ?>";
            
            // If they came specifically for the sale, open the modal instead of the popup
            if (autoOpen === 'Bundle') {
                setTimeout(() => { openCategoryModal('Bundle'); }, 150);
                sessionStorage.setItem('sneakdup_promo_seen', 'true'); // Don't show popup
            } else {
                // Otherwise, trigger the entry intent logic
                showPromoPopup();
            }

            // Keep the expanding search bar open if there is an active search query!
            const searchInput = document.getElementById('searchInput');
            if (searchInput.value.trim() !== '') {
                searchInput.classList.add('active');
            }
        });
    </script>
</body>
</html>