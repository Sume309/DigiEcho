<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../src/settings.php';
require_once __DIR__ . '/../../src/db/MysqliDb.php';
require_once __DIR__ . '/../../src/auth/admin.php';

use App\auth\Admin;

// Set proper headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Check authentication
if (!Admin::Check()) {
    echo json_encode([
        'success' => false, 
        'message' => 'Authentication required',
        'redirect' => 'auto-login.php'
    ]);
    exit;
}

try {
    $db = new MysqliDb(
        settings()['hostname'], 
        settings()['user'], 
        settings()['password'], 
        settings()['database']
    );
    
    // Get comprehensive product statistics
    $stats = getProductStatistics($db);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'timestamp' => time(),
        'formatted_time' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log('Product Stats API Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to fetch statistics: ' . $e->getMessage(),
        'stats' => getDefaultStats()
    ]);
}

function getProductStatistics($db) {
    try {
        // Basic counts using rawQuery for reliability
        $totalProducts = intval($db->rawQueryOne('SELECT COUNT(*) as count FROM products')['count'] ?? 0);
        $activeProducts = intval($db->rawQueryOne('SELECT COUNT(*) as count FROM products WHERE status = "active"')['count'] ?? 0);
        $inactiveProducts = intval($db->rawQueryOne('SELECT COUNT(*) as count FROM products WHERE status = "inactive"')['count'] ?? 0);
        $draftProducts = intval($db->rawQueryOne('SELECT COUNT(*) as count FROM products WHERE status = "draft"')['count'] ?? 0);
        
        // Stock-related counts
        $outOfStockProducts = intval($db->rawQueryOne('SELECT COUNT(*) as count FROM products WHERE status = "out_of_stock" OR stock_quantity = 0')['count'] ?? 0);
        $lowStockProducts = intval($db->rawQueryOne('SELECT COUNT(*) as count FROM products WHERE stock_quantity <= min_stock_level AND stock_quantity > 0')['count'] ?? 0);
        $inStockProducts = intval($db->rawQueryOne('SELECT COUNT(*) as count FROM products WHERE stock_quantity > min_stock_level AND status = "active"')['count'] ?? 0);
        
        // Feature-based counts
        $hotItems = intval($db->rawQueryOne('SELECT COUNT(*) as count FROM products WHERE is_hot_item = 1')['count'] ?? 0);
        $featuredProducts = intval($db->rawQueryOne('SELECT COUNT(*) as count FROM products WHERE is_featured = 1')['count'] ?? 0);
        $discountedProducts = intval($db->rawQueryOne('SELECT COUNT(*) as count FROM products WHERE discount_price IS NOT NULL AND discount_price > 0')['count'] ?? 0);
        
        // Calculate combined inactive (inactive + draft)
        $totalInactive = $inactiveProducts + $draftProducts;
        
        // Calculate percentages
        $percentages = [];
        if ($totalProducts > 0) {
            $percentages = [
                'active' => round(($activeProducts / $totalProducts) * 100, 1),
                'inactive' => round(($totalInactive / $totalProducts) * 100, 1),
                'out_of_stock' => round(($outOfStockProducts / $totalProducts) * 100, 1),
                'low_stock' => round(($lowStockProducts / $totalProducts) * 100, 1),
                'hot_items' => round(($hotItems / $totalProducts) * 100, 1),
                'featured' => round(($featuredProducts / $totalProducts) * 100, 1)
            ];
        }
        
        // Additional analytics using rawQuery
        $avgPriceResult = $db->rawQueryOne('SELECT AVG(selling_price) as avg_price FROM products WHERE status = "active"');
        $avgPrice = floatval($avgPriceResult['avg_price'] ?? 0);
        
        $totalValueResult = $db->rawQueryOne('SELECT SUM(selling_price * stock_quantity) as total_value FROM products WHERE status = "active"');
        $totalValue = floatval($totalValueResult['total_value'] ?? 0);
        
        $categoriesWithProducts = intval($db->rawQueryOne('SELECT COUNT(DISTINCT category_id) as count FROM products WHERE category_id IS NOT NULL')['count'] ?? 0);
        $brandsWithProducts = intval($db->rawQueryOne('SELECT COUNT(DISTINCT brand) as count FROM products WHERE brand IS NOT NULL')['count'] ?? 0);
        
        return [
            // Main counters
            'total' => $totalProducts,
            'active' => $activeProducts,
            'inactive' => $totalInactive,
            'out_of_stock' => $outOfStockProducts,
            'low_stock' => $lowStockProducts,
            'in_stock' => $inStockProducts,
            'hot_items' => $hotItems,
            'featured' => $featuredProducts,
            'discounted' => $discountedProducts,
            'draft' => $draftProducts,
            
            // Breakdown
            'breakdown' => [
                'status' => [
                    'active' => $activeProducts,
                    'inactive' => $inactiveProducts,
                    'draft' => $draftProducts,
                    'out_of_stock' => $outOfStockProducts
                ],
                'stock' => [
                    'in_stock' => $inStockProducts,
                    'low_stock' => $lowStockProducts,
                    'out_of_stock' => $outOfStockProducts
                ]
            ],
            
            // Percentages
            'percentages' => $percentages,
            
            // Analytics
            'analytics' => [
                'avg_price' => round($avgPrice, 2),
                'total_value' => round($totalValue, 2),
                'categories_with_products' => $categoriesWithProducts,
                'brands_with_products' => $brandsWithProducts
            ],
            
            // Metadata
            'last_updated' => date('Y-m-d H:i:s'),
            'data_source' => 'real-time'
        ];
        
    } catch (Exception $e) {
        error_log('Statistics calculation error: ' . $e->getMessage());
        return getDefaultStats();
    }
}

function getDefaultStats() {
    return [
        'total' => 0,
        'active' => 0,
        'inactive' => 0,
        'out_of_stock' => 0,
        'low_stock' => 0,
        'in_stock' => 0,
        'hot_items' => 0,
        'featured' => 0,
        'discounted' => 0,
        'draft' => 0,
        'breakdown' => [
            'status' => ['active' => 0, 'inactive' => 0, 'draft' => 0, 'out_of_stock' => 0],
            'stock' => ['in_stock' => 0, 'low_stock' => 0, 'out_of_stock' => 0]
        ],
        'percentages' => [],
        'analytics' => [
            'avg_price' => 0,
            'total_value' => 0,
            'categories_with_products' => 0,
            'brands_with_products' => 0
        ],
        'last_updated' => date('Y-m-d H:i:s'),
        'data_source' => 'default'
    ];
}
?>