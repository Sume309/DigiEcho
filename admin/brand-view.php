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

// Get brand ID from URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['error'] = 'Invalid brand ID';
    header('Location: brand-all.php');
    exit;
}

// Get brand data
$db->where('id', $id);
$brand = $db->getOne('brands');

if (!$brand) {
    $_SESSION['error'] = 'Brand not found';
    header('Location: brand-all.php');
    exit;
}

// Format dates
$createdAt = new DateTime($brand['created_at']);
$updatedAt = $brand['updated_at'] ? new DateTime($brand['updated_at']) : null;

// Get brand statistics
$totalProducts = $db->where('brand', $id)->getValue('products', 'count(*)') ?: 0;
$activeProducts = $db->where('brand', $id)->where('is_active', 1)->where('status', 'active')->getValue('products', 'count(*)') ?: 0;

// Get recent products in this brand
$db->where('brand', $id);
$db->where('is_active', 1);
$db->where('status', 'active');
$db->orderBy('created_at', 'DESC');
$recentProducts = $db->get('products', 5);

// Get featured status and other details
$isFeatured = $brand['is_featured'] ? 'Yes' : 'No';
$statusText = $brand['is_active'] ? 'Active' : 'Inactive';
$statusColor = $brand['is_active'] ? 'success' : 'danger';
?>

