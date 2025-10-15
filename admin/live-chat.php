<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$page = "Live Chat Dashboard";

// Database connection
try {
    $pdo = new PDO("mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get chat statistics
try {
    $stats = [];
    
    // Total conversations
    $stmt = $pdo->query("SELECT COUNT(*) FROM chat_conversations");
    $stats['total_conversations'] = $stmt->fetchColumn();
    
    // Active conversations
    $stmt = $pdo->query("SELECT COUNT(*) FROM chat_conversations WHERE status = 'active'");
    $stats['active_conversations'] = $stmt->fetchColumn();
    
    // Pending conversations
    $stmt = $pdo->query("SELECT COUNT(*) FROM chat_conversations WHERE status = 'pending'");
    $stats['pending_conversations'] = $stmt->fetchColumn();
    
    // Unread messages
    $stmt = $pdo->query("SELECT COUNT(*) FROM chat_messages WHERE is_read = 0 AND sender_type = 'user'");
    $stats['unread_messages'] = $stmt->fetchColumn();
    
} catch(PDOException $e) {
    $stats = ['total_conversations' => 0, 'active_conversations' => 0, 'pending_conversations' => 0, 'unread_messages' => 0];
}
?>

<?php require __DIR__ . '/components/header.php'; ?>

<!-- jQuery (required for real-time updates) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<body class="sb-nav-fixed">
    <?php require __DIR__ . '/components/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php require __DIR__ . '/components/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mt-4">Live Chat Dashboard</h1>
                        <div class="mt-4">
                            <span class="badge bg-success me-2">Online</span>
                            <span id="currentTime" class="text-muted"></span>
                        </div>
                    </div>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Live Chat</li>
                    </ol>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-white-75 small">Total Conversations</div>
                                            <div class="text-lg fw-bold"><?= $stats['total_conversations'] ?></div>
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
                                            <div class="text-white-75 small">Active Chats</div>
                                            <div class="text-lg fw-bold" id="activeChatsCount"><?= $stats['active_conversations'] ?></div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-comment-dots fa-2x"></i>
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
                                            <div class="text-white-75 small">Pending</div>
                                            <div class="text-lg fw-bold" id="pendingChatsCount"><?= $stats['pending_conversations'] ?></div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-clock fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-white-75 small">Unread Messages</div>
                                            <div class="text-lg fw-bold" id="unreadMessagesCount"><?= $stats['unread_messages'] ?></div>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-envelope fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Conversations List -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-list me-1"></i>
                                    Active Conversations
                                    <button class="btn btn-sm btn-outline-primary float-end" onclick="refreshConversations()">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div id="conversationsList" class="conversation-list">
                                        <!-- Conversations will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Interface -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header" id="chatHeader">
                                    <i class="fas fa-comment me-1"></i>
                                    <span id="chatTitle">Select a conversation to start chatting</span>
                                    <div class="float-end" id="chatActions" style="display: none;">
                                        <button class="btn btn-sm btn-outline-success" onclick="closeConversation()">
                                            <i class="fas fa-check"></i> Close Chat
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <!-- Chat Messages -->
                                    <div id="chatMessages" class="chat-messages-admin">
                                        <div class="text-center py-5 text-muted">
                                            <i class="fas fa-comments fa-3x mb-3"></i>
                                            <h6>No conversation selected</h6>
                                            <p>Choose a conversation from the left to start chatting</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Typing Indicator -->
                                    <div id="typingIndicatorAdmin" class="typing-indicator-admin" style="display: none;">
                                        <div class="typing-dots">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </div>
                                        <span class="typing-text">User is typing...</span>
                                    </div>
                                </div>
                                <div class="card-footer" id="chatInputContainer" style="display: none;">
                                    <div class="input-group">
                                        <input type="text" id="adminMessageInput" class="form-control" placeholder="Type your message..." maxlength="1000">
                                        <button class="btn btn-primary" type="button" onclick="sendAdminMessage()">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
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

    <style>
    .conversation-list {
        max-height: 600px;
        overflow-y: auto;
    }

    .conversation-item {
        padding: 15px;
        border-bottom: 1px solid #e9ecef;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .conversation-item:hover {
        background-color: #f8f9fa;
    }

    .conversation-item.active {
        background-color: #e3f2fd;
        border-left: 4px solid #2196f3;
    }

    .conversation-item.unread {
        background-color: #fff3cd;
    }

    .conversation-name {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .conversation-preview {
        font-size: 0.875rem;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .conversation-time {
        font-size: 0.75rem;
        color: #6c757d;
    }

    .conversation-status {
        float: right;
        margin-top: -20px;
    }

    .chat-messages-admin {
        height: 400px;
        overflow-y: auto;
        padding: 15px;
        background: #f8f9fa;
    }

    .admin-message {
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
    }

    .admin-message.user {
        align-items: flex-start;
    }

    .admin-message.admin {
        align-items: flex-end;
    }

    .admin-message.system {
        align-items: center;
    }

    .admin-message-bubble {
        max-width: 70%;
        padding: 10px 15px;
        border-radius: 18px;
        word-wrap: break-word;
        position: relative;
    }

    .admin-message.user .admin-message-bubble {
        background: #e9ecef;
        color: #333;
    }

    .admin-message.admin .admin-message-bubble {
        background: #007bff;
        color: white;
    }

    .admin-message.system .admin-message-bubble {
        background: #6c757d;
        color: white;
        font-style: italic;
        text-align: center;
        border-radius: 12px;
    }

    .admin-message-time {
        font-size: 11px;
        color: #6c757d;
        margin-top: 5px;
    }

    .typing-indicator-admin {
        padding: 10px 15px;
        display: flex;
        align-items: center;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }

    .typing-dots {
        display: flex;
        gap: 4px;
        margin-right: 8px;
    }

    .typing-dots span {
        width: 6px;
        height: 6px;
        background: #6c757d;
        border-radius: 50%;
        animation: typing 1.4s infinite ease-in-out;
    }

    .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
    .typing-dots span:nth-child(2) { animation-delay: -0.16s; }

    @keyframes typing {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }

    .typing-text {
        font-size: 12px;
        color: #6c757d;
    }

    .unread-count {
        background: #dc3545;
        color: white;
        border-radius: 10px;
        padding: 2px 6px;
        font-size: 0.75rem;
        font-weight: bold;
    }
    </style>

    <script>
    class AdminChatDashboard {
        constructor() {
            this.currentConversationId = null;
            this.lastMessageId = 0;
            this.adminName = '<?= $_SESSION['username'] ?>';
            this.pollInterval = null;
            this.isTyping = false;
            this.typingTimer = null;
            
            this.initializeEventListeners();
            this.loadConversations();
            this.startPolling();
            this.updateClock();
        }
        
        initializeEventListeners() {
            // Message input events
            const messageInput = document.getElementById('adminMessageInput');
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.sendMessage();
                } else {
                    this.handleTyping();
                }
            });
            
            messageInput.addEventListener('input', () => {
                this.handleTyping();
            });
            
            messageInput.addEventListener('blur', () => {
                this.stopTyping();
            });
        }
        
        async loadConversations() {
            try {
                const response = await fetch('../api/chat.php?action=conversations');
                const data = await response.json();
                
                if (data.success) {
                    this.displayConversations(data.conversations);
                }
            } catch (error) {
                console.error('Error loading conversations:', error);
            }
        }
        
        displayConversations(conversations) {
            const container = document.getElementById('conversationsList');
            
            if (conversations.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>No conversations yet</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = conversations.map(conv => {
                const time = conv.last_message_time ? new Date(conv.last_message_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';
                const unreadBadge = conv.unread_count > 0 ? `<span class="unread-count">${conv.unread_count}</span>` : '';
                const statusBadge = conv.status === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-warning">Pending</span>';
                
                return `
                    <div class="conversation-item ${conv.unread_count > 0 ? 'unread' : ''}" 
                         onclick="selectConversation(${conv.id})" 
                         data-conversation-id="${conv.id}">
                        <div class="conversation-name">
                            ${conv.user_name}
                            <div class="conversation-status">
                                ${unreadBadge}
                                ${statusBadge}
                            </div>
                        </div>
                        <div class="conversation-preview">${conv.user_email}</div>
                        <div class="conversation-time">${time}</div>
                    </div>
                `;
            }).join('');
        }
        
        async selectConversation(conversationId) {
            this.currentConversationId = conversationId;
            this.lastMessageId = 0;
            
            // Update UI
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-conversation-id="${conversationId}"]`).classList.add('active');
            
            // Show chat interface
            document.getElementById('chatTitle').textContent = 'Loading conversation...';
            document.getElementById('chatActions').style.display = 'block';
            document.getElementById('chatInputContainer').style.display = 'block';
            
            // Load messages
            await this.loadMessages();
            
            // Mark messages as read
            await this.markMessagesRead();
        }
        
        async loadMessages() {
            if (!this.currentConversationId) return;
            
            try {
                const response = await fetch(`../api/chat.php?action=messages&conversation_id=${this.currentConversationId}&last_id=${this.lastMessageId}`);
                const data = await response.json();
                
                if (data.success) {
                    if (this.lastMessageId === 0) {
                        // First load - clear messages
                        document.getElementById('chatMessages').innerHTML = '';
                    }
                    
                    data.messages.forEach(message => {
                        this.addMessageToUI(message);
                        this.lastMessageId = Math.max(this.lastMessageId, message.id);
                    });
                    
                    this.scrollToBottom();
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }
        
        addMessageToUI(message) {
            const messagesContainer = document.getElementById('chatMessages');
            
            // Check if message already exists in UI to prevent duplicates
            const existingMessages = messagesContainer.querySelectorAll('.admin-message');
            for (let existingMsg of existingMessages) {
                const existingText = existingMsg.querySelector('.admin-message-bubble').textContent;
                if (existingText === message.message && 
                    existingMsg.classList.contains(message.sender_type)) {
                    // Message already exists, don't add duplicate
                    return;
                }
            }
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `admin-message ${message.sender_type}`;
            messageDiv.setAttribute('data-message-id', message.id || 'temp');
            
            const time = new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            messageDiv.innerHTML = `
                <div class="admin-message-bubble">
                    ${this.escapeHtml(message.message)}
                </div>
                <div class="admin-message-time">${time} - ${message.sender_name}</div>
            `;
            
            messagesContainer.appendChild(messageDiv);
        }
        
        async sendMessage() {
            const messageInput = document.getElementById('adminMessageInput');
            const message = messageInput.value.trim();
            
            if (!message || !this.currentConversationId) return;
            
            try {
                // Clear input immediately to prevent double sending
                messageInput.value = '';
                this.stopTyping();
                
                const response = await fetch('../api/chat.php?action=send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        conversation_id: this.currentConversationId,
                        message: message,
                        sender_type: 'admin',
                        sender_name: this.adminName
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Trigger immediate message load instead of adding to UI directly
                    setTimeout(() => this.loadMessages(), 100);
                    console.log('Message sent successfully');
                } else {
                    console.error('Failed to send message:', data.error);
                    // Restore message to input if failed
                    messageInput.value = message;
                }
            } catch (error) {
                console.error('Error sending message:', error);
                // Restore message to input if failed
                messageInput.value = message;
            }
        }
        
        async markMessagesRead() {
            if (!this.currentConversationId) return;
            
            try {
                await fetch('../api/chat.php?action=mark_read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        conversation_id: this.currentConversationId,
                        sender_type: 'user'
                    })
                });
            } catch (error) {
                console.error('Error marking messages as read:', error);
            }
        }
        
        handleTyping() {
            if (!this.isTyping) {
                this.isTyping = true;
                this.updateTypingStatus(true);
            }
            
            clearTimeout(this.typingTimer);
            this.typingTimer = setTimeout(() => {
                this.stopTyping();
            }, 3000);
        }
        
        stopTyping() {
            if (this.isTyping) {
                this.isTyping = false;
                this.updateTypingStatus(false);
            }
            clearTimeout(this.typingTimer);
        }
        
        async updateTypingStatus(isTyping) {
            if (!this.currentConversationId) return;
            
            try {
                await fetch('../api/chat.php?action=typing', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        conversation_id: this.currentConversationId,
                        user_type: 'admin',
                        user_name: this.adminName,
                        is_typing: isTyping
                    })
                });
            } catch (error) {
                console.error('Error updating typing status:', error);
            }
        }
        
        async checkTypingStatus() {
            if (!this.currentConversationId) return;
            
            try {
                const response = await fetch(`../api/chat.php?action=typing&conversation_id=${this.currentConversationId}`);
                const data = await response.json();
                
                if (data.success) {
                    const userTyping = data.typing.find(t => t.user_type === 'user');
                    const typingIndicator = document.getElementById('typingIndicatorAdmin');
                    
                    if (userTyping) {
                        typingIndicator.style.display = 'flex';
                    } else {
                        typingIndicator.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Error checking typing status:', error);
            }
        }
        
        startPolling() {
            this.pollInterval = setInterval(() => {
                this.loadConversations();
                this.updateStatistics();
                if (this.currentConversationId) {
                    this.loadMessages();
                    this.checkTypingStatus();
                }
            }, 2000); // Faster polling for better real-time experience
        }
        
        async updateStatistics() {
            try {
                // Update the statistics cards with real-time data
                const response = await fetch('../api/chat.php?action=conversations');
                const data = await response.json();
                
                if (data.success) {
                    const conversations = data.conversations;
                    const activeCount = conversations.filter(c => c.status === 'active').length;
                    const pendingCount = conversations.filter(c => c.status === 'pending').length;
                    const unreadCount = conversations.reduce((sum, c) => sum + parseInt(c.unread_count || 0), 0);
                    
                    // Update the statistics display
                    const activeElement = document.getElementById('activeChatsCount');
                    const pendingElement = document.getElementById('pendingChatsCount');
                    const unreadElement = document.getElementById('unreadMessagesCount');
                    
                    if (activeElement) activeElement.textContent = activeCount;
                    if (pendingElement) pendingElement.textContent = pendingCount;
                    if (unreadElement) unreadElement.textContent = unreadCount;
                }
            } catch (error) {
                console.error('Error updating statistics:', error);
            }
        }
        
        scrollToBottom() {
            const messagesContainer = document.getElementById('chatMessages');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        updateClock() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString();
            setTimeout(() => this.updateClock(), 1000);
        }
        
        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    }

    // Global functions
    let chatDashboard;

    function selectConversation(id) {
        chatDashboard.selectConversation(id);
    }

    function sendAdminMessage() {
        chatDashboard.sendMessage();
    }

    function refreshConversations() {
        chatDashboard.loadConversations();
    }

    function closeConversation() {
        // Implementation for closing conversation
        if (confirm('Are you sure you want to close this conversation?')) {
            // Add close conversation logic here
        }
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        chatDashboard = new AdminChatDashboard();
        
        // Check if conversation ID is provided in URL
        const urlParams = new URLSearchParams(window.location.search);
        const conversationId = urlParams.get('conversation');
        if (conversationId) {
            // Wait a bit for the conversations to load, then select the conversation
            setTimeout(() => {
                chatDashboard.selectConversation(parseInt(conversationId));
            }, 1000);
        }
    });
    </script>
</body>
</html>
