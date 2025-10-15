<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_GET['id'])) {
    header("HTTP/1.0 400 Bad Request");
    exit;
}
require __DIR__ . '/vendor/autoload.php';
$db = new MysqliDb();
$page = "Product Details";
/* $db->where("id", intval($_GET['id']));
$product = $db->getOne('products'); */
$db->join("categories c", "p.category_id = c.id", "LEFT");
$db->join("subcategories sc", "p.subcategory_id = sc.id", "LEFT");
$db->join("brands b", "p.brand = b.id", "LEFT");
$db->orderBy("p.id", "DESC");
$db->where("p.id", intval($_GET['id']));
$products = $db->get("products p", null, "p.*, c.name as category_name, sc.name as subcategory_name, b.name as brand_name");

// Check if product exists
if (empty($products)) {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>Product Not Found</h1><p>The requested product could not be found.</p>";
    exit;
}

// Fetch review statistics for this product
$productId = intval($_GET['id']);
$reviewStats = [
    'total_reviews' => 0,
    'average_rating' => 0,
    'rating_distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]
];

// Get total approved reviews and average rating
$db->where('product_id', $productId);
$db->where('is_approved', 1);
$statsResult = $db->getOne('product_reviews', 'COUNT(*) as total, AVG(rating) as avg_rating');

if ($statsResult && $statsResult['total'] > 0) {
    $reviewStats['total_reviews'] = intval($statsResult['total']);
    $reviewStats['average_rating'] = round(floatval($statsResult['avg_rating']), 1);
}

// Get rating distribution
for ($rating = 1; $rating <= 5; $rating++) {
    $count = $db->where('product_id', $productId)
               ->where('is_approved', 1)
               ->where('rating', $rating)
               ->getValue('product_reviews', 'COUNT(*)');
    $reviewStats['rating_distribution'][$rating] = intval($count ?: 0);
}

// Get recent approved reviews (limit 5 for initial display)
$db->where('product_id', $productId);
$db->where('is_approved', 1);
$db->orderBy('created_at', 'DESC');
$recentReviews = $db->get('product_reviews', 5);

// Open Graph data for product page
if (!empty($products)) {
    $product = $products[0];
    $og_title = htmlspecialchars($product['name']) . " - " . settings()['companyname'];

    // Create rich description for social sharing
    $price = "৳" . number_format($product['selling_price'], 2);
    $og_description = !empty($product['short_description']) ?
    htmlspecialchars($product['short_description']) . " | Price: " . $price :
    htmlspecialchars($product['description']) . " | Price: " . $price;

    // Ensure full URL for product image
    $og_image = 'https://coders64.xyz/projects/haatbazar/assets/products/' . htmlspecialchars($product['image']);
    $og_url = 'https://coders64.xyz/projects/haatbazar/product-details.php?id=' . intval($_GET['id']);
    $og_type = "product";

    // Additional product-specific Open Graph tags
    $og_product_price = $product['selling_price'];
    $og_product_currency = "BDT";
    $og_product_availability = $product['stock_quantity'] > 0 ? "in stock" : "out of stock";
    $og_product_brand = htmlspecialchars($product['brand_name']);
    $og_product_category = htmlspecialchars($product['category_name']);
} else {
    // Fallback if product not found
    $og_title = "Product Not Found - " . settings()['companyname'];
    $og_description = "The requested product could not be found.";
    $og_image = 'https://coders64.xyz/projects/haatbazar/' . ltrim(settings()['logo'], '/');
    $og_url = 'https://coders64.xyz/projects/haatbazar/product-details.php?id=' . intval($_GET['id']);
    $og_type = "website";
}
/* var_dump($products);

exit; */
/*
array(26) { ["id"]=> int(2) ["category_id"]=> int(5) ["subcategory_id"]=> int(16) ["name"]=> string(4) "Akij" ["slug"]=> string(2) "ac" ["description"]=> string(7) "dsfgdfg" ["short_description"]=> string(15) " sdfsd fdsaf sd" ["sku"]=> string(9) "idbac2ton" ["barcode"]=> string(16) "43543545fdgdfgfg" ["selling_price"]=> string(8) "45000.00" ["cost_price"]=> string(8) "42000.00" ["markup_percentage"]=> string(4) "0.00" ["pricing_method"]=> string(6) "manual" ["auto_update_price"]=> int(0) ["stock_quantity"]=> int(55) ["min_stock_level"]=> int(5) ["image"]=> string(28) "685b85aaebc84_1750828458.png" ["is_hot_item"]=> int(1) ["is_active"]=> int(1) ["weight"]=> string(5) "55.00" ["dimensions"]=> string(2) "55" ["created_at"]=> string(19) "2025-06-24 05:49:06" ["updated_at"]=> string(19) "2025-06-25 11:14:19" ["brand"]=> int(2) ["sort_order"]=> int(0) ["logo"]=> string(28) "685a2040b8017_1750736960.jpg" }
 */
