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

// Get product ID from URL
$productId = intval($_GET['id'] ?? 0);

if (!$productId) {
    $_SESSION['error'] = 'Invalid product ID';
    header('Location: product-management.php');
    exit;
}

// Get product details
$product = $db->where('id', $productId)->getOne('products');

if (!$product) {
    $_SESSION['error'] = 'Product not found';
    header('Location: product-management.php');
    exit;
}

// Get categories, subcategories, and brands for form
$categories = $db->get('categories', null, ['id', 'name']);
$subcategories = $db->get('subcategories', null, ['id', 'name', 'category_id']);
$brands = $db->get('brands', null, ['id', 'name']);

require __DIR__ . '/components/header.php'; ?>

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
        margin: 5px;
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
    
    .existing-gallery-image {
        position: relative;
        display: inline-block;
    }
    
    .remove-gallery-image {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 12px;
        z-index: 10;
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
                            <h1 class="h3 mb-1">Edit Product</h1>
                            <p class="text-muted mb-0">Update product details</p>
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
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        
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
                                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="sku" class="form-label">SKU *</label>
                                                        <input type="text" class="form-control" id="sku" name="sku" value="<?php echo htmlspecialchars($product['sku']); ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="slug" class="form-label">Slug</label>
                                                <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($product['slug']); ?>">
                                                <div class="form-text">Will be automatically generated if left empty</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="short_description" class="form-label">Short Description</label>
                                                <textarea class="form-control" id="short_description" name="short_description" rows="2"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="description" class="form-label">Full Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
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
                                                                <option value="<?php echo $category['id']; ?>" <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
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
                                                            <?php foreach ($subcategories as $subcategory): ?>
                                                                <?php if ($subcategory['category_id'] == $product['category_id']): ?>
                                                                    <option value="<?php echo $subcategory['id']; ?>" <?php echo ($product['subcategory_id'] == $subcategory['id']) ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($subcategory['name']); ?>
                                                                    </option>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="brand_id" class="form-label">Brand</label>
                                                <select class="form-select" id="brand_id" name="brand_id">
                                                    <option value="">Select Brand</option>
                                                    <?php foreach ($brands as $brand): ?>
                                                        <option value="<?php echo $brand['id']; ?>" <?php echo ($product['brand'] == $brand['id']) ? 'selected' : ''; ?>>
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
                                                            <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" min="0" value="<?php echo $product['selling_price']; ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="cost_price" class="form-label">Cost Price</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">৳</span>
                                                            <input type="number" class="form-control" id="cost_price" name="cost_price" step="0.01" min="0" value="<?php echo $product['cost_price'] ?? ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="markup_percentage" class="form-label">Markup %</label>
                                                        <div class="input-group">
                                                            <input type="number" class="form-control" id="markup_percentage" name="markup_percentage" step="0.01" min="0" value="<?php echo $product['markup_percentage'] ?? '0'; ?>">
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
                                                            <input type="number" class="form-control" id="discount_price" name="discount_price" step="0.01" min="0" value="<?php echo $product['discount_price'] ?? ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="discount_start_date" class="form-label">Discount Start</label>
                                                        <input type="date" class="form-control" id="discount_start_date" name="discount_start_date" value="<?php echo $product['discount_start_date'] ?? ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <label for="discount_end_date" class="form-label">Discount End</label>
                                                        <input type="date" class="form-control" id="discount_end_date" name="discount_end_date" value="<?php echo $product['discount_end_date'] ?? ''; ?>">
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
                                                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo $product['stock_quantity']; ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="min_stock_level" class="form-label">Min Stock Level</label>
                                                        <input type="number" class="form-control" id="min_stock_level" name="min_stock_level" min="0" value="<?php echo $product['min_stock_level'] ?? '5'; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="barcode" class="form-label">Barcode</label>
                                                        <input type="text" class="form-control" id="barcode" name="barcode" value="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="weight" class="form-label">Weight (kg)</label>
                                                        <input type="number" class="form-control" id="weight" name="weight" step="0.01" min="0" value="<?php echo $product['weight'] ?? ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="dimensions" class="form-label">Dimensions</label>
                                                        <input type="text" class="form-control" id="dimensions" name="dimensions" placeholder="L x W x H" value="<?php echo htmlspecialchars($product['dimensions'] ?? ''); ?>">
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
                                                <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?php echo htmlspecialchars($product['meta_title'] ?? ''); ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="meta_description" class="form-label">Meta Description</label>
                                                <textarea class="form-control" id="meta_description" name="meta_description" rows="2"><?php echo htmlspecialchars($product['meta_description'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                                <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" placeholder="keyword1, keyword2, keyword3" value="<?php echo htmlspecialchars($product['meta_keywords'] ?? ''); ?>">
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
                                            <?php if (!empty($product['image'])): ?>
                                                <div class="mb-2">
                                                    <img src="../assets/products/<?php echo htmlspecialchars($product['image']); ?>" alt="Current Main Image" class="image-preview">
                                                    <div class="text-muted small mt-1">Current main image</div>
                                                </div>
                                            <?php endif; ?>
                                            <div class="image-upload-area" id="mainImageUpload">
                                                <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-primary"></i>
                                                <p class="mb-1">Click to upload new main image</p>
                                                <p class="text-muted small">JPG, PNG, GIF (Max 2MB)</p>
                                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="document.getElementById('main_image').click()">Browse Files</button>
                                                <input type="file" class="d-none" id="main_image" name="main_image" accept="image/*">
                                            </div>
                                            <div id="mainImagePreview" class="mt-2 d-none">
                                                <img src="" alt="Main Image Preview" class="image-preview">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="gallery_images" class="form-label">Gallery Images</label>
                                            <div id="existingGalleryImages" class="mb-2">
                                                <?php 
                                                if (!empty($product['gallery_images'])):
                                                    $galleryImages = json_decode($product['gallery_images'], true);
                                                    if (is_array($galleryImages)):
                                                        foreach ($galleryImages as $index => $image):
                                                ?>
                                                    <div class="existing-gallery-image">
                                                        <span class="remove-gallery-image" data-index="<?php echo $index; ?>">×</span>
                                                        <img src="../assets/products/<?php echo htmlspecialchars($image); ?>" alt="Gallery Image" class="gallery-image-preview">
                                                        <input type="hidden" name="existing_gallery_images[]" value="<?php echo htmlspecialchars($image); ?>">
                                                    </div>
                                                <?php 
                                                        endforeach;
                                                    endif;
                                                endif; 
                                                ?>
                                            </div>
                                            <div class="image-upload-area" id="galleryImagesUpload">
                                                <i class="fas fa-images fa-2x mb-2 text-info"></i>
                                                <p class="mb-1">Click to upload gallery images</p>
                                                <p class="text-muted small">Multiple images allowed</p>
                                                <button type="button" class="btn btn-outline-info btn-sm mt-2" onclick="document.getElementById('gallery_images').click()">Browse Files</button>
                                                <input type="file" class="d-none" id="gallery_images" name="gallery_images[]" accept="image/*" multiple>
                                            </div>
                                            <div id="galleryImagesPreview" class="mt-2 d-none">
                                                <div class="d-flex flex-wrap gap-2" id="galleryPreviewContainer"></div>
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
                                                <option value="draft" <?php echo ($product['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                                <option value="active" <?php echo ($product['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo ($product['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                <option value="out_of_stock" <?php echo ($product['status'] == 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Display Options</label>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?php echo ($product['is_featured'] == 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_featured">
                                                    Featured Product
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="is_hot_item" name="is_hot_item" value="1" <?php echo ($product['is_hot_item'] == 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_hot_item">
                                                    Hot Item
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($product['is_active'] == 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_active">
                                                    Active
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="sort_order" class="form-label">Sort Order</label>
                                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo $product['sort_order'] ?? '0'; ?>" min="0">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="tags" class="form-label">Tags</label>
                                            <input type="text" class="form-control" id="tags" name="tags" placeholder="tag1, tag2, tag3" value="<?php echo htmlspecialchars($product['tags'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="card form-card">
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                                <i class="fas fa-save me-2"></i>Update Product
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
        console.log('DOM ready - Product Edit Form, initializing upload functionality...');
        
        // Test if elements exist
        console.log('Main upload area exists:', $('#mainImageUpload').length > 0);
        console.log('Gallery upload area exists:', $('#galleryImagesUpload').length > 0);
        console.log('Main image input exists:', $('#main_image').length > 0);
        console.log('Gallery images input exists:', $('#gallery_images').length > 0);
        
        // Global variables for file handling
        let mainImageFile = null;
        let galleryFiles = [];
        
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
            const currentSubcategoryId = <?php echo $product['subcategory_id'] ?? 'null'; ?>;
            $('#subcategory_id').empty().append('<option value="">Select Subcategory</option>');
            
            if (categoryId) {
                // Filter subcategories by category
                <?php foreach ($subcategories as $subcategory): ?>
                    if (<?php echo $subcategory['category_id']; ?> == categoryId) {
                        const selected = (<?php echo $subcategory['id']; ?> == currentSubcategoryId) ? 'selected' : '';
                        $('#subcategory_id').append(
                            '<option value="<?php echo $subcategory['id']; ?>" ' + selected + '><?php echo htmlspecialchars($subcategory['name']); ?></option>'
                        );
                    }
                <?php endforeach; ?>
            }
        });
        
        // Image upload handlers - Multiple approaches for reliability
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
            console.log('Direct main image icon/text clicked');
            e.stopPropagation();
            $('#main_image').click();
        });
        
        // Test click handler
        $('#mainImageUpload').on('mousedown', function(e) {
            console.log('Mouse down on main upload area');
        });
        
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
                // Store the file for form submission
                mainImageFile = file;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#mainImagePreview img').attr('src', e.target.result);
                    $('#mainImagePreview').removeClass('d-none');
                    console.log('Preview generated for:', file.name);
                };
                reader.readAsDataURL(file);
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
        
        $('#gallery_images').on('change', function(e) {
            const files = e.target.files;
            $('#galleryPreviewContainer').empty();
            
            // Validate and store files for form submission
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
            
            galleryFiles = validFiles;
            
            if (validFiles.length > 0) {
                $('#galleryImagesPreview').removeClass('d-none');
                
                for (let i = 0; i < validFiles.length; i++) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#galleryPreviewContainer').append(
                            '<img src="' + e.target.result + '" alt="Gallery Image" class="gallery-image-preview">'
                        );
                    };
                    reader.readAsDataURL(validFiles[i]);
                }
            } else {
                $('#galleryImagesPreview').addClass('d-none');
            }
            
            this.value = ''; // Reset input
        });
        
        // Remove existing gallery image
        $('.remove-gallery-image').on('click', function() {
            const index = $(this).data('index');
            $(this).closest('.existing-gallery-image').remove();
            // Add hidden input to mark for deletion
            $('#productForm').append('<input type="hidden" name="remove_gallery_images[]" value="' + index + '">');
        });
        
        // Form submission
        $('#productForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Form submission started');
            
            // Disable submit button and show loading
            const submitBtn = $('#submitBtn');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Updating Product...').prop('disabled', true);
            
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
            if (galleryFiles.length > 0) {
                console.log('Adding', galleryFiles.length, 'gallery images to FormData');
                formData.delete('gallery_images[]'); // Remove any existing
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
            
            $.ajax({
                url: 'product-ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    console.log('AJAX request starting to: product-ajax.php');
                },
                success: function(response) {
                    console.log('AJAX success response:', response);
                    
                    if (response.success) {
                        console.log('Product updated successfully:', response.message);
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
                        console.log('Product update failed:', response.message);
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
                    
                    let errorMessage = 'Failed to update product. Please try again.';
                    
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