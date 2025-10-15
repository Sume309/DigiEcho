<?php
require_once __DIR__ . '/../src/settings.php';

echo "<h2>Creating Notifications Table</h2>";

try {
    $settings = settings();
    $mysqli = new mysqli($settings['hostname'], $settings['user'], $settings['password'], $settings['database']);
    
    if ($mysqli->connect_error) {
        throw new Exception('Connection failed: ' . $mysqli->connect_error);
    }
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Create the notifications table
    $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `type` varchar(50) NOT NULL,
        `is_read` tinyint(1) NOT NULL DEFAULT 0,
        `metadata` text DEFAULT NULL,
        `created_at` datetime NOT NULL,
        `read_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `type` (`type`),
        KEY `is_read` (`is_read`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($mysqli->query($sql)) {
        echo "<p style='color: green;'>✅ Notifications table created successfully!</p>";
        
        // Insert a test notification
        $testSql = "INSERT INTO notifications (title, message, type, created_at) VALUES 
                   ('System Ready', 'Notification system is now active', 'system', NOW())";
        
        if ($mysqli->query($testSql)) {
            echo "<p style='color: green;'>✅ Test notification inserted!</p>";
        }
        
        echo "<p><strong>Next steps:</strong></p>";
        echo "<ul>";
        echo "<li>✅ Notifications table is ready</li>";
        echo "<li>✅ Try logging in/out to see user activity notifications</li>";
        echo "<li>✅ Place an order to see order notifications</li>";
        echo "<li>✅ Register a new user to see registration notifications</li>";
        echo "</ul>";
        
        echo "<p><a href='notifications.php'>View Notifications</a> | <a href='index.php'>Dashboard</a></p>";
        
    } else {
        throw new Exception('Failed to create table: ' . $mysqli->error);
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
