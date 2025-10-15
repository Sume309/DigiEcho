<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

// Establish PDO connection
try {
    $pdo = new PDO(
        "mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'],
        settings()['user'],
        settings()['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get brand ID from URL
$brand_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$brand_id) {
    header('Location: brand-all.php');
    exit;
}

// Check if brand exists
$stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
$stmt->execute([$brand_id]);
$brand = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$brand) {
    $_SESSION['error'] = "Brand not found.";
    header('Location: brand-all.php');
    exit;
}

// Check if brand has products
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE brand = ?");
$stmt->execute([$brand_id]);
$product_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($product_count > 0) {
    $_SESSION['error'] = "Cannot delete brand '{$brand['name']}' because it has {$product_count} product(s) associated with it. Please move or delete the products first.";
    header('Location: brand-all.php');
    exit;
}

// If it's a POST request, proceed with deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Delete the brand
        $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
        
        if ($stmt->execute([$brand_id])) {
            // Delete associated logo file if it exists
            if ($brand['logo']) {
                $logo_path = settings()['physical_path'] . '/assets/brands/' . $brand['logo'];
                if (file_exists($logo_path)) {
                    unlink($logo_path);
                }
            }
            
            $_SESSION['success'] = "Brand '{$brand['name']}' has been deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete brand.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    
    header('Location: brand-all.php');
    exit;
}
?>
<?php require __DIR__ . '/components/header.php'; ?>

</head>

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-exclamation-triangle"></i> Delete Brand
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-warning"></i>
                                        <strong>Warning!</strong> This action cannot be undone.
                                    </div>
                                    
                                    <p>Are you sure you want to delete the following brand?</p>
                                    
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <?php if ($brand['logo']): ?>
                                                        <img src="<?= settings()['root'] ?>/assets/brands/<?= $brand['logo'] ?>" 
                                                             class="img-thumbnail" width="80" height="80">
                                                    <?php else: ?>
                                                        <div class="bg-secondary d-flex align-items-center justify-content-center" 
                                                             style="width: 80px; height: 80px;">
                                                            <i class="fas fa-image text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-9">
                                                    <h6 class="mb-1"><?= htmlspecialchars($brand['name']) ?></h6>
                                                    <p class="text-muted mb-1">
                                                        <small>Created: <?= htmlspecialchars($brand['created_at']) ?></small>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <form method="POST" class="d-inline">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Yes, Delete Brand
                                            </button>
                                        </form>
                                        <a href="brand-all.php" class="btn btn-secondary ml-2">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <!-- footer -->
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>
    <script src="<?= settings()['adminpage'] ?>assets/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
</body>

</html>
