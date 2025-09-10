<?php
/**
 * Campus Hub System Monitor
 * Real-time system health monitoring API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

class SystemMonitor {
    private $config;
    private $thresholds;
    
    public function __construct() {
        $this->config = $this->loadConfig();
        $this->thresholds = [
            'cpu_warning' => 70,
            'cpu_critical' => 90,
            'memory_warning' => 80,
            'memory_critical' => 95,
            'disk_warning' => 80,
            'disk_critical' => 90,
            'load_warning' => 2.0,
            'load_critical' => 4.0
        ];
    }
    
    public function getSystemHealth() {
        $health = [
            'status' => 'OK',
            'timestamp' => date('c'),
            'uptime' => $this->getUptime(),
            'metrics' => [
                'cpu' => $this->getCPUUsage(),
                'memory' => $this->getMemoryUsage(),
                'disk' => $this->getDiskUsage(),
                'load' => $this->getLoadAverage(),
                'processes' => $this->getProcessInfo()
            ],
            'services' => $this->checkServices(),
            'database' => $this->checkDatabase(),
            'ssl' => $this->checkSSLCertificate(),
            'logs' => $this->getRecentErrors(),
            'alerts' => []
        ];
        
        // Generate alerts based on thresholds
        $health['alerts'] = $this->generateAlerts($health['metrics']);
        
        // Determine overall status
        $health['status'] = $this->determineOverallStatus($health);
        
        return $health;
    }
    
    private function loadConfig() {
        $configFile = dirname(__DIR__) . '/config/config.php';
        if (file_exists($configFile)) {
            return include $configFile;
        }
        
        // Fallback configuration
        return [
            'db_host' => 'localhost',
            'db_name' => 'campus_hub',
            'db_user' => 'root',
            'db_password' => '',
            'app_url' => 'http://localhost'
        ];
    }
    
    private function getUptime() {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = file_get_contents('/proc/uptime');
            $uptime = explode(' ', $uptime)[0];
            
            $days = floor($uptime / 86400);
            $hours = floor(($uptime % 86400) / 3600);
            $minutes = floor(($uptime % 3600) / 60);
            
            return [
                'seconds' => (int)$uptime,
                'formatted' => sprintf('%d days, %d hours, %d minutes', $days, $hours, $minutes)
            ];
        }
        
        return [
            'seconds' => 0,
            'formatted' => 'Not available on this platform'
        ];
    }
    
    private function getCPUUsage() {
        if (PHP_OS_FAMILY === 'Linux') {
            // Get CPU usage from /proc/stat
            $prevVal = $this->getCPUStat();
            sleep(1);
            $curVal = $this->getCPUStat();
            
            $prevIdle = $prevVal['idle'] + $prevVal['iowait'];
            $curIdle = $curVal['idle'] + $curVal['iowait'];
            
            $prevNonIdle = $prevVal['user'] + $prevVal['nice'] + $prevVal['system'] + $prevVal['irq'] + $prevVal['softirq'] + $prevVal['steal'];
            $curNonIdle = $curVal['user'] + $curVal['nice'] + $curVal['system'] + $curVal['irq'] + $curVal['softirq'] + $curVal['steal'];
            
            $prevTotal = $prevIdle + $prevNonIdle;
            $curTotal = $curIdle + $curNonIdle;
            
            $totald = $curTotal - $prevTotal;
            $idled = $curIdle - $prevIdle;
            
            $usage = ($totald - $idled) / $totald * 100;
            
            return [
                'usage' => round($usage, 2),
                'cores' => $this->getCPUCores(),
                'status' => $this->getStatusLevel($usage, $this->thresholds['cpu_warning'], $this->thresholds['cpu_critical'])
            ];
        }
        
        return [
            'usage' => 0,
            'cores' => 1,
            'status' => 'unknown'
        ];
    }
    
    private function getCPUStat() {
        $stat = file_get_contents('/proc/stat');
        $lines = explode("\n", $stat);
        $cpuLine = $lines[0];
        $values = preg_split('/\s+/', $cpuLine);
        
        return [
            'user' => (int)$values[1],
            'nice' => (int)$values[2],
            'system' => (int)$values[3],
            'idle' => (int)$values[4],
            'iowait' => (int)$values[5],
            'irq' => (int)$values[6],
            'softirq' => (int)$values[7],
            'steal' => isset($values[8]) ? (int)$values[8] : 0
        ];
    }
    
    private function getCPUCores() {
        if (PHP_OS_FAMILY === 'Linux') {
            $cores = (int)shell_exec('nproc');
            return $cores > 0 ? $cores : 1;
        }
        return 1;
    }
    
    private function getMemoryUsage() {
        if (PHP_OS_FAMILY === 'Linux') {
            $meminfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $totalMatch);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $availableMatch);
            
            if (!$availableMatch) {
                preg_match('/MemFree:\s+(\d+)/', $meminfo, $freeMatch);
                preg_match('/Buffers:\s+(\d+)/', $meminfo, $buffersMatch);
                preg_match('/Cached:\s+(\d+)/', $meminfo, $cachedMatch);
                
                $available = ($freeMatch[1] ?? 0) + ($buffersMatch[1] ?? 0) + ($cachedMatch[1] ?? 0);
            } else {
                $available = $availableMatch[1];
            }
            
            $total = $totalMatch[1] ?? 0;
            $used = $total - $available;
            $usage = $total > 0 ? ($used / $total * 100) : 0;
            
            return [
                'total' => $this->formatBytes($total * 1024),
                'used' => $this->formatBytes($used * 1024),
                'available' => $this->formatBytes($available * 1024),
                'usage' => round($usage, 2),
                'status' => $this->getStatusLevel($usage, $this->thresholds['memory_warning'], $this->thresholds['memory_critical'])
            ];
        }
        
        return [
            'total' => 'Unknown',
            'used' => 'Unknown',
            'available' => 'Unknown',
            'usage' => 0,
            'status' => 'unknown'
        ];
    }
    
    private function getDiskUsage() {
        $path = dirname(__DIR__);
        $totalBytes = disk_total_space($path);
        $freeBytes = disk_free_space($path);
        $usedBytes = $totalBytes - $freeBytes;
        $usage = $totalBytes > 0 ? ($usedBytes / $totalBytes * 100) : 0;
        
        return [
            'total' => $this->formatBytes($totalBytes),
            'used' => $this->formatBytes($usedBytes),
            'free' => $this->formatBytes($freeBytes),
            'usage' => round($usage, 2),
            'status' => $this->getStatusLevel($usage, $this->thresholds['disk_warning'], $this->thresholds['disk_critical'])
        ];
    }
    
    private function getLoadAverage() {
        if (function_exists('sys_getloadavg')) {
            $loads = sys_getloadavg();
            $load1 = $loads[0];
            $load5 = $loads[1];
            $load15 = $loads[2];
            
            return [
                '1min' => round($load1, 2),
                '5min' => round($load5, 2),
                '15min' => round($load15, 2),
                'status' => $this->getStatusLevel($load1, $this->thresholds['load_warning'], $this->thresholds['load_critical'])
            ];
        }
        
        return [
            '1min' => 0,
            '5min' => 0,
            '15min' => 0,
            'status' => 'unknown'
        ];
    }
    
    private function getProcessInfo() {
        if (PHP_OS_FAMILY === 'Linux') {
            $processes = (int)shell_exec('ps aux | wc -l') - 1; // Subtract header line
            $phpProcesses = (int)shell_exec('ps aux | grep php | grep -v grep | wc -l');
            $apacheProcesses = (int)shell_exec('ps aux | grep apache | grep -v grep | wc -l');
            
            return [
                'total' => $processes,
                'php' => $phpProcesses,
                'apache' => $apacheProcesses
            ];
        }
        
        return [
            'total' => 0,
            'php' => 0,
            'apache' => 0
        ];
    }
    
    private function checkServices() {
        $services = [];
        
        // Check web server
        if (function_exists('apache_get_version')) {
            $services['apache'] = [
                'status' => 'running',
                'version' => apache_get_version()
            ];
        } else {
            $services['apache'] = [
                'status' => 'unknown',
                'version' => 'N/A'
            ];
        }
        
        // Check PHP
        $services['php'] = [
            'status' => 'running',
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ];
        
        return $services;
    }
    
    private function checkDatabase() {
        try {
            $startTime = microtime(true);
            
            $pdo = new PDO(
                "mysql:host={$this->config['db_host']};dbname={$this->config['db_name']}",
                $this->config['db_user'],
                $this->config['db_password'],
                [PDO::ATTR_TIMEOUT => 5]
            );
            
            // Test query
            $stmt = $pdo->query('SELECT 1');
            $result = $stmt->fetch();
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Get database info
            $version = $pdo->query('SELECT VERSION() as version')->fetch()['version'];
            $connections = $pdo->query('SHOW STATUS LIKE "Threads_connected"')->fetch()['Value'];
            $maxConnections = $pdo->query('SHOW VARIABLES LIKE "max_connections"')->fetch()['Value'];
            
            return [
                'status' => 'connected',
                'response_time' => $responseTime . 'ms',
                'version' => $version,
                'connections' => [
                    'current' => (int)$connections,
                    'max' => (int)$maxConnections,
                    'usage' => round(($connections / $maxConnections) * 100, 2)
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'response_time' => 'N/A',
                'version' => 'N/A',
                'connections' => [
                    'current' => 0,
                    'max' => 0,
                    'usage' => 0
                ]
            ];
        }
    }
    
    private function checkSSLCertificate() {
        $url = $this->config['app_url'] ?? 'https://localhost';
        
        if (strpos($url, 'https://') !== 0) {
            return [
                'status' => 'not_ssl',
                'message' => 'Site not using HTTPS'
            ];
        }
        
        $hostname = parse_url($url, PHP_URL_HOST);
        
        if (!$hostname) {
            return [
                'status' => 'error',
                'message' => 'Invalid hostname'
            ];
        }
        
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $socket = @stream_socket_client(
            "ssl://{$hostname}:443",
            $errno,
            $errstr,
            5,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$socket) {
            return [
                'status' => 'error',
                'message' => "Connection failed: $errstr"
            ];
        }
        
        $cert = stream_context_get_params($socket)['options']['ssl']['peer_certificate'];
        $certInfo = openssl_x509_parse($cert);
        
        $validFrom = date('Y-m-d H:i:s', $certInfo['validFrom_time_t']);
        $validTo = date('Y-m-d H:i:s', $certInfo['validTo_time_t']);
        $daysLeft = ceil(($certInfo['validTo_time_t'] - time()) / 86400);
        
        fclose($socket);
        
        $status = 'valid';
        if ($daysLeft < 0) {
            $status = 'expired';
        } elseif ($daysLeft < 30) {
            $status = 'expiring_soon';
        }
        
        return [
            'status' => $status,
            'issuer' => $certInfo['issuer']['CN'] ?? 'Unknown',
            'subject' => $certInfo['subject']['CN'] ?? 'Unknown',
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'days_left' => $daysLeft
        ];
    }
    
    private function getRecentErrors() {
        $errors = [];
        
        // Check PHP error log
        $phpErrorLog = ini_get('error_log');
        if ($phpErrorLog && file_exists($phpErrorLog)) {
            $errors['php'] = $this->getLogTail($phpErrorLog, 10);
        }
        
        // Check Apache error log (common locations)
        $apacheErrorLogs = [
            '/var/log/apache2/error.log',
            '/var/log/httpd/error_log',
            dirname(__DIR__) . '/logs/error.log'
        ];
        
        foreach ($apacheErrorLogs as $logFile) {
            if (file_exists($logFile)) {
                $errors['apache'] = $this->getLogTail($logFile, 10);
                break;
            }
        }
        
        // Check application logs
        $appLogDir = dirname(__DIR__) . '/logs';
        if (is_dir($appLogDir)) {
            $logFiles = glob($appLogDir . '/*.log');
            foreach ($logFiles as $logFile) {
                $name = basename($logFile, '.log');
                $errors[$name] = $this->getLogTail($logFile, 5);
            }
        }
        
        return $errors;
    }
    
    private function getLogTail($filename, $lines = 10) {
        if (!file_exists($filename) || !is_readable($filename)) {
            return [];
        }
        
        $handle = fopen($filename, 'r');
        if (!$handle) {
            return [];
        }
        
        $lineArray = [];
        while (!feof($handle)) {
            $line = trim(fgets($handle));
            if (!empty($line)) {
                $lineArray[] = $line;
            }
        }
        fclose($handle);
        
        return array_slice($lineArray, -$lines);
    }
    
    private function generateAlerts($metrics) {
        $alerts = [];
        
        // CPU alerts
        if ($metrics['cpu']['usage'] >= $this->thresholds['cpu_critical']) {
            $alerts[] = [
                'level' => 'critical',
                'message' => "CPU usage is critically high: {$metrics['cpu']['usage']}%",
                'metric' => 'cpu'
            ];
        } elseif ($metrics['cpu']['usage'] >= $this->thresholds['cpu_warning']) {
            $alerts[] = [
                'level' => 'warning',
                'message' => "CPU usage is high: {$metrics['cpu']['usage']}%",
                'metric' => 'cpu'
            ];
        }
        
        // Memory alerts
        if ($metrics['memory']['usage'] >= $this->thresholds['memory_critical']) {
            $alerts[] = [
                'level' => 'critical',
                'message' => "Memory usage is critically high: {$metrics['memory']['usage']}%",
                'metric' => 'memory'
            ];
        } elseif ($metrics['memory']['usage'] >= $this->thresholds['memory_warning']) {
            $alerts[] = [
                'level' => 'warning',
                'message' => "Memory usage is high: {$metrics['memory']['usage']}%",
                'metric' => 'memory'
            ];
        }
        
        // Disk alerts
        if ($metrics['disk']['usage'] >= $this->thresholds['disk_critical']) {
            $alerts[] = [
                'level' => 'critical',
                'message' => "Disk usage is critically high: {$metrics['disk']['usage']}%",
                'metric' => 'disk'
            ];
        } elseif ($metrics['disk']['usage'] >= $this->thresholds['disk_warning']) {
            $alerts[] = [
                'level' => 'warning',
                'message' => "Disk usage is high: {$metrics['disk']['usage']}%",
                'metric' => 'disk'
            ];
        }
        
        // Load average alerts
        if ($metrics['load']['1min'] >= $this->thresholds['load_critical']) {
            $alerts[] = [
                'level' => 'critical',
                'message' => "System load is critically high: {$metrics['load']['1min']}",
                'metric' => 'load'
            ];
        } elseif ($metrics['load']['1min'] >= $this->thresholds['load_warning']) {
            $alerts[] = [
                'level' => 'warning',
                'message' => "System load is high: {$metrics['load']['1min']}",
                'metric' => 'load'
            ];
        }
        
        return $alerts;
    }
    
    private function determineOverallStatus($health) {
        // Check for critical alerts
        foreach ($health['alerts'] as $alert) {
            if ($alert['level'] === 'critical') {
                return 'CRITICAL';
            }
        }
        
        // Check database status
        if ($health['database']['status'] !== 'connected') {
            return 'CRITICAL';
        }
        
        // Check for warnings
        foreach ($health['alerts'] as $alert) {
            if ($alert['level'] === 'warning') {
                return 'WARNING';
            }
        }
        
        return 'OK';
    }
    
    private function getStatusLevel($value, $warning, $critical) {
        if ($value >= $critical) {
            return 'critical';
        } elseif ($value >= $warning) {
            return 'warning';
        }
        return 'ok';
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Handle the request
try {
    $monitor = new SystemMonitor();
    $health = $monitor->getSystemHealth();
    
    http_response_code(200);
    echo json_encode($health, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'System monitor error: ' . $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
}
?>
