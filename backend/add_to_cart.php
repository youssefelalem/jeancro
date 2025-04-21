<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id']) || !isset($data['quantity']) || 
    !is_numeric($data['product_id']) || !is_numeric($data['quantity']) || 
    $data['quantity'] < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$product_id = (int)$data['product_id'];
$quantity = (int)$data['quantity'];

try {
    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if product already in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $product_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }

    // Add new item if not found
    if (!$found) {
        $_SESSION['cart'][] = [
            'id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'size' => $product['size'],
            'quantity' => $quantity,
            'image_url' => $product['image_url']
        ];
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 