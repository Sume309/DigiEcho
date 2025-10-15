<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/settings.php';
require_once __DIR__ . '/../../src/db/MysqliDb.php';

use App\auth\Admin;

// Set JSON content type
header('Content-Type: application/json');

// Check admin authentication
if (!Admin::Check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Initialize database
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    // Get the notification ID from POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
        exit;
    }
    
    // Check if notification exists
    $db->where('id', $id);
    $existing = $db->getOne('notifications');
    
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Notification not found']);
        exit;
    }
    
    // Delete the notification
    $db->where('id', $id);
    $result = $db->delete('notifications');
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete notification');
    }
    
} catch (Exception $e) {
    error_log('Delete Notification API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
?>