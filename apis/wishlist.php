<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $db = new MysqliDb();
    
    // Check if user is logged in
    if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Please login to use wishlist feature'
        ]);
        exit;
    }
    
    $userId = (int)$_SESSION['userid'];
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action']) || !isset($input['product_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request data'
        ]);
        exit;
    }
    
    $action = $input['action'];
    $productId = (int)$input['product_id'];
    
    // Validate product exists
    $product = $db->where('id', $productId)->getOne('products', 'id, name');
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit;
    }
    
    // Create wishlist table if it doesn't exist
    $db->rawQuery("CREATE TABLE IF NOT EXISTS wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_wishlist (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    
    if ($action === 'add') {
        // Check if already in wishlist
        $existing = $db->where('user_id', $userId)
                      ->where('product_id', $productId)
                      ->getOne('wishlist');
        
        if ($existing) {
            echo json_encode([
                'success' => false,
                'message' => 'Product is already in your wishlist'
            ]);
            exit;
        }
        
        // Add to wishlist
        $result = $db->insert('wishlist', [
            'user_id' => $userId,
            'product_id' => $productId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            // Create notification for wishlist activity
            $notificationServicePath = __DIR__ . '/../src/NotificationService.php';
            if (file_exists($notificationServicePath)) {
                require_once $notificationServicePath;
                try {
                    $ns = new NotificationService();
                    $user = $db->where('id', $userId)->getOne('users', 'first_name, last_name');
                    $username = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'User';
                    
                    $ns->createWishlistActivityNotification(
                        $userId,
                        $username,
                        'added to wishlist',
                        $productId,
                        $product['name']
                    );
                } catch (Exception $e) { /* ignore */ }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Added to wishlist successfully!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to add to wishlist'
            ]);
        }
        
    } elseif ($action === 'remove') {
        // Remove from wishlist
        $result = $db->where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->delete('wishlist');
        
        if ($result) {
            // Create notification for wishlist activity
            $notificationServicePath = __DIR__ . '/../src/NotificationService.php';
            if (file_exists($notificationServicePath)) {
                require_once $notificationServicePath;
                try {
                    $ns = new NotificationService();
                    $user = $db->where('id', $userId)->getOne('users', 'first_name, last_name');
                    $username = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'User';
                    
                    $ns->createWishlistActivityNotification(
                        $userId,
                        $username,
                        'removed from wishlist',
                        $productId,
                        $product['name']
                    );
                } catch (Exception $e) { /* ignore */ }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Removed from wishlist successfully!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to remove from wishlist'
            ]);
        }
        
    } elseif ($action === 'get') {
        // Get user's wishlist
        $db->join('products p', 'wishlist.product_id = p.id', 'LEFT');
        $db->where('wishlist.user_id', $userId);
        $db->orderBy('wishlist.created_at', 'DESC');
        
        $wishlistItems = $db->get('wishlist', null, 'wishlist.*, p.name, p.image, p.selling_price, p.short_description');
        
        echo json_encode([
            'success' => true,
            'data' => $wishlistItems
        ]);
        
    } elseif ($action === 'check') {
        // Check if product is in wishlist
        $exists = $db->where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->getOne('wishlist');
        
        echo json_encode([
            'success' => true,
            'in_wishlist' => $exists ? true : false
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$db->disconnect();
?>
