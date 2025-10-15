<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('HTTP/1.1 503 Service Unavailable');
    exit;
}

$db = new MysqliDb(
    settings()['hostname'], 
    settings()['user'], 
    settings()['password'], 
    settings()['database']
);
$errors = [];
$success = '';

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$category_id) {
    $_SESSION['error'] = 'Invalid category ID.';
    header('Location: category-management.php');
    exit;
}

// Fetch category data
$db->where('id', $category_id);
$category = $db->getOne('categories');

if (!$category) {
    $_SESSION['error'] = 'Category not found.';
    header('Location: category-management.php');
    exit;
}

// Check for child categories
$db->where('parent_id', $category_id);
$childCategories = $db->get('categories', null, ['id', 'name', 'is_active']);

// Check for products directly in this category
$db->where('category_id', $category_id);
$directProducts = $db->get('products', null, ['id', 'name', 'image'], null, 10); // Limit to 10 for display

$db->where('category_id', $category_id);
$totalDirectProducts = $db->getValue('products', 'COUNT(*)');

// Check for products in child categories
$totalChildProducts = 0;
$childProductSamples = [];
if (!empty($childCategories)) {
    foreach ($childCategories as $child) {
        $db->where('category_id', $child['id']);
        $childCount = $db->getValue('products', 'COUNT(*)');
        $totalChildProducts += $childCount;
        
        if ($childCount > 0 && count($childProductSamples) < 5) {
            $db->where('category_id', $child['id']);
            $products = $db->get('products', 3, ['id', 'name', 'image']);
            foreach ($products as $product) {
                $product['category_name'] = $child['name'];
                $childProductSamples[] = $product;
            }
        }
    }
}

$totalProducts = $totalDirectProducts + $totalChildProducts;

