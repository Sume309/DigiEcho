<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';
require_once __DIR__ . '/../src/auth/admin.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('Location: auto-login.php');
    exit;
}

$db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);

// Handle AJAX requests
if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        switch ($action) {
            case 'get_discounts':
                handleGetDiscounts($db);
                break;
            case 'get_products':
                handleGetProducts($db);
                break;
            case 'get_categories':
                handleGetCategories($db);
                break;
            case 'get_brands':
                handleGetBrands($db);
                break;
            case 'create_discount':
                handleCreateDiscount($db);
                break;
            case 'update_discount':
                handleUpdateDiscount($db);
                break;
            case 'delete_discount':
                handleDeleteDiscount($db);
                break;
            case 'toggle_discount':
                handleToggleDiscount($db);
                break;
            case 'get_stats':
                handleGetStats($db);
                break;
            case 'get_discount':
                handleGetDiscount($db);
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

// Handle get discounts for DataTables
function handleGetDiscounts($db) {
    try {
        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10);
        $search = $_GET['search']['value'] ?? '';
        $statusFilter = $_GET['status_filter'] ?? '';
        
        $whereConditions = [];
        $params = [];
        
        // Search filter
        if (!empty($search)) {
            $searchTerms = explode(' ', $search);
            $searchConditions = [];
            foreach ($searchTerms as $term) {
                $term = trim($term);
                if (!empty($term)) {
                    $searchConditions[] = "(name LIKE ? OR description LIKE ?)";
                    $params = array_merge($params, ["%$term%", "%$term%"]);
                }
            }
            if (!empty($searchConditions)) {
                $whereConditions[] = '(' . implode(' AND ', $searchConditions) . ')';
            }
        }
        
        // Status filter
        if ($statusFilter !== '') {
            $whereConditions[] = 'is_active = ?';
            $params[] = intval($statusFilter);
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count (unfiltered)
        $totalCountQuery = "SELECT COUNT(*) as total FROM product_discounts";
        $totalCountResult = $db->rawQuery($totalCountQuery);
        $totalRecords = $totalCountResult[0]['total'] ?? 0;
        
        // Get filtered count
        $filteredCountQuery = "SELECT COUNT(*) as total FROM product_discounts $whereClause";
        if (!empty($params)) {
            $filteredResult = $db->rawQuery($filteredCountQuery, $params);
        } else {
            $filteredResult = $db->rawQuery($filteredCountQuery);
        }
        $filteredRecords = $filteredResult[0]['total'] ?? 0;
        
        // Get discounts data
        $query = "
            SELECT *
            FROM product_discounts
            $whereClause
            ORDER BY created_at DESC
            LIMIT $start, $length
        ";
        
        if (!empty($params)) {
            $discounts = $db->rawQuery($query, $params);
        } else {
            $discounts = $db->rawQuery($query);
        }
        
        $data = [];
        if ($discounts) {
            foreach ($discounts as $discount) {
                // Status badge
                $statusBadge = '';
                if ($discount['is_active'] == 1 && $discount['start_date'] <= date('Y-m-d') && $discount['end_date'] >= date('Y-m-d')) {
                    $statusBadge = '<span class="badge bg-success">Active</span>';
                } else {
                    $statusBadge = '<span class="badge bg-secondary">Inactive</span>';
                }
                
                // Discount value display
                $discountValue = '';
                if ($discount['discount_type'] == 'percentage') {
                    $discountValue = $discount['discount_value'] . '%';
                } elseif ($discount['discount_type'] == 'fixed_amount') {
                    $discountValue = '৳' . $discount['discount_value'];
                } else {
                    $discountValue = 'Buy ' . $discount['min_quantity'] . ' Get ' . $discount['max_quantity'];
                }
                
                // Applies to
                $appliesTo = '';
                switch ($discount['applies_to']) {
                    case 'all_products':
                        $appliesTo = 'All Products';
                        break;
                    case 'specific_products':
                        $appliesTo = 'Specific Products';
                        break;
                    case 'categories':
                        $appliesTo = 'Categories';
                        break;
                    case 'brands':
                        $appliesTo = 'Brands';
                        break;
                }
                
                // Usage
                $usage = $discount['usage_count'] . '/' . ($discount['usage_limit'] ?: '∞');
                
                $data[] = [
                    '<strong>' . htmlspecialchars($discount['name']) . '</strong><br><small class="text-muted">' . htmlspecialchars($discount['description'] ?? '') . '</small>',
                    $discountValue,
                    $appliesTo,
                    date('M j, Y', strtotime($discount['start_date'])) . ' - ' . date('M j, Y', strtotime($discount['end_date'])),
                    $usage,
                    $statusBadge,
                    '<div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary edit-discount" data-id="' . $discount['id'] . '" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-' . ($discount['is_active'] ? 'warning' : 'success') . ' toggle-discount" data-id="' . $discount['id'] . '" data-status="' . $discount['is_active'] . '" title="' . ($discount['is_active'] ? 'Deactivate' : 'Activate') . '">
                            <i class="fas fa-' . ($discount['is_active'] ? 'pause' : 'play') . '"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger delete-discount" data-id="' . $discount['id'] . '" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>'
                ];
            }
        }
        
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching discounts: ' . $e->getMessage()]);
    }
}

// Handle get single discount for editing
function handleGetDiscount($db) {
    try {
        $discountId = intval($_GET['discount_id'] ?? 0);
        
        if (!$discountId) {
            throw new Exception('Invalid discount ID');
        }
        
        // Get discount details
        $db->where('id', $discountId);
        $discount = $db->getOne('product_discounts');
        
        if (!$discount) {
            throw new Exception('Discount not found');
        }
        
        // Get related items based on applies_to
        $relatedItems = [];
        if ($discount['applies_to'] !== 'all_products') {
            $db->where('discount_id', $discountId);
            $relations = $db->get('product_discount_relations');
            
            foreach ($relations as $relation) {
                if ($relation['product_id']) {
                    $relatedItems[] = $relation['product_id'];
                } elseif ($relation['category_id']) {
                    $relatedItems[] = $relation['category_id'];
                } elseif ($relation['brand_id']) {
                    $relatedItems[] = $relation['brand_id'];
                }
            }
        }
        
        $discount['related_items'] = $relatedItems;
        
        echo json_encode([
            'success' => true, 
            'data' => $discount
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle get products for dropdown
function handleGetProducts($db) {
    try {
        $products = $db->get('products', null, ['id', 'name', 'sku']);
        echo json_encode(['success' => true, 'data' => $products]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle get categories for dropdown
function handleGetCategories($db) {
    try {
        $categories = $db->get('categories', null, ['id', 'name']);
        echo json_encode(['success' => true, 'data' => $categories]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle get brands for dropdown
function handleGetBrands($db) {
    try {
        $brands = $db->get('brands', null, ['id', 'name']);
        echo json_encode(['success' => true, 'data' => $brands]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle create discount
function handleCreateDiscount($db) {
    try {
        // Validate required fields
        $requiredFields = ['name', 'discount_type', 'discount_value', 'start_date', 'end_date'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Required field '$field' is missing");
            }
        }
        
        // Prepare discount data
        $discountData = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? null,
            'discount_type' => $_POST['discount_type'],
            'discount_value' => floatval($_POST['discount_value']),
            'min_quantity' => !empty($_POST['min_quantity']) ? intval($_POST['min_quantity']) : 1,
            'max_quantity' => !empty($_POST['max_quantity']) ? intval($_POST['max_quantity']) : null,
            'min_order_amount' => !empty($_POST['min_order_amount']) ? floatval($_POST['min_order_amount']) : null,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'usage_limit' => !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null,
            'applies_to' => $_POST['applies_to'] ?? 'all_products',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Insert discount
        $discountId = $db->insert('product_discounts', $discountData);
        
        if ($discountId) {
            // Handle relations if applicable
            $appliesTo = $_POST['applies_to'] ?? 'all_products';
            
            if ($appliesTo == 'specific_products' && !empty($_POST['selected_products'])) {
                $productIds = explode(',', $_POST['selected_products']);
                foreach ($productIds as $productId) {
                    $relationData = [
                        'discount_id' => $discountId,
                        'product_id' => intval($productId)
                    ];
                    $db->insert('product_discount_relations', $relationData);
                }
            } elseif ($appliesTo == 'categories' && !empty($_POST['selected_categories'])) {
                $categoryIds = explode(',', $_POST['selected_categories']);
                foreach ($categoryIds as $categoryId) {
                    $relationData = [
                        'discount_id' => $discountId,
                        'category_id' => intval($categoryId)
                    ];
                    $db->insert('product_discount_relations', $relationData);
                }
            } elseif ($appliesTo == 'brands' && !empty($_POST['selected_brands'])) {
                $brandIds = explode(',', $_POST['selected_brands']);
                foreach ($brandIds as $brandId) {
                    $relationData = [
                        'discount_id' => $discountId,
                        'brand_id' => intval($brandId)
                    ];
                    $db->insert('product_discount_relations', $relationData);
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Discount created successfully',
                'discount_id' => $discountId
            ]);
        } else {
            throw new Exception('Failed to create discount: ' . $db->getLastError());
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle update discount
function handleUpdateDiscount($db) {
    try {
        $discountId = intval($_POST['discount_id'] ?? 0);
        
        if (!$discountId) {
            throw new Exception('Invalid discount ID');
        }
        
        // Validate required fields
        $requiredFields = ['name', 'discount_type', 'discount_value', 'start_date', 'end_date'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Required field '$field' is missing");
            }
        }
        
        // Prepare discount data
        $discountData = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? null,
            'discount_type' => $_POST['discount_type'],
            'discount_value' => floatval($_POST['discount_value']),
            'min_quantity' => !empty($_POST['min_quantity']) ? intval($_POST['min_quantity']) : 1,
            'max_quantity' => !empty($_POST['max_quantity']) ? intval($_POST['max_quantity']) : null,
            'min_order_amount' => !empty($_POST['min_order_amount']) ? floatval($_POST['min_order_amount']) : null,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'usage_limit' => !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null,
            'applies_to' => $_POST['applies_to'] ?? 'all_products',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Update discount
        $db->where('id', $discountId);
        if ($db->update('product_discounts', $discountData)) {
            // Delete existing relations
            $db->where('discount_id', $discountId)->delete('product_discount_relations');
            
            // Handle relations if applicable
            $appliesTo = $_POST['applies_to'] ?? 'all_products';
            
            if ($appliesTo == 'specific_products' && !empty($_POST['selected_products'])) {
                $productIds = explode(',', $_POST['selected_products']);
                foreach ($productIds as $productId) {
                    $relationData = [
                        'discount_id' => $discountId,
                        'product_id' => intval($productId)
                    ];
                    $db->insert('product_discount_relations', $relationData);
                }
            } elseif ($appliesTo == 'categories' && !empty($_POST['selected_categories'])) {
                $categoryIds = explode(',', $_POST['selected_categories']);
                foreach ($categoryIds as $categoryId) {
                    $relationData = [
                        'discount_id' => $discountId,
                        'category_id' => intval($categoryId)
                    ];
                    $db->insert('product_discount_relations', $relationData);
                }
            } elseif ($appliesTo == 'brands' && !empty($_POST['selected_brands'])) {
                $brandIds = explode(',', $_POST['selected_brands']);
                foreach ($brandIds as $brandId) {
                    $relationData = [
                        'discount_id' => $discountId,
                        'brand_id' => intval($brandId)
                    ];
                    $db->insert('product_discount_relations', $relationData);
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Discount updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update discount: ' . $db->getLastError());
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle delete discount
function handleDeleteDiscount($db) {
    try {
        $discountId = intval($_POST['discount_id'] ?? 0);
        
        if (!$discountId) {
            throw new Exception('Invalid discount ID');
        }
        
        // Delete relations first
        $db->where('discount_id', $discountId)->delete('product_discount_relations');
        
        // Delete discount
        $db->where('id', $discountId);
        if ($db->delete('product_discounts')) {
            echo json_encode([
                'success' => true, 
                'message' => 'Discount deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete discount: ' . $db->getLastError());
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle toggle discount status
function handleToggleDiscount($db) {
    try {
        $discountId = intval($_POST['discount_id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        
        if (!$discountId) {
            throw new Exception('Invalid discount ID');
        }
        
        // Toggle status (0 to 1 or 1 to 0)
        $newStatus = $status == 1 ? 0 : 1;
        
        $db->where('id', $discountId);
        if ($db->update('product_discounts', ['is_active' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')])) {
            echo json_encode([
                'success' => true, 
                'message' => 'Discount ' . ($newStatus ? 'activated' : 'deactivated') . ' successfully',
                'new_status' => $newStatus
            ]);
        } else {
            throw new Exception('Failed to toggle discount status: ' . $db->getLastError());
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle get statistics
function handleGetStats($db) {
    try {
        $stats = [
            'total' => $db->getValue('product_discounts', 'COUNT(*)'),
            'active' => $db->getValue('product_discounts', 'COUNT(*)', 'is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE()'),
            'upcoming' => $db->getValue('product_discounts', 'COUNT(*)', 'is_active = 1 AND start_date > CURDATE()'),
            'expired' => $db->getValue('product_discounts', 'COUNT(*)', 'end_date < CURDATE()')
        ];
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching statistics: ' . $e->getMessage()]);
    }
}
