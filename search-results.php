<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone to Bangladesh Standard Time
date_default_timezone_set('Asia/Dhaka');

require_once __DIR__ . '/vendor/autoload.php';

$page = 'Search Results';

// Get search query
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

$products = [];
$total_products = 0;
$categories = [];
$brands = [];
$all_results = [];
$total_results = 0;

// Use the comprehensive search API
if (!empty($query)) {
    // Make API call to get comprehensive search results
    $api_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/apis/search.php?q=" . urlencode($query);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'timeout' => 10
        ]
    ]);

    $response = file_get_contents($api_url, false, $context);

    if ($response !== false) {
        $search_data = json_decode($response, true);
        if ($search_data && isset($search_data['success']) && $search_data['success']) {
            $all_results = $search_data['data'];
            $total_results = $search_data['total_results'];

            // Extract products for backward compatibility with existing display logic
}

.search-results-section .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
</style>

<!-- Professional Search Header -->
<div class="search-header">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="display-5 fw-bold mb-3">Search Results</h1>
                <?php if (!empty($query)): ?>
                    <p class="lead mb-2">Results for "<strong><?= htmlspecialchars($query) ?></strong>"</p>
                <?php endif; ?>
                <p class="mb-0"><?= $total_results ?> total results found</p>
            </div>
        </div>
    </div>
</div>

<div class="container my-4">

    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="search-results.php" id="filterForm">
                        <input type="hidden" name="q" value="<?= htmlspecialchars($query) ?>">
                        
                        <!-- Category Filter -->
                        <div class="mb-3">
                            <label class="form-label"><strong>Category</strong></label>
                            <select name="category" class="form-select" onchange="document.getElementById('filterForm').submit()">
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
                            <label class="form-label"><strong>Brand</strong></label>
                            <select name="brand" class="form-select" onchange="document.getElementById('filterForm').submit()">
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
                            <label class="form-label"><strong>Price Range</strong></label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="number" name="min_price" class="form-control" placeholder="Min" 
                                           value="<?= htmlspecialchars($min_price) ?>" step="0.01">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="max_price" class="form-control" placeholder="Max" 
                                           value="<?= htmlspecialchars($max_price) ?>" step="0.01">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        <a href="search-results.php?q=<?= urlencode($query) ?>" class="btn btn-outline-secondary w-100 mt-2">Clear Filters</a>
                    </form>
                </div>
            </div>
        </div>

        <!-- Comprehensive Search Results -->
        <div class="col-lg-9 col-md-8">
            <?php if (!empty($all_results) && $total_results > 0): ?>

                <!-- Products Section -->
                <?php if (!empty($all_results['products'])): ?>
                <div class="search-results-section mb-5">
                    <h3 class="section-title mb-3">
                        <i class="fas fa-box me-2 text-primary"></i>
                        Products (<?= count($all_results['products']) ?>)
                    </h3>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
                        <?php foreach ($all_results['products'] as $product):
                            $imagePath = 'assets/products/' . $product['image'];
                            $imageUrl = file_exists($imagePath) ? settings()['root'] . $imagePath : settings()['logo'];
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
                                        <p class="card-text text-primary fw-bold mb-3">৳<?= number_format($product['price'], 2) ?></p>
                                        <div class="mt-auto">
                                            <a href="product-details.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-eye me-1"></i> View Details
                                            </a>
                                            <button class="btn btn-primary w-100 mt-2 btn-add-cart"
                                                    data-product-id="<?= $product['id'] ?>"
                                                    data-product-name="<?= htmlspecialchars($product['name']) ?>"
                                                    data-product-price="<?= $product['price'] ?>"
                                                    data-product-image="<?= htmlspecialchars($product['image'] ?? '') ?>">
                                                <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Categories Section -->
                <?php if (!empty($all_results['categories'])): ?>
                <div class="search-results-section mb-5">
                    <h3 class="section-title mb-3">
                        <i class="fas fa-tags me-2 text-success"></i>
                        Categories (<?= count($all_results['categories']) ?>)
                    </h3>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3">
                        <?php foreach ($all_results['categories'] as $category):
                            $imagePath = 'assets/categories/' . $category['image'];
                            $imageUrl = file_exists($imagePath) ? settings()['root'] . $imagePath : 'admin/assets/img/no-image.png';
                        ?>
                            <div class="col">
                                <div class="card h-100 shadow-sm">
                                    <div style="height: 120px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                        <img src="<?= $imageUrl ?>"
                                             class="card-img-top p-2"
                                             alt="<?= htmlspecialchars($category['name']) ?>"
                                             style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                    </div>
                                    <div class="card-body text-center">
                                        <h6 class="card-title mb-2"><?= htmlspecialchars($category['name']) ?></h6>
                                        <a href="index.php?category=<?= $category['id'] ?>" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-eye me-1"></i> Browse
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Brands Section -->
                <?php if (!empty($all_results['brands'])): ?>
                <div class="search-results-section mb-5">
                    <h3 class="section-title mb-3">
                        <i class="fas fa-star me-2 text-warning"></i>
                        Brands (<?= count($all_results['brands']) ?>)
                    </h3>
                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-6 g-3">
                        <?php foreach ($all_results['brands'] as $brand):
                            $imagePath = 'assets/brands/' . $brand['logo'];
                            $imageUrl = file_exists($imagePath) ? settings()['root'] . $imagePath : 'admin/assets/img/no-image.png';
                        ?>
                            <div class="col">
                                <div class="card h-100 shadow-sm">
                                    <div style="height: 80px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                        <img src="<?= $imageUrl ?>"
                                             class="card-img-top p-2"
                                             alt="<?= htmlspecialchars($brand['name']) ?>"
                                             style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                    </div>
                                    <div class="card-body text-center p-2">
                                        <h6 class="card-title mb-1" style="font-size: 0.8rem;"><?= htmlspecialchars($brand['name']) ?></h6>
                                        <a href="brands.php?brand=<?= $brand['id'] ?>" class="btn btn-outline-warning btn-sm" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Reviews Section -->
                <?php if (!empty($all_results['reviews'])): ?>
                <div class="search-results-section mb-5">
                    <h3 class="section-title mb-3">
                        <i class="fas fa-star me-2 text-info"></i>
                        Reviews (<?= count($all_results['reviews']) ?>)
                    </h3>
                    <div class="row row-cols-1 row-cols-md-2 g-3">
                        <?php foreach ($all_results['reviews'] as $review): ?>
                            <div class="col">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?= htmlspecialchars($review['customer_name']) ?></h6>
                                                <div class="text-warning mb-1">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-o' ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <small class="text-muted">★ <?= $review['rating'] ?>/5</small>
                                        </div>
                                        <?php if (!empty($review['title'])): ?>
                                            <h6 class="card-title mb-2">"<?= htmlspecialchars($review['title']) ?>"</h6>
                                        <?php endif; ?>
                                        <p class="card-text mb-2" style="font-size: 0.9rem;"><?= htmlspecialchars(substr($review['review_text'], 0, 150)) ?><?php if (strlen($review['review_text']) > 150): ?>...<?php endif; ?></p>
                                        <a href="product-details.php?id=<?= $review['product_id'] ?>" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-eye me-1"></i> View Product
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Admin Results (only for admin users) -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>

                    <!-- Users Section -->
                    <?php if (!empty($all_results['users'])): ?>
                    <div class="search-results-section mb-5">
                        <h3 class="section-title mb-3">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Users (<?= count($all_results['users']) ?>)
                        </h3>
                        <div class="row row-cols-1 row-cols-md-3 g-3">
                            <?php foreach ($all_results['users'] as $user): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body">
                                            <h6 class="card-title mb-2">
                                                <i class="fas fa-user-circle me-2"></i>
                                                <?= htmlspecialchars($user['name']) ?>
                                            </h6>
                                            <p class="card-text mb-2">
                                                <i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($user['email']) ?>
                                            </p>
                                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'secondary' ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                            <div class="mt-2">
                                                <a href="admin/user-view.php?id=<?= $user['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i> View Profile
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Orders Section -->
                    <?php if (!empty($all_results['orders'])): ?>
                    <div class="search-results-section mb-5">
                        <h3 class="section-title mb-3">
                            <i class="fas fa-shopping-bag me-2 text-success"></i>
                            Orders (<?= count($all_results['orders']) ?>)
                        </h3>
                        <div class="row row-cols-1 row-cols-md-2 g-3">
                            <?php foreach ($all_results['orders'] as $order): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-0">
                                                    Order #<?= htmlspecialchars($order['order_number']) ?>
                                                </h6>
                                                <span class="badge bg-<?= $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </div>
                                            <p class="card-text mb-1">
                                                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($order['customer_name']) ?>
                                            </p>
                                            <p class="card-text mb-2">
                                                <i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($order['customer_email']) ?>
                                            </p>
                                            <p class="card-text fw-bold text-primary mb-2">
                                                Total: ৳<?= number_format($order['total_amount'], 2) ?>
                                            </p>
                                            <a href="admin/order-details.php?id=<?= $order['id'] ?>" class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-eye me-1"></i> View Order
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Contact Messages Section -->
                    <?php if (!empty($all_results['contact_messages'])): ?>
                    <div class="search-results-section mb-5">
                        <h3 class="section-title mb-3">
                            <i class="fas fa-envelope me-2 text-warning"></i>
                            Contact Messages (<?= count($all_results['contact_messages']) ?>)
                        </h3>
                        <div class="row row-cols-1 row-cols-md-2 g-3">
                            <?php foreach ($all_results['contact_messages'] as $message): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-0">
                                                    <i class="fas fa-user me-1"></i> <?= htmlspecialchars($message['name']) ?>
                                                </h6>
                                                <span class="badge bg-<?= $message['priority'] === 'high' ? 'danger' : ($message['priority'] === 'medium' ? 'warning' : 'info') ?>">
                                                    <?= ucfirst($message['priority']) ?>
                                                </span>
                                            </div>
                                            <p class="card-text mb-1">
                                                <i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($message['email']) ?>
                                            </p>
                                            <h6 class="card-title mb-2">"<?= htmlspecialchars($message['subject']) ?>"</h6>
                                            <p class="card-text mb-2" style="font-size: 0.9rem;"><?= htmlspecialchars($message['message']) ?></p>
                                            <a href="admin/contact-view.php?id=<?= $message['id'] ?>" class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-eye me-1"></i> View Message
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Notifications Section -->
                    <?php if (!empty($all_results['notifications'])): ?>
                    <div class="search-results-section mb-5">
                        <h3 class="section-title mb-3">
                            <i class="fas fa-bell me-2 text-info"></i>
                            Notifications (<?= count($all_results['notifications']) ?>)
                        </h3>
                        <div class="row row-cols-1 row-cols-md-2 g-3">
                            <?php foreach ($all_results['notifications'] as $notification): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-0 <?= $notification['is_read'] ? 'text-muted' : 'fw-bold' ?>">
                                                    <?= htmlspecialchars($notification['title']) ?>
                                                </h6>
                                                <small class="badge bg-<?= $notification['is_read'] ? 'secondary' : 'primary' ?>">
                                                    <?= $notification['is_read'] ? 'Read' : 'Unread' ?>
                                                </small>
                                            </div>
                                            <p class="card-text mb-2" style="font-size: 0.9rem;"><?= htmlspecialchars($notification['message']) ?></p>
                                            <small class="text-muted mb-2 d-block">
                                                Type: <?= ucfirst($notification['type']) ?>
                                            </small>
                                            <a href="admin/notification-management.php" class="btn btn-outline-info btn-sm">
                                                <i class="fas fa-bell me-1"></i> View All
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Team Members Section -->
                    <?php if (!empty($all_results['team_members'])): ?>
                    <div class="search-results-section mb-5">
                        <h3 class="section-title mb-3">
                            <i class="fas fa-user-tie me-2 text-secondary"></i>
                            Team Members (<?= count($all_results['team_members']) ?>)
                        </h3>
                        <div class="row row-cols-1 row-cols-md-3 g-3">
                            <?php foreach ($all_results['team_members'] as $member): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body text-center">
                                            <?php if (!empty($member['image'])): ?>
                                                <img src="assets/team/<?= htmlspecialchars($member['image']) ?>"
                                                     alt="<?= htmlspecialchars($member['name']) ?>"
                                                     class="rounded-circle mb-3"
                                                     style="width: 60px; height: 60px; object-fit: cover;">
                                            <?php endif; ?>
                                            <h6 class="card-title mb-1"><?= htmlspecialchars($member['name']) ?></h6>
                                            <p class="card-text text-muted mb-2"><?= htmlspecialchars($member['position']) ?></p>
                                            <a href="admin/team-all.php" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-users me-1"></i> View Team
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Chat Messages Section -->
                    <?php if (!empty($all_results['chat_messages'])): ?>
                    <div class="search-results-section mb-5">
                        <h3 class="section-title mb-3">
                            <i class="fas fa-comments me-2 text-primary"></i>
                            Chat Messages (<?= count($all_results['chat_messages']) ?>)
                        </h3>
                        <div class="row row-cols-1 g-3">
                            <?php foreach ($all_results['chat_messages'] as $message): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <strong class="me-2">
                                                    <i class="fas fa-user me-1"></i> <?= htmlspecialchars($message['username']) ?>:
                                                </strong>
                                                <small class="text-muted ms-auto">
                                                    <?= date('M d, H:i', strtotime($message['created_at'])) ?>
                                                </small>
                                            </div>
                                            <p class="card-text mb-2" style="font-size: 0.95rem; font-style: italic;">
                                                "<?= htmlspecialchars($message['message']) ?>"
                                            </p>
                                            <a href="admin/live-chat.php" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-comments me-1"></i> View Chat
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Discounts Section -->
                    <?php if (!empty($all_results['discounts'])): ?>
                    <div class="search-results-section mb-5">
                        <h3 class="section-title mb-3">
                            <i class="fas fa-percent me-2 text-success"></i>
                            Discounts (<?= count($all_results['discounts']) ?>)
                        </h3>
                        <div class="row row-cols-1 row-cols-md-3 g-3">
                            <?php foreach ($all_results['discounts'] as $discount): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-0">
                                                    <?= htmlspecialchars($discount['name']) ?>
                                                </h6>
                                                <span class="badge bg-<?= $discount['is_active'] ? 'success' : 'secondary' ?>">
                                                    <?= $discount['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </div>
                                            <p class="card-text mb-2" style="font-size: 0.9rem;">
                                                <?= htmlspecialchars(substr($discount['description'], 0, 100)) ?>
                                                <?php if (strlen($discount['description']) > 100): ?>...<?php endif; ?>
                                            </p>
                                            <p class="card-text mb-2">
                                                <strong><?= ucfirst($discount['discount_type']) ?>: <?= $discount['discount_value'] ?>
                                                <?php if ($discount['discount_type'] === 'percentage'): ?>%<?php endif; ?></strong>
                                            </p>
                                            <a href="admin/discounts-management.php" class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-percent me-1"></i> Manage Discounts
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Reports Section -->
                    <?php if (!empty($all_results['reports'])): ?>
                    <div class="search-results-section mb-5">
                        <h3 class="section-title mb-3">
                            <i class="fas fa-chart-bar me-2 text-info"></i>
                            Reports (<?= count($all_results['reports']) ?>)
                        </h3>
                        <div class="row row-cols-1 row-cols-md-2 g-3">
                            <?php foreach ($all_results['reports'] as $report): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body">
                                            <h6 class="card-title mb-2">
                                                <i class="fas fa-file-alt me-1"></i> <?= htmlspecialchars($report['title']) ?>
                                            </h6>
                                            <p class="card-text mb-2" style="font-size: 0.9rem;">
                                                <?= htmlspecialchars(substr($report['content'], 0, 150)) ?>
                                                <?php if (strlen($report['content']) > 150): ?>...<?php endif; ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-secondary">
                                                    <?= ucfirst($report['type']) ?>
                                                </span>
                                                <a href="admin/reports.php" class="btn btn-outline-info btn-sm">
                                                    <i class="fas fa-eye me-1"></i> View Report
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <h3 class="text-muted">No results found</h3>
                        <p class="text-muted">Try adjusting your search query or browse our categories.</p>
                        <a href="index.php" class="btn btn-primary btn-lg mt-3">Browse All Products</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Sort Options -->
            <?php if (!empty($products)): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="text-muted">Showing <?= min($offset + 1, $total_products) ?>-<?= min($offset + $limit, $total_products) ?> of <?= $total_products ?> results</span>
                </div>
                <div>
                    <select name="sort" class="form-select" style="width: auto; display: inline-block;" 
                            onchange="updateSort(this.value)">
                            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Sort by: Name</option>
                            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Sort by: Price (Low to High)</option>
                            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Sort by: Price (High to Low)</option>
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Sort by: Newest</option>
                        </select>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateSort(sortValue) {
    const url = new URL(window.location);
    url.searchParams.set('sort', sortValue);
    url.searchParams.set('page', '1'); // Reset to first page
    window.location.href = url.toString();
}

function addToCart(productId) {
    if (typeof Cart !== 'undefined') {
        Cart.addItem(productId, 1);
    } else {
        alert('Cart functionality is not available');
    }
}
</script>

<?php
$db->disconnect();
include 'components/footer.php';
?>
