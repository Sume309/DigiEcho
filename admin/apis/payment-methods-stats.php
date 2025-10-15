<?php
require_once __DIR__ . '/../../src/settings.php';
require_once __DIR__ . '/../../src/db/MysqliDb.php';

header('Content-Type: application/json');

// Initialize database connection
$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

try {
    // Get payment methods distribution for the last 30 days
    $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
    
    $db->groupBy('payment_method');
    $db->where('created_at', $thirtyDaysAgo, '>=');
    $paymentMethods = $db->get('orders', null, [
        'payment_method',
        'COUNT(*) as count',
        'SUM(total_amount) as total_amount'
    ]);

    // Format the response
    $response = [
        'success' => true,
        'payment_methods' => []
    ];

    $totalOrders = 0;
    $totalAmount = 0;
    
    // Calculate totals
    foreach ($paymentMethods as $method) {
        $totalOrders += $method['count'];
        $totalAmount += $method['total_amount'];
    }
    
    // Prepare response data with percentages
    foreach ($paymentMethods as $method) {
        $percentage = $totalOrders > 0 ? round(($method['count'] / $totalOrders) * 100, 1) : 0;
        $avgOrderValue = $method['count'] > 0 ? $method['total_amount'] / $method['count'] : 0;
        
        $response['payment_methods'][] = [
            'method' => ucfirst($method['payment_method']),
            'count' => (int)$method['count'],
            'percentage' => $percentage,
            'total_amount' => (float)$method['total_amount'],
            'avg_order_value' => round($avgOrderValue, 2)
        ];
    }
    
    // Sort by count (highest first)
    usort($response['payment_methods'], function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    // Add summary statistics
    $response['summary'] = [
        'total_orders' => $totalOrders,
        'total_amount' => (float)$totalAmount,
        'period' => 'Last 30 days'
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch payment methods statistics: ' . $e->getMessage()
    ]);
}
?>