<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo "Access denied";
    exit();
}

// Database connection
try {
    $pdo = new PDO("mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo "Database connection failed";
    exit();
}

$conversation_id = intval($_GET['id'] ?? 0);

if (!$conversation_id) {
    http_response_code(400);
    echo "Invalid conversation ID";
    exit();
}

try {
    // Get conversation details
    $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE id = ?");
    $stmt->execute([$conversation_id]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        http_response_code(404);
        echo "Conversation not found";
        exit();
    }
    
    // Get message statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_messages,
            COUNT(CASE WHEN sender_type = 'user' THEN 1 END) as user_messages,
            COUNT(CASE WHEN sender_type = 'admin' THEN 1 END) as admin_messages,
            COUNT(CASE WHEN is_read = 0 AND sender_type = 'user' THEN 1 END) as unread_messages,
            MIN(created_at) as first_message_time,
            MAX(created_at) as last_message_time
        FROM chat_messages 
        WHERE conversation_id = ?
    ");
    $stmt->execute([$conversation_id]);
    $message_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent messages (last 10)
    $stmt = $pdo->prepare("
        SELECT * FROM chat_messages 
        WHERE conversation_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$conversation_id]);
    $recent_messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch(PDOException $e) {
    http_response_code(500);
    echo "Error fetching conversation details";
    exit();
}

// Format status badge
function getStatusBadge($status) {
    $classes = [
        'active' => 'bg-success',
        'pending' => 'bg-warning',
        'closed' => 'bg-secondary'
    ];
    return '<span class="badge ' . ($classes[$status] ?? 'bg-secondary') . '">' . ucfirst($status) . '</span>';
}

// Calculate conversation duration
function getConversationDuration($start_time, $end_time = null) {
    $start = new DateTime($start_time);
    $end = $end_time ? new DateTime($end_time) : new DateTime();
    $interval = $start->diff($end);
    
    if ($interval->days > 0) {
        return $interval->days . ' days, ' . $interval->h . ' hours';
    } elseif ($interval->h > 0) {
        return $interval->h . ' hours, ' . $interval->i . ' minutes';
    } else {
        return $interval->i . ' minutes';
    }
}
?>

<div class="row">
    <div class="col-md-6">
        <h6><i class="fas fa-user me-2"></i>User Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Name:</strong></td>
                <td><?= htmlspecialchars($conversation['user_name']) ?></td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td>
                    <a href="mailto:<?= htmlspecialchars($conversation['user_email']) ?>">
                        <?= htmlspecialchars($conversation['user_email']) ?>
                    </a>
                </td>
            </tr>
            <tr>
                <td><strong>Session ID:</strong></td>
                <td><code><?= htmlspecialchars($conversation['session_id']) ?></code></td>
            </tr>
            <tr>
                <td><strong>User ID:</strong></td>
                <td><?= $conversation['user_id'] ? $conversation['user_id'] : 'Guest' ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6><i class="fas fa-info-circle me-2"></i>Conversation Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Status:</strong></td>
                <td><?= getStatusBadge($conversation['status']) ?></td>
            </tr>
            <tr>
                <td><strong>Started:</strong></td>
                <td><?= date('M j, Y g:i A', strtotime($conversation['created_at'])) ?></td>
            </tr>
            <tr>
                <td><strong>Last Updated:</strong></td>
                <td><?= date('M j, Y g:i A', strtotime($conversation['updated_at'])) ?></td>
            </tr>
            <tr>
                <td><strong>Duration:</strong></td>
                <td><?= getConversationDuration($conversation['created_at'], $conversation['updated_at']) ?></td>
            </tr>
            <tr>
                <td><strong>Assigned Admin:</strong></td>
                <td><?= $conversation['assigned_admin'] ? 'Admin #' . $conversation['assigned_admin'] : 'Unassigned' ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <h6><i class="fas fa-chart-bar me-2"></i>Message Statistics</h6>
        <div class="row">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h4><?= $message_stats['total_messages'] ?></h4>
                        <small>Total Messages</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h4><?= $message_stats['user_messages'] ?></h4>
                        <small>User Messages</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h4><?= $message_stats['admin_messages'] ?></h4>
                        <small>Admin Messages</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4><?= $message_stats['unread_messages'] ?></h4>
                        <small>Unread Messages</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($recent_messages)): ?>
<div class="row mt-3">
    <div class="col-12">
        <h6><i class="fas fa-comments me-2"></i>Recent Messages (Last 10)</h6>
        <div class="card">
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <?php foreach ($recent_messages as $message): ?>
                <div class="message-item mb-3 p-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <strong class="text-<?= $message['sender_type'] === 'user' ? 'primary' : 'success' ?>">
                                <?= htmlspecialchars($message['sender_name']) ?>
                                (<?= ucfirst($message['sender_type']) ?>)
                            </strong>
                            <?php if ($message['sender_type'] === 'user' && !$message['is_read']): ?>
                                <span class="badge bg-danger ms-2">Unread</span>
                            <?php endif; ?>
                            <div class="mt-1">
                                <?= nl2br(htmlspecialchars($message['message'])) ?>
                            </div>
                        </div>
                        <small class="text-muted ms-3">
                            <?= date('M j, g:i A', strtotime($message['created_at'])) ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mt-3">
    <div class="col-12">
        <h6><i class="fas fa-cogs me-2"></i>Quick Actions</h6>
        <div class="btn-group" role="group">
            <?php if ($conversation['status'] === 'active'): ?>
                <button type="button" class="btn btn-warning" onclick="updateStatus('close')">
                    <i class="fas fa-times me-1"></i>Close Conversation
                </button>
            <?php elseif ($conversation['status'] === 'closed'): ?>
                <button type="button" class="btn btn-success" onclick="updateStatus('reopen')">
                    <i class="fas fa-redo me-1"></i>Reopen Conversation
                </button>
            <?php endif; ?>
            
            <button type="button" class="btn btn-info" onclick="markAllRead()">
                <i class="fas fa-check-double me-1"></i>Mark All Read
            </button>
            
            <button type="button" class="btn btn-primary" onclick="exportConversation()">
                <i class="fas fa-download me-1"></i>Export Chat
            </button>
        </div>
    </div>
</div>

<script>
// Store conversation ID for actions
const conversationId = <?= $conversation_id ?>;

function updateStatus(action) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'chat-conversations.php';
    form.innerHTML = `
        <input type="hidden" name="conversation_id" value="${conversationId}">
        <input type="hidden" name="action" value="${action}">
    `;
    document.body.appendChild(form);
    form.submit();
}

async function markAllRead() {
    try {
        const response = await fetch('../api/chat.php?action=mark_read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                conversation_id: conversationId,
                sender_type: 'user'
            })
        });
        
        const data = await response.json();
        if (data.success) {
            alert('All messages marked as read');
            location.reload();
        }
    } catch (error) {
        console.error('Error marking messages as read:', error);
        alert('Error marking messages as read');
    }
}

function exportConversation() {
    window.open(`export-conversation.php?id=${conversationId}`, '_blank');
}
</script>

<style>
.message-item {
    transition: background-color 0.2s;
}

.message-item:hover {
    background-color: #f8f9fa;
}

.card {
    border: 1px solid #dee2e6;
}

.card-body {
    padding: 1rem;
}

.btn-group .btn {
    margin-right: 0.25rem;
}
</style>
