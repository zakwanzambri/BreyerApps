-- Campus Hub Portal - Performance Monitoring Database Tables
-- Tables for performance tracking, optimization, and monitoring

-- Slow query logging
CREATE TABLE IF NOT EXISTS slow_query_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query_hash VARCHAR(32) NOT NULL,
    query_text TEXT NOT NULL,
    parameters TEXT NULL,
    execution_time DECIMAL(10,6) NOT NULL,
    execution_count INT DEFAULT 1,
    total_time DECIMAL(10,6) NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_execution TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_query (query_hash),
    INDEX idx_execution_time (execution_time),
    INDEX idx_execution_count (execution_count),
    INDEX idx_created_at (created_at)
);

-- Performance metrics for individual requests
CREATE TABLE IF NOT EXISTS performance_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_time DECIMAL(10,6) NOT NULL COMMENT 'Total request time in seconds',
    memory_usage BIGINT NOT NULL COMMENT 'Memory usage in bytes',
    query_count INT NOT NULL DEFAULT 0,
    cache_hits INT NOT NULL DEFAULT 0,
    cache_misses INT NOT NULL DEFAULT 0,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    request_uri VARCHAR(500) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_request_time (request_time),
    INDEX idx_memory_usage (memory_usage),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- System performance metrics
CREATE TABLE IF NOT EXISTS system_performance_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cpu_usage DECIMAL(5,2) NULL COMMENT 'CPU usage percentage',
    memory_usage JSON NULL COMMENT 'Memory usage statistics',
    disk_usage JSON NULL COMMENT 'Disk usage statistics',
    db_response_time DECIMAL(10,6) NULL COMMENT 'Database response time',
    active_connections INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cpu_usage (cpu_usage),
    INDEX idx_db_response_time (db_response_time),
    INDEX idx_created_at (created_at)
);

-- Performance alerts
CREATE TABLE IF NOT EXISTS performance_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type VARCHAR(50) NOT NULL COMMENT 'cpu_high, memory_high, disk_high, db_slow',
    message TEXT NOT NULL,
    severity ENUM('info', 'warning', 'critical') DEFAULT 'warning',
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    resolved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alert_type (alert_type),
    INDEX idx_severity (severity),
    INDEX idx_is_resolved (is_resolved),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Cache statistics
CREATE TABLE IF NOT EXISTS cache_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_type VARCHAR(50) NOT NULL COMMENT 'file, redis, memcached',
    hits BIGINT DEFAULT 0,
    misses BIGINT DEFAULT 0,
    sets BIGINT DEFAULT 0,
    deletes BIGINT DEFAULT 0,
    total_size BIGINT DEFAULT 0 COMMENT 'Total cache size in bytes',
    entry_count INT DEFAULT 0,
    hit_rate DECIMAL(5,2) GENERATED ALWAYS AS (
        CASE 
            WHEN (hits + misses) > 0 THEN (hits / (hits + misses)) * 100
            ELSE 0 
        END
    ) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cache_type (cache_type),
    INDEX idx_hit_rate (hit_rate),
    INDEX idx_created_at (created_at)
);

