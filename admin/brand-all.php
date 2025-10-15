<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('Location: auto-login.php');
    exit;
}

$db = new MysqliDb(
    settings()['hostname'],
    settings()['user'],
    settings()['password'],
    settings()['database']
);

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_brands':
            handleGetBrands();
            exit;
            
        case 'get_stats':
            handleGetStats();
            exit;
            
        case 'toggle_status':
            handleToggleStatus();
            exit;
            
        case 'bulk_delete':
            handleBulkDelete();
            exit;
            
        case 'export':
            handleExport();
            exit;
    }
}

// Get brands for DataTables
function handleGetBrands() {
    global $db;
    
    try {
        // DataTables parameters
        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 25);
        $search = $_GET['search']['value'] ?? '';
        $orderColumn = intval($_GET['order'][0]['column'] ?? 2);
        $orderDir = $_GET['order'][0]['dir'] ?? 'asc';
        
        // Filter parameters
        $statusFilter = $_GET['status_filter'] ?? '';
        $dateFilter = $_GET['date_filter'] ?? '';
        $productsFilter = $_GET['products_filter'] ?? '';
        $featuredFilter = $_GET['featured_filter'] ?? '';
        
        // Build base query conditions
        $whereConditions = [];
        
        // Search filter
        if (!empty($search)) {
            $searchTerms = explode(' ', $search);
            $searchConditions = [];
            foreach ($searchTerms as $term) {
                $term = trim($term);
                if (!empty($term)) {
                    $searchConditions[] = "(name LIKE '%{$term}%' OR slug LIKE '%{$term}%' OR description LIKE '%{$term}%')";
                }
            }
            if (!empty($searchConditions)) {
                $whereConditions[] = '(' . implode(' AND ', $searchConditions) . ')';
            }
        }
        
        // Status filter
        if ($statusFilter !== '') {
            $whereConditions[] = "is_active = " . intval($statusFilter);
        }
        
        // Date filter
        if (!empty($dateFilter)) {
            switch ($dateFilter) {
                case 'today':
                    $whereConditions[] = "DATE(created_at) = CURDATE()";
                    break;
                case 'week':
                    $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                    break;
            }
        }
        
        // Products filter
        if (!empty($productsFilter)) {
            if ($productsFilter === 'with_products') {
                $whereConditions[] = "id IN (SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL)";
            } elseif ($productsFilter === 'without_products') {
                $whereConditions[] = "id NOT IN (SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL)";
            }
        }
        
        // Featured filter
        if ($featuredFilter !== '') {
            $whereConditions[] = "is_featured = " . intval($featuredFilter);
        }
        
        // Build WHERE clause
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }
        
        // Get total count with filters applied
        $countQuery = "SELECT COUNT(*) as total FROM brands {$whereClause}";
        $totalResult = $db->rawQuery($countQuery);
        $totalRecords = $totalResult[0]['total'];
        
        // Order by
        $columns = ['', '', 'name', 'slug', 'is_active', 'product_count', 'website', 'created_at'];
        $orderByColumn = $columns[$orderColumn] ?? 'name';
        $orderBy = "ORDER BY {$orderByColumn} {$orderDir}";
        
        // Get filtered data with pagination
        $dataQuery = "SELECT * FROM brands {$whereClause} {$orderBy} LIMIT {$start}, {$length}";
        $brands = $db->rawQuery($dataQuery);
        
        // Get product counts for each brand
        $brandIds = array_column($brands, 'id');
        $productCounts = [];
        
        if (!empty($brandIds)) {
            $db->where('brand', $brandIds, 'IN');
            $db->groupBy('brand');
            $productResults = $db->get('products', null, 'brand, COUNT(*) as product_count');
            
            foreach ($productResults as $result) {
                $productCounts[$result['brand']] = $result['product_count'];
            }
        }
        
        // Format data for DataTables
        $data = [];
        foreach ($brands as $brand) {
            $productCount = $productCounts[$brand['id']] ?? 0;
            
            $logo = !empty($brand['logo']) ? 
                "<img src='../assets/brands/{$brand['logo']}' alt='{$brand['name']}' class='brand-logo rounded' width='50' height='40'>" : 
                "<div class='placeholder-logo rounded d-flex align-items-center justify-content-center' style='width:50px;height:40px;background:#f8f9fa;'><i class='fas fa-image text-muted'></i></div>";
            
            $status = $brand['is_active'] ? 
                "<span class='badge bg-success'>Active</span>" : 
                "<span class='badge bg-danger'>Inactive</span>";
            
            $website = !empty($brand['website']) ? 
                "<a href='{$brand['website']}' target='_blank' class='btn btn-sm btn-outline-primary'><i class='fas fa-external-link-alt'></i></a>" : 
                "<span class='text-muted'>-</span>";
            
            $actions = "
                <div class='btn-group' role='group'>
                    <a href='brand-view.php?id={$brand['id']}' class='btn btn-sm btn-info' title='View Details'>
                        <i class='fas fa-eye'></i>
                    </a>
                    <button type='button' class='btn btn-sm btn-outline-" . ($brand['is_active'] ? 'warning' : 'success') . " toggle-status' 
                            data-id='{$brand['id']}' data-status='{$brand['is_active']}' title='" . ($brand['is_active'] ? 'Deactivate' : 'Activate') . "'>
                        <i class='fas fa-" . ($brand['is_active'] ? 'eye-slash' : 'eye') . "'></i>
                    </button>
                    <a href='brand-edit-enhanced.php?id={$brand['id']}' class='btn btn-sm btn-outline-primary' title='Edit'>
                        <i class='fas fa-edit'></i>
                    </a>
                    <button type='button' class='btn btn-sm btn-outline-danger delete-brand' 
                            data-id='{$brand['id']}' data-name='{$brand['name']}' title='Delete'>
                        <i class='fas fa-trash'></i>
                    </button>
                </div>
            ";
            
            $data[] = [
                'checkbox' => "<input type='checkbox' class='form-check-input brand-checkbox' value='{$brand['id']}' />",
                'logo' => $logo,
                'name' => htmlspecialchars($brand['name']),
                'slug' => htmlspecialchars($brand['slug']),
                'status' => $status,
                'products' => "<span class='badge bg-info'>{$productCount}</span>",
                'website' => $website,
                'created_at' => date('M j, Y', strtotime($brand['created_at'])),
                'actions' => $actions
            ];
        }
        
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// Get statistics
function handleGetStats() {
    global $db;
    
    try {
        $stats = [];
        
        // Total brands
        $stats['total'] = $db->getValue('brands', 'COUNT(*)');
        
        // Active brands
        $db->where('is_active', 1);
        $stats['active'] = $db->getValue('brands', 'COUNT(*)');
        
        // Inactive brands
        $db->where('is_active', 0);
        $stats['inactive'] = $db->getValue('brands', 'COUNT(*)');
        
        // Featured brands
        $db->where('is_featured', 1);
        $stats['featured'] = $db->getValue('brands', 'COUNT(*)');
        
        // Brands with products
        $db->where('brand IS NOT NULL');
        $withProductsResult = $db->get('products', null, 'DISTINCT brand');
        $stats['with_products'] = count($withProductsResult);
        
        // Brands without products
        $stats['without_products'] = $stats['total'] - $stats['with_products'];
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load statistics: ' . $e->getMessage()
        ]);
    }
}

