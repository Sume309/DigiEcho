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
$page_title = "Report Builder";

// Get categories for form
$categories = $reportManager->getReportCategories();

// Get available tables for query builder
$tables_query = "SHOW TABLES";
$stmt = $pdo->query($tables_query);
$available_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?= $page_title ?> - Family Haat Bazar</title>
    <link href="assets/css/styles.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css" rel="stylesheet" />
    <style>
        .builder-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
        }
        .section-body {
            padding: 20px;
        }
        .query-editor {
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 200px;
        }
        .table-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
        }
        .table-item {
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
            margin-bottom: 5px;
            transition: background 0.2s;
        }
        .table-item:hover {
            background: #f8f9fa;
        }
        .preview-area {
            min-height: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            background: #f8f9fa;
        }
        .parameter-row, .column-row {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 10px;
            border-left: 4px solid #007bff;
        }
        .btn-add-parameter, .btn-add-column {
            background: #28a745;
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-remove {
            background: #dc3545;
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .chart-preview {
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border: 2px dashed #ddd;
            border-radius: 8px;
            color: #666;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'components/header.php'; ?>
    
    <div id="layoutSidenav">
        <?php include 'components/sidebar.php'; ?>
        
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mt-4"><?= $page_title ?></h1>
                        <div class="mt-4">
                            <a href="reports-list.php" class="btn btn-secondary">
                                <i class="fas fa-list"></i> All Reports
                            </a>
                            <a href="reports-dashboard.php" class="btn btn-primary">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </div>
                    </div>

                    <form id="reportBuilderForm">
                        <!-- Basic Information -->
                        <div class="builder-section">
                            <div class="section-header">
                                <i class="fas fa-info-circle me-2"></i>Basic Information
                            </div>
                            <div class="section-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="reportName" class="form-label">Report Name *</label>
                                            <input type="text" class="form-control" id="reportName" name="name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="reportCategory" class="form-label">Category *</label>
                                            <select class="form-select" id="reportCategory" name="category_id" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="chartType" class="form-label">Chart Type *</label>
                                            <select class="form-select" id="chartType" name="chart_type" required>
                                                <option value="table">Table</option>
                                                <option value="line">Line Chart</option>
                                                <option value="bar">Bar Chart</option>
                                                <option value="pie">Pie Chart</option>
                                                <option value="doughnut">Doughnut Chart</option>
                                                <option value="area">Area Chart</option>
                                                <option value="column">Column Chart</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="reportDescription" class="form-label">Description</label>
                                            <textarea class="form-control" id="reportDescription" name="description" rows="3"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="isFeatured" name="is_featured">
                                                <label class="form-check-label" for="isFeatured">
                                                    Featured Report
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="refreshInterval" class="form-label">Auto Refresh (minutes)</label>
                                            <input type="number" class="form-control" id="refreshInterval" name="refresh_interval" value="30" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Query Builder -->
                        <div class="builder-section">
                            <div class="section-header">
                                <i class="fas fa-database me-2"></i>Query Builder
                            </div>
                            <div class="section-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <h6>Available Tables</h6>
                                        <div class="table-list">
                                            <?php foreach ($available_tables as $table): ?>
                                            <div class="table-item" onclick="insertTable('<?= $table ?>')">
                                                <i class="fas fa-table me-2"></i><?= $table ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6>SQL Query</h6>
                                            <div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="testQuery()">
                                                    <i class="fas fa-play"></i> Test Query
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="formatQuery()">
                                                    <i class="fas fa-code"></i> Format
                                                </button>
                                            </div>
                                        </div>
                                        <div class="query-editor">
                                            <textarea id="sqlQuery" name="query_template" placeholder="SELECT * FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY)"></textarea>
                                        </div>
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Use <code>{{parameter_name}}</code> for dynamic parameters
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parameters -->
                        <div class="builder-section">
                            <div class="section-header">
                                <i class="fas fa-sliders-h me-2"></i>Parameters
                            </div>
                            <div class="section-body">
                                <div id="parametersContainer">
                                    <!-- Parameters will be added dynamically -->
                                </div>
                                <button type="button" class="btn-add-parameter" onclick="addParameter()">
                                    <i class="fas fa-plus me-2"></i>Add Parameter
                                </button>
                            </div>
                        </div>

                        <!-- Columns Configuration -->
                        <div class="builder-section">
                            <div class="section-header">
                                <i class="fas fa-columns me-2"></i>Columns Configuration
                            </div>
                            <div class="section-body">
                                <div id="columnsContainer">
                                    <!-- Columns will be added dynamically -->
                                </div>
                                <button type="button" class="btn-add-column" onclick="addColumn()">
                                    <i class="fas fa-plus me-2"></i>Add Column
                                </button>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="builder-section">
                            <div class="section-header">
                                <i class="fas fa-eye me-2"></i>Preview
                            </div>
                            <div class="section-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Data Preview</h6>
                                        <div class="preview-area" id="dataPreview">
                                            <div class="text-center text-muted">
                                                <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                                <p>Test your query to see data preview</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Chart Preview</h6>
                                        <div class="chart-preview" id="chartPreview">
                                            <div class="text-center">
                                                <i class="fas fa-chart-line fa-3x mb-3"></i>
                                                <p>Chart preview will appear here</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="builder-section">
                            <div class="section-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                            <i class="fas fa-undo me-2"></i>Reset
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" onclick="saveAsDraft()">
                                            <i class="fas fa-save me-2"></i>Save as Draft
                                        </button>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-success" onclick="saveReport()">
                                            <i class="fas fa-check me-2"></i>Create Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/sql/sql.min.js"></script>
    <script src="assets/js/scripts.js"></script>
    <script>
        let sqlEditor;
        let parameterCount = 0;
        let columnCount = 0;

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize CodeMirror for SQL editor
            sqlEditor = CodeMirror.fromTextArea(document.getElementById('sqlQuery'), {
                mode: 'text/x-sql',
                theme: 'monokai',
                lineNumbers: true,
                autoCloseBrackets: true,
                matchBrackets: true,
                indentWithTabs: true,
                smartIndent: true,
                lineWrapping: true
            });

            // Auto-generate slug from name
            document.getElementById('reportName').addEventListener('input', function() {
                // Auto-generate slug logic could go here
            });
        });

        function insertTable(tableName) {
            const cursor = sqlEditor.getCursor();
            sqlEditor.replaceRange(tableName, cursor);
            sqlEditor.focus();
        }

        function addParameter() {
            parameterCount++;
            const container = document.getElementById('parametersContainer');
            const parameterHtml = `
                <div class="parameter-row" id="parameter_${parameterCount}">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Parameter Name</label>
                            <input type="text" class="form-control" name="parameters[${parameterCount}][name]" placeholder="e.g., days">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="parameters[${parameterCount}][type]">
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="date">Date</option>
                                <option value="select">Select</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Label</label>
                            <input type="text" class="form-control" name="parameters[${parameterCount}][label]" placeholder="Display label">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Default Value</label>
                            <input type="text" class="form-control" name="parameters[${parameterCount}][default]" placeholder="Default value">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn-remove" onclick="removeParameter(${parameterCount})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', parameterHtml);
        }

        function removeParameter(id) {
            document.getElementById(`parameter_${id}`).remove();
        }

        function addColumn() {
            columnCount++;
            const container = document.getElementById('columnsContainer');
            const columnHtml = `
                <div class="column-row" id="column_${columnCount}">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Column Key</label>
                            <input type="text" class="form-control" name="columns[${columnCount}][key]" placeholder="e.g., total_sales">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Display Label</label>
                            <input type="text" class="form-control" name="columns[${columnCount}][label]" placeholder="e.g., Total Sales">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Data Type</label>
                            <select class="form-select" name="columns[${columnCount}][type]">
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="currency">Currency</option>
                                <option value="percentage">Percentage</option>
                                <option value="date">Date</option>
                                <option value="datetime">DateTime</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Format</label>
                            <input type="text" class="form-control" name="columns[${columnCount}][format]" placeholder="Optional format">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn-remove" onclick="removeColumn(${columnCount})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', columnHtml);
        }

        function removeColumn(id) {
            document.getElementById(`column_${id}`).remove();
        }

        function testQuery() {
            const query = sqlEditor.getValue();
            if (!query.trim()) {
                alert('Please enter a SQL query');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'test_query');
            formData.append('query', query);

            fetch('reports-api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayPreview(data.data);
                } else {
                    document.getElementById('dataPreview').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Query Error: ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while testing the query');
            });
        }

        function displayPreview(data) {
            const previewContainer = document.getElementById('dataPreview');
            
            if (!data || data.length === 0) {
                previewContainer.innerHTML = '<div class="text-muted">No data returned</div>';
                return;
            }

            // Create table
            let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
            
            // Headers
            html += '<thead class="table-dark"><tr>';
            Object.keys(data[0]).forEach(key => {
                html += `<th>${key}</th>`;
            });
            html += '</tr></thead>';
            
            // Data (limit to first 10 rows for preview)
            html += '<tbody>';
            data.slice(0, 10).forEach(row => {
                html += '<tr>';
                Object.values(row).forEach(value => {
                    html += `<td>${value || ''}</td>`;
                });
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            
            if (data.length > 10) {
                html += `<small class="text-muted">Showing first 10 of ${data.length} rows</small>`;
            }
            
            previewContainer.innerHTML = html;
        }

        function formatQuery() {
            const query = sqlEditor.getValue();
            // Basic SQL formatting
            const formatted = query
                .replace(/\bSELECT\b/gi, '\nSELECT')
                .replace(/\bFROM\b/gi, '\nFROM')
                .replace(/\bWHERE\b/gi, '\nWHERE')
                .replace(/\bGROUP BY\b/gi, '\nGROUP BY')
                .replace(/\bORDER BY\b/gi, '\nORDER BY')
                .replace(/\bJOIN\b/gi, '\nJOIN')
                .replace(/\bLEFT JOIN\b/gi, '\nLEFT JOIN')
                .replace(/\bRIGHT JOIN\b/gi, '\nRIGHT JOIN')
                .replace(/\bINNER JOIN\b/gi, '\nINNER JOIN');
            
            sqlEditor.setValue(formatted.trim());
        }

        function saveReport() {
            const formData = new FormData(document.getElementById('reportBuilderForm'));
            formData.append('action', 'create_report');
            formData.append('query_template', sqlEditor.getValue());

            // Collect parameters
            const parameters = {};
            document.querySelectorAll('[name^="parameters["]').forEach(input => {
                const matches = input.name.match(/parameters\[(\d+)\]\[(\w+)\]/);
                if (matches) {
                    const id = matches[1];
                    const field = matches[2];
                    if (!parameters[id]) parameters[id] = {};
                    parameters[id][field] = input.value;
                }
            });

            // Collect columns
            const columns = {};
            document.querySelectorAll('[name^="columns["]').forEach(input => {
                const matches = input.name.match(/columns\[(\d+)\]\[(\w+)\]/);
                if (matches) {
                    const id = matches[1];
                    const field = matches[2];
                    if (!columns[id]) columns[id] = {};
                    columns[id][field] = input.value;
                }
            });

            formData.append('parameters_data', JSON.stringify(parameters));
            formData.append('columns_data', JSON.stringify(columns));

            fetch('reports-api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Report created successfully!');
                    window.location.href = `reports-view.php?template_id=${data.template_id}`;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the report');
            });
        }

        function saveAsDraft() {
            // Implementation for saving as draft
            alert('Save as draft functionality coming soon!');
        }

        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All changes will be lost.')) {
                document.getElementById('reportBuilderForm').reset();
                sqlEditor.setValue('');
                document.getElementById('parametersContainer').innerHTML = '';
                document.getElementById('columnsContainer').innerHTML = '';
                document.getElementById('dataPreview').innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                        <p>Test your query to see data preview</p>
                    </div>
                `;
                parameterCount = 0;
                columnCount = 0;
            }
        }
    </script>
</body>
</html>
