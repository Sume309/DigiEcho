<?php
// Simple migration script to create team_members table
// This can be run without admin authentication

require __DIR__ . '/vendor/autoload.php';

echo "<h2>Creating Team Members Table</h2>";
echo "<p>Database: " . settings()['database'] . "</p>";
echo "<p>Host: " . settings()['hostname'] . "</p>";

try {
    $pdo = new PDO("mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Check if table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'team_members'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='color: orange; padding: 10px; border: 1px solid orange; margin: 10px 0;'>⚠️ Table 'team_members' already exists!</div>";
    } else {
        // Create table
        $createTableSQL = "
        CREATE TABLE `team_members` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL,
          `position` varchar(255) NOT NULL,
          `description` text DEFAULT NULL,
          `image` varchar(255) DEFAULT NULL,
          `email` varchar(255) DEFAULT NULL,
          `phone` varchar(20) DEFAULT NULL,
          `linkedin` varchar(255) DEFAULT NULL,
          `twitter` varchar(255) DEFAULT NULL,
          `facebook` varchar(255) DEFAULT NULL,
          `sort_order` int(11) NOT NULL DEFAULT 0,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `is_active` (`is_active`),
          KEY `sort_order` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        
        $pdo->exec($createTableSQL);
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>✅ Table 'team_members' created successfully!</div>";
        
        // Insert default team members
        $insertSQL = "
        INSERT INTO `team_members` (`name`, `position`, `description`, `image`, `sort_order`, `is_active`) VALUES
        ('Ahmed Rahman', 'Chief Executive Officer', 'Leading our company with vision and dedication, Ahmed brings over 10 years of experience in e-commerce and business development.', 'uploads/team/member1.jpg', 1, 1),
        ('Md. Karim Hassan', 'Chief Technology Officer', 'Karim oversees our technical infrastructure and ensures our platform delivers the best user experience with cutting-edge technology.', 'uploads/team/member2.jpg', 2, 1),
        ('Rashid Ahmed', 'Head of Operations', 'Rashid manages our day-to-day operations, ensuring smooth order processing, inventory management, and customer satisfaction.', 'uploads/team/member3.jpg', 3, 1),
        ('Fahim Hasan', 'Marketing Director', 'Fahim leads our marketing initiatives and customer outreach programs, helping us connect with customers across Bangladesh.', 'uploads/team/member4.jpg', 4, 1);
        ";
        
        $pdo->exec($insertSQL);
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>✅ Default team members inserted successfully!</div>";
    }
    
    // Show current team members
    $stmt = $pdo->query("SELECT * FROM team_members ORDER BY sort_order");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Team Members:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Position</th><th>Status</th><th>Sort Order</th></tr>";
    
    foreach ($members as $member) {
        $status = $member['is_active'] ? 'Active' : 'Inactive';
        echo "<tr>";
        echo "<td>{$member['id']}</td>";
        echo "<td>{$member['name']}</td>";
        echo "<td>{$member['position']}</td>";
        echo "<td>{$status}</td>";
        echo "<td>{$member['sort_order']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='color: blue; padding: 15px; border: 1px solid blue; margin: 20px 0;'>";
    echo "<h3>🎉 Migration Complete!</h3>";
    echo "<p>You can now access:</p>";
    echo "<ul>";
    echo "<li><a href='admin/team-all.php'>Team Management (View All)</a></li>";
    echo "<li><a href='admin/team-add.php'>Add New Team Member</a></li>";
    echo "<li><a href='about.php'>About Page (Frontend)</a></li>";
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
