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
            case 'get_products':
                handleGetProducts($db);
                break;
            case 'update_stock':
                handleUpdateStock($db);
                break;
            case 'get_low_stock':
                handleGetLowStock($db);
                break;
            case 'import_stock':
                handleImportStock($db);
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

// Handle product list for DataTables
function handleGetProducts($db) {
    try {
        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10);
        $search = $_GET['search']['value'] ?? '';
        $categoryFilter = $_GET['category_filter'] ?? '';
        $stockFilter = $_GET['stock_filter'] ?? '';
        
        $whereConditions = [];
        $params = [];
        
        // Search filter
        if (!empty($search)) {
            $searchTerms = explode(' ', $search);
            $searchConditions = [];
            foreach ($searchTerms as $term) {
                $term = trim($term);
                if (!empty($term)) {
                    $searchConditions[] = "(p.name LIKE ? OR p.sku LIKE ?)";
                    $params = array_merge($params, ["%$term%", "%$term%"]);
                }
            }
            if (!empty($searchConditions)) {
                $whereConditions[] = '(' . implode(' AND ', $searchConditions) . ')';
            }
        }
        
        // Category filter
        if (!empty($categoryFilter)) {
            $whereConditions[] = 'p.category_id = ?';
            $params[] = intval($categoryFilter);
        }
        
        // Stock filter
        if (!empty($stockFilter)) {
            switch ($stockFilter) {
                case 'in_stock':
                    $whereConditions[] = 'p.stock_quantity > 0 AND p.status = "active"';
                    break;
                case 'low_stock':
                    $whereConditions[] = 'p.stock_quantity <= p.min_stock_level AND p.stock_quantity > 0';
                    break;
                case 'out_of_stock':
                    $whereConditions[] = '(p.stock_quantity = 0 OR p.status = "out_of_stock")';
                    break;
            }
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count (unfiltered)
        $totalCountQuery = "SELECT COUNT(*) as total FROM products p";
        $totalCountResult = $db->rawQuery($totalCountQuery);
        $totalRecords = $totalCountResult[0]['total'] ?? 0;
        
        // Get filtered count
        $filteredCountQuery = "SELECT COUNT(*) as total FROM products p $whereClause";
        if (!empty($params)) {
            $filteredResult = $db->rawQuery($filteredCountQuery, $params);
        } else {
            $filteredResult = $db->rawQuery($filteredCountQuery);
        }
        $filteredRecords = $filteredResult[0]['total'] ?? 0;
        
        // Get products data
        $query = "
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            $whereClause
            ORDER BY p.stock_quantity ASC, p.name ASC
            LIMIT $start, $length
        ";
        
        if (!empty($params)) {
            $products = $db->rawQuery($query, $params);
        } else {
            $products = $db->rawQuery($query);
        }
        
        $data = [];
        if ($products) {
            foreach ($products as $product) {
                // Stock status
                $stockStatus = '';
                $stockClass = '';
                if ($product['stock_quantity'] == 0) {
                    $stockStatus = '<span class="badge bg-danger">Out of Stock</span>';
                    $stockClass = 'table-danger';
                } elseif ($product['stock_quantity'] <= $product['min_stock_level']) {
                    $stockStatus = '<span class="badge bg-warning">Low Stock</span>';
                    $stockClass = 'table-warning';
                } else {
                    $stockStatus = '<span class="badge bg-success">In Stock</span>';
                    $stockClass = '';
                }
                
                // Image
                $image = !empty($product['image']) ? 
                    "<img src='../assets/products/{$product['image']}' alt='{$product['name']}' class='product-image rounded' width='50' height='40'>" : 
                    "<div class='placeholder-image rounded d-flex align-items-center justify-content-center' style='width:50px;height:40px;background:#f8f9fa;'><i class='fas fa-image text-muted'></i></div>";
                
                $data[] = [
                    $image,
                    '<strong>' . htmlspecialchars($product['name']) . '</strong><br><small class="text-muted">' . htmlspecialchars($product['sku']) . '</small>',
                    $product['category_name'] ?? 'No Category',
                    $product['stock_quantity'],
                    $product['min_stock_level'],
                    $stockStatus,
                    '<div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary update-stock" 
                                data-id="' . $product['id'] . '" 
                                data-name="' . htmlspecialchars($product['name']) . '" 
                                data-current="' . $product['stock_quantity'] . '"
                                title="Update Stock">
                            <i class="fas fa-edit"></i>
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
        echo json_encode(['success' => false, 'message' => 'Error fetching products: ' . $e->getMessage()]);
    }
}

// Handle stock update
function handleUpdateStock($db) {
    try {
        $productId = intval($_POST['product_id'] ?? 0);
        $newQuantity = intval($_POST['new_quantity'] ?? 0);
        $reason = $_POST['reason'] ?? 'Manual update';
        
        if (!$productId) {
            throw new Exception('Invalid product ID');
        }
        
        // Get current product
        $product = $db->where('id', $productId)->getOne('products', 'id, name, stock_quantity');
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        $oldQuantity = $product['stock_quantity'];
        
        // Update stock quantity
        $db->where('id', $productId);
        if ($db->update('products', ['stock_quantity' => $newQuantity])) {
            // Log inventory change
            $logData = [
                'product_id' => $productId,
                'action_type' => ($newQuantity > $oldQuantity) ? 'add' : 'remove',
                'quantity_before' => $oldQuantity,
                'quantity_changed' => abs($newQuantity - $oldQuantity),
                'quantity_after' => $newQuantity,
                'reason' => $reason,
                'created_by' => $_SESSION['admin_id'] ?? null
            ];
            
            $db->insert('product_inventory_logs', $logData);
            
            // Update product status if needed
            $newStatus = '';
            if ($newQuantity == 0) {
                $newStatus = 'out_of_stock';
            } elseif ($newQuantity > 0 && $newQuantity <= $product['min_stock_level']) {
                $newStatus = 'active'; // Still active but low stock
            } else {
                $newStatus = 'active';
            }
            
            $db->where('id', $productId);
            $db->update('products', ['status' => $newStatus]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Stock updated successfully',
                'new_quantity' => $newQuantity
            ]);
        } else {
            throw new Exception('Failed to update stock: ' . $db->getLastError());
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle low stock products
function handleGetLowStock($db) {
    try {
        $db->where('stock_quantity <= min_stock_level');
        $db->where('stock_quantity > 0');
        $db->orderBy('stock_quantity', 'ASC');
        $products = $db->get('products', 10, ['id', 'name', 'sku', 'stock_quantity', 'min_stock_level']);
        
        echo json_encode(['success' => true, 'data' => $products]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle stock import
function handleImportStock($db) {
    try {
        if (!isset($_FILES['stock_file']) || $_FILES['stock_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }
        
        $file = $_FILES['stock_file']['tmp_name'];
        $handle = fopen($file, 'r');
        
        if (!$handle) {
            throw new Exception('Failed to open uploaded file');
        }
        
        $updatedCount = 0;
        $errors = [];
        $header = fgetcsv($handle); // Skip header row
        
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            if (count($data) >= 2) {
                $sku = trim($data[0]);
                $newQuantity = intval($data[1]);
                $reason = isset($data[2]) ? trim($data[2]) : 'CSV Import';
                
                // Find product by SKU
                $product = $db->where('sku', $sku)->getOne('products', 'id, name, stock_quantity, min_stock_level');
                
                if ($product) {
                    $oldQuantity = $product['stock_quantity'];
                    
                    // Update stock
                    $db->where('id', $product['id']);
                    if ($db->update('products', ['stock_quantity' => $newQuantity])) {
                        // Log inventory change
                        $logData = [
                            'product_id' => $product['id'],
                            'action_type' => ($newQuantity > $oldQuantity) ? 'add' : 'remove',
                            'quantity_before' => $oldQuantity,
                            'quantity_changed' => abs($newQuantity - $oldQuantity),
                            'quantity_after' => $newQuantity,
                            'reason' => $reason,
                            'created_by' => $_SESSION['admin_id'] ?? null
                        ];
                        
                        $db->insert('product_inventory_logs', $logData);
                        $updatedCount++;
                    } else {
                        $errors[] = "Failed to update stock for product: {$product['name']} (SKU: $sku)";
                    }
                } else {
                    $errors[] = "Product not found with SKU: $sku";
                }
            }
        }
        
        fclose($handle);
        
        $message = "Stock import completed. $updatedCount products updated.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'updated_count' => $updatedCount,
            'errors' => $errors
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Get data for filters
$categories = $db->get('categories', null, ['id', 'name']);

require __DIR__ . '/components/header.php'; ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
    
    .product-image {
        object-fit: contain;
        border: 2px solid #dee2e6;
    }
    
    .placeholder-image {
        border: 2px dashed #dee2e6;
    }
    
    .table th {
        border-top: none;
        font-weight: 600;
        color: #495057;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    @keyframes countUp {
        from { transform: scale(0.5); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    
    .count-animation {
        animation: countUp 0.6s ease-out;
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
                    <h1 class="mt-4">Inventory Management</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Inventory</li>
                    </ol>

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
                    <div class="row mb-4">
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-primary me-3">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="totalProducts">0</p>
                                        <p class="stats-label">Total Products</p>
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
                                        <p class="stats-number count-animation" id="inStockProducts">0</p>
                                        <p class="stats-label">In Stock</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-warning me-3">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="lowStockProducts">0</p>
                                        <p class="stats-label">Low Stock</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-danger me-3">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="outOfStockProducts">0</p>
                                        <p class="stats-label">Out of Stock</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card filter-card">
                                <div class="card-header bg-warning text-white">
                                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert</h6>
                                </div>
                                <div class="card-body">
                                    <div id="lowStockAlerts">
                                        <p class="text-center text-muted">Loading low stock products...</p>
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
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="categoryFilter" class="form-label">Category</label>
                                            <select class="form-select" id="categoryFilter">
                                                <option value="">All Categories</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="stockFilter" class="form-label">Stock Status</label>
                                            <select class="form-select" id="stockFilter">
                                                <option value="">All Stock</option>
                                                <option value="in_stock">In Stock</option>
                                                <option value="low_stock">Low Stock</option>
                                                <option value="out_of_stock">Out of Stock</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label class="form-label">Import Stock</label>
                                            <form id="importStockForm" enctype="multipart/form-data">
                                                <div class="input-group">
                                                    <input type="file" class="form-control" id="stockFile" name="stock_file" accept=".csv">
                                                    <button class="btn btn-outline-primary" type="submit" id="importBtn">Import</button>
                                                </div>
                                                <div class="form-text">
                                                    <a href="#" id="downloadTemplate">Download CSV template</a>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label class="form-label">Quick Actions</label>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                                                    <i class="fas fa-times"></i> Clear
                                                </button>
                                                <button type="button" class="btn btn-outline-success" id="refreshData">
                                                    <i class="fas fa-sync-alt"></i> Refresh
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
                                        <h6 class="mb-0"><i class="fas fa-warehouse me-2"></i>Inventory Management</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="inventoryTable">
                                            <thead>
                                                <tr>
                                                    <th width="80">Image</th>
                                                    <th>Product</th>
                                                    <th>Category</th>
                                                    <th width="100">Current Stock</th>
                                                    <th width="100">Min Stock</th>
                                                    <th width="120">Stock Status</th>
                                                    <th width="100">Actions</th>
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

    <!-- Update Stock Modal -->
    <div class="modal fade" id="updateStockModal" tabindex="-1" aria-labelledby="updateStockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="updateStockForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateStockModalLabel">Update Stock Quantity</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="productId" name="product_id">
                        <div class="mb-3">
                            <label for="productName" class="form-label">Product</label>
                            <input type="text" class="form-control" id="productName" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="currentQuantity" class="form-label">Current Quantity</label>
                            <input type="number" class="form-control" id="currentQuantity" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="newQuantity" class="form-label">New Quantity *</label>
                            <input type="number" class="form-control" id="newQuantity" name="new_quantity" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Change</label>
                            <select class="form-select" id="reason" name="reason">
                                <option value="Manual update">Manual update</option>
                                <option value="Received shipment">Received shipment</option>
                                <option value="Returned product">Returned product</option>
                                <option value="Damaged goods">Damaged goods</option>
                                <option value="Expired products">Expired products</option>
                                <option value="Seasonal adjustment">Seasonal adjustment</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveStockBtn">Update Stock</button>
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
    <script src="assets/js/scripts.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#inventoryTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'inventory-management.php?action=get_products',
                type: 'GET',
                data: function(d) {
                    d.category_filter = $('#categoryFilter').val();
                    d.stock_filter = $('#stockFilter').val();
                },
                error: function(xhr, error, code) {
                    console.error('DataTable AJAX error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Loading Error',
                        text: 'Failed to load inventory data. Please refresh the page.'
                    });
                }
            },
            columns: [
                { data: '0', orderable: false, searchable: false },
                { data: '1' },
                { data: '2' },
                { data: '3' },
                { data: '4' },
                { data: '5', orderable: false },
                { data: '6', orderable: false, searchable: false }
            ],
            order: [[3, 'asc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: 'No products found',
                zeroRecords: 'No matching products found',
                search: 'Search products:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ products',
                infoEmpty: 'Showing 0 to 0 of 0 products',
                infoFiltered: '(filtered from _MAX_ total products)',
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
        
        // Load low stock alerts
        loadLowStockAlerts();

        // Filter change handlers
        $('#categoryFilter, #stockFilter').on('change', function() {
            table.ajax.reload();
        });

        // Clear filters
        $('#clearFilters').on('click', function() {
            $('#categoryFilter, #stockFilter').val('');
            table.ajax.reload();
            loadStatistics();
        });

        // Refresh data
        $('#refreshData').on('click', function() {
            table.ajax.reload();
            loadStatistics();
            loadLowStockAlerts();
        });

        // Download template
        $('#downloadTemplate').on('click', function(e) {
            e.preventDefault();
            
            // Create CSV content
            const csvContent = "SKU,New Quantity,Reason\nPRODUCT001,50,Received shipment\nPRODUCT002,25,Manual update";
            
            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', 'stock_import_template.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Import stock
        $('#importStockForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'import_stock');
            
            $.ajax({
                url: 'inventory-management.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    $('#importBtn').html('<i class="fas fa-spinner fa-spin"></i> Importing...').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message
                        });
                        table.ajax.reload();
                        loadStatistics();
                        loadLowStockAlerts();
                        $('#stockFile').val('');
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
                        text: 'Failed to import stock data. Please try again.'
                    });
                },
                complete: function() {
                    $('#importBtn').html('Import').prop('disabled', false);
                }
            });
        });

        // Update stock modal
        $(document).on('click', '.update-stock', function() {
            const productId = $(this).data('id');
            const productName = $(this).data('name');
            const currentQuantity = $(this).data('current');
            
            $('#productId').val(productId);
            $('#productName').val(productName);
            $('#currentQuantity').val(currentQuantity);
            $('#newQuantity').val(currentQuantity);
            
            $('#updateStockModal').modal('show');
        });

        // Update stock form submission
        $('#updateStockForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_stock');
            
            $.ajax({
                url: 'inventory-management.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    $('#saveStockBtn').html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message
                        });
                        $('#updateStockModal').modal('hide');
                        table.ajax.reload();
                        loadStatistics();
                        loadLowStockAlerts();
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
                        text: 'Failed to update stock. Please try again.'
                    });
                },
                complete: function() {
                    $('#saveStockBtn').html('Update Stock').prop('disabled', false);
                }
            });
        });

        // Load statistics
        function loadStatistics() {
            $.ajax({
                url: 'product-management.php?action=get_stats',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        animateCounter('#totalProducts', response.stats.total);
                        animateCounter('#inStockProducts', response.stats.active);
                        animateCounter('#lowStockProducts', response.stats.low_stock);
                        animateCounter('#outOfStockProducts', response.stats.out_of_stock);
                    }
                },
                error: function() {
                    console.error('Failed to load statistics');
                }
            });
        }

        // Load low stock alerts
        function loadLowStockAlerts() {
            $.ajax({
                url: 'inventory-management.php?action=get_low_stock',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '<div class="row">';
                        response.data.forEach(function(product) {
                            html += `
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="alert alert-warning mb-0 p-2">
                                        <strong>${product.name}</strong> (${product.sku})<br>
                                        <small>Current: ${product.stock_quantity} | Min: ${product.min_stock_level}</small>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        $('#lowStockAlerts').html(html);
                    } else {
                        $('#lowStockAlerts').html('<p class="text-center text-muted mb-0">No low stock products found</p>');
                    }
                },
                error: function() {
                    $('#lowStockAlerts').html('<p class="text-center text-danger mb-0">Failed to load low stock alerts</p>');
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