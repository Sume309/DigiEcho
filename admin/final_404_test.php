<?php
// Final 404 debugging script
echo "Content-Type: text/html; charset=utf-8\n\n";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Final 404 Debug Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
    </style>
</head>
<body>
    <h1>Final 404 Error Detection</h1>
    
    <div class="test">
        <h3>Step 1: Check if you can access the discount management page</h3>
        <p><a href="discounts-management.php" target="_blank">Click here to open Discount Management</a></p>
        <p>If this page loads but shows 404 errors in console, continue to Step 2.</p>
    </div>
    
    <div class="test">
        <h3>Step 2: Test individual resources</h3>
        <p>Click each link below. If any show "404 Not Found", that's your problem:</p>
        <ul>
            <li><a href="assets/js/scripts.js" target="_blank">assets/js/scripts.js</a></li>
            <li><a href="assets/css/styles.css" target="_blank">assets/css/styles.css</a></li>
            <li><a href="assets/js/notifications.js" target="_blank">assets/js/notifications.js</a></li>
        </ul>
    </div>
    
    <div class="test">
        <h3>Step 3: Test AJAX endpoints (requires login)</h3>
        <p>These will redirect to login if not authenticated:</p>
        <ul>
            <li><a href="discounts-management.php?action=get_stats" target="_blank">get_stats</a></li>
            <li><a href="discounts-management.php?action=get_discount&discount_id=1" target="_blank">get_discount</a></li>
        </ul>
    </div>
    
    <div class="test">
        <h3>Step 4: Browser Console Instructions</h3>
        <ol>
            <li>Open the discount management page</li>
            <li>Press F12 to open Developer Tools</li>
            <li>Go to the Console tab</li>
            <li>Look for red error messages</li>
            <li>Go to the Network tab</li>
            <li>Refresh the page</li>
            <li>Look for any red/failed requests (status 404)</li>
            <li>Click on the failed request to see the exact URL</li>
        </ol>
    </div>
    
    <div class="test">
        <h3>Step 5: Common 404 Sources Fixed</h3>
        <p>✅ Fixed: js/scripts.js → assets/js/scripts.js</p>
        <p>✅ Added: handleGetDiscount function</p>
        <p>✅ Fixed: Date filter issue</p>
        <p>✅ Added: Accessibility fixes</p>
    </div>
    
    <script>
        console.log('=== 404 DEBUG TEST ===');
        console.log('If you see any 404 errors below, those are the resources causing problems:');
        
        // Test common problematic files
        var testFiles = [
            'assets/js/scripts.js',
            'assets/css/styles.css', 
            'assets/js/notifications.js',
            'js/scripts.js', // old path
            'css/styles.css' // old path
        ];
        
        testFiles.forEach(function(file) {
            fetch(file)
                .then(response => {
                    if (response.status === 404) {
                        console.error('❌ 404 ERROR: ' + file);
                    } else {
                        console.log('✅ OK: ' + file + ' (status: ' + response.status + ')');
                    }
                })
                .catch(error => {
                    console.error('❌ FETCH ERROR: ' + file, error);
                });
        });
    </script>
</body>
</html>
