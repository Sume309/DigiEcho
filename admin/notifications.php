<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/auth/admin.php';
use App\auth\Admin;
if(!Admin::Check()){
    header('HTTP/1.1 503 Service Unavailable');
    exit;
}
$page = "notifications";
?>
<?php require __DIR__.'/components/header.php'; ?>
    </head>
    <body class="sb-nav-fixed">
    <?php require __DIR__.'/components/navbar.php'; ?>
        <div id="layoutSidenav">
        <?php require __DIR__.'/components/sidebar.php'; ?>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                            <div>
                                <h1>Notifications</h1>
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Notifications</li>
                                </ol>
                            </div>
                            <div>
                                <button class="btn btn-outline-primary me-2" onclick="markAllAsRead()">
                                    <i class="fas fa-check-double me-1"></i> Mark All as Read
                                </button>
                                <button class="btn btn-outline-secondary" onclick="refreshNotifications()">
                                    <i class="fas fa-sync-alt me-1"></i> Refresh
                                </button>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-bell me-1"></i>
                                Recent Notifications
                                <span class="float-end">
                                    <span class="badge bg-primary" id="notificationCount">0</span>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="list-group" id="notificationsList">
                                    <div class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2">Loading notifications...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <?php require __DIR__.'/components/footer.php'; ?>
            </div>
        </div>

        <script src="<?= settings()['adminpage'] ?>assets/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
        
        <script>
        // Load notifications when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadNotificationsPage();
        });

        function loadNotificationsPage() {
            fetch('apis/dashboard-stats.php?action=notifications')
                .then(response => response.json())
                .then(data => {
                    const notificationsList = document.getElementById('notificationsList');
                    const notificationCount = document.getElementById('notificationCount');
                    
                    if (!data.success || !data.data || data.data.length === 0) {
                        notificationsList.innerHTML = `
                            <div class="text-center py-5">
                                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No notifications found</p>
                            </div>
                        `;
                        notificationCount.textContent = '0';
                        return;
                    }

                    let html = '';
                    let unreadCount = 0;
                    data.data.forEach(notification => {
                        const timeAgo = getTimeAgo(notification.created_at);
                        const icon = getNotificationIcon(notification.type);
                        const color = getNotificationColor(notification.type);
                        
                        if (!notification.is_read) {
                            unreadCount++;
                        }
                        
                        html += `
                            <div class="list-group-item list-group-item-action ${!notification.is_read ? 'bg-light' : ''}">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <i class="fas ${icon} text-${color} me-2"></i>
                                        ${notification.title}
                                    </h6>
                                    <small class="text-muted">${timeAgo}</small>
                                </div>
                                <p class="mb-1">${formatNotificationMessage(notification)}</p>
                                ${formatNotificationDetails(notification)}
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-primary mark-read-btn" 
                                            data-id="${notification.id}" 
                                            ${notification.is_read ? 'disabled' : ''}>
                                        ${notification.is_read ? 'Read' : 'Mark as Read'}
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    
                    notificationsList.innerHTML = html;
                    notificationCount.textContent = unreadCount;
                    
                    // Add event listeners to mark as read buttons
                    document.querySelectorAll('.mark-read-btn').forEach(button => {
                        button.addEventListener('click', function() {
                            const id = this.getAttribute('data-id');
                            markAsRead(id, this);
                        });
                    });
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    const notificationsList = document.getElementById('notificationsList');
                    notificationsList.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading notifications. Please try again later.
                        </div>
                    `;
                });
        }

        function markAsRead(id, button) {
            fetch('apis/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({id: id})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Success', 'Notification marked as read');
                    button.disabled = true;
                    button.textContent = 'Read';
                    button.classList.remove('btn-outline-primary');
                    button.classList.add('btn-outline-secondary');
                    
                    // Update notification count
                    const notificationCount = document.getElementById('notificationCount');
                    const currentCount = parseInt(notificationCount.textContent);
                    if (currentCount > 0) {
                        notificationCount.textContent = currentCount - 1;
                    }
                    
                    // Update background color of the notification
                    const notificationItem = button.closest('.list-group-item');
                    if (notificationItem) {
                        notificationItem.classList.remove('bg-light');
                    }
                } else {
                    showAlert('danger', 'Error', data.message || 'Failed to mark notification as read');
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
                showAlert('danger', 'Error', 'Failed to mark notification as read');
            });
        }

        function markAllAsRead() {
            fetch('apis/dashboard-stats.php?action=mark_notifications_read', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Success', 'All notifications marked as read');
                    loadNotificationsPage();
                    // Also update the notification badge in the header
                    const badge = document.getElementById('notificationCount');
                    if (badge) {
                        badge.textContent = '0';
                    }
                } else {
                    showAlert('danger', 'Error', data.message || 'Failed to mark all notifications as read');
                }
            })
            .catch(error => {
                console.error('Error marking notifications as read:', error);
                showAlert('danger', 'Error', 'Failed to mark notifications as read');
            });
        }

        function refreshNotifications() {
            loadNotificationsPage();
            showAlert('info', 'Refreshed', 'Notifications refreshed');
        }

        function formatNotificationMessage(notification) {
            // Return clean, professional message without technical details
            return notification.message;
        }

        function formatNotificationDetails(notification) {
            if (!notification.metadata) return '';
            
            try {
                const metadata = typeof notification.metadata === 'string' 
                    ? JSON.parse(notification.metadata) 
                    : notification.metadata;
                
                let details = '';
                
                // Format based on notification type
                switch (notification.type) {
                    case 'user_activity':
                        if (metadata.details && metadata.details.ip) {
                            const ip = metadata.details.ip === '::1' ? 'localhost' : metadata.details.ip;
                            details = `<small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>From: ${ip}</small>`;
                        }
                        break;
                        
                    case 'new_order':
                        if (metadata.amount) {
                            details = `<small class="text-muted"><i class="fas fa-money-bill me-1"></i>Amount: BDT ${metadata.amount}</small>`;
                        }
                        break;
                        
                    case 'high_value_order':
                        if (metadata.amount && metadata.threshold) {
                            details = `<small class="text-muted"><i class="fas fa-exclamation-triangle me-1"></i>High value: BDT ${metadata.amount} (threshold: BDT ${metadata.threshold})</small>`;
                        }
                        break;
                        
                    case 'security':
                        if (metadata.ip) {
                            const ip = metadata.ip === '::1' ? 'localhost' : metadata.ip;
                            details = `<small class="text-muted text-danger"><i class="fas fa-shield-alt me-1"></i>Security Alert from: ${ip}</small>`;
                        }
                        break;
                        
                    case 'payment':
                        if (metadata.amount && metadata.payment_method) {
                            details = `<small class="text-muted"><i class="fas fa-credit-card me-1"></i>${metadata.payment_method} - BDT ${metadata.amount}</small>`;
                        }
                        break;
                        
                    case 'user_registration':
                        if (metadata.email) {
                            details = `<small class="text-muted"><i class="fas fa-envelope me-1"></i>${metadata.email}</small>`;
                        }
                        break;
                        
                    default:
                        // For other types, show minimal relevant info
                        if (metadata.product_name) {
                            details = `<small class="text-muted"><i class="fas fa-box me-1"></i>${metadata.product_name}</small>`;
                        }
                        break;
                }
                
                return details;
            } catch (e) {
                // If metadata parsing fails, return empty
                return '';
            }
        }

        function getNotificationIcon(type) {
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

        function getNotificationColor(type) {
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

        function getTimeAgo(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diffInSeconds = Math.floor((now - time) / 1000);
            
            if (diffInSeconds < 0) return 'Just now';
            if (diffInSeconds < 60) return 'Just now';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
            return Math.floor(diffInSeconds / 86400) + ' days ago';
        }

        function showAlert(type, title, message) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.notification-alert');
            existingAlerts.forEach(alert => alert.remove());
            
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show notification-alert" role="alert">
                    <i class="fas ${getNotificationIcon(type)} me-2"></i>
                    <strong>${title}</strong> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Add to the top of the card body
            const cardBody = document.querySelector('.card-body');
            if (cardBody) {
                cardBody.insertAdjacentHTML('afterbegin', alertHtml);
            }
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.querySelector('.notification-alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
        </script>
    </body>
</html>