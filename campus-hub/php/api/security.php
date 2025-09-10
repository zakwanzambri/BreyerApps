<?php
/**
 * Campus Hub Portal - Security Enhancement Module
 * Enhanced security features and utilities
 * 
 * Features:
 * - Advanced password validation
 * - Session security management
 * - CSRF protection
 * - XSS prevention
 * - SQL injection prevention
 * - Rate limiting enhancements
 * - Security headers
 * - Input sanitization
 * - File upload security
 * - Audit logging
 */

class SecurityManager {
    private $config;
    private $db;
    private $session_timeout = 1800; // 30 minutes
    private $max_login_attempts = 5;
    private $lockout_duration = 900; // 15 minutes
    
    public function __construct($config, $db) {
        $this->config = $config;
        $this->db = $db;
        $this->initializeSecurity();
    }
    
    /**
     * Initialize security settings
     */
    private function initializeSecurity() {
        // Set security headers
        $this->setSecurityHeaders();
        
        // Initialize session security
        $this->initializeSessionSecurity();
        
        // Start session timeout monitoring
        $this->monitorSessionTimeout();
    }
    
    /**
     * Set comprehensive security headers
     */
    public function setSecurityHeaders() {
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Strict transport security (HTTPS only)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self'; " .
               "media-src 'self'; " .
               "object-src 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self';";
        header("Content-Security-Policy: $csp");
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Feature policy
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        
        // Remove server information
        header_remove('Server');
        header_remove('X-Powered-By');
    }
    
    /**
     * Initialize secure session configuration
     */
    private function initializeSessionSecurity() {
        // Session configuration
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', $this->session_timeout);
        
        // Custom session save path
        $session_path = __DIR__ . '/../sessions';
        if (!is_dir($session_path)) {
            mkdir($session_path, 0700, true);
        }
        ini_set('session.save_path', $session_path);
        
        // Regenerate session ID periodically
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            }
            
            if (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    
    /**
     * Monitor and enforce session timeout
     */
    private function monitorSessionTimeout() {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            $last_activity = $_SESSION['last_activity'] ?? time();
            
            if (time() - $last_activity > $this->session_timeout) {
                $this->logSecurityEvent('session_timeout', $_SESSION['user_id'], 'Session timed out');
                session_destroy();
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Session expired']);
                exit;
            }
            
            $_SESSION['last_activity'] = time();
        }
    }
    
    /**
     * Validate password strength
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        
        // Minimum length
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        // Maximum length
        if (strlen($password) > 128) {
            $errors[] = 'Password must be less than 128 characters long';
        }
        
        // Must contain uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        // Must contain lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        // Must contain number
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        // Must contain special character
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        // Check for common patterns
        $common_patterns = [
            '/(.)\1{2,}/', // Repeated characters
            '/123456|654321|qwerty|password|admin/', // Common passwords
            '/(.)(.*?\1){2,}/' // Pattern repetition
        ];
        
        foreach ($common_patterns as $pattern) {
            if (preg_match($pattern, strtolower($password))) {
                $errors[] = 'Password contains common patterns and is not secure';
                break;
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => $this->calculatePasswordStrength($password)
        ];
    }
    
    /**
     * Calculate password strength score
     */
    private function calculatePasswordStrength($password) {
        $score = 0;
        
        // Length scoring
        $length = strlen($password);
        if ($length >= 8) $score += 10;
        if ($length >= 12) $score += 10;
        if ($length >= 16) $score += 10;
        
        // Character variety scoring
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/\d/', $password)) $score += 10;
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) $score += 15;
        
