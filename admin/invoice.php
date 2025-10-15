<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin authentication check
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/settings.php';

// Database connection
$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
$id = $_GET['id'] ?? 0;

if (!$db->where('id', $id)->has('orders')) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Handle PDF generation request
if (isset($_GET['action']) && $_GET['action'] === 'pdf') {
    // Fetch order and items for PDF generation
    $order = $db->where('id', $id)->getOne('orders');
    $db->join('products', 'order_items.product_id = products.id', 'LEFT');
    $db->where('order_id', $id);
    $items = $db->get('order_items', NULL, 'order_items.*, products.name as product_name, products.sku as product_sku');
    
    require_once __DIR__ . '/../generate-invoice-pdf.php';
    generateInvoicePDF($order, $items);
    exit;
}

// Redirect to dynamic invoice system for admin
$dynamicInvoiceUrl = settings()['root'] . 'dynamic-invoice.php?id=' . $id . '&admin=1';
header('Location: ' . $dynamicInvoiceUrl);
exit;
?>