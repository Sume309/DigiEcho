<?php
/**
 * Script to automatically generate system notifications
 * This script should be run periodically (e.g., via cron job) to check for events
 * and generate appropriate notifications
 */

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';
require_once __DIR__ . '/../src/NotificationService.php';

try {
    // Initialize database and notification service
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    $notificationService = new NotificationService();
    
    echo "Generating system notifications...\n";
    
    // Generate low stock notifications
    $lowStockCount = generateLowStockNotifications($db, $notificationService);
    echo "Generated $lowStockCount low stock notifications\n";
    
    // Generate new order notifications (for orders in the last hour)
    $newOrderCount = generateNewOrderNotifications($db, $notificationService);
    echo "Generated $newOrderCount new order notifications\n";
    
    // Generate pending order notifications (for orders older than 24 hours)
    $pendingOrderCount = generatePendingOrderNotifications($db, $notificationService);
    echo "Generated $pendingOrderCount pending order notifications\n";
    
    // Generate out of stock notifications
    $outOfStockCount = generateOutOfStockNotifications($db, $notificationService);
    echo "Generated $outOfStockCount out of stock notifications\n";
    
    // Generate new user notifications (for users registered in the last day)
    $newUserCount = generateNewUserNotifications($db, $notificationService);
    echo "Generated $newUserCount new user notifications\n";
    
    echo "Notifications generated successfully\n";
    
} catch (Exception $e) {
    error_log('Generate Notifications Script Error: ' . $e->getMessage());
    echo "Error generating notifications: " . $e->getMessage() . "\n";
}

/**
 * Generate low stock notifications
 */
function generateLowStockNotifications($db, $notificationService) {
    $count = 0;
    
    // Get products with low stock (stock <= min_stock_level but > 0)
    $lowStockProducts = $db->get('products', null, 'id, name, stock_quantity, min_stock_level', 'stock_quantity <= min_stock_level AND stock_quantity > 0');
    
    foreach ($lowStockProducts as $product) {
        // Check if we already have a recent notification for this product (within last 24 hours)
        $db->where('type', 'low_stock');
        $db->where('created_at', date('Y-m-d H:i:s', strtotime('-24 hours')), '>=');
        $db->where('metadata[like]', '%"product_id":"' . $product['id'] . '"%');
        $existingNotification = $db->getOne('notifications');
        
        if (!$existingNotification) {
            // Create low stock notification
            $notificationService->createLowStockNotification(
                $product['id'],
                $product['name'],
                $product['stock_quantity'],
                $product['min_stock_level']
            );
            $count++;
        }
    }
    
    return $count;
}

/**
 * Generate new order notifications
 */
function generateNewOrderNotifications($db, $notificationService) {
    $count = 0;
    
    // Get orders from the last hour that don't have notifications yet
    $db->where('created_at', date('Y-m-d H:i:s', strtotime('-1 hour')), '>=');
    $recentOrders = $db->get('orders', null, 'id, order_number, user_id, total_amount, created_at');
    
    foreach ($recentOrders as $order) {
        // Check if we already have a notification for this order
        $db->where('type', 'new_order');
        $db->where('metadata[like]', '%"order_id":"' . $order['id'] . '"%');
        $existingNotification = $db->getOne('notifications');
        
        if (!$existingNotification) {
            // Create new order notification
            $notificationService->createOrderNotification(
                $order['id'],
                $order['user_id'],
                $order['total_amount'],
                $order['order_number']
            );
            $count++;
        }
    }
    
    return $count;
}

/**
 * Generate pending order notifications
 */
function generatePendingOrderNotifications($db, $notificationService) {
    $count = 0;
    
    // Get pending orders older than 24 hours that don't have notifications yet
    $db->where('status', 'pending');
    $db->where('created_at', date('Y-m-d H:i:s', strtotime('-24 hours')), '<=');
    $pendingOrders = $db->get('orders', null, 'id, order_number, user_id, total_amount, created_at');
    
    foreach ($pendingOrders as $order) {
        // Check if we already have a recent notification for this order (within last 24 hours)
        $db->where('type', 'order_update');
        $db->where('created_at', date('Y-m-d H:i:s', strtotime('-24 hours')), '>=');
        $db->where('metadata[like]', '%"order_id":"' . $order['id'] . '"%');
        $existingNotification = $db->getOne('notifications');
        
        if (!$existingNotification) {
            // Create pending order notification
            $notificationService->createOrderUpdateNotification(
                $order['id'],
                'pending',
                'Order is still pending for more than 24 hours',
                $order['order_number']
            );
            $count++;
        }
    }
    
    return $count;
}

/**
 * Generate out of stock notifications
 */
function generateOutOfStockNotifications($db, $notificationService) {
    $count = 0;
    
    // Get products that are out of stock
    $outOfStockProducts = $db->get('products', null, 'id, name', 'stock_quantity = 0');
    
    foreach ($outOfStockProducts as $product) {
        // Check if we already have a recent notification for this product (within last 24 hours)
        $db->where('type', 'low_stock');
        $db->where('created_at', date('Y-m-d H:i:s', strtotime('-24 hours')), '>=');
        $db->where('metadata[like]', '%"product_id":"' . $product['id'] . '"%');
        $existingNotification = $db->getOne('notifications');
        
        if (!$existingNotification) {
            // Create out of stock notification
            $notificationService->createLowStockNotification(
                $product['id'],
                $product['name'],
                0,
                1
            );
            $count++;
        }
    }
    
    return $count;
}

/**
 * Generate new user notifications
 */
function generateNewUserNotifications($db, $notificationService) {
    $count = 0;
    
    // Get users registered in the last day
    $db->where('created_at', date('Y-m-d H:i:s', strtotime('-1 day')), '>=');
    $newUsers = $db->get('users', null, 'id, first_name, last_name, email, created_at');
    
    foreach ($newUsers as $user) {
        // Check if we already have a notification for this user
        $db->where('type', 'user_registration');
        $db->where('metadata[like]', '%"user_id":"' . $user['id'] . '"%');
        $existingNotification = $db->getOne('notifications');
        
        if (!$existingNotification) {
            // Create new user notification
            $fullName = trim($user['first_name'] . ' ' . $user['last_name']);
            $notificationService->createNewUserNotification(
                $user['id'],
                $fullName ?: 'User',
                $user['email']
            );
            $count++;
        }
    }
    
    return $count;
}
?>