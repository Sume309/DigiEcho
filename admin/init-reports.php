<?php
/**
 * Initialize Report Management System with Professional Report Templates
 * This script populates the database with comprehensive e-commerce reports
 */

require_once '../config/database.php';

try {
    // First, run the database migration
    $migration = file_get_contents('../migrations/20250929_create_report_system.sql');
    $statements = explode(';', $migration);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✅ Database tables created successfully\n";
    
    // Insert comprehensive report templates
    insertReportTemplates($pdo);
    
    echo "✅ Report templates inserted successfully\n";
    echo "🎉 Report Management System initialized!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

function insertReportTemplates($pdo) {
    // Get category IDs
    $categories = [];
    $stmt = $pdo->query("SELECT id, slug FROM report_categories");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[$row['slug']] = $row['id'];
    }
    
    $reports = [
        // SALES & REVENUE REPORTS
        [
            'category_id' => $categories['sales-revenue'],
            'name' => 'Sales by Payment Method',
            'slug' => 'sales-by-payment-method',
            'description' => 'Revenue breakdown by payment methods (bKash, Cash, Card)',
            'query_template' => "SELECT payment_method, COUNT(*) as order_count, SUM(total_amount) as total_revenue, AVG(total_amount) as avg_order_value FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND status != 'cancelled' GROUP BY payment_method ORDER BY total_revenue DESC",
            'chart_type' => 'pie',
            'parameters' => '{"days": {"type": "number", "default": 30, "label": "Days"}}',
            'columns' => '[{"key": "payment_method", "label": "Payment Method", "type": "text"}, {"key": "order_count", "label": "Orders", "type": "number"}, {"key": "total_revenue", "label": "Revenue", "type": "currency"}, {"key": "avg_order_value", "label": "AOV", "type": "currency"}]',
            'is_featured' => 1
        ],
        [
            'category_id' => $categories['sales-revenue'],
            'name' => 'Refunds & Returns Analysis',
            'slug' => 'refunds-returns-analysis',
            'description' => 'Analysis of refunded and cancelled orders',
            'query_template' => "SELECT DATE(created_at) as date, COUNT(*) as refund_count, SUM(total_amount) as refund_amount, payment_method FROM orders WHERE status IN ('refunded', 'cancelled') AND created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) GROUP BY DATE(created_at), payment_method ORDER BY date DESC",
            'chart_type' => 'line',
            'parameters' => '{"days": {"type": "number", "default": 30, "label": "Days"}}',
            'columns' => '[{"key": "date", "label": "Date", "type": "date"}, {"key": "refund_count", "label": "Refunds", "type": "number"}, {"key": "refund_amount", "label": "Amount", "type": "currency"}, {"key": "payment_method", "label": "Payment Method", "type": "text"}]'
        ],
        [
            'category_id' => $categories['sales-revenue'],
            'name' => 'Average Order Value Trends',
            'slug' => 'aov-trends',
            'description' => 'Average order value trends over time',
            'query_template' => "SELECT DATE(created_at) as date, AVG(total_amount) as avg_order_value, COUNT(*) as order_count FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND status != 'cancelled' GROUP BY DATE(created_at) ORDER BY date DESC",
            'chart_type' => 'line',
            'parameters' => '{"days": {"type": "number", "default": 30, "label": "Days"}}',
            'columns' => '[{"key": "date", "label": "Date", "type": "date"}, {"key": "avg_order_value", "label": "AOV", "type": "currency"}, {"key": "order_count", "label": "Orders", "type": "number"}]'
        ],
        [
            'category_id' => $categories['sales-revenue'],
            'name' => 'Sales by Region',
            'slug' => 'sales-by-region',
            'description' => 'Sales performance by shipping regions/cities',
            'query_template' => "SELECT shipping_city as region, COUNT(*) as order_count, SUM(total_amount) as total_sales FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND status != 'cancelled' AND shipping_city IS NOT NULL GROUP BY shipping_city ORDER BY total_sales DESC LIMIT 20",
            'chart_type' => 'bar',
            'parameters' => '{"days": {"type": "number", "default": 30, "label": "Days"}}',
            'columns' => '[{"key": "region", "label": "Region", "type": "text"}, {"key": "order_count", "label": "Orders", "type": "number"}, {"key": "total_sales", "label": "Sales", "type": "currency"}]'
        ],

        // CUSTOMER ANALYTICS REPORTS
        [
            'category_id' => $categories['customer-analytics'],
            'name' => 'Customer Lifetime Value',
            'slug' => 'customer-lifetime-value',
            'description' => 'Top customers by total purchase value',
            'query_template' => "SELECT CONCAT(u.first_name, ' ', u.last_name) as customer_name, u.email, COUNT(o.id) as total_orders, SUM(o.total_amount) as lifetime_value, AVG(o.total_amount) as avg_order_value, MAX(o.created_at) as last_order_date FROM users u JOIN orders o ON u.id = o.user_id WHERE o.status != 'cancelled' GROUP BY u.id ORDER BY lifetime_value DESC LIMIT {{limit}}",
            'chart_type' => 'table',
            'parameters' => '{"limit": {"type": "number", "default": 50, "label": "Top Customers"}}',
            'columns' => '[{"key": "customer_name", "label": "Customer", "type": "text"}, {"key": "email", "label": "Email", "type": "text"}, {"key": "total_orders", "label": "Orders", "type": "number"}, {"key": "lifetime_value", "label": "LTV", "type": "currency"}, {"key": "avg_order_value", "label": "AOV", "type": "currency"}, {"key": "last_order_date", "label": "Last Order", "type": "date"}]',
            'is_featured' => 1
        ],
        [
            'category_id' => $categories['customer-analytics'],
            'name' => 'Customer Demographics',
            'slug' => 'customer-demographics',
            'description' => 'Customer distribution by location and registration date',
            'query_template' => "SELECT city, COUNT(*) as customer_count, DATE_FORMAT(created_at, '%Y-%m') as registration_month FROM users WHERE role = 'customer' AND created_at >= DATE_SUB(CURDATE(), INTERVAL {{months}} MONTH) GROUP BY city, DATE_FORMAT(created_at, '%Y-%m') ORDER BY customer_count DESC",
            'chart_type' => 'bar',
            'parameters' => '{"months": {"type": "number", "default": 12, "label": "Months"}}',
            'columns' => '[{"key": "city", "label": "City", "type": "text"}, {"key": "customer_count", "label": "Customers", "type": "number"}, {"key": "registration_month", "label": "Month", "type": "text"}]'
        ],
        [
            'category_id' => $categories['customer-analytics'],
            'name' => 'Customer Retention Analysis',
            'slug' => 'customer-retention',
            'description' => 'New vs returning customer analysis',
            'query_template' => "SELECT DATE_FORMAT(o.created_at, '%Y-%m') as month, COUNT(DISTINCT CASE WHEN customer_orders.order_count = 1 THEN o.user_id END) as new_customers, COUNT(DISTINCT CASE WHEN customer_orders.order_count > 1 THEN o.user_id END) as returning_customers FROM orders o JOIN (SELECT user_id, COUNT(*) as order_count FROM orders WHERE status != 'cancelled' GROUP BY user_id) customer_orders ON o.user_id = customer_orders.user_id WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL {{months}} MONTH) AND o.status != 'cancelled' GROUP BY DATE_FORMAT(o.created_at, '%Y-%m') ORDER BY month DESC",
            'chart_type' => 'line',
            'parameters' => '{"months": {"type": "number", "default": 12, "label": "Months"}}',
            'columns' => '[{"key": "month", "label": "Month", "type": "text"}, {"key": "new_customers", "label": "New Customers", "type": "number"}, {"key": "returning_customers", "label": "Returning Customers", "type": "number"}]'
        ],

        // PRODUCT PERFORMANCE REPORTS
        [
            'category_id' => $categories['product-performance'],
            'name' => 'Low Stock Alert',
            'slug' => 'low-stock-alert',
            'description' => 'Products with low inventory levels',
            'query_template' => "SELECT p.name, p.sku, p.stock_quantity, c.name as category, b.name as brand, p.selling_price FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id WHERE p.stock_quantity <= {{threshold}} AND p.is_active = 1 ORDER BY p.stock_quantity ASC",
            'chart_type' => 'table',
            'parameters' => '{"threshold": {"type": "number", "default": 10, "label": "Stock Threshold"}}',
            'columns' => '[{"key": "name", "label": "Product", "type": "text"}, {"key": "sku", "label": "SKU", "type": "text"}, {"key": "stock_quantity", "label": "Stock", "type": "number"}, {"key": "category", "label": "Category", "type": "text"}, {"key": "brand", "label": "Brand", "type": "text"}, {"key": "selling_price", "label": "Price", "type": "currency"}]',
            'is_featured' => 1
        ],
        [
            'category_id' => $categories['product-performance'],
            'name' => 'Product Performance by Category',
            'slug' => 'product-performance-category',
            'description' => 'Sales performance breakdown by product categories',
            'query_template' => "SELECT c.name as category, COUNT(DISTINCT p.id) as product_count, COALESCE(SUM(oi.quantity), 0) as total_sold, COALESCE(SUM(oi.price * oi.quantity), 0) as total_revenue FROM categories c LEFT JOIN products p ON c.id = p.category_id LEFT JOIN order_items oi ON p.id = oi.product_id LEFT JOIN orders o ON oi.order_id = o.id WHERE (o.created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) OR o.created_at IS NULL) AND (o.status != 'cancelled' OR o.status IS NULL) GROUP BY c.id, c.name ORDER BY total_revenue DESC",
            'chart_type' => 'doughnut',
            'parameters' => '{"days": {"type": "number", "default": 30, "label": "Days"}}',
            'columns' => '[{"key": "category", "label": "Category", "type": "text"}, {"key": "product_count", "label": "Products", "type": "number"}, {"key": "total_sold", "label": "Qty Sold", "type": "number"}, {"key": "total_revenue", "label": "Revenue", "type": "currency"}]'
        ],
        [
            'category_id' => $categories['product-performance'],
            'name' => 'Dead Stock Analysis',
            'slug' => 'dead-stock-analysis',
            'description' => 'Products with no sales in specified period',
            'query_template' => "SELECT p.name, p.sku, p.stock_quantity, p.cost_price, p.selling_price, (p.stock_quantity * p.cost_price) as inventory_value, c.name as category FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN order_items oi ON p.id = oi.product_id LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND o.status != 'cancelled' WHERE p.is_active = 1 AND p.stock_quantity > 0 AND oi.id IS NULL ORDER BY inventory_value DESC",
            'chart_type' => 'table',
            'parameters' => '{"days": {"type": "number", "default": 90, "label": "Days"}}',
            'columns' => '[{"key": "name", "label": "Product", "type": "text"}, {"key": "sku", "label": "SKU", "type": "text"}, {"key": "stock_quantity", "label": "Stock", "type": "number"}, {"key": "inventory_value", "label": "Inventory Value", "type": "currency"}, {"key": "category", "label": "Category", "type": "text"}]'
        ],

        // MARKETING & CAMPAIGNS REPORTS
        [
            'category_id' => $categories['marketing-campaigns'],
            'name' => 'Coupon Usage Report',
            'slug' => 'coupon-usage-report',
            'description' => 'Coupon and discount code usage analysis',
            'query_template' => "SELECT c.code, c.name, c.type, c.value, c.used_count, c.usage_limit, COALESCE(SUM(cu.discount_amount), 0) as total_discount_given FROM coupons c LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id LEFT JOIN orders o ON cu.order_id = o.id WHERE (o.created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) OR o.created_at IS NULL) GROUP BY c.id ORDER BY total_discount_given DESC",
            'chart_type' => 'table',
            'parameters' => '{"days": {"type": "number", "default": 30, "label": "Days"}}',
            'columns' => '[{"key": "code", "label": "Code", "type": "text"}, {"key": "name", "label": "Name", "type": "text"}, {"key": "type", "label": "Type", "type": "text"}, {"key": "used_count", "label": "Used", "type": "number"}, {"key": "usage_limit", "label": "Limit", "type": "number"}, {"key": "total_discount_given", "label": "Total Discount", "type": "currency"}]'
        ],
        [
            'category_id' => $categories['marketing-campaigns'],
            'name' => 'Conversion Funnel Analysis',
            'slug' => 'conversion-funnel',
            'description' => 'Customer journey from registration to purchase',
            'query_template' => "SELECT 'Registered Users' as stage, COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) UNION ALL SELECT 'Users with Orders' as stage, COUNT(DISTINCT user_id) as count FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) UNION ALL SELECT 'Completed Orders' as stage, COUNT(*) as count FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND status = 'delivered'",
            'chart_type' => 'bar',
            'parameters' => '{"days": {"type": "number", "default": 30, "label": "Days"}}',
            'columns' => '[{"key": "stage", "label": "Stage", "type": "text"}, {"key": "count", "label": "Count", "type": "number"}]'
        ],

        // OPERATIONS & LOGISTICS REPORTS
        [
            'category_id' => $categories['operations-logistics'],
            'name' => 'Order Fulfillment Status',
            'slug' => 'order-fulfillment-status',
            'description' => 'Current status of all orders',
            'query_template' => "SELECT status, COUNT(*) as order_count, SUM(total_amount) as total_value, AVG(DATEDIFF(CURDATE(), created_at)) as avg_days_since_order FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) GROUP BY status ORDER BY order_count DESC",
            'chart_type' => 'pie',
            'parameters' => '{"days": {"type": "number", "default": 30, "label": "Days"}}',
            'columns' => '[{"key": "status", "label": "Status", "type": "text"}, {"key": "order_count", "label": "Orders", "type": "number"}, {"key": "total_value", "label": "Value", "type": "currency"}, {"key": "avg_days_since_order", "label": "Avg Days", "type": "number"}]',
            'is_featured' => 1
        ],
        [
            'category_id' => $categories['operations-logistics'],
            'name' => 'Shipping Performance',
            'slug' => 'shipping-performance',
            'description' => 'Delivery performance by shipping method and region',
            'query_template' => "SELECT shipping_city, COUNT(*) as total_orders, COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders, AVG(CASE WHEN status = 'delivered' THEN DATEDIFF(updated_at, created_at) END) as avg_delivery_days FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND shipping_city IS NOT NULL GROUP BY shipping_city ORDER BY total_orders DESC LIMIT 20",
            'chart_type' => 'table',
            'parameters' => '{"days": {"type": "number", "default": 30, "label": "Days"}}',
            'columns' => '[{"key": "shipping_city", "label": "City", "type": "text"}, {"key": "total_orders", "label": "Total Orders", "type": "number"}, {"key": "delivered_orders", "label": "Delivered", "type": "number"}, {"key": "avg_delivery_days", "label": "Avg Delivery Days", "type": "number"}]'
        ],

        // FINANCIAL REPORTS
        [
            'category_id' => $categories['financial-reports'],
            'name' => 'Tax Collection Report',
            'slug' => 'tax-collection-report',
            'description' => 'VAT and tax collection summary',
            'query_template' => "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as order_count, SUM(subtotal) as gross_sales, SUM(tax_amount) as total_tax, SUM(total_amount) as net_sales FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{months}} MONTH) AND status != 'cancelled' GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month DESC",
            'chart_type' => 'bar',
            'parameters' => '{"months": {"type": "number", "default": 12, "label": "Months"}}',
            'columns' => '[{"key": "month", "label": "Month", "type": "text"}, {"key": "order_count", "label": "Orders", "type": "number"}, {"key": "gross_sales", "label": "Gross Sales", "type": "currency"}, {"key": "total_tax", "label": "Tax Collected", "type": "currency"}, {"key": "net_sales", "label": "Net Sales", "type": "currency"}]',
            'is_featured' => 1
        ],
        [
            'category_id' => $categories['financial-reports'],
            'name' => 'Payment Method Analysis',
            'slug' => 'payment-method-analysis',
            'description' => 'Revenue and transaction fees by payment method',
            'query_template' => "SELECT payment_method, COUNT(*) as transaction_count, SUM(total_amount) as total_revenue, AVG(total_amount) as avg_transaction_value, payment_status FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) GROUP BY payment_method, payment_status ORDER BY total_revenue DESC",
            'chart_type' => 'table',
            'parameters' => '{"days": {"type": "number", "default": 30, "label": "Days"}}',
            'columns' => '[{"key": "payment_method", "label": "Payment Method", "type": "text"}, {"key": "transaction_count", "label": "Transactions", "type": "number"}, {"key": "total_revenue", "label": "Revenue", "type": "currency"}, {"key": "avg_transaction_value", "label": "Avg Value", "type": "currency"}, {"key": "payment_status", "label": "Status", "type": "text"}]'
        ],
        [
            'category_id' => $categories['financial-reports'],
            'name' => 'Profit & Loss Summary',
            'slug' => 'profit-loss-summary',
            'description' => 'Basic P&L statement based on orders and costs',
            'query_template' => "SELECT DATE_FORMAT(o.created_at, '%Y-%m') as month, SUM(o.total_amount) as total_revenue, SUM(o.tax_amount) as total_tax, SUM(oi.quantity * p.cost_price) as cost_of_goods, (SUM(o.total_amount) - SUM(oi.quantity * p.cost_price)) as gross_profit FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL {{months}} MONTH) AND o.status != 'cancelled' GROUP BY DATE_FORMAT(o.created_at, '%Y-%m') ORDER BY month DESC",
            'chart_type' => 'line',
            'parameters' => '{"months": {"type": "number", "default": 12, "label": "Months"}}',
            'columns' => '[{"key": "month", "label": "Month", "type": "text"}, {"key": "total_revenue", "label": "Revenue", "type": "currency"}, {"key": "cost_of_goods", "label": "COGS", "type": "currency"}, {"key": "gross_profit", "label": "Gross Profit", "type": "currency"}, {"key": "total_tax", "label": "Tax", "type": "currency"}]'
        ]
    ];

    // Insert all report templates
    $stmt = $pdo->prepare("
        INSERT INTO report_templates (
            category_id, name, slug, description, query_template, chart_type, 
            chart_config, parameters, filters, columns, refresh_interval, 
            cache_duration, is_featured, sort_order
        ) VALUES (
            :category_id, :name, :slug, :description, :query_template, :chart_type,
            :chart_config, :parameters, :filters, :columns, :refresh_interval,
            :cache_duration, :is_featured, :sort_order
        )
    ");

    foreach ($reports as $index => $report) {
        $stmt->execute([
            'category_id' => $report['category_id'],
            'name' => $report['name'],
            'slug' => $report['slug'],
            'description' => $report['description'],
            'query_template' => $report['query_template'],
            'chart_type' => $report['chart_type'],
            'chart_config' => json_encode(['responsive' => true, 'maintainAspectRatio' => false]),
            'parameters' => $report['parameters'] ?? '{}',
            'filters' => $report['filters'] ?? '{}',
            'columns' => $report['columns'] ?? '[]',
            'refresh_interval' => 30,
            'cache_duration' => 300,
            'is_featured' => $report['is_featured'] ?? 0,
            'sort_order' => $index + 1
        ]);
    }

    // Create a default dashboard
    createDefaultDashboard($pdo, $categories);
}

function createDefaultDashboard($pdo, $categories) {
    // Create default dashboard
    $stmt = $pdo->prepare("
        INSERT INTO report_dashboards (name, slug, description, layout_config, widgets, is_default, is_active)
        VALUES (?, ?, ?, ?, ?, 1, 1)
    ");
    
    $widgets = [
        [
            'id' => 'widget_1',
            'template_id' => 1, // Daily Sales Summary
            'title' => 'Sales Trend',
            'position' => ['x' => 0, 'y' => 0],
            'size' => ['width' => 8, 'height' => 4],
            'parameters' => ['days' => 30],
            'filters' => []
        ],
        [
            'id' => 'widget_2',
            'template_id' => 8, // Product Performance by Category
            'title' => 'Category Performance',
            'position' => ['x' => 8, 'y' => 0],
            'size' => ['width' => 4, 'height' => 4],
            'parameters' => ['days' => 30],
            'filters' => []
        ],
        [
            'id' => 'widget_3',
            'template_id' => 12, // Order Fulfillment Status
            'title' => 'Order Status',
            'position' => ['x' => 0, 'y' => 4],
            'size' => ['width' => 6, 'height' => 3],
            'parameters' => ['days' => 30],
            'filters' => []
        ],
        [
            'id' => 'widget_4',
            'template_id' => 6, // Low Stock Alert
            'title' => 'Low Stock Alert',
            'position' => ['x' => 6, 'y' => 4],
            'size' => ['width' => 6, 'height' => 3],
            'parameters' => ['threshold' => 10],
            'filters' => []
        ]
    ];
    
    $layout_config = [
        'cols' => 12,
        'rowHeight' => 60,
        'margin' => [10, 10],
        'containerPadding' => [10, 10]
    ];
    
    $stmt->execute([
        'E-commerce Dashboard',
        'ecommerce-dashboard',
        'Comprehensive overview of sales, orders, inventory, and performance metrics',
        json_encode($layout_config),
        json_encode($widgets)
    ]);
    
    echo "✅ Default dashboard created\n";
}
?>
