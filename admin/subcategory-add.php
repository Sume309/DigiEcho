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

// Initialize database connection using project's MysqliDb class
$db = new MysqliDb(
    settings()['hostname'], 
    settings()['user'], 
    settings()['password'], 
    settings()['database']
);

$errors = [];
$success = '';
$formData = [
    'name' => '',
    'category_id' => '',
    'description' => '',
    'meta_title' => '',
    'meta_description' => '',
    'meta_keywords' => '',
    'sort_order' => 0,
    'status' => 1,
    'is_featured' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize and collect form data
        $formData = [
            'name' => trim($_POST['name'] ?? ''),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'description' => trim($_POST['description'] ?? ''),
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'status' => isset($_POST['status']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0
        ];

        // Enhanced validation
        if (empty($formData['name'])) {
            $errors[] = "Subcategory name is required.";
        } elseif (strlen($formData['name']) < 2) {
            $errors[] = "Subcategory name must be at least 2 characters long.";
        } elseif (strlen($formData['name']) > 100) {
            $errors[] = "Subcategory name cannot exceed 100 characters.";
        }

        if ($formData['category_id'] <= 0) {
            $errors[] = "Please select a valid parent category.";
        } else {
            // Verify category exists and is active
            $categoryExists = $db->rawQueryOne('SELECT id FROM categories WHERE id = ? AND is_active = 1', [$formData['category_id']]);
            if (!$categoryExists) {
                $errors[] = "Selected category does not exist or is inactive.";
            }
        }

        // Validate meta fields
        if (!empty($formData['meta_title']) && strlen($formData['meta_title']) > 200) {
            $errors[] = "Meta title cannot exceed 200 characters.";
        }

        if (!empty($formData['meta_description']) && strlen($formData['meta_description']) > 500) {
            $errors[] = "Meta description cannot exceed 500 characters.";
        }

        // Check for duplicate name within the same category (proper scope)
        if (!empty($formData['name']) && $formData['category_id'] > 0) {
            $duplicateCheck = $db->rawQueryOne(
                'SELECT s.id, s.name, c.name as category_name FROM subcategories s LEFT JOIN categories c ON s.category_id = c.id WHERE s.name = ? AND s.category_id = ?', 
                [$formData['name'], $formData['category_id']]
            );
            if ($duplicateCheck) {
                $errors[] = "A subcategory with this name '{$formData['name']}' already exists in the '{$duplicateCheck['category_name']}' category.";
                error_log("Duplicate subcategory attempt: {$formData['name']} in category {$formData['category_id']}");
            } else {
                // Log successful validation for debugging
                error_log("Subcategory name validation passed: {$formData['name']} can be added to category {$formData['category_id']}");
            }
        }

        // Handle image upload with enhanced error checking
        $imageName = null;
        $imageUploadError = null;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Debug image upload
            error_log("Image upload attempt - Error code: " . $_FILES['image']['error']);
            error_log("Image upload attempt - File name: " . $_FILES['image']['name']);
            error_log("Image upload attempt - File size: " . $_FILES['image']['size']);
            error_log("Image upload attempt - File type: " . $_FILES['image']['type']);
            
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = validateAndUploadImage($_FILES['image']);
                
                if (is_array($uploadResult)) {
                    // Upload failed, add errors
                    $errors = array_merge($errors, $uploadResult);
                    $imageUploadError = implode(', ', $uploadResult);
                } else {
                    // Upload successful, get filename
                    $imageName = $uploadResult;
                    error_log("Image upload successful: " . $imageName);
                }
            } else {
                // Handle different upload errors
                switch ($_FILES['image']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $errors[] = "Image file is too large. Maximum size is 5MB.";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errors[] = "Image upload was incomplete. Please try again.";
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $errors[] = "Server error: Missing temporary folder.";
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $errors[] = "Server error: Cannot write file to disk.";
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $errors[] = "Server error: File upload stopped by extension.";
                        break;
                    default:
                        $errors[] = "Unknown error occurred during image upload.";
                }
                $imageUploadError = end($errors);
            }
        }

        if (empty($errors)) {
            // Generate category-aware unique slug to avoid global conflicts
            $slug = createSlug($formData['name']);
            $originalSlug = $slug;
            $counter = 1;
            
            // Check for slug uniqueness globally (slugs must be unique across all subcategories)
            while (true) {
                $slugExists = $db->rawQueryOne('SELECT id FROM subcategories WHERE slug = ?', [$slug]);
                if (!$slugExists) {
                    break;
                }
                // Generate category-specific slug to avoid conflicts
                $categorySlug = $db->rawQueryOne('SELECT slug FROM categories WHERE id = ?', [$formData['category_id']]);
                $categoryPrefix = $categorySlug ? $categorySlug['slug'] . '-' : 'cat' . $formData['category_id'] . '-';
                $slug = $categoryPrefix . $originalSlug . '-' . $counter;
                $counter++;
                
                // Fallback to timestamp if too many attempts
                if ($counter > 10) {
                    $slug = $categoryPrefix . $originalSlug . '-' . time();
                    break;
                }
            }
            
            error_log("Generated unique slug: {$slug} for subcategory: {$formData['name']}");

            // Set meta_title to name if empty
            if (empty($formData['meta_title'])) {
                $formData['meta_title'] = $formData['name'];
            }

            // Prepare data for insertion
            $insertData = [
                'category_id' => $formData['category_id'],
                'name' => $formData['name'],
                'slug' => $slug,
                'description' => $formData['description'],
                'meta_title' => $formData['meta_title'],
                'meta_description' => $formData['meta_description'],
                'meta_keywords' => $formData['meta_keywords'],
                'sort_order' => $formData['sort_order'],
                'is_active' => $formData['status'],
                'is_featured' => $formData['is_featured']
            ];

            if ($imageName) {
                $insertData['image'] = $imageName;
            }

            // Insert subcategory
            $subcategoryId = $db->insert('subcategories', $insertData);

            if ($subcategoryId) {
                $success = "Subcategory '" . htmlspecialchars($formData['name']) . "' added successfully!";
                
                // Reset form data
                $formData = [
                    'name' => '',
                    'category_id' => '',
                    'description' => '',
                    'meta_title' => '',
                    'meta_description' => '',
                    'meta_keywords' => '',
                    'sort_order' => 0,
                    'status' => 1,
                    'is_featured' => 0
                ];
            } else {
                $errors[] = "Failed to add subcategory: " . $db->getLastError();
            }
        }
    } catch (Exception $e) {
        $errors[] = "An error occurred: " . $e->getMessage();
        error_log("Subcategory add error: " . $e->getMessage());
    }
}