        // Bonus for mixed case and numbers
        if (preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password)) $score += 5;
        if (preg_match('/\d/', $password) && preg_match('/[a-zA-Z]/', $password)) $score += 5;
        
        // Complexity bonus
        $unique_chars = count(array_unique(str_split($password)));
        $score += min($unique_chars * 2, 20);
        
        // Penalty for common patterns
        if (preg_match('/(.)\1{2,}/', $password)) $score -= 10;
        if (preg_match('/123|abc|qwe/i', $password)) $score -= 10;
        
        return min(max($score, 0), 100);
    }
    
    /**
     * Check login attempts and enforce lockout
     */
    public function checkLoginAttempts($identifier) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt 
            FROM login_attempts 
            WHERE identifier = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$identifier, $this->lockout_duration]);
        $result = $stmt->fetch();
        
        if ($result['attempts'] >= $this->max_login_attempts) {
            $remaining_time = $this->lockout_duration - (time() - strtotime($result['last_attempt']));
            if ($remaining_time > 0) {
                return [
                    'locked' => true,
                    'remaining_time' => $remaining_time,
                    'message' => "Account locked. Try again in " . ceil($remaining_time / 60) . " minutes."
                ];
            }
        }
        
        return ['locked' => false];
    }
    
    /**
     * Record login attempt
     */
    public function recordLoginAttempt($identifier, $success, $user_id = null, $ip = null, $user_agent = null) {
        $ip = $ip ?: $this->getClientIP();
        $user_agent = $user_agent ?: ($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (identifier, user_id, ip_address, user_agent, success, attempt_time) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$identifier, $user_id, $ip, $user_agent, $success ? 1 : 0]);
        
        // Clean old attempts
        $this->db->prepare("
            DELETE FROM login_attempts 
            WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ")->execute();
        
        // Log security event
        $event_type = $success ? 'login_success' : 'login_failure';
        $this->logSecurityEvent($event_type, $user_id, "Login attempt from IP: $ip");
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_time'] = time();
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_time'])) {
            return false;
        }
        
        // Token expires after 1 hour
        if (time() - $_SESSION['csrf_time'] > 3600) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize input to prevent XSS
     */
    public function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return $this->sanitizeInput($item, $type);
            }, $input);
        }
        
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            case 'html':
                // Allow safe HTML tags
                $allowed_tags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img>';
                return strip_tags($input, $allowed_tags);
            
            case 'filename':
                // Sanitize filename
                $input = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $input);
                return substr($input, 0, 255);
            
            case 'string':
            default:
                // Remove HTML tags and encode special characters
                $input = strip_tags($input);
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate file upload security
     */
    public function validateFileUpload($file, $allowed_types = [], $max_size = null) {
        $errors = [];
        
        // Check if file was uploaded
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed with error code: ' . $file['error'];
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        $max_size = $max_size ?: 10 * 1024 * 1024; // 10MB default
        if ($file['size'] > $max_size) {
            $errors[] = 'File size exceeds maximum allowed size of ' . ($max_size / 1024 / 1024) . 'MB';
        }
        
        // Check file type
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $file['tmp_name']);
        finfo_close($file_info);
        
        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Define safe MIME types and extensions
        $safe_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain'
        ];
        
        if (!empty($allowed_types)) {
            $safe_types = array_intersect_key($safe_types, array_flip($allowed_types));
        }
        
        if (!isset($safe_types[$extension]) || $safe_types[$extension] !== $mime_type) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', array_keys($safe_types));
        }
        
        // Additional security checks
        if (in_array($extension, ['php', 'phtml', 'php3', 'php4', 'php5', 'pl', 'py', 'jsp', 'asp', 'sh'])) {
            $errors[] = 'Executable files are not allowed';
        }
        
        // Check for malicious content (basic)
        $file_content = file_get_contents($file['tmp_name'], false, null, 0, 1024);
        if (preg_match('/<\?php|<script|<\?=/', $file_content)) {
            $errors[] = 'File contains potentially malicious content';
        }
        
        // Validate image files
        if (strpos($mime_type, 'image/') === 0) {
            $image_info = getimagesize($file['tmp_name']);
            if (!$image_info) {
                $errors[] = 'Invalid image file';
            } else {
                // Check image dimensions
                if ($image_info[0] > 5000 || $image_info[1] > 5000) {
                    $errors[] = 'Image dimensions too large (max 5000x5000)';
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mime_type,
            'extension' => $extension
        ];
    }
    
    /**
     * Get client IP address
     */
    public function getClientIP() {
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
     * Check for suspicious activity
     */
    public function detectSuspiciousActivity($user_id) {
        $ip = $this->getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Check for multiple IPs in short time
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT ip_address) as ip_count 
            FROM login_attempts 
            WHERE user_id = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR) AND success = 1
        ");
        $stmt->execute([$user_id]);
        $ip_count = $stmt->fetchColumn();
        
        if ($ip_count > 3) {
            $this->logSecurityEvent('suspicious_multiple_ips', $user_id, "Multiple IPs detected: $ip_count");
            return true;
        }
        
        // Check for unusual user agent
        $stmt = $this->db->prepare("
            SELECT user_agent FROM login_attempts 
            WHERE user_id = ? AND success = 1 
            ORDER BY attempt_time DESC LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $recent_agents = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($recent_agents) && !in_array($user_agent, $recent_agents)) {
            $this->logSecurityEvent('suspicious_user_agent', $user_id, "New user agent: $user_agent");
        }
        
        // Check for rapid requests
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as request_count 
            FROM activity_logs 
            WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        $stmt->execute([$user_id]);
        $request_count = $stmt->fetchColumn();
        
        if ($request_count > 60) { // More than 60 requests per minute
            $this->logSecurityEvent('suspicious_rapid_requests', $user_id, "Rapid requests: $request_count/minute");
            return true;
        }
        
        return false;
    }
    
    /**
     * Log security events
     */
    public function logSecurityEvent($event_type, $user_id = null, $description = '', $severity = 'medium') {
        $ip = $this->getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        $stmt = $this->db->prepare("
            INSERT INTO security_logs 
            (event_type, user_id, ip_address, user_agent, request_uri, description, severity, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $event_type,
            $user_id,
            $ip,
            $user_agent,
            $request_uri,
            $description,
            $severity
        ]);
        
        // Alert on high severity events
        if ($severity === 'high') {
            $this->sendSecurityAlert($event_type, $description);
        }
    }
    
    /**
     * Send security alert to administrators
     */
    private function sendSecurityAlert($event_type, $description) {
        // Get admin emails
        $stmt = $this->db->prepare("SELECT email FROM users WHERE role = 'admin' AND status = 'active'");
        $stmt->execute();
        $admin_emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($admin_emails)) {
            $subject = "Security Alert: " . ucwords(str_replace('_', ' ', $event_type));
            $message = "A high-severity security event has been detected:\n\n";
            $message .= "Event Type: " . $event_type . "\n";
            $message .= "Description: " . $description . "\n";
            $message .= "Time: " . date('Y-m-d H:i:s') . "\n";
            $message .= "IP Address: " . $this->getClientIP() . "\n";
            $message .= "\nPlease review the security logs for more details.";
            
            foreach ($admin_emails as $email) {
                mail($email, $subject, $message);
            }
        }
    }
    
    /**
     * Perform security audit
     */
    public function performSecurityAudit() {
        $audit_results = [];
        
        // Check for weak passwords (if we can access hashed passwords)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as weak_passwords 
            FROM users 
            WHERE LENGTH(password) < 60 OR password LIKE '%password%' OR password LIKE '%123456%'
        ");
        $stmt->execute();
        $audit_results['weak_passwords'] = $stmt->fetchColumn();
        
        // Check for inactive admin accounts
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as inactive_admins 
            FROM users 
            WHERE role = 'admin' AND status = 'inactive'
        ");
        $stmt->execute();
        $audit_results['inactive_admins'] = $stmt->fetchColumn();
        
        // Check for failed login attempts
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as failed_logins 
            FROM login_attempts 
            WHERE success = 0 AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
        $audit_results['failed_logins_24h'] = $stmt->fetchColumn();
        
        // Check for suspicious activities
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as high_severity_events 
            FROM security_logs 
            WHERE severity = 'high' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        $audit_results['high_severity_events_7d'] = $stmt->fetchColumn();
        
        // Check session security
        $session_path = session_save_path();
        $audit_results['session_path_permissions'] = is_writable($session_path) ? 'writable' : 'not_writable';
        
        // Check file permissions
        $upload_path = __DIR__ . '/../uploads';
        $audit_results['upload_path_permissions'] = is_writable($upload_path) ? 'writable' : 'not_writable';
        
        return $audit_results;
    }
    
    /**
     * Clean up security data
     */
    public function cleanupSecurityData() {
        // Remove old login attempts (older than 30 days)
        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        $deleted_attempts = $stmt->rowCount();
        
        // Remove old security logs (older than 90 days, except high severity)
        $stmt = $this->db->prepare("
            DELETE FROM security_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY) AND severity != 'high'
        ");
        $stmt->execute();
        $deleted_logs = $stmt->rowCount();
        
        // Remove old sessions
        $session_path = session_save_path();
        if (is_dir($session_path)) {
            $files = glob($session_path . '/sess_*');
            $deleted_sessions = 0;
            foreach ($files as $file) {
                if (filemtime($file) < time() - 86400) { // 24 hours old
                    unlink($file);
                    $deleted_sessions++;
                }
            }
        }
        
        return [
            'deleted_login_attempts' => $deleted_attempts,
            'deleted_security_logs' => $deleted_logs,
            'deleted_sessions' => $deleted_sessions ?? 0
        ];
    }
}

