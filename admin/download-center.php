<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('HTTP/1.1 503 Service Unavailable');
    exit;
}

$page = 'Download Center';
?>
<?php require __DIR__ . '/components/header.php'; ?>

</head>

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Download Center</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Download Center</li>
                    </ol>

                    <div class="row">
                        <!-- Sales Reports -->
                        <div class="col-xl-6 col-md-6">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-chart-line fa-2x me-3"></i>
                                        <div>
                                            <h5 class="card-title mb-1">Sales Report</h5>
                                            <p class="card-text mb-0">Download detailed sales data with filters</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-light btn-sm" onclick="downloadReport('sales', 'csv')">
                                            <i class="fas fa-file-csv"></i> CSV
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm" onclick="downloadReport('sales', 'excel')">
                                            <i class="fas fa-file-excel"></i> Excel
                                        </button>
                                    </div>
                                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#salesModal">
                                        <i class="fas fa-cog"></i> Options
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Report -->
                        <div class="col-xl-6 col-md-6">
                            <div class="card bg-success text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-boxes fa-2x me-3"></i>
                                        <div>
                                            <h5 class="card-title mb-1">Stock Report</h5>
                                            <p class="card-text mb-0">Current inventory levels and stock status</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-light btn-sm" onclick="downloadReport('stock', 'csv')">
                                            <i class="fas fa-file-csv"></i> CSV
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm" onclick="downloadReport('stock', 'excel')">
                                            <i class="fas fa-file-excel"></i> Excel
                                        </button>
                                    </div>
                                    <small class="text-white-50">Ready to download</small>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Orders -->
                        <div class="col-xl-6 col-md-6">
                            <div class="card bg-warning text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clock fa-2x me-3"></i>
                                        <div>
                                            <h5 class="card-title mb-1">Pending Orders</h5>
                                            <p class="card-text mb-0">Orders awaiting processing</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-light btn-sm" onclick="downloadReport('pending_orders', 'csv')">
                                            <i class="fas fa-file-csv"></i> CSV
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm" onclick="downloadReport('pending_orders', 'excel')">
                                            <i class="fas fa-file-excel"></i> Excel
                                        </button>
                                    </div>
                                    <small class="text-white-50">Ready to download</small>
                                </div>
                            </div>
                        </div>

                        <!-- Processing Orders -->
                        <div class="col-xl-6 col-md-6">
                            <div class="card bg-info text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-cogs fa-2x me-3"></i>
                                        <div>
                                            <h5 class="card-title mb-1">Processing Orders</h5>
                                            <p class="card-text mb-0">Orders currently being processed</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-light btn-sm" onclick="downloadReport('processing_orders', 'csv')">
                                            <i class="fas fa-file-csv"></i> CSV
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm" onclick="downloadReport('processing_orders', 'excel')">
                                            <i class="fas fa-file-excel"></i> Excel
                                        </button>
                                    </div>
                                    <small class="text-white-50">Ready to download</small>
                                </div>
                            </div>
                        </div>

                        <!-- Delivered Orders -->
                        <div class="col-xl-6 col-md-6">
                            <div class="card bg-success text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle fa-2x me-3"></i>
                                        <div>
                                            <h5 class="card-title mb-1">Delivered Orders</h5>
                                            <p class="card-text mb-0">Successfully completed orders</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-light btn-sm" onclick="downloadReport('delivered_orders', 'csv')">
                                            <i class="fas fa-file-csv"></i> CSV
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm" onclick="downloadReport('delivered_orders', 'excel')">
                                            <i class="fas fa-file-excel"></i> Excel
                                        </button>
                                    </div>
                                    <small class="text-white-50">Ready to download</small>
                                </div>
                            </div>
                        </div>

                        <!-- Cancelled Orders -->
                        <div class="col-xl-6 col-md-6">
                            <div class="card bg-danger text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-times-circle fa-2x me-3"></i>
                                        <div>
                                            <h5 class="card-title mb-1">Cancelled Orders</h5>
                                            <p class="card-text mb-0">Orders that were cancelled</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-light btn-sm" onclick="downloadReport('cancelled_orders', 'csv')">
                                            <i class="fas fa-file-csv"></i> CSV
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm" onclick="downloadReport('cancelled_orders', 'excel')">
                                            <i class="fas fa-file-excel"></i> Excel
                                        </button>
                                    </div>
                                    <small class="text-white-50">Ready to download</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Download History -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-history me-1"></i>
                            Recent Downloads
                        </div>
                        <div class="card-body">
                            <div id="downloadHistory">
                                <p class="text-muted">No recent downloads</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <!-- Sales Report Options Modal -->
    <div class="modal fade" id="salesModal" tabindex="-1" aria-labelledby="salesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="salesModalLabel">Sales Report Options</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="salesReportForm">
                        <div class="mb-3">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="start_date">
                        </div>
                        <div class="mb-3">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate" name="end_date">
                        </div>
                        <div class="mb-3">
                            <label for="format" class="form-label">Format</label>
                            <select class="form-select" id="format" name="format">
                                <option value="csv">CSV</option>
                                <option value="excel">Excel</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="downloadSalesReport()">Download</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function downloadReport(type, format) {
            // Add to download history
            addToHistory(type, format);
            
            // Create download URL
            const url = `apis/download-reports.php?type=${type}&format=${format}`;
            
            // Create temporary link and trigger download
            const link = document.createElement('a');
            link.href = url;
            link.download = '';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Show success message
            Swal.fire({
                position: "top-end",
                icon: "success",
                title: "Download started!",
                showConfirmButton: false,
                timer: 1500
            });
        }

        function downloadSalesReport() {
            const form = document.getElementById('salesReportForm');
            const formData = new FormData(form);
            
            const startDate = formData.get('start_date');
            const endDate = formData.get('end_date');
            const format = formData.get('format');
            
            let url = `apis/download-reports.php?type=sales&format=${format}`;
            if (startDate) url += `&start_date=${startDate}`;
            if (endDate) url += `&end_date=${endDate}`;
            
            // Add to download history
            addToHistory('sales', format, startDate, endDate);
            
            // Create temporary link and trigger download
            const link = document.createElement('a');
            link.href = url;
            link.download = '';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Close modal and show success
            const modal = bootstrap.Modal.getInstance(document.getElementById('salesModal'));
            modal.hide();
            
            Swal.fire({
                position: "top-end",
                icon: "success",
                title: "Sales report download started!",
                showConfirmButton: false,
                timer: 1500
            });
        }

        function addToHistory(type, format, startDate = '', endDate = '') {
            const historyDiv = document.getElementById('downloadHistory');
            const now = new Date().toLocaleString();
            
            let reportName = type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            let dateRange = '';
            if (startDate && endDate) {
                dateRange = ` (${startDate} to ${endDate})`;
            }
            
            const historyItem = document.createElement('div');
            historyItem.className = 'alert alert-success alert-dismissible fade show';
            historyItem.innerHTML = `
                <i class="fas fa-download me-2"></i>
                <strong>${reportName} Report</strong> downloaded as ${format.toUpperCase()}${dateRange}
                <small class="text-muted ms-2">${now}</small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Remove "No recent downloads" message if it exists
            const noDownloads = historyDiv.querySelector('.text-muted');
            if (noDownloads) {
                noDownloads.remove();
            }
            
            historyDiv.insertBefore(historyItem, historyDiv.firstChild);
            
            // Keep only last 5 downloads
            const alerts = historyDiv.querySelectorAll('.alert');
            if (alerts.length > 5) {
                alerts[alerts.length - 1].remove();
            }
        }

        // Set default dates (last 30 days)
        document.addEventListener('DOMContentLoaded', function() {
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - 30);
            
            document.getElementById('endDate').value = endDate.toISOString().split('T')[0];
            document.getElementById('startDate').value = startDate.toISOString().split('T')[0];
        });
    </script>
</body>
</html>
