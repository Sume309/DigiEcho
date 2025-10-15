<?php
header('Location: category-edit-enhanced.php' . (isset($_GET['id']) ? '?id=' . $_GET['id'] : ''));
exit;
?>

// Function to get all main categories
function getMainCategories($pdo) {
    return $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
}

$pdo = new mysqli(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
$errors = [];
$success = '';

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$category_id) {
    header('Location: category-all.php');
    exit;
}

// Fetch existing category data
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();

if (!$category) {
    header('Location: category-all.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];

    // Validation
    if (empty($name)) {
        $errors[] = "Category name is required.";
    }

    if (strlen($name) < 2) {
        $errors[] = "Category name must be at least 2 characters long.";
    }

    if (strlen($name) > 100) {
        $errors[] = "Category name must not exceed 100 characters.";
    }

    // Check if category name already exists (excluding current category)
    if (!empty($name)) {
        $check_name = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $check_name->bind_param("si", $name, $category_id);
        $check_name->execute();
        $result = $check_name->get_result();
        if ($result->fetch_assoc()) {
            $errors[] = "A category with this name already exists.";
        }
    }

    // Handle image upload
    $image_name = $category['image']; // Keep existing image by default
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Only JPEG, PNG, GIF, and WebP images are allowed.";
        }

        if ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Image size must not exceed 5MB.";
        }

        if (empty($errors)) {
            $upload_dir = settings()['physical_path'] . '/assets/categories/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_image_name = uniqid() . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_image_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if it exists
                if ($category['image'] && file_exists($upload_dir . $category['image'])) {
                    unlink($upload_dir . $category['image']);
                }
                $image_name = $new_image_name;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    // Update category if no errors
    if (empty($errors)) {
        try {
            // Get parent_id from form
            $parent_id = isset($_POST['is_subcategory']) && $_POST['is_subcategory'] == '1' && !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
            
            // Prevent making a category its own parent
            if ($parent_id == $category_id) {
                $errors[] = "A category cannot be its own parent.";
            } else {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, image = ?, is_active = ?, parent_id = ?, updated_at = NOW() WHERE id = ?");
                
                // Convert boolean to integer for database
                $status_int = $status ? 1 : 0;
                
                // Bind parameters with correct types
                $stmt->bind_param("ssssiis", $name, $slug, $description, $image_name, $status_int, $parent_id, $category_id);
                
                if ($stmt->execute()) {
                    $success = "Category updated successfully!";
                    // Refresh category data
                    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
                    $stmt->bind_param("i", $category_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $category = $result->fetch_assoc();
                } else {
                    $errors[] = "Failed to update category: " . $pdo->error;
                }
            }
        } catch (Exception $e) {
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
                                    <h5 class="card-title mb-0">Edit Category</h5>
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
                                            value="<?php echo htmlspecialchars($category['name']); ?>"
                                            required maxlength="100">
                                        <small class="form-text text-muted">Enter a unique category name (2-100 characters)</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="slug">Slug</label>
                                        <input type="text" class="form-control" id="slug" name="slug"
                                            value="<?php echo htmlspecialchars($category['slug']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="is_subcategory">Is this a subcategory?</label>
                                        <select class="form-control" id="is_subcategory" name="is_subcategory" onchange="toggleParentCategory(this.value)">
                                            <option value="0">No, this is a main category</option>
                                            <option value="1" <?php echo (isset($category['parent_id']) && $category['parent_id'] > 0) ? 'selected' : ''; ?>>Yes, this is a subcategory</option>
                                        </select>
                                    </div>

                                    <div class="form-group" id="parent_category_group" style="display: none;">
                                        <label for="parent_id">Parent Category</label>
                                        <select class="form-control" id="parent_id" name="parent_id">
                                            <option value="0">-- No Parent (Main Category) --</option>
                                            <?php
                                            $parent_cats = $pdo->query("SELECT id, name FROM categories WHERE parent_id = 0 AND id != $category_id");
                                            while($parent = $parent_cats->fetch_assoc()):
                                                $selected = ($category['parent_id'] == $parent['id']) ? 'selected' : '';
                                                echo "<option value='{$parent['id']}' $selected>{$parent['name']}</option>";
                                            endwhile;
                                            ?>
                                        </select>
                                        <small class="form-text text-muted">Select a parent category to make this a subcategory</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"
                                            maxlength="500"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                        <small class="form-text text-muted">Optional description (max 500 characters)</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="image">Category Image</label>
                                        <?php if ($category['image']): ?>
                                            <div class="mb-2">
                                                <img src="<?= settings()['root'] ?>/assets/categories/<?= $category['image'] ?>" 
                                                     width="100" height="100" class="img-thumbnail">
                                                <p class="small text-muted">Current image</p>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control-file" id="image" name="image"
                                            accept="image/jpeg,image/png,image/gif,image/webp">
                                        <small class="form-text text-muted">
                                            Leave empty to keep current image. Supported formats: JPEG, PNG, GIF, WebP. Max size: 5MB
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="1" <?php echo ($category['is_active'] == 1) ? 'selected' : ''; ?>>
                                                Active
                                            </option>
                                            <option value="0" <?php echo ($category['is_active'] == 0) ? 'selected' : ''; ?>>
                                                Inactive
                                            </option>
                                        </select>
                                        <small class="form-text text-muted">
                                            Inactive categories won't be visible to customers
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Category
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
    <script>
    // Show/hide parent category based on subcategory selection
    function toggleParentCategory(isSubcategory) {
        const parentGroup = document.getElementById('parent_category_group');
        const parentSelect = document.getElementById('parent_id');
        
        if (isSubcategory === '1') {
            parentGroup.style.display = 'block';
            parentSelect.setAttribute('required', 'required');
        } else {
            parentGroup.style.display = 'none';
            parentSelect.removeAttribute('required');
            parentSelect.value = '';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const isSubcategory = document.getElementById('is_subcategory').value;
        toggleParentCategory(isSubcategory);
    });
    </script>
    <script src="<?= settings()['adminpage'] ?>assets/demo/chart-bar-demo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
    <script src="<?= settings()['adminpage'] ?>assets/js/datatables-simple-demo.js"></script>
</body>

</html>
