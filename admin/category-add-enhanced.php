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

// Initialize form data
$formData = [
    'name' => '',
    'slug' => '',
    'description' => '',
    'meta_title' => '',
    'meta_description' => '',
    'meta_keywords' => '',
    'is_active' => 1,
    'sort_order' => 0,
    'parent_id' => 0
];

// Get parent categories for dropdown
$db->where('is_active', 1);
$db->orderBy('name', 'ASC');
$parentCategories = $db->get('categories', null, ['id', 'name']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['slug'] = trim($_POST['slug'] ?? '');
    $formData['description'] = trim($_POST['description'] ?? '');
    $formData['meta_title'] = trim($_POST['meta_title'] ?? '');
    $formData['meta_description'] = trim($_POST['meta_description'] ?? '');
    $formData['meta_keywords'] = trim($_POST['meta_keywords'] ?? '');
    $formData['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $formData['sort_order'] = intval($_POST['sort_order'] ?? 0);
    $formData['parent_id'] = intval($_POST['parent_id'] ?? 0);
    
    // Validation
    if (empty($formData['name'])) {
        $errors[] = "Category name is required.";
    } elseif (strlen($formData['name']) < 2) {
        $errors[] = "Category name must be at least 2 characters long.";
    } elseif (strlen($formData['name']) > 100) {
        $errors[] = "Category name must not exceed 100 characters.";
    }
    
    // Generate slug if empty
    if (empty($formData['slug'])) {
        $formData['slug'] = generateSlug($formData['name']);
    } else {
        $formData['slug'] = generateSlug($formData['slug']);
    }
    
    // Check for duplicate name
    if (!empty($formData['name'])) {
        $db->where('LOWER(name)', strtolower($formData['name']));
        if ($db->has('categories')) {
            $errors[] = "A category with this name already exists.";
        }
    }
    
    // Ensure slug uniqueness
    if (!empty($formData['slug'])) {
        $formData['slug'] = ensureUniqueSlug($db, $formData['slug']);
    }
    
    // Validate description length
    if (strlen($formData['description']) > 1000) {
        $errors[] = "Description must not exceed 1000 characters.";
    }
    
    // Validate meta fields
    if (strlen($formData['meta_title']) > 200) {
        $errors[] = "Meta title must not exceed 200 characters.";
    }
    if (strlen($formData['meta_description']) > 300) {
        $errors[] = "Meta description must not exceed 300 characters.";
    }
    if (strlen($formData['meta_keywords']) > 500) {
        $errors[] = "Meta keywords must not exceed 500 characters.";
    }
    
    // Handle image upload
    $uploadedImage = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleImageUpload($_FILES['image']);
        if ($uploadResult['success']) {
            $uploadedImage = $uploadResult['filename'];
        } else {
            $errors[] = $uploadResult['error'];
        }
    }
    
    // Insert category if no errors
    if (empty($errors)) {
        try {
            $db->startTransaction();
            
            $categoryData = [
                'name' => $formData['name'],
                'slug' => $formData['slug'],
                'description' => $formData['description'],
                'meta_title' => $formData['meta_title'],
                'meta_description' => $formData['meta_description'],
                'meta_keywords' => $formData['meta_keywords'],
                'is_active' => $formData['is_active'],
                'sort_order' => $formData['sort_order'],
                'parent_id' => $formData['parent_id'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (!empty($uploadedImage)) {
                $categoryData['image'] = $uploadedImage;
            }
            
            $categoryId = $db->insert('categories', $categoryData);
            
            if ($categoryId) {
                $db->commit();
                $_SESSION['success'] = 'Category added successfully!';
                
                // Redirect based on user choice
                $redirect = $_POST['redirect_action'] ?? 'list';
                if ($redirect === 'add_another') {
                    header('Location: category-add.php?success=1');
                } else {
                    header('Location: category-management.php');
                }
                exit;
            } else {
                throw new Exception('Failed to insert category: ' . $db->getLastError());
            }
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Database error: ' . $e->getMessage();
            
            // Clean up uploaded image if insert failed
            if (!empty($uploadedImage)) {
                $imagePath = __DIR__ . "/../assets/categories/$uploadedImage";
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
        }
    }
}

// Helper functions
function generateSlug($string) {
    // Convert to lowercase and replace spaces with hyphens
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // If empty, generate a random slug
    if (empty($slug)) {
        $slug = 'category-' . time();
    }
    
    return $slug;
}

function ensureUniqueSlug($db, $slug, $excludeId = null) {
    $originalSlug = $slug;
    $counter = 1;
    
    while (true) {
        $db->where('slug', $slug);
        if ($excludeId) {
            $db->where('id', $excludeId, '!=');
        }
        
        if (!$db->has('categories')) {
            break;
        }
        
        $slug = $originalSlug . '-' . $counter;
        $counter++;
        
        // Prevent infinite loop
        if ($counter > 100) {
            $slug = $originalSlug . '-' . time();
            break;
        }
    }
    
    return $slug;
}

function handleImageUpload($file) {
    $uploadDir = __DIR__ . '/../assets/categories/';
    $thumbDir = $uploadDir . 'thumbs/';
    
    // Create directories if they don't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0755, true);
    }
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size must not exceed 5MB.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'cat_' . uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Create thumbnail (optional - only if GD extension is available)
        if (extension_loaded('gd')) {
            try {
                createThumbnail($uploadPath, $thumbDir . $filename, 300, 300);
            } catch (Exception $e) {
                // Thumbnail creation failed, but upload succeeded
                error_log('Thumbnail creation failed: ' . $e->getMessage());
            }
        }
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to upload image.'];
    }
}

function createThumbnail($sourcePath, $targetPath, $width, $height) {
    // Check if GD extension is loaded
    if (!extension_loaded('gd')) {
        error_log('GD extension not loaded - thumbnail creation skipped');
        return false;
    }
    
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // Create source image
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$sourceImage) {
        return false;
    }
    
    // Calculate aspect ratio and dimensions
    $aspectRatio = $sourceWidth / $sourceHeight;
    if ($aspectRatio > 1) {
        $newWidth = $width;
        $newHeight = $width / $aspectRatio;
    } else {
        $newHeight = $height;
        $newWidth = $height * $aspectRatio;
    }
    
    // Create thumbnail
    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
    
    // Handle transparency for PNG and GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
    
    // Save thumbnail
    $result = false;
    switch ($mimeType) {
        case 'image/jpeg':
            $result = imagejpeg($thumbnail, $targetPath, 90);
            break;
        case 'image/png':
            $result = imagepng($thumbnail, $targetPath, 9);
            break;
        case 'image/gif':
            $result = imagegif($thumbnail, $targetPath);
            break;
        case 'image/webp':
            $result = imagewebp($thumbnail, $targetPath, 90);
            break;
    }
    
    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($thumbnail);
    
    return $result;
}
?>

