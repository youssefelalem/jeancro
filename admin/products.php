<?php
session_start();
require_once '../database/db.php';

// Fetch settings from the database
try {
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings === false) {
        $settings = [];
    }
} catch (PDOException $e) {
    error_log("Error fetching settings: " . $e->getMessage());
    $settings = [];
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
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

// Get all products with category names
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المنتجات - لوحة إدارة متجر الملابس</title>
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
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-white">
            <div class="p-4">
                <h1 class="text-xl font-bold">لوحة الإدارة</h1>
            </div>
            <nav class="mt-4">
                <a href="index.php" class="block py-2.5 px-4 hover:bg-gray-700">
                    <i class="fas fa-home ml-2"></i> الرئيسية
                </a>
                <a href="products.php" class="block py-2.5 px-4 bg-blue-600 text-white">
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
                    <h1 class="text-2xl font-bold text-gray-800">إدارة المنتجات</h1>
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
                
                <div class="flex justify-between items-center mb-6">
                    <div class="flex space-x-4 rtl:space-x-reverse">
                        <a href="add_product.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            <i class="fas fa-plus ml-2"></i> إضافة منتج جديد
                        </a>
                    </div>
                    
                    <div class="flex space-x-4 rtl:space-x-reverse">
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="بحث عن منتج..." 
                                   class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="relative">
                            <select id="categoryFilter" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">جميع الفئات</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المنتج</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">السعر</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المقاس</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الفئة</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ الإضافة</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($products as $product): ?>
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></div>
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
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        function confirmDelete(productId, productName) {
            if (confirm('هل أنت متأكد من حذف المنتج "' + productName + '"؟')) {
                window.location.href = 'products.php?delete=' + productId;
            }
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const productName = row.querySelector('.text-gray-900').textContent.toLowerCase();
                if (productName.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Category filter functionality
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const selectedCategory = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const categoryCell = row.querySelector('td:nth-child(4) .text-gray-900');
                const categoryId = categoryCell.getAttribute('data-category-id');
                
                if (!selectedCategory || categoryId === selectedCategory) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>