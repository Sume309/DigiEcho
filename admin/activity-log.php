<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/ActivityLogger.php';

use App\auth\Admin;

if (!Admin::Check()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

$activityLogger = new ActivityLogger();
$page = "Activity Log";
?>

<?php require __DIR__ . '/components/header.php'; ?>

<style>
.activity-item {
    border-left: 4px solid #e3e6f0;
    padding: 1.25rem;
    margin-bottom: 1rem;
    background: #fff;
    border-radius: 0 12px 12px 0;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
    border: 1px solid #f8f9fc;
}
.activity-item:hover {
    border-left-color: #4e73df;
    box-shadow: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.2);
    transform: translateY(-2px);
}
.activity-auth { border-left-color: #1cc88a; }
.activity-user { border-left-color: #36b9cc; }
.activity-product { border-left-color: #f6c23e; }
.activity-order { border-left-color: #e74a3b; }
.activity-category { border-left-color: #6f42c1; }
.activity-brand { border-left-color: #6c757d; }
.activity-system { border-left-color: #343a40; }
.activity-payment { border-left-color: #28a745; }
.activity-inventory { border-left-color: #fd7e14; }
.activity-settings { border-left-color: #17a2b8; }

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.form-control:focus, .form-select:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.btn-outline-primary:hover {
    background-color: #4e73df;
    border-color: #4e73df;
}

#loadingState {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 10px;
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}

.badge {
    font-size: 0.7em;
    padding: 0.35em 0.65em;
}

.activity-item .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
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
                    <h1 class="mt-4">Activity Log</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Activity Log</li>
                    </ol>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Today's Activities</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="todayCount">-</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">This Week</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="weekCount">-</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Activities</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalCount">-</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Active Users</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeUsers">-</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-history me-2"></i>Activity Log
                            </h6>
                            <div class="dropdown no-arrow">
                                <button class="btn btn-sm btn-outline-primary" onclick="refreshActivities()">
                                    <i class="fas fa-sync-alt me-1"></i>Refresh
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Advanced Filters -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Activity Type</label>
                                    <select class="form-select" id="typeFilter">
                                        <option value="">All Types</option>
                                        <option value="auth">Authentication</option>
                                        <option value="user">User Management</option>
                                        <option value="product">Product Management</option>
                                        <option value="order">Order Management</option>
                                        <option value="category">Category Management</option>
                                        <option value="brand">Brand Management</option>
                                        <option value="system">System</option>
                                        <option value="payment">Payment</option>
                                        <option value="inventory">Inventory</option>
                                        <option value="settings">Settings</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">User Type</label>
                                    <select class="form-select" id="userTypeFilter">
                                        <option value="">All Users</option>
                                        <option value="admin">Admin</option>
                                        <option value="user">User</option>
                                        <option value="guest">Guest</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="dateFromFilter">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="dateToFilter" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Search</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="searchFilter" placeholder="Search activities...">
                                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-12">
                                    <button class="btn btn-primary me-2" onclick="applyFilters()">
                                        <i class="fas fa-filter me-1"></i>Apply Filters
                                    </button>
                                    <button class="btn btn-outline-secondary me-2" onclick="clearAllFilters()">
                                        <i class="fas fa-times me-1"></i>Clear All
                                    </button>
                                    <button class="btn btn-outline-info" onclick="exportActivities()">
                                        <i class="fas fa-download me-1"></i>Export
                                    </button>
                                </div>
                            </div>

                            <!-- Loading State -->
                            <div id="loadingState" class="text-center py-4" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading activities...</p>
                            </div>

                            <!-- Activities List -->
                            <div id="activityList">
                                <!-- Activities will be loaded here -->
                            </div>

                            <!-- Load More Button -->
                            <div class="text-center mt-4" id="loadMoreContainer" style="display: none;">
                                <button class="btn btn-outline-primary" id="loadMoreBtn" onclick="loadMoreActivities()">
                                    <i class="fas fa-plus me-1"></i>Load More Activities
                                </button>
                            </div>

                            <!-- No Results -->
                            <div id="noResults" class="text-center py-5" style="display: none;">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No activities found</h5>
                                <p class="text-muted">Try adjusting your filters or search terms.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <script>
    let currentPage = 1;
    let isLoading = false;
    let hasMoreData = true;
    let currentFilters = {};

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadActivities(1, true);
        loadStatistics();
        
        // Add search input event listener
        document.getElementById('searchFilter').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    });

    function loadActivities(page = 1, reset = false) {
        if (isLoading) return;
        
        isLoading = true;
        showLoading();
        
        if (reset) {
            currentPage = 1;
            document.getElementById('activityList').innerHTML = '';
        }
        
        const params = new URLSearchParams({
            page: page,
            limit: 10,
            ...currentFilters
        });
        
        fetch(`apis/activity-logs.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    if (data.data.length === 0 && page === 1) {
                        showNoResults();
                        return;
                    }
                    
                    hideNoResults();
                    renderActivities(data.data, reset);
                    updatePagination(data.pagination);
                    currentPage = data.pagination.current_page;
                    hasMoreData = data.pagination.has_next;
                } else {
                    showError('Failed to load activities: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showError('Network error occurred while loading activities');
            })
            .finally(() => {
                isLoading = false;
            });
    }

    function renderActivities(activities, reset = false) {
        const container = document.getElementById('activityList');
        
        if (reset) {
            container.innerHTML = '';
        }
        
        activities.forEach(activity => {
            const activityHtml = createActivityHTML(activity);
            container.insertAdjacentHTML('beforeend', activityHtml);
        });
    }

    function createActivityHTML(activity) {
        const metadata = activity.metadata ? 
            `<div class="small text-muted mt-1">
                <i class="fas fa-info-circle me-1"></i>
                ${formatMetadata(activity.metadata)}
            </div>` : '';
            
        return `
            <div class="activity-item activity-${activity.type} mb-3" data-type="${activity.type}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-${activity.icon} me-2 text-${activity.color}"></i>
                            <h6 class="mb-0">${escapeHtml(activity.action)}</h6>
                            <span class="badge ${activity.badge_class} ms-2">${activity.type.toUpperCase()}</span>
                        </div>
                        <p class="mb-2 text-muted">${escapeHtml(activity.description)}</p>
                        <div class="small text-muted">
                            <i class="fas fa-clock me-1"></i>
                            ${activity.formatted_date}
                            <span class="ms-2 text-primary">(${activity.time_ago})</span>
                            <span class="ms-3">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                ${escapeHtml(activity.ip_address)}
                            </span>
                            <span class="ms-3">
                                <i class="fas fa-user me-1"></i>
                                ${activity.user_type.toUpperCase()}
                            </span>
                        </div>
                        ${metadata}
                    </div>
                    <div class="text-end">
                        <button class="btn btn-sm btn-outline-info" onclick="showActivityDetails(${activity.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    function formatMetadata(metadata) {
        if (!metadata || typeof metadata !== 'object') return '';
        
        const items = Object.entries(metadata).map(([key, value]) => {
            return `${key}: ${value}`;
        }).join(', ');
        
        return items.length > 50 ? items.substring(0, 50) + '...' : items;
    }

    function updatePagination(pagination) {
        const loadMoreContainer = document.getElementById('loadMoreContainer');
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        
        if (pagination.has_next) {
            loadMoreContainer.style.display = 'block';
            loadMoreBtn.disabled = false;
            loadMoreBtn.innerHTML = '<i class="fas fa-plus me-1"></i>Load More Activities';
        } else {
            loadMoreContainer.style.display = 'none';
        }
    }

    function loadMoreActivities() {
        if (hasMoreData && !isLoading) {
            loadActivities(currentPage + 1, false);
        }
    }

    function applyFilters() {
        currentFilters = {
            type: document.getElementById('typeFilter').value,
            user_type: document.getElementById('userTypeFilter').value,
            date_from: document.getElementById('dateFromFilter').value,
            date_to: document.getElementById('dateToFilter').value,
            search: document.getElementById('searchFilter').value.trim()
        };
        
        // Remove empty filters
        Object.keys(currentFilters).forEach(key => {
            if (!currentFilters[key]) {
                delete currentFilters[key];
            }
        });
        
        loadActivities(1, true);
    }

    function clearAllFilters() {
        document.getElementById('typeFilter').value = '';
        document.getElementById('userTypeFilter').value = '';
        document.getElementById('dateFromFilter').value = '';
        document.getElementById('dateToFilter').value = '<?= date('Y-m-d') ?>';
        document.getElementById('searchFilter').value = '';
        
        currentFilters = {};
        loadActivities(1, true);
    }

    function clearSearch() {
        document.getElementById('searchFilter').value = '';
    }

    function refreshActivities() {
        loadActivities(1, true);
        loadStatistics();
        showSuccess('Activities refreshed successfully');
    }

    function exportActivities() {
        const params = new URLSearchParams({
            export: 'csv',
            ...currentFilters
        });
        
        window.open(`apis/activity-logs.php?${params.toString()}`, '_blank');
    }

    function loadStatistics() {
        // This would typically load from a separate API endpoint
        // For now, we'll use placeholder values
        document.getElementById('todayCount').textContent = '12';
        document.getElementById('weekCount').textContent = '89';
        document.getElementById('totalCount').textContent = '1,247';
        document.getElementById('activeUsers').textContent = '5';
    }

    function showActivityDetails(activityId) {
        // Implement activity details modal
        alert(`Show details for activity ID: ${activityId}`);
    }

    // Utility functions
    function showLoading() {
        document.getElementById('loadingState').style.display = 'block';
        document.getElementById('noResults').style.display = 'none';
    }

    function hideLoading() {
        document.getElementById('loadingState').style.display = 'none';
    }

    function showNoResults() {
        document.getElementById('noResults').style.display = 'block';
        document.getElementById('loadMoreContainer').style.display = 'none';
    }

    function hideNoResults() {
        document.getElementById('noResults').style.display = 'none';
    }

    function showError(message) {
        // You can implement a toast notification system here
        alert('Error: ' + message);
    }

    function showSuccess(message) {
        // You can implement a toast notification system here
        console.log('Success: ' + message);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>
</body>
</html>
