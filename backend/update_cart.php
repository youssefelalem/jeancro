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

// Check if cart exists and has items
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

// Update quantity for the specified product
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['id'] == $product_id) {
        $item['quantity'] = $quantity;
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Product not found in cart']);
    exit;
}

echo json_encode(['success' => true]);
?> 