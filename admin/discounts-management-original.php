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
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        switch ($_GET['action']) {
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
        
        // Check if discount is currently active based on dates
        $currentDate = date('Y-m-d');
        $whereConditions[] = '(start_date <= ? AND end_date >= ?)';
        $params = array_merge($params, [$currentDate, $currentDate]);
        
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

require __DIR__ . '/components/header.php'; ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
<style>
    .stats-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }
    
    .stats-number {
        font-size: 2rem;
        font-weight: bold;
        margin: 0;
    }
    
    .stats-label {
        color: #6c757d;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .filter-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    
    .table-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    
    .table th {
        border-top: none;
        font-weight: 600;
        color: #495057;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .discount-form .nav-link {
        font-weight: 500;
        color: #6c757d;
        border: none;
        border-bottom: 3px solid transparent;
    }
    
    .discount-form .nav-link.active {
        color: #0d6efd;
        border-bottom: 3px solid #0d6efd;
        background: transparent;
    }
    
    @keyframes countUp {
        from { transform: scale(0.5); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    
    .count-animation {
        animation: countUp 0.6s ease-out;
    }
    
    .select2-container {
        width: 100% !important;
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
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                        <div>
                            <h1 class="h3 mb-1">Discounts & Offers Management</h1>
                            <p class="text-muted mb-0">Create and manage product discounts and special offers</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary" id="addDiscountBtn">
                                <i class="fas fa-plus me-2"></i>Add Discount
                            </button>
                        </div>
                    </div>

                    <!-- Success/Error Messages -->
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
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- Statistics Dashboard -->
                    <div class="row mb-4" id="statsContainer">
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-primary me-3">
                                        <i class="fas fa-tags"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="totalDiscounts">0</p>
                                        <p class="stats-label">Total Discounts</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-success me-3">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="activeDiscounts">0</p>
                                        <p class="stats-label">Active</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-warning me-3">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="upcomingDiscounts">0</p>
                                        <p class="stats-label">Upcoming</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-secondary me-3">
                                        <i class="fas fa-history"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="expiredDiscounts">0</p>
                                        <p class="stats-label">Expired</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters and Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card filter-card">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filters & Actions</h6>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshStats">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="statusFilter" class="form-label">Status</label>
                                            <select class="form-select" id="statusFilter">
                                                <option value="">All Status</option>
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label class="form-label">Quick Actions</label>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                                                    <i class="fas fa-times"></i> Clear
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card table-card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-table me-2"></i>Discounts & Offers</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="discountsTable">
                                            <thead>
                                                <tr>
                                                    <th>Discount</th>
                                                    <th width="100">Value</th>
                                                    <th width="150">Applies To</th>
                                                    <th width="200">Date Range</th>
                                                    <th width="100">Usage</th>
                                                    <th width="100">Status</th>
                                                    <th width="150">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <!-- Add/Edit Discount Modal -->
    <div class="modal fade" id="discountModal" tabindex="-1" aria-labelledby="discountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="discountForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="discountModalLabel">Add New Discount</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="discountId" name="discount_id">
                        <input type="hidden" name="action" id="formAction" value="create_discount">
                        
                        <div class="discount-form">
                            <!-- Basic Information -->
                            <div class="mb-4">
                                <h6 class="section-title">Basic Information</h6>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Discount Name *</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="is_active" class="form-label">Status</label>
                                            <select class="form-select" id="is_active" name="is_active">
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                                </div>
                            </div>
                            
                            <!-- Discount Type and Value -->
                            <div class="mb-4">
                                <h6 class="section-title">Discount Type & Value</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="discount_type" class="form-label">Discount Type *</label>
                                            <select class="form-select" id="discount_type" name="discount_type" required>
                                                <option value="percentage">Percentage Off</option>
                                                <option value="fixed_amount">Fixed Amount Off</option>
                                                <option value="buy_x_get_y">Buy X Get Y</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="discount_value" class="form-label">Discount Value *</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="discount_value" name="discount_value" step="0.01" min="0" required>
                                                <span class="input-group-text" id="valueSuffix">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3" id="minOrderAmountGroup" style="display: none;">
                                            <label for="min_order_amount" class="form-label">Minimum Order Amount</label>
                                            <div class="input-group">
                                                <span class="input-group-text">৳</span>
                                                <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" step="0.01" min="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row" id="buyXGetYFields" style="display: none;">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="min_quantity" class="form-label">Minimum Quantity (X)</label>
                                            <input type="number" class="form-control" id="min_quantity" name="min_quantity" min="1" value="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_quantity" class="form-label">Free Quantity (Y)</label>
                                            <input type="number" class="form-control" id="max_quantity" name="max_quantity" min="1" value="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Date Range -->
                            <div class="mb-4">
                                <h6 class="section-title">Date Range</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date *</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date *</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Usage Limits -->
                            <div class="mb-4">
                                <h6 class="section-title">Usage Limits</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="usage_limit" class="form-label">Usage Limit</label>
                                            <input type="number" class="form-control" id="usage_limit" name="usage_limit" min="0" placeholder="No limit">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Applies To -->
                            <div class="mb-4">
                                <h6 class="section-title">Applies To</h6>
                                <div class="mb-3">
                                    <label for="applies_to" class="form-label">Apply Discount To</label>
                                    <select class="form-select" id="applies_to" name="applies_to">
                                        <option value="all_products">All Products</option>
                                        <option value="specific_products">Specific Products</option>
                                        <option value="categories">Categories</option>
                                        <option value="brands">Brands</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="specificProductsGroup" style="display: none;">
                                    <label for="selected_products" class="form-label">Select Products</label>
                                    <select class="form-select" id="selected_products" name="selected_products" multiple>
                                        <!-- Products will be loaded via AJAX -->
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="categoriesGroup" style="display: none;">
                                    <label for="selected_categories" class="form-label">Select Categories</label>
                                    <select class="form-select" id="selected_categories" name="selected_categories" multiple>
                                        <!-- Categories will be loaded via AJAX -->
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="brandsGroup" style="display: none;">
                                    <label for="selected_brands" class="form-label">Select Brands</label>
                                    <select class="form-select" id="selected_brands" name="selected_brands" multiple>
                                        <!-- Brands will be loaded via AJAX -->
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveDiscountBtn">Save Discount</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="assets/js/scripts.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#discountsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'discounts-management.php?action=get_discounts',
                type: 'GET',
                data: function(d) {
                    d.status_filter = $('#statusFilter').val();
                },
                error: function(xhr, error, code) {
                    console.error('DataTable AJAX error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Loading Error',
                        text: 'Failed to load discounts data. Please refresh the page.'
                    });
                }
            },
            columns: [
                { data: '0' },
                { data: '1' },
                { data: '2' },
                { data: '3' },
                { data: '4' },
                { data: '5', orderable: false },
                { data: '6', orderable: false, searchable: false }
            ],
            order: [[3, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: 'No discounts found',
                zeroRecords: 'No matching discounts found',
                search: 'Search discounts:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ discounts',
                infoEmpty: 'Showing 0 to 0 of 0 discounts',
                infoFiltered: '(filtered from _MAX_ total discounts)',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Previous'
                }
            }
        });

        // Load statistics on page load
        loadStatistics();

        // Filter change handlers
        $('#statusFilter').on('change', function() {
            table.ajax.reload();
        });

        // Clear filters
        $('#clearFilters').on('click', function() {
            $('#statusFilter').val('');
            table.ajax.reload();
            loadStatistics();
        });

        // Refresh stats manually
        $('#refreshStats').on('click', function() {
            loadStatistics();
            table.ajax.reload();
        });

        // Add discount button
        $('#addDiscountBtn').on('click', function() {
            resetDiscountForm();
            $('#discountModalLabel').text('Add New Discount');
            $('#formAction').val('create_discount');
            $('#discountModal').modal('show');
        });

        // Edit discount
        $(document).on('click', '.edit-discount', function() {
            const discountId = $(this).data('id');
            editDiscount(discountId);
        });

        // Toggle discount
        $(document).on('click', '.toggle-discount', function() {
            const discountId = $(this).data('id');
            const status = $(this).data('status');
            
            $.ajax({
                url: 'discounts-management.php',
                type: 'POST',
                data: {
                    action: 'toggle_discount',
                    discount_id: discountId,
                    status: status
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        table.ajax.reload();
                        loadStatistics();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to toggle discount. Please try again.'
                    });
                }
            });
        });

        // Delete discount
        $(document).on('click', '.delete-discount', function() {
            const discountId = $(this).data('id');
            
            Swal.fire({
                title: 'Delete Discount',
                text: 'Are you sure you want to delete this discount? This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'discounts-management.php',
                        type: 'POST',
                        data: {
                            action: 'delete_discount',
                            discount_id: discountId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                table.ajax.reload();
                                loadStatistics();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Failed to delete discount. Please try again.'
                            });
                        }
                    });
                }
            });
        });

        // Discount type change
        $('#discount_type').on('change', function() {
            const type = $(this).val();
            
            if (type === 'percentage') {
                $('#valueSuffix').text('%');
                $('#minOrderAmountGroup').show();
                $('#buyXGetYFields').hide();
            } else if (type === 'fixed_amount') {
                $('#valueSuffix').text('৳');
                $('#minOrderAmountGroup').show();
                $('#buyXGetYFields').hide();
            } else {
                $('#valueSuffix').text('');
                $('#minOrderAmountGroup').hide();
                $('#buyXGetYFields').show();
            }
        });

        // Applies to change
        $('#applies_to').on('change', function() {
            const appliesTo = $(this).val();
            
            $('#specificProductsGroup, #categoriesGroup, #brandsGroup').hide();
            
            if (appliesTo === 'specific_products') {
                $('#specificProductsGroup').show();
                loadProducts();
            } else if (appliesTo === 'categories') {
                $('#categoriesGroup').show();
                loadCategories();
            } else if (appliesTo === 'brands') {
                $('#brandsGroup').show();
                loadBrands();
            }
        });

        // Initialize Select2
        $('#selected_products, #selected_categories, #selected_brands').select2({
            placeholder: 'Select options',
            allowClear: true
        });

        // Discount form submission
        $('#discountForm').on('submit', function(e) {
            e.preventDefault();
            
            // Get selected values for multiple selects
            const selectedProducts = $('#selected_products').val() || [];
            const selectedCategories = $('#selected_categories').val() || [];
            const selectedBrands = $('#selected_brands').val() || [];
            
            // Add to form data
            const formData = new FormData(this);
            formData.append('selected_products', selectedProducts.join(','));
            formData.append('selected_categories', selectedCategories.join(','));
            formData.append('selected_brands', selectedBrands.join(','));
            
            $.ajax({
                url: 'discounts-management.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    $('#saveDiscountBtn').html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message
                        });
                        $('#discountModal').modal('hide');
                        table.ajax.reload();
                        loadStatistics();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to save discount. Please try again.'
                    });
                },
                complete: function() {
                    $('#saveDiscountBtn').html('Save Discount').prop('disabled', false);
                }
            });
        });

        // Reset discount form
        function resetDiscountForm() {
            $('#discountForm')[0].reset();
            $('#discountId').val('');
            $('#formAction').val('create_discount');
            $('#discountModalLabel').text('Add New Discount');
            
            // Reset dynamic fields
            $('#valueSuffix').text('%');
            $('#minOrderAmountGroup').hide();
            $('#buyXGetYFields').hide();
            $('#specificProductsGroup, #categoriesGroup, #brandsGroup').hide();
            
            // Reset Select2
            $('#selected_products, #selected_categories, #selected_brands').val(null).trigger('change');
        }

        // Edit discount
        function editDiscount(discountId) {
            Swal.fire({
                title: 'Loading...',
                text: 'Please wait while we load the discount details.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // TODO: Implement edit functionality
            // This would require a get_discount endpoint to fetch discount details
            Swal.close();
            Swal.fire({
                icon: 'info',
                title: 'Not Implemented',
                text: 'Edit functionality is not yet implemented.'
            });
        }

        // Load products for dropdown
        function loadProducts() {
            if ($('#selected_products option').length <= 1) { // Only load if not already loaded
                $.ajax({
                    url: 'discounts-management.php?action=get_products',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            let options = '<option value=""></option>';
                            response.data.forEach(function(product) {
                                options += `<option value="${product.id}">${product.name} (${product.sku})</option>`;
                            });
                            $('#selected_products').html(options);
                        }
                    }
                });
            }
        }

        // Load categories for dropdown
        function loadCategories() {
            if ($('#selected_categories option').length <= 1) { // Only load if not already loaded
                $.ajax({
                    url: 'discounts-management.php?action=get_categories',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            let options = '<option value=""></option>';
                            response.data.forEach(function(category) {
                                options += `<option value="${category.id}">${category.name}</option>`;
                            });
                            $('#selected_categories').html(options);
                        }
                    }
                });
            }
        }

        // Load brands for dropdown
        function loadBrands() {
            if ($('#selected_brands option').length <= 1) { // Only load if not already loaded
                $.ajax({
                    url: 'discounts-management.php?action=get_brands',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            let options = '<option value=""></option>';
                            response.data.forEach(function(brand) {
                                options += `<option value="${brand.id}">${brand.name}</option>`;
                            });
                            $('#selected_brands').html(options);
                        }
                    }
                });
            }
        }

        // Load statistics
        function loadStatistics() {
            $.ajax({
                url: 'discounts-management.php?action=get_stats',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        animateCounter('#totalDiscounts', response.stats.total);
                        animateCounter('#activeDiscounts', response.stats.active);
                        animateCounter('#upcomingDiscounts', response.stats.upcoming);
                        animateCounter('#expiredDiscounts', response.stats.expired);
                    }
                },
                error: function() {
                    console.error('Failed to load statistics');
                }
            });
        }

        // Animate counter
        function animateCounter(element, target) {
            const $element = $(element);
            const current = parseInt($element.text()) || 0;
            
            if (current !== target) {
                $element.removeClass('count-animation');
                setTimeout(() => {
                    $element.addClass('count-animation');
                }, 10);
            }
            
            $({ count: current }).animate({ count: target }, {
                duration: 800,
                easing: 'swing',
                step: function() {
                    $element.text(Math.floor(this.count));
                },
                complete: function() {
                    $element.text(target);
                }
            });
        }
    });
    </script>
</body>
</html>