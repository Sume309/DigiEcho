<?php
/**
 * Enhanced Product Management Migration Script
 * Adds missing columns and creates additional tables for comprehensive product management
 */

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

$db = new MysqliDb(
    settings()['hostname'], 
    settings()['user'], 
    settings()['password'], 
    settings()['database']
);

echo "<h2>Enhanced Product Management Migration</h2>";
echo "<p>Setting up comprehensive product management system...</p>";

try {
    // Step 1: Enhance products table
    echo "<h3>1. Enhancing Products Table</h3>";
    
    $enhanceProductsColumns = [
        'meta_title' => 'VARCHAR(255) DEFAULT NULL COMMENT "SEO meta title"',
        'meta_description' => 'TEXT DEFAULT NULL COMMENT "SEO meta description"',
        'meta_keywords' => 'TEXT DEFAULT NULL COMMENT "SEO meta keywords"',
        'discount_price' => 'DECIMAL(10,2) DEFAULT NULL COMMENT "Discounted selling price"',
        'discount_start_date' => 'DATE DEFAULT NULL COMMENT "Discount start date"',
        'discount_end_date' => 'DATE DEFAULT NULL COMMENT "Discount end date"',
        'is_featured' => 'TINYINT(1) DEFAULT 0 COMMENT "Featured product flag"',
        'sort_order' => 'INT(11) DEFAULT 0 COMMENT "Display sort order"',
        'views' => 'INT(11) DEFAULT 0 COMMENT "Product view count"',
        'sales_count' => 'INT(11) DEFAULT 0 COMMENT "Total sales count"',
        'rating_average' => 'DECIMAL(3,2) DEFAULT 0.00 COMMENT "Average rating (0-5)"',
        'rating_count' => 'INT(11) DEFAULT 0 COMMENT "Total ratings count"',
        'gallery_images' => 'TEXT DEFAULT NULL COMMENT "JSON array of additional images"',
        'tags' => 'TEXT DEFAULT NULL COMMENT "Product tags separated by commas"',
        'status' => 'ENUM("draft","active","inactive","out_of_stock") DEFAULT "draft" COMMENT "Product status"'
    ];
    
    $existingColumns = [];
    $result = $db->rawQuery("SHOW COLUMNS FROM products");
    foreach ($result as $column) {
        $existingColumns[] = $column['Field'];
    }
    
    foreach ($enhanceProductsColumns as $columnName => $columnDefinition) {
        if (!in_array($columnName, $existingColumns)) {
            $alterQuery = "ALTER TABLE products ADD COLUMN $columnName $columnDefinition";
            $db->rawQuery($alterQuery);
            echo "<p style='color: green;'>✅ Added column: $columnName</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Column $columnName already exists</p>";
        }
    }
    
    // Step 2: Create product reviews table
    echo "<h3>2. Creating Product Reviews Table</h3>";
    
    $createReviewsTable = "
    CREATE TABLE IF NOT EXISTS product_reviews (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        product_id INT(11) NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        customer_email VARCHAR(150) NOT NULL,
        rating TINYINT(1) NOT NULL COMMENT 'Rating 1-5',
        title VARCHAR(200) DEFAULT NULL,
        review_text TEXT DEFAULT NULL,
        is_approved TINYINT(1) DEFAULT 0,
        is_featured TINYINT(1) DEFAULT 0,
        helpful_votes INT(11) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX idx_product_reviews (product_id, is_approved),
        INDEX idx_rating (rating),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $db->rawQuery($createReviewsTable);
    echo "<p style='color: green;'>✅ Product reviews table created/verified</p>";
    
    // Step 3: Create product discounts/offers table
    echo "<h3>3. Creating Product Discounts Table</h3>";
    
    $createDiscountsTable = "
    CREATE TABLE IF NOT EXISTS product_discounts (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT DEFAULT NULL,
        discount_type ENUM('percentage','fixed_amount','buy_x_get_y') NOT NULL DEFAULT 'percentage',
        discount_value DECIMAL(10,2) NOT NULL,
        min_quantity INT(11) DEFAULT 1,
        max_quantity INT(11) DEFAULT NULL,
        min_order_amount DECIMAL(10,2) DEFAULT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        usage_limit INT(11) DEFAULT NULL,
        usage_count INT(11) DEFAULT 0,
        applies_to ENUM('all_products','specific_products','categories','brands') DEFAULT 'all_products',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_active_dates (is_active, start_date, end_date),
        INDEX idx_applies_to (applies_to)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $db->rawQuery($createDiscountsTable);
    echo "<p style='color: green;'>✅ Product discounts table created/verified</p>";
    
    // Step 4: Create product discount relations table
    echo "<h3>4. Creating Product Discount Relations Table</h3>";
    
    $createDiscountRelationsTable = "
    CREATE TABLE IF NOT EXISTS product_discount_relations (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        discount_id INT(11) NOT NULL,
        product_id INT(11) DEFAULT NULL,
        category_id INT(11) DEFAULT NULL,
        brand_id INT(11) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (discount_id) REFERENCES product_discounts(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
        INDEX idx_discount_product (discount_id, product_id),
        INDEX idx_discount_category (discount_id, category_id),
        INDEX idx_discount_brand (discount_id, brand_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $db->rawQuery($createDiscountRelationsTable);
    echo "<p style='color: green;'>✅ Product discount relations table created/verified</p>";
    
    // Step 5: Create product inventory logs table
    echo "<h3>5. Creating Product Inventory Logs Table</h3>";
    
    $createInventoryLogsTable = "
    CREATE TABLE IF NOT EXISTS product_inventory_logs (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        product_id INT(11) NOT NULL,
        action_type ENUM('add','remove','adjust','sale','return','damaged','expired') NOT NULL,
        quantity_before INT(11) NOT NULL,
        quantity_changed INT(11) NOT NULL,
        quantity_after INT(11) NOT NULL,
        reason VARCHAR(255) DEFAULT NULL,
        reference_id INT(11) DEFAULT NULL COMMENT 'Order ID, Purchase ID, etc.',
        created_by INT(11) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX idx_product_logs (product_id, created_at),
        INDEX idx_action_type (action_type),
        INDEX idx_reference (reference_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $db->rawQuery($createInventoryLogsTable);
    echo "<p style='color: green;'>✅ Product inventory logs table created/verified</p>";
    
    // Step 6: Create product variants table (for size, color, etc.)
    echo "<h3>6. Creating Product Variants Table</h3>";
    
    $createVariantsTable = "
    CREATE TABLE IF NOT EXISTS product_variants (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        product_id INT(11) NOT NULL,
        variant_name VARCHAR(100) NOT NULL COMMENT 'e.g., Size, Color, Material',
        variant_value VARCHAR(100) NOT NULL COMMENT 'e.g., XL, Red, Cotton',
        sku_suffix VARCHAR(50) DEFAULT NULL COMMENT 'Additional SKU identifier',
        price_adjustment DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Price difference from base product',
        stock_quantity INT(11) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT(11) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX idx_product_variants (product_id, is_active),
        INDEX idx_variant_name (variant_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $db->rawQuery($createVariantsTable);
    echo "<p style='color: green;'>✅ Product variants table created/verified</p>";
    
    // Step 7: Update existing products to set default status
    echo "<h3>7. Updating Existing Product Data</h3>";
    
    $updateExistingProducts = "
    UPDATE products 
    SET status = CASE 
        WHEN is_active = 1 AND stock_quantity > 0 THEN 'active'
        WHEN is_active = 1 AND stock_quantity = 0 THEN 'out_of_stock'
        ELSE 'inactive'
    END 
    WHERE status = 'draft' OR status IS NULL";
    
    $db->rawQuery($updateExistingProducts);
    echo "<p style='color: green;'>✅ Updated existing product statuses</p>";
    
    // Step 8: Create indexes for better performance
    echo "<h3>8. Creating Performance Indexes</h3>";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_products_status ON products(status, is_active)",
        "CREATE INDEX IF NOT EXISTS idx_products_stock ON products(stock_quantity, min_stock_level)",
        "CREATE INDEX IF NOT EXISTS idx_products_featured ON products(is_featured, status)",
        "CREATE INDEX IF NOT EXISTS idx_products_category_brand ON products(category_id, brand, status)",
        "CREATE INDEX IF NOT EXISTS idx_products_price ON products(selling_price, discount_price)",
        "CREATE INDEX IF NOT EXISTS idx_products_created ON products(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_products_views_sales ON products(views, sales_count)"
    ];
    
    foreach ($indexes as $indexQuery) {
        try {
            $db->rawQuery($indexQuery);
            echo "<p style='color: green;'>✅ Index created successfully</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Index already exists or error: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>9. Final Table Structure Verification</h3>";
    
    // Show final products table structure
    $structure = $db->rawQuery("SHOW COLUMNS FROM products");
    echo "<h4>Enhanced Products Table Structure:</h4>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($structure as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Migration Completed Successfully!</h3>";
    echo "<p><strong>Tables Created/Enhanced:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Products table enhanced with 15 new columns</li>";
    echo "<li>✅ Product reviews table created</li>";
    echo "<li>✅ Product discounts table created</li>";
    echo "<li>✅ Product discount relations table created</li>";
    echo "<li>✅ Product inventory logs table created</li>";
    echo "<li>✅ Product variants table created</li>";
    echo "<li>✅ Performance indexes created</li>";
    echo "</ul>";
    echo "<p><strong>Your enhanced product management system is now ready!</strong></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Migration Error</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

$db->disconnect();
?>