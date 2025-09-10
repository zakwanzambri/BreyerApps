-- Campus Hub Portal - User Analytics Database Tables
-- Tables for comprehensive user behavior tracking and analytics

-- User behavior tracking
CREATE TABLE IF NOT EXISTS user_behavior_tracking (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(128) NOT NULL,
    action_type VARCHAR(50) NOT NULL COMMENT 'page_view, click, search, download, etc.',
    page_url VARCHAR(500) NULL,
    element_id VARCHAR(100) NULL COMMENT 'Button ID, link ID, etc.',
    element_type VARCHAR(50) NULL COMMENT 'button, link, form, etc.',
    content_id INT NULL COMMENT 'Related news/event ID',
    content_type VARCHAR(50) NULL COMMENT 'news, event, user',
    action_data JSON NULL COMMENT 'Additional action-specific data',
    referrer_url VARCHAR(500) NULL,
    user_agent TEXT NULL,
    ip_address VARCHAR(45) NULL,
    screen_resolution VARCHAR(20) NULL,
    viewport_size VARCHAR(20) NULL,
    time_spent INT NULL COMMENT 'Time spent on page in seconds',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_action_type (action_type),
    INDEX idx_content_id_type (content_id, content_type),
    INDEX idx_created_at (created_at),
    INDEX idx_page_url (page_url(255)),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- User engagement metrics
CREATE TABLE IF NOT EXISTS user_engagement_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    session_count INT DEFAULT 0,
    total_session_time INT DEFAULT 0 COMMENT 'Total time in seconds',
    page_views INT DEFAULT 0,
    unique_pages_viewed INT DEFAULT 0,
    actions_performed INT DEFAULT 0,
    content_consumed INT DEFAULT 0 COMMENT 'News read, events viewed',
    events_registered INT DEFAULT 0,
    search_queries INT DEFAULT 0,
    downloads INT DEFAULT 0,
    social_shares INT DEFAULT 0,
    engagement_score DECIMAL(5,2) DEFAULT 0 COMMENT 'Calculated engagement score',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_date (date),
    INDEX idx_engagement_score (engagement_score),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Content analytics
CREATE TABLE IF NOT EXISTS content_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(50) NOT NULL COMMENT 'news, event',
    content_id INT NOT NULL,
    date DATE NOT NULL,
    views INT DEFAULT 0,
    unique_viewers INT DEFAULT 0,
    time_spent_total INT DEFAULT 0 COMMENT 'Total time spent by all users',
    time_spent_avg DECIMAL(8,2) DEFAULT 0 COMMENT 'Average time spent',
    shares INT DEFAULT 0,
    downloads INT DEFAULT 0,
    registrations INT DEFAULT 0 COMMENT 'For events',
    bounce_rate DECIMAL(5,2) DEFAULT 0 COMMENT 'Percentage who left quickly',
    engagement_rate DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_content_date (content_type, content_id, date),
    INDEX idx_content_type (content_type),
    INDEX idx_content_id (content_id),
    INDEX idx_date (date),
    INDEX idx_views (views),
    INDEX idx_engagement_rate (engagement_rate)
);

