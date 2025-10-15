<?php
// Enhanced Brand Delete with Validation
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
    header('Location: auto-login.php');
    exit;
}

$db = new MysqliDb(
    settings()['hostname'], 
    settings()['user'], 
    settings()['password'], 
    settings()['database']
);

$brandId = intval($_GET['id'] ?? 0);

if ($brandId <= 0) {
    $_SESSION['error'] = 'Invalid brand ID';
    header('Location: brand-all.php');
    exit;
}

// Get brand data
$db->where('id', $brandId);
$brand = $db->getOne('brands');

if (!$brand) {
    $_SESSION['error'] = 'Brand not found';
    header('Location: brand-all.php');
    exit;
}

// Check if brand has products
$db->where('brand', $brandId);
$productCount = $db->getValue('products', 'COUNT(*)');

// Handle delete confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Re-check product count
        $db->where('brand', $brandId);
        $currentProductCount = $db->getValue('products', 'COUNT(*)');
        
        if ($currentProductCount > 0) {
            throw new Exception("Cannot delete brand. It has {$currentProductCount} product(s) assigned to it.");
        }
        
        // Get brand logo before deletion
        $logoPath = null;
        if (!empty($brand['logo'])) {
            $logoPath = __DIR__ . "/../assets/brands/{$brand['logo']}";
        }
        
        // Delete brand
        $db->where('id', $brandId);
        $result = $db->delete('brands');
        
        if ($result) {
            // Delete associated logo file
            if ($logoPath && file_exists($logoPath)) {
                unlink($logoPath);
            }
            
            $_SESSION['success'] = "Brand '{$brand['name']}' has been deleted successfully!";
            header('Location: brand-all.php');
            exit;
        } else {
            throw new Exception('Failed to delete brand from database');
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: brand-all.php');
        exit;
    }
}
?>

