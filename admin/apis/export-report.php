<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone to Bangladesh Standard Time
date_default_timezone_set('Asia/Dhaka');

require __DIR__ . '/../../vendor/autoload.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
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

$format = $_GET['format'] ?? 'csv';

try {
    switch ($format) {
        case 'excel':
            exportExcel($db);
            break;
        case 'pdf':
            exportPDF($db);
            break;
        case 'csv':
        default:
            exportCSV($db);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function exportCSV($db) {
    $filename = 'dashboard_report_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    $output = fopen('php://output', 'w');
    
    // Dashboard Summary
    fputcsv($output, ['Dashboard Summary Report']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Statistics
    fputcsv($output, ['Statistics']);
    fputcsv($output, ['Metric', 'Value']);
    
    $totalUsers = $db->getValue('users', 'COUNT(*)') ?: 0;
    $totalProducts = $db->getValue('products', 'COUNT(*)') ?: 0;
    $totalCategories = $db->getValue('categories', 'COUNT(*)') ?: 0;
    $totalBrands = $db->getValue('brands', 'COUNT(*)') ?: 0;
    $pendingOrders = $db->where('status', 'pending')->getValue('orders', 'COUNT(*)') ?: 0;
    $lowStockItems = $db->where('stock_quantity', 10, '<=')->getValue('products', 'COUNT(*)') ?: 0;
    
    $today = date('Y-m-d');
    $todaysSales = $db->where('DATE(created_at)', $today)
        ->where('status', 'completed')
        ->getValue('orders', 'SUM(total_amount)') ?: 0;
    
    $currentMonth = date('Y-m');
    $monthlyRevenue = $db->where('DATE_FORMAT(created_at, "%Y-%m")', $currentMonth)
        ->where('status', 'completed')
        ->getValue('orders', 'SUM(total_amount)') ?: 0;
    
    fputcsv($output, ['Total Users', $totalUsers]);
    fputcsv($output, ['Total Products', $totalProducts]);
    fputcsv($output, ['Total Categories', $totalCategories]);
    fputcsv($output, ['Total Brands', $totalBrands]);
    fputcsv($output, ['Pending Orders', $pendingOrders]);
    fputcsv($output, ['Low Stock Items', $lowStockItems]);
    fputcsv($output, ['Today\'s Sales', '৳' . number_format($todaysSales, 2)]);
    fputcsv($output, ['Monthly Revenue', '৳' . number_format($monthlyRevenue, 2)]);
    
    fputcsv($output, []);
    
    // Recent Orders
    fputcsv($output, ['Recent Orders']);
    fputcsv($output, ['Order Number', 'Customer', 'Status', 'Payment Status', 'Total Amount', 'Date']);
    
    $recentOrders = $db->orderBy('created_at', 'DESC')
        ->get('orders', 20, [
            'id', 'order_number', 'user_id', 'status',
            'payment_status', 'total_amount', 'created_at',
        ]);
    
    foreach ($recentOrders as $order) {
        $customerName = 'Guest';
        if ($order['user_id']) {
            $user = $db->where('id', $order['user_id'])->getOne('users', ['email', 'first_name', 'last_name']);
            $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $customerName = $fullName ?: $user['email'] ?: 'User #' . $order['user_id'];
        }
        
        fputcsv($output, [
            $order['order_number'],
            $customerName,
            ucfirst($order['status']),
            ucfirst($order['payment_status']),
            '৳' . number_format($order['total_amount'], 2),
            date('Y-m-d H:i:s', strtotime($order['created_at']))
        ]);
    }
    
    fputcsv($output, []);
    
    // Low Stock Products
    fputcsv($output, ['Low Stock Products']);
    fputcsv($output, ['Product Name', 'SKU', 'Stock Quantity']);
    
    $lowStockProducts = $db->where('stock_quantity', 10, '<=')
        ->orderBy('stock_quantity', 'ASC')
        ->get('products', null, 'name, sku, stock_quantity');
    
    foreach ($lowStockProducts as $product) {
        fputcsv($output, [
            $product['name'],
            $product['sku'],
            $product['stock_quantity']
        ]);
    }
    
    fclose($output);
}

function exportExcel($db) {
    // For Excel export, we'll create a simple HTML table that Excel can open
    $filename = 'dashboard_report_' . date('Y-m-d_H-i-s') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<h2>Dashboard Summary Report</h2>';
    echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    
    // Statistics Table
    echo '<h3>Statistics</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>Metric</th><th>Value</th></tr>';
    
    $totalUsers = $db->getValue('users', 'COUNT(*)') ?: 0;
    $totalProducts = $db->getValue('products', 'COUNT(*)') ?: 0;
    $totalCategories = $db->getValue('categories', 'COUNT(*)') ?: 0;
    $totalBrands = $db->getValue('brands', 'COUNT(*)') ?: 0;
    $pendingOrders = $db->where('status', 'pending')->getValue('orders', 'COUNT(*)') ?: 0;
    $lowStockItems = $db->where('stock_quantity', 10, '<=')->getValue('products', 'COUNT(*)') ?: 0;
    
    $today = date('Y-m-d');
    $todaysSales = $db->where('DATE(created_at)', $today)
        ->where('status', 'completed')
        ->getValue('orders', 'SUM(total_amount)') ?: 0;
    
    $currentMonth = date('Y-m');
    $monthlyRevenue = $db->where('DATE_FORMAT(created_at, "%Y-%m")', $currentMonth)
        ->where('status', 'completed')
        ->getValue('orders', 'SUM(total_amount)') ?: 0;
    
    echo '<tr><td>Total Users</td><td>' . $totalUsers . '</td></tr>';
    echo '<tr><td>Total Products</td><td>' . $totalProducts . '</td></tr>';
    echo '<tr><td>Total Categories</td><td>' . $totalCategories . '</td></tr>';
    echo '<tr><td>Total Brands</td><td>' . $totalBrands . '</td></tr>';
    echo '<tr><td>Pending Orders</td><td>' . $pendingOrders . '</td></tr>';
    echo '<tr><td>Low Stock Items</td><td>' . $lowStockItems . '</td></tr>';
    echo '<tr><td>Today\'s Sales</td><td>$' . number_format($todaysSales, 2) . '</td></tr>';
    echo '<tr><td>Monthly Revenue</td><td>$' . number_format($monthlyRevenue, 2) . '</td></tr>';
    echo '</table><br>';
    
    // Recent Orders Table
    echo '<h3>Recent Orders</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>Order Number</th><th>Customer</th><th>Status</th><th>Payment Status</th><th>Total Amount</th><th>Date</th></tr>';
    
    $recentOrders = $db->orderBy('created_at', 'DESC')
        ->get('orders', 20, [
            'id', 'order_number', 'user_id', 'status',
            'payment_status', 'total_amount', 'created_at',
        ]);
    
    foreach ($recentOrders as $order) {
        $customerName = 'Guest';
        if ($order['user_id']) {
            $user = $db->where('id', $order['user_id'])->getOne('users', ['email', 'first_name', 'last_name']);
            $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $customerName = $fullName ?: $user['email'] ?: 'User #' . $order['user_id'];
        }
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($order['order_number']) . '</td>';
        echo '<td>' . htmlspecialchars($customerName) . '</td>';
        echo '<td>' . ucfirst($order['status']) . '</td>';
        echo '<td>' . ucfirst($order['payment_status']) . '</td>';
        echo '<td>৳' . number_format($order['total_amount'], 2) . '</td>';
        echo '<td>' . date('Y-m-d H:i:s', strtotime($order['created_at'])) . '</td>';
        echo '</tr>';
    }
    echo '</table><br>';
    
    // Low Stock Products
    echo '<h3>Low Stock Products</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>Product Name</th><th>SKU</th><th>Stock Quantity</th></tr>';
    
    $lowStockProducts = $db->where('stock_quantity', 10, '<=')
        ->orderBy('stock_quantity', 'ASC')
        ->get('products', null, 'name, sku, stock_quantity');
    
    foreach ($lowStockProducts as $product) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($product['name']) . '</td>';
        echo '<td>' . htmlspecialchars($product['sku']) . '</td>';
        echo '<td>' . $product['stock_quantity'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '</body></html>';
}

function exportPDF($db) {
    // Simple PDF export using HTML to PDF conversion
    $filename = 'dashboard_report_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // For now, we'll create a simple HTML version that can be printed to PDF
    header('Content-Type: text/html');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    
    echo '<!DOCTYPE html>';
    echo '<html><head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Dashboard Report</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo 'table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }';
    echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
    echo 'th { background-color: #f2f2f2; }';
    echo 'h2, h3 { color: #333; }';
    echo '@media print { body { margin: 0; } }';
    echo '</style>';
    echo '</head><body>';
    
    echo '<h2>Dashboard Summary Report</h2>';
    echo '<p><strong>Generated on:</strong> ' . date('Y-m-d H:i:s') . '</p>';
    
    // Statistics
    echo '<h3>Statistics</h3>';
    echo '<table>';
    echo '<tr><th>Metric</th><th>Value</th></tr>';
    
    $totalUsers = $db->getValue('users', 'COUNT(*)') ?: 0;
    $totalProducts = $db->getValue('products', 'COUNT(*)') ?: 0;
    $totalCategories = $db->getValue('categories', 'COUNT(*)') ?: 0;
    $totalBrands = $db->getValue('brands', 'COUNT(*)') ?: 0;
    $pendingOrders = $db->where('status', 'pending')->getValue('orders', 'COUNT(*)') ?: 0;
    $lowStockItems = $db->where('stock_quantity', 10, '<=')->getValue('products', 'COUNT(*)') ?: 0;
    
    $today = date('Y-m-d');
    $todaysSales = $db->where('DATE(created_at)', $today)
        ->where('status', 'completed')
        ->getValue('orders', 'SUM(total_amount)') ?: 0;
    
    $currentMonth = date('Y-m');
    $monthlyRevenue = $db->where('DATE_FORMAT(created_at, "%Y-%m")', $currentMonth)
        ->where('status', 'completed')
        ->getValue('orders', 'SUM(total_amount)') ?: 0;
    
    echo '<tr><td>Total Users</td><td>' . $totalUsers . '</td></tr>';
    echo '<tr><td>Total Products</td><td>' . $totalProducts . '</td></tr>';
    echo '<tr><td>Total Categories</td><td>' . $totalCategories . '</td></tr>';
    echo '<tr><td>Total Brands</td><td>' . $totalBrands . '</td></tr>';
    echo '<tr><td>Pending Orders</td><td>' . $pendingOrders . '</td></tr>';
    echo '<tr><td>Low Stock Items</td><td>' . $lowStockItems . '</td></tr>';
    echo '<tr><td>Today\'s Sales</td><td>$' . number_format($todaysSales, 2) . '</td></tr>';
    echo '<tr><td>Monthly Revenue</td><td>$' . number_format($monthlyRevenue, 2) . '</td></tr>';
    echo '</table>';
    
    echo '<p><em>Note: Use your browser\'s print function and select "Save as PDF" to generate a PDF file.</em></p>';
    
    echo '</body></html>';
}

$db->disconnect();
?>
