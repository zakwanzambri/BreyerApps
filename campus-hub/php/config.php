<?php
/**
 * Database Configuration and Connection Handler
 * Campus Hub Portal - Enhanced Version
 * AI Vibe Coding Challenge 2025
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Database configuration
    private $host = 'localhost';
    private $username = 'root';  // Change as needed
    private $password = '';      // Change as needed  
    private $database = 'campus_hub_db';
    private $charset = 'utf8mb4';
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
}

/**
 * Response Helper Class
 */
class ResponseHelper {
    public static function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    public static function success($data = null, $message = 'Success') {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    public static function error($message = 'Error occurred', $status = 400) {
        self::json([
            'success' => false,
            'message' => $message,
            'data' => null
        ], $status);
    }
    
    public static function unauthorized($message = 'Unauthorized access') {
        self::error($message, 401);
    }
    
    public static function forbidden($message = 'Access forbidden') {
        self::error($message, 403);
    }
    
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }
}

/**
 * Session Management Class
 */
class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function remove($key) {
        self::start();
        unset($_SESSION[$key]);
    }
    
    public static function destroy() {
        self::start();
        session_destroy();
    }
    
    public static function isLoggedIn() {
        return self::get('user_id') !== null;
    }
    
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT id, username, email, full_name, role, student_id, program_id, status FROM users WHERE id = ?",
            [self::get('user_id')]
        );
        
        return $user;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            ResponseHelper::unauthorized('Please login to continue');
        }
    }
    
    public static function requireRole($roles) {
        self::requireLogin();
        $user = self::getCurrentUser();
        
        if (!in_array($user['role'], (array)$roles)) {
            ResponseHelper::forbidden('Insufficient permissions');
        }
    }
}

/**
 * Input Validation and Sanitization
 */
class Validator {
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validateRequired($value) {
        return !empty(trim($value));
    }
    
    public static function validateLength($value, $min = 0, $max = PHP_INT_MAX) {
        $length = strlen(trim($value));
        return $length >= $min && $length <= $max;
    }
    
    public static function validatePassword($password) {
        // At least 6 characters, contains letter and number
        return strlen($password) >= 6 && 
               preg_match('/[A-Za-z]/', $password) && 
               preg_match('/[0-9]/', $password);
    }
    
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}

/**
 * Utility Functions
 */
class Utils {
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function formatDate($date, $format = 'Y-m-d H:i:s') {
        if ($date instanceof DateTime) {
            return $date->format($format);
        }
        return date($format, strtotime($date));
    }
    
    public static function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        if ($time < 31536000) return floor($time/2592000) . ' months ago';
        
        return floor($time/31536000) . ' years ago';
    }
    
    public static function slugify($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        
        return empty($text) ? 'n-a' : $text;
    }
}

// Error handling
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $error = [
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'severity' => $severity
    ];
    
    error_log(json_encode($error));
    
    if (ini_get('display_errors')) {
        ResponseHelper::error('Internal server error: ' . $message, 500);
    } else {
        ResponseHelper::error('Internal server error', 500);
    }
});

// Exception handling
set_exception_handler(function($exception) {
    error_log($exception->getMessage());
    
    if (ini_get('display_errors')) {
        ResponseHelper::error('Exception: ' . $exception->getMessage(), 500);
    } else {
        ResponseHelper::error('Internal server error', 500);
    }
});

// CORS headers for frontend integration
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

?>
