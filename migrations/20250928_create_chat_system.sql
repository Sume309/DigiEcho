-- Create chat_conversations table for managing chat sessions
CREATE TABLE IF NOT EXISTS `chat_conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `status` enum('active','closed','pending') NOT NULL DEFAULT 'active',
  `assigned_admin` int(11) DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `assigned_admin` (`assigned_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create chat_messages table for storing individual messages
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `sender_type` enum('user','admin') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_name` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','image','file','system') NOT NULL DEFAULT 'text',
  `attachment_path` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `sender_type` (`sender_type`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create chat_typing_indicators table for real-time typing status
CREATE TABLE IF NOT EXISTS `chat_typing_indicators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `user_type` enum('user','admin') NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `is_typing` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversation_user` (`conversation_id`, `user_type`),
  KEY `conversation_id` (`conversation_id`),
  FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
