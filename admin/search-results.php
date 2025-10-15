<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

$db = new MysqliDb();
$query = $_GET['q'] ?? '';
$results = [];

if (!empty($query)) {
    // Search in products (expanded)
    $products = [];
    try {
        $db->where("(name LIKE ? OR description LIKE ? OR sku LIKE ? OR tags LIKE ?)", ["%$query%", "%$query%", "%$query%", "%$query%"]);
        $products = $db->get('products', 10);
    } catch (Exception $e) {
        error_log("Products search error: " . $e->getMessage());
        $products = [];
    }

    // Search in categories and subcategories
    $categories = [];
    try {
        $db->where("(name LIKE ? OR description LIKE ?)", ["%$query%", "%$query%"]);
        $categories = $db->get('categories', 10);
    } catch (Exception $e) {
        error_log("Categories search error: " . $e->getMessage());
        $categories = [];
    }

    // Search in brands
    $brands = [];
    try {
        $db->where("(name LIKE ? OR description LIKE ?)", ["%$query%", "%$query%"]);
        $brands = $db->get('brands', 10);
    } catch (Exception $e) {
        error_log("Brands search error: " . $e->getMessage());
        $brands = [];
    }

    // Search in users (expanded)
    $users = [];
    try {
        $db->where("(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)", ["%$query%", "%$query%", "%$query%", "%$query%"]);
        $users = $db->get('users', 10);
    } catch (Exception $e) {
        error_log("Users search error: " . $e->getMessage());
        $users = [];
    }

    // Search in orders (expanded)
    $orders = [];
    try {
        $db->where("(id LIKE ? OR order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ?)", ["%$query%", "%$query%", "%$query%", "%$query%"]);
        $orders = $db->get('orders', 10);
    } catch (Exception $e) {
        error_log("Orders search error: " . $e->getMessage());
        // Try alternative search
        try {
            $db->where("id LIKE ?", ["%$query%"]);
            $orders = $db->get('orders', 10);
        } catch (Exception $e2) {
            $orders = [];
        }
    }

    // Search in contact messages
    $contact_messages = [];
    try {
        $db->where("(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)", ["%$query%", "%$query%", "%$query%", "%$query%"]);
        $contact_messages = $db->get('contact_messages', 10);
    } catch (Exception $e) {
        error_log("Contact messages search error: " . $e->getMessage());
        $contact_messages = [];
    }

    // Search in notifications
    $notifications = [];
    try {
        $db->where("(title LIKE ? OR message LIKE ? OR type LIKE ?)", ["%$query%", "%$query%", "%$query%"]);
        $notifications = $db->get('notifications', 10);
    } catch (Exception $e) {
        error_log("Notifications search error: " . $e->getMessage());
        $notifications = [];
    }

    // Search in team members
    $team_members = [];
    try {
        $db->where("(name LIKE ? OR position LIKE ? OR bio LIKE ? OR email LIKE ?)", ["%$query%", "%$query%", "%$query%", "%$query%"]);
        $team_members = $db->get('team_members', 10);
    } catch (Exception $e) {
        error_log("Team members search error: " . $e->getMessage());
        $team_members = [];
    }

    // Search in live chat messages
    $chat_messages = [];
    try {
        $db->where("(message LIKE ? OR user_name LIKE ?)", ["%$query%", "%$query%"]);
        $chat_messages = $db->get('chat_messages', 10);
    } catch (Exception $e) {
        error_log("Chat messages search error: " . $e->getMessage());
        $chat_messages = [];
    }

    // Search in product reviews
    $reviews = [];
    try {
        $db->where("(customer_name LIKE ? OR customer_email LIKE ? OR title LIKE ? OR review_text LIKE ?)", ["%$query%", "%$query%", "%$query%", "%$query%"]);
        $reviews = $db->get('product_reviews', 10);
    } catch (Exception $e) {
        error_log("Reviews search error: " . $e->getMessage());
        $reviews = [];
    }

    // Search in discounts
    $discounts = [];
    try {
        $db->where("(name LIKE ? OR description LIKE ?)", ["%$query%", "%$query%"]);
        $discounts = $db->get('product_discounts', 10);
    } catch (Exception $e) {
        error_log("Discounts search error: " . $e->getMessage());
        $discounts = [];
    }

    // Search in reports (if table exists)
    $reports = [];
    try {
        $db->where("(title LIKE ? OR content LIKE ? OR type LIKE ?)", ["%$query%", "%$query%", "%$query%"]);
        $reports = $db->get('reports', 10);
    } catch (Exception $e) {
        // Reports table might not exist, ignore error
        $reports = [];
    }

    $results = [
        'products' => $products ?: [],
        'categories' => $categories ?: [],
        'brands' => $brands ?: [],
        'users' => $users ?: [],
        'orders' => $orders ?: [],
        'contact_messages' => $contact_messages ?: [],
        'notifications' => $notifications ?: [],
        'team_members' => $team_members ?: [],
        'chat_messages' => $chat_messages ?: [],
        'reviews' => $reviews ?: [],
        'discounts' => $discounts ?: [],
        'reports' => $reports ?: []
    ];
}
?>

