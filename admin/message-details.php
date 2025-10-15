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

$message_id = intval($_GET['id'] ?? 0);

if (!$message_id) {
    http_response_code(400);
    echo "Invalid message ID";
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        http_response_code(404);
        echo "Message not found";
        exit();
    }
    
    // Mark as read if it's new
    if ($message['status'] === 'new') {
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$message_id]);
        
        // Mark notification as read (if notifications table exists)
        try {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE JSON_EXTRACT(metadata, '$.message_id') = ?");
            $stmt->execute([$message_id]);
        } catch(PDOException $e) {
            // Ignore if notifications table doesn't exist
            error_log("Notification update failed: " . $e->getMessage());
        }
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo "Error fetching message";
    exit();
}

// Format priority badge
function getPriorityBadge($priority) {
    $classes = [
        'low' => 'bg-secondary',
        'normal' => 'bg-primary',
        'high' => 'bg-warning',
        'urgent' => 'bg-danger'
    ];
    return '<span class="badge ' . ($classes[$priority] ?? 'bg-primary') . '">' . ucfirst($priority) . '</span>';
}

// Format status badge
function getStatusBadge($status) {
    $classes = [
        'new' => 'bg-success',
        'read' => 'bg-info',
        'replied' => 'bg-primary',
        'archived' => 'bg-secondary'
    ];
    return '<span class="badge ' . ($classes[$status] ?? 'bg-secondary') . '">' . ucfirst($status) . '</span>';
}
?>

<div class="row">
    <div class="col-md-6">
        <h6><i class="fas fa-user me-2"></i>Sender Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Name:</strong></td>
                <td><?= htmlspecialchars($message['name']) ?></td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td>
                    <a href="mailto:<?= htmlspecialchars($message['email']) ?>">
                        <?= htmlspecialchars($message['email']) ?>
                    </a>
                </td>
            </tr>
            <?php if ($message['phone']): ?>
            <tr>
                <td><strong>Phone:</strong></td>
                <td>
                    <a href="tel:<?= htmlspecialchars($message['phone']) ?>">
                        <?= htmlspecialchars($message['phone']) ?>
                    </a>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td><strong>IP Address:</strong></td>
                <td><?= htmlspecialchars($message['ip_address'] ?? 'N/A') ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6><i class="fas fa-info-circle me-2"></i>Message Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Subject:</strong></td>
                <td><?= htmlspecialchars($message['subject']) ?></td>
            </tr>
            <tr>
                <td><strong>Priority:</strong></td>
                <td><?= getPriorityBadge($message['priority']) ?></td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td><?= getStatusBadge($message['status']) ?></td>
            </tr>
            <tr>
                <td><strong>Received:</strong></td>
                <td><?= date('M j, Y g:i A', strtotime($message['created_at'])) ?></td>
            </tr>
            <?php if ($message['updated_at'] !== $message['created_at']): ?>
            <tr>
                <td><strong>Last Updated:</strong></td>
                <td><?= date('M j, Y g:i A', strtotime($message['updated_at'])) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <h6><i class="fas fa-comment me-2"></i>Message Content</h6>
        <div class="card">
            <div class="card-body">
                <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($message['message']) ?></p>
            </div>
        </div>
    </div>
</div>

<?php if ($message['admin_notes']): ?>
<div class="row mt-3">
    <div class="col-12">
        <h6><i class="fas fa-sticky-note me-2"></i>Admin Notes</h6>
        <div class="card bg-light">
            <div class="card-body">
                <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($message['admin_notes']) ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex gap-2">
            <a href="mailto:<?= htmlspecialchars($message['email']) ?>?subject=Re: <?= urlencode($message['subject']) ?>&body=<?= urlencode("Dear " . $message['name'] . ",\n\nThank you for contacting us.\n\nRegards,\n" . settings()['companyname']) ?>" 
               class="btn btn-primary btn-sm">
                <i class="fas fa-reply me-1"></i>Reply via Email
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i>Print
            </button>
        </div>
    </div>
</div>

<script>
// Store message data for reply button (if it exists)
try {
    const replyButton = document.getElementById('replyButton');
    if (replyButton) {
        replyButton.dataset.message = JSON.stringify({
            email: '<?= htmlspecialchars($message['email']) ?>',
            subject: '<?= htmlspecialchars($message['subject']) ?>',
            name: '<?= htmlspecialchars($message['name']) ?>'
        });
    }
} catch(e) {
    // Ignore if replyButton doesn't exist
    console.log('Reply button not found');
}
</script>
