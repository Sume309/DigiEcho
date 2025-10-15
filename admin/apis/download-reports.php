<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone to Bangladesh Standard Time
date_default_timezone_set('Asia/Dhaka');

require __DIR__ . '/../../vendor/autoload.php';

use App\auth\Admin;

// Check admin authentication
if (!Admin::Check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Database connection using settings
$db = new MysqliDb(
    settings()['hostname'],
    settings()['user'],
    settings()['password'],
    settings()['database']
);

$reportType = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Debug logging
error_log("Download Reports Debug - Report Type: " . $reportType . ", Format: " . $format);

try {
    switch ($reportType) {
        case 'sales':
            exportSalesReport($db, $format, $startDate, $endDate);
            break;
        case 'stock':
            exportStockReport($db, $format);
            break;
        case 'pending_orders':
            exportOrdersByStatus($db, $format, 'pending');
            break;
        case 'cancelled_orders':
            exportOrdersByStatus($db, $format, 'cancelled');
            break;
        case 'delivered_orders':
            exportOrdersByStatus($db, $format, 'delivered');
            break;
        case 'processing_orders':
            exportOrdersByStatus($db, $format, 'processing');
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid report type: ' . $reportType,
                'received_params' => $_GET,
                'valid_types' => ['sales', 'stock', 'pending_orders', 'cancelled_orders', 'delivered_orders', 'processing_orders']
            ]);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function exportSalesReport($db, $format, $startDate = '', $endDate = '') {
    $filename = 'sales_report_' . date('Y-m-d_H-i-s');
    
    // Build query with date filters
    if ($startDate && $endDate) {
        $db->where('DATE(created_at)', array($startDate, $endDate), 'BETWEEN');
    }
    
    $orders = $db->orderBy('created_at', 'DESC')
        ->get('orders', null, [
            'id', 'order_number', 'user_id', 'status', 'payment_status',
            'subtotal', 'discount_amount', 'tax_amount', 'total_amount', 'created_at'
        ]);

    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['Sales Report']);
        fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
        if ($startDate && $endDate) {
            fputcsv($output, ['Period: ' . $startDate . ' to ' . $endDate]);
        }
        fputcsv($output, []);
        fputcsv($output, ['Order Number', 'Customer', 'Status', 'Payment Status', 'Subtotal', 'Discount', 'Tax', 'Total', 'Date']);
        
        foreach ($orders as $order) {
            $customerName = getCustomerName($db, $order['user_id']);
            fputcsv($output, [
                $order['order_number'],
                $customerName,
                ucfirst($order['status']),
                ucfirst($order['payment_status']),
                '৳' . number_format($order['subtotal'], 2),
                '৳' . number_format($order['discount_amount'], 2),
                '৳' . number_format($order['tax_amount'], 2),
                '৳' . number_format($order['total_amount'], 2),
                date('Y-m-d H:i:s', strtotime($order['created_at']))
            ]);
        }
        
        fclose($output);
    } else {
        // Excel format
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<h2>Sales Report</h2>';
        echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
        if ($startDate && $endDate) {
            echo '<p>Period: ' . $startDate . ' to ' . $endDate . '</p>';
        }
        
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr><th>Order Number</th><th>Customer</th><th>Status</th><th>Payment Status</th><th>Subtotal</th><th>Discount</th><th>Tax</th><th>Total</th><th>Date</th></tr>';
        
        foreach ($orders as $order) {
            $customerName = getCustomerName($db, $order['user_id']);
            echo '<tr>';
            echo '<td>' . htmlspecialchars($order['order_number']) . '</td>';
            echo '<td>' . htmlspecialchars($customerName) . '</td>';
            echo '<td>' . ucfirst($order['status']) . '</td>';
            echo '<td>' . ucfirst($order['payment_status']) . '</td>';
            echo '<td>৳' . number_format($order['subtotal'], 2) . '</td>';
            echo '<td>৳' . number_format($order['discount_amount'], 2) . '</td>';
            echo '<td>৳' . number_format($order['tax_amount'], 2) . '</td>';
            echo '<td>৳' . number_format($order['total_amount'], 2) . '</td>';
            echo '<td>' . date('Y-m-d H:i:s', strtotime($order['created_at'])) . '</td>';
            echo '</tr>';
        }
        echo '</table></body></html>';
    }
}

