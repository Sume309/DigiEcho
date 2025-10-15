<?php
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

echo "<h2>Creating Notifications Table</h2>";

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    // Create the notifications table
    $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `type` varchar(50) NOT NULL COMMENT 'new_order, low_stock, system, etc.',
        `is_read` tinyint(1) NOT NULL DEFAULT 0,
        `metadata` text DEFAULT NULL COMMENT 'JSON encoded data with additional information',
        `created_at` datetime NOT NULL,
        `read_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `type` (`type`),
        KEY `is_read` (`is_read`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($db->rawQuery($sql)) {
        echo "<p style='color: green;'>✅ Notifications table created successfully!</p>";
        
        // Test the table by inserting a sample notification
        $testData = [
            'title' => 'System Ready',
            'message' => 'Notification system is now active and ready to track user activities',
            'type' => 'system',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if ($db->insert('notifications', $testData)) {
            echo "<p style='color: green;'>✅ Test notification created successfully!</p>";
        }
        
        echo "<p><a href='notifications.php' class='btn btn-primary'>View Notifications</a></p>";
        echo "<p><a href='index.php' class='btn btn-secondary'>Back to Dashboard</a></p>";
        
    } else {
        throw new Exception('Failed to create table: ' . $db->getLastError());
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
