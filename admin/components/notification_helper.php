<?php
/**
 * Notification Helper Functions
 * This file contains helper functions to easily create notifications for various user activities
 */

require_once __DIR__ . '/../../src/NotificationService.php';

/**
 * Initialize notification service
 */
function getNotificationService() {
    static $notificationService = null;
    if ($notificationService === null) {
        $notificationService = new NotificationService();
    }
    return $notificationService;
}

/**
 * Create a user activity notification
 */
function notifyUserActivity($userId, $username, $activity, $details = null) {
    $notificationService = getNotificationService();
    
    $message = $activity;
    if ($details) {
        $message .= ": " . $details;
    }
    
    $metadata = [
        'user_id' => $userId,
        'username' => $username,
        'activity' => $activity
    ];
    
    if ($details) {
        $metadata['details'] = $details;
    }
    
    return $notificationService->createNotification(
        'User Activity',
        $message,
        'user_activity',
        $metadata
    );
}

/**
 * Create a product activity notification
 */
function notifyProductActivity($productId, $productName, $activity, $userId = null, $username = null) {
    $notificationService = getNotificationService();
    
    $message = "Product '{$productName}' {$activity}";
    
    $metadata = [
        'product_id' => $productId,
        'product_name' => $productName,
        'activity' => $activity
    ];
    
    if ($userId) {
        $metadata['user_id'] = $userId;
    }
    
    if ($username) {
        $metadata['username'] = $username;
    }
    
    return $notificationService->createNotification(
        'Product Activity',
        $message,
        'system',
        $metadata
    );
}

/**
 * Create an order activity notification
 */
function notifyOrderActivity($orderId, $activity, $details = null, $userId = null, $username = null) {
    $notificationService = getNotificationService();
    
    $message = "Order #{$orderId} {$activity}";
    if ($details) {
        $message .= ": " . $details;
    }
    
    $metadata = [
        'order_id' => $orderId,
        'activity' => $activity
    ];
    
    if ($details) {
        $metadata['details'] = $details;
    }
    
    if ($userId) {
        $metadata['user_id'] = $userId;
    }
    
    if ($username) {
        $metadata['username'] = $username;
    }
    
    return $notificationService->createNotification(
        'Order Activity',
        $message,
        'order_update',
        $metadata
    );
}

/**
 * Create a system notification
 */
function notifySystem($title, $message, $type = 'system', $metadata = null) {
    $notificationService = getNotificationService();
    return $notificationService->createNotification($title, $message, $type, $metadata);
}

/**
 * Create a user management notification
 */
function notifyUserManagement($targetUserId, $targetUsername, $action, $adminUserId, $adminUsername) {
    $notificationService = getNotificationService();
    
    $message = "User '{$targetUsername}' has been {$action} by {$adminUsername}";
    
    $metadata = [
        'target_user_id' => $targetUserId,
        'target_username' => $targetUsername,
        'action' => $action,
        'admin_user_id' => $adminUserId,
        'admin_username' => $adminUsername
    ];
    
    return $notificationService->createNotification(
        'User Management',
        $message,
        'user_activity',
        $metadata
    );
}

/**
 * Create a category management notification
 */
function notifyCategoryActivity($categoryId, $categoryName, $activity, $userId = null, $username = null) {
    $notificationService = getNotificationService();
    
    $message = "Category '{$categoryName}' {$activity}";
    
    $metadata = [
        'category_id' => $categoryId,
        'category_name' => $categoryName,
        'activity' => $activity
    ];
    
    if ($userId) {
        $metadata['user_id'] = $userId;
    }
    
    if ($username) {
        $metadata['username'] = $username;
    }
    
    return $notificationService->createNotification(
        'Category Activity',
        $message,
        'system',
        $metadata
    );
}

/**
 * Create a brand management notification
 */
function notifyBrandActivity($brandId, $brandName, $activity, $userId = null, $username = null) {
    $notificationService = getNotificationService();
    
    $message = "Brand '{$brandName}' {$activity}";
    
    $metadata = [
        'brand_id' => $brandId,
        'brand_name' => $brandName,
        'activity' => $activity
    ];
    
    if ($userId) {
        $metadata['user_id'] = $userId;
    }
    
    if ($username) {
        $metadata['username'] = $username;
    }
    
    return $notificationService->createNotification(
        'Brand Activity',
        $message,
        'system',
        $metadata
    );
}
?>