<?php
/**
 * Cart and Wishlist Tracking API
 * This script tracks user activities related to cart and wishlist
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';
require_once __DIR__ . '/../src/NotificationService.php';

header('Content-Type: application/json');

// Initialize database and notification service
$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
$notificationService = new NotificationService();

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add_to_cart':
            handleAddToCart($db, $notificationService);
            break;
            
        case 'add_to_wishlist':
            handleAddToWishlist($db, $notificationService);
            break;
            
        case 'remove_from_cart':
            handleRemoveFromCart($db, $notificationService);
            break;
            
        case 'remove_from_wishlist':
            handleRemoveFromWishlist($db, $notificationService);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log('Cart/Wishlist Tracker Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

function handleAddToCart($db, $notificationService) {
    $productId = intval($_POST['product_id'] ?? 0);
    $productName = $_POST['product_name'] ?? '';
    $userId = $_SESSION['userid'] ?? null;
    $username = $_SESSION['username'] ?? 'Guest';
    
    if (!$productId || !$productName) {
        echo json_encode(['success' => false, 'message' => 'Product information missing']);
        return;
    }
    
    // Create cart activity notification
    $notificationService->createCartActivityNotification(
        $userId,
        $username,
        'added product to cart',
        $productId,
        $productName
    );
    
    echo json_encode(['success' => true, 'message' => 'Product added to cart']);
}

function handleAddToWishlist($db, $notificationService) {
    $productId = intval($_POST['product_id'] ?? 0);
    $productName = $_POST['product_name'] ?? '';
    $userId = $_SESSION['userid'] ?? null;
    $username = $_SESSION['username'] ?? 'Guest';
    
    if (!$productId || !$productName) {
        echo json_encode(['success' => false, 'message' => 'Product information missing']);
        return;
    }
    
    // Create wishlist activity notification
    $notificationService->createWishlistActivityNotification(
        $userId,
        $username,
        'added product to wishlist',
        $productId,
        $productName
    );
    
    echo json_encode(['success' => true, 'message' => 'Product added to wishlist']);
}

function handleRemoveFromCart($db, $notificationService) {
    $productId = intval($_POST['product_id'] ?? 0);
    $productName = $_POST['product_name'] ?? '';
    $userId = $_SESSION['userid'] ?? null;
    $username = $_SESSION['username'] ?? 'Guest';
    
    if (!$productId || !$productName) {
        echo json_encode(['success' => false, 'message' => 'Product information missing']);
        return;
    }
    
    // Create cart activity notification
    $notificationService->createCartActivityNotification(
        $userId,
        $username,
        'removed product from cart',
        $productId,
        $productName
    );
    
    echo json_encode(['success' => true, 'message' => 'Product removed from cart']);
}

function handleRemoveFromWishlist($db, $notificationService) {
    $productId = intval($_POST['product_id'] ?? 0);
    $productName = $_POST['product_name'] ?? '';
    $userId = $_SESSION['userid'] ?? null;
    $username = $_SESSION['username'] ?? 'Guest';
    
    if (!$productId || !$productName) {
        echo json_encode(['success' => false, 'message' => 'Product information missing']);
        return;
    }
    
    // Create wishlist activity notification
    $notificationService->createWishlistActivityNotification(
        $userId,
        $username,
        'removed product from wishlist',
        $productId,
        $productName
    );
    
    echo json_encode(['success' => true, 'message' => 'Product removed from wishlist']);
}
?>