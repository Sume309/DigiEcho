<?php
// Debug script to test discount creation directly
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

echo "Testing Discount Creation Process:\n\n";

// Test 1: Check if we can insert a basic discount
try {
    $testData = [
        'name' => 'Test Discount ' . date('Y-m-d H:i:s'),
        'description' => 'Test description',
        'discount_type' => 'percentage',
        'discount_value' => 10.00,
        'min_quantity' => 1,
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+30 days')),
        'is_active' => 1,
        'applies_to' => 'all_products',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $discountId = $db->insert('product_discounts', $testData);
    
    if ($discountId) {
        echo "✅ Basic Insert Test: SUCCESS - Created discount ID: $discountId\n";
        
        // Clean up test data
        $db->where('id', $discountId)->delete('product_discounts');
        echo "   (Test data cleaned up)\n\n";
    } else {
        echo "❌ Basic Insert Test: FAILED\n";
        echo "   Error: " . $db->getLastError() . "\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Basic Insert Test: EXCEPTION - " . $e->getMessage() . "\n\n";
}

// Test 2: Check table structure for any missing fields
try {
    $structure = $db->rawQuery("DESCRIBE product_discounts");
    echo "✅ Table Structure Check:\n";
    foreach ($structure as $column) {
        $null = $column['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column['Default'] ? "DEFAULT '{$column['Default']}'" : '';
        echo "   - {$column['Field']}: {$column['Type']} $null $default\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Table Structure Check: FAILED - " . $e->getMessage() . "\n\n";
}

// Test 3: Check if required tables exist
try {
    $tables = ['product_discounts', 'product_discount_relations', 'products', 'categories'];
    foreach ($tables as $table) {
        $result = $db->rawQuery("SHOW TABLES LIKE '$table'");
        if ($result) {
            echo "✅ Table '$table': EXISTS\n";
        } else {
            echo "❌ Table '$table': MISSING\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Table Check: FAILED - " . $e->getMessage() . "\n\n";
}

// Test 4: Simulate the exact POST data that would be sent
echo "Simulating Form Submission:\n";
$_POST = [
    'action' => 'create_discount',
    'name' => 'Test Form Discount',
    'description' => 'Test form description',
    'discount_type' => 'percentage',
    'discount_value' => '15',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+30 days')),
    'is_active' => '1',
    'applies_to' => 'all_products'
];

// Simulate the handleCreateDiscount function logic
try {
    // Validate required fields
    $requiredFields = ['name', 'discount_type', 'discount_value', 'start_date', 'end_date'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Required field '$field' is missing");
        }
    }
    echo "✅ Required Fields Validation: PASSED\n";
    
    // Prepare discount data
    $discountData = [
        'name' => $_POST['name'],
        'description' => $_POST['description'] ?? null,
        'discount_type' => $_POST['discount_type'],
        'discount_value' => floatval($_POST['discount_value']),
        'min_quantity' => !empty($_POST['min_quantity']) ? intval($_POST['min_quantity']) : 1,
        'max_quantity' => !empty($_POST['max_quantity']) ? intval($_POST['max_quantity']) : null,
        'min_order_amount' => !empty($_POST['min_order_amount']) ? floatval($_POST['min_order_amount']) : null,
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'usage_limit' => !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null,
        'applies_to' => $_POST['applies_to'] ?? 'all_products',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    echo "✅ Data Preparation: PASSED\n";
    
    // Try to insert
    $discountId = $db->insert('product_discounts', $discountData);
    
    if ($discountId) {
        echo "✅ Form Simulation Insert: SUCCESS - Created discount ID: $discountId\n";
        
        // Clean up
        $db->where('id', $discountId)->delete('product_discounts');
        echo "   (Test data cleaned up)\n";
    } else {
        echo "❌ Form Simulation Insert: FAILED\n";
        echo "   Error: " . $db->getLastError() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Form Simulation: EXCEPTION - " . $e->getMessage() . "\n";
}

echo "\nDEBUG COMPLETE\n";
?>
