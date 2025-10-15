<?php
// Get current user info if logged in
$current_user = null;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] && isset($_SESSION['userid'])) {
    try {
        $db = new MysqliDb();
        $db->where("id", $_SESSION['userid']);
        $current_user = $db->getOne("users", "first_name, last_name, email");
    } catch(Exception $e) {
        // If there's an error, continue without user info
        $current_user = null;
    }
}
?>

<!-- Chat Widget -->
<div id="chatWidget" class="chat-widget" 
     data-user-logged-in="<?= $current_user ? 'true' : 'false' ?>"
     data-user-name="<?= $current_user ? htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']) : '' ?>"
     data-user-email="<?= $current_user ? htmlspecialchars($current_user['email']) : '' ?>"
     data-user-id="<?= $current_user ? $_SESSION['userid'] : '' ?>">
    <!-- Chat Toggle Button -->
    <div id="chatToggle" class="chat-toggle">
        <i class="fas fa-comments"></i>
        <span id="unreadBadge" class="unread-badge" style="display: none;">0</span>
    </div>
    
    <!-- Chat Window -->
    <div id="chatWindow" class="chat-window" style="display: none;">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="chat-header-info">
                <h6 class="mb-0">Live Chat Support</h6>
                <small class="text-muted">We're here to help!</small>
            </div>
            <div class="chat-header-actions">
                <button id="switchUser" class="btn btn-sm btn-link text-white" title="Switch User" style="display: none;">
                    <i class="fas fa-user-switch"></i>
                </button>
                <button id="minimizeChat" class="btn btn-sm btn-link text-white">
                    <i class="fas fa-minus"></i>
                </button>
                <button id="closeChat" class="btn btn-sm btn-link text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Chat Messages -->
        <div id="chatMessages" class="chat-messages">
            <div class="chat-welcome">
                <div class="text-center py-3">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h6>Welcome to Live Chat!</h6>
                    <p class="text-muted small">Please enter your details to start chatting with our support team.</p>
                </div>
            </div>
        </div>
        
        <!-- Typing Indicator -->
        <div id="typingIndicator" class="typing-indicator" style="display: none;">
            <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span class="typing-text">Support is typing...</span>
        </div>
        
        <!-- Chat Input -->
        <div id="chatInput" class="chat-input">
            <!-- User Info Form -->
            <div id="userInfoForm" class="user-info-form">
                <div class="mb-2">
                    <input type="text" id="userName" class="form-control form-control-sm" placeholder="Your Name" required>
                </div>
                <div class="mb-2">
                    <input type="email" id="userEmail" class="form-control form-control-sm" placeholder="Your Email" required>
                </div>
                <button id="startChat" class="btn btn-primary btn-sm w-100">Start Chat</button>
            </div>
            
            <!-- Logged-in User Quick Start -->
            <div id="loggedInStart" class="logged-in-start" style="display: none;">
                <div class="text-center">
                    <p class="mb-2">Welcome back!</p>
                    <button id="quickStartChat" class="btn btn-primary btn-sm w-100">Start Live Chat</button>
                </div>
            </div>
            
            <!-- Message Input -->
            <div id="messageForm" class="message-form" style="display: none;">
                <div class="input-group">
                    <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." maxlength="1000">
                    <button id="sendMessage" class="btn btn-primary" type="button">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.chat-toggle {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    position: relative;
}

.chat-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

.chat-toggle i {
    color: white;
    font-size: 24px;
}

.unread-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.chat-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 350px;
    height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.chat-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-header-info h6 {
    font-weight: 600;
}

.chat-header-actions button {
    padding: 2px 6px;
    border: none;
    background: none;
}

.chat-header-actions button:hover {
    background: rgba(255,255,255,0.1);
    border-radius: 4px;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    background: #f8f9fa;
}

.chat-welcome {
    text-align: center;
    color: #6c757d;
}

.message {
    margin-bottom: 15px;
    display: flex;
    flex-direction: column;
}

.message.user {
    align-items: flex-end;
}

.message.admin, .message.system {
    align-items: flex-start;
}

