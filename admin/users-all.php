<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';

use App\auth\Admin;
use App\model\User;

if (!Admin::Check()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

$db = new MysqliDb();

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = isset($_GET['status']) ? (int)$_GET['status'] : null;

// Build query
if (!empty($search)) {
    $db->where("(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)", 
        ["%$search%", "%$search%", "%$search%", "%$search%"]);
}

if (!empty($role)) {
    $db->where('role', $role);
}

if ($status !== null) {
    $db->where('is_active', $status);
}

// Get all users with pagination and profile images
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$db->pageLimit = $perPage;
$db->join('user_profiles up', 'users.id = up.user_id', 'LEFT');
$db->orderBy('users.created_at', 'DESC');
// Ensure we get fresh data by adding a small random element to prevent caching
$users = $db->get('users', null, 'users.*, up.profile_image, up.updated_at as profile_updated');
$totalPages = $db->totalPages;

// Get roles for filter dropdown
$roles = $db->getValue('users', 'role', null, 'DISTINCT');
?>

<?php require __DIR__ . '/components/header.php'; ?>
<!-- Add DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    .status-badge {
        padding: 5px 10px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    .status-active {
        background-color: #d1fae5;
        color: #065f46;
    }
    .status-inactive {
        background-color: #fee2e2;
        color: #991b1b;
    }
    .role-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .role-admin {
        background-color: #dbeafe;
        color: #1e40af;
    }
    .role-customer {
        background-color: #e0f2fe;
        color: #075985;
    }
    .role-staff {
        background-color: #f0fdf4;
        color: #166534;
    }
    .action-btns .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .filter-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
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
                    <h1 class="mt-4">User Management</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Users</li>
                    </ol>

                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $_SESSION['message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <div class="card filter-card mb-4">
                        <div class="card-body">
                            <form method="get" action="" class="row g-3">
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, email or phone">
                                </div>
                                <div class="col-md-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role">
                                        <option value="">All Roles</option>
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?= htmlspecialchars($r) ?>" <?= $role === $r ? 'selected' : '' ?>>
                                                <?= ucfirst(htmlspecialchars($r)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All Statuses</option>
                                        <option value="1" <?= $status === 1 ? 'selected' : '' ?>>Active</option>
                                        <option value="0" <?= $status === 0 ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                                    <a href="users-all.php" class="btn btn-outline-secondary">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-users me-1"></i>
                                User List
                            </div>
                            <a href="user-add.php" class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i> Add New User
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="usersTable" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>User</th>
                                            <th>Contact</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $index => $user): 
                                            $fullName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
                                            $email = htmlspecialchars($user['email']);
                                            $phone = htmlspecialchars($user['phone'] ?? 'N/A');
                                            $role = $user['role'];
                                            $isActive = (int)$user['is_active'] === 1;
                                            $createdAt = new DateTime($user['created_at']);
                                            
                                            // Generate avatar with initials
                                            $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
                                            $avatarColor = '#' . substr(md5($user['email']), 0, 6);
                                        ?>
                                            <tr>
                                                <td><?= $index + 1 + (($page - 1) * $perPage) ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <?php if (!empty($user['profile_image'])): ?>
                                                                <img src="../assets/uploads/profiles/<?= htmlspecialchars($user['profile_image']) ?>?v=<?= time() ?>" 
                                                                     alt="Profile Picture" 
                                                                     class="rounded-circle border" 
                                                                     style="width: 40px; height: 40px; object-fit: cover;"
                                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                                <div class="d-none align-items-center justify-content-center rounded-circle" 
                                                                     style="width: 40px; height: 40px; background-color: <?= $avatarColor ?>; color: white;">
                                                                    <?= $initials ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="d-flex align-items-center justify-content-center rounded-circle" 
                                                                     style="width: 40px; height: 40px; background-color: <?= $avatarColor ?>; color: white;">
                                                                    <?= $initials ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?= $fullName ?></div>
                                                            <div class="text-muted small">ID: <?= $user['id'] ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <a href="mailto:<?= $email ?>" class="text-decoration-none">
                                                            <i class="fas fa-envelope me-1 text-muted"></i> <?= $email ?>
                                                        </a>
                                                        <?php if (!empty($phone)): ?>
                                                            <a href="tel:<?= $phone ?>" class="text-decoration-none">
                                                                <i class="fas fa-phone me-1 text-muted"></i> <?= $phone ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="role-badge role-<?= strtolower($role) ?>">
                                                        <?= ucfirst($role) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?= $isActive ? 'status-active' : 'status-inactive' ?>">
                                                        <i class="fas fa-<?= $isActive ? 'check-circle' : 'times-circle' ?> me-1"></i>
                                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="small text-muted">
                                                        <div><?= $createdAt->format('M d, Y') ?></div>
                                                        <div class="text-muted"><?= $createdAt->format('h:i A') ?></div>
                                                    </div>
                                                </td>
                                                <td class="action-btns">
                                                    <a href="user-view.php?id=<?= $user['id'] ?>" 
                                                       class="btn btn-sm btn-info text-white" 
                                                       title="View User">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="user-edit.php?id=<?= $user['id'] ?>" 
                                                       class="btn btn-sm btn-warning text-white" 
                                                       title="Edit User">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($_SESSION['userid'] != $user['id']): ?>
                                                        <a href="user-delete.php?id=<?= $user['id'] ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           title="Delete User"
                                                           onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Page navigation" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role) ? '&role=' . urlencode($role) : '' ?><?= $status !== null ? '&status=' . $status : '' ?>" 
                                               aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role) ? '&role=' . urlencode($role) : '' ?><?= $status !== null ? '&status=' . $status : '' ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role) ? '&role=' . urlencode($role) : '' ?><?= $status !== null ? '&status=' . $status : '' ?>" 
                                               aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Initialize DataTable
            $('#usersTable').DataTable({
                responsive: true,
                paging: false, // We're using custom pagination
                searching: false, // We're using custom search
                info: false, // Hide showing X of X entries
                order: [[5, 'desc']], // Sort by join date by default
                columnDefs: [
                    { orderable: false, targets: [6] } // Disable sorting on actions column
                ]
            });

            // Toggle user status
            $('.toggle-status').on('click', function(e) {
                e.preventDefault();
                const userId = $(this).data('id');
                const isActive = $(this).data('active');
                const newStatus = isActive ? 0 : 1;
                
                if (confirm(`Are you sure you want to ${isActive ? 'deactivate' : 'activate'} this user?`)) {
                    $.ajax({
                        url: 'ajax/update-user-status.php',
                        type: 'POST',
                        data: {
                            id: userId,
                            status: newStatus
                        },
                        success: function(response) {
                            location.reload();
                        },
                        error: function() {
                            alert('Error updating user status. Please try again.');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