-- Database optimization recommendations
CREATE TABLE IF NOT EXISTS optimization_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recommendation_type VARCHAR(50) NOT NULL COMMENT 'index, query, table',
    table_name VARCHAR(64) NULL,
    column_name VARCHAR(64) NULL,
    current_performance DECIMAL(10,6) NULL,
    estimated_improvement DECIMAL(5,2) NULL COMMENT 'Estimated improvement percentage',
    recommendation_sql TEXT NULL,
    description TEXT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('pending', 'applied', 'rejected', 'obsolete') DEFAULT 'pending',
    applied_at TIMESTAMP NULL,
    applied_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_recommendation_type (recommendation_type),
    INDEX idx_table_name (table_name),
    INDEX idx_priority (priority),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (applied_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Resource usage tracking
CREATE TABLE IF NOT EXISTS resource_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type VARCHAR(50) NOT NULL COMMENT 'bandwidth, storage, cpu_time',
    resource_value DECIMAL(15,6) NOT NULL,
    resource_unit VARCHAR(20) NOT NULL COMMENT 'bytes, seconds, percentage',
    user_id INT NULL,
    session_id VARCHAR(128) NULL,
    ip_address VARCHAR(45) NULL,
    request_uri VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_resource_type (resource_type),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- CDN statistics (if using CDN)
CREATE TABLE IF NOT EXISTS cdn_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL COMMENT 'css, js, image, document',
    requests_count BIGINT DEFAULT 0,
    bytes_served BIGINT DEFAULT 0,
    cache_hit_ratio DECIMAL(5,2) DEFAULT 0,
    avg_response_time DECIMAL(8,3) DEFAULT 0,
    last_accessed TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_file_path (file_path(255)),
    INDEX idx_file_type (file_type),
    INDEX idx_requests_count (requests_count),
    INDEX idx_last_accessed (last_accessed)
);

-- Page load statistics
CREATE TABLE IF NOT EXISTS page_load_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(500) NOT NULL,
    load_time DECIMAL(8,3) NOT NULL COMMENT 'Page load time in milliseconds',
    dom_ready_time DECIMAL(8,3) NULL,
    first_contentful_paint DECIMAL(8,3) NULL,
    largest_contentful_paint DECIMAL(8,3) NULL,
    cumulative_layout_shift DECIMAL(8,6) NULL,
    user_id INT NULL,
    session_id VARCHAR(128) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page_url (page_url(255)),
    INDEX idx_load_time (load_time),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Add performance-related columns to existing tables if needed
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS last_activity_at TIMESTAMP NULL AFTER last_login_ip,
ADD COLUMN IF NOT EXISTS session_count INT DEFAULT 0 AFTER last_activity_at,
ADD COLUMN IF NOT EXISTS total_login_time BIGINT DEFAULT 0 AFTER session_count;

-- Create views for performance monitoring
CREATE OR REPLACE VIEW performance_overview AS
SELECT 
    'Average Request Time (24h)' as metric,
    ROUND(AVG(request_time), 4) as value,
    'seconds' as unit
FROM performance_metrics 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)

UNION ALL

SELECT 
    'Total Requests (24h)' as metric,
    COUNT(*) as value,
    'count' as unit
FROM performance_metrics 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)

UNION ALL

SELECT 
    'Cache Hit Rate (24h)' as metric,
    ROUND(
        (SUM(cache_hits) / (SUM(cache_hits) + SUM(cache_misses))) * 100, 2
    ) as value,
    'percentage' as unit
FROM performance_metrics 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
AND (cache_hits + cache_misses) > 0

UNION ALL

SELECT 
    'Slow Queries (24h)' as metric,
    COUNT(*) as value,
    'count' as unit
FROM slow_query_log 
WHERE last_execution > DATE_SUB(NOW(), INTERVAL 24 HOUR)
AND execution_time > 1

UNION ALL

SELECT 
    'Active Alerts' as metric,
    COUNT(*) as value,
    'count' as unit
FROM performance_alerts 
WHERE is_resolved = FALSE

UNION ALL

SELECT 
    'Average CPU Usage (24h)' as metric,
    ROUND(AVG(cpu_usage), 2) as value,
    'percentage' as unit
FROM system_performance_metrics 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
AND cpu_usage IS NOT NULL;

-- Create view for top slow queries
CREATE OR REPLACE VIEW top_slow_queries AS
SELECT 
    query_hash,
    LEFT(query_text, 100) as query_preview,
    execution_count,
    ROUND(total_time, 4) as total_time,
    ROUND(total_time / execution_count, 4) as avg_time,
    ROUND((total_time / execution_count) * execution_count, 4) as total_impact,
    last_execution
FROM slow_query_log
WHERE execution_count > 1
ORDER BY total_time DESC
LIMIT 20;

