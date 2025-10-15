<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';

// Include notification helper
require_once __DIR__ . '/components/notification_helper.php';

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

// Prevent deleting own account
if ($id == $_SESSION['userid']) {
    $_SESSION['error'] = 'You cannot delete your own account while logged in';
    header('Location: users-all.php');
    exit;
}

// Check if user exists and get profile
$db->join('user_profiles up', 'users.id = up.user_id', 'LEFT');
$db->where('users.id', $id);
$user = $db->getOne('users', 'users.*, up.profile_image');

if (!$user) {
    $_SESSION['error'] = 'User not found';
    header('Location: users-all.php');
    exit;
}

// Check for related records before deletion
$db->where('user_id', $id);
$hasOrders = $db->has('orders');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = $_POST['confirm'] ?? '';
    
    if ($confirm === 'yes') {
        try {
            // Start transaction
            $db->startTransaction();
            
            // Delete profile image file if exists
            if (!empty($user['profile_image'])) {
                $imagePath = __DIR__ . '/../assets/uploads/profiles/' . $user['profile_image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            // Delete related records first
            $db->where('user_id', $id);
            $db->delete('user_profiles');
            
            // Delete the user
            $db->where('id', $id);
            $result = $db->delete('users');
            
            if ($result) {
                $db->commit();
                
                // Notify about user deletion
                $fullName = $user['first_name'] . ' ' . ($user['last_name'] ?? '');
                notifyUserManagement(
                    $id,
                    $fullName,
                    'deleted',
                    $_SESSION['user_id'] ?? null,
                    $_SESSION['username'] ?? null
                );
                
                $_SESSION['message'] = 'User deleted successfully';
                header('Location: users-all.php');
                exit;
            } else {
                throw new Exception('Failed to delete user: ' . $db->getLastError());
            }
        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['error'] = 'Error deleting user: ' . $e->getMessage();
            header('Location: users-all.php');
            exit;
        }
    } else {
        $_SESSION['message'] = 'User deletion cancelled';
        header('Location: users-all.php');
        exit;
    }
}
?>

<?php require __DIR__ . '/components/header.php'; ?>

<style>
    .delete-container {
        max-width: 600px;
        margin: 2rem auto;
    }
    .delete-card {
        border: none;
        border-radius: 8px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    .delete-card-header {
        background-color: #e74a3b;
        color: white;
        border-radius: 8px 8px 0 0 !important;
    }
    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto 1rem;
        display: block;
    }
    .impact-warning {
        border-left: 4px solid #f6c23e;
        background-color: #f8f9fc;
        padding: 1rem;
        margin: 1rem 0;
        border-radius: 4px;
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
                            <li class="breadcrumb-item active">Delete User</li>
                        </ol>
                    </nav>

                    <div class="delete-container">
                        <div class="card delete-card">
                            <div class="card-header delete-card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Confirm User Deletion
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <?php
                                    $initials = strtoupper(substr($user['first_name'], 0, 1) . 
                                                (isset($user['last_name'][0]) ? $user['last_name'][0] : ''));
                                    $avatarColor = '#' . substr(md5($user['email']), 0, 6);
                                    $hasImage = !empty($user['profile_image']) && file_exists(__DIR__ . '/../assets/uploads/profiles/' . $user['profile_image']);
                                    ?>
                                    <?php if ($hasImage): ?>
                                        <img src="../assets/uploads/profiles/<?= htmlspecialchars($user['profile_image']) ?>" 
                                             alt="Profile Picture" 
                                             class="user-avatar"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="mx-auto d-none align-items-center justify-content-center rounded-circle" 
                                             style="width: 80px; height: 80px; background-color: <?= $avatarColor ?>; color: white; font-size: 2rem; margin-bottom: 1rem;">
                                            <?= $initials ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="mx-auto d-flex align-items-center justify-content-center rounded-circle" 
                                             style="width: 80px; height: 80px; background-color: <?= $avatarColor ?>; color: white; font-size: 2rem; margin-bottom: 1rem;">
                                            <?= $initials ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                    <h4 class="mb-1"><?= htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')) ?></h4>
                                    <p class="text-muted mb-0"><?= htmlspecialchars($user['email']) ?></p>
                                    <p class="mb-0">
                                        <span class="badge bg-<?= $user['is_active'] ? 'success' : 'danger' ?> mt-2">
                                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </p>
                                </div>

                                <?php if ($hasOrders): ?>
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Important Notice</h6>
                                        <p class="mb-0">
                                            This user has <?= $hasOrders ?> order(s) in the system. Deleting this user will remove all associated data.
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <div class="impact-warning">
                                    <h6><i class="fas fa-exclamation-circle me-2"></i>This action cannot be undone</h6>
                                    <p class="mb-0">
                                        All user data, including orders, addresses, and other related information will be permanently deleted.
                                    </p>
                                </div>

                                <form method="post" class="mt-4">
                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" id="confirmDelete" name="confirm" value="yes" required>
                                        <label class="form-check-label" for="confirmDelete">
                                            I understand that this action cannot be undone and I want to delete this user
                                        </label>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="users-all.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-1"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-danger" id="deleteButton">
                                            <i class="fas fa-trash-alt me-1"></i> Delete User Permanently
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer text-muted text-center">
                                <small>User ID: <?= $user['id'] ?> • Created: <?= date('M j, Y', strtotime($user['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="<?= settings()['adminpage'] ?>assets/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
        // Disable form submission if the checkbox is not checked
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const deleteButton = document.getElementById('deleteButton');
            const confirmCheckbox = document.getElementById('confirmDelete');
            
            if (form && deleteButton && confirmCheckbox) {
                form.addEventListener('submit', function(e) {
                    if (!confirmCheckbox.checked) {
                        e.preventDefault();
                        alert('Please confirm that you understand this action cannot be undone');
                        return false;
                    }
                    
                    // Change button state to show loading
                    deleteButton.disabled = true;
                    deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';
                    return true;
                });
                
                // Add confirmation dialog
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you absolutely sure you want to delete this user? This action cannot be undone.')) {
                        e.preventDefault();
                        deleteButton.disabled = false;
                        deleteButton.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Delete User Permanently';
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>