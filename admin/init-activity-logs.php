<?php
/**
 * Initialize Activity Logs Database Table
 * Run this file once to create the activity_logs table
 */

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    // Create activity_logs table
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS `activity_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `user_type` enum('admin','user','guest') DEFAULT 'admin',
            `action` varchar(100) NOT NULL,
            `description` text NOT NULL,
            `type` enum('auth','user','product','order','category','brand','system','payment','inventory','settings') DEFAULT 'system',
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `metadata` json DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_type` (`type`),
            KEY `idx_created_at` (`created_at`),
            KEY `idx_user_type` (`user_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->rawQuery($createTableQuery);
    
    // Insert some sample activity logs
    $sampleActivities = [
        [
            'user_id' => 1,
            'user_type' => 'admin',
            'action' => 'Login',
            'description' => 'Admin logged into the system',
            'type' => 'auth',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'metadata' => json_encode(['login_method' => 'form']),
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'user_id' => 1,
            'user_type' => 'admin',
            'action' => 'User Profile Updated',
            'description' => 'Updated user profile information',
            'type' => 'user',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'metadata' => json_encode(['fields_updated' => ['email', 'phone']]),
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ],
        [
            'user_id' => 1,
            'user_type' => 'admin',
            'action' => 'Product Added',
            'description' => 'Added new product to inventory',
            'type' => 'product',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'metadata' => json_encode(['product_id' => 123, 'category' => 'Electronics']),
            'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
        ],
        [
            'user_id' => 1,
            'user_type' => 'admin',
            'action' => 'Order Status Updated',
            'description' => 'Changed order status from pending to processing',
            'type' => 'order',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'metadata' => json_encode(['order_id' => 'ORD-001', 'old_status' => 'pending', 'new_status' => 'processing']),
            'created_at' => date('Y-m-d H:i:s', strtotime('-15 minutes'))
        ],
        [
            'user_id' => 1,
            'user_type' => 'admin',
            'action' => 'Category Created',
            'description' => 'Created new product category',
            'type' => 'category',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'metadata' => json_encode(['category_name' => 'Home & Garden']),
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 minutes'))
        ],
        [
            'user_id' => 1,
            'user_type' => 'admin',
            'action' => 'Settings Updated',
            'description' => 'Updated site settings and configuration',
            'type' => 'settings',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'metadata' => json_encode(['settings_changed' => ['site_name', 'logo']]),
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
        ]
    ];
    
    foreach ($sampleActivities as $activity) {
        $db->insert('activity_logs', $activity);
    }
    
    echo "✅ Activity logs table created successfully!\n";
    echo "✅ Sample activity data inserted!\n";
    echo "📊 Total activities: " . count($sampleActivities) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
