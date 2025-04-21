<?php
require_once '../database/db.php';
session_start();

// Get product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
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
        header('Location: index.php');
        exit;
    }

    // Get similar products
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.category_id = ? AND p.id != ? 
        LIMIT 4
    ");
    $stmt->execute([$product['category_id'], $product_id]);
    $similar_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get store settings
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get user data if logged in
    $user = null;
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo htmlspecialchars($settings['store_name']); ?></title>
    <link rel="shortcut icon" href="../<?php echo htmlspecialchars($settings['logo_url']); ?>" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        .size-btn {
            transition: all 0.3s ease;
        }
        .size-btn.selected {
            background-color: #10B981;
            color: white;
        }
        .size-option input:checked + .size-btn {
            background-color: #10B981;
            color: white;
        }
        .quantity-btn:active {
            transform: scale(0.95);
        }
        .quantity-btn {
            transition: transform 0.1s;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-2xl font-bold text-blue-600">
                    <?php echo htmlspecialchars($settings['store_name']); ?>
                </a>
                <a href="index.php" class="text-gray-600 hover:text-blue-600">
                    <i class="fas fa-arrow-right ml-2"></i>
                    العودة للرئيسية
                </a>
            </div>
        </div>
    </header>

    <!-- Product Details -->
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="md:flex">
                <!-- Product Image -->
                <div class="md:w-1/2">
                    <div class="relative pb-[100%]">
                        <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="absolute inset-0 w-full h-full object-cover">
                    </div>
                </div>
                
                <!-- Product Info -->
                <div class="md:w-1/2 p-6">
                    <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($product['category_name'] ?? 'غير مصنف'); ?></p>
                    <p class="text-2xl font-bold text-green-600 mb-4"><?php echo number_format($product['price'], 2); ?> ريال</p>
                    
                    <div class="mb-6">
                        <h2 class="font-bold mb-2">الوصف:</h2>
                        <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                    
                    <!-- Size Selection -->
                    <div class="mb-6">
                        <h2 class="font-bold mb-2">اختر المقاس:</h2>
                        <div class="flex flex-wrap gap-2" id="sizesContainer">
                            <?php
                            $sizes = explode(',', $product['size']);
                            foreach ($sizes as $index => $size):
                                $size = trim($size);
                            ?>
                            <label class="size-option cursor-pointer">
                                <input type="radio" name="size" value="<?php echo htmlspecialchars($size); ?>" class="hidden">
                                <span class="size-btn inline-block px-4 py-2 border rounded-lg hover:bg-gray-100">
                                    <?php echo htmlspecialchars($size); ?>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div id="sizeError" class="text-red-500 mt-2 hidden">الرجاء اختيار المقاس</div>
                    </div>

                    <!-- Quantity Selection -->
                    <div class="mb-6">
                        <h2 class="font-bold mb-2">الكمية:</h2>
                        <div class="flex items-center gap-4">
                            <button type="button" id="decreaseQty" 
                                    class="quantity-btn w-10 h-10 flex items-center justify-center border rounded-lg hover:bg-gray-100">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="text" id="quantity" value="1" 
                                   class="w-20 text-center border rounded-lg" 
                                   readonly>
                            <button type="button" id="increaseQty" 
                                    class="quantity-btn w-10 h-10 flex items-center justify-center border rounded-lg hover:bg-gray-100">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Order Button -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button type="button" id="orderButton"
                                class="w-full bg-green-500 text-white text-center py-3 rounded-lg hover:bg-green-600 transition duration-300 mb-4">
                            <i class="fab fa-whatsapp ml-2"></i>
                            إرسال الطلب عبر واتساب
                        </button>
                    <?php else: ?>
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                           class="block w-full bg-blue-500 text-white text-center py-3 rounded-lg hover:bg-blue-600 transition duration-300 mb-4">
                            <i class="fas fa-sign-in-alt ml-2"></i>
                            تسجيل الدخول للطلب
                        </a>
                    <?php endif; ?>

                    <!-- Direct Contact -->
                    <a href="tel:<?php echo $settings['store_phone']; ?>" 
                       class="block w-full bg-blue-500 text-white text-center py-3 rounded-lg hover:bg-blue-600 transition duration-300">
                        <i class="fas fa-phone ml-2"></i>
                        اتصل بنا مباشرة
                    </a>
                </div>
            </div>
        </div>

        <!-- Similar Products -->
        <?php if (!empty($similar_products)): ?>
        <div class="mt-12">
            <h2 class="text-2xl font-bold mb-6">منتجات مشابهة</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($similar_products as $similar): ?>
                <a href="product.php?id=<?php echo $similar['id']; ?>" 
                   class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
                    <div class="relative pb-[100%]">
                        <img src="../<?php echo htmlspecialchars($similar['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($similar['name']); ?>"
                             class="absolute inset-0 w-full h-full object-cover">
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-bold text-center mb-2"><?php echo htmlspecialchars($similar['name']); ?></h3>
                        <p class="text-green-600 font-bold text-center"><?php echo number_format($similar['price'], 2); ?> ريال</p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['store_name']); ?></p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variables
            let selectedSize = '';
            const quantityInput = document.getElementById('quantity');
            const decreaseBtn = document.getElementById('decreaseQty');
            const increaseBtn = document.getElementById('increaseQty');
            const orderButton = document.getElementById('orderButton');
            const sizeError = document.getElementById('sizeError');
            const sizeInputs = document.querySelectorAll('input[name="size"]');

            // Handle quantity changes
            if (decreaseBtn) {
                decreaseBtn.addEventListener('click', function() {
                    let value = parseInt(quantityInput.value) || 1;
                    if (value > 1) {
                        quantityInput.value = value - 1;
                    }
                });
            }

            if (increaseBtn) {
                increaseBtn.addEventListener('click', function() {
                    let value = parseInt(quantityInput.value) || 1;
                    if (value < 99) {
                        quantityInput.value = value + 1;
                    }
                });
            }

            // Handle size selection
            sizeInputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    selectedSize = this.value;
                    if (sizeError) {
                        sizeError.classList.add('hidden');
                    }
                });
            });

            // Handle order button
            if (orderButton) {
                orderButton.addEventListener('click', function() {
                    if (!selectedSize) {
                        if (sizeError) {
                            sizeError.classList.remove('hidden');
                        }
                        return;
                    }

                    const phone = '<?php echo preg_replace('/[^0-9]/', '', $settings['store_phone']); ?>';
                    const message = `*طلب جديد* 🛍️

📋 تفاصيل الطلب:
━━━━━━━━━━━━━━━
🏪 المتجر: *<?php echo addslashes($settings['store_name']); ?>*

👤 معلومات العميل:
<?php if (isset($user)): ?>• الاسم: *<?php echo addslashes($user['username']); ?>*
• الهاتف: *<?php echo addslashes($user['phone']); ?>*
• العنوان: *<?php echo addslashes($user['address']); ?>*<?php endif; ?>

📦 تفاصيل المنتج:
• اسم المنتج: *<?php echo addslashes($product['name']); ?>*
• الفئة: *<?php echo addslashes($product['category_name'] ?? 'غير مصنف'); ?>*
• المقاس: *${selectedSize}*
• الكمية: *${quantityInput.value}*
• السعر: *<?php echo number_format($product['price'], 2); ?> ريال*

📝 الوصف:
${'-'.repeat(20)}
<?php echo addslashes($product['description']); ?>
${'-'.repeat(20)}

🖼️ رابط صورة المنتج:
${window.location.origin}/<?php echo addslashes($product['image_url']); ?>

_تم إرسال الطلب عبر المتجر الإلكتروني_`;

                    const whatsappUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
                    window.open(whatsappUrl, '_blank');
                });
            }
        });
    </script>
</body>
</html>