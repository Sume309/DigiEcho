<?php
// Redirect to the new enhanced category add page
header('Location: category-add-enhanced.php');
exit;

// Initialize database connection
$db = new MysqliDb();
$errors = [];
$success = '';

// Initialize PDO for raw queries if needed
$pdo = new PDO(
    'mysql:host=' . settings()['hostname'] . ';dbname=' . settings()['database'] . ';charset=utf8mb4',
    settings()['user'],
    settings()['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

// Initialize all variables with default values
$name = '';
$slug = '';
$description = '';
$metaTitle = '';
$metaDescription = '';
$metaKeywords = '';
$status = 1;
$sortOrder = 0;
$parentId = 0;
$image_name = '';
$isActive = 1;

// Initialize category array with default values
$category = [
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

// Get all parent categories for the dropdown
$parentCategories = $db->get('categories', null, ['id', 'name']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input with proper defaults
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $errors[] = "Category name is required.";
    }
    
    // Generate initial slug from name or provided slug
    $baseSlug = !empty(trim($_POST['slug'] ?? '')) ? trim($_POST['slug']) : $name;
    if (empty($baseSlug)) {
        $baseSlug = 'category';
    }
    
    // Generate a clean slug
    $slug = createSlug($baseSlug);
    
    // Ensure slug is not empty
    if (empty($slug)) {
        $slug = 'category';
    }
    
    // Ensure slug is unique
    $originalSlug = $slug;
    $counter = 1;
    $maxAttempts = 100;
    $uniqueSlugFound = false;
    
    // Keep trying until we find a unique slug or reach max attempts
    while (!$uniqueSlugFound && $counter <= $maxAttempts) {
        try {
            // Ensure slug is not empty in each iteration
            if (empty($slug)) {
                $slug = 'category' . $counter;
            }
            
            // Check if slug exists using a direct query with prepared statement
            $params = [$slug];
            $sql = "SELECT COUNT(*) as count FROM categories WHERE slug = ?";
            
            if (isset($id) && $id > 0) {
                $sql .= " AND id != ?";
                $params[] = $id;
            }
            
            $check = $db->rawQueryOne($sql, $params);
            
            if (empty($check) || $check['count'] == 0) {
                $uniqueSlugFound = true;
                break;
            }
            
            // Generate a new slug with counter
            $slug = $originalSlug . '-' . $counter;
            $counter++;
            
            // If we've tried several times, add a random string
            if ($counter > 5) {
                $slug = $originalSlug . '-' . bin2hex(random_bytes(3));
            }
            
            // If we've tried many times, add timestamp to ensure uniqueness
            if ($counter > 20) {
                $slug = 'cat-' . time() . '-' . bin2hex(random_bytes(4));
                break;
            }
            
        } catch (Exception $e) {
            // Log the error and generate a unique slug with timestamp
            error_log("Error checking for duplicate slug: " . $e->getMessage());
            $slug = 'cat-' . time() . '-' . bin2hex(random_bytes(4));
            $uniqueSlugFound = true;
            break;
        }
    }
    
    // Final fallback if all else fails
    if (!$uniqueSlugFound || empty($slug)) {
        $slug = 'cat-' . time() . '-' . bin2hex(random_bytes(4));
    }
    
    // Final validation to ensure slug is not empty
    if (empty($slug)) {
        $slug = 'category-' . time();
    }
    
    // Ensure we have a valid slug
    if (empty($slug)) {
        $slug = 'cat-' . time() . '-' . bin2hex(random_bytes(4));
    }
    
    // Optional fields with defaults
    $description = trim($_POST['description'] ?? '');
    $metaTitle = trim($_POST['meta_title'] ?? $name); // Default to name if empty
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $metaKeywords = trim($_POST['meta_keywords'] ?? '');
    $status = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
    $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    
    // Update category array with submitted values
    $category = [
        'name' => $name,
        'slug' => $slug,
        'description' => $description,
        'meta_title' => $metaTitle,
        'meta_description' => $metaDescription,
        'meta_keywords' => $metaKeywords,
        'is_active' => $status,
        'sort_order' => $sortOrder,
        'parent_id' => $parentId,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Only set created_at for new records
    if (empty($id)) {
        $category['created_at'] = date('Y-m-d H:i:s');
    }

    // Validation
    if (empty($name)) {
        $errors[] = "Category name is required.";
    } elseif (strlen($name) < 2) {
        $errors[] = "Category name must be at least 2 characters long.";
    } elseif (strlen($name) > 100) {
        $errors[] = "Category name must not exceed 100 characters.";
    }

    // Generate slug if empty
    if (empty($slug)) {
        $slug = createSlug($name);
        $category['slug'] = $slug;
    } else {
        $slug = createSlug($slug);
        $category['slug'] = $slug;
    }

    // Check if category name already exists (case-insensitive)
    $db->where('LOWER(name)', strtolower($name));
    if ($id > 0) {
        $db->where('id', $id, '!=');
    }
    if ($db->has('categories')) {
        $errors[] = "A category with this name already exists.";
    }

    // Handle image upload
    $uploadedImage = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__) . '/assets/categories/';
        $thumbDir = $uploadDir . 'thumbs/';
        
        // Create directories if they don't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileMimeType = mime_content_type($_FILES['image']['tmp_name']);
        
        // Validate file type
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, GIF, and WebP images are allowed.";
        } 
        // Validate file size (max 5MB)
        else if ($_FILES['image']['size'] > (5 * 1024 * 1024)) {
            $errors[] = "Image size must be less than 5MB.";
        } 
        // Process the image
        else {
            $fileName = 'cat_' . uniqid() . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                // Verify the image is valid
                if (@getimagesize($targetPath)) {
                    $uploadedImage = 'assets/categories/' . $fileName;
                    $category['image'] = $uploadedImage;
                    
                    // Create thumbnail
                    if (!createThumbnail($targetPath, $thumbDir . $fileName, 200, 200)) {
                        $errors[] = "Failed to create thumbnail. The image may be corrupted.";
                        @unlink($targetPath); // Remove the uploaded file if thumbnail creation fails
                    }
                } else {
                    $errors[] = "The uploaded file is not a valid image.";
                    @unlink($targetPath); // Remove the invalid file
                }
            } else {
                $errors[] = "Failed to upload image. Please check directory permissions.";
            }
        }
    }

    // If no errors, save to database
    if (empty($errors)) {
        // Start transaction
        $db->startTransaction();
        
        try {
            // Double-check slug uniqueness right before insert
            $check = $db->rawQueryOne("SELECT COUNT(*) as count FROM categories WHERE slug = ?" . ($id > 0 ? " AND id != $id" : ""), [$slug]);
            if ($check['count'] > 0) {
                throw new Exception('A category with this slug already exists.');
            }
            
            // Insert the category
            $id = $db->insert('categories', $category);
            
            if (!$id) {
                throw new Exception('Failed to insert category: ' . $db->getLastError());
            }
            
            // If we get here, the insert was successful
            $db->commit();
            
            // Clean up any uploaded files if they exist
            if (!empty($uploadedImage)) {
                $fullPath = dirname(__DIR__) . '/' . ltrim($uploadedImage, '/');
                $thumbPath = dirname($fullPath) . '/thumbs/' . basename($fullPath);
                
                if (file_exists($fullPath)) @unlink($fullPath);
                if (file_exists($thumbPath)) @unlink($thumbPath);
            }
            
            // Set success message
            $_SESSION['success'] = [
                'title' => 'Success!',
                'message' => 'Category has been added successfully.',
                'category_id' => $id
            ];
            
            // Redirect to categories list
            header('Location: category-all.php');
            exit();
            
        } catch (Exception $e) {
            // Something went wrong, rollback the transaction
            $db->rollback();
            
            // Log the error
            error_log('Category insert failed: ' . $e->getMessage());
            
            // Clean up uploaded files if any
            if (!empty($uploadedImage)) {
                $fullPath = dirname(__DIR__) . '/' . ltrim($uploadedImage, '/');
                $thumbPath = dirname($fullPath) . '/thumbs/' . basename($fullPath);
                
                if (file_exists($fullPath)) @unlink($fullPath);
                if (file_exists($thumbPath)) @unlink($thumbPath);
            }
            
            // User-friendly error message
            $errors[] = 'Failed to add category. Please try again with a different name or slug.';
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

/**
 * Create a URL-friendly slug from a string
 * 
 * @param string $string The string to convert to a slug
 * @return string The generated slug
 */
function createSlug($string) {
    // Convert to ASCII if possible
    if (function_exists('iconv')) {
        $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    }
    
    // Remove all non-word characters (keeps letters, numbers, and dashes)
    $string = preg_replace('/[^\w\s-]/', '', $string);
    
    // Replace all whitespace with a single dash
    $string = preg_replace('/\s+/', '-', $string);
    
    // Replace multiple dashes with a single dash
    $string = preg_replace('/-+/', '-', $string);
    
    // Convert to lowercase and trim dashes from beginning/end
    $slug = strtolower(trim($string, '-'));
    
    // If the slug is empty after processing (e.g., input was only special chars)
    if (empty($slug)) {
        $slug = 'category-' . uniqid();
    }
    
    return $slug;
}

/**
 * Create a thumbnail from an image
 */
function createThumbnail($sourcePath, $targetPath, $width, $height) {
    $dir = dirname($targetPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        case 'webp':
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$sourceImage) return false;
    
    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);
    
    // Calculate aspect ratio
    $sourceAspect = $sourceWidth / $sourceHeight;
    $thumbAspect = $width / $height;
    
    if ($sourceAspect > $thumbAspect) {
        // Source is wider than thumbnail
        $newHeight = $height;
        $newWidth = (int)($height * $sourceAspect);
    } else {
        // Source is taller than thumbnail
        $newWidth = $width;
        $newHeight = (int)($width / $sourceAspect);
    }
    
    $thumb = imagecreatetruecolor($width, $height);
    
    // Handle transparency for PNG and GIF
    if ($extension === 'png' || $extension === 'gif') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $width, $height, $transparent);
    }
    
    // Resize and crop
    imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
    
    // Save the thumbnail
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($thumb, $targetPath, 90);
            break;
        case 'png':
            imagepng($thumb, $targetPath, 9);
            break;
        case 'gif':
            imagegif($thumb, $targetPath);
            break;
        case 'webp':
            imagewebp($thumb, $targetPath, 90);
            break;
    }
    
    imagedestroy($sourceImage);
    imagedestroy($thumb);
    
    return true;
}

    // Handle image upload
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $max_size = 5 * 1024 * 1024; // 5MB
        $allowed_types = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        
        // Validate file type and size
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (!array_key_exists($file_type, $allowed_types) || $allowed_types[$file_type] !== $file_extension) {
            $errors[] = 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.';
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = 'Image size must not exceed 5MB.';
        } else {
            $uploadDir = dirname(__DIR__) . '/assets/categories/';
            $thumbDir = $uploadDir . 'thumbs/';
            
            // Create directories if they don't exist with proper permissions
            if (!file_exists($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                $errors[] = 'Failed to create upload directory. Please check directory permissions.';
            }
            
            if (empty($errors) && !file_exists($thumbDir) && !mkdir($thumbDir, 0755, true)) {
                $errors[] = 'Failed to create thumbnail directory. Please check directory permissions.';
            }
            
            if (empty($errors)) {
                $image_name = 'cat_' . time() . '_' . uniqid() . '.' . $file_extension;
                $uploadPath = $uploadDir . $image_name;
                $thumbPath = $thumbDir . $image_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    // Set proper permissions
                    chmod($uploadPath, 0644);
                    
                    // Create thumbnail
                    if ($this->createThumbnail($uploadPath, $thumbPath, 300, 300)) {
                        chmod($thumbPath, 0644);
                    } else {
                        $errors[] = 'Failed to create thumbnail. The image may be corrupted or in an unsupported format.';
                        // If thumbnail creation fails, remove the uploaded image
                        if (file_exists($uploadPath)) {
                            unlink($uploadPath);
                        }
                        $image_name = '';
                    }
                } else {
                    $errors[] = 'Failed to upload image. Please check directory permissions.';
                }
            }
        }
    }

    // Insert category if no errors
    if (empty($errors)) {
        try {
            // Required fields with validation
            $fields = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'is_active' => $status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Add optional fields if they have values
            $optionalFields = [
                'image' => $image_name,
                'parent_id' => $parentId,
                'sort_order' => $sortOrder,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'meta_keywords' => $metaKeywords
            ];
            
            // Add non-empty optional fields
            foreach ($optionalFields as $field => $value) {
                if (!empty($value) || $value === 0) {
                    $fields[$field] = $value;
                }
            }
            
            // Get table columns for validation
            $tableInfo = $db->rawQuery("SHOW COLUMNS FROM categories");
            $dbColumns = [];
            foreach ($tableInfo as $column) {
                $dbColumns[] = $column['Field'];
            }
            
            // Filter fields to only include existing columns
            $fields = array_intersect_key($fields, array_flip($dbColumns));
            
            // Prepare and execute the insert
            $columns = array_keys($fields);
            $placeholders = array_fill(0, count($columns), '?');
            $values = array_values($fields);
            
            $sql = "INSERT INTO categories (" . implode(', ', $columns) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
                    
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute($values)) {
                    // Commit the transaction
                    $db->commit();
                    
                    // Set success message
                    $_SESSION['success'] = 'Category added successfully';
                    
                    // Redirect to avoid form resubmission
                    header('Location: category-all.php');
                    exit();
                } else {
                    throw new Exception('Failed to execute database query');
                }
        } catch (Exception $e) {
            // Rollback the transaction on error
            $db->rollback();
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }

