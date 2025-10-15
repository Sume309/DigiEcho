<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Set timezone to Bangladesh Standard Time
date_default_timezone_set('Asia/Dhaka');

// Include SettingsHelper for dynamic settings
require_once __DIR__ . '/../src/SettingsHelper.php';

// Get dynamic settings
$siteName = SettingsHelper::get('site_name', 'DigiEcho');
$siteLogo = SettingsHelper::get('logo', 'assets/images/logo.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$siteName?> - <?=$page?></title>

    <!-- Open Graph Protocol Meta Tags -->
    <meta property="og:title" content="<?=isset($og_title) ? $og_title : settings()['companyname'] . ' - ' . $page?>">
    <meta property="og:description" content="<?=isset($og_description) ? $og_description : 'Shop the best products at ' . settings()['companyname'] . '. Quality products, great prices, fast delivery.'?>">
    <meta property="og:image" content="<?=isset($og_image) ? $og_image : 'https://coders64.xyz/projects/digiecho/' . ltrim(settings()['logo'], '/')?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?=isset($og_title) ? $og_title : settings()['companyname']?>">
    <meta property="og:url" content="<?=isset($og_url) ? $og_url : 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']?>">
    <meta property="og:type" content="<?=isset($og_type) ? $og_type : 'website'?>">
    <meta property="og:site_name" content="<?=settings()['companyname']?>">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?=isset($og_title) ? $og_title : settings()['companyname'] . ' - ' . $page?>">
    <meta name="twitter:description" content="<?=isset($og_description) ? $og_description : 'Shop the best products at ' . settings()['companyname'] . '. Quality products, great prices, fast delivery.'?>">
    <meta name="twitter:image" content="<?=isset($og_image) ? $og_image : 'https://coders64.xyz/projects/haatbazar/' . ltrim(settings()['logo'], '/')?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="<?=settings()['homepage']?>assets/css/styles.css">
<link rel="stylesheet" href="<?=settings()['homepage']?>assets/css/footer.css">
<link rel="stylesheet" href="<?=settings()['homepage']?>assets/assets/owl.carousel.min.css">
<script src="<?=settings()['homepage']?>assets/js/jquery-3.7.1.min.js"></script>
<script src="<?=settings()['homepage']?>assets/js/cart.js"></script>
<script src="<?=settings()['homepage']?>assets/js/wishlist.js"></script>
<script>console.log("Cart.js loaded. Cart is defined: ", typeof Cart !== 'undefined');</script>
<script>console.log("Wishlist.js loaded. Wishlist is defined: ", typeof Wishlist !== 'undefined');</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Fixed navbar styles */
    .navbar-fixed {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1030;
        box-shadow: 0 2px 4px rgba(0,0,0,.1);
    }
    
    /* Body padding to account for fixed navbar */
    body {
        padding-top: 70px; /* Adjust this value based on your navbar height */
    }
    
    /* Adjust for mobile */
    @media (max-width: 991.98px) {
        body {
            padding-top: 60px; /* Slightly smaller padding on mobile */
        }
    }

    /* Search Results Styles */
    .search-results-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        z-index: 1050;
        max-height: 400px;
        overflow-y: auto;
        margin-top: 2px;
    }

    .search-section {
        border-bottom: 1px solid #f8f9fa;
        padding: 0;
    }

    .search-section:last-child {
        border-bottom: none;
    }

    .search-section-header {
        background: #f8f9fa;
        padding: 8px 12px;
        font-size: 0.875rem;
        font-weight: 600;
        color: #495057;
        border-bottom: 1px solid #e9ecef;
    }

    .search-result-item {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f8f9fa;
        transition: background-color 0.15s ease-in-out;
    }

    .search-result-item:hover {
        background-color: #f8f9fa;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .search-result-image {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
    }

    .search-result-title {
        font-size: 0.875rem;
        font-weight: 500;
        color: #212529;
        margin-bottom: 2px;
    }

    .search-result-meta {
        font-size: 0.75rem;
        color: #6c757d;
    }

    .search-no-results {
        color: #6c757d;
        font-style: italic;
    }

    .search-footer {
        padding: 8px 12px;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        text-align: center;
    }

    .search-loading {
        color: #6c757d;
    }
