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

// Get current admin user data
$adminId = $_SESSION['userid'];
$db->where('id', $adminId);
$admin = $db->getOne('users');

// Get admin statistics
$totalUsers = $db->getValue('users', 'COUNT(*)') ?: 0;
$totalProducts = $db->getValue('products', 'COUNT(*)') ?: 0;
$totalOrders = $db->getValue('orders', 'COUNT(*)') ?: 0;
$todaysSales = $db->getValue('orders', 'SUM(total_amount)', 'DATE(created_at) = CURDATE() AND status = "completed"') ?: 0;

// Get recent activities (mock data)
$recentActivities = [
    ['action' => 'User Management', 'time' => '2 hours ago', 'icon' => 'users'],
    ['action' => 'Product Update', 'time' => '4 hours ago', 'icon' => 'box'],
    ['action' => 'Order Processing', 'time' => '6 hours ago', 'icon' => 'shopping-cart'],
    ['action' => 'System Login', 'time' => '8 hours ago', 'icon' => 'sign-in-alt']
];
?>

<?php require __DIR__ . '/components/header.php'; ?>

<style>
.profile-header {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    padding: 2rem 0;
    border-radius: 15px;
    margin-bottom: 2rem;
}
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border-left: 4px solid #4e73df;
    margin-bottom: 1.5rem;
    transition: transform 0.2s ease;
}
.stat-card:hover {
    transform: translateY(-2px);
}
.activity-item {
    display: flex;
    align-items-center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e3e6f0;
}
.activity-item:last-child {
    border-bottom: none;
}
.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    background: #f8f9fc;
    color: #4e73df;
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
                    <h1 class="mt-4">Admin Profile</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Admin Profile</li>
                    </ol>

                    <!-- Profile Header -->
                    <div class="profile-header text-center">
                        <div class="container">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="profile-avatar">
                                        <i class="fas fa-user-circle" style="font-size: 5rem;"></i>
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-start">
                                    <h2 class="mb-1"><?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></h2>
                                    <p class="mb-2">
                                        <i class="fas fa-envelope me-2"></i><?= htmlspecialchars($admin['email']) ?>
                                    </p>
                                    <p class="mb-0">
                                        <span class="badge bg-light text-dark fs-6">
                                            <i class="fas fa-crown me-1"></i><?= ucfirst($admin['role']) ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-3 text-md-end">
                                    <a href="profile-settings.php" class="btn btn-light">
                                        <i class="fas fa-edit me-1"></i>Edit Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Statistics Cards -->
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-users fa-2x text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalUsers) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-box fa-2x text-success"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Products</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalProducts) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-shopping-cart fa-2x text-info"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Orders</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalOrders) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-dollar-sign fa-2x text-warning"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Today's Sales</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">৳<?= number_format($todaysSales, 2) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Information -->
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-info-circle me-1"></i>Account Information
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Full Name:</strong> <?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></p>
                                            <p><strong>Email:</strong> <?= htmlspecialchars($admin['email']) ?></p>
                                            <p><strong>Role:</strong> <?= ucfirst($admin['role']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Phone:</strong> <?= htmlspecialchars($admin['phone'] ?? 'Not provided') ?></p>
                                            <p><strong>Account Created:</strong> <?= date('M j, Y', strtotime($admin['created_at'])) ?></p>
                                            <p><strong>Last Updated:</strong> <?= $admin['updated_at'] ? date('M j, Y', strtotime($admin['updated_at'])) : 'Never' ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-history me-1"></i>Recent Activity
                                </div>
                                <div class="card-body">
                                    <?php foreach ($recentActivities as $activity): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <i class="fas fa-<?= $activity['icon'] ?>"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold"><?= htmlspecialchars($activity['action']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($activity['time']) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="text-center mt-3">
                                        <a href="activity-log.php" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>View All Activity
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <i class="fas fa-bolt me-1"></i>Quick Actions
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="profile-settings.php" class="btn btn-outline-primary">
                                            <i class="fas fa-cog me-2"></i>Account Settings
                                        </a>
                                        <a href="activity-log.php" class="btn btn-outline-info">
                                            <i class="fas fa-history me-2"></i>Activity Log
                                        </a>
                                        <a href="users-all.php" class="btn btn-outline-success">
                                            <i class="fas fa-users me-2"></i>Manage Users
                                        </a>
                                        <a href="product-all.php" class="btn btn-outline-warning">
                                            <i class="fas fa-box me-2"></i>Manage Products
                                        </a>
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
</body>
</html>

<?php $db->disconnect(); ?>
