<?php
// Direct check of products data
require_once __DIR__ . '/../src/settings.php';

echo "<h2>Direct Products Data Check</h2>";

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'haatbazar';

$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    die("<p style='color: red;'>Connection failed: " . $mysqli->connect_error . "</p>");
}

echo "<p style='color: green;'>Connected successfully</p>";

// Check if products table exists
$result = $mysqli->query("SHOW TABLES LIKE 'products'");
if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>Products table exists</p>";
    
    // Count products
    $countResult = $mysqli->query("SELECT COUNT(*) as count FROM products");
    if ($countResult) {
        $row = $countResult->fetch_assoc();
        $count = $row['count'];
        echo "<p><strong>Total products:</strong> " . $count . "</p>";
        
        if ($count > 0) {
            // Check table structure
            echo "<h3>Products Table Structure:</h3>";
            $structure = $mysqli->query("DESCRIBE products");
            if ($structure) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                while ($column = $structure->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><strong>" . $column['Field'] . "</strong></td>";
                    echo "<td>" . $column['Type'] . "</td>";
                    echo "<td>" . $column['Null'] . "</td>";
                    echo "<td>" . $column['Key'] . "</td>";
                    echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
                    echo "<td>" . $column['Extra'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Get sample products
            echo "<h3>Sample Products:</h3>";
            $products = $mysqli->query("SELECT * FROM products LIMIT 3");
            if ($products) {
                echo "<table border='1' cellpadding='5'>";
                // Print headers
                $fields = $products->fetch_fields();
                echo "<tr>";
                foreach ($fields as $field) {
                    echo "<th>" . $field->name . "</th>";
                }
                echo "</tr>";
                
                // Print data
                while ($product = $products->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($product as $value) {
                        echo "<td>" . (is_null($value) ? '<em>null</em>' : htmlspecialchars(substr($value, 0, 50))) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p style='color: orange;'>No products found in database</p>";
        }
    }
} else {
    echo "<p style='color: red;'>Products table does not exist!</p>";
}

$mysqli->close();
?>