// Function to create URL-friendly slug
function createSlug($text) {
    // Convert to lowercase
    $text = mb_strtolower($text, 'UTF-8');
    
    // Replace spaces and special characters with hyphens
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    
    // Remove multiple consecutive hyphens
    $text = preg_replace('/-+/', '-', $text);
    
    // Trim hyphens from beginning and end
    $text = trim($text, '-');
    
    return $text;
}

// Function to validate and upload image
function validateAndUploadImage($file) {
    try {
        $uploadDir = __DIR__ . '/../assets/subcategories/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("Failed to create upload directory: " . $uploadDir);
                return ['Failed to create upload directory.'];
            }
        }
        
        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            error_log("Upload directory is not writable: " . $uploadDir);
            return ['Upload directory is not writable.'];
        }
        
        // Validate file type using multiple methods
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        $fileType = strtolower($file['type']);
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check MIME type
        if (!in_array($fileType, $allowedTypes)) {
            error_log("Invalid MIME type: " . $fileType);
            return ['Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.'];
        }
        
        // Check file extension
        if (!in_array($fileExtension, $allowedExtensions)) {
            error_log("Invalid file extension: " . $fileExtension);
            return ['Invalid file extension. Please upload JPG, PNG, GIF, or WebP images only.'];
        }
        
        // Additional file type validation using getimagesize
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            error_log("File is not a valid image: " . $file['name']);
            return ['File is not a valid image.'];
        }
        
        // Validate file size (5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            error_log("File size too large: " . $file['size'] . " bytes");
            return ['File size too large. Maximum size is 5MB.'];
        }
        
        if ($file['size'] <= 0) {
            error_log("File size is zero: " . $file['name']);
            return ['Invalid file size.'];
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Ensure filename doesn't already exist (very unlikely but safe)
        $counter = 1;
        while (file_exists($filepath)) {
            $filename = time() . '_' . uniqid() . '_' . $counter . '.' . $extension;
            $filepath = $uploadDir . $filename;
            $counter++;
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Verify file was actually moved and is readable
            if (file_exists($filepath) && is_readable($filepath)) {
                error_log("Image upload successful: " . $filename);
                return $filename; // Return filename on success
            } else {
                error_log("File was moved but is not accessible: " . $filepath);
                return ['Failed to upload image. File is not accessible.'];
            }
        } else {
            error_log("move_uploaded_file failed from " . $file['tmp_name'] . " to " . $filepath);
            return ['Failed to upload image. Please try again.'];
        }
        
    } catch (Exception $e) {
        error_log("Image upload exception: " . $e->getMessage());
        return ['An error occurred during image upload: ' . $e->getMessage()];
    }
}
?>
<?php require __DIR__ . '/components/header.php'; ?>

