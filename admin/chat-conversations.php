<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$page = "Chat Conversations";

// Database connection
try {
    $pdo = new PDO("mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle conversation actions
if (isset($_POST['action'])) {
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'close':
                $stmt = $pdo->prepare("UPDATE chat_conversations SET status = 'closed', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$conversation_id]);
                $success_message = "Conversation closed successfully!";
                break;
                
            case 'reopen':
                $stmt = $pdo->prepare("UPDATE chat_conversations SET status = 'active', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$conversation_id]);
                $success_message = "Conversation reopened successfully!";
                break;
                
            case 'assign':
                $assigned_admin = $_POST['assigned_admin'] ?? null;
                $stmt = $pdo->prepare("UPDATE chat_conversations SET assigned_admin = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$assigned_admin, $conversation_id]);
                $success_message = "Conversation assigned successfully!";
                break;
                
            case 'delete':
                // Delete conversation and all related messages
                $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE conversation_id = ?");
                $stmt->execute([$conversation_id]);
                $stmt = $pdo->prepare("DELETE FROM chat_conversations WHERE id = ?");
                $stmt->execute([$conversation_id]);
                $success_message = "Conversation deleted successfully!";
                break;
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(c.user_name LIKE ? OR c.user_email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
}

if ($date_from) {
    $where_conditions[] = "DATE(c.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(c.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Fetch conversations with message counts
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(m.id) as message_count,
               COUNT(CASE WHEN m.is_read = 0 AND m.sender_type = 'user' THEN 1 END) as unread_count,
               MAX(m.created_at) as last_message_time,
               (SELECT m2.message FROM chat_messages m2 WHERE m2.conversation_id = c.id ORDER BY m2.created_at DESC LIMIT 1) as last_message
        FROM chat_conversations c 
        LEFT JOIN chat_messages m ON c.id = m.conversation_id 
        $where_clause
        GROUP BY c.id 
        ORDER BY c.last_message_at DESC, c.created_at DESC
    ");
    $stmt->execute($params);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count
        FROM chat_conversations");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Error fetching conversations: " . $e->getMessage();
    $conversations = [];
    $stats = ['total' => 0, 'active_count' => 0, 'pending_count' => 0, 'closed_count' => 0];
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
                        <h1 class="mt-4">Chat Conversations</h1>
                        <div class="mt-4">
                            <span class="badge bg-primary me-2">Total: <?= $stats['total'] ?></span>
                            <span class="badge bg-success me-2">Active: <?= $stats['active_count'] ?></span>
                            <span class="badge bg-warning me-2">Pending: <?= $stats['pending_count'] ?></span>
                            <span class="badge bg-secondary">Closed: <?= $stats['closed_count'] ?></span>
                        </div>
                    </div>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="live-chat.php">Live Chat</a></li>
                        <li class="breadcrumb-item active">All Conversations</li>
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
                                <div class="col-md-2">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" name="status" id="status">
                                        <option value="">All Statuses</option>
                                        <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Closed</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" class="form-control" name="search" id="search" 
                                           value="<?= htmlspecialchars($search) ?>" 
                                           placeholder="Search by name or email...">
                                </div>
                                <div class="col-md-2">
                                    <label for="date_from" class="form-label">From Date</label>
                                    <input type="date" class="form-control" name="date_from" id="date_from" 
                                           value="<?= htmlspecialchars($date_from) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="date_to" class="form-label">To Date</label>
                                    <input type="date" class="form-control" name="date_to" id="date_to" 
                                           value="<?= htmlspecialchars($date_to) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="chat-conversations.php" class="btn btn-outline-secondary">Clear</a>
                                        <a href="live-chat.php" class="btn btn-success">Live Chat</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Conversations Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-comments me-1"></i>
                            Chat Conversations
                            <?php if ($status_filter): ?>
                                - <?= ucfirst($status_filter) ?>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="conversationsTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Messages</th>
                                            <th>Last Message</th>
                                            <th>Started</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($conversations as $conv): ?>
                                        <tr class="<?= $conv['unread_count'] > 0 ? 'table-warning' : '' ?>">
                                            <td>
                                                <strong><?= htmlspecialchars($conv['user_name']) ?></strong>
                                                <?php if ($conv['unread_count'] > 0): ?>
                                                    <span class="badge bg-danger ms-2"><?= $conv['unread_count'] ?> unread</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="mailto:<?= htmlspecialchars($conv['user_email']) ?>">
                                                    <?= htmlspecialchars($conv['user_email']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $conv['status'] === 'active' ? 'success' : 
                                                    ($conv['status'] === 'pending' ? 'warning' : 'secondary') 
                                                ?>">
                                                    <?= ucfirst($conv['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $conv['message_count'] ?> messages</span>
                                            </td>
                                            <td>
                                                <?php if ($conv['last_message']): ?>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars(substr($conv['last_message'], 0, 50)) ?>
                                                        <?= strlen($conv['last_message']) > 50 ? '...' : '' ?>
                                                    </small><br>
                                                    <small class="text-muted">
                                                        <?= $conv['last_message_time'] ? date('M j, Y g:i A', strtotime($conv['last_message_time'])) : 'No messages' ?>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted">No messages yet</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= date('M j, Y g:i A', strtotime($conv['created_at'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="live-chat.php?conversation=<?= $conv['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Open Chat">
                                                        <i class="fas fa-comments"></i>
                                                    </a>
                                                    
                                                    <?php if ($conv['status'] === 'active'): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-warning" 
                                                                onclick="updateConversationStatus(<?= $conv['id'] ?>, 'close')"
                                                                title="Close Conversation">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php elseif ($conv['status'] === 'closed'): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-success" 
                                                                onclick="updateConversationStatus(<?= $conv['id'] ?>, 'reopen')"
                                                                title="Reopen Conversation">
                                                            <i class="fas fa-redo"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-info" 
                                                            onclick="showConversationDetails(<?= $conv['id'] ?>)"
                                                            title="View Details">
                                                        <i class="fas fa-info"></i>
                                                    </button>
                                                    
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            onclick="confirmDelete(<?= $conv['id'] ?>, '<?= htmlspecialchars($conv['user_name']) ?>')"
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

    <!-- Conversation Details Modal -->
    <div class="modal fade" id="conversationDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Conversation Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="conversationDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="openLiveChat()">Open Live Chat</button>
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
                    <p>Are you sure you want to delete the conversation with <strong id="userName"></strong>?</p>
                    <p class="text-danger">This action will permanently delete all messages and cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="conversation_id" id="deleteConversationId">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-danger">Delete Conversation</button>
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
        let currentConversationId = null;

        // Initialize DataTable
        $(document).ready(function() {
            $('#conversationsTable').DataTable({
                "pageLength": 25,
                "order": [[ 5, "desc" ]], // Sort by created date descending
                "columnDefs": [
                    { "orderable": false, "targets": [6] } // Disable sorting for actions column
                ]
            });
        });

        // Update conversation status
        function updateConversationStatus(conversationId, action) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="conversation_id" value="${conversationId}">
                <input type="hidden" name="action" value="${action}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Show conversation details
        async function showConversationDetails(conversationId) {
            currentConversationId = conversationId;
            
            try {
                const response = await fetch(`conversation-details.php?id=${conversationId}`);
                const data = await response.text();
                
                document.getElementById('conversationDetailsContent').innerHTML = data;
                new bootstrap.Modal(document.getElementById('conversationDetailsModal')).show();
            } catch (error) {
                console.error('Error loading conversation details:', error);
                alert('Error loading conversation details');
            }
        }

        // Open live chat
        function openLiveChat() {
            if (currentConversationId) {
                window.location.href = `live-chat.php?conversation=${currentConversationId}`;
            }
        }

        // Delete confirmation
        function confirmDelete(conversationId, userName) {
            document.getElementById('deleteConversationId').value = conversationId;
            document.getElementById('userName').textContent = userName;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
