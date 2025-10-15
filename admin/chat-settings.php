<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$page = "Chat Settings";

// Database connection
try {
    $pdo = new PDO("mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create or update chat settings (you can extend this to use a settings table)
        $settings = [
            'chat_enabled' => isset($_POST['chat_enabled']) ? 1 : 0,
            'welcome_message' => trim($_POST['welcome_message'] ?? ''),
            'offline_message' => trim($_POST['offline_message'] ?? ''),
            'auto_close_hours' => intval($_POST['auto_close_hours'] ?? 24),
            'max_file_size' => intval($_POST['max_file_size'] ?? 5),
            'allowed_file_types' => trim($_POST['allowed_file_types'] ?? ''),
            'business_hours_start' => $_POST['business_hours_start'] ?? '09:00',
            'business_hours_end' => $_POST['business_hours_end'] ?? '17:00',
            'timezone' => $_POST['timezone'] ?? 'Asia/Dhaka'
        ];
        
        // For now, we'll store in a simple way. You can create a proper settings table later.
        file_put_contents(__DIR__ . '/../config/chat_settings.json', json_encode($settings, JSON_PRETTY_PRINT));
        
        $success_message = "Chat settings updated successfully!";
    } catch(Exception $e) {
        $error_message = "Error updating settings: " . $e->getMessage();
    }
}

// Load current settings
$default_settings = [
    'chat_enabled' => 1,
    'welcome_message' => 'Hello! Welcome to our live chat. How can we help you today?',
    'offline_message' => 'We are currently offline. Please leave a message and we will get back to you soon.',
    'auto_close_hours' => 24,
    'max_file_size' => 5,
    'allowed_file_types' => 'jpg,jpeg,png,gif,pdf,doc,docx',
    'business_hours_start' => '09:00',
    'business_hours_end' => '17:00',
    'timezone' => 'Asia/Dhaka'
];

$settings_file = __DIR__ . '/../config/chat_settings.json';
if (file_exists($settings_file)) {
    $current_settings = json_decode(file_get_contents($settings_file), true);
    $current_settings = array_merge($default_settings, $current_settings);
} else {
    $current_settings = $default_settings;
}

