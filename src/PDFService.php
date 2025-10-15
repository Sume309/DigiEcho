<?php
namespace App;

// Ensure no output before headers; define a proper class for PDF generation
class PDFService {

    public function generateInvoiceHTML($order, $orderItems) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 8mm; size: A4; }
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body { 
                    font-family: "Arial", sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #333;
                    background: #ffffff;
                }
                
                /* 1. Header / Branding Section */
                .invoice-header {
                    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    position: relative;
                }
                
                .header-top {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 15px;
                }
                
                .company-logo {
                    display: flex;
                    align-items: center;
                }
                
                .logo-icon {
                    width: 50px;
                    height: 50px;
                    background: rgba(255,255,255,0.2);
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    font-weight: bold;
                    margin-right: 15px;
                    border: 2px solid rgba(255,255,255,0.3);
                }
                
                .company-details h1 {
                    font-size: 28px;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                
                .company-contact {
                    font-size: 11px;
                    opacity: 0.9;
                    line-height: 1.3;
                }
                
                .invoice-meta {
                    text-align: right;
                }
                
                .invoice-title {
                    font-size: 32px;
                    font-weight: bold;
                    margin-bottom: 10px;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                }
                
                .invoice-info {
                    font-size: 11px;
                    opacity: 0.9;
                }
                
                .status-badge {
                    display: inline-block;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 10px;
                    font-weight: bold;
                    text-transform: uppercase;
                    margin-top: 8px;
                }
                
                .status-paid { background: #10b981; }
                .status-unpaid { background: #ef4444; }
                .status-pending { background: #f59e0b; }
                
                /* 2. Customer Information Section */
                .customer-section {
                    display: flex;
                    gap: 30px;
                    margin-bottom: 25px;
                }
                
                .customer-info, .shipping-info {
                    flex: 1;
                    background: #f8fafc;
                    border: 2px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 20px;
                }
                
                .section-title {
                    font-size: 14px;
                    font-weight: bold;
                    color: #1e40af;
                    margin-bottom: 12px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    border-bottom: 2px solid #1e40af;
                    padding-bottom: 5px;
                }
                
                .customer-name {
                    font-size: 16px;
                    font-weight: bold;
                    color: #1f2937;
                    margin-bottom: 8px;
                }
                
                .customer-details {
                    font-size: 11px;
                    line-height: 1.5;
                    color: #4b5563;
                }
                
                /* 3. Order Details Table */
                .order-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 25px;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                
                .order-table thead {
                    background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
                }
                
                .order-table th {
                    padding: 15px 12px;
                    text-align: left;
                    font-weight: bold;
                    color: white;
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .order-table th.text-center { text-align: center; }
                .order-table th.text-right { text-align: right; }
                
                .order-table tbody tr {
                    border-bottom: 1px solid #e5e7eb;
                }
                
                .order-table tbody tr:nth-child(even) {
                    background: #f9fafb;
                }
                
                .order-table tbody tr:hover {
                    background: #f3f4f6;
                }
                
                .order-table td {
                    padding: 12px;
                    font-size: 11px;
                    color: #374151;
                }
                
                .product-name {
                    font-weight: bold;
                    color: #1f2937;
                    margin-bottom: 3px;
                }
                
                .product-description {
                    color: #6b7280;
                    font-size: 10px;
                }
                
                .currency {
                    font-family: "Courier New", monospace;
                    font-weight: 500;
                    color: #059669;
                }
                
                /* 4. Totals Section */
                .totals-section {
                    display: flex;
                    justify-content: flex-end;
                    margin-bottom: 25px;
                }
                
                .totals-table {
                    min-width: 350px;
                    border-collapse: collapse;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                
                .totals-table tr {
                    border-bottom: 1px solid #e5e7eb;
                }
                
                .totals-table tr:last-child {
                    border-bottom: none;
                }
                
                .totals-table td {
                    padding: 12px 18px;
                    font-size: 12px;
                }
                
                .totals-label {
                    color: #4b5563;
                    font-weight: 500;
                    text-align: right;
                    background: #f9fafb;
                }
                
                .totals-amount {
                    color: #1f2937;
                    font-weight: 600;
                    text-align: right;
                    font-family: "Courier New", monospace;
                    background: #f9fafb;
                }
                
                .grand-total {
                    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
                    color: white !important;
                }
                
                .grand-total td {
                    padding: 15px 18px;
                    font-size: 16px;
                    font-weight: bold;
                    background: transparent;
                }
                
                /* 5. Payment Method Section */
                .payment-section {
                    background: #fef3c7;
                    border: 2px solid #f59e0b;
                    border-radius: 8px;
                    padding: 20px;
                    margin-bottom: 25px;
                }
                
                .payment-title {
                    font-size: 14px;
                    font-weight: bold;
                    color: #92400e;
                    margin-bottom: 12px;
                    display: flex;
                    align-items: center;
                }
                
                .payment-icon {
                    width: 20px;
                    height: 20px;
                    background: #f59e0b;
                    border-radius: 50%;
                    margin-right: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: bold;
                    font-size: 12px;
                }
                
                .payment-details {
                    display: flex;
                    gap: 30px;
                }
                
                .payment-method, .transaction-info {
                    flex: 1;
                }
                
                .payment-method-name {
                    font-size: 16px;
                    font-weight: bold;
                    color: #92400e;
                    margin-bottom: 5px;
                }
                
                .payment-info {
                    font-size: 11px;
                    color: #78350f;
                    line-height: 1.4;
                }
                
                /* 6. Shipping Information */
                .shipping-section {
                    background: #dbeafe;
                    border: 2px solid #3b82f6;
                    border-radius: 8px;
                    padding: 20px;
                    margin-bottom: 25px;
                }
                
                .shipping-title {
                    font-size: 14px;
                    font-weight: bold;
                    color: #1d4ed8;
                    margin-bottom: 12px;
                    display: flex;
                    align-items: center;
                }
                
                .shipping-icon {
                    width: 20px;
                    height: 20px;
                    background: #3b82f6;
                    border-radius: 50%;
                    margin-right: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: bold;
                    font-size: 12px;
                }
                
                .shipping-details {
                    display: flex;
                    gap: 30px;
                }
                
                .courier-info, .delivery-info {
                    flex: 1;
                }
                
                .shipping-info-text {
                    font-size: 11px;
                    color: #1e40af;
                    line-height: 1.4;
                }
                
                /* 7. Footer Section */
                .footer-section {
                    background: #f1f5f9;
                    border: 2px solid #cbd5e1;
                    border-radius: 8px;
                    padding: 20px;
                    text-align: center;
                }
                
                .thank-you {
                    font-size: 18px;
                    font-weight: bold;
                    color: #059669;
                    margin-bottom: 15px;
                }
                
                .policies {
                    font-size: 10px;
                    color: #4b5563;
                    margin-bottom: 15px;
                    line-height: 1.4;
                }
                
                .legal-details {
                    font-size: 9px;
                    color: #6b7280;
                    border-top: 1px solid #cbd5e1;
                    padding-top: 12px;
                    margin-top: 12px;
                }
                
                .qr-code {
                    width: 60px;
                    height: 60px;
                    background: #e5e7eb;
                    border: 2px solid #9ca3af;
                    border-radius: 4px;
                    margin: 10px auto;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 10px;
                    color: #6b7280;
                }
                
                /* Utility Classes */
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .text-left { text-align: left; }
                
                @media print {
                    body { background: white; }
                    .invoice-container { box-shadow: none; }
                }
            </style>
        </head>
        <body>
            <div class="invoice-container">
                
                <!-- 1. Header / Branding Section -->
                <div class="invoice-header">
                    <div class="header-top">
                        <div class="company-logo">
                            <div class="logo-icon">DE</div>
                            <div class="company-details">
                                <h1>DigiEcho</h1>
                                <div class="company-contact">
                                    Dhaka, Bangladesh<br>
                                    Email: info@digiecho.com | Phone: +880 1700-000000<br>
                                    Website: www.digiecho.com
                                </div>
                            </div>
                        </div>
                        <div class="invoice-meta">
                            <div class="invoice-title">Invoice</div>
                            <div class="invoice-info">
                                <strong>Invoice #:</strong> ' . htmlspecialchars($order['order_number']) . '<br>
                                <strong>Date:</strong> ' . date('d M Y', strtotime($order['created_at'])) . '<br>
                                <strong>Order #:</strong> ' . htmlspecialchars($order['order_number']) . '
                            </div>
                            <div class="status-badge status-' . strtolower($order['payment_status']) . '">
                                ' . ucfirst($order['payment_status']) . '
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 2. Customer Information Section -->
                <div class="customer-section">
                    <div class="customer-info">
                        <div class="section-title">Bill To</div>
                        <div class="customer-name">' . htmlspecialchars($order['billing_first_name'] . ' ' . $order['billing_last_name']) . '</div>
                        <div class="customer-details">
                            <strong>Email:</strong> ' . htmlspecialchars($order['billing_email']) . '<br>
                            <strong>Phone:</strong> ' . htmlspecialchars($order['billing_phone']) . '<br><br>
                            <strong>Billing Address:</strong><br>
                            ' . htmlspecialchars($order['billing_address_line_1']) . '<br>';
        
        if (!empty($order['billing_address_line_2'])) {
            $html .= htmlspecialchars($order['billing_address_line_2']) . '<br>';
        }
        
        $html .= htmlspecialchars($order['billing_city'] . ', ' . $order['billing_state'] . ' ' . $order['billing_postal_code']) . '<br>
                            ' . htmlspecialchars($order['billing_country']) . '
                        </div>
                    </div>
                    
                    <div class="shipping-info">
                        <div class="section-title">Ship To</div>
                        <div class="customer-name">' . htmlspecialchars($order['shipping_first_name'] . ' ' . $order['shipping_last_name']) . '</div>
                        <div class="customer-details">
                            <strong>Phone:</strong> ' . htmlspecialchars($order['shipping_phone']) . '<br><br>
                            <strong>Shipping Address:</strong><br>
                            ' . htmlspecialchars($order['shipping_address_line_1']) . '<br>';
        
        if (!empty($order['shipping_address_line_2'])) {
            $html .= htmlspecialchars($order['shipping_address_line_2']) . '<br>';
        }
        
        $html .= htmlspecialchars($order['shipping_city'] . ', ' . $order['shipping_state'] . ' ' . $order['shipping_postal_code']) . '<br>
                            ' . htmlspecialchars($order['shipping_country']) . '
                        </div>
                    </div>
                </div>
                
                <!-- 3. Order Details Table -->
                <table class="order-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Item</th>
                            <th style="width: 25%;">Description</th>
                            <th class="text-center" style="width: 8%;">Qty</th>
                            <th class="text-right" style="width: 12%;">Unit Price</th>
                            <th class="text-right" style="width: 15%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($orderItems as $item) {
            $html .= '
                        <tr>
                            <td>
                                <div class="product-name">' . htmlspecialchars($item['product_name']) . '</div>
                                <div class="product-description">SKU: ' . htmlspecialchars($item['product_sku']) . '</div>
                            </td>
                            <td>
                                <div class="product-description">' . htmlspecialchars($item['product_name']) . '</div>
                            </td>
                            <td class="text-center">' . $item['quantity'] . '</td>
                            <td class="text-right currency">৳ ' . number_format($item['unit_price'], 2) . '</td>
                            <td class="text-right currency">৳ ' . number_format($item['total_price'], 2) . '</td>
                        </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
                
                <!-- 4. Totals Section -->
                <div class="totals-section">
                    <table class="totals-table">
                        <tr>
                            <td class="totals-label">Subtotal:</td>
                            <td class="totals-amount">৳ ' . number_format($order['subtotal'], 2) . '</td>
                        </tr>';
        
        if ($order['discount_amount'] > 0) {
            $html .= '
                        <tr>
                            <td class="totals-label">Discount:</td>
                            <td class="totals-amount">- ৳ ' . number_format($order['discount_amount'], 2) . '</td>
                        </tr>';
        }
        
        $html .= '
                        <tr>
                            <td class="totals-label">Shipping Charges:</td>
                            <td class="totals-amount">৳ ' . number_format($order['shipping_amount'] ?? 0, 2) . '</td>
                        </tr>';
        
        if ($order['tax_amount'] > 0) {
            $html .= '
                        <tr>
                            <td class="totals-label">Tax / VAT:</td>
                            <td class="totals-amount">৳ ' . number_format($order['tax_amount'], 2) . '</td>
                        </tr>';
        }
        
        $html .= '
                        <tr class="grand-total">
                            <td>Grand Total</td>
                            <td>৳ ' . number_format($order['total_amount'], 2) . '</td>
                        </tr>
                    </table>
                </div>
                
                <!-- 5. Payment Method Section -->
                <div class="payment-section">
                    <div class="payment-title">
                        <div class="payment-icon">₹</div>
                        Payment Information
                    </div>
                    <div class="payment-details">
                        <div class="payment-method">
                            <div class="payment-method-name">' . ucfirst($order['payment_method'] ?? 'Cash on Delivery') . '</div>
                            <div class="payment-info">
                                Payment Method: ' . ucfirst($order['payment_method'] ?? 'COD') . '<br>
                                Transaction ID: ' . ($order['transaction_id'] ?? 'N/A') . '<br>
                                Payment Date: ' . date('d M Y', strtotime($order['created_at'])) . '
                            </div>
                        </div>
                        <div class="transaction-info">
                            <div class="payment-info">
                                <strong>Available Payment Methods:</strong><br>
                                • bKash, Nagad, Rocket<br>
                                • Credit/Debit Card<br>
                                • Cash on Delivery<br>
                                • Bank Transfer
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 6. Shipping Information -->
                <div class="shipping-section">
                    <div class="shipping-title">
                        <div class="shipping-icon">🚚</div>
                        Shipping & Delivery
                    </div>
                    <div class="shipping-details">
                        <div class="courier-info">
                            <div class="shipping-info-text">
                                <strong>Courier Service:</strong> ' . ($order['courier_service'] ?? 'Standard Delivery') . '<br>
                                <strong>Tracking Number:</strong> ' . ($order['tracking_number'] ?? 'TBD') . '<br>
                                <strong>Shipping Method:</strong> ' . ($order['shipping_method'] ?? 'Home Delivery') . '
                            </div>
                        </div>
                        <div class="delivery-info">
                            <div class="shipping-info-text">
                                <strong>Estimated Delivery:</strong> ' . date('d M Y', strtotime($order['created_at'] . ' +3 days')) . '<br>
                                <strong>Delivery Status:</strong> ' . ucfirst($order['status']) . '<br>
                                <strong>Special Instructions:</strong> ' . ($order['delivery_notes'] ?? 'Handle with care') . '
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 7. Footer Section -->
                <div class="footer-section">
                    <div class="thank-you">Thank you for shopping with DigiEcho!</div>
                    
                    <div class="policies">
                        <strong>Refund & Return Policy:</strong> Returns accepted within 7 days of delivery with original receipt. 
                        Visit <strong>www.digiecho.com/returns</strong> for complete policy details.
                    </div>
                    
                    <div class="qr-code">QR Code<br>' . $order['order_number'] . '</div>
                    
                    <div class="legal-details">
                        <strong>DigiEcho</strong> | Trade License: TL-123456789 | VAT Registration: VAT-987654321<br>
                        Generated on ' . date('d M Y \a\t g:i A') . ' (Bangladesh Standard Time)<br>
                        For support: support@digiecho.com | +880 1700-000000
                    </div>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }

    // Generate and store invoice PDF for a given order ID. Returns file path or false on failure.
    public function generateOrderInvoicePDF($orderId) {
        // Lazy-load Composer autoload if present (Dompdf/TCPDF)
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        // Load settings and DB helper to fetch order data
        $settingsFile = __DIR__ . '/settings.php';
        if (file_exists($settingsFile)) {
            require_once $settingsFile;
        }
        if (!class_exists('MysqliDb')) {
            $dbLib = __DIR__ . '/db/MysqliDb.php';
            if (file_exists($dbLib)) {
                require_once $dbLib;
            }
        }

        // Fetch order and items
        try {
            if (!class_exists('MysqliDb') || !function_exists('settings')) {
                throw new \Exception('Dependencies missing for fetching order data');
            }
            $db = new \MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
            $order = $db->where('id', (int)$orderId)->getOne('orders');
            if (!$order) {
                throw new \Exception('Order not found');
            }
            $orderItems = $db->where('order_id', (int)$orderId)->get('order_items');
        } catch (\Exception $e) {
            error_log('PDFService: ' . $e->getMessage());
            return false;
        }

        $html = $this->generateInvoiceHTML($order, $orderItems);

        // Ensure output directory exists (match order_confirmation.php: assets/invoices/invoice-<ORDER>.pdf)
        $assetsRoot = realpath(__DIR__ . '/../assets');
        if ($assetsRoot === false) {
            $assetsRoot = __DIR__ . '/../assets';
        }
        $outDir = $assetsRoot . '/invoices';
        if (!is_dir($outDir)) {
            @mkdir($outDir, 0775, true);
        }

        $safeOrderNumber = preg_replace('/[^A-Za-z0-9\-]/', '-', $order['order_number'] ?? ('ORD-' . (int)$orderId));
        $pdfPath = $outDir . '/invoice-' . $safeOrderNumber . '.pdf';

        // Try Dompdf
        if (class_exists('Dompdf\\Dompdf')) {
            try {
                $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
                $dompdf->loadHtml($html, 'UTF-8');
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $output = $dompdf->output();
                file_put_contents($pdfPath, $output);
                return $pdfPath;
            } catch (\Throwable $t) {
                error_log('Dompdf error: ' . $t->getMessage());
            }
        }

        // Fallback to TCPDF
        if (class_exists('TCPDF')) {
            try {
                $pdf = new \TCPDF();
                $pdf->SetCreator('Haat Bazar');
                $pdf->SetAuthor('Haat Bazar');
                $pdf->SetTitle('Invoice ' . ($order['order_number'] ?? $orderId));
                $pdf->SetMargins(8, 8, 8);
                $pdf->AddPage();
                $pdf->writeHTML($html, true, false, true, false, '');
                $pdf->Output($pdfPath, 'F');
                return $pdfPath;
            } catch (\Throwable $t) {
                error_log('TCPDF error: ' . $t->getMessage());
            }
        }

        // As a last resort, save HTML so at least something is generated (use same naming pattern)
        $htmlPath = $outDir . '/invoice-' . $safeOrderNumber . '.html';
        file_put_contents($htmlPath, $html);
        return $htmlPath;
    }
}