<?php
// Simple test file to verify dashboard functionality
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

header('Content-Type: application/json');

try {
    // Initialize database
    $db = new MysqliDb([
        'host' => settings()['hostname'],
        'username' => settings()['user'],
        'password' => settings()['password'],
        'db' => settings()['database'],
        'port' => 3306
    ]);
    
    // Test the connection
    if (!$db->ping()) {
        throw new Exception('Failed to connect to database');
    }
    
    // Get basic stats
    $totalOrders = $db->getValue('orders', 'COUNT(*)') ?: 0;
    $todayOrders = $db->getValue('orders', 'COUNT(*)', 'DATE(created_at) = CURDATE()') ?: 0;
    $pendingOrders = $db->getValue('orders', 'COUNT(*)', 'status = "pending"') ?: 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_orders' => $totalOrders,
            'today_orders' => $todayOrders,
            'pending_orders' => $pendingOrders
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>