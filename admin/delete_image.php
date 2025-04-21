<?php
session_start();
require_once '../database/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'delete') {
    try {
        $type = $data['type'];
        
        // Get field name based on type
        $field_name = '';
        switch ($type) {
            case 'logo':
                $field_name = 'logo_url';
                break;
            case 'header_banner':
                $field_name = 'header_banner_url';
                break;
            case 'banner1':
                $field_name = 'banner1_url';
                break;
            case 'banner2':
                $field_name = 'banner2_url';
                break;
            case 'banner3':
                $field_name = 'banner3_url';
                break;
            default:
                throw new Exception('Invalid image type specified');
        }

        // Get current file path
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
            $stmt = $pdo->prepare("UPDATE settings SET $field_name = NULL WHERE id = 1");
            $stmt->execute();

            echo json_encode([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        } else {
            throw new Exception('Image not found');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request'
    ]);
} 