// Enhanced Rate Limiter with IP-based tracking
class AdvancedRateLimiter {
    private $db;
    private $limits = [
        'api' => ['requests' => 200, 'window' => 3600], // 200 requests per hour
        'login' => ['requests' => 10, 'window' => 900], // 10 attempts per 15 minutes
        'upload' => ['requests' => 20, 'window' => 3600], // 20 uploads per hour
        'search' => ['requests' => 100, 'window' => 3600] // 100 searches per hour
    ];
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function checkLimit($type, $identifier = null) {
        $identifier = $identifier ?: $this->getClientIP();
        $limit = $this->limits[$type] ?? $this->limits['api'];
        
        // Clean old entries
        $this->db->prepare("
            DELETE FROM rate_limits 
            WHERE request_time < DATE_SUB(NOW(), INTERVAL ? SECOND)
        ")->execute([$limit['window']]);
        
        // Count current requests
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM rate_limits 
            WHERE identifier = ? AND request_type = ? 
            AND request_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$identifier, $type, $limit['window']]);
        $count = $stmt->fetchColumn();
        
        if ($count >= $limit['requests']) {
            return [
                'allowed' => false,
                'limit' => $limit['requests'],
                'remaining' => 0,
                'reset_time' => time() + $limit['window']
            ];
        }
        
        // Record request
        $this->db->prepare("
            INSERT INTO rate_limits (identifier, request_type, request_time) 
            VALUES (?, ?, NOW())
        ")->execute([$identifier, $type]);
        
        return [
            'allowed' => true,
            'limit' => $limit['requests'],
            'remaining' => $limit['requests'] - $count - 1,
            'reset_time' => time() + $limit['window']
        ];
    }
    
    private function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

?>
