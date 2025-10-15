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
$page_title = "All Reports";

// Get search parameters
$search_query = $_GET['search'] ?? '';
$category_id = $_GET['category'] ?? null;
$page = (int)($_GET['page'] ?? 1);

// Get reports and categories
$reports_data = $reportManager->searchReports($search_query, $category_id, $_SESSION['userid'], $page, 12);
$categories = $reportManager->getReportCategories();
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
    <style>
        .report-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            height: 100%;
        }
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .report-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .report-body {
            padding: 20px;
            flex-grow: 1;
        }
        .report-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }
        .category-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            color: white;
        }
        .search-filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .featured-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ffc107;
            color: #000;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .report-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #666;
            margin-top: 10px;
        }
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 30px;
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
                            <a href="reports-dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a href="reports-builder.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Report
                            </a>
                        </div>
                    </div>

                    <!-- Search and Filters -->
                    <div class="search-filters mt-4">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label for="search" class="form-label">Search Reports</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?= htmlspecialchars($search_query) ?>" 
                                           placeholder="Search by name or description...">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>

                    <!-- Reports Grid -->
                    <div class="row">
                        <?php if (empty($reports_data['reports'])): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Reports Found</h4>
                                <p class="text-muted">Try adjusting your search criteria or create a new report.</p>
                                <a href="reports-builder.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create New Report
                                </a>
                            </div>
                        </div>
                        <?php else: ?>
                        <?php foreach ($reports_data['reports'] as $report): ?>
                        <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                            <div class="report-card d-flex flex-column position-relative">
                                <?php if ($report['is_featured']): ?>
                                <div class="featured-badge">Featured</div>
                                <?php endif; ?>
                                
                                <div class="report-header">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="flex-grow-1">
                                            <h5 class="mb-2"><?= htmlspecialchars($report['name']) ?></h5>
                                            <span class="category-badge" style="background-color: <?= $report['category_color'] ?>">
                                                <i class="<?= $report['category_icon'] ?> me-1"></i>
                                                <?= htmlspecialchars($report['category_name']) ?>
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <i class="fas fa-chart-<?= $report['chart_type'] ?> fa-2x text-muted"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="report-body">
                                    <p class="text-muted mb-3">
                                        <?= htmlspecialchars(substr($report['description'] ?? 'No description available.', 0, 120)) ?>
                                        <?= strlen($report['description'] ?? '') > 120 ? '...' : '' ?>
                                    </p>
                                    
                                    <div class="report-stats">
                                        <span><i class="fas fa-clock me-1"></i>Cache: <?= $report['cache_duration'] ?? 300 ?>s</span>
                                        <span><i class="fas fa-sync me-1"></i>Refresh: <?= $report['refresh_interval'] ?? 0 ?>m</span>
                                    </div>
                                </div>
                                
                                <div class="report-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Updated <?= date('M d, Y', strtotime($report['updated_at'])) ?>
                                        </small>
                                        <div class="btn-group" role="group">
                                            <a href="reports-view.php?template_id=<?= $report['id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="reports-view.php?template_id=<?= $report['id'] ?>">
                                                    <i class="fas fa-eye me-2"></i>View Report
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="exportReport(<?= $report['id'] ?>, 'pdf')">
                                                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="exportReport(<?= $report['id'] ?>, 'excel')">
                                                    <i class="fas fa-file-excel me-2"></i>Export Excel
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="exportReport(<?= $report['id'] ?>, 'csv')">
                                                    <i class="fas fa-file-csv me-2"></i>Export CSV
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="#" onclick="scheduleReport(<?= $report['id'] ?>)">
                                                    <i class="fas fa-calendar me-2"></i>Schedule
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="addToFavorites(<?= $report['id'] ?>)">
                                                    <i class="fas fa-star me-2"></i>Add to Favorites
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($reports_data['pagination']['total_pages'] > 1): ?>
                    <div class="pagination-wrapper">
                        <nav aria-label="Reports pagination">
                            <ul class="pagination">
                                <?php
                                $current_page = $reports_data['pagination']['current_page'];
                                $total_pages = $reports_data['pagination']['total_pages'];
                                $query_params = http_build_query(array_filter([
                                    'search' => $search_query,
                                    'category' => $category_id
                                ]));
                                ?>
                                
                                <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $current_page - 1 ?>&<?= $query_params ?>">Previous</a>
                                </li>
                                
                                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&<?= $query_params ?>"><?= $i ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $current_page + 1 ?>&<?= $query_params ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
    <script>
        function exportReport(templateId, format) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'reports-api.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'export_report';
            form.appendChild(actionInput);
            
            const templateInput = document.createElement('input');
            templateInput.type = 'hidden';
            templateInput.name = 'template_id';
            templateInput.value = templateId;
            form.appendChild(templateInput);
            
            const formatInput = document.createElement('input');
            formatInput.type = 'hidden';
            formatInput.name = 'format';
            formatInput.value = format;
            form.appendChild(formatInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function scheduleReport(templateId) {
            // This would open a modal for scheduling
            alert('Schedule report functionality coming soon!');
        }

        function addToFavorites(templateId) {
            fetch('reports-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_favorite&template_id=${templateId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Added to favorites!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }
    </script>
</body>
</html>
