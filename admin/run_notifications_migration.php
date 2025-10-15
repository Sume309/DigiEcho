<?php
// Run notifications table migration
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

// Database connection using settings
$db = new MysqliDb(
    settings()['hostname'],
    settings()['user'],
    settings()['password'],
    settings()['database']
);

// Read the SQL file
$sql = file_get_contents(__DIR__ . '/../migrations/20240906_create_notifications_table.sql');

// Execute the SQL
if ($db->rawQuery($sql)) {
    echo "Successfully created notifications table.\n";
    
    // Check if the table exists
    $tables = $db->rawQuery('SHOW TABLES LIKE "notifications"');
    if (count($tables) > 0) {
        echo "The notifications table has been created successfully.\n";
    } else {
        echo "Error: The notifications table was not created.\n";
    }
} else {
    echo "Error creating notifications table: " . $db->getLastError() . "\n";
}

echo "You can now close this window and try placing an order again.";
