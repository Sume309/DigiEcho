<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone to Bangladesh Standard Time
date_default_timezone_set('Asia/Dhaka');

require_once __DIR__ . '/vendor/autoload.php';

$page = 'Search Results';

// Get search parameters
$query = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'name';
$page_num = intval($_GET['page'] ?? 1);
$limit = 12;
$offset = ($page_num - 1) * $limit;

try {
    // Database connection
    $db = new MysqliDb(
        settings()['hostname'],
        settings()['user'],
        settings()['password'],
        settings()['database']
    );
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}

include 'components/header.php';

// Initialize variables
$products = [];
$total_products = 0;
$categories = [];
$brands = [];

// Get all active categories for filter
$categories = $db->where('is_active', 1)->get('categories');

// Get all active brands for filter
$brands = $db->where('is_active', 1)->get('brands');

// Build search query
if (!empty($query) || !empty($category) || !empty($brand)) {
    $db->where('p.is_active', 1);
    
    if (!empty($query)) {
        $db->where("(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)", 
                  ["%{$query}%", "%{$query}%", "%{$query}%", "%{$query}%"]);
    }
    
    if (!empty($category)) {
        $db->where('p.category_id', $category);
    }
    
    if (!empty($brand)) {
        $db->where('p.brand', $brand);
    }
    
    if (!empty($min_price)) {
        $db->where('p.selling_price >=', floatval($min_price));
    }
    
    if (!empty($max_price)) {
        $db->where('p.selling_price <=', floatval($max_price));
    }
    
    // Get total count for pagination
    $db->join("categories c", "p.category_id = c.id", "LEFT");
    $db->join("brands b", "p.brand = b.id", "LEFT");
    $total_products = $db->getValue("products p", "count(*)");
    
    // Apply sorting
    switch($sort) {
        case 'price_low':
            $db->orderBy('p.selling_price', 'ASC');
            break;
        case 'price_high':
            $db->orderBy('p.selling_price', 'DESC');
            break;
        case 'newest':
            $db->orderBy('p.created_at', 'DESC');
            break;
        default:
            $db->orderBy('p.name', 'ASC');
    }
    
    // Get paginated results
    $products = $db->arrayBuilder()
                  ->withTotalCount()
                  ->paginate('products p', $page_num, 'p.*, c.name as category_name, b.name as brand_name');
    
    $total_pages = $db->totalPages;
}
?>

