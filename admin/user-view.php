<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

$db = new MysqliDb();

// Get user ID from URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['error'] = 'Invalid user ID';
    header('Location: users-all.php');
    exit;
}

// Get user data with profile
$db->join('user_profiles up', 'users.id = up.user_id', 'LEFT');
$db->where('users.id', $id);
$user = $db->getOne('users', 'users.*, up.profile_image');

if (!$user) {
    $_SESSION['error'] = 'User not found';
    header('Location: users-all.php');
    exit;
}

// Format dates
$createdAt = new DateTime($user['created_at']);
$updatedAt = $user['updated_at'] ? new DateTime($user['updated_at']) : null;
$lastLogin = $user['last_login'] ? new DateTime($user['last_login']) : null;

// Get user role name
$roleNames = [
    'admin' => 'Administrator',
    'staff' => 'Staff',
    'customer' => 'Customer'
];

$roleName = $roleNames[$user['role']] ?? 'Unknown';

// Get user's recent activity (example: last 5 orders)
$db->where('user_id', $id);
$db->orderBy('created_at', 'DESC');
$recentOrders = $db->get('orders', 5);
?>

<?php require __DIR__ . '/components/header.php'; ?>
<style>
    .profile-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 8px;
    }
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 1rem;
    }
    .stat-card {
        border-left: 4px solid #4e73df;
        padding: 1rem;
        margin-bottom: 1.5rem;
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    }
    .stat-card .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #5a5c69;
    }
    .stat-card .stat-label {
        font-size: 0.875rem;
        color: #858796;
        text-transform: uppercase;
        font-weight: 600;
    }
    .activity-item {
        position: relative;
        padding-left: 1.5rem;
        padding-bottom: 1.5rem;
        border-left: 1px solid #e3e6f0;
    }
    .activity-item:last-child {
        padding-bottom: 0;
        border-left: 1px solid transparent;
    }
    .activity-item:before {
        content: '';
        position: absolute;
        left: -6px;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #4e73df;
    }
    .activity-time {
        font-size: 0.8rem;
        color: #b7b9cc;
    }
    .badge-status {
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.35rem;
    }
    .badge-active {
        background-color: #1cc88a1a;
        color: #1cc88a;
    }
    .badge-inactive {
        background-color: #e74a3b1a;
        color: #e74a3b;
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
                    <nav aria-label="breadcrumb" class="mt-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="users-all.php">Users</a></li>
                            <li class="breadcrumb-item active">User Details</li>
                        </ol>
                    </nav>

                    <!-- Header Section -->
                    <div class="profile-header">
                        <div class="container">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <?php
                                    $initials = strtoupper(substr($user['first_name'], 0, 1) . 
                                                (isset($user['last_name'][0]) ? $user['last_name'][0] : ''));
                                    $avatarColor = '#' . substr(md5($user['email']), 0, 6);
                                    $hasImage = !empty($user['profile_image']) && file_exists(__DIR__ . '/../assets/uploads/profiles/' . $user['profile_image']);
                                    ?>
                                    <?php if ($hasImage): ?>
                                        <img src="../assets/uploads/profiles/<?= htmlspecialchars($user['profile_image']) ?>" 
                                             alt="Profile Picture" 
                                             class="profile-avatar"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="mx-auto d-none align-items-center justify-content-center rounded-circle" 
                                             style="width: 120px; height: 120px; background-color: <?= $avatarColor ?>; color: white; font-size: 3rem; border: 4px solid rgba(255, 255, 255, 0.2);">
                                            <?= $initials ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="mx-auto d-flex align-items-center justify-content-center rounded-circle" 
                                             style="width: 120px; height: 120px; background-color: <?= $avatarColor ?>; color: white; font-size: 3rem; border: 4px solid rgba(255, 255, 255, 0.2);">
                                            <?= $initials ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h2 class="mb-1"><?= htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')) ?></h2>
                                    <p class="mb-2">
                                        <span class="badge bg-<?= $user['is_active'] ? 'success' : 'danger' ?> me-2">
                                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                        <span class="badge bg-info"><?= $roleName ?></span>
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-envelope me-2"></i> <?= htmlspecialchars($user['email']) ?><br>
                                        <?php if (!empty($user['phone'])): ?>
                                            <i class="fas fa-phone me-2"></i> <?= htmlspecialchars($user['phone']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <a href="user-edit.php?id=<?= $user['id'] ?>" class="btn btn-light me-2">
                                        <i class="fas fa-edit me-1"></i> Edit Profile
                                    </a>
                                    <a href="users-all.php" class="btn btn-outline-light">
                                        <i class="fas fa-arrow-left me-1"></i> Back to Users
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-lg-8">
                            <!-- Account Details -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Account Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-3">
                                                <strong>Member Since:</strong><br>
                                                <span class="text-muted">
                                                    <?= $createdAt->format('F j, Y') ?>
                                                    <small class="d-block text-muted">
                                                        <?= $createdAt->format('g:i A') ?>
                                                    </small>
                                                </span>
                                            </p>
                                            
                                            <p class="mb-3">
                                                <strong>Last Updated:</strong><br>
                                                <span class="text-muted">
                                                    <?= $updatedAt ? $updatedAt->format('F j, Y') : 'Never' ?>
                                                    <?php if ($updatedAt): ?>
                                                        <small class="d-block text-muted">
                                                            <?= $updatedAt->format('g:i A') ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-3">
                                                <strong>Last Login:</strong><br>
                                                <span class="text-muted">
                                                    <?= $lastLogin ? $lastLogin->format('F j, Y g:i A') : 'Never logged in' ?>
                                                </span>
                                            </p>
                                            
                                            <p class="mb-3">
                                                <strong>Status:</strong><br>
                                                <span class="badge-status <?= $user['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                                    <i class="fas fa-<?= $user['is_active'] ? 'check-circle' : 'times-circle' ?> me-1"></i>
                                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activity -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($recentOrders)): ?>
                                        <div class="timeline">
                                            <?php foreach ($recentOrders as $order): 
                                                $orderDate = new DateTime($order['created_at']);
                                            ?>
                                                <div class="activity-item">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <h6 class="mb-0">
                                                            Order #<?= $order['id'] ?>
                                                            <span class="badge bg-<?= 
                                                                $order['status'] === 'completed' ? 'success' : 
                                                                ($order['status'] === 'processing' ? 'primary' : 'secondary') 
                                                            ?> ms-2">
                                                                <?= ucfirst($order['status']) ?>
                                                            </span>
                                                        </h6>
                                                        <small class="activity-time">
                                                            <?= $orderDate->format('M j, Y g:i A') ?>
                                                        </small>
                                                    </div>
                                                    <p class="mb-0">
                                                        <?= number_format($order['total_amount'], 2) ?> ৳
                                                        <span class="text-muted ms-2">
                                                            <?= $order['item_count'] ?? 0 ?> items
                                                        </span>
                                                    </p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="text-center mt-3">
                                            <a href="orders.php?user_id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                View All Orders
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <div class="text-muted mb-3">
                                                <i class="fas fa-inbox fa-3x"></i>
                                            </div>
                                            <p class="mb-0">No recent activity found</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-lg-4">
                            <!-- Account Status -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Account Status</h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-4">
                                        <div class="d-flex justify-content-around mb-3">
                                            <div class="text-center">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Total Orders
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?= $db->where('user_id', $user['id'])->getValue('orders', 'count(*)') ?: 0 ?>
                                                </div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Total Spent
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?= number_format($db->where('user_id', $user['id'])->getValue('orders', 'SUM(total_amount)') ?: 0, 2) ?> ৳
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label d-block">Email Verified</label>
                                        <span class="badge bg-<?= $user['email_verified_at'] ? 'success' : 'warning' ?>">
                                            <?= $user['email_verified_at'] ? 'Verified' : 'Not Verified' ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label d-block">Two-Factor Authentication</label>
                                        <span class="badge bg-<?= $user['two_factor_enabled'] ? 'success' : 'secondary' ?>">
                                            <?= $user['two_factor_enabled'] ? 'Enabled' : 'Disabled' ?>
                                        </span>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-envelope me-1"></i> Send Email
                                        </a>
                                        <?php if (!empty($user['phone'])): ?>
                                            <a href="tel:<?= htmlspecialchars($user['phone']) ?>" class="btn btn-outline-success">
                                                <i class="fas fa-phone me-1"></i> Call User
                                            </a>
                                        <?php endif; ?>
                                        <a href="user-edit.php?id=<?= $user['id'] ?>" class="btn btn-primary">
                                            <i class="fas fa-edit me-1"></i> Edit Profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- User Notes -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Admin Notes</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($user['admin_notes'])): ?>
                                        <div class="mb-3 p-3 bg-light rounded">
                                            <?= nl2br(htmlspecialchars($user['admin_notes'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted font-italic">No notes available for this user.</p>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                                        <i class="fas fa-plus me-1"></i> Add Note
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <!-- Add Note Modal -->
            <div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addNoteModalLabel">Add Admin Note</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="update-user-notes.php" method="post">
                            <div class="modal-body">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <div class="mb-3">
                                    <label for="adminNotes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="adminNotes" name="admin_notes" rows="5"><?= htmlspecialchars($user['admin_notes'] ?? '') ?></textarea>
                                    <div class="form-text">These notes are only visible to administrators.</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Note</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="<?= settings()['adminpage'] ?>assets/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
    
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>
