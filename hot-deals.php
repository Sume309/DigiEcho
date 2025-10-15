<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/vendor/autoload.php';
$db = new MysqliDb();
$page = "Hot Deals";

// Get filter parameters
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;
$offset = ($currentPage - 1) * $limit;

// Build query with filters
$db->where('is_hot_item', 1);

if ($category > 0) {
    $db->where('category_id', $category);
}

if ($minPrice > 0) {
    $db->where('selling_price', $minPrice, '>=');
}

if ($maxPrice > 0) {
    $db->where('selling_price', $maxPrice, '<=');
}

if (!empty($search)) {
    $db->where('(name LIKE "%' . $db->escape($search) . '%" OR short_description LIKE "%' . $db->escape($search) . '%")');
}

// Apply sorting
switch ($sortBy) {
    case 'price_low':
        $db->orderBy('selling_price', 'ASC');
        break;
    case 'price_high':
        $db->orderBy('selling_price', 'DESC');
        break;
    case 'newest':
        $db->orderBy('created_at', 'DESC');
        break;
    case 'popular':
        $db->orderBy('stock_quantity', 'DESC');
        break;
    default:
        $db->orderBy('name', 'ASC');
        break;
}

// Get total count for pagination (with same filters)
$countDb = new MysqliDb();
$countDb->where('is_hot_item', 1);
if ($category > 0) {
    $countDb->where('category_id', $category);
}
if ($minPrice > 0) {
    $countDb->where('selling_price', $minPrice, '>=');
}
if ($maxPrice > 0) {
    $countDb->where('selling_price', $maxPrice, '<=');
}
if (!empty($search)) {
    $countDb->where('(name LIKE "%' . $countDb->escape($search) . '%" OR short_description LIKE "%' . $countDb->escape($search) . '%")');
}
$totalProducts = $countDb->getValue('products', "count(*)");
$totalPages = ceil($totalProducts / $limit);

// Fetch products with pagination
$products = $db->get('products', [$offset, $limit]);

// Function to get applicable discounts for a product
function getProductDiscounts($db, $productId, $categoryId = null, $brandId = null) {
    $currentDate = date('Y-m-d');
    
    // Get all active discounts that apply to this product
    $discounts = [];
    
    // 1. Check for all products discounts
    $allProductsDiscounts = $db->rawQuery(
        "SELECT * FROM product_discounts 
         WHERE applies_to = 'all_products' 
         AND is_active = 1 
         AND start_date <= ? 
         AND end_date >= ? 
         AND (usage_limit IS NULL OR usage_count < usage_limit)
         ORDER BY discount_value DESC",
        [$currentDate, $currentDate]
    );
    
    if ($allProductsDiscounts) {
        $discounts = array_merge($discounts, $allProductsDiscounts);
    }
    
    // 2. Check for specific product discounts
    $specificDiscounts = $db->rawQuery(
        "SELECT pd.* FROM product_discounts pd
         INNER JOIN product_discount_relations pdr ON pd.id = pdr.discount_id
         WHERE pd.applies_to = 'specific_products' 
         AND pdr.product_id = ?
         AND pd.is_active = 1 
         AND pd.start_date <= ? 
         AND pd.end_date >= ?
         AND (pd.usage_limit IS NULL OR pd.usage_count < pd.usage_limit)
         ORDER BY pd.discount_value DESC",
        [$productId, $currentDate, $currentDate]
    );
    
    if ($specificDiscounts) {
        $discounts = array_merge($discounts, $specificDiscounts);
    }
    
    // 3. Check for category discounts
    if ($categoryId) {
        $categoryDiscounts = $db->rawQuery(
            "SELECT pd.* FROM product_discounts pd
             INNER JOIN product_discount_relations pdr ON pd.id = pdr.discount_id
             WHERE pd.applies_to = 'categories' 
             AND pdr.category_id = ?
             AND pd.is_active = 1 
             AND pd.start_date <= ? 
             AND pd.end_date >= ?
             AND (pd.usage_limit IS NULL OR pd.usage_count < pd.usage_limit)
             ORDER BY pd.discount_value DESC",
            [$categoryId, $currentDate, $currentDate]
        );
        
        if ($categoryDiscounts) {
            $discounts = array_merge($discounts, $categoryDiscounts);
        }
    }
    
    // 4. Check for brand discounts
    if ($brandId) {
        $brandDiscounts = $db->rawQuery(
            "SELECT pd.* FROM product_discounts pd
             INNER JOIN product_discount_relations pdr ON pd.id = pdr.discount_id
             WHERE pd.applies_to = 'brands' 
             AND pdr.brand_id = ?
             AND pd.is_active = 1 
             AND pd.start_date <= ? 
             AND pd.end_date >= ?
             AND (pd.usage_limit IS NULL OR pd.usage_count < pd.usage_limit)
             ORDER BY pd.discount_value DESC",
            [$brandId, $currentDate, $currentDate]
        );
        
        if ($brandDiscounts) {
            $discounts = array_merge($discounts, $brandDiscounts);
        }
    }
    
    // Remove duplicates and return the best discount
    $uniqueDiscounts = [];
    $seenIds = [];
    
    foreach ($discounts as $discount) {
        if (!in_array($discount['id'], $seenIds)) {
            $uniqueDiscounts[] = $discount;
            $seenIds[] = $discount['id'];
        }
    }
    
    return $uniqueDiscounts;
}

