<?php
echo "=== COMPREHENSIVE 404 DEBUG ANALYSIS ===\n\n";

// Test 1: Check if main file exists and is accessible
echo "1. TESTING FILE ACCESSIBILITY:\n";
$mainFile = 'discounts-management.php';
if (file_exists($mainFile)) {
    echo "✅ discounts-management.php exists\n";
    $perms = substr(sprintf('%o', fileperms($mainFile)), -4);
    echo "   File permissions: $perms\n";
} else {
    echo "❌ discounts-management.php NOT FOUND\n";
}

// Test 2: Check all possible AJAX endpoints
echo "\n2. TESTING ALL AJAX ENDPOINTS:\n";
$endpoints = [
    'get_discounts',
    'get_discount', 
    'create_discount',
    'update_discount',
    'delete_discount',
    'toggle_discount',
    'get_stats',
    'get_products',
    'get_categories',
    'get_brands'
];

foreach ($endpoints as $endpoint) {
    $url = "http://localhost/DigiEcho/admin/discounts-management.php?action=$endpoint";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $status = ($httpCode == 200) ? "✅" : "❌";
    echo "   $status $endpoint: HTTP $httpCode\n";
    
    if ($httpCode == 404) {
        echo "      ⚠️  404 ERROR FOUND FOR: $endpoint\n";
    }
}

// Test 3: Check for missing CSS/JS files
echo "\n3. TESTING STATIC RESOURCES:\n";
$staticFiles = [
    'css/styles.css',
    'js/scripts.js',
    '../assets/css/bootstrap.min.css',
    '../assets/js/bootstrap.bundle.min.js'
];

foreach ($staticFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists\n";
    } else {
        echo "❌ $file NOT FOUND (potential 404 source)\n";
    }
}

// Test 4: Check for missing images or other assets
echo "\n4. CHECKING FOR COMMON MISSING ASSETS:\n";
$assetPaths = [
    '../assets/images/',
    '../uploads/',
    'images/',
    'css/',
    'js/'
];

foreach ($assetPaths as $path) {
    if (is_dir($path)) {
        echo "✅ Directory exists: $path\n";
    } else {
        echo "❌ Directory missing: $path (potential 404 source)\n";
    }
}

// Test 5: Parse the HTML file for external resources
echo "\n5. SCANNING HTML FOR EXTERNAL RESOURCES:\n";
if (file_exists($mainFile)) {
    $content = file_get_contents($mainFile);
    
    // Find all src and href attributes
    preg_match_all('/(?:src|href)=["\']([^"\']+)["\']/', $content, $matches);
    
    $externalResources = array_unique($matches[1]);
    foreach ($externalResources as $resource) {
        // Skip external URLs (http/https)
        if (strpos($resource, 'http') === 0) continue;
        
        // Check if local resource exists
        $resourcePath = $resource;
        if ($resource[0] !== '/') {
            $resourcePath = $resource;
        }
        
        if (file_exists($resourcePath)) {
            echo "✅ $resource\n";
        } else {
            echo "❌ MISSING: $resource (LIKELY 404 SOURCE)\n";
        }
    }
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. Check browser Developer Tools > Network tab\n";
echo "2. Look for red/failed requests showing exact 404 URLs\n";
echo "3. Any files marked as MISSING above need to be created/fixed\n";
echo "4. Check console for JavaScript errors that might cause 404s\n";
?>
