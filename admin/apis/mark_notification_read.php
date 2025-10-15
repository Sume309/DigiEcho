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
    $notificationId = $input['id'] ?? null;
    
    if (!$notificationId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
        exit;
    }
    
    // Update the notification as read
    $db->where('id', $notificationId);
    $result = $db->update('notifications', [
        'is_read' => 1,
        'read_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Notification not found'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Mark Notification Read API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
?>