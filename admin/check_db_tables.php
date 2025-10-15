<?php
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

try {
    $db = new MysqliDb(settings()['hostname'], settings()['user'], settings()['password'], settings()['database']);
    
    echo "Checking for discount-related tables:\n";
    
    // Check for product_discounts table
    $tables = $db->rawQuery('SHOW TABLES LIKE "%discount%"');
    if ($tables) {
        foreach($tables as $table) {
            $tableName = array_values($table)[0];
            echo "✓ Found table: $tableName\n";
            
            // Show table structure
            $structure = $db->rawQuery("DESCRIBE $tableName");
            echo "  Structure:\n";
            foreach($structure as $column) {
                echo "    - {$column['Field']} ({$column['Type']})\n";
            }
            echo "\n";
        }
    } else {
        echo "❌ No discount tables found!\n";
        echo "Creating required tables...\n";
        
        // Create product_discounts table
        $createDiscountsTable = "
        CREATE TABLE IF NOT EXISTS `product_discounts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text,
            `discount_type` enum('percentage','fixed_amount','buy_x_get_y') NOT NULL DEFAULT 'percentage',
            `discount_value` decimal(10,2) NOT NULL,
            `min_quantity` int(11) DEFAULT 1,
            `max_quantity` int(11) DEFAULT NULL,
            `min_order_amount` decimal(10,2) DEFAULT NULL,
            `start_date` date NOT NULL,
            `end_date` date NOT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `usage_limit` int(11) DEFAULT NULL,
            `usage_count` int(11) DEFAULT 0,
            `applies_to` enum('all_products','specific_products','categories','brands') DEFAULT 'all_products',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_active_dates` (`is_active`,`start_date`,`end_date`),
            KEY `idx_applies_to` (`applies_to`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        // Create product_discount_relations table
        $createRelationsTable = "
        CREATE TABLE IF NOT EXISTS `product_discount_relations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `discount_id` int(11) NOT NULL,
            `product_id` int(11) DEFAULT NULL,
            `category_id` int(11) DEFAULT NULL,
            `brand_id` int(11) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_discount_id` (`discount_id`),
            KEY `idx_product_id` (`product_id`),
            KEY `idx_category_id` (`category_id`),
            KEY `idx_brand_id` (`brand_id`),
            FOREIGN KEY (`discount_id`) REFERENCES `product_discounts`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        if ($db->rawQuery($createDiscountsTable)) {
            echo "✓ Created product_discounts table\n";
        } else {
            echo "❌ Failed to create product_discounts table\n";
        }
        
        if ($db->rawQuery($createRelationsTable)) {
            echo "✓ Created product_discount_relations table\n";
        } else {
            echo "❌ Failed to create product_discount_relations table\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
