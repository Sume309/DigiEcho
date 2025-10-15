-- Report Management System Database Schema
-- Created: 2025-09-29
-- Description: Comprehensive reporting system for Family-Haat-Bazar e-commerce platform

-- Report Categories Table
CREATE TABLE `report_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL UNIQUE,
  `description` text,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#007bff',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report Templates Table
CREATE TABLE `report_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL UNIQUE,
  `description` text,
  `query_template` longtext NOT NULL,
  `chart_type` enum('line','bar','pie','doughnut','area','column','table','metric','gauge') DEFAULT 'table',
  `chart_config` json DEFAULT NULL,
  `parameters` json DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `columns` json DEFAULT NULL,
  `aggregations` json DEFAULT NULL,
  `refresh_interval` int(11) DEFAULT 0 COMMENT 'Auto refresh in minutes, 0 = manual',
  `cache_duration` int(11) DEFAULT 300 COMMENT 'Cache duration in seconds',
  `access_roles` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_active_featured` (`is_active`, `is_featured`),
  FOREIGN KEY (`category_id`) REFERENCES `report_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report Executions Table (for caching and performance tracking)
CREATE TABLE `report_executions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `parameters` json DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `result_data` longtext DEFAULT NULL,
  `result_count` int(11) DEFAULT 0,
  `execution_time` decimal(8,3) DEFAULT NULL COMMENT 'Execution time in seconds',
  `cache_key` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','completed','failed','cached') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_template_user` (`template_id`, `user_id`),
  KEY `idx_cache_key` (`cache_key`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report Schedules Table
CREATE TABLE `report_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `schedule_type` enum('daily','weekly','monthly','quarterly','yearly','custom') NOT NULL,
  `schedule_config` json NOT NULL COMMENT 'Cron expression and schedule details',
  `recipients` json NOT NULL COMMENT 'Email addresses and notification settings',
  `parameters` json DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `export_format` enum('pdf','excel','csv','json') DEFAULT 'pdf',
  `is_active` tinyint(1) DEFAULT 1,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `run_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_next_run` (`next_run_at`),
  KEY `idx_active` (`is_active`),
  FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report Exports Table
CREATE TABLE `report_exports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `format` enum('pdf','excel','csv','json') NOT NULL,
  `parameters` json DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `record_count` int(11) DEFAULT 0,
  `generation_time` decimal(8,3) DEFAULT NULL,
  `status` enum('generating','completed','failed','expired') DEFAULT 'generating',
  `error_message` text DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_template_user` (`template_id`, `user_id`),
  KEY `idx_schedule` (`schedule_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expires` (`expires_at`),
  FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`schedule_id`) REFERENCES `report_schedules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report Dashboards Table
CREATE TABLE `report_dashboards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL UNIQUE,
  `description` text,
  `layout_config` json DEFAULT NULL COMMENT 'Dashboard layout and widget positions',
  `widgets` json DEFAULT NULL COMMENT 'Widget configurations',
  `filters` json DEFAULT NULL COMMENT 'Global dashboard filters',
  `refresh_interval` int(11) DEFAULT 300 COMMENT 'Auto refresh in seconds',
  `access_roles` json DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_active_default` (`is_active`, `is_default`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report Alerts Table
CREATE TABLE `report_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `condition_type` enum('threshold','change','anomaly','schedule') NOT NULL,
  `condition_config` json NOT NULL COMMENT 'Alert conditions and thresholds',
  `notification_config` json NOT NULL COMMENT 'Email, SMS, webhook settings',
  `parameters` json DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `trigger_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_active` (`is_active`),
  FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report User Preferences Table
CREATE TABLE `report_user_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `dashboard_id` int(11) DEFAULT NULL,
  `preference_type` enum('favorite','bookmark','recent','custom_filter','layout') NOT NULL,
  `preference_data` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_type` (`user_id`, `preference_type`),
  KEY `idx_template` (`template_id`),
  KEY `idx_dashboard` (`dashboard_id`),
  FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`dashboard_id`) REFERENCES `report_dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Report Categories
INSERT INTO `report_categories` (`name`, `slug`, `description`, `icon`, `color`, `sort_order`) VALUES
('Sales & Revenue', 'sales-revenue', 'Sales performance, revenue analysis, and financial metrics', 'fas fa-chart-line', '#28a745', 1),
('Customer Analytics', 'customer-analytics', 'Customer behavior, demographics, and retention analysis', 'fas fa-users', '#007bff', 2),
('Product Performance', 'product-performance', 'Product sales, inventory, and performance metrics', 'fas fa-box', '#fd7e14', 3),
('Marketing & Campaigns', 'marketing-campaigns', 'Marketing ROI, campaign performance, and conversion metrics', 'fas fa-bullhorn', '#e83e8c', 4),
('Operations & Logistics', 'operations-logistics', 'Order fulfillment, shipping, and operational efficiency', 'fas fa-truck', '#6f42c1', 5),
('Financial Reports', 'financial-reports', 'Financial statements, tax reports, and accounting metrics', 'fas fa-calculator', '#20c997', 6);

-- Insert Default Report Templates
INSERT INTO `report_templates` (`category_id`, `name`, `slug`, `description`, `query_template`, `chart_type`, `chart_config`, `parameters`, `filters`, `columns`, `refresh_interval`, `is_featured`) VALUES
-- Sales & Revenue Reports
(1, 'Daily Sales Summary', 'daily-sales-summary', 'Daily sales performance with trends and comparisons', 
'SELECT DATE(created_at) as sale_date, COUNT(*) as total_orders, SUM(total_amount) as total_sales, AVG(total_amount) as avg_order_value, order_type FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND status != "cancelled" GROUP BY DATE(created_at), order_type ORDER BY sale_date DESC', 
'line', 
'{"responsive": true, "scales": {"y": {"beginAtZero": true}}}', 
'{"days": {"type": "number", "default": 30, "label": "Days"}}', 
'{"status": {"type": "select", "options": ["pending", "processing", "shipped", "delivered"], "multiple": true}}', 
'[{"key": "sale_date", "label": "Date", "type": "date"}, {"key": "total_orders", "label": "Orders", "type": "number"}, {"key": "total_sales", "label": "Sales", "type": "currency"}, {"key": "avg_order_value", "label": "AOV", "type": "currency"}]', 
30, 1),

(1, 'Monthly Revenue Analysis', 'monthly-revenue-analysis', 'Monthly revenue breakdown with year-over-year comparison', 
'SELECT YEAR(created_at) as sale_year, MONTH(created_at) as sale_month, MONTHNAME(created_at) as month_name, COUNT(*) as total_orders, SUM(total_amount) as total_sales, SUM(tax_amount) as total_tax FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND status != "cancelled" GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY sale_year DESC, sale_month DESC', 
'bar', 
'{"responsive": true, "plugins": {"legend": {"display": true}}}', 
'{}', 
'{"year": {"type": "select", "options": ["2024", "2025"], "multiple": false}}', 
'[{"key": "month_name", "label": "Month", "type": "text"}, {"key": "total_orders", "label": "Orders", "type": "number"}, {"key": "total_sales", "label": "Revenue", "type": "currency"}, {"key": "total_tax", "label": "Tax", "type": "currency"}]', 
60, 1),

-- Customer Analytics Reports
(2, 'Customer Acquisition Report', 'customer-acquisition-report', 'New vs returning customer analysis', 
'SELECT DATE(created_at) as registration_date, COUNT(*) as new_customers FROM users WHERE role = "customer" AND created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) GROUP BY DATE(created_at) ORDER BY registration_date DESC', 
'area', 
'{"responsive": true, "fill": true}', 
'{"days": {"type": "number", "default": 30, "label": "Days"}}', 
'{}', 
'[{"key": "registration_date", "label": "Date", "type": "date"}, {"key": "new_customers", "label": "New Customers", "type": "number"}]', 
60, 1),

-- Product Performance Reports
(3, 'Top Selling Products', 'top-selling-products', 'Best performing products by sales volume and revenue', 
'SELECT p.name, p.sku, SUM(oi.quantity) as total_sold, SUM(oi.price * oi.quantity) as total_revenue, AVG(oi.price) as avg_price FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL {{days}} DAY) AND o.status != "cancelled" GROUP BY p.id ORDER BY total_revenue DESC LIMIT {{limit}}', 
'bar', 
'{"responsive": true, "indexAxis": "y"}', 
'{"days": {"type": "number", "default": 30, "label": "Days"}, "limit": {"type": "number", "default": 20, "label": "Limit"}}', 
'{"category": {"type": "select", "source": "categories", "multiple": true}}', 
'[{"key": "name", "label": "Product", "type": "text"}, {"key": "sku", "label": "SKU", "type": "text"}, {"key": "total_sold", "label": "Qty Sold", "type": "number"}, {"key": "total_revenue", "label": "Revenue", "type": "currency"}]', 
30, 1);

-- Create indexes for better performance
CREATE INDEX idx_orders_created_status ON orders(created_at, status);
CREATE INDEX idx_orders_user_created ON orders(user_id, created_at);
CREATE INDEX idx_order_items_product ON order_items(product_id);
CREATE INDEX idx_users_role_created ON users(role, created_at);
