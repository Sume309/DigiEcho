<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('HTTP/1.1 503 Service Unauthorized');
    exit('Unauthorized access');
}

$db = new MysqliDb([
    'host' => settings()['hostname'],
    'username' => settings()['user'],
    'password' => settings()['password'],
    'db' => settings()['database'],
    'port' => 3306,
    'prefix' => '',
    'charset' => 'utf8mb4'
]);

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    // Handle category deletion
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        // Check if category has products
        $db->where('category_id', $id);
        $productCount = $db->getValue('products', 'COUNT(*)');
        
        if ($productCount > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete category with associated products']);
            exit;
        }
        
        // Get category image before deletion
        $db->where('id', $id);
        $category = $db->getOne('categories', ['image']);
        
        // Delete category
        $db->where('id', $id);
        if ($db->delete('categories')) {
            // Delete associated images
            if (!empty($category['image'])) {
                $uploadDir = dirname(__DIR__) . '/assets/categories/';
                $thumbDir = $uploadDir . 'thumbs/';
                
                if (file_exists($uploadDir . $category['image'])) {
                    unlink($uploadDir . $category['image']);
                }
                if (file_exists($thumbDir . $category['image'])) {
                    unlink($thumbDir . $category['image']);
                }
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
        }
        exit;
    }
    
    // Handle category status toggle
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $status = (int)$_POST['status'];
        
        $db->where('id', $id);
        if ($db->update('categories', ['is_active' => $status])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        exit;
    }
    
    // Handle category data for editing
    if (isset($_GET['action']) && $_GET['action'] === 'get_category' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $db->where('id', $id);
        $category = $db->getOne('categories');
        
        if ($category) {
            echo json_encode(['success' => true, 'data' => $category]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Category not found']);
        }
        exit;
    }
    
    // Handle category list for DataTables
    if (isset($_GET['action']) && $_GET['action'] === 'list') {
        $columns = [
            0 => 'id',
            1 => 'name',
            2 => 'slug',
            3 => 'sort_order',
            4 => 'is_active',
            5 => 'created_at',
            6 => 'actions'
        ];
        
        // Get total records
        $totalRecords = $db->getValue('categories', 'COUNT(*)');
        $totalFiltered = $totalRecords;
        
        // Build where conditions
        $where = [];
        if (!empty($_GET['search']['value'])) {
            $search = $_GET['search']['value'];
            $where[] = "(name LIKE '%$search%' OR slug LIKE '%$search%' OR description LIKE '%$search%')";
            $totalFiltered = $db->getValue('categories', 'COUNT(*)', $where);
        }
        
        // Ordering
        $order = $columns[$_GET['order'][0]['column']] ?? 'id';
        $dir = $_GET['order'][0]['dir'] ?? 'desc';
        
        // Pagination
        $limit = (int)$_GET['length'];
        $offset = (int)$_GET['start'];
        
        // Get filtered data
        $db->orderBy($order, strtoupper($dir));
        if (!empty($where)) {
            $db->where(implode(' AND ', $where));
        }
        $categories = $db->get('categories', [$offset, $limit]);
        
        // Prepare data for DataTables
        $data = [];
        foreach ($categories as $category) {
            $nestedData = [];
            $nestedData[] = $category['id'];
            $nestedData[] = htmlspecialchars($category['name']);
            $nestedData[] = htmlspecialchars($category['slug']);
            $nestedData[] = $category['sort_order'];
            $nestedData[] = $category['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            $nestedData[] = date('M d, Y', strtotime($category['created_at']));
            
            // Action buttons
            $viewBtn = '<a href="category-view.php?id=' . $category['id'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>';
            $editBtn = '<button class="btn btn-sm btn-primary edit-category" data-id="' . $category['id'] . '"><i class="fas fa-edit"></i></button>';
            $deleteBtn = '<button class="btn btn-sm btn-danger delete-category" data-id="' . $category['id'] . '"><i class="fas fa-trash"></i></button>';
            $statusBtn = $category['is_active'] ? 
                '<button class="btn btn-sm btn-warning toggle-status" data-id="' . $category['id'] . '" data-status="0"><i class="fas fa-eye-slash"></i></button>' :
                '<button class="btn btn-sm btn-success toggle-status" data-id="' . $category['id'] . '" data-status="1"><i class="fas fa-eye"></i></button>';
            
            $nestedData[] = '<div class="btn-group">' . $viewBtn . $editBtn . $statusBtn . $deleteBtn . '</div>';
            
            $data[] = $nestedData;
        }
        
        // Return JSON response
        $json_data = [
            "draw" => intval($_GET['draw']),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ];
        
        echo json_encode($json_data);
        exit;
    }
}

// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $metaTitle = trim($_POST['meta_title'] ?? $name);
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $metaKeywords = trim($_POST['meta_keywords'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $parentId = (int)($_POST['parent_id'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Category name is required';
    }
    
    // Generate or clean the slug
    $baseSlug = !empty($slug) ? $slug : $name;
    $slug = createSlug($baseSlug);
    
    // Check if slug already exists and make it unique if needed
    $originalSlug = $slug;
    $counter = 1;
    
    do {
        $db->where('slug', $slug);
        if ($id > 0) {
            $db->where('id', $id, '!=');
        }
        $exists = $db->getOne('categories', 'id');
        
        if ($exists) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    } while ($exists);
    
    // Handle image upload
    $imageName = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        
        $fileType = mime_content_type($_FILES['image']['tmp_name']);
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (!array_key_exists($fileType, $allowedTypes) || $allowedTypes[$fileType] !== $fileExtension) {
            $errors[] = 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.';
        } elseif ($_FILES['image']['size'] > $maxSize) {
            $errors[] = 'Image size must not exceed 5MB.';
        } else {
            $uploadDir = dirname(__DIR__) . '/assets/categories/';
            $thumbDir = $uploadDir . 'thumbs/';
            
            // Create directories if they don't exist
            if (!file_exists($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                $errors[] = 'Failed to create upload directory';
            }
            
            if (empty($errors) && !file_exists($thumbDir) && !mkdir($thumbDir, 0755, true)) {
                $errors[] = 'Failed to create thumbnail directory';
            }
            
            if (empty($errors)) {
                $imageName = 'cat_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $imageName;
                $thumbPath = $thumbDir . $imageName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    chmod($uploadPath, 0644);
                    
                    // Create thumbnail
                    if (!createThumbnail($uploadPath, $thumbPath, 300, 300)) {
                        $errors[] = 'Failed to create thumbnail';
                        if (file_exists($uploadPath)) {
                            unlink($uploadPath);
                        }
                        $imageName = '';
                    } else {
                        chmod($thumbPath, 0644);
                    }
                } else {
                    $errors[] = 'Failed to upload image';
                }
            }
        }
    }
    
    // Update or insert category
    if (empty($errors)) {
        $categoryData = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
            'sort_order' => $sortOrder,
            'parent_id' => $parentId,
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($imageName)) {
            $categoryData['image'] = $imageName;
            
            // Delete old image if exists
            if ($id > 0) {
                $db->where('id', $id);
                $oldCategory = $db->getOne('categories', ['image']);
                
                if (!empty($oldCategory['image'])) {
                    $oldImage = $uploadDir . $oldCategory['image'];
                    $oldThumb = $thumbDir . $oldCategory['image'];
                    
                    if (file_exists($oldImage)) unlink($oldImage);
                    if (file_exists($oldThumb)) unlink($oldThumb);
                }
            }
        }
        
        if ($id > 0) {
            // Update existing category
            $db->where('id', $id);
            if ($db->update('categories', $categoryData)) {
                $_SESSION['success'] = 'Category updated successfully';
            } else {
                $errors[] = 'Failed to update category: ' . $db->getLastError();
            }
        } else {
            // Insert new category
            $categoryData['created_at'] = date('Y-m-d H:i:s');
            if ($db->insert('categories', $categoryData)) {
                $_SESSION['success'] = 'Category added successfully';
                $id = $db->getInsertId();
            } else {
                $errors[] = 'Failed to add category: ' . $db->getLastError();
            }
        }
        
        if (empty($errors)) {
            header('Location: categories.php');
            exit;
        }
    }
}

// Get all parent categories for dropdown
$parentCategories = $db->get('categories', null, ['id', 'name', 'parent_id']);

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

// Function to create thumbnail
function createThumbnail($sourcePath, $targetPath, $width, $height) {
    if (!file_exists($sourcePath)) {
        return false;
    }
    
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    list($origWidth, $origHeight, $type) = $imageInfo;
    
    // Calculate aspect ratio
    $aspectRatio = $origWidth / $origHeight;
    $newWidth = $width;
    $newHeight = $height;
    
    if ($width / $height > $aspectRatio) {
        $newWidth = $height * $aspectRatio;
    } else {
        $newHeight = $width / $aspectRatio;
    }
    
    // Create image resource based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$sourceImage) {
        return false;
    }
    
    // Create new image
    $thumb = imagecreatetruecolor($width, $height);
    
    // Handle transparency for PNG and GIF
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
        imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    
    // Resize and center the image
    $dstX = ($width - $newWidth) / 2;
    $dstY = ($height - $newHeight) / 2;
    
    imagecopyresampled(
        $thumb, $sourceImage,
        $dstX, $dstY, 0, 0,
        $newWidth, $newHeight, $origWidth, $origHeight
    );
    
    // Save the thumbnail
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($thumb, $targetPath, 90);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($thumb, $targetPath, 9);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($thumb, $targetPath);
            break;
        case IMAGETYPE_WEBP:
            $result = imagewebp($thumb, $targetPath, 90);
            break;
    }
    
    // Free up memory
    imagedestroy($sourceImage);
    imagedestroy($thumb);
    
    return $result;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - <?= settings()['site_name'] ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .category-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .btn-group .btn {
            margin-right: 2px;
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
                    <h1 class="mt-4">Category Management</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Categories</li>
                    </ol>
                    
                    <?php if (!empty($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6>Please fix the following errors:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-table me-1"></i>
                                    All Categories
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal">
                                    <i class="fas fa-plus"></i> Add New Category
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="categoriesTable" class="table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded by DataTables -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            
            <!-- Add/Edit Category Modal -->
            <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="categoryForm" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="category_id" id="category_id" value="">
                            <input type="hidden" name="save_category" value="1">
                            
                            <div class="modal-header">
                                <h5 class="modal-title" id="categoryModalLabel">Add New Category</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="slug" class="form-label">Slug</label>
                                            <input type="text" class="form-control" id="slug" name="slug">
                                            <small class="text-muted">Leave blank to auto-generate from name</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="parent_id" class="form-label">Parent Category</label>
                                            <select class="form-select" id="parent_id" name="parent_id">
                                                <option value="0">None (Top Level)</option>
                                                <?php foreach ($parentCategories as $parent): ?>
                                                    <option value="<?= $parent['id'] ?>"><?= htmlspecialchars($parent['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">Category Image</h6>
                                            </div>
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <img id="imagePreview" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='200' viewBox='0 0 300 200'%3E%3Crect width='300' height='200' fill='%23f8f9fa'/%3E%3Ctext x='50%25' y='50%25' font-family='sans-serif' font-size='14' text-anchor='middle' dominant-baseline='middle' fill='%236c757d'%3ENo image selected%3C/text%3E%3C/svg%3E" 
                                                         alt="Category Image" class="img-fluid rounded" style="max-height: 150px;">
                                                </div>
                                                <div class="d-grid">
                                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                                    <small class="text-muted">Recommended size: 800x600px (max 5MB)</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">Settings</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="sort_order" class="form-label">Sort Order</label>
                                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                                                </div>
                                                
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                                    <label class="form-check-label" for="is_active">Active</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">SEO Settings</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="meta_title" class="form-label">Meta Title</label>
                                                    <input type="text" class="form-control" id="meta_title" name="meta_title">
                                                    <small class="text-muted">If empty, category name will be used</small>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="meta_description" class="form-label">Meta Description</label>
                                                    <textarea class="form-control" id="meta_description" name="meta_description" rows="2"></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                                    <input type="text" class="form-control" id="meta_keywords" name="meta_keywords">
                                                    <small class="text-muted">Comma-separated keywords</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Category</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this category? This action cannot be undone.</p>
                            <p class="text-danger">Note: If this category has subcategories or products, it cannot be deleted.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#categoriesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: 'categories.php',
                    type: 'GET',
                    data: { action: 'list' },
                    dataType: 'json',
                    dataSrc: 'data'
                },
                columns: [
                    { data: 0 },
                    { data: 1 },
                    { data: 2 },
                    { data: 3 },
                    { data: 4 },
                    { data: 5 },
                    { 
                        data: 6,
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [[0, 'desc']],
                responsive: true
            });
            
            // Initialize modal
            var categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
            var categoryForm = document.getElementById('categoryForm');
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            var deleteId = null;
            
            // Handle add new category
            $('.btn-add-category').click(function() {
                $('#categoryModalLabel').text('Add New Category');
                categoryForm.reset();
                $('#category_id').val('');
                $('#imagePreview').attr('src', 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'200\' viewBox=\'0 0 300 200\'%3E%3Crect width=\'300\' height=\'200\' fill=\'%23f8f9fa\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' font-family=\'sans-serif\' font-size=\'14\' text-anchor=\'middle\' dominant-baseline=\'middle\' fill=\'%236c757d\'%3ENo image selected%3C/text%3E%3C/svg%3E');
                $('#is_active').prop('checked', true);
            });
            
            // Handle edit category
            $(document).on('click', '.edit-category', function() {
                var id = $(this).data('id');
                
                $.ajax({
                    url: 'categories.php',
                    type: 'GET',
                    dataType: 'json',
                    data: { action: 'get_category', id: id },
                    success: function(response) {
                        if (response.success) {
                            var category = response.data;
                            $('#categoryModalLabel').text('Edit Category');
                            categoryForm.reset();
                            $('#category_id').val(category.id);
                            $('#name').val(category.name);
                            $('#slug').val(category.slug);
                            $('#description').val(category.description);
                            $('#parent_id').val(category.parent_id || 0);
                            $('#meta_title').val(category.meta_title);
                            $('#meta_description').val(category.meta_description);
                            $('#meta_keywords').val(category.meta_keywords);
                            $('#sort_order').val(category.sort_order || 0);
                            $('#is_active').prop('checked', parseInt(category.is_active) === 1);
                            
                            if (category.image) {
                                $('#imagePreview').attr('src', '../../assets/categories/' + category.image);
                            } else {
                                $('#imagePreview').attr('src', 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'200\' viewBox=\'0 0 300 200\'%3E%3Crect width=\'300\' height=\'200\' fill=\'%23f8f9fa\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' font-family=\'sans-serif\' font-size=\'14\' text-anchor=\'middle\' dominant-baseline=\'middle\' fill=\'%236c757d\'%3ENo image selected%3C/text%3E%3C/svg%3E');
                            }
                            
                            categoryModal.show();
                        } else {
                            alert(response.message || 'Failed to load category data');
                        }
                    },
                    error: function() {
                        alert('Error loading category data');
                    }
                });
            });
            
            // Handle delete category
            $(document).on('click', '.delete-category', function() {
                deleteId = $(this).data('id');
                deleteModal.show();
            });
            
            // Confirm delete
            $('#confirmDelete').click(function() {
                if (!deleteId) return;
                
                $.ajax({
                    url: 'categories.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { 
                        action: 'delete',
                        id: deleteId
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            deleteModal.hide();
                        } else {
                            alert(response.message || 'Failed to delete category');
                        }
                    },
                    error: function() {
                        alert('Error deleting category');
                    }
                });
            });
            
            // Toggle status
            $(document).on('click', '.toggle-status', function() {
                var id = $(this).data('id');
                var status = $(this).data('status');
                var $btn = $(this);
                
                $.ajax({
                    url: 'categories.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { 
                        action: 'toggle_status',
                        id: id,
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                        } else {
                            alert(response.message || 'Failed to update status');
                        }
                    },
                    error: function() {
                        alert('Error updating status');
                    }
                });
            });
            
            // Auto-generate slug from name
            $('#name').on('blur', function() {
                if ($('#slug').val() === '') {
                    var slug = $('#name').val()
                        .toLowerCase()
                        .replace(/[^\w\s-]/g, '') // remove non-word chars
                        .replace(/\s+/g, '-')     // replace spaces with -
                        .replace(/--+/g, '-')     // replace multiple - with single
                        .trim();
                    $('#slug').val(slug);
                }
            });
            
            // Preview image before upload
            $('#image').on('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#imagePreview').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>
