// Notification System for Admin Panel

// Load notifications from the server
function loadNotifications() {
    fetch('apis/dashboard-stats.php?action=notifications')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.length > 0) {
                updateNotificationBadge(data.data);
                updateNotificationDropdown(data.data);
            } else {
                // No notifications
                updateNotificationBadge([]);
                updateNotificationDropdown([]);
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
        });
}

// Update the notification badge in the navbar
function updateNotificationBadge(notifications) {
    const badge = document.getElementById('notificationCount');
    if (!badge) return;
    
    const unreadCount = notifications.filter(n => !n.is_read).length;
    
    if (unreadCount > 0) {
        badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
        badge.style.display = 'block';
    } else {
        badge.style.display = 'none';
    }
}

// Update the notification dropdown content
function updateNotificationDropdown(notifications) {
    const notificationList = document.getElementById('notificationList');
    if (!notificationList) return;
    
    if (notifications.length === 0) {
        notificationList.innerHTML = `
            <li class="dropdown-item text-center text-muted py-3">
                No notifications found
            </li>
        `;
        return;
    }
    
    let html = '';
    notifications.slice(0, 5).forEach(notification => {
        const timeAgo = getTimeAgo(notification.created_at);
        const icon = getNotificationIcon(notification.type);
        const color = getNotificationColor(notification.type);
        const isUnread = !notification.is_read ? 'bg-light' : '';
        
        html += `
            <li class="notification-item ${isUnread}">
                <a class="dropdown-item py-2" href="${getNotificationLink(notification)}">
                    <div class="d-flex">
                        <div class="notification-icon me-2">
                            <i class="fas ${icon} text-${color}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="notification-title">${notification.title}</div>
                            <div class="notification-message text-muted small">${notification.message}</div>
                            <div class="notification-time small text-muted">${timeAgo}</div>
                        </div>
                    </div>
                </a>
            </li>
        `;
    });
    
    // Add view all link if there are more than 5 notifications
    if (notifications.length > 5) {
        html += `
            <li class="dropdown-divider"></li>
            <li class="text-center py-1">
                <a href="notifications.php" class="text-primary small">View All Notifications</a>
            </li>
        `;
    }
    
    notificationList.innerHTML = html;
}

// Mark all notifications as read
function markAllAsRead() {
    fetch('apis/dashboard-stats.php?action=mark_notifications_read', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI to show all notifications as read
            const notifications = document.querySelectorAll('.notification-item');
            notifications.forEach(notification => {
                notification.classList.remove('bg-light');
            });
            
            // Update badge
            const badge = document.getElementById('notificationCount');
            if (badge) {
                badge.style.display = 'none';
            }
            
            // Show success message if on notifications page
            if (typeof showAlert === 'function') {
                showAlert('success', 'Success', 'All notifications marked as read');
            }
        }
    })
    .catch(error => {
        console.error('Error marking notifications as read:', error);
        if (typeof showAlert === 'function') {
            showAlert('danger', 'Error', 'Failed to mark notifications as read');
        }
    });
}

// Helper function to get notification icon based on type
function getNotificationIcon(type) {
    const icons = {
        'low_stock': 'fa-exclamation-triangle',
        'new_order': 'fa-shopping-cart',
        'system': 'fa-info-circle',
        'warning': 'fa-exclamation-triangle',
        'success': 'fa-check-circle',
        'danger': 'fa-exclamation-circle'
    };
    return icons[type] || 'fa-bell';
}

// Helper function to get notification color based on type
function getNotificationColor(type) {
    const colors = {
        'low_stock': 'warning',
        'new_order': 'success',
        'system': 'info',
        'warning': 'warning',
        'danger': 'danger',
        'success': 'success'
    };
    return colors[type] || 'primary';
}

// Helper function to get time ago string
function getTimeAgo(timestamp) {
    const now = new Date();
    const time = new Date(timestamp);
    const diffInSeconds = Math.floor((now - time) / 1000);
    
    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) {
        const mins = Math.floor(diffInSeconds / 60);
        return `${mins} minute${mins === 1 ? '' : 's'} ago`;
    }
    if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} hour${hours === 1 ? '' : 's'} ago`;
    }
    const days = Math.floor(diffInSeconds / 86400);
    return `${days} day${days === 1 ? '' : 's'} ago`;
}

// Helper function to get notification link based on type
function getNotificationLink(notification) {
    switch (notification.type) {
        case 'new_order':
            return 'orders.php';
        case 'low_stock':
            return 'products.php?filter=low_stock';
        default:
            return '#';
    }
}

// Initialize notification system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Load notifications immediately
    loadNotifications();
    
    // Then refresh every 30 seconds
    setInterval(loadNotifications, 30000);
});