-- Create view for resource usage summary
CREATE OR REPLACE VIEW resource_usage_summary AS
SELECT 
    resource_type,
    resource_unit,
    COUNT(*) as usage_count,
    SUM(resource_value) as total_value,
    AVG(resource_value) as avg_value,
    MAX(resource_value) as max_value,
    DATE(created_at) as usage_date
FROM resource_usage
WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY resource_type, resource_unit, DATE(created_at)
ORDER BY usage_date DESC, total_value DESC;

-- Create stored procedures for performance operations
DELIMITER //

-- Procedure to analyze and create optimization recommendations
CREATE PROCEDURE IF NOT EXISTS AnalyzePerformance()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE table_name VARCHAR(64);
    DECLARE avg_query_time DECIMAL(10,6);
    
    -- Cursor for tables with slow queries
    DECLARE table_cursor CURSOR FOR
        SELECT DISTINCT 
            SUBSTRING_INDEX(SUBSTRING_INDEX(query_text, 'FROM ', -1), ' ', 1) as tbl,
            AVG(execution_time) as avg_time
        FROM slow_query_log 
        WHERE query_text LIKE '%FROM %'
        AND execution_time > 0.1
        GROUP BY tbl
        HAVING avg_time > 0.5;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Clear old recommendations
    DELETE FROM optimization_recommendations WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    OPEN table_cursor;
    
    read_loop: LOOP
        FETCH table_cursor INTO table_name, avg_query_time;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Check if table has proper indexes
        SET @index_count = (
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = table_name
            AND NON_UNIQUE = 0
        );
        
        IF @index_count < 2 THEN
            INSERT INTO optimization_recommendations 
            (recommendation_type, table_name, current_performance, description, priority)
            VALUES (
                'index',
                table_name,
                avg_query_time,
                CONCAT('Table ', table_name, ' has slow queries and may benefit from additional indexes'),
                CASE 
                    WHEN avg_query_time > 2 THEN 'high'
                    WHEN avg_query_time > 1 THEN 'medium'
                    ELSE 'low'
                END
            );
        END IF;
    END LOOP;
    
    CLOSE table_cursor;
    
    -- Recommend query optimizations for most expensive queries
    INSERT INTO optimization_recommendations 
    (recommendation_type, current_performance, recommendation_sql, description, priority)
    SELECT 
        'query',
        total_time,
        CONCAT('-- Optimize this query:\n', LEFT(query_text, 200), '...'),
        CONCAT('Query executed ', execution_count, ' times with total time of ', ROUND(total_time, 2), ' seconds'),
        CASE 
            WHEN total_time > 10 THEN 'critical'
            WHEN total_time > 5 THEN 'high'
            WHEN total_time > 2 THEN 'medium'
            ELSE 'low'
        END
    FROM slow_query_log 
    WHERE total_time > 1
    AND id NOT IN (
        SELECT SUBSTRING_INDEX(description, ' ', -1) 
        FROM optimization_recommendations 
        WHERE recommendation_type = 'query'
    )
    ORDER BY total_time DESC
    LIMIT 10;
    
    SELECT 'Performance analysis completed' as status;
END//

-- Procedure to cleanup performance data
CREATE PROCEDURE IF NOT EXISTS CleanupPerformanceData()
BEGIN
    DECLARE deleted_metrics INT DEFAULT 0;
    DECLARE deleted_alerts INT DEFAULT 0;
    DECLARE deleted_logs INT DEFAULT 0;
    
    -- Delete old performance metrics (older than 30 days)
    DELETE FROM performance_metrics WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    SET deleted_metrics = ROW_COUNT();
    
    -- Delete old system metrics (older than 90 days)
    DELETE FROM system_performance_metrics WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Delete resolved alerts (older than 7 days)
    DELETE FROM performance_alerts 
    WHERE is_resolved = TRUE AND resolved_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    SET deleted_alerts = ROW_COUNT();
    
    -- Delete old slow query logs (keep only last 1000 entries per query)
    DELETE sq1 FROM slow_query_log sq1
    INNER JOIN (
        SELECT query_hash, id
        FROM (
            SELECT query_hash, id,
                   ROW_NUMBER() OVER (PARTITION BY query_hash ORDER BY last_execution DESC) as rn
            FROM slow_query_log
        ) ranked
        WHERE rn > 1000
    ) sq2 ON sq1.id = sq2.id;
    SET deleted_logs = ROW_COUNT();
    
    -- Clean up resource usage data (older than 60 days)
    DELETE FROM resource_usage WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY);
    
    -- Clean up page load statistics (older than 30 days)
    DELETE FROM page_load_statistics WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Update cache statistics
    UPDATE cache_statistics SET 
        updated_at = NOW()
    WHERE updated_at < DATE_SUB(NOW(), INTERVAL 1 DAY);
    
    SELECT deleted_metrics as metrics_deleted,
           deleted_alerts as alerts_deleted,
           deleted_logs as slow_queries_deleted;
