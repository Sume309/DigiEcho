<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/settings.php';
require_once __DIR__ . '/../../src/db/MysqliDb.php';

use App\auth\Admin;

// Set JSON content type
header('Content-Type: application/json');

// Check admin authentication
if (!Admin::Check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
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
    
    // Get filters from request
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $dateRange = isset($_GET['dateRange']) ? $_GET['dateRange'] : '';
    
    // Build query conditions
    $whereConditions = [];
    
    if (!empty($status)) {
        $whereConditions[] = "status = '$status'";
    }
    
    if (!empty($dateRange)) {
        switch ($dateRange) {
            case 'today':
                $whereConditions[] = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
    }
    
    // Construct the WHERE clause
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Get recent orders with customer information from actual database structure
    $query = "
        SELECT 
            o.id,
            o.order_number,
            o.total_amount,
            o.status,
            o.payment_status,
            o.payment_method,
            o.created_at,
            COALESCE(
                CONCAT(o.billing_first_name, ' ', o.billing_last_name),
                u.email,
                'Guest Customer'
            ) as customer_name,
            COALESCE(u.email, o.billing_phone, '') as customer_email,
            COALESCE(o.billing_phone, '') as customer_phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        $whereClause
        ORDER BY o.created_at DESC
        LIMIT $limit
    ";
    
    $recentOrders = $db->rawQuery($query);
    
    // Format the orders data
    $formattedOrders = [];
    foreach ($recentOrders as $order) {
        $formattedOrders[] = [
            'id' => (int)$order['id'],
            'order_number' => $order['order_number'] ?: '#' . $order['id'],
            'customer_name' => $order['customer_name'],
            'customer_email' => $order['customer_email'],
            'customer_phone' => $order['customer_phone'],
            'total_amount' => (float)$order['total_amount'],
            'status' => $order['status'],
            'payment_status' => $order['payment_status'] ?: 'pending',
            'payment_method' => $order['payment_method'],
            'created_at' => $order['created_at'],
            'formatted_date' => date('M j, Y g:i A', strtotime($order['created_at'])),
            'time_ago' => getTimeAgo($order['created_at'])
        ];
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $formattedOrders,
        'total' => count($formattedOrders),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    $errorMessage = 'Recent Orders API Error: ' . $e->getMessage();
    error_log($errorMessage);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

// Helper function to get time ago
function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Just now';
    } elseif ($time < 3600) {
        return floor($time / 60) . ' minutes ago';
    } elseif ($time < 86400) {
        return floor($time / 3600) . ' hours ago';
    } elseif ($time < 2592000) {
        return floor($time / 86400) . ' days ago';
    } elseif ($time < 31536000) {
        return floor($time / 2592000) . ' months ago';
    } else {
        return floor($time / 31536000) . ' years ago';
    }
}
?>