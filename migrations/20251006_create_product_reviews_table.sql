-- Migration: Create product_reviews table
-- Date: 2025-10-06
-- Description: Create table for product reviews and ratings

-- Create product_reviews table if it doesn't exist
CREATE TABLE IF NOT EXISTS `product_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(150) NOT NULL,
  `rating` tinyint(1) NOT NULL COMMENT 'Rating 1-5',
  `title` varchar(200) DEFAULT NULL,
  `review_text` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `helpful_votes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_reviews` (`product_id`,`is_approved`),
  KEY `idx_rating` (`rating`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add rating columns to products table if they don't exist
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `rating_average` DECIMAL(3,2) DEFAULT 0.00 COMMENT 'Average rating from reviews',
ADD COLUMN IF NOT EXISTS `rating_count` INT DEFAULT 0 COMMENT 'Total number of approved reviews';

-- Create index on rating columns for better performance
CREATE INDEX IF NOT EXISTS `idx_product_rating` ON `products` (`rating_average`, `rating_count`);
