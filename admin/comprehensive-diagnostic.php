<?php
/**
 * Comprehensive Diagnostic Script for Product Management Issues
 */
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';
require_once __DIR__ . '/../src/auth/admin.php';

use App\auth\Admin;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Product Management Diagnostic</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>
    <h1 class='mb-4'>Product Management Diagnostic</h1>";

// Test 1: Database Connection
echo "<div class='card mb-3'>
    <div class='card-header bg-primary text-white'>
        <h3>Test 1: Database Connection</h3>
    </div>
    <div class='card-body'>";

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    echo "<div class='alert alert-success'>✅ Database connection successful</div>";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    exit;
}

echo "</div></div>";

// Test 2: Authentication Status
echo "<div class='card mb-3'>
    <div class='card-header bg-primary text-white'>
        <h3>Test 2: Authentication Status</h3>
    </div>
    <div class='card-body'>";

$isAdmin = Admin::Check();
if ($isAdmin) {
    echo "<div class='alert alert-success'>✅ Admin authentication successful</div>";
    echo "<p>Session data:</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "<div class='alert alert-warning'>⚠️ Admin authentication failed</div>";
    echo "<p>Session data:</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

echo "</div></div>";

// Test 3: Products Table Check
echo "<div class='card mb-3'>
    <div class='card-header bg-primary text-white'>
        <h3>Test 3: Products Table Structure</h3>
    </div>
    <div class='card-body'>";

try {
    // Check if products table exists
    $tables = $db->rawQuery("SHOW TABLES LIKE 'products'");
    
    if (empty($tables)) {
        echo "<div class='alert alert-danger'>❌ Products table does not exist!</div>";
    } else {
        echo "<div class='alert alert-success'>✅ Products table exists</div>";
        
        // Get table structure
        $columns = $db->rawQuery("DESCRIBE products");
        echo "<h4>Products Table Columns:</h4>";
        echo "<div class='table-responsive'>
            <table class='table table-bordered table-striped'>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Key</th>
                        <th>Default</th>
                        <th>Extra</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td><strong>" . $column['Field'] . "</strong></td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table></div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error checking products table: " . $e->getMessage() . "</div>";
}

echo "</div></div>";

// Test 4: Product Count and Sample Data
echo "<div class='card mb-3'>
    <div class='card-header bg-primary text-white'>
        <h3>Test 4: Product Data Check</h3>
    </div>
    <div class='card-body'>";

try {
    $count = $db->getValue('products', 'COUNT(*)');
    echo "<p><strong>Total products in database:</strong> " . $count . "</p>";
    
    if ($count > 0) {
        echo "<div class='alert alert-success'>✅ Products found in database</div>";
        
        // Get first 5 products
        $products = $db->get('products', 5);
        echo "<h4>First 5 products:</h4>";
        echo "<div class='table-responsive'>
            <table class='table table-bordered table-striped'>
                <thead>
                    <tr>";
        
        // Get column names from first product
        if (!empty($products)) {
            $firstProduct = $products[0];
            foreach ($firstProduct as $key => $value) {
                echo "<th>" . $key . "</th>";
            }
            echo "</tr></thead><tbody>";
            
            foreach ($products as $product) {
                echo "<tr>";
                foreach ($product as $value) {
                    echo "<td>" . (is_null($value) ? '<em>null</em>' : htmlspecialchars(substr($value, 0, 50))) . "</td>";
                }
                echo "</tr>";
            }
        }
        echo "</tbody></table></div>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ No products found in database</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error fetching product data: " . $e->getMessage() . "</div>";
}

echo "</div></div>";

// Test 5: AJAX Endpoint Test
echo "<div class='card mb-3'>
    <div class='card-header bg-primary text-white'>
        <h3>Test 5: AJAX Endpoint Simulation</h3>
    </div>
    <div class='card-body'>";

try {
    // Simulate the AJAX call
    $_GET['action'] = 'get_products';
    $_GET['draw'] = 1;
    $_GET['start'] = 0;
    $_GET['length'] = 10;
    
    // We'll manually call the function to see what happens
    echo "<p>Simulating AJAX request to get_products...</p>";
    
    // Get total count
    $totalCountQuery = "SELECT COUNT(*) as total FROM products p";
    $totalCountResult = $db->rawQuery($totalCountQuery);
    $totalRecords = $totalCountResult[0]['total'] ?? 0;
    
    echo "<p><strong>Total records in products table:</strong> " . $totalRecords . "</p>";
    
    // Get filtered count (no filters)
    $filteredCountQuery = "SELECT COUNT(*) as total FROM products p";
    $filteredResult = $db->rawQuery($filteredCountQuery);
    $filteredRecords = $filteredResult[0]['total'] ?? 0;
    
    echo "<p><strong>Filtered records (no filters):</strong> " . $filteredRecords . "</p>";
    
    // Get products data
    $query = "
        SELECT p.*, c.name as category_name, sc.name as subcategory_name, b.name as brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
        LEFT JOIN brands b ON p.brand = b.id
        ORDER BY p.created_at DESC
        LIMIT 0, 10
    ";
    
    $products = $db->rawQuery($query);
    
    echo "<p><strong>Products retrieved with JOINs:</strong> " . count($products) . "</p>";
    
    if (!empty($products)) {
        echo "<div class='alert alert-success'>✅ AJAX endpoint would return data</div>";
        echo "<p>Sample product data:</p>";
        echo "<pre>";
        print_r(array_slice($products, 0, 2)); // Show first 2 products
        echo "</pre>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ No products retrieved with JOINs</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error in AJAX simulation: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</div></div>";

// Test 6: Session and Authentication Debug
echo "<div class='card mb-3'>
    <div class='card-header bg-primary text-white'>
        <h3>Test 6: Session and Authentication Debug</h3>
    </div>
    <div class='card-body'>";

echo "<p><strong>Current Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";
echo "<p><strong>Session Save Path:</strong> " . session_save_path() . "</p>";

echo "<h4>Full Session Data:</h4>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h4>Server Variables:</h4>";
echo "<pre>";
print_r([
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
    'PHP_SELF' => $_SERVER['PHP_SELF'] ?? 'N/A',
    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'N/A'
]);
echo "</pre>";

echo "</div></div>";

echo "</div>
</body>
</html>";
?>