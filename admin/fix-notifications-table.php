<?php
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

echo "<h2>Fix Missing Notifications Table</h2>";

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    // Check if notifications table exists
    $tables = $db->rawQuery("SHOW TABLES LIKE 'notifications'");
    
    if (count($tables) > 0) {
        echo "<p style='color: green;'>✅ Notifications table already exists!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Notifications table is missing. Creating it now...</p>";
        
        // Read and execute the migration SQL
        $sqlFile = __DIR__ . '/../migrations/20240906_create_notifications_table.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception("Migration file not found: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        if ($db->rawQuery($sql)) {
            echo "<p style='color: green;'>✅ Successfully created notifications table!</p>";
            
            // Verify the table was created
            $verifyTables = $db->rawQuery("SHOW TABLES LIKE 'notifications'");
            if (count($verifyTables) > 0) {
                echo "<p style='color: green;'>✅ Table verification successful!</p>";
                
                // Show table structure
                $columns = $db->rawQuery("DESCRIBE notifications");
                echo "<h3>Table Structure:</h3>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
                foreach ($columns as $column) {
                    echo "<tr>";
                    echo "<td>{$column['Field']}</td>";
                    echo "<td>{$column['Type']}</td>";
                    echo "<td>{$column['Null']}</td>";
                    echo "<td>{$column['Key']}</td>";
                    echo "<td>{$column['Default']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
            } else {
                echo "<p style='color: red;'>❌ Table creation verification failed!</p>";
            }
        } else {
            throw new Exception("Failed to execute SQL: " . $db->getLastError());
        }
    }
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li>✅ The notifications table is now ready</li>";
    echo "<li>✅ You can now place orders without errors</li>";
    echo "<li>✅ Order notifications will be properly stored</li>";
    echo "</ul>";
    
    echo "<p><a href='../place_order.php' class='btn btn-primary'>Test Order Placement</a></p>";
    echo "<p><a href='notifications.php' class='btn btn-secondary'>View Notifications</a></p>";
    echo "<p><a href='index.php' class='btn btn-outline-secondary'>Back to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>