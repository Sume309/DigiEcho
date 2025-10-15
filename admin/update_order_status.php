<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    require __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/components/notification_helper.php';
    $db = new \MysqliDb();

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    $allowed = ['pending','processing','shipped','delivered','cancelled','refunded'];
    if ($id <= 0 || !in_array($status, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }

    // Get order details before updating
    $order = $db->where('id', $id)->getOne('orders');
    
    $ok = $db->where('id', $id)->update('orders', [
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    if ($ok) {
        // Notify about order status update
        if ($order) {
            notifyOrderActivity(
                $id, 
                "status updated to {$status}", 
                "Order status changed from {$order['status']} to {$status}",
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? null
            );
        }
        
        echo json_encode(['success' => true, 'message' => 'Status updated']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
}
