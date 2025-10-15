<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

use App\auth\Admin;

// Check admin authentication using the same method as other admin pages
if(!Admin::Check()){
    // Debug information for troubleshooting
    error_log("Settings access denied. Session data: " . print_r($_SESSION, true));
    
    // Check if it's a simple session issue vs authentication issue
    if (!isset($_SESSION['userid'])) {
        header('Location: ../login.php?message=Please login to access admin settings');
    } else {
        header('Location: ../login.php?message=Admin access required');
    }
    exit;
}

try {
    $db = new MysqliDb();
    
    // Create tables if they don't exist (only once)
    $db->rawQuery("CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $db->rawQuery("CREATE TABLE IF NOT EXISTS banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        subtitle VARCHAR(255),
        description TEXT,
        image VARCHAR(255) NOT NULL,
        button_text VARCHAR(100),
        button_link VARCHAR(255),
        page_type ENUM('homepage', 'hot-deals') DEFAULT 'homepage',
        status ENUM('active', 'inactive') DEFAULT 'active',
        sort_order INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Add page_type column to existing banners table if it doesn't exist
    $db->rawQuery("ALTER TABLE banners ADD COLUMN IF NOT EXISTS page_type ENUM('homepage', 'hot-deals') DEFAULT 'homepage' AFTER button_link");
    
} catch (Exception $e) {
    error_log("Database setup error: " . $e->getMessage());
    $db = new MysqliDb(); // Continue with basic connection
}

$page = "Settings";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_general':
            $siteName = $_POST['site_name'] ?? '';
            $siteDescription = $_POST['site_description'] ?? '';
            $contactEmail = $_POST['contact_email'] ?? '';
            $contactPhone = $_POST['contact_phone'] ?? '';
            $address = $_POST['address'] ?? '';
            
            try {
                // Batch update settings for better performance
                $settingsToUpdate = [
                    'site_name' => $siteName,
                    'site_description' => $siteDescription,
                    'contact_email' => $contactEmail,
                    'contact_phone' => $contactPhone,
                    'address' => $address
                ];
                
                foreach ($settingsToUpdate as $key => $value) {
                    $db->rawQuery("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", 
                                  [$key, $value]);
                }
                
                $success = "General settings updated successfully!";
            } catch (Exception $e) {
                $error = "Error updating settings: " . $e->getMessage();
            }
            break;
            
        case 'update_notice':
            $noticeEnabled = isset($_POST['notice_enabled']) ? 1 : 0;
            $noticeText = $_POST['notice_text'] ?? '';
            $noticeType = $_POST['notice_type'] ?? 'info';
            
            try {
                $noticeSettings = [
                    'notice_enabled' => $noticeEnabled,
                    'notice_text' => $noticeText,
                    'notice_type' => $noticeType
                ];
                
                foreach ($noticeSettings as $key => $value) {
                    $db->rawQuery("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", 
                                  [$key, $value]);
                }
                
                $success = "Notice bar settings updated successfully!";
            } catch (Exception $e) {
                $error = "Error updating notice settings: " . $e->getMessage();
            }
            break;
    }
}

// Get current settings with caching
$settings = [];
$cacheFile = __DIR__ . '/../cache/settings.json';
$cacheTime = 300; // 5 minutes cache

// Try to load from cache first
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    $settings = json_decode(file_get_contents($cacheFile), true) ?: [];
}

// If cache is empty or expired, load from database
if (empty($settings)) {
    try {
        $settingsData = $db->get('site_settings');
        foreach ($settingsData as $setting) {
            $settings[$setting['setting_key']] = $setting['setting_value'];
        }
        
        // Cache the settings
        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }
        file_put_contents($cacheFile, json_encode($settings));
        
    } catch (Exception $e) {
        error_log("Error loading settings: " . $e->getMessage());
        // Set default values
        $settings = [
            'site_name' => 'DigiEcho',
            'site_description' => 'Your trusted online marketplace',
            'contact_email' => 'info@digiecho.com',
            'contact_phone' => '+880 1234567890',
            'address' => 'Dhaka, Bangladesh',
            'notice_enabled' => '0',
            'notice_text' => '',
            'notice_type' => 'info'
        ];
    }
}

// Load banners via AJAX
$banners = [];

?>