// Toggle brand status
function handleToggleStatus() {
    global $db;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }
    
    $brandId = intval($_POST['brand_id'] ?? 0);
    
    if ($brandId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid brand ID']);
        return;
    }
    
    try {
        // Get current status
        $db->where('id', $brandId);
        $brand = $db->getOne('brands', 'is_active, name');
        
        if (!$brand) {
            echo json_encode(['success' => false, 'message' => 'Brand not found']);
            return;
        }
        
        // Toggle status
        $newStatus = $brand['is_active'] ? 0 : 1;
        $updateData = [
            'is_active' => $newStatus,
            'updated_at' => $db->now()
        ];
        
        $db->where('id', $brandId);
        $db->update('brands', $updateData);
        
        $action = $newStatus ? 'activated' : 'deactivated';
        echo json_encode([
            'success' => true,
            'message' => "Brand '{$brand['name']}' has been {$action} successfully."
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update brand status: ' . $e->getMessage()
        ]);
    }
}

// Handle bulk delete
function handleBulkDelete() {
    global $db;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }
    
    $brandIds = $_POST['brand_ids'] ?? [];
    
    if (empty($brandIds) || !is_array($brandIds)) {
        echo json_encode(['success' => false, 'message' => 'No brands selected for deletion']);
        return;
    }
    
    try {
        $deletedCount = 0;
        $skippedCount = 0;
        $errors = [];
        
        foreach ($brandIds as $brandId) {
            $brandId = intval($brandId);
            
            // Check if brand has products
            $db->where('brand', $brandId);
            $productCount = $db->getValue('products', 'COUNT(*)');
            
            if ($productCount > 0) {
                $db->where('id', $brandId);
                $brandName = $db->getValue('brands', 'name');
                $errors[] = "Cannot delete '{$brandName}' - it has {$productCount} associated products";
                $skippedCount++;
                continue;
            }
            
            // Delete brand
            $db->where('id', $brandId);
            if ($db->delete('brands')) {
                $deletedCount++;
            }
        }
        
        $message = "Successfully deleted {$deletedCount} brand(s).";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} brand(s) were skipped.";
        }
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete brands: ' . $e->getMessage()
        ]);
    }
}

