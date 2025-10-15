<?php
// Check product status values
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

echo "<h2>Product Status Analysis</h2>";

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    echo "<p>Connected to database successfully</p>";
    
    // Get total product count
    $totalCount = $db->getValue('products', 'COUNT(*)');
    echo "<p><strong>Total products:</strong> " . $totalCount . "</p>";
    
    if ($totalCount > 0) {
        // Get status distribution
        $statusQuery = "SELECT status, COUNT(*) as count FROM products GROUP BY status";
        $statusResults = $db->rawQuery($statusQuery);
        
        echo "<h3>Status Distribution:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Status</th><th>Count</th></tr>";
        foreach ($statusResults as $row) {
            echo "<tr>";
            echo "<td>" . ($row['status'] ?? '<em>null</em>') . "</td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check what the AJAX endpoint would see
        echo "<h3>What the AJAX endpoint sees:</h3>";
        
        // Get total count (unfiltered)
        $totalCountQuery = "SELECT COUNT(*) as total FROM products p";
        $totalCountResult = $db->rawQuery($totalCountQuery);
        $totalRecords = $totalCountResult[0]['total'] ?? 0;
        echo "<p><strong>Total records (unfiltered):</strong> " . $totalRecords . "</p>";
        
        // Get filtered count (no filters)
        $filteredCountQuery = "SELECT COUNT(*) as total FROM products p";
        $filteredResult = $db->rawQuery($filteredCountQuery);
        $filteredRecords = $filteredResult[0]['total'] ?? 0;
        echo "<p><strong>Filtered records (no filters):</strong> " . $filteredRecords . "</p>";
        
        // Get products with JOINs (like the AJAX endpoint does)
        $query = "
            SELECT p.*, c.name as category_name, sc.name as subcategory_name, b.name as brand_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
            LEFT JOIN brands b ON p.brand = b.id
            ORDER BY p.created_at DESC
            LIMIT 0, 5
        ";
        
        $products = $db->rawQuery($query);
        echo "<p><strong>Products retrieved with JOINs:</strong> " . count($products) . "</p>";
        
        if (!empty($products)) {
            echo "<h4>Sample products:</h4>";
            echo "<table border='1' cellpadding='5'>";
            // Headers
            $headers = array_keys($products[0]);
            echo "<tr>";
            foreach ($headers as $header) {
                echo "<th>" . $header . "</th>";
            }
            echo "</tr>";
            
            // Data
            foreach ($products as $product) {
                echo "<tr>";
                foreach ($product as $value) {
                    echo "<td>" . (is_null($value) ? '<em>null</em>' : htmlspecialchars(substr($value, 0, 30))) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Check for any potential issues
        echo "<h3>Potential Issues:</h3>";
        
        // Check for products with null status
        $nullStatusCount = $db->getValue('products', 'COUNT(*)', 'status IS NULL');
        echo "<p><strong>Products with NULL status:</strong> " . $nullStatusCount . "</p>";
        
        // Check for products with empty status
        $emptyStatusCount = $db->getValue('products', 'COUNT(*)', 'status = ""');
        echo "<p><strong>Products with empty status:</strong> " . $emptyStatusCount . "</p>";
        
        // Check for products with invalid status values
        $invalidStatusQuery = "SELECT DISTINCT status FROM products WHERE status NOT IN ('active', 'inactive', 'draft', 'out_of_stock') AND status IS NOT NULL AND status != ''";
        $invalidStatusResults = $db->rawQuery($invalidStatusQuery);
        if (!empty($invalidStatusResults)) {
            echo "<p><strong>Products with invalid status values:</strong></p>";
            echo "<ul>";
            foreach ($invalidStatusResults as $row) {
                echo "<li>'" . $row['status'] . "'</li>";
            }
            echo "</ul>";
        } else {
            echo "<p><strong>All status values are valid</strong></p>";
        }
        
    } else {
        echo "<p>No products found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>