</style>

</head>
<body>

<?php include __DIR__ . '/notice-bar.php'; ?>

<nav class="navbar navbar-expand-lg navbar-light bg-light navbar-fixed">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><img src="<?= $siteLogo ?>" alt="<?= $siteName ?> Logo" width="90"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-list me-2"></i>Categories
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                        <?php
                        // Database connection for categories dropdown
                        $conn = new mysqli(
                            settings()['hostname'],
                            settings()['user'],
                            settings()['password'],
                            settings()['database']);
                        if ($conn->connect_error) {
                            die("Connection failed: " . $conn->connect_error);
                        }

                        // Fetch active categories with subcategories
                        $category_query = "SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order, name";
                        $category_result = $conn->query($category_query);

                        while ($category = $category_result->fetch_assoc()) {
                            $category_id = $category['id'];
                            $category_name = htmlspecialchars($category['name']);
                            $category_slug = htmlspecialchars($category['slug']);

                            // Fetch subcategories for this category
                            $subcategory_query = "SELECT id, name, slug FROM subcategories WHERE category_id = ? AND is_active = 1 ORDER BY sort_order, name";
                            $stmt = $conn->prepare($subcategory_query);
                            $stmt->bind_param("i", $category_id);
                            $stmt->execute();
                            $subcategory_result = $stmt->get_result();

                            if ($subcategory_result->num_rows > 0) {
                                // Category with subcategories - create dropdown submenu
                                echo '<li class="dropdown-submenu">';
                                echo '<a class="dropdown-item dropdown-toggle" href="index.php?category=' . $category_id . '">' . $category_name . '</a>';
                                echo '<ul class="dropdown-menu">';
                                
                                while ($subcategory = $subcategory_result->fetch_assoc()) {
                                    $subcategory_id = $subcategory['id'];
                                    $subcategory_name = htmlspecialchars($subcategory['name']);
                                    echo '<li><a class="dropdown-item" href="index.php?subcategory=' . $subcategory_id . '">' . $subcategory_name . '</a></li>';
                                }
                                
                                echo '</ul>';
                                echo '</li>';
                            } else {
                                // Category without subcategories - direct link
                                echo '<li><a class="dropdown-item" href="index.php?category=' . $category_id . '">' . $category_name . '</a></li>';
                            }
                            
                            $stmt->close();
                        }
                        $conn->close();
                        ?>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="hot-deals.php">Hot Deals</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="brands.php">Brands</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>
               
            </ul>
            <div class="d-flex position-relative me-3">
                <div class="search-container position-relative">
                    <div class="input-group" style="width: 400px;">
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search..." autocomplete="off">
                        <button class="btn btn-outline-primary btn-sm" type="button" id="searchButton">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div id="searchResults" class="search-results-dropdown" style="display: none;"></div>
                </div>
            </div>
            <ul class="navbar-nav">
              <!-- Professional Shopping Cart & Wishlist -->
<li class="nav-item">
    <a href="cart.php" class="btn position-relative me-2" style="background: none; border: none; padding: 8px; text-decoration: none;" title="Shopping Cart">
        <i class="fas fa-shopping-cart" style="font-size: 24px; color: #007bff;"></i>
        <span id="cartCountButton" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px; min-width: 18px; height: 18px;">0</span>
    </a>
</li>

<li class="nav-item">
    <a href="wishlist.php" class="btn position-relative me-3" style="background: none; border: none; padding: 8px; text-decoration: none;" title="Wishlist">
        <i class="fas fa-heart" style="font-size: 22px; color: #dc3545;"></i>
        <span id="wishlistCountButton" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size: 10px; min-width: 18px; height: 18px;">0</span>
    </a>
