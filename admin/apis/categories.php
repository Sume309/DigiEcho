<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../../vendor/autoload.php';

use App\auth\Admin;

// Check admin authentication
if (!Admin::Check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$db = new MysqliDb(
    settings()['hostname'], 
    settings()['user'], 
    settings()['password'], 
    settings()['database']
);

// Get the action from the request
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_categories':
            handleGetCategories($db);
            break;
            
        case 'get_category':
            handleGetCategory($db);
            break;
            
        case 'create_category':
            handleCreateCategory($db);
            break;
            
        case 'update_category':
            handleUpdateCategory($db);
            break;
            
        case 'delete_category':
            handleDeleteCategory($db);
            break;
            
        case 'bulk_delete':
            handleBulkDelete($db);
            break;
            
        case 'toggle_status':
            handleToggleStatus($db);
            break;
            
        case 'bulk_status':
            handleBulkStatus($db);
            break;
            
        case 'search_categories':
            handleSearchCategories($db);
            break;
            
        case 'get_stats':
            handleGetStats($db);
            break;
            
        case 'check_slug':
            handleCheckSlug($db);
            break;
            
        case 'get_parent_options':
            handleGetParentOptions($db);
            break;
            
        case 'export_categories':
            handleExportCategories($db);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetCategories($db) {
    // DataTables server-side processing
    $draw = intval($_GET['draw'] ?? 1);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 10);
    $searchValue = $_GET['search']['value'] ?? '';
    $orderColumn = intval($_GET['order'][0]['column'] ?? 0);
    $orderDir = $_GET['order'][0]['dir'] ?? 'asc';
    
    // Column mapping
    $columns = ['id', 'name', 'slug', 'parent_id', 'is_active', 'created_at'];
    $orderBy = $columns[$orderColumn] ?? 'id';
    
    // Build query
    $whereConditions = [];
    $bindParams = [];
    
    if (!empty($searchValue)) {
        $whereConditions[] = "(name LIKE ? OR slug LIKE ? OR description LIKE ?)";
        $searchParam = "%$searchValue%";
        $bindParams = array_merge($bindParams, [$searchParam, $searchParam, $searchParam]);
    }
    
    // Status filter
    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $whereConditions[] = "is_active = ?";
        $bindParams[] = intval($_GET['status']);
    }
    
    // Parent filter
    if (isset($_GET['parent']) && $_GET['parent'] !== '') {
        if ($_GET['parent'] === '0') {
            $whereConditions[] = "parent_id = 0";
        } else {
            $whereConditions[] = "parent_id = ?";
            $bindParams[] = intval($_GET['parent']);
        }
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total records
    $totalQuery = "SELECT COUNT(*) as total FROM categories $whereClause";
    if (!empty($bindParams)) {
        $totalStmt = $db->rawQuery($totalQuery, $bindParams);
    } else {
        $totalStmt = $db->rawQuery($totalQuery);
    }
    $totalRecords = $totalStmt[0]['total'] ?? 0;
    
    // Get filtered records
    $dataQuery = "
        SELECT c.*, 
               p.name as parent_name,
               (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as child_count,
               (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
        FROM categories c 
        LEFT JOIN categories p ON c.parent_id = p.id 
        $whereClause 
        ORDER BY $orderBy $orderDir 
        LIMIT $start, $length
    ";
    
    if (!empty($bindParams)) {
        $data = $db->rawQuery($dataQuery, $bindParams);
    } else {
        $data = $db->rawQuery($dataQuery);
    }
    
    // Format data for DataTables
    $formattedData = [];
    foreach ($data as $row) {
        $formattedData[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'parent_name' => $row['parent_name'] ?? 'Main Category',
            'is_active' => $row['is_active'],
            'child_count' => $row['child_count'],
            'product_count' => $row['product_count'],
            'image' => $row['image'] ?? '',
            'created_at' => $row['created_at'],
            'description' => $row['description'] ?? ''
        ];
    }
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $formattedData
    ]);
}

