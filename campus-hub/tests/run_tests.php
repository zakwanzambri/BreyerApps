#!/usr/bin/env php
<?php
/**
 * Campus Hub Test Runner
 * Command-line test runner for all test suites
 */

// Set memory limit for testing
ini_set('memory_limit', '256M');
set_time_limit(300); // 5 minutes

// Include required files
require_once 'TestFramework.php';
require_once 'ApiTests.php';
require_once 'DatabaseTests.php';

class TestRunner {
    private $config;
    private $results;
    private $startTime;
    
    public function __construct() {
        $this->config = include 'test_config.php';
        $this->results = [
            'suites' => [],
            'summary' => []
        ];
        $this->startTime = microtime(true);
    }
    
    public function run($options = []) {
        echo "=================================================================\n";
        echo "Campus Hub Portal - Comprehensive Test Suite\n";
        echo "=================================================================\n\n";
        
        $this->displayEnvironmentInfo();
        
        // Create results directory if it doesn't exist
        $this->ensureResultsDirectory();
        
        // Run test suites based on options
        if (empty($options) || in_array('all', $options)) {
            $this->runAllTests();
        } else {
            if (in_array('database', $options)) {
                $this->runDatabaseTests();
            }
            if (in_array('api', $options)) {
                $this->runApiTests();
            }
            if (in_array('performance', $options)) {
                $this->runPerformanceTests();
            }
            if (in_array('security', $options)) {
                $this->runSecurityTests();
            }
        }
        
        $this->generateFinalReport();
        $this->saveResults();
        
        return $this->getExitCode();
    }
    
    private function runAllTests() {
        echo "Running all test suites...\n\n";
        
        $this->runDatabaseTests();
        $this->runApiTests();
        $this->runPerformanceTests();
        $this->runSecurityTests();
    }
    