<style>
    .form-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    
    .form-card:hover {
        box-shadow: 0 5px 30px rgba(0,0,0,0.12);
    }
    
    .form-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 1.5rem;
    }
    
    .form-section {
        border-left: 4px solid #667eea;
        background: #f8f9ff;
        border-radius: 0 8px 8px 0;
        margin-bottom: 1.5rem;
    }
    
    .form-section-header {
        background: linear-gradient(90deg, #667eea, #764ba2);
        color: white;
        margin: -1px 0 15px -4px;
        padding: 0.75rem 1rem;
        border-radius: 0 8px 0 0;
    }
    
    .image-upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        background: #f8f9fa;
    }
    
    .image-upload-area:hover {
        border-color: #667eea;
        background: #f0f3ff;
    }
    
    .image-upload-area.dragover {
        border-color: #667eea;
        background: #e3f2fd;
        transform: scale(1.02);
    }
    
    .image-preview {
        max-width: 200px;
        max-height: 200px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .slug-preview {
        background: #e9ecef;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
        color: #495057;
    }
    
    .char-counter {
        font-size: 0.8rem;
        color: #6c757d;
        text-align: right;
    }
    
    .char-counter.warning {
        color: #fd7e14;
    }
    
    .char-counter.danger {
        color: #dc3545;
    }
    
    .advanced-options {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 1rem;
    }
    
    .feature-badge {
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        margin-left: 0.5rem;
    }
    
    .btn-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }
    
    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .form-floating label {
        color: #6c757d;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
    }
    
    .alert-custom {
        border: none;
        border-radius: 10px;
        padding: 1rem 1.5rem;
    }
</style>

</head>
<style>
    .form-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    
    .form-card:hover {
        box-shadow: 0 5px 30px rgba(0,0,0,0.12);
    }
    
    .form-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 1.5rem;
    }
    
    .form-section {
        border-left: 4px solid #667eea;
        background: #f8f9ff;
        border-radius: 0 8px 8px 0;
        margin-bottom: 1.5rem;
    }
    
    .form-section-header {
        background: linear-gradient(90deg, #667eea, #764ba2);
        color: white;
        margin: -1px 0 15px -4px;
        padding: 0.75rem 1rem;
        border-radius: 0 8px 0 0;
    }
    
    .image-upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        background: #f8f9fa;
    }
    
    .image-upload-area:hover {
        border-color: #667eea;
        background: #f0f3ff;
    }
    
    .image-upload-area.dragover {
        border-color: #667eea;
        background: #e3f2fd;
        transform: scale(1.02);
    }
    
    .image-preview {
        max-width: 200px;
        max-height: 200px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .slug-preview {
        background: #e9ecef;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
        color: #495057;
    }
    
    .char-counter {
        font-size: 0.8rem;
        color: #6c757d;
        text-align: right;
    }
    
    .char-counter.warning {
        color: #fd7e14;
    }
    
    .char-counter.danger {
        color: #dc3545;
    }
    
    .advanced-options {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 1rem;
    }
    
    .feature-badge {
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        margin-left: 0.5rem;
    }
    
    .btn-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }
    
    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .form-floating label {
        color: #6c757d;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
    }
    
    .alert-custom {
        border: none;
        border-radius: 10px;
        padding: 1rem 1.5rem;
    }
