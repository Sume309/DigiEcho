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

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

// Get categories, subcategories, and brands for form
$categories = $db->get('categories', null, ['id', 'name']);
$subcategories = $db->get('subcategories', null, ['id', 'name', 'category_id']);
$brands = $db->get('brands', null, ['id', 'name']);

require __DIR__ . '/components/header.php';

// Include notification helper
require_once __DIR__ . '/components/notification_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productData = [
        'name' => $_POST['name'],
        'sku' => $_POST['sku'],
        'slug' => $_POST['slug'],
        'short_description' => $_POST['short_description'],
        'description' => $_POST['description'],
        'category_id' => $_POST['category_id'],
        'subcategory_id' => $_POST['subcategory_id'],
        'brand_id' => $_POST['brand_id'],
        'selling_price' => $_POST['selling_price'],
        'cost_price' => $_POST['cost_price'],
        'markup_percentage' => $_POST['markup_percentage'],
        'discount_price' => $_POST['discount_price'],
        'discount_start_date' => $_POST['discount_start_date'],
        'discount_end_date' => $_POST['discount_end_date'],
        'stock_quantity' => $_POST['stock_quantity'],
        'min_stock_level' => $_POST['min_stock_level'],
        'barcode' => $_POST['barcode'],
        'weight' => $_POST['weight'],
        'dimensions' => $_POST['dimensions'],
        'meta_title' => $_POST['meta_title'],
        'meta_description' => $_POST['meta_description'],
        'meta_keywords' => $_POST['meta_keywords'],
        'status' => $_POST['status'],
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_hot_item' => isset($_POST['is_hot_item']) ? 1 : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'sort_order' => $_POST['sort_order'],
        'tags' => $_POST['tags'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    $insertedId = $db->insert('products', $productData);

    if ($insertedId) {
        // Notify about new product creation
        notifyProductActivity(
            $insertedId, 
            $productData['name'], 
            'created',
            $_SESSION['user_id'] ?? null,
            $_SESSION['username'] ?? null
        );

        $_SESSION['success'] = 'Product created successfully!';
        header('Location: product-management.php');
        exit;
    } else {
        $_SESSION['error'] = 'Failed to create product. Please try again.';
        header('Location: add-product.php');
        exit;
    }
}

?>
<style>
    .form-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 0 25px rgba(0,0,0,0.08);
    }
    
    .nav-tabs .nav-link {
        font-weight: 500;
        color: #6c757d;
        border: none;
        border-bottom: 3px solid transparent;
    }
    
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom: 3px solid #0d6efd;
        background: transparent;
    }
    
    .image-preview {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px dashed #dee2e6;
    }
    
    .gallery-image-preview {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        position: relative;
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    
    .gallery-image-preview:hover {
        transform: scale(1.05);
    }
    
    .image-preview-container {
        position: relative;
        display: inline-block;
    }
    
    .remove-image-btn {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #dc3545;
        border: 2px solid white;
        color: white;
        font-size: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        z-index: 10;
    }
    
    .remove-image-btn:hover {
        background: #c82333;
        transform: scale(1.1);
    }
    
    .upload-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: rgba(13, 110, 253, 0.2);
        border-radius: 0 0 8px 8px;
        overflow: hidden;
    }
    
    .upload-progress-bar {
        height: 100%;
        background: #0d6efd;
        width: 0%;
        transition: width 0.3s ease;
    }
    
    .image-upload-loading {
        pointer-events: none;
        opacity: 0.7;
    }
    
    .image-upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 1;
        pointer-events: auto !important;
    }
    
    .image-upload-area:hover {
        border-color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.05);
        transform: translateY(-2px);
    }
    
    .image-upload-area.dragover {
        border-color: #198754;
        background-color: rgba(25, 135, 84, 0.1);
        border-style: solid;
    }
    
    .image-upload-area.dragover::before {
        content: 'Drop files here';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: bold;
        color: #198754;
        font-size: 1.1rem;
        z-index: 10;
    }
    
    .image-upload-area.dragover > * {
        opacity: 0.3;
    }
    
    .form-label {
        font-weight: 500;
        color: #495057;
    }
    
    .section-divider {
        border-top: 1px solid #e9ecef;
        margin: 2rem 0;
    }
    
    .section-title {
        position: relative;
        padding-bottom: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #0d6efd;
        display: inline-block;
    }




    
    