// Get chat statistics for the dashboard
try {
    $stats = [];
    
    // Today's conversations
    $stmt = $pdo->query("SELECT COUNT(*) FROM chat_conversations WHERE DATE(created_at) = CURDATE()");
    $stats['today_conversations'] = $stmt->fetchColumn();
    
    // Today's messages
    $stmt = $pdo->query("SELECT COUNT(*) FROM chat_messages WHERE DATE(created_at) = CURDATE()");
    $stats['today_messages'] = $stmt->fetchColumn();
    
    // Average response time (in minutes)
    $stmt = $pdo->query("
        SELECT AVG(TIMESTAMPDIFF(MINUTE, u.created_at, a.created_at)) as avg_response_time
        FROM chat_messages u
        JOIN chat_messages a ON u.conversation_id = a.conversation_id 
        WHERE u.sender_type = 'user' 
        AND a.sender_type = 'admin' 
        AND a.created_at > u.created_at
        AND DATE(u.created_at) = CURDATE()
    ");
    $stats['avg_response_time'] = round($stmt->fetchColumn() ?? 0, 1);
    
    // Active conversations
    $stmt = $pdo->query("SELECT COUNT(*) FROM chat_conversations WHERE status = 'active'");
    $stats['active_conversations'] = $stmt->fetchColumn();
    
} catch(PDOException $e) {
    $stats = ['today_conversations' => 0, 'today_messages' => 0, 'avg_response_time' => 0, 'active_conversations' => 0];
}
?>

<?php require __DIR__ . '/components/header.php'; ?>

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mt-4">Chat Settings</h1>
                        <div class="mt-4">
                            <span class="badge bg-<?= $current_settings['chat_enabled'] ? 'success' : 'danger' ?>">
                                Chat <?= $current_settings['chat_enabled'] ? 'Enabled' : 'Disabled' ?>
                            </span>
                        </div>
                    </div>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="live-chat.php">Live Chat</a></li>
                        <li class="breadcrumb-item active">Settings</li>
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

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-info text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-white-75 small">Today's Conversations</div>
                                            <div class="text-lg fw-bold"><?= $stats['today_conversations'] ?></div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-comments fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-white-75 small">Today's Messages</div>
                                            <div class="text-lg fw-bold"><?= $stats['today_messages'] ?></div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-envelope fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-white-75 small">Avg Response Time</div>
                                            <div class="text-lg fw-bold"><?= $stats['avg_response_time'] ?>m</div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-clock fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-white-75 small">Active Chats</div>
                                            <div class="text-lg fw-bold"><?= $stats['active_conversations'] ?></div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-comment-dots fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Settings Form -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-cog me-1"></i>
                                    Chat Configuration
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <!-- General Settings -->
                                        <h6 class="mb-3">General Settings</h6>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="chat_enabled" name="chat_enabled" 
                                                       <?= $current_settings['chat_enabled'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="chat_enabled">
                                                    Enable Live Chat
                                                </label>
                                            </div>
                                            <div class="form-text">Turn on/off the chat widget on your website</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="welcome_message" class="form-label">Welcome Message</label>
                                            <textarea class="form-control" id="welcome_message" name="welcome_message" rows="3" 
                                                      placeholder="Enter the welcome message shown to users"><?= htmlspecialchars($current_settings['welcome_message']) ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="offline_message" class="form-label">Offline Message</label>
                                            <textarea class="form-control" id="offline_message" name="offline_message" rows="3" 
                                                      placeholder="Message shown when chat is offline"><?= htmlspecialchars($current_settings['offline_message']) ?></textarea>
                                        </div>

                                        <hr class="my-4">

                                        <!-- Business Hours -->
                                        <h6 class="mb-3">Business Hours</h6>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="business_hours_start" class="form-label">Start Time</label>
                                                <input type="time" class="form-control" id="business_hours_start" name="business_hours_start" 
                                                       value="<?= htmlspecialchars($current_settings['business_hours_start']) ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="business_hours_end" class="form-label">End Time</label>
                                                <input type="time" class="form-control" id="business_hours_end" name="business_hours_end" 
                                                       value="<?= htmlspecialchars($current_settings['business_hours_end']) ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="timezone" class="form-label">Timezone</label>
                                                <select class="form-select" id="timezone" name="timezone">
                                                    <option value="Asia/Dhaka" <?= $current_settings['timezone'] === 'Asia/Dhaka' ? 'selected' : '' ?>>Asia/Dhaka</option>
                                                    <option value="UTC" <?= $current_settings['timezone'] === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                                    <option value="America/New_York" <?= $current_settings['timezone'] === 'America/New_York' ? 'selected' : '' ?>>America/New_York</option>
                                                    <option value="Europe/London" <?= $current_settings['timezone'] === 'Europe/London' ? 'selected' : '' ?>>Europe/London</option>
                                                </select>
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <!-- Advanced Settings -->
                                        <h6 class="mb-3">Advanced Settings</h6>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="auto_close_hours" class="form-label">Auto-close Conversations (hours)</label>
                                                <input type="number" class="form-control" id="auto_close_hours" name="auto_close_hours" 
                                                       value="<?= $current_settings['auto_close_hours'] ?>" min="1" max="168">
                                                <div class="form-text">Automatically close inactive conversations after specified hours</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="max_file_size" class="form-label">Max File Size (MB)</label>
                                                <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                                       value="<?= $current_settings['max_file_size'] ?>" min="1" max="50">
                                                <div class="form-text">Maximum file size for attachments</div>
                                            </div>
                                        </div>

                                        <div class="mb-3 mt-3">
                                            <label for="allowed_file_types" class="form-label">Allowed File Types</label>
                                            <input type="text" class="form-control" id="allowed_file_types" name="allowed_file_types" 
                                                   value="<?= htmlspecialchars($current_settings['allowed_file_types']) ?>"
                                                   placeholder="jpg,jpeg,png,gif,pdf,doc,docx">
                                            <div class="form-text">Comma-separated list of allowed file extensions</div>
                                        </div>

                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i>Save Settings
                                            </button>
                                            <a href="live-chat.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-arrow-left me-1"></i>Back to Live Chat
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-tools me-1"></i>
                                    Quick Actions
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="live-chat.php" class="btn btn-primary">
                                            <i class="fas fa-comments me-1"></i>Open Live Chat
                                        </a>
                                        <a href="chat-conversations.php" class="btn btn-info">
                                            <i class="fas fa-list me-1"></i>View All Conversations
                                        </a>
                                        <button type="button" class="btn btn-warning" onclick="clearOldConversations()">
                                            <i class="fas fa-broom me-1"></i>Clear Old Conversations
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="exportChatData()">
                                            <i class="fas fa-download me-1"></i>Export Chat Data
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-4">
                                <div class="card-header">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Chat Widget Code
                                </div>
                                <div class="card-body">
                                    <p class="small text-muted">The chat widget is automatically included on all pages. If you need to manually add it, use this code:</p>
                                    <code class="small">
                                        &lt;?php require 'components/chat-widget.php'; ?&gt;
                                    </code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php require __DIR__ . '/components/footer.php'; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function clearOldConversations() {
            if (confirm('Are you sure you want to clear conversations older than 30 days? This action cannot be undone.')) {
                // Implementation for clearing old conversations
                fetch('chat-maintenance.php?action=clear_old', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Old conversations cleared successfully');
                        location.reload();
                    } else {
                        alert('Error clearing conversations: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error clearing conversations');
                });
            }
        }

        function exportChatData() {
            window.open('export-chat-data.php', '_blank');
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const welcomeMessage = document.getElementById('welcome_message').value.trim();
            const offlineMessage = document.getElementById('offline_message').value.trim();
            
            if (!welcomeMessage) {
                alert('Please enter a welcome message');
                e.preventDefault();
                return;
            }
            
            if (!offlineMessage) {
                alert('Please enter an offline message');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