<div class="container my-4">
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="search-results.php" id="filterForm">
                        <input type="hidden" name="q" value="<?= htmlspecialchars($query) ?>">
                        
                        <!-- Category Filter -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <select name="category" class="form-select" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Brand Filter -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Brand</label>
                            <select name="brand" class="form-select" onchange="this.form.submit()">
                                <option value="">All Brands</option>
                                <?php foreach ($brands as $br): ?>
                                    <option value="<?= $br['id'] ?>" <?= $brand == $br['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($br['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Price Range</label>
                            <div class="row g-2">
                                <div class="col">
                                    <input type="number" name="min_price" class="form-control" placeholder="Min" 
                                           value="<?= htmlspecialchars($min_price) ?>" step="0.01" min="0">
                                </div>
                                <div class="col">
                                    <input type="number" name="max_price" class="form-control" placeholder="Max" 
                                           value="<?= htmlspecialchars($max_price) ?>" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        <a href="search-results.php" class="btn btn-outline-secondary w-100 mt-2">Reset Filters</a>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-lg-9 col-md-8">
            <!-- Search Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <?php if (!empty($query) || !empty($category) || !empty($brand)): ?>
                        <h4>Search Results</h4>
                        <p class="text-muted mb-0">
                            <?php 
                            $filters = [];
                            if (!empty($query)) $filters[] = '"' . htmlspecialchars($query) . '"';
                            if (!empty($category)) {
                                $cat_name = '';
                                foreach ($categories as $cat) {
                                    if ($cat['id'] == $category) {
                                        $cat_name = $cat['name'];
                                        break;
                                    }
                                }
                                $filters[] = 'Category: ' . htmlspecialchars($cat_name);
                            }
                            if (!empty($brand)) {
                                $brand_name = '';
                                foreach ($brands as $br) {
                                    if ($br['id'] == $brand) {
                                        $brand_name = $br['name'];
                                        break;
                                    }
                                }
                                $filters[] = 'Brand: ' . htmlspecialchars($brand_name);
                            }
                            echo implode(' • ', $filters);
                            ?>
                        </p>
                    <?php else: ?>
                        <h4>All Products</h4>
                    <?php endif; ?>
                </div>
                <div>
                    <select name="sort" class="form-select" onchange="updateSort(this.value)">
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Sort by: Name</option>
                        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Sort by: Price (Low to High)</option>
                        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Sort by: Price (High to Low)</option>
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Sort by: Newest</option>
                    </select>
                </div>
            </div>

            <?php if (!empty($products)): ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
                    <?php foreach ($products as $product): 
                        $imagePath = 'assets/products/' . $product['image'];
                        $imageUrl = !empty($product['image']) && file_exists($imagePath) 
                            ? settings()['root'] . $imagePath 
                            : settings()['logo'];
                    ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <div style="height: 200px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                    <img src="<?= $imageUrl ?>" 
                                         class="card-img-top p-3" 
                                         alt="<?= htmlspecialchars($product['name']) ?>"
                                         style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    <p class="card-text text-primary fw-bold mb-3">৳<?= number_format($product['selling_price'], 2) ?></p>
                                    <?php if (!empty($product['category_name'])): ?>
                                        <p class="text-muted small mb-2"><?= htmlspecialchars($product['category_name']) ?></p>
                                    <?php endif; ?>
                                    <div class="mt-auto">
                                        <a href="product-details.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-eye me-1"></i> View Details
                                        </a>
                                        <button class="btn btn-primary w-100 mt-2 btn-add-cart" 
                                                data-product-id="<?= $product['id'] ?>"
                                                data-product-name="<?= htmlspecialchars($product['name']) ?>"
                                                data-product-price="<?= $product['selling_price'] ?>"
                                                data-product-image="<?= htmlspecialchars($product['image'] ?? '') ?>">
                                            <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>
                                <?php if ($product['stock_quantity'] <= 0): ?>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-danger">Out of Stock</span>
                                    </div>
                                <?php elseif ($product['stock_quantity'] <= 5): ?>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-warning">Low Stock</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if (isset($total_pages) && $total_pages > 1): ?>
                    <nav aria-label="Search results pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page_num > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page_num - 1])) ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start_page = max(1, $page_num - 2);
                            $end_page = min($total_pages, $page_num + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                                $params = array_merge($_GET, ['page' => $i]);
                            ?>
                                <li class="page-item <?= $i == $page_num ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query($params) ?>"><?= $i ?></a>
                                </li>
                            <?php 
                            endfor; 
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page_num < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page_num + 1])) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <h3 class="text-muted">No products found</h3>
                        <p class="text-muted">Try adjusting your search criteria or browse our categories.</p>
                        <a href="index.php" class="btn btn-primary mt-3">Browse All Products</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateSort(sortValue) {
    const url = new URL(window.location);
    url.searchParams.set('sort', sortValue);
    url.searchParams.set('page', '1'); // Reset to first page when changing sort
    window.location.href = url.toString();
}

// Initialize cart functionality
if (typeof Cart !== 'undefined') {
    // Add to cart button click handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-add-cart')) {
            e.preventDefault();
            const button = e.target.closest('.btn-add-cart');
            const productId = button.dataset.productId;
            const productName = button.dataset.productName;
            const productPrice = parseFloat(button.dataset.productPrice);
            const productImage = button.dataset.productImage;
            
            Cart.addItem(productId, 1, productName, productPrice, productImage);
            
            // Show success message
            const toast = new bootstrap.Toast(document.getElementById('addedToCartToast'));
            document.getElementById('toastProductName').textContent = productName;
            toast.show();
        }
    });
}
</script>

<!-- Success Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="addedToCartToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">Success</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <i class="fas fa-check-circle text-success me-2"></i>
            <span id="toastProductName"></span> added to cart!
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>
