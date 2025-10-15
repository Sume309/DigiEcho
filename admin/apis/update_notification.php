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
    
    // Get the notification data from POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'] ?? null;
    $title = $input['title'] ?? '';
    $message = $input['message'] ?? '';
    $type = $input['type'] ?? 'system';
    $metadata = $input['metadata'] ?? null;
    $isRead = $input['is_read'] ?? 0;
    
    // Validate required fields
    if (!$id || empty($title) || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID, title and message are required']);
        exit;
    }
    
    // Validate metadata if provided
    if ($metadata) {
        // Check if it's valid JSON
        $decoded = json_decode($metadata, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Metadata must be valid JSON']);
            exit;
        }
    }
    
    // Check if notification exists
    $db->where('id', $id);
    $existing = $db->getOne('notifications');
    
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Notification not found']);
        exit;
    }
    
    // Update the notification
    $data = [
        'title' => $title,
        'message' => $message,
        'type' => $type,
        'metadata' => $metadata,
        'is_read' => $isRead
    ];
    
    // If marking as read, set read_at timestamp
    if ($isRead == 1 && $existing['is_read'] == 0) {
        $data['read_at'] = date('Y-m-d H:i:s');
    }
    
    $db->where('id', $id);
    $result = $db->update('notifications', $data);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update notification');
    }
    
} catch (Exception $e) {
    error_log('Update Notification API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
?>