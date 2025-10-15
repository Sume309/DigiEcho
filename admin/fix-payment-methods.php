<?php
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

echo "<h2>Fixing Payment Methods for Existing Orders</h2>";

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    // Update orders with NULL or empty payment methods
    $updateResult1 = $db->rawQuery("UPDATE orders SET payment_method = 'cash' WHERE payment_method IS NULL OR payment_method = ''");
    
    // Update orders with NULL or empty payment status
    $updateResult2 = $db->rawQuery("UPDATE orders SET payment_status = 'pending' WHERE payment_status IS NULL OR payment_status = ''");
    
    // Get count of updated records
    $nullPaymentMethods = $db->rawQuery("SELECT COUNT(*) as count FROM orders WHERE payment_method IS NULL OR payment_method = ''");
    $nullPaymentStatus = $db->rawQuery("SELECT COUNT(*) as count FROM orders WHERE payment_status IS NULL OR payment_status = ''");
    
    echo "<p style='color: green;'>✅ Payment methods updated successfully!</p>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>Orders with NULL payment methods: Fixed</li>";
    echo "<li>Orders with NULL payment status: Fixed</li>";
    echo "<li>Default payment method set to: <strong>Cash</strong></li>";
    echo "<li>Default payment status set to: <strong>Pending</strong></li>";
    echo "</ul>";
    
    // Show sample of updated orders
    $sampleOrders = $db->rawQuery("SELECT id, order_number, payment_method, payment_status FROM orders ORDER BY id DESC LIMIT 10");
    
    echo "<h3>Sample Orders (Latest 10):</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Order Number</th><th>Payment Method</th><th>Payment Status</th></tr>";
    
    foreach ($sampleOrders as $order) {
        echo "<tr>";
        echo "<td>{$order['id']}</td>";
        echo "<td>{$order['order_number']}</td>";
        echo "<td><strong>{$order['payment_method']}</strong></td>";
        echo "<td><strong>{$order['payment_status']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><a href='order-management.php' class='btn btn-primary'>Back to Order Management</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
