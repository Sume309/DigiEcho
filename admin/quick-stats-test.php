<?php
// Quick database statistics test
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/db/MysqliDb.php';

try {
    $db = new MysqliDb(
        settings()['hostname'], 
        settings()['user'], 
        settings()['password'], 
        settings()['database']
    );
    
    // Test basic queries using raw queries (more reliable)
    $totalCategories = $db->rawQueryOne('SELECT COUNT(*) as count FROM categories')['count'] ?? 0;
    $activeCategories = $db->rawQueryOne('SELECT COUNT(*) as count FROM categories WHERE is_active = 1')['count'] ?? 0;
    $inactiveCategories = $db->rawQueryOne('SELECT COUNT(*) as count FROM categories WHERE is_active = 0')['count'] ?? 0;
    $categoriesWithProducts = $db->rawQueryOne('SELECT COUNT(DISTINCT category_id) as count FROM products WHERE category_id IS NOT NULL')['count'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_categories' => $totalCategories,
            'active_categories' => $activeCategories,
            'inactive_categories' => $inactiveCategories,
            'categories_with_products' => $categoriesWithProducts,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>