<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone to Bangladesh Standard Time
date_default_timezone_set('Asia/Dhaka');

// Load Composer autoload FIRST to prevent duplicate includes of files listed in composer.json (autoload.files)
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

require_once __DIR__ . '/src/settings.php';
// Only include MysqliDb manually if it hasn't been loaded by composer autoload
if (!class_exists('MysqliDb')) {
    require_once __DIR__ . '/src/db/MysqliDb.php';
}

$orderNo = isset($_GET['order']) ? trim($_GET['order']) : '';
if ($orderNo === '') {
    header('Location: ' . settings()['root'] . 'user-orders.php');
    exit;
}

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

$order = $db->where('order_number', $orderNo)->getOne('orders');
if (!$order) {
    $db->disconnect();
    http_response_code(404);
    echo '<!doctype html><html><body><h3>Order not found</h3><p>The order you requested was not found.</p></body></html>';
    exit;
}

$items = $db->where('order_id', $order['id'])->get('order_items');

// Compute totals from DB values
$subtotal = (float)($order['subtotal'] ?? 0);
$discount = (float)($order['discount_amount'] ?? 0);
$tax = (float)($order['tax_amount'] ?? 0);
$shipping = (float)($order['shipping_amount'] ?? 0);
$grand = (float)($order['total_amount'] ?? 0);

// Dynamic invoice URLs
$dynamicInvoiceUrl = settings()['root'] . 'dynamic-invoice.php?order=' . urlencode($orderNo);
$pdfInvoiceUrl = settings()['root'] . 'dynamic-invoice.php?order=' . urlencode($orderNo) . '&action=pdf';

// Check if user is logged in for download functionality
$isLoggedIn = isset($_SESSION['user_id']);
$canDownload = $isLoggedIn && ($_SESSION['user_id'] == $order['user_id']);

