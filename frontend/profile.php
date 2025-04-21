<?php
require_once '../database/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'حدث خطأ في جلب بيانات المستخدم';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        // Check if username is changed and already exists
        if ($username !== $user['username']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $error = 'اسم المستخدم مستخدم بالفعل';
                goto skip_update;
            }
        }

        // Check if email is changed and already exists
        if ($email !== $user['email']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $error = 'البريد الإلكتروني مستخدم بالفعل';
                goto skip_update;
            }
        }

        // Start building update query
        $updates = [];
        $params = [];

        // Add basic fields
        if ($username !== $user['username']) {
            $updates[] = "username = ?";
            $params[] = $username;
        }
        if ($email !== $user['email']) {
            $updates[] = "email = ?";
            $params[] = $email;
        }
        if ($phone !== $user['phone']) {
            $updates[] = "phone = ?";
            $params[] = $phone;
        }
        if ($address !== $user['address']) {
            $updates[] = "address = ?";
            $params[] = $address;
        }

        // Handle password change
        if (!empty($current_password)) {
            if (password_verify($current_password, $user['password'])) {
                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        $error = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل';
                        goto skip_update;
                    }
                    if ($new_password !== $confirm_password) {
                        $error = 'كلمة المرور الجديدة غير متطابقة';
                        goto skip_update;
                    }
                    $updates[] = "password = ?";
                    $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                }
            } else {
                $error = 'كلمة المرور الحالية غير صحيحة';
                goto skip_update;
            }
        }

        // If there are updates to make
        if (!empty($updates)) {
            $params[] = $_SESSION['user_id'];
            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $success = 'تم تحديث البيانات بنجاح';
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error = 'حدث خطأ أثناء تحديث البيانات';
    }
}

skip_update:

// Get store settings
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - <?php echo htmlspecialchars($settings['store_name']); ?></title>
    <link rel="shortcut icon" href="../<?php echo htmlspecialchars($settings['logo_url']); ?>" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
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

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">الملف الشخصي</h1>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="bg-white shadow-md rounded-lg p-6">
                <div class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">اسم المستخدم</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">البريد الإلكتروني</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">رقم الهاتف</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone']); ?>"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">العنوان</label>
                        <textarea id="address" name="address" rows="3"
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>

                    <div class="border-t pt-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">تغيير كلمة المرور</h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700">كلمة المرور الحالية</label>
                                <input type="password" id="current_password" name="current_password"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700">كلمة المرور الجديدة</label>
                                <input type="password" id="new_password" name="new_password"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700">تأكيد كلمة المرور الجديدة</label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            حفظ التغييرات
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>