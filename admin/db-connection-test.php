<?php
// Direct database connection test
echo "<h2>Direct Database Connection Test</h2>";

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'haatbazar';

echo "<p>Attempting to connect to database: $database on host: $host with user: $user</p>";

// Using mysqli directly
$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    echo "<p style='color: red;'>Connection failed: " . $mysqli->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>Connected successfully to database!</p>";
    
    // Check if products table exists
    $result = $mysqli->query("SHOW TABLES LIKE 'products'");
    
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>Products table exists.</p>";
        
        // Count products
        $countResult = $mysqli->query("SELECT COUNT(*) as count FROM products");
        if ($countResult) {
            $row = $countResult->fetch_assoc();
            $count = $row['count'];
            echo "<p>Total products: " . $count . "</p>";
            
            if ($count > 0) {
                // Get first 5 products
                $productsResult = $mysqli->query("SELECT id, name, sku, status, stock_quantity FROM products LIMIT 5");
                if ($productsResult) {
                    echo "<h3>First 5 products:</h3>";
                    echo "<table border='1' cellpadding='5'>";
                    echo "<tr><th>ID</th><th>Name</th><th>SKU</th><th>Status</th><th>Stock</th></tr>";
                    while ($product = $productsResult->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($product['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($product['sku']) . "</td>";
                        echo "<td>" . htmlspecialchars($product['status']) . "</td>";
                        echo "<td>" . htmlspecialchars($product['stock_quantity']) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } else {
                echo "<p>No products found in database.</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>Products table does not exist!</p>";
    }
    
    $mysqli->close();
}
?>