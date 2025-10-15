<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../vendor/autoload.php';

try {
    $pdo = new PDO("mysql:host=" . settings()['hostname'] . ";dbname=" . settings()['database'], settings()['user'], settings()['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        handleGet($pdo, $action);
        break;
    case 'POST':
        handlePost($pdo, $action);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleGet($pdo, $action) {
    switch ($action) {
        case 'messages':
            getMessages($pdo);
            break;
        case 'conversations':
            getConversations($pdo);
            break;
        case 'typing':
            getTypingStatus($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handlePost($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'send':
            sendMessage($pdo, $input);
            break;
        case 'start_conversation':
            startConversation($pdo, $input);
            break;
        case 'typing':
            updateTypingStatus($pdo, $input);
            break;
        case 'mark_read':
            markMessagesRead($pdo, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function startConversation($pdo, $input) {
    $user_name = $input['user_name'] ?? '';
    $user_email = $input['user_email'] ?? '';
    $user_id = $input['user_id'] ?? null;
    $session_id = $input['session_id'] ?? session_id();
    
    if (empty($user_name) || empty($user_email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and email are required']);
        return;
    }
    
    try {
        // For logged-in users, check by user_id first, then email
        // For guest users, check by email and session
        if ($user_id) {
            $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE user_id = ? AND status != 'closed' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$user_id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE user_email = ? AND session_id = ? AND status != 'closed'");
            $stmt->execute([$user_email, $session_id]);
        }
        
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation) {
            // Create new conversation with unique session per user
            $unique_session = $session_id . '_' . md5($user_email . time() . ($user_id ?? ''));
            $stmt = $pdo->prepare("INSERT INTO chat_conversations (user_id, user_name, user_email, session_id, status, last_message_at) VALUES (?, ?, ?, ?, 'active', NOW())");
            $stmt->execute([$user_id, $user_name, $user_email, $unique_session]);
            $conversation_id = $pdo->lastInsertId();
            
            // Send welcome message
            $welcome_msg = "Hello " . $user_name . "! Welcome to our live chat. How can we help you today?";
            $stmt = $pdo->prepare("INSERT INTO chat_messages (conversation_id, sender_type, sender_name, message, message_type) VALUES (?, 'system', 'System', ?, 'system')");
            $stmt->execute([$conversation_id, $welcome_msg]);
            
            $session_id = $unique_session;
        } else {
            $conversation_id = $conversation['id'];
            $session_id = $conversation['session_id'];
            
            // Update last activity and user info (in case name changed)
            $stmt = $pdo->prepare("UPDATE chat_conversations SET user_name = ?, last_message_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$user_name, $conversation_id]);
        }
        
        echo json_encode([
            'success' => true,
            'conversation_id' => $conversation_id,
            'session_id' => $session_id
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to start conversation: ' . $e->getMessage()]);
    }
}

function sendMessage($pdo, $input) {
    $conversation_id = $input['conversation_id'] ?? 0;
    $message = trim($input['message'] ?? '');
    $sender_type = $input['sender_type'] ?? 'user';
    $sender_name = $input['sender_name'] ?? 'User';
    
    if (empty($message) || !$conversation_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Message and conversation ID are required']);
        return;
    }
    
    try {
        // Insert message
        $stmt = $pdo->prepare("INSERT INTO chat_messages (conversation_id, sender_type, sender_name, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$conversation_id, $sender_type, $sender_name, $message]);
        $message_id = $pdo->lastInsertId();
        
        // Update conversation last message time
        $stmt = $pdo->prepare("UPDATE chat_conversations SET last_message_at = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$conversation_id]);
        
        // Create notification for user messages only
        if ($sender_type === 'user') {
            // Get conversation details for notification
            $stmt = $pdo->prepare("SELECT user_name, user_email FROM chat_conversations WHERE id = ?");
            $stmt->execute([$conversation_id]);
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($conversation) {
                // Create notification
                $notificationMessage = "New message from {$conversation['user_name']} ({$conversation['user_email']}): " . substr($message, 0, 100) . (strlen($message) > 100 ? '...' : '');
                
                $notificationData = [
                    'title' => 'New Live Chat Message',
                    'message' => $notificationMessage,
                    'type' => 'user_activity',
                    'metadata' => json_encode([
                        'conversation_id' => $conversation_id,
                        'message_id' => $message_id,
                        'sender_name' => $sender_name,
                        'sender_type' => $sender_type,
                        'user_name' => $conversation['user_name'],
                        'user_email' => $conversation['user_email'],
                        'message_preview' => substr($message, 0, 50) . (strlen($message) > 50 ? '...' : '')
                    ]),
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $stmt = $pdo->prepare("INSERT INTO notifications (title, message, type, metadata, created_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $notificationData['title'],
                    $notificationData['message'],
                    $notificationData['type'],
                    $notificationData['metadata'],
                    $notificationData['created_at']
                ]);
            }
        }
        
        echo json_encode(['success' => true, 'message_id' => $message_id]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message']);
    }
}

function getMessages($pdo) {
    $conversation_id = $_GET['conversation_id'] ?? 0;
    $last_id = $_GET['last_id'] ?? 0;
    
    if (!$conversation_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Conversation ID is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE conversation_id = ? AND id > ? ORDER BY created_at ASC");
        $stmt->execute([$conversation_id, $last_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch messages']);
    }
}

function getConversations($pdo) {
    // Admin only - check if user is logged in as admin
    if (!isset($_SESSION['username'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    try {
        $stmt = $pdo->query("
            SELECT c.*, 
                   COUNT(m.id) as message_count,
                   COUNT(CASE WHEN m.is_read = 0 AND m.sender_type = 'user' THEN 1 END) as unread_count,
                   MAX(m.created_at) as last_message_time
            FROM chat_conversations c 
            LEFT JOIN chat_messages m ON c.id = m.conversation_id 
            GROUP BY c.id 
            ORDER BY c.last_message_at DESC, c.created_at DESC
        ");
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'conversations' => $conversations]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch conversations']);
    }
}

function updateTypingStatus($pdo, $input) {
    $conversation_id = $input['conversation_id'] ?? 0;
    $user_type = $input['user_type'] ?? 'user';
    $user_name = $input['user_name'] ?? 'User';
    $is_typing = $input['is_typing'] ?? false;
    
    if (!$conversation_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Conversation ID is required']);
        return;
    }
    
    try {
        if ($is_typing) {
            $stmt = $pdo->prepare("INSERT INTO chat_typing_indicators (conversation_id, user_type, user_name, is_typing) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE is_typing = 1, updated_at = NOW()");
            $stmt->execute([$conversation_id, $user_type, $user_name]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM chat_typing_indicators WHERE conversation_id = ? AND user_type = ?");
            $stmt->execute([$conversation_id, $user_type]);
        }
        
        echo json_encode(['success' => true]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update typing status']);
    }
}

function getTypingStatus($pdo) {
    $conversation_id = $_GET['conversation_id'] ?? 0;
    
    if (!$conversation_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Conversation ID is required']);
        return;
    }
    
    try {
        // Clean up old typing indicators (older than 10 seconds)
        $stmt = $pdo->prepare("DELETE FROM chat_typing_indicators WHERE updated_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)");
        $stmt->execute();
        
        $stmt = $pdo->prepare("SELECT * FROM chat_typing_indicators WHERE conversation_id = ?");
        $stmt->execute([$conversation_id]);
        $typing = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'typing' => $typing]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch typing status']);
    }
}

function markMessagesRead($pdo, $input) {
    $conversation_id = $input['conversation_id'] ?? 0;
    $sender_type = $input['sender_type'] ?? 'user';
    
    if (!$conversation_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Conversation ID is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND sender_type = ?");
        $stmt->execute([$conversation_id, $sender_type]);
        
        echo json_encode(['success' => true]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to mark messages as read']);
    }
}
?>
