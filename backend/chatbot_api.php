<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../database/db.php';

// Set JSON content type header
header('Content-Type: application/json');

try {
    // Ensure that the request is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    // Get the raw POST data
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    // Log request data for debugging
    file_put_contents('chatbot_debug.log', "Request data: " . print_r($data, true) . "\n", FILE_APPEND);

    // Validate the request data
    if (!isset($data['message']) || empty($data['message'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => 'Message is required']);
        exit;
    }

    // Fetch products data from database to provide to the chatbot context
    $products = [];
    $categories = [];
    
    try {
        // Get all products with their categories
        $stmt = $pdo->query("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.name ASC
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all categories
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get store settings
        $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Format products data for context
        $productsText = "المنتجات المتوفرة في المتجر:\n";
        foreach ($products as $product) {
            $productsText .= "- " . $product['name'] . " (" . $product['category_name'] . ") - السعر: " . $product['price'] . " درهم - المقاس: " . $product['size'] . "\n";
        }
        
        // Format categories data for context
        $categoriesText = "أقسام المتجر:\n";
        foreach ($categories as $category) {
            $categoriesText .= "- " . $category['name'] . "\n";
        }
        
        // Store information
        $storeInfo = "معلومات المتجر:\n";
        $storeInfo .= "اسم المتجر: " . $settings['store_name'] . "\n";
        $storeInfo .= "وصف المتجر: " . $settings['store_description'] . "\n";
        $storeInfo .= "سياسة الشحن: " . $settings['shipping_policy'] . "\n";
        $storeInfo .= "سياسة الإرجاع: " . $settings['return_policy'] . "\n";
        $storeInfo .= "رقم الهاتف: " . $settings['store_phone'] . "\n";
        $storeInfo .= "البريد الإلكتروني: " . $settings['store_email'] . "\n";
        
    } catch (PDOException $e) {
        // Log database error but continue with chatbot
        file_put_contents('chatbot_error.log', date('Y-m-d H:i:s') . " - Database error: " . $e->getMessage() . "\n", FILE_APPEND);
        $productsText = "لا يمكن جلب بيانات المنتجات حالياً.";
        $categoriesText = "لا يمكن جلب بيانات الأقسام حالياً.";
        $storeInfo = "لا يمكن جلب بيانات المتجر حالياً.";
    }

    // Information about the special crochet service
    $crochetServiceInfo = "معلومات حول خدمة الكروشيه للجاكيتات:
- نقدم خدمة عمل كروشيه مميزة للجاكيتات التي يحضرها العملاء
- سعر الخدمة: 250 درهم
- كيفية الاستفادة من الخدمة:
  1. يمكن للعميل الاتصال بنا على رقم الهاتف المذكور في الموقع للتنسيق
  2. يمكن للعميل إرسال الجاكيت إلى العنوان المذكور في الموقع
  3. يمكن للعميل زيارتنا مباشرة في متجرنا لتقديم الجاكيت
- تتراوح مدة إنجاز الكروشيه بين 3-7 أيام حسب التصميم المطلوب
- يمكن للعملاء اختيار الألوان والتصاميم المفضلة لديهم";

    // Your Gemini API key - replace with your actual API key
    // Ideally, this should be stored in an environment variable or in a secure configuration file
    $apiKey = 'AIzaSyDXX5NTKqx39wn2hb_Nqg3YJ1U5B2uCw4M'; 

    // Check if API key is empty
    if (empty($apiKey)) {
        echo json_encode([
            'success' => false,
            'error' => 'API key is not configured'
        ]);
        exit;
    }

    // Prepare the request to Gemini API
    $message = $data['message'];

    // Define context for the chatbot with products and categories data
    $storeContext = "أنت مساعد للمتجر تساعد العملاء في إيجاد المنتجات والإجابة على أسئلتهم حول المتجر وسياساته. التحدث باللغة العربية هو الافتراضي، ولكن يمكن الرد باللغة الفرنسية إذا سأل العميل بالفرنسية. أجب بإيجاز وبطريقة ودية. 
    
إليك معلومات عن المتجر والمنتجات المتوفرة:

$storeInfo

$categoriesText

$productsText

$crochetServiceInfo

إذا سأل العميل عن منتجات معينة، أخبره بالمنتجات المتوفرة في الفئة ذات الصلة. إذا سأل عن شيء غير موجود في القائمة، اقترح عليه منتجات مشابهة أو اطلب منه التواصل مع خدمة العملاء.

إذا سأل العميل عن خدمة الكروشيه للجاكيتات، قدم له معلومات مفصلة حول كيفية إرسال الجاكيت إلينا والسعر وكل التفاصيل المتعلقة بهذه الخدمة.

إذا سأل العميل عن العروض والتخفيضات، أخبره بالمنتجات المتوفرة وشجعه على تصفح المتجر لمعرفة المزيد.

يمكن للعميل طلب المنتجات عبر الموقع عن طريق إضافتها إلى سلة المشتريات والدفع أونلاين.";

    // Create the request body for Gemini API
    $requestBody = [
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    ["text" => $storeContext . "\n\n" . $message]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "topK" => 40,
            "topP" => 0.95,
            "maxOutputTokens" => 800,
        ]
    ];

    // Updated Gemini API endpoint with gemini-2.0-flash model
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;

    // Log the URL being called (without the API key)
    file_put_contents('chatbot_debug.log', "Calling API URL: https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent\n", FILE_APPEND);

    // Initialize cURL session
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);  // Ensure proper SSL verification
    
    // Add verbose debugging
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('curl_verbose.log', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    // Execute cURL session and get the response
    $response = curl_exec($ch);

    // Log the raw response for debugging
    file_put_contents('chatbot_debug.log', "API Response: " . $response . "\n", FILE_APPEND);

    // Check for cURL errors
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }

    // Get HTTP status code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL session
    curl_close($ch);
    fclose($verbose);

    // Process the response
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        
        // Log parsed response data
        file_put_contents('chatbot_debug.log', "Parsed response: " . print_r($responseData, true) . "\n", FILE_APPEND);
        
        // Extract the response text from the Gemini API response - updated to handle beta API format
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            $responseText = $responseData['candidates'][0]['content']['parts'][0]['text'];
            echo json_encode(['success' => true, 'response' => $responseText]);
        } else {
            throw new Exception('Invalid response format from API: ' . json_encode($responseData));
        }
    } else {
        throw new Exception('API request failed with status code: ' . $httpCode . ', Response: ' . $response);
    }

} catch (Exception $e) {
    // Log the error
    file_put_contents('chatbot_error.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Return error response
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false, 
        'error' => 'An error occurred: ' . $e->getMessage(),
        'debug_tip' => 'Check chatbot_error.log and chatbot_debug.log for more information'
    ]);
}
?>