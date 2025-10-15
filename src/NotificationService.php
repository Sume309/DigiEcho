<?php

require_once __DIR__ . '/db/MysqliDb.php';
require_once __DIR__ . '/settings.php';

class NotificationService {
    private $db;
    
    public function __construct() {
        $this->db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    }
    
    /**
     * Create a new notification
     */
    public function createNotification($title, $message, $type = 'system', $metadata = null) {
        try {
            $data = [
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'metadata' => $metadata ? (is_string($metadata) ? $metadata : json_encode($metadata)) : null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $id = $this->db->insert('notifications', $data);
            return $id;
        } catch (Exception $e) {
            error_log('NotificationService Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new order notification
     */
    public function createOrderNotification($orderId, $customerId, $totalAmount, $orderNumber = null) {
        $title = "New Order Received";
        $message = "Order #{$orderNumber} has been placed for BDT {$totalAmount}";
        $metadata = [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'customer_id' => $customerId,
            'amount' => $totalAmount
        ];
        
        return $this->createNotification($title, $message, 'new_order', $metadata);
    }
    
    /**
     * Create a low stock notification
     */
    public function createLowStockNotification($productId, $productName, $stockQuantity, $minStockLevel) {
        $title = "Low Stock Alert";
        $message = "{$productName} is running low (Stock: {$stockQuantity}, Min: {$minStockLevel})";
        $metadata = [
            'product_id' => $productId,
            'product_name' => $productName,
            'stock_quantity' => $stockQuantity,
            'min_stock_level' => $minStockLevel
        ];
        
        return $this->createNotification($title, $message, 'low_stock', $metadata);
    }
    
    /**
     * Create a user activity notification
     */
    public function createUserActivityNotification($userId, $username, $activity, $details = null) {
        $title = "User Activity";
        $message = "{$username} {$activity}";
        $metadata = [
            'user_id' => $userId,
            'username' => $username,
            'activity' => $activity
        ];
        
        if ($details) {
            $metadata['details'] = $details;
        }
        
        return $this->createNotification($title, $message, 'user_activity', $metadata);
    }
    
    /**
     * Create an order update notification
     */
    public function createOrderUpdateNotification($orderId, $status, $details = '', $orderNumber = null) {
        $title = "Order Status Updated";
        $message = "Order #{$orderNumber} status changed to {$status}" . ($details ? ": {$details}" : "");
        $metadata = [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'status' => $status,
            'details' => $details
        ];
        
        return $this->createNotification($title, $message, 'order_update', $metadata);
    }
    
    /**
     * Create a payment notification
     */
    public function createPaymentNotification($orderId, $amount, $paymentMethod, $status, $orderNumber = null) {
        $title = "Payment " . ucfirst($status);
        $message = "Payment of BDT {$amount} via {$paymentMethod} for Order #{$orderNumber} has been {$status}";
        $metadata = [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => $status
        ];
        
        return $this->createNotification($title, $message, 'payment', $metadata);
    }

    /**
     * High-value order alert
     */
    public function createHighValueOrderAlert($orderId, $orderNumber, $amount, $threshold) {
        $title = "High-Value Order";
        $message = "Order #{$orderNumber} is BDT {$amount} (≥ BDT {$threshold})";
        $metadata = [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'amount' => $amount,
            'threshold' => $threshold
        ];
        return $this->createNotification($title, $message, 'high_value_order', $metadata);
    }

    /**
     * Generic security alert (e.g., unauthorized access attempt, multiple failed logins)
     */
    public function createSecurityAlert($title, $message, $metadata = null) {
        return $this->createNotification($title, $message, 'security', $metadata);
    }

    /**
     * System alert for failures (gateway/API/job failures)
     */
    public function createSystemAlert($title, $message, $metadata = null) {
        return $this->createNotification($title, $message, 'system_error', $metadata);
    }
    
    /**
     * Create a new user registration notification
     */
    public function createNewUserNotification($userId, $username, $email) {
        $title = "New User Registration";
        $message = "New user {$username} ({$email}) has registered";
        $metadata = [
            'user_id' => $userId,
            'username' => $username,
            'email' => $email
        ];
        
        return $this->createNotification($title, $message, 'user_registration', $metadata);
    }
    
    /**
     * Create a contact form submission notification
     */
    public function createContactFormNotification($name, $email, $subject, $messageContent) {
        $title = "New Contact Form Submission";
        $message = "New message from {$name} ({$email}): {$subject}";
        $metadata = [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $messageContent
        ];
        
        return $this->createNotification($title, $message, 'contact_form', $metadata);
    }
    
    /**
     * Create a product review notification
     */
    public function createProductReviewNotification($productId, $productName, $userId, $username, $rating) {
        $title = "New Product Review";
        $message = "{$username} rated {$productName} with {$rating} stars";
        $metadata = [
            'product_id' => $productId,
            'product_name' => $productName,
            'user_id' => $userId,
            'username' => $username,
            'rating' => $rating
        ];
        
        return $this->createNotification($title, $message, 'product_review', $metadata);
    }
    
    /**
     * Create a cart activity notification
     */
    public function createCartActivityNotification($userId, $username, $action, $productId = null, $productName = null) {
        $title = "Cart Activity";
        $message = "{$username} {$action}" . ($productName ? " {$productName}" : "");
        $metadata = [
            'user_id' => $userId,
            'username' => $username,
            'action' => $action
        ];
        
        if ($productId) {
            $metadata['product_id'] = $productId;
        }
        
        if ($productName) {
            $metadata['product_name'] = $productName;
        }
        
        return $this->createNotification($title, $message, 'cart_activity', $metadata);
    }
    
    /**
     * Create a wishlist activity notification
     */
    public function createWishlistActivityNotification($userId, $username, $action, $productId = null, $productName = null) {
        $title = "Wishlist Activity";
        $message = "{$username} {$action}" . ($productName ? " {$productName}" : "");
        $metadata = [
            'user_id' => $userId,
            'username' => $username,
            'action' => $action
        ];
        
        if ($productId) {
            $metadata['product_id'] = $productId;
        }
        
        if ($productName) {
            $metadata['product_name'] = $productName;
        }
        
        return $this->createNotification($title, $message, 'wishlist_activity', $metadata);
    }
    
    /**
     * Create an order cancellation notification
     */
    public function createOrderCancellationNotification($orderId, $orderNumber, $reason = null) {
        $title = "Order Cancelled";
        $message = "Order #{$orderNumber} has been cancelled" . ($reason ? ": {$reason}" : "");
        $metadata = [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'reason' => $reason
        ];
        
        return $this->createNotification($title, $message, 'order_cancellation', $metadata);
    }
    
    /**
     * Create a reported product notification
     */
    public function createReportedProductNotification($productId, $productName, $userId, $username, $reason) {
        $title = "Product Reported";
        $message = "{$username} reported product {$productName}: {$reason}";
        $metadata = [
            'product_id' => $productId,
            'product_name' => $productName,
            'user_id' => $userId,
            'username' => $username,
            'reason' => $reason
        ];
        
        return $this->createNotification($title, $message, 'product_report', $metadata);
    }
    
    /**
     * Get unread notifications count
     */
    public function getUnreadCount() {
        try {
            return $this->db->getValue('notifications', 'COUNT(*)', 'is_read = 0');
        } catch (Exception $e) {
            error_log('NotificationService Error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($id) {
        try {
            $this->db->where('id', $id);
            return $this->db->update('notifications', [
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('NotificationService Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead() {
        try {
            $this->db->where('is_read', 0);
            return $this->db->update('notifications', [
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('NotificationService Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent notifications
     */
    public function getRecentNotifications($limit = 20) {
        try {
            $this->db->orderBy('created_at', 'DESC');
            return $this->db->get('notifications', $limit);
        } catch (Exception $e) {
            error_log('NotificationService Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get notifications by type
     */
    public function getNotificationsByType($type, $limit = 20) {
        try {
            $this->db->where('type', $type);
            $this->db->orderBy('created_at', 'DESC');
            return $this->db->get('notifications', $limit);
        } catch (Exception $e) {
            error_log('NotificationService Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get unread notifications
     */
    public function getUnreadNotifications($limit = 20) {
        try {
            $this->db->where('is_read', 0);
            $this->db->orderBy('created_at', 'DESC');
            return $this->db->get('notifications', $limit);
        } catch (Exception $e) {
            error_log('NotificationService Error: ' . $e->getMessage());
            return [];
        }
    }
}
