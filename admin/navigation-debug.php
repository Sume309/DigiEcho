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
        <!-- Additional debugging scripts -->
        <script>
        // Debug script to check if jQuery and Bootstrap are loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded');
            
            // Check if jQuery is loaded
            if (typeof jQuery !== 'undefined') {
                console.log('jQuery is loaded');
            } else {
                console.error('jQuery is NOT loaded');
            }
            
            // Check if Bootstrap is loaded
            if (typeof bootstrap !== 'undefined') {
                console.log('Bootstrap is loaded');
            } else {
                console.error('Bootstrap is NOT loaded');
            }
            
            // Add click event listeners to all navigation links for debugging
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    console.log('Navigation link clicked:', e.target);
                    console.log('Href:', e.target.getAttribute('href'));
                    
                    // If it's a collapse toggle, log that too
                    if (e.target.hasAttribute('data-bs-toggle')) {
                        console.log('Collapse toggle clicked:', e.target.getAttribute('data-bs-target'));
                    }
                });
            });
        });
    </head>
    <body class="sb-nav-fixed">
    <?php require __DIR__.'/components/navbar.php'; ?>
        <div id="layoutSidenav">
        <?php require __DIR__.'/components/sidebar.php'; ?>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Navigation Debug</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Navigation Debug</li>
                        </ol>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-bug me-1"></i>
                                Navigation Debug Information
                            </div>
                            <div class="card-body">
                                <h3>Debug Information</h3>
                                <p>This page helps debug navigation issues.</p>
                                
                                <h4>Check Browser Console</h4>
                                <p>Open your browser's developer tools (F12) and check the console for debug messages.</p>
                                
                                <h4>Manual Navigation Test:</h4>
                                <ol>
                                    <li>Try clicking on "Categories" in the sidebar - you should see a console message</li>
                                    <li>Try clicking on "All Categories" in the dropdown - you should navigate to the page</li>
                                    <li>If navigation doesn't work, check for JavaScript errors in the console</li>
                                </ol>
                                
                                <h4>Direct Links for Testing:</h4>
                                <ul>
                                    <li><a href="index.php">Dashboard</a></li>
                                    <li><a href="category-all.php">Category Management</a></li>
                                    <li><a href="subcategory-all.php">Subcategory Management</a></li>
                                    <li><a href="brand-all.php">Brand Management</a></li>
                                    <li><a href="product-management.php">Product Management</a></li>
                                </ul>
                                
                                <h4>JavaScript Status:</h4>
                                <div id="js-status">
                                    <p>Checking JavaScript status...</p>
                                </div>
                                
                                <script>
                                // Update JavaScript status on page
                                document.addEventListener('DOMContentLoaded', function() {
                                    const statusDiv = document.getElementById('js-status');
                                    let statusHTML = '<ul>';
                                    
                                    statusHTML += '<li>jQuery: ' + (typeof jQuery !== 'undefined' ? 'Loaded' : 'NOT Loaded') + '</li>';
                                    statusHTML += '<li>Bootstrap: ' + (typeof bootstrap !== 'undefined' ? 'Loaded' : 'NOT Loaded') + '</li>';
                                    
                                    // Check for specific Bootstrap components
                                    statusHTML += '<li>Bootstrap Collapse: ' + (typeof bootstrap !== 'undefined' && typeof bootstrap.Collapse !== 'undefined' ? 'Available' : 'NOT Available') + '</li>';
                                    statusHTML += '<li>Bootstrap Dropdown: ' + (typeof bootstrap !== 'undefined' && typeof bootstrap.Dropdown !== 'undefined' ? 'Available' : 'NOT Available') + '</li>';
                                    
                                    statusHTML += '</ul>';
                                    statusDiv.innerHTML = statusHTML;
                                });
                                </script>
                            </div>
                        </div>
                    </div>
                </main>
                <?php require __DIR__.'/components/footer.php'; ?>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="assets/js/scripts.js"></script>
    </body>
</html>