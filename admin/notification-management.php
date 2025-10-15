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
$page = "notification-management";
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
                                <h1>Notification Management</h1>
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Notification Management</li>
                                </ol>
                            </div>
                            <div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNotificationModal">
                                    <i class="fas fa-plus me-1"></i> Create Notification
                                </button>
                            </div>
                        </div>

                        <!-- Notification Filters -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-filter me-1"></i>
                                Filter Notifications
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="filterType" class="form-label">Type</label>
                                        <select class="form-select" id="filterType">
                                            <option value="">All Types</option>
                                            <option value="new_order">New Order</option>
                                            <option value="low_stock">Low Stock</option>
                                            <option value="user_activity">User Activity</option>
                                            <option value="order_update">Order Update</option>
                                            <option value="payment">Payment</option>
                                            <option value="user_registration">User Registration</option>
                                            <option value="contact_form">Contact Form</option>
                                            <option value="product_review">Product Review</option>
                                            <option value="cart_activity">Cart Activity</option>
                                            <option value="wishlist_activity">Wishlist Activity</option>
                                            <option value="order_cancellation">Order Cancellation</option>
                                            <option value="product_report">Product Report</option>
                                            <option value="system">System</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="filterStatus" class="form-label">Status</label>
                                        <select class="form-select" id="filterStatus">
                                            <option value="">All Statuses</option>
                                            <option value="unread">Unread</option>
                                            <option value="read">Read</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="filterDate" class="form-label">Date Range</label>
                                        <select class="form-select" id="filterDate">
                                            <option value="">All Time</option>
                                            <option value="today">Today</option>
                                            <option value="week">This Week</option>
                                            <option value="month">This Month</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button class="btn btn-primary me-2" onclick="filterNotifications()">
                                                <i class="fas fa-search me-1"></i> Filter
                                            </button>
                                            <button class="btn btn-outline-secondary" onclick="resetFilters()">
                                                <i class="fas fa-undo me-1"></i> Reset
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-bell me-1"></i>
                                All Notifications
                                <div class="float-end">
                                    <button class="btn btn-sm btn-outline-secondary me-2" onclick="refreshNotifications()">
                                        <i class="fas fa-sync-alt me-1"></i> Refresh
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAllNotifications()">
                                        <i class="fas fa-trash me-1"></i> Delete All
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="notificationsTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Title</th>
                                                <th>Message</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Created At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="notificationsTableBody">
                                            <tr>
                                                <td colspan="7" class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                    <p class="mt-2">Loading notifications...</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary" onclick="markAllAsRead()">
                                            <i class="fas fa-check-double me-1"></i> Mark All as Read
                                        </button>
                                    </div>
                                    <div>
                                        <nav>
                                            <ul class="pagination mb-0" id="pagination">
                                                <!-- Pagination will be loaded here -->
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <?php require __DIR__.'/components/footer.php'; ?>
            </div>
        </div>

        <!-- Create Notification Modal -->
        <div class="modal fade" id="createNotificationModal" tabindex="-1" aria-labelledby="createNotificationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createNotificationModalLabel">Create New Notification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="createNotificationForm">
                            <div class="mb-3">
                                <label for="notificationTitle" class="form-label">Title</label>
                                <input type="text" class="form-control" id="notificationTitle" required>
                            </div>
                            <div class="mb-3">
                                <label for="notificationMessage" class="form-label">Message</label>
                                <textarea class="form-control" id="notificationMessage" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="notificationType" class="form-label">Type</label>
                                <select class="form-select" id="notificationType" required>
                                    <option value="system">System</option>
                                    <option value="new_order">New Order</option>
                                    <option value="low_stock">Low Stock</option>
                                    <option value="warning">Warning</option>
                                    <option value="success">Success</option>
                                    <option value="user_activity">User Activity</option>
                                    <option value="order_update">Order Update</option>
                                    <option value="payment">Payment</option>
                                    <option value="user_registration">User Registration</option>
                                    <option value="contact_form">Contact Form</option>
                                    <option value="product_review">Product Review</option>
                                    <option value="cart_activity">Cart Activity</option>
                                    <option value="wishlist_activity">Wishlist Activity</option>
                                    <option value="order_cancellation">Order Cancellation</option>
                                    <option value="product_report">Product Report</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="notificationMetadata" class="form-label">Metadata (Optional)</label>
                                <input type="text" class="form-control" id="notificationMetadata" placeholder="Additional information in JSON format">
                                <div class="form-text">Enter additional data as JSON (e.g., {"user_id": 123, "order_id": 456})</div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="createNotification()">Create</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Notification Modal -->
        <div class="modal fade" id="editNotificationModal" tabindex="-1" aria-labelledby="editNotificationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editNotificationModalLabel">Edit Notification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editNotificationForm">
                            <input type="hidden" id="editNotificationId">
                            <div class="mb-3">
                                <label for="editNotificationTitle" class="form-label">Title</label>
                                <input type="text" class="form-control" id="editNotificationTitle" required>
                            </div>
                            <div class="mb-3">
                                <label for="editNotificationMessage" class="form-label">Message</label>
                                <textarea class="form-control" id="editNotificationMessage" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="editNotificationType" class="form-label">Type</label>
                                <select class="form-select" id="editNotificationType" required>
                                    <option value="system">System</option>
                                    <option value="new_order">New Order</option>
                                    <option value="low_stock">Low Stock</option>
                                    <option value="warning">Warning</option>
                                    <option value="success">Success</option>
                                    <option value="user_activity">User Activity</option>
                                    <option value="order_update">Order Update</option>
                                    <option value="payment">Payment</option>
                                    <option value="user_registration">User Registration</option>
                                    <option value="contact_form">Contact Form</option>
                                    <option value="product_review">Product Review</option>
                                    <option value="cart_activity">Cart Activity</option>
                                    <option value="wishlist_activity">Wishlist Activity</option>
                                    <option value="order_cancellation">Order Cancellation</option>
                                    <option value="product_report">Product Report</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="editNotificationMetadata" class="form-label">Metadata (Optional)</label>
                                <input type="text" class="form-control" id="editNotificationMetadata" placeholder="Additional information in JSON format">
                                <div class="form-text">Enter additional data as JSON (e.g., {"user_id": 123, "order_id": 456})</div>
                            </div>
                            <div class="mb-3">
                                <label for="editNotificationStatus" class="form-label">Status</label>
                                <select class="form-select" id="editNotificationStatus" required>
                                    <option value="0">Unread</option>
                                    <option value="1">Read</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="updateNotification()">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="<?= settings()['adminpage'] ?>assets/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="<?= settings()['adminpage'] ?>assets/js/scripts.js"></script>
        
        <script>
        let currentPage = 1;
        let currentFilters = {};

        // Load notifications when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications(currentPage);
        });

        function loadNotifications(page = 1) {
            const params = new URLSearchParams({
                action: 'notifications',
                page: page,
                ...currentFilters
            });
            
            fetch('apis/dashboard-stats.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('notificationsTableBody');
                    
                    if (!data.success || !data.data || data.data.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="7" class="text-center">
                                    <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No notifications found</p>
                                </td>
                            </tr>
                        `;
                        document.getElementById('pagination').innerHTML = '';
                        return;
                    }

                    let html = '';
                    data.data.forEach(notification => {
                        const createdAt = new Date(notification.created_at).toLocaleString();
                        const status = notification.is_read == 1 ? 'Read' : 'Unread';
                        const statusClass = notification.is_read == 1 ? 'badge bg-success' : 'badge bg-warning';
                        const typeClass = getNotificationTypeClass(notification.type);
                        
                        html += `
                            <tr>
                                <td>${notification.id}</td>
                                <td>${notification.title}</td>
                                <td>${formatNotificationMessageTable(notification)}</td>
                                <td><span class="badge ${typeClass}">${notification.type}</span></td>
                                <td><span class="${statusClass}">${status}</span></td>
                                <td>${createdAt}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editNotification(${notification.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteNotification(${notification.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    tableBody.innerHTML = html;
                    
                    // Update pagination
                    if (data.pagination) {
                        renderPagination(data.pagination);
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    const tableBody = document.getElementById('notificationsTableBody');
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error loading notifications. Please try again later.
                            </td>
                        </tr>
                    `;
                });
        }

        function renderPagination(pagination) {
            const paginationElement = document.getElementById('pagination');
            let html = '';
            
            // Previous button
            if (pagination.current_page > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadNotifications(${pagination.current_page - 1})">Previous</a></li>`;
            }
            
            // Page numbers
            for (let i = 1; i <= pagination.total_pages; i++) {
                const activeClass = i === pagination.current_page ? 'active' : '';
                html += `<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="loadNotifications(${i})">${i}</a></li>`;
            }
            
            // Next button
            if (pagination.current_page < pagination.total_pages) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadNotifications(${pagination.current_page + 1})">Next</a></li>`;
            }
            
            paginationElement.innerHTML = html;
        }

        function refreshNotifications() {
            loadNotifications(currentPage);
            showAlert('info', 'Refreshed', 'Notifications refreshed');
        }

        function filterNotifications() {
            currentFilters = {
                type: document.getElementById('filterType').value,
                status: document.getElementById('filterStatus').value,
                date: document.getElementById('filterDate').value
            };
            currentPage = 1;
            loadNotifications(currentPage);
        }

        function resetFilters() {
            document.getElementById('filterType').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterDate').value = '';
            currentFilters = {};
            currentPage = 1;
            loadNotifications(currentPage);
        }

        function createNotification() {
            const title = document.getElementById('notificationTitle').value;
            const message = document.getElementById('notificationMessage').value;
            const type = document.getElementById('notificationType').value;
            const metadata = document.getElementById('notificationMetadata').value;

            // Validate metadata if provided
            if (metadata) {
                try {
                    JSON.parse(metadata);
                } catch (e) {
                    showAlert('danger', 'Error', 'Metadata must be valid JSON');
                    return;
                }
            }

            fetch('apis/create_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    title: title,
                    message: message,
                    type: type,
                    metadata: metadata || null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Success', 'Notification created successfully');
                    document.getElementById('createNotificationForm').reset();
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createNotificationModal'));
                    modal.hide();
                    loadNotifications(currentPage);
                } else {
                    showAlert('danger', 'Error', data.message || 'Failed to create notification');
                }
            })
            .catch(error => {
                console.error('Error creating notification:', error);
                showAlert('danger', 'Error', 'Failed to create notification');
            });
        }

        function editNotification(id) {
            fetch('apis/get_notification.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const notification = data.data;
                        document.getElementById('editNotificationId').value = notification.id;
                        document.getElementById('editNotificationTitle').value = notification.title;
                        document.getElementById('editNotificationMessage').value = notification.message;
                        document.getElementById('editNotificationType').value = notification.type;
                        document.getElementById('editNotificationMetadata').value = notification.metadata || '';
                        document.getElementById('editNotificationStatus').value = notification.is_read;
                        
                        const modal = new bootstrap.Modal(document.getElementById('editNotificationModal'));
                        modal.show();
                    } else {
                        showAlert('danger', 'Error', data.message || 'Failed to load notification');
                    }
                })
                .catch(error => {
                    console.error('Error loading notification:', error);
                    showAlert('danger', 'Error', 'Failed to load notification');
                });
        }

        function updateNotification() {
            const id = document.getElementById('editNotificationId').value;
            const title = document.getElementById('editNotificationTitle').value;
            const message = document.getElementById('editNotificationMessage').value;
            const type = document.getElementById('editNotificationType').value;
            const metadata = document.getElementById('editNotificationMetadata').value;
            const isRead = document.getElementById('editNotificationStatus').value;

            // Validate metadata if provided
            if (metadata) {
                try {
                    JSON.parse(metadata);
                } catch (e) {
                    showAlert('danger', 'Error', 'Metadata must be valid JSON');
                    return;
                }
            }

            fetch('apis/update_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    title: title,
                    message: message,
                    type: type,
                    metadata: metadata || null,
                    is_read: isRead
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Success', 'Notification updated successfully');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editNotificationModal'));
                    modal.hide();
                    loadNotifications(currentPage);
                } else {
                    showAlert('danger', 'Error', data.message || 'Failed to update notification');
                }
            })
            .catch(error => {
                console.error('Error updating notification:', error);
                showAlert('danger', 'Error', 'Failed to update notification');
            });
        }

        function deleteNotification(id) {
            if (!confirm('Are you sure you want to delete this notification?')) {
                return;
            }

            fetch('apis/delete_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({id: id})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Success', 'Notification deleted successfully');
                    loadNotifications(currentPage);
                } else {
                    showAlert('danger', 'Error', data.message || 'Failed to delete notification');
                }
            })
            .catch(error => {
                console.error('Error deleting notification:', error);
                showAlert('danger', 'Error', 'Failed to delete notification');
            });
        }

        function deleteAllNotifications() {
            if (!confirm('Are you sure you want to delete ALL notifications? This action cannot be undone.')) {
                return;
            }

            fetch('apis/delete_all_notifications.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Success', 'All notifications deleted successfully');
                    loadNotifications(currentPage);
                } else {
                    showAlert('danger', 'Error', data.message || 'Failed to delete all notifications');
                }
            })
            .catch(error => {
                console.error('Error deleting all notifications:', error);
                showAlert('danger', 'Error', 'Failed to delete all notifications');
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
                    loadNotifications(currentPage);
                    // Also update the notification badge in the header
                    const badge = document.getElementById('notificationCount');
                    if (badge) {
                        badge.style.display = 'none';
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

        function formatNotificationMessageTable(notification) {
            // Clean up messages for table display
            let message = notification.message;
            
            // Remove technical metadata from display
            if (notification.type === 'user_activity') {
                // Just show the clean message without JSON
                message = notification.message;
            }
            
            // Truncate very long messages for table
            if (message.length > 80) {
                message = message.substring(0, 77) + '...';
            }
            
            return message;
        }

        function getNotificationTypeClass(type) {
            const typeClasses = {
                'new_order': 'bg-success',
                'low_stock': 'bg-warning',
                'user_activity': 'bg-primary',
                'order_update': 'bg-info',
                'payment': 'bg-success',
                'user_registration': 'bg-primary',
                'contact_form': 'bg-info',
                'product_review': 'bg-warning',
                'cart_activity': 'bg-secondary',
                'wishlist_activity': 'bg-danger',
                'order_cancellation': 'bg-danger',
                'product_report': 'bg-warning',
                'system': 'bg-secondary',
                'warning': 'bg-warning',
                'success': 'bg-success'
            };
            return typeClasses[type] || 'bg-secondary';
        }

        function showAlert(type, title, message) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.notification-alert');
            existingAlerts.forEach(alert => alert.remove());
            
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show notification-alert position-fixed top-0 end-0 m-3" style="z-index: 9999;" role="alert">
                    <i class="fas ${getNotificationIcon(type)} me-2"></i>
                    <strong>${title}</strong> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', alertHtml);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.querySelector('.notification-alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }

        function getNotificationIcon(type) {
            const icons = {
                'success': 'fa-check-circle',
                'danger': 'fa-exclamation-triangle',
                'warning': 'fa-exclamation-triangle',
                'info': 'fa-info-circle'
            };
            return icons[type] || 'fa-bell';
        }
        </script>
    </body>
</html>