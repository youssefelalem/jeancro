<?php
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

$product_id = (int)$_GET['id'];

try {
    // Get product details with category name
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    // Get similar products from the same category
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.category_id = ? AND p.id != ? 
        LIMIT 4
    ");
    $stmt->execute([$product['category_id'], $product_id]);
    $similar_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $product['similar_products'] = $similar_products;
    echo json_encode($product);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?> 