-- User journey tracking
CREATE TABLE IF NOT EXISTS user_journey_tracking (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(128) NOT NULL,
    journey_step INT NOT NULL COMMENT 'Sequential step number in journey',
    page_url VARCHAR(500) NOT NULL,
    page_title VARCHAR(200) NULL,
    entry_time TIMESTAMP NOT NULL,
    exit_time TIMESTAMP NULL,
    time_spent INT NULL COMMENT 'Time spent on this page in seconds',
    scroll_depth DECIMAL(5,2) NULL COMMENT 'Percentage of page scrolled',
    interactions INT DEFAULT 0 COMMENT 'Number of interactions on page',
    exit_method VARCHAR(50) NULL COMMENT 'navigation, close, timeout',
    next_page VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_session (user_id, session_id),
    INDEX idx_journey_step (journey_step),
    INDEX idx_page_url (page_url(255)),
    INDEX idx_entry_time (entry_time),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Search analytics
CREATE TABLE IF NOT EXISTS search_analytics (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(128) NULL,
    search_query VARCHAR(500) NOT NULL,
    search_type VARCHAR(50) NOT NULL COMMENT 'global, news, events, users',
    results_count INT DEFAULT 0,
    clicked_result_position INT NULL COMMENT 'Position of clicked result (1-based)',
    clicked_result_id INT NULL,
    clicked_result_type VARCHAR(50) NULL,
    search_time DECIMAL(8,3) NULL COMMENT 'Time to complete search in seconds',
    refined_search BOOLEAN DEFAULT FALSE COMMENT 'Whether user refined the search',
    no_results BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_search_query (search_query(255)),
    INDEX idx_search_type (search_type),
    INDEX idx_created_at (created_at),
    INDEX idx_no_results (no_results),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Device and browser analytics
CREATE TABLE IF NOT EXISTS device_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(128) NOT NULL,
    device_type VARCHAR(50) NOT NULL COMMENT 'desktop, mobile, tablet',
    operating_system VARCHAR(100) NULL,
    browser VARCHAR(100) NULL,
    browser_version VARCHAR(50) NULL,
    screen_resolution VARCHAR(20) NULL,
    viewport_size VARCHAR(20) NULL,
    is_mobile BOOLEAN DEFAULT FALSE,
    is_touch_device BOOLEAN DEFAULT FALSE,
    connection_type VARCHAR(50) NULL COMMENT 'wifi, cellular, ethernet',
    page_load_time DECIMAL(8,3) NULL COMMENT 'Initial page load time',
    session_start TIMESTAMP NOT NULL,
    session_end TIMESTAMP NULL,
    session_duration INT NULL COMMENT 'Session duration in seconds',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_device_type (device_type),
    INDEX idx_operating_system (operating_system),
    INDEX idx_browser (browser),
    INDEX idx_session_start (session_start),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Geographic analytics
CREATE TABLE IF NOT EXISTS geographic_analytics (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(128) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    country VARCHAR(100) NULL,
    region VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    timezone VARCHAR(100) NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    isp VARCHAR(200) NULL,
    organization VARCHAR(200) NULL,
    is_campus_network BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_country (country),
    INDEX idx_is_campus_network (is_campus_network),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Conversion tracking
CREATE TABLE IF NOT EXISTS conversion_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(128) NOT NULL,
    conversion_type VARCHAR(50) NOT NULL COMMENT 'registration, login, event_signup, download',
    conversion_goal VARCHAR(100) NOT NULL,
    conversion_value DECIMAL(10,2) NULL COMMENT 'Value assigned to conversion',
    source_page VARCHAR(500) NULL COMMENT 'Page where conversion originated',
    conversion_path JSON NULL COMMENT 'Array of pages leading to conversion',
    time_to_convert INT NULL COMMENT 'Time from first visit to conversion in seconds',
    touchpoints INT DEFAULT 1 COMMENT 'Number of interactions before conversion',
    campaign_source VARCHAR(100) NULL,
    campaign_medium VARCHAR(100) NULL,
    campaign_name VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_conversion_type (conversion_type),
    INDEX idx_conversion_goal (conversion_goal),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- A/B testing and experiments
CREATE TABLE IF NOT EXISTS ab_test_experiments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_name VARCHAR(100) NOT NULL,
    experiment_description TEXT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('draft', 'active', 'paused', 'completed') DEFAULT 'draft',
    target_metric VARCHAR(100) NOT NULL COMMENT 'What we are measuring',
    variants JSON NOT NULL COMMENT 'Array of variant configurations',
    traffic_split JSON NOT NULL COMMENT 'Traffic allocation percentages',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_experiment_name (experiment_name),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- A/B test participation
CREATE TABLE IF NOT EXISTS ab_test_participation (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    experiment_id INT NOT NULL,
    user_id INT NULL,
    session_id VARCHAR(128) NOT NULL,
    variant_name VARCHAR(100) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    converted BOOLEAN DEFAULT FALSE,
    converted_at TIMESTAMP NULL,
    conversion_value DECIMAL(10,2) NULL,
    INDEX idx_experiment_id (experiment_id),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_variant_name (variant_name),
    INDEX idx_converted (converted),
    FOREIGN KEY (experiment_id) REFERENCES ab_test_experiments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Real-time analytics summary
CREATE TABLE IF NOT EXISTS realtime_analytics_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_type VARCHAR(50) NOT NULL COMMENT 'active_users, page_views, events, etc.',
    metric_value BIGINT NOT NULL,
    time_window INT NOT NULL COMMENT 'Time window in minutes (1, 5, 15, 30, 60)',
    timestamp_start TIMESTAMP NOT NULL,
    timestamp_end TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_metric_window (metric_type, time_window, timestamp_start),
    INDEX idx_metric_type (metric_type),
    INDEX idx_time_window (time_window),
    INDEX idx_timestamp_start (timestamp_start)
);

-- Create views for analytics dashboards
CREATE OR REPLACE VIEW user_engagement_overview AS
SELECT 
    u.id,
    u.username,
    u.name,
    u.role,
    u.created_at as registered_at,
    COUNT(DISTINCT ue.date) as active_days,
    COALESCE(SUM(ue.session_count), 0) as total_sessions,
    COALESCE(SUM(ue.total_session_time), 0) as total_time_seconds,
    COALESCE(SUM(ue.page_views), 0) as total_page_views,
    COALESCE(SUM(ue.actions_performed), 0) as total_actions,
    COALESCE(AVG(ue.engagement_score), 0) as avg_engagement_score,
    MAX(ue.date) as last_active_date
FROM users u
LEFT JOIN user_engagement_metrics ue ON u.id = ue.user_id
WHERE u.status = 'active'
GROUP BY u.id, u.username, u.name, u.role, u.created_at;

CREATE OR REPLACE VIEW content_performance_overview AS
SELECT 
    ca.content_type,
    ca.content_id,
    CASE 
        WHEN ca.content_type = 'news' THEN n.title
        WHEN ca.content_type = 'event' THEN e.title
        ELSE 'Unknown'
    END as content_title,
    SUM(ca.views) as total_views,
    SUM(ca.unique_viewers) as total_unique_viewers,
    AVG(ca.time_spent_avg) as avg_time_spent,
    SUM(ca.shares) as total_shares,
    AVG(ca.engagement_rate) as avg_engagement_rate,
    MIN(ca.date) as first_tracked,
    MAX(ca.date) as last_tracked
FROM content_analytics ca
LEFT JOIN news n ON ca.content_type = 'news' AND ca.content_id = n.id
LEFT JOIN events e ON ca.content_type = 'event' AND ca.content_id = e.id
WHERE ca.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY ca.content_type, ca.content_id
ORDER BY total_views DESC;

CREATE OR REPLACE VIEW popular_search_terms AS
SELECT 
    search_query,
    COUNT(*) as search_count,
    AVG(results_count) as avg_results,
    SUM(CASE WHEN clicked_result_position IS NOT NULL THEN 1 ELSE 0 END) as click_throughs,
    ROUND(
        (SUM(CASE WHEN clicked_result_position IS NOT NULL THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2
    ) as click_through_rate,
    SUM(CASE WHEN no_results = TRUE THEN 1 ELSE 0 END) as no_results_count
FROM search_analytics
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY search_query
HAVING search_count > 1
ORDER BY search_count DESC, click_through_rate DESC
LIMIT 50;

CREATE OR REPLACE VIEW device_usage_stats AS
SELECT 
    device_type,
    COUNT(*) as session_count,
    COUNT(DISTINCT user_id) as unique_users,
    AVG(session_duration) as avg_session_duration,
    AVG(page_load_time) as avg_page_load_time
FROM device_analytics
WHERE session_start >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY device_type
ORDER BY session_count DESC;

-- Create stored procedures for analytics operations
DELIMITER //

-- Procedure to calculate daily engagement scores
CREATE PROCEDURE IF NOT EXISTS CalculateEngagementScores(IN target_date DATE)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE user_id_var INT;
    DECLARE engagement_score DECIMAL(5,2);
    
    DECLARE user_cursor CURSOR FOR
        SELECT DISTINCT user_id
        FROM user_behavior_tracking
        WHERE DATE(created_at) = target_date;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN user_cursor;
    
    read_loop: LOOP
        FETCH user_cursor INTO user_id_var;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Calculate engagement score based on various factors
        SELECT 
            LEAST(100, (
                -- Page views (max 20 points)
                LEAST(20, page_views * 0.5) +
                -- Time spent (max 25 points)
                LEAST(25, (total_session_time / 60) * 0.1) +
                -- Actions performed (max 20 points)
                LEAST(20, actions_performed * 0.3) +
                -- Content consumed (max 15 points)
                LEAST(15, content_consumed * 2) +
                -- Event registrations (max 10 points)
                LEAST(10, events_registered * 5) +
                -- Search activity (max 10 points)
                LEAST(10, search_queries * 1)
            )) INTO engagement_score
        FROM user_engagement_metrics
        WHERE user_id = user_id_var AND date = target_date;
        
        -- Update the engagement score
        UPDATE user_engagement_metrics
        SET engagement_score = COALESCE(engagement_score, 0)
        WHERE user_id = user_id_var AND date = target_date;
        
    END LOOP;
    
    CLOSE user_cursor;
    
    SELECT CONCAT('Engagement scores calculated for ', ROW_COUNT(), ' users on ', target_date) as result;
END//

-- Procedure to generate analytics report
CREATE PROCEDURE IF NOT EXISTS GenerateAnalyticsReport(IN days_back INT)
BEGIN
    -- User activity overview
    SELECT 'User Activity Overview' as section;
    SELECT 
        DATE(ue.date) as date,
        COUNT(DISTINCT ue.user_id) as active_users,
        SUM(ue.session_count) as total_sessions,
        SUM(ue.page_views) as total_page_views,
        SUM(ue.actions_performed) as total_actions,
        AVG(ue.engagement_score) as avg_engagement_score
    FROM user_engagement_metrics ue
    WHERE ue.date >= DATE_SUB(CURDATE(), INTERVAL days_back DAY)
    GROUP BY DATE(ue.date)
    ORDER BY date DESC;
    
    -- Content performance
    SELECT 'Top Content Performance' as section;
    SELECT * FROM content_performance_overview LIMIT 20;
    
    -- Device usage
    SELECT 'Device Usage Statistics' as section;
    SELECT * FROM device_usage_stats;
    
    -- Search analytics
    SELECT 'Popular Search Terms' as section;
    SELECT * FROM popular_search_terms LIMIT 15;
    
    -- Geographic distribution
    SELECT 'Geographic Distribution' as section;
    SELECT 
        country,
        COUNT(*) as sessions,
        COUNT(DISTINCT user_id) as unique_users,
        SUM(CASE WHEN is_campus_network THEN 1 ELSE 0 END) as campus_sessions
    FROM geographic_analytics
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL days_back DAY)
    GROUP BY country
    ORDER BY sessions DESC
    LIMIT 20;
    
    -- Conversion metrics
    SELECT 'Conversion Metrics' as section;
    SELECT 
        conversion_type,
        COUNT(*) as conversions,
        AVG(time_to_convert / 60) as avg_time_to_convert_minutes,
        AVG(touchpoints) as avg_touchpoints
    FROM conversion_tracking
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL days_back DAY)
    GROUP BY conversion_type
    ORDER BY conversions DESC;
END//

-- Procedure to cleanup old analytics data
CREATE PROCEDURE IF NOT EXISTS CleanupAnalyticsData()
BEGIN
    DECLARE deleted_behavior INT DEFAULT 0;
    DECLARE deleted_journey INT DEFAULT 0;
    DECLARE deleted_search INT DEFAULT 0;
    
    -- Delete old behavior tracking (older than 90 days)
    DELETE FROM user_behavior_tracking WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    SET deleted_behavior = ROW_COUNT();
    
    -- Delete old journey tracking (older than 60 days)
    DELETE FROM user_journey_tracking WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY);
    SET deleted_journey = ROW_COUNT();
    
    -- Delete old search analytics (older than 180 days)
    DELETE FROM search_analytics WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY);
    SET deleted_search = ROW_COUNT();
    
    -- Delete old device analytics (older than 180 days)
    DELETE FROM device_analytics WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY);
    
    -- Delete old geographic analytics (older than 180 days)
    DELETE FROM geographic_analytics WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY);
    
    -- Keep engagement metrics and content analytics for longer (1 year)
    DELETE FROM user_engagement_metrics WHERE date < DATE_SUB(CURDATE(), INTERVAL 365 DAY);
    DELETE FROM content_analytics WHERE date < DATE_SUB(CURDATE(), INTERVAL 365 DAY);
    
    -- Delete old real-time summaries (older than 30 days)
    DELETE FROM realtime_analytics_summary WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    SELECT deleted_behavior as behavior_tracking_deleted,
           deleted_journey as journey_tracking_deleted,
           deleted_search as search_analytics_deleted;
