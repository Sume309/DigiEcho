<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the request
file_put_contents('order_dashboard_debug.log', '[' . date('Y-m-d H:i:s') . '] Request received' . PHP_EOL, FILE_APPEND);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/settings.php';
require_once __DIR__ . '/../../src/db/MysqliDb.php';

use App\auth\Admin;

try {

    // Set JSON content type
    header('Content-Type: application/json');

    // Check admin authentication - temporarily disabled for debugging
    // if (!Admin::Check()) {
    //     http_response_code(401);
    //     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    //     exit;
    // }
    
    // Initialize database
    $db = new MysqliDb([
        'host' => settings()['hostname'],
        'username' => settings()['user'],
        'password' => settings()['password'],
        'db' => settings()['database'],
        'port' => 3306
    ]);
    
    // Test the connection
    if (!$db->ping()) {
        throw new Exception('Failed to connect to database');
    }
    
    // Check if orders table exists
    $tables = $db->rawQuery('SHOW TABLES LIKE "orders"');
    if (empty($tables)) {
        throw new Exception('Orders table does not exist in the database');
    }
    
    // Get comprehensive order statistics with debugging
    $totalOrders = $db->getValue('orders', 'COUNT(*)') ?: 0;
    $completedOrders = $db->getValue('orders', 'COUNT(*)', 'status = "delivered"') ?: 0;
    $pendingOrders = $db->getValue('orders', 'COUNT(*)', 'status = "pending"') ?: 0;
    $processingOrders = $db->getValue('orders', 'COUNT(*)', 'status = "processing"') ?: 0;
    $shippedOrders = $db->getValue('orders', 'COUNT(*)', 'status = "shipped"') ?: 0;
    $cancelledOrders = $db->getValue('orders', 'COUNT(*)', 'status = "cancelled"') ?: 0;
    $refundedOrders = $db->getValue('orders', 'COUNT(*)', 'status = "refunded"') ?: 0;
    
    // Debug: Log the actual counts
    error_log("Order Counts Debug: Total=$totalOrders, Delivered=$completedOrders, Pending=$pendingOrders, Processing=$processingOrders, Shipped=$shippedOrders, Cancelled=$cancelledOrders");
    
    // Also check what statuses actually exist in the database
    $actualStatuses = $db->rawQuery('SELECT status, COUNT(*) as count FROM orders GROUP BY status');
    error_log("Actual statuses in DB: " . json_encode($actualStatuses));
    
    // Get recent orders (last 3 days) instead of just today
    $todayOrders = $db->getValue('orders', 'COUNT(*)', 'DATE(created_at) >= CURDATE() - INTERVAL 3 DAY') ?: 0;
    if ($todayOrders == 0) {
        // If still no recent orders, get last week's orders
        $todayOrders = $db->getValue('orders', 'COUNT(*)', 'DATE(created_at) >= CURDATE() - INTERVAL 7 DAY') ?: 0;
        error_log("No recent orders, using last week's orders: $todayOrders");
    }
    if ($todayOrders == 0) {
        // If still no orders, get last month's orders
        $todayOrders = $db->getValue('orders', 'COUNT(*)', 'DATE(created_at) >= CURDATE() - INTERVAL 30 DAY') ?: 0;
        error_log("No recent orders, using last month's orders: $todayOrders");
    }
    
    $todayRevenue = $db->getValue('orders', 'SUM(total_amount)', 
        'DATE(created_at) >= CURDATE() - INTERVAL 3 DAY AND status IN ("delivered", "processing", "shipped")') ?: 0;
    
    // Debug recent orders
    error_log("Recent Orders Debug: Count=$todayOrders, Revenue=$todayRevenue, Current Date=" . date('Y-m-d'));
    
    // Also check recent orders (last 7 days) for debugging
    $recentOrders = $db->getValue('orders', 'COUNT(*)', 'created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)') ?: 0;
    error_log("Recent Orders (last 7 days): $recentOrders");
    
    // Get yesterday's comparison
    $yesterdayOrders = $db->getValue('orders', 'COUNT(*)', 'DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)') ?: 0;
    $yesterdayRevenue = $db->getValue('orders', 'SUM(total_amount)', 
        'DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND status IN ("delivered", "processing", "shipped")') ?: 0;
    
    // Get last week's data for additional comparisons
    $lastWeekOrders = $db->getValue('orders', 'COUNT(*)', 'DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 7 DAY)') ?: 0;
    $lastWeekPending = $db->getValue('orders', 'COUNT(*)', 'status = "pending" AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 7 DAY)') ?: 0;
    $lastWeekProcessing = $db->getValue('orders', 'COUNT(*)', 'status = "processing" AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 7 DAY)') ?: 0;
    $lastWeekDelivered = $db->getValue('orders', 'COUNT(*)', 'status = "delivered" AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 7 DAY)') ?: 0;
    $lastWeekCancelled = $db->getValue('orders', 'COUNT(*)', 'status = "cancelled" AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 7 DAY)') ?: 0;
    
    // Calculate growth percentages
    $ordersGrowth = $yesterdayOrders > 0 ? (($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100 : 0;
    $revenueGrowth = $yesterdayRevenue > 0 ? (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100 : 0;
    
    // Calculate growth for individual statuses
    $pendingGrowth = $lastWeekPending > 0 ? (($pendingOrders - $lastWeekPending) / $lastWeekPending) * 100 : 0;
    $processingGrowth = $lastWeekProcessing > 0 ? (($processingOrders - $lastWeekProcessing) / $lastWeekProcessing) * 100 : 0;
    $deliveredGrowth = $lastWeekDelivered > 0 ? (($completedOrders - $lastWeekDelivered) / $lastWeekDelivered) * 100 : 0;
    $cancelledGrowth = $lastWeekCancelled > 0 ? (($cancelledOrders - $lastWeekCancelled) / $lastWeekCancelled) * 100 : 0;
    
    // Get payment method distribution
    $paymentMethods = $db->rawQuery(
        'SELECT payment_method, COUNT(*) as count, SUM(total_amount) as total_amount 
         FROM orders 
         WHERE status IN ("delivered", "processing", "shipped") AND payment_method IS NOT NULL AND payment_method != ""
         GROUP BY payment_method'
    );
    
    // Get recent urgent orders (pending for more than 2 days)
    $urgentOrders = $db->getValue('orders', 'COUNT(*)', 
        'status = "pending" AND created_at < DATE_SUB(NOW(), INTERVAL 2 DAY)') ?: 0;
    
    // Get orders needing attention (processing for more than 3 days)
    $attentionOrders = $db->getValue('orders', 'COUNT(*)', 
        'status = "processing" AND created_at < DATE_SUB(NOW(), INTERVAL 3 DAY)') ?: 0;
    
    // Get average order value
    $avgOrderValue = $db->getValue('orders', 'AVG(total_amount)', 
        'status IN ("delivered", "processing", "shipped")') ?: 0;
    
    // Get monthly revenue (current month) - use delivered orders
    $currentMonth = date('Y-m');
    $monthlyRevenue = $db->getValue('orders', 'SUM(total_amount)', 
        "DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth' AND status IN ('delivered', 'processing', 'shipped')") ?: 0;

    // Get order status distribution with correct statuses from database
    $statusDistribution = [
        'pending' => 0,
        'processing' => 0,
        'shipped' => 0,
        'delivered' => 0,
        'cancelled' => 0,
        'refunded' => 0
    ];
    
    try {
        // Get actual status counts from database
        $statusCounts = $db->rawQuery('SELECT status, COUNT(*) as count FROM orders GROUP BY status');
        
        // Update the status distribution with actual counts
        foreach ($statusCounts as $status) {
            $statusName = strtolower(trim($status['status']));
            if (isset($statusDistribution[$statusName])) {
                $statusDistribution[$statusName] = (int)$status['count'];
            }
        }
        
    } catch (Exception $e) {
        // Fallback to individual counts if GROUP BY fails
        $statusDistribution = [
            'pending' => $pendingOrders,
            'processing' => $processingOrders,
            'shipped' => $shippedOrders,
            'delivered' => $completedOrders,
            'cancelled' => $cancelledOrders,
            'refunded' => $refundedOrders
        ];
        error_log('Status distribution error: ' . $e->getMessage());
    }

    // Get daily orders for the last 7 days with proper date handling
    $dailyOrders = [];
    
    // Create array for last 7 days
    $allDays = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $allDays[$date] = [
            'date' => $date,
            'order_count' => 0,
            'revenue' => 0
        ];
    }
    
    // Get actual daily data
    $sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));
    $dailyData = $db->rawQuery(
        "SELECT 
            DATE(created_at) as order_date, 
            COUNT(*) as order_count,
            SUM(CASE WHEN status IN ('delivered', 'processing', 'shipped') THEN total_amount ELSE 0 END) as revenue
        FROM orders 
        WHERE created_at >= ? 
        GROUP BY DATE(created_at) 
        ORDER BY DATE(created_at) ASC",
        [$sevenDaysAgo . ' 00:00:00']
    );
    
    // Merge with actual data
    foreach ($dailyData as $day) {
        $date = $day['order_date'];
        if (isset($allDays[$date])) {
            $allDays[$date]['order_count'] = (int)$day['order_count'];
            $allDays[$date]['revenue'] = (float)$day['revenue'];
        }
    }
    
    // Convert to indexed array
    $dailyOrders = array_values($allDays);

    // Get top selling products with proper joins
    $topProducts = $db->rawQuery(
        "SELECT 
            p.name, 
            p.image, 
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.unit_price) as total_revenue,
            p.sku
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE o.status IN ('delivered', 'processing', 'shipped')
        GROUP BY oi.product_id, p.name, p.image, p.sku
        ORDER BY total_sold DESC
        LIMIT 5"
    );
    
    // Format top products data
    $formattedTopProducts = [];
    foreach ($topProducts as $product) {
        $formattedTopProducts[] = [
            'name' => $product['name'] ?: 'Unknown Product',
            'sku' => $product['sku'] ?: 'N/A',
            'image' => !empty($product['image']) ? $product['image'] : 'assets/img/default-product.png',
            'total_sold' => (int)$product['total_sold'],
            'total_revenue' => (float)$product['total_revenue']
        ];
    }

    // Prepare response data
    $responseData = [
        'total_orders' => $totalOrders,
        'completed_orders' => $completedOrders,
        'pending_orders' => $pendingOrders,
        'processing_orders' => $processingOrders,
        'shipped_orders' => $shippedOrders,
        'cancelled_orders' => $cancelledOrders,
        'refunded_orders' => $refundedOrders,
        'today_orders' => $todayOrders,
        'today_revenue' => $todayRevenue,
        'yesterday_orders' => $yesterdayOrders,
        'yesterday_revenue' => $yesterdayRevenue,
        'orders_growth' => round($ordersGrowth, 2),
        'revenue_growth' => round($revenueGrowth, 2),
        'pending_growth' => round($pendingGrowth, 2),
        'processing_growth' => round($processingGrowth, 2),
        'delivered_growth' => round($deliveredGrowth, 2),
        'cancelled_growth' => round($cancelledGrowth, 2),
        'monthly_revenue' => $monthlyRevenue,
        'avg_order_value' => round($avgOrderValue, 2),
        'urgent_orders' => $urgentOrders,
        'attention_orders' => $attentionOrders,
        'payment_methods' => $paymentMethods,
        'status_distribution' => $statusDistribution,
        'daily_orders' => $dailyOrders,
        'top_products' => $formattedTopProducts,
        'timestamp' => date('Y-m-d H:i:s'),
        'last_updated' => time()
    ];
    
    // Debug: Log the response data
    error_log("API Response Data: " . json_encode($responseData));
    
    // Return comprehensive success response
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
    
} catch (Exception $e) {
    $errorMessage = 'Order Dashboard Stats API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
    error_log($errorMessage);
    
    // Log detailed error information
    $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $errorMessage . PHP_EOL;
    $logMessage .= 'Database: ' . settings()['database'] . PHP_EOL;
    $logMessage .= 'Host: ' . settings()['hostname'] . PHP_EOL;
    $logMessage .= 'PHP Version: ' . phpversion() . PHP_EOL;
    $logMessage .= '----------------------------------------' . PHP_EOL;
    
    file_put_contents('order_dashboard_error.log', $logMessage, FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'database' => settings()['database'],
        'host' => settings()['hostname']
    ]);
}
?>