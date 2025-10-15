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

// Fetch existing category data
$db->where('id', $category_id);
$category = $db->getOne('categories');

if (!$category) {
    $_SESSION['error'] = 'Category not found.';
    header('Location: category-management.php');
    exit;
}

// Initialize form data
$formData = [
    'name' => $category['name'],
    'slug' => $category['slug'],
    'description' => $category['description'] ?? '',
    'is_active' => $category['is_active'],
    'parent_id' => $category['parent_id'] ?? 0
];

// Get parent categories
$db->where('is_active', 1);
$db->where('id', $category_id, '!=');
$parentCategories = $db->get('categories', null, ['id', 'name'], 'name ASC');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['slug'] = trim($_POST['slug'] ?? '');
    $formData['description'] = trim($_POST['description'] ?? '');
    $formData['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $formData['parent_id'] = intval($_POST['parent_id'] ?? 0);
    
    // Validation
    if (empty($formData['name'])) {
        $errors[] = "Category name is required.";
    } elseif (strlen($formData['name']) < 2) {
        $errors[] = "Category name must be at least 2 characters long.";
    } elseif (strlen($formData['name']) > 100) {
        $errors[] = "Category name must not exceed 100 characters.";
    }
    
    // Check for duplicate name
    if (!empty($formData['name'])) {
        $db->where('LOWER(name)', strtolower($formData['name']));
        $db->where('id', $category_id, '!=');
        if ($db->has('categories')) {
            $errors[] = "A category with this name already exists.";
        }
    }
    
    // Generate slug if empty
    if (empty($formData['slug'])) {
        $formData['slug'] = generateSlug($formData['name']);
    } else {
        $formData['slug'] = generateSlug($formData['slug']);
    }
    
    // Prevent circular references
    if ($formData['parent_id'] == $category_id) {
        $errors[] = "A category cannot be its own parent.";
    }
    
    // Handle image upload
    $updatedImage = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleImageUpload($_FILES['image']);
        if ($uploadResult['success']) {
            $updatedImage = $uploadResult['filename'];
            // Delete old image
            if (!empty($category['image'])) {
                $oldImagePath = __DIR__ . "/../assets/categories/{$category['image']}";
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
        } else {
            $errors[] = $uploadResult['error'];
        }
    }
    
    // Update category if no errors
    if (empty($errors)) {
        try {
            $categoryData = [
                'name' => $formData['name'],
                'slug' => $formData['slug'],
                'description' => $formData['description'],
                'is_active' => $formData['is_active'],
                'parent_id' => $formData['parent_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($updatedImage !== null) {
                $categoryData['image'] = $updatedImage;
            }
            
            $db->where('id', $category_id);
            $updated = $db->update('categories', $categoryData);
            
            if ($updated) {
                $_SESSION['success'] = 'Category updated successfully!';
                // Add parameter to indicate category was updated
                header('Location: category-management.php?updated=1&category_id=' . $category_id);
                exit;
            } else {
                $errors[] = 'Failed to update category.';
            }
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

function generateSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

function handleImageUpload($file) {
    $uploadDir = __DIR__ . '/../assets/categories/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Only JPEG, PNG, GIF, and WebP images are allowed.'];
    }
    
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Image size must not exceed 5MB.'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to upload image.'];
    }
}

