<?php
// Simple AJAX test
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

echo "<h2>Simple AJAX Test</h2>";

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Test basic query
    $count = $db->getValue('products', 'COUNT(*)');
    echo "<p><strong>Total products:</strong> " . $count . "</p>";
    
    if ($count > 0) {
        // Test the exact query used in the AJAX endpoint
        $query = "
            SELECT p.*, c.name as category_name, sc.name as subcategory_name, b.name as brand_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
            LEFT JOIN brands b ON p.brand = b.id
            ORDER BY p.created_at DESC
            LIMIT 0, 10
        ";
        
        echo "<p>Executing query:</p>";
        echo "<pre>" . htmlspecialchars($query) . "</pre>";
        
        $products = $db->rawQuery($query);
        
        echo "<p><strong>Query result - Number of products:</strong> " . count($products) . "</p>";
        
        if (!empty($products)) {
            echo "<h3>First product data:</h3>";
            echo "<pre>";
            print_r($products[0]);
            echo "</pre>";
        }
    } else {
        echo "<p>No products found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>