// Include the header after all processing is done
require __DIR__ . '/components/header.php';
?>

</head>

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <!-- changed content -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6>Please fix the following errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
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
                                    <h5 class="card-title mb-0">Category Details</h5>
                                    <a href="category-all.php" class="btn btn-primary ml-auto">
                                        <i class="fas fa-arrow-left"></i> Back to Categories
                                    </a>
                                </div>

                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="name">Category Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                                            required maxlength="100">
                                        <small class="form-text text-muted">Enter a unique category name (2-100 characters)</small>
                                    </div>
                                    <!-- slug -->
                                    <div class="form-group">
                                        <label for="slug">Slug</label>
                                        <input type="text" class="form-control" id="slug" name="slug"
                                            value="<?php echo isset($slug) ? $slug : ''; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"
                                            maxlength="500"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                                        <small class="form-text text-muted">Optional description (max 500 characters)</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="image">Category Image</label>
                                        <input type="file" class="form-control-file" id="image" name="image"
                                            accept="image/jpeg,image/png,image/gif,image/webp">
                                        <small class="form-text text-muted">
                                            Optional. Supported formats: JPEG, PNG, GIF, WebP. Max size: 5MB
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="1" <?php echo (!isset($status) || $status === '1') ? 'selected' : ''; ?>>
                                                Active
                                            </option>
                                            <option value="0" <?php echo (isset($status) && $status === '0') ? 'selected' : ''; ?>>
                                                Inactive
                                            </option>
                                        </select>
                                        <small class="form-text text-muted">
                                            Inactive categories won't be visible to customers
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Add Category
                                        </button>
                                        <a href="category-all.php" class="btn btn-secondary ml-2">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-info-circle"></i> Category Guidelines
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
                                        Use high-quality images (square format works best)
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
                                    <i class="fas fa-image"></i> Image Tips
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
                <!-- changed content  ends-->
            </main>
            <!-- footer -->
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
</body>

</html>