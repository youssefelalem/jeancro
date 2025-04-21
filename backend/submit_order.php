<?php
session_start();
require_once '../database/db.php';

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: ../frontend/index.php?error=empty_cart');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: process.php');
    exit;
}

// Validate required fields
$required_fields = ['name', 'phone', 'email', 'address'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        header('Location: process.php?error=missing_fields');
        exit;
    }
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Calculate total
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // Insert order
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            customer_name, customer_phone, customer_email, 
            customer_address, notes, total_amount, status, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $_POST['name'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['address'],
        $_POST['notes'] ?? '',
        $total
    ]);
    
    $order_id = $pdo->lastInsertId();

    // Insert order items
    $stmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id, product_id, quantity, price, size
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($_SESSION['cart'] as $item) {
        $stmt->execute([
            $order_id,
            $item['id'],
            $item['quantity'],
            $item['price'],
            $item['size']
        ]);
    }

    // Commit transaction
    $pdo->commit();

    // Clear cart
    $_SESSION['cart'] = [];

    // Redirect to success page
    header('Location: order_success.php?id=' . $order_id);
    exit;

} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    header('Location: process.php?error=db_error');
    exit;
} 