END//

DELIMITER ;

-- Create indexes for better analytics performance
CREATE INDEX IF NOT EXISTS idx_behavior_tracking_composite ON user_behavior_tracking(user_id, action_type, created_at);
CREATE INDEX IF NOT EXISTS idx_engagement_metrics_composite ON user_engagement_metrics(date, engagement_score, user_id);
CREATE INDEX IF NOT EXISTS idx_content_analytics_composite ON content_analytics(content_type, date, views);
CREATE INDEX IF NOT EXISTS idx_search_analytics_composite ON search_analytics(search_query(100), created_at, results_count);

-- Insert sample A/B test experiment
INSERT IGNORE INTO ab_test_experiments 
(experiment_name, experiment_description, start_date, status, target_metric, variants, traffic_split, created_by)
VALUES 
('Homepage Layout Test', 'Testing different homepage layouts for better engagement', CURDATE(), 'draft', 'engagement_rate',
 JSON_ARRAY(
     JSON_OBJECT('name', 'control', 'description', 'Current homepage layout'),
     JSON_OBJECT('name', 'variant_a', 'description', 'New layout with featured content'),
     JSON_OBJECT('name', 'variant_b', 'description', 'Minimal layout with quick actions')
 ),
 JSON_OBJECT('control', 50, 'variant_a', 25, 'variant_b', 25),
 1);

-- Create events for automatic analytics maintenance
/*
CREATE EVENT IF NOT EXISTS calculate_daily_engagement
ON SCHEDULE EVERY 1 DAY
STARTS TIMESTAMP(CURDATE() + INTERVAL 1 DAY, '01:00:00')
DO
  CALL CalculateEngagementScores(CURDATE() - INTERVAL 1 DAY);

CREATE EVENT IF NOT EXISTS cleanup_analytics_data
ON SCHEDULE EVERY 1 WEEK
STARTS '2024-01-01 02:00:00'
DO
  CALL CleanupAnalyticsData();
*/
