<?php
session_start();
require_once '../config/database.php';
require_once '../src/ReportManager.php';

// Check admin authentication using the same method as other admin pages
require_once __DIR__ . '/../vendor/autoload.php';
use App\auth\Admin;

if(!Admin::Check()){
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$reportManager = new ReportManager($pdo);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'dashboard_metrics':
            echo json_encode(getDashboardMetrics($pdo));
            break;
            
        case 'category_reports':
            $category_id = $_GET['category_id'] ?? null;
            echo json_encode(getCategoryReports($reportManager, $category_id));
            break;
            
        case 'execute_report':
            $template_id = $_POST['template_id'] ?? null;
            $parameters = json_decode($_POST['parameters'] ?? '{}', true);
            $filters = json_decode($_POST['filters'] ?? '{}', true);
            echo json_encode(executeReport($reportManager, $template_id, $parameters, $filters, $_SESSION['userid']));
            break;
            
        case 'paginated_report':
            $template_id = $_GET['template_id'] ?? null;
            $page = (int)($_GET['page'] ?? 1);
            $per_page = (int)($_GET['per_page'] ?? 50);
            $parameters = json_decode($_GET['parameters'] ?? '{}', true);
            $filters = json_decode($_GET['filters'] ?? '{}', true);
            echo json_encode(getPaginatedReport($reportManager, $template_id, $page, $per_page, $parameters, $filters, $_SESSION['userid']));
            break;
            
        case 'export_report':
            $template_id = $_POST['template_id'] ?? null;
            $format = $_POST['format'] ?? 'csv';
            $parameters = json_decode($_POST['parameters'] ?? '{}', true);
            $filters = json_decode($_POST['filters'] ?? '{}', true);
            echo json_encode(exportReport($reportManager, $template_id, $format, $parameters, $filters, $_SESSION['userid']));
            break;
            
        case 'search_reports':
            $query = $_GET['query'] ?? '';
            $category_id = $_GET['category_id'] ?? null;
            $page = (int)($_GET['page'] ?? 1);
            echo json_encode(searchReports($reportManager, $query, $category_id, $page, $_SESSION['userid']));
            break;
            
        case 'get_report_template':
            $template_id = $_GET['template_id'] ?? null;
            echo json_encode(getReportTemplate($reportManager, $template_id));
            break;
            
        case 'test_query':
            $query = $_POST['query'] ?? null;
            echo json_encode(testQuery($pdo, $query));
            break;
            
        case 'create_report':
            echo json_encode(createReport($pdo, $_POST));
            break;
            
        case 'add_favorite':
            $template_id = $_POST['template_id'] ?? null;
            echo json_encode(addToFavorites($pdo, $template_id, $_SESSION['userid']));
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getDashboardMetrics($pdo) {
    $current_month = date('Y-m-01');
    $last_month = date('Y-m-01', strtotime('-1 month'));
    
    // Total Sales This Month
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_sales FROM orders WHERE created_at >= ? AND status != 'cancelled'");
    $stmt->execute([$current_month]);
    $total_sales = $stmt->fetchColumn();
    
    // Total Orders This Month
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE created_at >= ? AND status != 'cancelled'");
    $stmt->execute([$current_month]);
    $total_orders = $stmt->fetchColumn();
    
    // New Customers This Month
    $stmt = $pdo->prepare("SELECT COUNT(*) as new_customers FROM users WHERE created_at >= ? AND role = 'customer'");
    $stmt->execute([$current_month]);
    $new_customers = $stmt->fetchColumn();
    
    // Conversion Rate (orders/customers ratio)
    $conversion_rate = $new_customers > 0 ? $total_orders / $new_customers : 0;
    
    // Sales Trend (last 30 days)
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COALESCE(SUM(total_amount), 0) as sales 
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND status != 'cancelled'
        GROUP BY DATE(created_at) 
        ORDER BY date ASC
    ");
    $stmt->execute();
    $sales_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top Categories
    $stmt = $pdo->prepare("
        SELECT c.name, COALESCE(SUM(oi.price * oi.quantity), 0) as revenue
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= ? AND (o.status != 'cancelled' OR o.status IS NULL)
        GROUP BY c.id, c.name
        ORDER BY revenue DESC
        LIMIT 6
    ");
    $stmt->execute([$current_month]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'metrics' => [
            'total_sales' => $total_sales,
            'total_orders' => $total_orders,
            'new_customers' => $new_customers,
            'conversion_rate' => $conversion_rate
        ],
        'charts' => [
            'sales_trend' => [
                'labels' => array_column($sales_trend, 'date'),
                'data' => array_column($sales_trend, 'sales')
            ],
            'categories' => [
                'labels' => array_column($categories, 'name'),
                'data' => array_column($categories, 'revenue')
            ]
        ]
    ];
}

function getCategoryReports($reportManager, $category_id) {
    if (!$category_id) {
        throw new Exception('Category ID is required');
    }
    
    $reports = $reportManager->searchReports('', $category_id, $_SESSION['user_id'], 1, 100);
    
    return [
        'success' => true,
        'reports' => $reports['reports']
    ];
}

function executeReport($reportManager, $template_id, $parameters, $filters, $user_id = null) {
    if (!$template_id) {
        throw new Exception('Template ID is required');
    }
    
    $result = $reportManager->executeReport($template_id, $parameters, $filters, $_SESSION['user_id']);
    
    return [
        'success' => true,
        'data' => $result
    ];
}

function getPaginatedReport($reportManager, $template_id, $page, $per_page, $parameters, $filters) {
    if (!$template_id) {
        throw new Exception('Template ID is required');
    }
    
    $result = $reportManager->getPaginatedReport($template_id, $page, $per_page, $parameters, $filters, $_SESSION['user_id']);
    
    return [
        'success' => true,
        'data' => $result
    ];
}

function exportReport($reportManager, $template_id, $format, $parameters, $filters) {
    if (!$template_id) {
        throw new Exception('Template ID is required');
    }
    
    $result = $reportManager->exportReport($template_id, $format, $parameters, $filters, $_SESSION['user_id']);
    
    return [
        'success' => true,
        'export' => $result
    ];
}

function searchReports($reportManager, $query, $category_id, $page) {
    $result = $reportManager->searchReports($query, $category_id, $_SESSION['user_id'], $page, 20);
    
    return [
        'success' => true,
        'data' => $result
    ];
}

function getReportTemplate($reportManager, $template_id) {
    if (!$template_id) {
        throw new Exception('Template ID is required');
    }
    
    // This would need to be implemented in ReportManager
    $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM report_templates WHERE id = ? AND is_active = 1");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        throw new Exception('Report template not found');
    }
    
    return [
        'success' => true,
        'template' => $template
    ];
}

function testQuery($pdo, $query) {
    if (!$query) {
        throw new Exception('Query is required');
    }
    
    try {
        // Replace parameters with sample values for testing
        $test_query = str_replace('{{days}}', '30', $query);
        $test_query = str_replace('{{months}}', '12', $test_query);
        $test_query = str_replace('{{limit}}', '10', $test_query);
        $test_query = str_replace('{{threshold}}', '5', $test_query);
        
        // Add LIMIT to prevent large result sets
        if (stripos($test_query, 'LIMIT') === false) {
            $test_query .= ' LIMIT 100';
        }
        
        $stmt = $pdo->prepare($test_query);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $data,
            'row_count' => count($data)
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function createReport($pdo, $data) {
    try {
        // Validate required fields
        $required_fields = ['name', 'category_id', 'chart_type', 'query_template'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Generate slug from name
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $data['name']));
        
        // Process parameters
        $parameters = [];
        if (!empty($data['parameters_data'])) {
            $params_data = json_decode($data['parameters_data'], true);
            foreach ($params_data as $param) {
                if (!empty($param['name'])) {
                    $parameters[$param['name']] = [
                        'type' => $param['type'] ?? 'text',
                        'label' => $param['label'] ?? $param['name'],
                        'default' => $param['default'] ?? ''
                    ];
                }
            }
        }
        
        // Process columns
        $columns = [];
        if (!empty($data['columns_data'])) {
            $columns_data = json_decode($data['columns_data'], true);
            foreach ($columns_data as $col) {
                if (!empty($col['key'])) {
                    $columns[] = [
                        'key' => $col['key'],
                        'label' => $col['label'] ?? $col['key'],
                        'type' => $col['type'] ?? 'text',
                        'format' => $col['format'] ?? null
                    ];
                }
            }
        }
        
        // Insert report template
        $stmt = $pdo->prepare("
            INSERT INTO report_templates (
                category_id, name, slug, description, query_template, chart_type,
                chart_config, parameters, columns, refresh_interval, cache_duration,
                is_featured, created_by
            ) VALUES (
                :category_id, :name, :slug, :description, :query_template, :chart_type,
                :chart_config, :parameters, :columns, :refresh_interval, :cache_duration,
                :is_featured, :created_by
            )
        ");
        
        $stmt->execute([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? '',
            'query_template' => $data['query_template'],
            'chart_type' => $data['chart_type'],
            'chart_config' => json_encode(['responsive' => true, 'maintainAspectRatio' => false]),
            'parameters' => json_encode($parameters),
            'columns' => json_encode($columns),
            'refresh_interval' => (int)($data['refresh_interval'] ?? 30),
            'cache_duration' => 300,
            'is_featured' => isset($data['is_featured']) ? 1 : 0,
            'created_by' => $_SESSION['user_id']
        ]);
        
        $template_id = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'message' => 'Report created successfully',
            'template_id' => $template_id
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function addToFavorites($pdo, $template_id, $user_id) {
    if (!$template_id || !$user_id) {
        throw new Exception('Template ID and User ID are required');
    }
    
    try {
        // Check if already exists
        $stmt = $pdo->prepare("SELECT id FROM report_user_preferences WHERE user_id = ? AND template_id = ? AND preference_type = 'favorite'");
        $stmt->execute([$user_id, $template_id]);
        
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Already in favorites'
            ];
        }
        
        // Add to favorites
        $stmt = $pdo->prepare("INSERT INTO report_user_preferences (user_id, template_id, preference_type) VALUES (?, ?, 'favorite')");
        $stmt->execute([$user_id, $template_id]);
        
        return [
            'success' => true,
            'message' => 'Added to favorites'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
?>
