-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'new_order, low_stock, system, etc.',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` text DEFAULT NULL COMMENT 'JSON encoded data with additional information',
  `created_at` datetime NOT NULL,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
