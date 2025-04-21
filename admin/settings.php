<?php
session_start();
require_once '../database/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Handle banner deletion
if (isset($_POST['delete_banner'])) {
    $banner_type = $_POST['delete_banner'];
    try {
        // Get current URL based on banner type
        $field_name = '';
        $link_field = '';
        
        switch ($banner_type) {
            case 'header':
                $field_name = 'header_banner_url';
                $link_field = 'header_banner_link';
                break;
            case '1':
                $field_name = 'banner1_url';
                $link_field = 'banner1_link';
                break;
            case '2':
                $field_name = 'banner2_url';
                $link_field = 'banner2_link';
                break;
            case '3':
                $field_name = 'banner3_url';
                $link_field = 'banner3_link';
                break;
            case 'logo':
                $field_name = 'logo_url';
                $link_field = null;
                break;
        }

        if ($field_name) {
            // Get current file URL
            $stmt = $pdo->prepare("SELECT $field_name FROM settings WHERE id = 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result[$field_name]) {
                // Delete physical file
                $file_path = "../" . $result[$field_name];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }

                // Update database
                if ($link_field) {
                    $sql = "UPDATE settings SET $field_name = NULL, $link_field = NULL WHERE id = 1";
                } else {
                    $sql = "UPDATE settings SET $field_name = NULL WHERE id = 1";
                }
                $pdo->exec($sql);

                $_SESSION['success_message'] = "تم حذف الصورة بنجاح";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "حدث خطأ أثناء حذف الصورة: " . $e->getMessage();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Display messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Fetch settings from the database
try {
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings === false) {
        $settings = []; // Initialize if no settings exist
    }
} catch (PDOException $e) {
    error_log("Error fetching settings: " . $e->getMessage());
    $settings = [];
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = '../frontend/assets/images/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    try {
        // First update basic text fields
            $stmt = $pdo->prepare("UPDATE settings SET 
                store_name = ?,
                store_description = ?,
                store_email = ?,
                store_phone = ?,
                store_address = ?,
                shipping_policy = ?,
                return_policy = ?,
                privacy_policy = ?,
                terms_conditions = ?,
                facebook_url = ?,
                instagram_url = ?,
            twitter_url = ?
                WHERE id = 1");
            
        $stmt->execute([
            $_POST['store_name'],
            $_POST['store_description'],
            $_POST['store_email'],
            $_POST['store_phone'],
            $_POST['store_address'] ?? '',
            $_POST['shipping_policy'],
            $_POST['return_policy'],
            $_POST['privacy_policy'],
            $_POST['terms_conditions'],
            $_POST['facebook_url'] ?? '',
            $_POST['instagram_url'] ?? '',
            $_POST['twitter_url'] ?? ''
        ]);

        // Handle logo upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $new_filename = 'logo_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $new_filename)) {
                    $pdo->prepare("UPDATE settings SET logo_url = ? WHERE id = 1")
                        ->execute(['frontend/assets/images/' . $new_filename]);
                }
            }
        }

        // Handle header banner upload
        if (isset($_FILES['header_banner']) && $_FILES['header_banner']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['header_banner']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $new_filename = 'header_banner_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['header_banner']['tmp_name'], $upload_dir . $new_filename)) {
                    $pdo->prepare("UPDATE settings SET header_banner_url = ? WHERE id = 1")
                        ->execute(['frontend/assets/images/' . $new_filename]);
                }
            }
        }

        // Handle header banner link
        if (!empty($_POST['header_banner_link'])) {
            $pdo->prepare("UPDATE settings SET header_banner_link = ? WHERE id = 1")
                ->execute([$_POST['header_banner_link']]);
        }

        // Handle banner uploads
        for ($i = 1; $i <= 3; $i++) {
            // Handle banner image
            if (isset($_FILES['banner' . $i]) && $_FILES['banner' . $i]['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['banner' . $i]['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $new_filename = 'banner' . $i . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['banner' . $i]['tmp_name'], $upload_dir . $new_filename)) {
                        $pdo->prepare("UPDATE settings SET banner{$i}_url = ? WHERE id = 1")
                            ->execute(['frontend/assets/images/' . $new_filename]);
                    }
                }
            }

            // Handle banner link
            if (!empty($_POST['banner' . $i . '_link'])) {
                $pdo->prepare("UPDATE settings SET banner{$i}_link = ? WHERE id = 1")
                    ->execute([$_POST['banner' . $i . '_link']]);
            }
        }

        // Refresh settings
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $success_message = "تم تحديث إعدادات المتجر بنجاح";

    } catch (PDOException $e) {
        $error_message = "حدث خطأ في قاعدة البيانات: " . $e->getMessage();
    } catch (Exception $e) {
        $error_message = "حدث خطأ: " . $e->getMessage();
    }
}

