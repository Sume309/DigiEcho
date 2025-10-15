<?php
/**
 * Migration Script: Add Enhanced Category Fields
 * Adds missing columns for enhanced category management functionality
 */

// Direct database connection for migration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'haatbazar';

// Create mysqli connection
$mysqli = new mysqli($host, $username, $password, $database);

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

try {
    echo "<h2>Category Table Enhancement Migration</h2>";
    echo "<p>Adding missing columns for enhanced category management...</p>";

    // Check if columns already exist before adding them
    $columnsToAdd = [
        'parent_id' => 'INT(11) DEFAULT 0 COMMENT "Parent category ID for hierarchical structure"',
        'meta_title' => 'VARCHAR(200) DEFAULT NULL COMMENT "SEO meta title"',
        'meta_description' => 'TEXT DEFAULT NULL COMMENT "SEO meta description"',
        'meta_keywords' => 'TEXT DEFAULT NULL COMMENT "SEO meta keywords"'
    ];

    $existingColumns = [];
    $result = $mysqli->query("DESCRIBE categories");
    while ($column = $result->fetch_assoc()) {
        $existingColumns[] = $column['Field'];
    }

    $addedColumns = [];
    $skippedColumns = [];

    foreach ($columnsToAdd as $columnName => $columnDefinition) {
        if (!in_array($columnName, $existingColumns)) {
            $sql = "ALTER TABLE categories ADD COLUMN $columnName $columnDefinition";
            
            if ($mysqli->query($sql)) {
                $addedColumns[] = $columnName;
                echo "<p style='color: green;'>✅ Added column: <strong>$columnName</strong></p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to add column <strong>$columnName</strong>: " . $mysqli->error . "</p>";
            }
        } else {
            $skippedColumns[] = $columnName;
            echo "<p style='color: blue;'>ℹ️ Column <strong>$columnName</strong> already exists, skipping</p>";
        }
    }

    // Add foreign key constraint for parent_id if it was added
    if (in_array('parent_id', $addedColumns)) {
        // First check if the constraint already exists
        $constraintCheck = $mysqli->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'categories' 
            AND COLUMN_NAME = 'parent_id' 
            AND CONSTRAINT_NAME != 'PRIMARY'
        ");

        if ($constraintCheck->num_rows == 0) {
            if ($mysqli->query("ALTER TABLE categories ADD FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL")) {
                echo "<p style='color: green;'>✅ Added foreign key constraint for parent_id</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Warning: Could not add foreign key constraint for parent_id: " . $mysqli->error . "</p>";
                echo "<p>This is not critical - the functionality will work without the constraint.</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ Foreign key constraint for parent_id already exists</p>";
        }
    }

    // Show final table structure
    echo "<h3>Updated Categories Table Structure:</h3>";
    $structure = $mysqli->query("DESCRIBE categories");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($column = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h3>Migration Summary:</h3>";
    echo "<p><strong>Added columns:</strong> " . (empty($addedColumns) ? "None" : implode(", ", $addedColumns)) . "</p>";
    echo "<p><strong>Skipped columns:</strong> " . (empty($skippedColumns) ? "None" : implode(", ", $skippedColumns)) . "</p>";
    
    if (!empty($addedColumns)) {
        echo "<p style='color: green; font-weight: bold;'>✅ Migration completed successfully!</p>";
        echo "<p>You can now use the enhanced category management features.</p>";
    } else {
        echo "<p style='color: blue; font-weight: bold;'>ℹ️ No changes were needed - all columns already exist.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Migration failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
} finally {
    $mysqli->close();
}
?>