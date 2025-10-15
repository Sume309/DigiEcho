<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/settings.php';
try {
    require __DIR__ . '/../vendor/autoload.php';
} catch (Exception $e) {
    // Fallback if autoload fails
    require_once __DIR__ . '/../src/db/MysqliDb.php';
}

try {
    $db = new \MysqliDb();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

header('Content-Type: application/json');

function getOrderStats($filters = null) {
    global $db;
    
    try {
        $stats = [];

        // Apply filters if provided
        $whereClause = '';
        $whereParams = [];
        
        if ($filters) {
            $conditions = [];
            
            // Date range filter
            if (!empty($filters['date_range'])) {
                switch ($filters['date_range']) {
                    case 'today':
                        $conditions[] = "DATE(created_at) = CURDATE()";
                        break;
                    case 'week':
                        $conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                        break;
                    case 'month':
                        $conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                        break;
                    case 'quarter':
                        $conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
                        break;
                    case 'year':
                        $conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                        break;
                }
            }
            
            // Status filter
            if (!empty($filters['status'])) {
                $conditions[] = "status = '" . $db->escape($filters['status']) . "'";
            }
            
            // Payment filter
            if (!empty($filters['payment'])) {
                $conditions[] = "payment_status = '" . $db->escape($filters['payment']) . "'";
            }
            
            if (!empty($conditions)) {
                $whereClause = ' WHERE ' . implode(' AND ', $conditions);
            }
        }

        // Total orders with filters
        $stats['total_orders'] = (int)$db->rawQueryOne("SELECT COUNT(*) as count FROM orders" . $whereClause)['count'];

        // Status counts with filters
        $statusQuery = "SELECT status, COUNT(*) as count FROM orders" . $whereClause . " GROUP BY status";
        $statusCounts = $db->rawQuery($statusQuery);
        $stats['status_counts'] = [];
        if (is_array($statusCounts)) {
            foreach ($statusCounts as $row) {
                $stats['status_counts'][$row['status']] = (int)$row['count'];
            }
        }

        // Revenue calculation with filters
        $revenueConditions = $whereClause ? $whereClause . " AND payment_status = 'paid'" : " WHERE payment_status = 'paid'";
        $revenueQuery = "SELECT COALESCE(SUM(total_amount),0) as revenue FROM orders" . $revenueConditions;
        $stats['monthly_revenue'] = (float)$db->rawQueryOne($revenueQuery)['revenue'];

        // Specific status counts with filters
        $pendingConditions = $whereClause ? $whereClause . " AND status = 'pending'" : " WHERE status = 'pending'";
        $stats['pending_orders'] = (int)$db->rawQueryOne("SELECT COUNT(*) as count FROM orders" . $pendingConditions)['count'];

        $processingConditions = $whereClause ? $whereClause . " AND status = 'processing'" : " WHERE status = 'processing'";
        $stats['processing_orders'] = (int)$db->rawQueryOne("SELECT COUNT(*) as count FROM orders" . $processingConditions)['count'];

        $deliveredConditions = $whereClause ? $whereClause . " AND status = 'delivered'" : " WHERE status = 'delivered'";
        $stats['completed_orders'] = (int)$db->rawQueryOne("SELECT COUNT(*) as count FROM orders" . $deliveredConditions)['count'];

        return $stats;
    } catch (Exception $e) {
        // Return default stats if database query fails
        return [
            'total_orders' => 0,
            'completed_orders' => 0,
            'pending_orders' => 0,
            'processing_orders' => 0,
            'monthly_revenue' => 0.0,
            'status_counts' => []
        ];
    }
}

function getRecentOrders($limit = 10, $filters = null) {
    global $db;
    try {
        // Apply filters if provided
        if ($filters) {
            // Date range filter
            if (!empty($filters['date_range'])) {
                switch ($filters['date_range']) {
                    case 'today':
                        $db->where('DATE(created_at)', 'CURDATE()', '=');
                        break;
                    case 'week':
                        $db->where('created_at', 'DATE_SUB(NOW(), INTERVAL 1 WEEK)', '>=');
                        break;
                    case 'month':
                        $db->where('created_at', 'DATE_SUB(NOW(), INTERVAL 1 MONTH)', '>=');
                        break;
                    case 'quarter':
                        $db->where('created_at', 'DATE_SUB(NOW(), INTERVAL 3 MONTH)', '>=');
                        break;
                    case 'year':
                        $db->where('created_at', 'DATE_SUB(NOW(), INTERVAL 1 YEAR)', '>=');
                        break;
                }
            }
            
            // Status filter
            if (!empty($filters['status'])) {
                $db->where('status', $filters['status']);
            }
            
            // Payment filter
            if (!empty($filters['payment'])) {
                $db->where('payment_status', $filters['payment']);
            }
        }
        
        return $db->orderBy('created_at', 'DESC')->get('orders', $limit);
    } catch (Exception $e) {
        return [];
    }
}

function getOrderById($id) {
    global $db;
    $order = $db->where('id', $id)->getOne('orders');
    if ($order) {
        // Join with products to include product_name and product_sku for UI
        $db->join('products', 'order_items.product_id = products.id', 'LEFT');
        $db->where('order_id', $id);
        $order['items'] = $db->get('order_items', null, 'order_items.*, products.name as product_name, products.sku as product_sku');
        $order['timeline'] = getOrderTimeline($id);
    }
    return $order;
}

function getOrderTimeline($orderId) {
    global $db;
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
    global $db;
    $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

    if (!in_array($status, $allowedStatuses)) {
        return ['success' => false, 'message' => 'Invalid status'];
    }

    // Capture old status before updating
    $oldStatus = $db->where('id', $orderId)->getValue('orders', 'status');

    $updateData = [
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if (!empty($notes)) {
        $updateData['notes'] = $notes;
    }

    $result = $db->where('id', $orderId)->update('orders', $updateData);

    if ($result) {
        // Log the status change if history table exists
        $tableCheck = $db->rawQuery("SHOW TABLES LIKE 'order_status_history'");
        if (is_array($tableCheck) && count($tableCheck) > 0) {
            $db->insert('order_status_history', [
                'order_id' => $orderId,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'changed_by' => $_SESSION['user_id'] ?? 1,
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Create notification for order status update
        $notificationServicePath = __DIR__ . '/../src/NotificationService.php';
        if (file_exists($notificationServicePath)) {
            require_once $notificationServicePath;
            try {
                $ns = new NotificationService();
                $order = $db->where('id', $orderId)->getOne('orders');
                if ($order) {
                    $ns->createOrderUpdateNotification(
                        $orderId,
                        $status,
                        $notes,
                        $order['order_number']
                    );
                }
            } catch (Exception $e) { /* ignore */ }
        }

        return ['success' => true, 'message' => 'Status updated successfully'];
    }

    return ['success' => false, 'message' => 'Failed to update status'];
}

// Helpers
function buildOrderNumber($prefix = 'ORD') {
    return sprintf('%s-%s-%04d', $prefix, time(), rand(1000, 9999));
}

function mapPaymentFilter($input)
{
    if ($input === 'unpaid') return 'pending';
    return $input;
}

function applyOrderFilters($keyword = '', $statusFilter = '', $paymentFilter = '', $dateRange = '', $userId = null)
{
    global $db;
    
    // User filter (if specific user is selected)
    if ($userId && is_numeric($userId)) {
        $db->where('user_id', (int)$userId);
    }
    
    // Status filter
    $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
    if ($statusFilter && in_array($statusFilter, $allowedStatuses, true)) {
        $db->where('status', $statusFilter);
    }

    // Payment status filter
    $allowedPayment = ['pending', 'paid', 'failed', 'refunded'];
    $paymentFilter = mapPaymentFilter($paymentFilter);
    if ($paymentFilter && in_array($paymentFilter, $allowedPayment, true)) {
        $db->where('payment_status', $paymentFilter);
    }

    // Date range filter
    if ($dateRange === 'today') {
        $db->where('created_at', date('Y-m-d 00:00:00'), '>=');
    } elseif ($dateRange === 'week') {
        $db->where('created_at', date('Y-m-d 00:00:00', strtotime('-7 days')), '>=');
    } elseif ($dateRange === 'month') {
        $db->where('created_at', date('Y-m-01 00:00:00'), '>=');
    }

    // Keyword search across multiple columns
    if ($keyword !== '') {
        $db->where('(order_number LIKE "%' . $db->escape($keyword) . '%" OR ' .
                  'status LIKE "%' . $db->escape($keyword) . '%" OR ' .
                  'payment_status LIKE "%' . $db->escape($keyword) . '%" OR ' .
                  'payment_method LIKE "%' . $db->escape($keyword) . '%" OR ' .
                  'transaction_id LIKE "%' . $db->escape($keyword) . '%" OR ' .
                  'notes LIKE "%' . $db->escape($keyword) . '%")');
    }
}

function createOrder(array $data)
{
    global $db;
    $customerName = trim($data['customer_name'] ?? '');
    $customerEmail = trim($data['customer_email'] ?? '');
    $customerPhone = trim($data['customer_phone'] ?? '');
    $orderType = in_array(($data['order_type'] ?? 'online'), ['online', 'pos'], true) ? $data['order_type'] : 'online';
    $notes = $data['notes'] ?? '';

    if ($customerName === '' || $customerEmail === '') {
        return ['success' => false, 'message' => 'Customer name and email are required'];
    }

    // Split name into first/last (best-effort)
    $parts = preg_split('/\s+/', $customerName);
    $firstName = $parts[0] ?? '';
    $lastName = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';

    $insert = [
        'order_number' => buildOrderNumber($orderType === 'pos' ? 'POS' : 'ORD'),
        'user_id' => $_SESSION['user_id'] ?? null,
        'order_type' => $orderType,
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_method' => 'cash', // Default payment method
        'transaction_id' => null,
        'subtotal' => 0.00,
        'discount_amount' => 0.00,
        'tax_amount' => 0.00,
        'shipping_amount' => 0.00,
        'total_amount' => 0.00,
        'currency' => 'BDT',
        'notes' => $notes,
        'billing_first_name' => $firstName,
        'billing_last_name' => $lastName,
        'billing_company' => null,
        'billing_address_line_1' => 'Admin Created Order',
        'billing_address_line_2' => null,
        'billing_city' => 'Dhaka',
        'billing_state' => null,
        'billing_postal_code' => '1000',
        'billing_country' => 'Bangladesh',
        'billing_phone' => $customerPhone,
        'shipping_first_name' => $firstName,
        'shipping_last_name' => $lastName,
        'shipping_company' => null,
        'shipping_address_line_1' => 'Admin Created Order',
        'shipping_address_line_2' => null,
        'shipping_city' => 'Dhaka',
        'shipping_state' => null,
        'shipping_postal_code' => '1000',
        'shipping_country' => 'Bangladesh',
        'shipping_phone' => $customerPhone,
        'processed_by' => $_SESSION['user_id'] ?? null,
        'processed_at' => null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    $id = $db->insert('orders', $insert);
    if ($id) {
        return ['success' => true, 'order_id' => $id, 'order_number' => $insert['order_number']];
    }

    return ['success' => false, 'message' => 'Failed to create order'];
}

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Function to get order status history
function getOrderStatusHistory($orderId) {
    global $db;
    return $db->where('order_id', $orderId)
              ->orderBy('created_at', 'DESC')
              ->get('order_status_history');
}

// Function to get order with all related data
function getOrderWithDetails($orderId) {
    global $db;
    $order = $db->where('id', $orderId)->getOne('orders');
    if ($order) {
        $db->join('products', 'order_items.product_id = products.id', 'LEFT');
        $db->where('order_id', $orderId);
        $order['items'] = $db->get('order_items', null, 'order_items.*, products.name as product_name, products.sku as product_sku');
        $order['timeline'] = getOrderStatusHistory($orderId);
    }
    return $order;
}

switch ($action) {
    case 'get_stats':
        try {
            // Get filter parameters
            $filters = [];
            if (isset($_GET['date_range']) && !empty($_GET['date_range'])) {
                $filters['date_range'] = $_GET['date_range'];
            }
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            if (isset($_GET['payment']) && !empty($_GET['payment'])) {
                $filters['payment'] = $_GET['payment'];
            }
            
            echo json_encode([
                'success' => true,
                'data' => getOrderStats(empty($filters) ? null : $filters),
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get stats: ' . $e->getMessage()
            ]);
        }
        break;

    case 'get_recent_orders':
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            // Get filter parameters
            $filters = [];
            if (isset($_GET['date_range']) && !empty($_GET['date_range'])) {
                $filters['date_range'] = $_GET['date_range'];
            }
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            if (isset($_GET['payment']) && !empty($_GET['payment'])) {
                $filters['payment'] = $_GET['payment'];
            }
            
            $rows = getRecentOrders($limit, empty($filters) ? null : $filters);
            // Enhanced mapping for dashboard recent orders with all required fields
            $data = [];
            foreach ($rows as $r) {
                $data[] = [
                    'id' => (int)$r['id'],
                    'order_number' => $r['order_number'],
                    'status' => $r['status'],
                    'total_amount' => $r['total_amount'] ?? '0.00',
                    'payment_status' => $r['payment_status'] ?? 'pending',
                    'created_at' => $r['created_at'],
                ];
            }
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get recent orders: ' . $e->getMessage()
            ]);
        }
        break;

    case 'get_report_metrics':
        // Basic metrics used by reports-analytics.php
        $totalOrders = (int)$db->getValue('orders', 'COUNT(*)');
        $totalRevenue = (float)$db->where('payment_status', 'paid')->getValue('orders', 'COALESCE(SUM(total_amount),0)');
        $avgOrderValue = $totalOrders > 0 ? ($db->getValue('orders', 'COALESCE(AVG(total_amount),0)')) : 0;
        $paidOrders = (int)$db->where('payment_status', 'paid')->getValue('orders', 'COUNT(*)');
        $conversionRate = $totalOrders > 0 ? round(($paidOrders / $totalOrders) * 100, 2) : 0;
        echo json_encode([
            'success' => true,
            'data' => [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'avg_order_value' => (float)$avgOrderValue,
                'conversion_rate' => $conversionRate,
            ]
        ]);
        break;

    case 'get_order':
        $orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order id']);
            break;
        }
        $order = getOrderById($orderId);
        if ($order) {
            echo json_encode(['success' => true, 'data' => $order]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
        }
        break;

    case 'fetch':
        // Handle DataTables request
        $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 0;
        $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
        $length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
        $orderColumnIndex = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
        $orderDir = isset($_POST['order'][0]['dir']) && in_array(strtoupper($_POST['order'][0]['dir']), ['ASC','DESC']) ? strtoupper($_POST['order'][0]['dir']) : 'DESC';

        // Custom filters from UI
        $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
        if ($keyword === '' && isset($_POST['search']['value'])) {
            // Fallback to DataTables search box
            $keyword = trim($_POST['search']['value']);
        }
        $statusFilter = isset($_POST['status']) ? trim($_POST['status']) : '';
        $paymentFilter = isset($_POST['payment']) ? trim($_POST['payment']) : '';
        $dateRange = isset($_POST['date_range']) ? trim($_POST['date_range']) : '';
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;

        // Define column mappings (whitelist)
        $columns = [
            'id', 'order_number', 'order_type', 'status', 'payment_status',
            'payment_method', 'transaction_id', 'subtotal', 'discount_amount',
            'tax_amount', 'total_amount', 'currency', 'notes', 'created_at', 'updated_at'
        ];

        // Total records without filtering
        $totalRecords = (int) $db->getValue('orders', 'count(*)');

        // Filtered count
        applyOrderFilters($keyword, $statusFilter, $paymentFilter, $dateRange, $userId);
        $filteredRecords = (int) $db->getValue('orders', 'count(*)');

        // Order by
        if (isset($columns[$orderColumnIndex])) {
            $db->orderBy($columns[$orderColumnIndex], $orderDir);
        } else {
            $db->orderBy('id', 'DESC');
        }

        // Fetch data for current page (reapply filters)
        applyOrderFilters($keyword, $statusFilter, $paymentFilter, $dateRange, $userId);
        $orders = $db->get('orders', [$start, $length]);

        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                'id' => (int)$order['id'],
                'order_number' => $order['order_number'],
                'order_type' => ucfirst($order['order_type']),
                'status' => $order['status'],
                'payment_status' => ucfirst($order['payment_status'] ?: 'pending'),
                'payment_method' => ucfirst($order['payment_method'] ?: 'cash'),
                'transaction_id' => $order['transaction_id'] ?: 'N/A',
                // Important: use dot as decimal separator without thousands separator for JS parseFloat
                'subtotal' => number_format((float)$order['subtotal'], 2, '.', ''),
                'discount_amount' => number_format((float)$order['discount_amount'], 2, '.', ''),
                'tax_amount' => number_format((float)$order['tax_amount'], 2, '.', ''),
                'total_amount' => number_format((float)$order['total_amount'], 2, '.', ''),
                'currency' => $order['currency'],
                'notes' => $order['notes'] ?: 'N/A',
                'created_at' => date('M d, Y H:i', strtotime($order['created_at'])),
                'updated_at' => date('M d, Y H:i', strtotime($order['updated_at'])),
            ];
        }

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
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

    case 'update_payment_method':
        $orderId = (int)($_POST['order_id'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? '';
        $paymentStatus = $_POST['payment_status'] ?? '';

        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            break;
        }

        $allowedMethods = ['cash', 'card', 'bank', 'bKash', 'nagad', 'rocket', 'credit_card', 'debit_card'];
        $allowedStatuses = ['pending', 'paid', 'failed', 'refunded'];

        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        
        if (!empty($paymentMethod) && in_array($paymentMethod, $allowedMethods)) {
            $updateData['payment_method'] = $paymentMethod;
        }
        
        if (!empty($paymentStatus) && in_array($paymentStatus, $allowedStatuses)) {
            $updateData['payment_status'] = $paymentStatus;
        }

        if (count($updateData) > 1) { // More than just updated_at
            $result = $db->where('id', $orderId)->update('orders', $updateData);
            if ($result) {
                // Create notification for payment update
                $notificationServicePath = __DIR__ . '/../src/NotificationService.php';
                if (file_exists($notificationServicePath)) {
                    require_once $notificationServicePath;
                    try {
                        $ns = new NotificationService();
                        $order = $db->where('id', $orderId)->getOne('orders');
                        if ($order && !empty($paymentStatus)) {
                            $ns->createPaymentNotification(
                                $orderId,
                                $order['total_amount'],
                                $paymentMethod ?: $order['payment_method'],
                                $paymentStatus,
                                $order['order_number']
                            );
                        }
                    } catch (Exception $e) { /* ignore */ }
                }
                
                echo json_encode(['success' => true, 'message' => 'Payment information updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update payment information']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No valid payment data provided']);
        }
        break;

    case 'create_order':
        $payload = [
            'customer_name' => $_POST['customer_name'] ?? '',
            'customer_email' => $_POST['customer_email'] ?? '',
            'customer_phone' => $_POST['customer_phone'] ?? '',
            'order_type' => $_POST['order_type'] ?? 'online',
            'notes' => $_POST['notes'] ?? '',
        ];
        echo json_encode(createOrder($payload));
        break;

    case 'export_orders':
        // If this is a GET request, stream CSV for download; otherwise return JSON stub
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Gather filters from query string
            $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
            $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
            $paymentFilter = isset($_GET['payment']) ? trim($_GET['payment']) : '';
            $dateRange = isset($_GET['date_range']) ? trim($_GET['date_range']) : '';
            $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

            // Apply same filtering logic as DataTables fetch
            applyOrderFilters($keyword, $statusFilter, $paymentFilter, $dateRange, $userId);

            $orders = $db->orderBy('id', 'DESC')->get('orders');

            // Stream CSV
            $filename = 'orders_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $out = fopen('php://output', 'w');
            // Header row
            fputcsv($out, [
                'ID', 'Order Number', 'Type', 'Status', 'Payment Status', 'Payment Method',
                'Subtotal', 'Discount', 'Tax', 'Total', 'Currency', 'Created At'
            ]);

            foreach ($orders as $order) {
                fputcsv($out, [
                    $order['id'],
                    $order['order_number'],
                    $order['order_type'],
                    $order['status'],
                    $order['payment_status'],
                    $order['payment_method'],
                    $order['subtotal'],
                    $order['discount_amount'],
                    $order['tax_amount'],
                    $order['total_amount'],
                    $order['currency'],
                    $order['created_at'],
                ]);
            }
            fclose($out);
            exit; // Prevent further output
        } else {
            echo json_encode(['success' => false, 'message' => 'Export must be a GET request']);
        }
        break;

    // STUB ENDPOINTS: Payment Management
    case 'get_payments':
    case 'get_refunds':
    case 'get_invoices':
        // Standard DataTables empty response structure
        $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 0;
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ]);
        break;

    case 'get_payment_stats':
        echo json_encode([
            'success' => true,
            'data' => [
                'total_payments' => 0,
                'monthly_payments' => 0,
                'pending_payments' => 0,
                'total_refunds' => 0,
            ]
        ]);
        break;

    // STUB ENDPOINTS: Returns Management
    case 'get_return_requests':
        $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 0;
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ]);
        break;

    case 'get_returns_stats':
        echo json_encode([
            'success' => true,
            'data' => [
                'total_returns' => 0,
                'pending_returns' => 0,
                'approved_returns' => 0,
                'rejected_returns' => 0,
            ]
        ]);
        break;

    case 'get_return_details':
        echo json_encode(['success' => false, 'message' => 'Return details not implemented']);
        break;

    // STUB ENDPOINTS: Shipping Management
    case 'get_shipments':
        $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 0;
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ]);
        break;

    case 'get_shipping_stats':
        echo json_encode([
            'success' => true,
            'data' => [
                'in_transit' => 0,
                'delivered_today' => 0,
                'pending_shipments' => 0,
            ]
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$db->disconnect();
?>