</li>
                
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 'true'): ?>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item">
                            <a href="admin/index.php" class="nav-link btn btn-primary me-2">Dashboard</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle btn btn-outline-primary" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i><?=htmlspecialchars($_SESSION['username'] ?? $_SESSION['email'] ?? 'Admin')?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="user-orders.php"><i class="fas fa-shopping-bag me-2"></i>My Orders</a></li>
                                <li><a class="dropdown-item" href="user-settings.php"><i class="fas fa-cog me-2"></i>User Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle btn btn-outline-primary" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i><?=htmlspecialchars($_SESSION['username'] ?? $_SESSION['email'] ?? 'User')?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="user-orders.php"><i class="fas fa-shopping-bag me-2"></i>My Orders</a></li>
                                <li><a class="dropdown-item" href="user-settings.php"><i class="fas fa-cog me-2"></i>User Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php endif;?>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="login.php" class="nav-link btn btn-primary me-2">Sign In</a>
                    </li>
                    <li class="nav-item">
                        <a href="registration.php" class="nav-link btn btn-primary">Sign up</a>
                    </li>
                <?php endif;?>
            </ul>
        </div>
    </div>
</nav>

<!-- Off canvas for cart -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCart" aria-labelledby="offcanvasCartLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasCartLabel">Your Cart</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body" id="cartContent">
    <!-- You can load cart content dynamically here using PHP or JS -->
     <table class="table table-hover">
        <thead>
            <tr>
                <th scope="col">Item</th>
                <th scope="col">Quantity</th>
                <th scope="col">Price</th>
                <th scope="col">Total</th>
                <th scope="col">Action</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-end fw-bold">Grand Total</td>
                <td id="grandTotalCanvas" class="fw-bold">0.00</td>
                <td></td>
            </tr>
        </tfoot>
     </table>

    <div class="text-center mt-3">
        <a href="cart.php" class="btn btn-outline-primary">
            <i class="fas fa-shopping-cart me-1"></i>View Full Cart
        </a>
    </div>
    <?php include 'cart-preview.php'; // create this file for cart content preview ?>
  </div>
</div>

<!-- Off canvas for wishlist -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasWishlist" aria-labelledby="offcanvasWishlistLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasWishlistLabel"><i class="fas fa-heart me-2 text-danger"></i>Your Wishlist</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body" id="wishlistContent">
     <table class="table table-hover">
        <thead>
            <tr>
                <th scope="col">Item</th>
                <th scope="col">Price</th>
                <th scope="col">Action</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
     </table>

    <div class="text-center mt-3">
        <a href="wishlist.php" class="btn btn-outline-primary">
            <i class="fas fa-heart me-1"></i>View Full Wishlist
        </a>
    </div>
  </div>
</div>

<script>
// Categories dropdown submenu functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle dropdown submenu on hover for desktop
    const dropdownSubmenus = document.querySelectorAll('.dropdown-submenu');
    
    dropdownSubmenus.forEach(function(submenu) {
        submenu.addEventListener('mouseenter', function() {
            if (window.innerWidth > 991) {
                const submenuDropdown = this.querySelector('.dropdown-menu');
                if (submenuDropdown) {
                    submenuDropdown.style.display = 'block';
                }
            }
        });
        
        submenu.addEventListener('mouseleave', function() {
            if (window.innerWidth > 991) {
                const submenuDropdown = this.querySelector('.dropdown-menu');
                if (submenuDropdown) {
                    submenuDropdown.style.display = 'none';
                }
            }
        });
        
        // Handle click for mobile
        const submenuToggle = submenu.querySelector('.dropdown-toggle');
        if (submenuToggle) {
            submenuToggle.addEventListener('click', function(e) {
                if (window.innerWidth <= 991) {
                    e.preventDefault();
                    const submenuDropdown = this.nextElementSibling;
                    if (submenuDropdown) {
                        const isVisible = submenuDropdown.style.display === 'block';
                        submenuDropdown.style.display = isVisible ? 'none' : 'block';
                    }
                }
            });
        }
    });
});
</script>

<!-- Main Content -->
<div class="main-content">
        <?php require __DIR__ . '/dismissalert.php';?>
        <?php require __DIR__ . '/sessiondata.php';?>

<script>
function openUserSettings() {
    // For now, show a simple alert. You can later create a user settings page or modal
    Swal.fire({
        title: 'User Settings',
        text: 'User settings functionality will be implemented soon.',
        icon: 'info',
        confirmButtonText: 'OK'
    });
}
</script>

