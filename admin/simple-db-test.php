<?php
// Simple database test script
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

echo "<h2>Database Connection Test</h2>";

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    echo "<p style='color: green;'>Database connection successful!</p>";
    
    // Test if products table exists
    $tables = $db->rawQuery("SHOW TABLES LIKE 'products'");
    
    if (empty($tables)) {
        echo "<p style='color: red;'>Products table does not exist!</p>";
    } else {
        echo "<p style='color: green;'>Products table exists.</p>";
        
        // Count products
        $count = $db->getValue('products', 'COUNT(*)');
        echo "<p>Total products: " . $count . "</p>";
        
        if ($count > 0) {
            // Get first 5 products
            $products = $db->get('products', 5, ['id', 'name', 'sku', 'status', 'stock_quantity']);
            echo "<h3>First 5 products:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>SKU</th><th>Status</th><th>Stock</th></tr>";
            foreach ($products as $product) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($product['id']) . "</td>";
                echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                echo "<td>" . htmlspecialchars($product['sku']) . "</td>";
                echo "<td>" . htmlspecialchars($product['status']) . "</td>";
                echo "<td>" . htmlspecialchars($product['stock_quantity']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No products found in database.</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>