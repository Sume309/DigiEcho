<?php
// Fix product statuses to ensure they're properly set
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

echo "<h2>Fixing Product Statuses</h2>";

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check if products table has the required columns
    $columns = $db->rawQuery("DESCRIBE products");
    $columnNames = [];
    foreach ($columns as $column) {
        $columnNames[] = $column['Field'];
    }
    
    echo "<h3>Products Table Columns:</h3>";
    echo "<pre>";
    print_r($columnNames);
    echo "</pre>";
    
    // Check current status distribution
    echo "<h3>Current Status Distribution:</h3>";
    $statusQuery = "SELECT status, COUNT(*) as count FROM products GROUP BY status";
    $statusResults = $db->rawQuery($statusQuery);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Status</th><th>Count</th></tr>";
    foreach ($statusResults as $row) {
        echo "<tr>";
        echo "<td>" . ($row['status'] ?? '<em>null</em>') . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Fix product statuses
    echo "<h3>Fixing Product Statuses:</h3>";
    
    // Check if is_active column exists
    $hasIsActive = in_array('is_active', $columnNames);
    $hasStatus = in_array('status', $columnNames);
    
    echo "<p>Has is_active column: " . ($hasIsActive ? 'Yes' : 'No') . "</p>";
    echo "<p>Has status column: " . ($hasStatus ? 'Yes' : 'No') . "</p>";
    
    if ($hasStatus) {
        // Update products with NULL or empty status
        $db->rawQuery("UPDATE products SET status = 'inactive' WHERE status IS NULL OR status = ''");
        echo "<p>✅ Updated products with NULL or empty status to 'inactive'</p>";
        
        // If is_active column exists, update based on that
        if ($hasIsActive) {
            // Update active products with stock
            $db->rawQuery("UPDATE products SET status = 'active' WHERE is_active = 1 AND (stock_quantity > 0 OR stock_quantity IS NULL)");
            echo "<p>✅ Updated active products with stock to 'active'</p>";
            
            // Update active products without stock
            $db->rawQuery("UPDATE products SET status = 'out_of_stock' WHERE is_active = 1 AND stock_quantity = 0");
            echo "<p>✅ Updated active products without stock to 'out_of_stock'</p>";
            
            // Update inactive products
            $db->rawQuery("UPDATE products SET status = 'inactive' WHERE is_active = 0");
            echo "<p>✅ Updated inactive products to 'inactive'</p>";
        } else {
            // If no is_active column, set all products to 'active' by default (except those with 0 stock)
            $db->rawQuery("UPDATE products SET status = 'active' WHERE status != 'out_of_stock' AND (stock_quantity > 0 OR stock_quantity IS NULL)");
            echo "<p>✅ Updated products to 'active' (except out of stock)</p>";
            
            $db->rawQuery("UPDATE products SET status = 'out_of_stock' WHERE stock_quantity = 0");
            echo "<p>✅ Updated products with 0 stock to 'out_of_stock'</p>";
        }
        
        // Verify the update
        echo "<h3>Status Distribution After Fix:</h3>";
        $statusResults = $db->rawQuery($statusQuery);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Status</th><th>Count</th></tr>";
        foreach ($statusResults as $row) {
            echo "<tr>";
            echo "<td>" . ($row['status'] ?? '<em>null</em>') . "</td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>✅ Product Statuses Fixed Successfully!</h3>";
        echo "<p>Your products should now be visible in the product management page.</p>";
        echo "<p><a href='product-management.php'>Go to Product Management</a></p>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ Status column not found in products table</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>