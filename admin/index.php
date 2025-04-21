<?php
session_start();
require_once '../database/db.php';

// Fetch settings from the database
try {
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    // Provide default empty array if no settings found
    if ($settings === false) {
        $settings = [];
    }
} catch (PDOException $e) {
    // Handle potential error, maybe log it or set default settings
    error_log("Error fetching settings: " . $e->getMessage());
    $settings = []; // Default to empty array on error
}

// Check if admin is logged in
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple authentication (in production, use proper authentication)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $is_logged_in = true;
    } else {
        $login_error = "اسم المستخدم أو كلمة المرور غير صحيحة";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get product details before deleting
        $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            // Delete the product
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            // Delete the image file if it exists
            if (!empty($product['image_url'])) {
                $image_path = '../' . $product['image_url'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Commit transaction
            $pdo->commit();
            $success_message = "تم حذف المنتج بنجاح";
        } else {
            $error_message = "المنتج غير موجود";
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error_message = "حدث خطأ أثناء حذف المنتج";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة إدارة متجر الملابس</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="../<?php echo htmlspecialchars($settings['logo_url'] ?? 'frontend/assets/images/logo_6803fc3a55593.jpeg'); ?>" type="image/x-icon">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php if (!$is_logged_in): ?>
    <!-- Login Form -->
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-2xl font-bold text-center mb-6">تسجيل الدخول</h1>
            
            <?php if (isset($login_error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $login_error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        اسم المستخدم
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           id="username" name="username" type="text" placeholder="اسم المستخدم" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        كلمة المرور
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" 
                           id="password" name="password" type="password" placeholder="كلمة المرور" required>
                </div>
                <div class="flex items-center justify-center">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" 
                            type="submit" name="login">
                        تسجيل الدخول
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Admin Dashboard -->
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-white">
            <div class="p-4">
                <h1 class="text-xl font-bold">لوحة الإدارة</h1>
            </div>
            <nav class="mt-4">
                <a href="index.php" class="block py-2.5 px-4 bg-blue-600 text-white">
                    <i class="fas fa-home ml-2"></i> الرئيسية
                </a>
                <a href="products.php" class="block py-2.5 px-4 hover:bg-gray-700">
                    <i class="fas fa-tshirt ml-2"></i> المنتجات
                </a>
                <a href="categories.php" class="block py-2.5 px-4 hover:bg-gray-700">
                    <i class="fas fa-tags ml-2"></i> الفئات
                </a>
                <a href="settings.php" class="block py-2.5 px-4 hover:bg-gray-700">
                    <i class="fas fa-cog ml-2"></i> الإعدادات
                </a>
                <a href="index.php?logout=1" class="block py-2.5 px-4 hover:bg-gray-700 text-red-400">
                    <i class="fas fa-sign-out-alt ml-2"></i> تسجيل الخروج
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <header class="bg-white shadow">
                <div class="py-6 px-4">
                    <h1 class="text-2xl font-bold text-gray-800">لوحة التحكم</h1>
                </div>
            </header>
            
            <main class="p-6">
                <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Stats Cards -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                                <i class="fas fa-tshirt text-2xl"></i>
                            </div>
                            <div class="mr-4">
                                <h2 class="text-gray-600 text-sm">المنتجات</h2>
                                <p class="text-2xl font-bold">
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-500">
                                <i class="fas fa-tags text-2xl"></i>
                            </div>
                            <div class="mr-4">
                                <h2 class="text-gray-600 text-sm">الفئات</h2>
                                <p class="text-2xl font-bold">
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Products -->
                <div class="mt-8">
                    <h2 class="text-xl font-bold mb-4">أحدث المنتجات</h2>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المنتج</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">السعر</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المقاس</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الفئة</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                $stmt = $pdo->query("
                                    SELECT p.*, c.name as category_name 
                                    FROM products p 
                                    LEFT JOIN categories c ON p.category_id = c.id 
                                    ORDER BY p.created_at DESC 
                                    LIMIT 5
                                ");
                                while ($product = $stmt->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <img class="h-10 w-10 rounded-full object-cover" 
                                                     src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            </div>
                                            <div class="mr-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo number_format($product['price'], 2); ?> dh</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($product['size']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($product['category_name'] ?? 'غير مصنف'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="text-blue-600 hover:text-blue-900 ml-3">تعديل</a>
                                        <a href="javascript:void(0)" 
                                           onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')"
                                           class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i> حذف
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 text-left">
                        <a href="products.php" class="text-blue-600 hover:text-blue-800">عرض جميع المنتجات &rarr;</a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        function confirmDelete(productId, productName) {
            if (confirm('هل أنت متأكد من حذف المنتج "' + productName + '"؟')) {
                window.location.href = 'index.php?delete=' + productId;
            }
        }
    </script>
</body>
</html>