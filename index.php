<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/vendor/autoload.php';
$db = new MysqliDb();
$page = "Home";

// Check if this is a filtered view (category or subcategory)
$isFilteredView = isset($_GET['category']) || isset($_GET['subcategory']);
$filterTitle = '';
$filterSubtitle = '';
$hasError = false;
$errorMessage = '';

if ($isFilteredView) {
    if (isset($_GET['subcategory'])) {
        // Get subcategory info
        $subcategory_id = (int)$_GET['subcategory'];
        $db->where('id', $subcategory_id);
        $subcategory = $db->getOne('subcategories', ['id', 'name', 'category_id']);
        
        if ($subcategory) {
            $filterTitle = htmlspecialchars($subcategory['name']) . ' Products';
            
            // Get category name for breadcrumb
            $db->where('id', $subcategory['category_id']);
            $category = $db->getOne('categories', ['name']);
            if ($category) {
                $filterSubtitle = 'in ' . htmlspecialchars($category['name']);
            }
        } else {
            // Subcategory not found
            $hasError = true;
            $errorMessage = 'Subcategory not found';
            $filterTitle = 'Subcategory Not Found';
        }
    } elseif (isset($_GET['category'])) {
        // Get category info
        $category_id = (int)$_GET['category'];
        $db->where('id', $category_id);
        $category = $db->getOne('categories', ['name']);
        
        if ($category) {
            $filterTitle = htmlspecialchars($category['name']) . ' Products';
            $filterSubtitle = 'Browse all products in this category';
        } else {
            // Category not found
            $hasError = true;
            $errorMessage = 'Category not found';
            $filterTitle = 'Category Not Found';
        }
    }
    
    $page = $filterTitle ?: "Products";
} else {
    // Fetch hot items for the carousel only for homepage
    $db->where('is_hot_item', 1);
    $hotItems = $db->get('products');
}

// Open Graph data
$og_title = $isFilteredView ? $filterTitle . " - " . settings()['companyname'] : settings()['companyname'] . " - Your One-Stop Online Shopping Destination";
$og_description = $isFilteredView ? "Shop " . strtolower($filterTitle) . " at " . settings()['companyname'] : "Discover amazing products at " . settings()['companyname'] . ". Shop electronics, garments, automobiles and more with great deals, quality products, and fast delivery.";
$og_image = settings()['homepage'] . ltrim(settings()['logo'], '/');
$og_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$og_type = "website";

?>
<?php require __DIR__ . '/components/header.php'; ?>

<?php if (!$isFilteredView): ?>
    <!-- Banner Slider for Homepage -->
    <div class="container-fluid px-0">
        <?php include __DIR__ . '/components/banner-slider.php'; ?>
    </div>
<?php endif; ?>

<?php if (!$isFilteredView): ?>
<style>
/* Optimized homepage layout */
.homepage-optimized {
    padding-top: 1rem;
}

.homepage-optimized .welcome-section {
    text-align: center;
    margin-bottom: 1.5rem;
}

.homepage-optimized .welcome-title {
    font-size: 3rem;
    font-weight: bold;
    color:rgba(46, 87, 148, 0.9);
    margin-bottom: 0.5rem;
    margin-top:50px;
}

