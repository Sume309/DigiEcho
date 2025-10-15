<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$page = "Messages Management";

// Database connection
try {
    $pdo = new PDO("mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle status updates
if (isset($_POST['update_status'])) {
    try {
        $message_id = intval($_POST['message_id']);
        $new_status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $message_id]);
        
        // Mark notification as read if status is changed to 'read'
        if ($new_status === 'read') {
            try {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE JSON_EXTRACT(metadata, '$.message_id') = ?");
                $stmt->execute([$message_id]);
            } catch(PDOException $e) {
                // Ignore if notifications table doesn't exist
                error_log("Notification update failed: " . $e->getMessage());
            }
        }
        
        $success_message = "Message status updated successfully!";
    } catch(PDOException $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Handle delete request
if (isset($_POST['delete_id'])) {
    try {
        $message_id = intval($_POST['delete_id']);
        
        // Delete related notifications (if notifications table exists)
        try {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE JSON_EXTRACT(metadata, '$.message_id') = ?");
            $stmt->execute([$message_id]);
        } catch(PDOException $e) {
            // Ignore if notifications table doesn't exist
            error_log("Notification deletion failed: " . $e->getMessage());
        }
        
        // Delete message
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$message_id]);
        
        $success_message = "Message deleted successfully!";
    } catch(PDOException $e) {
        $error_message = "Error deleting message: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($priority_filter) {
    $where_conditions[] = "priority = ?";
    $params[] = $priority_filter;
}

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Fetch messages
try {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages $where_clause ORDER BY created_at DESC");
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts for dashboard
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
        SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
        SUM(CASE WHEN priority = 'high' OR priority = 'urgent' THEN 1 ELSE 0 END) as high_priority_count
        FROM contact_messages");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Error fetching messages: " . $e->getMessage();
    $messages = [];
    $stats = ['total' => 0, 'new_count' => 0, 'read_count' => 0, 'replied_count' => 0, 'high_priority_count' => 0];
}
?>

<?php require __DIR__ . '/components/header.php'; ?>

<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/v/bs5/dt-2.3.2/datatables.min.css" rel="stylesheet">

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mt-4">Messages Management</h1>
                        <div class="mt-4">
                            <span class="badge bg-primary me-2">Total: <?= $stats['total'] ?></span>
                            <span class="badge bg-success me-2">New: <?= $stats['new_count'] ?></span>
                            <span class="badge bg-warning">High Priority: <?= $stats['high_priority_count'] ?></span>
                        </div>
                    </div>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Messages Management</li>
                    </ol>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" name="status" id="status">
                                        <option value="">All Statuses</option>
                                        <option value="new" <?= $status_filter === 'new' ? 'selected' : '' ?>>New</option>
                                        <option value="read" <?= $status_filter === 'read' ? 'selected' : '' ?>>Read</option>
                                        <option value="replied" <?= $status_filter === 'replied' ? 'selected' : '' ?>>Replied</option>
                                        <option value="archived" <?= $status_filter === 'archived' ? 'selected' : '' ?>>Archived</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="priority" class="form-label">Priority</label>
                                    <select class="form-select" name="priority" id="priority">
                                        <option value="">All Priorities</option>
                                        <option value="low" <?= $priority_filter === 'low' ? 'selected' : '' ?>>Low</option>
                                        <option value="normal" <?= $priority_filter === 'normal' ? 'selected' : '' ?>>Normal</option>
                                        <option value="high" <?= $priority_filter === 'high' ? 'selected' : '' ?>>High</option>
                                        <option value="urgent" <?= $priority_filter === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" class="form-control" name="search" id="search" 
                                           value="<?= htmlspecialchars($search) ?>" 
                                           placeholder="Search by name, email, subject...">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="messages-all.php" class="btn btn-outline-secondary">Clear</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Messages Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-envelope me-1"></i>
                            Contact Messages
                            <?php if ($status_filter): ?>
                                - <?= ucfirst($status_filter) ?>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="messagesTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Subject</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($messages as $message): ?>
                                        <tr class="<?= $message['status'] === 'new' ? 'table-warning' : '' ?>">
                                            <td><?= date('M j, Y g:i A', strtotime($message['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($message['name']) ?></td>
                                            <td>
                                                <a href="mailto:<?= htmlspecialchars($message['email']) ?>">
                                                    <?= htmlspecialchars($message['email']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($message['subject']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $message['priority'] === 'urgent' ? 'danger' : 
                                                    ($message['priority'] === 'high' ? 'warning' : 
                                                    ($message['priority'] === 'low' ? 'secondary' : 'primary')) 
                                                ?>">
                                                    <?= ucfirst($message['priority']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                    <select name="status" class="form-select form-select-sm" 
                                                            onchange="this.form.submit()" style="width: auto;">
                                                        <option value="new" <?= $message['status'] === 'new' ? 'selected' : '' ?>>New</option>
                                                        <option value="read" <?= $message['status'] === 'read' ? 'selected' : '' ?>>Read</option>
                                                        <option value="replied" <?= $message['status'] === 'replied' ? 'selected' : '' ?>>Replied</option>
                                                        <option value="archived" <?= $message['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewMessage(<?= $message['id'] ?>)"
                                                            title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="mailto:<?= htmlspecialchars($message['email']) ?>?subject=Re: <?= urlencode($message['subject']) ?>" 
                                                       class="btn btn-sm btn-outline-success" 
                                                       title="Reply">
                                                        <i class="fas fa-reply"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            onclick="confirmDelete(<?= $message['id'] ?>, '<?= htmlspecialchars($message['name']) ?>')"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <!-- View Message Modal -->
    <div class="modal fade" id="viewMessageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Message Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="messageContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="replyButton">Reply</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the message from <strong id="senderName"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="delete_id" id="deleteId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/v/bs5/dt-2.3.2/datatables.min.js"></script>

    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#messagesTable').DataTable({
                "pageLength": 25,
                "order": [[ 0, "desc" ]], // Sort by date descending
                "columnDefs": [
                    { "orderable": false, "targets": [6] } // Disable sorting for actions column
                ]
            });
        });

        // View message details
        function viewMessage(id) {
            fetch('message-details.php?id=' + id)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('messageContent').innerHTML = data;
                    new bootstrap.Modal(document.getElementById('viewMessageModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading message details');
                });
        }

        // Delete confirmation
        function confirmDelete(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('senderName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Reply button functionality
        document.getElementById('replyButton').addEventListener('click', function() {
            const messageData = JSON.parse(this.dataset.message || '{}');
            if (messageData.email) {
                window.location.href = `mailto:${messageData.email}?subject=Re: ${encodeURIComponent(messageData.subject)}`;
            }
        });
    </script>
</body>
</html>