<script>
        // Unified search functionality
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        const searchButton = document.getElementById('searchButton');

        if (searchInput && searchResults) {
            // Real-time search dropdown
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }
                
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            });

            // Handle Enter key and search button
            function handleSearch() {
                const query = searchInput.value.trim();
                if (query) {
                    // Always redirect to search results page for consistency
                    window.location.href = `search-results.php?q=${encodeURIComponent(query)}`;
                }
            }

            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handleSearch();
                }
            });

            if (searchButton) {
                searchButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    handleSearch();
                });
            }

            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });
        }

        function performSearch(query) {
            const searchResults = document.getElementById('searchResults');
            
            // Show loading
            searchResults.innerHTML = '<div class="search-loading p-3 text-center"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
            searchResults.style.display = 'block';
            
            // Build correct API URL - simplified approach
            const searchUrl = `${window.location.origin}${window.location.pathname.replace(/\/[^\/]*$/, '')}/apis/search.php?q=${encodeURIComponent(query)}`;
            
            fetch(searchUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    displaySearchResults(data);
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<div class="search-error p-3 text-center text-danger">Search failed. Please try again.</div>';
                });
        }

        function displaySearchResults(data) {
            const searchResults = document.getElementById('searchResults');
            if (!searchResults) return;
            
            let html = '';
            
            if (data.products && data.products.length > 0) {
                html += '<div class="search-section">';
                html += '<div class="search-section-header"><i class="fas fa-box me-2"></i>Products</div>';
                data.products.forEach(product => {
                    const imagePath = product.image ? `assets/products/${product.image}` : 'admin/assets/img/no-image.png';
                    const stockStatus = product.stock > 0 ? 'In Stock' : 'Out of Stock';
                    const stockClass = product.stock > 0 ? 'text-success' : 'text-danger';
                    
                    html += `
                        <div class="search-result-item" onclick="window.location.href='${product.url}'">
                            <div class="d-flex align-items-center">
                                <img src="${imagePath}" alt="${product.name}" class="search-result-image me-3">
                                <div class="flex-grow-1">
                                    <div class="search-result-title">${product.name}</div>
                                    <div class="search-result-meta">
                                        <span class="me-2">৳${product.price}</span>
                                        <span class="${stockClass}">${stockStatus}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            }
            
            if (data.categories && data.categories.length > 0) {
                html += '<div class="search-section">';
                html += '<div class="search-section-header"><i class="fas fa-tags me-2"></i>Categories</div>';
                data.categories.forEach(category => {
                    const imagePath = category.image ? `assets/categories/${category.image}` : 'admin/assets/img/no-image.png';
                    
                    html += `
                        <div class="search-result-item" onclick="window.location.href='${category.url}'">
                            <div class="d-flex align-items-center">
                                <img src="${imagePath}" alt="${category.name}" class="search-result-image me-3">
                                <div class="flex-grow-1">
                                    <div class="search-result-title">${category.name}</div>
                                    <div class="search-result-meta">Category</div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            }
            
            if (data.brands && data.brands.length > 0) {
                html += '<div class="search-section">';
                html += '<div class="search-section-header"><i class="fas fa-star me-2"></i>Brands</div>';
                data.brands.forEach(brand => {
                    const imagePath = brand.logo ? `assets/brands/${brand.logo}` : 'admin/assets/img/no-image.png';
                    
                    html += `
                        <div class="search-result-item" onclick="window.location.href='${brand.url}'">
                            <div class="d-flex align-items-center">
                                <img src="${imagePath}" alt="${brand.name}" class="search-result-image me-3">
                                <div class="flex-grow-1">
                                    <div class="search-result-title">${brand.name}</div>
                                    <div class="search-result-meta">Brand</div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            if (data.reviews && data.reviews.length > 0) {
                html += '<div class="search-section">';
                html += '<div class="search-section-header"><i class="fas fa-star me-2"></i>Reviews</div>';
                data.reviews.forEach(review => {
                    const stars = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
                    html += `
                        <div class="search-result-item" onclick="window.location.href='${review.url}'">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="search-result-title">${review.customer_name}</div>
                                    <div class="search-result-meta">
                                        <span class="text-warning">${stars}</span> • ${review.title}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            }

            // Show admin-only results if user is admin
            const isAdmin = <?= isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'true' : 'false' ?>;
            if (isAdmin) {
                if (data.users && data.users.length > 0) {
                    html += '<div class="search-section">';
                    html += '<div class="search-section-header"><i class="fas fa-users me-2"></i>Users</div>';
                    data.users.forEach(user => {
                        html += `
                            <div class="search-result-item" onclick="window.location.href='${user.url}'">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="search-result-title">${user.name}</div>
                                        <div class="search-result-meta">${user.email} • ${user.role}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }

                if (data.orders && data.orders.length > 0) {
                    html += '<div class="search-section">';
                    html += '<div class="search-section-header"><i class="fas fa-shopping-bag me-2"></i>Orders</div>';
                    data.orders.forEach(order => {
                        html += `
                            <div class="search-result-item" onclick="window.location.href='${order.url}'">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="search-result-title">${order.customer_name}</div>
                                        <div class="search-result-meta">Order #${order.order_number} • ৳${order.total_amount} • ${order.status}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }

                if (data.contact_messages && data.contact_messages.length > 0) {
                    html += '<div class="search-section">';
                    html += '<div class="search-section-header"><i class="fas fa-envelope me-2"></i>Contact Messages</div>';
                    data.contact_messages.forEach(message => {
                        const priorityClass = message.priority === 'high' ? 'text-danger' : message.priority === 'medium' ? 'text-warning' : 'text-info';
                        html += `
                            <div class="search-result-item" onclick="window.location.href='${message.url}'">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="search-result-title">${message.name}</div>
                                        <div class="search-result-meta">
                                            <span class="${priorityClass}">${message.priority}</span> • ${message.subject}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }

                if (data.notifications && data.notifications.length > 0) {
                    html += '<div class="search-section">';
                    html += '<div class="search-section-header"><i class="fas fa-bell me-2"></i>Notifications</div>';
                    data.notifications.forEach(notification => {
                        const readClass = notification.is_read ? 'text-muted' : 'fw-bold';
                        html += `
                            <div class="search-result-item ${readClass}" onclick="window.location.href='${notification.url}'">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="search-result-title">${notification.title}</div>
                                        <div class="search-result-meta">${notification.type}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }

                if (data.team_members && data.team_members.length > 0) {
                    html += '<div class="search-section">';
                    html += '<div class="search-section-header"><i class="fas fa-user-tie me-2"></i>Team Members</div>';
                    data.team_members.forEach(member => {
                        html += `
                            <div class="search-result-item" onclick="window.location.href='${member.url}'">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="search-result-title">${member.name}</div>
                                        <div class="search-result-meta">${member.position}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }

                if (data.chat_messages && data.chat_messages.length > 0) {
                    html += '<div class="search-section">';
                    html += '<div class="search-section-header"><i class="fas fa-comments me-2"></i>Chat Messages</div>';
                    data.chat_messages.forEach(message => {
                        html += `
                            <div class="search-result-item" onclick="window.location.href='${message.url}'">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="search-result-title">${message.username}</div>
                                        <div class="search-result-meta">${message.message}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }

                if (data.discounts && data.discounts.length > 0) {
                    html += '<div class="search-section">';
                    html += '<div class="search-section-header"><i class="fas fa-percent me-2"></i>Discounts</div>';
                    data.discounts.forEach(discount => {
                        const activeClass = discount.is_active ? 'text-success' : 'text-muted';
                        html += `
                            <div class="search-result-item" onclick="window.location.href='${discount.url}'">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="search-result-title">${discount.name}</div>
                                        <div class="search-result-meta">
                                            <span class="${activeClass}">${discount.discount_type}: ${discount.discount_value}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }

                if (data.reports && data.reports.length > 0) {
                    html += '<div class="search-section">';
                    html += '<div class="search-section-header"><i class="fas fa-chart-bar me-2"></i>Reports</div>';
                    data.reports.forEach(report => {
                        html += `
                            <div class="search-result-item" onclick="window.location.href='${report.url}'">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="search-result-title">${report.title}</div>
                                        <div class="search-result-meta">${report.type}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }
            }

            if (!html) {
                html = '<div class="search-no-results p-3 text-center">No results found</div>';
            } else {
                html += '<div class="search-footer"><small class="text-muted">Press Enter to see all results</small></div>';
            }
            
            searchResults.innerHTML = html;
            searchResults.style.display = 'block';
        }

</script>
</body>
</html>