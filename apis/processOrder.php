<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone to Bangladesh Standard Time
date_default_timezone_set('Asia/Dhaka');

// Composer autoloader for external packages (PHPMailer, TCPDF, etc.)
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

require_once __DIR__ . '/../src/settings.php';
if (!class_exists('MysqliDb')) {
    require_once __DIR__ . '/../src/db/MysqliDb.php';
}
require_once __DIR__ . '/../src/EmailService.php';
require_once __DIR__ . '/../src/NotificationService.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    $notificationService = new NotificationService();

    // Validate required fields
    $required_fields = [
        'billing_first_name', 'billing_last_name', 'billing_address_line_1', 'billing_city',
        'billing_postal_code', 'billing_country', 'billing_phone', 'shipping_first_name',
        'shipping_last_name', 'shipping_address_line_1', 'shipping_city', 'shipping_postal_code',
        'shipping_country', 'shipping_phone', 'payment_method', 'totalPrice', 'items',
        'discount_amount', 'tax_amount', 'grandTotal',
    ];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }

        // Allow numeric fields to be 0 or '0' or '0.00'
        if (in_array($field, ['discount_amount', 'tax_amount', 'totalPrice', 'grandTotal'])) {
            if (!is_numeric($_POST[$field])) {
                throw new Exception("Invalid numeric value for field: $field");
            }
        } else {
            // For non-numeric fields, check if empty (but allow '0')
            if (is_string($_POST[$field]) && empty($_POST[$field]) && $_POST[$field] !== '0') {
                throw new Exception("Missing required field: $field");
            }
        }
    }

    // Validate items
    $items = json_decode(json_encode($_POST['items']), true);
    if (empty($items)) {
        throw new Exception("Cart is empty");
    }

    $db->startTransaction();

    // Generate unique order number
    $order_number = 'ORD-' . time() . '-' . rand(1000, 9999);

    // Insert into orders table
    $order_data = [
        'order_number' => $order_number,
        'user_id' => isset($_SESSION['userid']) ? (int) $_SESSION['userid'] : null,
        'order_type' => 'online',
        'status' => 'pending',
        'payment_status' => $_POST['payment_method'] == 'cash' ? 'pending' : 'pending',
        'payment_method' => $_POST['payment_method'],
        'transaction_id' => isset($_POST['transaction_id']) ? $_POST['transaction_id'] : null,
        'subtotal' => (float) $_POST['totalPrice'],
        'discount_amount' => (float) $_POST['discount_amount'],
        'tax_amount' => (float) $_POST['tax_amount'],
        'shipping_amount' => 0.00,
        'total_amount' => (float) $_POST['grandTotal'],
        'currency' => 'BDT',
        'notes' => isset($_POST['notes']) ? $_POST['notes'] : null,
        'billing_first_name' => $_POST['billing_first_name'],
        'billing_last_name' => $_POST['billing_last_name'],
        'billing_company' => isset($_POST['billing_company']) ? $_POST['billing_company'] : null,
        'billing_address_line_1' => $_POST['billing_address_line_1'],
        'billing_address_line_2' => isset($_POST['billing_address_line_2']) ? $_POST['billing_address_line_2'] : null,
        'billing_city' => $_POST['billing_city'],
        'billing_state' => isset($_POST['billing_state']) ? $_POST['billing_state'] : null,
        'billing_postal_code' => $_POST['billing_postal_code'],
        'billing_country' => $_POST['billing_country'],
        'billing_phone' => $_POST['billing_phone'],
        'shipping_first_name' => $_POST['shipping_first_name'],
        'shipping_last_name' => $_POST['shipping_last_name'],
        'shipping_company' => isset($_POST['shipping_company']) ? $_POST['shipping_company'] : null,
        'shipping_address_line_1' => $_POST['shipping_address_line_1'],
        'shipping_address_line_2' => isset($_POST['shipping_address_line_2']) ? $_POST['shipping_address_line_2'] : null,
        'shipping_city' => $_POST['shipping_city'],
        'shipping_state' => isset($_POST['shipping_state']) ? $_POST['shipping_state'] : null,
        'shipping_postal_code' => $_POST['shipping_postal_code'],
        'shipping_country' => $_POST['shipping_country'],
        'shipping_phone' => $_POST['shipping_phone'],
        'processed_by' => null,
        'processed_at' => null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    $order_id = $db->insert('orders', $order_data);
    if (!$order_id) {
        throw new Exception('Failed to create order');
    }

    // Insert order items
    foreach ($items as $item) {
        if (!isset($item['id']) || !isset($item['name']) || !isset($item['quantity']) || !isset($item['price'])) {
            throw new Exception("Invalid item data for: {$item['name']}");
        }

        $product = $db->where('id', $item['id'])->getOne('products');
        if (!$product) {
            throw new Exception("Product not found: {$item['name']}");
        }

        $item_data = [
            'order_id' => $order_id,
            'product_id' => (int) $item['id'],
            'product_name' => $product['name'],
            'product_sku' => $product['sku'],
            'quantity' => (int) $item['quantity'],
            'unit_price' => (float) $item['price'],
            'total_price' => (float) ($item['quantity'] * $item['price']),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (!$db->insert('order_items', $item_data)) {
            throw new Exception("Failed to add item: {$item['name']}");
        }

        // Deduct stock quantity
        $current_stock = $product['stock_quantity'];
        $new_stock = $current_stock - (int) $item['quantity'];
        $db->where('id', (int) $item['id'])->update('products', ['stock_quantity' => $new_stock]);
    }

    // Insert payment transaction
    $gateway_response = null;
    if (in_array($_POST['payment_method'], ['debit_card', 'credit_card'])) {
        // Only store safe, non-sensitive metadata
        $cardMeta = isset($_POST['card_meta']) ? json_decode(json_encode($_POST['card_meta']), true) : null;
        if (is_array($cardMeta)) {
            $safe = [
                'type' => $cardMeta['type'] ?? $_POST['payment_method'],
                'brand' => $cardMeta['brand'] ?? 'card',
                'last4' => $cardMeta['last4'] ?? null,
                'exp_month' => $cardMeta['exp_month'] ?? null,
                'exp_year' => $cardMeta['exp_year'] ?? null,
            ];
            $gateway_response = json_encode(['card' => $safe]);
        }
    }

    $payment_data = [
        'order_id' => $order_id,
        'transaction_id' => isset($_POST['transaction_id']) ? $_POST['transaction_id'] : $order_number,
        'payment_method' => $_POST['payment_method'],
        'amount' => (float) $_POST['grandTotal'],
        'status' => 'pending',
        'gateway_response' => $gateway_response,
        'processed_at' => null,
        'created_at' => date('Y-m-d H:i:s'),
    ];
    if (!$db->insert('payment_transactions', $payment_data)) {
        throw new Exception('Failed to record payment transaction');
    }

    // Create admin notification for new order using NotificationService
    try {
        $customerId = isset($_SESSION['userid']) ? (int) $_SESSION['userid'] : null;
        $customerName = trim($_POST['billing_first_name'] . ' ' . $_POST['billing_last_name']);
        
        $notificationService->createOrderNotification(
            $order_id, 
            $customerId, 
            (float) $_POST['grandTotal'], 
            $order_number
        );

        // Payment pending notification
        $notificationService->createPaymentNotification(
            $order_id,
            (float) $_POST['grandTotal'],
            $_POST['payment_method'],
            'pending',
            $order_number
        );

        // High-value order alert if applicable
        $threshold = 0;
        try {
            $cfg = settings();
            if (isset($cfg['notifications']) && isset($cfg['notifications']['high_value_threshold'])) {
                $threshold = (float)$cfg['notifications']['high_value_threshold'];
            } elseif (isset($cfg['high_value_threshold'])) { // fallback
                $threshold = (float)$cfg['high_value_threshold'];
            } else {
                $threshold = 10000.0; // sensible default
            }
        } catch (Exception $e) { $threshold = 10000.0; }
        if ((float)$_POST['grandTotal'] >= $threshold) {
            $notificationService->createHighValueOrderAlert(
                $order_id,
                $order_number,
                (float) $_POST['grandTotal'],
                $threshold
            );
        }
    } catch (Exception $e) {
        // Log and continue without failing the order
        error_log('Notification handling error for order ' . $order_number . ': ' . $e->getMessage());
    }

    $db->commit();

    // Prepare immediate response to speed up user experience
    $response['success'] = true;
    $response['message'] = 'Order placed successfully';
    $response['order_number'] = $order_number;

    // Send response to client immediately and close connection so heavy tasks can continue
    $payload = json_encode($response);
    // Close the session to unblock the client
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header('Connection: close');
    header('Content-Length: ' . strlen($payload));
    echo $payload;
    // Try to flush/finish request
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        if (ob_get_length() === false) {
            // nothing
        } elseif (ob_get_length() > 0) {
            @ob_end_flush();
        }
        @flush();
    }
    // Allow script to continue even if client disconnects
    ignore_user_abort(true);

    // Continue post-processing (email) after response is sent
    try {
        // Initialize services using proper namespacing
        $emailService = new App\EmailService();

        // Prepare customer email
        $customerEmail = '';
        if (isset($_SESSION['userid']) && $_SESSION['userid']) {
            // Get user email from database
            $user = $db->where('id', $_SESSION['userid'])->getOne('users', ['email', 'first_name', 'last_name']);
            $customerEmail = $user['email'] ?? '';
            $customerName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        } else {
            // For guest orders, fallback to billing email if provided
            $customerEmail = $_POST['billing_email'] ?? '';
            $customerName = trim($_POST['billing_first_name'] . ' ' . $_POST['billing_last_name']);
        }

        if ($customerEmail) {
            // Prepare order data for email
            $emailOrderData = [
                'order_number' => $order_number,
                'customer_name' => $customerName ?: 'Valued Customer',
                'created_at' => $order_data['created_at'],
                'total_amount' => $order_data['total_amount'],
                'payment_method' => $order_data['payment_method'],
                'shipping_name' => trim($_POST['shipping_first_name'] . ' ' . $_POST['shipping_last_name']),
                'shipping_address' => $_POST['shipping_address_line_1'] . ', ' . $_POST['shipping_city'] . ', ' . $_POST['shipping_postal_code'],
                'shipping_phone' => $_POST['shipping_phone'],
            ];

            // Send confirmation email (no invoice attachment)
            $emailResult = $emailService->sendOrderConfirmationEmail($emailOrderData, $customerEmail, null);

            if ($emailResult['success'] ?? false) {
                error_log("Order confirmation email sent successfully for order: " . $order_number);
            } else {
                $err = is_array($emailResult) && isset($emailResult['message']) ? $emailResult['message'] : 'unknown error';
                error_log("Failed to send order confirmation email for order: " . $order_number . " - " . $err);
            }
        } else {
            error_log("No customer email available for order: " . $order_number);
        }

    } catch (Exception $e) {
        error_log("Email/PDF processing error for order " . $order_number . ": " . $e->getMessage());
    }
} catch (Exception $e) {
    if (isset($db) && $db instanceof MysqliDb) {
        $db->rollback();
    }
    $response['message'] = $e->getMessage();
}

// If we reached here without early echo (error path), send response now
if (!(isset($response['success']) && $response['success'] === true)) {
    echo json_encode($response);
}
if (isset($db) && $db instanceof MysqliDb) {
    $db->disconnect();
}