<?php
require 'vendor/autoload.php';

echo "<h2>Creating Contact Messages Table</h2>";

try {
    $pdo = new PDO('mysql:host=' . settings()['hostname'] . ';dbname=' . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Check if table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'contact_messages'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='color: orange; padding: 10px; border: 1px solid orange; margin: 10px 0;'>⚠️ Table 'contact_messages' already exists!</div>";
    } else {
        // Create table
        $createTableSQL = "
        CREATE TABLE `contact_messages` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL,
          `email` varchar(255) NOT NULL,
          `subject` varchar(255) NOT NULL,
          `message` text NOT NULL,
          `phone` varchar(20) DEFAULT NULL,
          `ip_address` varchar(45) DEFAULT NULL,
          `user_agent` text DEFAULT NULL,
          `status` enum('new','read','replied','archived') NOT NULL DEFAULT 'new',
          `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
          `admin_notes` text DEFAULT NULL,
          `replied_at` datetime DEFAULT NULL,
          `replied_by` int(11) DEFAULT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `status` (`status`),
          KEY `priority` (`priority`),
          KEY `email` (`email`),
          KEY `created_at` (`created_at`),
          KEY `subject` (`subject`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        
        $pdo->exec($createTableSQL);
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>✅ Table 'contact_messages' created successfully!</div>";
    }
    
    echo "<div style='color: blue; padding: 15px; border: 1px solid blue; margin: 20px 0;'>";
    echo "<h3>🎉 Contact Messages System Ready!</h3>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li><a href='contact.php'>Test Contact Form</a></li>";
    echo "<li><a href='admin/messages-all.php'>View Messages in Admin</a> (after creating admin pages)</li>";
    echo "</ul>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>❌ Database error: " . $e->getMessage() . "</div>";
} catch(Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { margin: 20px 0; }
th { background-color: #f0f0f0; }
a { color: #007cba; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
