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

$errors = [];
$success = '';

// Get brand ID from URL
$brand_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$brand_id) {
    header('Location: brand-all.php');
    exit;
}

// Fetch existing brand data
$stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
$stmt->execute([$brand_id]);
$brand = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$brand) {
    header('Location: brand-all.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    // Validation
    if (empty($name)) {
        $errors[] = "Brand name is required.";
    } elseif (strlen($name) < 2) {
        $errors[] = "Brand name must be at least 2 characters long.";
    } elseif (strlen($name) > 100) {
        $errors[] = "Brand name must not exceed 100 characters.";
    } else {
        // Check if brand name already exists (excluding current brand)
        $check_name = $pdo->prepare("SELECT id FROM brands WHERE name = ? AND id != ?");
        $check_name->execute([$name, $brand_id]);
        if ($check_name->fetch()) {
            $errors[] = "A brand with this name already exists.";
        }
    }

    // Handle image upload
    $logo_name = $brand['logo']; // Keep existing logo by default
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['logo']['type'], $allowed_types)) {
            $errors[] = "Only JPEG, PNG, GIF, and WebP images are allowed.";
        } elseif ($_FILES['logo']['size'] > $max_size) {
            $errors[] = "Logo size must not exceed 5MB.";
        } else {
            $upload_dir = settings()['physical_path'] . '/assets/brands/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if (!is_writable($upload_dir)) {
                $errors[] = "Upload directory is not writable.";
            } else {
                $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $new_logo_name = uniqid() . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_logo_name;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                    // Delete old logo if it exists
                    if ($brand['logo'] && file_exists($upload_dir . $brand['logo'])) {
                        unlink($upload_dir . $brand['logo']);
                    }
                    $logo_name = $new_logo_name;
                } else {
                    $errors[] = "Failed to upload logo.";
                }
            }
        }
    }

    // Update brand if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE brands SET name = ?, logo = ? WHERE id = ?");
            if ($stmt->execute([$name, $logo_name, $brand_id])) {
                $success = "Brand updated successfully!";
                // Refresh brand data
                $stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
                $stmt->execute([$brand_id]);
                $brand = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $errors[] = "Failed to update brand.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
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
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6>Please fix the following errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>×</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <script>
                        Swal.fire({
                            position: "top-end",
                            icon: "success",
                            title: "<?php echo htmlspecialchars($success); ?>",
                            showConfirmButton: false,
                            timer: 1500
                        });
                    </script>
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between">
                                    <h5 class="card-title mb-0">Edit Brand</h5>
                                    <a href="brand-all.php" class="btn btn-primary ml-auto">
                                        <i class="fas fa-arrow-left"></i> Back to Brands
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="name">Brand Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="<?php echo htmlspecialchars($brand['name']); ?>"
                                            required maxlength="100">
                                        <small class="form-text text-muted">Enter a unique brand name (2-100 characters)</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="logo">Brand Logo</label>
                                        <?php if ($brand['logo']): ?>
                                            <div class="mb-2">
                                                <img src="<?= settings()['root'] ?>/assets/brands/<?= $brand['logo'] ?>" 
                                                     width="100" height="100" class="img-thumbnail">
                                                <p class="small text-muted">Current logo</p>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control-file" id="logo" name="logo"
                                            accept="image/jpeg,image/png,image/gif,image/webp"
                                            onchange="previewImage(event)">
                                        <small class="form-text text-muted">
                                            Leave empty to keep current logo. Supported formats: JPEG, PNG, GIF, WebP. Max size: 5MB
                                        </small>
                                        <div class="mt-2">
                                            <img id="logo-preview" src="#"
                                                style="max-width: 150px; max-height: 150px; display: none;"
                                                alt="Logo Preview">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="created_at">Created At</label>
                                        <input type="text" class="form-control" id="created_at" name="created_at"
                                            value="<?php echo htmlspecialchars($brand['created_at']); ?>" readonly>
                                        <small class="form-text text-muted">Brand creation date</small>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Brand
                                        </button>
                                        <a href="brand-all.php" class="btn btn-secondary ml-2">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-info-circle"></i> Brand Guidelines
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        Use clear, descriptive names
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        Keep names concise but informative
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        Add relevant descriptions for SEO
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        Use high-quality logos (square format works best)
                                    </li>
                                    <li class="mb-0">
                                        <i class="fas fa-check text-success"></i>
                                        Set status to active when ready to display
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-image"></i> Logo Tips
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-1">• Recommended size: 300x300px</li>
                                    <li class="mb-1">• Format: PNG or JPG</li>
                                    <li class="mb-1">• Max file size: 5MB</li>
                                    <li class="mb-0">• Use transparent backgrounds for PNG</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>
    <script src="<?= settings()['adminpage'] ?>assets/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="<?= settings()['adminpage'] ?>assets/demo/chart-area-demo.js"></script>
    <script src="<?= settings()['adminpage'] ?>assets/demo/chart-bar-demo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
    <script src="<?= settings()['adminpage'] ?>assets/js/datatables-simple-demo.js"></script>
    <script>
        function previewImage(event) {
            const input = event.target;
            const preview = document.getElementById('logo-preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