// Get other categories for moving products
$db->where('id', $category_id, '!=');
$db->where('is_active', 1);
$otherCategories = $db->get('categories', null, ['id', 'name', 'parent_id'], 'name ASC');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'move_products' && isset($_POST['target_category'])) {
        try {
            $db->startTransaction();
            
            $targetCategoryId = (int)$_POST['target_category'];
            
            // Move direct products
            if ($totalDirectProducts > 0) {
                $updateData = ['category_id' => $targetCategoryId, 'updated_at' => date('Y-m-d H:i:s')];
                $db->where('category_id', $category_id);
                $db->update('products', $updateData);
            }
            
            // Move products from child categories
            if (!empty($childCategories)) {
                foreach ($childCategories as $child) {
                    $updateData = ['category_id' => $targetCategoryId, 'updated_at' => date('Y-m-d H:i:s')];
                    $db->where('category_id', $child['id']);
                    $db->update('products', $updateData);
                }
                
                // Delete child categories
                $db->where('parent_id', $category_id);
                $db->delete('categories');
            }
            
            $db->commit();
            
            $_SESSION['success'] = 'All products and subcategories have been moved/deleted successfully.';
            header("Location: category-delete-enhanced.php?id=$category_id");
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    } elseif ($action === 'delete_category') {
        // Double-check for dependencies
        $db->where('parent_id', $category_id);
        $currentChildCount = $db->getValue('categories', 'COUNT(*)');
        
        $db->where('category_id', $category_id);
        $currentProductCount = $db->getValue('products', 'COUNT(*)');
        
        if ($currentChildCount > 0 || $currentProductCount > 0) {
            $errors[] = 'Cannot delete category. It still has dependencies.';
        } else {
            try {
                $db->startTransaction();
                
                // Delete the category
                $db->where('id', $category_id);
                $deleted = $db->delete('categories');
                
                if ($deleted) {
                    // Delete associated image file
                    if (!empty($category['image'])) {
                        $imagePath = __DIR__ . "/../assets/categories/{$category['image']}";
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                        // Also delete thumbnail if exists
                        $thumbPath = __DIR__ . "/../assets/categories/thumb_{$category['image']}";
                        if (file_exists($thumbPath)) {
                            unlink($thumbPath);
                        }
                    }
                    
                    $db->commit();
                    $_SESSION['success'] = "Category '{$category['name']}' has been deleted successfully.";
                    header('Location: category-management.php');
                    exit;
                } else {
                    throw new Exception('Failed to delete category.');
                }
            } catch (Exception $e) {
                $db->rollback();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
?>
<?php require __DIR__ . '/components/header.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mt-4">Delete Category</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="category-management.php">Categories</a></li>
                            <li class="breadcrumb-item active">Delete Category</li>
                        </ol>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6><i class="fas fa-exclamation-triangle"></i> Error:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Category: <?= htmlspecialchars($category['name']) ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($totalProducts > 0 || !empty($childCategories)): ?>
                                        <!-- Category has dependencies -->
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Cannot Delete!</strong> This category has dependencies that must be resolved first:
                                        </div>

                                        <!-- Statistics Cards -->
                                        <div class="row mb-4">
                                            <?php if ($totalDirectProducts > 0): ?>
                                                <div class="col-md-4">
                                                    <div class="card bg-primary text-white">
                                                        <div class="card-body text-center">
                                                            <i class="fas fa-box fa-2x mb-2"></i>
                                                            <h4><?= $totalDirectProducts ?></h4>
                                                            <small>Direct Products</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($childCategories)): ?>
                                                <div class="col-md-4">
                                                    <div class="card bg-info text-white">
                                                        <div class="card-body text-center">
                                                            <i class="fas fa-folder fa-2x mb-2"></i>
                                                            <h4><?= count($childCategories) ?></h4>
                                                            <small>Child Categories</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($totalChildProducts > 0): ?>
                                                <div class="col-md-4">
                                                    <div class="card bg-warning text-white">
                                                        <div class="card-body text-center">
                                                            <i class="fas fa-boxes fa-2x mb-2"></i>
                                                            <h4><?= $totalChildProducts ?></h4>
                                                            <small>Products in Child Categories</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Child Categories Display -->
                                        <?php if (!empty($childCategories)): ?>
                                            <div class="mb-4">
                                                <h6><i class="fas fa-folder me-2"></i>Child Categories:</h6>
                                                <div class="row">
                                                    <?php foreach ($childCategories as $child): ?>
                                                        <div class="col-md-6 mb-2">
                                                            <div class="card border-info">
                                                                <div class="card-body p-3">
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <span><i class="fas fa-folder me-1"></i> <?= htmlspecialchars($child['name']) ?></span>
                                                                        <span class="badge <?= $child['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                                            <?= $child['is_active'] ? 'Active' : 'Inactive' ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Product Samples Display -->
                                        <?php if (!empty($directProducts) || !empty($childProductSamples)): ?>
                                            <div class="mb-4">
                                                <h6><i class="fas fa-box me-2"></i>Sample Products:</h6>
                                                <div class="row">
                                                    <?php foreach (array_merge(array_slice($directProducts, 0, 5), array_slice($childProductSamples, 0, 5)) as $product): ?>
                                                        <div class="col-md-6 mb-2">
                                                            <div class="card">
                                                                <div class="card-body p-3">
                                                                    <div class="d-flex align-items-center">
                                                                        <?php if (!empty($product['image'])): ?>
                                                                            <<img src="<?= settings()['root'] ?>/assets/products/<?= $product['image'] ?>"
                                                                                 width="40" height="40" class="rounded me-2">
                                                                        <?php else: ?>
                                                                            <div class="bg-light d-flex align-items-center justify-content-center me-2" 
                                                                                 style="width: 40px; height: 40px;">
                                                                                <i class="fas fa-image text-muted"></i>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <div>
                                                                            <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                                                            <?php if (isset($product['category_name'])): ?>
                                                                                <small class="text-muted">From: <?= htmlspecialchars($product['category_name']) ?></small>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php if ($totalProducts > 10): ?>
                                                    <p class="text-muted">... and <?= $totalProducts - 10 ?> more products</p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Move Products Form -->
                                        <?php if (!empty($otherCategories)): ?>
                                            <div class="card bg-light">
                                                <div class="card-header">
                                                    <h6 class="mb-0"><i class="fas fa-arrows-alt me-2"></i>Move All Products to Another Category</h6>
                                                </div>
                                                <div class="card-body">
                                                    <form method="POST" id="moveProductsForm">
                                                        <input type="hidden" name="action" value="move_products">
                                                        <div class="mb-3">
                                                            <label for="target_category" class="form-label">Select Target Category:</label>
                                                            <select name="target_category" id="target_category" class="form-select" required>
                                                                <option value="">-- Select a category --</option>
                                                                <?php foreach ($otherCategories as $cat): ?>
                                                                    <option value="<?= $cat['id'] ?>">
                                                                        <?= $cat['parent_id'] ? '↳ ' : '' ?><?= htmlspecialchars($cat['name']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <button type="submit" class="btn btn-warning">
                                                            <i class="fas fa-arrows-alt me-1"></i> Move All Products & Delete Child Categories
                                                        </button>
                                                    </form>
                                                    <div class="alert alert-info mt-3 mb-0">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        <strong>Note:</strong> This will:
                                                        <ul class="mb-0 mt-2">
                                                            <li>Move all <?= $totalProducts ?> products to the selected category</li>
                                                            <li>Delete all <?= count($childCategories) ?> child categories</li>
                                                            <li>Allow you to then delete this category</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                No other active categories available. Please create another category first or manually move the products.
                                            </div>
                                        <?php endif; ?>

                                    <?php else: ?>
                                        <!-- No dependencies - can delete -->
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Warning!</strong> This action cannot be undone.
                                        </div>

                                        <h6>You are about to delete the following category:</h6>
                                        
                                        <div class="card bg-light mb-4">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <?php if (!empty($category['image'])): ?>
                                                            <img src="<?= settings()['root'] ?>/assets/categories/<?= $category['image'] ?>" 
                                                                 class="rounded" width="80" height="80">
                                                        <?php else: ?>
                                                            <div class="bg-secondary d-flex align-items-center justify-content-center rounded" 
                                                                 style="width: 80px; height: 80px;">
                                                                <i class="fas fa-image text-white fa-2x"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col">
                                                        <h5 class="mb-1"><?= htmlspecialchars($category['name']) ?></h5>
                                                        <p class="text-muted mb-1">
                                                            <small><strong>Slug:</strong> <?= htmlspecialchars($category['slug']) ?></small>
                                                        </p>
                                                        <?php if (!empty($category['description'])): ?>
                                                            <p class="mb-2">
                                                                <small><?= htmlspecialchars($category['description']) ?></small>
                                                            </p>
                                                        <?php endif; ?>
                                                        <span class="badge <?= $category['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                            <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                        <?php if ($category['parent_id']): ?>
                                                            <span class="badge bg-info">Subcategory</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                                <i class="fas fa-trash me-1"></i> Yes, Delete Category
                                            </button>
                                            <a href="category-management.php" class="btn btn-secondary">
                                                <i class="fas fa-times me-1"></i> Cancel
                                            </a>
                                        </div>

                                        <form method="POST" id="deleteForm" style="display: none;">
                                            <input type="hidden" name="action" value="delete_category">
                                        </form>
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

    <script src="<?= settings()['adminpage'] ?>assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.js"></script>

    <script>
    function confirmDelete() {
        Swal.fire({
            title: 'Are you sure?',
            text: "This category will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm').submit();
            }
        });
    }

    document.getElementById('moveProductsForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const targetCategory = document.getElementById('target_category').value;
        if (!targetCategory) {
            Swal.fire('Error', 'Please select a target category.', 'error');
            return;
        }

        Swal.fire({
            title: 'Move Products?',
            text: "This will move all products and delete child categories. This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, move products!'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });
    </script>
</body>
</html>