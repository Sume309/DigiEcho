<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

use App\auth\Admin;

if(!Admin::Check()){
    header('HTTP/1.1 503 Service Unavailable');
    exit;
}

// Initialize database for comprehensive statistics
$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

// Get comprehensive statistics
try {
    // Basic counts
    $totalUsers = $db->getValue('users', 'COUNT(*)') ?: 0;
    $totalProducts = $db->getValue('products', 'COUNT(*)') ?: 0;
    $totalCategories = $db->getValue('categories', 'COUNT(*)') ?: 0;
    $totalBrands = $db->getValue('brands', 'COUNT(*)') ?: 0;

    // Order statistics
    $totalOrders = $db->getValue('orders', 'COUNT(*)') ?: 0;
    $db->where('status', 'pending');
    $pendingOrders = $db->getValue('orders', 'COUNT(*)') ?: 0;
    // Treat delivered as completed as well
    $db->where('status', ['completed', 'delivered'], 'IN');
    $completedOrders = $db->getValue('orders', 'COUNT(*)') ?: 0;
    $db->where('status', 'cancelled');
    $cancelledOrders = $db->getValue('orders', 'COUNT(*)') ?: 0;

    // Helper: orders that count as revenue (paid OR completed/delivered)
    $revenueWhere = '(payment_status = ? OR status IN (?, ?))';
    $revenueParams = ['paid', 'completed', 'delivered'];

    // Sales statistics (more representative):
    // Today
    $db->where('DATE(created_at)', date('Y-m-d'));
    $db->where($revenueWhere, $revenueParams);
    $todaysSales = (float)$db->getValue('orders', 'SUM(total_amount)') ?: 0;

    // This Week
    $db->where('YEARWEEK(created_at, 1)', date('oW'));
    $db->where($revenueWhere, $revenueParams);
    $thisWeekSales = (float)$db->getValue('orders', 'SUM(total_amount)') ?: 0;

    // This Month
    $db->where('DATE_FORMAT(created_at, "%Y-%m")', date('Y-m'));
    $db->where($revenueWhere, $revenueParams);
    $thisMonthSales = (float)$db->getValue('orders', 'SUM(total_amount)') ?: 0;

    // Total Revenue (lifetime)
    $db->where($revenueWhere, $revenueParams);
    $totalRevenue = (float)$db->getValue('orders', 'SUM(total_amount)') ?: 0;
    
    // Product statistics (dynamic)
    $db->where('status', 'active');
    $activeProducts = $db->getValue('products', 'COUNT(*)') ?: 0;
    $db->where('status', 'inactive');
    $inactiveProducts = $db->getValue('products', 'COUNT(*)') ?: 0;
    $db->where('stock_quantity = 0');
    $outOfStockProducts = $db->getValue('products', 'COUNT(*)') ?: 0;
    $db->where('stock_quantity > 0');
    $db->where('stock_quantity <= min_stock_level'); // column-to-column comparison
    $lowStockProducts = $db->getValue('products', 'COUNT(*)') ?: 0;
    $db->where('is_hot_item', 1);
    $hotItems = $db->getValue('products', 'COUNT(*)') ?: 0;
    
    // Recent activity data
    $recentOrders = $db->orderBy('created_at', 'DESC')->get('orders', 5, ['id', 'order_number', 'total_amount', 'status', 'created_at']);
    $recentProducts = $db->orderBy('created_at', 'DESC')->get('products', 5, ['id', 'name', 'selling_price', 'status', 'created_at']);
    
    // Chart data for last 7 days sales
    $salesChartData = [];
    $salesChartLabels = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $db->where('DATE(created_at)', $date);
        $db->where($revenueWhere, $revenueParams);
        $dailySales = (float)$db->getValue('orders', 'SUM(total_amount)') ?: 0;
        $salesChartData[] = floatval($dailySales);
        $salesChartLabels[] = date('M j', strtotime($date));
    }
    
    // Monthly revenue chart data for last 6 months
    $monthlyData = [];
    $monthlyLabels = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $db->where("DATE_FORMAT(created_at, '%Y-%m')", $month);
        $db->where($revenueWhere, $revenueParams);
        $monthlyRevenue = (float)$db->getValue('orders', 'SUM(total_amount)') ?: 0;
        $monthlyData[] = floatval($monthlyRevenue);
        $monthlyLabels[] = date('M Y', strtotime($month));
    }
    
} catch (Exception $e) {
    error_log('Dashboard Stats Error: ' . $e->getMessage());
    // Set default values on error
    $totalUsers = $totalProducts = $totalCategories = $totalBrands = 0;
    $totalOrders = $pendingOrders = $completedOrders = $cancelledOrders = 0;
    $todaysSales = $thisWeekSales = $thisMonthSales = $totalRevenue = 0;
    $activeProducts = $inactiveProducts = $lowStockProducts = $outOfStockProducts = $hotItems = 0;
    $recentOrders = $recentProducts = [];
    $salesChartData = $salesChartLabels = $monthlyData = $monthlyLabels = [];
}

