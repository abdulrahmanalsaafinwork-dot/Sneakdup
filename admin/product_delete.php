<?php
session_start();
include __DIR__ . '/../db_conn.php';

// 1. Security Check: Ensure only Admin can delete products
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php");
    exit();
}

// 2. Check if an ID was actually sent in the URL
if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);

    // --- SMART FEATURE 1: DELETE THE IMAGE FILE ---
    // First, find the image filename in the database
    $stmt_img = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt_img->bind_param("i", $product_id);
    $stmt_img->execute();
    $result_img = $stmt_img->get_result();
    
    if ($result_img->num_rows > 0) {
        $row = $result_img->fetch_assoc();
        $image_path = "../uploads/" . $row['image'];
        
        // If the file exists and isn't a generic default image, delete it from the folder
        if (!empty($row['image']) && file_exists($image_path) && $row['image'] !== 'default.jpg') {
            unlink($image_path); 
        }
    }

    // --- SMART FEATURE 2: DELETE ASSOCIATED SIZES/VARIANTS ---
    // Delete all stock sizes linked to this product so they don't get left behind
    $stmt_variants = $conn->prepare("DELETE FROM product_variants WHERE product_id = ?");
    $stmt_variants->bind_param("i", $product_id);
    $stmt_variants->execute();

    // --- FINAL STEP: DELETE THE PRODUCT ---
    $stmt_product = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt_product->bind_param("i", $product_id);
    
    if ($stmt_product->execute()) {
        // 🔥 FIX: Successfully deleted, redirect back to inventory
        header("Location: inventory.php");
        exit();
    } else {
        // 🔥 FIX: If there was an error, redirect back to inventory
        header("Location: inventory.php");
        exit();
    }

} else {
    // 🔥 FIX: If someone tries to access this page directly without an ID, send them back to inventory
    header("Location: inventory.php");
    exit();
}
?>