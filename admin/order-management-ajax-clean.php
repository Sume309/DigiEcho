<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';

$db = new \MysqliDb();

header('Content-Type: application/json');

function getOrderStats() {
    $stats = [];

    // Total orders
    $stats['total_orders'] = $db->getValue('orders', 'count(*)');

    // Status counts
    $statusCounts = $db->rawQuery("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $stats['status_counts'] = [];
    foreach ($statusCounts as $row) {
        $stats['status_counts'][$row['status']] = $row['count'];
    }

    // Revenue this month
    $currentMonth = date('Y-m-01');
    $stats['monthly_revenue'] = $db->where('created_at', $currentMonth, '>=')->where('payment_status', 'paid')->getValue('orders', 'SUM(total_amount)');

    // Pending orders
    $stats['pending_orders'] = $db->where('status', 'pending')->getValue('orders', 'count(*)');

    // Processing orders
    $stats['processing_orders'] = $db->where('status', 'processing')->getValue('orders', 'count(*)');

    // Completed orders
    $stats['completed_orders'] = $db->where('status', 'delivered')->getValue('orders', 'count(*)');

    return $stats;
}

function getRecentOrders($limit = 10) {
    return $db->orderBy('created_at', 'DESC')->get('orders', $limit);
}

function getOrderById($id) {
    $order = $db->where('id', $id)->getOne('orders');
    if ($order) {
        $order['items'] = $db->where('order_id', $id)->get('order_items');
        $order['timeline'] = getOrderTimeline($id);
    }
    return $order;
}

function getOrderTimeline($orderId) {
    $timeline = [];

    // Get order
    $order = $db->where('id', $orderId)->getOne('orders');
    if ($order) {
        $timeline[] = [
            'status' => 'placed',
            'title' => 'Order Placed',
            'description' => 'Order was placed successfully',
            'timestamp' => $order['created_at'],
            'icon' => 'fas fa-shopping-cart',
            'color' => 'primary'
        ];

        if ($order['status'] != 'pending') {
            $timeline[] = [
                'status' => 'confirmed',
                'title' => 'Order Confirmed',
                'description' => 'Order has been confirmed',
                'timestamp' => $order['created_at'],
                'icon' => 'fas fa-check-circle',
                'color' => 'success'
            ];
        }

        if (in_array($order['status'], ['processing', 'shipped', 'delivered'])) {
            $timeline[] = [
                'status' => 'processing',
                'title' => 'Order Processing',
                'description' => 'Order is being processed',
                'timestamp' => $order['created_at'],
                'icon' => 'fas fa-cog',
                'color' => 'info'
            ];
        }

        if (in_array($order['status'], ['shipped', 'delivered'])) {
            $timeline[] = [
                'status' => 'shipped',
                'title' => 'Order Shipped',
                'description' => 'Order has been shipped',
                'timestamp' => $order['created_at'],
                'icon' => 'fas fa-truck',
                'color' => 'warning'
            ];
        }

        if ($order['status'] == 'delivered') {
            $timeline[] = [
                'status' => 'delivered',
                'title' => 'Order Delivered',
                'description' => 'Order has been delivered successfully',
                'timestamp' => $order['updated_at'],
                'icon' => 'fas fa-check-double',
                'color' => 'success'
            ];
        }

        if ($order['status'] == 'cancelled') {
            $timeline[] = [
                'status' => 'cancelled',
                'title' => 'Order Cancelled',
                'description' => 'Order was cancelled',
                'timestamp' => $order['updated_at'],
                'icon' => 'fas fa-times-circle',
                'color' => 'danger'
            ];
        }

        if ($order['status'] == 'refunded') {
            $timeline[] = [
                'status' => 'refunded',
                'title' => 'Order Refunded',
                'description' => 'Order has been refunded',
                'timestamp' => $order['updated_at'],
                'icon' => 'fas fa-undo-alt',
                'color' => 'secondary'
            ];
        }
    }

    return array_reverse($timeline);
}

function updateOrderStatus($orderId, $status, $notes = '') {
    $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

    if (!in_array($status, $allowedStatuses)) {
        return ['success' => false, 'message' => 'Invalid status'];
    }

    $updateData = [
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if (!empty($notes)) {
        $updateData['notes'] = $notes;
    }

    $result = $db->where('id', $orderId)->update('orders', $updateData);

    if ($result) {
        // Log the status change
        $db->insert('order_status_history', [
            'order_id' => $orderId,
            'old_status' => $db->where('id', $orderId)->getValue('orders', 'status'),
            'new_status' => $status,
            'changed_by' => $_SESSION['user_id'] ?? 1,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return ['success' => true, 'message' => 'Status updated successfully'];
    }

    return ['success' => false, 'message' => 'Failed to update status'];
}

function createOrder($data) {
    try {
        $db->startTransaction();

        // Generate order number
        $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Create order
        $orderData = [
            'order_number' => $orderNumber,
            'user_id' => $_SESSION['user_id'] ?? 1,
            'order_type' => $data['order_type'] ?? 'online',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'currency' => 'BDT',
            'notes' => $data['notes'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $orderId = $db->insert('orders', $orderData);

        if (!$orderId) {
            throw new Exception('Failed to create order');
        }

        $db->commit();
        return ['success' => true, 'message' => 'Order created successfully', 'order_id' => $orderId];
    } catch (Exception $e) {
        $db->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function exportOrders($filters = []) {
    // This would implement order export functionality
    // For now, return placeholder response
    return ['success' => true, 'message' => 'Export functionality would be implemented here'];
}

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_stats':
        echo json_encode(['success' => true, 'data' => getOrderStats()]);
        break;

    case 'get_recent_orders':
        echo json_encode(['success' => true, 'data' => getRecentOrders()]);
        break;

    case 'get_order':
        $orderId = (int)($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
        if ($orderId > 0) {
            echo json_encode(['success' => true, 'data' => getOrderById($orderId)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        }
        break;

    case 'update_status':
        $orderId = (int)($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $notes = $_POST['notes'] ?? '';

        if ($orderId > 0 && !empty($status)) {
            echo json_encode(updateOrderStatus($orderId, $status, $notes));
        } else {
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        }
        break;

    case 'create_order':
        $customerName = $_POST['customer_name'] ?? '';
        $customerEmail = $_POST['customer_email'] ?? '';
        $customerPhone = $_POST['customer_phone'] ?? '';
        $orderType = $_POST['order_type'] ?? 'online';
        $notes = $_POST['notes'] ?? '';

        if (empty($customerName) || empty($customerEmail)) {
            echo json_encode(['success' => false, 'message' => 'Customer name and email are required']);
        } else {
            echo json_encode(createOrder([
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'order_type' => $orderType,
                'notes' => $notes
            ]));
        }
        break;

    case 'export_orders':
        echo json_encode(exportOrders());
        break;

    case 'fetch':
        // Handle DataTables request
        $draw = $_POST['draw'];
        $start = $_POST['start'];
        $length = $_POST['length'];
        $searchValue = $_POST['search']['value'];
        $orderColumnIndex = $_POST['order'][0]['column'];
        $orderColumnName = $_POST['columns'][$orderColumnIndex]['data'];
        $orderDir = $_POST['order'][0]['dir'];

        // Define column mappings
        $columns = [
            'id', 'order_number', 'order_type', 'status', 'payment_status',
            'payment_method', 'transaction_id', 'subtotal', 'discount_amount',
            'tax_amount', 'total_amount', 'currency', 'notes', 'created_at', 'updated_at'
        ];

        // Total records without filtering
        $totalRecords = $db->getValue('orders', "count(*)");

        // Build where clause for search
        if (!empty($searchValue)) {
            $db->where('order_number', '%' . $searchValue . '%', 'LIKE');
            $db->orWhere('status', '%' . $searchValue . '%', 'LIKE');
            $db->orWhere('payment_status', '%' . $searchValue . '%', 'LIKE');
            $db->orWhere('payment_method', '%' . $searchValue . '%', 'LIKE');
            $db->orWhere('transaction_id', '%' . $searchValue . '%', 'LIKE');
            $db->orWhere('notes', '%' . $searchValue . '%', 'LIKE');
        }

        // Get filtered count
        $filteredRecords = $db->getValue('orders', "count(*)");

        // Build order by clause
        if (isset($columns[$orderColumnIndex])) {
            $db->orderBy($columns[$orderColumnIndex], $orderDir);
        } else {
            $db->orderBy('id', 'DESC');
        }

        // Fetch data for the current page
        if (!empty($searchValue)) {
            $db->where('order_number', '%' . $searchValue . '%', 'LIKE');
            $db->orWhere('status', '%' . $searchValue . '%', 'LIKE');
            $db->orWhere('payment_status', '%' . $searchValue . '%', 'LIKE');
            $db->orWhere('payment_method', '%' . $searchValue . '%', 'LIKE');
            $db->orWhere('transaction_id', '%' . $searchValue . '%', 'LIKE');
            $db->orWhere('notes', '%' . $searchValue . '%', 'LIKE');
        }

        $orders = $db->get('orders', array($start, $length));

        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                "id" => $order['id'],
                "order_number" => $order['order_number'],
                "order_type" => ucfirst($order['order_type']),
                "status" => $order['status'],
                "payment_status" => ucfirst($order['payment_status']),
                "payment_method" => ucfirst($order['payment_method']),
                "transaction_id" => $order['transaction_id'] ?: 'N/A',
                "subtotal" => number_format($order['subtotal'], 2),
                "discount_amount" => number_format($order['discount_amount'], 2),
                "tax_amount" => number_format($order['tax_amount'], 2),
                "total_amount" => number_format($order['total_amount'], 2),
                "currency" => $order['currency'],
                "notes" => $order['notes'] ?: 'N/A',
                "created_at" => date('M d, Y H:i', strtotime($order['created_at'])),
                "updated_at" => date('M d, Y H:i', strtotime($order['updated_at']))
            ];
        }

        echo json_encode([
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $filteredRecords,
            "data" => $data
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$db->disconnect();
?>
