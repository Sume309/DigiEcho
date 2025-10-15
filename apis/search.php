<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone to Bangladesh Standard Time
date_default_timezone_set('Asia/Dhaka');

require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection using settings
$db = new MysqliDb(
    settings()['hostname'],
    settings()['user'],
    settings()['password'],
    settings()['database']
);

$query = $_GET['q'] ?? '';
$limit = intval($_GET['limit'] ?? 10);

// Minimum query length
if (strlen(trim($query)) < 2) {
    echo json_encode([
        'success' => false,
        'message' => 'Search query must be at least 2 characters long',
        'data' => []
    ]);
    exit;
}

try {
    $results = [
        'products' => [],
        'categories' => [],
        'brands' => [],
        'users' => [],
        'orders' => [],
        'contact_messages' => [],
        'notifications' => [],
        'team_members' => [],
        'chat_messages' => [],
        'reviews' => [],
        'discounts' => [],
        'reports' => []
    ];

    // Search Team Members
    $products = $db->get('products', $limit, 'id, name, sku, selling_price, image, stock_quantity');

    foreach ($products as $product) {
        $results['products'][] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'sku' => $product['sku'],
            'price' => floatval($product['selling_price']),
            'image' => $product['image'],
            'stock' => intval($product['stock_quantity']),
            'type' => 'product',
            'url' => 'product-details.php?id=' . $product['id']
        ];
    }

    // Search Categories
    $db->where("name LIKE '%{$query}%' OR description LIKE '%{$query}%'");
    $db->where('is_active', 1);
    $db->orderBy('name', 'ASC');
    $categories = $db->get('categories', 5, 'id, name, slug, image');

    foreach ($categories as $category) {
        $results['categories'][] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'slug' => $category['slug'],
            'image' => $category['image'],
            'type' => 'category',
            'url' => 'index.php?category=' . $category['id']
        ];
    }

    // Search Brands
    $db->where("name LIKE '%{$query}%' OR description LIKE '%{$query}%'");
    $db->where('is_active', 1);
    $db->orderBy('name', 'ASC');
    $brands = $db->get('brands', 5, 'id, name, slug, logo');

    foreach ($brands as $brand) {
        $results['brands'][] = [
            'id' => $brand['id'],
            'name' => $brand['name'],
            'slug' => $brand['slug'],
            'logo' => $brand['logo'],
            'type' => 'brand',
            'url' => 'brands.php?brand=' . $brand['id']
        ];
    }

    // Search Users (admin only - limited fields)
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $db->where("first_name LIKE '%{$query}%' OR last_name LIKE '%{$query}%' OR email LIKE '%{$query}%'");
        $db->orderBy('first_name', 'ASC');
        $users_result = $db->get('users', 5, 'id, first_name, last_name, email, role');
        $users = is_array($users_result) ? $users_result : [];

        foreach ($users as $user) {
            $results['users'][] = [
                'id' => $user['id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'type' => 'user',
                'url' => 'admin/user-view.php?id=' . $user['id']
            ];
        }
    }

    // Search Orders (admin only)
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $db->where("order_number LIKE '%{$query}%' OR customer_name LIKE '%{$query}%' OR customer_email LIKE '%{$query}%'");
        $db->orderBy('created_at', 'DESC');
        $orders_result = $db->get('orders', 5, 'id, order_number, customer_name, customer_email, total_amount, status');
        $orders = is_array($orders_result) ? $orders_result : [];

        foreach ($orders as $order) {
            $results['orders'][] = [
                'id' => $order['id'],
                'order_number' => $order['order_number'] ?? $order['id'],
                'customer_name' => $order['customer_name'],
                'customer_email' => $order['customer_email'],
                'total_amount' => floatval($order['total_amount']),
                'status' => $order['status'],
                'type' => 'order',
                'url' => 'admin/order-details.php?id=' . $order['id']
            ];
        }
    }

    // Search Contact Messages (admin only)
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $db->where("name LIKE '%{$query}%' OR email LIKE '%{$query}%' OR subject LIKE '%{$query}%' OR message LIKE '%{$query}%'");
        $db->orderBy('created_at', 'DESC');
        $contact_messages_result = $db->get('contact_messages', 5, 'id, name, email, subject, message, priority, created_at');
        $contact_messages = is_array($contact_messages_result) ? $contact_messages_result : [];

        foreach ($contact_messages as $message) {
            $results['contact_messages'][] = [
                'id' => $message['id'],
                'name' => $message['name'],
                'email' => $message['email'],
                'subject' => $message['subject'],
                'message' => substr($message['message'], 0, 100),
                'priority' => $message['priority'],
                'type' => 'contact_message',
                'url' => 'admin/contact-view.php?id=' . $message['id']
            ];
        }
    }

    // Search Notifications (admin only)
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $db->where("title LIKE '%{$query}%' OR message LIKE '%{$query}%' OR type LIKE '%{$query}%'");
        $db->orderBy('created_at', 'DESC');
        $notifications_result = $db->get('notifications', 5, 'id, title, message, type, is_read, created_at');
        $notifications = is_array($notifications_result) ? $notifications_result : [];

        foreach ($notifications as $notification) {
            $results['notifications'][] = [
                'id' => $notification['id'],
                'title' => $notification['title'],
                'message' => substr($notification['message'], 0, 100),
                'type' => $notification['type'],
                'is_read' => (bool)$notification['is_read'],
                'url' => 'admin/notification-management.php'
            ];
        }
    }

    // Search Team Members
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $db->where("name LIKE '%{$query}%' OR position LIKE '%{$query}%' OR bio LIKE '%{$query}%' OR email LIKE '%{$query}%'");
        $db->where('is_active', 1);
        $db->orderBy('name', 'ASC');
        $team_members_result = $db->get('team_members', 5, 'id, name, position, image, email');
        $team_members = is_array($team_members_result) ? $team_members_result : [];

        foreach ($team_members as $member) {
            $results['team_members'][] = [
                'id' => $member['id'],
                'name' => $member['name'],
                'position' => $member['position'],
                'image' => $member['image'],
                'email' => $member['email'],
                'type' => 'team_member',
                'url' => 'admin/team-all.php'
            ];
        }
    }

    // Search Chat Messages
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $db->where("message LIKE '%{$query}%' OR username LIKE '%{$query}%'");
        $db->orderBy('created_at', 'DESC');
        $chat_messages_result = $db->get('chat_messages', 5, 'id, message, username, created_at');
        $chat_messages = is_array($chat_messages_result) ? $chat_messages_result : [];

        foreach ($chat_messages as $message) {
            $results['chat_messages'][] = [
                'id' => $message['id'],
                'message' => substr($message['message'], 0, 100),
                'username' => $message['username'],
                'created_at' => $message['created_at'],
                'type' => 'chat_message',
                'url' => 'admin/live-chat.php'
            ];
        }
    }

    // Search Product Reviews
    $db->where("title LIKE '%{$query}%' OR review_text LIKE '%{$query}%' OR customer_name LIKE '%{$query}%' OR customer_email LIKE '%{$query}%'");
    $db->where('is_approved', 1);
    $db->orderBy('created_at', 'DESC');
    $reviews_result = $db->get('product_reviews', 5, 'id, product_id, customer_name, customer_email, title, review_text, rating, created_at');
    $reviews = is_array($reviews_result) ? $reviews_result : [];

    foreach ($reviews as $review) {
        $results['reviews'][] = [
            'id' => $review['id'],
            'product_id' => $review['product_id'],
            'customer_name' => $review['customer_name'],
            'customer_email' => $review['customer_email'],
            'title' => $review['title'],
            'review_text' => substr($review['review_text'], 0, 100),
            'rating' => intval($review['rating']),
            'type' => 'review',
            'url' => 'admin/reviews-management.php'
        ];
    }

    // Search Product Discounts
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $db->where("name LIKE '%{$query}%' OR description LIKE '%{$query}%'");
        $db->orderBy('created_at', 'DESC');
        $discounts_result = $db->get('product_discounts', 5, 'id, name, description, discount_type, discount_value, is_active');
        $discounts = is_array($discounts_result) ? $discounts_result : [];

        foreach ($discounts as $discount) {
            $results['discounts'][] = [
                'id' => $discount['id'],
                'name' => $discount['name'],
                'description' => substr($discount['description'], 0, 100),
                'discount_type' => $discount['discount_type'],
                'discount_value' => floatval($discount['discount_value']),
                'is_active' => (bool)$discount['is_active'],
                'type' => 'discount',
                'url' => 'admin/discounts-management.php'
            ];
        }
    }

    // Search Reports
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $db->where("title LIKE '%{$query}%' OR content LIKE '%{$query}%' OR type LIKE '%{$query}%'");
        $db->orderBy('created_at', 'DESC');
        $reports_result = $db->get('reports', 5, 'id, title, content, type, created_at');
        $reports = is_array($reports_result) ? $reports_result : [];

        foreach ($reports as $report) {
            $results['reports'][] = [
                'id' => $report['id'],
                'title' => $report['title'],
                'content' => substr($report['content'], 0, 150),
                'type' => $report['type'],
                'created_at' => $report['created_at'],
                'type' => 'report',
                'url' => 'admin/reports.php'
            ];
        }
    }

    // Calculate total results
    $totalResults = (is_array($results['products']) ? count($results['products']) : 0) +
                   (is_array($results['categories']) ? count($results['categories']) : 0) +
                   (is_array($results['brands']) ? count($results['brands']) : 0) +
                   (is_array($results['users']) ? count($results['users']) : 0) +
                   (is_array($results['orders']) ? count($results['orders']) : 0) +
                   (is_array($results['contact_messages']) ? count($results['contact_messages']) : 0) +
                   (is_array($results['notifications']) ? count($results['notifications']) : 0) +
                   (is_array($results['team_members']) ? count($results['team_members']) : 0) +
                   (is_array($results['chat_messages']) ? count($results['chat_messages']) : 0) +
                   (is_array($results['reviews']) ? count($results['reviews']) : 0) +
                   (is_array($results['discounts']) ? count($results['discounts']) : 0) +
                   (is_array($results['reports']) ? count($results['reports']) : 0);

    echo json_encode([
        'success' => true,
        'query' => $query,
        'total_results' => $totalResults,
        'data' => $results
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Search failed: ' . $e->getMessage(),
        'data' => []
    ]);
}

$db->disconnect();
?>