?>
<?php require __DIR__ . '/components/header.php';?>

<!-- Professional Product Details Page -->
<style>
.product-details {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 2rem 0;
}

.product-image-container {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-image {
    max-width: 100%;
    max-height: 400px;
    object-fit: contain;
    border-radius: 10px;
}

.product-info {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    height: 100%;
}

.breadcrumb-custom {
    background: white;
    border-radius: 10px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.product-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.product-brand {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 1rem;
}

.price-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin: 1.5rem 0;
}

.price-main {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0;
}

.price-currency {
    font-size: 1.2rem;
    opacity: 0.9;
}

.product-description {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    padding: 1rem;
    border-radius: 15px;
    border-left: 5px solid #2196f3;
    margin: 0.5rem;
    height: 400px;
    box-shadow: 0 3px 10px rgba(33, 150, 243, 0.1);
}

.product-description h6 {
    color:rgb(25, 118, 210);
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 1.2rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.product-description p {
    color: #424242;
    line-height: 1.8;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.product-description hr {
    border-top: 2px solid #2196f3;
    opacity: 0.3;
    margin: 1rem 0;
}

.description-content {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin: 1rem 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.key-features {
    background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid #ff9800;
}

.key-features h6 {
    color: #f57c00;
    font-weight: 600;
    font-size: 1rem;
}



.action-buttons {
    gap: 1rem;
    margin: 2rem 0;
}

.btn-add-to-cart {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    padding: 0.8rem 2rem;
    font-weight: 600;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.btn-add-to-cart:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
}

.btn-wishlist {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    border: none;
    padding: 0.8rem 2rem;
    font-weight: 600;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.btn-wishlist:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
}

.social-share {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    margin-top: 2rem;
}

.social-btn {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0.25rem;
    transition: all 0.3s ease;
    border: none;
}

.social-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.back-button {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.back-button:hover {
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}

/* Reviews Section Styles */
.reviews-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.reviews-header h3 {
    color: #2c3e50;
    font-weight: 700;
}

.review-stats-container {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.overall-rating {
    text-align: center;
}

.rating-number {
    font-size: 3rem;
    font-weight: bold;
    color: #ffc107;
    line-height: 1;
}

.rating-stars {
    font-size: 1.5rem;
    color: #ffc107;
    margin: 0.5rem 0;
}

.rating-text {
    color: #6c757d;
    font-size: 0.9rem;
}

.rating-breakdown {
    padding-left: 2rem;
}

.rating-bar {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
}

.rating-bar-label {
    width: 60px;
    font-size: 0.9rem;
    color: #6c757d;
}

.rating-bar-fill {
    flex: 1;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    margin: 0 1rem;
    overflow: hidden;
}

.rating-bar-progress {
    height: 100%;
    background: #ffc107;
    transition: width 0.3s ease;
}

.rating-bar-count {
    width: 40px;
    text-align: right;
    font-size: 0.9rem;
    color: #6c757d;
}

.review-form-container {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 2rem;
}

.rating-input {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.rating-input i {
    font-size: 1.5rem;
    color: #dee2e6;
    cursor: pointer;
    transition: color 0.2s ease;
}

.rating-input i:hover,
.rating-input i.active {
    color: #ffc107 !important;
    transform: scale(1.1);
}

.rating-input i.selected {
    color: #ffc107 !important;
    text-shadow: 0 0 5px rgba(255, 193, 7, 0.5);
}

.review-item {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    background: white;
}

.review-header {
    display: flex;
    justify-content: between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.review-author {
    font-weight: 600;
    color: #2c3e50;
}

.review-date {
    color: #6c757d;
    font-size: 0.9rem;
}

.review-rating {
    color: #ffc107;
    margin: 0.25rem 0;
}

.review-title {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.review-text {
    color: #6c757d;
    line-height: 1.6;
}

.review-actions {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.helpful-btn {
    background: none;
    border: 1px solid #dee2e6;
    color: #6c757d;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    transition: all 0.2s ease;
}

.helpful-btn:hover {
    border-color: #007bff;
    color: #007bff;
}

@media (max-width: 768px) {
    .product-details {
        padding: 1rem 0;
    }
    
    .product-image-container,
    .product-info {
        margin-bottom: 1rem;
    }
    
    .product-title {
        font-size: 1.5rem;
    }
    
    .price-main {
        font-size: 1.5rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .reviews-section {
        padding: 1rem;
    }
    
    .review-form-container {
        padding: 1rem;
    }
    
    .rating-breakdown {
        padding-left: 0;
        margin-top: 1rem;
    }
    
    .overall-rating {
        margin-bottom: 1rem;
    }
}
</style>

<div class="product-details">
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="breadcrumb-custom">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                <?php if (!empty($products[0]['category_name'])): ?>
                    <li class="breadcrumb-item"><a href="index.php?category=<?= $products[0]['category_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($products[0]['category_name']) ?></a></li>
                <?php endif; ?>
                <?php if (!empty($products[0]['subcategory_name'])): ?>
                    <li class="breadcrumb-item"><a href="index.php?subcategory=<?= $products[0]['subcategory_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($products[0]['subcategory_name']) ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($products[0]['name']) ?></li>
            </ol>
        </nav>

        <!-- Back Button -->
        <div class="mb-3">
            <a href="javascript:history.back()" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Products
            </a>
        </div>

        <div class="row">
            <!-- Product Image -->
            <div class="col-lg-6 mb-4">
                <div class="product-image-container">
                    <?php 
                    $imagePath = 'assets/products/' . $products[0]['image'];
                    $imageUrl = (!empty($products[0]['image']) && file_exists($imagePath)) ? settings()['root'] . $imagePath : settings()['logo'];
                    ?>
                    <img src="<?= $imageUrl ?>" 
                         alt="<?= htmlspecialchars($products[0]['name']) ?>" 
                         class="product-image"
                         id="main-image">
                </div>
            </div>

            <!-- Product Information -->
            <div class="col-lg-6">
                <div class="product-info">
                    <!-- Product Brand -->
                    <?php if (!empty($products[0]['brand_name'])): ?>
                        <div class="product-brand">
                            <i class="fas fa-award me-2"></i><?= htmlspecialchars($products[0]['brand_name']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Product Title -->
                    <h1 class="product-title"><?= htmlspecialchars($products[0]['name']) ?></h1>

                    <!-- Price Section -->
                    <div class="price-section">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="price-main">
                                    <span class="price-currency">৳</span><?= number_format($products[0]['selling_price'], 2) ?>
                                </div>
                                <small class="opacity-75">Per Unit</small>
                            </div>
                            <div class="text-end">
                                <?php if ($products[0]['stock_quantity'] > 0): ?>
                                    <span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>In Stock</span>
                                    <br><small class="opacity-75"><?= $products[0]['stock_quantity'] ?> units available</small>
                                <?php else: ?>
                                    <span class="badge bg-danger fs-6"><i class="fas fa-times-circle me-1"></i>Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Product Description -->
                    <div class="product-description">
                       
                                    
                                        
                                        <div class="card-body">
                                            <?php echo !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : '<p class="text-muted">No full description provided.</p>'; ?>
                                        </div>
                                   
                               
                       
                        
                        
                        
                        
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-3 action-buttons">
                        <button class="btn btn-add-to-cart btn-lg flex-fill btn-add-cart" 
                                data-product-id="<?= $products[0]['id'] ?>" 
                                data-product-name="<?= htmlspecialchars($products[0]['name']) ?>" 
                                data-product-price="<?= $products[0]['selling_price'] ?>" 
                                data-product-image="<?= htmlspecialchars($products[0]['image']) ?>"
                                <?= $products[0]['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                            <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                        </button>
                        
                        <button class="btn btn-wishlist btn-lg flex-fill btn-add-wishlist" 
                                data-product-id="<?= $products[0]['id'] ?>" 
                                data-product-name="<?= htmlspecialchars($products[0]['name']) ?>" 
                                data-product-price="<?= $products[0]['selling_price'] ?>" 
                                data-product-image="<?= htmlspecialchars($products[0]['image']) ?>">
                            <i class="far fa-heart me-2"></i>Add to Wishlist
                        </button>
                    </div>

                    <!-- Social Share Section -->
                    <div class="social-share">
                        <h6 class="mb-3"><i class="fas fa-share-alt me-2"></i>Share this product</h6>
                        <div class="d-flex flex-wrap justify-content-center">
                            <button class="social-btn btn-primary" onclick="shareOnFacebook()" title="Share on Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </button>
                            <button class="social-btn btn-info" onclick="shareOnTwitter()" title="Share on Twitter">
                                <i class="fab fa-twitter"></i>
                            </button>
                            <button class="social-btn btn-success" onclick="shareOnWhatsApp()" title="Share on WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                            <button class="social-btn" style="background: #bd081c" onclick="shareOnPinterest()" title="Share on Pinterest">
                                <i class="fab fa-pinterest"></i>
                            </button>
                            <button class="social-btn" style="background: #0077b5" onclick="shareOnLinkedIn()" title="Share on LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </button>
                            <button class="social-btn btn-secondary" onclick="copyToClipboard()" title="Copy Link">
                                <i class="fas fa-link"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="reviews-section">
                    <!-- Reviews Header -->
                    <div class="reviews-header">
                        <h3 class="mb-4"><i class="fas fa-star me-2"></i>Customer Reviews</h3>
                        
                        <!-- Review Statistics -->
                        <div class="review-stats-container" id="reviewStatsContainer">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="overall-rating">
                                        <div class="rating-number" id="overallRating"><?= $reviewStats['average_rating'] ?></div>
                                        <div class="rating-stars" id="overallStars">
                                            <?php
                                            $rating = $reviewStats['average_rating'];
                                            $fullStars = floor($rating);
                                            $hasHalfStar = ($rating - $fullStars) >= 0.5;
                                            
                                            // Display full stars
                                            for ($i = 0; $i < $fullStars; $i++) {
                                                echo '<i class="fas fa-star"></i>';
                                            }
                                            
                                            // Display half star if needed
                                            if ($hasHalfStar) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            }
                                            
                                            // Display empty stars
                                            $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                                            for ($i = 0; $i < $emptyStars; $i++) {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                            ?>
                                        </div>
                                        <div class="rating-text">Based on <span id="totalReviews"><?= $reviewStats['total_reviews'] ?></span> reviews</div>
                                    </div>
                                </div>
                                <div class="col-lg-8">
                                    <div class="rating-breakdown" id="ratingBreakdown">
                                        <?php for ($i = 5; $i >= 1; $i--): 
                                            $count = $reviewStats['rating_distribution'][$i];
                                            $percentage = $reviewStats['total_reviews'] > 0 ? ($count / $reviewStats['total_reviews']) * 100 : 0;
                                        ?>
                                        <div class="rating-bar">
                                            <div class="rating-bar-label"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></div>
                                            <div class="rating-bar-fill">
                                                <div class="rating-bar-progress" style="width: <?= $percentage ?>%"></div>
                                            </div>
                                            <div class="rating-bar-count"><?= $count ?></div>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Review Form -->
                    <div class="review-form-section mt-4">
                        <h4 class="mb-3">Write a Review</h4>
                        <div class="review-form-container">
                            <form id="reviewForm" class="review-form">
                                <input type="hidden" name="product_id" value="<?= $products[0]['id'] ?>">
                                
                                <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true): ?>
                                <!-- Guest user - show name and email fields -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="customerName" class="form-label">Your Name *</label>
                                            <input type="text" class="form-control" id="customerName" name="customer_name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="customerEmail" class="form-label">Your Email *</label>
                                            <input type="email" class="form-control" id="customerEmail" name="customer_email" required>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <!-- Logged in user - hide name and email fields, show user info -->
                                <div class="mb-3">
                                    <div class="alert alert-info">
                                        <i class="fas fa-user-check me-2"></i>
                                        <strong>Reviewing as:</strong> <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                                      
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label class="form-label">Rating *</label>
                                    <div class="rating-input" id="ratingInput">
                                        <i class="fas fa-star" data-rating="1"></i>
                                        <i class="fas fa-star" data-rating="2"></i>
                                        <i class="fas fa-star" data-rating="3"></i>
                                        <i class="fas fa-star" data-rating="4"></i>
                                        <i class="fas fa-star" data-rating="5"></i>
                                    </div>
                                    <input type="hidden" name="rating" id="selectedRating" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="reviewTitle" class="form-label">Review Title</label>
                                    <input type="text" class="form-control" id="reviewTitle" name="title" placeholder="Summarize your review">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="reviewText" class="form-label">Your Review *</label>
                                    <textarea class="form-control" id="reviewText" name="review_text" rows="4" required placeholder="Share your experience with this product..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Review
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Reviews List -->
                    <div class="reviews-list-section mt-5">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4>Customer Reviews</h4>
                            <div class="reviews-sort">
                                <select class="form-select" id="reviewsSort">
                                    <option value="newest">Newest First</option>
                                    <option value="oldest">Oldest First</option>
                                    <option value="highest">Highest Rating</option>
                                    <option value="lowest">Lowest Rating</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="reviewsList">
                            <?php if (empty($recentReviews)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-comments fa-3x mb-3"></i><br>
                                    No reviews yet. Be the first to review this product!
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentReviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div>
                                            <div class="review-author"><?= htmlspecialchars($review['customer_name']) ?></div>
                                            <div class="review-rating">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $review['rating']) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <div class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></div>
                                    </div>
                                    <?php if (!empty($review['title'])): ?>
                                        <div class="review-title"><?= htmlspecialchars($review['title']) ?></div>
                                    <?php endif; ?>
                                    <div class="review-text"><?= htmlspecialchars($review['review_text']) ?></div>
                                    <div class="review-actions">
                                        <button class="helpful-btn" onclick="markHelpful(<?= $review['id'] ?>)">
                                            <i class="fas fa-thumbs-up me-1"></i>Helpful (<?= $review['helpful_votes'] ?>)
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-center mt-4">
                            <?php if ($reviewStats['total_reviews'] > 5): ?>
                            <button class="btn btn-outline-primary" id="loadMoreReviews">
                                <i class="fas fa-plus me-2"></i>Load More Reviews
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Social Sharing JavaScript -->
<script>
// Product information for sharing
const productInfo = <?= json_encode([
    'name' => $products[0]['name'],
    'price' => $products[0]['selling_price'],
    'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
    'image' => $imageUrl,
    'description' => $products[0]['description'] ?? $products[0]['short_description'] ?? 'Check out this amazing product!'
], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

// Use window.location.href for current URL
productInfo.url = window.location.href;

function shareOnFacebook() {
    const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(productInfo.url)}`;
    window.open(url, '_blank', 'width=600,height=400');
}

function shareOnTwitter() {
    const text = `Check out ${productInfo.name} for only ৳${productInfo.price}!`;
    const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(productInfo.url)}`;
    window.open(url, '_blank', 'width=600,height=400');
}

function shareOnWhatsApp() {
    const text = `Check out this amazing product: *${productInfo.name}* for only ৳${productInfo.price}! ${productInfo.url}`;
    const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
}

function shareOnPinterest() {
    const url = `https://pinterest.com/pin/create/button/?url=${encodeURIComponent(productInfo.url)}&media=${encodeURIComponent(productInfo.image)}&description=${encodeURIComponent(productInfo.description)}`;
    window.open(url, '_blank', 'width=600,height=400');
}

function shareOnLinkedIn() {
    const url = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(productInfo.url)}`;
    window.open(url, '_blank', 'width=600,height=400');
}

function copyToClipboard() {
    navigator.clipboard.writeText(productInfo.url).then(function() {
        // Show success notification
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Link Copied!',
                text: 'Product link copied to clipboard',
                timer: 2000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        } else {
            alert('Product link copied to clipboard!');
        }
    }).catch(function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = productInfo.url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Product link copied to clipboard!');
    });
}

// Image zoom functionality
document.addEventListener('DOMContentLoaded', function() {
    const mainImage = document.getElementById('main-image');
    if (mainImage) {
        mainImage.addEventListener('click', function() {
            // Create modal for image zoom
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                cursor: pointer;
            `;
            
            const zoomedImage = document.createElement('img');
            zoomedImage.src = this.src;
            zoomedImage.style.cssText = `
                max-width: 90%;
                max-height: 90%;
                object-fit: contain;
                border-radius: 10px;
            `;
            
            modal.appendChild(zoomedImage);
            document.body.appendChild(modal);
            
            modal.addEventListener('click', function() {
                document.body.removeChild(modal);
            });
        });
        
        // Add cursor pointer to indicate clickable image
        mainImage.style.cursor = 'pointer';
        mainImage.title = 'Click to zoom';
    }

    // Reviews System JavaScript
    let currentPage = 2; // Start from page 2 since page 1 is already loaded by PHP
    let currentSort = 'newest';
    const productId = <?= $products[0]['id'] ?>;
    const totalReviews = <?= $reviewStats['total_reviews'] ?>;

    // Initialize reviews system - stats are already loaded by PHP, no need to reload
    // loadReviewStats(); // Commented out since data is already loaded
    // loadReviews(); // Commented out since initial reviews are already loaded

    // Rating input functionality
    const ratingStars = document.querySelectorAll('#ratingInput i');
    const selectedRatingInput = document.getElementById('selectedRating');

    console.log('Rating stars found:', ratingStars.length);
    console.log('Selected rating input:', selectedRatingInput);

    ratingStars.forEach((star, index) => {
        star.addEventListener('mouseover', function() {
            console.log('Hovering over star:', index + 1);
            highlightStars(index + 1);
        });

        star.addEventListener('click', function() {
            const rating = index + 1;
            console.log('Clicked star:', rating);
            selectedRatingInput.value = rating;
            setActiveStars(rating);
        });
    });

    const ratingContainer = document.getElementById('ratingInput');
    if (ratingContainer) {
        ratingContainer.addEventListener('mouseleave', function() {
            // Clear hover effects but keep selected stars
            ratingStars.forEach(star => {
                star.classList.remove('active');
            });
            
            const currentRating = selectedRatingInput.value;
            if (currentRating) {
                setActiveStars(parseInt(currentRating));
            }
        });
    }

    function highlightStars(count) {
        ratingStars.forEach((star, index) => {
            star.classList.toggle('active', index < count);
        });
    }

    function setActiveStars(count) {
        ratingStars.forEach((star, index) => {
            star.classList.remove('active', 'selected');
            if (index < count) {
                star.classList.add('selected');
            }
        });
    }

    function clearStars() {
        ratingStars.forEach(star => {
            star.classList.remove('active', 'selected');
        });
    }

    // Review form submission
    document.getElementById('reviewForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'submit_review');

        if (!selectedRatingInput.value) {
            alert('Please select a rating');
            return;
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
        submitBtn.disabled = true;

        console.log('Submitting review for product:', productId);
        console.log('Form data:', Object.fromEntries(formData));
        
        fetch('api/reviews.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid JSON response: ' + text);
                }
            });
        })
        .then(data => {
            console.log('Parsed response:', data);
            if (data.success) {
                alert(data.message);
                this.reset();
                selectedRatingInput.value = '';
                clearStars();
                // Reload the page to show updated reviews
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while submitting your review');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });

    // Load review statistics
    function loadReviewStats() {
        fetch(`api/reviews.php?action=get_review_stats&product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateReviewStats(data.stats);
            }
        })
        .catch(error => console.error('Error loading review stats:', error));
    }

    // Update review statistics display
    function updateReviewStats(stats) {
        document.getElementById('overallRating').textContent = stats.average_rating.toFixed(1);
        document.getElementById('totalReviews').textContent = stats.total_reviews;
        
        // Update overall stars
        const overallStars = document.getElementById('overallStars');
        overallStars.innerHTML = generateStarRating(stats.average_rating);

        // Update rating breakdown
        const ratingBreakdown = document.getElementById('ratingBreakdown');
        let breakdownHTML = '';
        
        for (let i = 5; i >= 1; i--) {
            const count = stats.rating_distribution[i] || 0;
            const percentage = stats.total_reviews > 0 ? (count / stats.total_reviews) * 100 : 0;
            
            breakdownHTML += `
                <div class="rating-bar">
                    <div class="rating-bar-label">${i} star${i > 1 ? 's' : ''}</div>
                    <div class="rating-bar-fill">
                        <div class="rating-bar-progress" style="width: ${percentage}%"></div>
                    </div>
                    <div class="rating-bar-count">${count}</div>
                </div>
            `;
        }
        
        ratingBreakdown.innerHTML = breakdownHTML;
    }

    // Load reviews
    function loadReviews(page = 1, append = false) {
        const url = `api/reviews.php?action=get_reviews&product_id=${productId}&page=${page}&limit=5`;
        
        fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayReviews(data.reviews, append);
                updatePagination(data.pagination);
            }
        })
        .catch(error => console.error('Error loading reviews:', error));
    }

    // Display reviews
    function displayReviews(reviews, append = false) {
        const reviewsList = document.getElementById('reviewsList');
        
        if (!append) {
            reviewsList.innerHTML = '';
        }

        if (reviews.length === 0 && !append) {
            reviewsList.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-comments fa-3x mb-3"></i><br>No reviews yet. Be the first to review this product!</div>';
            return;
        }

        reviews.forEach(review => {
            const reviewHTML = `
                <div class="review-item">
                    <div class="review-header">
                        <div>
                            <div class="review-author">${review.customer_name}</div>
                            <div class="review-rating">${generateStarRating(review.rating)}</div>
                        </div>
                        <div class="review-date">${review.created_at}</div>
                    </div>
                    ${review.title ? `<div class="review-title">${review.title}</div>` : ''}
                    <div class="review-text">${review.review_text}</div>
                    <div class="review-actions">
                        <button class="helpful-btn" onclick="markHelpful(${review.id})">
                            <i class="fas fa-thumbs-up me-1"></i>Helpful (${review.helpful_votes})
                        </button>
                    </div>
                </div>
            `;
            reviewsList.insertAdjacentHTML('beforeend', reviewHTML);
        });
    }

    // Generate star rating HTML
    function generateStarRating(rating) {
        let stars = '';
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        
        for (let i = 0; i < fullStars; i++) {
            stars += '<i class="fas fa-star"></i>';
        }
        
        if (hasHalfStar) {
            stars += '<i class="fas fa-star-half-alt"></i>';
        }
        
        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
        for (let i = 0; i < emptyStars; i++) {
            stars += '<i class="far fa-star"></i>';
        }
        
        return stars;
    }

    // Update pagination
    function updatePagination(pagination) {
        const loadMoreBtn = document.getElementById('loadMoreReviews');
        
        if (loadMoreBtn && pagination.current_page < pagination.total_pages) {
            loadMoreBtn.style.display = 'block';
            loadMoreBtn.onclick = () => {
                loadReviews(currentPage, true);
                currentPage++;
            };
        } else if (loadMoreBtn) {
            loadMoreBtn.style.display = 'none';
        }
    }

    // Initialize load more button if it exists
    const loadMoreBtn = document.getElementById('loadMoreReviews');
    if (loadMoreBtn) {
        loadMoreBtn.onclick = () => {
            loadReviews(currentPage, true);
            currentPage++;
        };
    }

    // Mark review as helpful
    window.markHelpful = function(reviewId) {
        // This would require additional API endpoint
        console.log('Mark helpful:', reviewId);
    };

    // Reviews sorting
    const sortSelect = document.getElementById('reviewsSort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            currentSort = this.value;
            currentPage = 2; // Reset to page 2 since page 1 will be reloaded
            // For now, just reload reviews (sorting would need to be implemented in API)
            loadReviews(1, false); // Load page 1 with new sorting
        });
    }
});
</script>

<?php require __DIR__ . '/components/footer.php';?>
<?php $db->disconnect();?>
</body>
</html>

