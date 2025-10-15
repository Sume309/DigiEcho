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
$template_id = $_GET['template_id'] ?? null;

if (!$template_id) {
    header('Location: reports-list.php');
    exit();
}

// Get report template
try {
    $stmt = $pdo->prepare("SELECT rt.*, rc.name as category_name, rc.color as category_color, rc.icon as category_icon 
                          FROM report_templates rt 
                          JOIN report_categories rc ON rt.category_id = rc.id 
                          WHERE rt.id = ? AND rt.is_active = 1");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        throw new Exception("Report template not found");
    }
    
    $page_title = $template['name'];
} catch (Exception $e) {
    header('Location: reports-list.php');
    exit();
}

// Get parameters and filters from template
$parameters = json_decode($template['parameters'] ?? '{}', true);
$filters = json_decode($template['filters'] ?? '{}', true);
$columns = json_decode($template['columns'] ?? '[]', true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?= htmlspecialchars($page_title) ?> - Family Haat Bazar</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="assets/css/styles.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-header {
            background: linear-gradient(135deg, <?= $template['category_color'] ?>, #667eea);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .report-controls {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .report-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .chart-container {
            position: relative;
            height: 400px;
            padding: 20px;
        }
        .table-container {
            overflow-x: auto;
        }
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .parameter-group {
            margin-bottom: 15px;
        }
        .filter-group {
            margin-bottom: 15px;
        }
        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .pagination-info {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'components/header.php'; ?>
    
    <div id="layoutSidenav">
        <?php include 'components/sidebar.php'; ?>
        
        <div id="layoutSidenav_content">
            <!-- Report Header -->
            <div class="report-header">
                <div class="container-fluid px-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="mb-2">
                                <i class="<?= $template['category_icon'] ?> me-2"></i>
                                <?= htmlspecialchars($template['name']) ?>
                            </h1>
                            <p class="mb-0 opacity-75">
                                <?= htmlspecialchars($template['description'] ?? 'No description available.') ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="export-buttons">
                                <button class="btn btn-light" onclick="refreshReport()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                                <div class="btn-group">
                                    <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="exportReport('pdf')">
                                            <i class="fas fa-file-pdf me-2"></i>PDF
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="exportReport('excel')">
                                            <i class="fas fa-file-excel me-2"></i>Excel
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="exportReport('csv')">
                                            <i class="fas fa-file-csv me-2"></i>CSV
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="exportReport('json')">
                                            <i class="fas fa-file-code me-2"></i>JSON
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <main>
                <div class="container-fluid px-4">
                    <!-- Report Controls -->
                    <div class="report-controls">
                        <form id="reportForm" onsubmit="return false;">
                            <div class="row">
                                <!-- Parameters -->
                                <?php if (!empty($parameters)): ?>
                                <div class="col-md-6">
                                    <h6 class="mb-3"><i class="fas fa-sliders-h me-2"></i>Parameters</h6>
                                    <?php foreach ($parameters as $key => $param): ?>
                                    <div class="parameter-group">
                                        <label for="param_<?= $key ?>" class="form-label">
                                            <?= htmlspecialchars($param['label'] ?? ucfirst($key)) ?>
                                        </label>
                                        <?php if ($param['type'] === 'number'): ?>
                                        <input type="number" class="form-control" id="param_<?= $key ?>" 
                                               name="parameters[<?= $key ?>]" value="<?= $param['default'] ?? '' ?>">
                                        <?php elseif ($param['type'] === 'date'): ?>
                                        <input type="date" class="form-control" id="param_<?= $key ?>" 
                                               name="parameters[<?= $key ?>]" value="<?= $param['default'] ?? date('Y-m-d') ?>">
                                        <?php else: ?>
                                        <input type="text" class="form-control" id="param_<?= $key ?>" 
                                               name="parameters[<?= $key ?>]" value="<?= $param['default'] ?? '' ?>">
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Filters -->
                                <?php if (!empty($filters)): ?>
                                <div class="col-md-6">
                                    <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filters</h6>
                                    <?php foreach ($filters as $key => $filter): ?>
                                    <div class="filter-group">
                                        <label for="filter_<?= $key ?>" class="form-label">
                                            <?= htmlspecialchars($filter['label'] ?? ucfirst($key)) ?>
                                        </label>
                                        <?php if ($filter['type'] === 'select'): ?>
                                        <select class="form-select" id="filter_<?= $key ?>" name="filters[<?= $key ?>]" 
                                                <?= $filter['multiple'] ?? false ? 'multiple' : '' ?>>
                                            <option value="">All</option>
                                            <?php if (isset($filter['options'])): ?>
                                                <?php foreach ($filter['options'] as $option): ?>
                                                <option value="<?= $option ?>"><?= ucfirst($option) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                        <?php else: ?>
                                        <input type="text" class="form-control" id="filter_<?= $key ?>" 
                                               name="filters[<?= $key ?>]">
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary" onclick="executeReport()">
                                        <i class="fas fa-play me-2"></i>Run Report
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                        <i class="fas fa-undo me-2"></i>Reset
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Report Content -->
                    <div class="report-content position-relative">
                        <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div class="mt-2">Loading report data...</div>
                            </div>
                        </div>
                        
                        <!-- Chart View -->
                        <div id="chartView" style="display: none;">
                            <div class="chart-container">
                                <canvas id="reportChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Table View -->
                        <div id="tableView">
                            <div class="table-container">
                                <table class="table table-striped" id="reportTable">
                                    <thead class="table-dark">
                                        <tr id="tableHeaders">
                                            <!-- Headers will be populated dynamically -->
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody">
                                        <tr>
                                            <td colspan="100%" class="text-center py-4">
                                                <i class="fas fa-chart-bar fa-2x text-muted mb-2"></i>
                                                <div class="text-muted">Click "Run Report" to load data</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="pagination-info" id="paginationInfo" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center w-100">
                                    <div>
                                        <span id="recordsInfo">Showing 0 of 0 records</span>
                                    </div>
                                    <div>
                                        <nav aria-label="Report pagination">
                                            <ul class="pagination pagination-sm mb-0" id="paginationControls">
                                                <!-- Pagination controls will be populated dynamically -->
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
    <script>
        const templateId = <?= $template_id ?>;
        const chartType = '<?= $template['chart_type'] ?>';
        let currentChart = null;
        let currentPage = 1;
        let currentData = null;

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing admin functionality...');
            console.log('Template ID:', templateId);
            console.log('Chart Type:', chartType);
            
            // Check if Bootstrap is loaded
            if (typeof bootstrap !== 'undefined') {
                console.log('Bootstrap object: Available');
            } else {
                console.log('Bootstrap object: Not available');
            }
            
            // Check if jQuery is loaded (if used)
            if (typeof $ !== 'undefined') {
                console.log('jQuery object: Available');
            } else {
                console.log('jQuery object: Not available');
            }
            
            // Initialize Bootstrap dropdowns
            const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
            const dropdownList = [...dropdownElementList].map(dropdownToggleEl => new bootstrap.Dropdown(dropdownToggleEl));
            console.log('Bootstrap 5 dropdowns initialized:', dropdownList.length);
            
            // Auto-execute report on page load
            executeReport();
        });

        function executeReport(page = 1) {
            currentPage = page;
            const formData = new FormData(document.getElementById('reportForm'));
            const parameters = {};
            const filters = {};
            
            // Extract parameters and filters
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('parameters[')) {
                    const paramKey = key.match(/parameters\[(.+)\]/)[1];
                    parameters[paramKey] = value;
                } else if (key.startsWith('filters[')) {
                    const filterKey = key.match(/filters\[(.+)\]/)[1];
                    filters[filterKey] = value;
                }
            }

            showLoading(true);

            // Use paginated API for table view
            const apiUrl = chartType === 'table' ? 'reports-api.php?action=paginated_report' : 'reports-api.php';
            const requestData = {
                action: chartType === 'table' ? 'paginated_report' : 'execute_report',
                template_id: templateId,
                parameters: JSON.stringify(parameters),
                filters: JSON.stringify(filters)
            };

            if (chartType === 'table') {
                requestData.page = page;
                requestData.per_page = 50;
            }

            const queryString = new URLSearchParams(requestData).toString();
            
            fetch(chartType === 'table' ? `reports-api.php?${queryString}` : 'reports-api.php', {
                method: chartType === 'table' ? 'GET' : 'POST',
                headers: chartType === 'table' ? {} : {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: chartType === 'table' ? null : new URLSearchParams(requestData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                showLoading(false);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        currentData = data.data;
                        displayReport(data.data);
                    } else {
                        showError(data.message || 'Failed to load report');
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Response text:', text);
                    showError('Invalid response from server. Check console for details.');
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Error:', error);
                showError('An error occurred while loading the report');
            });
        }

        function displayReport(data) {
            if (chartType === 'table') {
                displayTable(data);
            } else {
                displayChart(data);
            }
        }

        function displayTable(data) {
            const headers = document.getElementById('tableHeaders');
            const body = document.getElementById('tableBody');
            const paginationInfo = document.getElementById('paginationInfo');
            
            // Clear existing content
            headers.innerHTML = '';
            body.innerHTML = '';
            
            if (!data.data || data.data.length === 0) {
                body.innerHTML = '<tr><td colspan="100%" class="text-center py-4">No data available</td></tr>';
                paginationInfo.style.display = 'none';
                return;
            }
            
            // Create headers
            const columns = data.columns || Object.keys(data.data[0]).map(key => ({key, label: key}));
            columns.forEach(col => {
                const th = document.createElement('th');
                th.textContent = col.label || col.key;
                headers.appendChild(th);
            });
            
            // Create rows
            data.data.forEach(row => {
                const tr = document.createElement('tr');
                columns.forEach(col => {
                    const td = document.createElement('td');
                    td.textContent = row[col.key] || '';
                    tr.appendChild(td);
                });
                body.appendChild(tr);
            });
            
            // Update pagination
            if (data.pagination) {
                updatePagination(data.pagination);
                paginationInfo.style.display = 'block';
            }
        }

        function displayChart(data) {
            document.getElementById('chartView').style.display = 'block';
            document.getElementById('tableView').style.display = 'none';
            
            const ctx = document.getElementById('reportChart').getContext('2d');
            
            if (currentChart) {
                currentChart.destroy();
            }
            
            const chartConfig = data.chart_config || {};
            const chartData = prepareChartData(data);
            
            currentChart = new Chart(ctx, {
                type: chartType,
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    ...chartConfig
                }
            });
        }

        function prepareChartData(data) {
            // This is a simplified version - you'd need more sophisticated logic
            // based on your data structure and chart requirements
            const labels = data.data.map(row => Object.values(row)[0]);
            const values = data.data.map(row => Object.values(row)[1]);
            
            return {
                labels: labels,
                datasets: [{
                    label: data.template_name,
                    data: values,
                    backgroundColor: [
                        '#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14'
                    ],
                    borderColor: '#007bff',
                    borderWidth: 2
                }]
            };
        }

        function updatePagination(pagination) {
            const recordsInfo = document.getElementById('recordsInfo');
            const controls = document.getElementById('paginationControls');
            
            const start = (pagination.current_page - 1) * pagination.per_page + 1;
            const end = Math.min(start + pagination.per_page - 1, pagination.total_records);
            
            recordsInfo.textContent = `Showing ${start}-${end} of ${pagination.total_records} records`;
            
            controls.innerHTML = '';
            
            // Previous button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${!pagination.has_prev ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#" onclick="executeReport(${pagination.current_page - 1})">Previous</a>`;
            controls.appendChild(prevLi);
            
            // Page numbers
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === pagination.current_page ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#" onclick="executeReport(${i})">${i}</a>`;
                controls.appendChild(li);
            }
            
            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${!pagination.has_next ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#" onclick="executeReport(${pagination.current_page + 1})">Next</a>`;
            controls.appendChild(nextLi);
        }

        function exportReport(format) {
            const formData = new FormData(document.getElementById('reportForm'));
            const parameters = {};
            const filters = {};
            
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('parameters[')) {
                    const paramKey = key.match(/parameters\[(.+)\]/)[1];
                    parameters[paramKey] = value;
                } else if (key.startsWith('filters[')) {
                    const filterKey = key.match(/filters\[(.+)\]/)[1];
                    filters[filterKey] = value;
                }
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'reports-api.php';
            
            const fields = {
                action: 'export_report',
                template_id: templateId,
                format: format,
                parameters: JSON.stringify(parameters),
                filters: JSON.stringify(filters)
            };
            
            Object.keys(fields).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function refreshReport() {
            executeReport(currentPage);
        }

        function resetForm() {
            document.getElementById('reportForm').reset();
        }

        function showLoading(show) {
            document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
        }

        function showError(message) {
            const body = document.getElementById('tableBody');
            body.innerHTML = `<tr><td colspan="100%" class="text-center py-4 text-danger">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <div>Error: ${message}</div>
            </td></tr>`;
        }
    </script>
</body>
</html>
