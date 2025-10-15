<?php
/**
 * Migration Script: Add Enhanced Sub-Category Fields
 * Adds missing columns for enhanced sub-category management functionality
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
    echo "<h2>Sub-Category Table Enhancement Migration</h2>";
    echo "<p>Adding missing columns for enhanced sub-category management...</p>";

    // Check if columns already exist before adding them
    $columnsToAdd = [
        'meta_title' => 'VARCHAR(200) DEFAULT NULL COMMENT "SEO meta title"',
        'meta_description' => 'TEXT DEFAULT NULL COMMENT "SEO meta description"',
        'meta_keywords' => 'TEXT DEFAULT NULL COMMENT "SEO meta keywords"',
        'is_featured' => 'TINYINT(1) DEFAULT 0 COMMENT "Featured sub-category flag"'
    ];

    $existingColumns = [];
    $result = $mysqli->query("DESCRIBE subcategories");
    while ($column = $result->fetch_assoc()) {
        $existingColumns[] = $column['Field'];
    }

    $addedColumns = [];
    $skippedColumns = [];

    foreach ($columnsToAdd as $columnName => $columnDefinition) {
        if (!in_array($columnName, $existingColumns)) {
            $sql = "ALTER TABLE subcategories ADD COLUMN {$columnName} {$columnDefinition}";
            if ($mysqli->query($sql)) {
                echo "<p style='color: green;'>✅ Added column: {$columnName}</p>";
                $addedColumns[] = $columnName;
            } else {
                echo "<p style='color: red;'>❌ Failed to add column {$columnName}: " . $mysqli->error . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ Column {$columnName} already exists</p>";
            $skippedColumns[] = $columnName;
        }
    }

    // Show final table structure
    echo "<h3>Updated Sub-Categories Table Structure:</h3>";
    $structure = $mysqli->query("DESCRIBE subcategories");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($column = $structure->fetch_assoc()) {
        $isNew = in_array($column['Field'], $addedColumns);
        $rowStyle = $isNew ? "background-color: #d4edda;" : "";
        echo "<tr style='{$rowStyle}'>";
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
    echo "<p>✅ Added columns: " . (count($addedColumns) > 0 ? implode(', ', $addedColumns) : 'None') . "</p>";
    echo "<p>ℹ️ Existing columns: " . (count($skippedColumns) > 0 ? implode(', ', $skippedColumns) : 'None') . "</p>";
    echo "<p><strong>Migration completed successfully!</strong></p>";

    // Update any existing subcategories to have default values
    if (count($addedColumns) > 0) {
        echo "<h3>Setting Default Values:</h3>";
        
        // Set default is_featured to 0 for all existing subcategories
        if (in_array('is_featured', $addedColumns)) {
            $updateResult = $mysqli->query("UPDATE subcategories SET is_featured = 0 WHERE is_featured IS NULL");
            if ($updateResult) {
                echo "<p style='color: green;'>✅ Set default is_featured = 0 for existing subcategories</p>";
            }
        }
        
        // Set default meta_title from name for existing subcategories where meta_title is null
        if (in_array('meta_title', $addedColumns)) {
            $updateResult = $mysqli->query("UPDATE subcategories SET meta_title = name WHERE meta_title IS NULL OR meta_title = ''");
            if ($updateResult) {
                echo "<p style='color: green;'>✅ Set default meta_title from name for existing subcategories</p>";
            }
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
} finally {
    $mysqli->close();
}

echo "<hr>";
echo "<p><a href='subcategory-all.php'>← Back to Sub-Category Management</a></p>";
?>