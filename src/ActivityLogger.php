<?php

class ActivityLogger {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/settings.php';
        require_once __DIR__ . '/db/MysqliDb.php';
        $this->db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    }
    
    /**
     * Log an activity
     * 
     * @param string $action The action performed
     * @param string $description Detailed description
     * @param string $type Type of activity (auth, user, product, order, etc.)
     * @param array $metadata Additional data as associative array
     * @param int $userId User ID (optional)
     * @param string $userType Type of user (admin, user, guest)
     */
    public function log($action, $description, $type = 'system', $metadata = null, $userId = null, $userType = 'admin') {
        try {
            // Get current user ID from session if not provided
            if (!$userId && isset($_SESSION['userid'])) {
                $userId = $_SESSION['userid'];
            }
            
            // Determine user type from session if not provided
            if (!$userId && isset($_SESSION['role'])) {
                $userType = $_SESSION['role'] === 'admin' ? 'admin' : 'user';
            }
            
            $activityData = [
                'user_id' => $userId,
                'user_type' => $userType,
                'action' => $action,
                'description' => $description,
                'type' => $type,
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'metadata' => $metadata ? json_encode($metadata) : null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            return $this->db->insert('activity_logs', $activityData);
            
        } catch (Exception $e) {
            // Log error but don't break the application
            error_log("ActivityLogger Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get activities with pagination and filtering
     */
    public function getActivities($page = 1, $limit = 10, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Apply filters
            if (!empty($filters['type'])) {
                $this->db->where('type', $filters['type']);
            }
            
            if (!empty($filters['user_type'])) {
                $this->db->where('user_type', $filters['user_type']);
            }
            
            if (!empty($filters['date_from'])) {
                $this->db->where('DATE(created_at) >=', $filters['date_from']);
            }
            
            if (!empty($filters['date_to'])) {
                $this->db->where('DATE(created_at) <=', $filters['date_to']);
            }
            
            if (!empty($filters['search'])) {
                $this->db->where('(action LIKE ? OR description LIKE ?)', 
                    ['%' . $filters['search'] . '%', '%' . $filters['search'] . '%']);
            }
            
            // Get total count for pagination
            $totalQuery = clone $this->db;
            $total = $totalQuery->getValue('activity_logs', 'COUNT(*)');
            
            // Get activities
            $this->db->orderBy('created_at', 'DESC');
            $activities = $this->db->get('activity_logs', [$offset, $limit]);
            
            return [
                'activities' => $activities,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            error_log("ActivityLogger Error: " . $e->getMessage());
            return [
                'activities' => [],
                'total' => 0,
                'page' => 1,
                'limit' => $limit,
                'total_pages' => 0
            ];
        }
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Quick logging methods for common activities
     */
    public function logLogin($userId, $userType = 'admin') {
        return $this->log('Login', 'User logged into the system', 'auth', null, $userId, $userType);
    }
    
    public function logLogout($userId, $userType = 'admin') {
        return $this->log('Logout', 'User logged out of the system', 'auth', null, $userId, $userType);
    }
    
    public function logProductAdd($productId, $productName) {
        return $this->log('Product Added', "Added new product: {$productName}", 'product', ['product_id' => $productId]);
    }
    
    public function logProductUpdate($productId, $productName) {
        return $this->log('Product Updated', "Updated product: {$productName}", 'product', ['product_id' => $productId]);
    }
    
    public function logProductDelete($productId, $productName) {
        return $this->log('Product Deleted', "Deleted product: {$productName}", 'product', ['product_id' => $productId]);
    }
    
    public function logOrderStatusChange($orderId, $oldStatus, $newStatus) {
        return $this->log('Order Status Updated', "Changed order #{$orderId} status from {$oldStatus} to {$newStatus}", 'order', [
            'order_id' => $orderId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);
    }
    
    public function logUserAction($action, $description, $metadata = null) {
        return $this->log($action, $description, 'user', $metadata);
    }
    
    public function logSystemAction($action, $description, $metadata = null) {
        return $this->log($action, $description, 'system', $metadata);
    }
}
?>
