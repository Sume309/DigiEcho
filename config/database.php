<?php
/**
 * Database Configuration for Family-Haat-Bazar
 * Uses the existing settings system for database credentials
 */

// Include the vendor autoload to access settings
require_once __DIR__ . '/../vendor/autoload.php';

try {
    // Get database settings from the existing settings system
    $db_settings = settings();
    
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=" . $db_settings['hostname'] . ";dbname=" . $db_settings['database'] . ";charset=utf8mb4",
        $db_settings['user'],
        $db_settings['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    throw new Exception("Database connection failed. Please check your configuration.");
}
?>
