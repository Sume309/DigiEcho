<?php

/**
 * Report Management System - Core Class
 * Handles report generation, caching, exports, and ETL operations
 * 
 * @author Family-Haat-Bazar Development Team
 * @version 1.0.0
 * @created 2025-09-29
 */

class ReportManager
{
    private $db;
    private $cache_dir;
    private $export_dir;
    private $config;

    public function __construct($database_connection)
    {
        $this->db = $database_connection;
        $this->cache_dir = __DIR__ . '/../cache/reports/';
        $this->export_dir = __DIR__ . '/../exports/reports/';
        $this->config = [
            'default_cache_duration' => 300, // 5 minutes
            'max_execution_time' => 300, // 5 minutes
            'max_records_per_page' => 1000,
            'export_retention_days' => 7
        ];
        
        // Ensure directories exist
        $this->ensureDirectories();
    }

    /**
     * Execute a report template with parameters and filters
     */
    public function executeReport($template_id, $parameters = [], $filters = [], $user_id = null, $use_cache = true)
    {
        try {
            // Get report template
            $template = $this->getReportTemplate($template_id);
            if (!$template) {
                throw new Exception("Report template not found");
            }

            // Check cache first
            $cache_key = $this->generateCacheKey($template_id, $parameters, $filters);
            if ($use_cache) {
                $cached_result = $this->getCachedResult($cache_key);
                if ($cached_result) {
                    return $cached_result;
                }
            }

            // Start execution tracking
            $execution_id = $this->startExecution($template_id, $user_id, $parameters, $filters);
            $start_time = microtime(true);

            // Process query template
            $query = $this->processQueryTemplate($template['query_template'], $parameters, $filters);
            
            // Execute query
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Process results
            $result = [
                'template_id' => $template_id,
                'template_name' => $template['name'],
                'chart_type' => $template['chart_type'],
                'chart_config' => json_decode($template['chart_config'], true),
                'columns' => json_decode($template['columns'], true),
                'data' => $data,
                'record_count' => count($data),
                'execution_time' => round(microtime(true) - $start_time, 3),
                'generated_at' => date('Y-m-d H:i:s'),
                'cache_key' => $cache_key
            ];

            // Apply post-processing
            $result = $this->postProcessResults($result, $template);

            // Cache results
            if ($use_cache) {
                $this->cacheResult($cache_key, $result, $template['cache_duration'] ?? $this->config['default_cache_duration']);
            }

            // Update execution record
            $this->completeExecution($execution_id, $result);

            return $result;

        } catch (Exception $e) {
            if (isset($execution_id)) {
                $this->failExecution($execution_id, $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Get paginated report data
     */
    public function getPaginatedReport($template_id, $page = 1, $per_page = 50, $parameters = [], $filters = [], $user_id = null)
    {
        $per_page = min($per_page, $this->config['max_records_per_page']);
        $offset = ($page - 1) * $per_page;

        // Get template and modify query for pagination
        $template = $this->getReportTemplate($template_id);
        if (!$template) {
            throw new Exception("Report template not found");
        }

        // Get total count first
        $count_query = $this->processQueryTemplate($template['query_template'], $parameters, $filters, true);
        $stmt = $this->db->prepare($count_query);
        $stmt->execute();
        $total_records = $stmt->fetchColumn();

        // Get paginated data
        $query = $this->processQueryTemplate($template['query_template'], $parameters, $filters);
        $query .= " LIMIT $per_page OFFSET $offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'template_id' => $template_id,
            'template_name' => $template['name'],
            'columns' => json_decode($template['columns'], true),
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_records' => $total_records,
                'total_pages' => ceil($total_records / $per_page),
                'has_next' => $page < ceil($total_records / $per_page),
                'has_prev' => $page > 1
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Export report to various formats
     */
    public function exportReport($template_id, $format, $parameters = [], $filters = [], $user_id = null, $filename = null)
    {
        $allowed_formats = ['pdf', 'excel', 'csv', 'json'];
        if (!in_array($format, $allowed_formats)) {
            throw new Exception("Unsupported export format");
        }

        // Generate filename if not provided
        if (!$filename) {
            $template = $this->getReportTemplate($template_id);
            $filename = $this->sanitizeFilename($template['name']) . '_' . date('Y-m-d_H-i-s') . '.' . ($format === 'excel' ? 'xlsx' : $format);
        }

        // Start export tracking
        $export_id = $this->startExport($template_id, $user_id, $format, $filename, $parameters, $filters);
        
        try {
            $start_time = microtime(true);
            
            // Get report data (without pagination for export)
            $result = $this->executeReport($template_id, $parameters, $filters, $user_id, false);
            
            $file_path = $this->export_dir . $filename;
            
            switch ($format) {
                case 'csv':
                    $this->exportToCsv($result, $file_path);
                    break;
                case 'excel':
                    $this->exportToExcel($result, $file_path);
                    break;
                case 'pdf':
                    $this->exportToPdf($result, $file_path);
                    break;
                case 'json':
                    $this->exportToJson($result, $file_path);
                    break;
            }

            $file_size = filesize($file_path);
            $generation_time = round(microtime(true) - $start_time, 3);

            // Update export record
            $this->completeExport($export_id, $file_path, $file_size, $generation_time, count($result['data']));

            return [
                'export_id' => $export_id,
                'filename' => $filename,
                'file_path' => $file_path,
                'file_size' => $file_size,
                'format' => $format,
                'record_count' => count($result['data']),
                'generation_time' => $generation_time,
                'download_url' => '/admin/reports/download.php?export_id=' . $export_id
            ];

        } catch (Exception $e) {
            $this->failExport($export_id, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get dashboard data with multiple widgets
     */
    public function getDashboardData($dashboard_id, $user_id = null)
    {
        $dashboard = $this->getDashboard($dashboard_id);
        if (!$dashboard) {
            throw new Exception("Dashboard not found");
        }

        $widgets = json_decode($dashboard['widgets'], true) ?? [];
        $dashboard_data = [
            'id' => $dashboard_id,
            'name' => $dashboard['name'],
            'description' => $dashboard['description'],
            'layout_config' => json_decode($dashboard['layout_config'], true),
            'filters' => json_decode($dashboard['filters'], true),
            'widgets' => [],
            'generated_at' => date('Y-m-d H:i:s')
        ];

        foreach ($widgets as $widget) {
            try {
                $widget_data = $this->executeReport(
                    $widget['template_id'],
                    $widget['parameters'] ?? [],
                    $widget['filters'] ?? [],
                    $user_id,
                    true
                );
                
                $dashboard_data['widgets'][] = [
                    'id' => $widget['id'],
                    'title' => $widget['title'],
                    'position' => $widget['position'],
                    'size' => $widget['size'],
                    'data' => $widget_data
                ];
            } catch (Exception $e) {
                // Log error but continue with other widgets
                error_log("Widget error: " . $e->getMessage());
                $dashboard_data['widgets'][] = [
                    'id' => $widget['id'],
                    'title' => $widget['title'],
                    'position' => $widget['position'],
                    'size' => $widget['size'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return $dashboard_data;
    }

    /**
     * Search and filter reports
     */
    public function searchReports($query = '', $category_id = null, $user_id = null, $page = 1, $per_page = 20)
    {
        $where_conditions = ['rt.is_active = 1'];
        $params = [];

        if (!empty($query)) {
            $where_conditions[] = '(rt.name LIKE :query OR rt.description LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        if ($category_id) {
            $where_conditions[] = 'rt.category_id = :category_id';
            $params['category_id'] = $category_id;
        }

        $where_clause = implode(' AND ', $where_conditions);
        $offset = ($page - 1) * $per_page;

        // Get total count
        $count_sql = "SELECT COUNT(*) FROM report_templates rt 
                      JOIN report_categories rc ON rt.category_id = rc.id 
                      WHERE $where_clause";
        $stmt = $this->db->prepare($count_sql);
        $stmt->execute($params);
        $total_records = $stmt->fetchColumn();

        // Get paginated results
        $sql = "SELECT rt.*, rc.name as category_name, rc.color as category_color, rc.icon as category_icon
                FROM report_templates rt 
                JOIN report_categories rc ON rt.category_id = rc.id 
                WHERE $where_clause 
                ORDER BY rt.is_featured DESC, rt.sort_order ASC, rt.name ASC
                LIMIT $per_page OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'reports' => $reports,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_records' => $total_records,
                'total_pages' => ceil($total_records / $per_page)
            ]
        ];
    }

    /**
     * Get report categories
     */
    public function getReportCategories($active_only = true)
    {
        $where = $active_only ? 'WHERE is_active = 1' : '';
        $sql = "SELECT * FROM report_categories $where ORDER BY sort_order ASC, name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent report executions for user
     */
    public function getRecentReports($user_id, $limit = 10)
    {
        $sql = "SELECT re.*, rt.name as template_name, rt.chart_type, rc.name as category_name
                FROM report_executions re
                JOIN report_templates rt ON re.template_id = rt.id
                JOIN report_categories rc ON rt.category_id = rc.id
                WHERE re.user_id = :user_id AND re.status = 'completed'
                ORDER BY re.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Private helper methods

    private function getReportTemplate($template_id)
    {
        $sql = "SELECT * FROM report_templates WHERE id = :id AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $template_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getDashboard($dashboard_id)
    {
        $sql = "SELECT * FROM report_dashboards WHERE id = :id AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $dashboard_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function processQueryTemplate($query_template, $parameters = [], $filters = [], $count_only = false)
    {
        // Replace parameters in query
        $query = $query_template;
        foreach ($parameters as $key => $value) {
            $query = str_replace('{{' . $key . '}}', $this->db->quote($value), $query);
        }

        // Apply filters
        if (!empty($filters)) {
            $where_conditions = [];
            foreach ($filters as $field => $value) {
                if (is_array($value)) {
                    $quoted_values = array_map([$this->db, 'quote'], $value);
                    $where_conditions[] = "$field IN (" . implode(',', $quoted_values) . ")";
                } else {
                    $where_conditions[] = "$field = " . $this->db->quote($value);
                }
            }
            
            if (!empty($where_conditions)) {
                if (stripos($query, 'WHERE') !== false) {
                    $query .= ' AND ' . implode(' AND ', $where_conditions);
                } else {
                    $query .= ' WHERE ' . implode(' AND ', $where_conditions);
                }
            }
        }

        // Convert to count query if needed
        if ($count_only) {
            $query = "SELECT COUNT(*) FROM ($query) as count_query";
        }

        return $query;
    }

    private function generateCacheKey($template_id, $parameters, $filters)
    {
        return 'report_' . $template_id . '_' . md5(json_encode($parameters) . json_encode($filters));
    }

    private function getCachedResult($cache_key)
    {
        $cache_file = $this->cache_dir . $cache_key . '.json';
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $this->config['default_cache_duration']) {
            return json_decode(file_get_contents($cache_file), true);
        }
        return null;
    }

    private function cacheResult($cache_key, $result, $duration)
    {
        $cache_file = $this->cache_dir . $cache_key . '.json';
        file_put_contents($cache_file, json_encode($result));
        
        // Set expiration
        touch($cache_file, time() + $duration);
    }

    private function postProcessResults($result, $template)
    {
        // Apply aggregations if specified
        $aggregations = json_decode($template['aggregations'] ?? '[]', true);
        if (!empty($aggregations)) {
            $result['aggregations'] = $this->calculateAggregations($result['data'], $aggregations);
        }

        // Format data based on column types
        $columns = json_decode($template['columns'] ?? '[]', true);
        if (!empty($columns)) {
            $result['data'] = $this->formatData($result['data'], $columns);
        }

        return $result;
    }

    private function calculateAggregations($data, $aggregations)
    {
        $results = [];
        foreach ($aggregations as $agg) {
            $field = $agg['field'];
            $type = $agg['type'];
            $values = array_column($data, $field);
            
            switch ($type) {
                case 'sum':
                    $results[$field . '_sum'] = array_sum($values);
                    break;
                case 'avg':
                    $results[$field . '_avg'] = count($values) > 0 ? array_sum($values) / count($values) : 0;
                    break;
                case 'min':
                    $results[$field . '_min'] = min($values);
                    break;
                case 'max':
                    $results[$field . '_max'] = max($values);
                    break;
                case 'count':
                    $results[$field . '_count'] = count($values);
                    break;
            }
        }
        return $results;
    }

    private function formatData($data, $columns)
    {
        $column_types = [];
        foreach ($columns as $col) {
            $column_types[$col['key']] = $col['type'];
        }

        foreach ($data as &$row) {
            foreach ($row as $key => &$value) {
                if (isset($column_types[$key])) {
                    switch ($column_types[$key]) {
                        case 'currency':
                            $value = number_format($value, 2);
                            break;
                        case 'percentage':
                            $value = number_format($value * 100, 2) . '%';
                            break;
                        case 'date':
                            $value = date('M d, Y', strtotime($value));
                            break;
                        case 'datetime':
                            $value = date('M d, Y H:i', strtotime($value));
                            break;
                    }
                }
            }
        }

        return $data;
    }

    private function startExecution($template_id, $user_id, $parameters, $filters)
    {
        $sql = "INSERT INTO report_executions (template_id, user_id, parameters, filters, status) 
                VALUES (:template_id, :user_id, :parameters, :filters, 'pending')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'template_id' => $template_id,
            'user_id' => $user_id,
            'parameters' => json_encode($parameters),
            'filters' => json_encode($filters)
        ]);
        return $this->db->lastInsertId();
    }

    private function completeExecution($execution_id, $result)
    {
        $sql = "UPDATE report_executions SET 
                status = 'completed', 
                result_count = :count, 
                execution_time = :time,
                cache_key = :cache_key
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $execution_id,
            'count' => $result['record_count'],
            'time' => $result['execution_time'],
            'cache_key' => $result['cache_key']
        ]);
    }

    private function failExecution($execution_id, $error_message)
    {
        $sql = "UPDATE report_executions SET status = 'failed', error_message = :error WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $execution_id, 'error' => $error_message]);
    }

    private function startExport($template_id, $user_id, $format, $filename, $parameters, $filters)
    {
        $sql = "INSERT INTO report_exports (template_id, user_id, filename, format, parameters, filters, status) 
                VALUES (:template_id, :user_id, :filename, :format, :parameters, :filters, 'generating')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'template_id' => $template_id,
            'user_id' => $user_id,
            'filename' => $filename,
            'format' => $format,
            'parameters' => json_encode($parameters),
            'filters' => json_encode($filters)
        ]);
        return $this->db->lastInsertId();
    }

    private function completeExport($export_id, $file_path, $file_size, $generation_time, $record_count)
    {
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . $this->config['export_retention_days'] . ' days'));
        $sql = "UPDATE report_exports SET 
                status = 'completed',
                file_path = :file_path,
                file_size = :file_size,
                generation_time = :generation_time,
                record_count = :record_count,
                expires_at = :expires_at
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $export_id,
            'file_path' => $file_path,
            'file_size' => $file_size,
            'generation_time' => $generation_time,
            'record_count' => $record_count,
            'expires_at' => $expires_at
        ]);
    }

    private function failExport($export_id, $error_message)
    {
        $sql = "UPDATE report_exports SET status = 'failed', error_message = :error WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $export_id, 'error' => $error_message]);
    }

    private function exportToCsv($result, $file_path)
    {
        $fp = fopen($file_path, 'w');
        
        // Write headers
        if (!empty($result['columns'])) {
            $headers = array_column($result['columns'], 'label');
            fputcsv($fp, $headers);
        } elseif (!empty($result['data'])) {
            fputcsv($fp, array_keys($result['data'][0]));
        }
        
        // Write data
        foreach ($result['data'] as $row) {
            fputcsv($fp, $row);
        }
        
        fclose($fp);
    }

    private function exportToJson($result, $file_path)
    {
        file_put_contents($file_path, json_encode($result, JSON_PRETTY_PRINT));
    }

    private function exportToExcel($result, $file_path)
    {
        // This would require PhpSpreadsheet library
        // For now, export as CSV with .xlsx extension
        $this->exportToCsv($result, $file_path);
    }

    private function exportToPdf($result, $file_path)
    {
        // This would require TCPDF or similar library
        // For now, create a simple HTML to PDF conversion
        $html = $this->generateHtmlReport($result);
        file_put_contents($file_path, $html);
    }

    private function generateHtmlReport($result)
    {
        $html = '<html><head><title>' . htmlspecialchars($result['template_name']) . '</title></head><body>';
        $html .= '<h1>' . htmlspecialchars($result['template_name']) . '</h1>';
        $html .= '<p>Generated: ' . $result['generated_at'] . '</p>';
        $html .= '<p>Records: ' . $result['record_count'] . '</p>';
        
        if (!empty($result['data'])) {
            $html .= '<table border="1" cellpadding="5" cellspacing="0">';
            
            // Headers
            if (!empty($result['columns'])) {
                $html .= '<tr>';
                foreach ($result['columns'] as $col) {
                    $html .= '<th>' . htmlspecialchars($col['label']) . '</th>';
                }
                $html .= '</tr>';
            }
            
            // Data
            foreach ($result['data'] as $row) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
                $html .= '</tr>';
            }
            
            $html .= '</table>';
        }
        
        $html .= '</body></html>';
        return $html;
    }

    private function sanitizeFilename($filename)
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    }

    private function ensureDirectories()
    {
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        if (!is_dir($this->export_dir)) {
            mkdir($this->export_dir, 0755, true);
        }
    }
}