</style>
<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                        <div>
                            <h1 class="h3 mb-1">
                                <i class="fas fa-plus-circle me-2 text-primary"></i>
                                Add New Subcategory
                            </h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="subcategory-all.php">Subcategories</a></li>
                                    <li class="breadcrumb-item active">Add New</li>
                                </ol>
                            </nav>
                        </div>
                        <div>
                            <a href="subcategory-all.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to List
                            </a>
                        </div>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                                <div>
                                    <h6 class="mb-1">Please fix the following errors:</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-3 fs-4"></i>
                                <div>
                                    <h6 class="mb-1">Success!</h6>
                                    <p class="mb-0"><?php echo htmlspecialchars($success); ?></p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Main Form -->
                    <div class="row justify-content-center">
                        <div class="col-lg-10 col-xl-8">
                            <div class="card form-card">
                                <div class="form-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-sitemap me-2"></i>
                                        Subcategory Information
                                    </h5>
                                    <small class="opacity-75">Create a new subcategory with detailed information</small>
                                </div>
                                
                                <div class="card-body p-4">
                                    <form method="POST" action="" enctype="multipart/form-data" id="subcategoryForm">
                                        
                                        <!-- Basic Information Section -->
                                        <div class="form-section p-3">
                                            <div class="form-section-header">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Basic Information
                                                </h6>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" 
                                                               class="form-control" 
                                                               id="name" 
                                                               name="name" 
                                                               value="<?php echo htmlspecialchars($formData['name']); ?>"
                                                               placeholder="Enter subcategory name"
                                                               maxlength="100"
                                                               required>
                                                        <label for="name">
                                                            <i class="fas fa-tag me-1"></i>
                                                            Subcategory Name *
                                                        </label>
                                                        <div class="char-counter" id="nameCounter">0/100</div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="slug" class="form-label">
                                                            <i class="fas fa-link me-1"></i>
                                                            URL Slug (Auto-generated)
                                                        </label>
                                                        <div class="slug-preview" id="slugPreview">slug-will-appear-here</div>
                                                        <small class="text-muted">This will be automatically generated from the name</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <div class="form-floating mb-3">
                                                        <select class="form-select" 
                                                                id="category_id" 
                                                                name="category_id" 
                                                                required>
                                                            <option value="">-- Select Category --</option>
                                                            <?php
                                                            try {
                                                                $categories = $db->rawQuery("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name");
                                                                foreach ($categories as $category):
                                                                    $selected = ($formData['category_id'] == $category['id']) ? 'selected' : '';
                                                                    echo "<option value='{$category['id']}' $selected>{$category['name']}</option>";
                                                                endforeach;
                                                            } catch (Exception $e) {
                                                                echo '<option value="">Error loading categories</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                        <label for="category_id">
                                                            <i class="fas fa-folder me-1"></i>
                                                            Parent Category *
                                                        </label>
                                                    </div>
                                                    
                                                    <div class="form-floating mb-3">
                                                        <input type="number" 
                                                               class="form-control" 
                                                               id="sort_order" 
                                                               name="sort_order" 
                                                               value="<?php echo $formData['sort_order']; ?>"
                                                               min="0"
                                                               placeholder="0">
                                                        <label for="sort_order">
                                                            <i class="fas fa-sort-numeric-down me-1"></i>
                                                            Sort Order
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="description" class="form-label">
                                                    <i class="fas fa-align-left me-1"></i>
                                                    Description
                                                </label>
                                                <textarea class="form-control" 
                                                          id="description" 
                                                          name="description" 
                                                          rows="4"
                                                          maxlength="500"
                                                          placeholder="Enter a detailed description of this subcategory..."><?php echo htmlspecialchars($formData['description']); ?></textarea>
                                                <div class="char-counter" id="descriptionCounter">0/500</div>
                                            </div>
                                        </div>
                                        
                                        <!-- Image Upload Section -->
                                        <div class="mb-3">
                                            <label for="image" class="form-label">Subcategory Image</label>
                                            <div class="image-upload-area" id="imageUploadArea">
                                                <div id="uploadPrompt">
                                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-primary"></i>
                                                    <p class="mb-1">Click to upload or drag & drop</p>
                                                    <p class="text-muted small">JPG, PNG, GIF, WebP (Max 5MB)</p>
                                                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="document.getElementById('image').click()">Browse Files</button>
                                                </div>
                                                <input type="file" class="d-none" id="image" name="image" accept="image/*">
                                            </div>
                                            <div id="imagePreview" class="mt-2 d-none">
                                                <div class="image-preview-container position-relative">
                                                    <img src="" alt="Image Preview" class="image-preview" id="previewImg">
                                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" id="removeImage">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <div class="mt-1">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="changeImage">Change Image</button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- SEO & Settings Section -->
                                        <div class="form-section p-3">
                                            <div class="form-section-header">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-search me-2"></i>
                                                    SEO & Advanced Settings
                                                </h6>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" 
                                                               class="form-control" 
                                                               id="meta_title" 
                                                               name="meta_title" 
                                                               value="<?php echo htmlspecialchars($formData['meta_title']); ?>"
                                                               maxlength="200"
                                                               placeholder="SEO Title">
                                                        <label for="meta_title">
                                                            <i class="fas fa-heading me-1"></i>
                                                            SEO Title
                                                        </label>
                                                        <div class="char-counter" id="metaTitleCounter">0/200</div>
                                                        <small class="text-muted">Leave empty to use subcategory name</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="form-floating mb-3">
                                                        <input type="text" 
                                                               class="form-control" 
                                                               id="meta_keywords" 
                                                               name="meta_keywords" 
                                                               value="<?php echo htmlspecialchars($formData['meta_keywords']); ?>"
                                                               placeholder="keyword1, keyword2, keyword3">
                                                        <label for="meta_keywords">
                                                            <i class="fas fa-tags me-1"></i>
                                                            SEO Keywords
                                                        </label>
                                                        <small class="text-muted">Separate with commas</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="meta_description" class="form-label">
                                                    <i class="fas fa-paragraph me-1"></i>
                                                    SEO Description
                                                </label>
                                                <textarea class="form-control" 
                                                          id="meta_description" 
                                                          name="meta_description" 
                                                          rows="3"
                                                          maxlength="500"
                                                          placeholder="A brief description for search engines..."><?php echo htmlspecialchars($formData['meta_description']); ?></textarea>
                                                <div class="char-counter" id="metaDescriptionCounter">0/500</div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input" 
                                                               type="checkbox" 
                                                               id="status" 
                                                               name="status" 
                                                               <?php echo $formData['status'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="status">
                                                            <i class="fas fa-toggle-on me-1"></i>
                                                            Active Status
                                                        </label>
                                                        <small class="text-muted d-block">Enable to make this subcategory visible</small>
                                                    </div>
                                                </div>
                                                
                                                            
                                            </div>
                                        </div>
                                        
                                        <!-- Form Actions -->
                                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                            <div class="text-muted">
                                                <small><i class="fas fa-info-circle me-1"></i> Fields marked with * are required</small>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="subcategory-all.php" class="btn btn-outline-secondary">
                                                    <i class="fas fa-times me-1"></i> Cancel
                                                </a>
                                                <button type="submit" class="btn btn-gradient px-4">
                                                    <i class="fas fa-save me-1"></i> Add Subcategory
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
    <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    $(document).ready(function() {
        // Character counters
        function updateCharCounter(inputId, counterId, maxLength) {
            const input = $('#' + inputId);
            const counter = $('#' + counterId);
            
            function updateCount() {
                const length = input.val().length;
                counter.text(length + '/' + maxLength);
                
                counter.removeClass('warning danger');
                if (length > maxLength * 0.8) {
                    counter.addClass('warning');
                }
                if (length > maxLength * 0.95) {
                    counter.addClass('danger');
                }
            }
            
            input.on('input', updateCount);
            updateCount(); // Initial count
        }
        
        // Initialize character counters
        updateCharCounter('name', 'nameCounter', 100);
        updateCharCounter('description', 'descriptionCounter', 500);
        updateCharCounter('meta_title', 'metaTitleCounter', 200);
        updateCharCounter('meta_description', 'metaDescriptionCounter', 500);
        
        // Auto-generate slug from name
        $('#name').on('input', function() {
            const name = $(this).val().trim();
            if (name) {
                const slug = createSlug(name);
                $('#slugPreview').text(slug || 'slug-will-appear-here');
            } else {
                $('#slugPreview').text('slug-will-appear-here');
            }
        });
        
        // Dynamic auto-fill meta title from name
        $('#name').on('input blur', function() {
            const name = $(this).val().trim();
            const metaTitle = $('#meta_title').val().trim();
            
            if (name && !metaTitle) {
                $('#meta_title').val(name);
                updateCharCounter('meta_title', 'metaTitleCounter', 200);
            }
        });
        
        // Dynamic category-based suggestions
        $('#category_id').on('change', function() {
            const categoryId = $(this).val();
            const categoryText = $(this).find('option:selected').text();
            
            if (categoryId && categoryText !== '-- Select Category --') {
                // Auto-suggest meta keywords based on category
                const currentKeywords = $('#meta_keywords').val().trim();
                if (!currentKeywords) {
                    const suggestedKeywords = categoryText.toLowerCase() + ', ' + categoryText.toLowerCase() + ' products, ' + categoryText.toLowerCase() + ' items';
                    $('#meta_keywords').val(suggestedKeywords);
                }
                
                // Show category-specific tips
                showCategoryTips(categoryText);
            }
        });
        
        // Real-time name validation
        $('#name').on('input', function() {
            const name = $(this).val().trim();
            const nameInput = $(this);
            
            // Clear previous validation states
            nameInput.removeClass('is-valid is-invalid');
            
            if (name.length >= 2) {
                // Check for duplicate names via AJAX
                const categoryId = $('#category_id').val();
                if (categoryId) {
                    checkDuplicateName(name, categoryId, nameInput);
                }
                nameInput.addClass('is-valid');
            } else if (name.length > 0) {
                nameInput.addClass('is-invalid');
            }
        });
        
        // Dynamic sort order suggestion
        $('#category_id').on('change', function() {
            const categoryId = $(this).val();
            if (categoryId) {
                suggestSortOrder(categoryId);
            }
        });
        
        // Image upload functionality with safety checks
        const imageUploadArea = $('#imageUploadArea');
        const imageInput = $('#image');
        const uploadPrompt = $('#uploadPrompt');
        const imagePreview = $('#imagePreview');
        const previewImg = $('#previewImg');
        const browseButton = $('#browseButton');
        
        // Initialize image upload functionality only if required elements exist
        function initImageUpload() {
            // Only proceed if image input exists
            if (!imageInput.length) {
                console.warn('Image input element not found');
                return;
            }
            
            // Handle click on upload area if it exists
            if (imageUploadArea.length) {
                imageUploadArea.off('click').on('click', function(e) {
                    if (!$(e.target).is('button') && !$(e.target).closest('button').length) {
                        imageInput.trigger('click');
                    }
                });
            }
            
            // Handle browse button if it exists
            if (browseButton.length) {
                browseButton.off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    imageInput.trigger('click');
                });
            }
            
            // Handle change image button - using the form as the delegate container since it exists when the script runs
            const $form = $('#subcategoryForm');
            $form.off('click', '#changeImage').on('click', '#changeImage', function(e) {
                e.preventDefault();
                e.stopPropagation();
                imageInput.trigger('click');
            });
            
            // Handle file selection
            imageInput.off('change').on('change', function() {
                if (!this.files || this.files.length === 0) return;
                
                const file = this.files[0];
                console.log('File selected:', file.name, file.size, file.type);
                handleImageFile(file);
            });
        }
        
        // Initialize image upload functionality
        initImageUpload();
        
        // Remove image
        $(document).on('click', '#removeImage', function(e) {
            e.preventDefault();
            const imageInput = $('#image');
            const uploadPrompt = $('#uploadPrompt');
            const imagePreview = $('#imagePreview');
            
            if (imageInput.length) imageInput.val('');
            if (uploadPrompt.length) uploadPrompt.removeClass('d-none');
            if (imagePreview.length) imagePreview.addClass('d-none');
            console.log('Image removed');
        });
        
        // Handle image file with enhanced validation and error handling
        function handleImageFile(file) {
            try {
                console.log('Handling image file:', file.name, file.size, file.type);
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                const fileExtension = file.name.split('.').pop().toLowerCase();
                
                if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
                    Swal.fire({
                        title: 'Invalid File Type',
                        text: 'Please upload JPG, PNG, GIF, or WebP images only.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    if (imageInput.length) imageInput.val(''); // Clear the input
                    return;
                }
                
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({
                        title: 'File Too Large',
                        text: 'Please upload an image smaller than 5MB.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    if (imageInput.length) imageInput.val(''); // Clear the input
                    return;
                }
                
                if (file.size === 0) {
                    Swal.fire({
                        title: 'Invalid File',
                        text: 'The selected file appears to be empty.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    if (imageInput.length) imageInput.val(''); // Clear the input
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (previewImg.length) {
                        previewImg.attr('src', e.target.result);
                        if (uploadPrompt.length) uploadPrompt.addClass('d-none');
                        if (imagePreview.length) imagePreview.removeClass('d-none');
                        console.log('Image preview loaded successfully');
                    }
                };
                
                reader.onerror = function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to read the image file.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    if (imageInput.length) imageInput.val(''); // Clear the input
                };
                
                reader.readAsDataURL(file);
            } catch (error) {
                console.error('Error handling image file:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while processing the image.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        }
        
        // Form submission with validation
        $('#subcategoryForm').on('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Adding...');
            
            // Get form data
            const formData = new FormData(this);
            
            // Submit via AJAX
            $.ajax({
                url: '',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Parse response to check for success
                    const tempDiv = $('<div>').html(response);
                    const successAlert = tempDiv.find('.alert-success');
                    const errorAlert = tempDiv.find('.alert-danger');
                    
                    if (successAlert.length) {
                        // Success
                        Swal.fire({
                            title: 'Success!',
                            text: 'Subcategory added successfully!',
                            icon: 'success',
                            confirmButtonText: 'OK',
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'subcategory-all.php';
                            }
                        });
                    } else if (errorAlert.length) {
                        // Show errors
                        const errorList = errorAlert.find('ul li');
                        let errorText = '';
                        errorList.each(function() {
                            errorText += '• ' + $(this).text() + '\n';
                        });
                        
                        Swal.fire({
                            title: 'Validation Errors',
                            text: errorText,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        // Reload page if unclear response
                        window.location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Form submission error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while adding the subcategory. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        });
        
        // Helper function to create slug
        function createSlug(text) {
            return text
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                .replace(/\s+/g, '-')         // Replace spaces with hyphens
                .replace(/-+/g, '-')          // Replace multiple hyphens with single
                .replace(/^-+|-+$/g, '');     // Remove leading/trailing hyphens
        }
        
        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
        
        // Auto-resize textareas
        $('textarea').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Form validation feedback
        const form = document.getElementById('subcategoryForm');
        form.addEventListener('input', function(e) {
            if (e.target.checkValidity()) {
                e.target.classList.remove('is-invalid');
                e.target.classList.add('is-valid');
            } else {
                e.target.classList.remove('is-valid');
                e.target.classList.add('is-invalid');
            }
        });
    });
    </script>
    </body>
</html>
