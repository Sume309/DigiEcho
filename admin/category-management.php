<?php
// Prevent any output before JSON response
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files manually since vendor autoload is missing
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';
require_once __DIR__ . '/../src/auth/admin.php';

use App\auth\Admin;

// Check authentication for both regular and AJAX requests
if (!Admin::Check()) {
    // Debug logging
    error_log('Authentication failed for user. Session data: ' . print_r($_SESSION, true));
    
    if (isset($_GET['action'])) {
        // For AJAX requests, return JSON error
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false, 
            'message' => 'Authentication required',
            'redirect' => 'auto-login.php',
            'debug' => [
                'session_id' => session_id(),
                'session_status' => session_status(),
                'session_data' => $_SESSION,
                'admin_check' => Admin::Check(),
                'action' => $_GET['action'] ?? 'none'
            ]
        ]);
        exit;
    } else {
        // For regular page requests, redirect to login
        header('Location: auto-login.php');
        exit;
    }
}

$db = new MysqliDb(
    settings()['hostname'], 
    settings()['user'], 
    settings()['password'], 
    settings()['database']
);

// Handle AJAX requests
if (isset($_GET['action'])) {
    // Clear any output that might have been generated
    if (ob_get_contents()) {
        ob_clean();
    }
    
    // Set proper JSON header
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        switch ($_GET['action']) {
            case 'get_categories':
                handleCategoryList($db);
                break;
            case 'toggle_status':
                handleToggleStatus($db);
                break;
            case 'bulk_delete':
                handleBulkDelete($db);
                break;
            case 'get_stats':
                handleGetStats($db);
                break;
            case 'export':
                handleExport($db);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle category list for DataTables
function handleCategoryList($db) {
    try {
        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10); // Default 10 categories per page
        $search = $_GET['search']['value'] ?? '';
        $statusFilter = $_GET['status_filter'] ?? '';
        $dateFilter = $_GET['date_filter'] ?? '';
        $productsFilter = $_GET['products_filter'] ?? '';
        
        // Debug logging (remove in production)
        error_log("Category Search Debug - Search: '$search', Status: '$statusFilter', Date: '$dateFilter', Products: '$productsFilter'");
        
        $whereConditions = [];
        $params = [];
        
        // Search filter
        if (!empty($search)) {
            $whereConditions[] = '(c.name LIKE ? OR c.slug LIKE ? OR c.description LIKE ?)';
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
            error_log("Search filter applied: $search");
        } else {
            error_log("No search filter applied");
        }
        
        // Status filter
        if ($statusFilter !== '') {
            $whereConditions[] = 'c.is_active = ?';
            $params[] = intval($statusFilter);
        }
        
        // Date filter
        if (!empty($dateFilter)) {
            $today = date('Y-m-d');
            switch ($dateFilter) {
                case 'today':
                    $whereConditions[] = 'DATE(c.created_at) = ?';
                    $params[] = $today;
                    break;
                case 'week':
                    $weekStart = date('Y-m-d', strtotime('monday this week'));
                    $whereConditions[] = 'c.created_at >= ?';
                    $params[] = $weekStart;
                    break;
                case 'month':
                    $monthStart = date('Y-m-01');
                    $whereConditions[] = 'c.created_at >= ?';
                    $params[] = $monthStart;
                    break;
                case 'year':
                    $yearStart = date('Y-01-01');
                    $whereConditions[] = 'c.created_at >= ?';
                    $params[] = $yearStart;
                    break;
            }
        }
        
        // Products filter
        if (!empty($productsFilter)) {
            if ($productsFilter === 'with_products') {
                $whereConditions[] = 'p.product_count > 0';
            } elseif ($productsFilter === 'without_products') {
                $whereConditions[] = '(p.product_count IS NULL OR p.product_count = 0)';
            }
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count (unfiltered)
        $totalCountQuery = "SELECT COUNT(*) as total FROM categories";
        $totalCountResult = $db->rawQuery($totalCountQuery);
        $totalRecords = $totalCountResult[0]['total'] ?? 0;
        
        // Get filtered count
        $filteredCountQuery = "SELECT COUNT(*) as total FROM categories c";
        if (!empty($whereClause)) {
            $filteredCountQuery .= " LEFT JOIN (SELECT category_id, COUNT(*) as product_count FROM products GROUP BY category_id) p ON c.id = p.category_id $whereClause";
        }
        
        if (!empty($params)) {
            $filteredResult = $db->rawQuery($filteredCountQuery, $params);
        } else {
            $filteredResult = $db->rawQuery($filteredCountQuery);
        }
        $filteredRecords = $filteredResult[0]['total'] ?? 0;
        
        // Get categories data
        $query = "
            SELECT c.*, COALESCE(p.product_count, 0) as product_count
            FROM categories c
            LEFT JOIN (SELECT category_id, COUNT(*) as product_count FROM products GROUP BY category_id) p ON c.id = p.category_id
            $whereClause
            ORDER BY c.sort_order ASC, c.name ASC
            LIMIT $start, $length
        ";
        
        if (!empty($params)) {
            $categories = $db->rawQuery($query, $params);
        } else {
            $categories = $db->rawQuery($query);
        }
        
        $data = [];
        if ($categories) {
            foreach ($categories as $category) {
                $statusBadge = $category['is_active'] ? 
                    '<span class="badge bg-success">Active</span>' : 
                    '<span class="badge bg-secondary">Inactive</span>';
                    
                $image = !empty($category['image']) && file_exists(__DIR__ . "/../assets/categories/{$category['image']}") ?
                    sprintf(
                        '<img src="%sassets/categories/%s" alt="%s" class="category-image" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">',
                        settings()['root'],
                        htmlspecialchars($category['image'], ENT_QUOTES),
                        htmlspecialchars($category['name'], ENT_QUOTES)
                    ) :
                    '<div class="placeholder-image bg-light d-flex align-items-center justify-content-center" style="width:50px;height:50px;border-radius:4px;"><i class="fas fa-image text-muted"></i></div>';
                
                $actions = sprintf(
                    '<div class="btn-group btn-group-sm" role="group">'
                    . '<a href="category-edit-enhanced.php?id=%d" class="btn btn-outline-primary" title="Edit">'
                    . '<i class="fas fa-edit"></i>'
                    . '</a>'
                    . '<button class="btn btn-outline-warning toggle-status" data-id="%d" data-status="%d" title="Toggle Status">'
                    . '<i class="fas fa-toggle-%s"></i>'
                    . '</button>'
                    . '<button class="btn btn-outline-danger delete-category" data-id="%d" data-name="%s" title="Delete">'
                    . '<i class="fas fa-trash"></i>'
                    . '</button>'
                    . '</div>',
                    $category['id'],
                    $category['id'],
                    $category['is_active'],
                    $category['is_active'] ? 'on' : 'off',
                    $category['id'],
                    htmlspecialchars($category['name'], ENT_QUOTES)
                );
                
                $data[] = [
                    'checkbox' => sprintf('<input type="checkbox" class="category-checkbox" value="%d" data-name="%s">', $category['id'], htmlspecialchars($category['name'], ENT_QUOTES)),
                    'image' => $image,
                    'name' => sprintf('<strong>%s</strong><br><small class="text-muted">%s</small>', htmlspecialchars($category['name']), htmlspecialchars($category['slug'])),
                    'description' => !empty($category['description']) ? 
                        (strlen($category['description']) > 50 ? htmlspecialchars(substr($category['description'], 0, 50)) . '...' : htmlspecialchars($category['description'])) : 
                        '<span class="text-muted">No description</span>',
                    'product_count' => sprintf('<span class="badge bg-primary">%d</span>', $category['product_count']),
                    'status' => $statusBadge,
                    'sort_order' => $category['sort_order'],
                    'updated_at' => date('M d, Y', strtotime($category['updated_at'])),
                    'actions' => $actions
                ];
            }
        }
        
        $response = [
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            'draw' => intval($_GET['draw'] ?? 1),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// Handle status toggle
function handleToggleStatus($db) {
    try {
        // Add debug logging
        error_log('Status toggle request received: ' . print_r($_POST, true));
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            
            error_log("Attempting to update category ID: $id to status: $status");
            
            if ($id > 0) {
                // Get current category info for logging
                $db->where('id', $id);
                $currentCategory = $db->getOne('categories', ['name', 'is_active']);
                
                if (!$currentCategory) {
                    echo json_encode(['success' => false, 'message' => 'Category not found']);
                    return;
                }
                
                error_log("Current category status: {$currentCategory['is_active']}, changing to: $status");
                
                // Update the category
                $db->where('id', $id);
                $result = $db->update('categories', [
                    'is_active' => $status, 
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                if ($result) {
                    // Verify the update
                    $db->where('id', $id);
                    $updatedCategory = $db->getOne('categories', ['is_active']);
                    
                    error_log("Update result: $result, New status: {$updatedCategory['is_active']}");
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Status updated successfully',
                        'category_id' => $id,
                        'new_status' => intval($updatedCategory['is_active']),
                        'old_status' => intval($currentCategory['is_active'])
                    ]);
                } else {
                    error_log('Database update failed for category ID: ' . $id);
                    echo json_encode(['success' => false, 'message' => 'Failed to update status in database']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method. POST required.']);
        }
    } catch (Exception $e) {
        error_log('Status toggle error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// Handle bulk delete
function handleBulkDelete($db) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ids = $_POST['ids'] ?? [];
        
        if (!empty($ids) && is_array($ids)) {
            $successCount = 0;
            $errors = [];
            
            foreach ($ids as $id) {
                $id = intval($id);
                
                // Check if category has products
                $db->where('category_id', $id);
                $productCount = $db->getValue('products', 'COUNT(*)');
                
                if ($productCount > 0) {
                    $db->where('id', $id);
                    $category = $db->getOne('categories', ['name']);
                    $errors[] = "Category '{$category['name']}' has {$productCount} product(s)";
                    continue;
                }
                
                // Delete category
                $db->where('id', $id);
                if ($db->delete('categories')) {
                    $successCount++;
                } else {
                    $errors[] = "Failed to delete category ID: $id";
                }
            }
            
            $message = "$successCount categories deleted successfully.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }
            
            echo json_encode([
                'success' => $successCount > 0,
                'message' => $message,
                'deleted_count' => $successCount,
                'errors' => $errors
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No categories selected']);
        }
    }
}

// Handle statistics
function handleGetStats($db) {
    try {
        // Use raw queries for reliable results
        $totalCategories = $db->rawQueryOne('SELECT COUNT(*) as count FROM categories')['count'] ?? 0;
        $activeCategories = $db->rawQueryOne('SELECT COUNT(*) as count FROM categories WHERE is_active = 1')['count'] ?? 0;
        $inactiveCategories = $db->rawQueryOne('SELECT COUNT(*) as count FROM categories WHERE is_active = 0')['count'] ?? 0;
        $categoriesWithProducts = $db->rawQueryOne('SELECT COUNT(DISTINCT category_id) as count FROM products WHERE category_id IS NOT NULL')['count'] ?? 0;
        $categoriesWithoutProducts = $db->rawQueryOne('SELECT COUNT(*) as count FROM categories c LEFT JOIN products p ON c.id = p.category_id WHERE p.category_id IS NULL')['count'] ?? 0;
        $totalProducts = $db->rawQueryOne('SELECT COUNT(*) as count FROM products')['count'] ?? 0;
        $avgProductsPerCategory = $db->rawQueryOne('SELECT ROUND(AVG(product_count), 1) as avg FROM (SELECT COUNT(*) as product_count FROM products GROUP BY category_id) as counts')['avg'] ?? 0;
        
        $stats = [
            'total_categories' => $totalCategories,
            'active_categories' => $activeCategories,
            'inactive_categories' => $inactiveCategories,
            'categories_with_products' => $categoriesWithProducts,
            'categories_without_products' => $categoriesWithoutProducts,
            'total_products' => $totalProducts,
            'avg_products_per_category' => $avgProductsPerCategory,
            'last_updated' => date('Y-m-d H:i:s'),
            'percentages' => []
        ];
        
        // Calculate percentages
        $total = $stats['total_categories'];
        if ($total > 0) {
            $stats['percentages'] = [
                'active' => round(($stats['active_categories'] / $total) * 100, 1),
                'inactive' => round(($stats['inactive_categories'] / $total) * 100, 1),
                'with_products' => round(($stats['categories_with_products'] / $total) * 100, 1),
                'without_products' => round(($stats['categories_without_products'] / $total) * 100, 1)
            ];
        } else {
            $stats['percentages'] = [
                'active' => 0,
                'inactive' => 0,
                'with_products' => 0,
                'without_products' => 0
            ];
        }
        
        // Add debug information
        $stats['debug'] = [
            'timestamp' => time(),
            'server_time' => date('Y-m-d H:i:s'),
            'query_method' => 'rawQuery_fixed'
        ];
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $stats]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// Handle export
function handleExport($db) {
    $format = $_GET['format'] ?? 'csv';
    $search = $_GET['search'] ?? '';
    $statusFilter = $_GET['status_filter'] ?? '';
    $dateFilter = $_GET['date_filter'] ?? '';
    $productsFilter = $_GET['products_filter'] ?? '';
    
    $whereConditions = [];
    $params = [];
    
    // Apply same filters as in handleCategoryList
    if (!empty($search)) {
        $whereConditions[] = '(c.name LIKE ? OR c.slug LIKE ? OR c.description LIKE ?)';
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
    }
    
    if ($statusFilter !== '') {
        $whereConditions[] = 'c.is_active = ?';
        $params[] = intval($statusFilter);
    }
    
    if (!empty($dateFilter)) {
        $today = date('Y-m-d');
        switch ($dateFilter) {
            case 'today':
                $whereConditions[] = 'DATE(c.created_at) = ?';
                $params[] = $today;
                break;
            case 'week':
                $weekStart = date('Y-m-d', strtotime('monday this week'));
                $whereConditions[] = 'c.created_at >= ?';
                $params[] = $weekStart;
                break;
            case 'month':
                $monthStart = date('Y-m-01');
                $whereConditions[] = 'c.created_at >= ?';
                $params[] = $monthStart;
                break;
            case 'year':
                $yearStart = date('Y-01-01');
                $whereConditions[] = 'c.created_at >= ?';
                $params[] = $yearStart;
                break;
        }
    }
    
    if (!empty($productsFilter)) {
        if ($productsFilter === 'with_products') {
            $whereConditions[] = 'p.product_count > 0';
        } elseif ($productsFilter === 'without_products') {
            $whereConditions[] = '(p.product_count IS NULL OR p.product_count = 0)';
        }
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $query = "
        SELECT c.id, c.name, c.slug, c.description, c.is_active, c.sort_order, 
               c.created_at, c.updated_at, COALESCE(p.product_count, 0) as product_count
        FROM categories c
        LEFT JOIN (SELECT category_id, COUNT(*) as product_count FROM products GROUP BY category_id) p ON c.id = p.category_id
        $whereClause
        ORDER BY c.sort_order ASC, c.name ASC
    ";
    
    if (!empty($params)) {
        $categories = $db->rawQuery($query, $params);
    } else {
        $categories = $db->rawQuery($query);
    }
    
    if ($format === 'csv') {
        // Set CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="categories_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, ['ID', 'Name', 'Slug', 'Description', 'Status', 'Sort Order', 'Product Count', 'Created At', 'Updated At']);
        
        // Add data rows
        foreach ($categories as $category) {
            fputcsv($output, [
                $category['id'],
                $category['name'],
                $category['slug'],
                $category['description'],
                $category['is_active'] ? 'Active' : 'Inactive',
                $category['sort_order'],
                $category['product_count'],
                $category['created_at'],
                $category['updated_at']
            ]);
        }
        
        fclose($output);
    } else {
        // For Excel format, return JSON that can be processed by frontend
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $categories,
            'message' => 'Excel export functionality requires additional library. Please use CSV export.'
        ]);
    }
}
?>

<?php require __DIR__ . '/components/header.php'; ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .category-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }
    .placeholder-image {
        width: 50px;
        height: 50px;
        background: #f8f9fa;
        border-radius: 4px;
    }
    .status-badge {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .stats-card {
        transition: transform 0.2s;
    }
    .stats-card:hover {
        transform: translateY(-2px);
    }
    
    /* Enhanced Filter Section */
    .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 2px solid #dee2e6;
    }
    
    .input-group .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    /* Responsive filters */
    @media (max-width: 768px) {
        .card-header .d-flex {
            flex-direction: column;
            gap: 1rem;
        }
        
        .form-select,
        .input-group {
            min-width: 100% !important;
        }
        
        .btn-group {
            width: 100%;
        }
        
        .btn-group .btn {
            flex: 1;
        }
    }
    
    /* Enhanced Pagination Styles */
    .dataTables_wrapper .dataTables_length select {
        min-width: 80px;
        padding: 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.375rem 0.75rem;
        margin: 0 2px;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        background: white;
        color: #495057;
        font-size: 0.875rem;
        transition: all 0.15s ease-in-out;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #e9ecef;
        border-color: #adb5bd;
        color: #495057;
        text-decoration: none;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #007bff;
        border-color: #007bff;
        color: white;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
        background: #f8f9fa;
        border-color: #dee2e6;
        color: #6c757d;
        cursor: not-allowed;
    }
    
    #customPaginationInfo {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.5rem;
        text-align: center;
    }
    
    #pageJumpContainer {
        display: flex;
        justify-content: center;
        margin-top: 1rem;
    }
    
    .dataTables_wrapper .dataTables_info {
        padding-top: 0.75rem;
        color: #6c757d;
        font-size: 0.875rem;
    }
    
    .table-responsive {
        border-radius: 0.375rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .dataTables_wrapper .dataTables_filter input {
        margin-left: 0.5rem;
        padding: 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    
    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label {
        font-weight: 500;
        margin-bottom: 0;
    }
    
    /* Custom scrollbar for mobile */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .table-responsive::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    /* Loading overlay */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    
    /* Active sidebar link */
    .sb-sidenav .nav-link.active {
        background-color: rgba(0, 123, 255, 0.1);
        color: #007bff;
        font-weight: 600;
    }
    
    .sb-sidenav .nav-link.active i {
        color: #007bff;
    }
    
    /* Enhanced Statistics Cards */
    .stats-card {
        transition: all 0.3s ease;
        border: none;
        border-radius: 12px;
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
    }
    
    .stats-card .card-body {
        padding: 1.5rem;
    }
    
    .stats-card .card-footer {
        padding: 0.75rem 1.5rem;
        font-size: 0.85rem;
    }
    
    .stats-card .h3 {
        font-size: 2.2rem;
        font-weight: 700;
        line-height: 1;
    }
    
    .stats-card .fa-3x {
        font-size: 2.8rem;
    }
    
    /* Statistics animations */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .stats-card.updating .h3 {
        animation: pulse 0.5s ease-in-out;
    }
    
    /* Loading spinner */
    .fa-spin {
        animation: fa-spin 1s infinite linear;
    }
    
    @keyframes fa-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Live status indicator */
    #liveStatusIndicator {
        animation: pulse-glow 2s ease-in-out infinite alternate;
    }
    
    @keyframes pulse-glow {
        from {
            box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
        }
        to {
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.8);
        }
    }
    
    /* Statistics update banner */
    #statsUpdateBanner {
        border-left: 4px solid #17a2b8;
        background: linear-gradient(135deg, rgba(23, 162, 184, 0.1) 0%, rgba(23, 162, 184, 0.05) 100%);
    }
    
    /* Enhanced stats card hover effect */
    .stats-card {
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    
    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        transition: left 0.5s;
    }
    
    .stats-card:hover::before {
        left: 100%;
    }
    
    .stats-card:hover .card-body {
        transform: scale(1.02);
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
                    <h1 class="mt-4">Category Management</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Categories</li>
                    </ol>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <?php
                    // Get real statistics
                    $totalCategories = $db->getValue('categories', 'COUNT(*)');
                    $activeCategories = $db->getValue('categories', 'COUNT(*)', 'is_active = 1');
                    $inactiveCategories = $db->getValue('categories', 'COUNT(*)', 'is_active = 0');
                    $categoriesWithProductsResult = $db->rawQuery('SELECT COUNT(DISTINCT category_id) as count FROM products WHERE category_id IS NOT NULL');
                    $categoriesWithProducts = $categoriesWithProductsResult[0]['count'] ?? 0;
                    $totalProducts = $db->getValue('products', 'COUNT(*)');
                    ?>
                    
                    <!-- Enhanced Statistics Cards -->
                    <div class="row mb-4">
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card bg-primary text-white stats-card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-uppercase opacity-75">Total Categories</div>
                                            <div class="h3 mb-0 fw-bold" id="totalCategories"><?php echo $totalCategories; ?></div>
                                            <div class="small mt-1">
                                                <i class="fas fa-sync-alt fa-spin d-none" id="totalCategoriesLoader"></i>
                                                <span class="text-success" id="totalCategoriesChange"></span>
                                            </div>
                                        </div>
                                        <div class="fa-3x opacity-25">
                                            <i class="fas fa-tags"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <div class="d-flex justify-content-between">
                                        <small class="opacity-75">All categories</small>
                                        <small><i class="fas fa-arrow-right"></i></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card bg-success text-white stats-card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-uppercase opacity-75">Active Categories</div>
                                            <div class="h3 mb-0 fw-bold" id="activeCategories"><?php echo $activeCategories; ?></div>
                                            <div class="small mt-1">
                                                <i class="fas fa-sync-alt fa-spin d-none" id="activeCategoriesLoader"></i>
                                                <span class="text-light" id="activeCategoriesChange"></span>
                                            </div>
                                        </div>
                                        <div class="fa-3x opacity-25">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <div class="d-flex justify-content-between">
                                        <small class="opacity-75">Currently active</small>
                                        <small><i class="fas fa-arrow-right"></i></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card bg-secondary text-white stats-card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-uppercase opacity-75">Inactive Categories</div>
                                            <div class="h3 mb-0 fw-bold" id="inactiveCategories"><?php echo $inactiveCategories; ?></div>
                                            <div class="small mt-1">
                                                <i class="fas fa-sync-alt fa-spin d-none" id="inactiveCategoriesLoader"></i>
                                                <span class="text-light" id="inactiveCategoriesChange"></span>
                                            </div>
                                        </div>
                                        <div class="fa-3x opacity-25">
                                            <i class="fas fa-times-circle"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <div class="d-flex justify-content-between">
                                        <small class="opacity-75">Currently inactive</small>
                                        <small><i class="fas fa-arrow-right"></i></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card bg-warning text-white stats-card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-uppercase opacity-75">With Products</div>
                                            <div class="h3 mb-0 fw-bold" id="categoriesWithProducts"><?php echo $categoriesWithProducts; ?></div>
                                            <div class="small mt-1">
                                                <i class="fas fa-sync-alt fa-spin d-none" id="categoriesWithProductsLoader"></i>
                                                <span class="text-light" id="categoriesWithProductsChange"></span>
                                            </div>
                                        </div>
                                        <div class="fa-3x opacity-25">
                                            <i class="fas fa-box"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0">
                                    <div class="d-flex justify-content-between">
                                        <small class="opacity-75">Have products</small>
                                        <small><i class="fas fa-arrow-right"></i></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Live Statistics Status Banner -->
                    <div class="alert alert-info d-none" id="statsUpdateBanner" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-sync-alt fa-spin me-2"></i>
                            <div>
                                <strong>Statistics Updated!</strong> Real-time data refreshed successfully.
                                <button type="button" class="btn btn-sm btn-outline-primary ms-3 refresh-stats">
                                    <i class="fas fa-refresh me-1"></i>Refresh Now
                                </button>
                            </div>
                            <div class="ms-auto">
                                <span class="badge bg-success" id="liveStatusIndicator">
                                    <i class="fas fa-circle fa-xs me-1"></i>Live Updates Active
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                               
                                <div class="d-flex flex-column flex-lg-row gap-2 align-items-stretch align-items-lg-center">
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <!-- Enhanced Search Input -->
                                        <div class="input-group input-group-sm" style="min-width: 200px;">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            <input type="text" class="form-control" id="customSearch" placeholder="Search categories..." 
                                                   title="Search by name, slug, or description">
                                            <button class="btn btn-outline-secondary" type="button" id="clearSearch" title="Clear search">
                                                <i class="fas fa-times"></i>
                                            </button> 
                                        </div>
                                        
                                        <!-- Status Filter -->
                                        <select class="form-select form-select-sm" id="statusFilter" style="min-width: 120px;">
                                            <option value="">All Status</option>
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                        
                                        <!-- Date Filter -->
                                        <select class="form-select form-select-sm" id="dateFilter" style="min-width: 150px;">
                                            <option value="">All Dates</option>
                                            <option value="today">Today</option>
                                            <option value="week">This Week</option>
                                            <option value="month">This Month</option>
                                            <option value="year">This Year</option>
                                        </select>
                                        
                                        <!-- Products Filter -->
                                        <select class="form-select form-select-sm" id="productsFilter" style="min-width: 160px;">
                                            <option value="">All Categories</option>
                                            <option value="with_products">With Products</option>
                                            <option value="without_products">Without Products</option>
                                        </select>
                                        
                                        <!-- Clear Filters Button -->
                                      
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <!-- Export Options -->
                                      
                                        
                                        <!-- Add Category Button -->
                                        <a href="category-add.php" class="btn btn-primary btn-sm" 
                                        style="padding:20px 30px; border:20px; border-radius:8px; cursor:pointer; 
                                        font-size:20px; font-weight:1000; background:#28a745; color:#fff; margin-left:60px;">
                                            <i class="fas fa-plus me-1"></i> Add Category
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Bulk Actions Bar -->
                            <div class="row mb-3" id="bulkActionsBar" style="display: none;">
                                <div class="col-12">
                                    <div class="card border-warning">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="text-warning fw-bold">
                                                        <i class="fas fa-check-square me-1"></i>
                                                        <span id="selectedCount">0</span> categories selected
                                                    </span>
                                                </div>
                                                <div>
                                                    <button type="button" class="btn btn-sm btn-danger" id="bulkDelete">
                                                        <i class="fas fa-trash me-1"></i>Delete Selected
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-secondary" id="clearSelection">
                                                        <i class="fas fa-times me-1"></i>Clear
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Categories Table -->
                            <div class="table-responsive">
                                
                                
                                <table class="table table-hover table-bordered" id="categoriesTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="30" class="text-center">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th width="60" class="text-center">Image</th>
                                            <th>Name</th>
                                            <th class="d-none d-md-table-cell">Description</th>
                                            <th width="80" class="text-center">Products</th>
                                            <th width="80" class="text-center">Status</th>
                                            <th width="80" class="text-center d-none d-lg-table-cell">Order</th>
                                            <th width="120" class="text-center d-none d-lg-table-cell">Updated</th>
                                            <th width="150" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= settings()['adminpage'] ?>assets/js/bootstrap.bundle.js"></script>
    <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Global variables
        let categoryTable;
        
        $(document).ready(function() {
            console.log('Document ready - jQuery version:', $.fn.jquery);
            console.log('customSearch element exists:', $('#customSearch').length);
            console.log('SweetAlert2 available:', typeof Swal !== 'undefined');
            console.log('Setting up delete event handlers...');
            
            // IMMEDIATE TEST: Check if delete buttons exist in DOM
            setTimeout(function() {
                const deleteButtons = $('.delete-category');
                console.log('Delete buttons found immediately:', deleteButtons.length);
                if (deleteButtons.length > 0) {
                    console.log('Delete buttons sample:', deleteButtons.first().data());
                }
            }, 1000);
            
            // Log when delete buttons are found
            $(document).on('click', function(e) {
                if ($(e.target).hasClass('delete-category') || $(e.target).closest('.delete-category').length > 0) {
                    console.log('Delete button clicked - event captured by document click handler');
                }
            });
            
            // FAILSAFE: Add direct body click listener as backup
            $('body').on('click', '.delete-category', function(e) {
                console.log('🚨 FAILSAFE: Delete button clicked via body delegation');
                // The main handler will take over from here
            });
            
            // Initialize DataTable with improved error handling
            categoryTable = $('#categoriesTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: 'category-management.php?action=get_categories',
                        type: 'GET',
                        data: function(d) {
                            // Add custom search value to data sent to server
                            d.search.value = $('#customSearch').val() || d.search.value;
                            d.status_filter = $('#statusFilter').val();
                            d.date_filter = $('#dateFilter').val();
                            d.products_filter = $('#productsFilter').val();
                            
                            console.log('DataTables AJAX data being sent:', d);
                            console.log('Custom search value:', d.search.value);
                            return d;
                        },
                        error: function(xhr, error, code) {
                            console.log('DataTable AJAX Error:', {
                                xhr: xhr,
                                error: error,
                                code: code,
                                responseText: xhr.responseText
                            });
                            
                            // Hide the default loading indicator
                            $('#categoriesTable_processing').hide();
                            
                            // Try to parse the error response
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.redirect) {
                                    // Show authentication error and redirect
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Session Expired',
                                        text: 'Your session has expired. Please log in again.',
                                        showCancelButton: true,
                                        confirmButtonText: 'Auto Login',
                                        cancelButtonText: 'Manual Login'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = 'auto-login.php';
                                        } else {
                                            window.location.href = '../login.php';
                                        }
                                    });
                                    return;
                                }
                                if (response.message) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error Loading Data',
                                        text: response.message,
                                        footer: '<a href="javascript:location.reload()">Click here to refresh</a>'
                                    });
                                    return;
                                }
                            } catch (e) {
                                // If we can't parse the response, show a generic error
                                console.log('Failed to parse error response:', e);
                            }
                            
                            // Handle different HTTP status codes
                            if (xhr.status === 401 || xhr.status === 403) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Authentication Required',
                                    text: 'Please log in to continue.',
                                    showCancelButton: true,
                                    confirmButtonText: 'Auto Login',
                                    cancelButtonText: 'Manual Login'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = 'auto-login.php';
                                    } else {
                                        window.location.href = '../login.php';
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Failed to Load Categories',
                                    text: 'There was an error loading the category data. Please try refreshing the page.',
                                    footer: '<a href="javascript:location.reload()">Click here to refresh</a>'
                                });
                            }
                        }
                    },
                columns: [
                    { data: 'checkbox', orderable: false, searchable: false },
                    { data: 'image', orderable: false, searchable: false },
                    { data: 'name' },
                    { data: 'description', orderable: false },
                    { data: 'product_count', className: 'text-center' },
                    { data: 'status' },
                    { data: 'sort_order', className: 'text-center' },
                    { data: 'updated_at' },
                    { data: 'actions', orderable: false, searchable: false }
                ],
                order: [[6, 'asc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                pagingType: "full_numbers",
                responsive: true,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6">>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                searching: true, // Enable searching but hide default search box
                language: {
                    processing: '<i class="fas fa-spinner fa-spin"></i> ',
                    emptyTable: 'No categories found',
                    info: 'Showing _START_ to _END_ of _TOTAL_ categories',
                    infoEmpty: 'Showing 0 to 0 of 0 categories',
                    infoFiltered: '(filtered from _MAX_ total categories)',
                    lengthMenu: 'Show _MENU_ categories per page',
                    search: 'Search categories:',
                    paginate: {
                        first: '<i class="fas fa-angle-double-left"></i>',
                        last: '<i class="fas fa-angle-double-right"></i>',
                        next: '<i class="fas fa-angle-right"></i>',
                        previous: '<i class="fas fa-angle-left"></i>'
                    },
                    loadingRecords: 'Loading categories...',
                    zeroRecords: 'No matching categories found'
                },
                drawCallback: function(settings) {
                    console.log('DataTable draw callback triggered');
                    console.log('Draw callback settings:', settings);
                    
                    // Update pagination info
                    updatePaginationInfo(categoryTable);
                    
                    // Re-bind event handlers for new content
                    bindTableEvents();
                    
                    // CRITICAL: Re-bind delete events after table redraw
                    console.log('🔄 Re-binding delete events after table redraw...');
                    setTimeout(function() {
                        bindDeleteEvents();
                        
                        // Verify delete buttons are present
                        const deleteButtons = $('.delete-category');
                        console.log('🗺 After redraw - Delete buttons count:', deleteButtons.length);
                        if (deleteButtons.length === 0) {
                            console.error('❌ CRITICAL: No delete buttons after table redraw!');
                        }
                    }, 100);
                    
                    // Log current page info
                    const info = categoryTable.page.info();
                    console.log('Current page info:', info);
                },
                initComplete: function() {
                    console.log('DataTable initialized successfully');
                    console.log('Search input ID: customSearch exists:', $('#customSearch').length > 0);
                    console.log('Initial table data loaded');
                    
                    // CRITICAL: Verify delete buttons are generated
                    setTimeout(function() {
                        const deleteButtons = $('.delete-category');
                        console.log('🗺 DELETE BUTTONS CHECK:');
                        console.log('- Total delete buttons found:', deleteButtons.length);
                        console.log('- Table rows:', $('#categoriesTable tbody tr').length);
                        
                        if (deleteButtons.length === 0) {
                            console.error('❌ NO DELETE BUTTONS FOUND! This is the problem.');
                            console.log('Table HTML sample:', $('#categoriesTable tbody').html().substring(0, 500));
                        } else {
                            console.log('✅ Delete buttons found successfully');
                            deleteButtons.each(function(index) {
                                const $btn = $(this);
                                console.log(`Button ${index + 1}:`, {
                                    id: $btn.data('id'),
                                    name: $btn.data('name'),
                                    classes: $btn.attr('class'),
                                    hasClickHandler: $._data($btn[0], 'events') ? 'Yes' : 'No'
                                });
                            });
                        }
                        
                        // Force re-bind events if needed
                        console.log('Re-binding delete events as safety measure...');
                        bindDeleteEvents();
                    }, 2000); // Wait 2 seconds for table to fully render
                    
                    // Load initial statistics
                    loadStats();
                    
                    // Check if we returned from editing a category
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.get('updated') === '1') {
                        const categoryId = urlParams.get('category_id');
                        const isQuickStatus = urlParams.get('quick_status') === '1';
                        console.log('Category updated detected, refreshing data for category ID:', categoryId, 'Quick status:', isQuickStatus);
                        
                        // Force refresh table and stats
                        setTimeout(() => {
                            categoryTable.ajax.reload(function(json) {
                                console.log('Table refreshed after category update');
                                loadStats();
                                
                                // Show appropriate success notification
                                const notificationConfig = {
                                    icon: 'success',
                                    timer: 3000,
                                    showConfirmButton: false,
                                    position: 'top-end',
                                    toast: true
                                };
                                
                                if (isQuickStatus) {
                                    notificationConfig.title = 'Status Updated!';
                                    notificationConfig.text = 'Category status changed successfully. Table and statistics refreshed.';
                                } else {
                                    notificationConfig.title = 'Category Updated!';
                                    notificationConfig.text = 'The category has been updated successfully and data has been refreshed.';
                                }
                                
                                Swal.fire(notificationConfig);
                                
                                // Clean URL
                                const newUrl = window.location.pathname;
                                window.history.replaceState({}, document.title, newUrl);
                            }, false);
                        }, 500); // Small delay to ensure everything is loaded
                    }
                    
                    // Additional debug info for statistics
                    console.log('=== INITIAL STATISTICS CHECK ===');
                    console.log('Total categories element value:', $('#totalCategories').text());
                    console.log('Active categories element value:', $('#activeCategories').text());
                    console.log('Inactive categories element value:', $('#inactiveCategories').text());
                    console.log('Categories with products element value:', $('#categoriesWithProducts').text());
                    
                    // Force load fresh statistics
                    setTimeout(() => {
                        console.log('Loading fresh statistics after page load...');
                        loadStats();
                    }, 1000);
                }
            });
            
            // Check if DataTable initialization was successful
            if (!categoryTable || !categoryTable.hasOwnProperty('ajax')) {
                console.error('DataTable initialization failed');
                Swal.fire({
                    icon: 'error',
                    title: 'Initialization Failed',
                    text: 'Failed to initialize the category table. Please refresh the page.',
                    confirmButtonText: 'Refresh Page'
                }).then(() => {
                    location.reload();
                });
                return;
            }
            
            console.log('DataTable initialized successfully:', categoryTable);

            // Custom search functionality - with multiple event bindings for reliability
            $(document).on('keyup', '#customSearch', function() {
                var searchValue = this.value;
                console.log('Custom search triggered (delegated):', searchValue);
                if (categoryTable) {
                    categoryTable.search(searchValue).draw();
                } else {
                    console.error('categoryTable not initialized');
                }
            });
            
            // Custom search functionality with better event handling
            $('#customSearch').on('keyup input paste change', function() {
                var searchValue = this.value;
                console.log('Custom search triggered (direct):', searchValue);
                console.log('Event fired on element:', this);
                console.log('DataTable instance available:', !!categoryTable);
                
                if (categoryTable) {
                    console.log('Applying search:', searchValue);
                    categoryTable.search(searchValue).draw();
                    console.log('Search applied and table redrawn');
                } else {
                    console.error('categoryTable not initialized');
                }
            });
            
            // Delegated event as backup
            $(document).on('keyup input paste change', '#customSearch', function() {
                var searchValue = this.value;
                console.log('Custom search triggered (delegated):', searchValue);
                if (categoryTable) {
                    categoryTable.search(searchValue).draw();
                }
            });
            
            // Clear search button - with multiple event bindings
            $(document).on('click', '#clearSearch', function() {
                console.log('Clear search clicked (delegated)');
                $('#customSearch').val('');
                if (categoryTable) {
                    categoryTable.search('').draw();
                    showToast('info', 'Search cleared');
                } else {
                    console.error('categoryTable not initialized');
                }
            });
            
            $('#clearSearch').click(function() {
                console.log('Clear search clicked (direct)');
                $('#customSearch').val('');
                if (categoryTable) {
                    categoryTable.search('').draw();
                    showToast('info', 'Search cleared');
                } else {
                    console.error('categoryTable not initialized');
                }
            });
            
            // Test search functionality
            $('#testSearch').click(function() {
                console.log('=== SEARCH DEBUG TEST ===');
                console.log('customSearch element:', $('#customSearch'));
                console.log('customSearch value:', $('#customSearch').val());
                console.log('categoryTable object:', categoryTable);
                console.log('DataTable search function available:', typeof categoryTable.search);
                
                if (categoryTable) {
                    console.log('Current search value:', categoryTable.search());
                    console.log('Testing search with "test"');
                    $('#customSearch').val('test');
                    categoryTable.search('test').draw();
                    showToast('info', 'Search test: Searching for "test"');
                    
                    // Test if search actually affects the results
                    setTimeout(() => {
                        console.log('Search value after test:', categoryTable.search());
                        console.log('Clearing test search');
                        $('#customSearch').val('');
                        categoryTable.search('').draw();
                        showToast('info', 'Search test completed');
                    }, 3000);
                } else {
                    console.error('categoryTable is not available');
                    showToast('error', 'DataTable not initialized');
                }
                
                // Test direct AJAX call
                console.log('Testing direct AJAX call...');
                $.ajax({
                    url: 'category-management.php?action=get_categories',
                    type: 'GET',
                    data: {
                        draw: 1,
                        start: 0,
                        length: 10,
                        search: { value: 'test' }
                    },
                    success: function(response) {
                        console.log('Direct AJAX search response:', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Direct AJAX search error:', error);
                    }
                });
            });
            
            // Export functionality
            $('#exportCsv').click(function() {
                exportData('csv');
            });
            
            $('#exportExcel').click(function() {
                exportData('excel');
            });

            // Start auto-refresh if enabled
            if ($('#autoRefreshStats').is(':checked')) {
                startStatsAutoRefresh();
            }
            
            // Auto-refresh toggle
            $('#autoRefreshStats').change(function() {
                if (this.checked) {
                    startStatsAutoRefresh();
                    showToast('info', 'Auto-refresh enabled (30s intervals)');
                } else {
                    stopStatsAutoRefresh();
                    showToast('info', 'Auto-refresh disabled');
                }
            });
            
            // Initialize enhanced pagination
            initializePaginationEnhancements();
            
            // Refresh table button
            $('#refreshTable').click(function() {
                categoryTable.ajax.reload(null, false);
                showToast('info', 'Table refreshed');
            });
            
            // Quick page jump
            $('#quickJumpBtn, #pageJumpBtn').click(function() {
                const pageInput = $(this).attr('id') === 'quickJumpBtn' ? '#quickPageJump' : '#pageJumpInput';
                const pageNum = parseInt($(pageInput).val());
                const totalPages = categoryTable.page.info().pages;
                
                if (pageNum >= 1 && pageNum <= totalPages) {
                    categoryTable.page(pageNum - 1).draw('page');
                    $(pageInput).val('');
                    showToast('success', `Jumped to page ${pageNum}`);
                } else {
                    showToast('warning', `Please enter a page number between 1 and ${totalPages}`);
                }
            });
            
            // Enter key for page jump
            $('#quickPageJump, #pageJumpInput').keypress(function(e) {
                if (e.which === 13) {
                    const btnId = $(this).attr('id') === 'quickPageJump' ? '#quickJumpBtn' : '#pageJumpBtn';
                    $(btnId).click();
                }
            });
            
            // Pagination help
            $('#paginationHelp').click(function() {
                Swal.fire({
                    title: 'Pagination Shortcuts',
                    html: `
                        <div class="text-start">
                            <h6>Keyboard Shortcuts:</h6>
                            <ul class="list-unstyled">
                                <li><kbd>Ctrl</kbd> + <kbd>←</kbd> - Previous page</li>
                                <li><kbd>Ctrl</kbd> + <kbd>→</kbd> - Next page</li>
                                <li><kbd>Ctrl</kbd> + <kbd>R</kbd> - Refresh table</li>
                                <li><kbd>Enter</kbd> - Execute page jump</li>
                            </ul>
                            <h6>Features:</h6>
                            <ul class="list-unstyled">
                                <li>• Use the dropdown to change items per page</li>
                                <li>• Click page numbers to navigate</li>
                                <li>• Use search to filter categories</li>
                                <li>• Select rows for bulk operations</li>
                            </ul>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Got it!',
                    width: 500
                });
            });
            
            // Status filter change
            $('#statusFilter, #dateFilter, #productsFilter').change(function() {
                console.log('Filter changed:', this.id, this.value);
                categoryTable.ajax.reload(null, false);
                loadStats(); // Refresh stats when filters change
                const filterName = $(this).find('option:selected').text();
                if ($(this).val()) {
                    showToast('info', `Filter applied: ${filterName}`);
                }
            });
            
            // Clear all filters
            $('#clearFilters').click(function() {
                console.log('Clearing all filters');
                $('#statusFilter').val('');
                $('#dateFilter').val('');
                $('#productsFilter').val('');
                $('#customSearch').val('');
                categoryTable.search('').ajax.reload(null, false);
                loadStats();
                showToast('info', 'All filters cleared');
            });
            
            // Refresh table button
            $('#refreshTable').click(function() {
                console.log('Manual table refresh triggered');
                categoryTable.ajax.reload(null, false);
                loadStats();
                showToast('info', 'Table and statistics refreshed');
            });
            
            // Add debug function for troubleshooting
            window.debugCategoryTable = function() {
                console.log('=== CATEGORY TABLE DEBUG ===');
                console.log('Table instance:', categoryTable);
                console.log('Table settings:', categoryTable.settings());
                console.log('Current search:', categoryTable.search());
                console.log('Current page info:', categoryTable.page.info());
                console.log('Applied filters:', {
                    status: $('#statusFilter').val(),
                    date: $('#dateFilter').val(),
                    products: $('#productsFilter').val(),
                    search: $('#customSearch').val()
                });
                console.log('Stats auto-refresh:', !!statsInterval);
                
                // Test AJAX endpoints
                console.log('Testing AJAX endpoints...');
                
                // Test categories endpoint
                $.ajax({
                    url: 'category-management.php?action=get_categories',
                    type: 'GET',
                    data: {
                        draw: 1,
                        start: 0,
                        length: 5,
                        search: { value: '' }
                    },
                    success: function(response) {
                        console.log('✅ Categories endpoint working:', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Categories endpoint error:', error, xhr.responseText);
                    }
                });
                
                // Test stats endpoint
                $.ajax({
                    url: 'category-management.php?action=get_stats',
                    type: 'GET',
                    success: function(response) {
                        console.log('✅ Stats endpoint working:', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Stats endpoint error:', error, xhr.responseText);
                    }
                });
                
                // Test delete endpoint with fake ID
                $.ajax({
                    url: 'category-management.php?action=bulk_delete',
                    type: 'POST',
                    data: { ids: [99999] },
                    success: function(response) {
                        console.log('✅ Delete endpoint working:', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Delete endpoint error:', error, xhr.responseText);
                    }
                });
                
                // Test event handlers
                const events = $._data(document, 'events');
                if (events && events.click) {
                    const deleteHandlers = events.click.filter(event => 
                        event.selector && event.selector.includes('delete-category')
                    );
                    console.log('✅ Delete event handlers found:', deleteHandlers.length);
                } else {
                    console.log('❌ No click event handlers found on document');
                }
            };
            
            // Add function to test delete button functionality
            window.testDeleteButton = function(categoryId) {
                console.log('=== TESTING DELETE BUTTON ===');
                
                if (!categoryId) {
                    console.error('Please provide a category ID: testDeleteButton(123)');
                    return;
                }
                
                // Find the delete button for this category
                const $deleteButton = $(`.delete-category[data-id="${categoryId}"]`);
                
                if ($deleteButton.length === 0) {
                    console.error('Delete button not found for category ID:', categoryId);
                    console.log('Available delete buttons:', $('.delete-category').map(function() {
                        return $(this).data('id');
                    }).get());
                    return;
                }
                
                console.log('Found delete button:', $deleteButton);
                console.log('Button data:', {
                    id: $deleteButton.data('id'),
                    name: $deleteButton.data('name'),
                    disabled: $deleteButton.prop('disabled')
                });
                
                // Test click event
                console.log('Simulating click...');
                $deleteButton.click();
            };
            
            // Add function to manually test delete endpoint
            window.testDeleteEndpoint = function(categoryId, actuallyDelete = false) {
                console.log('=== TESTING DELETE ENDPOINT ===');
                
                const testId = actuallyDelete ? categoryId : 99999999; // Use fake ID unless actually deleting
                
                console.log('Testing with ID:', testId, actuallyDelete ? '(REAL DELETE)' : '(SAFE TEST)');
                
                $.ajax({
                    url: 'category-management.php?action=bulk_delete',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        ids: [testId]
                    },
                    beforeSend: function(xhr) {
                        console.log('Sending request...');
                    },
                    success: function(response) {
                        console.log('✅ Endpoint responded successfully:', response);
                        if (actuallyDelete && response.success) {
                            console.log('✅ Category deleted successfully!');
                            // Reload table
                            if (categoryTable) {
                                categoryTable.ajax.reload();
                                loadStats();
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Endpoint error:', {
                            status: status,
                            error: error,
                            statusCode: xhr.status,
                            responseText: xhr.responseText
                        });
                        
                        if (xhr.status === 401 || xhr.status === 403) {
                            console.log('🔐 Authentication issue detected');
                        }
                    }
                });
            };
            
            // Add real-time status test function
            window.testStatusToggle = function(categoryId) {
                if (!categoryId) {
                    console.error('Please provide a category ID: testStatusToggle(1)');
                    return;
                }
                
                console.log('Testing status toggle for category:', categoryId);
                
                // Get current status first
                const $button = $(`.toggle-status[data-id="${categoryId}"]`);
                if ($button.length === 0) {
                    console.error('Category button not found for ID:', categoryId);
                    return;
                }
                
                const currentStatus = parseInt($button.data('status'));
                const newStatus = currentStatus ? 0 : 1;
                
                console.log(`Current status: ${currentStatus}, will change to: ${newStatus}`);
                
                // Simulate the toggle
                $.ajax({
                    url: 'category-management.php?action=toggle_status',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: categoryId,
                        status: newStatus
                    },
                    success: function(response) {
                        console.log('✅ Status toggle successful:', response);
                        
                        // Reload table and stats
                        categoryTable.ajax.reload(function() {
                            console.log('✅ Table reloaded');
                            loadStats();
                        }, false);
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Status toggle failed:', error, xhr.responseText);
                    }
                });
            };
            
            // Export data function
            function exportData(format) {
                const currentFilters = {
                    search: categoryTable.search(),
                    status_filter: $('#statusFilter').val(),
                    date_filter: $('#dateFilter').val(),
                    products_filter: $('#productsFilter').val()
                };
                
                // Build export URL with current filters
                const params = new URLSearchParams(currentFilters);
                params.append('action', 'export');
                params.append('format', format);
                
                const exportUrl = `category-management.php?${params.toString()}`;
                
                // Create temporary link and trigger download
                const link = document.createElement('a');
                link.href = exportUrl;
                link.download = `categories_${new Date().toISOString().split('T')[0]}.${format}`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showToast('success', `Export ${format.toUpperCase()} started`);
            }
            
            // Select all checkbox
            $('#selectAll').change(function() {
                $('.category-checkbox').prop('checked', this.checked);
                updateBulkActions();
            });
            
            // Individual checkbox change
            $(document).on('change', '.category-checkbox', function() {
                updateBulkActions();
            });
            
            // Toggle status
            $(document).on('click', '.toggle-status', function(e) {
                e.preventDefault();
                const $button = $(this);
                const id = $button.data('id');
                const currentStatus = parseInt($button.data('status'));
                const newStatus = currentStatus ? 0 : 1;
                const statusText = newStatus ? 'activate' : 'deactivate';
                
                console.log('Status toggle clicked:', {
                    id: id,
                    currentStatus: currentStatus,
                    newStatus: newStatus,
                    statusText: statusText
                });
                
                // Disable button during request
                $button.prop('disabled', true);
                
                // Show confirmation
                Swal.fire({
                    title: 'Confirm Status Change',
                    text: `Are you sure you want to ${statusText} this category?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: `Yes, ${statusText} it!`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Updating...',
                            text: 'Please wait while we update the category status.',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            willOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // Make AJAX request
                        $.ajax({
                            url: 'category-management.php?action=toggle_status',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                id: id,
                                status: newStatus
                            },
                            success: function(response) {
                                console.log('Status toggle response:', response);
                                
                                if (response.success) {
                                    // Update button attributes and appearance
                                    $button.data('status', response.new_status || newStatus);
                                    
                                    const icon = $button.find('i');
                                    if (response.new_status || newStatus) {
                                        icon.removeClass('fa-toggle-off').addClass('fa-toggle-on');
                                        $button.removeClass('btn-outline-secondary').addClass('btn-outline-success');
                                    } else {
                                        icon.removeClass('fa-toggle-on').addClass('fa-toggle-off');
                                        $button.removeClass('btn-outline-success').addClass('btn-outline-secondary');
                                    }
                                    
                                    // Force table reload to reflect changes
                                    console.log('Reloading table data...');
                                    categoryTable.ajax.reload(function(json) {
                                        console.log('Table reloaded:', json);
                                        // Load updated statistics
                                        loadStats();
                                    }, false); // false = keep current page
                                    
                                    // Close loading and show success
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: response.message || `Category ${statusText}d successfully`,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message || 'Failed to update status'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Status toggle error:', {
                                    xhr: xhr,
                                    status: status,
                                    error: error,
                                    responseText: xhr.responseText
                                });
                                
                                let errorMessage = 'Error updating category status';
                                if (xhr.status === 403) {
                                    errorMessage = 'Access denied. Please refresh the page and try again.';
                                } else if (xhr.status === 404) {
                                    errorMessage = 'Category not found.';
                                } else if (xhr.responseText) {
                                    try {
                                        const errorResponse = JSON.parse(xhr.responseText);
                                        errorMessage = errorResponse.message || errorMessage;
                                    } catch (e) {
                                        console.error('Failed to parse error response:', e);
                                    }
                                }
                                
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: errorMessage
                                });
                            },
                            complete: function() {
                                // Re-enable button
                                $button.prop('disabled', false);
                            }
                        });
                    } else {
                        // Re-enable button if cancelled
                        $button.prop('disabled', false);
                    }
                });
            });
            
            // ESSENTIAL: Separate function to bind delete events
            function bindDeleteEvents() {
                console.log('🔗 Binding delete events...');
                
                // Remove any existing handlers to prevent duplicates
                $(document).off('click', '.delete-category');
                $('body').off('click', '.delete-category');
                
                // Primary event handler using document delegation
                $(document).on('click', '.delete-category', function(e) {
                    e.preventDefault();
                    console.log('🎯 PRIMARY: Delete button clicked!');
                    handleDeleteClick($(this));
                });
                
                // Backup handler using body delegation
                $('body').on('click', '.delete-category', function(e) {
                    console.log('🚨 BACKUP: Delete button clicked via body delegation');
                    // Primary handler should have already handled this
                });
                
                // Direct handler for existing buttons (if any)
                $('.delete-category').off('click').on('click', function(e) {
                    e.preventDefault();
                    console.log('📍 DIRECT: Delete button clicked via direct binding');
                    handleDeleteClick($(this));
                });
                
                console.log('✅ Delete event handlers bound successfully');
            }
            
            // CORE: The actual delete handling logic
            function handleDeleteClick($button) {
                console.log('🚀 Starting delete process...');
                
                const id = parseInt($button.data('id'));
                const name = $button.data('name') || 'Unknown Category';
                
                console.log('Delete request for:', { id, name, button: $button });
                
                // Validate ID
                if (!id || isNaN(id)) {
                    console.error('❌ Invalid category ID:', id);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Invalid category ID. Please refresh the page and try again.'
                    });
                    return;
                }
                
                // Disable button during process
                $button.prop('disabled', true);
                console.log('🔒 Button disabled for processing');
                
                Swal.fire({
                    title: 'Are you sure?',
                    html: `<p>Delete category <strong>"${name}"</strong>?</p><p class="text-danger">This action cannot be undone!</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    showLoaderOnConfirm: true,
                    allowOutsideClick: false,
                    preConfirm: () => {
                        console.log('👤 User confirmed delete, making AJAX request...');
                        
                        return $.ajax({
                            url: 'category-management.php?action=bulk_delete',
                            type: 'POST',
                            dataType: 'json',
                            timeout: 10000, // 10 second timeout
                            data: {
                                ids: [id]
                            },
                            beforeSend: function(xhr) {
                                console.log('📤 Sending delete request for category ID:', id);
                            }
                        }).then(response => {
                            console.log('📥 Delete response received:', response);
                            
                            if (!response || typeof response !== 'object') {
                                throw new Error('Invalid response format from server');
                            }
                            
                            if (!response.success) {
                                throw new Error(response.message || 'Failed to delete category');
                            }
                            
                            return response;
                        }).catch(error => {
                            console.error('❌ Delete error details:', {
                                error: error,
                                message: error.message,
                                status: error.status,
                                responseText: error.responseText
                            });
                            
                            // Handle authentication errors
                            if (error.status === 401 || error.status === 403) {
                                console.log('🔐 Authentication error detected');
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Authentication Required',
                                    text: 'Your session has expired. Please login again to delete categories.',
                                    showCancelButton: true,
                                    confirmButtonText: 'Auto Login',
                                    cancelButtonText: 'Manual Login',
                                    allowOutsideClick: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = 'auto-login.php';
                                    } else {
                                        window.location.href = '../login.php';
                                    }
                                });
                                return;
                            }
                            
                            // Handle network errors
                            if (error.status === 0) {
                                Swal.showValidationMessage('Network error: Unable to connect to server');
                                return;
                            }
                            
                            if (error.statusText === 'timeout') {
                                Swal.showValidationMessage('Request timed out. Please try again.');
                                return;
                            }
                            
                            // Parse error response
                            let errorMessage = error.message || 'Unknown error occurred';
                            if (error.responseText) {
                                try {
                                    const errorResponse = JSON.parse(error.responseText);
                                    if (errorResponse.message) {
                                        errorMessage = errorResponse.message;
                                    }
                                    if (errorResponse.redirect) {
                                        console.log('Server requested redirect to:', errorResponse.redirect);
                                        window.location.href = errorResponse.redirect;
                                        return;
                                    }
                                } catch (parseError) {
                                    console.error('Failed to parse error response:', parseError);
                                }
                            }
                            
                            Swal.showValidationMessage(`Request failed: ${errorMessage}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    console.log('🎆 SweetAlert result:', {
                        isConfirmed: result.isConfirmed,
                        isDismissed: result.isDismissed,
                        hasValue: !!result.value
                    });
                    
                    // Re-enable button
                    $button.prop('disabled', false);
                    console.log('🔓 Button re-enabled');
                    
                    if (result.isConfirmed && result.value && result.value.success) {
                        console.log('✅ Delete successful, refreshing table...');
                        
                        // Reload table and stats
                        if (categoryTable && typeof categoryTable.ajax !== 'undefined') {
                            categoryTable.ajax.reload(function(json) {
                                console.log('♾️ Table reloaded successfully:', json);
                                loadStats();
                                // Re-bind events after table reload
                                setTimeout(bindDeleteEvents, 500);
                            }, false);
                        } else {
                            console.error('❌ categoryTable not available, reloading page');
                            location.reload();
                        }
                        
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: result.value.message || 'Category has been deleted successfully.',
                            timer: 3000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    } else if (result.isDismissed) {
                        console.log('❌ Delete cancelled by user');
                    }
                }).catch(error => {
                    console.error('💥 SweetAlert error:', error);
                    $button.prop('disabled', false);
                    
                    // Fallback error handling
                    Swal.fire({
                        icon: 'error',
                        title: 'Unexpected Error',
                        text: 'An unexpected error occurred. Please try refreshing the page.',
                        confirmButtonText: 'Refresh Page'
                    }).then(() => {
                        location.reload();
                    });
                });
            }
            
            // CALL: Initial binding
            bindDeleteEvents();
            
            // Add debugging functions to window for easy testing
            window.forceBindDeleteEvents = function() {
                console.log('🔧 Manually forcing delete event binding...');
                bindDeleteEvents();
                
                // Test that events are bound
                setTimeout(function() {
                    const deleteButtons = $('.delete-category');
                    console.log('Delete buttons after manual binding:', deleteButtons.length);
                    
                    if (deleteButtons.length > 0) {
                        console.log('Testing first delete button...');
                        const $firstBtn = deleteButtons.first();
                        console.log('First button data:', $firstBtn.data());
                    }
                }, 100);
            };
            
            window.inspectTable = function() {
                console.log('=== TABLE INSPECTION ===');
                console.log('Table element:', $('#categoriesTable'));
                console.log('Table body HTML (first 1000 chars):', $('#categoriesTable tbody').html().substring(0, 1000));
                console.log('All buttons in table:', $('#categoriesTable button').length);
                console.log('Delete buttons specifically:', $('#categoriesTable .delete-category').length);
                console.log('Toggle buttons:', $('#categoriesTable .toggle-status').length);
                
                // Check each action cell
                $('#categoriesTable tbody tr').each(function(index) {
                    const $row = $(this);
                    const $actionCell = $row.find('td:last'); // Last cell should be actions
                    const deleteBtn = $actionCell.find('.delete-category');
                    console.log(`Row ${index + 1} delete button:`, deleteBtn.length > 0 ? deleteBtn.data() : 'NOT FOUND');
                });
            };
            
            window.quickDeleteTest = function() {
                console.log('=== QUICK DELETE TEST ===');
                
                // Find first available delete button
                const $deleteBtn = $('.delete-category').first();
                if ($deleteBtn.length === 0) {
                    console.error('❌ No delete buttons found in DOM');
                    return;
                }
                
                console.log('✅ Found delete button:', $deleteBtn.data());
                console.log('Triggering click event...');
                $deleteBtn.trigger('click');
            };
            
            // Bulk delete
            $('#bulkDelete').click(function() {
                const selectedIds = $('.category-checkbox:checked').map(function() {
                    return this.value;
                }).get();
                
                if (selectedIds.length === 0) {
                    showToast('warning', 'Please select categories to delete');
                    return;
                }
                
                Swal.fire({
                    title: 'Delete Selected Categories?',
                    text: `This will delete ${selectedIds.length} selected categories. This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('category-management.php?action=bulk_delete', {
                            ids: selectedIds
                        }, function(response) {
                            categoryTable.ajax.reload();
                            loadStats();
                            clearSelection();
                            showToast(response.success ? 'success' : 'warning', response.message);
                        }, 'json');
                    }
                });
            });
            
            // Clear selection
            $('#clearSelection').click(function() {
                clearSelection();
            });
            
            function updatePaginationInfo(table) {
                const info = table.page.info();
                const totalPages = info.pages;
                const currentPage = info.page + 1;
                
                // Add custom pagination info if needed
                if ($('#customPaginationInfo').length === 0) {
                    $('.dataTables_info').after(
                        '<div id="customPaginationInfo" class="mt-2 text-muted small">' +
                        'Page ' + currentPage + ' of ' + totalPages + 
                        ' • Total Records: ' + info.recordsTotal +
                        '</div>'
                    );
                } else {
                    $('#customPaginationInfo').html(
                        'Page ' + currentPage + ' of ' + totalPages + 
                        ' • Total Records: ' + info.recordsTotal
                    );
                }
            }
            
            function bindTableEvents() {
                // Rebind events that may be lost after table redraw
                $('.category-checkbox').off('change').on('change', function() {
                    updateBulkActions();
                });
            }
            
            // Quick page jump functionality
            function addPageJump() {
                if ($('#pageJumpContainer').length === 0) {
                    $('.dataTables_paginate').after(
                        '<div id="pageJumpContainer" class="mt-2">' +
                        '<div class="input-group input-group-sm" style="width: 200px;">' +
                        '<span class="input-group-text">Go to page:</span>' +
                        '<input type="number" id="pageJumpInput" class="form-control" min="1" placeholder="Page">' +
                        '<button class="btn btn-outline-secondary" id="pageJumpBtn">Go</button>' +
                        '</div>' +
                        '</div>'
                    );
                    
                    // Handle page jump
                    $('#pageJumpBtn').click(function() {
                        const pageNum = parseInt($('#pageJumpInput').val());
                        const totalPages = categoryTable.page.info().pages;
                        
                        if (pageNum >= 1 && pageNum <= totalPages) {
                            categoryTable.page(pageNum - 1).draw('page');
                            $('#pageJumpInput').val('');
                        } else {
                            showToast('warning', `Please enter a page number between 1 and ${totalPages}`);
                        }
                    });
                    
                    // Handle enter key
                    $('#pageJumpInput').keypress(function(e) {
                        if (e.which === 13) {
                            $('#pageJumpBtn').click();
                        }
                    });
                }
            }
            
            function updateBulkActions() {
                const selectedCount = $('.category-checkbox:checked').length;
                $('#selectedCount').text(selectedCount);
                
                if (selectedCount > 0) {
                    $('#bulkActionsBar').show();
                } else {
                    $('#bulkActionsBar').hide();
                }
            }
            
            function clearSelection() {
                $('.category-checkbox, #selectAll').prop('checked', false);
                updateBulkActions();
            }
            
            function initializePaginationEnhancements() {
                // Show pagination summary after table is loaded
                categoryTable.on('draw.dt', function() {
                    const info = categoryTable.page.info();
                    
                    // Update records info
                    let recordsText = '';
                    if (info.recordsTotal === 0) {
                        recordsText = 'No categories found';
                    } else if (info.recordsDisplay === info.recordsTotal) {
                        recordsText = `Showing ${info.recordsTotal} categories`;
                    } else {
                        recordsText = `Showing ${info.start + 1} to ${info.end} of ${info.recordsDisplay} categories (filtered from ${info.recordsTotal} total)`;
                    }
                    
                    $('#recordsInfo').text(recordsText);
                    
                    // Update page jump max value
                    $('#quickPageJump').attr('max', info.pages);
                    
                    // Show pagination summary
                    if (info.recordsTotal > 0) {
                        $('#paginationSummary').show();
                    } else {
                        $('#paginationSummary').hide();
                    }
                    
                    // Update pagination info
                    if (categoryTable && typeof categoryTable.page === 'function') {
                        updatePaginationInfo(categoryTable);
                        switch(e.which) {
                            case 37: // Ctrl + Left Arrow - Previous page
                                e.preventDefault();
                                if (categoryTable.page.info().page > 0) {
                                    categoryTable.page('previous').draw('page');
                                    showToast('info', 'Previous page');
                                }
                                break;
                            case 39: // Ctrl + Right Arrow - Next page
                                e.preventDefault();
                                const info = categoryTable.page.info();
                                if (info.page < info.pages - 1) {
                                    categoryTable.page('next').draw('page');
                                    showToast('info', 'Next page');
                                }
                                break;
                            case 82: // Ctrl + R - Refresh
                                e.preventDefault();
                                categoryTable.ajax.reload(null, false);
                                showToast('info', 'Table refreshed');
                                break;
                        }
                    }
                });
            }
            
            function loadStats() {
                console.log('Loading statistics...');
                
                // Show loading indicators
                $('#totalCategoriesLoader, #activeCategoriesLoader, #inactiveCategoriesLoader, #categoriesWithProductsLoader').removeClass('d-none');
                
                // Add updating class for animation
                $('.stats-card').addClass('updating');
                
                $.ajax({
                    url: 'category-management.php?action=get_stats',
                    type: 'GET',
                    dataType: 'json',
                    timeout: 10000, // 10 second timeout
                    cache: false, // Prevent caching
                    success: function(response) {
                        console.log('Statistics response:', response);
                        if (response.success && response.data) {
                            const data = response.data;
                            const previousData = {
                                total: parseInt($('#totalCategories').text()) || 0,
                                active: parseInt($('#activeCategories').text()) || 0,
                                inactive: parseInt($('#inactiveCategories').text()) || 0,
                                withProducts: parseInt($('#categoriesWithProducts').text()) || 0
                            };
                            
                            console.log('Previous stats:', previousData);
                            console.log('New stats:', {
                                total: data.total_categories,
                                active: data.active_categories,
                                inactive: data.inactive_categories,
                                withProducts: data.categories_with_products
                            });
                            
                            // Update the statistics with animation
                            animateCounterUpdate('#totalCategories', previousData.total, data.total_categories);
                            animateCounterUpdate('#activeCategories', previousData.active, data.active_categories);
                            animateCounterUpdate('#inactiveCategories', previousData.inactive, data.inactive_categories);
                            animateCounterUpdate('#categoriesWithProducts', previousData.withProducts, data.categories_with_products);
                            
                            // Show changes
                            showStatChange('#totalCategoriesChange', previousData.total, data.total_categories);
                            showStatChange('#activeCategoriesChange', previousData.active, data.active_categories);
                            showStatChange('#inactiveCategoriesChange', previousData.inactive, data.inactive_categories);
                            showStatChange('#categoriesWithProductsChange', previousData.withProducts, data.categories_with_products);
                            
                            // Update percentages in footer if they exist
                            if (data.percentages) {
                                updatePercentages(data.percentages);
                            }
                            
                            // Show update banner if stats changed
                            if (isStatsChanged(previousData, data)) {
                                $('#statsUpdateBanner').removeClass('d-none').addClass('show');
                                showToast('info', 'Statistics updated successfully');
                                setTimeout(() => {
                                    $('#statsUpdateBanner').addClass('d-none').removeClass('show');
                                }, 5000);
                            }
                            
                            console.log('Statistics updated successfully');
                        } else {
                            console.error('Invalid statistics response:', response);
                            showToast('error', response.message || 'Invalid statistics response');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Statistics AJAX error:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText,
                            statusCode: xhr.status
                        });
                        
                        let errorMessage = 'Error loading statistics';
                        if (status === 'timeout') {
                            errorMessage = 'Statistics request timed out - trying again...';
                            // Auto-retry after timeout
                            setTimeout(() => {
                                loadStats();
                            }, 3000);
                        } else if (xhr.status === 403) {
                            errorMessage = 'Access denied. Please refresh the page.';
                        } else if (xhr.status === 401) {
                            errorMessage = 'Authentication required. Redirecting to login...';
                            // Show authentication dialog with options
                            Swal.fire({
                                title: 'Session Expired',
                                text: 'Your session has expired. Please login again.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Auto-Login',
                                cancelButtonText: 'Manual Login',
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'auto-login.php';
                                } else {
                                    window.location.href = '../login.php';
                                }
                            });
                            return;
                        } else if (xhr.responseText) {
                            try {
                                const errorResponse = JSON.parse(xhr.responseText);
                                if (errorResponse.redirect) {
                                    // Auto-redirect for authentication issues
                                    if (errorResponse.message.includes('Authentication')) {
                                        Swal.fire({
                                            title: 'Authentication Required',
                                            text: 'Please login to continue viewing statistics.',
                                            icon: 'info',
                                            confirmButtonText: 'Login Now'
                                        }).then(() => {
                                            window.location.href = errorResponse.redirect;
                                        });
                                        return;
                                    }
                                    window.location.href = errorResponse.redirect;
                                    return;
                                }
                                errorMessage = errorResponse.message || errorMessage;
                            } catch (e) {
                                console.error('Failed to parse error response:', e);
                            }
                        }
                        
                        showToast('error', errorMessage);
                        
                        // Fallback: Load static statistics from page
                        console.log('Loading fallback statistics from page elements');
                        const fallbackStats = {
                            total_categories: parseInt($('#totalCategories').text()) || 0,
                            active_categories: parseInt($('#activeCategories').text()) || 0,
                            inactive_categories: parseInt($('#inactiveCategories').text()) || 0,
                            categories_with_products: parseInt($('#categoriesWithProducts').text()) || 0
                        };
                        console.log('Fallback stats loaded:', fallbackStats);
                    },
                    complete: function() {
                        // Hide loading indicators
                        $('#totalCategoriesLoader, #activeCategoriesLoader, #inactiveCategoriesLoader, #categoriesWithProductsLoader').addClass('d-none');
                        
                        // Remove updating class
                        $('.stats-card').removeClass('updating');
                    }
                });
            }
            
            function animateCounterUpdate(selector, from, to) {
                if (from === to) return;
                
                const element = $(selector);
                const duration = 1000;
                const steps = 30;
                const stepValue = (to - from) / steps;
                let current = from;
                let step = 0;
                
                const timer = setInterval(() => {
                    step++;
                    current += stepValue;
                    
                    if (step >= steps) {
                        element.text(to);
                        clearInterval(timer);
                    } else {
                        element.text(Math.round(current));
                    }
                }, duration / steps);
            }
            
            function showStatChange(selector, oldValue, newValue) {
                const element = $(selector);
                const change = newValue - oldValue;
                
                if (change !== 0) {
                    const icon = change > 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                    const color = change > 0 ? 'text-success' : 'text-warning';
                    const sign = change > 0 ? '+' : '';
                    
                    element.html(`<i class="fas ${icon}"></i> ${sign}${change}`);
                    element.removeClass('text-success text-warning text-danger').addClass(color);
                    
                    // Fade out after 3 seconds
                    setTimeout(() => {
                        element.fadeOut(500, () => {
                            element.empty().show();
                        });
                    }, 3000);
                } else {
                    element.empty();
                }
            }
            
            function updatePercentages(percentages) {
                // Update footer percentages if the structure supports it
                $('.stats-card').each(function() {
                    const card = $(this);
                    const footer = card.find('.card-footer small:last-child');
                    
                    if (card.find('#activeCategories').length) {
                        footer.text(percentages.active + '%');
                    } else if (card.find('#inactiveCategories').length) {
                        footer.text(percentages.inactive + '%');
                    } else if (card.find('#categoriesWithProducts').length) {
                        footer.text(percentages.with_products + '%');
                    }
                });
            }
            
            function isStatsChanged(previous, current) {
                return previous.total !== current.total_categories ||
                       previous.active !== current.active_categories ||
                       previous.inactive !== current.inactive_categories ||
                       previous.withProducts !== current.categories_with_products;
            }
            
            // Auto-refresh statistics every 5 seconds
            let statsInterval;
            function startStatsAutoRefresh() {
                stopStatsAutoRefresh(); // Clear any existing interval

                // Trigger an immediate refresh for up-to-date data
                loadStats();

                statsInterval = setInterval(() => {
                    loadStats();
                }, 5000); // 5 seconds
            }
            
            function stopStatsAutoRefresh() {
                if (statsInterval) {
                    clearInterval(statsInterval);
                    statsInterval = null;
                }
            }
            
            // Manual refresh button
            $(document).on('click', '.refresh-stats', function() {
                console.log('Manual statistics refresh triggered');
                loadStats();
                showToast('info', 'Statistics refresh requested...');
            });
            
            // Debug statistics function
            window.debugStats = function() {
                console.log('=== STATISTICS DEBUG ===');
                console.log('Current displayed values:');
                console.log('Total:', $('#totalCategories').text());
                console.log('Active:', $('#activeCategories').text());
                console.log('Inactive:', $('#inactiveCategories').text());
                console.log('With Products:', $('#categoriesWithProducts').text());
                
                console.log('Testing direct AJAX call...');
                $.ajax({
                    url: 'category-management.php?action=get_stats',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Direct AJAX success:', response);
                        if (response.success) {
                            console.log('New values from server:', response.data);
                            showToast('success', 'Debug: Statistics loaded successfully');
                        } else {
                            console.log('Server returned error:', response.message);
                            showToast('error', 'Debug: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Direct AJAX error:', { status, error, response: xhr.responseText });
                        showToast('error', 'Debug: AJAX failed - ' + status);
                    }
                });
            };
            
            // Force update statistics from database
            window.forceUpdateStats = function() {
                console.log('Force updating statistics...');
                
                // Show loading state
                $('.stats-card').addClass('updating');
                $('#totalCategoriesLoader, #activeCategoriesLoader, #inactiveCategoriesLoader, #categoriesWithProductsLoader').removeClass('d-none');
                
                // Make direct call to get fresh data
                $.ajax({
                    url: 'category-management.php?action=get_stats',
                    type: 'GET',
                    dataType: 'json',
                    cache: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    },
                    success: function(response) {
                        console.log('Force update response:', response);
                        if (response.success && response.data) {
                            // Directly update the values without animation for immediate results
                            $('#totalCategories').text(response.data.total_categories);
                            $('#activeCategories').text(response.data.active_categories);
                            $('#inactiveCategories').text(response.data.inactive_categories);
                            $('#categoriesWithProducts').text(response.data.categories_with_products);
                            
                            // Update percentages
                            if (response.data.percentages) {
                                updatePercentages(response.data.percentages);
                            }
                            
                            showToast('success', 'Statistics force updated successfully!');
                            
                            // Show update banner
                            $('#statsUpdateBanner').removeClass('d-none').addClass('show');
                            setTimeout(() => {
                                $('#statsUpdateBanner').addClass('d-none').removeClass('show');
                            }, 3000);
                        } else {
                            showToast('error', 'Force update failed: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Force update error:', { status, error, response: xhr.responseText });
                        showToast('error', 'Force update failed: ' + status);
                    },
                    complete: function() {
                        // Hide loading state
                        $('.stats-card').removeClass('updating');
                        $('#totalCategoriesLoader, #activeCategoriesLoader, #inactiveCategoriesLoader, #categoriesWithProductsLoader').addClass('d-none');
                    }
                });
            };
            
            function showToast(type, message) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
                
                Toast.fire({
                    icon: type,
                    title: message
                });
            }

            // Auto-hide alerts
            $('.alert').delay(5000).fadeOut('slow');
            
            // Ensure categories menu is expanded if on category page
            const categoriesCollapse = document.getElementById('collapseCategories');
            if (categoriesCollapse) {
                // Show the categories collapse section
                categoriesCollapse.classList.add('show');
                
                // Also ensure the parent link shows it's expanded
                const parentLink = document.querySelector('[data-bs-target="#collapseCategories"]');
                if (parentLink) {
                    parentLink.classList.remove('collapsed');
                    parentLink.setAttribute('aria-expanded', 'true');
                }
            }
            
            // Start real-time statistics auto-refresh
            console.log('Starting real-time statistics auto-refresh...');
            startStatsAutoRefresh();
            
            // Show live status indicator
            $('#liveStatusIndicator').removeClass('d-none');
            
            // Add click handlers for manual refresh buttons on cards
            $(document).on('click', '.stats-card', function() {
                console.log('Manual stats refresh triggered from card click');
                loadStats();
                showToast('info', 'Statistics refresh requested...');
            });
            
            // Page visibility API to pause/resume auto-refresh when tab is not active
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    console.log('Page hidden - pausing auto-refresh');
                    stopStatsAutoRefresh();
                } else {
                    console.log('Page visible - resuming auto-refresh');
                    startStatsAutoRefresh();
                    // Immediately refresh when page becomes visible
                    loadStats();
                }
            });
            
            console.log('Real-time category statistics system initialized successfully!');
        });
    </script>
</body>
</html>