function exportStockReport($db, $format) {
    $filename = 'stock_report_' . date('Y-m-d_H-i-s');
    
    $products = $db->orderBy('stock_quantity', 'ASC')
        ->get('products', null, [
            'id', 'name', 'sku', 'price', 'stock_quantity', 'category_id', 'brand_id'
        ]);

    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['Stock Report']);
        fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['Product Name', 'SKU', 'Price', 'Stock Quantity', 'Category', 'Brand', 'Status']);
        
        foreach ($products as $product) {
            $category = $db->where('id', $product['category_id'])->getOne('categories', 'name');
            $brand = $db->where('id', $product['brand_id'])->getOne('brands', 'name');
            
            $status = 'In Stock';
            if ($product['stock_quantity'] <= 0) {
                $status = 'Out of Stock';
            } elseif ($product['stock_quantity'] <= 10) {
                $status = 'Low Stock';
            }
            
            fputcsv($output, [
                $product['name'],
                $product['sku'],
                '৳' . number_format($product['price'], 2),
                $product['stock_quantity'],
                $category['name'] ?? 'N/A',
                $brand['name'] ?? 'N/A',
                $status
            ]);
        }
        
        fclose($output);
    } else {
        // Excel format
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<h2>Stock Report</h2>';
        echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
        
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr><th>Product Name</th><th>SKU</th><th>Price</th><th>Stock Quantity</th><th>Category</th><th>Brand</th><th>Status</th></tr>';
        
        foreach ($products as $product) {
            $category = $db->where('id', $product['category_id'])->getOne('categories', 'name');
            $brand = $db->where('id', $product['brand_id'])->getOne('brands', 'name');
            
            $status = 'In Stock';
            $statusColor = '#28a745';
            if ($product['stock_quantity'] <= 0) {
                $status = 'Out of Stock';
                $statusColor = '#dc3545';
            } elseif ($product['stock_quantity'] <= 10) {
                $status = 'Low Stock';
                $statusColor = '#ffc107';
            }
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($product['name']) . '</td>';
            echo '<td>' . htmlspecialchars($product['sku']) . '</td>';
            echo '<td>৳' . number_format($product['price'], 2) . '</td>';
            echo '<td>' . $product['stock_quantity'] . '</td>';
            echo '<td>' . htmlspecialchars($category['name'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($brand['name'] ?? 'N/A') . '</td>';
            echo '<td style="color: ' . $statusColor . '">' . $status . '</td>';
            echo '</tr>';
        }
        echo '</table></body></html>';
    }
}

function exportOrdersByStatus($db, $format, $status) {
    $filename = $status . '_orders_' . date('Y-m-d_H-i-s');
    
    $orders = $db->where('status', $status)
        ->orderBy('created_at', 'DESC')
        ->get('orders', null, [
            'id', 'order_number', 'user_id', 'status', 'payment_status',
            'total_amount', 'created_at', 'updated_at'
        ]);

    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, [ucfirst($status) . ' Orders Report']);
        fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, ['Total Orders: ' . count($orders)]);
        fputcsv($output, []);
        fputcsv($output, ['Order Number', 'Customer', 'Payment Status', 'Total Amount', 'Order Date', 'Last Updated']);
        
        foreach ($orders as $order) {
            $customerName = getCustomerName($db, $order['user_id']);
            fputcsv($output, [
                $order['order_number'],
                $customerName,
                ucfirst($order['payment_status']),
                '৳' . number_format($order['total_amount'], 2),
                date('Y-m-d H:i:s', strtotime($order['created_at'])),
                date('Y-m-d H:i:s', strtotime($order['updated_at']))
            ]);
        }
        
        fclose($output);
    } else {
        // Excel format
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<h2>' . ucfirst($status) . ' Orders Report</h2>';
        echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
        echo '<p>Total Orders: ' . count($orders) . '</p>';
        
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr><th>Order Number</th><th>Customer</th><th>Payment Status</th><th>Total Amount</th><th>Order Date</th><th>Last Updated</th></tr>';
        
        foreach ($orders as $order) {
            $customerName = getCustomerName($db, $order['user_id']);
            echo '<tr>';
            echo '<td>' . htmlspecialchars($order['order_number']) . '</td>';
            echo '<td>' . htmlspecialchars($customerName) . '</td>';
            echo '<td>' . ucfirst($order['payment_status']) . '</td>';
            echo '<td>৳' . number_format($order['total_amount'], 2) . '</td>';
            echo '<td>' . date('Y-m-d H:i:s', strtotime($order['created_at'])) . '</td>';
            echo '<td>' . date('Y-m-d H:i:s', strtotime($order['updated_at'])) . '</td>';
            echo '</tr>';
        }
        echo '</table></body></html>';
    }
}

function getCustomerName($db, $userId) {
    if (!$userId) return 'Guest';
    
    $user = $db->where('id', $userId)->getOne('users', ['email', 'first_name', 'last_name']);
    if (!$user) return 'User #' . $userId;
    
    $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    return $fullName ?: $user['email'] ?: 'User #' . $userId;
}

$db->disconnect();
?>
