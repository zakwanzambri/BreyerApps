<?php
/**
 * PHP Unit Testing Framework for Campus Hub Portal
 * Simple testing framework for PHP components
 */

class CampusTestFramework {
    private $tests = [];
    private $results = [];
    private $currentSuite = '';
    
    public function __construct() {
        $this->results = [
            'passed' => 0,
            'failed' => 0,
            'total' => 0,
            'suites' => []
        ];
    }
    
    /**
     * Create a test suite
     */
    public function suite($name, $callback) {
        $this->currentSuite = $name;
        $this->results['suites'][$name] = [
            'passed' => 0,
            'failed' => 0,
            'tests' => []
        ];
        
        echo "\n=== Test Suite: $name ===\n";
        $callback($this);
    }
    
    /**
     * Define a test case
     */
    public function test($description, $callback) {
        $startTime = microtime(true);
        
        try {
            $callback($this);
            $this->testPassed($description, microtime(true) - $startTime);
        } catch (Exception $e) {
            $this->testFailed($description, $e->getMessage(), microtime(true) - $startTime);
        }
    }
    
    /**
     * Assertion methods
     */
    public function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            throw new Exception("Assertion failed: Expected '$expected', got '$actual'. $message");
        }
    }
    
    public function assertTrue($condition, $message = '') {
        if (!$condition) {
            throw new Exception("Assertion failed: Expected true, got false. $message");
        }
    }
    
    public function assertFalse($condition, $message = '') {
        if ($condition) {
            throw new Exception("Assertion failed: Expected false, got true. $message");
        }
    }
    
    public function assertNull($value, $message = '') {
        if ($value !== null) {
            throw new Exception("Assertion failed: Expected null, got " . gettype($value) . ". $message");
        }
    }
    
    public function assertNotNull($value, $message = '') {
        if ($value === null) {
            throw new Exception("Assertion failed: Expected not null, got null. $message");
        }
    }
    
    public function assertArrayHasKey($key, $array, $message = '') {
        if (!array_key_exists($key, $array)) {
            throw new Exception("Assertion failed: Array does not contain key '$key'. $message");
        }
    }
    
    public function assertContains($needle, $haystack, $message = '') {
        if (is_array($haystack) && !in_array($needle, $haystack)) {
            throw new Exception("Assertion failed: Array does not contain '$needle'. $message");
        } elseif (is_string($haystack) && strpos($haystack, $needle) === false) {
            throw new Exception("Assertion failed: String does not contain '$needle'. $message");
        }
    }
    
    public function assertInstanceOf($expected, $actual, $message = '') {
        if (!($actual instanceof $expected)) {
            throw new Exception("Assertion failed: Expected instance of '$expected', got " . get_class($actual) . ". $message");
        }
    }
    
    public function assertThrows($callback, $expectedExceptionClass = 'Exception', $message = '') {
        try {
            $callback();
            throw new Exception("Assertion failed: Expected exception '$expectedExceptionClass' was not thrown. $message");
        } catch (Exception $e) {
            if (!($e instanceof $expectedExceptionClass)) {
                throw new Exception("Assertion failed: Expected '$expectedExceptionClass', got " . get_class($e) . ". $message");
            }
        }
    }
    
    /**
     * Database testing helpers
     */
    public function createTestDatabase() {
        // Create a test database connection
        $config = include 'config/test_config.php';
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['test_database']}", 
            $config['username'], 
            $config['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
    
    public function setupTestData($pdo) {
        // Insert test data
        $queries = [
            "INSERT INTO users (username, email, password_hash, user_type, created_at) VALUES 
             ('testuser', 'test@example.com', '" . password_hash('testpass', PASSWORD_DEFAULT) . "', 'student', NOW())",
            "INSERT INTO news (title, content, author_id, status, created_at) VALUES 
             ('Test News', 'This is test news content', 1, 'published', NOW())",
            "INSERT INTO events (title, description, event_date, location, created_by, created_at) VALUES 
             ('Test Event', 'This is a test event', '2024-12-31 10:00:00', 'Test Location', 1, NOW())"
        ];
        
        foreach ($queries as $query) {
            try {
                $pdo->exec($query);
            } catch (PDOException $e) {
                // Ignore errors for existing test data
            }
        }
    }
    
    public function cleanupTestData($pdo) {
        // Clean up test data
        $tables = ['users', 'news', 'events', 'user_sessions', 'search_analytics'];
        foreach ($tables as $table) {
            try {
                $pdo->exec("DELETE FROM $table WHERE email LIKE '%@example.com' OR title LIKE 'Test%'");
            } catch (PDOException $e) {
                // Ignore errors for non-existent tables
            }
        }
    }
    
    /**
     * HTTP testing helpers
     */
    public function makeApiRequest($method, $url, $data = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_POSTFIELDS => $data ? json_encode($data) : null,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false // For testing only
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: $error");
        }
        
        return [
            'status_code' => $httpCode,
            'body' => $response,
            'data' => json_decode($response, true)
        ];
    }
    
    /**
     * Performance testing helper
     */
    public function measurePerformance($callback, $iterations = 1) {
        $times = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $callback();
            $times[] = microtime(true) - $start;
        }
        
        return [
            'min' => min($times),
            'max' => max($times),
            'avg' => array_sum($times) / count($times),
            'total' => array_sum($times),
            'iterations' => $iterations
        ];
    }
    
    /**
     * Record test results
     */
    private function testPassed($description, $duration) {
        $this->results['passed']++;
        $this->results['total']++;
        $this->results['suites'][$this->currentSuite]['passed']++;
        $this->results['suites'][$this->currentSuite]['tests'][] = [
            'name' => $description,
            'status' => 'passed',
            'duration' => $duration,
            'message' => ''
        ];
        
        echo "✓ $description (" . number_format($duration * 1000, 2) . "ms)\n";
    }
    
    private function testFailed($description, $message, $duration) {
        $this->results['failed']++;
        $this->results['total']++;
        $this->results['suites'][$this->currentSuite]['failed']++;
        $this->results['suites'][$this->currentSuite]['tests'][] = [
            'name' => $description,
            'status' => 'failed',
            'duration' => $duration,
            'message' => $message
        ];
        
        echo "✗ $description (" . number_format($duration * 1000, 2) . "ms)\n";
        echo "  Error: $message\n";
    }
    
    /**
     * Generate test report
     */
    public function generateReport() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "TEST RESULTS SUMMARY\n";
        echo str_repeat("=", 50) . "\n";
        
        foreach ($this->results['suites'] as $suiteName => $suite) {
            $total = $suite['passed'] + $suite['failed'];
            $percentage = $total > 0 ? round(($suite['passed'] / $total) * 100, 1) : 0;
            
            echo "\n$suiteName: {$suite['passed']}/$total passed ($percentage%)\n";
            
            foreach ($suite['tests'] as $test) {
                $status = $test['status'] === 'passed' ? '✓' : '✗';
                echo "  $status {$test['name']}\n";
                if ($test['status'] === 'failed') {
                    echo "    Error: {$test['message']}\n";
                }
            }
        }
        
        $total = $this->results['total'];
        $passed = $this->results['passed'];
        $failed = $this->results['failed'];
        $percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        
        echo "\n" . str_repeat("-", 50) . "\n";
        echo "Overall: $passed/$total tests passed ($percentage%)\n";
        
        if ($failed > 0) {
            echo "TESTS FAILED\n";
            exit(1);
        } else {
            echo "ALL TESTS PASSED\n";
        }
    }
    
    /**
     * Save results to JSON file
     */
    public function saveResults($filename = 'test_results.json') {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'total' => $this->results['total'],
                'passed' => $this->results['passed'],
                'failed' => $this->results['failed'],
                'success_rate' => $this->results['total'] > 0 ? 
                    round(($this->results['passed'] / $this->results['total']) * 100, 2) : 0
            ],
            'suites' => $this->results['suites']
        ];
        
        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));
        echo "Results saved to $filename\n";
    }
}

// Helper function to include required files for testing
function requireTestFiles() {
    $files = [
        'php/config.php',
        'php/database.php'
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

// Auto-load test files if this script is run directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    requireTestFiles();
}
?>