function handleGetCategory($db) {
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID is required']);
        return;
    }
    
    $db->where('id', $id);
    $category = $db->getOne('categories');
    
    if (!$category) {
        http_response_code(404);
        echo json_encode(['error' => 'Category not found']);
        return;
    }
    
    echo json_encode(['success' => true, 'data' => $category]);
}

function handleToggleStatus($db) {
    $id = intval($_POST['id'] ?? 0);
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID is required']);
        return;
    }
    
    $db->where('id', $id);
    $category = $db->getOne('categories', ['id', 'is_active']);
    
    if (!$category) {
        http_response_code(404);
        echo json_encode(['error' => 'Category not found']);
        return;
    }
    
    $newStatus = $category['is_active'] ? 0 : 1;
    
    $db->where('id', $id);
    $updated = $db->update('categories', [
        'is_active' => $newStatus,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($updated) {
        echo json_encode([
            'success' => true,
            'message' => 'Category status updated successfully',
            'new_status' => $newStatus
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update category status']);
    }
}

function handleBulkDelete($db) {
    $ids = $_POST['ids'] ?? [];
    
    if (empty($ids) || !is_array($ids)) {
        http_response_code(400);
        echo json_encode(['error' => 'Category IDs are required']);
        return;
    }
    
    $ids = array_map('intval', $ids);
    $deletedCount = 0;
    $errors = [];
    
    foreach ($ids as $id) {
        // Check for dependencies
        $db->where('parent_id', $id);
        $childCount = $db->getValue('categories', 'COUNT(*)');
        
        $db->where('category_id', $id);
        $productCount = $db->getValue('products', 'COUNT(*)');
        
        if ($childCount > 0 || $productCount > 0) {
            $db->where('id', $id);
            $category = $db->getOne('categories', ['name']);
            $errors[] = "Cannot delete '{$category['name']}' - has dependencies";
            continue;
        }
        
        // Get category for image cleanup
        $db->where('id', $id);
        $category = $db->getOne('categories', ['image']);
        
        // Delete category
        $db->where('id', $id);
        if ($db->delete('categories')) {
            $deletedCount++;
            
            // Clean up image
            if (!empty($category['image'])) {
                $imagePath = __DIR__ . "/../../assets/categories/{$category['image']}";
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "$deletedCount categories deleted successfully",
        'deleted_count' => $deletedCount,
        'errors' => $errors
    ]);
}

function handleBulkStatus($db) {
    $ids = $_POST['ids'] ?? [];
    $status = intval($_POST['status'] ?? 1);
    
    if (empty($ids) || !is_array($ids)) {
        http_response_code(400);
        echo json_encode(['error' => 'Category IDs are required']);
        return;
    }
    
    $ids = array_map('intval', $ids);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    $query = "UPDATE categories SET is_active = ?, updated_at = ? WHERE id IN ($placeholders)";
    $params = array_merge([$status, date('Y-m-d H:i:s')], $ids);
    
    $result = $db->rawQuery($query, $params);
    
    if ($result !== false) {
        $statusText = $status ? 'activated' : 'deactivated';
        echo json_encode([
            'success' => true,
            'message' => count($ids) . " categories $statusText successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update category status']);
    }
}

function handleSearchCategories($db) {
    $query = $_GET['q'] ?? '';
    $limit = intval($_GET['limit'] ?? 20);
    
    if (empty($query)) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }
    
    $db->where("(name LIKE ? OR slug LIKE ?)", ["%$query%", "%$query%"]);
    $db->where('is_active', 1);
    $categories = $db->get('categories', $limit, ['id', 'name', 'slug', 'parent_id']);
    
    echo json_encode(['success' => true, 'data' => $categories]);
}

function handleGetStats($db) {
    $stats = [];
    
    // Total categories
    $stats['total'] = $db->getValue('categories', 'COUNT(*)');
    
    // Active categories
    $db->where('is_active', 1);
    $stats['active'] = $db->getValue('categories', 'COUNT(*)');
    
    // Inactive categories
    $stats['inactive'] = $stats['total'] - $stats['active'];
    
    // Main categories (no parent)
    $db->where('parent_id', 0);
    $stats['main_categories'] = $db->getValue('categories', 'COUNT(*)');
    
    // Subcategories
    $db->where('parent_id', 0, '>');
    $stats['subcategories'] = $db->getValue('categories', 'COUNT(*)');
    
    // Categories with products
    $stats['with_products'] = $db->rawQueryValue(
        "SELECT COUNT(DISTINCT category_id) FROM products WHERE category_id IS NOT NULL"
    );
    
    // Recent categories (last 7 days)
    $db->where('created_at', date('Y-m-d H:i:s', strtotime('-7 days')), '>=');
    $stats['recent'] = $db->getValue('categories', 'COUNT(*)');
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

function handleCheckSlug($db) {
    $slug = $_GET['slug'] ?? '';
    $excludeId = intval($_GET['exclude_id'] ?? 0);
    
    if (empty($slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'Slug is required']);
        return;
    }
    
    $db->where('slug', $slug);
    if ($excludeId > 0) {
        $db->where('id', $excludeId, '!=');
    }
    
    $exists = $db->has('categories');
    
    echo json_encode([
        'success' => true,
        'available' => !$exists,
        'message' => $exists ? 'Slug already exists' : 'Slug is available'
    ]);
}

function handleGetParentOptions($db) {
    $excludeId = intval($_GET['exclude_id'] ?? 0);
    
    $db->where('is_active', 1);
    if ($excludeId > 0) {
        $db->where('id', $excludeId, '!=');
    }
    
    $categories = $db->get('categories', null, ['id', 'name', 'parent_id'], 'name ASC');
    
    // Filter out potential circular references for edit operations
    if ($excludeId > 0) {
        $categories = array_filter($categories, function($cat) use ($excludeId, $db) {
            return !isChildOf($db, $cat['id'], $excludeId);
        });
    }
    
    echo json_encode(['success' => true, 'data' => array_values($categories)]);
}

function handleExportCategories($db) {
    $format = $_GET['format'] ?? 'csv';
    
    $categories = $db->rawQuery("
        SELECT c.*, p.name as parent_name,
               (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as child_count,
               (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
        FROM categories c 
        LEFT JOIN categories p ON c.parent_id = p.id 
        ORDER BY c.name ASC
    ");
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="categories_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'ID', 'Name', 'Slug', 'Parent Category', 'Status', 'Child Categories', 
            'Products', 'Description', 'Created At'
        ]);
        
        // CSV data
        foreach ($categories as $category) {
            fputcsv($output, [
                $category['id'],
                $category['name'],
                $category['slug'],
                $category['parent_name'] ?? 'Main Category',
                $category['is_active'] ? 'Active' : 'Inactive',
                $category['child_count'],
                $category['product_count'],
                $category['description'] ?? '',
                $category['created_at']
            ]);
        }
        
        fclose($output);
    } else {
        echo json_encode(['success' => true, 'data' => $categories]);
    }
}

function handleCreateCategory($db) {
    // This would be handled by the form submission in category-add-enhanced.php
    http_response_code(501);
    echo json_encode(['error' => 'Create category via form submission']);
}

function handleUpdateCategory($db) {
    // This would be handled by the form submission in category-edit-enhanced.php
    http_response_code(501);
    echo json_encode(['error' => 'Update category via form submission']);
}

function handleDeleteCategory($db) {
    // This would be handled by the form submission in category-delete-enhanced.php
    http_response_code(501);
    echo json_encode(['error' => 'Delete category via form submission']);
}

// Helper function for circular reference checking
function isChildOf($db, $potentialParentId, $categoryId) {
    $db->where('parent_id', $categoryId);
    $children = $db->get('categories', null, ['id']);
    
    foreach ($children as $child) {
        if ($child['id'] == $potentialParentId) {
            return true;
        }
        if (isChildOf($db, $potentialParentId, $child['id'])) {
            return true;
        }
    }
    
    return false;
}
?>