// Function to calculate discounted price
function calculateDiscountedPrice($originalPrice, $discount) {
    switch ($discount['discount_type']) {
        case 'percentage':
            return $originalPrice - ($originalPrice * $discount['discount_value'] / 100);
        case 'fixed_amount':
            return max(0, $originalPrice - $discount['discount_value']);
        case 'buy_x_get_y':
            // For display purposes, show original price (actual discount applies at cart level)
            return $originalPrice;
        default:
            return $originalPrice;
    }
}

// Function to format discount display
function formatDiscountDisplay($discount) {
    switch ($discount['discount_type']) {
        case 'percentage':
            return $discount['discount_value'] . '% OFF';
        case 'fixed_amount':
            return '৳' . number_format($discount['discount_value'], 0) . ' OFF';
        case 'buy_x_get_y':
            return 'Buy ' . $discount['min_quantity'] . ' Get ' . $discount['max_quantity'] . ' Free';
        default:
            return 'DISCOUNT';
    }
}

// Get categories for filter dropdown
$categories = $db->get('categories', null, 'id, name');

// Get price range for filter
$priceRange = $db->rawQueryOne('SELECT MIN(selling_price) as min_price, MAX(selling_price) as max_price FROM products WHERE is_hot_item = 1');

// Helper function for pagination URLs
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

?>

<?php require __DIR__ . '/components/header.php'; ?>

<!-- Hot Deals Banner Slider -->
<?php include __DIR__ . '/components/hot-deals-banner-slider.php'; ?>

