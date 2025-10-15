<?php
// Test Invoice Generation Script
require_once __DIR__ . '/src/PDFService.php';

// Generate HTML preview of the invoice
$pdfService = new App\PDFService();
$testOrder = [
    'id' => 1234,
    'order_number' => 'ORD-' . time() . '-TEST',
    'created_at' => date('Y-m-d H:i:s'),
    'billing_first_name' => 'John',
    'billing_last_name' => 'Doe',
    'billing_company' => 'Test Company Ltd.',
    'billing_address_line_1' => '123 Main Street',
    'billing_address_line_2' => 'Suite 100',
    'billing_city' => 'Dhaka',
    'billing_state' => 'Dhaka',
    'billing_postal_code' => '1000',
    'billing_country' => 'Bangladesh',
    'billing_phone' => '+880 1700-123456',
    'billing_email' => 'john.doe@example.com',
    'shipping_first_name' => 'John',
    'shipping_last_name' => 'Doe',
    'shipping_company' => 'Test Company Ltd.',
    'shipping_address_line_1' => '123 Main Street',
    'shipping_address_line_2' => 'Suite 100',
    'shipping_city' => 'Dhaka',
    'shipping_state' => 'Dhaka',
    'shipping_postal_code' => '1000',
    'shipping_country' => 'Bangladesh',
    'shipping_phone' => '+880 1700-123456',
    'payment_method' => 'bkash',
    'payment_status' => 'completed',
    'status' => 'processing',
    'subtotal' => 2300.00,
    'discount_amount' => 50.00,
    'tax_amount' => 230.00,
    'shipping_amount' => 50.00,
    'total_amount' => 2530.00,
    'notes' => 'Please handle with care. Customer requested expedited shipping.'
];

$testItems = [
    [
        'product_sku' => 'PROD-001',
        'product_name' => 'Premium Quality Rice (5kg)',
        'quantity' => 2,
        'unit_price' => 750.00,
        'total_price' => 1500.00
    ],
    [
        'product_sku' => 'PROD-002',
        'product_name' => 'Fresh Vegetables Bundle',
        'quantity' => 1,
        'unit_price' => 450.00,
        'total_price' => 450.00
    ],
    [
        'product_sku' => 'PROD-003',
        'product_name' => 'Organic Cooking Oil (1L)',
        'quantity' => 1,
        'unit_price' => 350.00,
        'total_price' => 350.00
    ]
];

// Generate HTML preview of the invoice
$pdfService = new PDFService();
$html = $pdfService->generateInvoiceHTML($testOrder, $testItems);

echo '<h2 style="text-align: center; color: #2563eb; margin: 20px 0;">✨ PROFESSIONAL ONE-PAGE INVOICE - DigiEcho ✨</h2>';
echo '<p style="text-align: center; margin-bottom: 30px; color: #64748b;">Complete professional invoice design with proper logo, BDT currency, and optimized one-page layout.</p>';
echo $html;
?>