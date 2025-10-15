<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/auth/admin.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('Location: auto-login.php');
    exit;
}

require __DIR__.'/components/header.php'; 
?>
        <!-- Additional Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="assets/js/scripts.js"></script>
    </head>
    <body class="sb-nav-fixed">
    <?php require __DIR__.'/components/navbar.php'; ?>
        <div id="layoutSidenav">
        <?php require __DIR__.'/components/sidebar.php'; ?>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Navigation Test - Fixed</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Navigation Test</li>
                        </ol>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-check-circle me-1"></i>
                                Navigation Test - Fixed Version
                            </div>
                            <div class="card-body">
                                <h3>Navigation Should Now Work</h3>
                                <p>This page tests if the sidebar navigation is working properly after the fix.</p>
                                
                                <h4>Test Instructions:</h4>
                                <ol>
                                    <li>Click on any of the main menu items in the sidebar (Categories, Sub Categories, Brands, Products, etc.)</li>
                                    <li>The submenu should expand/collapse</li>
                                    <li>Click on any submenu item to navigate to that page</li>
                                    <li>If navigation works, the issue has been fixed</li>
                                </ol>
                                
                                <h4>Direct Links for Testing:</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="list-group">
                                            <li class="list-group-item"><a href="index.php">Dashboard</a></li>
                                            <li class="list-group-item"><a href="category-management.php">Category Management</a></li>
                                            <li class="list-group-item"><a href="subcategory-management.php">Subcategory Management</a></li>
                                            <li class="list-group-item"><a href="brand-all.php">Brand Management</a></li>
                                            <li class="list-group-item"><a href="product-management.php">Product Management</a></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="list-group">
                                            <li class="list-group-item"><a href="inventory-management.php">Inventory Management</a></li>
                                            <li class="list-group-item"><a href="discounts-management.php">Discounts Management</a></li>
                                            <li class="list-group-item"><a href="reviews-management.php">Reviews Management</a></li>
                                            <li class="list-group-item"><a href="orders-all.php">Orders Management</a></li>
                                            <li class="list-group-item"><a href="users-all.php">Users Management</a></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <h4>Debug Information:</h4>
                                    <div id="debug-info">
                                        <p>Checking if all required scripts are loaded...</p>
                                    </div>
                                </div>
                                
                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const debugInfo = document.getElementById('debug-info');
                                    let infoHTML = '<ul class="list-unstyled">';
                                    
                                    // Check if required libraries are loaded
                                    infoHTML += '<li>jQuery: ' + (typeof jQuery !== 'undefined' ? '<span class="text-success">Loaded</span>' : '<span class="text-danger">Not Loaded</span>') + '</li>';
                                    infoHTML += '<li>Bootstrap: ' + (typeof bootstrap !== 'undefined' ? '<span class="text-success">Loaded</span>' : '<span class="text-danger">Not Loaded</span>') + '</li>';
                                    
                                    // Check for Bootstrap components
                                    if (typeof bootstrap !== 'undefined') {
                                        infoHTML += '<li>Bootstrap Collapse: ' + (typeof bootstrap.Collapse !== 'undefined' ? '<span class="text-success">Available</span>' : '<span class="text-warning">Not Available</span>') + '</li>';
                                        infoHTML += '<li>Bootstrap Dropdown: ' + (typeof bootstrap.Dropdown !== 'undefined' ? '<span class="text-success">Available</span>' : '<span class="text-warning">Not Available</span>') + '</li>';
                                    }
                                    
                                    infoHTML += '</ul>';
                                    debugInfo.innerHTML = infoHTML;
                                    
                                    // Test manual navigation
                                    console.log('Navigation test page loaded');
                                    console.log('jQuery loaded:', typeof jQuery !== 'undefined');
                                    console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
                                });
                                </script>
                            </div>
                        </div>
                    </div>
                </main>
                <?php require __DIR__.'/components/footer.php'; ?>
            </div>
        </div>
    </body>
</html>