<style>
    /* Product Card Styles - Matching index.php */
    .product-card {
        transition: all 0.3s ease;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .card-img-wrapper {
        background-color: #f8f9fa;
        border-radius: 8px 8px 0 0;
    }
    
    .product-image {
        transition: transform 0.2s ease;
    }
    
    .product-card:hover .product-image {
        transform: scale(1.02);
    }
    
    .product-title {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .product-description {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Discount Badge Animations */
    .discount-badge {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .hot-deal-badge {
        animation: glow 2s ease-in-out infinite alternate;
    }
    
    @keyframes glow {
        from { box-shadow: 0 0 5px #ffc107; }
        to { box-shadow: 0 0 10px #ffc107, 0 0 15px #ffc107; }
    }
    
    /* Price styling enhancements */
    .original-price {
        position: relative;
    }
    
    .original-price::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background: #dc3545;
        transform: translateY(-50%);
    }
    
    .savings {
        background: linear-gradient(45deg, #ff6b6b, #ee5a52);
        color: white !important;
        padding: 2px 6px;
        border-radius: 8px;
        display: inline-block;
        font-size: 0.65rem !important;
        font-weight: bold;
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        box-shadow: 0 2px 4px rgba(238, 90, 82, 0.3);
    }
    
    /* Enhanced product card hover effects */
    .product-card:hover .discount-badge {
        animation-duration: 0.5s;
    }
    
    .product-card:hover .hot-deal-badge {
        animation-duration: 1s;
    }
    
    .price {
        color: #28a745 !important;
    }
    
    .per-unit {
        color: #6c757d !important;
    }
    
    .filter-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .filter-title {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 1rem;
    }
    
    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .results-info {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .pagination .page-link {
        border-radius: 8px;
        margin: 0 2px;
        border: 1px solid #dee2e6;
        color: #007bff;
    }
    
    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #007bff, #0056b3);
        border-color: #007bff;
    }
    
    @media (max-width: 768px) {
        .product-card {
            margin-bottom: 1rem;
        }
        
        .filter-section {
            padding: 1rem;
        }
        
        .results-header {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<!-- content start -->
<div class="container my-4">
    

    <!-- Filters Section -->
    <div class="filter-section">
        <h5 class="filter-title"><i class="fas fa-filter me-2"></i>Filter Products</h5>
        <form method="GET" id="filterForm">
            <div class="row g-3">
                <!-- Search -->
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Product name...">
                </div>
                
                <!-- Category -->
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Price Range -->
                <div class="col-md-2">
                    <label class="form-label">Min Price</label>
                    <input type="number" class="form-control" name="min_price" value="<?= $minPrice ?>" placeholder="৳0" min="0" step="0.01">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Max Price</label>
                    <input type="number" class="form-control" name="max_price" value="<?= $maxPrice ?>" placeholder="৳<?= number_format($priceRange['max_price'] ?? 1000, 0) ?>" min="0" step="0.01">
                </div>
                
                <!-- Sort -->
                <div class="col-md-2">
                    <label class="form-label">Sort By</label>
                    <select class="form-select" name="sort">
                        <option value="name" <?= $sortBy == 'name' ? 'selected' : '' ?>>Name A-Z</option>
                        <option value="price_low" <?= $sortBy == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= $sortBy == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="popular" <?= $sortBy == 'popular' ? 'selected' : '' ?>>Most Popular</option>
                    </select>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Apply Filters
                    </button>
                    <a href="hot-deals-enhanced.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear All
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Header -->
    <div class="results-header">
        <div class="results-info">
            <strong><?= number_format($totalProducts) ?></strong> hot deals found
            <?php if ($search): ?>
                for "<strong><?= htmlspecialchars($search) ?></strong>"
            <?php endif; ?>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <!-- Per Page -->
            <div class="d-flex align-items-center">
                <label class="form-label me-2 mb-0">Show:</label>
                <select class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                    <option value="12" <?= $limit == 12 ? 'selected' : '' ?>>12</option>
                    <option value="24" <?= $limit == 24 ? 'selected' : '' ?>>24</option>
                    <option value="48" <?= $limit == 48 ? 'selected' : '' ?>>48</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No products found</h4>
            <p class="text-muted">Try adjusting your filters or search terms</p>
            <a href="hot-deals.php" class="btn btn-primary">View All Hot Deals</a>
        </div>
    <?php else: ?>
        <div class="row" id="productContainer">
            <?php foreach ($products as $product): ?>
                <div class="col-6 col-md-4 col-lg-2 mb-4">
                    <div class="card product-card h-100 border">
                        <a href="product-details.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                            <?php
                            // Get applicable discounts for this product (moved to top for proper scope)
                            $productDiscounts = getProductDiscounts($db, $product['id'], $product['category_id'] ?? null, $product['brand_id'] ?? null);
                            $bestDiscount = !empty($productDiscounts) ? $productDiscounts[0] : null;
                            $discountedPrice = $bestDiscount ? calculateDiscountedPrice($product['selling_price'], $bestDiscount) : $product['selling_price'];
                            $hasDiscount = $bestDiscount && $discountedPrice < $product['selling_price'];
                            ?>
                            
                            <div class="card-img-wrapper p-3 position-relative">
                                <?php if ($product['image']): ?>
                                    <img src="assets/products/<?= htmlspecialchars($product['image']) ?>" 
                                         class="card-img-top product-image" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         style="height: 120px; object-fit: contain; width: 100%;"
                                         onerror="this.onerror=null;this.src='<?= settings()['logo'] ?>';">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center" style="height: 120px;">
                                        <i class="fas fa-image fa-2x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Discount Badge -->
                                <?php if ($bestDiscount): ?>
                                    <div class="discount-badge position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-danger text-white fw-bold" style="font-size: 0.65rem; padding: 0.3rem 0.5rem; border-radius: 12px; box-shadow: 0 2px 4px rgba(220,53,69,0.3);">
                                            <?= formatDiscountDisplay($bestDiscount) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Hot Deal Badge -->
                                <div class="hot-deal-badge position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-warning text-dark fw-bold" style="font-size: 0.6rem; padding: 0.25rem 0.4rem; border-radius: 8px;">
                                        <i class="fas fa-fire me-1"></i>HOT
                                    </span>
                                </div>
                            </div>
                            <div class="card-body text-center p-2">
                                <h6 class="product-title mb-2" style="font-size: 0.85rem; font-weight: 600; color: #333; min-height: 2.4rem; line-height: 1.2;">
                                    <?= htmlspecialchars($product['name']) ?>
                                </h6>
                                <div class="product-description mb-2">
                                    <small class="text-muted" style="font-size: 0.75rem; line-height: 1.3;">
                                        <?= htmlspecialchars($product['short_description'] ?: 'High quality product with excellent features and specifications') ?>
                                    </small>
                                </div>
                                
                                <div class="price-section mb-3">
                                    <?php if ($hasDiscount): ?>
                                        <div class="original-price mb-1">
                                            <span class="text-muted text-decoration-line-through" style="font-size: 0.8rem;">৳<?= number_format($product['selling_price'], 2) ?></span>
                                        </div>
                                        <div class="current-price">
                                            <span class="price fw-bold text-success" style="font-size: 1rem;">৳<?= number_format($discountedPrice, 2) ?></span>
                                            <span class="per-unit text-muted" style="font-size: 0.75rem;">Per Unit</span>
                                        </div>
                                        <div class="savings mt-1">
                                            <span class="savings">
                                                Save ৳<?= number_format($product['selling_price'] - $discountedPrice, 2) ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="current-price">
                                            <span class="price fw-bold text-success" style="font-size: 1rem;">৳<?= number_format($product['selling_price'], 2) ?></span>
                                            <span class="per-unit text-muted" style="font-size: 0.75rem;">Per Unit</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        <div class="card-footer bg-white border-0 p-2">
                            <div class="d-flex gap-1 mb-2">
                                <a href="product-details.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary btn-sm flex-fill" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">
                                    <i class="fas fa-eye me-1"></i>Details
                                </a>
                            </div>
                            <div class="d-flex gap-1">
                                <button data-product-id="<?= $product['id'] ?>" 
                                        data-product-name="<?= htmlspecialchars($product['name']) ?>" 
                                        data-product-price="<?= $product['selling_price'] ?>" 
                                        data-quantity="1" 
                                        data-product-image="<?= htmlspecialchars($product['image']) ?>" 
                                        class="btn btn-primary btn-sm flex-fill btn-add-cart"
                                        style="font-size: 0.7rem; padding: 0.25rem 0.4rem;">
                                    <i class="fas fa-shopping-cart me-1"></i>Cart
                                </button>
                                <button data-product-id="<?= $product['id'] ?>" 
                                        data-product-name="<?= htmlspecialchars($product['name']) ?>" 
                                        data-product-price="<?= $product['selling_price'] ?>" 
                                        data-product-image="<?= htmlspecialchars($product['image']) ?>" 
                                        class="btn btn-outline-danger btn-sm flex-fill btn-add-wishlist"
                                        style="font-size: 0.7rem; padding: 0.25rem 0.4rem;"
                                        onclick="toggleWishlist(<?= $product['id'] ?>, this)">
                                    <i class="far fa-heart me-1"></i>Wish
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Enhanced Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <!-- Previous -->
                <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildPaginationUrl($currentPage - 1) ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Page Numbers -->
                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                
                if ($startPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildPaginationUrl(1) ?>">1</a>
                    </li>
                    <?php if ($startPage > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= buildPaginationUrl($i) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildPaginationUrl($totalPages) ?>"><?= $totalPages ?></a>
                    </li>
                <?php endif; ?>
                
                <!-- Next -->
                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= buildPaginationUrl($currentPage + 1) ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
<!-- content end -->

<!-- Wishlist and Cart JavaScript -->
<script>
// Change per page
function changePerPage(perPage) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', perPage);
    url.searchParams.delete('page'); // Reset to page 1
    window.location.href = url.toString();
}

// Wishlist functionality
function toggleWishlist(productId, button) {
    const icon = button.querySelector('i');
    const isInWishlist = icon.classList.contains('fas');
    
    // Optimistic UI update
    if (isInWishlist) {
        icon.classList.remove('fas');
        icon.classList.add('far');
        button.classList.remove('btn-danger');
        button.classList.add('btn-outline-danger');
    } else {
        icon.classList.remove('far');
        icon.classList.add('fas');
        button.classList.remove('btn-outline-danger');
        button.classList.add('btn-danger');
    }
    
    // Send AJAX request
    fetch('apis/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: isInWishlist ? 'remove' : 'add',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showToast(data.message || (isInWishlist ? 'Removed from wishlist' : 'Added to wishlist'), 'success');
        } else {
            // Revert UI changes on error
            if (isInWishlist) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                button.classList.remove('btn-outline-danger');
                button.classList.add('btn-danger');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                button.classList.remove('btn-danger');
                button.classList.add('btn-outline-danger');
            }
            showToast(data.message || 'Please login to use wishlist', 'error');
        }
    })
    .catch(error => {
        console.error('Wishlist error:', error);
        // Revert UI changes on error
        if (isInWishlist) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            button.classList.remove('btn-outline-danger');
            button.classList.add('btn-danger');
        } else {
            icon.classList.remove('fas');
            icon.classList.add('far');
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-danger');
        }
        showToast('Something went wrong. Please try again.', 'error');
    });
}

// Toast notification function
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}
</script>

<?php require __DIR__ . '/components/footer.php'; ?>

<?php
$db->disconnect();
?>
