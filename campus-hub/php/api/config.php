<?php
/**
 * API Configuration and Utilities
 * Campus Hub Portal - Enhanced Version
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS configuration
$allowed_origins = ['http://localhost', 'http://127.0.0.1', 'http://localhost:3000'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database Configuration
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $host = 'localhost';
        $dbname = 'campus_hub';
        $username = 'root';
        $password = '';
        
        try {
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            ErrorLogger::log('Database connection failed: ' . $e->getMessage());
            ResponseHelper::error('Database connection failed', 500);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }
}

// Response Helper
class ResponseHelper {
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    public static function error($message = 'An error occurred', $code = 400, $details = null) {
        http_response_code($code);
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    public static function paginated($data, $total, $page, $limit, $message = 'Success') {
        $totalPages = ceil($total / $limit);
        
        self::success([
            'items' => $data,
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => $totalPages,
                'total_items' => (int)$total,
                'items_per_page' => (int)$limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ], $message);
    }
}

// Session Manager
class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function requireAuth() {
        self::start();
        if (!isset($_SESSION['user_id'])) {
            ResponseHelper::error('Authentication required', 401);
        }
    }
    
    public static function requireRole($allowedRoles = []) {
        self::requireAuth();
        
        if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles)) {
            ResponseHelper::error('Insufficient permissions', 403);
        }
    }
    
    public static function getCurrentUser() {
        self::start();
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'name' => $_SESSION['name'] ?? null
        ];
    }
    
    public static function isLoggedIn() {
        self::start();
        return isset($_SESSION['user_id']);
    }
}

// Input Validator
class InputValidator {
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Required check
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "Field '$field' is required";
                continue;
            }
            
            // Skip validation if field is empty and not required
            if (empty($value)) continue;
            
            // Type validation
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Invalid email format";
                        }
                        break;
                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$field] = "Invalid URL format";
                        }
                        break;
                    case 'date':
                        if (!strtotime($value)) {
                            $errors[$field] = "Invalid date format";
                        }
                        break;
                    case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[$field] = "Must be a number";
                        }
                        break;
                }
            }
            
            // Length validation
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = "Minimum length is {$rule['min_length']} characters";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = "Maximum length is {$rule['max_length']} characters";
            }
            
            // Custom validation
            if (isset($rule['custom']) && is_callable($rule['custom'])) {
                $customResult = $rule['custom']($value);
                if ($customResult !== true) {
                    $errors[$field] = $customResult;
                }
            }
        }
        
        return $errors;
    }
    
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

// Error Logger
class ErrorLogger {
    private static $logFile = '../logs/error.log';
    
    public static function log($message, $level = 'ERROR') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        // Create logs directory if it doesn't exist
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function logException($exception, $context = '') {
        $message = "Exception: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}";
        if ($context) {
            $message .= " Context: $context";
        }
        self::log($message, 'EXCEPTION');
    }
}

// File Upload Helper
class FileUploadHelper {
    private static $uploadDir = '../uploads/';
    private static $allowedTypes = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'document' => ['pdf', 'doc', 'docx', 'txt'],
        'media' => ['mp4', 'avi', 'mov', 'mp3', 'wav']
    ];
    
    public static function upload($file, $type = 'image', $maxSize = 5242880) { // 5MB default
        try {
            // Create upload directory if it doesn't exist
            if (!is_dir(self::$uploadDir)) {
                mkdir(self::$uploadDir, 0755, true);
            }
            
            // Validate file
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload error');
            }
            
            if ($file['size'] > $maxSize) {
                throw new Exception('File size exceeds limit');
            }
            
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, self::$allowedTypes[$type] ?? [])) {
                throw new Exception('Invalid file type');
            }
            
            // Generate unique filename
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $filepath = self::$uploadDir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Failed to move uploaded file');
            }
            
            return [
                'filename' => $filename,
                'filepath' => $filepath,
                'url' => str_replace('../', '', $filepath),
                'size' => $file['size'],
                'type' => $file['type']
            ];
            
        } catch (Exception $e) {
            ErrorLogger::log('File upload failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public static function delete($filepath) {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}

// Cache Helper
class CacheHelper {
    private static $cacheDir = '../cache/';
    
    public static function get($key) {
        $filename = self::$cacheDir . md5($key) . '.cache';
        
        if (file_exists($filename)) {
            $data = unserialize(file_get_contents($filename));
            if ($data['expires'] > time()) {
                return $data['value'];
            } else {
                unlink($filename);
            }
        }
        
        return null;
    }
    
    public static function set($key, $value, $ttl = 3600) {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
        
        $filename = self::$cacheDir . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($filename, serialize($data), LOCK_EX);
    }
    
    public static function delete($key) {
        $filename = self::$cacheDir . md5($key) . '.cache';
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return false;
    }
    
    public static function clear() {
        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}

// Rate Limiter
class RateLimiter {
    private static $limitsFile = '../cache/rate_limits.json';
    
    public static function check($ip, $limit = 100, $window = 3600) {
        $limits = self::getLimits();
        $now = time();
        
        // Clean old entries
        foreach ($limits as $key => $data) {
            if ($data['reset'] < $now) {
                unset($limits[$key]);
            }
        }
        
        // Check current IP
        if (!isset($limits[$ip])) {
            $limits[$ip] = [
                'count' => 0,
                'reset' => $now + $window
            ];
        }
        
        $limits[$ip]['count']++;
        self::saveLimits($limits);
        
        if ($limits[$ip]['count'] > $limit) {
            ResponseHelper::error('Rate limit exceeded. Try again later.', 429);
        }
        
        return true;
    }
    
    private static function getLimits() {
        if (file_exists(self::$limitsFile)) {
            return json_decode(file_get_contents(self::$limitsFile), true) ?: [];
        }
        return [];
    }
    
    private static function saveLimits($limits) {
        $dir = dirname(self::$limitsFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(self::$limitsFile, json_encode($limits), LOCK_EX);
    }
}

// Email Helper
class EmailHelper {
    public static function send($to, $subject, $message, $from = 'noreply@campushub.edu') {
        $headers = [
            'From' => $from,
            'Reply-To' => $from,
            'Content-Type' => 'text/html; charset=UTF-8',
            'MIME-Version' => '1.0'
        ];
        
        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "$key: $value\r\n";
        }
        
        try {
            $success = mail($to, $subject, $message, $headerString);
            
            if ($success) {
                ErrorLogger::log("Email sent successfully to: $to", 'INFO');
            } else {
                ErrorLogger::log("Failed to send email to: $to");
            }
            
            return $success;
        } catch (Exception $e) {
            ErrorLogger::logException($e, "Email sending to: $to");
            return false;
        }
    }
    
    public static function sendTemplate($to, $template, $data = []) {
        $templatePath = "../templates/email/$template.html";
        
        if (!file_exists($templatePath)) {
            ErrorLogger::log("Email template not found: $template");
            return false;
        }
        
        $content = file_get_contents($templatePath);
        
        // Replace template variables
        foreach ($data as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }
        
        return self::send($to, $data['subject'] ?? 'Campus Hub Notification', $content);
    }
}

// Search Helper
class SearchHelper {
    public static function buildWhereClause($searchTerm, $fields) {
        if (empty($searchTerm) || empty($fields)) {
            return ['', []];
        }
        
        $conditions = [];
        $params = [];
        
        foreach ($fields as $field) {
            $conditions[] = "$field LIKE ?";
            $params[] = "%$searchTerm%";
        }
        
        $whereClause = '(' . implode(' OR ', $conditions) . ')';
        
        return [$whereClause, $params];
    }
    
    public static function highlightResults($text, $searchTerm) {
        if (empty($searchTerm)) {
            return $text;
        }
        
        return preg_replace(
            '/(' . preg_quote($searchTerm, '/') . ')/i',
            '<mark>$1</mark>',
            $text
        );
    }
}

// Utility Functions
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, 
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

// Apply rate limiting for API requests
RateLimiter::check(getClientIP(), 200, 3600); // 200 requests per hour

?>