.message-bubble {
    max-width: 80%;
    padding: 10px 15px;
    border-radius: 18px;
    word-wrap: break-word;
    position: relative;
}

.message.user .message-bubble {
    background: #007bff;
    color: white;
}

.message.admin .message-bubble {
    background: white;
    color: #333;
    border: 1px solid #e9ecef;
}

.message.system .message-bubble {
    background: #e9ecef;
    color: #6c757d;
    font-style: italic;
    text-align: center;
    border-radius: 12px;
}

.message-time {
    font-size: 11px;
    color: #6c757d;
    margin-top: 5px;
}

.message.user .message-time {
    text-align: right;
}

.typing-indicator {
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

.chat-input {
    padding: 15px;
    border-top: 1px solid #e9ecef;
    background: white;
}

.user-info-form .form-control {
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.logged-in-start {
    padding: 15px;
}

.logged-in-start p {
    color: #6c757d;
    font-size: 14px;
}

.logged-in-start .btn {
    border-radius: 8px;
}

.message-form .input-group {
    border-radius: 25px;
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.message-form .form-control {
    border: none;
    padding: 12px 15px;
}

.message-form .form-control:focus {
    box-shadow: none;
}

.message-form .btn {
    border: none;
    padding: 12px 15px;
    border-radius: 0;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .chat-window {
        width: 300px;
        height: 400px;
        bottom: 70px;
        right: -10px;
    }
    
    .chat-toggle {
        width: 50px;
        height: 50px;
    }
    
    .chat-toggle i {
        font-size: 20px;
    }
}

/* Scrollbar Styling */
.chat-messages::-webkit-scrollbar {
    width: 4px;
}

.chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
class ChatWidget {
    constructor() {
        this.conversationId = null;
        this.sessionId = null;
        this.lastMessageId = 0;
        this.isTyping = false;
        this.typingTimer = null;
        this.pollInterval = null;
        this.userName = '';
        this.userEmail = '';
        this.userId = '';
        this.isLoggedIn = false;
        this.sentMessages = new Set(); // Track sent messages to prevent duplicates
        this.isSending = false; // Prevent multiple sends
        
        this.initializeEventListeners();
        this.checkUserAuthentication();
        this.loadSessionData();
    }
    
    initializeEventListeners() {
        // Toggle chat window
        document.getElementById('chatToggle').addEventListener('click', () => {
            this.toggleChat();
        });
        
        // Minimize chat
        document.getElementById('minimizeChat').addEventListener('click', () => {
            this.minimizeChat();
        });
        
        // Close chat
        document.getElementById('closeChat').addEventListener('click', () => {
            this.closeChat();
        });
        
        // Switch user
        document.getElementById('switchUser').addEventListener('click', () => {
            if (confirm('Are you sure you want to switch user? This will end the current chat session.')) {
                this.clearUserSession();
                this.toggleChat(); // Open chat window to show login form
            }
        });
        
        // Start chat
        document.getElementById('startChat').addEventListener('click', () => {
            this.startConversation();
        });
        
        // Quick start chat for logged-in users
        document.getElementById('quickStartChat').addEventListener('click', () => {
            this.startConversation();
        });
        
        // Send message
        document.getElementById('sendMessage').addEventListener('click', () => {
            this.sendMessage();
        });
        
        // Message input events
        const messageInput = document.getElementById('messageInput');
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
    
    checkUserAuthentication() {
        // Get user data from data attributes
        const chatWidget = document.getElementById('chatWidget');
        this.isLoggedIn = chatWidget.dataset.userLoggedIn === 'true';
        
        if (this.isLoggedIn) {
            this.userName = chatWidget.dataset.userName;
            this.userEmail = chatWidget.dataset.userEmail;
            this.userId = chatWidget.dataset.userId;
            
            console.log('User authenticated:', this.userName, this.userEmail);
        }
    }
    
    loadSessionData() {
        // If user is logged in, use user-specific session key
        let sessionKey;
        if (this.isLoggedIn && this.userId) {
            sessionKey = `chatSession_user_${this.userId}`;
        } else {
            // For guest users, use browser fingerprint
            const browserFingerprint = this.getBrowserFingerprint();
            sessionKey = `chatSession_guest_${browserFingerprint}`;
        }
        
        const savedData = localStorage.getItem(sessionKey);
        if (savedData) {
            const data = JSON.parse(savedData);
            
            // For logged-in users, verify the session matches current user
            if (this.isLoggedIn) {
                if (data.userId === this.userId && data.userEmail === this.userEmail) {
                    this.conversationId = data.conversationId;
                    this.sessionId = data.sessionId;
                    
                    if (this.conversationId) {
                        this.showMessageForm();
                        this.startPolling();
                        this.loadMessages();
                    }
                } else {
                    // Session doesn't match current user, clear it
                    localStorage.removeItem(sessionKey);
                    this.showInitialUI();
                }
            } else {
                // For guest users, load saved data
                this.conversationId = data.conversationId;
                this.sessionId = data.sessionId;
                this.userName = data.userName;
                this.userEmail = data.userEmail;
                
                if (this.conversationId && this.userName && this.userEmail) {
                    this.showMessageForm();
                    this.startPolling();
                    this.loadMessages();
                } else {
                    this.showInitialUI();
                }
            }
        } else {
            // No saved data, show initial UI
            this.showInitialUI();
        }
    }
    
    showInitialUI() {
        if (this.isLoggedIn) {
            // For logged-in users, show quick start
            document.getElementById('userInfoForm').style.display = 'none';
            document.getElementById('loggedInStart').style.display = 'block';
            document.getElementById('messageForm').style.display = 'none';
        } else {
            // For guest users, show registration form
            document.getElementById('userInfoForm').style.display = 'block';
            document.getElementById('loggedInStart').style.display = 'none';
            document.getElementById('messageForm').style.display = 'none';
        }
    }
    
    saveSessionData() {
        // Use user-specific session key
        let sessionKey;
        if (this.isLoggedIn && this.userId) {
            sessionKey = `chatSession_user_${this.userId}`;
        } else {
            const browserFingerprint = this.getBrowserFingerprint();
            sessionKey = `chatSession_guest_${browserFingerprint}`;
        }
        
        const data = {
            conversationId: this.conversationId,
            sessionId: this.sessionId,
            userName: this.userName,
            userEmail: this.userEmail,
            userId: this.userId,
            timestamp: Date.now()
        };
        localStorage.setItem(sessionKey, JSON.stringify(data));
    }
    
    toggleChat() {
        const chatWindow = document.getElementById('chatWindow');
        if (chatWindow.style.display === 'none') {
            chatWindow.style.display = 'flex';
            
            // If user is logged in and no conversation exists, start one automatically
            if (this.isLoggedIn && !this.conversationId) {
                this.startConversation();
            } else if (this.conversationId) {
                this.scrollToBottom();
            }
        } else {
            chatWindow.style.display = 'none';
        }
    }
    
    minimizeChat() {
        document.getElementById('chatWindow').style.display = 'none';
    }
    
    closeChat() {
        document.getElementById('chatWindow').style.display = 'none';
        this.stopPolling();
    }
    
    async startConversation() {
        let userName, userEmail;
        
        // If user is logged in, use their info directly
        if (this.isLoggedIn) {
            userName = this.userName;
            userEmail = this.userEmail;
        } else {
            // For guest users, get info from form
            userName = document.getElementById('userName').value.trim();
            userEmail = document.getElementById('userEmail').value.trim();
            
            if (!userName || !userEmail) {
                alert('Please enter your name and email');
                return;
            }
            
            if (!this.validateEmail(userEmail)) {
                alert('Please enter a valid email address');
                return;
            }
            
            // Update instance variables for guest users
            this.userName = userName;
            this.userEmail = userEmail;
        }
        
        // Check if this is a different user than the current session
        if (this.userEmail && this.userEmail !== userEmail) {
            this.clearUserSession();
        }
        
        try {
            const response = await fetch('api/chat.php?action=start_conversation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_name: userName,
                    user_email: userEmail,
                    user_id: this.userId || null,
                    session_id: this.sessionId || this.generateSessionId()
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.conversationId = data.conversation_id;
                this.sessionId = data.session_id;
                this.userName = userName;
                this.userEmail = userEmail;
                
                this.saveSessionData();
                this.showMessageForm();
                this.startPolling();
                this.loadMessages();
            } else {
                alert('Failed to start conversation: ' + data.error);
            }
        } catch (error) {
            console.error('Error starting conversation:', error);
            alert('Failed to start conversation. Please try again.');
        }
    }
    
    showMessageForm() {
        document.getElementById('userInfoForm').style.display = 'none';
        document.getElementById('loggedInStart').style.display = 'none';
        document.getElementById('messageForm').style.display = 'block';
        document.querySelector('.chat-welcome').style.display = 'none';
        document.getElementById('switchUser').style.display = 'block';
        
        // Update header to show current user
        const headerInfo = document.querySelector('.chat-header-info h6');
        if (headerInfo && this.userName) {
            headerInfo.textContent = `Chat - ${this.userName}`;
        }
    }
    
    async sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        
        if (!message || !this.conversationId || this.isSending) return;
        
        // Prevent multiple sends
        this.isSending = true;
        
        try {
            // Clear input immediately to prevent double sending
            messageInput.value = '';
            this.stopTyping();
            
            // Create a unique identifier for this message
            const messageHash = this.hashMessage(message);
            
            // Check if we already sent this message recently
            if (this.sentMessages.has(messageHash)) {
                this.isSending = false;
                return;
            }
            
            // Add to sent messages tracking
            this.sentMessages.add(messageHash);
            
            const response = await fetch('api/chat.php?action=send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    conversation_id: this.conversationId,
                    message: message,
                    sender_type: 'user',
                    sender_name: this.userName
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Trigger immediate message load to show the sent message quickly
                setTimeout(() => this.loadMessages(), 100);
                console.log('Message sent successfully');
            } else {
                console.error('Failed to send message:', data.error);
                // Remove from sent messages and restore message to input if failed
                this.sentMessages.delete(messageHash);
                messageInput.value = message;
            }
        } catch (error) {
            console.error('Error sending message:', error);
            // Restore message to input if failed
            messageInput.value = message;
        } finally {
            // Reset sending flag after a short delay
            setTimeout(() => {
                this.isSending = false;
            }, 500);
        }
    }
    
    async loadMessages() {
        if (!this.conversationId) return;
        
        try {
            const response = await fetch(`api/chat.php?action=messages&conversation_id=${this.conversationId}&last_id=${this.lastMessageId}`);
            const data = await response.json();
            
            if (data.success && data.messages.length > 0) {
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
        const existingMessages = messagesContainer.querySelectorAll('.message');
        for (let existingMsg of existingMessages) {
            const existingText = existingMsg.querySelector('.message-bubble').textContent;
            if (existingText === message.message && 
                existingMsg.classList.contains(message.sender_type)) {
                // Message already exists, don't add duplicate
                return;
            }
        }
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.sender_type}`;
        messageDiv.setAttribute('data-message-id', message.id || 'temp');
        
        const time = new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        messageDiv.innerHTML = `
            <div class="message-bubble">
                ${this.escapeHtml(message.message)}
            </div>
            <div class="message-time">${time}</div>
        `;
        
        messagesContainer.appendChild(messageDiv);
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
        if (!this.conversationId) return;
        
        try {
            await fetch('api/chat.php?action=typing', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    conversation_id: this.conversationId,
                    user_type: 'user',
                    user_name: this.userName,
                    is_typing: isTyping
                })
            });
        } catch (error) {
            console.error('Error updating typing status:', error);
        }
    }
    
    async checkTypingStatus() {
        if (!this.conversationId) return;
        
        try {
            const response = await fetch(`api/chat.php?action=typing&conversation_id=${this.conversationId}`);
            const data = await response.json();
            
            if (data.success) {
                const adminTyping = data.typing.find(t => t.user_type === 'admin');
                const typingIndicator = document.getElementById('typingIndicator');
                
                if (adminTyping) {
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
            this.loadMessages();
            this.checkTypingStatus();
            this.cleanupSentMessages(); // Clean up old message hashes
        }, 2000);
    }
    
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }
    
    scrollToBottom() {
        const messagesContainer = document.getElementById('chatMessages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    generateSessionId() {
        const browserFingerprint = this.getBrowserFingerprint();
        return 'chat_' + Date.now() + '_' + browserFingerprint + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    getBrowserFingerprint() {
        // Create a simple browser fingerprint for user identification
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillText('Browser fingerprint', 2, 2);
        
        const fingerprint = [
            navigator.userAgent,
            navigator.language,
            screen.width + 'x' + screen.height,
            new Date().getTimezoneOffset(),
            canvas.toDataURL()
        ].join('|');
        
        // Simple hash function
        let hash = 0;
        for (let i = 0; i < fingerprint.length; i++) {
            const char = fingerprint.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32bit integer
        }
        return Math.abs(hash).toString(36);
    }
    
    hashMessage(message) {
        // Create a simple hash for message deduplication
        return message + '_' + Date.now();
    }
    
    cleanupSentMessages() {
        // Clean up old sent message hashes (keep only last 10)
        if (this.sentMessages.size > 10) {
            const messagesArray = Array.from(this.sentMessages);
            this.sentMessages.clear();
            // Keep only the last 5 messages
            messagesArray.slice(-5).forEach(hash => this.sentMessages.add(hash));
        }
    }
    
    clearUserSession() {
        // Clear current session data for user switching
        this.stopPolling();
        this.conversationId = null;
        this.sessionId = null;
        this.lastMessageId = 0;
        this.sentMessages.clear();
        
        // Clear localStorage for current user session
        if (this.isLoggedIn && this.userId) {
            localStorage.removeItem(`chatSession_user_${this.userId}`);
        } else {
            // Clear all guest sessions
            const keys = Object.keys(localStorage);
            keys.forEach(key => {
                if (key.startsWith('chatSession_guest_') || key.startsWith('chatSession_')) {
                    localStorage.removeItem(key);
                }
            });
        }
        
        // Don't clear userName and userEmail for logged-in users
        if (!this.isLoggedIn) {
            this.userName = '';
            this.userEmail = '';
        }
        
        // Reset UI based on user authentication status
        document.getElementById('chatMessages').innerHTML = `
            <div class="chat-welcome">
                <div class="text-center py-3">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h6>Welcome to Live Chat!</h6>
                    <p class="text-muted small">Please enter your details to start chatting with our support team.</p>
                </div>
            </div>
        `;
        
        if (this.isLoggedIn) {
            // For logged-in users, show quick start
            document.getElementById('userInfoForm').style.display = 'none';
            document.getElementById('loggedInStart').style.display = 'block';
            document.getElementById('messageForm').style.display = 'none';
        } else {
            // For guest users, show the registration form
            document.getElementById('userInfoForm').style.display = 'block';
            document.getElementById('loggedInStart').style.display = 'none';
            document.getElementById('messageForm').style.display = 'none';
            
            // Clear form inputs
            document.getElementById('userName').value = '';
            document.getElementById('userEmail').value = '';
        }
        
        document.querySelector('.chat-welcome').style.display = 'block';
        
        document.getElementById('switchUser').style.display = 'none';
        
        // Reset header
        const headerInfo = document.querySelector('.chat-header-info h6');
        if (headerInfo) {
            headerInfo.textContent = 'Live Chat Support';
        }
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

// Global variable for chat widget
let chatWidget;

// Initialize chat widget when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    chatWidget = new ChatWidget();
});

// Global function to clear chat sessions (called from logout)
function clearAllChatSessions() {
    if (typeof Storage !== "undefined") {
        const keys = Object.keys(localStorage);
        keys.forEach(key => {
            if (key.startsWith('chatSession_')) {
                localStorage.removeItem(key);
            }
        });
    }
}
</script>