?>
<?php require __DIR__ . '/components/header.php';?>
<div class="container my-5">
    <div class="alert alert-success d-print-none" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        Order placed successfully. Your order number is <strong><?= htmlspecialchars($orderNo) ?></strong>.
    </div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0"><i class="fas fa-file-invoice me-2"></i>Order Confirmation</h2>
        <div>
        
            <button class="btn btn-success" onclick="downloadInvoice('<?= htmlspecialchars($orderNo) ?>')">
                <i class="fas fa-download me-1"></i> Download Invoice
            </button>
                     
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-muted">Order Details</h5>
                    <p class="mb-1"><strong>Order Number:</strong> <?= htmlspecialchars($orderNo) ?></p>
                    <p class="mb-1"><strong>Order Date:</strong> <?= date('F d, Y \a\t g:i A', strtotime($order['created_at'])) ?></p>
                    <p class="mb-0"><strong>Status:</strong> <span class="badge bg-<?= $order['status']==='completed'?'success':'warning' ?>"><?= htmlspecialchars(ucfirst($order['status'])) ?></span></p>
                </div>
                <div class="col-md-6">
                    <h5 class="text-muted">Payment</h5>
                    <p class="mb-1"><strong>Method:</strong> <?= htmlspecialchars(ucfirst($order['payment_method'])) ?></p>
                    <p class="mb-1"><strong>Payment Status:</strong> <span class="badge bg-<?= $order['payment_status']==='completed'?'success':'warning' ?>"><?= htmlspecialchars(ucfirst($order['payment_status'])) ?></span></p>
                    <?php if (!empty($order['transaction_id'])): ?>
                        <p class="mb-0"><strong>Transaction ID:</strong> <?= htmlspecialchars($order['transaction_id']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="text-muted">Bill To</h5>
                    <p class="mb-1"><strong><?= htmlspecialchars($order['billing_first_name'] . ' ' . $order['billing_last_name']) ?></strong></p>
                    <?php if (!empty($order['billing_company'])): ?>
                        <p class="mb-1"><?= htmlspecialchars($order['billing_company']) ?></p>
                    <?php endif; ?>
                    <p class="mb-1"><?= htmlspecialchars($order['billing_address_line_1']) ?></p>
                    <?php if (!empty($order['billing_address_line_2'])): ?>
                        <p class="mb-1"><?= htmlspecialchars($order['billing_address_line_2']) ?></p>
                    <?php endif; ?>
                    <p class="mb-1"><?= htmlspecialchars($order['billing_city'] . ', ' . ($order['billing_state'] ?? '') . ' ' . $order['billing_postal_code']) ?></p>
                    <p class="mb-1"><?= htmlspecialchars($order['billing_country']) ?></p>
                    <p class="mb-0">📞 <?= htmlspecialchars($order['billing_phone']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="text-muted">Ship To</h5>
                    <p class="mb-1"><strong><?= htmlspecialchars($order['shipping_first_name'] . ' ' . $order['shipping_last_name']) ?></strong></p>
                    <?php if (!empty($order['shipping_company'])): ?>
                        <p class="mb-1"><?= htmlspecialchars($order['shipping_company']) ?></p>
                    <?php endif; ?>
                    <p class="mb-1"><?= htmlspecialchars($order['shipping_address_line_1']) ?></p>
                    <?php if (!empty($order['shipping_address_line_2'])): ?>
                        <p class="mb-1"><?= htmlspecialchars($order['shipping_address_line_2']) ?></p>
                    <?php endif; ?>
                    <p class="mb-1"><?= htmlspecialchars($order['shipping_city'] . ', ' . ($order['shipping_state'] ?? '') . ' ' . $order['shipping_postal_code']) ?></p>
                    <p class="mb-1"><?= htmlspecialchars($order['shipping_country']) ?></p>
                    <p class="mb-0">📞 <?= htmlspecialchars($order['shipping_phone']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="text-muted">Items</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width:5%">#</th>
                            <th style="width:15%">SKU</th>
                            <th>Product</th>
                            <th style="width:10%" class="text-center">Qty</th>
                            <th style="width:15%" class="text-end">Unit Price</th>
                            <th style="width:15%" class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i=1; foreach ($items as $it): ?>
                        <tr>
                            <td class="text-center"><?= $i++ ?></td>
                            <td><?= htmlspecialchars($it['product_sku']) ?></td>
                            <td><?= htmlspecialchars($it['product_name']) ?></td>
                            <td class="text-center"><?= (int)$it['quantity'] ?></td>
                            <td class="text-end">৳<?= number_format((float)$it['unit_price'], 2) ?></td>
                            <td class="text-end">৳<?= number_format((float)$it['total_price'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="row">
                <div class="col-md-6"></div>
                <div class="col-md-6">
                    <table class="table">
                        <tr>
                            <td><strong>Subtotal</strong></td>
                            <td class="text-end">৳<?= number_format($subtotal, 2) ?></td>
                        </tr>
                        <?php if ($discount > 0): ?>
                        <tr>
                            <td>Discount</td>
                            <td class="text-end text-danger">-৳<?= number_format($discount, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($tax > 0): ?>
                        <tr>
                            <td>Tax/VAT</td>
                            <td class="text-end">৳<?= number_format($tax, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($shipping > 0): ?>
                        <tr>
                            <td>Shipping</td>
                            <td class="text-end">৳<?= number_format($shipping, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="fw-bold">Grand Total</td>
                            <td class="text-end fw-bold">৳<?= number_format($grand, 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php if (!empty($order['notes'])): ?>
            <div class="alert alert-warning mt-3">
                <strong>Order Notes:</strong><br>
                <?= nl2br(htmlspecialchars($order['notes'])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center">
        <a href="<?= settings()['root'] ?>user-orders.php" class="btn btn-outline-secondary">
            <i class="fas fa-list me-1"></i> View My Orders
        </a>
        <a href="<?= settings()['root'] ?>index.php" class="btn btn-primary ms-2">
            <i class="fas fa-shopping-bag me-1"></i> Continue Shopping
        </a>
    </div>
</div>
<script>
function downloadInvoice(orderNumber) {
    // Build the dynamic invoice URL with the order number
    const invoiceUrl = `dynamic-invoice.php?order=${orderNumber}`;
    
    // Open the dynamic invoice in a new window/tab
    window.open(invoiceUrl, '_blank', 'width=900,height=700,scrollbars=yes,resizable=yes');
}
</script>

<?php 
$db->disconnect();
require __DIR__ . '/components/footer.php';
?>
