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
$message = '';
$success = false;

// Ensure uploads directory exists
$uploadDir = __DIR__ . '/../assets/uploads/profiles/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Get current admin user data
$adminId = $_SESSION['userid'];
$db->where('id', $adminId);
$admin = $db->getOne('users');

// Get or create admin profile
$db->where('user_id', $adminId);
$profile = $db->getOne('user_profiles');

if (!$profile) {
    // Create default profile for admin
    $defaultProfile = [
        'user_id' => $adminId,
        'first_name' => $admin['first_name'] ?? '',
        'last_name' => $admin['last_name'] ?? '',
        'phone' => $admin['phone'] ?? '',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    $db->insert('user_profiles', $defaultProfile);
    $profile = $db->where('user_id', $adminId)->getOne('user_profiles');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $jobTitle = trim($_POST['job_title'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zipCode = trim($_POST['zip_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $twitter = trim($_POST['twitter'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');
    $timezone = $_POST['timezone'] ?? 'Asia/Dhaka';
    $language = $_POST['language'] ?? 'en';
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $smsNotifications = isset($_POST['sms_notifications']) ? 1 : 0;
    $twoFactorAuth = isset($_POST['two_factor_auth']) ? 1 : 0;
    
    $errors = [];
    
    if (empty($firstName)) {
        $errors[] = 'First name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Valid email is required';
    } else {
        // Check if email already exists for another user
        $db->where('email', $email);
        $db->where('id', $adminId, '!=');
        if ($db->has('users')) {
            $errors[] = 'Email already exists';
        }
    }
    
    // Validate website URL if provided
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid website URL';
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
    
    // Handle profile image upload
    $newProfileImage = null;
    $removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] == '1';
    
    if ($removeImage) {
        // Delete old image if exists
        if (!empty($profile['profile_image']) && file_exists($uploadDir . $profile['profile_image'])) {
            unlink($uploadDir . $profile['profile_image']);
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
            if (!empty($profile['profile_image']) && file_exists($uploadDir . $profile['profile_image'])) {
                unlink($uploadDir . $profile['profile_image']);
            }
            
            // Generate unique filename
            $fileExt = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $fileName = 'admin_' . $adminId . '_' . time() . '.' . $fileExt;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
                $newProfileImage = $fileName;
            } else {
                $errors[] = 'Failed to upload image.';
            }
        }
    }
    
    if (empty($errors)) {
        // Update users table
        $updateData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($updatePassword) {
            $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $db->where('id', $adminId);
        $userUpdated = $db->update('users', $updateData);
        
        // Update profile table
        $profileData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'job_title' => $jobTitle,
            'department' => $department,
            'bio' => $bio,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip_code' => $zipCode,
            'country' => $country,
            'website' => $website,
            'linkedin' => $linkedin,
            'twitter' => $twitter,
            'facebook' => $facebook,
            'timezone' => $timezone,
            'language' => $language,
            'email_notifications' => $emailNotifications,
            'sms_notifications' => $smsNotifications,
            'two_factor_auth' => $twoFactorAuth,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($newProfileImage !== null) {
            $profileData['profile_image'] = $newProfileImage;
        }
        
        $db->where('user_id', $adminId);
        $profileUpdated = $db->update('user_profiles', $profileData);
        
        if ($userUpdated || $profileUpdated) {
            $message = 'Profile updated successfully';
            $success = true;
            // Refresh admin data
            $db->where('id', $adminId);
            $admin = $db->getOne('users');
            $db->where('user_id', $adminId);
            $profile = $db->getOne('user_profiles');
        } else {
            $errors[] = 'Failed to update profile: ' . $db->getLastError();
        }
    }
    
    if (!empty($errors)) {
        $message = implode('<br>', $errors);
    }
}
?>

<?php require __DIR__ . '/components/header.php'; ?>

<style>
.profile-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}
.profile-header {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    padding: 2rem;
}
.section-divider {
    border-top: 2px solid #e3e6f0;
    margin: 2rem 0;
    padding-top: 2rem;
}
.section-title {
    color: #4e73df;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}
.section-title i {
    margin-right: 0.5rem;
}
.profile-image-section {
    text-align: center;
    margin-bottom: 2rem;
}
.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #e3e6f0;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}
.profile-avatar:hover {
    border-color: #4e73df;
}
.upload-btn {
    position: relative;
    overflow: hidden;
    display: inline-block;
}
.upload-btn input[type=file] {
    position: absolute;
    left: -9999px;
}
.form-section {
    background: #f8f9fc;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
}
.social-input {
    position: relative;
}
.social-input .input-group-text {
    background: #4e73df;
    color: white;
    border: none;
}
.notification-card {
    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
    color: white;
    border-radius: 10px;
    padding: 1.5rem;
}
.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}
.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}
.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}
input:checked + .slider {
    background-color: #4e73df;
}
input:checked + .slider:before {
    transform: translateX(26px);
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
                    <h1 class="mt-4">Account Settings</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Account Settings</li>
                    </ol>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?= $success ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card profile-card">
                                <div class="profile-header text-center">
                                    <h2><i class="fas fa-user-cog me-2"></i>Admin Account Settings</h2>
                                    <p class="mb-0">Manage your profile information, preferences, and security settings</p>
                                </div>
                                
                                <div class="card-body p-4">
                                    <form method="post" action="" enctype="multipart/form-data">
                                        <input type="hidden" name="remove_image" id="removeImageInput" value="0">
                                        
                                        <!-- Profile Image Section -->
                                        <div class="profile-image-section">
                                            <h5 class="section-title">
                                                <i class="fas fa-camera"></i>Profile Picture
                                            </h5>
                                            <?php
                                            $hasImage = !empty($profile['profile_image']) && file_exists($uploadDir . $profile['profile_image']);
                                            $initials = strtoupper(substr($admin['first_name'], 0, 1) . 
                                                        (isset($admin['last_name'][0]) ? $admin['last_name'][0] : ''));
                                            $avatarColor = '#' . substr(md5($admin['email']), 0, 6);
                                            ?>
                                            <div id="avatarPreview" class="d-inline-block position-relative">
                                                <?php if ($hasImage): ?>
                                                    <img src="../assets/uploads/profiles/<?= htmlspecialchars($profile['profile_image']) ?>?v=<?= time() ?>" 
                                                         alt="Profile Picture" class="profile-avatar">
                                                <?php else: ?>
                                                    <div class="profile-avatar d-flex align-items-center justify-content-center" 
                                                         style="background-color: <?= $avatarColor ?>; color: white; font-size: 2.5rem;">
                                                        <?= $initials ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mt-3">
                                                <label for="profileImageInput" class="btn btn-primary upload-btn">
                                                    <i class="fas fa-upload me-2"></i>Upload New Picture
                                                    <input type="file" id="profileImageInput" name="profile_image" accept="image/*">
                                                </label>
                                                <?php if ($hasImage): ?>
                                                    <button type="button" id="removeImageBtn" class="btn btn-outline-danger ms-2">
                                                        <i class="fas fa-trash me-2"></i>Remove Picture
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted d-block mt-2">Supported formats: JPG, PNG, GIF, WebP (Max: 2MB)</small>
                                        </div>

                                        <!-- Basic Information -->
                                        <div class="form-section">
                                            <h5 class="section-title">
                                                <i class="fas fa-user"></i>Basic Information
                                            </h5>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                                           value="<?= htmlspecialchars($admin['first_name']) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="last_name" class="form-label">Last Name</label>
                                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                                           value="<?= htmlspecialchars($admin['last_name'] ?? '') ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                                    <input type="email" class="form-control" id="email" name="email" 
                                                           value="<?= htmlspecialchars($admin['email']) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="phone" class="form-label">Phone Number</label>
                                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                                           value="<?= htmlspecialchars($admin['phone'] ?? '') ?>" 
                                                           placeholder="+880 1XXX-XXXXXX">
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="job_title" class="form-label">Job Title</label>
                                                    <input type="text" class="form-control" id="job_title" name="job_title" 
                                                           value="<?= htmlspecialchars($profile['job_title'] ?? '') ?>" 
                                                           placeholder="e.g., System Administrator">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="department" class="form-label">Department</label>
                                                    <input type="text" class="form-control" id="department" name="department" 
                                                           value="<?= htmlspecialchars($profile['department'] ?? '') ?>" 
                                                           placeholder="e.g., IT Department">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="bio" class="form-label">Bio / Description</label>
                                                <textarea class="form-control" id="bio" name="bio" rows="3" 
                                                          placeholder="Brief description about yourself..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                                            </div>
                                        </div>

                                        <!-- Address Information -->
                                        <div class="form-section">
                                            <h5 class="section-title">
                                                <i class="fas fa-map-marker-alt"></i>Address Information
                                            </h5>
                                            <div class="mb-3">
                                                <label for="address" class="form-label">Street Address</label>
                                                <input type="text" class="form-control" id="address" name="address" 
                                                       value="<?= htmlspecialchars($profile['address'] ?? '') ?>" 
                                                       placeholder="House/Building, Street, Area">
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="city" class="form-label">City</label>
                                                    <input type="text" class="form-control" id="city" name="city" 
                                                           value="<?= htmlspecialchars($profile['city'] ?? '') ?>" 
                                                           placeholder="e.g., Dhaka">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="state" class="form-label">State/Division</label>
                                                    <input type="text" class="form-control" id="state" name="state" 
                                                           value="<?= htmlspecialchars($profile['state'] ?? '') ?>" 
                                                           placeholder="e.g., Dhaka Division">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                                                    <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                                           value="<?= htmlspecialchars($profile['zip_code'] ?? '') ?>" 
                                                           placeholder="e.g., 1000">
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="country" class="form-label">Country</label>
                                                <select class="form-select" id="country" name="country">
                                                    <option value="">Select Country</option>
                                                    <option value="BD" <?= ($profile['country'] ?? '') == 'BD' ? 'selected' : '' ?>>Bangladesh</option>
                                                    <option value="IN" <?= ($profile['country'] ?? '') == 'IN' ? 'selected' : '' ?>>India</option>
                                                    <option value="PK" <?= ($profile['country'] ?? '') == 'PK' ? 'selected' : '' ?>>Pakistan</option>
                                                    <option value="US" <?= ($profile['country'] ?? '') == 'US' ? 'selected' : '' ?>>United States</option>
                                                    <option value="GB" <?= ($profile['country'] ?? '') == 'GB' ? 'selected' : '' ?>>United Kingdom</option>
                                                    <option value="CA" <?= ($profile['country'] ?? '') == 'CA' ? 'selected' : '' ?>>Canada</option>
                                                    <option value="AU" <?= ($profile['country'] ?? '') == 'AU' ? 'selected' : '' ?>>Australia</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Social Media & Website -->
                                        <div class="form-section">
                                            <h5 class="section-title">
                                                <i class="fas fa-globe"></i>Social Media & Website
                                            </h5>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="website" class="form-label">Website</label>
                                                    <div class="input-group social-input">
                                                        <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                                        <input type="url" class="form-control" id="website" name="website" 
                                                               value="<?= htmlspecialchars($profile['website'] ?? '') ?>" 
                                                               placeholder="https://yourwebsite.com">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="linkedin" class="form-label">LinkedIn</label>
                                                    <div class="input-group social-input">
                                                        <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                                        <input type="text" class="form-control" id="linkedin" name="linkedin" 
                                                               value="<?= htmlspecialchars($profile['linkedin'] ?? '') ?>" 
                                                               placeholder="linkedin.com/in/username">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="twitter" class="form-label">Twitter</label>
                                                    <div class="input-group social-input">
                                                        <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                                        <input type="text" class="form-control" id="twitter" name="twitter" 
                                                               value="<?= htmlspecialchars($profile['twitter'] ?? '') ?>" 
                                                               placeholder="@username">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="facebook" class="form-label">Facebook</label>
                                                    <div class="input-group social-input">
                                                        <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                                                        <input type="text" class="form-control" id="facebook" name="facebook" 
                                                               value="<?= htmlspecialchars($profile['facebook'] ?? '') ?>" 
                                                               placeholder="facebook.com/username">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Preferences -->
                                        <div class="form-section">
                                            <h5 class="section-title">
                                                <i class="fas fa-cog"></i>Preferences
                                            </h5>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="timezone" class="form-label">Timezone</label>
                                                    <select class="form-select" id="timezone" name="timezone">
                                                        <option value="Asia/Dhaka" <?= ($profile['timezone'] ?? 'Asia/Dhaka') == 'Asia/Dhaka' ? 'selected' : '' ?>>Asia/Dhaka (GMT+6)</option>
                                                        <option value="Asia/Kolkata" <?= ($profile['timezone'] ?? '') == 'Asia/Kolkata' ? 'selected' : '' ?>>Asia/Kolkata (GMT+5:30)</option>
                                                        <option value="UTC" <?= ($profile['timezone'] ?? '') == 'UTC' ? 'selected' : '' ?>>UTC (GMT+0)</option>
                                                        <option value="America/New_York" <?= ($profile['timezone'] ?? '') == 'America/New_York' ? 'selected' : '' ?>>America/New_York (GMT-5)</option>
                                                        <option value="Europe/London" <?= ($profile['timezone'] ?? '') == 'Europe/London' ? 'selected' : '' ?>>Europe/London (GMT+0)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="language" class="form-label">Language</label>
                                                    <select class="form-select" id="language" name="language">
                                                        <option value="en" <?= ($profile['language'] ?? 'en') == 'en' ? 'selected' : '' ?>>English</option>
                                                        <option value="bn" <?= ($profile['language'] ?? '') == 'bn' ? 'selected' : '' ?>>বাংলা (Bengali)</option>
                                                        <option value="hi" <?= ($profile['language'] ?? '') == 'hi' ? 'selected' : '' ?>>हिन्दी (Hindi)</option>
                                                        <option value="ur" <?= ($profile['language'] ?? '') == 'ur' ? 'selected' : '' ?>>اردو (Urdu)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Notifications & Security -->
                                        <div class="notification-card">
                                            <h5 class="section-title text-white">
                                                <i class="fas fa-shield-alt"></i>Notifications & Security
                                            </h5>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>Email Notifications</strong>
                                                            <small class="d-block">Receive email alerts</small>
                                                        </div>
                                                        <label class="switch">
                                                            <input type="checkbox" name="email_notifications" <?= ($profile['email_notifications'] ?? 1) ? 'checked' : '' ?>>
                                                            <span class="slider"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>SMS Notifications</strong>
                                                            <small class="d-block">Receive SMS alerts</small>
                                                        </div>
                                                        <label class="switch">
                                                            <input type="checkbox" name="sms_notifications" <?= ($profile['sms_notifications'] ?? 0) ? 'checked' : '' ?>>
                                                            <span class="slider"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>Two-Factor Auth</strong>
                                                            <small class="d-block">Enhanced security</small>
                                                        </div>
                                                        <label class="switch">
                                                            <input type="checkbox" name="two_factor_auth" <?= ($profile['two_factor_auth'] ?? 0) ? 'checked' : '' ?>>
                                                            <span class="slider"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Password Change -->
                                        <div class="section-divider">
                                            <h5 class="section-title">
                                                <i class="fas fa-lock"></i>Change Password
                                            </h5>
                                            <p class="text-muted small mb-3">Leave blank to keep current password</p>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="password" class="form-label">New Password</label>
                                                    <input type="password" class="form-control" id="password" name="password" 
                                                           placeholder="Enter new password (min 8 characters)">
                                                    <div class="form-text">Password must be at least 8 characters long</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                                    <input type="password" class="form-control" id="confirm_password" 
                                                           name="confirm_password" placeholder="Confirm new password">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mt-4">
                                            <a href="index.php" class="btn btn-secondary btn-lg">
                                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                            </a>
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-save me-2"></i>Save All Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileImageInput = document.getElementById('profileImageInput');
        const avatarPreview = document.getElementById('avatarPreview');
        const removeImageBtn = document.getElementById('removeImageBtn');
        const removeImageInput = document.getElementById('removeImageInput');
        
        // Store original avatar content
        const originalAvatarContent = avatarPreview.innerHTML;
        
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
                        avatarPreview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="profile-avatar">`;
                        
                        // Reset remove image flag
                        if (removeImageInput) {
                            removeImageInput.value = '0';
                        }
                        
                        // Show/update remove button
                        if (removeImageBtn) {
                            removeImageBtn.style.display = 'inline-block';
                            removeImageBtn.innerHTML = '<i class="fas fa-trash me-2"></i>Remove New Image';
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
                    'Are you sure you want to remove your profile picture?';
                    
                if (confirm(confirmMessage)) {
                    // Clear file input
                    if (profileImageInput) {
                        profileImageInput.value = '';
                    }
                    
                    // Reset to initials
                    const initials = '<?= addslashes($initials) ?>';
                    const avatarColor = '<?= addslashes($avatarColor) ?>';
                    
                    avatarPreview.innerHTML = `<div class="profile-avatar d-flex align-items-center justify-content-center" 
                                                   style="background-color: ${avatarColor}; color: white; font-size: 2.5rem;">
                                                   ${initials}
                                               </div>`;
                    
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
        
        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password && password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                if (password && password.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long!');
                    return false;
                }
            });
        }
        
        // Auto-hide success messages
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
        
        // Social media URL formatting
        const socialInputs = {
            linkedin: document.getElementById('linkedin'),
            twitter: document.getElementById('twitter'),
            facebook: document.getElementById('facebook')
        };
        
        // Format LinkedIn URL
        if (socialInputs.linkedin) {
            socialInputs.linkedin.addEventListener('blur', function() {
                let value = this.value.trim();
                if (value && !value.includes('linkedin.com')) {
                    this.value = 'linkedin.com/in/' + value.replace(/^@/, '');
                }
            });
        }
        
        // Format Twitter handle
        if (socialInputs.twitter) {
            socialInputs.twitter.addEventListener('blur', function() {
                let value = this.value.trim();
                if (value && !value.startsWith('@')) {
                    this.value = '@' + value;
                }
            });
        }
        
        // Format Facebook URL
        if (socialInputs.facebook) {
            socialInputs.facebook.addEventListener('blur', function() {
                let value = this.value.trim();
                if (value && !value.includes('facebook.com')) {
                    this.value = 'facebook.com/' + value.replace(/^@/, '');
                }
            });
        }
    });
    </script>
</body>
</html>

<?php $db->disconnect(); ?>
