<?php
require_once '../database/db.php';
session_start();

if (isset($_SESSION['user_id'])) {
    try {
        // Delete user session from database
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Log error if needed
    }
}

// Clear all session data
session_destroy();

// Redirect to home page
header('Location: index.php');
exit;
?>

<link rel="shortcut icon" href="../<?php echo htmlspecialchars($settings['logo_url']); ?>" type="image/x-icon">