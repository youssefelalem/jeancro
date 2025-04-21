<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id']) || !is_numeric($data['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$product_id = (int)$data['product_id'];

// Check if cart exists and has items
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

// Remove the specified product
$found = false;
foreach ($_SESSION['cart'] as $key => $item) {
    if ($item['id'] == $product_id) {
        unset($_SESSION['cart'][$key]);
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Product not found in cart']);
    exit;
}

// Reindex array
$_SESSION['cart'] = array_values($_SESSION['cart']);

echo json_encode(['success' => true]);
?> 