<?php
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

echo "<h2>Products Table Structure Check</h2>";

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    // Check if products table exists
    $tables = $db->rawQuery("SHOW TABLES LIKE 'products'");
    
    if (empty($tables)) {
        echo "<p style='color: red;'>Products table does not exist!</p>";
        exit;
    }
    
    echo "<p style='color: green;'>Products table exists.</p>";
    
    // Get table structure
    $columns = $db->rawQuery("DESCRIBE products");
    
    echo "<h3>Products Table Columns:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check first few records
    $count = $db->getValue('products', 'COUNT(*)');
    echo "<p>Total records in products table: " . $count . "</p>";
    
    if ($count > 0) {
        $products = $db->get('products', 3);
        echo "<h3>First 3 products:</h3>";
        echo "<pre>";
        print_r($products);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>