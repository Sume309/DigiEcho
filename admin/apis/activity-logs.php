<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/ActivityLogger.php';

use App\auth\Admin;

// Check admin authentication
if (!Admin::Check()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $activityLogger = new ActivityLogger();
    
    // Get parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 10;
    
    // Build filters
    $filters = [];
    
    if (!empty($_GET['type'])) {
        $filters['type'] = $_GET['type'];
    }
    
    if (!empty($_GET['user_type'])) {
        $filters['user_type'] = $_GET['user_type'];
    }
    
    if (!empty($_GET['date_from'])) {
        $filters['date_from'] = $_GET['date_from'];
    }
    
    if (!empty($_GET['date_to'])) {
        $filters['date_to'] = $_GET['date_to'];
    }
    
    if (!empty($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }
    
    // Check if export is requested
    $export = $_GET['export'] ?? '';
    
    // Get activities
    $result = $activityLogger->getActivities($page, $limit, $filters);
    
    // Handle CSV export
    if ($export === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="activity-logs-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'ID',
            'Date & Time',
            'Action',
            'Description',
            'Type',
            'User Type',
            'IP Address',
            'User Agent',
            'Metadata'
        ]);
        
        // CSV data
        foreach ($result['activities'] as $activity) {
            $metadata = $activity['metadata'] ? json_encode(json_decode($activity['metadata'], true), JSON_UNESCAPED_SLASHES) : '';
            
            fputcsv($output, [
                $activity['id'],
                date('Y-m-d H:i:s', strtotime($activity['created_at'])),
                $activity['action'],
                $activity['description'],
                ucfirst($activity['type']),
                ucfirst($activity['user_type']),
                $activity['ip_address'],
                $activity['user_agent'],
                $metadata
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    // Handle Excel export
    if ($export === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="activity-logs-' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Date & Time</th>";
        echo "<th>Action</th>";
        echo "<th>Description</th>";
        echo "<th>Type</th>";
        echo "<th>User Type</th>";
        echo "<th>IP Address</th>";
        echo "<th>User Agent</th>";
        echo "<th>Metadata</th>";
        echo "</tr>";
        
        foreach ($result['activities'] as $activity) {
            $metadata = $activity['metadata'] ? json_encode(json_decode($activity['metadata'], true), JSON_UNESCAPED_SLASHES) : '';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($activity['id']) . "</td>";
            echo "<td>" . htmlspecialchars(date('Y-m-d H:i:s', strtotime($activity['created_at']))) . "</td>";
            echo "<td>" . htmlspecialchars($activity['action']) . "</td>";
            echo "<td>" . htmlspecialchars($activity['description']) . "</td>";
            echo "<td>" . htmlspecialchars(ucfirst($activity['type'])) . "</td>";
            echo "<td>" . htmlspecialchars(ucfirst($activity['user_type'])) . "</td>";
            echo "<td>" . htmlspecialchars($activity['ip_address']) . "</td>";
            echo "<td>" . htmlspecialchars($activity['user_agent']) . "</td>";
            echo "<td>" . htmlspecialchars($metadata) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        exit;
    }
    
    // Format activities for display
    $formattedActivities = [];
    foreach ($result['activities'] as $activity) {
        $formattedActivities[] = [
            'id' => $activity['id'],
            'action' => $activity['action'],
            'description' => $activity['description'],
            'type' => $activity['type'],
            'user_type' => $activity['user_type'],
            'ip_address' => $activity['ip_address'],
            'user_agent' => $activity['user_agent'],
            'metadata' => $activity['metadata'] ? json_decode($activity['metadata'], true) : null,
            'created_at' => $activity['created_at'],
            'formatted_date' => date('M j, Y g:i A', strtotime($activity['created_at'])),
            'time_ago' => timeAgo($activity['created_at']),
            'icon' => getActivityIcon($activity['type']),
            'color' => getActivityColor($activity['type']),
            'badge_class' => 'bg-' . getActivityColor($activity['type'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedActivities,
        'pagination' => [
            'current_page' => $result['page'],
            'total_pages' => $result['total_pages'],
            'total_items' => $result['total'],
            'items_per_page' => $result['limit'],
            'has_next' => $result['page'] < $result['total_pages'],
            'has_prev' => $result['page'] > 1
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Activity Logs API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}

function getActivityIcon($type) {
    $icons = [
        'auth' => 'sign-in-alt',
        'user' => 'user',
        'product' => 'box',
        'order' => 'shopping-cart',
        'category' => 'tags',
        'brand' => 'trademark',
        'system' => 'cog',
        'payment' => 'credit-card',
        'inventory' => 'warehouse',
        'settings' => 'cogs'
    ];
    return $icons[$type] ?? 'info-circle';
}

function getActivityColor($type) {
    $colors = [
        'auth' => 'success',
        'user' => 'info',
        'product' => 'warning',
        'order' => 'danger',
        'category' => 'primary',
        'brand' => 'secondary',
        'system' => 'dark',
        'payment' => 'success',
        'inventory' => 'warning',
        'settings' => 'info'
    ];
    return $colors[$type] ?? 'primary';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}
?>
