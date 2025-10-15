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
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if (!empty($action)) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        switch ($action) {
            case 'get_reviews':
                handleGetReviews($db);
                break;
            case 'approve_review':
                handleApproveReview($db);
                break;
            case 'reject_review':
                handleRejectReview($db);
                break;
            case 'delete_review':
                handleDeleteReview($db);
                break;
            case 'get_stats':
                handleGetStats($db);
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

// Handle get reviews for DataTables
function handleGetReviews($db) {
    try {
        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10);
        $search = $_GET['search']['value'] ?? '';
        $statusFilter = $_GET['status_filter'] ?? '';
        $ratingFilter = $_GET['rating_filter'] ?? '';
        
        $whereConditions = [];
        $params = [];
        
        // Search filter
        if (!empty($search)) {
            $searchTerms = explode(' ', $search);
            $searchConditions = [];
            foreach ($searchTerms as $term) {
                $term = trim($term);
                if (!empty($term)) {
                    $searchConditions[] = "(pr.customer_name LIKE ? OR pr.customer_email LIKE ? OR pr.title LIKE ? OR pr.review_text LIKE ? OR p.name LIKE ?)";
                    $params = array_merge($params, ["%$term%", "%$term%", "%$term%", "%$term%", "%$term%"]);
                }
            }
            if (!empty($searchConditions)) {
                $whereConditions[] = '(' . implode(' AND ', $searchConditions) . ')';
            }
        }
        
        // Status filter
        if ($statusFilter !== '') {
            $whereConditions[] = 'pr.is_approved = ?';
            $params[] = intval($statusFilter);
        }
        
        // Rating filter
        if ($ratingFilter !== '') {
            $whereConditions[] = 'pr.rating = ?';
            $params[] = intval($ratingFilter);
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count (unfiltered)
        $totalCountQuery = "SELECT COUNT(*) as total FROM product_reviews pr LEFT JOIN products p ON pr.product_id = p.id";
        $totalCountResult = $db->rawQuery($totalCountQuery);
        $totalRecords = $totalCountResult[0]['total'] ?? 0;
        
        // Get filtered count
        $filteredCountQuery = "SELECT COUNT(*) as total FROM product_reviews pr LEFT JOIN products p ON pr.product_id = p.id $whereClause";
        if (!empty($params)) {
            $filteredResult = $db->rawQuery($filteredCountQuery, $params);
        } else {
            $filteredResult = $db->rawQuery($filteredCountQuery);
        }
        $filteredRecords = $filteredResult[0]['total'] ?? 0;
        
        // Get reviews data
        $query = "
            SELECT pr.*, p.name as product_name, p.image as product_image
            FROM product_reviews pr
            LEFT JOIN products p ON pr.product_id = p.id
            $whereClause
            ORDER BY pr.created_at DESC
            LIMIT $start, $length
        ";
        
        if (!empty($params)) {
            $reviews = $db->rawQuery($query, $params);
        } else {
            $reviews = $db->rawQuery($query);
        }
        
        $data = [];
        if ($reviews) {
            foreach ($reviews as $review) {
                // Status badge
                $statusBadge = '';
                if ($review['is_approved'] == 1) {
                    $statusBadge = '<span class="badge bg-success">Approved</span>';
                } else {
                    $statusBadge = '<span class="badge bg-warning">Pending</span>';
                }
                
                // Rating stars
                $ratingStars = '';
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $review['rating']) {
                        $ratingStars .= '<i class="fas fa-star text-warning"></i>';
                    } else {
                        $ratingStars .= '<i class="far fa-star text-warning"></i>';
                    }
                }
                
                // Product image
                $productImage = !empty($review['product_image']) ? 
                    "<img src='../assets/products/{$review['product_image']}' alt='{$review['product_name']}' class='product-image rounded' width='50' height='40'>" : 
                    "<div class='placeholder-image rounded d-flex align-items-center justify-content-center' style='width:50px;height:40px;background:#f8f9fa;'><i class='fas fa-image text-muted'></i></div>";
                
                $data[] = [
                    $productImage,
                    '<strong>' . htmlspecialchars($review['product_name'] ?? 'Unknown Product') . '</strong>',
                    '<strong>' . htmlspecialchars($review['customer_name']) . '</strong><br><small class="text-muted">' . htmlspecialchars($review['customer_email']) . '</small>',
                    '<div>' . $ratingStars . ' (' . $review['rating'] . ')</div>',
                    '<div><strong>' . htmlspecialchars($review['title'] ?? '') . '</strong></div><div class="text-muted">' . htmlspecialchars(substr($review['review_text'], 0, 100)) . (strlen($review['review_text']) > 100 ? '...' : '') . '</div>',
                    date('M j, Y', strtotime($review['created_at'])),
                    $statusBadge,
                    '<div class="btn-group btn-group-sm" role="group">
                        ' . ($review['is_approved'] == 0 ? 
                            '<button type="button" class="btn btn-outline-success approve-review" data-id="' . $review['id'] . '" title="Approve">
                                <i class="fas fa-check"></i>
                            </button>' : 
                            '<button type="button" class="btn btn-outline-warning reject-review" data-id="' . $review['id'] . '" title="Reject">
                                <i class="fas fa-times"></i>
                            </button>') . '
                        <button type="button" class="btn btn-outline-danger delete-review" data-id="' . $review['id'] . '" title="Delete">
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
        echo json_encode(['success' => false, 'message' => 'Error fetching reviews: ' . $e->getMessage()]);
    }
}

// Handle approve review
function handleApproveReview($db) {
    try {
        $reviewId = intval($_POST['review_id'] ?? 0);
        
        if (!$reviewId) {
            throw new Exception('Invalid review ID');
        }
        
        $db->where('id', $reviewId);
        if ($db->update('product_reviews', ['is_approved' => 1, 'updated_at' => date('Y-m-d H:i:s')])) {
            // Update product rating average and count
            updateProductRating($db, $reviewId);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Review approved successfully'
            ]);
        } else {
            throw new Exception('Failed to approve review: ' . $db->getLastError());
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle reject review
function handleRejectReview($db) {
    try {
        $reviewId = intval($_POST['review_id'] ?? 0);
        
        if (!$reviewId) {
            throw new Exception('Invalid review ID');
        }
        
        $db->where('id', $reviewId);
        if ($db->update('product_reviews', ['is_approved' => 0, 'updated_at' => date('Y-m-d H:i:s')])) {
            // Update product rating average and count
            updateProductRating($db, $reviewId);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Review rejected successfully'
            ]);
        } else {
            throw new Exception('Failed to reject review: ' . $db->getLastError());
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle delete review
function handleDeleteReview($db) {
    try {
        $reviewId = intval($_POST['review_id'] ?? 0);
        
        if (!$reviewId) {
            throw new Exception('Invalid review ID');
        }
        
        // Get review before deletion to update product rating
        $review = $db->where('id', $reviewId)->getOne('product_reviews');
        
        if ($db->where('id', $reviewId)->delete('product_reviews')) {
            // Update product rating average and count
            if ($review) {
                updateProductRating($db, $reviewId, true); // true for deletion
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Review deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete review: ' . $db->getLastError());
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Handle get statistics
function handleGetStats($db) {
    try {
        // Get total reviews
        $totalResult = $db->rawQuery("SELECT COUNT(*) as total FROM product_reviews");
        $total = intval($totalResult[0]['total'] ?? 0);
        
        // Get approved reviews
        $approvedResult = $db->rawQuery("SELECT COUNT(*) as approved FROM product_reviews WHERE is_approved = 1");
        $approved = intval($approvedResult[0]['approved'] ?? 0);
        
        // Get pending reviews
        $pendingResult = $db->rawQuery("SELECT COUNT(*) as pending FROM product_reviews WHERE is_approved = 0");
        $pending = intval($pendingResult[0]['pending'] ?? 0);
        
        // Get average rating of approved reviews
        $avgResult = $db->rawQuery("SELECT AVG(rating) as avg_rating FROM product_reviews WHERE is_approved = 1");
        $avgRating = $avgResult[0]['avg_rating'] ? round(floatval($avgResult[0]['avg_rating']), 1) : 0;
        
        $stats = [
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'avg_rating' => $avgRating
        ];
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching statistics: ' . $e->getMessage()]);
    }
}

// Update product rating average and count
function updateProductRating($db, $reviewId, $isDeletion = false) {
    try {
        // Get the review to find the product
        $review = $db->where('id', $reviewId)->getOne('product_reviews', 'product_id');
        if (!$review) return;
        
        $productId = $review['product_id'];
        
        // Get all approved reviews for this product
        $db->where('product_id', $productId);
        $db->where('is_approved', 1);
        $reviews = $db->get('product_reviews');
        
        if ($reviews) {
            $totalRating = 0;
            $ratingCount = count($reviews);
            
            foreach ($reviews as $r) {
                $totalRating += $r['rating'];
            }
            
            $averageRating = $ratingCount > 0 ? round($totalRating / $ratingCount, 2) : 0;
        } else {
            $averageRating = 0;
            $ratingCount = 0;
        }
        
        // Update product
        $db->where('id', $productId);
        $db->update('products', [
            'rating_average' => $averageRating,
            'rating_count' => $ratingCount
        ]);
        
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log('Error updating product rating: ' . $e->getMessage());
    }
}

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
                    <h1 class="mt-4">Product Reviews Management</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Reviews</li>
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
                    <div class="row mb-4" id="statsContainer">
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-primary me-3">
                                        <i class="fas fa-comments"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="totalReviews">0</p>
                                        <p class="stats-label">Total Reviews</p>
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
                                        <p class="stats-number count-animation" id="approvedReviews">0</p>
                                        <p class="stats-label">Approved</p>
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
                                        <p class="stats-number count-animation" id="pendingReviews">0</p>
                                        <p class="stats-label">Pending</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-info me-3">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div>
                                        <p class="stats-number count-animation" id="avgRating">0</p>
                                        <p class="stats-label">Avg Rating</p>
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
                                                <option value="1">Approved</option>
                                                <option value="0">Pending</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="ratingFilter" class="form-label">Rating</label>
                                            <select class="form-select" id="ratingFilter">
                                                <option value="">All Ratings</option>
                                                <option value="5">5 Stars</option>
                                                <option value="4">4 Stars</option>
                                                <option value="3">3 Stars</option>
                                                <option value="2">2 Stars</option>
                                                <option value="1">1 Star</option>
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
                                        <h6 class="mb-0"><i class="fas fa-table me-2"></i>Product Reviews</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="reviewsTable">
                                            <thead>
                                                <tr>
                                                    <th width="80">Product</th>
                                                    <th>Product Name</th>
                                                    <th>Customer</th>
                                                    <th width="120">Rating</th>
                                                    <th>Review</th>
                                                    <th width="120">Date</th>
                                                    <th width="100">Status</th>
                                                    <th width="120">Actions</th>
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
        const table = $('#reviewsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'reviews-management.php?action=get_reviews',
                type: 'GET',
                data: function(d) {
                    d.status_filter = $('#statusFilter').val();
                    d.rating_filter = $('#ratingFilter').val();
                },
                error: function(xhr, error, code) {
                    console.error('DataTable AJAX error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Loading Error',
                        text: 'Failed to load reviews data. Please refresh the page.'
                    });
                }
            },
            columns: [
                { data: '0', orderable: false, searchable: false },
                { data: '1' },
                { data: '2' },
                { data: '3' },
                { data: '4' },
                { data: '5' },
                { data: '6', orderable: false },
                { data: '7', orderable: false, searchable: false }
            ],
            order: [[5, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: 'No reviews found',
                zeroRecords: 'No matching reviews found',
                search: 'Search reviews:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ reviews',
                infoEmpty: 'Showing 0 to 0 of 0 reviews',
                infoFiltered: '(filtered from _MAX_ total reviews)',
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
        $('#statusFilter, #ratingFilter').on('change', function() {
            table.ajax.reload();
        });

        // Clear filters
        $('#clearFilters').on('click', function() {
            $('#statusFilter, #ratingFilter').val('');
            table.ajax.reload();
            loadStatistics();
        });

        // Refresh stats manually
        $('#refreshStats').on('click', function() {
            loadStatistics();
            table.ajax.reload();
        });

        // Approve review
        $(document).on('click', '.approve-review', function() {
            const reviewId = $(this).data('id');
            
            Swal.fire({
                title: 'Approve Review',
                text: 'Are you sure you want to approve this review?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performReviewAction('approve_review', reviewId);
                }
            });
        });

        // Reject review
        $(document).on('click', '.reject-review', function() {
            const reviewId = $(this).data('id');
            
            Swal.fire({
                title: 'Reject Review',
                text: 'Are you sure you want to reject this review?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Reject',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performReviewAction('reject_review', reviewId);
                }
            });
        });

        // Delete review
        $(document).on('click', '.delete-review', function() {
            const reviewId = $(this).data('id');
            
            Swal.fire({
                title: 'Delete Review',
                text: 'Are you sure you want to delete this review? This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performReviewAction('delete_review', reviewId);
                }
            });
        });

        // Perform review action
        function performReviewAction(action, reviewId) {
            console.log('Performing action:', action, 'for review ID:', reviewId);
            
            $.ajax({
                url: 'reviews-management.php',
                type: 'POST',
                data: {
                    action: action,
                    review_id: reviewId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Response:', response);
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
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to perform action. Please try again.'
                    });
                }
            });
        }

        // Load statistics
        function loadStatistics() {
            $.ajax({
                url: 'reviews-management.php?action=get_stats',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        animateCounter('#totalReviews', response.stats.total);
                        animateCounter('#approvedReviews', response.stats.approved);
                        animateCounter('#pendingReviews', response.stats.pending);
                        animateCounter('#avgRating', response.stats.avg_rating.toFixed(1));
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
            const current = parseFloat($element.text()) || 0;
            
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
                    if ($(element).attr('id') === 'avgRating') {
                        $element.text(parseFloat(this.count).toFixed(1));
                    } else {
                        $element.text(Math.floor(this.count));
                    }
                },
                complete: function() {
                    if ($(element).attr('id') === 'avgRating') {
                        $element.text(parseFloat(target).toFixed(1));
                    } else {
                        $element.text(target);
                    }
                }
            });
        }
    });
    </script>
</body>
</html>