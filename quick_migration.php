<?php
require 'vendor/autoload.php';

echo "Creating team_members table...\n";

try {
    $pdo = new PDO('mysql:host=' . settings()['hostname'] . ';dbname=' . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create table
    $sql = "CREATE TABLE IF NOT EXISTS `team_members` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql);
    echo "✅ Table created successfully!\n";
    
    // Check if data already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM team_members");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert default data
        $stmt = $pdo->prepare("INSERT INTO team_members (name, position, description, image, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        
        $members = [
            ['Ahmed Rahman', 'Chief Executive Officer', 'Leading our company with vision and dedication, Ahmed brings over 10 years of experience in e-commerce and business development.', 'uploads/team/member1.jpg', 1, 1],
            ['Md. Karim Hassan', 'Chief Technology Officer', 'Karim oversees our technical infrastructure and ensures our platform delivers the best user experience with cutting-edge technology.', 'uploads/team/member2.jpg', 2, 1],
            ['Rashid Ahmed', 'Head of Operations', 'Rashid manages our day-to-day operations, ensuring smooth order processing, inventory management, and customer satisfaction.', 'uploads/team/member3.jpg', 3, 1],
            ['Fahim Hasan', 'Marketing Director', 'Fahim leads our marketing initiatives and customer outreach programs, helping us connect with customers across Bangladesh.', 'uploads/team/member4.jpg', 4, 1]
        ];
        
        foreach ($members as $member) {
            $stmt->execute($member);
        }
        
        echo "✅ Default team members inserted successfully!\n";
    } else {
        echo "ℹ️ Team members already exist ($count records)\n";
    }
    
    echo "🎉 Migration completed successfully!\n";
    echo "You can now access: http://localhost/Family-Haat-Bazar/admin/team-all.php\n";
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
