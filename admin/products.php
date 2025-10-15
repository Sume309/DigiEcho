<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';
require_once __DIR__ . '/../src/auth/admin.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('Location: auto-login.php');
    exit;
}

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

// Handle URL parameters for filtering
$brandFilter = filter_input(INPUT_GET, 'brand', FILTER_VALIDATE_INT);
$categoryFilter = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$searchQuery = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING);
$pageNum = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$limit = 12;
$offset = ($pageNum - 1) * $limit;

// Check if we have any filters applied - if so, show product listing instead of dashboard
$hasFilters = !empty($brandFilter) || !empty($categoryFilter) || !empty($searchQuery);

// If filters are applied, show product listing
if ($hasFilters) {
    // Build query conditions
    $whereConditions = ['p.is_active = 1'];
    $params = [];

    if (!empty($searchQuery)) {
        $whereConditions[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)";
        $params = array_merge($params, ["%$searchQuery%", "%$searchQuery%", "%$searchQuery%", "%$searchQuery%"]);
    }

    if (!empty($categoryFilter)) {
        $whereConditions[] = "p.category_id = ?";
        $params[] = $categoryFilter;
    }

    if (!empty($brandFilter)) {
        $whereConditions[] = "p.brand = ?";
        $params[] = $brandFilter;
    }

    $whereClause = implode(' AND ', $whereConditions);

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM products p WHERE $whereClause";
    $totalResult = !empty($params) ? $db->rawQuery($countQuery, $params) : $db->rawQuery($countQuery);
    $totalProducts = $totalResult[0]['total'] ?? 0;
    $totalPages = ceil($totalProducts / $limit);

    // Get filtered products
    $productsQuery = "
        SELECT p.*, c.name as category_name, b.name as brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand = b.id
        WHERE $whereClause
        ORDER BY p.created_at DESC
        LIMIT $limit OFFSET $offset
    ";

    $products = !empty($params) ? $db->rawQuery($productsQuery, $params) : $db->rawQuery($productsQuery);

    // Get filter names for display
    $filterNames = [];
    if (!empty($brandFilter)) {
        $brand = $db->where('id', $brandFilter)->getOne('brands', 'name');
        $filterNames[] = "Brand: " . ($brand['name'] ?? 'Unknown');
    }
    if (!empty($categoryFilter)) {
        $category = $db->where('id', $categoryFilter)->getOne('categories', 'name');
        $filterNames[] = "Category: " . ($category['name'] ?? 'Unknown');
    }
    if (!empty($searchQuery)) {
        $filterNames[] = "Search: \"$searchQuery\"";
    }

    $pageTitle = "Products - " . implode(' | ', $filterNames);
    $breadcrumbTitle = implode(' & ', $filterNames);
} else {
    // Default dashboard view - get statistics
    $totalProducts = $db->getValue('products', 'COUNT(*)');
    $activeProducts = $db->getValue('products', 'COUNT(*)', 'status = "active"');
    $lowStockProducts = $db->getValue('products', 'COUNT(*)', 'stock_quantity <= min_stock_level AND stock_quantity > 0');
    $outOfStockProducts = $db->getValue('products', 'COUNT(*)', 'status = "out_of_stock" OR stock_quantity = 0');
    $featuredProducts = $db->getValue('products', 'COUNT(*)', 'is_featured = 1');
    $pageTitle = "Product Management";
    $breadcrumbTitle = "Products";
    $products = [];
    $totalPages = 0;
}

require __DIR__ . '/components/header.php'; 
?>

<style>
    .dashboard-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        transition: transform 0.3s ease;
        height: 100%;
    }
    
    .dashboard-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    .module-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .module-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.25);
    }
    
    .module-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 1.5rem;
        margin-bottom: 15px;
    }
    
    .recent-product {
        border-left: 3px solid #0d6efd;
        padding-left: 15px;
        margin-bottom: 15px;
    }
