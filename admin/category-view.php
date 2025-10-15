<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

$db = new MysqliDb();

// Get category ID from URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['error'] = 'Invalid category ID';
    header('Location: categories.php');
    exit;
}

// Get category data
$db->where('id', $id);
$category = $db->getOne('categories');

if (!$category) {
    $_SESSION['error'] = 'Category not found';
    header('Location: categories.php');
    exit;
}

// Format dates
$createdAt = new DateTime($category['created_at']);
$updatedAt = $category['updated_at'] ? new DateTime($category['updated_at']) : null;

// Get category statistics
$totalProducts = $db->where('category_id', $id)->getValue('products', 'count(*)') ?: 0;
$activeProducts = $db->where('category_id', $id)->where('is_active', 1)->where('status', 'active')->getValue('products', 'count(*)') ?: 0;
$totalSubcategories = $db->where('category_id', $id)->where('is_active', 1)->getValue('subcategories', 'count(*)') ?: 0;

// Get recent products in this category
$db->where('category_id', $id);
$db->where('is_active', 1);
$db->where('status', 'active');
$db->orderBy('created_at', 'DESC');
$recentProducts = $db->get('products', 5);

// Get subcategories
$db->where('category_id', $id);
$db->where('is_active', 1);
$db->orderBy('name', 'ASC');
$subcategories = $db->get('subcategories', 10);
?>

