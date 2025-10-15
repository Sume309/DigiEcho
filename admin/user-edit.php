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

// Process form submission
// Ensure uploads directory exists
$uploadDir = __DIR__ . '/../assets/uploads/profiles/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $currentImage = $_POST['current_image'] ?? '';
    
    // Get current profile data before processing
    $currentProfile = null;
    if ($id) {
        $db->where('user_id', $id);
        $currentProfile = $db->getOne('user_profiles');
    }
    
    // Basic validation
    $errors = [];
    
    if (!$id) {
        $errors[] = 'Invalid user ID';
    }
    
    if (empty($firstName)) {
        $errors[] = 'First name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Valid email is required';
    } else {
        // Check if email already exists for another user
        $db->where('email', $email);
        $db->where('id', $id, '!=');
        if ($db->has('users')) {
            $errors[] = 'Email already exists';
        }
    }
    
    // Handle password update if provided
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $updatePassword = !empty($password);
    
    if ($updatePassword) {
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
    }
    
    // Handle file upload
    $newProfileImage = null;
    $removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] == '1';
    
    if ($removeImage) {
        // Delete old image if exists
        if (!empty($currentProfile['profile_image']) && file_exists($uploadDir . $currentProfile['profile_image'])) {
            unlink($uploadDir . $currentProfile['profile_image']);
        }
        $newProfileImage = ''; // Set empty to remove image
    } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($_FILES['profile_image']['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = 'Only JPG, PNG, GIF, and WebP files are allowed.';
        } elseif ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) { // 2MB max
            $errors[] = 'File size must be less than 2MB.';
        } else {
            // Delete old image if exists
            if (!empty($currentProfile['profile_image']) && file_exists($uploadDir . $currentProfile['profile_image'])) {
                unlink($uploadDir . $currentProfile['profile_image']);
            }
            
            // Generate unique filename
            $fileExt = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $fileName = 'profile_' . $id . '_' . time() . '.' . $fileExt;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
                $newProfileImage = $fileName;
            } else {
                $errors[] = 'Failed to upload image.';
            }
        }
    }

    if (empty($errors)) {
        $userData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($updatePassword) {
            $userData['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        // Update user table
        $db->where('id', $id);
        $userUpdated = $db->update('users', $userData);
        
        // Update profile table
        $profileData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($newProfileImage !== null) {
            $profileData['profile_image'] = $newProfileImage;
        }
        
        $db->where('user_id', $id);
        $profileUpdated = $db->update('user_profiles', $profileData);
        
        // Success if either user or profile was updated (or both)
        if ($userUpdated || $profileUpdated) {
            // Notify about user update
            $fullName = $firstName . ' ' . $lastName;
            notifyUserManagement(
                $id,
                $fullName,
                'updated',
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? null
            );
            
            $message = 'User updated successfully';
            $success = true;
            
            $_SESSION['message'] = $message;
            
            // Redirect to same page to show updated image
            header('Location: user-edit.php?id=' . $id . '&updated=1');
            exit;
        } else {
            $errors[] = 'Failed to update user. Error: ' . $db->getLastError();
        }
    }
    
    if (!empty($errors)) {
        $message = '<div class="alert alert-danger">' . implode('<br>', $errors) . '</div>';
    }
}

// Get user data for editing
$user = null;
$profile = null;
$showSuccessMessage = isset($_GET['updated']) && $_GET['updated'] == '1';

if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id) {
        $db->where('id', $id);
        $user = $db->getOne('users');
        
        if (!$user) {
            $_SESSION['error'] = 'User not found';
            header('Location: users-all.php');
            exit;
        }
        
        // Get user profile
        $db->where('user_id', $id);
        $profile = $db->getOne('user_profiles');
        
        if (!$profile) {
            // Create default profile
            $defaultProfile = [
                'user_id' => $id,
                'first_name' => $user['first_name'] ?? '',
                'last_name' => $user['last_name'] ?? '',
                'phone' => $user['phone'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $db->insert('user_profiles', $defaultProfile);
            $profile = $db->where('user_id', $id)->getOne('user_profiles');
        }
    } else {
        $_SESSION['error'] = 'Invalid user ID';
        header('Location: users-all.php');
        exit;
    }
} else {
    $_SESSION['error'] = 'No user ID provided';
    header('Location: users-all.php');
    exit;
}

// Available roles
$roles = ['admin' => 'Administrator', 'staff' => 'Staff', 'customer' => 'Customer'];
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
    #avatarPreview {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    #avatarPreview img {
        transition: transform 0.3s ease;
    }
    #avatarPreview:hover img {
        transform: scale(1.05);
    }
    .alert {
        border-radius: 8px;
    }
    .alert-success {
        border-left: 4px solid #28a745;
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
                    <h1 class="mt-4">Edit User</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="users-all.php">Users</a></li>
                        <li class="breadcrumb-item active">Edit User</li>
                    </ol>

                    <?php if ($showSuccessMessage): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>User updated successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

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
                                <form method="post" action="" enctype="multipart/form-data">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="remove_image" id="removeImageInput" value="0">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name"
                                                   value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($profile['profile_image'])): ?>
                                    <div class="text-center mb-3">
                                        <button type="button" id="removeImageBtn" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash-alt me-1"></i> Remove Current Image
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email"
                                               value="<?= htmlspecialchars($user['email']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                               value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                            <select class="form-select" id="role" name="role" required>
                                                <?php foreach ($roles as $value => $label): ?>
                                                    <option value="<?= $value ?>" <?= ($user['role'] === $value) ? 'selected' : '' ?>>
                                                        <?= $label ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                       value="1" <?= $user['is_active'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="is_active">Active User</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="border-top pt-3 mt-4">
                                        <h6>Change Password</h6>
                                        <p class="text-muted small">Leave blank to keep current password</p>
                                        
                                        <div class="mb-3">
                                            <label for="password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   placeholder="Leave blank to keep current password">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password" placeholder="Confirm new password">
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mt-4">
                                        <a href="users-all.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-1"></i> Back to Users
                                        </a>
                                        <div>
                                            <a href="user-delete.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-danger me-2"
                                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                <i class="fas fa-trash-alt me-1"></i> Delete
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i> Save Changes
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="form-section">
                                <h5>Account Status</h5>
                                <div class="text-center mb-3">
                                    <div class="position-relative d-inline-block">
                                        <?php
                                        $initials = strtoupper(substr($user['first_name'], 0, 1) . 
                                                    (isset($user['last_name'][0]) ? $user['last_name'][0] : ''));
                                        $avatarColor = '#' . substr(md5($user['email']), 0, 6);
                                        $hasImage = !empty($profile['profile_image']) && file_exists(__DIR__ . '/../assets/uploads/profiles/' . $profile['profile_image']);
                                        $imageUrl = $hasImage ? '../assets/uploads/profiles/' . htmlspecialchars($profile['profile_image']) . '?v=' . time() : '';
                                        ?>
                                        <div id="avatarPreview" class="mx-auto d-flex align-items-center justify-content-center rounded-circle overflow-hidden" 
                                             style="width: 120px; height: 120px; background-color: <?= $hasImage ? 'transparent' : $avatarColor ?>; color: white; font-size: 2.5rem; margin-bottom: 1rem; border: 3px solid #e3e6f0;">
                                            <?php if ($hasImage): ?>
                                                <img src="<?= $imageUrl ?>" alt="Profile Image" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <?= $initials ?>
                                            <?php endif; ?>
                                        </div>
                                        <label for="profileImageInput" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2" style="cursor: pointer;">
                                            <i class="fas fa-camera"></i>
                                            <input type="file" id="profileImageInput" name="profile_image" accept="image/*" class="d-none">
                                            <input type="hidden" name="current_image" value="<?= htmlspecialchars($profile['profile_image'] ?? '') ?>">
                                        </label>
                                    </div>
                                    <h5 class="mb-1"><?= htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')) ?></h5>
                                    <span class="badge bg-<?= $user['is_active'] ? 'success' : 'danger' ?> mb-3">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                    <p class="text-muted mb-0"><?= htmlspecialchars($user['email']) ?></p>
                                    <?php if (!empty($user['phone'])): ?>
                                        <p class="text-muted"><?= htmlspecialchars($user['phone']) ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <label class="form-label">Account Created</label>
                                    <p class="mb-0">
                                        <?= date('F j, Y', strtotime($user['created_at'])) ?>
                                        <small class="d-block text-muted">
                                            <?= date('g:i A', strtotime($user['created_at'])) ?>
                                        </small>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Last Updated</label>
                                    <p class="mb-0">
                                        <?= !empty($user['updated_at']) ? date('F j, Y', strtotime($user['updated_at'])) : 'Never' ?>
                                        <?php if (!empty($user['updated_at'])): ?>
                                            <small class="d-block text-muted">
                                                <?= date('g:i A', strtotime($user['updated_at'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <div class="alert alert-info">
                                    <small class="d-block"><i class="fas fa-info-circle me-1"></i> Last login: 
                                        <?= !empty($user['last_login']) ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never' ?>
                                    </small>
                                </div>
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
    <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
    
    <script>
    // Image preview functionality
    document.addEventListener('DOMContentLoaded', function() {
        const profileImageInput = document.getElementById('profileImageInput');
        const avatarPreview = document.getElementById('avatarPreview');
        const removeImageBtn = document.getElementById('removeImageBtn');
        const removeImageInput = document.getElementById('removeImageInput');
        
        // Store original avatar content for reset
        const originalAvatarContent = avatarPreview.innerHTML;
        const originalAvatarStyle = {
            backgroundColor: avatarPreview.style.backgroundColor,
            backgroundImage: avatarPreview.style.backgroundImage
        };
        
        // Image upload preview
        if (profileImageInput && avatarPreview) {
            profileImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    const fileType = file.type;
                    
                    if (!allowedTypes.includes(fileType)) {
                        alert('Please select a valid image file (JPG, PNG, GIF, or WebP)');
                        this.value = '';
                        return;
                    }
                    
                    // Validate file size (2MB max)
                    if (file.size > 2 * 1024 * 1024) {
                        alert('File size must be less than 2MB');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        avatarPreview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;">`;
                        avatarPreview.style.backgroundColor = 'transparent';
                        
                        // Reset remove image flag
                        if (removeImageInput) {
                            removeImageInput.value = '0';
                        }
                        
                        // Show remove button if it exists
                        if (removeImageBtn) {
                            removeImageBtn.style.display = 'inline-block';
                            removeImageBtn.textContent = 'Remove New Image';
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
        
        // Remove image functionality
        if (removeImageBtn && removeImageInput) {
            removeImageBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const confirmMessage = profileImageInput.files.length > 0 ? 
                    'Remove the selected image?' : 
                    'Are you sure you want to remove the current profile image?';
                    
                if (confirm(confirmMessage)) {
                    // Clear file input
                    if (profileImageInput) {
                        profileImageInput.value = '';
                    }
                    
                    // Reset to initials or hide remove button
                    const initials = '<?= addslashes($initials) ?>';
                    const avatarColor = '<?= addslashes($avatarColor) ?>';
                    
                    avatarPreview.innerHTML = initials;
                    avatarPreview.style.backgroundColor = avatarColor;
                    avatarPreview.style.backgroundImage = 'none';
                    
                    // Set remove flag if there was an existing image
                    const hasExistingImage = <?= !empty($profile['profile_image']) ? 'true' : 'false' ?>;
                    if (hasExistingImage) {
                        removeImageInput.value = '1';
                    }
                    
                    // Hide remove button
                    this.style.display = 'none';
                }
            });
        }
        
        // Auto-hide success message after 5 seconds
        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            setTimeout(function() {
                successAlert.style.transition = 'opacity 0.5s';
                successAlert.style.opacity = '0';
                setTimeout(function() {
                    successAlert.remove();
                }, 500);
            }, 5000);
        }
    });
    </script>
    
    <script>
        // Password match validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePassword() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords don't match");
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        if (password && confirmPassword) {
            password.onchange = validatePassword;
            confirmPassword.onkeyup = validatePassword;
        }
        
        // Form submission feedback
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function() {
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                }
            });
        }
    </script>
</body>
</html>
