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

// Get cart items from session if exists
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cart_count = count($cart_items);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['store_name']); ?> - المساعد الذكي</title>
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
        .chat-container {
            height: calc(100vh - 300px);
            min-height: 500px;
        }
        .chat-messages {
            height: calc(100% - 70px);
            overflow-y: auto;
        }
        .user-message {
            background-color: #e2f1ff;
            border-radius: 18px 18px 0 18px;
            padding: 10px 15px;
            max-width: 70%;
            margin-right: auto;
            margin-bottom: 15px;
        }
        .bot-message {
            background-color: #f0f0f0;
            border-radius: 18px 18px 18px 0;
            padding: 10px 15px;
            max-width: 70%;
            margin-left: auto;
            margin-bottom: 15px;
        }
        .typing-indicator {
            display: flex;
            padding: 10px 15px;
            background-color: #f0f0f0;
            border-radius: 18px;
            width: fit-content;
        }
        .typing-indicator span {
            height: 8px;
            width: 8px;
            margin: 0 2px;
            background-color: #9e9ea1;
            border-radius: 50%;
            display: inline-block;
            animation: bounce 1.5s infinite ease-in-out;
        }
        .typing-indicator span:nth-child(1) { animation-delay: 0s; }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes bounce {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-6px); }
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
                    <a href="chatbot.php" class="text-blue-600 font-bold">المساعد الذكي</a>
                    <a href="index.php#about" class="text-gray-700 hover:text-blue-600">من نحن</a>
                    <a href="index.php#contact" class="text-gray-700 hover:text-blue-600">اتصل بنا</a>
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

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8">المساعد الذكي</h1>
        
        <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6 chat-container">
                <div id="chatMessages" class="chat-messages mb-4">
                    <!-- Bot welcome message -->
                    <div class="bot-message">
                        <p>مرحبًا! أنا المساعد الذكي الخاص بـ <?php echo htmlspecialchars($settings['store_name']); ?>. كيف يمكنني مساعدتك اليوم؟</p>
                    </div>
                    
                    <!-- Messages will appear here -->
                </div>
                
                <div id="typingIndicator" class="typing-indicator mb-4" style="display: none;">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                
                <form id="chatForm" class="flex items-center">
                    <input type="text" id="userMessage" class="flex-grow border rounded-l-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           placeholder="اكتب رسالتك هنا..." required>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-r-lg hover:bg-blue-600 transition duration-300">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['store_name']); ?></p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatForm = document.getElementById('chatForm');
            const userMessageInput = document.getElementById('userMessage');
            const chatMessagesContainer = document.getElementById('chatMessages');
            const typingIndicator = document.getElementById('typingIndicator');
            
            // Function to add a new message to the chat
            function addMessage(content, isUser = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = isUser ? 'user-message' : 'bot-message';
                messageDiv.innerHTML = `<p>${content}</p>`;
                chatMessagesContainer.appendChild(messageDiv);
                
                // Scroll to bottom of chat
                chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
            }
            
            // Function to show typing indicator
            function showTypingIndicator() {
                typingIndicator.style.display = 'flex';
                chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
            }
            
            // Function to hide typing indicator
            function hideTypingIndicator() {
                typingIndicator.style.display = 'none';
            }
            
            // Handle form submission
            chatForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const userMessage = userMessageInput.value.trim();
                if (!userMessage) return;
                
                // Add user message to chat
                addMessage(userMessage, true);
                
                // Clear input
                userMessageInput.value = '';
                
                // Show typing indicator
                showTypingIndicator();
                
                try {
                    // Make API call to backend
                    const response = await fetch('../backend/chatbot_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ message: userMessage })
                    });
                    
                    // Parse response
                    const data = await response.json();
                    
                    // Hide typing indicator
                    hideTypingIndicator();
                    
                    // Add bot response to chat
                    if (data.success) {
                        addMessage(data.response);
                    } else {
                        addMessage('عذرًا، حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى لاحقًا.');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    hideTypingIndicator();
                    addMessage('عذرًا، حدث خطأ في الاتصال. يرجى التحقق من اتصالك بالإنترنت والمحاولة مرة أخرى.');
                }
            });
        });
    </script>
</body>
</html>