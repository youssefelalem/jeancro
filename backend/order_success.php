<?php
session_start();
require_once '../database/db.php';

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../frontend/index.php');
    exit;
}

$order_id = (int)$_GET['id'];

try {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, oi.product_id, oi.quantity, oi.price, oi.size, p.name as product_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($order_items)) {
        header('Location: ../frontend/index.php');
        exit;
    }

    // Get store settings
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get first item for order details
    $order = $order_items[0];
} catch (PDOException $e) {
    header('Location: ../frontend/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم تأكيد الطلب - <?php echo htmlspecialchars($settings['store_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-green-500 text-6xl mb-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="text-3xl font-bold mb-4">تم تأكيد طلبك بنجاح!</h1>
                <p class="text-gray-600 mb-6">
                    شكراً لك على ثقتك بنا. سنتواصل معك قريباً لتأكيد تفاصيل الطلب.
                </p>
                
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <h2 class="font-bold mb-2">تفاصيل الطلب</h2>
                    <p class="text-gray-600">رقم الطلب: #<?php echo $order_id; ?></p>
                    <p class="text-gray-600">التاريخ: <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></p>
                </div>

                <div class="space-y-4 mb-6">
                    <?php foreach ($order_items as $item): ?>
                    <div class="flex justify-between items-center border-b pb-4">
                        <div>
                            <h3 class="font-semibold"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                            <p class="text-gray-600">المقاس: <?php echo htmlspecialchars($item['size']); ?></p>
                            <p class="text-gray-600">الكمية: <?php echo $item['quantity']; ?></p>
                        </div>
                        <p class="text-green-600 font-bold">
                            <?php echo number_format($item['price'] * $item['quantity'], 2); ?> ريال
                        </p>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="flex justify-between items-center pt-4">
                        <span class="font-bold">المجموع:</span>
                        <span class="text-green-600 font-bold text-xl">
                            <?php echo number_format($order['total_amount'], 2); ?> ريال
                        </span>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <h2 class="font-bold mb-2">معلومات التوصيل</h2>
                    <p class="text-gray-600">الاسم: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p class="text-gray-600">العنوان: <?php echo htmlspecialchars($order['customer_address']); ?></p>
                    <p class="text-gray-600">الهاتف: <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                </div>

                <a href="../frontend/index.php" 
                   class="inline-block bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition duration-300">
                    العودة للرئيسية
                </a>
            </div>
        </div>
    </div>
</body>
</html> 