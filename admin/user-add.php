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
$message = '';
$success = false;

// Available roles
$roles = ['admin' => 'Administrator', 'staff' => 'Staff', 'customer' => 'Customer'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    $errors = [];
    
    if (empty($firstName)) {
        $errors[] = 'First name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Valid email is required';
    } else {
        // Check if email already exists
        $db->where('email', $email);
        if ($db->has('users')) {
            $errors[] = 'Email already exists';
        }
    }
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        $userData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'is_active' => $isActive,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $id = $db->insert('users', $userData);
        
        if ($id) {
            // Notify about new user creation
            $fullName = $firstName . ' ' . $lastName;
            notifyUserManagement(
                $id,
                $fullName,
                'created',
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? null
            );
            
            $message = 'User created successfully';
            $success = true;
            $_SESSION['message'] = $message;
            header('Location: users-all.php');
            exit;
        } else {
            $errors[] = 'Failed to create user: ' . $db->getLastError();
        }
    }
    
    if (!empty($errors)) {
        $message = '<div class="alert alert-danger">' . implode('<br>', $errors) . '</div>';
    }
}
?>

<?php require __DIR__ . '/components/header.php'; ?>
<style>
    .user-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 1rem;
    }
    .form-section {
        background: #fff;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        margin-bottom: 2rem;
    }
    .form-section h5 {
        color: #4e73df;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e3e6f0;
    }
    .password-strength {
        height: 5px;
        margin-top: 0.25rem;
        background-color: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
    }
    .password-strength-bar {
        height: 100%;
        width: 0;
        transition: width 0.3s ease;
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
                    <h1 class="mt-4">Add New User</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="users-all.php">Users</a></li>
                        <li class="breadcrumb-item active">Add New</li>
                    </ol>

                    <?php if (!empty($message) && !$success): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="form-section">
                                <h5>User Information</h5>
                                <form method="post" action="" id="userForm">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name"
                                                   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email"
                                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                            <select class="form-select" id="role" name="role" required>
                                                <?php foreach ($roles as $value => $label): ?>
                                                    <option value="<?= $value ?>" <?= (($_POST['role'] ?? '') === $value) ? 'selected' : '' ?>>
                                                        <?= $label ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                       value="1" <?= isset($_POST['is_active']) ? 'checked' : 'checked' ?>>
                                                <label class="form-check-label" for="is_active">Active User</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="border-top pt-3 mt-4">
                                        <h6>Account Password</h6>
                                        <p class="text-muted small">Password must be at least 8 characters long</p>
                                        
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <div class="password-strength">
                                                <div class="password-strength-bar bg-danger" style="width: 0%"></div>
                                            </div>
                                            <div class="form-text">Use 8 or more characters with a mix of letters, numbers & symbols</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mt-4">
                                        <a href="users-all.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-user-plus me-2"></i>Create User
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="form-section">
                                <h5>Instructions</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <i class="fas fa-info-circle text-primary me-2"></i>
                                        <strong>Required Fields:</strong> First Name and Email are mandatory
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-lock text-primary me-2"></i>
                                        <strong>Password Security:</strong> Minimum 8 characters required
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-user-check text-primary me-2"></i>
                                        <strong>Roles:</strong> Administrator has full access, Staff has limited access
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-envelope text-primary me-2"></i>
                                        <strong>Email Uniqueness:</strong> Each email must be unique in the system
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>
    
    <script src="<?= settings()['adminpage'] ?>assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
    
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.querySelector('.password-strength-bar');
            let strength = 0;
            
            if (password.length >= 8) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if (strength < 50) {
                strengthBar.className = 'password-strength-bar bg-danger';
            } else if (strength < 75) {
                strengthBar.className = 'password-strength-bar bg-warning';
            } else {
                strengthBar.className = 'password-strength-bar bg-success';
            }
        });
    </script>
</body>
</html>