if (isset($_GET['success']) && !empty($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
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
                        <h1 class="mt-4">Edit Category</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="category-management.php">Categories</a></li>
                            <li class="breadcrumb-item active">Edit Category</li>
                        </ol>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-xl-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-edit me-1"></i>
                                    Category Information
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="name" name="name"
                                                value="<?php echo htmlspecialchars($formData['name']); ?>"
                                                required maxlength="100" placeholder="Enter category name">
                                        </div>

                                        <div class="mb-3">
                                            <label for="slug" class="form-label">Slug</label>
                                            <input type="text" class="form-control" id="slug" name="slug"
                                                value="<?php echo htmlspecialchars($formData['slug']); ?>"
                                                placeholder="category-slug">
                                            <div class="form-text">URL-friendly version of the name.</div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="parent_id" class="form-label">Parent Category</label>
                                                    <select class="form-select" id="parent_id" name="parent_id">
                                                        <option value="0">-- No Parent (Main Category) --</option>
                                                        <?php foreach ($parentCategories as $parent): ?>
                                                            <option value="<?php echo $parent['id']; ?>" 
                                                                <?php echo ($formData['parent_id'] == $parent['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($parent['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="is_active" class="form-label">Status</label>
                                                    <select class="form-select" id="is_active" name="is_active">
                                                        <option value="1" <?php echo ($formData['is_active'] == 1) ? 'selected' : ''; ?>>Active</option>
                                                        <option value="0" <?php echo ($formData['is_active'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                    <div class="mt-2">
                                                        <span class="badge" id="statusPreview">
                                                            <?php echo ($formData['is_active'] == 1) ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                        <small class="text-muted d-block mt-1" id="statusDescription">
                                                            <?php echo ($formData['is_active'] == 1) ? 'Category is visible to customers' : 'Category is hidden from customers'; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="4"
                                                maxlength="1000" placeholder="Category description..."><?php echo htmlspecialchars($formData['description']); ?></textarea>
                                        </div>

                                        <div class="mt-4 d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i> Update Category
                                            </button>
                                            <a href="category-management.php" class="btn btn-secondary">
                                                <i class="fas fa-times me-1"></i> Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-image me-1"></i>
                                    Category Image
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($category['image'])): ?>
                                        <div class="text-center mb-3">
                                            <img src="<?= settings()['root'] ?>/assets/categories/<?= $category['image'] ?>" 
                                                 class="img-fluid rounded" style="max-width: 200px; max-height: 200px;">
                                            <div class="mt-2">
                                                <small class="text-muted">Current image</small>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Upload New Image</label>
                                        <input type="file" class="form-control" id="image" name="image"
                                            accept="image/jpeg,image/png,image/gif,image/webp">
                                        <div class="form-text">
                                            Supported formats: JPEG, PNG, GIF, WebP<br>
                                            Maximum size: 5MB
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Category Guidelines
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            Use clear, descriptive names
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            Keep names concise but informative
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            Add relevant descriptions for SEO
                                        </li>
                                        <li class="mb-0">
                                            <i class="fas fa-check text-success me-2"></i>
                                            Use high-quality images
                                        </li>
                                    </ul>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-generate slug from name
        const nameField = document.getElementById('name');
        const slugField = document.getElementById('slug');
        const statusSelect = document.getElementById('is_active');
        const statusPreview = document.getElementById('statusPreview');
        const statusDescription = document.getElementById('statusDescription');
        
        nameField.addEventListener('input', function() {
            if (slugField.value === '' || slugField.dataset.generated === 'true') {
                const slug = generateSlug(this.value);
                slugField.value = slug;
                slugField.dataset.generated = 'true';
            }
        });
        
        // Real-time status preview
        statusSelect.addEventListener('change', function() {
            const isActive = this.value === '1';
            
            // Update preview badge
            statusPreview.textContent = isActive ? 'Active' : 'Inactive';
            statusPreview.className = isActive ? 'badge bg-success' : 'badge bg-secondary';
            
            // Update description
            statusDescription.textContent = isActive ? 
                'Category is visible to customers' : 
                'Category is hidden from customers';
            
            // Add visual feedback
            statusPreview.style.transform = 'scale(1.1)';
            setTimeout(() => {
                statusPreview.style.transform = 'scale(1)';
            }, 200);
        });
        
        // Add quick status update button
        const statusContainer = statusSelect.parentElement;
        const quickUpdateBtn = document.createElement('button');
        quickUpdateBtn.type = 'button';
        quickUpdateBtn.className = 'btn btn-outline-primary btn-sm mt-2';
        quickUpdateBtn.innerHTML = '<i class="fas fa-bolt me-1"></i> Quick Status Update';
        quickUpdateBtn.onclick = quickStatusUpdate;
        statusContainer.appendChild(quickUpdateBtn);
        
        // Success message
        <?php if ($success): ?>
        Swal.fire({
            position: "top-end",
            icon: "success",
            title: "<?php echo htmlspecialchars($success); ?>",
            showConfirmButton: false,
            timer: 1500
        });
        <?php endif; ?>
    });

    function generateSlug(text) {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
    
    function quickStatusUpdate() {
        const categoryId = <?php echo $category_id; ?>;
        const newStatus = document.getElementById('is_active').value;
        const statusText = newStatus === '1' ? 'activate' : 'deactivate';
        
        Swal.fire({
            title: 'Quick Status Update',
            text: `Are you sure you want to ${statusText} this category?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: `Yes, ${statusText} it!`,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('category-management.php?action=toggle_status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${categoryId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to update status');
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Status Updated!',
                    text: 'The category status has been updated successfully. Redirecting to category list...',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Redirect back to category management with update indicator
                    window.location.href = `category-management.php?updated=1&category_id=${categoryId}&quick_status=1`;
                });
            }
        });
    }
    </script>
</body>
</html>