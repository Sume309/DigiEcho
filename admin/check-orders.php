<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

header('Content-Type: text/plain');

try {
    // Initialize database connection
    $db = new MysqliDb([
        'host' => settings()['hostname'],
        'username' => settings()['user'],
        'password' => settings()['password'],
        'db' => settings()['database'],
        'port' => 3306
    ]);

    echo "✅ Successfully connected to database: " . settings()['database'] . "\n\n";

    // Check if orders table exists
    $tables = $db->rawQuery('SHOW TABLES LIKE "orders"');
    if (empty($tables)) {
        die("❌ Error: 'orders' table does not exist in the database.\n");
    }
    echo "✅ 'orders' table exists.\n";

    // Get order count
    $orderCount = $db->getValue('orders', 'COUNT(*)');
    echo "📊 Total orders in database: " . $orderCount . "\n\n";

    if ($orderCount > 0) {
        // Get order status distribution
        echo "📋 Order Status Distribution:\n";
        $statusCounts = $db->rawQuery('SELECT status, COUNT(*) as count FROM orders GROUP BY status');
        
        if (!empty($statusCounts)) {
            foreach ($statusCounts as $status) {
                echo "- " . str_pad(ucfirst($status['status']), 15) . ": " . $status['count'] . " orders\n";
            }
        } else {
            echo "No orders with status information found.\n";
        }
        
        // Show sample order data
        echo "\n🔍 Sample Order Data (5 most recent):\n";
        $db->orderBy('created_at', 'DESC');
        $recentOrders = $db->get('orders', 5);
        
        if (!empty($recentOrders)) {
            foreach ($recentOrders as $order) {
                echo "- Order #" . ($order['order_number'] ?? $order['id']) . 
                     ", Status: " . ($order['status'] ?? 'N/A') .
                     ", Amount: ৳" . ($order['total_amount'] ?? '0.00') . 
                     ", Date: " . ($order['created_at'] ?? 'N/A') . "\n";
            }
        } else {
            echo "No recent orders found.\n";
        }
    } else {
        echo "ℹ️  No orders found in the database.\n";
        
        // Check if we should insert sample data
        echo "\nWould you like to insert sample order data? (y/n): ";
        $handle = fopen ('php://stdin', 'r');
        $line = trim(fgets($handle));
        
        if (strtolower($line) === 'y') {
            echo "\nInserting sample order data...\n";
            
            $sampleData = [
                [
                    'order_number' => 'ORD-' . time() . '-1',
                    'status' => 'pending',
                    'total_amount' => 1250.50,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
                ],
                [
                    'order_number' => 'ORD-' . time() . '-2',
                    'status' => 'processing',
                    'total_amount' => 750.25,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
                ],
                [
                    'order_number' => 'ORD-' . time() . '-3',
                    'status' => 'completed',
                    'total_amount' => 2250.75,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
                ]
            ];
            
            $inserted = 0;
            foreach ($sampleData as $order) {
                if ($db->insert('orders', $order)) {
                    $inserted++;
                    echo "✅ Added order: " . $order['order_number'] . " (Status: " . $order['status'] . ")\n";
                }
            }
            
            echo "\n✅ Successfully inserted $inserted sample orders.\n";
            echo "Refresh the order dashboard to see the changes.\n";
        } else {
            echo "\nNo data was inserted.\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    
    // Check database connection
    if (isset($db) && !$db->ping()) {
        echo "\n⚠️  Database connection failed. Please check your database settings.\n";
        echo "- Host: " . settings()['hostname'] . "\n";
        echo "- Database: " . settings()['database'] . "\n";
        echo "- Username: " . settings()['user'] . "\n";
    }
}
?>
