<?php
/**
 * Emergency Database Fix for Profile Settings
 * This script adds missing columns to the user_profiles table
 */

// Include the database connection
require_once __DIR__ . '/vendor/autoload.php';

echo "<h2>Family-Haat-Bazar Database Fix</h2>";
echo "<p>Adding missing columns to user_profiles table...</p>";

try {
    $db = new MysqliDb();
    
    // Check if columns already exist
    $result = $db->rawQuery("SHOW COLUMNS FROM user_profiles LIKE 'job_title'");
    
    if (!empty($result)) {
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
        echo "<strong>✓ Migration already completed!</strong><br>";
        echo "All required columns are already present in the user_profiles table.";
        echo "</div>";
        echo "<p><a href='admin/profile-settings.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Profile Settings</a></p>";
        exit;
    }
    
    echo "<p>Executing database migration...</p>";
    
    // Start transaction
    $db->startTransaction();
    
    // Add all missing columns
    $alterQueries = [
        "ALTER TABLE `user_profiles` ADD COLUMN `job_title` VARCHAR(100) DEFAULT NULL COMMENT 'Job title/position'",
        "ALTER TABLE `user_profiles` ADD COLUMN `department` VARCHAR(100) DEFAULT NULL COMMENT 'Department/division'",
        "ALTER TABLE `user_profiles` ADD COLUMN `bio` TEXT DEFAULT NULL COMMENT 'Biography/description'",
        "ALTER TABLE `user_profiles` ADD COLUMN `address` VARCHAR(255) DEFAULT NULL COMMENT 'Street address'",
        "ALTER TABLE `user_profiles` ADD COLUMN `city` VARCHAR(100) DEFAULT NULL COMMENT 'City'",
        "ALTER TABLE `user_profiles` ADD COLUMN `state` VARCHAR(100) DEFAULT NULL COMMENT 'State/province'",
        "ALTER TABLE `user_profiles` ADD COLUMN `zip_code` VARCHAR(20) DEFAULT NULL COMMENT 'ZIP/postal code'",
        "ALTER TABLE `user_profiles` ADD COLUMN `country` VARCHAR(100) DEFAULT 'Bangladesh' COMMENT 'Country'",
        "ALTER TABLE `user_profiles` ADD COLUMN `website` VARCHAR(255) DEFAULT NULL COMMENT 'Personal/company website'",
        "ALTER TABLE `user_profiles` ADD COLUMN `linkedin` VARCHAR(255) DEFAULT NULL COMMENT 'LinkedIn profile'",
        "ALTER TABLE `user_profiles` ADD COLUMN `twitter` VARCHAR(255) DEFAULT NULL COMMENT 'Twitter handle'",
        "ALTER TABLE `user_profiles` ADD COLUMN `facebook` VARCHAR(255) DEFAULT NULL COMMENT 'Facebook profile'",
        "ALTER TABLE `user_profiles` ADD COLUMN `timezone` VARCHAR(50) DEFAULT 'Asia/Dhaka' COMMENT 'User timezone'",
        "ALTER TABLE `user_profiles` ADD COLUMN `language` VARCHAR(10) DEFAULT 'en' COMMENT 'Preferred language'",
        "ALTER TABLE `user_profiles` ADD COLUMN `email_notifications` TINYINT(1) DEFAULT 1 COMMENT 'Email notifications enabled'",
        "ALTER TABLE `user_profiles` ADD COLUMN `two_factor_auth` TINYINT(1) DEFAULT 0 COMMENT 'Two-factor authentication enabled'"
    ];
    
    $successCount = 0;
    foreach ($alterQueries as $query) {
        $result = $db->rawQuery($query);
        if ($result !== false) {
            $successCount++;
            echo "<p style='color: green;'>✓ Added column: " . preg_replace('/.*ADD COLUMN `([^`]+)`.*/', '$1', $query) . "</p>";
        } else {
            throw new Exception("Failed to execute: " . $query . " - " . $db->getLastError());
        }
    }
    
    // Update existing records with default values
    $updateQuery = "UPDATE `user_profiles` SET 
        `timezone` = 'Asia/Dhaka',
        `language` = 'en',
        `email_notifications` = 1,
        `two_factor_auth` = 0,
        `country` = 'Bangladesh'
    WHERE `timezone` IS NULL OR `language` IS NULL OR `email_notifications` IS NULL OR `two_factor_auth` IS NULL";
    
    $db->rawQuery($updateQuery);
    
    // Add indexes for better performance
    $indexQueries = [
        "CREATE INDEX `idx_user_profiles_timezone` ON `user_profiles` (`timezone`)",
        "CREATE INDEX `idx_user_profiles_language` ON `user_profiles` (`language`)",
        "CREATE INDEX `idx_user_profiles_country` ON `user_profiles` (`country`)"
    ];
    
    foreach ($indexQueries as $query) {
        $db->rawQuery($query);
    }
    
    $db->commit();
    
    echo "<div style='color: green; padding: 15px; border: 2px solid green; margin: 20px 0; background: #f0fff0;'>";
    echo "<h3>✓ Migration Completed Successfully!</h3>";
    echo "<p>Added <strong>$successCount</strong> new columns to the user_profiles table.</p>";
    echo "<p>The profile settings page should now work correctly.</p>";
    echo "</div>";
    
    echo "<p><a href='admin/profile-settings.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Profile Settings</a></p>";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    
    echo "<div style='color: red; padding: 15px; border: 2px solid red; margin: 20px 0; background: #fff0f0;'>";
    echo "<h3>✗ Migration Failed!</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    line-height: 1.6;
}
h2 {
    color: #333;
    border-bottom: 2px solid #007cba;
    padding-bottom: 10px;
}
</style>
