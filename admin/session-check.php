<?php
session_start();

// Include required files
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/auth/admin.php';

use App\auth\Admin;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Session Debug Information</h3>
                    </div>
                    <div class="card-body">
                        <h5>Session Status:</h5>
                        <p><strong>Session ID:</strong> <?= session_id() ?></p>
                        <p><strong>Session Status:</strong> <?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive' ?></p>
                        
                        <h5>Session Variables:</h5>
                        <pre><?= print_r($_SESSION, true) ?></pre>
                        
                        <h5>Admin Authentication:</h5>
                        <p><strong>Admin Check Result:</strong> <?= Admin::Check() ? 'PASSED' : 'FAILED' ?></p>
                        
                        <div class="mt-4">
                            <a href="quick-login.php" class="btn btn-primary">Quick Login</a>
                            <a href="category-management.php" class="btn btn-secondary">Go to Category Management</a>
                            <a href="../login.php" class="btn btn-warning">Go to Login Page</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>