<?php require __DIR__ . '/components/header.php'; ?>
<style>
    .category-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 8px;
    }
    .category-image {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 1rem;
    }
    .stat-card {
        border-left: 4px solid #28a745;
        padding: 1rem;
        margin-bottom: 1.5rem;
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    }
    .stat-card .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #5a5c69;
    }
    .stat-card .stat-label {
        font-size: 0.875rem;
        color: #858796;
        text-transform: uppercase;
        font-weight: 600;
    }
    .badge-status {
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.35rem;
    }
    .badge-active {
        background-color: #1cc88a1a;
        color: #1cc88a;
    }
    .badge-inactive {
        background-color: #e74a3b1a;
        color: #e74a3b;
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
                    <nav aria-label="breadcrumb" class="mt-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="categories.php">Categories</a></li>
                            <li class="breadcrumb-item active">Category Details</li>
                        </ol>
                    </nav>

                    <!-- Header Section -->
                    <div class="category-header">
                        <div class="container">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <?php
                                    $hasImage = !empty($category['image']) && file_exists(__DIR__ . '/../assets/categories/' . $category['image']);
                                    ?>
                                    <?php if ($hasImage): ?>
                                        <img src="../assets/categories/<?= htmlspecialchars($category['image']) ?>"
                                             alt="Category Image"
                                             class="category-image"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="mx-auto d-none align-items-center justify-content-center rounded-circle"
                                             style="width: 120px; height: 120px; background-color: #28a745; color: white; font-size: 3rem; border: 4px solid rgba(255, 255, 255, 0.2);">
                                            <i class="fas fa-tag"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="mx-auto d-flex align-items-center justify-content-center rounded-circle"
                                             style="width: 120px; height: 120px; background-color: #28a745; color: white; font-size: 3rem; border: 4px solid rgba(255, 255, 255, 0.2);">
                                            <i class="fas fa-tag"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h2 class="mb-1"><?= htmlspecialchars($category['name']) ?></h2>
                                    <p class="mb-2">
                                        <span class="badge bg-<?= $category['is_active'] ? 'success' : 'danger' ?> me-2">
                                            <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                        <span class="badge bg-info">Sort Order: <?= $category['sort_order'] ?></span>
                                    </p>
                                    <?php if (!empty($category['slug'])): ?>
                                        <p class="mb-0">
                                            <i class="fas fa-link me-2"></i> Slug: <?= htmlspecialchars($category['slug']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <a href="category-edit-enhanced.php?id=<?= $category['id'] ?>" class="btn btn-light me-2">
                                        <i class="fas fa-edit me-1"></i> Edit Category
                                    </a>
                                    <a href="categories.php" class="btn btn-outline-light">
                                        <i class="fas fa-arrow-left me-1"></i> Back to Categories
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-lg-8">
                            <!-- Category Details -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-success">Category Details</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($category['description'])): ?>
                                        <div class="mb-4">
                                            <h6>Description</h6>
                                            <p class="text-muted"><?= nl2br(htmlspecialchars($category['description'])) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-3">
                                                <strong>Created:</strong><br>
                                                <span class="text-muted">
                                                    <?= $createdAt->format('F j, Y') ?>
                                                    <small class="d-block text-muted">
                                                        <?= $createdAt->format('g:i A') ?>
                                                    </small>
                                                </span>
                                            </p>

                                            <p class="mb-3">
                                                <strong>Last Updated:</strong><br>
                                                <span class="text-muted">
                                                    <?= $updatedAt ? $updatedAt->format('F j, Y') : 'Never' ?>
                                                    <?php if ($updatedAt): ?>
                                                        <small class="d-block text-muted">
                                                            <?= $updatedAt->format('g:i A') ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-3">
                                                <strong>Status:</strong><br>
                                                <span class="badge-status <?= $category['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                                    <i class="fas fa-<?= $category['is_active'] ? 'check-circle' : 'times-circle' ?> me-1"></i>
                                                    <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </p>

                                            <p class="mb-3">
                                                <strong>Display Order:</strong><br>
                                                <span class="text-muted">
                                                    <?= $category['sort_order'] ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <?php if (!empty($category['meta_title']) || !empty($category['meta_description'])): ?>
                                        <div class="mt-4 pt-3 border-top">
                                            <h6>SEO Information</h6>
                                            <?php if (!empty($category['meta_title'])): ?>
                                                <p class="mb-2"><strong>Meta Title:</strong> <?= htmlspecialchars($category['meta_title']) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($category['meta_description'])): ?>
                                                <p class="mb-0"><strong>Meta Description:</strong> <?= htmlspecialchars($category['meta_description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Recent Products -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-success">Recent Products (<?= count($recentProducts) ?>)</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($recentProducts)): ?>
                                        <div class="row row-cols-1 row-cols-md-2 g-3">
                                            <?php foreach ($recentProducts as $product):
                                                $imagePath = '../assets/products/' . $product['image'];
                                                $imageUrl = file_exists(__DIR__ . '/../assets/products/' . $product['image']) ? $imagePath : '../admin/assets/img/no-image.png';
                                                $productDate = new DateTime($product['created_at']);
                                            ?>
                                                <div class="col">
                                                    <div class="card h-100">
                                                        <div class="row g-0">
                                                            <div class="col-4">
                                                                <img src="<?= $imageUrl ?>"
                                                                     class="img-fluid rounded-start h-100 object-fit-cover"
                                                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                                                     style="min-height: 80px;">
                                                            </div>
                                                            <div class="col-8">
                                                                <div class="card-body p-2">
                                                                    <h6 class="card-title mb-1" style="font-size: 0.9rem;">
                                                                        <?= htmlspecialchars(substr($product['name'], 0, 30)) ?><?php if (strlen($product['name']) > 30): ?>...<?php endif; ?>
                                                                    </h6>
                                                                    <p class="card-text mb-1">
                                                                        <strong class="text-success">৳<?= number_format($product['selling_price'], 2) ?></strong>
                                                                        <span class="badge bg-<?= $product['stock_quantity'] > 0 ? 'success' : 'warning' ?> ms-1" style="font-size: 0.7rem;">
                                                                            <?= $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock' ?>
                                                                        </span>
                                                                    </p>
                                                                    <small class="text-muted">
                                                                        <?= $productDate->format('M j, Y') ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="text-center mt-3">
                                            <a href="products.php?category=<?= $category['id'] ?>" class="btn btn-sm btn-outline-success">
                                                View All Products
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <div class="text-muted mb-3">
                                                <i class="fas fa-box-open fa-3x"></i>
                                            </div>
                                            <p class="mb-0">No products found in this category</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-lg-4">
                            <!-- Statistics -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-success">Statistics</h6>
                                </div>
                                <div class="card-body">
                                    <div class="stat-card">
                                        <div class="stat-value text-primary"><?= $totalProducts ?></div>
                                        <div class="stat-label">Total Products</div>
                                    </div>

                                    <div class="stat-card">
                                        <div class="stat-value text-success"><?= $activeProducts ?></div>
                                        <div class="stat-label">Active Products</div>
                                    </div>

                                    <div class="stat-card">
                                        <div class="stat-value text-info"><?= $totalSubcategories ?></div>
                                        <div class="stat-label">Subcategories</div>
                                    </div>

                                    <div class="stat-card">
                                        <div class="stat-value text-warning">
                                            <?php
                                            $inactiveProducts = $totalProducts - $activeProducts;
                                            echo $inactiveProducts;
                                            ?>
                                        </div>
                                        <div class="stat-label">Inactive Products</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Subcategories -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-success">Subcategories (<?= count($subcategories) ?>)</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($subcategories)): ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($subcategories as $subcategory):
                                                $subProductCount = $db->where('subcategory_id', $subcategory['id'])->getValue('products', 'count(*)') ?: 0;
                                            ?>
                                                <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong class="text-sm"><?= htmlspecialchars($subcategory['name']) ?></strong>
                                                        <br><small class="text-muted"><?= $subProductCount ?> products</small>
                                                    </div>
                                                    <a href="product-all.php?subcategory=<?= $subcategory['id'] ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-3">
                                            <div class="text-muted mb-2">
                                                <i class="fas fa-list fa-2x"></i>
                                            </div>
                                            <p class="mb-0 text-muted">No subcategories</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-success">Quick Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="product-add-enhanced.php?category=<?= $category['id'] ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-plus me-1"></i> Add Product
                                        </a>
                                        <a href="subcategory-add.php?category=<?= $category['id'] ?>" class="btn btn-outline-info">
                                            <i class="fas fa-plus me-1"></i> Add Subcategory
                                        </a>
                                        <a href="category-edit-enhanced.php?id=<?= $category['id'] ?>" class="btn btn-outline-warning">
                                            <i class="fas fa-edit me-1"></i> Edit Category
                                        </a>
                                        <button class="btn btn-outline-danger" onclick="confirmDelete(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                            <i class="fas fa-trash me-1"></i> Delete Category
                                        </button>
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

    <!-- JavaScript Libraries -->
    <script src="<?= settings()['adminpage'] ?>assets/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>

    <script>
        function confirmDelete(categoryId, categoryName) {
            if (confirm(`Are you sure you want to delete the category "${categoryName}"? This action cannot be undone and will affect all related products and subcategories.`)) {
                window.location.href = `category-delete-enhanced.php?id=${categoryId}`;
            }
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>