// Handle export
function handleExport() {
    global $db;
    
    try {
        $format = $_GET['format'] ?? 'csv';
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="brands_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($output, ['ID', 'Name', 'Slug', 'Description', 'Website', 'Status', 'Featured', 'Products Count', 'Created Date']);
            
            // Get data
            $brands = $db->get('brands', null, '*');
            
            foreach ($brands as $brand) {
                // Get product count for each brand
                $db->where('brand', $brand['id']);
                $productCount = $db->getValue('products', 'COUNT(*)');
                
                fputcsv($output, [
                    $brand['id'],
                    $brand['name'],
                    $brand['slug'],
                    $brand['description'],
                    $brand['website'],
                    $brand['is_active'] ? 'Active' : 'Inactive',
                    $brand['is_featured'] ? 'Yes' : 'No',
                    $productCount,
                    $brand['created_at']
                ]);
            }
            
            fclose($output);
        }
        
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Export failed: ' . $e->getMessage();
    }
}

require __DIR__ . '/components/header.php'; ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .stats-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }
    
    .stats-number {
        font-size: 2rem;
        font-weight: bold;
        margin: 0;
    }
    
    .stats-label {
        color: #6c757d;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .filter-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    
    .table-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    
    .brand-logo {
        object-fit: contain;
        border: 2px solid #dee2e6;
    }
    
    .placeholder-logo {
        border: 2px dashed #dee2e6;
    }
    
    .btn-bulk-actions {
        display: none;
    }
    
    .bulk-actions-visible .btn-bulk-actions {
        display: inline-block;
    }
    
    .table th {
        border-top: none;
        font-weight: 600;
        color: #495057;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    @keyframes countUp {
        from { transform: scale(0.5); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    
    .count-animation {
        animation: countUp 0.6s ease-out;
    }
    
    /* Enhanced Search Styles */
    #customSearch {
        transition: all 0.3s ease;
        border: 2px solid #dee2e6;
    }
    
    #customSearch:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    #customSearch.border-primary {
        border-color: #0d6efd !important;
        background-color: #f8f9ff !important;
    }
    
    .search-highlight {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
    }
    
    .input-group-lg .form-control {
        font-size: 1.1rem;
    }
    
    .toast {
        min-width: 300px;
    }
</style>
</head>

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Brand Management</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Brands</li>
                    </ol>

                    <!-- Success/Error Messages -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- Statistics Dashboard -->
                    <div class="row mb-4" id="statsContainer">
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-primary me-3">
                                        <i class="fas fa-tags"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="totalBrands">0</p>
                                        <p class="stats-label">Total Brands</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-success me-3">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="activeBrands">0</p>
                                        <p class="stats-label">Active Brands</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-danger me-3">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="inactiveBrands">0</p>
                                        <p class="stats-label">Inactive Brands</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-warning me-3">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="featuredBrands">0</p>
                                        <p class="stats-label">Featured</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-info me-3">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="withProducts">0</p>
                                        <p class="stats-label">With Products</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-secondary me-3">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="withoutProducts">0</p>
                                        <p class="stats-label">Without Products</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                  

                    <!-- Filters and Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card filter-card">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filters & Actions</h6>
                                        
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="statusFilter" class="form-label">Status</label>
                                            <select class="form-select" id="statusFilter">
                                                <option value="">All Status</option>
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="dateFilter" class="form-label">Date Added</label>
                                            <select class="form-select" id="dateFilter">
                                                <option value="">All Time</option>
                                                <option value="today">Today</option>
                                                <option value="week">This Week</option>
                                                <option value="month">This Month</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="productsFilter" class="form-label">Products</label>
                                            <select class="form-select" id="productsFilter">
                                                <option value="">All Brands</option>
                                                <option value="with_products">With Products</option>
                                                <option value="without_products">Without Products</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="featuredFilter" class="form-label">Featured</label>
                                            <select class="form-select" id="featuredFilter">
                                                <option value="">All Brands</option>
                                                <option value="1">Featured Only</option>
                                                <option value="0">Non-Featured</option>
                                            </select>
                                        </div>
                                        
                                  
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card table-card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-table me-2"></i>Brand Management</h6>
                                        <div>
                                            <a href="brand-add-enhanced.php" class="btn btn-primary btn-sm"
                                            style="padding:10px 30px; border:20px; border-radius:50px; 
                                            cursor:pointer; font-size:20px; font-weight:1000;
                                             background: #436e70; color:#fff; margin-left:60px;">
                                                <i class="fas fa-plus me-2"></i>Add New Brand
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="brandsTable">
                                            <thead>
                                                <tr>
                                                    <th width="50">
                                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                                    </th>
                                                    <th width="80">Logo</th>
                                                    <th>Name</th>
                                                    <th>Slug</th>
                                                    <th width="120">Status</th>
                                                    <th width="80">Products</th>
                                                    <th width="80">Website</th>
                                                    <th width="120">Created</th>
                                                    <th width="120">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="assets/js/scripts.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#brandsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'brand-all.php?action=get_brands',
                type: 'GET',
                data: function(d) {
                    d.status_filter = $('#statusFilter').val();
                    d.date_filter = $('#dateFilter').val();
                    d.products_filter = $('#productsFilter').val();
                    d.featured_filter = $('#featuredFilter').val();
                },
                error: function(xhr, error, code) {
                    console.error('DataTable AJAX error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Loading Error',
                        text: 'Failed to load brands data. Please refresh the page.'
                    });
                }
            },
            columns: [
                { data: 'checkbox', orderable: false, searchable: false },
                { data: 'logo', orderable: false, searchable: false },
                { data: 'name' },
                { data: 'slug' },
                { data: 'status', orderable: false },
                { data: 'products' },
                { data: 'website', orderable: false },
                { data: 'created_at' },
                { data: 'actions', orderable: false, searchable: false }
            ],
            order: [[2, 'asc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            search: {
                smart: true,
                regex: false,
                caseInsensitive: true
            },
            search: {
                smart: true,
                regex: false,
                caseInsensitive: true
            },
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: 'No brands found',
                zeroRecords: 'No matching brands found',
                search: 'Search brands:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ brands',
                infoEmpty: 'Showing 0 to 0 of 0 brands',
                infoFiltered: '(filtered from _MAX_ total brands)',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Previous'
                }
            },
            drawCallback: function() {
                // Reinitialize event handlers after table redraw
                initializeEventHandlers();
                updateBulkActionVisibility();
            }
        });

        // Enhanced Real-time Search Functionality
        let searchTimeout;
        $('#customSearch').on('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val();
            
            // Visual feedback
            if (searchTerm.length > 0) {
                $(this).addClass('border-primary bg-light');
                $('#clearSearch').removeClass('d-none');
            } else {
                $(this).removeClass('border-primary bg-light');
                $('#clearSearch').addClass('d-none');
            }
            
            // Debounced search
            searchTimeout = setTimeout(function() {
                table.search(searchTerm).draw();
                
                // Show search results info
                if (searchTerm.length > 0) {
                    const info = table.page.info();
                    if (info.recordsDisplay === 0) {
                        showSearchFeedback('No brands found matching your search', 'warning');
                    } else {
                        showSearchFeedback(`Found ${info.recordsDisplay} brand(s) matching "${searchTerm}"`, 'success');
                    }
                }
            }, 300);
        });

        // Clear search functionality
        $('#clearSearch').on('click', function() {
            $('#customSearch').val('').removeClass('border-primary bg-light').focus();
            $(this).addClass('d-none');
            table.search('').draw();
            showSearchFeedback('Search cleared', 'info');
        });

        // Reset all search and filters
        $('#resetAll').on('click', function() {
            // Clear search
            $('#customSearch').val('').removeClass('border-primary bg-light');
            $('#clearSearch').addClass('d-none');
            
            // Clear filters
            $('#statusFilter, #dateFilter, #productsFilter, #featuredFilter').val('');
            
            // Reset table
            table.search('').draw();
            table.ajax.reload();
            loadStatistics();
            
            showSearchFeedback('All search and filters have been reset', 'success');
        });

        // Search feedback function
        function showSearchFeedback(message, type) {
            const toast = $(`
                <div class="position-fixed top-0 end-0 p-3" style="z-index: 11; margin-top: 60px;">
                    <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info'} border-0" role="alert">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-${type === 'success' ? 'check' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                                ${message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(toast);
            const toastElement = new bootstrap.Toast(toast.find('.toast')[0]);
            toastElement.show();
            
            // Auto remove after showing
            setTimeout(() => {
                toast.remove();
            }, 4000);
        }

        // Initialize search button state
        $('#clearSearch').addClass('d-none');

        // Load statistics on page load
        loadStatistics();
        
        // Auto-refresh statistics every 30 seconds
        setInterval(loadStatistics, 30000);

        // Enhanced search functionality
        $('#customSearch').on('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val();
            
            searchTimeout = setTimeout(function() {
                // Apply search to DataTable
                table.search(searchTerm).draw();
                
                // Show search feedback
                if (searchTerm.length > 0) {
                    $('#customSearch').addClass('border-primary');
                } else {
                    $('#customSearch').removeClass('border-primary');
                }
            }, 300); // Debounce search
        });

        // Clear custom search
        $('#clearSearch').on('click', function() {
            $('#customSearch').val('').removeClass('border-primary');
            table.search('').draw();
        });

        // Reset all search and filters
        $('#resetSearch').on('click', function() {
            $('#customSearch').val('').removeClass('border-primary');
            $('#statusFilter, #dateFilter, #productsFilter, #featuredFilter').val('');
            table.search('').draw();
            table.ajax.reload();
            loadStatistics();
        });

        // Filter change handlers
        $('#statusFilter, #dateFilter, #productsFilter, #featuredFilter').on('change', function() {
            table.ajax.reload();
            setTimeout(loadStatistics, 500); // Reload stats after filter
        });

        // Clear filters only (keep search)
        $('#clearFilters').on('click', function() {
            $('#statusFilter, #dateFilter, #productsFilter, #featuredFilter').val('');
            table.ajax.reload();
            loadStatistics();
            
            Swal.fire({
                icon: 'success',
                title: 'Filters Cleared',
                text: 'All filters have been reset',
                timer: 1500,
                showConfirmButton: false
            });
        });

        // Refresh stats manually
        $('#refreshStats').on('click', function() {
            loadStatistics();
            table.ajax.reload();
        });

        // Export CSV
        $('#exportCSV').on('click', function() {
            window.location.href = 'brand-all.php?action=export&format=csv';
        });

        // Select all checkbox
        $('#selectAll').on('change', function() {
            $('.brand-checkbox').prop('checked', this.checked);
            updateBulkActionVisibility();
        });

        // Bulk delete
        $('#bulkDelete').on('click', function() {
            const selectedIds = [];
            $('.brand-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select brands to delete.'
                });
                return;
            }

            Swal.fire({
                title: 'Delete Selected Brands',
                html: `Are you sure you want to delete <strong>${selectedIds.length}</strong> selected brand(s)?<br><br><small class="text-danger">This action cannot be undone!</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete All',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performBulkDelete(selectedIds);
                }
            });
        });

        // Initialize event handlers
        function initializeEventHandlers() {
            // Individual checkbox change
            $('.brand-checkbox').off('change').on('change', function() {
                updateBulkActionVisibility();
                
                // Update select all state
                const totalCheckboxes = $('.brand-checkbox').length;
                const checkedCheckboxes = $('.brand-checkbox:checked').length;
                $('#selectAll').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
                $('#selectAll').prop('checked', checkedCheckboxes === totalCheckboxes);
            });

            // Status toggle
            $('.toggle-status').off('click').on('click', function() {
                const brandId = $(this).data('id');
                const currentStatus = $(this).data('status');
                const brandName = $(this).closest('tr').find('td:nth-child(3)').text();
                
                toggleBrandStatus(brandId, currentStatus, brandName);
            });

            // Delete single brand
            $('.delete-brand').off('click').on('click', function() {
                const brandId = $(this).data('id');
                const brandName = $(this).data('name');
                
                deleteSingleBrand(brandId, brandName);
            });
        }

        // Update bulk actions visibility
        function updateBulkActionVisibility() {
            const hasChecked = $('.brand-checkbox:checked').length > 0;
            if (hasChecked) {
                $('body').addClass('bulk-actions-visible');
            } else {
                $('body').removeClass('bulk-actions-visible');
            }
        }

        // Load statistics
        function loadStatistics() {
            $.ajax({
                url: 'brand-all.php?action=get_stats',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        animateCounter('#totalBrands', response.stats.total);
                        animateCounter('#activeBrands', response.stats.active);
                        animateCounter('#inactiveBrands', response.stats.inactive);
                        animateCounter('#featuredBrands', response.stats.featured);
                        animateCounter('#withProducts', response.stats.with_products);
                        animateCounter('#withoutProducts', response.stats.without_products);
                    }
                },
                error: function() {
                    console.error('Failed to load statistics');
                }
            });
        }

        // Animate counter
        function animateCounter(element, target) {
            const $element = $(element);
            const current = parseInt($element.text()) || 0;
            
            if (current !== target) {
                $element.removeClass('count-animation');
                setTimeout(() => {
                    $element.addClass('count-animation');
                }, 10);
            }
            
            $({ count: current }).animate({ count: target }, {
                duration: 800,
                easing: 'swing',
                step: function() {
                    $element.text(Math.floor(this.count));
                },
                complete: function() {
                    $element.text(target);
                }
            });
        }

        // Toggle brand status
        function toggleBrandStatus(brandId, currentStatus, brandName) {
            const newStatus = currentStatus ? 0 : 1;
            const action = newStatus ? 'activate' : 'deactivate';
            
            Swal.fire({
                title: `${action.charAt(0).toUpperCase() + action.slice(1)} Brand`,
                text: `Are you sure you want to ${action} "${brandName}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: newStatus ? '#28a745' : '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${action.charAt(0).toUpperCase() + action.slice(1)}`,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'brand-all.php?action=toggle_status',
                        type: 'POST',
                        data: { brand_id: brandId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                table.ajax.reload(null, false);
                                loadStatistics();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to update brand status. Please try again.'
                            });
                        }
                    });
                }
            });
        }

        // Delete single brand
        function deleteSingleBrand(brandId, brandName) {
            Swal.fire({
                title: 'Delete Brand',
                html: `Are you sure you want to delete "<strong>${brandName}</strong>"?<br><br><small class="text-danger">This action cannot be undone!</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performBulkDelete([brandId]);
                }
            });
        }

        // Perform bulk delete
        function performBulkDelete(brandIds) {
            Swal.fire({
                title: 'Deleting Brands...',
                text: 'Please wait while we delete the selected brands.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'brand-all.php?action=bulk_delete',
                type: 'POST',
                data: { brand_ids: brandIds },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                        
                        // Reset checkboxes and reload table
                        $('#selectAll').prop('checked', false).prop('indeterminate', false);
                        table.ajax.reload();
                        loadStatistics();
                        updateBulkActionVisibility();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Delete Failed',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to delete brands. Please try again.'
                    });
                }
            });
        }
    });
    </script>
</body>
</html>