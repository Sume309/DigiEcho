<?php
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

header('Content-Type: text/plain');

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    // Check if notifications table exists
    $result = $db->rawQuery("SHOW TABLES LIKE 'notifications'");
    
    if (!empty($result)) {
        echo "Notifications table exists\n";
        
        // Show table structure
        $columns = $db->rawQuery("DESCRIBE notifications");
        echo "Table structure:\n";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
        
        // Check if there are any records
        $count = $db->getValue('notifications', 'COUNT(*)');
        echo "Number of notifications: " . $count . "\n";
        
        // Show recent notifications
        if ($count > 0) {
            echo "\nRecent notifications:\n";
            $db->orderBy('created_at', 'DESC');
            $notifications = $db->get('notifications', 5);
            foreach ($notifications as $notification) {
                echo "- [" . $notification['created_at'] . "] " . $notification['title'] . ": " . $notification['message'] . "\n";
            }
        }
    } else {
        echo "Notifications table does not exist\n";
        echo "Creating notifications table...\n";
        
        // Create the notifications table
        $createTableSql = "
            CREATE TABLE IF NOT EXISTS `notifications` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        
        $db->rawQuery($createTableSql);
        echo "Notifications table created successfully\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>