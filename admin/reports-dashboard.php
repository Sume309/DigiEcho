<?php
session_start();
require_once '../config/database.php';
require_once '../src/ReportManager.php';

// Check admin authentication using the same method as other admin pages
require_once __DIR__ . '/../vendor/autoload.php';
use App\auth\Admin;

if(!Admin::Check()){
    header('Location: ../login.php?message=Please login to access reports');
    exit();
}

$reportManager = new ReportManager($pdo);
$page_title = "Reports Dashboard";

// Get dashboard data
$dashboard_id = $_GET['dashboard_id'] ?? 1;
try {
    $dashboard_data = $reportManager->getDashboardData($dashboard_id, $_SESSION['userid']);
} catch (Exception $e) {
    $dashboard_data = ['widgets' => [], 'name' => 'Default Dashboard'];
}

// Get categories for sidebar
$categories = $reportManager->getReportCategories();
$recent_reports = $reportManager->getRecentReports($_SESSION['userid'], 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?= $page_title ?> - Family Haat Bazar</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="assets/css/styles.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .widget-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .widget-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        .widget-body {
            padding: 20px;
        }
        .metric-card {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .reports-sidebar {
            background: #f8f9fa;
            min-height: calc(100vh - 56px);
            padding: 20px 0;
        }
        .category-section {
            margin-bottom: 30px;
        }
        .category-header {
            padding: 10px 20px;
            background: #e9ecef;
            font-weight: bold;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-item {
            padding: 8px 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
        }
        .report-item:hover {
            background: #e9ecef;
        }
        .recent-reports {
            background: white;
            border-radius: 8px;
            margin: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 40px;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'components/header.php'; ?>
    
    <div id="layoutSidenav">
        <!-- Reports Sidebar -->
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-light">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Reports Dashboard</div>
                        <a class="nav-link active" href="reports-dashboard.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                        <a class="nav-link" href="reports-list.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-list"></i></div>
                            All Reports
                        </a>
                        <a class="nav-link" href="reports-builder.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tools"></i></div>
                            Report Builder
                        </a>
                        
                        <div class="sb-sidenav-menu-heading">Categories</div>
                        <?php foreach ($categories as $category): ?>
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" 
                           data-bs-target="#collapse<?= $category['id'] ?>" aria-expanded="false">
                            <div class="sb-nav-link-icon">
                                <i class="<?= $category['icon'] ?>" style="color: <?= $category['color'] ?>"></i>
                            </div>
                            <?= htmlspecialchars($category['name']) ?>
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapse<?= $category['id'] ?>">
                            <nav class="sb-sidenav-menu-nested nav" id="category-reports-<?= $category['id'] ?>">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </nav>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Recent Reports -->
                <?php if (!empty($recent_reports)): ?>
                <div class="recent-reports">
                    <div class="category-header">Recent Reports</div>
                    <?php foreach ($recent_reports as $report): ?>
                    <div class="report-item" onclick="loadReport(<?= $report['template_id'] ?>)">
                        <div style="font-size: 0.9rem; font-weight: 500;">
                            <?= htmlspecialchars($report['template_name']) ?>
                        </div>
                        <div style="font-size: 0.8rem; color: #666;">
                            <?= date('M d, H:i', strtotime($report['created_at'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Main Content -->
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mt-4"><?= htmlspecialchars($dashboard_data['name'] ?? 'Reports Dashboard') ?></h1>
                        <div class="mt-4">
                            <button class="btn btn-primary" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <button class="btn btn-success" onclick="exportDashboard()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    
                    <!-- Dashboard Widgets -->
                    <div class="row mt-4" id="dashboard-widgets">
                        <?php if (empty($dashboard_data['widgets'])): ?>
                        <!-- Default widgets when no dashboard is configured -->
                        <div class="col-xl-3 col-md-6">
                            <div class="widget-card">
                                <div class="widget-header">
                                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Total Sales</h6>
                                </div>
                                <div class="widget-body">
                                    <div class="metric-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                        <div class="metric-value text-white" id="total-sales">Loading...</div>
                                        <div class="metric-label text-white">This Month</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="widget-card">
                                <div class="widget-header">
                                    <h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Total Orders</h6>
                                </div>
                                <div class="widget-body">
                                    <div class="metric-card" style="background: linear-gradient(135deg, #007bff, #6f42c1);">
                                        <div class="metric-value text-white" id="total-orders">Loading...</div>
                                        <div class="metric-label text-white">This Month</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="widget-card">
                                <div class="widget-header">
                                    <h6 class="mb-0"><i class="fas fa-users me-2"></i>New Customers</h6>
                                </div>
                                <div class="widget-body">
                                    <div class="metric-card" style="background: linear-gradient(135deg, #fd7e14, #e83e8c);">
                                        <div class="metric-value text-white" id="new-customers">Loading...</div>
                                        <div class="metric-label text-white">This Month</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="widget-card">
                                <div class="widget-header">
                                    <h6 class="mb-0"><i class="fas fa-percentage me-2"></i>Conversion Rate</h6>
                                </div>
                                <div class="widget-body">
                                    <div class="metric-card" style="background: linear-gradient(135deg, #20c997, #17a2b8);">
                                        <div class="metric-value text-white" id="conversion-rate">Loading...</div>
                                        <div class="metric-label text-white">This Month</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Charts Row -->
                        <div class="col-xl-8">
                            <div class="widget-card">
                                <div class="widget-header">
                                    <h6 class="mb-0"><i class="fas fa-chart-area me-2"></i>Sales Trend</h6>
                                </div>
                                <div class="widget-body">
                                    <div class="chart-container">
                                        <canvas id="salesTrendChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-4">
                            <div class="widget-card">
                                <div class="widget-header">
                                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Top Categories</h6>
                                </div>
                                <div class="widget-body">
                                    <div class="chart-container">
                                        <canvas id="categoriesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php else: ?>
                        <!-- Configured dashboard widgets -->
                        <?php foreach ($dashboard_data['widgets'] as $widget): ?>
                        <div class="col-xl-<?= $widget['size']['width'] ?? 6 ?> col-md-6">
                            <div class="widget-card">
                                <div class="widget-header">
                                    <h6 class="mb-0"><?= htmlspecialchars($widget['title']) ?></h6>
                                </div>
                                <div class="widget-body">
                                    <?php if (isset($widget['error'])): ?>
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?= htmlspecialchars($widget['error']) ?>
                                    </div>
                                    <?php else: ?>
                                    <div id="widget-<?= $widget['id'] ?>">
                                        <!-- Widget content will be loaded here -->
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
    <script>
        // Dashboard functionality
        let dashboardData = <?= json_encode($dashboard_data) ?>;
        let charts = {};

        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            loadCategoryReports();
        });

        function loadDashboardData() {
            // Load default metrics
            fetch('reports-api.php?action=dashboard_metrics')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateMetrics(data.metrics);
                        createCharts(data.charts);
                    }
                })
                .catch(error => console.error('Error loading dashboard data:', error));
        }

        function updateMetrics(metrics) {
            document.getElementById('total-sales').textContent = formatCurrency(metrics.total_sales || 0);
            document.getElementById('total-orders').textContent = formatNumber(metrics.total_orders || 0);
            document.getElementById('new-customers').textContent = formatNumber(metrics.new_customers || 0);
            document.getElementById('conversion-rate').textContent = formatPercentage(metrics.conversion_rate || 0);
        }

        function createCharts(chartData) {
            // Sales Trend Chart
            const salesCtx = document.getElementById('salesTrendChart').getContext('2d');
            charts.salesTrend = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: chartData.sales_trend?.labels || [],
                    datasets: [{
                        label: 'Sales',
                        data: chartData.sales_trend?.data || [],
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            });

            // Categories Chart
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            charts.categories = new Chart(categoriesCtx, {
                type: 'doughnut',
                data: {
                    labels: chartData.categories?.labels || [],
                    datasets: [{
                        data: chartData.categories?.data || [],
                        backgroundColor: [
                            '#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function loadCategoryReports() {
            <?php foreach ($categories as $category): ?>
            loadReportsForCategory(<?= $category['id'] ?>);
            <?php endforeach; ?>
        }

        function loadReportsForCategory(categoryId) {
            fetch(`reports-api.php?action=category_reports&category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById(`category-reports-${categoryId}`);
                        container.innerHTML = '';
                        
                        data.reports.forEach(report => {
                            const item = document.createElement('a');
                            item.className = 'nav-link';
                            item.href = '#';
                            item.onclick = () => loadReport(report.id);
                            item.innerHTML = `<i class="fas fa-chart-bar me-2"></i>${report.name}`;
                            container.appendChild(item);
                        });
                    }
                })
                .catch(error => console.error('Error loading category reports:', error));
        }

        function loadReport(templateId) {
            window.location.href = `reports-view.php?template_id=${templateId}`;
        }

        function refreshDashboard() {
            location.reload();
        }

        function exportDashboard() {
            window.open(`reports-api.php?action=export_dashboard&dashboard_id=${dashboardData.id}&format=pdf`);
        }

        // Utility functions
        function formatCurrency(value) {
            return new Intl.NumberFormat('en-BD', {
                style: 'currency',
                currency: 'BDT',
                minimumFractionDigits: 0
            }).format(value);
        }

        function formatNumber(value) {
            return new Intl.NumberFormat().format(value);
        }

        function formatPercentage(value) {
            return (value * 100).toFixed(1) + '%';
        }
    </script>
</body>
</html>
