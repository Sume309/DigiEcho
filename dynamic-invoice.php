<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone to Bangladesh Standard Time
date_default_timezone_set('Asia/Dhaka');

// Load dependencies
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/settings.php';

// Only include MysqliDb manually if it hasn't been loaded by composer autoload
if (!class_exists('MysqliDb')) {
    require_once __DIR__ . '/src/db/MysqliDb.php';
}

// Get order parameter
$orderNo = isset($_GET['order']) ? trim($_GET['order']) : '';
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirect if no order specified
if ($orderNo === '' && $orderId === 0) {
    header('Location: ' . settings()['root'] . 'user-orders.php');
    exit;
}

// Database connection
$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

// Fetch order data
if ($orderNo) {
    $order = $db->where('order_number', $orderNo)->getOne('orders');
} else {
    $order = $db->where('id', $orderId)->getOne('orders');
}

if (!$order) {
    $db->disconnect();
    http_response_code(404);
    echo '<!doctype html><html><body><h3>Order not found</h3><p>The order you requested was not found.</p></body></html>';
    exit;
}

// Fetch order items with product details
$db->join('products p', 'oi.product_id = p.id', 'LEFT');
$items = $db->where('oi.order_id', $order['id'])->get('order_items oi', null, 'oi.*, p.name as product_name, p.sku as product_sku');

// Calculate totals
$subtotal = (float)($order['subtotal'] ?? 0);
$discount = (float)($order['discount_amount'] ?? 0);
$tax = (float)($order['tax_amount'] ?? 0);
$shipping = (float)($order['shipping_amount'] ?? 0);
$grand = (float)($order['total_amount'] ?? 0);

// Check if user is logged in for download functionality
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['admin_id']) || isset($_GET['admin']);
$canDownload = $isLoggedIn && ($_SESSION['user_id'] == $order['user_id']) || $isAdmin;

// Handle PDF generation request
if (isset($_GET['action']) && $_GET['action'] === 'pdf' && $canDownload) {
    require_once __DIR__ . '/generate-invoice-pdf.php';
    generateInvoicePDF($order, $items);
    exit;
}

// Real-time status check
function getOrderStatus($order) {
    $statusColors = [
        'pending' => 'warning',
        'processing' => 'info', 
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger',
        'completed' => 'success'
    ];
    
    $paymentColors = [
        'pending' => 'warning',
        'processing' => 'info',
        'completed' => 'success',
        'failed' => 'danger',
        'refunded' => 'secondary'
    ];
    
    return [
        'order_status' => $order['status'],
        'order_color' => $statusColors[$order['status']] ?? 'secondary',
        'payment_status' => $order['payment_status'],
        'payment_color' => $paymentColors[$order['payment_status']] ?? 'secondary'
    ];
}