</style>

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                        <div>
                            <h1 class="h3 mb-1"><?php echo htmlspecialchars($pageTitle); ?></h1>
                            <p class="text-muted mb-0">
                                <?php if ($hasFilters): ?>
                                    Showing filtered products • <?php echo $totalProducts; ?> results found
                                <?php else: ?>
                                    Comprehensive product management system
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <?php if (!$hasFilters): ?>
                                <a href="product-add-enhanced.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add New Product
                                </a>
                            <?php else: ?>
                                <a href="products.php" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                                </a>
                                <a href="product-management.php" class="btn btn-primary">
                                    <i class="fas fa-list me-1"></i> Full Management
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                            <?php if ($hasFilters): ?>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($breadcrumbTitle); ?></li>
                            <?php else: ?>
                                <li class="breadcrumb-item active">Overview</li>
                            <?php endif; ?>
                        </ol>
                    </nav>

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

                    <?php if ($hasFilters): ?>
                        <!-- Product Listing View -->
                        <?php if (!empty($products)): ?>
                            <div class="row">
                                <?php foreach ($products as $product):
                                    $imagePath = '../assets/products/' . $product['image'];
                                    $imageUrl = file_exists(__DIR__ . '/../assets/products/' . $product['image']) ? $imagePath : '../admin/assets/img/no-image.png';
                                    $discountInfo = '';
                                    if (!empty($product['discount_price']) && $product['discount_price'] > 0) {
                                        $discountInfo = "<del class='text-muted'>৳{$product['selling_price']}</del> <span class='text-success fw-bold'>৳{$product['discount_price']}</span>";
                                    } else {
                                        $discountInfo = "<span class='fw-bold'>৳{$product['selling_price']}</span>";
                                    }

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
                                    }

                                    $stockStatus = '';
                                    if ($product['stock_quantity'] == 0) {
                                        $stockStatus = '<span class="text-danger">Out of Stock</span>';
                                    } elseif ($product['stock_quantity'] <= $product['min_stock_level']) {
                                        $stockStatus = '<span class="text-warning">Low Stock</span>';
                                    } else {
                                        $stockStatus = '<span class="text-success">In Stock</span>';
                                    }
                                ?>
                                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                        <div class="card h-100 shadow-sm">
                                            <div style="height: 200px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                                <img src="<?= $imageUrl ?>"
                                                     class="card-img-top p-3"
                                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                                     style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                            </div>
                                            <div class="card-body d-flex flex-column">
                                                <h5 class="card-title mb-2" style="font-size: 1rem;">
                                                    <?= htmlspecialchars(substr($product['name'], 0, 50)) ?><?php if (strlen($product['name']) > 50): ?>...<?php endif; ?>
                                                </h5>

                                                <div class="mb-2">
                                                    <small class="text-muted d-block">
                                                        <strong>SKU:</strong> <?= htmlspecialchars($product['sku']) ?>
                                                    </small>
                                                    <small class="text-muted d-block">
                                                        <strong>Category:</strong> <?= htmlspecialchars($product['category_name'] ?? 'N/A') ?>
                                                    </small>
                                                    <small class="text-muted d-block">
                                                        <strong>Brand:</strong> <?= htmlspecialchars($product['brand_name'] ?? 'N/A') ?>
                                                    </small>
                                                </div>

                                                <div class="mb-2">
                                                    <?= $discountInfo ?>
                                                </div>

                                                <div class="mb-2">
                                                    <small class="d-block">
                                                        <strong>Stock:</strong> <?= $product['stock_quantity'] ?> (<?= $stockStatus ?>)
                                                    </small>
                                                </div>

                                                <div class="mb-2">
                                                    <?= $statusBadge ?>
                                                    <?php if ($product['is_featured']): ?>
                                                        <span class="badge bg-info ms-1">Featured</span>
                                                    <?php endif; ?>
                                                    <?php if ($product['is_hot_item']): ?>
                                                        <span class="badge bg-danger ms-1">Hot</span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="mt-auto">
                                                    <div class="btn-group w-100" role="group">
                                                        <a href="product-view.php?id=<?= $product['id'] ?>" class="btn btn-outline-info btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="product-edit-enhanced.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-success btn-sm btn-add-cart"
                                                                data-product-id="<?= $product['id'] ?>"
                                                                data-product-name="<?= htmlspecialchars($product['name']) ?>"
                                                                data-product-price="<?= $product['selling_price'] ?>"
                                                                data-product-image="<?= htmlspecialchars($product['image'] ?? '') ?>">
                                                            <i class="fas fa-cart-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer text-muted">
                                                <small>Added: <?= date('M j, Y', strtotime($product['created_at'])) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Product pagination" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php
                                        $baseUrl = "products.php?" . http_build_query(array_filter([
                                            'brand' => $brandFilter,
                                            'category' => $categoryFilter,
                                            'q' => $searchQuery
                                        ]));

                                        $startPage = max(1, $pageNum - 2);
                                        $endPage = min($totalPages, $pageNum + 2);

                                        if ($pageNum > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= $baseUrl ?>&page=<?= $pageNum - 1 ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif;

                                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <li class="page-item <?= $i == $pageNum ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= $baseUrl ?>&page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor;

                                        if ($pageNum < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= $baseUrl ?>&page=<?= $pageNum + 1 ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                                    <h3 class="text-muted">No products found</h3>
                                    <p class="text-muted">Try adjusting your search criteria or filters.</p>
                                    <a href="products.php" class="btn btn-primary btn-lg mt-3">Back to Dashboard</a>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Dashboard View -->
                    <div class="row mb-4">
                        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                            <div class="card dashboard-card border-start border-primary border-4">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="stat-number text-primary"><?php echo $totalProducts; ?></div>
                                            <div class="stat-label">Total Products</div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-boxes fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                            <div class="card dashboard-card border-start border-success border-4">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="stat-number text-success"><?php echo $activeProducts; ?></div>
                                            <div class="stat-label">Active Products</div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-check-circle fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                            <div class="card dashboard-card border-start border-warning border-4">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="stat-number text-warning"><?php echo $lowStockProducts; ?></div>
                                            <div class="stat-label">Low Stock</div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                            <div class="card dashboard-card border-start border-danger border-4">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="stat-number text-danger"><?php echo $outOfStockProducts; ?></div>
                                            <div class="stat-label">Out of Stock</div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                            <div class="card dashboard-card border-start border-info border-4">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="stat-number text-info"><?php echo $featuredProducts; ?></div>
                                            <div class="stat-label">Featured</div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-star fa-2x text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                            <div class="card dashboard-card border-start border-secondary border-4">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="stat-number text-secondary">
                                                <?php echo $db->getValue('product_reviews', 'COUNT(*)', 'is_approved = 1'); ?>
                                            </div>
                                            <div class="stat-label">Reviews</div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-comments fa-2x text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Product Management Modules -->
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 mb-4">
                                    <div class="card module-card">
                                        <div class="card-body text-center">
                                            <div class="module-icon bg-primary text-white mx-auto">
                                                <i class="fas fa-list"></i>
                                            </div>
                                            <h5 class="card-title">Manage Products</h5>
                                            <p class="card-text text-muted">View, edit, and delete all products in your inventory.</p>
                                            <a href="product-management.php" class="btn btn-primary">Go to Products</a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 col-md-6 mb-4">
                                    <div class="card module-card">
                                        <div class="card-body text-center">
                                            <div class="module-icon bg-success text-white mx-auto">
                                                <i class="fas fa-plus-circle"></i>
                                            </div>
                                            <h5 class="card-title">Add New Product</h5>
                                            <p class="card-text text-muted">Add new products with detailed information and images.</p>
                                            <a href="product-add-enhanced.php" class="btn btn-success">Add Product</a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 col-md-6 mb-4">
                                    <div class="card module-card">
                                        <div class="card-body text-center">
                                            <div class="module-icon bg-info text-white mx-auto">
                                                <i class="fas fa-upload"></i>
                                            </div>
                                            <h5 class="card-title">Bulk Upload</h5>
                                            <p class="card-text text-muted">Upload multiple products at once using CSV files.</p>
                                            <a href="product-bulk-upload.php" class="btn btn-info">Bulk Upload</a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 col-md-6 mb-4">
                                    <div class="card module-card">
                                        <div class="card-body text-center">
                                            <div class="module-icon bg-warning text-white mx-auto">
                                                <i class="fas fa-warehouse"></i>
                                            </div>
                                            <h5 class="card-title">Inventory Management</h5>
                                            <p class="card-text text-muted">Manage stock levels and track inventory movements.</p>
                                            <a href="inventory-management.php" class="btn btn-warning">Manage Inventory</a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 col-md-6 mb-4">
                                    <div class="card module-card">
                                        <div class="card-body text-center">
                                            <div class="module-icon bg-danger text-white mx-auto">
                                                <i class="fas fa-percent"></i>
                                            </div>
                                            <h5 class="card-title">Discounts & Offers</h5>
                                            <p class="card-text text-muted">Create and manage product discounts and special offers.</p>
                                            <a href="discounts-management.php" class="btn btn-danger">Manage Discounts</a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 col-md-6 mb-4">
                                    <div class="card module-card">
                                        <div class="card-body text-center">
                                            <div class="module-icon bg-secondary text-white mx-auto">
                                                <i class="fas fa-comments"></i>
                                            </div>
                                            <h5 class="card-title">Reviews & Ratings</h5>
                                            <p class="card-text text-muted">Manage customer reviews and product ratings.</p>
                                            <a href="reviews-management.php" class="btn btn-secondary">Manage Reviews</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Products -->
                        <div class="col-lg-4">
                            <div class="card dashboard-card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Recently Added Products</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $recentProducts = $db->orderBy('created_at', 'DESC')->get('products', 5);
                                    if (!empty($recentProducts)):
                                        foreach ($recentProducts as $product):
                                    ?>
                                        <div class="recent-product">
                                            <h6 class="mb-1">
                                                <a href="product-view.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                </a>
                                            </h6>
                                            <div class="d-flex justify-content-between small text-muted">
                                                <span>SKU: <?php echo htmlspecialchars($product['sku']); ?></span>
                                                <span>Stock: <?php echo $product['stock_quantity']; ?></span>
                                            </div>
                                            <div class="small">
                                                Added: <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
                                            </div>
                                        </div>
                                    <?php 
                                        endforeach;
                                    else:
                                    ?>
                                        <p class="text-muted text-center">No products found.</p>
                                    <?php endif; ?>
                                    <div class="text-center mt-3">
                                        <a href="product-management.php" class="btn btn-outline-primary btn-sm">View All Products</a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Quick Stats -->
                            <div class="card dashboard-card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Quick Stats</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Active Products:</span>
                                        <span class="fw-bold"><?php echo $activeProducts; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Inactive Products:</span>
                                        <span class="fw-bold">
                                            <?php echo $db->getValue('products', 'COUNT(*)', 'status IN ("inactive", "draft")'); ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Discounted Items:</span>
                                        <span class="fw-bold">
                                            <?php echo $db->getValue('products', 'COUNT(*)', 'discount_price IS NOT NULL AND discount_price > 0'); ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Hot Items:</span>
                                        <span class="fw-bold">
                                            <?php echo $db->getValue('products', 'COUNT(*)', 'is_hot_item = 1'); ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Total Categories:</span>
                                        <span class="fw-bold">
                                            <?php echo $db->getValue('categories', 'COUNT(*)'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>
</body>
</html>