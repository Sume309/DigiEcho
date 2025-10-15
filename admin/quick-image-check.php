<?php
// Simple test to check database connection and missing images
try {
    require_once __DIR__ . '/../src/settings.php';
    require_once __DIR__ . '/../src/db/MysqliDb.php';
    
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    // Test database connection
    $testQuery = $db->query("SELECT COUNT(*) as count FROM products");
    if (!$testQuery) {
        echo "Database connection failed: " . $db->getLastError();
        exit;
    }
    
    echo "<h2>✅ Database Connected Successfully</h2>";
    echo "<p>Total products in database: " . $testQuery[0]['count'] . "</p>";
    
    // Check for the specific missing images mentioned in your error
    $missingImageNames = [
        '68bbf99ac7ce6_1757149594.webp',
        '68b1e30c98917_1756488460.webp', 
        '68b1e9b9db746_1756490169.png'
    ];
    
    echo "<h3>Checking Specific Missing Images:</h3>";
    $uploadDir = __DIR__ . '/../assets/products/';
    
    foreach ($missingImageNames as $imageName) {
        $imagePath = $uploadDir . $imageName;
        $exists = file_exists($imagePath);
        $status = $exists ? '✅ EXISTS' : '❌ MISSING';
        echo "<p>$imageName: $status</p>";
        
        if (!$exists) {
            // Check if this image is referenced in database
            $products = $db->rawQuery("SELECT id, name FROM products WHERE image = ? OR gallery_images LIKE ?", 
                                    [$imageName, '%' . $imageName . '%']);
            if ($products) {
                echo "<ul>";
                foreach ($products as $product) {
                    echo "<li>Referenced by Product ID: {$product['id']} - {$product['name']}</li>";
                }
                echo "</ul>";
            }
        }
    }
    
    // Quick fix for these specific images
    echo "<h3>Quick Fix Available:</h3>";
    echo "<p><a href='fix-specific-images.php' class='btn btn-primary'>Fix These Missing Images</a></p>";
    echo "<p><a href='product-management.php' class='btn btn-secondary'>Back to Product Management</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>