<?php require __DIR__ . '/components/header.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .delete-card {
        border: 2px solid #dc3545;
        border-radius: 12px;
        box-shadow: 0 0 20px rgba(220, 53, 69, 0.2);
    }
    .delete-card .card-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border-radius: 10px 10px 0 0 !important;
        border: none;
    }
    .warning-icon {
        font-size: 4rem;
        color: #dc3545;
        margin-bottom: 1rem;
    }
    .brand-info {
        background: #f8f9fa;
        border-left: 4px solid #dc3545;
        padding: 1rem;
        margin: 1rem 0;
    }
    .product-warning {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 1rem;
        margin: 1rem 0;
    }
    .btn-delete {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: none;
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        color: white;
    }
    .btn-cancel {
        background: #6c757d;
        border: none;
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-cancel:hover {
        background: #5a6268;
        transform: translateY(-2px);
        color: white;
    }
    .brand-logo {
        max-width: 150px;
        max-height: 150px;
        border-radius: 8px;
        border: 2px solid #dee2e6;
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
                    <h1 class="mt-4">Delete Brand</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="brand-all.php">Brands</a></li>
                        <li class="breadcrumb-item active">Delete Brand</li>
                    </ol>

                    <div class="row justify-content-center">
                        <div class="col-xl-8 col-lg-10">
                            <div class="card delete-card">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Brand Confirmation
                                    </h6>
                                </div>
                                <div class="card-body text-center">
                                    <div class="warning-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    
                                    <h4 class="text-danger mb-3">Are you absolutely sure?</h4>
                                    <p class="text-muted mb-4">
                                        This action will permanently delete the brand and cannot be undone.
                                    </p>

                                    <!-- Brand Information -->
                                    <div class="brand-info">
                                        <div class="row align-items-center">
                                            <div class="col-md-3 text-center">
                                                <?php if (!empty($brand['logo']) && file_exists(__DIR__ . "/../assets/brands/{$brand['logo']}")): ?>
                                                    <img src="<?php echo settings()['root']; ?>assets/brands/<?php echo htmlspecialchars($brand['logo']); ?>" 
                                                         alt="Brand Logo" class="brand-logo">
                                                <?php else: ?>
                                                    <div class="placeholder-image bg-light d-flex align-items-center justify-content-center" style="width:100px;height:100px;border-radius:8px;margin:0 auto;">
                                                        <i class="fas fa-image fa-2x text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-9">
                                                <h5 class="text-danger mb-2"><?php echo htmlspecialchars($brand['name']); ?></h5>
                                                <p class="mb-1"><strong>Slug:</strong> <?php echo htmlspecialchars($brand['slug'] ?? 'N/A'); ?></p>
                                                <?php if (!empty($brand['website'])): ?>
                                                    <p class="mb-1"><strong>Website:</strong> 
                                                        <a href="<?php echo htmlspecialchars($brand['website']); ?>" target="_blank">
                                                            <?php echo htmlspecialchars($brand['website']); ?>
                                                        </a>
                                                    </p>
                                                <?php endif; ?>
                                                <p class="mb-1"><strong>Status:</strong> 
                                                    <span class="badge bg-<?php echo $brand['is_active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $brand['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                    <?php if ($brand['is_featured']): ?>
                                                        <span class="badge bg-warning text-dark ms-1">Featured</span>
                                                    <?php endif; ?>
                                                </p>
                                                <p class="mb-1"><strong>Created:</strong> <?php echo date('M d, Y', strtotime($brand['created_at'])); ?></p>
                                                <?php if (!empty($brand['description'])): ?>
                                                    <p class="mb-0"><strong>Description:</strong> <?php echo htmlspecialchars(substr($brand['description'], 0, 100)); ?><?php echo strlen($brand['description']) > 100 ? '...' : ''; ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($productCount > 0): ?>
                                        <!-- Product Warning -->
                                        <div class="product-warning">
                                            <h5 class="text-warning mb-2">
                                                <i class="fas fa-exclamation-triangle me-2"></i>Cannot Delete Brand
                                            </h5>
                                            <p class="mb-2">
                                                This brand has <strong class="text-danger"><?php echo $productCount; ?> product(s)</strong> assigned to it.
                                            </p>
                                            <p class="mb-0 small text-muted">
                                                Please move or delete all products from this brand before attempting to delete it.
                                            </p>
                                        </div>

                                        <!-- Action Buttons - Cannot Delete -->
                                        <div class="mt-4">
                                            <a href="brand-all.php" class="btn btn-cancel me-3">
                                                <i class="fas fa-arrow-left me-2"></i>Back to Brands
                                            </a>
                                            <a href="brand-edit-enhanced.php?id=<?php echo $brandId; ?>" class="btn btn-primary">
                                                <i class="fas fa-edit me-2"></i>Edit Brand
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <!-- Success - Can Delete -->
                                        <div class="alert alert-success">
                                            <h5 class="text-success mb-2">
                                                <i class="fas fa-check-circle me-2"></i>Safe to Delete
                                            </h5>
                                            <p class="mb-0">
                                                This brand has no products assigned to it and can be safely deleted.
                                            </p>
                                        </div>

                                        <!-- Action Buttons - Can Delete -->
                                        <form method="POST" id="deleteForm">
                                            <div class="mt-4">
                                                <a href="brand-all.php" class="btn btn-cancel me-3">
                                                    <i class="fas fa-times me-2"></i>Cancel
                                                </a>
                                                <button type="button" class="btn btn-delete" onclick="confirmFinalDelete()">
                                                    <i class="fas fa-trash me-2"></i>Yes, Delete Brand
                                                </button>
                                            </div>
                                            <input type="hidden" name="confirm_delete" value="1">
                                        </form>
                                    <?php endif; ?>

                                    <!-- Additional Information -->
                                    <div class="mt-4 pt-3 border-top">
                                        <small class="text-muted">
                                            <strong>Note:</strong> Deleting this brand will also remove any associated logo files. 
                                            This action is permanent and cannot be reversed.
                                        </small>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="assets/js/scripts.js"></script>

    <script>
    function confirmFinalDelete() {
        Swal.fire({
            title: 'Final Confirmation',
            html: 'Type <strong>DELETE</strong> to confirm you want to permanently delete this brand.',
            input: 'text',
            inputPlaceholder: 'Type DELETE here',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Delete Brand',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            inputValidator: (value) => {
                if (value !== 'DELETE') {
                    return 'You need to type DELETE to confirm!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting Brand...',
                    text: 'Please wait while we delete the brand',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Submit the form
                document.getElementById('deleteForm').submit();
            }
        });
    }
    </script>
</body>
</html>