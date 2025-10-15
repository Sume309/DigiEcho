-- Create contact_messages table for dynamic contact form management
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('new','read','replied','archived') NOT NULL DEFAULT 'new',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `admin_notes` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `email` (`email`),
  KEY `created_at` (`created_at`),
  KEY `subject` (`subject`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
