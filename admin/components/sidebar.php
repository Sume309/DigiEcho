<div id="layoutSidenav_nav">
<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
                <nav class="sb-sidenav accordion sb-sidenav-light" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <a class="nav-link d-flex align-items-center justify-content-center py-3" href="../index.php" title="Go to Homepage" target="_blank" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; margin-bottom: 20px; text-decoration: none; position: relative; overflow: hidden;">                                
                                <div class="d-flex align-items-center">
                                    <img class="img-fluid me-2" src="./assets/img/logo.jpg" alt="Homepage" style="max-height: 35px; width: auto; border-radius: 6px;">
                                    <i class="fas fa-home text-white" style="font-size: 18px; opacity: 0.9;"></i>
                                </div>
                                <div class="position-absolute top-0 start-0 w-100 h-100" style="background: rgba(255,255,255,0.1); opacity: 0; transition: opacity 0.3s ease;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'"></div>
                            </a>
                            <a class="nav-link" href="index.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsebrandLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Menu
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapsebrandLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="pos.php">POS</a>
                                    <!-- <a class="nav-link" href="brand-add.php">Add</a> -->
                                </nav>
                            </div>
                            <div class="sb-sidenav-menu-heading">Interface</div>

                            <!-- Products -->
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProducts" aria-expanded="false" aria-controls="collapseProducts" onclick="toggleCollapse('collapseProducts')">
                                <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>
                                Products
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseProducts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                  
                                    <a class="nav-link" href="product-management.php"><i class="fas fa-list-ul me-2"></i>Manage Products</a>
                                                                       
                                    <a class="nav-link" href="inventory-management.php"><i class="fas fa-warehouse me-2"></i>Inventory Management</a>
                                    <a class="nav-link" href="discounts-management.php"><i class="fas fa-percent me-2"></i>Discounts & Offers</a>
                                    <a class="nav-link" href="reviews-management.php"><i class="fas fa-comments me-2"></i>Reviews & Ratings</a>
                                </nav>
                            </div>

                             <!-- Brands -->
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseBrands" aria-expanded="false" aria-controls="collapseBrands" onclick="toggleCollapse('collapseBrands')">
                                <div class="sb-nav-link-icon"><i class="fas fa-trademark"></i></div>
                                Brands
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseBrands" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="brand-all.php"><i class="fas fa-list-ul me-2"></i>All Brands</a>
                                    <a class="nav-link" href="brand-add.php"><i class="fas fa-plus-circle me-2"></i>Add New</a>
                                </nav>
                            </div>
                            <!-- Categories -->
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCategories" aria-expanded="false" aria-controls="collapseCategories" onclick="toggleCollapse('collapseCategories')">
                                <div class="sb-nav-link-icon"><i class="fas fa-tags"></i></div>
                                Categories
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseCategories" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="category-all.php"><i class="fas fa-list-ul me-2"></i>All Categories</a>
                                    <a class="nav-link" href="category-add.php"><i class="fas fa-plus-circle me-2"></i>Add New</a>
                                   
                                </nav>
                            </div>
                            <!-- Sub Categories -->
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSubCategories" aria-expanded="false" aria-controls="collapseSubCategories" onclick="toggleCollapse('collapseSubCategories')">
                                <div class="sb-nav-link-icon"><i class="fas fa-tag"></i></div>
                                Sub Categories
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseSubCategories" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="subcategory-all.php"><i class="fas fa-list-ul me-2"></i>All Sub Categories</a>
                                    <a class="nav-link" href="subcategory-add.php"><i class="fas fa-plus-circle me-2"></i>Add New</a>
                                </nav>
                            </div>
                           
                            
                            
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseOrders" aria-expanded="false" aria-controls="collapsePages" onclick="toggleCollapse('collapseOrders')">
                                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                                Orders
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseOrders" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                  
                                    <a class="nav-link" href="order-management.php"><i class="fas fa-shopping-bag me-2"></i>Manage Orders</a>
                                </nav>
                            </div>
                            
                            
                            <!-- Messages Management -->
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseMessages" aria-expanded="false" aria-controls="collapseMessages" onclick="toggleCollapse('collapseMessages')">
                                <div class="sb-nav-link-icon"><i class="fas fa-envelope"></i></div>
                                Messages
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseMessages" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="messages-all.php"><i class="fas fa-list-ul me-2"></i>All Messages</a>
                                    <a class="nav-link" href="messages-all.php?status=new"><i class="fas fa-envelope me-2"></i>New Messages</a>
                                    <a class="nav-link" href="messages-all.php?status=replied"><i class="fas fa-reply me-2"></i>Replied</a>
                                </nav>
                            </div>
                            <!-- Live Chat Management -->
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseChat" aria-expanded="false" aria-controls="collapseChat" onclick="toggleCollapse('collapseChat')">
                                <div class="sb-nav-link-icon"><i class="fas fa-comments"></i></div>
                                Live Chat
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseChat" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="live-chat.php"><i class="fas fa-comments me-2"></i>Live Chat Dashboard</a>
                                    <a class="nav-link" href="chat-conversations.php"><i class="fas fa-list me-2"></i>All Conversations</a>
                                    <a class="nav-link" href="chat-settings.php"><i class="fas fa-cog me-2"></i>Chat Settings</a>
                                </nav>
                            </div>
                            <!-- Team Management -->
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseTeam" aria-expanded="false" aria-controls="collapseTeam" onclick="toggleCollapse('collapseTeam')">
                                <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                Team
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseTeam" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="team-all.php"><i class="fas fa-list-ul me-2"></i>All Team Members</a>
                                    <a class="nav-link" href="team-add.php"><i class="fas fa-plus-circle me-2"></i>Add New Member</a>
                                </nav>
                            </div>
                            <!-- Notification Management -->
                            <a class="nav-link" href="notification-management.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-bell"></i></div>
                                Notification
                            </a>
                            <!-- <div class="sb-sidenav-menu-heading">Addons</div>
                            <a class="nav-link" href="charts.html">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                Charts
                            </a>
                            <a class="nav-link" href="tables.html">
                                <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                                Tables
                            </a>
                            <a class="nav-link" href="blank.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                                Blank
                            </a> -->
                           

                             <!-- Users Management -->

                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseUsers" aria-expanded="false" aria-controls="collapseLayouts" onclick="toggleCollapse('collapseUsers')">
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Users
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseUsers" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="users-all.php">Manage User</a>
                                  
                                </nav>
                            </div>

                             <!-- reports management -->

                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseReports" aria-expanded="false" aria-controls="collapsePages" onclick="toggleCollapse('collapseReports')">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                Reports
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseReports" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="reports-dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Reports Dashboard</a>
                                    <a class="nav-link" href="reports-list.php"><i class="fas fa-list me-2"></i>All Reports</a>
                                    <a class="nav-link" href="reports-view.php"><i class="fas fa-eye me-2"></i>View Reports</a>
                                    <a class="nav-link" href="reports-builder.php"><i class="fas fa-tools me-2"></i>Report Builder</a>
                                    <a class="nav-link" href="download-center.php"><i class="fas fa-download me-2"></i>Download Center</a>
                                </nav>
                            </div>

                             <!-- Settings -->
                            <a class="nav-link" href="settings.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-cog"></i></div>
                                Settings
                            </a>
                            
                        </div>
                    </div>
                    <div class="sb-sidenav-footer">
                        <div class="small">Logged in as:</div>
                        <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin' ?>
                    </div>
                </nav>
            </div>
            
            <!-- Manual Collapse Toggle Script -->
            <script>
            function toggleCollapse(collapseId) {
                // Prevent default behavior
                event.preventDefault();
                
                // Get the collapse element
                const collapseElement = document.getElementById(collapseId);
                
                // Toggle the collapse
                if (collapseElement.classList.contains('show')) {
                    // Hide it
                    collapseElement.classList.remove('show');
                } else {
                    // Show it
                    collapseElement.classList.add('show');
                }
            }
            
            // Ensure all navigation links work properly
            document.addEventListener('DOMContentLoaded', function() {
                // Add click event to all nav links that have href attributes
                const navLinks = document.querySelectorAll('.nav-link[href]:not([data-bs-toggle])');
                navLinks.forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        const href = this.getAttribute('href');
                        if (href && href !== '#' && href !== '!') {
                            // Allow normal navigation
                            return true;
                        }
                    });
                });
            });
            </script>