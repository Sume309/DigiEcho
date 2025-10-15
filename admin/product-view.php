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

// Get product ID from URL
$productId = intval($_GET['id'] ?? 0);

if (!$productId) {
    $_SESSION['error'] = 'Invalid product ID';
    header('Location: product-management.php');
    exit;
}

// Get product details with related data
$db->join('categories c', 'p.category_id = c.id', 'LEFT');
$db->join('subcategories sc', 'p.subcategory_id = sc.id', 'LEFT');
$db->join('brands b', 'p.brand = b.id', 'LEFT');
$product = $db->where('p.id', $productId)->getOne('products p', 
    'p.*, c.name as category_name, sc.name as subcategory_name, b.name as brand_name');

if (!$product) {
    $_SESSION['error'] = 'Product not found';
    header('Location: product-management.php');
    exit;
}

// Get product reviews
$reviews = $db->where('product_id', $productId)->orderBy('created_at', 'DESC')->get('product_reviews', 10);

// Get product gallery images
$galleryImages = [];
if (!empty($product['gallery_images'])) {
    $galleryImages = json_decode($product['gallery_images'], true);
    if (!is_array($galleryImages)) {
        $galleryImages = [];
    }
}

require __DIR__ . '/components/header.php'; ?>

<style>
    .product-image {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    
    .gallery-image {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 6px;
        border: 2px solid #dee2e6;
        margin: 5px;
    }
    
    .info-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    
    .review-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    
    .status-badge {
        font-size: 0.9rem;
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
                            <h1 class="h3 mb-1">Product Details</h1>
                            <p class="text-muted mb-0">View detailed information about this product</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="product-edit-enhanced.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Edit Product
                            </a>
                            <a href="product-management.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Products
                            </a>
                        </div>
                    </div>

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

                    <div class="row">
                        <!-- Product Images -->
                        <div class="col-lg-4">
                            <div class="card info-card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-images me-2"></i>Product Images</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Main Image -->
                                    <div class="text-center mb-3">
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="../assets/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                                <i class="fas fa-image fa-3x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Gallery Images -->
                                    <?php if (!empty($galleryImages)): ?>
                                        <div class="mt-3">
                                            <h6 class="mb-2">Gallery Images</h6>
                                            <div class="d-flex flex-wrap">
                                                <?php foreach ($galleryImages as $image): ?>
                                                    <img src="../assets/products/<?php echo htmlspecialchars($image); ?>" 
                                                         alt="Gallery image" 
                                                         class="gallery-image">
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Information -->
                        <div class="col-lg-8">
                            <div class="card info-card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Product Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td><strong>Name:</strong></td>
                                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>SKU:</strong></td>
                                                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Slug:</strong></td>
                                                    <td><?php echo htmlspecialchars($product['slug']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Category:</strong></td>
                                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Subcategory:</strong></td>
                                                    <td><?php echo htmlspecialchars($product['subcategory_name'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Brand:</strong></td>
                                                    <td><?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Status:</strong></td>
                                                    <td>
                                                        <?php
                                                        switch ($product['status']) {
                                                            case 'active':
                                                                echo '<span class="badge bg-success status-badge">Active</span>';
                                                                break;
                                                            case 'inactive':
                                                                echo '<span class="badge bg-secondary status-badge">Inactive</span>';
                                                                break;
                                                            case 'draft':
                                                                echo '<span class="badge bg-warning status-badge">Draft</span>';
                                                                break;
                                                            case 'out_of_stock':
                                                                echo '<span class="badge bg-danger status-badge">Out of Stock</span>';
                                                                break;
                                                            default:
                                                                echo '<span class="badge bg-light status-badge">Unknown</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td><strong>Price:</strong></td>
                                                    <td>৳<?php echo number_format($product['selling_price'], 2); ?></td>
                                                </tr>
                                                <?php if (!empty($product['discount_price']) && $product['discount_price'] > 0): ?>
                                                    <tr>
                                                        <td><strong>Discount Price:</strong></td>
                                                        <td>৳<?php echo number_format($product['discount_price'], 2); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Savings:</strong></td>
                                                        <td>৳<?php echo number_format($product['selling_price'] - $product['discount_price'], 2); ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                <tr>
                                                    <td><strong>Stock Quantity:</strong></td>
                                                    <td><?php echo $product['stock_quantity']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Min Stock Level:</strong></td>
                                                    <td><?php echo $product['min_stock_level']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Is Featured:</strong></td>
                                                    <td><?php echo $product['is_featured'] ? '<span class="badge bg-warning">Yes</span>' : '<span class="badge bg-light">No</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Is Hot Item:</strong></td>
                                                    <td><?php echo $product['is_hot_item'] ? '<span class="badge bg-danger">Yes</span>' : '<span class="badge bg-light">No</span>'; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Created:</strong></td>
                                                    <td><?php echo date('M j, Y g:i A', strtotime($product['created_at'])); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- SEO Information -->
                                    <div class="mt-4">
                                        <h6><i class="fas fa-search me-2"></i>SEO Information</h6>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Meta Title:</strong><br>
                                                <?php echo htmlspecialchars($product['meta_title'] ?? 'N/A'); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Meta Keywords:</strong><br>
                                                <?php echo htmlspecialchars($product['meta_keywords'] ?? 'N/A'); ?></p>
                                            </div>
                                        </div>
                                        <p><strong>Meta Description:</strong><br>
                                        <?php echo htmlspecialchars($product['meta_description'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Product Descriptions -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card info-card mb-4">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-align-left me-2"></i>Short Description</h6>
                                        </div>
                                        <div class="card-body">
                                            <?php echo !empty($product['short_description']) ? nl2br(htmlspecialchars($product['short_description'])) : '<p class="text-muted">No short description provided.</p>'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card info-card mb-4">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-align-left me-2"></i>Full Description</h6>
                                        </div>
                                        <div class="card-body">
                                            <?php echo !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : '<p class="text-muted">No full description provided.</p>'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Product Reviews -->
                            <div class="card info-card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Recent Reviews</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($reviews)): ?>
                                        <?php foreach ($reviews as $review): ?>
                                            <div class="review-card p-3">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                                                        <div class="text-muted small"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                                                    </div>
                                                    <div>
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <?php if ($i <= $review['rating']): ?>
                                                                <i class="fas fa-star text-warning"></i>
                                                            <?php else: ?>
                                                                <i class="far fa-star text-warning"></i>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                        <span class="ms-1">(<?php echo $review['rating']; ?>/5)</span>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <strong><?php echo htmlspecialchars($review['title'] ?? ''); ?></strong>
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                                </div>
                                                <div class="mt-2">
                                                    <?php if ($review['is_approved']): ?>
                                                        <span class="badge bg-success">Approved</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">No reviews yet for this product.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>
</html>