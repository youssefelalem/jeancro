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

// Handle category deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Check if category is being used by any products
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        $productCount = $stmt->fetchColumn();
        
        if ($productCount > 0) {
            $error_message = "لا يمكن حذف الفئة لأنها مستخدمة في منتجات";
        } else {
            // Delete the category
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            // Commit transaction
            $pdo->commit();
            $success_message = "تم حذف الفئة بنجاح";
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error_message = "حدث خطأ أثناء حذف الفئة";
    }
}

// Handle category addition/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        if ($id) {
            // Update existing category
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
            $success_message = "تم تحديث الفئة بنجاح";
        } else {
            // Add new category
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $success_message = "تم إضافة الفئة بنجاح";
        }
        
        // Commit transaction
        $pdo->commit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error_message = "حدث خطأ أثناء حفظ الفئة";
    }
}

// Get category for editing if ID is provided
$editCategory = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all categories with product count
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الفئات - لوحة إدارة متجر الملابس</title>
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
                <a href="products.php" class="block py-2.5 px-4 hover:bg-gray-700">
                    <i class="fas fa-tshirt ml-2"></i> المنتجات
                </a>
                <a href="categories.php" class="block py-2.5 px-4 bg-blue-600 text-white">
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
                    <h1 class="text-2xl font-bold text-gray-800">إدارة الفئات</h1>
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
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Category Form -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h2 class="text-lg font-bold mb-4">
                                <?php echo $editCategory ? 'تعديل الفئة' : 'إضافة فئة جديدة'; ?>
                            </h2>
                            
                            <form method="POST" class="space-y-4">
                                <?php if ($editCategory): ?>
                                <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                                <?php endif; ?>
                                
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                                        اسم الفئة
                                    </label>
                                    <input type="text" name="name" id="name" required
                                           value="<?php echo $editCategory ? htmlspecialchars($editCategory['name']) : ''; ?>"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                                        وصف الفئة
                                    </label>
                                    <textarea name="description" id="description" rows="4"
                                              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo $editCategory ? htmlspecialchars($editCategory['description']) : ''; ?></textarea>
                                </div>
                                
                                <div class="flex items-center justify-end space-x-4 rtl:space-x-reverse">
                                    <?php if ($editCategory): ?>
                                    <a href="categories.php" class="text-gray-600 hover:text-gray-800">
                                        إلغاء
                                    </a>
                                    <?php endif; ?>
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        <?php echo $editCategory ? 'حفظ التغييرات' : 'إضافة الفئة'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Categories List -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اسم الفئة</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الوصف</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عدد المنتجات</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                            لا توجد فئات متاحة
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($category['description']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo $category['product_count']; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="?edit=<?php echo $category['id']; ?>" class="text-blue-600 hover:text-blue-900 ml-3">
                                                <i class="fas fa-edit"></i> تعديل
                                            </a>
                                            <?php if ($category['product_count'] == 0): ?>
                                            <a href="javascript:void(0)" 
                                               onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')"
                                               class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i> حذف
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        function confirmDelete(categoryId, categoryName) {
            if (confirm('هل أنت متأكد من حذف الفئة "' + categoryName + '"؟')) {
                window.location.href = 'categories.php?delete=' + categoryId;
            }
        }
    </script>
</body>
</html>