<?php require __DIR__ . '/components/header.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .preview-image {
        max-width: 200px;
        max-height: 200px;
        border: 2px dashed #ddd;
        border-radius: 8px;
        padding: 10px;
        display: none;
    }
    .form-section {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 1rem;
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 0.5rem;
    }
    .char-counter {
        font-size: 0.875rem;
        color: #6c757d;
    }
    .char-counter.warning {
        color: #fd7e14;
    }
    .char-counter.danger {
        color: #dc3545;
    }
    .required-field {
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
                    <h1 class="mt-4">Add New Category</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="category-management.php">Categories</a></li>
                        <li class="breadcrumb-item active">Add Category</li>
                    </ol>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6><i class="fas fa-exclamation-circle me-2"></i>Please fix the following errors:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>Previous category was added successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="categoryForm">
                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Basic Information -->
                                <div class="form-section">
                                    <div class="section-title">
                                        <i class="fas fa-info-circle me-2"></i>Basic Information
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Category Name <span class="required-field">*</span></label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?php echo htmlspecialchars($formData['name']); ?>" 
                                                       required maxlength="100">
                                                <div class="form-text">
                                                    <span class="char-counter" data-target="name">0/100 characters</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="slug" class="form-label">URL Slug</label>
                                                <input type="text" class="form-control" id="slug" name="slug" 
                                                       value="<?php echo htmlspecialchars($formData['slug']); ?>" 
                                                       maxlength="100">
                                                <div class="form-text">Leave empty to auto-generate from name</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" 
                                                  rows="4" maxlength="1000"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                                        <div class="form-text">
                                            <span class="char-counter" data-target="description">0/1000 characters</span>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="parent_id" class="form-label">Parent Category</label>
                                                <select class="form-select" id="parent_id" name="parent_id">
                                                    <option value="0">None (Main Category)</option>
                                                    <?php foreach ($parentCategories as $parent): ?>
                                                        <option value="<?php echo $parent['id']; ?>" 
                                                                <?php echo $formData['parent_id'] == $parent['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($parent['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="sort_order" class="form-label">Sort Order</label>
                                                <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                                       value="<?php echo $formData['sort_order']; ?>" min="0" max="9999">
                                                <div class="form-text">Lower numbers appear first</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SEO Information -->
                                <div class="form-section">
                                    <div class="section-title">
                                        <i class="fas fa-search me-2"></i>SEO Information
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="meta_title" class="form-label">Meta Title</label>
                                        <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                               value="<?php echo htmlspecialchars($formData['meta_title']); ?>" 
                                               maxlength="200">
                                        <div class="form-text">
                                            <span class="char-counter" data-target="meta_title">0/200 characters</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="meta_description" class="form-label">Meta Description</label>
                                        <textarea class="form-control" id="meta_description" name="meta_description" 
                                                  rows="3" maxlength="300"><?php echo htmlspecialchars($formData['meta_description']); ?></textarea>
                                        <div class="form-text">
                                            <span class="char-counter" data-target="meta_description">0/300 characters</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                        <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" 
                                               value="<?php echo htmlspecialchars($formData['meta_keywords']); ?>" 
                                               maxlength="500">
                                        <div class="form-text">
                                            Separate keywords with commas. 
                                            <span class="char-counter" data-target="meta_keywords">0/500 characters</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Image Upload -->
                                <div class="form-section">
                                    <div class="section-title">
                                        <i class="fas fa-image me-2"></i>Category Image
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Upload Image</label>
                                        <input type="file" class="form-control" id="image" name="image" 
                                               accept="image/jpeg,image/png,image/gif,image/webp">
                                        <div class="form-text">
                                            Supported formats: JPEG, PNG, GIF, WebP. Maximum size: 5MB. Recommended size: 300x300px.
                                        </div>
                                    </div>
                                    
                                    <div id="imagePreview" class="text-center">
                                        <img id="previewImg" class="preview-image" alt="Image preview">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <!-- Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-cog me-2"></i>Settings
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                   <?php echo $formData['is_active'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_active">
                                                Active Status
                                            </label>
                                            <div class="form-text">Inactive categories are hidden from customers</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-paper-plane me-2"></i>Actions
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="submit" name="redirect_action" value="list" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Save & Return to List
                                            </button>
                                            <button type="submit" name="redirect_action" value="add_another" class="btn btn-success">
                                                <i class="fas fa-plus me-2"></i>Save & Add Another
                                            </button>
                                            <a href="category-management.php" class="btn btn-secondary">
                                                <i class="fas fa-times me-2"></i>Cancel
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Guidelines -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-lightbulb me-2"></i>Guidelines
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled mb-0 small">
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Use clear, descriptive names
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Keep descriptions concise but informative
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Add SEO-friendly meta information
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Use high-quality square images
                                            </li>
                                            <li class="mb-0">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Set logical sort order for display
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </main>

            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Character counters
            $('[data-target]').each(function() {
                const target = $(this).data('target');
                const $input = $(`#${target}`);
                const maxLength = parseInt($input.attr('maxlength')) || 0;
                
                const updateCounter = () => {
                    const currentLength = $input.val().length;
                    const $counter = $(this);
                    
                    $counter.text(`${currentLength}/${maxLength} characters`);
                    
                    // Color coding
                    $counter.removeClass('warning danger');
                    if (currentLength > maxLength * 0.9) {
                        $counter.addClass('danger');
                    } else if (currentLength > maxLength * 0.7) {
                        $counter.addClass('warning');
                    }
                };
                
                $input.on('input', updateCounter);
                updateCounter(); // Initial count
            });
            
            // Auto-generate slug from name
            $('#name').on('input', function() {
                if ($('#slug').val() === '') {
                    const slug = $(this).val()
                        .toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .trim('-');
                    $('#slug').val(slug);
                }
            });
            
            // Image preview
            $('#image').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file size
                    if (file.size > 5 * 1024 * 1024) {
                        Swal.fire({
                            icon: 'error',
                            title: 'File Too Large',
                            text: 'Image size must not exceed 5MB.'
                        });
                        $(this).val('');
                        return;
                    }
                    
                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(file.type)) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid File Type',
                            text: 'Only JPEG, PNG, GIF, and WebP images are allowed.'
                        });
                        $(this).val('');
                        return;
                    }
                    
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#previewImg').attr('src', e.target.result).show();
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#previewImg').hide();
                }
            });
            
            // Form validation
            $('#categoryForm').on('submit', function(e) {
                const name = $('#name').val().trim();
                
                if (name === '') {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Category name is required.'
                    });
                    $('#name').focus();
                    return false;
                }
                
                if (name.length < 2) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Category name must be at least 2 characters long.'
                    });
                    $('#name').focus();
                    return false;
                }
                
                // Show loading state
                $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
            });
            
            // Auto-hide alerts
            $('.alert').delay(5000).fadeOut('slow');
        });
    </script>
</body>
</html>