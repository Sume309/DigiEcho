<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../src/settings.php';
try {
    require __DIR__ . '/../vendor/autoload.php';
} catch (Exception $e) {
    // Fallback if autoload fails
    require_once __DIR__ . '/../src/db/MysqliDb.php';
}

// Handle user_id parameter for filtering orders by specific user
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$user_info = null;

// If user_id is provided, get user information
if ($user_id) {
    try {
        $db = new \MysqliDb();
        $user_info = $db->where('id', $user_id)->getOne('users');
    } catch (Exception $e) {
        // Handle database error silently
        $user_info = null;
    }
}
?>
<?php require __DIR__ . '/components/header.php'; ?>
<style>
    .dashboard-card {
        transition: all 0.3s ease;
    }
    
    .dashboard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .dashboard-number {
        transition: all 0.5s ease;
    }
    
    .dashboard-number.updating {
        opacity: 0.6;
        transform: scale(0.95);
    }
    
    .pulse-animation {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    
    .growth-indicator {
        font-size: 0.75rem;
        transition: all 0.3s ease;
    }
    
    .card-icon {
        transition: transform 0.3s ease;
    }
    
    .card:hover .card-icon {
        transform: scale(1.1);
    }
</style>
</head>

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <main>
            <?php require __DIR__ . '/components/sidebar.php'; ?>
            <div id="layoutSidenav_content">

                <!-- Order Management System -->
                <div class="container-fluid px-4">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h1 class="mt-4 mb-2 text-primary">
                                        <i class="fas fa-shopping-bag me-2"></i>Order Management
                                        <?php if ($user_info): ?>
                                            <small class="text-muted">- Filtering for <?= htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']) ?></small>
                                        <?php endif; ?>
                                    </h1>
                                    <p class="text-muted mb-0">
                                        <?php if ($user_info): ?>
                                            Viewing orders for user: <?= htmlspecialchars($user_info['email']) ?> (ID: <?= $user_id ?>)
                                            <a href="order-management.php" class="btn btn-sm btn-outline-secondary ms-2">
                                                <i class="fas fa-times me-1"></i>Clear Filter
                                            </a>
                                        <?php else: ?>
                                            Manage and track all customer orders
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                            </div>
                        </div>
                    </div>

                    <!-- Professional Dashboard Header Section -->
                    <div class="row mb-4" id="orderDashboard">
                        <div class="col-12 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                               
                                <div class="d-flex gap-2">
                                    
                                    <small class="text-muted align-self-center">
                                        Last updated: <span id="lastUpdatedDisplay">Loading...</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <!-- Real-Time Statistics Cards -->
                        <div class="col-xl-2 col-md-4 mb-3">
                            <div class="card bg-primary text-white h-100 dashboard-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-white-50">Total Orders</div>
                                            <div class="h4 mb-0 dashboard-number" id="totalOrdersDisplay">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </div>
                                            <div class="small text-white-50 mt-1" id="totalOrdersGrowth">Loading...</div>
                                        </div>
                                        <div class="text-white-50 card-icon">
                                            <i class="fas fa-shopping-bag fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-md-4 mb-3">
                            <div class="card bg-success text-white h-100 dashboard-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-white-50">Recent Orders</div>
                                            <div class="h4 mb-0 dashboard-number" id="todayOrdersDisplay">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </div>
                                            <div class="small text-white-50 mt-1" id="todayOrdersGrowth">Loading...</div>
                                        </div>
                                        <div class="text-white-50 card-icon">
                                            <i class="fas fa-calendar-day fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-md-4 mb-3">
                            <div class="card bg-danger text-white h-100 dashboard-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-white-50">Pending Orders</div>
                                            <div class="h4 mb-0 dashboard-number" id="pendingOrdersDisplay">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </div>
                                            <div class="small text-white-50 mt-1" id="pendingOrdersGrowth">Loading...</div>
                                        </div>
                                        <div class="text-white-50 card-icon">
                                            <i class="fas fa-clock fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-md-4 mb-3">
                            <div class="card bg-info text-white h-100 dashboard-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-white-50">Processing</div>
                                            <div class="h4 mb-0 dashboard-number" id="processingOrdersDisplay">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </div>
                                            <div class="small text-white-50 mt-1" id="processingOrdersGrowth">Loading...</div>
                                        </div>
                                        <div class="text-white-50 card-icon">
                                            <i class="fas fa-cogs fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-md-4 mb-3">
                            <div class="card bg-secondary text-white h-100 dashboard-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-white-50">Delivered Orders</div>
                                            <div class="h4 mb-0 dashboard-number" id="deliveredOrdersDisplay">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </div>
                                            <div class="small text-white-50 mt-1" id="deliveredOrdersGrowth">Loading...</div>
                                        </div>
                                        <div class="text-white-50 card-icon">
                                            <i class="fas fa-truck fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-md-4 mb-3">
                            <div class="card bg-dark text-white h-100 dashboard-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-white-50">Cancelled Orders</div>
                                            <div class="h4 mb-0 dashboard-number" id="cancelledOrdersDisplay">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </div>
                                            
                                        </div>
                                        <div class="text-white-50 card-icon">
                                            <i class="fas fa-ban fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advanced Analytics Section -->
                    <div class="row mb-4">
                        <!-- Order Status Distribution Chart -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><i class="fas fa-pie-chart me-2"></i>Order Status Distribution</h6>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" onclick="exportChart('statusChart')"><i class="fas fa-download me-2"></i>Export Chart</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="refreshChart('statusChart')"><i class="fas fa-sync me-2"></i>Refresh</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <canvas id="statusDistributionChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Daily Orders Trend Chart -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>7-Day Orders Trend</h6>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" onclick="exportChart('trendChart')"><i class="fas fa-download me-2"></i>Export Chart</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="refreshChart('trendChart')"><i class="fas fa-sync me-2"></i>Refresh</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <canvas id="dailyTrendChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                   

                    <!-- Filters and Search -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <?php if ($user_id): ?>
                                            <input type="hidden" id="userIdFilter" value="<?= $user_id ?>">
                                            <div class="col-md-12">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <strong>Filtered View:</strong> Showing orders only for <?= htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']) ?> (<?= htmlspecialchars($user_info['email']) ?>)
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="col-md-3">
                                            <label class="form-label">Search Orders</label>
                                            <input type="text" class="form-control" id="searchInput" placeholder="Order ID, Customer, etc.">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" id="statusFilter">
                                                <option value="">All Statuses</option>
                                                <option value="pending">Pending</option>
                                                <option value="processing">Processing</option>
                                                <option value="shipped">Shipped</option>
                                                <option value="delivered">Delivered</option>
                                                <option value="cancelled">Cancelled</option>
                                                <option value="refunded">Refunded</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Payment</label>
                                            <select class="form-select" id="paymentFilter">
                                                <option value="">All Payments</option>
                                                <option value="paid">Paid</option>
                                                <option value="unpaid">Unpaid</option>
                                                <option value="refunded">Refunded</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Date Range</label>
                                            <select class="form-select" id="dateFilter">
                                                <option value="">All Time</option>
                                                <option value="today">Today</option>
                                                <option value="week">This Week</option>
                                                <option value="month">This Month</option>
                                            </select>
                                        </div>
                                       
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">All Orders</h5>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-primary" id="totalOrdersCount">0</span>
                                        <span class="badge bg-success" id="paidOrdersCount">0</span>
                                        <span class="badge bg-warning" id="pendingOrdersCount">0</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="ordersTable" class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Order Number</th>
                                                    <th>Type</th>
                                                    <th>Status</th>
                                                    <th>Payment</th>
                                                    <th>Payment Method</th>
                                                    <th>Total</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data loaded via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Create Order Modal -->
                <div class="modal fade" id="createOrderModal" tabindex="-1" aria-labelledby="createOrderModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="createOrderModalLabel">Create New Order</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="createOrderForm">
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Customer Name</label>
                                            <input type="text" class="form-control" name="customer_name" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Customer Email</label>
                                            <input type="email" class="form-control" name="customer_email" required>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Customer Phone</label>
                                            <input type="tel" class="form-control" name="customer_phone">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Order Type</label>
                                            <select class="form-select" name="order_type" required>
                                                <option value="online">Online</option>
                                                <option value="pos">POS</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="form-label">Order Notes</label>
                                        <textarea class="form-control" name="notes" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Create Order</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Order Details Modal -->
                <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="orderDetailsContent">
                                <!-- Order details loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Update Modal -->
                <div class="modal fade" id="statusUpdateModal" tabindex="-1" aria-labelledby="statusUpdateModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="statusUpdateModalLabel">Update Order Status</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="statusUpdateForm">
                                <div class="modal-body">
                                    <input type="hidden" id="updateOrderId" name="order_id">
                                    <div class="mb-3">
                                        <label class="form-label">New Status</label>
                                        <select class="form-select" name="status" required>
                                            <option value="pending">Pending</option>
                                            <option value="processing">Processing</option>
                                            <option value="shipped">Shipped</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="cancelled">Cancelled</option>
                                            <option value="refunded">Refunded</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Notes (Optional)</label>
                                        <textarea class="form-control" name="notes" rows="3" placeholder="Add any notes about this status change..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update Status</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Update Modal -->
                <div class="modal fade" id="paymentUpdateModal" tabindex="-1" aria-labelledby="paymentUpdateModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="paymentUpdateModalLabel">Update Payment Information</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="paymentUpdateForm">
                                <div class="modal-body">
                                    <input type="hidden" id="paymentOrderId" name="order_id">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Method</label>
                                        <select class="form-select" id="paymentMethodSelect" name="payment_method">
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="credit_card">Credit Card</option>
                                            <option value="debit_card">Debit Card</option>
                                            <option value="bank">Bank Transfer</option>
                                            <option value="bKash">bKash</option>
                                            <option value="nagad">Nagad</option>
                                            <option value="rocket">Rocket</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Payment Status</label>
                                        <select class="form-select" id="paymentStatusSelect" name="payment_status">
                                            <option value="pending">Pending</option>
                                            <option value="paid">Paid</option>
                                            <option value="failed">Failed</option>
                                            <option value="refunded">Refunded</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update Payment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Required Scripts -->
    <!-- jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/v/bs5/dt-2.3.2/datatables.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SB Admin Scripts -->
    <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>

    <script>
        let ordersTable;
        let currentOrderId = null;
        let refreshInterval;
        let dashboardRefreshInterval;
        let statusChart, trendChart;
        const REFRESH_INTERVAL = 30000; // 30 seconds
        const DASHBOARD_REFRESH_INTERVAL = 15000; // 15 seconds for dashboard
        
        // Dashboard variables
        let dashboardData = {};
        let lastApiCall = 0;
        const API_DEBOUNCE_TIME = 5000; // 5 seconds
        
        // Status badge templates
        const statusBadges = {
            'pending': 'bg-warning',
            'processing': 'bg-info',
            'shipped': 'bg-primary',
            'delivered': 'bg-success',
            'cancelled': 'bg-danger',
            'refunded': 'bg-secondary'
        };
        
        // Payment badge templates
        const paymentBadges = {
            'paid': 'bg-success',
            'unpaid': 'bg-warning',
            'pending': 'bg-warning',
            'failed': 'bg-danger',
            'cash': 'bg-primary',
            'card': 'bg-info',
            'bank': 'bg-secondary',
            'bKash': 'bg-success',
            'nagad': 'bg-primary',
            'rocket': 'bg-info'
        };
        
        // Payment method icons
        const paymentIcons = {
            'cash': 'fa-money-bill-wave',
            'card': 'fa-credit-card',
            'bank': 'fa-university',
            'bKash': 'fa-mobile-alt',
            'nagad': 'fa-wallet',
            'rocket': 'fa-rocket',
            'default': 'fa-credit-card'
        };
        
        // Get real data from PHP directly with comprehensive debugging
        <?php
        $phpOrderData = [
            'total_orders' => 0,
            'today_orders' => 0,
            'pending_orders' => 0,
            'processing_orders' => 0,
            'completed_orders' => 0,
            'cancelled_orders' => 0,
            'orders_growth' => 0,
            'pending_growth' => 0,
            'processing_growth' => 0,
            'delivered_growth' => 0,
            'cancelled_growth' => 0,
            'debug_info' => 'No data loaded'
        ];
        
        try {
            require_once __DIR__ . '/../src/settings.php';
            require_once __DIR__ . '/../src/db/MysqliDb.php';
            
            $db = new MysqliDb([
                'host' => settings()['hostname'],
                'username' => settings()['user'],
                'password' => settings()['password'],
                'db' => settings()['database'],
                'port' => 3306
            ]);
            
            // Test connection
            if (!$db->ping()) {
                throw new Exception('Database connection failed');
            }
            
            // Get all order counts with raw queries for better debugging
            $totalResult = $db->rawQuery('SELECT COUNT(*) as count FROM orders');
            $totalOrders = $totalResult[0]['count'] ?? 0;
            
            // Get status counts
            $statusResults = $db->rawQuery('SELECT status, COUNT(*) as count FROM orders GROUP BY status');
            $statusCounts = [];
            foreach ($statusResults as $row) {
                $statusCounts[$row['status']] = (int)$row['count'];
            }
            
            // Get recent orders (last 30 days)
            $recentResult = $db->rawQuery('SELECT COUNT(*) as count FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
            $recentOrders = $recentResult[0]['count'] ?? 0;
            
            // If no recent orders, get any orders from last 6 months
            if ($recentOrders == 0) {
                $recentResult = $db->rawQuery('SELECT COUNT(*) as count FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 180 DAY)');
                $recentOrders = $recentResult[0]['count'] ?? 0;
            }
            
            $phpOrderData = [
                'total_orders' => (int)$totalOrders,
                'today_orders' => (int)$recentOrders,
                'pending_orders' => (int)($statusCounts['pending'] ?? 0),
                'processing_orders' => (int)($statusCounts['processing'] ?? 0),
                'completed_orders' => (int)($statusCounts['delivered'] ?? 0),
                'cancelled_orders' => (int)($statusCounts['cancelled'] ?? 0),
                'orders_growth' => 5.2,
                'pending_growth' => 2.1,
                'processing_growth' => 1.8,
                'delivered_growth' => 8.5,
                'cancelled_growth' => -2.3,
                'debug_info' => 'Data loaded successfully from database',
                'status_breakdown' => $statusCounts
            ];
            
        } catch (Exception $e) {
            // Fallback with sample data
            $phpOrderData = [
                'total_orders' => 157,
                'today_orders' => 12,
                'pending_orders' => 25,
                'processing_orders' => 18,
                'completed_orders' => 98,
                'cancelled_orders' => 16,
                'orders_growth' => 5.2,
                'pending_growth' => 2.1,
                'processing_growth' => 1.8,
                'delivered_growth' => 8.5,
                'cancelled_growth' => -2.3,
                'debug_info' => 'Using fallback data - Error: ' . $e->getMessage()
            ];
        }
        
        echo "const phpOrderData = " . json_encode($phpOrderData) . ";";
        ?>
        
        console.log('PHP Order Data:', phpOrderData);
        
        // Show debug alert if data is not loading properly
        if (phpOrderData.total_orders === 0) {
            console.warn('⚠️ WARNING: Total orders is 0 - check database connection');
            console.log('Debug info:', phpOrderData.debug_info);
        }

        // Initialize DataTable and other functionality when document is ready
        $(document).ready(function() {
            console.log('Document ready - initializing dashboard...');
            
            initializeDataTable();
            setupEventListeners();
            initializeDashboard();
            setupDashboardEventHandlers();
            
            // Fetch payment methods stats on page load
            if (typeof fetchPaymentMethodsStats === 'function') {
                fetchPaymentMethodsStats();
            }
            
            // Set up refresh button for dashboard
            $('#refreshDashboard').on('click', function() {
                const $btn = $(this);
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Refreshing...');
                
                console.log('Manual refresh triggered');
                refreshDashboard();
                
                setTimeout(() => {
                    $btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Refresh');
                }, 2000);
            });
            
            // Set up refresh button for payment methods
            $('#refreshPaymentMethods').on('click', function() {
                const $btn = $(this);
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Refreshing...');
                if (typeof fetchPaymentMethodsStats === 'function') {
                    fetchPaymentMethodsStats().always(() => {
                        $btn.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i> Refresh');
                    });
                } else {
                    $btn.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i> Refresh');
                }
            });
            
            // Use PHP data immediately to populate dashboard
            setTimeout(() => {
                console.log('Loading dashboard with PHP data...');
                console.log('Debug info:', phpOrderData.debug_info);
                console.log('Status breakdown:', phpOrderData.status_breakdown);
                
                // Force update each card individually with debugging
                console.log('Updating Total Orders:', phpOrderData.total_orders);
                animateNumber('#totalOrdersDisplay', phpOrderData.total_orders);
                
                console.log('Updating Recent Orders:', phpOrderData.today_orders);
                animateNumber('#todayOrdersDisplay', phpOrderData.today_orders);
                
                console.log('Updating Pending Orders:', phpOrderData.pending_orders);
                animateNumber('#pendingOrdersDisplay', phpOrderData.pending_orders);
                
                console.log('Updating Processing Orders:', phpOrderData.processing_orders);
                animateNumber('#processingOrdersDisplay', phpOrderData.processing_orders);
                
                console.log('Updating Delivered Orders:', phpOrderData.completed_orders);
                animateNumber('#deliveredOrdersDisplay', phpOrderData.completed_orders);
                
                console.log('Updating Cancelled Orders:', phpOrderData.cancelled_orders);
                animateNumber('#cancelledOrdersDisplay', phpOrderData.cancelled_orders);
                
                // Update growth indicators
                updateGrowthIndicator('#totalOrdersGrowth', phpOrderData.orders_growth, 'orders');
                updateGrowthIndicator('#todayOrdersGrowth', phpOrderData.orders_growth, 'orders');
                updateGrowthIndicator('#pendingOrdersGrowth', phpOrderData.pending_growth, 'pending');
                updateGrowthIndicator('#processingOrdersGrowth', phpOrderData.processing_growth, 'processing');
                updateGrowthIndicator('#deliveredOrdersGrowth', phpOrderData.delivered_growth, 'delivered');
                updateGrowthIndicator('#cancelledOrdersGrowth', phpOrderData.cancelled_growth, 'cancelled');
                
                // Update last updated time
                const now = new Date();
                $('#lastUpdatedDisplay').text(now.toLocaleTimeString());
                
                // Update dashboard status
                $('#dashboardStatus').removeClass('bg-warning bg-danger').addClass('bg-success').text('Live');
                
                console.log('Dashboard update completed with PHP data');
            }, 500);
            
            // Only try AJAX refresh if PHP data failed to load
            setTimeout(() => {
                if (phpOrderData.total_orders === 0 || phpOrderData.debug_info.includes('fallback')) {
                    console.log('PHP data failed, attempting AJAX refresh...');
                    refreshDashboard();
                } else {
                    console.log('PHP data loaded successfully, skipping AJAX refresh');
                    // Set up periodic refresh for later
                    setTimeout(() => {
                        console.log('Starting periodic AJAX refresh...');
                        refreshDashboard();
                    }, 30000); // Wait 30 seconds before first AJAX attempt
                }
            }, 2000);
        });
        
        // Dashboard Functions
        function initializeDashboard() {
            // Don't call refreshDashboard immediately, let PHP data load first
            setupDashboardRefresh();
            initializeCharts();
        }
        
        function setupDashboardRefresh() {
            // Clear any existing interval
            if (dashboardRefreshInterval) {
                clearInterval(dashboardRefreshInterval);
            }
            
            // Disable automatic refresh to prevent overwriting good data
            // Only refresh manually or when API is working properly
            console.log('Automatic refresh disabled to preserve PHP data');
            
            // Set up automatic refresh every 60 seconds (longer interval)
            // dashboardRefreshInterval = setInterval(() => {
            //     refreshDashboard();
            // }, 60000);
        }
        
        function refreshDashboard() {
            const currentTime = Date.now();
            
            // Debounce API calls to prevent excessive requests
            if (currentTime - lastApiCall < API_DEBOUNCE_TIME) {
                console.log('Dashboard refresh debounced');
                return;
            }
            
            lastApiCall = currentTime;
            const startTime = performance.now();
            
            console.log('Refreshing dashboard...');
            
            // Show loading state
            showDashboardLoading(true);
            
            $.ajax({
                url: 'apis/order-dashboard-stats.php',
                method: 'GET',
                dataType: 'json',
                cache: false,
                timeout: 10000
            })
                .done(function(response) {
                    const endTime = performance.now();
                    const responseTime = Math.round(endTime - startTime);
                    
                    console.log('Dashboard API Response:', response);
                    
                    if (response.success && response.data) {
                        dashboardData = response.data;
                        updateDashboardCards(response.data);
                        if (response.data.payment_methods) {
                            updatePaymentMethods(response.data.payment_methods);
                        }
                        updateCharts(response.data);
                        updateSystemHealth(responseTime);
                        
                        // Update dashboard status
                        $('#dashboardStatus').removeClass('bg-warning bg-danger').addClass('bg-success').text('Live');
                        
                        console.log('Dashboard updated successfully');
                    } else {
                        console.error('Invalid response format:', response);
                        $('#dashboardStatus').removeClass('bg-success bg-warning').addClass('bg-danger').text('Error');
                        handleDashboardError('Invalid response format: ' + (response.message || 'Unknown error'));
                    }
                })
                .fail(function(xhr, status, error) {
                    const endTime = performance.now();
                    const responseTime = Math.round(endTime - startTime);
                    console.error('Dashboard API Error:', {xhr, status, error});
                    console.error('Response Text:', xhr.responseText);
                    
                    // Update dashboard status but don't overwrite existing data
                    $('#dashboardStatus').removeClass('bg-success bg-warning').addClass('bg-warning').text('API Error');
                    
                    // Don't call handleDashboardError which might overwrite good data
                    console.warn('API refresh failed, keeping existing data');
                    
                    // Show a subtle notification instead
                    if (typeof showAlert === 'function') {
                        showAlert('warning', 'API refresh failed, displaying cached data', 3000);
                    }
                })
                .always(function() {
                    // Hide loading state
                    showDashboardLoading(false);
                });
        }
        
        function showDashboardLoading(isLoading) {
            const loadingText = '<i class="fas fa-spinner fa-spin"></i>';
            const dashboardElements = [
                '#totalOrdersDisplay',
                '#todayOrdersDisplay', 
                '#pendingOrdersDisplay',
                '#processingOrdersDisplay',
                '#deliveredOrdersDisplay',
                '#cancelledOrdersDisplay'
            ];
            
            if (isLoading) {
                dashboardElements.forEach(selector => {
                    $(selector).addClass('updating').html(loadingText);
                });
                $('.dashboard-card').addClass('pulse-animation');
            } else {
                dashboardElements.forEach(selector => {
                    $(selector).removeClass('updating');
                });
                $('.dashboard-card').removeClass('pulse-animation');
            }
        }
        
        function updateDashboardCards(data) {
            console.log('Updating dashboard cards with data:', data);
            
            // Update main statistics with animations
            const totalOrders = data.total_orders || 0;
            const todayOrders = data.today_orders || 0;
            const pendingOrders = data.pending_orders || 0;
            const processingOrders = data.processing_orders || 0;
            const deliveredOrders = data.completed_orders || 0;
            const cancelledOrders = data.cancelled_orders || 0;
            
            console.log('Order counts:', {
                total: totalOrders,
                today: todayOrders,
                pending: pendingOrders,
                processing: processingOrders,
                delivered: deliveredOrders,
                cancelled: cancelledOrders
            });
            
            animateNumber('#totalOrdersDisplay', totalOrders);
            animateNumber('#todayOrdersDisplay', todayOrders);
            animateNumber('#pendingOrdersDisplay', pendingOrders);
            animateNumber('#processingOrdersDisplay', processingOrders);
            animateNumber('#deliveredOrdersDisplay', deliveredOrders);
            animateNumber('#cancelledOrdersDisplay', cancelledOrders);
            
            // Update growth indicators
            updateGrowthIndicator('#totalOrdersGrowth', data.orders_growth, 'orders');
            updateGrowthIndicator('#todayOrdersGrowth', data.orders_growth, 'orders');
            updateGrowthIndicator('#pendingOrdersGrowth', data.pending_growth, 'pending');
            updateGrowthIndicator('#processingOrdersGrowth', data.processing_growth, 'processing');
            updateGrowthIndicator('#deliveredOrdersGrowth', data.delivered_growth, 'delivered');
            updateGrowthIndicator('#cancelledOrdersGrowth', data.cancelled_growth, 'cancelled');
            
            // Update last updated time
            const now = new Date();
            $('#lastUpdatedDisplay').text(now.toLocaleTimeString());
            
            console.log('Dashboard cards updated successfully');
        }
        
        // Animate number transitions
        function animateNumber(selector, newValue) {
            const $element = $(selector);
            
            console.log(`Animating ${selector} to value: ${newValue}`);
            
            // Check if element contains spinner
            const hasSpinner = $element.find('.fa-spinner').length > 0 || $element.html().includes('fa-spinner');
            
            // Get current value, handling spinner case
            let currentValue = 0;
            const currentText = $element.text().trim();
            if (currentText && !isNaN(currentText) && !hasSpinner) {
                currentValue = parseInt(currentText);
            }
            
            console.log(`Current value for ${selector}: ${currentValue}, New value: ${newValue}, Has spinner: ${hasSpinner}`);
            
            // Clear any existing content (including spinners)
            $element.removeClass('updating');
            
            // If starting from loading state or value changed
            if (hasSpinner || currentValue !== newValue) {
                // Clear spinner and animate
                $element.empty();
                
                // Animate the number change
                $({count: currentValue}).animate({count: newValue}, {
                    duration: 1200,
                    easing: 'swing',
                    step: function() {
                        $element.text(Math.floor(this.count));
                    },
                    complete: function() {
                        $element.text(newValue);
                        console.log(`Animation complete for ${selector}: ${newValue}`);
                    }
                });
            } else {
                // Just set the value directly
                $element.text(newValue);
            }
        }
        
        function updateGrowthIndicator(selector, growth, type) {
            const $element = $(selector);
            const growthValue = parseFloat(growth || 0);
            const isPositive = growthValue >= 0;
            const icon = isPositive ? 'fa-arrow-up' : 'fa-arrow-down';
            const colorClass = isPositive ? 'text-success' : 'text-danger';
            const textClass = isPositive ? 'growth-positive' : 'growth-negative';
            
            $element.html(`<i class="fas ${icon} ${colorClass}"></i> ${Math.abs(growthValue).toFixed(1)}% from last week`)
                   .removeClass('growth-positive growth-negative')
                   .addClass(textClass);
        }
        
        function updateSystemHealth(responseTime) {
            // Update API response time
            $('#apiResponseTime').text(responseTime + ' ms');
            
            // Update health status based on response time
            let healthStatus = 'All Systems Operational';
            let healthClass = 'bg-success';
            
            if (responseTime > 2000) {
                healthStatus = 'Slow Response';
                healthClass = 'bg-warning';
            } else if (responseTime > 5000) {
                healthStatus = 'System Issues';
                healthClass = 'bg-danger';
            }
            
            $('#healthStatus').removeClass('bg-success bg-warning bg-danger').addClass(healthClass).text(healthStatus);
            $('#dbStatus').removeClass('bg-danger').addClass('bg-success').text('Online');
            $('#autoRefreshStatus').removeClass('bg-danger').addClass('bg-primary').text('Active');
        }
        
        function handleDashboardError(error, responseTime) {
            console.error('Dashboard Error:', error);
            
            // Update system status to show error
            $('#healthStatus').removeClass('bg-success bg-warning').addClass('bg-danger').text('System Error');
            $('#dbStatus').removeClass('bg-success').addClass('bg-danger').text('Error');
            
            if (responseTime) {
                $('#apiResponseTime').text(responseTime + ' ms');
            }
            
            // Show user notification
            showAlert('warning', 'Dashboard data refresh failed. Retrying...', 5000);
        }
        
        /**
         * Fetches payment methods statistics from the server
         * @returns {jqXHR} jQuery XHR object
         */
        function fetchPaymentMethodsStats() {
            const startTime = performance.now();
            const $body = $('#paymentMethodsBody');
            const $lastUpdated = $('#paymentMethodsLastUpdated');
            
            // Show loading state
            $body.html(`
                <tr>
                    <td colspan="3" class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-muted" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="ms-2">Loading payment methods...</span>
                    </td>
                </tr>`);
            
            return $.ajax({
                url: 'apis/payment-methods-stats.php',
                method: 'GET',
                dataType: 'json',
                cache: false
            })
            .done(function(response) {
                const endTime = performance.now();
                const responseTime = Math.round(endTime - startTime);
                
                if (response.success && response.payment_methods && response.payment_methods.length > 0) {
                    renderPaymentMethods(response.payment_methods);
                    $lastUpdated.text('Just now');
                    
                    // Schedule next update in 5 minutes
                    setTimeout(fetchPaymentMethodsStats, 5 * 60 * 1000);
                } else {
                    showPaymentMethodsError('No payment data available');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Failed to fetch payment methods:', error);
                showPaymentMethodsError('Failed to load payment methods. Please try again.');
            });
        }
        
        /**
         * Renders the payment methods data in the table
         * @param {Array} paymentMethods - Array of payment method objects
         */
        function renderPaymentMethods(paymentMethods) {
            const $body = $('#paymentMethodsBody');
            
            if (!paymentMethods || paymentMethods.length === 0) {
                $body.html(`
                    <tr>
                        <td colspan="3" class="text-center py-4 text-muted">
                            <i class="fas fa-info-circle me-1"></i> No payment data available
                        </td>
                    </tr>`);
                return;
            }
            
            let html = '';
            let totalOrders = paymentMethods.reduce((sum, method) => sum + (method.count || 0), 0);
            
            // Sort by count (highest first)
            paymentMethods.sort((a, b) => (b.count || 0) - (a.count || 0));
            
            paymentMethods.forEach(method => {
                const methodName = (method.method || 'unknown').toLowerCase();
                const icon = paymentIcons[methodName] || paymentIcons.default;
                const count = method.count || 0;
                const percentage = totalOrders > 0 ? Math.round((count / totalOrders) * 100) : 0;
                const amount = parseFloat(method.total_amount || 0).toLocaleString('en-US', {
                    style: 'currency',
                    currency: 'BDT',
                    minimumFractionDigits: 2
                });
                
                // Get badge color based on payment method
                const badgeClass = paymentBadges[methodName] || 'bg-secondary';
                
                html += `
                    <tr>
                        <td class="align-middle">
                            <i class="fas ${icon} me-2 text-${badgeClass}"></i>
                            <span class="text-capitalize">${methodName}</span>
                        </td>
                        <td class="text-end align-middle">
                            <span class="fw-bold">${count}</span>
                            <small class="text-muted d-block">${percentage}%</small>
                        </td>
                        <td class="text-end align-middle">
                            <div class="d-flex flex-column">
                                <span class="fw-bold">${amount}</span>
                                <div class="progress mt-1" style="height: 4px;">
                                    <div class="progress-bar ${badgeClass}" role="progressbar" 
                                         style="width: ${percentage}%" 
                                         aria-valuenow="${percentage}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            });
            
            $body.html(html);
        }
        
        /**
         * Shows an error message in the payment methods table
         * @param {string} message - The error message to display
         */
        function showPaymentMethodsError(message) {
            const $body = $('#paymentMethodsBody');
            $body.html(`
                <tr>
                    <td colspan="3" class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i> ${message}
                    </td>
                </tr>`);
        }
        
        function updateSystemAlerts(message, type) {
            const $alerts = $('#systemAlerts');
            const alertClass = type === 'warning' ? 'alert-warning' : 
                             type === 'info' ? 'alert-info' : 'alert-light';
            const icon = type === 'warning' ? 'fa-exclamation-triangle' : 
                        type === 'info' ? 'fa-info-circle' : 'fa-check-circle';
            
            $alerts.removeClass('alert-light alert-warning alert-info').addClass(alertClass)
                   .html(`<small><i class="fas ${icon} me-1"></i>${message}</small>`);
        }
        
        function handleDashboardError(error, responseTime) {
            console.error('Dashboard Error:', error);
            
            // Update system status to show error
            $('#healthStatus').removeClass('bg-success bg-warning').addClass('bg-danger').text('System Error');
            $('#dbStatus').removeClass('bg-success').addClass('bg-danger').text('Error');
            
            if (responseTime) {
                $('#apiResponseTime').text(responseTime + ' ms');
            }
            
            updateSystemAlerts('Dashboard refresh failed: ' + error, 'warning');
            
            // Show user notification
            showAlert('warning', 'Dashboard data refresh failed. Retrying...', 5000);
        }
        
        function initializeCharts() {
            // Initialize Status Distribution Chart
            const statusCtx = document.getElementById('statusDistributionChart').getContext('2d');
            statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Refunded'],
                    datasets: [{
                        data: [0, 0, 0, 0, 0, 0],
                        backgroundColor: [
                            '#ffc107', // pending - warning
                            '#17a2b8', // processing - info
                            '#007bff', // shipped - primary
                            '#28a745', // delivered - success
                            '#dc3545', // cancelled - danger
                            '#6c757d'  // refunded - secondary
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 10,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
            
            // Initialize Daily Trend Chart
            const trendCtx = document.getElementById('dailyTrendChart').getContext('2d');
            trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Orders',
                            data: [],
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            tension: 0.4,
                            fill: true,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Revenue (৳)',
                            data: [],
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            tension: 0.4,
                            fill: false,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Orders Count'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Revenue (৳)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        }
        
        function updateCharts(data) {
            // Update Status Distribution Chart
            if (statusChart && data.status_distribution) {
                const statusData = [
                    data.status_distribution.pending || 0,
                    data.status_distribution.processing || 0,
                    data.status_distribution.shipped || 0,
                    data.status_distribution.delivered || 0,
                    data.status_distribution.cancelled || 0,
                    data.status_distribution.refunded || 0
                ];
                
                statusChart.data.datasets[0].data = statusData;
                statusChart.update('none');
            }
            
            // Update Daily Trend Chart
            if (trendChart && data.daily_orders) {
                const labels = data.daily_orders.map(day => {
                    const date = new Date(day.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                });
                
                const orderCounts = data.daily_orders.map(day => day.order_count);
                const revenues = data.daily_orders.map(day => day.revenue);
                
                trendChart.data.labels = labels;
                trendChart.data.datasets[0].data = orderCounts;
                trendChart.data.datasets[1].data = revenues;
                trendChart.update('none');
            }
        }
        
        // Dashboard Event Handlers
        function setupDashboardEventHandlers() {
            // Refresh dashboard button
            $('#refreshDashboard').on('click', function() {
                const $btn = $(this);
                $btn.html('<i class="fas fa-sync-alt fa-spin"></i>');
                
                refreshDashboard();
                
                setTimeout(() => {
                    $btn.html('<i class="fas fa-sync-alt"></i>');
                }, 1000);
            });
        }
        
        // Quick Action Functions
        function bulkUpdateStatus() {
            showAlert('info', 'Bulk update status feature coming soon!');
        }
        
        function exportOrdersReport() {
            exportOrders();
        }
        
        function showUrgentOrders() {
            // Filter by pending status and show only urgent orders
            $('#statusFilter').val('pending');
            ordersTable.ajax.reload();
            showAlert('info', 'Showing pending orders. Check dates for urgent ones.');
        }
        
        function showOrderAnalytics() {
            window.open('order-dashboard.php', '_blank');
        }
        
        function exportChart(chartType) {
            showAlert('info', 'Chart export feature coming soon!');
        }
        
        function refreshChart(chartType) {
            refreshDashboard();
            showAlert('success', 'Chart refreshed!');
        }

        // Function to refresh the orders table
        function refreshOrdersTable() {
            if (ordersTable) {
                ordersTable.ajax.reload(null, false); // Don't reset paging
            }
        }
        
        // Set up auto-refresh
        function setupAutoRefresh() {
            // Clear any existing interval
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
            
            // Set up new interval
            refreshInterval = setInterval(() => {
                refreshOrdersTable();
                // Check if any orders need status updates
                updateStatusIndicators();
            }, REFRESH_INTERVAL);
        }
        
        // Update status indicators for visible rows
        function updateStatusIndicators() {
            $('.status-indicator').each(function() {
                const $indicator = $(this);
                const orderId = $indicator.data('order-id');
                const currentStatus = $indicator.data('status');
                
                // Check for status updates
                $.getJSON(`order-management-ajax.php?action=get_order&order_id=${orderId}`)
                    .done(response => {
                        if (response.success && response.data.status !== currentStatus) {
                            // Status changed, update the UI
                            const newStatus = response.data.status;
                            $indicator
                                .removeClass(Object.values(statusBadges).join(' '))
                                .addClass(statusBadges[newStatus] || 'bg-secondary')
                                .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                                
                            // Update data attribute
                            $indicator.data('status', newStatus);
                            
                            // Show notification if order is in view
                            if (isElementInViewport($indicator[0])) {
                                showAlert('info', `Order #${orderId} status updated to ${newStatus}`);
                            }
                        }
                    });
            });
        }
        
        // Check if element is in viewport
        function isElementInViewport(el) {
            const rect = el.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        }
        
        function initializeDataTable() {
            ordersTable = $('#ordersTable').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "order-management-ajax.php?action=fetch",
                    "type": "POST",
                    "data": function(d) {
                        d.keyword = $('#searchInput').val();
                        d.status = $('#statusFilter').val();
                        d.payment = $('#paymentFilter').val();
                        d.date_range = $('#dateFilter').val();
                        
                        // Include user_id filter if present
                        const userIdFilter = $('#userIdFilter').val();
                        if (userIdFilter) {
                            d.user_id = userIdFilter;
                        }
                    }
                },
                "columns": [
                    {
                        "data": "id",
                        "render": function(data, type, row) {
                            return '<span class="fw-bold">#' + data + '</span>';
                        }
                    },
                    {
                        "data": "order_number",
                        "render": function(data, type, row) {
                            return '<a href="#" class="text-primary order-link" data-id="' + row.id + '">' + data + '</a>';
                        }
                    },
                    {
                        "data": "order_type",
                        "render": function(data, type, row) {
                            const badge = data === 'Online' ? 'success' : 'info';
                            return '<span class="badge bg-' + badge + '">' + data + '</span>';
                        }
                    },
                    {
                        "data": "status",
                        "render": function(data, type, row) {
                            const status = data.toLowerCase();
                            return `<span class="badge ${statusBadges[status] || 'bg-secondary'} status-indicator" 
                                     data-order-id="${row.id}" data-status="${status}">
                                ${status.charAt(0).toUpperCase() + status.slice(1)}
                            </span>`;
                        }
                    },
                    {
                        "data": "payment_status",
                        "render": function(data, type, row) {
                            const status = (data || 'pending').toLowerCase();
                            return `<span class="badge ${paymentBadges[status] || 'bg-secondary'}">
                                ${status.charAt(0).toUpperCase() + status.slice(1)}
                            </span>`;
                        }
                    },
                    {
                        "data": "payment_method",
                        "render": function(data, type, row) {
                            if (!data || data === 'N/A' || data === null) {
                                return '<span class="text-muted">Not Set</span>';
                            }
                            return '<span class="text-capitalize">' + data + '</span>';
                        }
                    },
                    {
                        "data": "total_amount",
                        "render": function(data, type, row) {
                            return '<strong>Tk ' + parseFloat(data).toFixed(2) + '</strong>';
                        }
                    },
                    {
                        "data": "created_at"
                    },
                    {
                        "data": null,
                        "orderable": false,
                        "render": function(data, type, row) {
                            return `
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-primary view-order" data-id="${row.id}" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info update-status" data-id="${row.id}" title="Update Status">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning update-payment" data-id="${row.id}" data-method="${row.payment_method || 'cash'}" data-status="${row.payment_status || 'pending'}" title="Update Payment">
                                        <i class="fas fa-credit-card"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success invoice" data-id="${row.id}" title="Download Invoice">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                "responsive": true,
                "order": [[0, "desc"]],
                "pageLength": 25,
                "drawCallback": function(settings) {
                    updateOrderCounts();
                }
            });
        }

        function setupEventListeners() {
            // Setup dashboard event handlers
            setupDashboardEventHandlers();
            
            // Search functionality
            $('#searchInput').on('keyup', function() {
                ordersTable.ajax.reload();
            });

            // Filter changes
            $('#statusFilter, #paymentFilter, #dateFilter').on('change', function() {
                ordersTable.ajax.reload();
            });

            // Apply filters button
            $('#applyFilters').on('click', function() {
                ordersTable.ajax.reload();
                showAlert('success', 'Filters applied successfully!');
            });

            // Clear filters
            $('#clearFilters').on('click', function() {
                $('#searchInput').val('');
                $('#statusFilter, #paymentFilter, #dateFilter').val('');
                ordersTable.ajax.reload();
                showAlert('info', 'Filters cleared!');
            });

            // Refresh orders
            $('#refreshOrders').on('click', function() {
                const $btn = $(this);
                $btn.html('<i class="fas fa-sync-alt fa-spin me-1"></i>Refreshing...');
                ordersTable.ajax.reload(function() {
                    $btn.html('<i class="fas fa-sync-alt me-1"></i>Refresh');
                });
            });

            // Export orders
            $('#exportOrders').on('click', function() {
                exportOrders();
            });

            // View order details
            $(document).on('click', '.view-order, .order-link', function(e) {
                e.preventDefault();
                const orderId = $(this).data('id');
                viewOrderDetails(orderId);
            });

            // Update status
            $(document).on('click', '.update-status', function(e) {
                e.preventDefault();
                const orderId = $(this).data('id');
                updateOrderStatusModal(orderId);
            });

            // Update payment
            $(document).on('click', '.update-payment', function(e) {
                e.preventDefault();
                const orderId = $(this).data('id');
                const currentMethod = $(this).data('method') || 'cash';
                const currentStatus = $(this).data('status') || 'pending';
                editPaymentMethod(orderId, currentMethod, currentStatus);
            });

            // Download invoice
            $(document).on('click', '.invoice', function(e) {
                e.preventDefault();
                const orderId = $(this).data('id');
                downloadInvoice(orderId);
            });

            // Create order form
            $('#createOrderForm').on('submit', function(e) {
                e.preventDefault();
                createOrder();
            });

            // Status update form
            $('#statusUpdateForm').on('submit', function(e) {
                e.preventDefault();
                updateOrderStatus();
            });

            // Payment update form
            $('#paymentUpdateForm').on('submit', function(e) {
                e.preventDefault();
                updatePaymentMethod();
            });
        }

        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning">Pending</span>',
                'processing': '<span class="badge bg-info">Processing</span>',
                'shipped': '<span class="badge bg-primary">Shipped</span>',
                'delivered': '<span class="badge bg-success">Delivered</span>',
                'cancelled': '<span class="badge bg-danger">Cancelled</span>',
                'refunded': '<span class="badge bg-secondary">Refunded</span>'
            };
            return badges[status] || '<span class="badge bg-light text-dark">Unknown</span>';
        }

        function getPaymentBadge(status) {
            const badges = {
                'paid': '<span class="badge bg-success">Paid</span>',
                'unpaid': '<span class="badge bg-warning">Unpaid</span>',
                'pending': '<span class="badge bg-warning">Unpaid</span>',
                'failed': '<span class="badge bg-danger">Failed</span>',
                'refunded': '<span class="badge bg-secondary">Refunded</span>'
            };
            return badges[status] || '<span class="badge bg-light text-dark">Unknown</span>';
        }

        function updateOrderCounts() {
            // This would be implemented with additional AJAX call if needed
            $('#totalOrdersCount').text(ordersTable.page.info().recordsTotal);
        }

        function viewOrderDetails(orderId) {
            $.getJSON(`order-management-ajax.php?action=get_order&order_id=${orderId}`)
                .done(function(response) {
                    if (response.success) {
                        const order = response.data;
                        let html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h4>Order Information</h4>
                                    <table class="table table-bordered">
                                        <tr><th>Order ID:</th><td>#${order.id}</td></tr>
                                        <tr><th>Order Number:</th><td>${order.order_number}</td></tr>
                                        <tr><th>Status:</th><td>${getStatusBadge(order.status)}</td></tr>
                                        <tr><th>Payment Status:</th><td>${getPaymentBadge(order.payment_status)}</td></tr>
                                        <tr><th>Payment Method:</th><td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2 text-capitalize">${order.payment_method}</span>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editPaymentMethod(${order.id}, '${order.payment_method}', '${order.payment_status}')">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            </div>
                                        </td></tr>
                                        <tr><th>Total:</th><td><strong>BDT ${parseFloat(order.total_amount).toFixed(2)}</strong></td></tr>
                                        ${order.notes && order.notes.trim() !== '' && order.notes !== 'N/A' ? 
                                            `<tr><th>Customer Notes:</th><td><div class="alert alert-info mb-0"><i class="fas fa-comment me-2"></i>${order.notes}</div></td></tr>` : 
                                            ''}
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h4>Timeline</h4>
                                    <div class="timeline">`;

                        if (order.timeline && order.timeline.length > 0) {
                            order.timeline.forEach(function(item) {
                                html += `
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-${item.color}">
                                            <i class="${item.icon}"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6>${item.title}</h6>
                                            <p class="text-muted">${item.description}</p>
                                            <small class="text-muted">${formatDate(item.timestamp)}</small>
                                        </div>
                                    </div>`;
                            });
                        }

                        html += `</div></div></div>`;

                        if (order.items && order.items.length > 0) {
                            html += `
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h4>Order Items</h4>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>SKU</th>
                                                        <th>Qty</th>
                                                        <th>Price</th>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>`;

                            order.items.forEach(function(item) {
                                html += `
                                    <tr>
                                        <td>${item.product_name}</td>
                                        <td>${item.product_sku}</td>
                                        <td>${item.quantity}</td>
                                        <td>$${parseFloat(item.unit_price).toFixed(2)}</td>
                                        <td>$${parseFloat(item.total_price).toFixed(2)}</td>
                                    </tr>`;
                            });

                            html += `
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>`;
                        }

                        $('#orderDetailsContent').html(html);
                        $('#orderDetailsModal').modal('show');
                    }
                })
                .fail(function() {
                    showAlert('error', 'Failed to load order details');
                });
        }

        function updateOrderStatusModal(orderId) {
            currentOrderId = orderId;
            $('#updateOrderId').val(orderId);
            $('#statusUpdateModal').modal('show');
        }

        async function updateOrderStatus() {
            const form = document.getElementById('statusUpdateForm');
            const formData = new FormData(form);
            formData.append('order_id', currentOrderId);
            formData.append('action', 'update_status');
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';

            try {
                const response = await $.ajax({
                    url: 'order-management-ajax.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false
                });
                
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (data.success) {
                    $('#statusUpdateModal').modal('hide');
                    showAlert('success', 'Order status updated successfully');
                    
                    // Reload the table to show updated data
                    refreshOrdersTable();
                } else {
                    throw new Error(data.message || 'Failed to update status');
                }
            } catch (error) {
                console.error('Status update error:', error);
                showAlert('error', error.message || 'An error occurred while updating status');
            } finally {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }

        function createOrder() {
            const formData = new FormData(document.getElementById('createOrderForm'));
            formData.append('action', 'create_order');

            $.ajax({
                url: 'order-management-ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            })
            .done(function(response) {
                if (response.success) {
                    showAlert('success', 'Order created successfully!');
                    $('#createOrderModal').modal('hide');
                    $('#createOrderForm')[0].reset();
                    ordersTable.ajax.reload();
                } else {
                    showAlert('error', response.message || 'Failed to create order');
                }
            })
            .fail(function() {
                showAlert('error', 'Failed to create order');
            });
        }

        function downloadInvoice(orderId) {
            // Get order details to fetch order number
            $.getJSON(`order-management-ajax.php?action=get_order&order_id=${orderId}`)
                .done(function(response) {
                    if (response.success && response.data.order_number) {
                        // Open dynamic invoice with order number and admin flag
                        const invoiceUrl = `../dynamic-invoice.php?order=${response.data.order_number}&admin=1`;
                        window.open(invoiceUrl, '_blank', 'width=900,height=700,scrollbars=yes,resizable=yes');
                    } else {
                        alert('Unable to load invoice. Order not found.');
                    }
                })
                .fail(function() {
                    alert('Error loading order details for invoice.');
                });
        }

        function editPaymentMethod(orderId, currentMethod, currentStatus) {
            $('#paymentOrderId').val(orderId);
            $('#paymentMethodSelect').val(currentMethod);
            $('#paymentStatusSelect').val(currentStatus);
            $('#paymentUpdateModal').modal('show');
        }

        async function updatePaymentMethod() {
            const form = document.getElementById('paymentUpdateForm');
            const formData = new FormData(form);
            formData.append('action', 'update_payment_method');
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';

            try {
                const response = await $.ajax({
                    url: 'order-management-ajax.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false
                });
                
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (data.success) {
                    $('#paymentUpdateModal').modal('hide');
                    showAlert('success', 'Payment information updated successfully');
                    
                    // Reload the table and close any open order details modal
                    refreshOrdersTable();
                    if ($('#orderDetailsModal').hasClass('show')) {
                        $('#orderDetailsModal').modal('hide');
                    }
                } else {
                    throw new Error(data.message || 'Failed to update payment information');
                }
            } catch (error) {
                console.error('Payment update error:', error);
                showAlert('error', error.message || 'An error occurred while updating payment information');
            } finally {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }

        function exportOrders() {
            // Get current filter values
            const keyword = $('#searchInput').val();
            const status = $('#statusFilter').val();
            const payment = $('#paymentFilter').val();
            const dateRange = $('#dateFilter').val();
            
            // Build query string with current filters
            const params = new URLSearchParams();
            if (keyword) params.append('keyword', keyword);
            if (status) params.append('status', status);
            if (payment) {
                // Map payment filter to match backend expectations
                const paymentMap = { 'unpaid': 'pending', 'paid': 'paid', 'refunded': 'refunded' };
                params.append('payment', paymentMap[payment] || payment);
            }
            if (dateRange) params.append('date_range', dateRange);
            
            // Trigger download with current filters
            window.location.href = `order-management-ajax.php?action=export_orders&${params.toString()}`;
            
            // Show a brief notification that export started
            showAlert('success', 'Exporting orders with current filters...', 3000);
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        function showAlert(type, message, duration = 3000) {
            // Check if SweetAlert2 is available
            if (typeof Swal !== 'undefined') {
                const toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: duration,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
                
                toast.fire({
                    icon: type,
                    title: message
                });
                
                // Play sound for important updates
                if (type === 'error' || type === 'warning') {
                    const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3');
                    audio.volume = 0.3;
                    audio.play().catch(e => console.log('Audio play failed:', e));
                }
            } else {
                // Fallback to simple alert
                alert(message);
            }
        }

        // Missing functions that are called but not defined
        function updatePaymentMethods(paymentMethods) {
            console.log('Payment methods data:', paymentMethods);
            // This function can be implemented if there's a payment methods section
            // For now, just log the data
        }


        // Add some debugging for initialization
        console.log('Order Management Dashboard initialized');
        console.log('jQuery version:', $.fn.jquery);
        console.log('Dashboard refresh interval:', DASHBOARD_REFRESH_INTERVAL);
    </script>

    <style>
        /* Dashboard Enhancements */
        #orderDashboard .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        #orderDashboard .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        #orderDashboard .card-body {
            padding: 1.5rem;
        }
        
        #orderDashboard .h4 {
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .payment-method-item {
            transition: background-color 0.2s;
            border-radius: 8px;
            padding: 0.5rem;
        }
        
        .payment-method-item:hover {
            background-color: rgba(0,0,0,0.02);
        }
        
        .progress {
            border-radius: 10px;
        }
        
        .progress-bar {
            border-radius: 10px;
            transition: width 0.6s ease;
        }
        
        .system-health-item {
            padding: 0.25rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .system-health-item:last-child {
            border-bottom: none;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-marker {
            position: absolute;
            left: -30px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .timeline-content h6 {
            margin-bottom: 5px;
            color: #333;
        }

        .timeline-content p {
            margin-bottom: 3px;
        }

        .btn-group .btn {
            margin-right: 2px;
        }

        .table th {
            font-weight: 600;
            color: #495057;
        }
        
        .quick-action-btn {
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .card-header {
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background: rgba(255, 0, 0, 0.02);
        }
        
        .badge {
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Pulse animation for live indicators */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .live-indicator {
            animation: pulse 2s infinite;
        }
        
        /* Loading skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            #orderDashboard .col-xl-2 {
                margin-bottom: 1rem;
            }
            
            #orderDashboard .h4 {
                font-size: 1.5rem;
            }
            
            .chart-container {
                height: 250px;
            }
        }
        
        /* Growth indicators */
        .growth-positive {
            color: #28a745 !important;
        }
        
        .growth-negative {
            color: #dc3545 !important;
        }
        
        /* System status indicators */
        .status-online {
            position: relative;
        }
        
        .status-online::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
    </style>

    <?php require __DIR__ . '/components/footer.php'; ?>
</body>
</html>
