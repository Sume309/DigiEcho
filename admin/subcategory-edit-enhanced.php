<?php
// Enhanced Sub-Category Edit Form
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

$success = '';
$error = '';
$subcategoryId = intval($_GET['id'] ?? 0);

if ($subcategoryId <= 0) {
    $_SESSION['error'] = 'Invalid subcategory ID';
    header('Location: subcategory-all.php');
    exit;
}

// Get subcategory data
$db->where('id', $subcategoryId);
$subcategory = $db->getOne('subcategories');

if (!$subcategory) {
    $_SESSION['error'] = 'Subcategory not found';
    header('Location: subcategory-all.php');
    exit;
}

// Initialize form data with existing values
$formData = [
    'name' => $subcategory['name'],
    'slug' => $subcategory['slug'],
    'description' => $subcategory['description'] ?? '',
    'category_id' => $subcategory['category_id'],
    'meta_title' => $subcategory['meta_title'] ?? '',
    'meta_description' => $subcategory['meta_description'] ?? '',
    'meta_keywords' => $subcategory['meta_keywords'] ?? '',
    'sort_order' => $subcategory['sort_order'] ?? 0,
    'is_active' => $subcategory['is_active'],
    'is_featured' => $subcategory['is_featured'] ?? 0
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $meta_title = trim($_POST['meta_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $meta_keywords = trim($_POST['meta_keywords'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        // Store form data for redisplay if needed
        $formData = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'category_id' => $category_id,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'sort_order' => $sort_order,
            'is_active' => $is_active,
            'is_featured' => $is_featured
        ];
        
        // Validation
        if (empty($name)) {
            throw new Exception('Sub-category name is required');
        }
        
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }
        
        if ($category_id <= 0) {
            throw new Exception('Please select a parent category');
        }
        
        // Check if category exists
        $db->where('id', $category_id);
        $category = $db->getOne('categories', ['id', 'name']);
        if (!$category) {
            throw new Exception('Selected category does not exist');
        }
        
        // Check for duplicate name within the same category (exclude current subcategory)
        $duplicateCheck = $db->rawQueryOne(
            'SELECT s.id, s.name, c.name as category_name FROM subcategories s LEFT JOIN categories c ON s.category_id = c.id WHERE s.name = ? AND s.category_id = ? AND s.id != ?', 
            [$name, $category_id, $subcategoryId]
        );
        if ($duplicateCheck) {
            throw new Exception("A subcategory with this name '{$name}' already exists in the '{$duplicateCheck['category_name']}' category.");
        }
        
        // Check for duplicate slug (exclude current subcategory)
        $db->where('slug', $slug);
        $db->where('id', $subcategoryId, '!=');
        $existingSlug = $db->getOne('subcategories', ['id']);
        if ($existingSlug) {
            throw new Exception('Sub-category slug already exists');
        }
        
        // Handle image upload
        $imageName = $subcategory['image']; // Keep existing image by default
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/subcategories/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = $_FILES['image']['type'];
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed');
            }
            
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($_FILES['image']['size'] > $maxSize) {
                throw new Exception('Image size too large. Maximum 5MB allowed');
            }
            
            // Delete old image
            if (!empty($subcategory['image'])) {
                $oldImagePath = $uploadDir . $subcategory['image'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = time() . '_' . uniqid() . '.' . $extension;
            $uploadPath = $uploadDir . $imageName;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                throw new Exception('Failed to upload image');
            }
        }
        
        // Update subcategory
        $data = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'category_id' => $category_id,
            'image' => $imageName,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'sort_order' => $sort_order,
            'is_active' => $is_active,
            'is_featured' => $is_featured,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $db->where('id', $subcategoryId);
        $result = $db->update('subcategories', $data);
        
        if ($result) {
            $_SESSION['success'] = "Sub-category '{$name}' has been updated successfully!";
            header('Location: subcategory-all.php');
            exit;
        } else {
            throw new Exception('Failed to update sub-category');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get categories for dropdown
$categories = $db->where('is_active', 1)->orderBy('name', 'ASC')->get('categories', null, ['id', 'name']);
?>

<?php require __DIR__ . '/components/header.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .card-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border-radius: 12px 12px 0 0 !important;
        border: none;
    }
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 0.75rem;
        transition: all 0.3s ease;
    }
    .form-control:focus, .form-select:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    .btn {
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
    }
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }
    .current-image {
        max-width: 150px;
        max-height: 150px;
        border-radius: 8px;
        border: 2px solid #dee2e6;
    }
    .image-preview {
        max-width: 200px;
        max-height: 200px;
        border-radius: 8px;
        border: 2px dashed #dee2e6;
        padding: 1rem;
        text-align: center;
        background: #f8f9fa;
    }
    .required {
        color: #dc3545;
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
                    <h1 class="mt-4">Edit Sub-Category</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="subcategory-all.php">Sub-Categories</a></li>
                        <li class="breadcrumb-item active">Edit Sub-Category</li>
                    </ol>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row justify-content-center">
                        <div class="col-xl-10 col-lg-12">
                            <div class="card">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-edit me-2"></i>Edit Sub-Category: <?php echo htmlspecialchars($subcategory['name']); ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data" id="subcategoryForm">
                                        <div class="row">
                                            <!-- Basic Information -->
                                            <div class="col-lg-8">
                                                <h5 class="mb-3"><i class="fas fa-info-circle text-success me-2"></i>Basic Information</h5>
                                                
                                                <div class="mb-3">
                                                    <label for="name" class="form-label">Sub-Category Name <span class="required">*</span></label>
                                                    <input type="text" class="form-control" id="name" name="name" 
                                                           value="<?php echo htmlspecialchars($formData['name']); ?>" 
                                                           placeholder="Enter sub-category name" required maxlength="100">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="slug" class="form-label">URL Slug</label>
                                                    <input type="text" class="form-control" id="slug" name="slug" 
                                                           value="<?php echo htmlspecialchars($formData['slug']); ?>" 
                                                           placeholder="auto-generated-slug" maxlength="100">
                                                    <small class="form-text text-muted">Leave empty to auto-generate from name</small>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="category_id" class="form-label">Parent Category <span class="required">*</span></label>
                                                    <select class="form-select" id="category_id" name="category_id" required>
                                                        <option value="">Select Parent Category</option>
                                                        <?php foreach ($categories as $category): ?>
                                                            <option value="<?php echo $category['id']; ?>" 
                                                                    <?php echo $formData['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($category['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="description" class="form-label">Description</label>
                                                    <textarea class="form-control" id="description" name="description" rows="4" 
                                                              placeholder="Enter sub-category description" maxlength="500"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="sort_order" class="form-label">Sort Order</label>
                                                            <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                                                   value="<?php echo $formData['sort_order']; ?>" min="0" max="999">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Status Options</label>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                                       <?php echo $formData['is_active'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="is_active">
                                                                    Active (visible to customers)
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                                                       <?php echo $formData['is_featured'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="is_featured">
                                                                    Featured (show in highlights)
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Image and Meta -->
                                            <div class="col-lg-4">
                                                <h5 class="mb-3"><i class="fas fa-image text-success me-2"></i>Image & SEO</h5>
                                                
                                                <!-- Current Image -->
                                                <?php if (!empty($subcategory['image']) && file_exists(__DIR__ . "/../assets/subcategories/{$subcategory['image']}")): ?>
                                                    <div class="mb-3">
                                                        <label class="form-label">Current Image</label>
                                                        <div class="text-center">
                                                            <img src="<?php echo settings()['root']; ?>assets/subcategories/<?php echo htmlspecialchars($subcategory['image']); ?>" 
                                                                 alt="Current Image" class="current-image">
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="mb-3">
                                                    <label for="image" class="form-label">New Sub-Category Image</label>
                                                    <input type="file" class="form-control" id="image" name="image" 
                                                           accept="image/jpeg,image/png,image/gif,image/webp">
                                                    <small class="form-text text-muted">JPEG, PNG, GIF, WebP (Max: 5MB)</small>
                                                </div>

                                                <!-- SEO Meta Information -->
                                                <div class="card bg-light mt-4">
                                                    <div class="card-header bg-secondary text-white py-2">
                                                        <h6 class="mb-0"><i class="fas fa-search me-2"></i>SEO Meta Data</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="mb-3">
                                                            <label for="meta_title" class="form-label">Meta Title</label>
                                                            <input type="text" class="form-control form-control-sm" id="meta_title" 
                                                                   name="meta_title" value="<?php echo htmlspecialchars($formData['meta_title']); ?>" 
                                                                   placeholder="SEO title" maxlength="60">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="meta_description" class="form-label">Meta Description</label>
                                                            <textarea class="form-control form-control-sm" id="meta_description" 
                                                                      name="meta_description" rows="3" placeholder="SEO description" 
                                                                      maxlength="160"><?php echo htmlspecialchars($formData['meta_description']); ?></textarea>
                                                        </div>

                                                        <div class="mb-0">
                                                            <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                                            <input type="text" class="form-control form-control-sm" id="meta_keywords" 
                                                                   name="meta_keywords" value="<?php echo htmlspecialchars($formData['meta_keywords']); ?>" 
                                                                   placeholder="keyword1, keyword2, keyword3" maxlength="200">
                                                            <small class="form-text text-muted">Separate keywords with commas</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <div class="d-flex justify-content-between">
                                            <a href="subcategory-all.php" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left me-2"></i>Back to Sub-Categories
                                            </a>
                                            <div>
                                                <button type="button" class="btn btn-outline-danger me-2" onclick="confirmDelete()">
                                                    <i class="fas fa-trash me-2"></i>Delete Sub-Category
                                                </button>
                                                <button type="submit" class="btn btn-success" id="submitBtn">
                                                    <i class="fas fa-save me-2"></i>Update Sub-Category
                                                </button>
                                            </div>
                                        </div>
                                    </form>
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
    $(document).ready(function() {
        // Auto-generate slug from name
        $('#name').on('input', function() {
            if (!$('#slug').data('manual-edit')) {
                const slug = generateSlug($(this).val());
                $('#slug').val(slug);
            }
        });
        
        // Mark slug as manually edited
        $('#slug').on('input', function() {
            $(this).data('manual-edit', true);
        });
        
        // Form submission
        $('#subcategoryForm').on('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                submitForm();
            }
        });
    });

    function generateSlug(text) {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9 -]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    function validateForm() {
        const name = $('#name').val().trim();
        const categoryId = $('#category_id').val();
        
        if (name === '') {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Sub-category name is required'
            });
            $('#name').focus();
            return false;
        }
        
        if (categoryId === '') {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please select a parent category'
            });
            $('#category_id').focus();
            return false;
        }
        
        return true;
    }

    function submitForm() {
        const submitBtn = $('#submitBtn');
        const originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.prop('disabled', true)
                  .html('<i class="fas fa-spinner fa-spin me-2"></i>Updating...');
        
        // Submit form
        $('#subcategoryForm')[0].submit();
    }

    function confirmDelete() {
        Swal.fire({
            title: 'Delete Sub-Category',
            html: 'Are you sure you want to delete this sub-category?<br><br><strong><?php echo htmlspecialchars($subcategory['name']); ?></strong><br><br><small class="text-danger">This action cannot be undone!</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'subcategory-delete.php?id=<?php echo $subcategoryId; ?>';
            }
        });
    }
    </script>
</body>
</html>