</style>

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                        <div>
                            <h1 class="h3 mb-1">Add New Product</h1>
                            <p class="text-muted mb-0">Create a new product with all details</p>
                        </div>
                        <div>
                            <a href="product-management.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Products
                            </a>
                        </div>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <form id="productForm" action="product-ajax.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-lg-8">
                                <div class="card form-card mb-4">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0">Product Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Basic Information -->
                                        <div class="mb-4">
                                            <h6 class="section-title">Basic Information</h6>
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="mb-3">
                                                        <label for="name" class="form-label">Product Name *</label>
                                                        <input type="text" class="form-control" id="name" name="name" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="sku" class="form-label">SKU *</label>
                                                        <input type="text" class="form-control" id="sku" name="sku" required>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="slug" class="form-label">Slug</label>
                                                <input type="text" class="form-control" id="slug" name="slug">
                                                <div class="form-text">Will be automatically generated if left empty</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="short_description" class="form-label">Short Description</label>
                                                <textarea class="form-control" id="short_description" name="short_description" rows="2"></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="description" class="form-label">Full Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="section-divider"></div>
                                        
                                        <!-- Categories -->
                                        <div class="mb-4">
                                            <h6 class="section-title">Categories</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="category_id" class="form-label">Category *</label>
                                                        <select class="form-select" id="category_id" name="category_id" required>
                                                            <option value="">Select Category</option>
                                                            <?php foreach ($categories as $category): ?>
                                                                <option value="<?php echo $category['id']; ?>">
                                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="subcategory_id" class="form-label">Subcategory</label>
                                                        <select class="form-select" id="subcategory_id" name="subcategory_id">
                                                            <option value="">Select Subcategory</option>
                                                            <!-- Will be populated dynamically -->
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="brand_id" class="form-label">Brand</label>
                                                <select class="form-select" id="brand_id" name="brand_id">
                                                    <option value="">Select Brand</option>
                                                    <?php foreach ($brands as $brand): ?>
                                                        <option value="<?php echo $brand['id']; ?>">
                                                            <?php echo htmlspecialchars($brand['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="section-divider"></div>
                                        
                                        <!-- Pricing -->
                                        <div class="mb-4">
                                            <h6 class="section-title">Pricing</h6>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="selling_price" class="form-label">Selling Price *</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">৳</span>
                                                            <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" min="0" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="cost_price" class="form-label">Cost Price</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">৳</span>
                                                            <input type="number" class="form-control" id="cost_price" name="cost_price" step="0.01" min="0">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="markup_percentage" class="form-label">Markup %</label>
                                                        <div class="input-group">
                                                            <input type="number" class="form-control" id="markup_percentage" name="markup_percentage" step="0.01" min="0">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="discount_price" class="form-label">Discount Price</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">৳</span>
                                                            <input type="number" class="form-control" id="discount_price" name="discount_price" step="0.01" min="0">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="discount_start_date" class="form-label">Discount Start</label>
                                                        <input type="date" class="form-control" id="discount_start_date" name="discount_start_date">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="discount_end_date" class="form-label">Discount End</label>
                                                        <input type="date" class="form-control" id="discount_end_date" name="discount_end_date">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="section-divider"></div>
                                        
                                        <!-- Inventory -->
                                        <div class="mb-4">
                                            <h6 class="section-title">Inventory</h6>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="min_stock_level" class="form-label">Min Stock Level</label>
                                                        <input type="number" class="form-control" id="min_stock_level" name="min_stock_level" min="0" value="5">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="barcode" class="form-label">Barcode</label>
                                                        <input type="text" class="form-control" id="barcode" name="barcode">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="weight" class="form-label">Weight (kg)</label>
                                                        <input type="number" class="form-control" id="weight" name="weight" step="0.01" min="0">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="dimensions" class="form-label">Dimensions</label>
                                                        <input type="text" class="form-control" id="dimensions" name="dimensions" placeholder="L x W x H">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="section-divider"></div>
                                        
                                        <!-- SEO -->
                                        <div class="mb-4">
                                            <h6 class="section-title">SEO Information</h6>
                                            <div class="mb-3">
                                                <label for="meta_title" class="form-label">Meta Title</label>
                                                <input type="text" class="form-control" id="meta_title" name="meta_title">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="meta_description" class="form-label">Meta Description</label>
                                                <textarea class="form-control" id="meta_description" name="meta_description" rows="2"></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                                <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" placeholder="keyword1, keyword2, keyword3">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div class="col-lg-4">
                                <!-- Image Upload -->


                                 <div class="card form-card mb-4">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0">Product Images</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="main_image" class="form-label">Main Image</label>
                                            <div class="image-upload-area" id="mainImageUpload">
                                                <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-primary"></i>
                                                <p class="mb-1">Click to upload or drag & drop</p>
                                                <p class="text-muted small">JPG, PNG, GIF, WebP (Max 5MB)</p>
                                                <input type="file" class="d-none" id="main_image" name="main_image" accept="image/*">
                                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="document.getElementById('main_image').click()">Browse Files</button>
                                                <div class="upload-progress d-none">
                                                    <div class="upload-progress-bar"></div>
                                                </div>
                                            </div>
                                            <div id="mainImagePreview" class="mt-2 d-none">
                                                <div class="image-preview-container">
                                                    <img src="" alt="Main Image Preview" class="image-preview">
                                                    <div class="remove-image-btn" data-target="main">
                                                        <i class="fas fa-times"></i>
                                                    </div>
                                                </div>
                                                <div class="mt-1">
                                                    <small class="text-muted" id="mainImageInfo"></small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="gallery_images" class="form-label">Gallery Images</label>
                                            <div class="image-upload-area" id="galleryImagesUpload">
                                                <i class="fas fa-images fa-2x mb-2 text-info"></i>
                                                <p class="mb-1">Click to upload or drag & drop</p>
                                                <p class="text-muted small">Multiple images allowed (Max 5MB each)</p>
                                                <input type="file" class="d-none" id="gallery_images" name="gallery_images[]" accept="image/*" multiple>
                                                <button type="button" class="btn btn-outline-info btn-sm mt-2" onclick="document.getElementById('gallery_images').click()">Browse Files</button>
                                                <div class="upload-progress d-none">
                                                    <div class="upload-progress-bar"></div>
                                                </div>
                                            </div>
                                            <div id="galleryImagesPreview" class="mt-2 d-none">
                                                <div class="d-flex flex-wrap gap-2" id="galleryPreviewContainer"></div>
                                                <div class="mt-2">
                                                    <small class="text-muted" id="galleryImageInfo"></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                               
                                
                                <!-- Product Options -->
                                <div class="card form-card mb-4">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0">Product Options</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="status">
                                                <option value="draft">Draft</option>
                                                <option value="active" selected>Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Display Options</label>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1">
                                                <label class="form-check-label" for="is_featured">
                                                    Featured Product
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="is_hot_item" name="is_hot_item" value="1">
                                                <label class="form-check-label" for="is_hot_item">
                                                    Hot Item
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                                <label class="form-check-label" for="is_active">
                                                    Active
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="sort_order" class="form-label">Sort Order</label>
                                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="tags" class="form-label">Tags</label>
                                            <input type="text" class="form-control" id="tags" name="tags" placeholder="tag1, tag2, tag3">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="card form-card">
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                                <i class="fas fa-save me-2"></i>Create Product
                                            </button>
                                            <a href="product-management.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-times me-2"></i>Cancel
                                            </a>
                                        </div>
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

    <!-- Scripts - Load jQuery first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="assets/js/scripts.js"></script>

    <script>
    // Check if jQuery is loaded
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded!');
        alert('jQuery is not loaded. Please refresh the page.');
    } else {
        console.log('jQuery version:', jQuery.fn.jquery);
    }
    
    $(document).ready(function() {
        console.log('DOM ready, initializing upload functionality...');
        
        // Test if elements exist
        console.log('Main upload area exists:', $('#mainImageUpload').length > 0);
        console.log('Gallery upload area exists:', $('#galleryImagesUpload').length > 0);
        console.log('Main image input exists:', $('#main_image').length > 0);
        console.log('Gallery images input exists:', $('#gallery_images').length > 0);
        
        // Test click events immediately
        setTimeout(function() {
            console.log('Testing click events after 1 second...');
            $('#mainImageUpload').trigger('click');
        }, 1000);
        
        // Auto-generate slug from product name
        $('#name').on('blur', function() {
            const name = $(this).val();
            if (name && !$('#slug').val()) {
                const slug = name.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim('-');
                $('#slug').val(slug);
            }
        });
        
        // Populate subcategories based on selected category
        $('#category_id').on('change', function() {
            const categoryId = $(this).val();
            $('#subcategory_id').empty().append('<option value="">Select Subcategory</option>');
            
            if (categoryId) {
                // Filter subcategories by category
                <?php foreach ($subcategories as $subcategory): ?>
                    if (<?php echo $subcategory['category_id']; ?> == categoryId) {
                        $('#subcategory_id').append(
                            '<option value="<?php echo $subcategory['id']; ?>"><?php echo htmlspecialchars($subcategory['name']); ?></option>'
                        );
                    }
                <?php endforeach; ?>
            }
        });
        
        // Enhanced Image upload functionality
        let galleryFiles = [];
        let mainImageFile = null;
        
        console.log('Image upload variables initialized:', { mainImageFile, galleryFiles });
        
        // File validation function
        function validateFile(file) {
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!allowedTypes.includes(file.type)) {
                return { valid: false, message: 'Please select a valid image file (JPEG, PNG, GIF, WebP)' };
            }
            
            if (file.size > maxSize) {
                return { valid: false, message: 'File size must be less than 5MB' };
            }
            
            return { valid: true };
        }
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Drag and drop functionality
        function setupDragAndDrop(element, callback) {
            element.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                element.classList.add('dragover');
            });
            
            element.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                element.classList.remove('dragover');
            });
            
            element.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                element.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    callback(files);
                }
            });
        }
        
        // Setup drag and drop for main image
        setupDragAndDrop(document.getElementById('mainImageUpload'), function(files) {
            const file = files[0];
            const validation = validateFile(file);
            
            if (!validation.valid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File',
                    text: validation.message
                });
                return;
            }
            
            mainImageFile = file;
            previewMainImage(file);
        });
        
        // Setup drag and drop for gallery images
        setupDragAndDrop(document.getElementById('galleryImagesUpload'), function(files) {
            const validFiles = [];
            const errors = [];
            
            for (let i = 0; i < files.length; i++) {
                const validation = validateFile(files[i]);
                if (validation.valid) {
                    validFiles.push(files[i]);
                } else {
                    errors.push(`${files[i].name}: ${validation.message}`);
                }
            }
            
            if (errors.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Some files were rejected',
                    html: errors.join('<br>'),
                    confirmButtonText: 'Continue with valid files'
                });
            }
            
            if (validFiles.length > 0) {
                addGalleryImages(validFiles);
            }
        });
        
        // Preview main image
        function previewMainImage(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#mainImagePreview img').attr('src', e.target.result);
                $('#mainImageInfo').text(`${file.name} (${formatFileSize(file.size)})`);
                $('#mainImagePreview').removeClass('d-none');
            };
            reader.readAsDataURL(file);
        }
        
        // Add gallery images
        function addGalleryImages(files) {
            for (let i = 0; i < files.length; i++) {
                galleryFiles.push(files[i]);
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageHtml = `
                        <div class="image-preview-container" data-index="${galleryFiles.length - 1}">
                            <img src="${e.target.result}" alt="Gallery Image" class="gallery-image-preview">
                            <div class="remove-image-btn" data-target="gallery" data-index="${galleryFiles.length - 1}">
                                <i class="fas fa-times"></i>
                            </div>
                        </div>
                    `;
                    $('#galleryPreviewContainer').append(imageHtml);
                };
                reader.readAsDataURL(files[i]);
            }
            
            $('#galleryImagesPreview').removeClass('d-none');
            updateGalleryInfo();
        }
        
        // Update gallery info
        function updateGalleryInfo() {
            const totalSize = galleryFiles.reduce((sum, file) => sum + file.size, 0);
            $('#galleryImageInfo').text(`${galleryFiles.length} images selected (${formatFileSize(totalSize)} total)`);
        }
        
        // Main image upload click handler


        // Main image upload click handler - Multiple approaches for reliability
        $('#mainImageUpload').on('click', function(e) {
            console.log('Main image upload area clicked');
            if (!$(e.target).hasClass('remove-image-btn')) {
                console.log('Triggering file input click');
                $('#main_image').click();
            } else {
                console.log('Remove button clicked, not triggering file input');
            }
        });
        
        // Alternative click handler for direct icon/text clicks
        $('#mainImageUpload i, #mainImageUpload p').on('click', function(e) {
            console.log('Direct icon/text clicked');
            e.stopPropagation();
            $('#main_image').click();
        });
        
        // Test click handler
        $('#mainImageUpload').on('mousedown', function(e) {
            console.log('Mouse down on upload area');
        });
        
        // Main image file input change
        $('#main_image').on('change', function(e) {
            console.log('Main image file input changed');
            const file = e.target.files[0];
            console.log('Selected file:', file);
            
            if (file) {
                const validation = validateFile(file);
                console.log('Validation result:', validation);
                
                if (!validation.valid) {
                    console.log('File validation failed:', validation.message);
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid File',
                        text: validation.message
                    });
                    this.value = '';
                    return;
                }
                
                console.log('Setting mainImageFile to:', file.name);
                mainImageFile = file;
                previewMainImage(file);
                console.log('Preview generated for:', file.name);
            }
        });
        
        // Gallery images upload click handler - Multiple approaches
        $('#galleryImagesUpload').on('click', function(e) {
            console.log('Gallery upload area clicked');
            if (!$(e.target).hasClass('remove-image-btn')) {
                console.log('Triggering gallery file input click');
                $('#gallery_images').click();
            }
        });
        
        // Alternative gallery click handler
        $('#galleryImagesUpload i, #galleryImagesUpload p').on('click', function(e) {
            console.log('Direct gallery icon/text clicked');
            e.stopPropagation();
            $('#gallery_images').click();
        });
        
        // Gallery images file input change
        $('#gallery_images').on('change', function(e) {
            const files = Array.from(e.target.files);
            const validFiles = [];
            const errors = [];
            
            files.forEach(file => {
                const validation = validateFile(file);
                if (validation.valid) {
                    validFiles.push(file);
                } else {
                    errors.push(`${file.name}: ${validation.message}`);
                }
            });
            
            if (errors.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Some files were rejected',
                    html: errors.join('<br>'),
                    confirmButtonText: 'Continue with valid files'
                });
            }
            
            if (validFiles.length > 0) {
                addGalleryImages(validFiles);
            }
            
            this.value = ''; // Reset input
        });
        

       
        // Remove image handlers
        $(document).on('click', '.remove-image-btn', function(e) {
            e.stopPropagation();
            
            const target = $(this).data('target');
            
            if (target === 'main') {
                $('#mainImagePreview').addClass('d-none');
                $('#main_image').val('');
                mainImageFile = null;
            } else if (target === 'gallery') {
                const index = $(this).data('index');
                
                // Remove from array
                galleryFiles.splice(index, 1);
                
                // Remove preview
                $(this).closest('.image-preview-container').remove();
                
                // Update indices for remaining images
                $('#galleryPreviewContainer .image-preview-container').each(function(i) {
                    $(this).attr('data-index', i);
                    $(this).find('.remove-image-btn').attr('data-index', i);
                });
                
                if (galleryFiles.length === 0) {
                    $('#galleryImagesPreview').addClass('d-none');
                } else {
                    updateGalleryInfo();
                }
            }
        });
        
        // Backup: Ensure form always has the file from input
        $('#productForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Form submission started');
            
            // Disable submit button and show loading
            const submitBtn = $('#submitBtn');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Creating Product...').prop('disabled', true);
            
            const formData = new FormData(this);
            console.log('FormData created from form');
            
            // Ensure main image is properly added
            const mainImageInput = document.getElementById('main_image');
            if (mainImageFile) {
                console.log('Using stored mainImageFile:', mainImageFile.name, 'Size:', mainImageFile.size);
                formData.set('main_image', mainImageFile);
            } else if (mainImageInput && mainImageInput.files.length > 0) {
                console.log('Using file from input directly:', mainImageInput.files[0].name);
                formData.set('main_image', mainImageInput.files[0]);
            } else {
                console.log('No main image file found in either variable or input');
            }
            
            // Add gallery images if selected
            formData.delete('gallery_images[]'); // Remove any existing gallery images
            if (galleryFiles.length > 0) {
                console.log('Adding', galleryFiles.length, 'gallery images to FormData');
                galleryFiles.forEach((file, index) => {
                    formData.append('gallery_images[]', file);
                    console.log('Added gallery image', index, ':', file.name);
                });
            } else {
                console.log('No gallery images selected');
            }
            
            // Debug: Log all FormData entries
            console.log('Final FormData contents:');
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    console.log(key, ':', value.name, '(', value.size, 'bytes)');
                } else {
                    console.log(key, ':', value);
                }
            }
            
            // Show upload progress
            showUploadProgress(true);
            
            $.ajax({
                url: 'product-ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    console.log('AJAX request starting to: product-ajax.php');
                    console.log('Request data size:', formData);
                },
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    
                    // Upload progress
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            updateUploadProgress(percentComplete);
                        }
                    }, false);
                    
                    return xhr;
                },
                success: function(response) {
                    console.log('AJAX success response:', response);
                    showUploadProgress(false);
                    
                    if (response.success) {
                        console.log('Product created successfully:', response.message);
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = 'product-management.php';
                        });
                    } else {
                        console.log('Product creation failed:', response.message);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message
                        });
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error occurred:');
                    console.log('XHR:', xhr);
                    console.log('Status:', status);
                    console.log('Error:', error);
                    console.log('Response Text:', xhr.responseText);
                    
                    showUploadProgress(false);
                    
                    let errorMessage = 'Failed to create product. Please try again.';
                    
                    if (xhr.status === 413) {
                        errorMessage = 'File size too large. Please reduce image sizes and try again.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Network error. Please check your connection and try again.';
                    } else if (xhr.responseText) {
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            errorMessage = errorResponse.message || errorMessage;
                        } catch (e) {
                            errorMessage = 'Server error: ' + xhr.responseText.substring(0, 100);
                        }
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Error!',
                        text: errorMessage
                    });
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });
        
        // Show/hide upload progress
        function showUploadProgress(show) {
            const progressElements = $('.upload-progress');
            if (show) {
                progressElements.removeClass('d-none');
                $('.image-upload-area').addClass('image-upload-loading');
            } else {
                progressElements.addClass('d-none');
                $('.image-upload-area').removeClass('image-upload-loading');
                updateUploadProgress(0);
            }
        }
        
        // Update upload progress
        function updateUploadProgress(percent) {
            $('.upload-progress-bar').css('width', percent + '%');
        }
        
        // Calculate markup percentage
        $('#selling_price, #cost_price').on('input', function() {
            const sellingPrice = parseFloat($('#selling_price').val()) || 0;
            const costPrice = parseFloat($('#cost_price').val()) || 0;
            
            if (costPrice > 0 && sellingPrice > 0) {
                const markup = ((sellingPrice - costPrice) / costPrice) * 100;
                $('#markup_percentage').val(markup.toFixed(2));
            }
        });
    });
    </script>
</body>
</html>