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

// Get all categories
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $size = $_POST['size'] ?? '';
    $category = $_POST['category'] ?? '';
    $last_order_date = $_POST['last_order_date'] ?? null;
    $image_url = '';
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../frontend/assets/images/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = 'frontend/assets/images/' . $new_filename;
            } else {
                $error_message = "حدث خطأ أثناء رفع الصورة";
            }
        } else {
            $error_message = "نوع الملف غير مسموح به. الأنواع المسموحة هي: jpg, jpeg, png, gif";
        }
    } else {
        $error_message = "يرجى اختيار صورة للمنتج";
    }
    
    // Insert product into database
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, size, category_id, image_url, last_order_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute([$name, $description, $price, $size, $category, $image_url, $last_order_date]);
    
    if ($result) {
        header('Location: products.php?success=1');
        exit();
    } else {
        $error_message = "حدث خطأ أثناء إضافة المنتج";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة منتج جديد - لوحة إدارة متجر الملابس</title>
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
                    <h1 class="text-2xl font-bold text-gray-800">إضافة منتج جديد</h1>
                </div>
            </header>
            
            <main class="p-6">
                <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                                اسم المنتج
                            </label>
                            <input type="text" name="name" id="name" required
                                   class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                                وصف المنتج
                            </label>
                            <textarea name="description" id="description" rows="4" required
                                      class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="price">
                                السعر (درهم)
                            </label>
                            <input type="number" name="price" id="price" step="0.01" min="0" required
                                   class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="size">
                                المقاس
                            </label>
                            <input type="text" name="size" id="size" required
                                   class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="category">
                                الفئة
                            </label>
                            <select name="category" id="category" required
                                    class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">اختر الفئة</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                                <option value="new">إضافة فئة جديدة</option>
                            </select>
                        </div>
                        
                        <div id="new-category-input" class="hidden">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="new-category">
                                الفئة الجديدة
                            </label>
                            <input type="text" name="new-category" id="new-category"
                                   class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                                صورة المنتج
                            </label>
                            <input type="file" name="image" id="image" accept="image/*" required
                                   class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="last_order_date">
                                تاريخ آخر طلب
                            </label>
                            <input type="date" name="last_order_date" id="last_order_date"
                                   class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <p class="text-sm text-gray-500 mt-1">اترك هذا الحقل فارغاً إذا لم يتم طلب المنتج بعد</p>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-plus ml-1"></i> إضافة المنتج
                            </button>
                            <a href="products.php" class="text-gray-600 hover:text-gray-800">
                                <i class="fas fa-times ml-1"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Show/hide new category input based on selection
        document.getElementById('category').addEventListener('change', function() {
            const newCategoryInput = document.getElementById('new-category-input');
            if (this.value === 'new') {
                newCategoryInput.classList.remove('hidden');
            } else {
                newCategoryInput.classList.add('hidden');
            }
        });
    </script>
</body>
</html>