END//

-- Procedure to generate performance report
CREATE PROCEDURE IF NOT EXISTS GeneratePerformanceReport(IN days_back INT)
BEGIN
    -- Performance overview
    SELECT 'Performance Overview' as section;
    SELECT * FROM performance_overview;
    
    -- Request statistics
    SELECT 'Request Statistics' as section;
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total_requests,
        ROUND(AVG(request_time), 4) as avg_response_time,
        ROUND(MAX(request_time), 4) as max_response_time,
        ROUND(AVG(memory_usage / 1024 / 1024), 2) as avg_memory_mb,
        ROUND(AVG(query_count), 1) as avg_queries_per_request
    FROM performance_metrics 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL days_back DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC;
    
    -- Top slow queries
    SELECT 'Top Slow Queries' as section;
    SELECT * FROM top_slow_queries;
    
    -- System performance trends
    SELECT 'System Performance Trends' as section;
    SELECT 
        DATE(created_at) as date,
        ROUND(AVG(cpu_usage), 2) as avg_cpu_usage,
        ROUND(AVG(db_response_time), 4) as avg_db_response_time,
        COUNT(*) as measurements
    FROM system_performance_metrics 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL days_back DAY)
    AND cpu_usage IS NOT NULL
    GROUP BY DATE(created_at)
    ORDER BY date DESC;
    
    -- Active alerts
    SELECT 'Active Performance Alerts' as section;
    SELECT 
        alert_type,
        COUNT(*) as alert_count,
        MAX(created_at) as latest_alert
    FROM performance_alerts 
    WHERE is_resolved = FALSE
    GROUP BY alert_type
    ORDER BY alert_count DESC;
    
    -- Optimization recommendations
    SELECT 'Optimization Recommendations' as section;
    SELECT 
        recommendation_type,
        priority,
        COUNT(*) as recommendation_count
    FROM optimization_recommendations 
    WHERE status = 'pending'
    GROUP BY recommendation_type, priority
    ORDER BY FIELD(priority, 'critical', 'high', 'medium', 'low');
END//

DELIMITER ;

-- Create indexes for better performance monitoring queries
CREATE INDEX IF NOT EXISTS idx_performance_metrics_composite ON performance_metrics(created_at, request_time, user_id);
CREATE INDEX IF NOT EXISTS idx_slow_query_composite ON slow_query_log(execution_time, execution_count, last_execution);
CREATE INDEX IF NOT EXISTS idx_system_metrics_composite ON system_performance_metrics(created_at, cpu_usage, db_response_time);

-- Insert default cache statistics entry
INSERT IGNORE INTO cache_statistics (cache_type, hits, misses, sets, deletes)
VALUES ('file', 0, 0, 0, 0);

-- Create events for automatic performance maintenance (if events are enabled)
/*
CREATE EVENT IF NOT EXISTS performance_cleanup
ON SCHEDULE EVERY 1 DAY
STARTS '2024-01-01 03:00:00'
DO
  CALL CleanupPerformanceData();

CREATE EVENT IF NOT EXISTS performance_analysis
ON SCHEDULE EVERY 1 WEEK
STARTS '2024-01-01 04:00:00'
DO
  CALL AnalyzePerformance();
*/
