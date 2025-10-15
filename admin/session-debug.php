<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set the session correctly
$_SESSION['loggedin'] = true;
$_SESSION['role'] = 'admin';
$_SESSION['userid'] = 1;
$_SESSION['username'] = 'admin';

echo "<h2>Session Debug</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

require_once __DIR__ . '/../src/auth/admin.php';
use App\auth\Admin;

echo "<h3>Admin Check Result:</h3>";
var_dump(Admin::Check());

echo "<h3>Test Direct API Call:</h3>";
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

// Test the statistics function directly
$totalResult = $db->rawQuery('SELECT COUNT(*) as count FROM products');
$activeResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE status = "active"');
$inactiveResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE status = "inactive"');
$hotItemsResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE is_hot_item = 1');

echo "Total: " . ($totalResult[0]['count'] ?? 0) . "<br>";
echo "Active: " . ($activeResult[0]['count'] ?? 0) . "<br>";
echo "Inactive: " . ($inactiveResult[0]['count'] ?? 0) . "<br>";
echo "Hot Items: " . ($hotItemsResult[0]['count'] ?? 0) . "<br>";

echo "<p><a href='product-management.php' style='padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Product Management</a></p>";
?>