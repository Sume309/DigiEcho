<?php
// Sub-Category Management with Professional Features
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';
require_once __DIR__ . '/../src/auth/admin.php';

use App\auth\Admin;

// Authentication check
if (!Admin::Check()) {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false, 
            'message' => 'Authentication required',
            'redirect' => 'auto-login.php'
        ]);
        exit;
    } else {
        header('Location: auto-login.php');
        exit;
    }
}

$db = new MysqliDb(
    settings()['hostname'], 
    settings()['user'], 
    settings()['password'], 
    settings()['database']
);

// Handle AJAX requests
if (isset($_GET['action'])) {
    if (ob_get_contents()) {
        ob_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        switch ($_GET['action']) {
            case 'get_subcategories':
                handleSubcategoryList($db);
                break;
            case 'toggle_status':
                handleToggleStatus($db);
                break;
            case 'bulk_delete':
                handleBulkDelete($db);
                break;
            case 'get_stats':
                handleGetStats($db);
                break;
            case 'export':
                handleExport($db);
                break;
            case 'get_categories':
                handleGetCategories($db);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// AJAX Handler Functions
function handleSubcategoryList($db) {
    try {
        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10);
        $search = $_GET['search']['value'] ?? '';
        $statusFilter = $_GET['status_filter'] ?? '';
        $categoryFilter = $_GET['category_filter'] ?? '';
        $dateFilter = $_GET['date_filter'] ?? '';
        $productsFilter = $_GET['products_filter'] ?? '';
        
        $whereConditions = [];
        $params = [];
        
        // Search filter
        if (!empty($search)) {
            $whereConditions[] = '(s.name LIKE ? OR s.slug LIKE ? OR s.description LIKE ? OR c.name LIKE ?)';
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
        }
        
        // Status filter
        if ($statusFilter !== '') {
            $whereConditions[] = 's.is_active = ?';
            $params[] = intval($statusFilter);
        }
        
        // Category filter
        if (!empty($categoryFilter)) {
            $whereConditions[] = 's.category_id = ?';
            $params[] = intval($categoryFilter);
        }
        
        // Date filter
        if (!empty($dateFilter)) {
            $today = date('Y-m-d');
            switch ($dateFilter) {
                case 'today':
                    $whereConditions[] = 'DATE(s.created_at) = ?';
                    $params[] = $today;
                    break;
                case 'week':
                    $weekStart = date('Y-m-d', strtotime('monday this week'));
                    $whereConditions[] = 's.created_at >= ?';
                    $params[] = $weekStart;
                    break;
                case 'month':
                    $monthStart = date('Y-m-01');
                    $whereConditions[] = 's.created_at >= ?';
                    $params[] = $monthStart;
                    break;
            }
        }
        
        // Products filter
        if (!empty($productsFilter)) {
            if ($productsFilter === 'with_products') {
                $whereConditions[] = 'p.product_count > 0';
            } elseif ($productsFilter === 'without_products') {
                $whereConditions[] = '(p.product_count IS NULL OR p.product_count = 0)';
            }
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count
        $totalCountQuery = "SELECT COUNT(*) as total FROM subcategories";
        $totalCountResult = $db->rawQuery($totalCountQuery);
        $totalRecords = $totalCountResult[0]['total'] ?? 0;
        
        // Get filtered count
        $filteredCountQuery = "SELECT COUNT(*) as total FROM subcategories s 
                              LEFT JOIN categories c ON s.category_id = c.id";
        if (!empty($whereClause)) {
            $filteredCountQuery .= " LEFT JOIN (SELECT subcategory_id, COUNT(*) as product_count FROM products GROUP BY subcategory_id) p ON s.id = p.subcategory_id $whereClause";
        }
        
        if (!empty($params)) {
            $filteredResult = $db->rawQuery($filteredCountQuery, $params);
        } else {
            $filteredResult = $db->rawQuery($filteredCountQuery);
        }
        $filteredRecords = $filteredResult[0]['total'] ?? 0;
        
        // Get subcategories data
        $query = "
            SELECT s.*, c.name as category_name, COALESCE(p.product_count, 0) as product_count
            FROM subcategories s
            LEFT JOIN categories c ON s.category_id = c.id
            LEFT JOIN (SELECT subcategory_id, COUNT(*) as product_count FROM products GROUP BY subcategory_id) p ON s.id = p.subcategory_id
            $whereClause
            ORDER BY s.sort_order ASC, s.name ASC
            LIMIT $start, $length
        ";
        
        if (!empty($params)) {
            $subcategories = $db->rawQuery($query, $params);
        } else {
            $subcategories = $db->rawQuery($query);
        }
        
        $data = [];
        if ($subcategories) {
            foreach ($subcategories as $subcategory) {
                $statusBadge = $subcategory['is_active'] ? 
                    '<span class="badge bg-success">Active</span>' : 
                    '<span class="badge bg-secondary">Inactive</span>';
                    
                $image = !empty($subcategory['image']) && file_exists(__DIR__ . "/../assets/subcategories/{$subcategory['image']}") ?
                    sprintf(
                        '<img src="%sassets/subcategories/%s" alt="%s" class="subcategory-image" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">',
                        settings()['root'],
                        htmlspecialchars($subcategory['image'], ENT_QUOTES),
                        htmlspecialchars($subcategory['name'], ENT_QUOTES)
                    ) :
                    '<div class="placeholder-image bg-light d-flex align-items-center justify-content-center" style="width:50px;height:50px;border-radius:4px;"><i class="fas fa-image text-muted"></i></div>';
                
                $actions = sprintf(
                    '<div class="btn-group btn-group-sm" role="group">'
                    . '<a href="subcategory-edit-enhanced.php?id=%d" class="btn btn-outline-primary" title="Edit">'
                    . '<i class="fas fa-edit"></i>'
                    . '</a>'
                    . '<button class="btn btn-outline-warning toggle-status" data-id="%d" data-status="%d" title="Toggle Status">'
                    . '<i class="fas fa-toggle-%s"></i>'
                    . '</button>'
                    . '<button class="btn btn-outline-danger delete-subcategory" data-id="%d" data-name="%s" title="Delete">'
                    . '<i class="fas fa-trash"></i>'
                    . '</button>'
                    . '</div>',
                    $subcategory['id'],
                    $subcategory['id'],
                    $subcategory['is_active'],
                    $subcategory['is_active'] ? 'on' : 'off',
                    $subcategory['id'],
                    htmlspecialchars($subcategory['name'], ENT_QUOTES)
                );
                
                $data[] = [
                    'checkbox' => sprintf('<input type="checkbox" class="subcategory-checkbox" value="%d" data-name="%s">', $subcategory['id'], htmlspecialchars($subcategory['name'], ENT_QUOTES)),
                    'image' => $image,
                    'name' => sprintf('<strong>%s</strong><br><small class="text-muted">%s</small>', htmlspecialchars($subcategory['name']), htmlspecialchars($subcategory['slug'])),
                    'category' => $subcategory['category_name'] ? sprintf('<span class="badge bg-info">%s</span>', htmlspecialchars($subcategory['category_name'])) : '<span class="text-muted">No Category</span>',
                    'description' => !empty($subcategory['description']) ? 
                        (strlen($subcategory['description']) > 50 ? htmlspecialchars(substr($subcategory['description'], 0, 50)) . '...' : htmlspecialchars($subcategory['description'])) : 
                        '<span class="text-muted">No description</span>',
                    'product_count' => sprintf('<span class="badge bg-primary">%d</span>', $subcategory['product_count']),
                    'status' => $statusBadge,
                    'sort_order' => $subcategory['sort_order'],
                    'updated_at' => date('M d, Y', strtotime($subcategory['updated_at'])),
                    'actions' => $actions
                ];
            }
        }
        
        $response = [
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            'draw' => intval($_GET['draw'] ?? 1),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function handleToggleStatus($db) {
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            
            if ($id > 0) {
                $db->where('id', $id);
                $currentSubcategory = $db->getOne('subcategories', ['name', 'is_active']);
                
                if (!$currentSubcategory) {
                    echo json_encode(['success' => false, 'message' => 'Subcategory not found']);
                    return;
                }
                
                $db->where('id', $id);
                $result = $db->update('subcategories', [
                    'is_active' => $status, 
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                if ($result) {
                    $db->where('id', $id);
                    $updatedSubcategory = $db->getOne('subcategories', ['is_active']);
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Status updated successfully',
                        'subcategory_id' => $id,
                        'new_status' => intval($updatedSubcategory['is_active']),
                        'old_status' => intval($currentSubcategory['is_active'])
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update status in database']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid subcategory ID']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method. POST required.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function handleBulkDelete($db) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ids = $_POST['ids'] ?? [];
        
        if (!empty($ids) && is_array($ids)) {
            $successCount = 0;
            $errors = [];
            
            foreach ($ids as $id) {
                $id = intval($id);
                
                // Check if subcategory has products
                $db->where('subcategory_id', $id);
                $productCount = $db->getValue('products', 'COUNT(*)');
                
                if ($productCount > 0) {
                    $db->where('id', $id);
                    $subcategory = $db->getOne('subcategories', ['name']);
                    $errors[] = "Subcategory '{$subcategory['name']}' has {$productCount} product(s)";
                    continue;
                }
                
                // Get subcategory image before deletion
                $db->where('id', $id);
                $subcategory = $db->getOne('subcategories', ['image']);
                
                // Delete subcategory
                $db->where('id', $id);
                if ($db->delete('subcategories')) {
                    $successCount++;
                    
                    // Delete associated image
                    if (!empty($subcategory['image'])) {
                        $imagePath = __DIR__ . "/../assets/subcategories/{$subcategory['image']}";
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                } else {
                    $errors[] = "Failed to delete subcategory ID: $id";
                }
            }
            
            $message = "$successCount subcategories deleted successfully.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }
            
            echo json_encode([
                'success' => $successCount > 0,
                'message' => $message,
                'deleted_count' => $successCount,
                'errors' => $errors
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No subcategories selected']);
        }
    }
}

function handleGetStats($db) {
    try {
        $totalSubcategories = $db->rawQueryOne('SELECT COUNT(*) as count FROM subcategories')['count'] ?? 0;
        $activeSubcategories = $db->rawQueryOne('SELECT COUNT(*) as count FROM subcategories WHERE is_active = 1')['count'] ?? 0;
        $inactiveSubcategories = $db->rawQueryOne('SELECT COUNT(*) as count FROM subcategories WHERE is_active = 0')['count'] ?? 0;
        $subcategoriesWithProducts = $db->rawQueryOne('SELECT COUNT(DISTINCT subcategory_id) as count FROM products WHERE subcategory_id IS NOT NULL')['count'] ?? 0;
        $subcategoriesWithoutProducts = $db->rawQueryOne('SELECT COUNT(*) as count FROM subcategories s LEFT JOIN products p ON s.id = p.subcategory_id WHERE p.subcategory_id IS NULL')['count'] ?? 0;
        $totalProducts = $db->rawQueryOne('SELECT COUNT(*) as count FROM products WHERE subcategory_id IS NOT NULL')['count'] ?? 0;
        $avgProductsPerSubcategory = $db->rawQueryOne('SELECT ROUND(AVG(product_count), 1) as avg FROM (SELECT COUNT(*) as product_count FROM products WHERE subcategory_id IS NOT NULL GROUP BY subcategory_id) as counts')['avg'] ?? 0;
        
        // Get category distribution
        $categoryDistribution = $db->rawQuery('
            SELECT c.name as category_name, COUNT(s.id) as subcategory_count 
            FROM categories c 
            LEFT JOIN subcategories s ON c.id = s.category_id 
            WHERE c.is_active = 1 
            GROUP BY c.id, c.name 
            ORDER BY subcategory_count DESC 
            LIMIT 5
        ');
        
        $stats = [
            'total_subcategories' => $totalSubcategories,
            'active_subcategories' => $activeSubcategories,
            'inactive_subcategories' => $inactiveSubcategories,
            'subcategories_with_products' => $subcategoriesWithProducts,
            'subcategories_without_products' => $subcategoriesWithoutProducts,
            'total_products' => $totalProducts,
            'avg_products_per_subcategory' => $avgProductsPerSubcategory,
            'category_distribution' => $categoryDistribution,
            'last_updated' => date('Y-m-d H:i:s'),
            'percentages' => []
        ];
        
        // Calculate percentages
        $total = $stats['total_subcategories'];
        if ($total > 0) {
            $stats['percentages'] = [
                'active' => round(($stats['active_subcategories'] / $total) * 100, 1),
                'inactive' => round(($stats['inactive_subcategories'] / $total) * 100, 1),
                'with_products' => round(($stats['subcategories_with_products'] / $total) * 100, 1),
                'without_products' => round(($stats['subcategories_without_products'] / $total) * 100, 1)
            ];
        } else {
            $stats['percentages'] = [
                'active' => 0,
                'inactive' => 0,
                'with_products' => 0,
                'without_products' => 0
            ];
        }
        
        $stats['debug'] = [
            'timestamp' => time(),
            'server_time' => date('Y-m-d H:i:s'),
            'query_method' => 'rawQuery_enhanced'
        ];
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $stats]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function handleGetCategories($db) {
    try {
        $categories = $db->rawQuery('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC');
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $categories]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function handleExport($db) {
    $format = $_GET['format'] ?? 'csv';
    $search = $_GET['search'] ?? '';
    $statusFilter = $_GET['status_filter'] ?? '';
    $categoryFilter = $_GET['category_filter'] ?? '';
    
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = '(s.name LIKE ? OR s.slug LIKE ? OR s.description LIKE ? OR c.name LIKE ?)';
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
    }
    
    if ($statusFilter !== '') {
        $whereConditions[] = 's.is_active = ?';
        $params[] = intval($statusFilter);
    }
    
    if (!empty($categoryFilter)) {
        $whereConditions[] = 's.category_id = ?';
        $params[] = intval($categoryFilter);
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $query = "
        SELECT s.id, s.name, s.slug, s.description, s.is_active, s.sort_order, 
               s.created_at, s.updated_at, c.name as category_name, COALESCE(p.product_count, 0) as product_count
        FROM subcategories s
        LEFT JOIN categories c ON s.category_id = c.id
        LEFT JOIN (SELECT subcategory_id, COUNT(*) as product_count FROM products GROUP BY subcategory_id) p ON s.id = p.subcategory_id
        $whereClause
        ORDER BY s.sort_order ASC, s.name ASC
    ";
    
    if (!empty($params)) {
        $subcategories = $db->rawQuery($query, $params);
    } else {
        $subcategories = $db->rawQuery($query);
    }
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="subcategories_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['ID', 'Name', 'Slug', 'Category', 'Description', 'Status', 'Sort Order', 'Product Count', 'Created At', 'Updated At']);
        
        foreach ($subcategories as $subcategory) {
            fputcsv($output, [
                $subcategory['id'],
                $subcategory['name'],
                $subcategory['slug'],
                $subcategory['category_name'] ?? 'No Category',
                $subcategory['description'],
                $subcategory['is_active'] ? 'Active' : 'Inactive',
                $subcategory['sort_order'],
                $subcategory['product_count'],
                $subcategory['created_at'],
                $subcategory['updated_at']
            ]);
        }
        
        fclose($output);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $subcategories,
            'message' => 'Excel export functionality requires additional library. Please use CSV export.'
        ]);
    }
}

// Get statistics for page display
$totalSubcategories = $db->getValue('subcategories', 'COUNT(*)');
$activeSubcategories = $db->getValue('subcategories', 'COUNT(*)', 'is_active = 1');
$inactiveSubcategories = $db->getValue('subcategories', 'COUNT(*)', 'is_active = 0');
$subcategoriesWithProductsResult = $db->rawQuery('SELECT COUNT(DISTINCT subcategory_id) as count FROM products WHERE subcategory_id IS NOT NULL');
$subcategoriesWithProducts = $subcategoriesWithProductsResult[0]['count'] ?? 0;
$totalProducts = $db->getValue('products', 'COUNT(*)', 'subcategory_id IS NOT NULL');
?>

<?php require __DIR__ . '/components/header.php'; ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .subcategory-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }
    .placeholder-image {
        width: 50px;
        height: 50px;
        background: #f8f9fa;
        border-radius: 4px;
    }
    .stats-card {
        transition: all 0.3s ease;
        border: none;
        border-radius: 12px;
    }
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
    }
    .stats-card .h3 {
        font-size: 2.2rem;
        font-weight: 700;
        line-height: 1;
    }
    .stats-card .fa-3x {
        font-size: 2.8rem;
    }
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    .stats-card.updating .h3 {
        animation: pulse 0.5s ease-in-out;
    }
    .fa-spin {
        animation: fa-spin 1s infinite linear;
    }
    @keyframes fa-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 2px solid #dee2e6;
    }
    @media (max-width: 768px) {
        .card-header .d-flex {
            flex-direction: column;
            gap: 1rem;
        }
        .form-select,
        .input-group {
            min-width: 100% !important;
        }
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
                    <h1 class="mt-4">Sub-Category Management</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Sub-Categories</li>
                    </ol>

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
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <!-- Enhanced Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-12 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Sub-Category Statistics</h4>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm refresh-stats" title="Refresh Statistics">
                                        <i class="fas fa-sync-alt"></i> Refresh Stats
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="forceUpdateStats()" title="Force Update from Database">
                                        <i class="fas fa-database"></i> Force Update
                                    </button>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="autoRefreshStats" checked>
                                        <label class="form-check-label" for="autoRefreshStats" title="Auto-refresh every 30 seconds">
                                            Auto-refresh
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <hr class="mt-2 mb-3">
                        </div>
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card bg-primary text-white stats-card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-uppercase opacity-75">Total Sub-Categories</div>
                                            <div class="h3 mb-0 fw-bold" id="totalSubcategories"><?php echo $totalSubcategories; ?></div>
                                            <div class="small mt-1">
                                                <i class="fas fa-sync-alt fa-spin d-none" id="totalSubcategoriesLoader"></i>
                                                <span class="text-success" id="totalSubcategoriesChange"></span>
                                            </div>
                                        </div>
                                        <div class="fa-3x opacity-25">
                                            <i class="fas fa-sitemap"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <div class="d-flex justify-content-between">
                                        <small class="opacity-75">All sub-categories</small>
                                        <small><i class="fas fa-arrow-right"></i></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card bg-success text-white stats-card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-uppercase opacity-75">Active Sub-Categories</div>
                                            <div class="h3 mb-0 fw-bold" id="activeSubcategories"><?php echo $activeSubcategories; ?></div>
                                            <div class="small mt-1">
                                                <i class="fas fa-sync-alt fa-spin d-none" id="activeSubcategoriesLoader"></i>
                                                <span class="text-light" id="activeSubcategoriesChange"></span>
                                            </div>
                                        </div>
                                        <div class="fa-3x opacity-25">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <div class="d-flex justify-content-between">
                                        <small class="opacity-75">Published & visible</small>
                                        <small class="opacity-75"><?php echo $totalSubcategories > 0 ? round(($activeSubcategories / $totalSubcategories) * 100, 1) : 0; ?>%</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card bg-secondary text-white stats-card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-uppercase opacity-75">Inactive Sub-Categories</div>
                                            <div class="h3 mb-0 fw-bold" id="inactiveSubcategories"><?php echo $inactiveSubcategories; ?></div>
                                            <div class="small mt-1">
                                                <i class="fas fa-sync-alt fa-spin d-none" id="inactiveSubcategoriesLoader"></i>
                                                <span class="text-light" id="inactiveSubcategoriesChange"></span>
                                            </div>
                                        </div>
                                        <div class="fa-3x opacity-25">
                                            <i class="fas fa-times-circle"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <div class="d-flex justify-content-between">
                                        <small class="opacity-75">Hidden & disabled</small>
                                        <small class="opacity-75"><?php echo $totalSubcategories > 0 ? round(($inactiveSubcategories / $totalSubcategories) * 100, 1) : 0; ?>%</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card bg-warning text-white stats-card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-uppercase opacity-75">With Products</div>
                                            <div class="h3 mb-0 fw-bold" id="subcategoriesWithProducts"><?php echo $subcategoriesWithProducts; ?></div>
                                            <div class="small mt-1">
                                                <i class="fas fa-sync-alt fa-spin d-none" id="subcategoriesWithProductsLoader"></i>
                                                <span class="text-light" id="subcategoriesWithProductsChange"></span>
                                            </div>
                                        </div>
                                        <div class="fa-3x opacity-25">
                                            <i class="fas fa-box"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <div class="d-flex justify-content-between">
                                        <small class="opacity-75">Have products assigned</small>
                                        <small class="opacity-75"><?php echo $totalSubcategories > 0 ? round(($subcategoriesWithProducts / $totalSubcategories) * 100, 1) : 0; ?>%</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Sub-Category Management Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-sitemap me-2"></i>Sub-Category Management
                                    </h6>
                                    <span class="badge bg-info ms-2" id="table-info">Loading...</span>
                                </div>
                                
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <!-- Quick Filters -->
                                    <div class="d-flex gap-2 align-items-center">
                                        <select class="form-select form-select-sm" id="statusFilter" style="min-width: 120px;">
                                            <option value="">All Status</option>
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                        
                                        <select class="form-select form-select-sm" id="categoryFilter" style="min-width: 150px;">
                                            <option value="">All Categories</option>
                                        </select>
                                        
                                        <select class="form-select form-select-sm" id="dateFilter" style="min-width: 120px;">
                                            <option value="">All Time</option>
                                            <option value="today">Today</option>
                                            <option value="week">This Week</option>
                                            <option value="month">This Month</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Search -->
                                    <div class="input-group" style="min-width: 250px; max-width: 300px;">
                                        <input type="text" class="form-control form-control-sm" id="searchInput" 
                                               placeholder="Search sub-categories...">
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="btn-group" role="group">
                                        <a href="subcategory-add-enhanced.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus"></i> Add Sub-Category
                                        </a>
                                        <button type="button" class="btn btn-success btn-sm" id="refreshTable">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                        <button type="button" class="btn btn-info btn-sm" id="exportCsv">
                                            <i class="fas fa-download"></i> Export CSV
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Bulk Actions -->
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                            <label class="form-check-label" for="selectAll">Select All</label>
                                        </div>
                                        <div class="btn-group" id="bulkActions" style="display: none;">
                                            <button type="button" class="btn btn-outline-danger btn-sm" id="bulkDelete">
                                                <i class="fas fa-trash"></i> Delete Selected
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-sm" id="bulkActivate">
                                                <i class="fas fa-check"></i> Activate Selected
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="bulkDeactivate">
                                                <i class="fas fa-times"></i> Deactivate Selected
                                            </button>
                                        </div>
                                        <span class="badge bg-secondary" id="selectedCount" style="display: none;">0 selected</span>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <small class="text-muted">
                                        Show 
                                        <select class="form-select form-select-sm d-inline-block" id="pageLength" style="width: auto;">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                            <option value="-1">All</option>
                                        </select>
                                        entries
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="subcategoriesTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40px;"><input type="checkbox" id="selectAllTable" class="form-check-input"></th>
                                            <th style="width: 80px;">Image</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Description</th>
                                            <th style="width: 100px;">Products</th>
                                            <th style="width: 100px;">Status</th>
                                            <th style="width: 80px;">Sort</th>
                                            <th style="width: 120px;">Updated</th>
                                            <th style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
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
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="assets/js/scripts.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        let table = $('#subcategoriesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: window.location.href,
                type: 'GET',
                data: function(d) {
                    d.action = 'get_subcategories';
                    d.status_filter = $('#statusFilter').val();
                    d.category_filter = $('#categoryFilter').val();
                    d.date_filter = $('#dateFilter').val();
                    d.products_filter = $('#productsFilter').val();
                    return d;
                },
                error: function(xhr, error, code) {
                    console.error('DataTables AJAX Error:', error, code);
                    Swal.fire({
                        icon: 'error',
                        title: 'Loading Error',
                        text: 'Failed to load sub-categories data. Please refresh the page.',
                        confirmButtonText: 'Refresh Page',
                        confirmButtonColor: '#007bff'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.reload();
                        }
                    });
                }
            },
            columns: [
                { data: 'checkbox', orderable: false, searchable: false },
                { data: 'image', orderable: false, searchable: false },
                { data: 'name', orderable: true },
                { data: 'category', orderable: true },
                { data: 'description', orderable: false },
                { data: 'product_count', orderable: true },
                { data: 'status', orderable: true },
                { data: 'sort_order', orderable: true },
                { data: 'updated_at', orderable: true },
                { data: 'actions', orderable: false, searchable: false }
            ],
            order: [[7, 'asc'], [2, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                processing: '<i class="fas fa-spinner fa-spin"></i> Loading sub-categories...',
                emptyTable: 'No sub-categories found',
                zeroRecords: 'No matching sub-categories found',
                info: 'Showing _START_ to _END_ of _TOTAL_ sub-categories',
                infoEmpty: 'Showing 0 to 0 of 0 sub-categories',
                infoFiltered: '(filtered from _MAX_ total sub-categories)'
            },
            responsive: true,
            stateSave: true,
            drawCallback: function(settings) {
                const info = table.page.info();
                $('#table-info').text(`${info.recordsDisplay} sub-categories`);
                $('[title]').tooltip();
                updateBulkActionVisibility();
            }
        });

        // Load categories for filter
        loadCategories();
        loadStats();
        setupStatsAutoRefresh();
        setupEventHandlers(table);
        $('[title]').tooltip();
    });

    function loadCategories() {
        $.ajax({
            url: window.location.href,
            type: 'GET',
            data: { action: 'get_categories' },
            success: function(response) {
                if (response.success && response.data) {
                    let options = '<option value="">All Categories</option>';
                    response.data.forEach(category => {
                        options += `<option value="${category.id}">${category.name}</option>`;
                    });
                    $('#categoryFilter').html(options);
                }
            }
        });
    }

    function setupEventHandlers(table) {
        // Filter change handlers
        $('#statusFilter, #categoryFilter, #dateFilter').on('change', function() {
            table.ajax.reload(null, false);
        });
        
        // Search with debounce
        let searchTimeout;
        $('#searchInput').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                table.search(this.value).draw();
            }, 500);
        });
        
        $('#clearSearch').on('click', function() {
            $('#searchInput').val('');
            table.search('').draw();
        });
        
        $('#pageLength').on('change', function() {
            table.page.len($(this).val()).draw();
        });
        
        $('#refreshTable').on('click', function() {
            const btn = $(this);
            const icon = btn.find('i');
            icon.addClass('fa-spin');
            
            table.ajax.reload(function() {
                icon.removeClass('fa-spin');
                loadStats();
                Swal.fire({
                    icon: 'success',
                    title: 'Refreshed',
                    text: 'Sub-categories data refreshed',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        });
        
        // Select all functionality
        $('#selectAll, #selectAllTable').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('.subcategory-checkbox').prop('checked', isChecked);
            $('#selectAll, #selectAllTable').prop('checked', isChecked);
            updateBulkActionVisibility();
        });
        
        $(document).on('change', '.subcategory-checkbox', function() {
            updateBulkActionVisibility();
            const totalCheckboxes = $('.subcategory-checkbox').length;
            const checkedCheckboxes = $('.subcategory-checkbox:checked').length;
            $('#selectAll, #selectAllTable').prop('checked', totalCheckboxes === checkedCheckboxes);
        });
        
        // Delete and status toggle
        bindDeleteEvents();
        bindStatusToggleEvents();
        setupBulkActions(table);
        setupExportHandlers();
    }

    function bindDeleteEvents() {
        $(document).off('click', '.delete-subcategory');
        $(document).on('click', '.delete-subcategory', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            Swal.fire({
                title: 'Delete Sub-Category',
                html: `Delete sub-category <strong>"${name}"</strong>?<br><small class="text-danger">This cannot be undone!</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteSubcategory(id, name);
                }
            });
        });
    }

    function deleteSubcategory(id, name) {
        Swal.fire({
            title: 'Deleting...',
            text: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: { action: 'bulk_delete', ids: [id] },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: `Sub-category "${name}" deleted`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#subcategoriesTable').DataTable().ajax.reload(null, false);
                    loadStats();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete Failed',
                        text: response.message || 'Failed to delete'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred'
                });
            }
        });
    }

    function bindStatusToggleEvents() {
        $(document).off('click', '.toggle-status');
        $(document).on('click', '.toggle-status', function(e) {
            e.preventDefault();
            const btn = $(this);
            const id = btn.data('id');
            const currentStatus = btn.data('status');
            const newStatus = currentStatus ? 0 : 1;
            
            const icon = btn.find('i');
            icon.removeClass('fa-toggle-on fa-toggle-off').addClass('fa-spinner fa-spin');
            btn.prop('disabled', true);
            
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: { action: 'toggle_status', id: id, status: newStatus },
                success: function(response) {
                    if (response.success) {
                        btn.data('status', response.new_status);
                        icon.removeClass('fa-spinner fa-spin');
                        icon.addClass(response.new_status ? 'fa-toggle-on' : 'fa-toggle-off');
                        
                        $('#subcategoriesTable').DataTable().ajax.reload(null, false);
                        loadStats();
                        
                        const statusText = response.new_status ? 'activated' : 'deactivated';
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Updated',
                            text: `Sub-category ${statusText}`,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        icon.removeClass('fa-spinner fa-spin');
                        icon.addClass(currentStatus ? 'fa-toggle-on' : 'fa-toggle-off');
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: response.message || 'Failed to update'
                        });
                    }
                },
                error: function() {
                    icon.removeClass('fa-spinner fa-spin');
                    icon.addClass(currentStatus ? 'fa-toggle-on' : 'fa-toggle-off');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred'
                    });
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        });
    }

    function setupBulkActions(table) {
        $('#bulkDelete').on('click', function() {
            const selectedIds = getSelectedIds();
            if (selectedIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select sub-categories to delete'
                });
                return;
            }
            
            Swal.fire({
                title: 'Bulk Delete',
                html: `Delete <strong>${selectedIds.length}</strong> selected sub-categories?<br><small class="text-danger">This cannot be undone!</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Yes, Delete All'
            }).then((result) => {
                if (result.isConfirmed) {
                    performBulkDelete(selectedIds, table);
                }
            });
        });
        
        $('#bulkActivate').on('click', function() {
            const selectedIds = getSelectedIds();
            if (selectedIds.length === 0) {
                Swal.fire({ icon: 'warning', title: 'No Selection', text: 'Please select sub-categories to activate' });
                return;
            }
            performBulkStatusChange(selectedIds, 1, 'activate', table);
        });
        
        $('#bulkDeactivate').on('click', function() {
            const selectedIds = getSelectedIds();
            if (selectedIds.length === 0) {
                Swal.fire({ icon: 'warning', title: 'No Selection', text: 'Please select sub-categories to deactivate' });
                return;
            }
            performBulkStatusChange(selectedIds, 0, 'deactivate', table);
        });
    }

    function getSelectedIds() {
        const selectedIds = [];
        $('.subcategory-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        return selectedIds;
    }

    function performBulkDelete(ids, table) {
        Swal.fire({
            title: 'Deleting...',
            text: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: { action: 'bulk_delete', ids: ids },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Bulk Delete Completed',
                        html: `<strong>${response.deleted_count}</strong> sub-categories deleted.` +
                              (response.errors && response.errors.length > 0 ? `<br><small class="text-warning">Warnings: ${response.errors.join(', ')}</small>` : ''),
                        timer: 3000
                    });
                    
                    $('.subcategory-checkbox').prop('checked', false);
                    $('#selectAll, #selectAllTable').prop('checked', false);
                    updateBulkActionVisibility();
                    table.ajax.reload(null, false);
                    loadStats();
                } else {
                    Swal.fire({ icon: 'error', title: 'Bulk Delete Failed', text: response.message });
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred' });
            }
        });
    }

    function performBulkStatusChange(ids, status, action, table) {
        Swal.fire({
            title: `${action === 'activate' ? 'Activating' : 'Deactivating'}...`,
            text: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        let completed = 0, success = 0, errors = [];
        
        ids.forEach(id => {
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: { action: 'toggle_status', id: id, status: status },
                success: function(response) {
                    completed++;
                    if (response.success) success++;
                    else errors.push(`ID ${id}: ${response.message}`);
                    
                    if (completed === ids.length) {
                        finalizeBulkStatusChange(success, errors, action, table);
                    }
                },
                error: function() {
                    completed++;
                    errors.push(`ID ${id}: Server error`);
                    if (completed === ids.length) {
                        finalizeBulkStatusChange(success, errors, action, table);
                    }
                }
            });
        });
    }

    function finalizeBulkStatusChange(successCount, errors, action, table) {
        if (successCount > 0) {
            Swal.fire({
                icon: 'success',
                title: `Bulk ${action} Completed`,
                html: `<strong>${successCount}</strong> sub-categories ${action}d.` +
                      (errors.length > 0 ? `<br><small class="text-warning">Errors: ${errors.join(', ')}</small>` : ''),
                timer: 3000
            });
            
            $('.subcategory-checkbox').prop('checked', false);
            $('#selectAll, #selectAllTable').prop('checked', false);
            updateBulkActionVisibility();
            table.ajax.reload(null, false);
            loadStats();
        } else {
            Swal.fire({ icon: 'error', title: `Bulk ${action} Failed`, text: errors.join(', ') });
        }
    }

    function updateBulkActionVisibility() {
        const selectedCount = $('.subcategory-checkbox:checked').length;
        if (selectedCount > 0) {
            $('#bulkActions').show();
            $('#selectedCount').show().text(`${selectedCount} selected`);
        } else {
            $('#bulkActions').hide();
            $('#selectedCount').hide();
        }
    }

    function setupExportHandlers() {
        $('#exportCsv').on('click', function(e) {
            e.preventDefault();
            const filters = {
                search: $('#searchInput').val(),
                status_filter: $('#statusFilter').val(),
                category_filter: $('#categoryFilter').val(),
                date_filter: $('#dateFilter').val(),
                format: 'csv'
            };
            
            const params = new URLSearchParams({ action: 'export', ...filters });
            window.location.href = `${window.location.href}?${params.toString()}`;
        });
    }

    function loadStats() {
        $.ajax({
            url: window.location.href,
            type: 'GET',
            data: { action: 'get_stats' },
            success: function(response) {
                if (response.success && response.data) {
                    updateStatsDisplay(response.data);
                }
            }
        });
    }

    function updateStatsDisplay(stats) {
        animateStatUpdate('totalSubcategories', stats.total_subcategories);
        animateStatUpdate('activeSubcategories', stats.active_subcategories);
        animateStatUpdate('inactiveSubcategories', stats.inactive_subcategories);
        animateStatUpdate('subcategoriesWithProducts', stats.subcategories_with_products);
    }

    function animateStatUpdate(elementId, newValue) {
        const element = $(`#${elementId}`);
        const loader = $(`#${elementId}Loader`);
        const currentValue = parseInt(element.text()) || 0;
        
        if (currentValue !== newValue) {
            loader.removeClass('d-none');
            element.parent().addClass('updating');
            
            $({ value: currentValue }).animate({ value: newValue }, {
                duration: 800,
                step: function() { element.text(Math.ceil(this.value)); },
                complete: function() {
                    element.text(newValue);
                    loader.addClass('d-none');
                    element.parent().removeClass('updating');
                }
            });
        }
    }

    function setupStatsAutoRefresh() {
        let autoRefreshInterval;
        
        function startAutoRefresh() {
            autoRefreshInterval = setInterval(() => {
                if ($('#autoRefreshStats').prop('checked')) {
                    loadStats();
                }
            }, 30000);
        }
        
        $('#autoRefreshStats').on('change', function() {
            if ($(this).prop('checked')) {
                startAutoRefresh();
            } else {
                clearInterval(autoRefreshInterval);
            }
        });
        
        if ($('#autoRefreshStats').prop('checked')) {
            startAutoRefresh();
        }
        
        $('.refresh-stats').on('click', function() {
            const btn = $(this);
            const icon = btn.find('i');
            icon.addClass('fa-spin');
            loadStats();
            setTimeout(() => icon.removeClass('fa-spin'), 1000);
        });
    }

    function forceUpdateStats() {
        Swal.fire({
            title: 'Force Update Statistics',
            text: 'This will refresh all statistics from the database',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Update Now'
        }).then((result) => {
            if (result.isConfirmed) {
                loadStats();
                Swal.fire({
                    icon: 'success',
                    title: 'Statistics Updated',
                    text: 'All statistics refreshed from database',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    }
    </script>
</body>
</html>