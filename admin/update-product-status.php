<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

echo "<h2>Product Status Update</h2>";

try {
    // Get all products
    $products = $db->get('products', null, ['id', 'name', 'status', 'is_hot_item', 'is_featured']);
    
    echo "<h3>Current Products:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Status</th><th>Hot Item</th><th>Featured</th></tr>";
    
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>{$product['id']}</td>";
        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
        echo "<td>{$product['status']}</td>";
        echo "<td>" . ($product['is_hot_item'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ($product['is_featured'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Update some products to have correct status
    if (count($products) > 0) {
        echo "<h3>Updating Product Statuses...</h3>";
        
        // Update first product to be active
        if (isset($products[0])) {
            $db->where('id', $products[0]['id']);
            $db->update('products', ['status' => 'active']);
            echo "Updated product ID {$products[0]['id']} to active<br>";
        }
        
        // Update second product to be active and hot item
        if (isset($products[1])) {
            $db->where('id', $products[1]['id']);
            $db->update('products', ['status' => 'active', 'is_hot_item' => 1]);
            echo "Updated product ID {$products[1]['id']} to active and hot item<br>";
        }
        
        // Update third product to be inactive
        if (isset($products[2])) {
            $db->where('id', $products[2]['id']);
            $db->update('products', ['status' => 'inactive']);
            echo "Updated product ID {$products[2]['id']} to inactive<br>";
        }
        
        // Update fourth product to be featured
        if (isset($products[3])) {
            $db->where('id', $products[3]['id']);
            $db->update('products', ['status' => 'active', 'is_featured' => 1]);
            echo "Updated product ID {$products[3]['id']} to active and featured<br>";
        }
        
        echo "<h3>After Updates:</h3>";
        
        // Test statistics
        $totalProducts = $db->getValue('products', 'COUNT(*)') ?: 0;
        $activeProducts = $db->getValue('products', 'COUNT(*)', 'status = "active"') ?: 0;
        $inactiveProducts = $db->getValue('products', 'COUNT(*)', 'status = "inactive" OR status = "draft"') ?: 0;
        $hotItems = $db->getValue('products', 'COUNT(*)', 'is_hot_item = 1') ?: 0;
        $featuredProducts = $db->getValue('products', 'COUNT(*)', 'is_featured = 1') ?: 0;
        
        echo "Total Products: <strong>$totalProducts</strong><br>";
        echo "Active Products: <strong>$activeProducts</strong><br>";
        echo "Inactive Products: <strong>$inactiveProducts</strong><br>";
        echo "Hot Items: <strong>$hotItems</strong><br>";
        echo "Featured Products: <strong>$featuredProducts</strong><br>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
}
?>

<p><a href="product-management.php" style="padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">Go to Product Management</a></p>