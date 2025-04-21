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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'update_link') {
    try {
        $type = $data['type'];
        $link = $data['link'];
        
        // Get field name based on type
        $field_name = '';
        switch ($type) {
            case 'header_banner':
                $field_name = 'header_banner_link';
                break;
            case 'banner1':
                $field_name = 'banner1_link';
                break;
            case 'banner2':
                $field_name = 'banner2_link';
                break;
            case 'banner3':
                $field_name = 'banner3_link';
                break;
            default:
                throw new Exception('Invalid banner type specified');
        }

        // Update database
        $stmt = $pdo->prepare("UPDATE settings SET $field_name = ? WHERE id = 1");
        $stmt->execute([$link]);

        echo json_encode([
            'success' => true,
            'message' => 'Link updated successfully'
        ]);

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