<?php
/**
 * Campus Hub Portal - Performance Optimization Module
 * Database optimization, caching strategies, and performance monitoring
 * 
 * Features:
 * - Database query optimization
 * - Advanced caching system
 * - Performance monitoring
 * - Resource optimization
 * - CDN integration
 * - Memory management
 * - Response time tracking
 */

class PerformanceOptimizer {
    private $db;
    private $config;
    private $cache;
    private $metrics = [];
    
    public function __construct($db, $config) {
        $this->db = $db;
        $this->config = $config;
        $this->cache = new AdvancedCacheManager($config);
        $this->startPerformanceTracking();
    }
    
    /**
     * Start performance tracking for current request
     */
    private function startPerformanceTracking() {
        $this->metrics['start_time'] = microtime(true);
        $this->metrics['start_memory'] = memory_get_usage(true);
        $this->metrics['queries'] = 0;
        $this->metrics['cache_hits'] = 0;
        $this->metrics['cache_misses'] = 0;
    }
    
    /**
     * Optimize database queries with intelligent caching
     */
    public function optimizedQuery($query, $params = [], $cache_key = null, $cache_ttl = 3600) {
        $query_start = microtime(true);
        $this->metrics['queries']++;
        
        // Generate cache key if not provided
        if ($cache_key === null) {
            $cache_key = 'query_' . md5($query . serialize($params));
        }
        
        // Try to get from cache first
        $cached_result = $this->cache->get($cache_key);
        if ($cached_result !== false) {
            $this->metrics['cache_hits']++;
            $this->logQueryPerformance($query, microtime(true) - $query_start, true);
            return $cached_result;
        }
        
        $this->metrics['cache_misses']++;
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cache the result
            $this->cache->set($cache_key, $result, $cache_ttl);
            
            $query_time = microtime(true) - $query_start;
            $this->logQueryPerformance($query, $query_time, false);
            
            return $result;
            
        } catch (PDOException $e) {
            $this->logSlowQuery($query, $params, microtime(true) - $query_start, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Log query performance metrics
     */
    private function logQueryPerformance($query, $execution_time, $from_cache) {
        // Log slow queries (>100ms)
        if ($execution_time > 0.1) {
            $this->logSlowQuery($query, [], $execution_time, 'Slow query detected');
        }
        
        // Store performance metrics
        $this->metrics['total_query_time'] = ($this->metrics['total_query_time'] ?? 0) + $execution_time;
    }
    
    /**
     * Log slow queries for optimization
     */
    private function logSlowQuery($query, $params, $execution_time, $error = null) {
        $stmt = $this->db->prepare("
            INSERT INTO slow_query_log (query_hash, query_text, parameters, execution_time, error_message, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            execution_count = execution_count + 1,
            total_time = total_time + VALUES(execution_time),
            last_execution = NOW()
        ");
        
        $query_hash = md5($query);
        $params_json = json_encode($params);
        
        $stmt->execute([$query_hash, $query, $params_json, $execution_time, $error]);
    }
    
    /**
     * Optimize database indexes based on query patterns
     */
    public function optimizeIndexes() {
        $recommendations = [];
        
        // Analyze slow queries
        $slow_queries = $this->db->query("
            SELECT query_text, execution_count, avg_time, total_time
            FROM (
                SELECT query_hash, query_text, execution_count,
                       (total_time / execution_count) as avg_time, total_time
                FROM slow_query_log
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND execution_count > 5
            ) sq
            ORDER BY total_time DESC
            LIMIT 20
        ")->fetchAll();
        
        foreach ($slow_queries as $query) {
            $analysis = $this->analyzeQueryForIndexing($query['query_text']);
            if (!empty($analysis)) {
                $recommendations[] = [
                    'query' => substr($query['query_text'], 0, 100) . '...',
                    'avg_time' => $query['avg_time'],
                    'total_time' => $query['total_time'],
                    'execution_count' => $query['execution_count'],
                    'recommendations' => $analysis
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Analyze query for potential indexing improvements
     */
    private function analyzeQueryForIndexing($query) {
        $recommendations = [];
        
        // Look for WHERE clauses without indexes
        if (preg_match_all('/WHERE\s+(\w+)\.(\w+)\s*[=<>]/', $query, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $table = $matches[1][$i];
                $column = $matches[2][$i];
                
                // Check if index exists
                $index_check = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM INFORMATION_SCHEMA.STATISTICS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = ? 
                    AND COLUMN_NAME = ?
                ");
                $index_check->execute([$table, $column]);
                
                if ($index_check->fetchColumn() == 0) {
                    $recommendations[] = "CREATE INDEX idx_{$table}_{$column} ON {$table}({$column})";
                }
            }
        }
        
        // Look for JOIN conditions without indexes
        if (preg_match_all('/JOIN\s+(\w+)\s+\w+\s+ON\s+\w+\.(\w+)\s*=\s*\w+\.(\w+)/', $query, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $table = $matches[1][$i];
                $column1 = $matches[2][$i];
                $column2 = $matches[3][$i];
                
                // Check indexes for both columns
                foreach ([$column1, $column2] as $col) {
                    $index_check = $this->db->prepare("
                        SELECT COUNT(*) 
                        FROM INFORMATION_SCHEMA.STATISTICS 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = ? 
                        AND COLUMN_NAME = ?
                    ");
                    $index_check->execute([$table, $col]);
                    
                    if ($index_check->fetchColumn() == 0) {
                        $recommendations[] = "CREATE INDEX idx_{$table}_{$col} ON {$table}({$col})";
                    }
                }
            }
        }
        
        return array_unique($recommendations);
    }
    
    /**
     * Clean up database for better performance
     */
    public function cleanupDatabase() {
        $cleanup_results = [];
        
        // Optimize tables
        $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $this->db->exec("OPTIMIZE TABLE `$table`");
            $cleanup_results['optimized_tables'][] = $table;
        }
        
        // Clean up old data
        $cleanup_queries = [
            'old_sessions' => "DELETE FROM sessions WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'old_logs' => "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)",
            'old_cache' => "DELETE FROM cache_entries WHERE expires_at < NOW()",
            'old_notifications' => "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY) AND is_read = 1"
        ];
        
        foreach ($cleanup_queries as $type => $query) {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $cleanup_results[$type] = $stmt->rowCount();
        }
        
        // Update table statistics
        $this->db->exec("ANALYZE TABLE users, news, events, notifications");
        
        return $cleanup_results;
    }
    
    /**
     * Get database performance statistics
     */
    public function getDatabaseStats() {
        $stats = [];
        
        // Table sizes
        $table_stats = $this->db->query("
            SELECT 
                table_name,
                table_rows,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                ROUND((data_length / 1024 / 1024), 2) AS data_mb,
                ROUND((index_length / 1024 / 1024), 2) AS index_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
        ")->fetchAll();
        
        $stats['table_sizes'] = $table_stats;
        
        // Connection statistics
        $connection_stats = $this->db->query("
            SHOW STATUS WHERE Variable_name IN (
                'Connections', 'Max_used_connections', 'Threads_connected',
                'Queries', 'Questions', 'Slow_queries',
                'Select_scan', 'Select_full_join'
            )
        ")->fetchAll();
        
        $stats['connection_stats'] = $connection_stats;
        
        // InnoDB statistics
        $innodb_stats = $this->db->query("
            SHOW STATUS WHERE Variable_name LIKE 'Innodb%' AND Variable_name IN (
                'Innodb_buffer_pool_hit_rate', 'Innodb_buffer_pool_reads',
                'Innodb_buffer_pool_read_requests', 'Innodb_log_waits'
            )
        ")->fetchAll();
        
        $stats['innodb_stats'] = $innodb_stats;
        
        return $stats;
    }
    
    /**
     * Generate performance report
     */
    public function generatePerformanceReport($days = 7) {
        $end_time = microtime(true);
        $this->metrics['total_time'] = $end_time - $this->metrics['start_time'];
        $this->metrics['memory_usage'] = memory_get_usage(true) - $this->metrics['start_memory'];
        $this->metrics['peak_memory'] = memory_get_peak_usage(true);
        
        $report = [
            'current_request' => $this->metrics,
            'database_stats' => $this->getDatabaseStats(),
            'cache_performance' => $this->cache->getStatistics(),
            'slow_queries' => $this->getSlowQueriesReport($days),
            'optimization_recommendations' => $this->optimizeIndexes()
        ];
        
        // Save performance metrics
        $this->savePerformanceMetrics($report);
        
        return $report;
    }
    
    /**
     * Get slow queries report
     */
    private function getSlowQueriesReport($days) {
        return $this->db->query("
            SELECT 
                query_hash,
                LEFT(query_text, 100) as query_preview,
                execution_count,
                ROUND(total_time, 4) as total_time,
                ROUND(total_time / execution_count, 4) as avg_time,
                last_execution
            FROM slow_query_log
            WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY)
            ORDER BY total_time DESC
            LIMIT 20
        ")->fetchAll();
    }
    
    /**
     * Save performance metrics to database
     */
    private function savePerformanceMetrics($report) {
        $stmt = $this->db->prepare("
            INSERT INTO performance_metrics 
            (request_time, memory_usage, query_count, cache_hits, cache_misses, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $report['current_request']['total_time'],
            $report['current_request']['memory_usage'],
            $report['current_request']['queries'],
            $report['current_request']['cache_hits'],
            $report['current_request']['cache_misses']
        ]);
    }
}

/**
 * Advanced Cache Manager with multiple backends
 */
class AdvancedCacheManager {
    private $driver;
    private $config;
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0
    ];
    
    public function __construct($config) {
        $this->config = $config;
        $this->initializeDriver();
    }
    
    /**
     * Initialize cache driver based on configuration
     */
    private function initializeDriver() {
        $driver_type = $this->config['cache']['driver'] ?? 'file';
        
        switch ($driver_type) {
            case 'redis':
                $this->driver = new RedisCacheDriver($this->config);
                break;
            case 'memcached':
                $this->driver = new MemcachedCacheDriver($this->config);
                break;
            case 'file':
            default:
                $this->driver = new FileCacheDriver($this->config);
                break;
        }
    }
    
    /**
     * Get value from cache
     */
    public function get($key) {
        $result = $this->driver->get($key);
        
        if ($result !== false) {
            $this->stats['hits']++;
        } else {
            $this->stats['misses']++;
        }
        
        return $result;
    }
    
    /**
     * Set value in cache
     */
    public function set($key, $value, $ttl = 3600) {
        $this->stats['sets']++;
        return $this->driver->set($key, $value, $ttl);
    }
    
    /**
     * Delete value from cache
     */
    public function delete($key) {
        $this->stats['deletes']++;
        return $this->driver->delete($key);
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        return $this->driver->clear();
    }
    
    /**
     * Get cache statistics
     */
    public function getStatistics() {
        $driver_stats = $this->driver->getStatistics();
        return array_merge($this->stats, $driver_stats);
    }
}

/**
 * File-based cache driver
 */
class FileCacheDriver {
    private $cache_path;
    
    public function __construct($config) {
        $this->cache_path = $config['cache']['path'] ?? __DIR__ . '/../cache/';
        if (!is_dir($this->cache_path)) {
            mkdir($this->cache_path, 0755, true);
        }
    }
    
    public function get($key) {
        $file_path = $this->getFilePath($key);
        
        if (!file_exists($file_path)) {
            return false;
        }
        
        $data = file_get_contents($file_path);
        $cache_data = unserialize($data);
        
        if ($cache_data['expires'] < time()) {
            unlink($file_path);
            return false;
        }
        
        return $cache_data['value'];
    }
    
    public function set($key, $value, $ttl = 3600) {
        $file_path = $this->getFilePath($key);
        $cache_data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return file_put_contents($file_path, serialize($cache_data)) !== false;
    }
    
    public function delete($key) {
        $file_path = $this->getFilePath($key);
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return true;
    }
    
    public function clear() {
        $files = glob($this->cache_path . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
    
    public function getStatistics() {
        $files = glob($this->cache_path . '*.cache');
        $total_size = 0;
        $expired_count = 0;
        
        foreach ($files as $file) {
            $total_size += filesize($file);
            $data = unserialize(file_get_contents($file));
            if ($data['expires'] < time()) {
                $expired_count++;
            }
        }
        
        return [
            'total_entries' => count($files),
            'total_size_mb' => round($total_size / 1024 / 1024, 2),
            'expired_entries' => $expired_count
        ];
    }
    
    private function getFilePath($key) {
        return $this->cache_path . md5($key) . '.cache';
    }
}

/**
 * Resource Optimizer for static assets
 */
class ResourceOptimizer {
    private $config;
    private $cdn_url;
    
    public function __construct($config) {
        $this->config = $config;
        $this->cdn_url = $config['cdn']['url'] ?? '';
    }
    
    /**
     * Optimize CSS and JS files
     */
    public function optimizeAssets($assets_dir) {
        $optimization_results = [];
        
        // Minify CSS files
        $css_files = glob($assets_dir . '/css/*.css');
        foreach ($css_files as $css_file) {
            $minified = $this->minifyCSS(file_get_contents($css_file));
            $minified_file = str_replace('.css', '.min.css', $css_file);
            file_put_contents($minified_file, $minified);
            
            $original_size = filesize($css_file);
            $minified_size = filesize($minified_file);
            $optimization_results['css'][] = [
                'file' => basename($css_file),
                'original_size' => $original_size,
                'minified_size' => $minified_size,
                'savings' => round((($original_size - $minified_size) / $original_size) * 100, 2)
            ];
        }
        
        // Minify JS files
        $js_files = glob($assets_dir . '/js/*.js');
        foreach ($js_files as $js_file) {
            if (strpos($js_file, '.min.js') === false) {
                $minified = $this->minifyJS(file_get_contents($js_file));
                $minified_file = str_replace('.js', '.min.js', $js_file);
                file_put_contents($minified_file, $minified);
                
                $original_size = filesize($js_file);
                $minified_size = filesize($minified_file);
                $optimization_results['js'][] = [
                    'file' => basename($js_file),
                    'original_size' => $original_size,
                    'minified_size' => $minified_size,
                    'savings' => round((($original_size - $minified_size) / $original_size) * 100, 2)
                ];
            }
        }
        
        // Optimize images
        $image_files = glob($assets_dir . '/images/*.{jpg,jpeg,png}', GLOB_BRACE);
        foreach ($image_files as $image_file) {
            $optimization_results['images'][] = $this->optimizeImage($image_file);
        }
        
        return $optimization_results;
    }
    
    /**
     * Minify CSS content
     */
    private function minifyCSS($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        // Remove trailing semicolon before closing braces
        $css = str_replace(';}', '}', $css);
        return trim($css);
    }
    
    /**
     * Minify JavaScript content
     */
    private function minifyJS($js) {
        // Remove single line comments
        $js = preg_replace('!//.*!', '', $js);
        // Remove multi-line comments
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        // Remove whitespace
        $js = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $js);
        // Remove extra spaces
        $js = preg_replace('/\s+/', ' ', $js);
        return trim($js);
    }
    
    /**
     * Optimize image files
     */
    private function optimizeImage($image_path) {
        $original_size = filesize($image_path);
        $info = getimagesize($image_path);
        
        if (!$info) {
            return ['error' => 'Invalid image file: ' . basename($image_path)];
        }
        
        $mime_type = $info['mime'];
        $optimized_path = dirname($image_path) . '/optimized_' . basename($image_path);
        
        switch ($mime_type) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($image_path);
                imagejpeg($image, $optimized_path, 85); // 85% quality
                break;
            case 'image/png':
                $image = imagecreatefrompng($image_path);
                imagepng($image, $optimized_path, 6); // Compression level 6
                break;
            default:
                return ['error' => 'Unsupported image type: ' . $mime_type];
        }
        
        if (isset($image)) {
            imagedestroy($image);
        }
        
        $optimized_size = filesize($optimized_path);
        $savings = round((($original_size - $optimized_size) / $original_size) * 100, 2);
        
        return [
            'file' => basename($image_path),
            'original_size' => $original_size,
            'optimized_size' => $optimized_size,
            'savings' => $savings
        ];
    }
    
    /**
     * Generate CDN URLs for assets
     */
    public function getCDNUrl($asset_path) {
        if (empty($this->cdn_url)) {
            return $asset_path;
        }
        
        return rtrim($this->cdn_url, '/') . '/' . ltrim($asset_path, '/');
    }
    
    /**
     * Generate asset version for cache busting
     */
    public function getAssetVersion($asset_path) {
        if (file_exists($asset_path)) {
            return filemtime($asset_path);
        }
        return time();
    }
}

/**
 * Performance Monitor for real-time tracking
 */
class PerformanceMonitor {
    private $db;
    private $alerts = [];
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Monitor system performance
     */
    public function monitor() {
        $metrics = [
            'timestamp' => time(),
            'cpu_usage' => $this->getCPUUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'db_performance' => $this->getDatabasePerformance(),
            'response_times' => $this->getAverageResponseTimes()
        ];
        
        // Check for performance issues
        $this->checkPerformanceThresholds($metrics);
        
        // Save metrics
        $this->saveMetrics($metrics);
        
        return $metrics;
    }
    
    /**
     * Get CPU usage percentage
     */
    private function getCPUUsage() {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0] * 100, 2);
        }
        return null;
    }
    
    /**
     * Get memory usage statistics
     */
    private function getMemoryUsage() {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit')
        ];
    }
    
    /**
     * Get disk usage statistics
     */
    private function getDiskUsage() {
        $total_bytes = disk_total_space(".");
        $free_bytes = disk_free_space(".");
        $used_bytes = $total_bytes - $free_bytes;
        
        return [
            'total' => $total_bytes,
            'used' => $used_bytes,
            'free' => $free_bytes,
            'percentage' => round(($used_bytes / $total_bytes) * 100, 2)
        ];
    }
    
    /**
     * Get database performance metrics
     */
    private function getDatabasePerformance() {
        try {
            $start_time = microtime(true);
            $this->db->query("SELECT 1")->fetch();
            $db_response_time = microtime(true) - $start_time;
            
            return [
                'response_time' => $db_response_time,
                'status' => 'healthy'
            ];
        } catch (Exception $e) {
            return [
                'response_time' => null,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get average response times from recent requests
     */
    private function getAverageResponseTimes() {
        $stmt = $this->db->query("
            SELECT AVG(request_time) as avg_response_time
            FROM performance_metrics
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        return $stmt->fetchColumn() ?: 0;
    }
    
    /**
     * Check performance thresholds and generate alerts
     */
    private function checkPerformanceThresholds($metrics) {
        // CPU usage alert
        if ($metrics['cpu_usage'] && $metrics['cpu_usage'] > 80) {
            $this->alerts[] = [
                'type' => 'cpu_high',
                'message' => "High CPU usage: {$metrics['cpu_usage']}%",
                'severity' => 'warning'
            ];
        }
        
        // Memory usage alert
        $memory_percentage = ($metrics['memory_usage']['current'] / $this->parseBytes($metrics['memory_usage']['limit'])) * 100;
        if ($memory_percentage > 85) {
            $this->alerts[] = [
                'type' => 'memory_high',
                'message' => "High memory usage: " . round($memory_percentage, 2) . "%",
                'severity' => 'warning'
            ];
        }
        
        // Disk usage alert
        if ($metrics['disk_usage']['percentage'] > 90) {
            $this->alerts[] = [
                'type' => 'disk_high',
                'message' => "High disk usage: {$metrics['disk_usage']['percentage']}%",
                'severity' => 'critical'
            ];
        }
        
        // Database response time alert
        if ($metrics['db_performance']['response_time'] && $metrics['db_performance']['response_time'] > 1) {
            $this->alerts[] = [
                'type' => 'db_slow',
                'message' => "Slow database response: " . round($metrics['db_performance']['response_time'], 3) . "s",
                'severity' => 'warning'
            ];
        }
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parseBytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        
        return $val;
    }
    
    /**
     * Save performance metrics to database
     */
    private function saveMetrics($metrics) {
        $stmt = $this->db->prepare("
            INSERT INTO system_performance_metrics 
            (cpu_usage, memory_usage, disk_usage, db_response_time, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $metrics['cpu_usage'],
            json_encode($metrics['memory_usage']),
            json_encode($metrics['disk_usage']),
            $metrics['db_performance']['response_time']
        ]);
        
        // Save alerts
        foreach ($this->alerts as $alert) {
            $alert_stmt = $this->db->prepare("
                INSERT INTO performance_alerts (alert_type, message, severity, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $alert_stmt->execute([$alert['type'], $alert['message'], $alert['severity']]);
        }
    }
    
    /**
     * Get recent performance alerts
     */
    public function getRecentAlerts($hours = 24) {
        $stmt = $this->db->prepare("
            SELECT * FROM performance_alerts 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
            ORDER BY created_at DESC
        ");
        $stmt->execute([$hours]);
        
        return $stmt->fetchAll();
    }
}

?>
