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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    $migrationFile = __DIR__ . '/../migrations/20250929_add_admin_profile_columns.sql';
    
    if (file_exists($migrationFile)) {
        $sql = file_get_contents($migrationFile);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $db->startTransaction();
        
        try {
            foreach ($statements as $statement) {
                if (!empty($statement) && !str_starts_with($statement, '--')) {
                    $result = $db->rawQuery($statement);
                    if (!$result) {
                        throw new Exception("Failed to execute: " . $statement . " - " . $db->getLastError());
                    }
                }
            }
            
            $db->commit();
            $message = 'Migration executed successfully! The user_profiles table has been updated with new columns.';
            $success = true;
            
        } catch (Exception $e) {
            $db->rollback();
            $message = 'Migration failed: ' . $e->getMessage();
            $success = false;
        }
    } else {
        $message = 'Migration file not found!';
        $success = false;
    }
}

// Check if migration is needed
$migrationNeeded = false;
try {
    $result = $db->rawQuery("SHOW COLUMNS FROM user_profiles LIKE 'job_title'");
    $migrationNeeded = empty($result);
} catch (Exception $e) {
    $migrationNeeded = true;
}
?>

<?php require __DIR__ . '/components/header.php'; ?>

<style>
.migration-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}
.migration-header {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    padding: 2rem;
}
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.875rem;
}
.status-needed {
    background-color: #f6c23e;
    color: #1f2937;
}
.status-completed {
    background-color: #1cc88a;
    color: white;
}
.code-block {
    background-color: #f8f9fc;
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 1rem;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    max-height: 300px;
    overflow-y: auto;
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
                    <h1 class="mt-4">Database Migration</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Database Migration</li>
                    </ol>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?= $success ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card migration-card">
                                <div class="migration-header text-center">
                                    <h2><i class="fas fa-database me-2"></i>Admin Profile Migration</h2>
                                    <p class="mb-0">Update user_profiles table to support comprehensive admin profile management</p>
                                </div>
                                
                                <div class="card-body p-4">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h5><i class="fas fa-info-circle me-2 text-primary"></i>Migration Status</h5>
                                            <?php if ($migrationNeeded): ?>
                                                <span class="status-badge status-needed">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Migration Required
                                                </span>
                                                <p class="mt-2 text-muted">The user_profiles table is missing required columns for admin profile management.</p>
                                            <?php else: ?>
                                                <span class="status-badge status-completed">
                                                    <i class="fas fa-check-circle me-1"></i>Migration Completed
                                                </span>
                                                <p class="mt-2 text-muted">All required columns are present in the user_profiles table.</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <h5><i class="fas fa-list me-2 text-success"></i>New Columns to Add</h5>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-plus text-success me-1"></i> job_title</li>
                                                <li><i class="fas fa-plus text-success me-1"></i> department</li>
                                                <li><i class="fas fa-plus text-success me-1"></i> bio</li>
                                                <li><i class="fas fa-plus text-success me-1"></i> address, city, state, zip_code</li>
                                                <li><i class="fas fa-plus text-success me-1"></i> website, linkedin, twitter, facebook</li>
                                                <li><i class="fas fa-plus text-success me-1"></i> timezone, language</li>
                                                <li><i class="fas fa-plus text-success me-1"></i> email_notifications, two_factor_auth</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <?php if ($migrationNeeded): ?>
                                        <div class="alert alert-warning" role="alert">
                                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Action Required</h6>
                                            <p class="mb-3">The profile settings page is currently failing because required database columns are missing. Run this migration to fix the issue.</p>
                                            
                                            <form method="post" action="" onsubmit="return confirm('Are you sure you want to run this migration? This will modify the database structure.');">
                                                <button type="submit" name="run_migration" class="btn btn-primary">
                                                    <i class="fas fa-play me-2"></i>Run Migration Now
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-success" role="alert">
                                            <h6><i class="fas fa-check-circle me-2"></i>Migration Complete</h6>
                                            <p class="mb-0">The database has been successfully updated. You can now use the admin profile settings page.</p>
                                            <a href="profile-settings.php" class="btn btn-success mt-2">
                                                <i class="fas fa-user-cog me-2"></i>Go to Profile Settings
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-4">
                                        <h5><i class="fas fa-code me-2 text-info"></i>Migration SQL Preview</h5>
                                        <div class="code-block">
<?php
$migrationFile = __DIR__ . '/../migrations/20250929_add_admin_profile_columns.sql';
if (file_exists($migrationFile)) {
    echo htmlspecialchars(file_get_contents($migrationFile));
} else {
    echo "Migration file not found.";
}
?>
                                        </div>
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
