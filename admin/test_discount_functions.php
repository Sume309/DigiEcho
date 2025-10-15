<?php
// Test script to verify discount functions work after fixes
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

echo "Testing Discount Management Functions:\n\n";

// Test 1: Check if we can get stats
try {
    $stats = [
        'total' => $db->getValue('product_discounts', 'COUNT(*)'),
        'active' => $db->getValue('product_discounts', 'COUNT(*)', 'is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE()'),
        'upcoming' => $db->getValue('product_discounts', 'COUNT(*)', 'is_active = 1 AND start_date > CURDATE()'),
        'expired' => $db->getValue('product_discounts', 'COUNT(*)', 'end_date < CURDATE()')
    ];
    
    echo "✅ Statistics Test:\n";
    echo "   Total: {$stats['total']}\n";
    echo "   Active: {$stats['active']}\n";
    echo "   Upcoming: {$stats['upcoming']}\n";
    echo "   Expired: {$stats['expired']}\n\n";
} catch (Exception $e) {
    echo "❌ Statistics Test Failed: " . $e->getMessage() . "\n\n";
}

// Test 2: Check if we can get products for dropdown
try {
    $products = $db->get('products', 5, ['id', 'name', 'sku']);
    echo "✅ Products Test: Found " . count($products) . " products\n";
    foreach ($products as $product) {
        echo "   - {$product['name']} ({$product['sku']})\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Products Test Failed: " . $e->getMessage() . "\n\n";
}

// Test 3: Check if we can get categories
try {
    $categories = $db->get('categories', 5, ['id', 'name']);
    echo "✅ Categories Test: Found " . count($categories) . " categories\n";
    foreach ($categories as $category) {
        echo "   - {$category['name']}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Categories Test Failed: " . $e->getMessage() . "\n\n";
}

// Test 4: Check existing discounts
try {
    $discounts = $db->get('product_discounts', 5);
    echo "✅ Existing Discounts Test: Found " . count($discounts) . " discounts\n";
    foreach ($discounts as $discount) {
        echo "   - {$discount['name']} ({$discount['discount_type']}: {$discount['discount_value']})\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Existing Discounts Test Failed: " . $e->getMessage() . "\n\n";
}

echo "Test completed. If all tests show ✅, the database is ready for the discount management system.\n";
echo "Now apply the fixes from complete_discount_fix.php to make the interface fully functional.\n";
?>
