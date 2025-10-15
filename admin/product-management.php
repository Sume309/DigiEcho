<?php
// Prevent any output before JSON response
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';
require_once __DIR__ . '/../src/auth/admin.php';

use App\auth\Admin;

// Check authentication
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
            case 'get_products':
                handleGetProducts($db);
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
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle product list for DataTables
function handleGetProducts($db) {
    // Clear any output buffer to ensure clean JSON response
    if (ob_get_length()) {
        ob_clean();
    }
    
    try {
        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10);
        $search = $_GET['search']['value'] ?? '';
        $statusFilter = $_GET['status_filter'] ?? '';
        $categoryFilter = $_GET['category_filter'] ?? '';
        $brandFilter = $_GET['brand_filter'] ?? '';
        $stockFilter = $_GET['stock_filter'] ?? '';
        // Featured filter removed as per requirements
        
        $whereConditions = [];
        $params = [];
        
        // Search filter
        if (!empty($search)) {
            $searchTerms = explode(' ', $search);
            $searchConditions = [];
            foreach ($searchTerms as $term) {
                $term = trim($term);
                if (!empty($term)) {
                    $searchConditions[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ? OR p.slug LIKE ?)";
                    $params = array_merge($params, ["%$term%", "%$term%", "%$term%", "%$term%"]);
                }
            }
            if (!empty($searchConditions)) {
                $whereConditions[] = '(' . implode(' AND ', $searchConditions) . ')';
            }
        }
        
        // Status filter - only apply if not empty
        if (!empty($statusFilter)) {
            $whereConditions[] = 'p.status = ?';
            $params[] = $statusFilter;
        }
        
        // Category filter
        if (!empty($categoryFilter)) {
            $whereConditions[] = 'p.category_id = ?';
            $params[] = intval($categoryFilter);
        }
        
        // Brand filter
        if (!empty($brandFilter)) {
            $whereConditions[] = 'p.brand = ?';
            $params[] = intval($brandFilter);
        }
        
        // Stock filter
        if (!empty($stockFilter)) {
            switch ($stockFilter) {
                case 'in_stock':
                    $whereConditions[] = 'p.stock_quantity > 0 AND p.status = "active"';
                    break;
                case 'low_stock':
                    $whereConditions[] = 'p.stock_quantity <= p.min_stock_level AND p.stock_quantity > 0';
                    break;
                case 'out_of_stock':
                    $whereConditions[] = '(p.stock_quantity = 0 OR p.status = "out_of_stock")';
                    break;
            }
        }
        
        // No featured filter needed - removing as per requirements
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count (unfiltered)
        $totalCountQuery = "SELECT COUNT(*) as total FROM products p";
        $totalCountResult = $db->rawQuery($totalCountQuery);
        $totalRecords = $totalCountResult[0]['total'] ?? 0;
        
        // Get filtered count
        $filteredCountQuery = "SELECT COUNT(*) as total FROM products p $whereClause";
        if (!empty($params)) {
            $filteredResult = $db->rawQuery($filteredCountQuery, $params);
        } else {
            $filteredResult = $db->rawQuery($filteredCountQuery);
        }
        $filteredRecords = $filteredResult[0]['total'] ?? 0;
        
        // Get products data
        $query = "
            SELECT p.*, c.name as category_name, sc.name as subcategory_name, b.name as brand_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
            LEFT JOIN brands b ON p.brand = b.id
            $whereClause
            ORDER BY p.created_at DESC
            LIMIT $start, $length
        ";
        
        if (!empty($params)) {
            $products = $db->rawQuery($query, $params);
        } else {
            $products = $db->rawQuery($query);
        }
        
        $data = [];
        if ($products) {
            foreach ($products as $product) {
                // Status badge
                $statusBadge = '';
                switch ($product['status']) {
                    case 'active':
                        $statusBadge = '<span class="badge bg-success">Active</span>';
                        break;
                    case 'inactive':
                        $statusBadge = '<span class="badge bg-secondary">Inactive</span>';
                        break;
                    case 'draft':
                        $statusBadge = '<span class="badge bg-warning">Draft</span>';
                        break;
                    case 'out_of_stock':
                        $statusBadge = '<span class="badge bg-danger">Out of Stock</span>';
                        break;
                    default:
                        $statusBadge = '<span class="badge bg-light">Unknown</span>';
                }
                
                // Hot item badge
                $hotBadge = $product['is_hot_item'] ? 
                    '<span class="badge bg-danger"><i class="fas fa-fire"></i></span>' : 
                    '<span class="badge bg-light">-</span>';
                
                // Image
                $image = !empty($product['image']) ? 
                    "<img src='../assets/products/{$product['image']}' alt='{$product['name']}' class='product-image rounded' width='50' height='40'>" : 
                    "<div class='placeholder-image rounded d-flex align-items-center justify-content-center' style='width:50px;height:40px;background:#f8f9fa;'><i class='fas fa-image text-muted'></i></div>";
                
                // Discount info
                $discountInfo = '';
                if (!empty($product['discount_price']) && $product['discount_price'] > 0) {
                    $discountInfo = "<div class='text-danger'><s>৳{$product['selling_price']}</s> ৳{$product['discount_price']}</div>";
                } else {
                    $discountInfo = "৳{$product['selling_price']}";
                }
                
                // Stock status
                $stockStatus = '';
                if ($product['stock_quantity'] == 0) {
                    $stockStatus = '<span class="badge bg-danger">Out of Stock</span>';
                } elseif ($product['stock_quantity'] <= $product['min_stock_level']) {
                    $stockStatus = '<span class="badge bg-warning">Low Stock</span>';
                } else {
                    $stockStatus = '<span class="badge bg-success">In Stock</span>';
                }
                
                $data[] = [
                    'checkbox' => '<input type="checkbox" class="product-checkbox form-check-input" value="' . $product['id'] . '">',
                    'image' => $image,
                    'name' => '<strong>' . htmlspecialchars($product['name']) . '</strong><br><small class="text-muted">' . htmlspecialchars($product['sku']) . '</small>',
                    'category' => $product['category_name'] ?? 'No Category',
                    'brand' => $product['brand_name'] ?? 'No Brand',
                    'price' => $discountInfo,
                    'stock' => $product['stock_quantity'],
                    'stock_status' => $stockStatus,
                    'tags' => $hotBadge,
                    'status' => $statusBadge,
                    'created_at' => date('M j, Y', strtotime($product['created_at'])),
                    'actions' => '<div class="btn-group btn-group-sm" role="group">
                        <a href="product-edit-enhanced.php?id=' . $product['id'] . '" class="btn btn-outline-primary" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-outline-danger delete-product" data-id="' . $product['id'] . '" data-name="' . htmlspecialchars($product['name']) . '" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                        <a href="product-view.php?id=' . $product['id'] . '" class="btn btn-outline-info" title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>'
                ];
            }
        }
        
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => (int)$totalRecords,
            'recordsFiltered' => (int)$filteredRecords,
            'data' => $data,
            'error' => null
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        echo json_encode([
            'draw' => intval($_GET['draw'] ?? 1),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Error fetching products: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

// Handle toggle status
function handleToggleStatus($db) {
    try {
        $productId = intval($_POST['product_id'] ?? 0);
        
        if (!$productId) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            return;
        }
        
        // Get current product
        $product = $db->where('id', $productId)->getOne('products', 'id, status');
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }
        
        // Toggle status
        $newStatus = '';
        switch ($product['status']) {
            case 'active':
                $newStatus = 'inactive';
                break;
            case 'inactive':
                $newStatus = 'active';
                break;
            case 'draft':
                $newStatus = 'active';
                break;
            case 'out_of_stock':
                $newStatus = 'active';
                break;
            default:
                $newStatus = 'inactive';
        }
        
        $db->where('id', $productId);
        if ($db->update('products', ['status' => $newStatus])) {
            echo json_encode([
                'success' => true, 
                'message' => 'Product status updated successfully',
                'new_status' => $newStatus
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update product status']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating product status: ' . $e->getMessage()]);
    }
}

// Handle bulk delete
function handleBulkDelete($db) {
    try {
        $productIds = $_POST['product_ids'] ?? [];
        
        if (empty($productIds) || !is_array($productIds)) {
            echo json_encode(['success' => false, 'message' => 'No products selected']);
            return;
        }
        
        // Convert to integers for security
        $ids = array_map('intval', $productIds);
        
        // Delete products
        $db->where('id', $ids, 'IN');
        if ($db->delete('products')) {
            echo json_encode([
                'success' => true, 
                'message' => count($ids) . ' product(s) deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete products']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting products: ' . $e->getMessage()]);
    }
}

// Handle get statistics
function handleGetStats($db) {
    try {
        // Get all product statistics using rawQuery for better reliability
        $totalResult = $db->rawQuery('SELECT COUNT(*) as count FROM products');
        $totalProducts = intval($totalResult[0]['count'] ?? 0);
        
        $activeResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE status = "active"');
        $activeProducts = intval($activeResult[0]['count'] ?? 0);
        
        $inactiveResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE status = "inactive"');
        $inactiveProducts = intval($inactiveResult[0]['count'] ?? 0);
        
        $draftResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE status = "draft"');
        $draftProducts = intval($draftResult[0]['count'] ?? 0);
        
        $outOfStockResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE status = "out_of_stock" OR stock_quantity = 0');
        $outOfStockProducts = intval($outOfStockResult[0]['count'] ?? 0);
        
        $lowStockResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE stock_quantity <= min_stock_level AND stock_quantity > 0');
        $lowStockProducts = intval($lowStockResult[0]['count'] ?? 0);
        
        $hotItemsResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE is_hot_item = 1');
        $hotItems = intval($hotItemsResult[0]['count'] ?? 0);
        
        $featuredResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE is_featured = 1');
        $featuredProducts = intval($featuredResult[0]['count'] ?? 0);
        
        $discountedResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE discount_price IS NOT NULL AND discount_price > 0');
        $discountedProducts = intval($discountedResult[0]['count'] ?? 0);
        
        // Calculate additional inactive count (combining inactive and draft)
        $totalInactive = $inactiveProducts + $draftProducts;
        
        $stats = [
            'total' => $totalProducts,
            'active' => $activeProducts,
            'inactive' => $totalInactive,
            'out_of_stock' => $outOfStockProducts,
            'low_stock' => $lowStockProducts,
            'hot_items' => $hotItems,
            'featured' => $featuredProducts,
            'discounted' => $discountedProducts,
            'draft' => $draftProducts,
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        
    } catch (Exception $e) {
        error_log('Product Stats Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching statistics: ' . $e->getMessage()]);
    }
}

// Handle export
function handleExport($db) {
    try {
        $format = $_GET['format'] ?? 'csv';
        
        // Get all products
        $db->join('categories c', 'products.category_id = c.id', 'LEFT');
        $db->join('subcategories sc', 'products.subcategory_id = sc.id', 'LEFT');
        $db->join('brands b', 'products.brand = b.id', 'LEFT');
        $db->orderBy('products.id', 'DESC');
        $products = $db->get('products', null, 
            'products.*, c.name as category_name, sc.name as subcategory_name, b.name as brand_name');
        
        if ($format === 'csv') {
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="products_' . date('Y-m-d') . '.csv"');
            
            // Output CSV
            $output = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($output, [
                'ID', 'Name', 'SKU', 'Category', 'Subcategory', 'Brand', 'Price', 'Discount Price', 
                'Stock Quantity', 'Min Stock Level', 'Status', 'Is Hot Item', 
                'Sales Count', 'Views', 'Created At'
            ]);
            
            // CSV data
            foreach ($products as $product) {
                fputcsv($output, [
                    $product['id'],
                    $product['name'],
                    $product['sku'],
                    $product['category_name'] ?? '',
                    $product['subcategory_name'] ?? '',
                    $product['brand_name'] ?? '',
                    $product['selling_price'],
                    $product['discount_price'] ?? '',
                    $product['stock_quantity'],
                    $product['min_stock_level'],
                    $product['status'],
                    $product['is_hot_item'] ? 'Yes' : 'No',
                    $product['sales_count'],
                    $product['views'],
                    $product['created_at']
                ]);
            }
            
            fclose($output);
        }
        
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Export failed: ' . $e->getMessage();
    }
}

// Get data for filters
$categories = $db->get('categories', null, ['id', 'name']);
$brands = $db->get('brands', null, ['id', 'name']);

// Get initial statistics for server-side rendering
$initialStats = [];
try {
    // Use rawQuery for more reliable results
    $totalResult = $db->rawQuery('SELECT COUNT(*) as count FROM products');
    $activeResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE status = "active"');
    $inactiveResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE status = "inactive" OR status = "draft"');
    $outOfStockResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE status = "out_of_stock" OR stock_quantity = 0');
    $lowStockResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE stock_quantity <= min_stock_level AND stock_quantity > 0');
    $hotItemsResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE is_hot_item = 1');
    $featuredResult = $db->rawQuery('SELECT COUNT(*) as count FROM products WHERE is_featured = 1'); // Add featured products query
    
    $initialStats = [
        'total' => intval($totalResult[0]['count'] ?? 0),
        'active' => intval($activeResult[0]['count'] ?? 0),
        'inactive' => intval($inactiveResult[0]['count'] ?? 0),
        'out_of_stock' => intval($outOfStockResult[0]['count'] ?? 0),
        'low_stock' => intval($lowStockResult[0]['count'] ?? 0),
        'hot_items' => intval($hotItemsResult[0]['count'] ?? 0)
    ];
    
    // Define the variables used in the HTML
    $totalProducts = $initialStats['total'];
    $activeProducts = $initialStats['active'];
    $inactiveProducts = $initialStats['inactive'];
    $outOfStockProducts = $initialStats['out_of_stock'];
    $lowStockProducts = $initialStats['low_stock'];
    $hotItems = $initialStats['hot_items'];
    $featuredProducts = intval($featuredResult[0]['count'] ?? 0); // Define featured products variable
} catch (Exception $e) {
    error_log('Initial stats error: ' . $e->getMessage());
    $initialStats = [
        'total' => 0,
        'active' => 0,
        'inactive' => 0,
        'out_of_stock' => 0,
        'low_stock' => 0,
        'hot_items' => 0
    ];
    
    // Define default values for all variables to prevent undefined variable errors
    $totalProducts = 0;
    $activeProducts = 0;
    $inactiveProducts = 0;
    $outOfStockProducts = 0;
    $lowStockProducts = 0;
    $hotItems = 0;
    $featuredProducts = 0;
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
    
    .product-image {
        object-fit: contain;
        border: 2px solid #dee2e6;
    }
    
    .placeholder-image {
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
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .stats-card:hover .stats-number {
        animation: pulse 0.6s ease-in-out;
    }
    
    .stats-loading {
        opacity: 0.7;
        transition: opacity 0.3s ease;
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
                    <h1 class="mt-4">Product Management</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Products</li>
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

                    
                    <!-- Main Statistics -->
                    <div class="row mb-4">
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card primary h-100">
                                <div class="card-body text-center">
                                    <div class="stats-icon bg-primary mx-auto">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <h3 class="stats-number count-animation" id="totalProducts"><?php echo $totalProducts; ?></h3>
                                    <p class="stats-label">Total Products</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card success h-100">
                                <div class="card-body text-center">
                                    <div class="stats-icon bg-success mx-auto">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <h3 class="stats-number count-animation" id="activeProducts"><?php echo $activeProducts; ?></h3>
                                    <p class="stats-label">Active Products</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card warning h-100">
                                <div class="card-body text-center">
                                    <div class="stats-icon bg-warning mx-auto">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <h3 class="stats-number count-animation" id="lowStockProducts"><?php echo $lowStockProducts; ?></h3>
                                    <p class="stats-label">Low Stock</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card danger h-100">
                                <div class="card-body text-center">
                                    <div class="stats-icon bg-danger mx-auto">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                    <h3 class="stats-number count-animation" id="outOfStockProducts"><?php echo $outOfStockProducts; ?></h3>
                                    <p class="stats-label">Out of Stock</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card info h-100">
                                <div class="card-body text-center">
                                    <div class="stats-icon bg-info mx-auto">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <h3 class="stats-number count-animation" id="featuredProducts"><?php echo $featuredProducts; ?></h3>
                                    <p class="stats-label">Featured</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="card stats-card secondary h-100">
                                <div class="card-body text-center">
                                    <div class="stats-icon bg-secondary mx-auto">
                                        <i class="fas fa-fire"></i>
                                    </div>
                                    <h3 class="stats-number count-animation" id="hotItems"><?php echo $inactiveProducts; ?></h3>
                                    <p class="stats-label">Inactive Product</p>
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
                                        <div class="col-lg-2 col-md-4 mb-3">
                                            <label for="statusFilter" class="form-label">Status</label>
                                            <select class="form-select" id="statusFilter">
                                                <option value="">All Status</option>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="draft">Draft</option>
                                                <option value="out_of_stock">Out of Stock</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-lg-2 col-md-4 mb-3">
                                            <label for="categoryFilter" class="form-label">Category</label>
                                            <select class="form-select" id="categoryFilter">
                                                <option value="">All Categories</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-lg-2 col-md-4 mb-3">
                                            <label for="brandFilter" class="form-label">Brand</label>
                                            <select class="form-select" id="brandFilter">
                                                <option value="">All Brands</option>
                                                <?php foreach ($brands as $brand): ?>
                                                    <option value="<?php echo $brand['id']; ?>">
                                                        <?php echo htmlspecialchars($brand['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-lg-2 col-md-4 mb-3">
                                            <label for="stockFilter" class="form-label">Stock Status</label>
                                            <select class="form-select" id="stockFilter">
                                                <option value="">All Stock</option>
                                                <option value="in_stock">In Stock</option>
                                                <option value="low_stock">Low Stock</option>
                                                <option value="out_of_stock">Out of Stock</option>
                                            </select>
                                        </div>
                                        
                                        <!-- Featured filter removed as per requirements -->
                                        
                                       
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
                                        <h6 class="mb-0"><i class="fas fa-table me-2"></i>Product Management</h6>
                                        <div>
                                            <button type="button" class="btn btn-danger btn-sm btn-bulk-actions me-2" id="bulkDelete">
                                                <i class="fas fa-trash"></i> Delete Selected
                                            </button>
                                            <a href="product-add-enhanced.php" class="btn btn-primary btn-sm"style="padding:10px 20px; border:20px; border-radius:500px; cursor:pointer; font-size:20px; font-weight:1000; background: #999966; color:#fff; margin-left:500px;">
                                                
                                            <i class="fas fa-plus me-2"></i>Add New Product
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="productsTable">
                                            <thead>
                                                <tr>
                                                    <th width="50">
                                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                                    </th>
                                                    <th width="80">Image</th>
                                                    <th>Name</th>
                                                    <th>Category</th>
                                                    <th>Brand</th>
                                                    <th>Price</th>
                                                    <th width="80">Stock</th>
                                                    <th width="100">Stock Status</th>
                                                    <th width="80">Tags</th>
                                                    <th width="100">Status</th>
                                                    <th width="120">Created</th>
                                                    <th width="150">Actions</th>
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
        const table = $('#productsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'product-management.php?action=get_products',
                type: 'GET',
                data: function(d) {
                    d.status_filter = $('#statusFilter').val();
                    d.category_filter = $('#categoryFilter').val();
                    d.brand_filter = $('#brandFilter').val();
                    d.stock_filter = $('#stockFilter').val();
                    // Featured filter removed
                },
                error: function(xhr, error, code) {
                    console.error('DataTable AJAX error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Loading Error',
                        text: 'Failed to load products data. Please refresh the page.'
                    });
                }
            },
            columns: [
                { data: 'checkbox', orderable: false, searchable: false },
                { data: 'image', orderable: false, searchable: false },
                { data: 'name' },
                { data: 'category' },
                { data: 'brand' },
                { data: 'price' },
                { data: 'stock' },
                { data: 'stock_status', orderable: false },
                { data: 'tags', orderable: false },
                { data: 'status', orderable: false },
                { data: 'created_at' },
                { data: 'actions', orderable: false, searchable: false }
            ],
            order: [[10, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: 'No products found',
                zeroRecords: 'No matching products found',
                search: 'Search products:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ products',
                infoEmpty: 'Showing 0 to 0 of 0 products',
                infoFiltered: '(filtered from _MAX_ total products)',
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

        // Load statistics on page load (now using AJAX for real-time updates)
        // Initial stats are loaded server-side, but we still want to refresh them
        setTimeout(loadStatistics, 1000); // Load after 1 second to refresh
        
        // Auto-refresh statistics every 30 seconds
        setInterval(loadStatistics, 30000);

        // Filter change handlers
        $('#statusFilter, #categoryFilter, #brandFilter, #stockFilter').on('change', function() {
            table.ajax.reload();
            setTimeout(loadStatistics, 500);
        });

        // Clear filters
        $('#clearFilters').on('click', function() {
            $('#statusFilter, #categoryFilter, #brandFilter, #stockFilter').val('');
            table.ajax.reload();
            loadStatistics();
        });

        // Refresh stats manually
        $('#refreshStats').on('click', function() {
            const $btn = $(this);
            const originalHtml = $btn.html();
            
            // Show loading state
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
            
            // Load stats via primary endpoint first
            $.ajax({
                url: 'apis/product-stats.php',
                type: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    if (response.success && response.stats) {
                        // Update counters
                        animateCounter('#totalProducts', response.stats.total || 0);
                        animateCounter('#activeProducts', response.stats.active || 0);
                        animateCounter('#lowStockProducts', response.stats.low_stock || 0);
                        animateCounter('#outOfStockProducts', response.stats.out_of_stock || 0);
                        animateCounter('#inactiveProducts', response.stats.inactive || 0);
                        animateCounter('#hotItems', response.stats.inactive || 0); // Changed from hot_items to inactive
                        
                        // Update tooltip
                        const lastUpdated = response.stats.last_updated || 'Unknown';
                        $btn.attr('title', 'Last updated: ' + lastUpdated);
                    } else {
                        // Try fallback
                        refreshStatsFallback($btn, originalHtml);
                        return;
                    }
                },
                error: function() {
                    // Try fallback
                    refreshStatsFallback($btn, originalHtml);
                    return;
                },
                complete: function() {
                    // Restore button state
                    setTimeout(() => {
                        $btn.html(originalHtml).prop('disabled', false);
                    }, 500);
                }
            });
            
            // Also reload table
            table.ajax.reload();
        });
        
        // Refresh stats fallback function
        function refreshStatsFallback($btn, originalHtml) {
            $.ajax({
                url: 'product-management.php?action=get_stats',
                type: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    if (response.success && response.stats) {
                        // Update counters
                        animateCounter('#totalProducts', response.stats.total || 0);
                        animateCounter('#activeProducts', response.stats.active || 0);
                        animateCounter('#lowStockProducts', response.stats.low_stock || 0);
                        animateCounter('#outOfStockProducts', response.stats.out_of_stock || 0);
                        animateCounter('#inactiveProducts', response.stats.inactive || 0);
                        animateCounter('#hotItems', response.stats.inactive || 0); // Changed from hot_items to inactive
                        
                        // Update tooltip
                        const lastUpdated = response.stats.last_updated || 'Unknown';
                        $btn.attr('title', 'Last updated: ' + lastUpdated);
                    } else {
                        showStatisticsError('Failed to refresh statistics');
                    }
                },
                error: function() {
                    showStatisticsError('Failed to refresh statistics');
                }
            });
        }

        // Export CSV
        $('#exportCSV').on('click', function() {
            window.location.href = 'product-management.php?action=export&format=csv';
        });

        // Select all checkbox
        $('#selectAll').on('change', function() {
            $('.product-checkbox').prop('checked', this.checked);
            updateBulkActionVisibility();
        });

        // Bulk delete
        $('#bulkDelete').on('click', function() {
            const selectedIds = [];
            $('.product-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select products to delete.'
                });
                return;
            }

            Swal.fire({
                title: 'Delete Selected Products',
                html: `Are you sure you want to delete <strong>${selectedIds.length}</strong> selected product(s)?<br><br><small class="text-danger">This action cannot be undone!</small>`,
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
            $('.product-checkbox').off('change').on('change', function() {
                updateBulkActionVisibility();
                
                // Update select all state
                const totalCheckboxes = $('.product-checkbox').length;
                const checkedCheckboxes = $('.product-checkbox:checked').length;
                $('#selectAll').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
                $('#selectAll').prop('checked', checkedCheckboxes === totalCheckboxes);
            });

            // Delete single product
            $('.delete-product').off('click').on('click', function() {
                const productId = $(this).data('id');
                const productName = $(this).data('name');
                
                deleteSingleProduct(productId, productName);
            });
        }

        // Update bulk actions visibility
        function updateBulkActionVisibility() {
            const hasChecked = $('.product-checkbox:checked').length > 0;
            if (hasChecked) {
                $('body').addClass('bulk-actions-visible');
            } else {
                $('body').removeClass('bulk-actions-visible');
            }
        }

        // Load statistics
        function loadStatistics() {
            console.log('Loading statistics...');
            
            $.ajax({
                url: 'product-management.php?action=get_stats',
                type: 'GET',
                dataType: 'json',
                timeout: 15000,
                beforeSend: function() {
                    console.log('Sending request to load statistics');
                    // Show loading state for visible elements only
                    $('#statsContainer .stats-card').addClass('stats-loading');
                },
                success: function(response) {
                    console.log('Statistics response received:', response);
                    if (response.success && response.stats) {
                        console.log('Statistics data:', response.stats);
                        // Update only the counters that exist in this page
                        animateCounter('#totalProducts', response.stats.total || 0);
                        animateCounter('#activeProducts', response.stats.active || 0);
                        animateCounter('#inactiveProducts', response.stats.inactive || 0);
                        animateCounter('#hotItems', response.stats.inactive || 0); // Changed from hot_items to inactive
                        
                        // Update refresh button to show last update time
                        const lastUpdated = response.stats.last_updated || 'Unknown';
                        $('#refreshStats').attr('title', 'Last updated: ' + lastUpdated);
                        
                        console.log('Statistics updated successfully');
                    } else {
                        console.warn('Invalid statistics response:', response);
                        showStatisticsError('Failed to load statistics: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Statistics AJAX Error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        readyState: xhr.readyState,
                        statusCode: xhr.status
                    });
                    
                    showStatisticsError('Failed to connect to statistics endpoint');
                },
                complete: function() {
                    // Remove loading state
                    $('#statsContainer .stats-card').removeClass('stats-loading');
                }
            });
        }
        
        // Fallback statistics loading using the main endpoint
        function loadStatisticsFallback() {
            console.log('Loading statistics via fallback endpoint...');
            
            $.ajax({
                url: 'product-management.php?action=get_stats',
                type: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    console.log('Fallback statistics response:', response);
                    if (response.success && response.stats) {
                        // Update all counters with animation
                        animateCounter('#totalProducts', response.stats.total || 0);
                        animateCounter('#activeProducts', response.stats.active || 0);
                        animateCounter('#lowStockProducts', response.stats.low_stock || 0);
                        animateCounter('#outOfStockProducts', response.stats.out_of_stock || 0);
                        animateCounter('#inactiveProducts', response.stats.inactive || 0);
                        animateCounter('#hotItems', response.stats.inactive || 0); // Changed from hot_items to inactive
                        
                        console.log('Fallback statistics updated successfully');
                    } else {
                        console.error('Fallback statistics failed:', response);
                        showStatisticsError('Both primary and fallback endpoints failed');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Fallback Statistics AJAX Error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    showStatisticsError('Both primary and fallback endpoints failed');
                }
            });
        }
        
        // Show statistics error
        function showStatisticsError(message) {
            console.error('Statistics Error:', message);
            // Don't overwrite valid numbers, just log the error
            // The page already has server-side loaded statistics
            
            // Show a subtle notification instead of replacing numbers
            const errorNotification = $('<div class="alert alert-warning alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; max-width: 300px;" role="alert">' +
                '<small><i class="fas fa-exclamation-triangle me-2"></i>Statistics auto-refresh failed. Using cached data.</small>' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                '</div>');
            
            $('body').append(errorNotification);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                errorNotification.alert('close');
            }, 5000);
        }

        // Animate counter
        function animateCounter(element, target) {
            const $element = $(element);
            
            // Remove loading spinner if present
            if ($element.find('.fa-spinner').length > 0) {
                $element.empty();
            }
            
            const current = parseInt($element.text()) || 0;
            target = parseInt(target) || 0;
            
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

        // Delete single product
        function deleteSingleProduct(productId, productName) {
            Swal.fire({
                title: 'Delete Product',
                html: `Are you sure you want to delete <strong>"${productName}"</strong>?<br><br><small class="text-danger">This action cannot be undone!</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'product-delete.php',
                        type: 'POST',
                        data: { 
                            action: 'delete',
                            product_id: productId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
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
                                text: 'Failed to delete product. Please try again.'
                            });
                        }
                    });
                }
            });
        }

        // Perform bulk delete
        function performBulkDelete(productIds) {
            $.ajax({
                url: 'product-management.php?action=bulk_delete',
                type: 'POST',
                data: { product_ids: productIds },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        table.ajax.reload(null, false);
                        loadStatistics();
                        updateBulkActionVisibility();
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
                        text: 'Failed to delete products. Please try again.'
                    });
                }
            });
        }
    });
    </script>
</body>
</html>