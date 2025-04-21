<?php
session_start();
require_once '../database/db.php';

// Get language from session or default to Arabic
$lang = $_SESSION['lang'] ?? 'ar';

// Load translations
$translations = [];
if ($lang === 'fr') {
    $translations = require_once 'lang/fr.php';
}

// Get store settings
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected category
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : null;

// Get products with category names
$query = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE 1=1
";
$params = [];

if ($selected_category) {
    $query .= " AND p.category_id = ?";
    $params[] = $selected_category;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cart items from session if exists
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cart_count = count($cart_items);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['store_name']); ?> - متجر</title>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="../<?php echo htmlspecialchars($settings['logo_url']); ?>" type="image/x-icon">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }
        .sidebar.open {
            transform: translateX(0);
        }
        .sidebar.closed {
            transform: translateX(100%);
        }
        .product-details-modal {
            display: none;
        }
        .product-details-modal.active {
            display: flex;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header with Menu -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <?php if (!empty($settings['logo_url'])): ?>
                    <a href="index.php" class="ml-4">
                        <img src="../<?php echo htmlspecialchars($settings['logo_url']); ?>" 
                             alt="<?php echo htmlspecialchars($settings['store_name']); ?>" 
                             class="h-12 w-auto">
                    </a>
                    <?php endif; ?>
                </div>
                
                <nav class="hidden md:flex space-x-6 rtl:space-x-reverse">
                    <a href="index.php" class="text-gray-700 hover:text-blue-600">الرئيسية</a>
                    <a href="#about" class="text-gray-700 hover:text-blue-600">من نحن</a>
                    <a href="#contact" class="text-gray-700 hover:text-blue-600">اتصل بنا</a>
                </nav>
                
                <div class="flex items-center space-x-4 rtl:space-x-reverse">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" type="button" class="flex items-center text-gray-700 hover:text-blue-600">
                                <i class="fas fa-user text-xl"></i>
                            </button>
                            <div x-show="open" 
                                 @click.away="open = false" 
                                 class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50"
                                 style="display: none;">
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user-circle ml-2"></i>
                                    الملف الشخصي
                                </a>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt ml-2"></i>
                                    تسجيل الخروج
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-blue-600 p-2" title="تسجيل الدخول">
                            <i class="fas fa-sign-in-alt text-xl"></i>
                        </a>
                        <a href="register.php" class="text-gray-700 hover:text-blue-600 p-2" title="إنشاء حساب">
                            <i class="fas fa-user-plus text-xl"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Header Banner -->
    <?php if (!empty($settings['header_banner_url'])): ?>
    <div class="w-full">
        <a href="<?php echo htmlspecialchars($settings['header_banner_link'] ?? '#'); ?>" class="block">
            <img src="../<?php echo htmlspecialchars($settings['header_banner_url']); ?>" 
                 alt="البانر الرئيسي" 
                 class="w-full object-cover shadow-md hover:shadow-lg transition duration-300 mb-8 <?php echo (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $_SERVER['HTTP_USER_AGENT'])) ? '' : 'h-[400px]'; ?>">
        </a>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Banners Section -->
        <?php if (!empty($settings['banner1_url']) || !empty($settings['banner2_url']) || !empty($settings['banner3_url'])): ?>
        <div class="mb-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php if (!empty($settings['banner1_url'])): ?>
                <a href="<?php echo htmlspecialchars($settings['banner1_link'] ?? '#'); ?>" class="block">
                    <img src="../<?php echo htmlspecialchars($settings['banner1_url']); ?>" 
                         alt="Banner 1" 
                         class="w-full h-48 object-cover rounded-lg shadow-md hover:shadow-lg transition duration-300">
                </a>
                <?php endif; ?>
                
                <?php if (!empty($settings['banner2_url'])): ?>
                <a href="<?php echo htmlspecialchars($settings['banner2_link'] ?? '#'); ?>" class="block">
                    <img src="../<?php echo htmlspecialchars($settings['banner2_url']); ?>" 
                         alt="Banner 2" 
                         class="w-full h-48 object-cover rounded-lg shadow-md hover:shadow-lg transition duration-300">
                </a>
                <?php endif; ?>
                
                <?php if (!empty($settings['banner3_url'])): ?>
                <a href="<?php echo htmlspecialchars($settings['banner3_link'] ?? '#'); ?>" class="block">
                    <img src="../<?php echo htmlspecialchars($settings['banner3_url']); ?>" 
                         alt="Banner 3" 
                         class="w-full h-48 object-cover rounded-lg shadow-md hover:shadow-lg transition duration-300">
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <h1 class="text-3xl font-bold text-center mb-8">منتجاتنا</h1>
        
        <!-- Categories -->
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">الفئات</h2>
            <div class="flex flex-wrap gap-4">
                <a href="index.php" 
                   class="px-4 py-2 rounded-full <?php echo !$selected_category ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    الكل
                </a>
                <?php foreach ($categories as $category): ?>
                <a href="?category=<?php echo $category['id']; ?>" 
                   class="px-4 py-2 rounded-full <?php echo $selected_category == $category['id'] ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($products)): ?>
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500 text-lg">لا توجد منتجات متاحة</p>
            </div>
            <?php else: ?>
            <?php foreach ($products as $product): ?>
            <a href="product.php?id=<?php echo $product['id']; ?>" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
                <div class="relative pb-[100%]">
                    <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="absolute inset-0 w-full h-full object-cover">
                </div>
                <div class="p-4">
                    <h2 class="text-2xl font-bold text-center mb-2"><?php echo htmlspecialchars($product['name']); ?></h2>
                    <p class="text-xl font-bold text-green-600 text-center mb-2"><?php echo number_format($product['price'], 2); ?> dh</p>
                    <p class="text-gray-600 text-center">المقاس: <?php echo htmlspecialchars($product['size']); ?></p>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cart Sidebar -->
    <div id="cartSidebar" class="sidebar fixed top-0 right-0 w-80 h-full bg-white shadow-lg z-50 closed">
        <div class="p-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">سلة المشتريات</h2>
                <button id="closeSidebar" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="cartItems" class="mb-4 max-h-96 overflow-y-auto">
                <?php if (empty($cart_items)): ?>
                <p class="text-center text-gray-500">السلة فارغة</p>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item flex items-center justify-between border-b pb-2 mb-2" data-id="<?php echo $item['id']; ?>">
                        <div>
                            <h3 class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="text-sm text-gray-600">المقاس: <?php echo htmlspecialchars($item['size']); ?></p>
                            <p class="text-green-600"><?php echo number_format($item['price'], 2); ?> ريال</p>
                        </div>
                        <div class="flex items-center">
                            <button class="decrease-qty text-gray-500 hover:text-red-500 px-1">-</button>
                            <span class="qty mx-2"><?php echo $item['quantity']; ?></span>
                            <button class="increase-qty text-gray-500 hover:text-green-500 px-1">+</button>
                            <button class="remove-item text-red-500 hover:text-red-700 mr-2">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="border-t pt-4">
                <div class="flex justify-between mb-4">
                    <span class="font-bold">المجموع:</span>
                    <span id="cartTotal" class="font-bold text-green-600">
                        <?php 
                        $total = 0;
                        foreach ($cart_items as $item) {
                            $total += $item['price'] * $item['quantity'];
                        }
                        echo number_format($total, 2);
                        ?> ريال
                    </span>
                </div>
                
                <button id="checkoutButton" class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition duration-300">
                    تأكيد الطلب
                </button>
            </div>
        </div>
    </div>

    <!-- Product Details Modal -->
    <div id="productDetailsModal" class="product-details-modal fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modalProductName" class="text-xl font-bold"></h2>
                <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalProductContent" class="mb-4">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="flex justify-end">
                <button id="addToCartFromModal" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition duration-300">
                    إضافة للسلة
                </button>
            </div>
        </div>
    </div>

    <!-- About Us Section -->
    <section id="about" class="bg-white py-12 mt-12">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-8">من نحن</h2>
            <div class="max-w-3xl mx-auto text-center">
                <p class="text-gray-700 mb-4">
                    <?php echo htmlspecialchars($settings['store_description']); ?>
                </p>
                <p class="text-gray-700 mb-8">
                    تأسس متجرنا في عام 2023، ونفخر بتقديم خدمة عملاء متميزة وتجربة تسوق مميزة لجميع عملائنا.
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-right">
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-xl font-bold mb-4">سياسة الشحن</h3>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($settings['shipping_policy'])); ?></p>
                    </div>
                    
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-xl font-bold mb-4">سياسة الإرجاع</h3>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($settings['return_policy'])); ?></p>
                    </div>
                    
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-xl font-bold mb-4">سياسة الخصوصية</h3>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($settings['privacy_policy'])); ?></p>
                    </div>
                    
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-xl font-bold mb-4">الشروط والأحكام</h3>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($settings['terms_conditions'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="bg-gray-100 py-12 mt-12">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-8"><?php echo $lang === 'fr' ? $translations['contact'] : 'اتصل بنا'; ?></h2>
            <div class="max-w-3xl mx-auto text-center">
                <p class="text-gray-700 mb-4">
                    <?php echo $lang === 'fr' ? $translations['contact_text'] : 'نحن هنا لخدمتك! يمكنك التواصل معنا عبر:'; ?>
                </p>
                <div class="flex justify-center space-x-6 rtl:space-x-reverse mb-8">
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['store_phone']); ?>" class="text-green-600 hover:text-green-700">
                        <i class="fab fa-whatsapp text-3xl"></i>
                    </a>
                    <a href="mailto:<?php echo $settings['store_email']; ?>" class="text-blue-600 hover:text-blue-700">
                        <i class="fas fa-envelope text-3xl"></i>
                    </a>
                    <a href="tel:<?php echo $settings['store_phone']; ?>" class="text-gray-600 hover:text-gray-700">
                        <i class="fas fa-phone text-3xl"></i>
                    </a>
                </div>

                <!-- Social Media Links -->
                <div class="flex justify-center space-x-6 rtl:space-x-reverse">
                    <?php if (!empty($settings['facebook_url'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['facebook_url']); ?>" target="_blank" class="text-blue-600 hover:text-blue-700">
                        <i class="fab fa-facebook text-3xl"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($settings['instagram_url'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['instagram_url']); ?>" target="_blank" class="text-pink-600 hover:text-pink-700">
                        <i class="fab fa-instagram text-3xl"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($settings['twitter_url'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['twitter_url']); ?>" target="_blank" class="text-blue-400 hover:text-blue-500">
                        <i class="fab fa-twitter text-3xl"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['store_name']); ?></p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cart Sidebar Toggle
            const cartButton = document.getElementById('cartButton');
            const cartSidebar = document.getElementById('cartSidebar');
            const closeSidebar = document.getElementById('closeSidebar');
            
            cartButton.addEventListener('click', function() {
                cartSidebar.classList.remove('closed');
                cartSidebar.classList.add('open');
            });
            
            closeSidebar.addEventListener('click', function() {
                cartSidebar.classList.remove('open');
                cartSidebar.classList.add('closed');
            });
            
            // Product Details Modal
            const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
            const productDetailsModal = document.getElementById('productDetailsModal');
            const closeModal = document.getElementById('closeModal');
            const modalProductName = document.getElementById('modalProductName');
            const modalProductContent = document.getElementById('modalProductContent');
            const addToCartFromModal = document.getElementById('addToCartFromModal');
            
            let currentProductId = null;
            
            viewDetailsButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    currentProductId = productId;
                    
                    // Fetch product details
                    fetch(`../backend/get_product.php?id=${productId}`)
                        .then(response => response.json())
                        .then(product => {
                            modalProductName.textContent = product.name;
                            modalProductContent.innerHTML = `
                                <img src="${product.image_url}" alt="${product.name}" class="w-full h-64 object-cover mb-4 rounded">
                                <p class="text-gray-700 mb-2">${product.description}</p>
                                <p class="text-lg font-bold text-green-600 mb-2">${product.price} ريال</p>
                                <p class="text-gray-600 mb-4">المقاس: ${product.size}</p>
                                <div class="flex items-center mb-4">
                                    <label class="mr-2">الكمية:</label>
                                    <div class="flex items-center border rounded">
                                        <button class="decrease-qty-modal px-2 py-1 text-gray-500 hover:text-red-500">-</button>
                                        <input type="number" class="qty-modal w-12 text-center border-x" value="1" min="1">
                                        <button class="increase-qty-modal px-2 py-1 text-gray-500 hover:text-green-500">+</button>
                                    </div>
                                </div>
                            `;
                            
                            // Quantity controls in modal
                            const decreaseQtyModal = modalProductContent.querySelector('.decrease-qty-modal');
                            const increaseQtyModal = modalProductContent.querySelector('.increase-qty-modal');
                            const qtyInputModal = modalProductContent.querySelector('.qty-modal');
                            
                            decreaseQtyModal.addEventListener('click', function() {
                                if (qtyInputModal.value > 1) {
                                    qtyInputModal.value = parseInt(qtyInputModal.value) - 1;
                                }
                            });
                            
                            increaseQtyModal.addEventListener('click', function() {
                                qtyInputModal.value = parseInt(qtyInputModal.value) + 1;
                            });
                            
                            productDetailsModal.classList.add('active');
                        });
                });
            });
            
            closeModal.addEventListener('click', function() {
                productDetailsModal.classList.remove('active');
            });
            
            // Add to cart from modal
            addToCartFromModal.addEventListener('click', function() {
                if (currentProductId) {
                    const qtyInput = document.querySelector('.qty-modal');
                    const quantity = parseInt(qtyInput.value);
                    
                    addToCart(currentProductId, quantity);
                    productDetailsModal.classList.remove('active');
                }
            });
            
            // Add to cart from product cards
            const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
            
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    addToCart(productId, 1);
                });
            });
            
            // Add to cart function
            function addToCart(productId, quantity) {
                fetch('../backend/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantity
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload page to update cart
                        window.location.reload();
                    } else {
                        alert('حدث خطأ أثناء إضافة المنتج إلى السلة');
                    }
                });
            }
            
            // Cart item controls
            const decreaseQtyButtons = document.querySelectorAll('.decrease-qty');
            const increaseQtyButtons = document.querySelectorAll('.increase-qty');
            const removeItemButtons = document.querySelectorAll('.remove-item');
            
            decreaseQtyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const cartItem = this.closest('.cart-item');
                    const productId = cartItem.getAttribute('data-id');
                    const qtySpan = cartItem.querySelector('.qty');
                    let qty = parseInt(qtySpan.textContent);
                    
                    if (qty > 1) {
                        qty--;
                        updateCartItemQuantity(productId, qty);
                    }
                });
            });
            
            increaseQtyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const cartItem = this.closest('.cart-item');
                    const productId = cartItem.getAttribute('data-id');
                    const qtySpan = cartItem.querySelector('.qty');
                    let qty = parseInt(qtySpan.textContent);
                    
                    qty++;
                    updateCartItemQuantity(productId, qty);
                });
            });
            
            removeItemButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const cartItem = this.closest('.cart-item');
                    const productId = cartItem.getAttribute('data-id');
                    
                    removeCartItem(productId);
                });
            });
            
            // Update cart item quantity
            function updateCartItemQuantity(productId, quantity) {
                fetch('../backend/update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantity
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('حدث خطأ أثناء تحديث السلة');
                    }
                });
            }
            
            // Remove cart item
            function removeCartItem(productId) {
                fetch('../backend/remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('حدث خطأ أثناء إزالة المنتج من السلة');
                    }
                });
            }
            
            // Checkout button
            const checkoutButton = document.getElementById('checkoutButton');
            
            checkoutButton.addEventListener('click', function() {
                window.location.href = '../backend/process.php';
            });
        });
    </script>
</body>
</html> 