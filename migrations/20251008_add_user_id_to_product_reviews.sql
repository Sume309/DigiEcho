-- Migration: Add user_id column to product_reviews table
-- Date: 2025-10-08
-- Description: Add user_id column to link reviews to registered users

-- Add user_id column to product_reviews table
ALTER TABLE `product_reviews`
ADD COLUMN IF NOT EXISTS `user_id` int(11) DEFAULT NULL COMMENT 'User ID if review is from logged-in user',
ADD KEY `idx_user_id` (`user_id`);

-- Add foreign key constraint (optional - uncomment if you want to enforce referential integrity)
-- ALTER TABLE `product_reviews`
-- ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
