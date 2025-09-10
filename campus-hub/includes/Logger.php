<?php
/**
 * Campus Hub Logging System
 * Centralized application logging with different log levels
 */

class Logger {
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    
    private $logDir;
    private $maxFileSize;
    private $maxFiles;
    private $dateFormat;
    private $logFormat;
    
    public function __construct($logDir = null) {
        $this->logDir = $logDir ?: dirname(__DIR__) . '/logs';
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->maxFiles = 5;
        $this->dateFormat = 'Y-m-d H:i:s';
        $this->logFormat = '[%s] %s: %s%s';
        
        $this->ensureLogDirectory();
    }
    
    /**
     * Log a debug message
     */
    public function debug($message, $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log an info message
     */
    public function info($message, $context = []) {
        $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Log a warning message
     */
    public function warning($message, $context = []) {
        $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Log an error message
     */
    public function error($message, $context = []) {
        $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Log a critical message
     */
    public function critical($message, $context = []) {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log user activity
     */
    public function logUserActivity($userId, $action, $details = [], $ip = null) {
        $context = [
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $ip ?: $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'details' => $details
        ];
        
        $this->log(self::INFO, "User activity: $action", $context, 'activity');
    }
    
    /**
     * Log security events
     */
    public function logSecurity($event, $details = [], $level = self::WARNING) {
        $context = [
            'event' => $event,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'timestamp' => time(),
            'details' => $details
        ];
        
        $this->log($level, "Security event: $event", $context, 'security');
    }
    
    /**
     * Log API requests
     */
    public function logAPI($method, $endpoint, $statusCode, $responseTime, $userId = null) {
        $context = [
            'method' => $method,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'user_id' => $userId,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        $level = $statusCode >= 500 ? self::ERROR : ($statusCode >= 400 ? self::WARNING : self::INFO);
        $this->log($level, "API Request: $method $endpoint ($statusCode)", $context, 'api');
    }
    
    /**
     * Log database operations
     */
    public function logDatabase($operation, $table, $affectedRows = 0, $executionTime = 0, $error = null) {
        $context = [
            'operation' => $operation,
            'table' => $table,
            'affected_rows' => $affectedRows,
            'execution_time' => $executionTime,
            'error' => $error
        ];
        
        $level = $error ? self::ERROR : self::INFO;
        $message = $error ? "Database error in $operation on $table: $error" : "Database $operation on $table";
        
        $this->log($level, $message, $context, 'database');
    }
    
    /**
     * Log system performance metrics
     */
    public function logPerformance($metric, $value, $threshold = null, $context = []) {
        $logContext = array_merge([
            'metric' => $metric,
            'value' => $value,
            'threshold' => $threshold,
            'timestamp' => time()
        ], $context);
        
        $level = ($threshold && $value > $threshold) ? self::WARNING : self::INFO;
        $message = "Performance metric: $metric = $value" . ($threshold ? " (threshold: $threshold)" : '');
        
        $this->log($level, $message, $logContext, 'performance');
    }
    
    /**
     * Main logging method
     */
    private function log($level, $message, $context = [], $logFile = 'application') {
        $timestamp = date($this->dateFormat);
        $contextString = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        
        $logEntry = sprintf(
            $this->logFormat,
            $timestamp,
            $level,
            $message,
            $contextString
        ) . PHP_EOL;
        
        $filename = $this->logDir . '/' . $logFile . '.log';
        
        // Rotate log file if it's too large
        $this->rotateLogFile($filename);
        
        // Write log entry
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also write to error log for critical messages
        if ($level === self::CRITICAL || $level === self::ERROR) {
            error_log("Campus Hub [$level]: $message");
        }
    }
    
    /**
     * Rotate log files when they get too large
     */
    private function rotateLogFile($filename) {
        if (!file_exists($filename) || filesize($filename) < $this->maxFileSize) {
            return;
        }
        
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $directory = dirname($filename);
        
        // Rotate existing files
        for ($i = $this->maxFiles - 1; $i > 0; $i--) {
            $oldFile = "$directory/$baseName.$i.$extension";
            $newFile = "$directory/$baseName." . ($i + 1) . ".$extension";
            
            if (file_exists($oldFile)) {
                if ($i + 1 <= $this->maxFiles) {
                    rename($oldFile, $newFile);
                } else {
                    unlink($oldFile);
                }
            }
        }
        
        // Move current file to .1
        rename($filename, "$directory/$baseName.1.$extension");
    }
    
    /**
     * Get logs for display in admin interface
     */
    public function getLogs($logFile = 'application', $lines = 100, $level = null) {
        $filename = $this->logDir . '/' . $logFile . '.log';
        
        if (!file_exists($filename)) {
            return [];
        }
        
        $logs = [];
        $handle = fopen($filename, 'r');
        
        if ($handle) {
            $allLines = [];
            while (($line = fgets($handle)) !== false) {
                $allLines[] = trim($line);
            }
            fclose($handle);
            
            // Get the last N lines
            $recentLines = array_slice($allLines, -$lines);
            
            foreach ($recentLines as $line) {
                if (empty($line)) continue;
                
                $parsed = $this->parseLogLine($line);
                if ($parsed && (!$level || $parsed['level'] === $level)) {
                    $logs[] = $parsed;
                }
            }
        }
        
        return array_reverse($logs); // Most recent first
    }
    
    /**
     * Parse a log line into components
     */
    private function parseLogLine($line) {
        // Pattern: [timestamp] level: message context
        if (preg_match('/^\[([^\]]+)\] ([A-Z]+): (.+)$/', $line, $matches)) {
            $message = $matches[3];
            $context = null;
            
            // Try to extract JSON context
            if (preg_match('/^(.+?) (\{.+\})$/', $message, $contextMatches)) {
                $message = $contextMatches[1];
                $context = json_decode($contextMatches[2], true);
            }
            
            return [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'message' => $message,
                'context' => $context
            ];
        }
        
        return null;
    }
    
    /**
     * Get log statistics
     */
    public function getLogStats($logFile = 'application', $hours = 24) {
        $filename = $this->logDir . '/' . $logFile . '.log';
        
        if (!file_exists($filename)) {
            return [
                'total' => 0,
                'by_level' => [],
                'recent_errors' => 0
            ];
        }
        
        $stats = [
            'total' => 0,
            'by_level' => [
                'DEBUG' => 0,
                'INFO' => 0,
                'WARNING' => 0,
                'ERROR' => 0,
                'CRITICAL' => 0
            ],
            'recent_errors' => 0
        ];
        
        $cutoffTime = time() - ($hours * 3600);
        $handle = fopen($filename, 'r');
        
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $parsed = $this->parseLogLine(trim($line));
                if (!$parsed) continue;
                
                $stats['total']++;
                $stats['by_level'][$parsed['level']]++;
                
                // Count recent errors
                $logTime = strtotime($parsed['timestamp']);
                if ($logTime > $cutoffTime && in_array($parsed['level'], ['ERROR', 'CRITICAL'])) {
                    $stats['recent_errors']++;
                }
            }
            fclose($handle);
        }
        
        return $stats;
    }
    
    /**
     * Clean old log files
     */
    public function cleanOldLogs($days = 30) {
        $cutoffTime = time() - ($days * 24 * 3600);
        $pattern = $this->logDir . '/*.log*';
        
        $deleted = 0;
        foreach (glob($pattern) as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
            }
        }
        
        $this->info("Cleaned $deleted old log files older than $days days");
        return $deleted;
    }
    
    /**
     * Export logs for download
     */
    public function exportLogs($logFile = 'application', $format = 'csv') {
        $logs = $this->getLogs($logFile, 10000); // Get up to 10k recent logs
        
        if ($format === 'csv') {
            return $this->exportToCSV($logs);
        } elseif ($format === 'json') {
            return json_encode($logs, JSON_PRETTY_PRINT);
        }
        
        return false;
    }
    
    private function exportToCSV($logs) {
        $output = fopen('php://temp', 'r+');
        
        // CSV headers
        fputcsv($output, ['Timestamp', 'Level', 'Message', 'Context']);
        
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['timestamp'],
                $log['level'],
                $log['message'],
                $log['context'] ? json_encode($log['context']) : ''
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectory() {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Create .htaccess to protect log files
        $htaccessFile = $this->logDir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents($htaccessFile, "Order Deny,Allow\nDeny from all\n");
        }
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return 'unknown';
    }
}

// Global logger instance
function getLogger() {
    static $logger = null;
    if ($logger === null) {
        $logger = new Logger();
    }
    return $logger;
}

// Convenience functions
function logDebug($message, $context = []) {
    getLogger()->debug($message, $context);
}

function logInfo($message, $context = []) {
    getLogger()->info($message, $context);
}

function logWarning($message, $context = []) {
    getLogger()->warning($message, $context);
}

function logError($message, $context = []) {
    getLogger()->error($message, $context);
}

function logCritical($message, $context = []) {
    getLogger()->critical($message, $context);
}

function logUserActivity($userId, $action, $details = [], $ip = null) {
    getLogger()->logUserActivity($userId, $action, $details, $ip);
}

function logSecurity($event, $details = [], $level = Logger::WARNING) {
    getLogger()->logSecurity($event, $details, $level);
}

function logAPI($method, $endpoint, $statusCode, $responseTime, $userId = null) {
    getLogger()->logAPI($method, $endpoint, $statusCode, $responseTime, $userId);
}

function logDatabase($operation, $table, $affectedRows = 0, $executionTime = 0, $error = null) {
    getLogger()->logDatabase($operation, $table, $affectedRows, $executionTime, $error);
}

function logPerformance($metric, $value, $threshold = null, $context = []) {
    getLogger()->logPerformance($metric, $value, $threshold, $context);
}
?>
