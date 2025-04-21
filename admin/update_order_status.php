<?php
session_start();
require_once '../database/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Check if it's a POST request with required data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['order_status'])) {
    $product_id = (int)$_POST['product_id'];
    $order_status = $_POST['order_status'];
    
    // Validate order status
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($order_status, $valid_statuses)) {
        header('Location: products.php?error=invalid_status');
        exit();
    }
    
    // Update the order status
    $stmt = $pdo->prepare("UPDATE products SET order_status = ? WHERE id = ?");
    $result = $stmt->execute([$order_status, $product_id]);
    
    if ($result) {
        // If status is delivered, update last_order_date
        if ($order_status === 'delivered') {
            $stmt = $pdo->prepare("UPDATE products SET last_order_date = NOW() WHERE id = ?");
            $stmt->execute([$product_id]);
        }
        
        header('Location: products.php?success=status_updated');
    } else {
        header('Location: products.php?error=update_failed');
    }
} else {
    header('Location: products.php');
}
exit(); 