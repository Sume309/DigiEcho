<?php
// PDF Generation for DigiEcho Invoices
// Using TCPDF library for professional PDF generation

require_once __DIR__ . '/vendor/autoload.php';

// Import required classes for DOMPDF (if needed)
use Dompdf\Dompdf;
use Dompdf\Options;

// Check if TCPDF is available, if not use DOMPDF as fallback
if (class_exists('TCPDF')) {
    function generateInvoicePDF($order, $items) {
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('DigiEcho');
        $pdf->SetAuthor('DigiEcho');
        $pdf->SetTitle('Invoice - ' . $order['order_number']);
        $pdf->SetSubject('Professional Invoice');
        $pdf->SetKeywords('Invoice, DigiEcho, Order, Receipt');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, 'DigiEcho Invoice', 'Professional Invoice - ' . $order['order_number']);
        
        // Set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Calculate totals
        $subtotal = (float)($order['subtotal'] ?? 0);
        $discount = (float)($order['discount_amount'] ?? 0);
        $tax = (float)($order['tax_amount'] ?? 0);
        $shipping = (float)($order['shipping_amount'] ?? 0);
        $grand = (float)($order['total_amount'] ?? 0);
        
        // HTML content for the invoice
        $html = '
        <style>
            .header { text-align: center; margin-bottom: 20px; }
            .company-info { background-color: #f8f9fa; padding: 10px; margin-bottom: 15px; }
            .invoice-details { margin-bottom: 15px; }
            .address-section { margin-bottom: 20px; }
            .address-block { border: 1px solid #dee2e6; padding: 10px; margin-bottom: 10px; }
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .items-table th, .items-table td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
            .items-table th { background-color: #e9ecef; font-weight: bold; }
            .totals-table { width: 50%; margin-left: auto; border-collapse: collapse; }
            .totals-table td { border: 1px solid #dee2e6; padding: 8px; }
            .total-final { background-color: #007bff; color: white; font-weight: bold; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .currency { font-family: monospace; }
        </style>
        
        <div class="header">
            <h1 style="color: #007bff;">INVOICE</h1>
            <h2>' . htmlspecialchars($order['order_number']) . '</h2>
        </div>
        
        <div class="company-info">
            <table width="100%">
                <tr>
                    <td width="50%">
                        <strong>DigiEcho</strong><br>
                        House 815, West Kazipara<br>
                        Mirpur, Dhaka, Bangladesh 1216<br>
                        Phone: +880 1700-000000<br>
                        Email: contact@digiecho.com
                    </td>
                    <td width="50%" style="text-align: right;">
                        <strong>Invoice Date:</strong> ' . date('M d, Y', strtotime($order['created_at'])) . '<br>
                        <strong>Due Date:</strong> ' . date('M d, Y', strtotime($order['created_at'] . ' +30 days')) . '<br>
                        <strong>Payment Method:</strong> ' . htmlspecialchars(ucfirst($order['payment_method'])) . '<br>
                        <strong>Status:</strong> ' . htmlspecialchars(ucfirst($order['status'])) . '
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="address-section">
            <table width="100%">
                <tr>
                    <td width="50%">
                        <div class="address-block">
                            <strong>Bill From:</strong><br>
                            DigiEcho<br>
                            House 815, West Kazipara<br>
                            Mirpur, Dhaka, Bangladesh 1216<br>
                            Phone: +880 1700-000000
                        </div>
                    </td>
                    <td width="50%">
                        <div class="address-block">
                            <strong>Bill To:</strong><br>
                            ' . htmlspecialchars($order['billing_first_name'] . ' ' . $order['billing_last_name']) . '<br>';
                            
        if (!empty($order['billing_company'])) {
            $html .= htmlspecialchars($order['billing_company']) . '<br>';
        }
        
        $html .= htmlspecialchars($order['billing_address_line_1']) . '<br>';
        
        if (!empty($order['billing_address_line_2'])) {
            $html .= htmlspecialchars($order['billing_address_line_2']) . '<br>';
        }
        
        $html .= htmlspecialchars($order['billing_city'] . ', ' . ($order['billing_state'] ?? '') . ' ' . $order['billing_postal_code']) . '<br>
                            ' . htmlspecialchars($order['billing_country']) . '<br>
                            Phone: ' . htmlspecialchars($order['billing_phone']) . '
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th width="5%" class="text-center">#</th>
                    <th width="40%">Item Description</th>
                    <th width="10%" class="text-center">Qty</th>
                    <th width="15%" class="text-right">Unit Price</th>
                    <th width="15%" class="text-right">Tax</th>
                    <th width="15%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>';
            
        $i = 1;
        foreach ($items as $item) {
            $html .= '<tr>
                        <td class="text-center">' . $i++ . '</td>
                        <td>' . htmlspecialchars($item['product_name']) . '<br>
                            <small>SKU: ' . htmlspecialchars($item['product_sku'] ?? 'N/A') . '</small>
                        </td>
                        <td class="text-center">' . (int)$item['quantity'] . '</td>
                        <td class="text-right currency">৳' . number_format((float)$item['unit_price'], 2) . '</td>
                        <td class="text-right currency">৳0.00</td>
                        <td class="text-right currency">৳' . number_format((float)$item['total_price'], 2) . '</td>
                    </tr>';
        }
        
        $html .= '</tbody>
        </table>
        
        <table class="totals-table">
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td class="text-right currency">৳' . number_format($subtotal, 2) . '</td>
            </tr>';
            
        if ($discount > 0) {
            $html .= '<tr>
                        <td>Discount:</td>
                        <td class="text-right currency">-৳' . number_format($discount, 2) . '</td>
                    </tr>';
        }
        
        if ($tax > 0) {
            $html .= '<tr>
                        <td>Tax/VAT:</td>
                        <td class="text-right currency">৳' . number_format($tax, 2) . '</td>
                    </tr>';
        }
        
        if ($shipping > 0) {
            $html .= '<tr>
                        <td>Shipping:</td>
                        <td class="text-right currency">৳' . number_format($shipping, 2) . '</td>
                    </tr>';
        }
        
        $html .= '<tr class="total-final">
                    <td><strong>Grand Total:</strong></td>
                    <td class="text-right currency"><strong>৳' . number_format($grand, 2) . '</strong></td>
                </tr>
        </table>
        
        <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">
            <p><strong>Terms & Conditions:</strong></p>
            <p>Payment is due within 30 days from the invoice date. Returns are accepted within 7 days with original receipt.</p>
            <p>Late payment fee of 1.5% per month will be applied to overdue amounts.</p>
            <br>
            <p><strong>DigiEcho - Your Digital Commerce Partner</strong></p>
            <p>Thank you for choosing DigiEcho! We appreciate your business.</p>
            <p><small>Generated on ' . date('F d, Y \a\t g:i A') . ' (Bangladesh Standard Time)</small></p>
        </div>';
        
        // Print text using writeHTMLCell()
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF document
        $filename = 'Invoice-' . $order['order_number'] . '.pdf';
        $pdf->Output($filename, 'D');
    }
} else {
    // Fallback to DOMPDF if TCPDF is not available
    function generateInvoicePDF($order, $items) {
        require_once __DIR__ . '/vendor/dompdf/dompdf/autoload.inc.php';
        
        // Configure Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        
        // Initialize Dompdf
        $dompdf = new Dompdf($options);
        
        // Calculate totals
        $subtotal = (float)($order['subtotal'] ?? 0);
        $discount = (float)($order['discount_amount'] ?? 0);
        $tax = (float)($order['tax_amount'] ?? 0);
        $shipping = (float)($order['shipping_amount'] ?? 0);
        $grand = (float)($order['total_amount'] ?? 0);
        
        // Generate HTML for PDF
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 20mm; size: A4; }
                body { 
                    font-family: "DejaVu Sans", sans-serif; 
                    font-size: 10px; 
                    line-height: 1.4; 
                    color: #333; 
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    border-bottom: 3px solid #007bff; 
                    padding-bottom: 20px; 
                }
                .company-name { 
                    font-size: 24px; 
                    font-weight: bold; 
                    color: #007bff; 
                    margin-bottom: 5px; 
                }
                .invoice-title { 
                    font-size: 32px; 
                    font-weight: bold; 
                    color: #666; 
                    margin-bottom: 10px; 
                }
                .invoice-number { 
                    font-size: 14px; 
                    color: #666; 
                }
                .company-details { 
                    width: 100%; 
                    margin-bottom: 20px; 
                }
                .company-details td { 
                    vertical-align: top; 
                    padding: 10px; 
                    background-color: #f8f9fa; 
                }
                .address-section { 
                    width: 100%; 
                    margin-bottom: 20px; 
                }
                .address-block { 
                    border: 1px solid #dee2e6; 
                    padding: 15px; 
                    background-color: #f8f9fa; 
                }
                .items-table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-bottom: 20px; 
                }
                .items-table th, .items-table td { 
                    border: 1px solid #dee2e6; 
                    padding: 10px; 
                    text-align: left; 
                }
                .items-table th { 
                    background-color: #007bff; 
                    color: white; 
                    font-weight: bold; 
                    text-align: center; 
                }
                .items-table tbody tr:nth-child(even) { 
                    background-color: #f8f9fa; 
                }
                .totals-table { 
                    width: 300px; 
                    margin-left: auto; 
                    border-collapse: collapse; 
                    margin-bottom: 20px; 
                }
                .totals-table td { 
                    border: 1px solid #dee2e6; 
                    padding: 10px; 
                }
                .total-final { 
                    background-color: #007bff; 
                    color: white; 
                    font-weight: bold; 
                    font-size: 14px; 
                }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .currency { font-family: monospace; font-weight: bold; }
                .footer { 
                    margin-top: 30px; 
                    text-align: center; 
                    font-size: 9px; 
                    color: #666; 
                    border-top: 1px solid #dee2e6; 
                    padding-top: 15px; 
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company-name">DigiEcho</div>
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">' . htmlspecialchars($order['order_number']) . '</div>
            </div>
            
            <table class="company-details">
                <tr>
                    <td width="50%">
                        <strong>DigiEcho</strong><br>
                        House 815, West Kazipara<br>
                        Mirpur, Dhaka, Bangladesh 1216<br>
                        Phone: +880 1700-000000<br>
                        Email: contact@digiecho.com<br>
                        Website: www.digiecho.com
                    </td>
                    <td width="50%" style="text-align: right;">
                        <strong>Invoice Date:</strong> ' . date('M d, Y', strtotime($order['created_at'])) . '<br>
                        <strong>Due Date:</strong> ' . date('M d, Y', strtotime($order['created_at'] . ' +30 days')) . '<br>
                        <strong>Payment Method:</strong> ' . htmlspecialchars(ucfirst($order['payment_method'])) . '<br>
                        <strong>Order Status:</strong> ' . htmlspecialchars(ucfirst($order['status'])) . '<br>
                        <strong>Payment Status:</strong> ' . htmlspecialchars(ucfirst($order['payment_status'])) . '
                    </td>
                </tr>
            </table>
            
            <table class="address-section">
                <tr>
                    <td width="50%">
                        <div class="address-block">
                            <strong>Bill From:</strong><br>
                            DigiEcho<br>
                            House 815, West Kazipara<br>
                            Mirpur, Dhaka, Bangladesh 1216<br>
                            Phone: +880 1700-000000<br>
                            Email: contact@digiecho.com
                        </div>
                    </td>
                    <td width="50%">
                        <div class="address-block">
                            <strong>Bill To:</strong><br>
                            ' . htmlspecialchars($order['billing_first_name'] . ' ' . $order['billing_last_name']) . '<br>';
                            
        if (!empty($order['billing_company'])) {
            $html .= htmlspecialchars($order['billing_company']) . '<br>';
        }
        
        $html .= htmlspecialchars($order['billing_address_line_1']) . '<br>';
        
        if (!empty($order['billing_address_line_2'])) {
            $html .= htmlspecialchars($order['billing_address_line_2']) . '<br>';
        }
        
        $html .= htmlspecialchars($order['billing_city'] . ', ' . ($order['billing_state'] ?? '') . ' ' . $order['billing_postal_code']) . '<br>
                            ' . htmlspecialchars($order['billing_country']) . '<br>
                            Phone: ' . htmlspecialchars($order['billing_phone']) . '
                        </div>
                    </td>
                </tr>
            </table>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="40%">Item Description</th>
                        <th width="10%">Qty</th>
                        <th width="15%">Unit Price</th>
                        <th width="15%">Tax</th>
                        <th width="15%">Total</th>
                    </tr>
                </thead>
                <tbody>';
                
        $i = 1;
        foreach ($items as $item) {
            $html .= '<tr>
                        <td class="text-center">' . $i++ . '</td>
                        <td>
                            <strong>' . htmlspecialchars($item['product_name']) . '</strong><br>
                            <small>SKU: ' . htmlspecialchars($item['product_sku'] ?? 'N/A') . '</small>
                        </td>
                        <td class="text-center">' . (int)$item['quantity'] . '</td>
                        <td class="text-right currency">৳' . number_format((float)$item['unit_price'], 2) . '</td>
                        <td class="text-right currency">৳0.00</td>
                        <td class="text-right currency">৳' . number_format((float)$item['total_price'], 2) . '</td>
                    </tr>';
        }
        
        $html .= '</tbody>
            </table>
            
            <table class="totals-table">
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td class="text-right currency">৳' . number_format($subtotal, 2) . '</td>
                </tr>';
                
        if ($discount > 0) {
            $html .= '<tr>
                        <td>Discount:</td>
                        <td class="text-right currency">-৳' . number_format($discount, 2) . '</td>
                    </tr>';
        }
        
        if ($tax > 0) {
            $html .= '<tr>
                        <td>Tax/VAT:</td>
                        <td class="text-right currency">৳' . number_format($tax, 2) . '</td>
                    </tr>';
        }
        
        if ($shipping > 0) {
            $html .= '<tr>
                        <td>Shipping:</td>
                        <td class="text-right currency">৳' . number_format($shipping, 2) . '</td>
                    </tr>';
        }
        
        $html .= '<tr class="total-final">
                    <td><strong>Grand Total:</strong></td>
                    <td class="text-right currency"><strong>৳' . number_format($grand, 2) . '</strong></td>
                </tr>
            </table>
            
            <div class="footer">
                <p><strong>Terms & Conditions:</strong></p>
                <p>Payment is due within 30 days from the invoice date. Returns are accepted within 7 days with original receipt.</p>
                <p>Late payment fee of 1.5% per month will be applied to overdue amounts.</p>
                <p>All prices are in Bangladeshi Taka (BDT) and include applicable taxes.</p>
                <br>
                <p><strong>DigiEcho - Your Digital Commerce Partner</strong></p>
                <p>Thank you for choosing DigiEcho! We appreciate your business.</p>
                <p><small>Generated on ' . date('F d, Y \a\t g:i A') . ' (Bangladesh Standard Time)</small></p>
                <p><small>Invoice ID: ' . $order['id'] . ' | Order: ' . htmlspecialchars($order['order_number']) . '</small></p>
            </div>
        </body>
        </html>';
        
        // Load HTML content
        $dompdf->loadHtml($html);
        
        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Render the HTML as PDF
        $dompdf->render();
        
        // Output the generated PDF
        $filename = 'Invoice-' . $order['order_number'] . '.pdf';
        $dompdf->stream($filename, array('Attachment' => true));
    }
}
?>