<?php require __DIR__ . '/components/header.php'; ?>
<style>
    .brand-header {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 8px;
    }
    .brand-logo-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: contain;
        border: 4px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 1rem;
    }
    .stat-card {
        border-left: 4px solid #ff6b6b;
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
                            <li class="breadcrumb-item"><a href="brand-all.php">Brands</a></li>
                            <li class="breadcrumb-item active">Brand Details</li>
                        </ol>
                    </nav>

                    <!-- Header Section -->
                    <div class="brand-header">
                        <div class="container">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <?php
                                    $hasLogo = !empty($brand['logo']) && file_exists(__DIR__ . '/../assets/brands/' . $brand['logo']);
                                    ?>
                                    <?php if ($hasLogo): ?>
                                        <img src="../assets/brands/<?= htmlspecialchars($brand['logo']) ?>"
                                             alt="Brand Logo"
                                             class="brand-logo-large"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="mx-auto d-none align-items-center justify-content-center rounded-circle"
                                             style="width: 120px; height: 120px; background-color: #ff6b6b; color: white; font-size: 3rem; border: 4px solid rgba(255, 255, 255, 0.2);">
                                            <i class="fas fa-tag"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="mx-auto d-flex align-items-center justify-content-center rounded-circle"
                                             style="width: 120px; height: 120px; background-color: #ff6b6b; color: white; font-size: 3rem; border: 4px solid rgba(255, 255, 255, 0.2);">
                                            <i class="fas fa-tag"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h2 class="mb-1"><?= htmlspecialchars($brand['name']) ?></h2>
                                    <p class="mb-2">
                                        <span class="badge bg-<?= $statusColor ?> me-2">
                                            <?= $statusText ?>
                                        </span>
                                        <span class="badge bg-<?= $brand['is_featured'] ? 'warning' : 'secondary' ?>">
                                            <?= $brand['is_featured'] ? 'Featured' : 'Not Featured' ?>
                                        </span>
                                    </p>
                                    <?php if (!empty($brand['slug'])): ?>
                                        <p class="mb-0">
                                            <i class="fas fa-link me-2"></i> Slug: <?= htmlspecialchars($brand['slug']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <a href="brand-edit-enhanced.php?id=<?= $brand['id'] ?>" class="btn btn-light me-2">
                                        <i class="fas fa-edit me-1"></i> Edit Brand
                                    </a>
                                    <a href="brand-all.php" class="btn btn-outline-light">
                                        <i class="fas fa-arrow-left me-1"></i> Back to Brands
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-lg-8">
                            <!-- Brand Details -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-danger">Brand Details</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($brand['description'])): ?>
                                        <div class="mb-4">
                                            <h6>Description</h6>
                                            <p class="text-muted"><?= nl2br(htmlspecialchars($brand['description'])) ?></p>
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
                                                <span class="badge-status <?= $brand['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                                    <i class="fas fa-<?= $brand['is_active'] ? 'check-circle' : 'times-circle' ?> me-1"></i>
                                                    <?= $brand['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </p>

                                            <p class="mb-3">
                                                <strong>Featured:</strong><br>
                                                <span class="badge-status <?= $brand['is_featured'] ? 'badge-active' : 'badge-inactive' ?>">
                                                    <i class="fas fa-<?= $brand['is_featured'] ? 'star' : 'star-o' ?> me-1"></i>
                                                    <?= $brand['is_featured'] ? 'Featured' : 'Not Featured' ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <?php if (!empty($brand['website'])): ?>
                                        <div class="mt-4 pt-3 border-top">
                                            <h6>Website</h6>
                                            <p class="mb-0">
                                                <a href="<?= htmlspecialchars($brand['website']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-external-link-alt me-1"></i>
                                                    Visit Website
                                                </a>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Recent Products -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-danger">Recent Products (<?= count($recentProducts) ?>)</h6>
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
                                            <a href="products.php?brand=<?= $brand['id'] ?>" class="btn btn-sm btn-outline-danger">
                                                View All Products
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <div class="text-muted mb-3">
                                                <i class="fas fa-box-open fa-3x"></i>
                                            </div>
                                            <p class="mb-0">No products found for this brand</p>
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
                                    <h6 class="m-0 font-weight-bold text-danger">Statistics</h6>
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
                                        <div class="stat-value text-info">
                                            <?php
                                            $inactiveProducts = $totalProducts - $activeProducts;
                                            echo $inactiveProducts;
                                            ?>
                                        </div>
                                        <div class="stat-label">Inactive Products</div>
                                    </div>

                                    <div class="stat-card">
                                        <div class="stat-value text-warning">
                                            <?php
                                            $featuredCount = $db->where('brand', $id)->where('is_featured', 1)->getValue('products', 'count(*)') ?: 0;
                                            echo $featuredCount;
                                            ?>
                                        </div>
                                        <div class="stat-label">Featured Products</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Brand Information -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-danger">Brand Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label d-block">Brand Name</label>
                                        <strong class="text-dark"><?= htmlspecialchars($brand['name']) ?></strong>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label d-block">Slug</label>
                                        <code class="text-muted bg-light px-2 py-1 rounded"><?= htmlspecialchars($brand['slug']) ?></code>
                                    </div>

                                    <?php if (!empty($brand['website'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Website</label>
                                            <a href="<?= htmlspecialchars($brand['website']) ?>" target="_blank" class="text-primary">
                                                <?= htmlspecialchars($brand['website']) ?>
                                                <i class="fas fa-external-link-alt ms-1"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <label class="form-label d-block">Sort Order</label>
                                        <span class="badge bg-secondary">Position <?= $brand['sort_order'] ?: 0 ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-danger">Quick Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="product-add-enhanced.php?brand=<?= $brand['id'] ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-plus me-1"></i> Add Product
                                        </a>
                                        <a href="brand-edit-enhanced.php?id=<?= $brand['id'] ?>" class="btn btn-outline-warning">
                                            <i class="fas fa-edit me-1"></i> Edit Brand
                                        </a>
                                        <button class="btn btn-outline-danger" onclick="confirmDelete(<?= $brand['id'] ?>, '<?= htmlspecialchars($brand['name']) ?>')">
                                            <i class="fas fa-trash me-1"></i> Delete Brand
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
        function confirmDelete(brandId, brandName) {
            if (confirm(`Are you sure you want to delete the brand "${brandName}"? This action will affect all associated products.`)) {
                window.location.href = `brand-delete-enhanced.php?id=${brandId}`;
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
