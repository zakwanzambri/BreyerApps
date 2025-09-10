<?php
/**
 * Campus Hub Portal - Analytics Tracking API
 * Comprehensive user behavior and analytics tracking system
 * 
 * Features:
 * - User behavior tracking
 * - Page view analytics
 * - Content engagement metrics
 * - Search analytics
 * - Device and browser tracking
 * - Geographic analytics
 * - Conversion tracking
 * - Real-time analytics
 * - A/B testing support
 */

require_once __DIR__ . '/config.php';

class AnalyticsTracker {
    private $db;
    private $config;
    private $user_id;
    private $session_id;
    private $ip_address;
    private $user_agent;
    
    public function __construct($db, $config) {
        $this->db = $db;
        $this->config = $config;
        $this->session_id = session_id();
        $this->user_id = $_SESSION['user_id'] ?? null;
        $this->ip_address = $this->getClientIP();
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Initialize session tracking
        $this->initializeSession();
    }
    
    /**
     * Initialize session tracking
     */
    private function initializeSession() {
        if (!$this->session_id) {
            return;
        }
        
        // Check if this is a new session
        $stmt = $this->db->prepare("
            SELECT id FROM device_analytics 
            WHERE session_id = ? AND session_end IS NULL
        ");
        $stmt->execute([$this->session_id]);
        
        if (!$stmt->fetch()) {
            $this->trackNewSession();
        }
    }
    
    /**
     * Track new session start
     */
    private function trackNewSession() {
        $device_info = $this->getDeviceInfo();
        $geo_info = $this->getGeographicInfo();
        
        // Insert device analytics
        $stmt = $this->db->prepare("
            INSERT INTO device_analytics 
            (user_id, session_id, device_type, operating_system, browser, browser_version,
             screen_resolution, viewport_size, is_mobile, is_touch_device, 
             connection_type, session_start)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $this->user_id,
            $this->session_id,
            $device_info['device_type'],
            $device_info['os'],
            $device_info['browser'],
            $device_info['browser_version'],
            $device_info['screen_resolution'],
            $device_info['viewport_size'],
            $device_info['is_mobile'],
            $device_info['is_touch'],
            $device_info['connection_type']
        ]);
        
        // Insert geographic analytics
        if ($geo_info) {
            $stmt = $this->db->prepare("
                INSERT INTO geographic_analytics 
                (user_id, session_id, ip_address, country, region, city, 
                 timezone, latitude, longitude, isp, organization, is_campus_network)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $this->user_id,
                $this->session_id,
                $this->ip_address,
                $geo_info['country'],
                $geo_info['region'],
                $geo_info['city'],
                $geo_info['timezone'],
                $geo_info['latitude'],
                $geo_info['longitude'],
                $geo_info['isp'],
                $geo_info['organization'],
                $geo_info['is_campus_network']
            ]);
        }
    }
    
    /**
     * Track user behavior action
     */
    public function trackAction($action_type, $data = []) {
        if (!$this->session_id) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO user_behavior_tracking 
            (user_id, session_id, action_type, page_url, element_id, element_type,
             content_id, content_type, action_data, referrer_url, user_agent, 
             ip_address, screen_resolution, viewport_size, time_spent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $this->user_id,
            $this->session_id,
            $action_type,
            $data['page_url'] ?? $_SERVER['REQUEST_URI'] ?? null,
            $data['element_id'] ?? null,
            $data['element_type'] ?? null,
            $data['content_id'] ?? null,
            $data['content_type'] ?? null,
            !empty($data['action_data']) ? json_encode($data['action_data']) : null,
            $data['referrer_url'] ?? $_SERVER['HTTP_REFERER'] ?? null,
            $this->user_agent,
            $this->ip_address,
            $data['screen_resolution'] ?? null,
            $data['viewport_size'] ?? null,
            $data['time_spent'] ?? null
        ]);
        
        // Update real-time metrics
        $this->updateRealTimeMetrics($action_type);
        
        return $result;
    }
    
    /**
     * Track page view
     */
    public function trackPageView($page_url, $page_title = null, $time_spent = null) {
        // Track as behavior
        $this->trackAction('page_view', [
            'page_url' => $page_url,
            'time_spent' => $time_spent
        ]);
        
        // Track journey
        $this->trackJourneyStep($page_url, $page_title);
        
        // Update daily metrics
        $this->updateDailyMetrics('page_views');
        
        return true;
    }
    
    /**
     * Track content engagement
     */
    public function trackContentEngagement($content_type, $content_id, $engagement_data = []) {
        // Track behavior
        $this->trackAction('content_engagement', [
            'content_type' => $content_type,
            'content_id' => $content_id,
            'action_data' => $engagement_data
        ]);
        
        // Update content analytics
        $this->updateContentAnalytics($content_type, $content_id, $engagement_data);
        
        return true;
    }
    
    /**
     * Track search query
     */
    public function trackSearch($query, $search_type, $results_count, $search_time = null) {
        $stmt = $this->db->prepare("
            INSERT INTO search_analytics 
            (user_id, session_id, search_query, search_type, results_count, 
             search_time, no_results, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $this->user_id,
            $this->session_id,
            $query,
            $search_type,
            $results_count,
            $search_time,
            $results_count == 0 ? 1 : 0,
            $this->ip_address,
            $this->user_agent
        ]);
        
        // Update daily metrics
        $this->updateDailyMetrics('search_queries');
        
        return $result;
    }
    
    /**
     * Track search result click
     */
    public function trackSearchClick($query, $result_position, $result_id, $result_type) {
        $stmt = $this->db->prepare("
            UPDATE search_analytics 
            SET clicked_result_position = ?, clicked_result_id = ?, clicked_result_type = ?
            WHERE user_id = ? AND session_id = ? AND search_query = ?
            AND clicked_result_position IS NULL
            ORDER BY created_at DESC LIMIT 1
        ");
        
        return $stmt->execute([
            $result_position,
            $result_id,
            $result_type,
            $this->user_id,
            $this->session_id,
            $query
        ]);
    }
    
    /**
     * Track conversion
     */
    public function trackConversion($conversion_type, $conversion_goal, $conversion_value = null, $data = []) {
        $stmt = $this->db->prepare("
            INSERT INTO conversion_tracking 
            (user_id, session_id, conversion_type, conversion_goal, conversion_value,
             source_page, conversion_path, time_to_convert, touchpoints,
             campaign_source, campaign_medium, campaign_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $this->user_id,
            $this->session_id,
            $conversion_type,
            $conversion_goal,
            $conversion_value,
            $data['source_page'] ?? $_SERVER['HTTP_REFERER'] ?? null,
            !empty($data['conversion_path']) ? json_encode($data['conversion_path']) : null,
            $data['time_to_convert'] ?? null,
            $data['touchpoints'] ?? 1,
            $data['campaign_source'] ?? null,
            $data['campaign_medium'] ?? null,
            $data['campaign_name'] ?? null
        ]);
        
        // Update daily metrics for conversions
        $this->updateDailyMetrics('conversions', $conversion_type);
        
        return $result;
    }
    
    /**
     * Track journey step
     */
    private function trackJourneyStep($page_url, $page_title = null) {
        if (!$this->user_id || !$this->session_id) {
            return false;
        }
        
        // Get current journey step
        $stmt = $this->db->prepare("
            SELECT MAX(journey_step) as last_step 
            FROM user_journey_tracking 
            WHERE user_id = ? AND session_id = ?
        ");
        $stmt->execute([$this->user_id, $this->session_id]);
        $last_step = $stmt->fetchColumn() ?: 0;
        
        // End previous step
        if ($last_step > 0) {
            $this->db->prepare("
                UPDATE user_journey_tracking 
                SET exit_time = NOW(), 
                    time_spent = TIMESTAMPDIFF(SECOND, entry_time, NOW()),
                    next_page = ?
                WHERE user_id = ? AND session_id = ? AND journey_step = ?
                AND exit_time IS NULL
            ")->execute([$page_url, $this->user_id, $this->session_id, $last_step]);
        }
        
        // Insert new step
        $stmt = $this->db->prepare("
            INSERT INTO user_journey_tracking 
            (user_id, session_id, journey_step, page_url, page_title, entry_time)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $this->user_id,
            $this->session_id,
            $last_step + 1,
            $page_url,
            $page_title
        ]);
    }
    
    /**
     * Update daily engagement metrics
     */
    private function updateDailyMetrics($metric_type, $sub_type = null) {
        if (!$this->user_id) {
            return false;
        }
        
        $today = date('Y-m-d');
        
        // Insert or update daily metrics
        $stmt = $this->db->prepare("
            INSERT INTO user_engagement_metrics 
            (user_id, date, session_count, page_views, actions_performed, 
             content_consumed, events_registered, search_queries, downloads, social_shares)
            VALUES (?, ?, 1, 0, 0, 0, 0, 0, 0, 0)
            ON DUPLICATE KEY UPDATE
            page_views = CASE WHEN ? = 'page_views' THEN page_views + 1 ELSE page_views END,
            actions_performed = actions_performed + 1,
            content_consumed = CASE WHEN ? = 'content_consumed' THEN content_consumed + 1 ELSE content_consumed END,
            events_registered = CASE WHEN ? = 'event_registration' THEN events_registered + 1 ELSE events_registered END,
            search_queries = CASE WHEN ? = 'search_queries' THEN search_queries + 1 ELSE search_queries END,
            downloads = CASE WHEN ? = 'downloads' THEN downloads + 1 ELSE downloads END,
            social_shares = CASE WHEN ? = 'social_shares' THEN social_shares + 1 ELSE social_shares END
        ");
        
        return $stmt->execute([
            $this->user_id, $today, $metric_type, $metric_type, 
            $sub_type, $metric_type, $metric_type, $metric_type
        ]);
    }
    
    /**
     * Update content analytics
     */
    private function updateContentAnalytics($content_type, $content_id, $engagement_data) {
        $today = date('Y-m-d');
        
        $stmt = $this->db->prepare("
            INSERT INTO content_analytics 
            (content_type, content_id, date, views, unique_viewers, time_spent_total, shares, downloads)
            VALUES (?, ?, ?, 1, 1, ?, 0, 0)
            ON DUPLICATE KEY UPDATE
            views = views + 1,
            time_spent_total = time_spent_total + COALESCE(?, 0),
            time_spent_avg = time_spent_total / views,
            shares = CASE WHEN ? = 'share' THEN shares + 1 ELSE shares END,
            downloads = CASE WHEN ? = 'download' THEN downloads + 1 ELSE downloads END
        ");
        
        $time_spent = $engagement_data['time_spent'] ?? 0;
        $action = $engagement_data['action'] ?? 'view';
        
        return $stmt->execute([
            $content_type, $content_id, $today, $time_spent, 
            $time_spent, $action, $action
        ]);
    }
    
    /**
     * Update real-time metrics
     */
    private function updateRealTimeMetrics($metric_type) {
        $windows = [1, 5, 15, 30, 60]; // minutes
        
        foreach ($windows as $window) {
            $start_time = date('Y-m-d H:i:00', strtotime("-$window minutes"));
            $end_time = date('Y-m-d H:i:59', strtotime("-$window minutes"));
            
            $stmt = $this->db->prepare("
                INSERT INTO realtime_analytics_summary 
                (metric_type, metric_value, time_window, timestamp_start, timestamp_end)
                VALUES (?, 1, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                metric_value = metric_value + 1
            ");
            
            $stmt->execute([$metric_type, $window, $start_time, $end_time]);
        }
    }
    
    /**
     * Get device information
     */
    private function getDeviceInfo() {
        $user_agent = $this->user_agent;
        
        // Detect device type
        $is_mobile = preg_match('/Mobile|Android|iPhone|iPad/', $user_agent);
        $is_tablet = preg_match('/iPad|Tablet/', $user_agent);
        
        if ($is_tablet) {
            $device_type = 'tablet';
        } elseif ($is_mobile) {
            $device_type = 'mobile';
        } else {
            $device_type = 'desktop';
        }
        
        // Detect OS
        $os = 'Unknown';
        if (preg_match('/Windows NT 10/', $user_agent)) $os = 'Windows 10';
        elseif (preg_match('/Windows NT 6\.3/', $user_agent)) $os = 'Windows 8.1';
        elseif (preg_match('/Windows NT 6\.2/', $user_agent)) $os = 'Windows 8';
        elseif (preg_match('/Windows NT 6\.1/', $user_agent)) $os = 'Windows 7';
        elseif (preg_match('/Mac OS X/', $user_agent)) $os = 'macOS';
        elseif (preg_match('/Linux/', $user_agent)) $os = 'Linux';
        elseif (preg_match('/Android/', $user_agent)) $os = 'Android';
        elseif (preg_match('/iOS/', $user_agent)) $os = 'iOS';
        
        // Detect browser
        $browser = 'Unknown';
        $browser_version = '';
        if (preg_match('/Chrome\/([0-9\.]+)/', $user_agent, $matches)) {
            $browser = 'Chrome';
            $browser_version = $matches[1];
        } elseif (preg_match('/Firefox\/([0-9\.]+)/', $user_agent, $matches)) {
            $browser = 'Firefox';
            $browser_version = $matches[1];
        } elseif (preg_match('/Safari\/([0-9\.]+)/', $user_agent, $matches)) {
            $browser = 'Safari';
            $browser_version = $matches[1];
        } elseif (preg_match('/Edge\/([0-9\.]+)/', $user_agent, $matches)) {
            $browser = 'Edge';
            $browser_version = $matches[1];
        }
        
        return [
            'device_type' => $device_type,
            'os' => $os,
            'browser' => $browser,
            'browser_version' => $browser_version,
            'is_mobile' => $is_mobile ? 1 : 0,
            'is_touch' => $is_mobile ? 1 : 0, // Simplified detection
            'screen_resolution' => null, // Would be passed from frontend
            'viewport_size' => null, // Would be passed from frontend
            'connection_type' => null // Would be detected via JS
        ];
    }
    
    /**
     * Get geographic information (simplified - in production use GeoIP service)
     */
    private function getGeographicInfo() {
        // This is a simplified version
        // In production, use services like MaxMind GeoIP, IPInfo.io, etc.
        
        $ip = $this->ip_address;
        
        // Check if it's a campus network (customize these ranges)
        $campus_networks = [
            '192.168.0.0/16',
            '10.0.0.0/8',
            '172.16.0.0/12'
        ];
        
        $is_campus_network = false;
        foreach ($campus_networks as $network) {
            if ($this->ipInRange($ip, $network)) {
                $is_campus_network = true;
                break;
            }
        }
        
        // For demo purposes, return basic info
        return [
            'country' => 'Malaysia', // Would be detected
            'region' => null,
            'city' => null,
            'timezone' => 'Asia/Kuala_Lumpur',
            'latitude' => null,
            'longitude' => null,
            'isp' => null,
            'organization' => null,
            'is_campus_network' => $is_campus_network
        ];
    }
    
    /**
     * Check if IP is in range
     */
    private function ipInRange($ip, $cidr) {
        list($range, $netmask) = explode('/', $cidr, 2);
        $range_decimal = ip2long($range);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~ $wildcard_decimal;
        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * End session tracking
     */
    public function endSession() {
        if (!$this->session_id) {
            return false;
        }
        
        // Update device analytics with session end
        $stmt = $this->db->prepare("
            UPDATE device_analytics 
            SET session_end = NOW(),
                session_duration = TIMESTAMPDIFF(SECOND, session_start, NOW())
            WHERE session_id = ? AND session_end IS NULL
        ");
        
        $stmt->execute([$this->session_id]);
        
        // End any open journey steps
        $stmt = $this->db->prepare("
            UPDATE user_journey_tracking 
            SET exit_time = NOW(),
                time_spent = TIMESTAMPDIFF(SECOND, entry_time, NOW()),
                exit_method = 'session_end'
            WHERE session_id = ? AND exit_time IS NULL
        ");
        
        return $stmt->execute([$this->session_id]);
    }
}

class AnalyticsReporter {
    private $db;
    private $config;
    
    public function __construct($db, $config) {
        $this->db = $db;
        $this->config = $config;
    }
    
    /**
     * Get user engagement overview
     */
    public function getUserEngagementOverview($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(ue.date) as date,
                COUNT(DISTINCT ue.user_id) as active_users,
                SUM(ue.session_count) as total_sessions,
                SUM(ue.page_views) as total_page_views,
                SUM(ue.actions_performed) as total_actions,
                AVG(ue.engagement_score) as avg_engagement_score
            FROM user_engagement_metrics ue
            WHERE ue.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(ue.date)
            ORDER BY date DESC
        ");
        
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get top content performance
     */
    public function getTopContent($limit = 20, $days = 30) {
        $stmt = $this->db->prepare("
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
                AVG(ca.engagement_rate) as avg_engagement_rate
            FROM content_analytics ca
            LEFT JOIN news n ON ca.content_type = 'news' AND ca.content_id = n.id
            LEFT JOIN events e ON ca.content_type = 'event' AND ca.content_id = e.id
            WHERE ca.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY ca.content_type, ca.content_id
            ORDER BY total_views DESC
            LIMIT ?
        ");
        
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get device usage statistics
     */
    public function getDeviceStats($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                device_type,
                operating_system,
                browser,
                COUNT(*) as session_count,
                COUNT(DISTINCT user_id) as unique_users,
                AVG(session_duration) as avg_session_duration
            FROM device_analytics
            WHERE session_start >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY device_type, operating_system, browser
            ORDER BY session_count DESC
        ");
        
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get search analytics
     */
    public function getSearchAnalytics($days = 30, $limit = 50) {
        $stmt = $this->db->prepare("
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
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY search_query
            HAVING search_count > 1
            ORDER BY search_count DESC
            LIMIT ?
        ");
        
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get real-time analytics
     */
    public function getRealTimeAnalytics($time_window = 5) {
        $stmt = $this->db->prepare("
            SELECT 
                metric_type,
                SUM(metric_value) as total_value
            FROM realtime_analytics_summary
            WHERE time_window = ? 
            AND timestamp_start >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            GROUP BY metric_type
            ORDER BY total_value DESC
        ");
        
        $stmt->execute([$time_window, $time_window]);
        return $stmt->fetchAll();
    }
    
    /**
     * Generate comprehensive analytics report
     */
    public function generateReport($days = 30) {
        return [
            'overview' => $this->getUserEngagementOverview($days),
            'top_content' => $this->getTopContent(20, $days),
            'device_stats' => $this->getDeviceStats($days),
            'search_analytics' => $this->getSearchAnalytics($days, 20),
            'real_time' => $this->getRealTimeAnalytics(5),
            'generated_at' => date('Y-m-d H:i:s'),
            'period_days' => $days
        ];
    }
}

// API endpoint handling
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    try {
        $database = Database::getInstance();
        $db = $database->getConnection();
        $reporter = new AnalyticsReporter($db, $config);
        
        switch ($action) {
            case 'overview':
                $days = intval($_GET['days'] ?? 30);
                $data = $reporter->getUserEngagementOverview($days);
                ResponseHelper::success($data);
                break;
                
            case 'content-performance':
                $days = intval($_GET['days'] ?? 30);
                $limit = intval($_GET['limit'] ?? 20);
                $data = $reporter->getTopContent($limit, $days);
                ResponseHelper::success($data);
                break;
                
            case 'device-stats':
                $days = intval($_GET['days'] ?? 30);
                $data = $reporter->getDeviceStats($days);
                ResponseHelper::success($data);
                break;
                
            case 'search-analytics':
                $days = intval($_GET['days'] ?? 30);
                $limit = intval($_GET['limit'] ?? 50);
                $data = $reporter->getSearchAnalytics($days, $limit);
                ResponseHelper::success($data);
                break;
                
            case 'real-time':
                $window = intval($_GET['window'] ?? 5);
                $data = $reporter->getRealTimeAnalytics($window);
                ResponseHelper::success($data);
                break;
                
            case 'report':
                $days = intval($_GET['days'] ?? 30);
                $data = $reporter->generateReport($days);
                ResponseHelper::success($data);
                break;
                
            default:
                ResponseHelper::error('Invalid action', 400);
        }
        
    } catch (Exception $e) {
        ErrorLogger::log('Analytics API Error: ' . $e->getMessage(), $e->getTrace());
        ResponseHelper::error('Failed to retrieve analytics data');
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    
    try {
        $database = Database::getInstance();
        $db = $database->getConnection();
        $tracker = new AnalyticsTracker($db, $config);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'track-action':
                $action_type = $input['action_type'] ?? '';
                $data = $input['data'] ?? [];
                
                if (empty($action_type)) {
                    ResponseHelper::error('Action type is required', 400);
                    break;
                }
                
                $result = $tracker->trackAction($action_type, $data);
                ResponseHelper::success(['tracked' => $result]);
                break;
                
            case 'track-page-view':
                $page_url = $input['page_url'] ?? '';
                $page_title = $input['page_title'] ?? null;
                $time_spent = $input['time_spent'] ?? null;
                
                if (empty($page_url)) {
                    ResponseHelper::error('Page URL is required', 400);
                    break;
                }
                
                $result = $tracker->trackPageView($page_url, $page_title, $time_spent);
                ResponseHelper::success(['tracked' => $result]);
                break;
                
            case 'track-search':
                $query = $input['query'] ?? '';
                $search_type = $input['search_type'] ?? 'global';
                $results_count = $input['results_count'] ?? 0;
                $search_time = $input['search_time'] ?? null;
                
                if (empty($query)) {
                    ResponseHelper::error('Search query is required', 400);
                    break;
                }
                
                $result = $tracker->trackSearch($query, $search_type, $results_count, $search_time);
                ResponseHelper::success(['tracked' => $result]);
                break;
                
            case 'track-conversion':
                $conversion_type = $input['conversion_type'] ?? '';
                $conversion_goal = $input['conversion_goal'] ?? '';
                $conversion_value = $input['conversion_value'] ?? null;
                $data = $input['data'] ?? [];
                
                if (empty($conversion_type) || empty($conversion_goal)) {
                    ResponseHelper::error('Conversion type and goal are required', 400);
                    break;
                }
                
                $result = $tracker->trackConversion($conversion_type, $conversion_goal, $conversion_value, $data);
                ResponseHelper::success(['tracked' => $result]);
                break;
                
            default:
                ResponseHelper::error('Invalid action', 400);
        }
        
    } catch (Exception $e) {
        ErrorLogger::log('Analytics Tracking Error: ' . $e->getMessage(), $e->getTrace());
        ResponseHelper::error('Failed to track analytics data');
    }
    
} else {
    ResponseHelper::error('Method not allowed', 405);
}

?>
