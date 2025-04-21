<?php
session_start();
require_once '../database/db.php';

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: ../frontend/index.php?error=empty_cart');
    exit;
}

// Get store settings
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Get shipping policy
$shipping_policy = $settings['shipping_policy'] ?? 'سيتم التواصل معك لتحديد طريقة الشحن المناسبة';

// Get return policy
$return_policy = $settings['return_policy'] ?? 'يمكنك إرجاع المنتج خلال 14 يوم من تاريخ الاستلام';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد الطلب - <?php echo htmlspecialchars($settings['store_name']); ?></title>
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
        <h1 class="text-3xl font-bold text-center mb-8">تأكيد الطلب</h1>

        <!-- Order Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4">ملخص الطلب</h2>
            <div class="space-y-4">
                <?php foreach ($_SESSION['cart'] as $item): ?>
                <div class="flex justify-between items-center border-b pb-4">
                    <div>
                        <h3 class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
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
                        <?php echo number_format($total, 2); ?> ريال
                    </span>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">معلومات التواصل</h2>
            <form action="submit_order.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-700 mb-2">الاسم الكامل</label>
                    <input type="text" name="name" required
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">رقم الجوال</label>
                    <input type="tel" name="phone" required
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">البريد الإلكتروني</label>
                    <input type="email" name="email" required
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">العنوان</label>
                    <textarea name="address" required rows="3"
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2">ملاحظات إضافية</label>
                    <textarea name="notes" rows="3"
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                </div>

                <!-- Policies -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-bold mb-2">سياسة الشحن</h3>
                    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($shipping_policy); ?></p>
                    
                    <h3 class="font-bold mb-2">سياسة الإرجاع</h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($return_policy); ?></p>
                </div>

                <button type="submit" 
                        class="w-full bg-green-500 text-white py-3 rounded-lg hover:bg-green-600 transition duration-300">
                    تأكيد الطلب
                </button>
            </form>
        </div>
    </div>
</body>
</html> 