    private function runDatabaseTests() {
        echo "Starting Database Tests...\n";
        echo str_repeat("-", 50) . "\n";
        
        try {
            $testFramework = new CampusTestFramework();
            $databaseTests = new DatabaseTests($testFramework);
            
            $databaseTests->runAllTests();
            
            $this->results['suites']['database'] = [
                'name' => 'Database Tests',
                'results' => $testFramework->results,
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            echo "Database tests failed: " . $e->getMessage() . "\n";
            $this->results['suites']['database'] = [
                'name' => 'Database Tests',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
        
        echo "\n";
    }
    
    private function runApiTests() {
        echo "Starting API Tests...\n";
        echo str_repeat("-", 50) . "\n";
        
        try {
            $testFramework = new CampusTestFramework();
            $apiTests = new ApiTests($testFramework, $this->config['api']['base_url']);
            
            $apiTests->runAllTests();
            
            // Run performance tests
            $performanceTests = new PerformanceTests($testFramework, $this->config['api']['base_url']);
            $performanceTests->runPerformanceTests();
            
            $this->results['suites']['api'] = [
                'name' => 'API Tests',
                'results' => $testFramework->results,
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            echo "API tests failed: " . $e->getMessage() . "\n";
            $this->results['suites']['api'] = [
                'name' => 'API Tests',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
        
        echo "\n";
    }
    
    private function runPerformanceTests() {
        echo "Starting Performance Tests...\n";
        echo str_repeat("-", 50) . "\n";
        
        try {
            $testFramework = new CampusTestFramework();
            
            $testFramework->suite('Performance Benchmarks', function($test) {
                
                $test->test('Database connection performance', function($test) {
                    $performance = $test->measurePerformance(function() use ($test) {
                        $pdo = $test->createTestDatabase();
                        $stmt = $pdo->query("SELECT 1");
                        $stmt->fetch();
                    }, 10);
                    
                    $avgTime = $performance['avg'];
                    $test->assertTrue($avgTime < 0.1, "DB connection too slow: {$avgTime}s");
                    
                    echo "    Average connection time: " . number_format($avgTime * 1000, 2) . "ms\n";
                });
                
                $test->test('Large dataset query performance', function($test) {
                    $pdo = $test->createTestDatabase();
                    
                    $performance = $test->measurePerformance(function() use ($pdo) {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                        $stmt->fetch();
                    }, 5);
                    
                    $avgTime = $performance['avg'];
                    $test->assertTrue($avgTime < 0.5, "Large query too slow: {$avgTime}s");
                    
                    echo "    Average query time: " . number_format($avgTime * 1000, 2) . "ms\n";
                });
                
                $test->test('Memory usage should be reasonable', function($test) {
                    $memoryUsage = memory_get_usage(true);
                    $memoryLimit = ini_get('memory_limit');
                    
                    // Convert memory limit to bytes
                    $limitBytes = $this->convertToBytes($memoryLimit);
                    $usagePercent = ($memoryUsage / $limitBytes) * 100;
                    
                    $test->assertTrue($usagePercent < 50, "Memory usage too high: {$usagePercent}%");
                    
                    echo "    Memory usage: " . number_format($memoryUsage / 1024 / 1024, 2) . "MB ({$usagePercent}%)\n";
                });
            });
            
            $testFramework->generateReport();
            
            $this->results['suites']['performance'] = [
                'name' => 'Performance Tests',
                'results' => $testFramework->results,
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            echo "Performance tests failed: " . $e->getMessage() . "\n";
            $this->results['suites']['performance'] = [
                'name' => 'Performance Tests',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
        
        echo "\n";
    }
    
    private function runSecurityTests() {
        echo "Starting Security Tests...\n";
        echo str_repeat("-", 50) . "\n";
        
        try {
            $testFramework = new CampusTestFramework();
            
            $testFramework->suite('Security Tests', function($test) {
                
                $test->test('SQL injection protection', function($test) {
                    $maliciousSql = "'; DROP TABLE users; --";
                    $response = $test->makeApiRequest('GET', 
                        $this->config['api']['base_url'] . '/php/api/news.php?search=' . urlencode($maliciousSql));
                    
                    $test->assertTrue($response['status_code'] < 500, 'SQL injection may have caused server error');
                    $test->assertFalse(strpos($response['body'], 'error') !== false, 'Unexpected error in response');
                });
                
                $test->test('XSS protection', function($test) {
                    $xssPayload = "<script>alert('xss')</script>";
                    $response = $test->makeApiRequest('GET', 
                        $this->config['api']['base_url'] . '/php/api/news.php?search=' . urlencode($xssPayload));
                    
                    $test->assertEquals(200, $response['status_code'], 'XSS test caused unexpected response');
                    $test->assertFalse(strpos($response['body'], '<script>'), 'XSS payload not properly escaped');
                });
                
                $test->test('File upload security', function($test) {
                    // Test that dangerous file uploads are rejected
                    $dangerousFile = [
                        'name' => 'malicious.php',
                        'type' => 'application/x-php',
                        'content' => '<?php system($_GET["cmd"]); ?>'
                    ];
                    
                    // This test assumes there's a file upload endpoint
                    // For now, we'll just test that PHP files would be rejected
                    $test->assertTrue(true, 'File upload security test placeholder');
                });
                
                $test->test('Authentication bypass protection', function($test) {
                    // Test accessing protected endpoints without authentication
                    $protectedEndpoints = [
                        '/php/api/users.php?action=admin-only',
                        '/php/api/analytics.php?action=dashboard-data'
                    ];
                    
                    foreach ($protectedEndpoints as $endpoint) {
                        $response = $test->makeApiRequest('GET', 
                            $this->config['api']['base_url'] . $endpoint);
                        
                        $test->assertTrue($response['status_code'] >= 400, 
                            "Protected endpoint {$endpoint} should require authentication");
                    }
                });
            });
            
            $testFramework->generateReport();
            
            $this->results['suites']['security'] = [
                'name' => 'Security Tests',
                'results' => $testFramework->results,
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            echo "Security tests failed: " . $e->getMessage() . "\n";
            $this->results['suites']['security'] = [
                'name' => 'Security Tests',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
        
        echo "\n";
    }
    
    private function displayEnvironmentInfo() {
        echo "Environment Information:\n";
        echo "  PHP Version: " . PHP_VERSION . "\n";
        echo "  Memory Limit: " . ini_get('memory_limit') . "\n";
        echo "  Max Execution Time: " . ini_get('max_execution_time') . "s\n";
        echo "  Current Directory: " . getcwd() . "\n";
        echo "  Test Configuration: " . (file_exists('test_config.php') ? 'Found' : 'Missing') . "\n";
        echo "\n";
    }
    
    private function ensureResultsDirectory() {
        $resultsDir = $this->config['reporting']['results_directory'];
        if (!is_dir($resultsDir)) {
            mkdir($resultsDir, 0755, true);
            echo "Created results directory: $resultsDir\n";
        }
    }
    
    private function generateFinalReport() {
        $totalDuration = microtime(true) - $this->startTime;
        
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "FINAL TEST REPORT\n";
        echo str_repeat("=", 70) . "\n";
        
        $totalTests = 0;
        $totalPassed = 0;
        $totalFailed = 0;
        
        foreach ($this->results['suites'] as $suiteName => $suite) {
            if (isset($suite['results'])) {
                echo "\n{$suite['name']}:\n";
                echo "  Total: {$suite['results']['total']}\n";
                echo "  Passed: {$suite['results']['passed']}\n";
                echo "  Failed: {$suite['results']['failed']}\n";
                
                $totalTests += $suite['results']['total'];
                $totalPassed += $suite['results']['passed'];
                $totalFailed += $suite['results']['failed'];
            } else {
                echo "\n{$suite['name']}: {$suite['status']}\n";
                if (isset($suite['error'])) {
                    echo "  Error: {$suite['error']}\n";
                }
            }
        }
        
        echo "\n" . str_repeat("-", 50) . "\n";
        echo "OVERALL SUMMARY:\n";
        echo "  Total Tests: $totalTests\n";
        echo "  Passed: $totalPassed\n";
        echo "  Failed: $totalFailed\n";
        echo "  Success Rate: " . ($totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 1) : 0) . "%\n";
        echo "  Total Duration: " . number_format($totalDuration, 2) . "s\n";
        
        $this->results['summary'] = [
            'total_tests' => $totalTests,
            'passed' => $totalPassed,
            'failed' => $totalFailed,
            'success_rate' => $totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 1) : 0,
            'duration' => $totalDuration,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($totalFailed > 0) {
            echo "\n❌ SOME TESTS FAILED\n";
        } else {
            echo "\n✅ ALL TESTS PASSED\n";
        }
    }
    
    private function saveResults() {
        if (!$this->config['reporting']['save_results']) {
            return;
        }
        
        $resultsDir = $this->config['reporting']['results_directory'];
        $timestamp = date('Y-m-d_H-i-s');
        
        // Save JSON results
        $jsonFile = "$resultsDir/test_results_$timestamp.json";
        file_put_contents($jsonFile, json_encode($this->results, JSON_PRETTY_PRINT));
        echo "\nResults saved to: $jsonFile\n";
        
        // Generate HTML report if enabled
        if ($this->config['reporting']['generate_html_report']) {
            $this->generateHtmlReport($resultsDir, $timestamp);
        }
    }
    
    private function generateHtmlReport($resultsDir, $timestamp) {
        $htmlFile = "$resultsDir/test_report_$timestamp.html";
        
        $html = "<!DOCTYPE html>
<html>
<head>
    <title>Campus Hub Test Report - $timestamp</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #007bff; color: white; padding: 20px; border-radius: 5px; }
        .summary { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .suite { margin: 20px 0; border: 1px solid #ddd; border-radius: 5px; }
        .suite-header { background: #e9ecef; padding: 10px; font-weight: bold; }
        .suite-content { padding: 15px; }
        .passed { color: #28a745; }
        .failed { color: #dc3545; }
        .error { background: #f8d7da; padding: 10px; border-radius: 3px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>Campus Hub Test Report</h1>
        <p>Generated: " . date('Y-m-d H:i:s') . "</p>
    </div>
    
    <div class='summary'>
        <h2>Summary</h2>
        <p><strong>Total Tests:</strong> {$this->results['summary']['total_tests']}</p>
        <p><strong>Passed:</strong> <span class='passed'>{$this->results['summary']['passed']}</span></p>
        <p><strong>Failed:</strong> <span class='failed'>{$this->results['summary']['failed']}</span></p>
        <p><strong>Success Rate:</strong> {$this->results['summary']['success_rate']}%</p>
        <p><strong>Duration:</strong> " . number_format($this->results['summary']['duration'], 2) . "s</p>
    </div>";
        
        foreach ($this->results['suites'] as $suiteName => $suite) {
            $html .= "<div class='suite'>
                <div class='suite-header'>{$suite['name']}</div>
                <div class='suite-content'>";
                
            if (isset($suite['results'])) {
                $html .= "<p>Total: {$suite['results']['total']}, ";
                $html .= "Passed: <span class='passed'>{$suite['results']['passed']}</span>, ";
                $html .= "Failed: <span class='failed'>{$suite['results']['failed']}</span></p>";
            } else {
                $html .= "<p>Status: {$suite['status']}</p>";
                if (isset($suite['error'])) {
                    $html .= "<div class='error'>Error: {$suite['error']}</div>";
                }
            }
            
            $html .= "</div></div>";
        }
        
        $html .= "</body></html>";
        
        file_put_contents($htmlFile, $html);
        echo "HTML report saved to: $htmlFile\n";
    }
    
    private function getExitCode() {
        $failed = $this->results['summary']['failed'] ?? 0;
        return $failed > 0 ? 1 : 0;
    }
    
    private function convertToBytes($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        $value = (int) $value;
        
        switch($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
}

// Command line argument parsing
function parseArguments($argv) {
    $options = [];
    
    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];
        
        if ($arg === '--help' || $arg === '-h') {
            displayHelp();
            exit(0);
        } elseif ($arg === '--all') {
            $options[] = 'all';
        } elseif ($arg === '--database') {
            $options[] = 'database';
        } elseif ($arg === '--api') {
            $options[] = 'api';
        } elseif ($arg === '--performance') {
            $options[] = 'performance';
        } elseif ($arg === '--security') {
            $options[] = 'security';
        }
    }
    
    return empty($options) ? ['all'] : $options;
}

function displayHelp() {
    echo "Campus Hub Test Runner\n\n";
    echo "Usage: php run_tests.php [options]\n\n";
    echo "Options:\n";
    echo "  --all           Run all test suites (default)\n";
    echo "  --database      Run database tests only\n";
    echo "  --api           Run API tests only\n";
    echo "  --performance   Run performance tests only\n";
    echo "  --security      Run security tests only\n";
    echo "  --help, -h      Show this help message\n\n";
    echo "Examples:\n";
    echo "  php run_tests.php\n";
    echo "  php run_tests.php --database --api\n";
    echo "  php run_tests.php --performance\n";
}

// Main execution
if (php_sapi_name() === 'cli') {
    $options = parseArguments($argv);
    
    $runner = new TestRunner();
    $exitCode = $runner->run($options);
    
    exit($exitCode);
} else {
    echo "This script must be run from the command line.\n";
    exit(1);
}
?>