<?php require __DIR__ . '/components/header.php'; ?>

<style>
.search-result-item {
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
}
.search-result-item:hover {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transform: translateY(-1px);
}
.search-category {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
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
                    <h1 class="mt-4">Search Results</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Search Results</li>
                    </ol>

                    <?php if (!empty($query)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-search me-2"></i>
                            Search results for: <strong>"<?= htmlspecialchars($query) ?>"</strong>
                        </div>

                        <!-- Products Results -->
                        <?php if (!empty($results['products'])): ?>
                            <div class="search-category">
                                <h5 class="mb-0"><i class="fas fa-box me-2"></i>Products (<?= count($results['products']) ?>)</h5>
                            </div>
                            <?php foreach ($results['products'] as $product): ?>
                                <div class="search-result-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="product-view.php?id=<?= $product['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($product['name']) ?>
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-1">SKU: <?= htmlspecialchars($product['sku'] ?? 'N/A') ?></p>
                                            <p class="mb-0"><?= htmlspecialchars(substr($product['description'] ?? '', 0, 100)) ?>...</p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?= $product['status'] == 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($product['status']) ?>
                                            </span>
                                            <div class="mt-1">
                                                <small class="text-muted">Stock: <?= $product['stock_quantity'] ?? 0 ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Categories Results -->
                        <?php if (!empty($results['categories'])): ?>
                            <div class="search-category" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);">
                                <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Categories (<?= count($results['categories']) ?>)</h5>
                            </div>
                            <?php foreach ($results['categories'] as $category): ?>
                                <div class="search-result-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="category-view.php?id=<?= $category['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-0">Slug: <?= htmlspecialchars($category['slug'] ?? 'N/A') ?></p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?= $category['is_active'] ? 'success' : 'secondary' ?>">
                                                <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Brands Results -->
                        <?php if (!empty($results['brands'])): ?>
                            <div class="search-category" style="background: linear-gradient(135deg, #f6c23e 0%, #d4a574 100%);">
                                <h5 class="mb-0"><i class="fas fa-copyright me-2"></i>Brands (<?= count($results['brands']) ?>)</h5>
                            </div>
                            <?php foreach ($results['brands'] as $brand): ?>
                                <div class="search-result-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="brand-view.php?id=<?= $brand['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($brand['name']) ?>
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-0">Slug: <?= htmlspecialchars($brand['slug'] ?? 'N/A') ?></p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?= $brand['is_active'] ? 'success' : 'secondary' ?>">
                                                <?= $brand['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Users Results -->
                        <?php if (!empty($results['users'])): ?>
                            <div class="search-category" style="background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);">
                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Users (<?= count($results['users']) ?>)</h5>
                            </div>
                            <?php foreach ($results['users'] as $user): ?>
                                <div class="search-result-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="user-view.php?id=<?= $user['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-0"><?= htmlspecialchars($user['email']) ?></p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?= $user['is_active'] ? 'success' : 'danger' ?>">
                                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                            <div class="mt-1">
                                                <small class="text-muted"><?= ucfirst($user['role']) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Orders Results -->
                        <?php if (!empty($results['orders'])): ?>
                            <div class="search-category" style="background: linear-gradient(135deg, #e74a3b 0%, #b52d24 100%);">
                                <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Orders (<?= count($results['orders']) ?>)</h5>
                            </div>
                            <?php foreach ($results['orders'] as $order): ?>
                                <div class="search-result-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="order-details.php?id=<?= $order['id'] ?>" class="text-decoration-none">
                                                    Order #<?= htmlspecialchars($order['order_number'] ?? $order['id']) ?>
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-0">
                                                Customer: <?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?> 
                                                (<?= htmlspecialchars($order['customer_email'] ?? 'N/A') ?>)
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?= $order['status'] == 'completed' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'danger') ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                            <div class="mt-1">
                                                <small class="text-muted">৳<?= number_format($order['total_amount'] ?? 0, 2) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Contact Messages Results -->
                        <?php if (!empty($results['contact_messages'])): ?>
                            <div class="search-category" style="background: linear-gradient(135deg, #6610f2 0%, #4c0aa4 100%);">
                                <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Contact Messages (<?= count($results['contact_messages']) ?>)</h5>
                            </div>
                            <?php foreach ($results['contact_messages'] as $message): ?>
                                <div class="search-result-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="contact-view.php?id=<?= $message['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($message['subject']) ?>
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-1">From: <?= htmlspecialchars($message['name']) ?> (<?= htmlspecialchars($message['email']) ?>)</p>
                                            <p class="mb-0"><?= htmlspecialchars(substr($message['message'], 0, 100)) ?>...</p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?= $message['priority'] == 'high' ? 'danger' : ($message['priority'] == 'low' ? 'secondary' : 'warning') ?>">
                                                <?= ucfirst($message['priority'] ?? 'normal') ?>
                                            </span>
                                            <div class="mt-1">
                                                <small class="text-muted"><?= date('M d, H:i', strtotime($message['created_at'] ?? 'now')) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Notifications Results -->
                        <?php if (!empty($results['notifications'])): ?>
                            <div class="search-category" style="background: linear-gradient(135deg, #fd7e14 0%, #c9640a 100%);">
                                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notifications (<?= count($results['notifications']) ?>)</h5>
                            </div>
                            <?php foreach ($results['notifications'] as $notification): ?>
                                <div class="search-result-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <?= htmlspecialchars($notification['title']) ?>
                                            </h6>
                                            <p class="text-muted mb-0">Type: <?= htmlspecialchars($notification['type']) ?></p>
                                            <p class="mb-0"><?= htmlspecialchars(substr($notification['message'], 0, 100)) ?>...</p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?= $notification['is_read'] ? 'success' : 'warning' ?>">
                                                <?= $notification['is_read'] ? 'Read' : 'Unread' ?>
                                            </span>
                                            <div class="mt-1">
                                                <small class="text-muted"><?= date('M d, H:i', strtotime($notification['created_at'])) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Team Members Results -->
                        <?php if (!empty($results['team_members'])): ?>
                            <div class="search-category" style="background: linear-gradient(135deg, #20c997 0%, #1a9b7a 100%);">
                                <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Team Members (<?= count($results['team_members']) ?>)</h5>
                            </div>
                            <?php foreach ($results['team_members'] as $member): ?>
                                <div class="search-result-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="team-view.php?id=<?= $member['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($member['name']) ?>
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-0">Position: <?= htmlspecialchars($member['position']) ?></p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?= $member['is_active'] ? 'success' : 'secondary' ?>">
                                                <?= $member['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Chat Messages Results -->
                        <?php if (!empty($results['chat_messages'])): ?>
                            <div class="search-category" style="background: linear-gradient(135deg, #6f42c1 0%, #563d7c 100%);">
                                <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Chat Messages (<?= count($results['chat_messages']) ?>)</h5>
                            </div>
                            <?php foreach ($results['chat_messages'] as $chat): ?>
                                <div class="search-result-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                From: <?= htmlspecialchars($chat['user_name']) ?>
                                            </h6>
                                            <p class="mb-0"><?= htmlspecialchars(substr($chat['message'], 0, 100)) ?>...</p>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted"><?= date('M d, H:i', strtotime($chat['created_at'] ?? 'now')) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Reviews Results -->
                        <?php if (!empty($results['reviews'])): ?>
                            <div class="search-category" style="background: linear-gradient(135deg, #dc3545 0%, #a02622 100%);">
                                <h5 class="mb-0"><i class="fas fa-star me-2"></i>Product Reviews (<?= count($results['reviews']) ?>)</h5>
                            </div>
                            <?php foreach ($results['reviews'] as $review): ?>
                                <div class="search-result-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="review-view.php?id=<?= $review['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($review['title'] ?? 'Review') ?>
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-1">By: <?= htmlspecialchars($review['customer_name']) ?> (<?= htmlspecialchars($review['customer_email']) ?>)</p>
                                            <p class="mb-0"><?= htmlspecialchars(substr($review['review_text'], 0, 100)) ?>...</p>
                                        </div>
                                        <div class="text-end">
                                            <div class="mb-1">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="badge bg-<?= $review['is_approved'] ? 'success' : 'warning' ?>">
                                                <?= $review['is_approved'] ? 'Approved' : 'Pending' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Discounts Results -->
                        <?php if (!empty($results['discounts'])): ?>
                            <div class="search-category" style="background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);">
                                <h5 class="mb-0"><i class="fas fa-percent me-2"></i>Discounts (<?= count($results['discounts']) ?>)</h5>
                            </div>
                            <?php foreach ($results['discounts'] as $discount): ?>
                                <div class="search-result-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="discount-view.php?id=<?= $discount['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($discount['name']) ?>
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-0">Type: <?= htmlspecialchars($discount['discount_type']) ?></p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?= $discount['is_active'] ? 'success' : 'secondary' ?>">
                                                <?= $discount['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                            <div class="mt-1">
                                                <small class="text-muted">Value: <?= $discount['discount_value'] ?><?= $discount['discount_type'] == 'percentage' ? '%' : '৳' ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Reports Results -->
                        <?php if (!empty($results['reports'])): ?>
                            <div class="search-category" style="background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);">
                                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Reports (<?= count($results['reports']) ?>)</h5>
                            </div>
                            <?php foreach ($results['reports'] as $report): ?>
                                <div class="search-result-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="report-view.php?id=<?= $report['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($report['title']) ?>
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-0">Type: <?= htmlspecialchars($report['type']) ?></p>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted"><?= date('M d, Y', strtotime($report['created_at'] ?? 'now')) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php
                        $totalResults = array_sum(array_map('count', $results));
                        if ($totalResults == 0): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No results found for "<?= htmlspecialchars($query) ?>". Try a different search term.
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Please enter a search term to search across all system areas including products, categories, brands, users, orders, contact messages, notifications, team members, chat messages, reviews, discounts, and reports.
                        </div>
                    <?php endif; ?>

                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>
</body>
</html>

<?php $db->disconnect(); ?>