// Add debug information at the top of the form
if (isset($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo $error_message; ?>
        <?php if (isset($_FILES)): ?>
            <pre class="mt-2 text-sm">
                <?php print_r($_FILES); ?>
            </pre>
        <?php endif; ?>
    </div>
<?php endif;

// Get current settings
try {
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no settings exist, create default settings
    if (!$settings) {
        $defaultSettings = [
            'store_name' => 'متجر جان كرو',
            'store_description' => 'متجر متخصص في بيع الملابس النسائية',
            'store_email' => 'info@jeancro.com',
            'store_phone' => '0123456789',
            'store_address' => 'العنوان: شارع الرئيسي، المدينة',
            'shipping_policy' => 'سياسة الشحن: يتم الشحن خلال 3-5 أيام عمل',
            'return_policy' => 'سياسة الإرجاع: يمكن إرجاع المنتج خلال 14 يوماً',
            'privacy_policy' => 'سياسة الخصوصية: نحن نحترم خصوصية عملائنا',
            'terms_conditions' => 'الشروط والأحكام: يرجى قراءة الشروط بعناية',
            'facebook_url' => '',
            'instagram_url' => '',
            'twitter_url' => '',
            'logo_url' => null,
            'header_banner_url' => null,
            'header_banner_link' => null,
            'banner1_url' => null,
            'banner2_url' => null,
            'banner3_url' => null,
            'banner1_link' => null,
            'banner2_link' => null,
            'banner3_link' => null
        ];
        
        $stmt = $pdo->prepare("INSERT INTO settings (
            store_name,
            store_description,
            store_email,
            store_phone,
            store_address,
            shipping_policy,
            return_policy,
            privacy_policy,
            terms_conditions,
            facebook_url,
            instagram_url,
            twitter_url,
            logo_url,
            header_banner_url,
            header_banner_link,
            banner1_url,
            banner2_url,
            banner3_url,
            banner1_link,
            banner2_link,
            banner3_link
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $defaultSettings['store_name'],
            $defaultSettings['store_description'],
            $defaultSettings['store_email'],
            $defaultSettings['store_phone'],
            $defaultSettings['store_address'],
            $defaultSettings['shipping_policy'],
            $defaultSettings['return_policy'],
            $defaultSettings['privacy_policy'],
            $defaultSettings['terms_conditions'],
            $defaultSettings['facebook_url'],
            $defaultSettings['instagram_url'],
            $defaultSettings['twitter_url'],
            $defaultSettings['logo_url'],
            $defaultSettings['header_banner_url'],
            $defaultSettings['header_banner_link'],
            $defaultSettings['banner1_url'],
            $defaultSettings['banner2_url'],
            $defaultSettings['banner3_url'],
            $defaultSettings['banner1_link'],
            $defaultSettings['banner2_link'],
            $defaultSettings['banner3_link']
        ]);
        
        $settings = $defaultSettings;
    }
} catch (Exception $e) {
    $error_message = "حدث خطأ أثناء جلب الإعدادات: " . $e->getMessage();
    $settings = [
        'store_name' => '',
        'store_description' => '',
        'store_email' => '',
        'store_phone' => '',
        'store_address' => '',
        'shipping_policy' => '',
        'return_policy' => '',
        'privacy_policy' => '',
        'terms_conditions' => '',
        'facebook_url' => '',
        'instagram_url' => '',
        'twitter_url' => '',
        'logo_url' => null,
        'header_banner_url' => null,
        'header_banner_link' => null,
        'banner1_url' => null,
        'banner2_url' => null,
        'banner3_url' => null,
        'banner1_link' => null,
        'banner2_link' => null,
        'banner3_link' => null
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات المتجر - لوحة الإدارة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="../<?php echo htmlspecialchars($settings['logo_url'] ?? 'frontend/assets/images/logo_6803fc3a55593.jpeg'); ?>" type="image/x-icon">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        .file-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        .file-upload input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .upload-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            background-color: #4F46E5;
            color: white;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .upload-btn:hover {
            background-color: #4338CA;
        }
        .image-preview {
            position: relative;
            margin-top: 1rem;
            display: inline-block;
        }
        .image-preview img {
            max-width: 200px;
            max-height: 200px;
            object-fit: contain;
            border-radius: 0.375rem;
        }
        .delete-btn {
            position: absolute;
            top: -0.5rem;
            right: -0.5rem;
            background-color: #EF4444;
            color: white;
            border-radius: 9999px;
            width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .delete-btn:hover {
            background-color: #DC2626;
        }
        .loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 1.5rem;
            height: 1.5rem;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            transform: translate(-50%, -50%);
        }
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
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
                <a href="categories.php" class="block py-2.5 px-4 hover:bg-gray-700">
                    <i class="fas fa-tags ml-2"></i> الفئات
                </a>
                <a href="settings.php" class="block py-2.5 px-4 bg-blue-600 text-white">
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
                    <h1 class="text-2xl font-bold text-gray-800">إعدادات المتجر</h1>
                </div>
            </header>
            
            <main class="p-6">
                <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="bg-white rounded-lg shadow p-6" enctype="multipart/form-data">
                    <!-- معلومات المتجر الأساسية -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold mb-4">معلومات المتجر الأساسية</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="store_name">
                                    اسم المتجر
                                </label>
                                <input type="text" id="store_name" name="store_name" 
                                       value="<?php echo htmlspecialchars($settings['store_name']); ?>"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="store_description">
                                    وصف المتجر
                                </label>
                                <textarea id="store_description" name="store_description" rows="3"
                                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($settings['store_description']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- معلومات الاتصال -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold mb-4">معلومات الاتصال</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="store_email">
                                    البريد الإلكتروني
                                </label>
                                <input type="email" id="store_email" name="store_email" 
                                       value="<?php echo htmlspecialchars($settings['store_email']); ?>"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="store_phone">
                                    رقم الهاتف
                                </label>
                                <input type="text" id="store_phone" name="store_phone" 
                                       value="<?php echo htmlspecialchars($settings['store_phone']); ?>"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="store_address">
                                    العنوان
                                </label>
                                <input type="text" id="store_address" name="store_address" 
                                       value="<?php echo htmlspecialchars($settings['store_address'] ?? ''); ?>"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                        </div>
                    </div>
                    
                    <!-- سياسات المتجر -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold mb-4">سياسات المتجر</h2>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="shipping_policy">
                                    سياسة الشحن
                                </label>
                                <textarea id="shipping_policy" name="shipping_policy" rows="3"
                                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($settings['shipping_policy']); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="return_policy">
                                    سياسة الإرجاع
                                </label>
                                <textarea id="return_policy" name="return_policy" rows="3"
                                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($settings['return_policy']); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="privacy_policy">
                                    سياسة الخصوصية
                                </label>
                                <textarea id="privacy_policy" name="privacy_policy" rows="3"
                                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($settings['privacy_policy']); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="terms_conditions">
                                    الشروط والأحكام
                                </label>
                                <textarea id="terms_conditions" name="terms_conditions" rows="3"
                                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($settings['terms_conditions']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- روابط التواصل الاجتماعي -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold mb-4">روابط التواصل الاجتماعي</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="facebook_url">
                                    فيسبوك
                                </label>
                                <input type="url" id="facebook_url" name="facebook_url" 
                                       value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="instagram_url">
                                    انستغرام
                                </label>
                                <input type="url" id="instagram_url" name="instagram_url" 
                                       value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="twitter_url">
                                    تويتر
                                </label>
                                <input type="url" id="twitter_url" name="twitter_url" 
                                       value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Store Logo -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold mb-4">شعار المتجر</h2>
                        <div class="flex items-center space-x-4 rtl:space-x-reverse">
                            <div class="image-preview" id="logoPreview">
                            <?php if (!empty($settings['logo_url'])): ?>
                                <img src="../<?php echo htmlspecialchars($settings['logo_url']); ?>" alt="شعار المتجر">
                                <div class="delete-btn" onclick="deleteImage('logo')">
                                    <i class="fas fa-times"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="file-upload">
                                <input type="file" name="logo" accept="image/*" onchange="handleFileUpload(this, 'logo')">
                                <div class="upload-btn">
                                    <i class="fas fa-upload ml-2"></i>
                                    اختر الشعار
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Header Banner -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold mb-4">البانر الرئيسي</h2>
                        <div class="border rounded-lg p-4">
                            <div class="image-preview" id="headerBannerPreview">
                                <?php if (!empty($settings['header_banner_url'])): ?>
                                <img src="../<?php echo htmlspecialchars($settings['header_banner_url']); ?>" alt="البانر الرئيسي">
                                <div class="delete-btn" onclick="deleteImage('header_banner')">
                                    <i class="fas fa-times"></i>
                                </div>
                            <?php endif; ?>
                            </div>
                            <div class="mt-4">
                                <div class="file-upload">
                                    <input type="file" name="header_banner" accept="image/*" onchange="handleFileUpload(this, 'header_banner')">
                                    <div class="upload-btn">
                                        <i class="fas fa-upload ml-2"></i>
                                        اختر البانر الرئيسي
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">
                                    رابط البانر
                                </label>
                                <input type="text" name="header_banner_link" value="<?php echo htmlspecialchars($settings['header_banner_link'] ?? ''); ?>"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                        </div>
                    </div>

                    <!-- البنرات الإعلانية -->
                    <div class="mb-6">
                        <h2 class="text-lg font-bold mb-4">البنرات الإعلانية</h2>
                        <div class="grid grid-cols-1 gap-6">
                            <!-- البنر الأول -->
                            <div class="border rounded-lg p-4">
                                <h3 class="text-md font-bold mb-2">البنر الأول</h3>
                                <div class="image-preview" id="banner1Preview">
                                    <?php if (!empty($settings['banner1_url'])): ?>
                                    <img src="../<?php echo htmlspecialchars($settings['banner1_url']); ?>" alt="البنر الأول">
                                    <div class="delete-btn" onclick="deleteImage('banner1')">
                                        <i class="fas fa-times"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-4">
                                    <div class="file-upload">
                                        <input type="file" name="banner1" accept="image/*" onchange="handleFileUpload(this, 'banner1')">
                                        <div class="upload-btn">
                                            <i class="fas fa-upload ml-2"></i>
                                            اختر البنر الأول
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">
                                        رابط البنر
                                    </label>
                                    <input type="text" name="banner1_link" value="<?php echo htmlspecialchars($settings['banner1_link'] ?? ''); ?>"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                </div>
                            </div>

                            <!-- البنر الثاني -->
                            <div class="border rounded-lg p-4">
                                <h3 class="text-md font-bold mb-2">البنر الثاني</h3>
                                <div class="image-preview" id="banner2Preview">
                                    <?php if (!empty($settings['banner2_url'])): ?>
                                    <img src="../<?php echo htmlspecialchars($settings['banner2_url']); ?>" alt="البنر الثاني">
                                    <div class="delete-btn" onclick="deleteImage('banner2')">
                                        <i class="fas fa-times"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-4">
                                    <div class="file-upload">
                                        <input type="file" name="banner2" accept="image/*" onchange="handleFileUpload(this, 'banner2')">
                                        <div class="upload-btn">
                                            <i class="fas fa-upload ml-2"></i>
                                            اختر البنر الثاني
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">
                                        رابط البنر
                                    </label>
                                    <input type="text" name="banner2_link" value="<?php echo htmlspecialchars($settings['banner2_link'] ?? ''); ?>"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                </div>
                            </div>

                            <!-- البنر الثالث -->
                            <div class="border rounded-lg p-4">
                                <h3 class="text-md font-bold mb-2">البنر الثالث</h3>
                                <div class="image-preview" id="banner3Preview">
                                    <?php if (!empty($settings['banner3_url'])): ?>
                                    <img src="../<?php echo htmlspecialchars($settings['banner3_url']); ?>" alt="البنر الثالث">
                                    <div class="delete-btn" onclick="deleteImage('banner3')">
                                        <i class="fas fa-times"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-4">
                                    <div class="file-upload">
                                        <input type="file" name="banner3" accept="image/*" onchange="handleFileUpload(this, 'banner3')">
                                        <div class="upload-btn">
                                            <i class="fas fa-upload ml-2"></i>
                                            اختر البنر الثالث
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">
                                        رابط البنر
                                    </label>
                                    <input type="text" name="banner3_link" value="<?php echo htmlspecialchars($settings['banner3_link'] ?? ''); ?>"
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            <i class="fas fa-save ml-2"></i>
                            حفظ الإعدادات
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>

<script>
async function handleFileUpload(input, type) {
    const file = input.files[0];
    if (!file) return;

    // التحقق من نوع الملف
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        alert('نوع الملف غير مسموح به. الأنواع المسموحة: JPG, PNG, GIF');
        return;
    }

    // التحقق من حجم الملف (5MB كحد أقصى)
    if (file.size > 5 * 1024 * 1024) {
        alert('حجم الملف كبير جداً. الحد الأقصى هو 5 ميجابايت');
        return;
    }

    // إضافة تأثير التحميل
    const uploadBtn = input.nextElementSibling;
    uploadBtn.classList.add('loading');

    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);
    formData.append('action', 'upload');

    try {
        const response = await fetch('upload_handler.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            // تحديث معاينة الصورة
            updateImagePreview(type, result.file_path);
            showMessage('success', 'تم تحميل الصورة بنجاح');
        } else {
            showMessage('error', result.error);
        }
    } catch (error) {
        showMessage('error', 'حدث خطأ أثناء تحميل الصورة');
        console.error('Upload error:', error);
    } finally {
        uploadBtn.classList.remove('loading');
    }
}

function updateImagePreview(type, filePath) {
    const previewId = type + 'Preview';
    const previewContainer = document.getElementById(previewId);
    
    if (previewContainer) {
        previewContainer.innerHTML = `
            <img src="../${filePath}" alt="${type}">
            <div class="delete-btn" onclick="deleteImage('${type}')">
                <i class="fas fa-times"></i>
            </div>
        `;
    }
}

async function deleteImage(type) {
    if (!confirm('هل أنت متأكد من حذف هذه الصورة؟')) {
        return;
    }

    try {
        const response = await fetch('delete_image.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: type,
                action: 'delete'
            })
        });

        const result = await response.json();
        
        if (result.success) {
            const previewContainer = document.getElementById(type + 'Preview');
            if (previewContainer) {
                previewContainer.innerHTML = '';
            }
            showMessage('success', 'تم حذف الصورة بنجاح');
        } else {
            showMessage('error', result.error);
        }
    } catch (error) {
        showMessage('error', 'حدث خطأ أثناء حذف الصورة');
        console.error('Delete error:', error);
    }
}

function showMessage(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 p-4 rounded-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
    alertDiv.textContent = message;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// تحديث روابط البانرات
async function updateBannerLink(type, link) {
    try {
        const response = await fetch('update_banner_link.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: type,
                link: link,
                action: 'update_link'
            })
        });

        const result = await response.json();
        
        if (result.success) {
            showMessage('success', 'تم تحديث الرابط بنجاح');
        } else {
            showMessage('error', result.error);
        }
    } catch (error) {
        showMessage('error', 'حدث خطأ أثناء تحديث الرابط');
        console.error('Update link error:', error);
    }
}

// إضافة مستمعي الأحداث لحقول الروابط
document.addEventListener('DOMContentLoaded', function() {
    const linkInputs = document.querySelectorAll('input[name$="_link"]');
    linkInputs.forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const type = input.name.replace('_link', '');
                updateBannerLink(type, input.value);
            }, 1000);
        });
    });
});
</script>
</body>
</html>