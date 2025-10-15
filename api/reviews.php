<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'submit_review':
            handleSubmitReview($db);
            break;
        case 'get_reviews':
            handleGetReviews($db);
            break;
        case 'get_review_stats':
            handleGetReviewStats($db);
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleSubmitReview($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $productId = intval($_POST['product_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $reviewText = trim($_POST['review_text'] ?? '');

    // Check if user is logged in
    $isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    $userId = $isLoggedIn ? intval($_SESSION['userid'] ?? 0) : 0;

    if ($isLoggedIn && $userId) {
        // Get user details from database
        $user = $db->where('id', $userId)->getOne('users', ['first_name', 'last_name', 'email']);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        $customerName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Anonymous User';
        $customerEmail = $user['email'];
    } else {
        // Guest user - require name and email from form
        $customerName = trim($_POST['customer_name'] ?? '');
        $customerEmail = trim($_POST['customer_email'] ?? '');
        
        if (empty($customerName)) {
            throw new Exception('Customer name is required');
        }
        
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Valid email address is required');
        }
    }

    if (!$productId) {
        throw new Exception('Product ID is required');
    }

    if ($rating < 1 || $rating > 5) {
        throw new Exception('Rating must be between 1 and 5');
    }

    if (empty($reviewText)) {
        throw new Exception('Review text is required');
    }

    // Check if product exists
    $product = $db->where('id', $productId)->getOne('products', 'id, name');
    if (!$product) {
        throw new Exception('Product not found');
    }

    // Check for duplicate review - different logic for logged in vs guest users
    if ($isLoggedIn && $userId) {
        // For logged-in users, check by user_id and product_id
        $existingReview = $db->where('user_id', $userId)
                            ->where('product_id', $productId)
                            ->getOne('product_reviews');
        $duplicateMessage = 'You have already submitted a review for this product';
    } else {
        // For guest users, check by email and product_id
        $existingReview = $db->where('product_id', $productId)
                            ->where('customer_email', $customerEmail)
                            ->getOne('product_reviews');
        $duplicateMessage = 'A review has already been submitted with this email address for this product';
    }
    
    if ($existingReview) {
        throw new Exception($duplicateMessage);
    }

    // Insert review
    $reviewData = [
        'product_id' => $productId,
        'user_id' => $userId, // Add user_id for logged-in users
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'rating' => $rating,
        'title' => $title,
        'review_text' => $reviewText,
        'is_approved' => 0, // Pending approval by default
        'created_at' => date('Y-m-d H:i:s')
    ];

    $reviewId = $db->insert('product_reviews', $reviewData);

    if ($reviewId) {
        // Create notification for admin
        $notificationData = [
            'title' => 'New Product Review',
            'message' => "New review submitted for '{$product['name']}' by {$customerName}. Rating: {$rating} stars.",
            'type' => 'product_review',
            'metadata' => json_encode([
                'product_id' => $productId,
                'product_name' => $product['name'],
                'review_id' => $reviewId,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'rating' => $rating,
                'review_text' => substr($reviewText, 0, 100) . (strlen($reviewText) > 100 ? '...' : '')
            ]),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('notifications', $notificationData);
        
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your review! It will be published after approval.',
            'review_id' => $reviewId
        ]);
    } else {
        throw new Exception('Failed to submit review: ' . $db->getLastError());
    }
}

function handleGetReviews($db) {
    $productId = intval($_GET['product_id'] ?? 0);
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 10);
    $offset = ($page - 1) * $limit;

    if (!$productId) {
        throw new Exception('Product ID is required');
    }

    // Get approved reviews for the product
    $db->where('product_id', $productId);
    $db->where('is_approved', 1);
    $db->orderBy('created_at', 'DESC');
    $reviews = $db->get('product_reviews', [$offset, $limit]);

    // Get total count for pagination
    $totalCount = $db->where('product_id', $productId)
                    ->where('is_approved', 1)
                    ->getValue('product_reviews', 'COUNT(*)');

    // Format reviews for display
    $formattedReviews = [];
    if ($reviews) {
        foreach ($reviews as $review) {
            $formattedReviews[] = [
                'id' => $review['id'],
                'customer_name' => htmlspecialchars($review['customer_name']),
                'rating' => intval($review['rating']),
                'title' => htmlspecialchars($review['title'] ?? ''),
                'review_text' => htmlspecialchars($review['review_text']),
                'helpful_votes' => intval($review['helpful_votes']),
                'created_at' => date('M j, Y', strtotime($review['created_at'])),
                'created_timestamp' => strtotime($review['created_at'])
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'reviews' => $formattedReviews,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalCount / $limit),
            'total_reviews' => intval($totalCount),
            'per_page' => $limit
        ]
    ]);
}

function handleGetReviewStats($db) {
    $productId = intval($_GET['product_id'] ?? 0);

    if (!$productId) {
        throw new Exception('Product ID is required');
    }

    // Get review statistics
    $stats = [
        'total_reviews' => 0,
        'average_rating' => 0,
        'rating_distribution' => [
            '5' => 0,
            '4' => 0,
            '3' => 0,
            '2' => 0,
            '1' => 0
        ]
    ];

    // Get total approved reviews and average rating
    $db->where('product_id', $productId);
    $db->where('is_approved', 1);
    $reviewStats = $db->getOne('product_reviews', 'COUNT(*) as total, AVG(rating) as avg_rating');

    if ($reviewStats) {
        $stats['total_reviews'] = intval($reviewStats['total']);
        $stats['average_rating'] = round(floatval($reviewStats['avg_rating']), 1);
    }

    // Get rating distribution
    for ($rating = 1; $rating <= 5; $rating++) {
        $count = $db->where('product_id', $productId)
                   ->where('is_approved', 1)
                   ->where('rating', $rating)
                   ->getValue('product_reviews', 'COUNT(*)');
        $stats['rating_distribution'][$rating] = intval($count);
    }

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
}
?>