<?php require __DIR__ . '/components/header.php'; ?>
    </head>
    <body class="sb-nav-fixed">
        <?php require __DIR__ . '/components/navbar.php'; ?>
        <div id="layoutSidenav">
            <?php require __DIR__ . '/components/sidebar.php'; ?>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                            <h1 class="h2"><i class="fas fa-cog me-2"></i>Settings</h1>
                            <div class="btn-toolbar mb-2 mb-md-0">
                              
                            </div>
                        </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Settings Tabs -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                <i class="fas fa-info-circle me-2"></i>General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="homepage-banners-tab" data-bs-toggle="tab" data-bs-target="#homepage-banners" type="button" role="tab">
                                <i class="fas fa-home me-2"></i>Homepage Banners
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="hotdeals-banners-tab" data-bs-toggle="tab" data-bs-target="#hotdeals-banners" type="button" role="tab">
                                <i class="fas fa-fire me-2"></i>Hot Deals Banners
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notice-tab" data-bs-toggle="tab" data-bs-target="#notice" type="button" role="tab">
                                <i class="fas fa-bullhorn me-2"></i>Notice Bar
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab">
                                <i class="fas fa-palette me-2"></i>Appearance
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-0">
                    <div class="tab-content" id="settingsTabContent">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active p-4" id="general" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="mb-4"><i class="fas fa-info-circle me-2 text-primary"></i>General Settings</h5>
                                </div>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_general">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Site Name</label>
                                            <input type="text" class="form-control" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? 'DigiEcho') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Contact Email</label>
                                            <input type="email" class="form-control" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Contact Phone</label>
                                            <input type="text" class="form-control" name="contact_phone" value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($settings['address'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Site Description</label>
                                    <textarea class="form-control" name="site_description" rows="3"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save General Settings
                                </button>
                            </form>
                        </div>

                        <!-- Homepage Banner Management -->
                <div class="tab-pane fade p-4" id="homepage-banners" role="tabpanel">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h5 class="mb-0"><i class="fas fa-home me-2 text-primary"></i>Homepage Banner Management</h5>
                                    <small class="text-muted">Manage banners displayed on the homepage slider</small>
                                </div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBannerModal" onclick="setBannerPageType('homepage')">
                                    <i class="fas fa-plus me-2"></i>Add Homepage Banner
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <div id="homepageBannersLoading" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading homepage banners...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading homepage banners...</p>
                        </div>
                        <table class="table table-striped" id="homepageBannersTableContainer" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Preview</th>
                                    <th>Title</th>
                                    <th>Subtitle</th>
                                    <th>Status</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="homepageBannersTable">
                                <!-- Homepage banners will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Hot Deals Banner Management -->
                <div class="tab-pane fade p-4" id="hotdeals-banners" role="tabpanel">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h5 class="mb-0"><i class="fas fa-fire me-2 text-danger"></i>Hot Deals Banner Management</h5>
                                    <small class="text-muted">Manage banners displayed on the hot deals page</small>
                                </div>
                                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addBannerModal" onclick="setBannerPageType('hot-deals')">
                                    <i class="fas fa-plus me-2"></i>Add Hot Deals Banner
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <div id="hotdealsBannersLoading" class="text-center py-4">
                            <div class="spinner-border text-danger" role="status">
                                <span class="visually-hidden">Loading hot deals banners...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading hot deals banners...</p>
                        </div>
                        <table class="table table-striped" id="hotdealsBannersTableContainer" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Preview</th>
                                    <th>Title</th>
                                    <th>Subtitle</th>
                                    <th>Status</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="hotdealsBannersTable">
                                <!-- Hot deals banners will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                        <!-- Notice Bar -->
                        <div class="tab-pane fade p-4" id="notice" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="mb-4"><i class="fas fa-bullhorn me-2 text-primary"></i>Notice Bar Settings</h5>
                                </div>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_notice">
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="notice_enabled" id="noticeEnabled" <?= ($settings['notice_enabled'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="noticeEnabled">
                                            Enable Notice Bar
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Notice Text</label>
                                    <textarea class="form-control" name="notice_text" rows="2" placeholder="Enter your notice message..."><?= htmlspecialchars($settings['notice_text'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Notice Type</label>
                                    <select class="form-select" name="notice_type">
                                        <option value="info" <?= ($settings['notice_type'] ?? 'info') === 'info' ? 'selected' : '' ?>>Info (Blue)</option>
                                        <option value="success" <?= ($settings['notice_type'] ?? 'info') === 'success' ? 'selected' : '' ?>>Success (Green)</option>
                                        <option value="warning" <?= ($settings['notice_type'] ?? 'info') === 'warning' ? 'selected' : '' ?>>Warning (Yellow)</option>
                                        <option value="danger" <?= ($settings['notice_type'] ?? 'info') === 'danger' ? 'selected' : '' ?>>Important (Red)</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Notice Settings
                                </button>
                            </form>
                        </div>

                        <!-- Appearance -->
                        <div class="tab-pane fade p-4" id="appearance" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="mb-4"><i class="fas fa-palette me-2 text-primary"></i>Appearance Settings</h5>
                                </div>
                            </div>
                            <!-- Logo Upload -->
                            <div class="mb-4">
                                <h6>Site Logo</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="current-logo mb-3">
                                            <img src="../<?= htmlspecialchars($settings['logo'] ?? 'assets/images/logo.png') ?>" 
                                                 alt="Current Logo" 
                                                 class="img-thumbnail" 
                                                 style="max-height: 100px;">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="upload_logo">
                                            <div class="mb-3">
                                                <label class="form-label">Upload New Logo</label>
                                                <input type="file" class="form-control" name="logo" accept="image/*" required>
                                                <div class="form-text">Recommended size: 200x60px. Supported formats: JPG, PNG, GIF</div>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload me-2"></i>Update Logo
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Professional Theme Settings -->
                            <div class="appearance-section mb-5">
                                <div class="section-header d-flex align-items-center mb-4">
                                    <div class="section-icon me-3">
                                        <i class="fas fa-palette fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-1">Theme & Display Settings</h4>
                                        <p class="text-muted mb-0">Customize the visual appearance and theme behavior</p>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <!-- Theme Mode Selection -->
                                    <div class="col-lg-8">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-header bg-gradient-primary text-white">
                                                <h6 class="mb-0"><i class="fas fa-moon me-2"></i>Theme Mode Selection</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="theme-options">
                                                    <!-- Auto Theme -->
                                                    <div class="theme-option mb-3">
                                                        <div class="form-check theme-check">
                                                            <input class="form-check-input" type="radio" name="theme_mode" id="themeAuto" value="auto" <?= ($settings['theme_mode'] ?? 'auto') === 'auto' ? 'checked' : '' ?>>
                                                            <label class="form-check-label theme-label" for="themeAuto">
                                                                <div class="theme-card auto-theme">
                                                                    <div class="theme-icon">
                                                                        <i class="fas fa-magic fa-2x text-primary"></i>
                                                                    </div>
                                                                    <div class="theme-info">
                                                                        <h6 class="theme-title">Smart Auto Mode</h6>
                                                                        <p class="theme-desc">Automatically switches between light and dark themes based on time of day</p>
                                                                        <div class="theme-features">
                                                                            <span class="badge bg-primary">Intelligent</span>
                                                                            <span class="badge bg-info">Time-based</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                        
                                                        <!-- Auto Schedule Settings -->
                                                        <div class="auto-schedule-settings mt-3" id="autoSchedule" style="display: <?= ($settings['theme_mode'] ?? 'auto') === 'auto' ? 'block' : 'none' ?>;">
                                                            <div class="schedule-card">
                                                                <h6 class="text-primary mb-3"><i class="fas fa-clock me-2"></i>Auto-Switch Schedule</h6>
                                                                <div class="row g-3">
                                                                    <div class="col-md-6">
                                                                        <div class="time-input-group">
                                                                            <label class="form-label"><i class="fas fa-sun me-2 text-warning"></i>Day Mode Starts</label>
                                                                            <input type="time" class="form-control time-input" name="day_start_time" value="<?= $settings['day_start_time'] ?? '06:00' ?>" onchange="updateSchedulePreview()">
                                                                            <small class="form-text text-muted">When to switch to light theme</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="time-input-group">
                                                                            <label class="form-label"><i class="fas fa-moon me-2 text-info"></i>Night Mode Starts</label>
                                                                            <input type="time" class="form-control time-input" name="night_start_time" value="<?= $settings['night_start_time'] ?? '18:00' ?>" onchange="updateSchedulePreview()">
                                                                            <small class="form-text text-muted">When to switch to dark theme</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="schedule-preview mt-3" id="schedulePreview">
                                                                    <!-- Schedule preview will be populated by JavaScript -->
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Light Theme -->
                                                    <div class="theme-option mb-3">
                                                        <div class="form-check theme-check">
                                                            <input class="form-check-input" type="radio" name="theme_mode" id="themeLight" value="light" <?= ($settings['theme_mode'] ?? 'auto') === 'light' ? 'checked' : '' ?>>
                                                            <label class="form-check-label theme-label" for="themeLight">
                                                                <div class="theme-card light-theme">
                                                                    <div class="theme-icon">
                                                                        <i class="fas fa-sun fa-2x text-warning"></i>
                                                                    </div>
                                                                    <div class="theme-info">
                                                                        <h6 class="theme-title">Light Mode</h6>
                                                                        <p class="theme-desc">Clean, bright interface perfect for daytime use</p>
                                                                        <div class="theme-features">
                                                                            <span class="badge bg-warning">Bright</span>
                                                                            <span class="badge bg-success">Professional</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <!-- Dark Theme -->
                                                    <div class="theme-option mb-3">
                                                        <div class="form-check theme-check">
                                                            <input class="form-check-input" type="radio" name="theme_mode" id="themeDark" value="dark" <?= ($settings['theme_mode'] ?? 'auto') === 'dark' ? 'checked' : '' ?>>
                                                            <label class="form-check-label theme-label" for="themeDark">
                                                                <div class="theme-card dark-theme">
                                                                    <div class="theme-icon">
                                                                        <i class="fas fa-moon fa-2x text-info"></i>
                                                                    </div>
                                                                    <div class="theme-info">
                                                                        <h6 class="theme-title">Dark Mode</h6>
                                                                        <p class="theme-desc">Easy on the eyes, perfect for low-light environments</p>
                                                                        <div class="theme-features">
                                                                            <span class="badge bg-dark">Modern</span>
                                                                            <span class="badge bg-info">Eye-friendly</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Live Theme Preview -->
                                    <div class="col-lg-4">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-header bg-gradient-success text-white">
                                                <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h6>
                                            </div>
                                            <div class="card-body">
                                                <div id="themePreview" class="theme-preview-container">
                                                    <div class="preview-window">
                                                        <div class="preview-header">
                                                            <div class="preview-logo">
                                                                <i class="fas fa-store me-2"></i>
                                                                <span>Family Haat Bazar</span>
                                                            </div>
                                                            <div class="preview-status">
                                                                <span class="status-indicator"></span>
                                                                <span id="currentThemeText">Auto Mode</span>
                                                            </div>
                                                        </div>
                                                        <div class="preview-content">
                                                            <div class="preview-nav">
                                                                <div class="nav-item active">Dashboard</div>
                                                                <div class="nav-item">Products</div>
                                                                <div class="nav-item">Orders</div>
                                                            </div>
                                                            <div class="preview-main">
                                                                <h6>Dashboard Overview</h6>
                                                                <p>This preview shows how your admin interface will appear.</p>
                                                                <div class="preview-buttons">
                                                                    <button class="btn-preview btn-primary">Primary Action</button>
                                                                    <button class="btn-preview btn-secondary">Secondary</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="theme-status mt-3">
                                                    <div class="status-info">
                                                        <small class="text-muted">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Current Time: <span id="currentTime"><?= date('H:i') ?></span>
                                                        </small>
                                                    </div>
                                                    <div class="theme-actions mt-2">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="previewTheme('light')">
                                                            <i class="fas fa-sun me-1"></i>Preview Light
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="previewTheme('dark')">
                                                            <i class="fas fa-moon me-1"></i>Preview Dark
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Professional Language & Localization Settings -->
                            <div class="appearance-section mb-5">
                                <div class="section-header d-flex align-items-center mb-4">
                                    <div class="section-icon me-3">
                                        <i class="fas fa-globe fa-2x text-success"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-1">Language & Localization</h4>
                                        <p class="text-muted mb-0">Configure language, currency, date formats, and regional settings</p>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <!-- Language & Currency Settings -->
                                    <div class="col-lg-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-header bg-gradient-success text-white">
                                                <h6 class="mb-0"><i class="fas fa-language me-2"></i>Language & Currency</h6>
                                            </div>
                                            <div class="card-body">
                                                <!-- Language Selection -->
                                                <div class="form-group mb-4">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-flag me-2 text-primary"></i>Default Language
                                                    </label>
                                                    <select class="form-select form-select-lg" name="default_language" id="defaultLanguage" onchange="updateLanguagePreview()">
                                                        <option value="en" <?= ($settings['default_language'] ?? 'en') === 'en' ? 'selected' : '' ?>>
                                                            🇺🇸 English (United States)
                                                        </option>
                                                        <option value="bn" <?= ($settings['default_language'] ?? 'en') === 'bn' ? 'selected' : '' ?>>
                                                            🇧🇩 বাংলা (Bengali - Bangladesh)
                                                        </option>
                                                        <option value="hi" <?= ($settings['default_language'] ?? 'en') === 'hi' ? 'selected' : '' ?>>
                                                            🇮🇳 हिन्दी (Hindi - India)
                                                        </option>
                                                        <option value="ur" <?= ($settings['default_language'] ?? 'en') === 'ur' ? 'selected' : '' ?>>
                                                            🇵🇰 اردو (Urdu - Pakistan)
                                                        </option>
                                                        <option value="ar" <?= ($settings['default_language'] ?? 'en') === 'ar' ? 'selected' : '' ?>>
                                                            🇸🇦 العربية (Arabic - Saudi Arabia)
                                                        </option>
                                                    </select>
                                                    <div class="form-text">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        This will be the default language for new visitors and system messages
                                                    </div>
                                                </div>

                                                <!-- Currency Selection -->
                                                <div class="form-group mb-4">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-coins me-2 text-warning"></i>Currency Format
                                                    </label>
                                                    <select class="form-select form-select-lg" name="currency_format" id="currencyFormat" onchange="updateCurrencyPreview()">
                                                        <option value="BDT" <?= ($settings['currency_format'] ?? 'BDT') === 'BDT' ? 'selected' : '' ?>>
                                                            ৳ Bangladeshi Taka (BDT)
                                                        </option>
                                                        <option value="USD" <?= ($settings['currency_format'] ?? 'BDT') === 'USD' ? 'selected' : '' ?>>
                                                            $ US Dollar (USD)
                                                        </option>
                                                        <option value="EUR" <?= ($settings['currency_format'] ?? 'BDT') === 'EUR' ? 'selected' : '' ?>>
                                                            € Euro (EUR)
                                                        </option>
                                                        <option value="INR" <?= ($settings['currency_format'] ?? 'BDT') === 'INR' ? 'selected' : '' ?>>
                                                            ₹ Indian Rupee (INR)
                                                        </option>
                                                    </select>
                                                    <div class="currency-preview mt-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-eye me-1"></i>
                                                            Preview: <span id="currencyPreview" class="fw-bold">৳ 1,250.00</span>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Date & Time Settings -->
                                    <div class="col-lg-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-header bg-gradient-info text-white">
                                                <h6 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Date & Time Formats</h6>
                                            </div>
                                            <div class="card-body">
                                                <!-- Date Format -->
                                                <div class="form-group mb-4">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-calendar me-2 text-info"></i>Date Format
                                                    </label>
                                                    <select class="form-select form-select-lg" name="date_format" id="dateFormat" onchange="updateDatePreview()">
                                                        <option value="Y-m-d" <?= ($settings['date_format'] ?? 'Y-m-d') === 'Y-m-d' ? 'selected' : '' ?>>
                                                            YYYY-MM-DD (International)
                                                        </option>
                                                        <option value="d/m/Y" <?= ($settings['date_format'] ?? 'Y-m-d') === 'd/m/Y' ? 'selected' : '' ?>>
                                                            DD/MM/YYYY (European)
                                                        </option>
                                                        <option value="m/d/Y" <?= ($settings['date_format'] ?? 'Y-m-d') === 'm/d/Y' ? 'selected' : '' ?>>
                                                            MM/DD/YYYY (American)
                                                        </option>
                                                        <option value="d-M-Y" <?= ($settings['date_format'] ?? 'Y-m-d') === 'd-M-Y' ? 'selected' : '' ?>>
                                                            DD-MMM-YYYY (Readable)
                                                        </option>
                                                    </select>
                                                    <div class="date-preview mt-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-eye me-1"></i>
                                                            Today: <span id="datePreview" class="fw-bold"><?= date('Y-m-d') ?></span>
                                                        </small>
                                                    </div>
                                                </div>

                                                <!-- Timezone Selection -->
                                                <div class="form-group mb-4">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-clock me-2 text-primary"></i>Time Zone
                                                    </label>
                                                    <select class="form-select form-select-lg" name="timezone" id="timezone" onchange="updateTimezonePreview()">
                                                        <option value="Asia/Dhaka" <?= ($settings['timezone'] ?? 'Asia/Dhaka') === 'Asia/Dhaka' ? 'selected' : '' ?>>
                                                            🇧🇩 Asia/Dhaka (GMT+6:00)
                                                        </option>
                                                        <option value="Asia/Kolkata" <?= ($settings['timezone'] ?? 'Asia/Dhaka') === 'Asia/Kolkata' ? 'selected' : '' ?>>
                                                            🇮🇳 Asia/Kolkata (GMT+5:30)
                                                        </option>
                                                        <option value="Asia/Karachi" <?= ($settings['timezone'] ?? 'Asia/Dhaka') === 'Asia/Karachi' ? 'selected' : '' ?>>
                                                            🇵🇰 Asia/Karachi (GMT+5:00)
                                                        </option>
                                                        <option value="UTC" <?= ($settings['timezone'] ?? 'Asia/Dhaka') === 'UTC' ? 'selected' : '' ?>>
                                                            🌍 UTC (GMT+0:00)
                                                        </option>
                                                        <option value="America/New_York" <?= ($settings['timezone'] ?? 'Asia/Dhaka') === 'America/New_York' ? 'selected' : '' ?>>
                                                            🇺🇸 America/New_York (GMT-5:00)
                                                        </option>
                                                    </select>
                                                    <div class="timezone-preview mt-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-eye me-1"></i>
                                                            Current Time: <span id="timezonePreview" class="fw-bold"><?= date('H:i:s') ?></span>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Localization Preview -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-gradient-warning text-dark">
                                                <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Localization Preview</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="localization-preview">
                                                    <div class="row g-3">
                                                        <div class="col-md-3">
                                                            <div class="preview-item">
                                                                <label class="form-label text-muted">Language</label>
                                                                <div class="preview-value" id="languagePreview">🇺🇸 English</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="preview-item">
                                                                <label class="form-label text-muted">Currency</label>
                                                                <div class="preview-value" id="currencyDisplayPreview">৳ 1,250.00</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="preview-item">
                                                                <label class="form-label text-muted">Date Format</label>
                                                                <div class="preview-value" id="dateDisplayPreview"><?= date('Y-m-d') ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="preview-item">
                                                                <label class="form-label text-muted">Time Zone</label>
                                                                <div class="preview-value" id="timezoneDisplayPreview">GMT+6:00</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Professional Action Buttons -->
                            <div class="appearance-actions">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h6 class="mb-1"><i class="fas fa-cog me-2 text-primary"></i>Apply Changes</h6>
                                                <p class="text-muted mb-0">Save your appearance settings and apply them to the entire system</p>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <form method="POST" id="appearanceForm" class="d-inline">
                                                    <input type="hidden" name="action" value="update_appearance">
                                                    <button type="submit" class="btn btn-primary btn-lg px-4 me-2">
                                                        <i class="fas fa-save me-2"></i>Save Settings
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary btn-lg px-4" onclick="resetAppearanceSettings()">
                                                        <i class="fas fa-undo me-2"></i>Reset
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Settings Status -->
                                        <div class="settings-status mt-3 pt-3 border-top">
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <div class="status-item">
                                                        <i class="fas fa-palette text-primary"></i>
                                                        <span class="status-label">Theme</span>
                                                        <span class="status-value" id="statusTheme">Auto Mode</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="status-item">
                                                        <i class="fas fa-globe text-success"></i>
                                                        <span class="status-label">Language</span>
                                                        <span class="status-value" id="statusLanguage">English</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="status-item">
                                                        <i class="fas fa-coins text-warning"></i>
                                                        <span class="status-label">Currency</span>
                                                        <span class="status-value" id="statusCurrency">BDT</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="status-item">
                                                        <i class="fas fa-clock text-info"></i>
                                                        <span class="status-label">Timezone</span>
                                                        <span class="status-value" id="statusTimezone">GMT+6</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> <!-- End tab-content -->
                </div> <!-- End card-body -->
            </div> <!-- End card -->
        </div> <!-- End container -->

<!-- Add Banner Modal -->
<div class="modal fade" id="addBannerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Banner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bannerForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Banner Title</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Subtitle</label>
                                <input type="text" class="form-control" name="subtitle">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Button Text</label>
                                <input type="text" class="form-control" name="button_text" placeholder="e.g., Shop Now">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Button Link</label>
                                <input type="url" class="form-control" name="button_link" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Banner Image</label>
                        <input type="file" class="form-control" name="image" accept="image/*" required>
                        <div class="form-text">Recommended size: 1920x600px for best results</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Page Type</label>
                                <select class="form-select" name="page_type" id="bannerPageType">
                                    <option value="homepage">Homepage</option>
                                    <option value="hot-deals">Hot Deals</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" class="form-control" name="sort_order" value="1" min="1">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Banner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Banner Modal -->
<div class="modal fade" id="editBannerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Banner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editBannerForm" enctype="multipart/form-data">
                <input type="hidden" name="banner_id" id="editBannerId">
                <div class="modal-body">
                    <!-- Same form fields as add banner -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Banner Title</label>
                                <input type="text" class="form-control" name="title" id="editTitle" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Subtitle</label>
                                <input type="text" class="form-control" name="subtitle" id="editSubtitle">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" id="editDescription"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Button Text</label>
                                <input type="text" class="form-control" name="button_text" id="editButtonText">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Button Link</label>
                                <input type="url" class="form-control" name="button_link" id="editButtonLink">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Banner Image</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                        <div class="form-text">Leave empty to keep current image</div>
                        <div class="mt-2" id="currentImagePreview"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="editStatus">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" class="form-control" name="sort_order" id="editSortOrder" min="1">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Banner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Professional Settings Page Styling */
.settings-container {
    background-color: #f8f9fa;
    min-height: 100vh;
}

.nav-tabs .nav-link {
    border: none;
    border-radius: 0;
    color: #6c757d;
    font-weight: 500;
    padding: 1rem 1.5rem;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
    border-color: transparent;
    color: #007bff;
    background-color: #f8f9fa;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
    border-bottom: 2px solid #007bff;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 0.5rem;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn-primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border: none;
    border-radius: 0.375rem;
    font-weight: 500;
    padding: 0.5rem 1.5rem;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
}

.alert {
    border: none;
    border-radius: 0.5rem;
    font-weight: 500;
}

.modal-content {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.modal-header {
    border-bottom: 1px solid #e9ecef;
    background-color: #f8f9fa;
    border-radius: 0.75rem 0.75rem 0 0;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.img-thumbnail {
    border: 2px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 0.25rem;
}

/* Animation for page load */
.tab-pane {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive improvements */
@media (max-width: 768px) {
    .nav-tabs .nav-link {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
    
    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Load banners only when the banners tab is clicked
let currentBannerPageType = 'homepage';

document.addEventListener('DOMContentLoaded', function() {
    // Load banners only when banner tabs are activated
    const homepageBannersTab = document.getElementById('homepage-banners-tab');
    const hotdealsBannersTab = document.getElementById('hotdeals-banners-tab');
    let homepageBannersLoaded = false;
    let hotdealsBannersLoaded = false;
    
    homepageBannersTab.addEventListener('click', function() {
        if (!homepageBannersLoaded) {
            loadBanners('homepage');
            homepageBannersLoaded = true;
        }
    });
    
    hotdealsBannersTab.addEventListener('click', function() {
        if (!hotdealsBannersLoaded) {
            loadBanners('hot-deals');
            hotdealsBannersLoaded = true;
        }
    });
    
    // If homepage banners tab is active on page load, load banners
    if (homepageBannersTab.classList.contains('active')) {
        loadBanners('homepage');
        homepageBannersLoaded = true;
    }
});

// Set banner page type when modal is opened
function setBannerPageType(pageType) {
    currentBannerPageType = pageType;
    document.getElementById('bannerPageType').value = pageType;
}

// Load banners function with page type support
function loadBanners(pageType) {
    const loadingDiv = document.getElementById(pageType === 'homepage' ? 'homepageBannersLoading' : 'hotdealsBannersLoading');
    const tableContainer = document.getElementById(pageType === 'homepage' ? 'homepageBannersTableContainer' : 'hotdealsBannersTableContainer');
    
    fetch(`settings-ajax.php?action=get_banners&page_type=${pageType}`)
        .then(response => response.json())
        .then(data => {
            // Hide loading, show table
            loadingDiv.style.display = 'none';
            tableContainer.style.display = 'table';
            
            const tbody = document.getElementById(pageType === 'homepage' ? 'homepageBannersTable' : 'hotdealsBannersTable');
            tbody.innerHTML = '';
            
            if (data.success && data.banners.length > 0) {
                data.banners.forEach(banner => {
                    const row = `
                        <tr>
                            <td>
                                <img src="../${banner.image}" alt="${banner.title}" style="width: 80px; height: 40px; object-fit: cover;" class="rounded">
                            </td>
                            <td>${banner.title}</td>
                            <td>${banner.subtitle || '-'}</td>
                            <td>
                                <span class="badge bg-${banner.status === 'active' ? 'success' : 'secondary'}">
                                    ${banner.status.charAt(0).toUpperCase() + banner.status.slice(1)}
                                </span>
                            </td>
                            <td>${banner.sort_order}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="editBanner(${banner.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteBanner(${banner.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center">No ${pageType === 'homepage' ? 'homepage' : 'hot deals'} banners found</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading banners:', error);
            loadingDiv.style.display = 'none';
            tableContainer.style.display = 'table';
            const tbody = document.getElementById(pageType === 'homepage' ? 'homepageBannersTable' : 'hotdealsBannersTable');
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error loading banners. Please try again.</td></tr>`;
        });
}

// Edit banner function
function editBanner(id) {
    fetch(`settings-ajax.php?action=get_banner&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.banner) {
                const banner = data.banner;
                
                // Populate the form with banner data
                document.querySelector('input[name="title"]').value = banner.title || '';
                document.querySelector('input[name="subtitle"]').value = banner.subtitle || '';
                document.querySelector('textarea[name="description"]').value = banner.description || '';
                document.querySelector('input[name="button_text"]').value = banner.button_text || '';
                document.querySelector('input[name="button_link"]').value = banner.button_link || '';
                document.querySelector('select[name="page_type"]').value = banner.page_type || 'homepage';
                document.querySelector('select[name="status"]').value = banner.status || 'active';
                document.querySelector('input[name="sort_order"]').value = banner.sort_order || 1;
                
                // Update modal title and form action
                document.querySelector('#addBannerModal .modal-title').textContent = 'Edit Banner';
                document.getElementById('bannerForm').setAttribute('data-banner-id', id);
                document.getElementById('bannerForm').setAttribute('data-action', 'edit');
                
                // Make image field optional for editing
                document.querySelector('input[name="image"]').removeAttribute('required');
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('addBannerModal'));
                modal.show();
            } else {
                showAlert('Error loading banner data', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading banner data', 'danger');
        });
}

// Delete banner function
function deleteBanner(id) {
    if (confirm('Are you sure you want to delete this banner? This action cannot be undone.')) {
        fetch('settings-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_banner&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Banner deleted successfully!', 'success');
                // Reload the appropriate banner list
                const currentTab = document.querySelector('.nav-link.active').getAttribute('data-bs-target');
                if (currentTab === '#homepage-banners') {
                    loadBanners('homepage');
                } else if (currentTab === '#hotdeals-banners') {
                    loadBanners('hot-deals');
                }
            } else {
                showAlert(data.message || 'Error deleting banner', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error deleting banner', 'danger');
        });
    }
}

// Reset banner form when modal is closed
document.getElementById('addBannerModal').addEventListener('hidden.bs.modal', function () {
    const form = document.getElementById('bannerForm');
    form.reset();
    form.removeAttribute('data-banner-id');
    form.removeAttribute('data-action');
    document.querySelector('#addBannerModal .modal-title').textContent = 'Add New Banner';
    document.querySelector('input[name="image"]').setAttribute('required', 'required');
});

// Add/Edit banner form submission
document.getElementById('bannerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const isEdit = this.getAttribute('data-action') === 'edit';
    const bannerId = this.getAttribute('data-banner-id');
    
    if (isEdit) {
        formData.append('action', 'edit_banner');
        formData.append('id', bannerId);
    } else {
        formData.append('action', 'add_banner');
    }
    
    fetch('settings-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addBannerModal')).hide();
            this.reset();
            loadBanners(currentBannerPageType);
            showAlert('Banner added successfully!', 'success');
        } else {
            showAlert(data.message || 'Error adding banner', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error adding banner', 'danger');
    });
});

// Edit banner function
function editBanner(id) {
    fetch(`settings-ajax.php?action=get_banner&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const banner = data.banner;
                document.getElementById('editBannerId').value = banner.id;
                document.getElementById('editTitle').value = banner.title;
                document.getElementById('editSubtitle').value = banner.subtitle || '';
                document.getElementById('editDescription').value = banner.description || '';
                document.getElementById('editButtonText').value = banner.button_text || '';
                document.getElementById('editButtonLink').value = banner.button_link || '';
                document.getElementById('editStatus').value = banner.status;
                document.getElementById('editSortOrder').value = banner.sort_order;
                
                // Show current image
                const preview = document.getElementById('currentImagePreview');
                preview.innerHTML = `<img src="../${banner.image}" alt="Current Image" style="width: 200px; height: 100px; object-fit: cover;" class="rounded">`;
                
                new bootstrap.Modal(document.getElementById('editBannerModal')).show();
            }
        });
}

// Edit banner form submission
document.getElementById('editBannerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'update_banner');
    
    fetch('settings-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editBannerModal')).hide();
            loadBanners();
            showAlert('Banner updated successfully!', 'success');
        } else {
            showAlert(data.message || 'Error updating banner', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating banner', 'danger');
    });
});


// Show alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('main');
    container.insertBefore(alertDiv, container.firstChild.nextSibling);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Enhanced Theme and Localization functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all previews
    initializeAppearanceSettings();
    
    // Theme mode change handlers
    document.querySelectorAll('input[name="theme_mode"]').forEach(radio => {
        radio.addEventListener('change', function() {
            handleThemeChange(this.value);
        });
    });
    
    // Update current time every second
    setInterval(updateCurrentTime, 1000);
    
    // Initialize all preview updates
    updateAllPreviews();
});

function initializeAppearanceSettings() {
    // Initialize theme preview
    updateThemePreview();
    
    // Initialize schedule preview
    updateSchedulePreview();
    
    // Initialize localization previews
    updateLanguagePreview();
    updateCurrencyPreview();
    updateDatePreview();
    updateTimezonePreview();
    
    // Set initial theme based on current selection
    const selectedTheme = document.querySelector('input[name="theme_mode"]:checked').value;
    handleThemeChange(selectedTheme);
}

function handleThemeChange(themeValue) {
    const autoSchedule = document.getElementById('autoSchedule');
    const currentThemeText = document.getElementById('currentThemeText');
    const statusIndicator = document.querySelector('.status-indicator');
    
    // Update visibility of auto schedule
    if (themeValue === 'auto') {
        autoSchedule.style.display = 'block';
        currentThemeText.textContent = 'Auto Mode';
        statusIndicator.className = 'status-indicator status-auto';
    } else {
        autoSchedule.style.display = 'none';
        currentThemeText.textContent = themeValue === 'light' ? 'Light Mode' : 'Dark Mode';
        statusIndicator.className = `status-indicator status-${themeValue}`;
    }
    
    // Update theme preview
    updateThemePreview();
    
    // Apply theme to current page immediately
    applyThemeToPage();
}

function updateSchedulePreview() {
    const dayStart = document.querySelector('input[name="day_start_time"]').value;
    const nightStart = document.querySelector('input[name="night_start_time"]').value;
    const schedulePreview = document.getElementById('schedulePreview');
    
    const currentHour = new Date().getHours();
    const dayStartHour = parseInt(dayStart.split(':')[0]);
    const nightStartHour = parseInt(nightStart.split(':')[0]);
    
    let currentMode = 'light';
    if (currentHour >= dayStartHour && currentHour < nightStartHour) {
        currentMode = 'light';
    } else {
        currentMode = 'dark';
    }
    
    schedulePreview.innerHTML = `
        <div class="schedule-timeline">
            <div class="timeline-item ${currentMode === 'light' ? 'active' : ''}">
                <i class="fas fa-sun text-warning"></i>
                <span>Light Mode: ${dayStart} - ${nightStart}</span>
            </div>
            <div class="timeline-item ${currentMode === 'dark' ? 'active' : ''}">
                <i class="fas fa-moon text-info"></i>
                <span>Dark Mode: ${nightStart} - ${dayStart}</span>
            </div>
            <div class="current-status">
                <small class="text-muted">
                    <i class="fas fa-clock me-1"></i>
                    Currently: <span class="fw-bold text-${currentMode === 'light' ? 'warning' : 'info'}">${currentMode === 'light' ? 'Light' : 'Dark'} Mode</span>
                </small>
            </div>
        </div>
    `;
}

function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { hour12: false });
    const currentTimeElement = document.getElementById('currentTime');
    if (currentTimeElement) {
        currentTimeElement.textContent = timeString;
    }
}

function updateLanguagePreview() {
    const languageSelect = document.getElementById('defaultLanguage');
    const selectedOption = languageSelect.options[languageSelect.selectedIndex];
    const languagePreview = document.getElementById('languagePreview');
    
    if (languagePreview) {
        languagePreview.textContent = selectedOption.textContent;
    }
}

function updateCurrencyPreview() {
    const currencySelect = document.getElementById('currencyFormat');
    const selectedCurrency = currencySelect.value;
    const currencyPreview = document.getElementById('currencyPreview');
    const currencyDisplayPreview = document.getElementById('currencyDisplayPreview');
    
    const currencySymbols = {
        'BDT': '৳ 1,250.00',
        'USD': '$ 1,250.00',
        'EUR': '€ 1,250.00',
        'INR': '₹ 1,250.00'
    };
    
    const previewValue = currencySymbols[selectedCurrency] || '৳ 1,250.00';
    
    if (currencyPreview) currencyPreview.textContent = previewValue;
    if (currencyDisplayPreview) currencyDisplayPreview.textContent = previewValue;
}

function updateDatePreview() {
    const dateFormatSelect = document.getElementById('dateFormat');
    const selectedFormat = dateFormatSelect.value;
    const datePreview = document.getElementById('datePreview');
    const dateDisplayPreview = document.getElementById('dateDisplayPreview');
    
    const now = new Date();
    let formattedDate = '';
    
    switch (selectedFormat) {
        case 'Y-m-d':
            formattedDate = now.toISOString().split('T')[0];
            break;
        case 'd/m/Y':
            formattedDate = `${now.getDate().toString().padStart(2, '0')}/${(now.getMonth() + 1).toString().padStart(2, '0')}/${now.getFullYear()}`;
            break;
        case 'm/d/Y':
            formattedDate = `${(now.getMonth() + 1).toString().padStart(2, '0')}/${now.getDate().toString().padStart(2, '0')}/${now.getFullYear()}`;
            break;
        case 'd-M-Y':
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            formattedDate = `${now.getDate().toString().padStart(2, '0')}-${months[now.getMonth()]}-${now.getFullYear()}`;
            break;
        default:
            formattedDate = now.toISOString().split('T')[0];
    }
    
    if (datePreview) datePreview.textContent = formattedDate;
    if (dateDisplayPreview) dateDisplayPreview.textContent = formattedDate;
}

function updateTimezonePreview() {
    const timezoneSelect = document.getElementById('timezone');
    const selectedTimezone = timezoneSelect.value;
    const timezonePreview = document.getElementById('timezonePreview');
    const timezoneDisplayPreview = document.getElementById('timezoneDisplayPreview');
    
    const timezoneOffsets = {
        'Asia/Dhaka': 'GMT+6:00',
        'Asia/Kolkata': 'GMT+5:30',
        'Asia/Karachi': 'GMT+5:00',
        'UTC': 'GMT+0:00',
        'America/New_York': 'GMT-5:00'
    };
    
    const offsetValue = timezoneOffsets[selectedTimezone] || 'GMT+6:00';
    
    if (timezonePreview) timezonePreview.textContent = new Date().toLocaleTimeString();
    if (timezoneDisplayPreview) timezoneDisplayPreview.textContent = offsetValue;
}

function updateAllPreviews() {
    updateLanguagePreview();
    updateCurrencyPreview();
    updateDatePreview();
    updateTimezonePreview();
    updateSchedulePreview();
    updateStatusIndicators();
}

function updateStatusIndicators() {
    // Update theme status
    const selectedTheme = document.querySelector('input[name="theme_mode"]:checked').value;
    const statusTheme = document.getElementById('statusTheme');
    if (statusTheme) {
        statusTheme.textContent = selectedTheme === 'auto' ? 'Auto Mode' : 
                                 selectedTheme === 'light' ? 'Light Mode' : 'Dark Mode';
    }
    
    // Update language status
    const languageSelect = document.getElementById('defaultLanguage');
    const statusLanguage = document.getElementById('statusLanguage');
    if (statusLanguage && languageSelect) {
        const selectedOption = languageSelect.options[languageSelect.selectedIndex];
        statusLanguage.textContent = selectedOption.textContent.split(' ')[1] || 'English';
    }
    
    // Update currency status
    const currencySelect = document.getElementById('currencyFormat');
    const statusCurrency = document.getElementById('statusCurrency');
    if (statusCurrency && currencySelect) {
        statusCurrency.textContent = currencySelect.value;
    }
    
    // Update timezone status
    const timezoneSelect = document.getElementById('timezone');
    const statusTimezone = document.getElementById('statusTimezone');
    if (statusTimezone && timezoneSelect) {
        const timezoneOffsets = {
            'Asia/Dhaka': 'GMT+6',
            'Asia/Kolkata': 'GMT+5:30',
            'Asia/Karachi': 'GMT+5',
            'UTC': 'GMT+0',
            'America/New_York': 'GMT-5'
        };
        statusTimezone.textContent = timezoneOffsets[timezoneSelect.value] || 'GMT+6';
    }
}

function previewTheme(theme) {
    const preview = document.getElementById('themePreview');
    const previewWindow = preview.querySelector('.preview-window');
    
    // Remove existing theme classes
    previewWindow.classList.remove('theme-light', 'theme-dark', 'theme-auto');
    
    // Apply theme
    previewWindow.classList.add(`theme-${theme}`);
    
    // Add temporary highlight effect
    previewWindow.style.transform = 'scale(1.02)';
    setTimeout(() => {
        previewWindow.style.transform = 'scale(1)';
    }, 200);
}

function updateThemePreview() {
    const selectedTheme = document.querySelector('input[name="theme_mode"]:checked').value;
    const preview = document.getElementById('themePreview');
    
    // Remove existing theme classes
    preview.classList.remove('theme-light', 'theme-dark', 'theme-auto');
    
    // Apply theme based on selection
    if (selectedTheme === 'dark') {
        preview.classList.add('theme-dark');
    } else if (selectedTheme === 'light') {
        preview.classList.add('theme-light');
    } else {
        // Auto mode - check current time
        const hour = new Date().getHours();
        const dayStart = parseInt(document.querySelector('input[name="day_start_time"]').value.split(':')[0]);
        const nightStart = parseInt(document.querySelector('input[name="night_start_time"]').value.split(':')[0]);
        
        if (hour >= dayStart && hour < nightStart) {
            preview.classList.add('theme-light');
        } else {
            preview.classList.add('theme-dark');
        }
        preview.classList.add('theme-auto');
    }
}

function resetAppearanceSettings() {
    if (confirm('Are you sure you want to reset all appearance settings to defaults?')) {
        // Reset theme mode
        document.getElementById('themeAuto').checked = true;
        document.getElementById('autoSchedule').style.display = 'block';
        
        // Reset language
        document.getElementById('defaultLanguage').value = 'en';
        
        // Reset other settings
        document.querySelector('select[name="currency_format"]').value = 'BDT';
        document.querySelector('select[name="date_format"]').value = 'Y-m-d';
        document.querySelector('select[name="timezone"]').value = 'Asia/Dhaka';
        
        // Reset time inputs
        document.querySelector('input[name="day_start_time"]').value = '06:00';
        document.querySelector('input[name="night_start_time"]').value = '18:00';
        
        updateThemePreview();
        showAlert('Settings reset to defaults. Click Save to apply changes.', 'info');
    }
}

// Auto-save functionality for appearance settings
document.getElementById('appearanceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'update_appearance');
    
    // Collect all form data
    const inputs = this.querySelectorAll('input, select');
    inputs.forEach(input => {
        if (input.type === 'radio' && !input.checked) return;
        formData.append(input.name, input.value);
    });
    
    fetch('settings-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Appearance settings saved successfully!', 'success');
            // Apply theme immediately if possible
            applyThemeToPage();
        } else {
            showAlert(data.message || 'Error saving appearance settings', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving appearance settings', 'danger');
    });
});

function applyThemeToPage() {
    const selectedTheme = document.querySelector('input[name="theme_mode"]:checked').value;
    const body = document.body;
    
    // Remove existing theme classes
    body.classList.remove('theme-light', 'theme-dark');
    
    if (selectedTheme === 'dark') {
        body.classList.add('theme-dark');
    } else if (selectedTheme === 'light') {
        body.classList.add('theme-light');
    } else {
        // Auto mode
        const hour = new Date().getHours();
        const dayStart = parseInt(document.querySelector('input[name="day_start_time"]').value.split(':')[0]);
        const nightStart = parseInt(document.querySelector('input[name="night_start_time"]').value.split(':')[0]);
        
        if (hour >= dayStart && hour < nightStart) {
            body.classList.add('theme-light');
        } else {
            body.classList.add('theme-dark');
        }
    }
}
</script>

<style>
/* Professional Appearance Settings Styles */
.appearance-section {
    margin-bottom: 3rem;
}

.section-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 0.75rem;
    margin-bottom: 2rem;
    border: 1px solid #dee2e6;
}

.section-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

/* Theme Cards */
.theme-option {
    margin-bottom: 1.5rem;
}

.theme-check {
    margin: 0;
}

.theme-label {
    cursor: pointer;
    width: 100%;
    margin: 0;
}

.theme-card {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border: 2px solid #e9ecef;
    border-radius: 0.75rem;
    background: #ffffff;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.theme-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
    transform: translateY(-2px);
}

.theme-check input:checked + .theme-label .theme-card {
    border-color: #007bff;
    background: linear-gradient(135deg, #f8f9ff 0%, #e6f3ff 100%);
    box-shadow: 0 4px 16px rgba(0, 123, 255, 0.2);
}

.theme-icon {
    margin-right: 1.5rem;
    width: 60px;
    text-align: center;
}

.theme-info {
    flex: 1;
}

.theme-title {
    margin-bottom: 0.5rem;
    color: #2c3e50;
    font-weight: 600;
}

.theme-desc {
    color: #6c757d;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
}

.theme-features .badge {
    margin-right: 0.5rem;
    font-size: 0.75rem;
}

/* Auto Schedule Settings */
.auto-schedule-settings {
    background: linear-gradient(135deg, #f8f9ff 0%, #e6f3ff 100%);
    border-radius: 0.5rem;
    padding: 1.5rem;
    border: 1px solid #b3d9ff;
}

.schedule-card {
    background: white;
    border-radius: 0.5rem;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
}

.time-input-group {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
    border: 1px solid #e9ecef;
}

.time-input {
    border: 2px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 0.75rem;
    font-size: 1.1rem;
    font-weight: 600;
    text-align: center;
}

.time-input:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Schedule Timeline */
.schedule-timeline {
    margin-top: 1rem;
}

.timeline-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
    border-left: 4px solid #e9ecef;
    transition: all 0.3s ease;
}

.timeline-item.active {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border-left-color: #28a745;
    color: #155724;
    font-weight: 600;
}

.timeline-item i {
    margin-right: 0.75rem;
    width: 20px;
}

.current-status {
    margin-top: 1rem;
    padding: 0.75rem;
    background: white;
    border-radius: 0.5rem;
    text-align: center;
    border: 1px solid #e9ecef;
}

/* Live Theme Preview */
.theme-preview-container {
    position: relative;
}

.preview-window {
    background: #ffffff;
    border: 2px solid #e9ecef;
    border-radius: 0.75rem;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.preview-window.theme-dark {
    background: #2d3748;
    color: #e2e8f0;
    border-color: #4a5568;
}

.preview-window.theme-light {
    background: #ffffff;
    color: #2d3748;
    border-color: #e2e8f0;
}

.preview-header {
    padding: 1rem;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.preview-window.theme-dark .preview-header {
    background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
}

.preview-logo {
    font-weight: 600;
    display: flex;
    align-items: center;
}

.preview-status {
    display: flex;
    align-items: center;
    font-size: 0.85rem;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 0.5rem;
    animation: pulse 2s infinite;
}

.status-indicator.status-auto {
    background: linear-gradient(45deg, #28a745, #007bff);
}

.status-indicator.status-light {
    background: #ffc107;
}

.status-indicator.status-dark {
    background: #6c757d;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.preview-content {
    padding: 1rem;
}

.preview-nav {
    display: flex;
    margin-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.preview-window.theme-dark .preview-nav {
    border-bottom-color: #4a5568;
}

.nav-item {
    padding: 0.5rem 1rem;
    margin-right: 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.nav-item.active {
    background: #007bff;
    color: white;
}

.preview-window.theme-dark .nav-item.active {
    background: #4299e1;
}

.preview-main h6 {
    margin-bottom: 0.75rem;
    font-weight: 600;
}

.preview-main p {
    font-size: 0.85rem;
    margin-bottom: 1rem;
    opacity: 0.8;
}

.preview-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-preview {
    padding: 0.4rem 0.8rem;
    border-radius: 0.25rem;
    border: none;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-preview.btn-primary {
    background: #007bff;
    color: white;
}

.btn-preview.btn-secondary {
    background: transparent;
    color: #6c757d;
    border: 1px solid #6c757d;
}

.preview-window.theme-dark .btn-preview.btn-primary {
    background: #4299e1;
}

.preview-window.theme-dark .btn-preview.btn-secondary {
    color: #e2e8f0;
    border-color: #e2e8f0;
}

/* Form Enhancements */
.form-select-lg {
    padding: 0.75rem 1rem;
    font-size: 1.1rem;
    border-radius: 0.5rem;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-select-lg:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-group {
    position: relative;
}

.form-label.fw-bold {
    font-size: 1rem;
    margin-bottom: 0.75rem;
    color: #2c3e50;
}

/* Preview Items */
.preview-item {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.preview-item:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

.preview-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #007bff;
    margin-top: 0.5rem;
}

/* Gradient Backgrounds */
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
}

/* Responsive Design */
@media (max-width: 768px) {
    .theme-card {
        flex-direction: column;
        text-align: center;
    }
    
    .theme-icon {
        margin-right: 0;
        margin-bottom: 1rem;
    }
    
    .section-header {
        flex-direction: column;
        text-align: center;
    }
    
    .section-icon {
        margin-right: 0;
        margin-bottom: 1rem;
    }
}

/* Animation Enhancements */
.theme-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.theme-check input:checked + .theme-label .theme-card::before {
    left: 100%;
}

/* Professional Action Buttons */
.appearance-actions {
    margin-top: 2rem;
    margin-bottom: 2rem;
}

.settings-status {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
}

.status-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 0.75rem;
    background: white;
    border-radius: 0.5rem;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.status-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.status-item i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.status-label {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.status-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2c3e50;
}

/* Theme Transitions and Animations */
@keyframes themeTransition {
    0%, 100% { 
        background: #ffffff; 
        color: #333333; 
    }
    50% { 
        background: #2d3748; 
        color: #e2e8f0; 
    }
}

.preview-window.theme-auto {
    animation: themeTransition 3s infinite;
}

/* Global Dark Theme Styles */
body.theme-dark {
    background-color: #1a202c;
    color: #e2e8f0;
    transition: all 0.3s ease;
}

body.theme-dark .card {
    background-color: #2d3748;
    border-color: #4a5568;
}

body.theme-dark .nav-tabs .nav-link {
    color: #e2e8f0;
    background-color: #2d3748;
    border-color: #4a5568;
}

body.theme-dark .nav-tabs .nav-link.active {
    background-color: #4a5568;
    border-color: #4a5568;
    color: #ffffff;
}

body.theme-dark .form-control,
body.theme-dark .form-select {
    background-color: #4a5568;
    border-color: #718096;
    color: #e2e8f0;
}

body.theme-dark .form-control:focus,
body.theme-dark .form-select:focus {
    background-color: #4a5568;
    border-color: #4299e1;
    color: #e2e8f0;
    box-shadow: 0 0 0 0.2rem rgba(66, 153, 225, 0.25);
}

body.theme-dark .table {
    color: #e2e8f0;
}

body.theme-dark .table-striped > tbody > tr:nth-of-type(odd) > td {
    background-color: rgba(255, 255, 255, 0.05);
}

body.theme-dark .section-header {
    background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
    color: #e2e8f0;
}

body.theme-dark .theme-card {
    background-color: #2d3748;
    border-color: #4a5568;
    color: #e2e8f0;
}

body.theme-dark .theme-card:hover {
    border-color: #4299e1;
    background-color: #4a5568;
}

body.theme-dark .status-item {
    background-color: #2d3748;
    border-color: #4a5568;
    color: #e2e8f0;
}

body.theme-dark .status-value {
    color: #4299e1;
}

body.theme-dark .settings-status {
    background-color: #2d3748;
}

/* Global Light Theme Styles */
body.theme-light {
    background-color: #f7fafc;
    color: #2d3748;
    transition: all 0.3s ease;
}

/* Auto-schedule visibility animation */
.auto-schedule {
    transition: all 0.3s ease;
}
</style>

                    </div>
                </main>
                <?php require __DIR__ . '/components/footer.php'; ?>
            </div>
        </div>
    </body>
</html>
