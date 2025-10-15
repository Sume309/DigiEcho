<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/settings.php';
require_once __DIR__ . '/../../src/db/MysqliDb.php';
require_once __DIR__ . '/../../src/NotificationService.php';

use App\auth\Admin;

// Set JSON content type
header('Content-Type: application/json');

// Check admin authentication
if (!Admin::Check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Initialize database
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    $notificationService = new NotificationService();
    
    // Handle different actions
    $action = $_GET['action'] ?? 'stats';
    
    if ($action === 'notifications') {
        // Get notifications with pagination and filtering
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Apply filters
        $typeFilter = $_GET['type'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        $dateFilter = $_GET['date'] ?? '';
        
        // Build query with filters
        if ($typeFilter) {
            $db->where('type', $typeFilter);
        }
        
        if ($statusFilter === 'unread') {
            $db->where('is_read', 0);
        } elseif ($statusFilter === 'read') {
            $db->where('is_read', 1);
        }
        
        if ($dateFilter) {
            switch ($dateFilter) {
                case 'today':
                    $db->where('DATE(created_at)', date('Y-m-d'));
                    break;
                case 'week':
                    $db->where('created_at', date('Y-m-d', strtotime('-7 days')), '>=');
                    break;
                case 'month':
                    $db->where('created_at', date('Y-m-d', strtotime('-30 days')), '>=');
                    break;
            }
        }
        
        // Get total count for pagination
        $totalCount = $db->getValue('notifications', 'COUNT(*)');
        $totalPages = ceil($totalCount / $limit);
        
        // Get notifications
        $db->orderBy('created_at', 'DESC');
        $notifications = $db->get('notifications', [$offset, $limit]);
        
        echo json_encode([
            'success' => true,
            'data' => $notifications,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalCount,
                'per_page' => $limit
            ]
        ]);
        exit;
    }
    
    if ($action === 'mark_notifications_read') {
        // Mark all notifications as read
        $result = $notificationService->markAllAsRead();
        
        echo json_encode([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
        exit;
    }
    
    if ($action === 'unread_count') {
        // Get count of unread notifications
        $count = $notificationService->getUnreadCount();
        
        echo json_encode([
            'success' => true,
            'count' => $count
        ]);
        exit;
    }
    
    // Default: Get basic statistics
    $totalUsers = $db->getValue('users', 'COUNT(*)') ?: 0;
    $totalProducts = $db->getValue('products', 'COUNT(*)') ?: 0;
    $totalOrders = $db->getValue('orders', 'COUNT(*)') ?: 0;
    $todaysSales = $db->getValue('orders', 'SUM(total_amount)', 'DATE(created_at) = CURDATE() AND status = "completed"') ?: 0;
    
    // Get order status counts
    $pendingOrders = $db->getValue('orders', 'COUNT(*)', 'status = "pending"') ?: 0;
    $completedOrders = $db->getValue('orders', 'COUNT(*)', 'status = "completed"') ?: 0;
    $cancelledOrders = $db->getValue('orders', 'COUNT(*)', 'status = "cancelled"') ?: 0;
    $outOfStockProducts = $db->getValue('products', 'COUNT(*)', 'stock_quantity = 0') ?: 0;
    
    // Get sales data
    $thisWeekSales = $db->getValue('orders', 'SUM(total_amount)', 'WEEK(created_at) = WEEK(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status = "completed"') ?: 0;
    $thisMonthSales = $db->getValue('orders', 'SUM(total_amount)', 'MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status = "completed"') ?: 0;
    
    // Get hot items count
    $hotItems = $db->getValue('products', 'COUNT(*)', 'is_hot_item = 1') ?: 0;
    
    // Active/inactive products
    $activeProducts = $db->getValue('products', 'COUNT(*)', 'status = "active"') ?: 0;
    $inactiveProducts = $db->getValue('products', 'COUNT(*)', 'status = "inactive"') ?: 0;
    
    // Low stock products
    $lowStockProducts = $db->getValue('products', 'COUNT(*)', 'stock_quantity <= min_stock_level AND stock_quantity > 0') ?: 0;
    
    // Total revenue
    $totalRevenue = $db->getValue('orders', 'SUM(total_amount)', 'status = "completed"') ?: 0;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'totalUsers' => $totalUsers,
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'todaysSales' => $todaysSales,
            'thisWeekSales' => $thisWeekSales,
            'thisMonthSales' => $thisMonthSales,
            'totalRevenue' => $totalRevenue,
            'pendingOrders' => $pendingOrders,
            'completedOrders' => $completedOrders,
            'cancelledOrders' => $cancelledOrders,
            'activeProducts' => $activeProducts,
            'inactiveProducts' => $inactiveProducts,
            'lowStockProducts' => $lowStockProducts,
            'outOfStockProducts' => $outOfStockProducts,
            'hotItems' => $hotItems,
            'timestamp' => date('Y-m-d H:i:s')
        ],
        // For compatibility with existing auto-refresh code
        'totalUsers' => $totalUsers,
        'totalProducts' => $totalProducts,
        'totalOrders' => $totalOrders,
        'todaysSales' => $todaysSales
    ]);
    
} catch (Exception $e) {
    error_log('Dashboard Stats API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
?>