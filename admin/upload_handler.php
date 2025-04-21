<?php
session_start();
require_once '../database/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    try {
        $upload_dir = '../frontend/assets/images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $type = $_POST['type'];
        $file = $_FILES['file'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed with error code: ' . $file['error']);
        }

        // Validate file type
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowed_extensions));
        }

        // Generate new filename
        $new_filename = $type . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        $db_path = 'frontend/assets/images/' . $new_filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Failed to move uploaded file');
        }

        // Update database based on type
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
                throw new Exception('Invalid file type specified');
        }

        // Update database
        $stmt = $pdo->prepare("UPDATE settings SET $field_name = ? WHERE id = 1");
        $stmt->execute([$db_path]);

        echo json_encode([
            'success' => true,
            'file_path' => $db_path,
            'message' => 'File uploaded successfully'
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