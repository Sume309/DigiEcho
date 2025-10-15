<?php
// Database Migration Script for Brands Table Enhancement
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

$db = new MysqliDb(
    settings()['hostname'], 
    settings()['user'], 
    settings()['password'], 
    settings()['database']
);

echo "<h2>Brand Table Enhancement Migration</h2>";
echo "<p>Adding missing columns for enhanced brand management...</p>";

try {
    // Check if columns exist and add them if missing
    $columnsToAdd = [
        'slug' => "VARCHAR(100) UNIQUE DEFAULT NULL",
        'description' => "TEXT DEFAULT NULL",
        'website' => "VARCHAR(255) DEFAULT NULL",
        'meta_title' => "VARCHAR(255) DEFAULT NULL",
        'meta_description' => "TEXT DEFAULT NULL", 
        'meta_keywords' => "TEXT DEFAULT NULL",
        'sort_order' => "INT(11) DEFAULT 0",
        'is_active' => "TINYINT(1) DEFAULT 1",
        'is_featured' => "TINYINT(1) DEFAULT 0",
        'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    
    $addedColumns = [];
    $existingColumns = [];
    
    foreach ($columnsToAdd as $columnName => $columnDefinition) {
        // Check if column exists
        $checkQuery = "SHOW COLUMNS FROM brands LIKE '$columnName'";
        $result = $db->rawQuery($checkQuery);
        
        if (empty($result)) {
            // Column doesn't exist, add it
            $alterQuery = "ALTER TABLE brands ADD COLUMN $columnName $columnDefinition";
            $db->rawQuery($alterQuery);
            $addedColumns[] = $columnName;
            echo "<p style='color: green;'>✅ Added column: $columnName</p>";
        } else {
            $existingColumns[] = $columnName;
            echo "<p style='color: blue;'>ℹ️ Column $columnName already exists</p>";
        }
    }
    
    // Also change the logo column from BLOB to VARCHAR for better file handling
    $alterLogoQuery = "ALTER TABLE brands MODIFY COLUMN logo VARCHAR(255) DEFAULT NULL";
    try {
        $db->rawQuery($alterLogoQuery);
        echo "<p style='color: green;'>✅ Updated logo column to VARCHAR(255)</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Logo column update: " . $e->getMessage() . "</p>";
    }
    
    // Show final table structure
    $structureQuery = "SHOW COLUMNS FROM brands";
    $structure = $db->rawQuery($structureQuery);
    
    echo "<h3>Updated Brands Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($structure as $column) {
        $highlight = in_array($column['Field'], $addedColumns) ? "style='background-color: #d4edda;'" : "style=''";
        echo "<tr $highlight>";
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
    if (!empty($addedColumns)) {
        echo "<p>✅ Added columns: " . implode(', ', $addedColumns) . "</p>";
    }
    if (!empty($existingColumns)) {
        echo "<p>ℹ️ Existing columns: " . implode(', ', $existingColumns) . "</p>";
    }
    
    echo "<p><strong>Migration completed successfully!</strong></p>";
    
    // Generate sample slugs for existing brands that don't have them
    $brandsWithoutSlugs = $db->rawQuery("SELECT id, name FROM brands WHERE slug IS NULL OR slug = ''");
    if (!empty($brandsWithoutSlugs)) {
        echo "<h4>Generating slugs for existing brands:</h4>";
        foreach ($brandsWithoutSlugs as $brand) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $brand['name'])));
            $slug = trim($slug, '-');
            
            // Ensure unique slug
            $counter = 1;
            $originalSlug = $slug;
            while (true) {
                $checkSlug = $db->rawQuery("SELECT id FROM brands WHERE slug = ? AND id != ?", [$slug, $brand['id']]);
                if (empty($checkSlug)) break;
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $db->where('id', $brand['id']);
            $db->update('brands', ['slug' => $slug]);
            echo "<p>Generated slug '<strong>$slug</strong>' for brand: {$brand['name']}</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='brand-all.php'>← Back to Brand Management</a></p>";
?>