.homepage-optimized .carousel-subtitle {
    font-size: 1.1rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.homepage-optimized .carousel-container {
    margin-bottom: 2rem;
}

.homepage-optimized .products-section {
    padding: 2rem 0;
}

.homepage-optimized .products-section h1 {
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
}
</style>
<!-- Homepage content -->
<div class="homepage-optimized">
    <div class="container-fluid">
        <div class="welcome-section">
            <h3> Our Hot Products</h3> 
        </div>

        <div class="carousel-container">
            <div class="owl-carousel owl-theme">
                <?php foreach ($hotItems as $item): ?>
                    <div class="item">
                        <a href="product-details.php?id=<?= urlencode($item['id']) ?>" class="text-decoration-none text-dark">
                            <div class="product-image-container" style="height: 200px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                <?php 
                                $imagePath = 'assets/products/' . $item['image'];
                                if (!empty($item['image']) && file_exists($imagePath)): 
                                ?>
                                    <img src="<?= $imagePath ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>"
                                         style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                <?php else: ?>
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                <?php endif; ?>
                            </div>
                            <span><?= htmlspecialchars($item['name']) ?></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- Products Section -->
<div class="products-section">
    <div class="container-fluid px-4">
        <?php if ($isFilteredView): ?>
            <?php if ($hasError): ?>
                <!-- Error View Header -->
                <div class="text-center mb-4">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Error</li>
                        </ol>
                    </nav>
                    <div class="alert alert-warning" role="alert">
                        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i><?= $filterTitle ?></h4>
                        <p><?= $errorMessage ?>. The item you're looking for might have been removed or is temporarily unavailable.</p>
                        <hr>
                        <a href="index.php" class="btn btn-primary"><i class="fas fa-home me-2"></i>Back to Home</a>
                    </div>
                </div>
            <?php else: ?>
            <!-- Filtered View Header -->
            <div class="text-center mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <?php if (isset($_GET['subcategory']) && isset($subcategory) && $subcategory && isset($category) && $category): ?>
                            <li class="breadcrumb-item"><a href="index.php?category=<?= $subcategory['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($subcategory['name']) ?></li>
                        <?php elseif (isset($_GET['category']) && isset($category) && $category): ?>
                            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($category['name']) ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>
                <h1 class="text-primary"><?= $filterTitle ?></h1>
                <?php if ($filterSubtitle): ?>
                    <p class="text-muted"><?= $filterSubtitle ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Homepage View Header -->
            <div class="text-center">
                <h1 class="text-primary">Our Products</h1>
            </div>
        <?php endif; ?>

        <div id="filter-info" class="text-center mb-3"></div>

        <?php if (!$hasError): ?>
        <div class="row g-2" id="productContainer">
            <!-- Products will be loaded here via AJAX -->
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center" id="pagination"></ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/components/footer.php'; ?>
<?php if (!$hasError): ?>
<script>
    const rootUrl = '<?= settings()['root'] ?>';
    $(document).ready(function() {
        let currentPage = 1;
        let currentCategory = <?= isset($_GET['category']) && !$hasError ? (int)$_GET['category'] : 'null' ?>;
        let currentSubcategory = <?= isset($_GET['subcategory']) && !$hasError ? (int)$_GET['subcategory'] : 'null' ?>;
        let currentSearch = null;
        
        // Make variables global for header search integration
        window.currentSearch = currentSearch;
        window.currentPage = currentPage;
        window.loadProducts = loadProducts;

        function loadProducts() {
            // Update global variables
            window.currentSearch = currentSearch;
            window.currentPage = currentPage;
            
            $.ajax({
                url: 'apis/get-products.php',
                type: 'GET',
                data: {
                    page: currentPage,
                    category: currentCategory,
                    subcategory: currentSubcategory,
                    search: currentSearch
                },
                dataType: 'json',
                success: function(response) {
                    $('#productContainer').empty();
                    if (response.products.length > 0) {
                        response.products.forEach(function(product) {
                            var productHtml = `
                                <div class="col-6 col-md-4 col-lg-2 mb-4">
                                    <div class="card product-card h-100 border">
                                        <a href="product-details.php?id=${product.id}" class="text-decoration-none text-dark">
                                            <div class="card-img-wrapper p-3">
                                                <img src="${rootUrl}assets/products/${product.image}" 
                                                     class="card-img-top product-image" 
                                                     alt="${product.name}" 
                                                     style="height: 120px; object-fit: contain; width: 100%;"
                                                     onerror="this.onerror=null;this.src='<?= settings()['logo'] ?>';">
                                            </div>
                                            <div class="card-body text-center p-2">
                                                <h6 class="product-title mb-2" style="font-size: 0.85rem; font-weight: 600; color: #333; min-height: 2.4rem; line-height: 1.2;">${product.name}</h6>
                                                <div class="product-description mb-2">
                                                    <small class="text-muted" style="font-size: 0.75rem; line-height: 1.3;">High quality product with excellent features and specifications</small>
                                                </div>
                                                <div class="price-section mb-3">
                                                    <div class="current-price">
                                                        <span class="price fw-bold text-success" style="font-size: 1rem;">৳${product.selling_price}</span>
                                                        <span class="per-unit text-muted" style="font-size: 0.75rem;">Per Unit</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                        <div class="card-footer bg-white border-0 p-2">
                                            <div class="d-flex gap-1 mb-2">
                                                <a href="product-details.php?id=${product.id}" class="btn btn-outline-primary btn-sm flex-fill" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">
                                                    <i class="fas fa-eye me-1"></i>Details
                                                </a>
                                            </div>
                                            <div class="d-flex gap-1">
                                                <button data-product-id="${product.id}" 
                                                        data-product-name="${product.name}" 
                                                        data-product-price="${product.selling_price}" 
                                                        data-quantity="1" 
                                                        data-product-image="${product.image}" 
                                                        class="btn btn-primary btn-sm flex-fill btn-add-cart"
                                                        style="font-size: 0.7rem; padding: 0.25rem 0.4rem;">
                                                    <i class="fas fa-shopping-cart me-1"></i>Cart
                                                </button>
                                                <button data-product-id="${product.id}" 
                                                        data-product-name="${product.name}" 
                                                        data-product-price="${product.selling_price}" 
                                                        data-product-image="${product.image}" 
                                                        class="btn btn-outline-danger btn-sm flex-fill btn-add-wishlist"
                                                        style="font-size: 0.7rem; padding: 0.25rem 0.4rem;">
                                                    <i class="far fa-heart me-1"></i>Wish
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                            $('#productContainer').append(productHtml);
                        });
                    } else {
                        $('#productContainer').html('<p class="text-center">No products found.</p>');
                    }

                    // Render pagination
                    renderPagination(response.total_pages, response.current_page);

                    // Show filter info only if not already displayed in header
                    <?php if (!$isFilteredView): ?>
                    if (response.subcategory_name) {
                        $('#filter-info').html(`
                            <span class="me-2">Filtered by Subcategory: <strong>${response.subcategory_name}</strong></span>
                            <button id="clearFilter" class="btn btn-sm btn-outline-danger">Clear Filter</button>
                        `);
                    } else if (currentCategory && response.category_name) {
                        $('#filter-info').html(`
                            <span class="me-2">Filtered by Category: <strong>${response.category_name}</strong></span>
                            <button id="clearFilter" class="btn btn-sm btn-outline-danger">Clear Filter</button>
                        `);
                    } else {
                        $('#filter-info').empty();
                    }
                    <?php else: ?>
                    // For filtered views, show product count and clear filter option
                    if (response.products.length > 0) {
                        $('#filter-info').html(`
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">${response.products.length} products found</span>
                                <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Home</a>
                            </div>
                        `);
                    }
                    <?php endif; ?>

                    // If a filter is active, scroll to products section
                    if (currentCategory || currentSubcategory) {
                        const target = document.querySelector('.products-section');
                        if (target) {
                            const y = target.getBoundingClientRect().top + window.pageYOffset - 70; // offset for navbar
                            window.scrollTo({ top: y, behavior: 'smooth' });
                        }
                    }
                }
            });
        }

        function renderPagination(totalPages, currentPage) {
            $('#pagination').empty();
            for (let i = 1; i <= totalPages; i++) {
                const liClass = (i === currentPage) ? 'page-item active' : 'page-item';
                const pageLink = `<li class="${liClass}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                $('#pagination').append(pageLink);
            }
        }

        // Initial load
        <?php if (!$hasError): ?>
        loadProducts();
        <?php endif; ?>
        
        // For filtered views, scroll to products section immediately
        <?php if ($isFilteredView): ?>
        $(window).on('load', function() {
            setTimeout(function() {
                const target = document.querySelector('.products-section');
                if (target) {
                    const y = target.getBoundingClientRect().top + window.pageYOffset - 70;
                    window.scrollTo({ top: y, behavior: 'smooth' });
                }
            }, 500);
        });
        <?php endif; ?>

        // Event handlers - removed since search box is now in header

        // Event handlers for category and subcategory filtering
        $(document).on('click', '.dropdown-item[href*="category="]', function(e) {
            // Category filtering - allow normal navigation
            console.log('Category clicked:', $(this).attr('href'));
        });

        $(document).on('click', '.dropdown-item[href*="subcategory="]', function(e) {
            // Subcategory filtering - allow normal navigation
            console.log('Subcategory clicked:', $(this).attr('href'));
        });

        $(document).on('click', '#clearFilter', function() {
            // Reset filters and URL
            window.location.href = 'index.php';
        });

        $(document).on('click', '.page-link', function(e) {
            e.preventDefault();
            currentPage = $(this).data('page');
            loadProducts();
        });

        // Initialize Owl Carousel
        const owlCarousel = $('.owl-carousel');
        if (owlCarousel.length > 0 && typeof owlCarousel.owlCarousel === 'function') {
            owlCarousel.owlCarousel({
                loop:true,
                margin:10,
                nav:true,
                autoplay:true,
                autoplayTimeout:3000,
                autoplayHoverPause:true,
                responsive:{
                    0:{
                        items:1
                    },
                    600:{
                        items:3
                    },
                    1000:{
                        items:5
                    }
                }
            });
        }
    });
</script>
<?php endif; ?>
<?php $db->disconnect(); ?>
</body>
</html>