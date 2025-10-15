<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone to Bangladesh Standard Time
date_default_timezone_set('Asia/Dhaka');

require __DIR__ . '/../vendor/autoload.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Database connection using settings
$db = new MysqliDb(
    settings()['hostname'],
    settings()['user'],
    settings()['password'],
    settings()['database']
);

$pageTitle = 'Advanced Reports';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= $pageTitle ?> - DigiEcho Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Date Range Picker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    
    <style>
        .report-card {
            transition: transform 0.2s;
        }
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .chart-container {
            position: relative;
            height: 400px;
        }
        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .export-buttons .btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'components/navbar.php'; ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'components/header.php'; ?>
                
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-chart-line me-2"></i><?= $pageTitle ?>
                        </h1>
                        <div class="export-buttons">
                            <button class="btn btn-success" onclick="exportReport('excel')">
                                <i class="fas fa-file-excel me-1"></i>Export Excel
                            </button>
                            <button class="btn btn-danger" onclick="exportReport('pdf')">
                                <i class="fas fa-file-pdf me-1"></i>Export PDF
                            </button>
                            <button class="btn btn-info" onclick="exportReport('csv')">
                                <i class="fas fa-file-csv me-1"></i>Export CSV
                            </button>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-section">
                        <h5><i class="fas fa-filter me-2"></i>Report Filters</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Date Range</label>
                                <input type="text" id="dateRange" class="form-control" placeholder="Select date range">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Report Type</label>
                                <select id="reportType" class="form-select">
                                    <option value="sales">Sales Report</option>
                                    <option value="products">Product Report</option>
                                    <option value="customers">Customer Report</option>
                                    <option value="inventory">Inventory Report</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status Filter</label>
                                <select id="statusFilter" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Category Filter</label>
                                <select id="categoryFilter" class="form-select">
                                    <option value="">All Categories</option>
                                    <!-- Categories will be loaded dynamically -->
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-primary d-block w-100" onclick="generateReport()">
                                    <i class="fas fa-search me-1"></i>Generate Report
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2 report-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Revenue
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalRevenue">৳0.00</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2 report-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Orders
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalOrders">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2 report-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Average Order Value
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="avgOrderValue">৳0.00</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2 report-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Growth Rate
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="growthRate">0%</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <!-- Revenue Trend Chart -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Revenue Trend</h6>
                                    <div class="dropdown no-arrow">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                            aria-labelledby="dropdownMenuLink">
                                            <div class="dropdown-header">Chart Options:</div>
                                            <a class="dropdown-item" href="#" onclick="changeChartType('line')">Line Chart</a>
                                            <a class="dropdown-item" href="#" onclick="changeChartType('bar')">Bar Chart</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="revenueChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Products Chart -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Top Products</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="topProductsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Detailed Report Data</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="reportTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr id="tableHeaders">
                                            <!-- Headers will be populated dynamically -->
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody">
                                        <!-- Data will be populated dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include 'components/footer.php'; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Date Range Picker -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        let revenueChart = null;
        let topProductsChart = null;

        $(document).ready(function() {
            // Initialize date range picker
            $('#dateRange').daterangepicker({
                startDate: moment().subtract(29, 'days'),
                endDate: moment(),
                ranges: {
                   'Today': [moment(), moment()],
                   'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                   'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                   'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                   'This Month': [moment().startOf('month'), moment().endOf('month')],
                   'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            });

            // Load categories for filter
            loadCategories();
            
            // Generate initial report
            generateReport();
        });

        function loadCategories() {
            fetch('apis/dashboard-stats.php?action=categories')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('categoryFilter');
                    if (data.success && data.data) {
                        data.data.forEach(category => {
                            const option = document.createElement('option');
                            option.value = category.id;
                            option.textContent = category.name;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading categories:', error));
        }

        function generateReport() {
            const dateRange = $('#dateRange').val();
            const reportType = document.getElementById('reportType').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;

            // Show loading state
            showLoading();

            // Fetch report data
            const params = new URLSearchParams({
                action: 'generate_report',
                date_range: dateRange,
                report_type: reportType,
                status_filter: statusFilter,
                category_filter: categoryFilter
            });

            fetch(`apis/reports-data.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateSummaryCards(data.summary);
                        updateCharts(data.charts);
                        updateTable(data.table);
                    } else {
                        console.error('Report generation failed:', data.error);
                        alert('Failed to generate report: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error generating report:', error);
                    alert('Error generating report. Please try again.');
                })
                .finally(() => {
                    hideLoading();
                });
        }

        function updateSummaryCards(summary) {
            document.getElementById('totalRevenue').textContent = '$' + summary.total_revenue.toFixed(2);
            document.getElementById('totalOrders').textContent = summary.total_orders.toLocaleString();
            document.getElementById('avgOrderValue').textContent = '$' + summary.avg_order_value.toFixed(2);
            document.getElementById('growthRate').textContent = summary.growth_rate.toFixed(1) + '%';
        }

        function updateCharts(charts) {
            // Update Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            
            if (revenueChart) {
                revenueChart.destroy();
            }
            
            revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: charts.revenue.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: charts.revenue.data,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
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
                                    return '$' + value.toFixed(0);
                                }
                            }
                        }
                    }
                }
            });

            // Update Top Products Chart
            const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
            
            if (topProductsChart) {
                topProductsChart.destroy();
            }
            
            topProductsChart = new Chart(topProductsCtx, {
                type: 'doughnut',
                data: {
                    labels: charts.top_products.labels,
                    datasets: [{
                        data: charts.top_products.data,
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
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

        function updateTable(tableData) {
            const headers = document.getElementById('tableHeaders');
            const body = document.getElementById('tableBody');
            
            // Clear existing content
            headers.innerHTML = '';
            body.innerHTML = '';
            
            // Add headers
            tableData.headers.forEach(header => {
                const th = document.createElement('th');
                th.textContent = header;
                headers.appendChild(th);
            });
            
            // Add data rows
            tableData.rows.forEach(row => {
                const tr = document.createElement('tr');
                row.forEach(cell => {
                    const td = document.createElement('td');
                    td.textContent = cell;
                    tr.appendChild(td);
                });
                body.appendChild(tr);
            });
        }

        function changeChartType(type) {
            if (revenueChart) {
                revenueChart.config.type = type;
                revenueChart.update();
            }
        }

        function exportReport(format) {
            const dateRange = $('#dateRange').val();
            const reportType = document.getElementById('reportType').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;

            const params = new URLSearchParams({
                format: format,
                date_range: dateRange,
                report_type: reportType,
                status_filter: statusFilter,
                category_filter: categoryFilter
            });

            window.open(`apis/export-report.php?${params}`, '_blank');
        }

        function showLoading() {
            // Add loading overlay or spinner
            const overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i><br>Generating Report...</div>';
            overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);color:white;display:flex;align-items:center;justify-content:center;z-index:9999;';
            document.body.appendChild(overlay);
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.remove();
            }
        }
    </script>
</body>
</html>

<?php $db->disconnect(); ?>
