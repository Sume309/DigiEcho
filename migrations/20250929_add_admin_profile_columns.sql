-- Migration: Add admin profile management columns to user_profiles table
-- Date: 2025-09-29
-- Description: Adds columns needed for comprehensive admin profile management

-- Add new columns for admin profile settings
ALTER TABLE `user_profiles` 
ADD COLUMN `job_title` VARCHAR(100) DEFAULT NULL COMMENT 'Job title/position',
ADD COLUMN `department` VARCHAR(100) DEFAULT NULL COMMENT 'Department/division',
ADD COLUMN `bio` TEXT DEFAULT NULL COMMENT 'Biography/description',
ADD COLUMN `address` VARCHAR(255) DEFAULT NULL COMMENT 'Street address',
ADD COLUMN `city` VARCHAR(100) DEFAULT NULL COMMENT 'City',
ADD COLUMN `state` VARCHAR(100) DEFAULT NULL COMMENT 'State/province',
ADD COLUMN `zip_code` VARCHAR(20) DEFAULT NULL COMMENT 'ZIP/postal code',
ADD COLUMN `country` VARCHAR(100) DEFAULT 'Bangladesh' COMMENT 'Country',
ADD COLUMN `website` VARCHAR(255) DEFAULT NULL COMMENT 'Personal/company website',
ADD COLUMN `linkedin` VARCHAR(255) DEFAULT NULL COMMENT 'LinkedIn profile',
ADD COLUMN `twitter` VARCHAR(255) DEFAULT NULL COMMENT 'Twitter handle',
ADD COLUMN `facebook` VARCHAR(255) DEFAULT NULL COMMENT 'Facebook profile',
ADD COLUMN `timezone` VARCHAR(50) DEFAULT 'Asia/Dhaka' COMMENT 'User timezone',
ADD COLUMN `language` VARCHAR(10) DEFAULT 'en' COMMENT 'Preferred language',
ADD COLUMN `email_notifications` TINYINT(1) DEFAULT 1 COMMENT 'Email notifications enabled',
ADD COLUMN `two_factor_auth` TINYINT(1) DEFAULT 0 COMMENT 'Two-factor authentication enabled';

-- Update existing records to have default values
UPDATE `user_profiles` SET 
    `timezone` = 'Asia/Dhaka',
    `language` = 'en',
    `email_notifications` = 1,
    `two_factor_auth` = 0,
    `country` = 'Bangladesh'
WHERE `timezone` IS NULL OR `language` IS NULL OR `email_notifications` IS NULL OR `two_factor_auth` IS NULL OR `country` IS NULL;

-- Add indexes for better performance
CREATE INDEX `idx_user_profiles_timezone` ON `user_profiles` (`timezone`);
CREATE INDEX `idx_user_profiles_language` ON `user_profiles` (`language`);
CREATE INDEX `idx_user_profiles_country` ON `user_profiles` (`country`);

-- Migration completed successfully