$status = getOrderStatus($order);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Invoice - <?= htmlspecialchars($order['order_number']) ?> | DigiEcho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        @page { margin: 10mm; size: A4; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #2d3748;
            background:rgb(248, 250, 250);
        }
        
        .invoice-container {
            max-width: 210mm;
            margin: 20px auto;
            background:rgba(139, 160, 154, 0.69);
            min-height: 297mm;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .invoice-content {
            padding: 20px;
        }
        
        /* Professional Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 3px solidrgb(45, 65, 110);
            background: linear-gradient(135deg,rgb(121, 174, 230) 0%,rgba(1, 6, 14, 0.92) 100%);
            margin: -20px -20px 25px -20px;
            padding: 20px;
        }
        
        .company-logo-section {
            display: flex;
            align-items: center;
            background:rgb(117, 216, 219);
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .company-logo {
            width: 50px;
            height: 50px;
            margin-right: 15px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .company-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg,rgb(28, 82, 182) 0%,rgb(142, 150, 155) 100%);
            border-radius: 8px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
            box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
        }
        
        .company-name {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: 0.5px;
        }
        
        .invoice-title-section {
            text-align: right;
            background:rgb(193, 218, 228);
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .invoice-title {
            font-size: 36px;
            font-weight: 800;
            color: #475569;
            letter-spacing: 4px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .invoice-number {
            font-size: 14px;
            color: #64748b;
            font-weight: 600;
        }
        
        /* Status Section */
        .status-section {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2563eb;
        }
        
        .status-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
        }
        
        /* Company & Invoice Info */
        .company-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            font-size: 12px;
            color: #64748b;
        }
        
        .company-info, .invoice-meta {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            flex: 1;
        }
        
        .company-info {
            margin-right: 15px;
        }
        
        .invoice-meta {
            margin-left: 15px;
            text-align: right;
        }
        
        /* Address Section */
        .address-section {
            display: flex;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .address-block {
            flex: 1;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .address-block:hover {
            border-color: #2563eb;
            box-shadow: 0 4px 8px rgba(37, 99, 235, 0.1);
        }
        
        .address-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 5px;
        }
        
        .address-content {
            font-size: 13px;
            line-height: 1.5;
            color: #4b5563;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .items-table thead {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        }
        
        .items-table th {
            padding: 15px 10px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-right: 1px solid rgba(255,255,255,0.2);
        }
        
        .items-table th:last-child {
            border-right: none;
        }
        
        .items-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s;
        }
        
        .items-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        
        .items-table tbody tr:hover {
            background: #e2e8f0;
        }
        
        .items-table td {
            padding: 12px 10px;
            font-size: 12px;
            color: #4b5563;
            border-right: 1px solid #f1f5f9;
        }
        
        .items-table td:last-child {
            border-right: none;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        
        .item-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 3px;
        }
        
        .item-sku {
            color:rgb(0, 21, 49);
            font-size: 15px;
            font-family: "Courier New", monospace;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .currency {
            font-family: "Courier New", monospace;
            font-weight: 600;
            color: #059669;
        }
        
        /* Totals Section */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        
        .totals-table {
            min-width: 300px;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .totals-table tr {
            border-bottom: 1px solid #f1f5f9;
        }
        
        .totals-table tr:last-child {
            border-bottom: none;
        }
        
        .totals-table td {
            padding: 12px 15px;
            font-size: 12px;
        }
        
        .totals-label {
            text-align: right;
            color: #4b5563;
            font-weight: 500;
            background: #f8fafc;
        }
        
        .totals-amount {
            text-align: right;
            color: #1e293b;
            font-weight: 600;
            font-family: "Courier New", monospace;
            background: #f8fafc;
        }
        
        .total-final {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: #ffffff !important;
        }
        
        .total-final td {
            padding: 15px;
            font-size: 16px;
            font-weight: 700;
            background: transparent;
        }
        
        /* Terms & Footer */
        .terms-section {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .terms-title {
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .terms-content {
            font-size: 10px;
            line-height: 1.4;
            color: #4b5563;
        }
        
        .footer-section {
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            font-size: 10px;
            color:rgb(4, 28, 61);
            margin-top: auto;
        }
        
        .footer-note {
            margin-top: 10px;
            font-style: italic;
            color:rgb(202, 0, 0);
        }
        
        /* Action Buttons */
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 10px 15px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            color: white;
        }
        
        @media print {
            body { background: #ffffff; }
            .invoice-container { 
                box-shadow: none; 
                min-height: auto; 
                margin: 0;
                border-radius: 0;
            }
            .action-buttons { display: none; }
            .no-print { display: none; }
        }
        
        @media (max-width: 768px) {
            .invoice-container {
                margin: 10px;
                min-height: auto;
            }
            
            .invoice-header {
                flex-direction: column;
                text-align: center;
            }
            
            .company-logo-section {
                margin-bottom: 15px;
            }
            
            .address-section {
                flex-direction: column;
                gap: 15px;
            }
            
            .company-details {
                flex-direction: column;
                gap: 15px;
            }
            
            .company-info, .invoice-meta {
                margin: 0;
            }
            
            .action-buttons {
                position: relative;
                top: auto;
                right: auto;
                margin-bottom: 20px;
                justify-content: center;
            }
        }
        
        /* Real-time status indicator */
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .status-success { background: #10b981; }
        .status-warning { background: #f59e0b; }
        .status-danger { background: #ef4444; }
        .status-info { background: #3b82f6; }
    </style>
</head>
<body>
    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <?php if ($isAdmin): ?>
        <a href="<?= settings()['adminpage'] ?>orders.php" class="btn-action" style="background: #6b7280; color: white;">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
        <?php endif; ?>
        <button class="btn-action btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
        <?php if ($canDownload): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['action' => 'pdf'])) ?>" class="btn-action btn-danger">
            <i class="fas fa-file-pdf"></i> Download PDF
        </a>
        <?php endif; ?>
        <button class="btn-action btn-success" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>

    <div class="invoice-container">
        <div class="invoice-content">
            <!-- Professional Header with Logo -->
            <div class="invoice-header">
                <div class="company-logo-section">
                    <?php if (file_exists(__DIR__ . '/assets/images/logo_1759092103.jpg')): ?>
                        <img src="<?= settings()['root'] ?>assets/images/logo_1759092103.jpg" alt="DigiEcho Logo" class="company-logo">
                    <?php elseif (file_exists(__DIR__ . '/admin/assets/img/logo.jpg')): ?>
                        <img src="<?= settings()['root'] ?>admin/assets/img/logo.jpg" alt="DigiEcho Logo" class="company-logo">
                    <?php else: ?>
                        <div class="company-icon">DE</div>
                    <?php endif; ?>
                    <div class="company-name">DigiEcho</div>
                </div>
                <div class="invoice-title-section">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-number"><?= htmlspecialchars($order['order_number']) ?></div>
                </div>
            </div>
            
            <!-- Real-time Status Section -->
            <div class="status-section">
                <div class="row">
                    <div class="col-md-6">
                        <div class="status-item">
                            <span class="status-indicator status-<?= $status['order_color'] ?>"></span>
                            <strong>Order Status:</strong> 
                            <span class="badge bg-<?= $status['order_color'] ?>"><?= ucfirst($status['order_status']) ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-indicator status-<?= $status['payment_color'] ?>"></span>
                            <strong>Payment Status:</strong> 
                            <span class="badge bg-<?= $status['payment_color'] ?>"><?= $status['payment_status'] === 'completed' ? 'Paid' : ucfirst($status['payment_status']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> Last updated: <?= date('M d, Y \a\t g:i A') ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Company Details & Invoice Meta -->
            <div class="company-details">
                <div class="company-info">
                    <strong>DigiEcho</strong><br>
                    House 815, West Kazipara<br>
                    Mirpur, Dhaka, Bangladesh 1216<br>
                    Phone: +880 1700-000000<br>
                    Email: contact@digiecho.com<br>
                    Website: www.digiecho.com
                </div>
                <div class="invoice-meta">
                    <strong>Invoice Date:</strong> <?= date('M d, Y', strtotime($order['created_at'])) ?><br>
                    <strong>Due Date:</strong> <?= date('M d, Y', strtotime($order['created_at'] . ' +30 days')) ?><br>
                    <strong>Payment Method:</strong> <?= !empty($order['payment_method']) ? htmlspecialchars(ucfirst($order['payment_method'])) : 'Cash on Delivery' ?><br>
                    <?php if (!empty($order['transaction_id'])): ?>
                    <strong>Transaction ID:</strong> <?= htmlspecialchars($order['transaction_id']) ?><br>
                    <?php endif; ?>
                    <strong>Currency:</strong> BDT (৳)
                </div>
            </div>
            
            <!-- Address Section -->
            <div class="address-section">
                <div class="address-block">
                    <div class="address-title">Bill From:</div>
                    <div class="address-content">
                        <strong>DigiEcho</strong><br>
                        House 815, West Kazipara<br>
                        Mirpur, Dhaka, Bangladesh 1216<br>
                        Phone: +880 1700-000000<br>
                        Email: contact@digiecho.com
                    </div>
                </div>
                <div class="address-block">
                    <div class="address-title">Bill To:</div>
                    <div class="address-content">
                        <strong><?= htmlspecialchars($order['billing_first_name'] . ' ' . $order['billing_last_name']) ?></strong><br>
                        <?php if (!empty($order['billing_company'])): ?>
                            <?= htmlspecialchars($order['billing_company']) ?><br>
                        <?php endif; ?>
                        <?= htmlspecialchars($order['billing_address_line_1']) ?><br>
                        <?php if (!empty($order['billing_address_line_2'])): ?>
                            <?= htmlspecialchars($order['billing_address_line_2']) ?><br>
                        <?php endif; ?>
                        <?= htmlspecialchars($order['billing_city'] . ', ' . ($order['billing_state'] ?? '') . ' ' . $order['billing_postal_code']) ?><br>
                        <?= htmlspecialchars($order['billing_country']) ?><br>
                        Phone: <?= htmlspecialchars($order['billing_phone']) ?>
                    </div>
                </div>
            </div>
            
            <!-- Professional Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 5%;">#</th>
                        <th class="text-left" style="width: 40%;">Item Description</th>
                        <th class="text-center" style="width: 10%;">Qty</th>
                        <th class="text-right" style="width: 15%;">Unit Price</th>
                        <th class="text-right" style="width: 15%;">Tax</th>
                        <th class="text-right" style="width: 15%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($items as $item): ?>
                    <tr>
                        <td class="text-center"><?= $i++ ?></td>
                        <td class="text-left">
                            <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                            <div class="item-sku">SKU: <?= htmlspecialchars($item['product_sku'] ?? 'N/A') ?></div>
                        </td>
                        <td class="text-center"><?= (int)$item['quantity'] ?></td>
                        <td class="text-right currency">৳<?= number_format((float)$item['unit_price'], 2) ?></td>
                        <td class="text-right currency">৳0.00</td>
                        <td class="text-right currency">৳<?= number_format((float)$item['total_price'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Terms & Conditions -->
            <div class="terms-section">
                <div class="terms-title">Terms & Conditions:</div>
                <div class="terms-content">
                    • Payment is due within 30 days from the invoice date.<br>
                    • Returns are accepted within 7 days with original receipt.<br>
                    • Late payment fee of 1.5% per month will be applied to overdue amounts.<br>
                    • All prices are in Bangladeshi Taka (BDT) and include applicable taxes.<br>
                    • For any queries, please contact us at contact@digiecho.com or +880 1700-000000.
                </div>
            </div>
            
            <!-- Professional Totals Section -->
            <div class="totals-section">
                <table class="totals-table">
                    <tr>
                        <td class="totals-label">Subtotal:</td>
                        <td class="totals-amount">৳<?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <?php if ($discount > 0): ?>
                    <tr>
                        <td class="totals-label">Discount:</td>
                        <td class="totals-amount">-৳<?= number_format($discount, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($tax > 0): ?>
                    <tr>
                        <td class="totals-label">Tax/VAT:</td>
                        <td class="totals-amount">৳<?= number_format($tax, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($shipping > 0): ?>
                    <tr>
                        <td class="totals-label">Shipping:</td>
                        <td class="totals-amount">৳<?= number_format($shipping, 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-final">
                        <td>Grand Total</td>
                        <td>৳<?= number_format($grand, 2) ?></td>
                    </tr>
                </table>
            </div>
            
            <!-- Professional Footer -->
            <div class="footer-section">
                <strong>DigiEcho - Your Digital Commerce Partner</strong><br>
                Thank you for choosing DigiEcho! We appreciate your business.<br>
                <div class="footer-note">
                    Generated on <?= date('F d, Y \a\t g:i A') ?> (Bangladesh Standard Time)<br>
                    Invoice ID: <?= $order['id'] ?> | Order: <?= htmlspecialchars($order['order_number']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time status update script -->
    <script>
        // Auto-refresh status every 30 seconds
        setInterval(function() {
            // Only refresh if user is on the page
            if (!document.hidden) {
                fetch(window.location.href + '&ajax=1')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status_changed) {
                            location.reload();
                        }
                    })
                    .catch(error => console.log('Status check failed:', error));
            }
        }, 30000);
        
        // Print functionality
        function printInvoice() {
            window.print();
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                printInvoice();
            }
        });
    </script>
</body>
</html>
<?php
$db->disconnect();
?>
