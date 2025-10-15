<?php
// Enhanced Brand Add Form
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

// Initialize form data
$formData = [
    'name' => '',
    'slug' => '',
    'description' => '',
    'website' => '',
    'meta_title' => '',
    'meta_description' => '',
    'meta_keywords' => '',
    'sort_order' => 0,
    'is_active' => 1,
    'is_featured' => 0
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $website = trim($_POST['website'] ?? '');
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
            'website' => $website,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'sort_order' => $sort_order,
            'is_active' => $is_active,
            'is_featured' => $is_featured
        ];
        
        // Validation
        if (empty($name)) {
            throw new Exception('Brand name is required');
        }
        
        if (strlen($name) < 2) {
            throw new Exception('Brand name must be at least 2 characters long');
        }
        
        if (strlen($name) > 100) {
            throw new Exception('Brand name must not exceed 100 characters');
        }
        
        // Auto-generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }
        
        // Validate website URL if provided
        if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
            throw new Exception('Please enter a valid website URL');
        }
        
        // Check for duplicate name
        $db->where('name', $name);
        $existingName = $db->getOne('brands', ['id']);
        if ($existingName) {
            throw new Exception('Brand name already exists');
        }
        
        // Check for duplicate slug
        $db->where('slug', $slug);
        $existingSlug = $db->getOne('brands', ['id']);
        if ($existingSlug) {
            $counter = 1;
            $originalSlug = $slug;
            do {
                $slug = $originalSlug . '-' . $counter;
                $db->where('slug', $slug);
                $existingSlug = $db->getOne('brands', ['id']);
                $counter++;
            } while ($existingSlug);
        }
        
        // Handle logo upload
        $logoName = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/brands/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = $_FILES['logo']['type'];
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('Invalid logo type. Only JPEG, PNG, GIF, and WebP are allowed');
            }
            
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($_FILES['logo']['size'] > $maxSize) {
                throw new Exception('Logo size too large. Maximum 5MB allowed');
            }
            
            $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $logoName = time() . '_' . uniqid() . '.' . $extension;
            $uploadPath = $uploadDir . $logoName;
            
            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                throw new Exception('Failed to upload logo');
            }
        }
        
        // Insert brand
        $data = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'logo' => $logoName,
            'website' => $website,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'sort_order' => $sort_order,
            'is_active' => $is_active,
            'is_featured' => $is_featured,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $db->insert('brands', $data);
        
        if ($result) {
            $_SESSION['success'] = "Brand '$name' has been added successfully!";
            header('Location: brand-all.php');
            exit;
        } else {
            throw new Exception('Failed to create brand');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
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
    .logo-preview {
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
                    <h1 class="mt-4">Add New Brand</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="brand-all.php">Brands</a></li>
                        <li class="breadcrumb-item active">Add Brand</li>
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
                                        <i class="fas fa-plus me-2"></i>Create New Brand
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data" id="brandForm">
                                        <div class="row">
                                            <!-- Basic Information -->
                                            <div class="col-lg-8">
                                                <h5 class="mb-3"><i class="fas fa-info-circle text-success me-2"></i>Basic Information</h5>
                                                
                                                <div class="mb-3">
                                                    <label for="name" class="form-label">Brand Name <span class="required">*</span></label>
                                                    <input type="text" class="form-control" id="name" name="name" 
                                                           value="<?php echo htmlspecialchars($formData['name']); ?>" 
                                                           placeholder="Enter brand name" required maxlength="100">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="slug" class="form-label">URL Slug</label>
                                                    <input type="text" class="form-control" id="slug" name="slug" 
                                                           value="<?php echo htmlspecialchars($formData['slug']); ?>" 
                                                           placeholder="auto-generated-slug" maxlength="100">
                                                    <small class="form-text text-muted">Leave empty to auto-generate from name</small>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="website" class="form-label">Website URL</label>
                                                    <input type="url" class="form-control" id="website" name="website" 
                                                           value="<?php echo htmlspecialchars($formData['website']); ?>" 
                                                           placeholder="https://example.com" maxlength="255">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="description" class="form-label">Description</label>
                                                    <textarea class="form-control" id="description" name="description" rows="4" 
                                                              placeholder="Enter brand description" maxlength="1000"><?php echo htmlspecialchars($formData['description']); ?></textarea>
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

                                            <!-- Logo and Meta -->
                                            <div class="col-lg-4">
                                                <h5 class="mb-3"><i class="fas fa-image text-success me-2"></i>Logo & SEO</h5>
                                                
                                                <div class="mb-3">
                                                    <label for="logo" class="form-label">Brand Logo</label>
                                                    <input type="file" class="form-control" id="logo" name="logo" 
                                                           accept="image/jpeg,image/png,image/gif,image/webp">
                                                    <small class="form-text text-muted">JPEG, PNG, GIF, WebP (Max: 5MB)</small>
                                                    <div class="logo-preview mt-2" id="logoPreview" style="display: none;">
                                                        <img id="previewImage" src="#" alt="Logo Preview" style="max-width: 100%; max-height: 150px;">
                                                    </div>
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
                                            <a href="brand-all.php" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left me-2"></i>Back to Brands
                                            </a>
                                            <button type="submit" class="btn btn-success" id="submitBtn">
                                                <i class="fas fa-save me-2"></i>Create Brand
                                            </button>
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
        
        // Logo preview
        $('#logo').on('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#previewImage').attr('src', e.target.result);
                    $('#logoPreview').show();
                };
                reader.readAsDataURL(file);
            } else {
                $('#logoPreview').hide();
            }
        });
        
        // Form submission
        $('#brandForm').on('submit', function(e) {
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
        
        if (name === '') {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Brand name is required'
            });
            $('#name').focus();
            return false;
        }
        
        if (name.length < 2) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Brand name must be at least 2 characters long'
            });
            $('#name').focus();
            return false;
        }
        
        const website = $('#website').val().trim();
        if (website !== '' && !isValidUrl(website)) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please enter a valid website URL'
            });
            $('#website').focus();
            return false;
        }
        
        return true;
    }

    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    function submitForm() {
        const submitBtn = $('#submitBtn');
        const originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.prop('disabled', true)
                  .html('<i class="fas fa-spinner fa-spin me-2"></i>Creating...');
        
        // Submit form
        $('#brandForm')[0].submit();
    }
    </script>
</body>
</html>