<?php
require 'vendor/autoload.php';

echo "<h2>Creating Chat System Tables</h2>";

try {
    $pdo = new PDO('mysql:host=' . settings()['hostname'] . ';dbname=' . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Database connection successful!</p>";
    
    // Read and execute the migration file
    $migration_file = __DIR__ . '/migrations/20250928_create_chat_system.sql';
    
    if (!file_exists($migration_file)) {
        throw new Exception("Migration file not found: " . $migration_file);
    }
    
    $sql = file_get_contents($migration_file);
    
    // Split SQL statements by semicolon and execute each one
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "<p>Executing: " . substr($statement, 0, 50) . "...</p>";
            $pdo->exec($statement);
        }
    }
    
    // Create uploads directory for chat attachments
    $chat_uploads_dir = __DIR__ . '/uploads/chat';
    if (!file_exists($chat_uploads_dir)) {
        mkdir($chat_uploads_dir, 0755, true);
        echo "<p>✅ Created chat uploads directory</p>";
    }
    
    echo "<div style='color: green; padding: 15px; border: 1px solid green; margin: 20px 0;'>";
    echo "<h3>🎉 Chat System Created Successfully!</h3>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li><a href='index.php'>Test User Chat Interface</a></li>";
    echo "<li><a href='admin/chat-management.php'>Manage Chats in Admin Panel</a></li>";
    echo "<li><a href='admin/live-chat.php'>Live Chat Dashboard</a></li>";
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
