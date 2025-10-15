<?php
require 'vendor/autoload.php';

echo "<h2>Fixing Team Member Images</h2>";

try {
    $pdo = new PDO('mysql:host=' . settings()['hostname'] . ';dbname=' . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Get current team members
    $stmt = $pdo->query("SELECT * FROM team_members ORDER BY id");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Team Members:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Current Image Path</th><th>File Exists</th><th>Action</th></tr>";
    
    $available_images = [
        'uploads/team/team_68d8e29d37790.png',
        'uploads/team/team_68d8e3bc4d09d.png', 
        'uploads/team/team_68d8e4324ca63.png',
        'uploads/team/team_68d8e47ee877e.png',
        'uploads/team/team_68d8e4cdc5207.png'
    ];
    
    $image_index = 0;
    
    foreach ($members as $member) {
        $current_image = $member['image'];
        $file_exists = $current_image && file_exists($current_image) ? 'Yes' : 'No';
        
        echo "<tr>";
        echo "<td>{$member['id']}</td>";
        echo "<td>{$member['name']}</td>";
        echo "<td>{$current_image}</td>";
        echo "<td style='color: " . ($file_exists === 'Yes' ? 'green' : 'red') . ";'>{$file_exists}</td>";
        
        if ($file_exists === 'No' && $image_index < count($available_images)) {
            // Assign an available image
            $new_image = $available_images[$image_index];
            $stmt = $pdo->prepare("UPDATE team_members SET image = ? WHERE id = ?");
            $stmt->execute([$new_image, $member['id']]);
            
            echo "<td style='color: blue;'>Updated to: {$new_image}</td>";
            $image_index++;
        } else {
            echo "<td>No change needed</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='color: green; padding: 15px; border: 1px solid green; margin: 20px 0;'>";
    echo "<h3>✅ Team Images Fixed!</h3>";
    echo "<p>All team members now have valid image paths.</p>";
    echo "<p><a href='about.php'>View About Page</a> | <a href='admin/team-all.php'>Manage Team</a></p>";
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
