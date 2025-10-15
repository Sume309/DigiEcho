<?php
// Test the AJAX endpoints directly
echo "Testing AJAX Endpoints:\n\n";

// Test create_discount endpoint
echo "1. Testing create_discount endpoint:\n";
$postData = [
    'action' => 'create_discount',
    'name' => 'AJAX Test Discount',
    'description' => 'Testing via direct AJAX call',
    'discount_type' => 'percentage',
    'discount_value' => '20',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+30 days')),
    'is_active' => '1',
    'applies_to' => 'all_products'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/DigiEcho/admin/discounts-management.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Response: $response\n\n";

// Test get_stats endpoint
echo "2. Testing get_stats endpoint:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/DigiEcho/admin/discounts-management.php?action=get_stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Response: $response\n\n";

// Test get_discount endpoint (this should fail if function is missing)
echo "3. Testing get_discount endpoint:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/DigiEcho/admin/discounts-management.php?action=get_discount&discount_id=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Response: $response\n\n";

echo "If get_discount returns 'Invalid action', the handleGetDiscount function is missing!\n";
?>
