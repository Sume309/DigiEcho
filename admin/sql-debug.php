<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

echo "<h2>SQL Debug Test</h2>";

try {
    // Test direct SQL
    echo "<h3>Direct SQL Queries:</h3>";
    
    $result1 = $db->rawQuery("SELECT COUNT(*) as count FROM products");
    echo "Total products: " . $result1[0]['count'] . "<br>";
    
    $result2 = $db->rawQuery("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
    echo "Active products: " . $result2[0]['count'] . "<br>";
    
    $result3 = $db->rawQuery("SELECT COUNT(*) as count FROM products WHERE status = 'inactive'");
    echo "Inactive products: " . $result3[0]['count'] . "<br>";
    
    $result4 = $db->rawQuery("SELECT COUNT(*) as count FROM products WHERE is_hot_item = 1");
    echo "Hot items: " . $result4[0]['count'] . "<br>";
    
    $result5 = $db->rawQuery("SELECT COUNT(*) as count FROM products WHERE is_featured = 1");
    echo "Featured products: " . $result5[0]['count'] . "<br>";
    
    echo "<h3>Using getValue method:</h3>";
    
    $total = $db->getValue('products', 'COUNT(*)');
    echo "Total products (getValue): " . ($total ?: 'NULL') . "<br>";
    
    $active = $db->getValue('products', 'COUNT(*)', 'status = "active"');
    echo "Active products (getValue): " . ($active ?: 'NULL') . "<br>";
    
    $inactive = $db->getValue('products', 'COUNT(*)', 'status = "inactive"');
    echo "Inactive products (getValue): " . ($inactive ?: 'NULL') . "<br>";
    
    $hot = $db->getValue('products', 'COUNT(*)', 'is_hot_item = 1');
    echo "Hot items (getValue): " . ($hot ?: 'NULL') . "<br>";
    
    $featured = $db->getValue('products', 'COUNT(*)', 'is_featured = 1');
    echo "Featured products (getValue): " . ($featured ?: 'NULL') . "<br>";
    
    echo "<h3>All Products Data:</h3>";
    $allProducts = $db->get('products', null, ['id', 'name', 'status', 'is_hot_item', 'is_featured']);
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Status</th><th>Hot Item</th><th>Featured</th></tr>";
    foreach ($allProducts as $product) {
        echo "<tr>";
        echo "<td>{$product['id']}</td>";
        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
        echo "<td>{$product['status']}</td>";
        echo "<td>" . ($product['is_hot_item'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ($product['is_featured'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<p><a href="product-management.php" style="padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">Go to Product Management</a></p>