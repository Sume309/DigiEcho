<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
            <!-- Navbar Brand-->
            <a class="navbar-brand ps-3" href="../index.php"><?= settings()['companyname']?></a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <!-- Navbar Search-->
            <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0" id="navbarSearchForm">
                <div class="input-group">
                    <input class="form-control" type="text" id="navbarSearchInput" placeholder="Search products, orders, users..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
                    <button class="btn btn-primary" id="btnNavbarSearch" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                <!-- Notifications Dropdown -->
                <li class="nav-item dropdown me-3">
                    <a class="nav-link dropdown-toggle position-relative" href="#" id="navbarNotifications" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationCount" style="display: none; font-size: 0.6rem;">
                            0
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="navbarNotifications" style="width: 320px; max-height: 400px; overflow-y: auto; right: 0; left: auto; transform: translateX(0) !important;">
                        <li class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <span class="fw-bold">Notifications</span>
                            <button class="btn btn-sm btn-outline-secondary py-0" onclick="markAllAsReadHeader()">Mark all read</button>
                        </li>
                        <div id="notificationList">
                            <li class="dropdown-item text-center text-muted py-3">Loading notifications...</li>
                        </div>
                        <li class="dropdown-footer text-center py-2 border-top">
                            <a href="notifications.php" class="text-primary">View All Notifications</a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                        <span class="d-none d-md-inline ms-1"><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin' ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><h6 class="dropdown-header">
                            <i class="fas fa-user-circle me-2"></i>
                            <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin' ?>
                        </h6></li>
                        <li><hr class="dropdown-divider" /></li>
                        <li><a class="dropdown-item" href="profile-settings.php">
                            <i class="fas fa-cog me-2"></i>Account Settings
                        </a></li>
                        <li><a class="dropdown-item" href="activity-log.php">
                            <i class="fas fa-history me-2"></i>Activity Log
                        </a></li>
                        <li><a class="dropdown-item" href="admin-profile.php">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </a></li>
                        <li><hr class="dropdown-divider" /></li>
                        <li><a class="dropdown-item text-danger" href="logout.php" onclick="return confirm('Are you sure you want to logout?')">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <style>
        /* Ensure notifications dropdown is properly positioned */
        .notification-dropdown {
            transform: translateX(0) !important;
            right: 0 !important;
            left: auto !important;
            margin-top: 0.5rem !important;
        }
        
        /* Adjust dropdown position on smaller screens */
        @media (max-width: 768px) {
            .notification-dropdown {
                right: -50px !important;
                width: 280px !important;
            }
        }
        
        /* Ensure dropdown doesn't go off screen */
        @media (max-width: 576px) {
            .notification-dropdown {
                right: -80px !important;
                width: 250px !important;
            }
        }
        
        /* Notification item styling */
        .notification-item {
            border-bottom: 1px solid #eee;
            padding: 10px 15px;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        
        .notification-item.unread {
            background-color: #f0f8ff;
        }
        
        .notification-icon {
            font-size: 1.2em;
            margin-right: 10px;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .notification-message {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .notification-time {
            font-size: 0.8em;
            color: #999;
        }
        </style>

        <script>
        // Load notifications for the header dropdown
        document.addEventListener('DOMContentLoaded', function() {
            loadHeaderNotifications();
            // Refresh notifications every 30 seconds
            setInterval(loadHeaderNotifications, 30000);
        });

        function loadHeaderNotifications() {
            fetch('apis/dashboard-stats.php?action=notifications')
                .then(response => response.json())
                .then(data => {
                    const notificationList = document.getElementById('notificationList');
                    const notificationCount = document.getElementById('notificationCount');
                    
                    if (!data.success || !data.data || data.data.length === 0) {
                        notificationList.innerHTML = '<li class="dropdown-item text-center text-muted py-3">No notifications</li>';
                        notificationCount.style.display = 'none';
                        return;
                    }

                    let html = '';
                    let unreadCount = 0;
                    
                    // Show only the 5 most recent notifications
                    const notificationsToShow = data.data.slice(0, 5);
                    
                    notificationsToShow.forEach(notification => {
                        const timeAgo = getTimeAgoHeader(notification.created_at);
                        const icon = getNotificationIconHeader(notification.type);
                        const color = getNotificationColorHeader(notification.type);
                        
                        if (!notification.is_read) {
                            unreadCount++;
                        }
                        
                        html += `
                            <li class="dropdown-item ${!notification.is_read ? 'unread' : ''}">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas ${icon} text-${color} notification-icon"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <div class="notification-title">${notification.title}</div>
                                        <div class="notification-message">${formatNotificationMessageHeader(notification)}</div>
                                        <div class="notification-time">${timeAgo}</div>
                                    </div>
                                </div>
                            </li>
                        `;
                    });
                    
                    notificationList.innerHTML = html;
                    
                    // Update notification count badge
                    if (unreadCount > 0) {
                        notificationCount.textContent = unreadCount;
                        notificationCount.style.display = 'block';
                    } else {
                        notificationCount.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading header notifications:', error);
                    document.getElementById('notificationList').innerHTML = '<li class="dropdown-item text-center text-danger py-3">Error loading notifications</li>';
                });
        }

        function markAllAsReadHeader() {
            fetch('apis/dashboard-stats.php?action=mark_notifications_read', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload notifications
                    loadHeaderNotifications();
                    // Also reload the main notifications page if it's open
                    if (typeof loadNotificationsPage === 'function') {
                        loadNotificationsPage();
                    }
                }
            })
            .catch(error => {
                console.error('Error marking notifications as read:', error);
            });
        }

        function formatNotificationMessageHeader(notification) {
            // Clean up messages for header dropdown display
            let message = notification.message;
            
            // For user activity, make it more concise
            if (notification.type === 'user_activity') {
                try {
                    const metadata = typeof notification.metadata === 'string' 
                        ? JSON.parse(notification.metadata) 
                        : notification.metadata;
                    
                    if (metadata && metadata.details && metadata.details.ip) {
                        const ip = metadata.details.ip === '::1' ? 'localhost' : metadata.details.ip;
                        message = `${metadata.username} ${metadata.activity} from ${ip}`;
                    }
                } catch (e) {
                    // Keep original message if parsing fails
                }
            }
            
            // Truncate long messages for dropdown
            if (message.length > 60) {
                message = message.substring(0, 57) + '...';
            }
            
            return message;
        }

        function getNotificationIconHeader(type) {
            const icons = {
                'low_stock': 'fa-exclamation-triangle',
                'new_order': 'fa-shopping-cart',
                'system': 'fa-info-circle',
                'warning': 'fa-exclamation-triangle',
                'success': 'fa-check-circle',
                'user_activity': 'fa-user',
                'order_update': 'fa-truck',
                'payment': 'fa-credit-card',
                'high_value_order': 'fa-money-bill-wave',
                'security': 'fa-shield-alt',
                'system_error': 'fa-bug',
                'back_in_stock': 'fa-box',
                'price_change': 'fa-tags'
            };
            return icons[type] || 'fa-bell';
        }

        function getNotificationColorHeader(type) {
            const colors = {
                'low_stock': 'warning',
                'new_order': 'success',
                'system': 'info',
                'warning': 'warning',
                'danger': 'danger',
                'success': 'success',
                'user_activity': 'primary',
                'order_update': 'info',
                'payment': 'success',
                'high_value_order': 'danger',
                'security': 'danger',
                'system_error': 'warning',
                'back_in_stock': 'success',
                'price_change': 'info'
            };
            return colors[type] || 'primary';
        }

        function getTimeAgoHeader(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diffInSeconds = Math.floor((now - time) / 1000);
            
            if (diffInSeconds < 0) return 'Just now';
            if (diffInSeconds < 60) return 'Just now';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
            return Math.floor(diffInSeconds / 86400) + 'd ago';
        }
        </script>