require __DIR__.'/components/header.php'; ?>

    </head>
    <body class="sb-nav-fixed">
        <?php require __DIR__.'/components/navbar.php'; ?>
        <div id="layoutSidenav">
            <?php require __DIR__.'/components/sidebar.php'; ?>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <!-- Dashboard Header -->
                        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                            <div>
                                <h1 class="h3 mb-1">Admin Dashboard</h1>
                                <p class="text-muted mb-0">Welcome back! Here's what's happening with your store.</p>
                            </div>
                            <div>
                                <button class="btn btn-outline-primary" id="refreshDashboard">
                                    <i class="fas fa-sync-alt me-2"></i>Refresh Data
                                </button>
                            </div>
                        </div>

                        <!-- Main Statistics Cards -->
                        <div class="row mb-4">
                            <!-- Sales Today -->
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="card stats-card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="stats-icon bg-primary">
                                                    <i class="fas fa-dollar-sign"></i>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="stats-number text-primary" id="todaysSales">৳<?= number_format($todaysSales, 2) ?></div>
                                                <div class="stats-label">Today's Sales</div>
                                                <small class="text-success"><i class="fas fa-arrow-up"></i> +12.5%</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Orders -->
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="card stats-card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="stats-icon bg-success">
                                                    <i class="fas fa-shopping-cart"></i>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="stats-number text-success" id="totalOrders"><?= number_format($totalOrders) ?></div>
                                                <div class="stats-label">Total Orders</div>
                                                <small class="text-success"><i class="fas fa-arrow-up"></i> +8.3%</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Products -->
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="card stats-card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="stats-icon bg-warning">
                                                    <i class="fas fa-boxes"></i>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="stats-number text-warning" id="totalProducts"><?= number_format($totalProducts) ?></div>
                                                <div class="stats-label">Total Products</div>
                                                <small class="text-info"><i class="fas fa-plus"></i> <?= $activeProducts ?> Active</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Users -->
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="card stats-card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="stats-icon bg-info">
                                                    <i class="fas fa-users"></i>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="stats-number text-info" id="totalUsers"><?= number_format($totalUsers) ?></div>
                                                <div class="stats-label">Total Users</div>
                                                <small class="text-success"><i class="fas fa-arrow-up"></i> +5.2%</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Status and Sales Overview -->
                        <div class="row mb-4">
                            <!-- Order Status Breakdown -->
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-white border-0">
                                        <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Order Status</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Pending Orders</span>
                                            <span class="badge bg-warning"><?= $pendingOrders ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Completed Orders</span>
                                            <span class="badge bg-success"><?= $completedOrders ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span>Cancelled Orders</span>
                                            <span class="badge bg-danger"><?= $cancelledOrders ?></span>
                                        </div>
                                        <div class="progress-container">
                                            <div class="d-flex justify-content-between text-sm mb-1">
                                                <span>Order Completion Rate</span>
                                                <span><?= $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0 ?>%</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" style="width: <?= $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0 ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales Overview -->
                            <div class="col-xl-4 col-lg-6 mb-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-white border-0">
                                        <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Sales Overview</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="sales-item mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>Today</span>
                                                <span class="fw-bold text-primary">৳<?= number_format($todaysSales, 2) ?></span>
                                            </div>
                                        </div>
                                        <div class="sales-item mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>This Week</span>
                                                <span class="fw-bold text-success">৳<?= number_format($thisWeekSales, 2) ?></span>
                                            </div>
                                        </div>
                                        <div class="sales-item mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>This Month</span>
                                                <span class="fw-bold text-info">৳<?= number_format($thisMonthSales, 2) ?></span>
                                            </div>
                                        </div>
                                        <div class="sales-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>Total Revenue</span>
                                                <span class="fw-bold text-warning">৳<?= number_format($totalRevenue, 2) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Status -->
                            <div class="col-xl-4 col-lg-12 mb-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-white border-0">
                                        <h6 class="mb-0"><i class="fas fa-cube me-2"></i>Product Status</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="product-status-item mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-check-circle text-success me-2"></i>Active</span>
                                                <span class="badge bg-success"><?= $activeProducts ?></span>
                                            </div>
                                        </div>
                                        <div class="product-status-item mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-pause-circle text-secondary me-2"></i>Inactive</span>
                                                <span class="badge bg-secondary"><?= $inactiveProducts ?></span>
                                            </div>
                                        </div>
                                        <div class="product-status-item mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-exclamation-triangle text-warning me-2"></i>Low Stock</span>
                                                <span class="badge bg-warning"><?= $lowStockProducts ?></span>
                                            </div>
                                        </div>
                                        <div class="product-status-item mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-times-circle text-danger me-2"></i>Out of Stock</span>
                                                <span class="badge bg-danger"><?= $outOfStockProducts ?></span>
                                            </div>
                                        </div>
                                        <div class="product-status-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-fire text-danger me-2"></i>Hot Items</span>
                                                <span class="badge bg-danger"><?= $hotItems ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts and Analytics Row -->
                        <div class="row mb-4">
                            <!-- Sales Chart -->
                            <div class="col-xl-8 col-lg-7">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-header bg-white border-0">
                                        <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Sales Trend (Last 7 Days)</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="salesChart" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Revenue Breakdown -->
                            <div class="col-xl-4 col-lg-5">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-header bg-white border-0">
                                        <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Revenue Breakdown</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="revenueChart" width="300" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Revenue Chart -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm border-0">
                                    <div class="card-header bg-white border-0">
                                        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Monthly Revenue (Last 6 Months)</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="monthlyChart" width="400" height="100"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity and Quick Actions Row -->
                        <div class="row mb-4">
                            <!-- Recent Orders -->
                            <div class="col-xl-6 mb-4">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Recent Orders</h6>
                                        <a href="order-management.php" class="btn btn-sm btn-outline-primary">View All</a>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Order #</th>
                                                        <th>Amount</th>
                                                        <th>Status</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($recentOrders)): ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center text-muted py-3">No recent orders</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($recentOrders as $order): ?>
                                                            <tr>
                                                                <td class="fw-medium">#<?= htmlspecialchars($order['order_number'] ?? $order['id']) ?></td>
                                                                <td class="text-success fw-medium">৳<?= number_format($order['total_amount'], 2) ?></td>
                                                                <td>
                                                                    <?php
                                                                    $statusClass = match($order['status']) {
                                                                        'completed' => 'success',
                                                                        'pending' => 'warning',
                                                                        'cancelled' => 'danger',
                                                                        default => 'secondary'
                                                                    };
                                                                    ?>
                                                                    <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($order['status']) ?></span>
                                                                </td>
                                                                <td class="text-muted"><?= date('M d', strtotime($order['created_at'])) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Products -->
                            <div class="col-xl-6 mb-4">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-cube me-2"></i>Recently Added Products</h6>
                                        <a href="product-management.php" class="btn btn-sm btn-outline-primary">View All</a>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>Price</th>
                                                        <th>Status</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($recentProducts)): ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center text-muted py-3">No recent products</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($recentProducts as $product): ?>
                                                            <tr>
                                                                <td class="fw-medium"><?= htmlspecialchars(substr($product['name'], 0, 30)) ?><?= strlen($product['name']) > 30 ? '...' : '' ?></td>
                                                                <td class="text-primary fw-medium">৳<?= number_format($product['selling_price'], 2) ?></td>
                                                                <td>
                                                                    <?php
                                                                    $statusClass = $product['status'] === 'active' ? 'success' : 'secondary';
                                                                    ?>
                                                                    <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($product['status']) ?></span>
                                                                </td>
                                                                <td class="text-muted"><?= date('M d', strtotime($product['created_at'])) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions and Reports Summary -->
                        <div class="row mb-4">
                            <!-- Quick Actions -->
                            <div class="col-xl-12 mb-4">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-header bg-white border-0">
                                        <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <a href="product-add-enhanced.php" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Add New Product
                                            </a>
                                            <a href="category-add.php" class="btn btn-success">
                                                <i class="fas fa-tags me-2"></i>Add Category
                                            </a>
                                            <a href="brand-add.php" class="btn btn-info">
                                                <i class="fas fa-trademark me-2"></i>Add Brand
                                            </a>
                                            <a href="pos.php" class="btn btn-warning">
                                                <i class="fas fa-cash-register me-2"></i>Open POS
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            
                        </div>
                    </div>
                </main>
                <?php require __DIR__.'/components/footer.php'; ?>
            </div>
        </div>

        <!-- Additional Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="assets/js/scripts.js"></script>
        <script src="assets/js/datatables-simple-demo.js"></script>

        <!-- Dashboard JavaScript -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sales Chart (Last 7 Days)
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($salesChartLabels) ?>,
                    datasets: [{
                        label: 'Daily Sales (৳)',
                        data: <?= json_encode($salesChartData) ?>,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '৳' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });

            // Revenue Breakdown Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Today', 'This Week', 'This Month'],
                    datasets: [{
                        data: [<?= $todaysSales ?>, <?= $thisWeekSales ?>, <?= $thisMonthSales ?>],
                        backgroundColor: [
                            '#4e73df',
                            '#1cc88a',
                            '#36b9cc'
                        ],
                        borderWidth: 0,
                        cutout: '70%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    }
                }
            });

            // Monthly Revenue Chart
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($monthlyLabels) ?>,
                    datasets: [{
                        label: 'Monthly Revenue (৳)',
                        data: <?= json_encode($monthlyData) ?>,
                        backgroundColor: 'rgba(28, 200, 138, 0.8)',
                        borderColor: '#1cc88a',
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '৳' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Refresh Dashboard Function
            document.getElementById('refreshDashboard').addEventListener('click', function() {
                const btn = this;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Refreshing...';
                btn.disabled = true;
                
                // Add loading class to stats container
                document.getElementById('statsContainer')?.classList.add('stats-loading');
                
                // Fetch fresh statistics
                fetch('apis/dashboard-stats.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update main statistics with animations
                            updateStatWithAnimation('todaysSales', '৳' + parseFloat(data.todaysSales).toLocaleString('en-US', {minimumFractionDigits: 2}));
                            updateStatWithAnimation('totalOrders', parseInt(data.totalOrders).toLocaleString());
                            updateStatWithAnimation('totalProducts', parseInt(data.totalProducts).toLocaleString());
                            updateStatWithAnimation('totalUsers', parseInt(data.totalUsers).toLocaleString());
                            
                            // Update product status badges
                            const badges = {
                                activeProducts: data.data.activeProducts,
                                inactiveProducts: data.data.inactiveProducts,
                                lowStockProducts: data.data.lowStockProducts,
                                outOfStockProducts: data.data.outOfStockProducts,
                                hotItems: data.data.hotItems || 0
                            };
                            
                            updateProductStatusBadges(badges);
                            
                            // Show success message
                            showRefreshSuccess();
                        } else {
                            throw new Error(data.message || 'Failed to refresh statistics');
                        }
                    })
                    .catch(error => {
                        console.error('Dashboard refresh failed:', error);
                        showRefreshError();
                    })
                    .finally(() => {
                        // Reset button
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        document.getElementById('statsContainer')?.classList.remove('stats-loading');
                    });
            });
            
            // Helper function to update stats with animation
            function updateStatWithAnimation(elementId, newValue) {
                const element = document.getElementById(elementId);
                if (element) {
                    element.style.transform = 'scale(0.8)';
                    element.style.opacity = '0.5';
                    setTimeout(() => {
                        element.textContent = newValue;
                        element.style.transform = 'scale(1)';
                        element.style.opacity = '1';
                        element.style.transition = 'all 0.3s ease';
                    }, 150);
                }
            }
            
            // Helper function to update product status badges
            function updateProductStatusBadges(badges) {
                const statusElements = [
                    { selector: '.product-status-item:nth-child(1) .badge', value: badges.activeProducts },
                    { selector: '.product-status-item:nth-child(2) .badge', value: badges.inactiveProducts },
                    { selector: '.product-status-item:nth-child(3) .badge', value: badges.lowStockProducts },
                    { selector: '.product-status-item:nth-child(4) .badge', value: badges.outOfStockProducts },
                    { selector: '.product-status-item:nth-child(5) .badge', value: badges.hotItems }
                ];
                
                statusElements.forEach(item => {
                    const element = document.querySelector(item.selector);
                    if (element) {
                        element.style.transform = 'scale(0.8)';
                        setTimeout(() => {
                            element.textContent = item.value;
                            element.style.transform = 'scale(1)';
                            element.style.transition = 'all 0.2s ease';
                        }, 100);
                    }
                });
                
                // Update active products count in main stats
                const activeProductsSmall = document.querySelector('#totalProducts').parentElement.querySelector('small');
                if (activeProductsSmall) {
                    activeProductsSmall.innerHTML = '<i class="fas fa-plus"></i> ' + badges.activeProducts + ' Active';
                }
            }
            
            // Helper function to show refresh success
            function showRefreshSuccess() {
                const message = document.createElement('div');
                message.className = 'alert alert-success alert-dismissible fade show position-fixed';
                message.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                message.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>Dashboard refreshed successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(message);
                setTimeout(() => message.remove(), 3000);
            }
            
            // Helper function to show refresh error
            function showRefreshError() {
                const message = document.createElement('div');
                message.className = 'alert alert-danger alert-dismissible fade show position-fixed';
                message.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                message.innerHTML = `
                    <i class="fas fa-exclamation-triangle me-2"></i>Failed to refresh dashboard. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(message);
                setTimeout(() => message.remove(), 5000);
            }

            // Auto refresh every 5 minutes
            setInterval(function() {
                // Refresh statistics via AJAX (optional)
                fetch('apis/dashboard-stats.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update main statistics
                            document.getElementById('todaysSales').textContent = '৳' + parseFloat(data.todaysSales).toLocaleString('en-US', {minimumFractionDigits: 2});
                            document.getElementById('totalOrders').textContent = parseInt(data.totalOrders).toLocaleString();
                            document.getElementById('totalProducts').textContent = parseInt(data.totalProducts).toLocaleString();
                            document.getElementById('totalUsers').textContent = parseInt(data.totalUsers).toLocaleString();
                            
                            // Update product status badges in the Product Status section
                            const activeProductsBadge = document.querySelector('.product-status-item:nth-child(1) .badge');
                            const inactiveProductsBadge = document.querySelector('.product-status-item:nth-child(2) .badge');
                            const lowStockBadge = document.querySelector('.product-status-item:nth-child(3) .badge');
                            const outOfStockBadge = document.querySelector('.product-status-item:nth-child(4) .badge');
                            const hotItemsBadge = document.querySelector('.product-status-item:nth-child(5) .badge');
                            
                            if (activeProductsBadge) activeProductsBadge.textContent = data.data.activeProducts;
                            if (inactiveProductsBadge) inactiveProductsBadge.textContent = data.data.inactiveProducts;
                            if (lowStockBadge) lowStockBadge.textContent = data.data.lowStockProducts;
                            if (outOfStockBadge) outOfStockBadge.textContent = data.data.outOfStockProducts;
                            if (hotItemsBadge) hotItemsBadge.textContent = data.data.hotItems || 0;
                            
                            // Update active products count in the main stats card small text
                            const activeProductsSmall = document.querySelector('#totalProducts').parentElement.querySelector('small');
                            if (activeProductsSmall) {
                                activeProductsSmall.innerHTML = '<i class="fas fa-plus"></i> ' + data.data.activeProducts + ' Active';
                            }
                        }
                    })
                    .catch(error => console.log('Auto-refresh failed:', error));
            }, 300000); // 5 minutes
        });
        </script>

        <!-- Enhanced Dashboard Styles -->
        <style>
        /* Stats Cards */
        .stats-card {
            transition: all 0.3s ease;
            border-radius: 12px;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
        }
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1;
        }
        .stats-label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
            margin-top: 4px;
        }

        /* Card Improvements */
        .card {
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .card-header {
            border-bottom: 1px solid #f1f3f4;
            font-weight: 600;
            color: #495057;
        }

        /* Progress Bars */
        .progress {
            border-radius: 10px;
            background-color: #f8f9fa;
        }
        .progress-bar {
            border-radius: 10px;
        }

        /* Badges */
        .badge {
            font-weight: 500;
            padding: 0.5em 0.8em;
            border-radius: 8px;
        }

        /* Tables */
        .table th {
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            font-size: 0.9rem;
        }
        .table td {
            font-size: 0.9rem;
            vertical-align: middle;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fb;
        }

        /* Buttons */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Timeline Styles */
        .timeline {
            position: relative;
            padding-left: 20px;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
            border-left: 2px solid #e3e6f0;
            padding-left: 20px;
            margin-left: 10px;
        }
        .timeline-marker {
            position: absolute;
            left: -6px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .timeline-title {
            margin-bottom: 5px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .timeline-text {
            margin-bottom: 5px;
            font-size: 0.85rem;
            color: #6c757d;
        }

        /* Status Items */
        .status-item, .product-status-item, .sales-item, .report-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f3f4;
        }
        .status-item:last-child, .product-status-item:last-child, .sales-item:last-child, .report-item:last-child {
            border-bottom: none;
        }

        /* Charts */
        canvas {
            border-radius: 8px;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .card {
            animation: fadeInUp 0.6s ease forwards;
        }
        .stats-card:nth-child(1) { animation-delay: 0.1s; }
        .stats-card:nth-child(2) { animation-delay: 0.2s; }
        .stats-card:nth-child(3) { animation-delay: 0.3s; }
        .stats-card:nth-child(4) { animation-delay: 0.4s; }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .stats-number {
                font-size: 1.5rem;
            }
            .stats-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
        }

        /* Custom scrollbar */
        .table-responsive::-webkit-scrollbar {
            height: 6px;
        }
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Loading state for statistics */
        .stats-loading {
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .stats-loading .stats-card {
            transform: scale(0.98);
            transition: transform 0.3s ease;
        }
        
        /* Enhanced refresh button */
        #refreshDashboard:disabled {
            opacity: 0.8;
        }
        
        /* Toast-like notifications */
        .alert.position-fixed {
            animation: slideInRight 0.3s ease;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        </style>
    </body>
</html>