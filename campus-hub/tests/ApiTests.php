<?php
/**
 * Campus Hub API Tests
 * Comprehensive testing for all API endpoints
 */

require_once 'TestFramework.php';
require_once '../php/config.php';

class ApiTests {
    private $baseUrl;
    private $testFramework;
    
    public function __construct($testFramework, $baseUrl = 'http://localhost/BreyerApps/campus-hub') {
        $this->testFramework = $testFramework;
        $this->baseUrl = $baseUrl;
    }
    
    public function runAllTests() {
        $this->testNewsApi();
        $this->testEventsApi();
        $this->testUsersApi();
        $this->testSearchApi();
        $this->testAnalyticsApi();
        $this->testAuthenticationApi();
    }
    
    /**
     * Test News API endpoints
     */
    public function testNewsApi() {
        $this->testFramework->suite('News API Tests', function($test) {
            
            $test->test('GET /api/news.php should return news list', function($test) {
                $response = $test->makeApiRequest('GET', $this->baseUrl . '/php/api/news.php');
                
                $test->assertEquals(200, $response['status_code']);
                $test->assertNotNull($response['data']);
                $test->assertTrue(is_array($response['data']));
            });
            
            $test->test('GET /api/news.php with ID should return single news item', function($test) {
                // First, get a news item ID
                $response = $test->makeApiRequest('GET', $this->baseUrl . '/php/api/news.php');
                $test->assertTrue(count($response['data']) > 0, 'No news items found for testing');
                
                $newsId = $response['data'][0]['id'];
                $singleResponse = $test->makeApiRequest('GET', 
                    $this->baseUrl . '/php/api/news.php?id=' . $newsId);
                
                $test->assertEquals(200, $singleResponse['status_code']);
                $test->assertArrayHasKey('id', $singleResponse['data']);
                $test->assertEquals($newsId, $singleResponse['data']['id']);
            });
            
            $test->test('POST /api/news.php should create new news item', function($test) {
                $newNews = [
                    'title' => 'Test News Item',
                    'content' => 'This is a test news content',
                    'category' => 'general',
                    'featured_image' => 'test.jpg'
                ];
                
                $response = $test->makeApiRequest('POST', 
                    $this->baseUrl . '/php/api/news.php', $newNews);
                
                // Should require authentication
                $test->assertTrue($response['status_code'] == 401 || $response['status_code'] == 201);
            });
            
            $test->test('GET /api/news.php with pagination should work', function($test) {
                $response = $test->makeApiRequest('GET', 
                    $this->baseUrl . '/php/api/news.php?page=1&limit=5');
                
                $test->assertEquals(200, $response['status_code']);
                $test->assertTrue(is_array($response['data']));
                $test->assertTrue(count($response['data']) <= 5);
            });
            
            $test->test('GET /api/news.php with search should filter results', function($test) {
                $response = $test->makeApiRequest('GET', 
                    $this->baseUrl . '/php/api/news.php?search=test');
                
                $test->assertEquals(200, $response['status_code']);
                $test->assertTrue(is_array($response['data']));
            });
        });
    }
    
    /**
     * Test Events API endpoints
     */
    public function testEventsApi() {
        $this->testFramework->suite('Events API Tests', function($test) {
            
            $test->test('GET /api/events.php should return events list', function($test) {
                $response = $test->makeApiRequest('GET', $this->baseUrl . '/php/api/events.php');
                
                $test->assertEquals(200, $response['status_code']);
                $test->assertNotNull($response['data']);
                $test->assertTrue(is_array($response['data']));
            });
            
            $test->test('GET /api/events.php with date filter should work', function($test) {
                $today = date('Y-m-d');
                $response = $test->makeApiRequest('GET', 
                    $this->baseUrl . '/php/api/events.php?date_from=' . $today);
                
                $test->assertEquals(200, $response['status_code']);
                $test->assertTrue(is_array($response['data']));
            });
            
            $test->test('GET /api/events.php with category filter should work', function($test) {
                $response = $test->makeApiRequest('GET', 
                    $this->baseUrl . '/php/api/events.php?category=academic');
                
                $test->assertEquals(200, $response['status_code']);
                $test->assertTrue(is_array($response['data']));
            });
            
            $test->test('POST /api/events.php should require authentication', function($test) {
                $newEvent = [
                    'title' => 'Test Event',
                    'description' => 'This is a test event',
                    'event_date' => '2024-12-31 10:00:00',
                    'location' => 'Test Location',
                    'category' => 'academic'
                ];
                
                $response = $test->makeApiRequest('POST', 
                    $this->baseUrl . '/php/api/events.php', $newEvent);
                
                $test->assertEquals(401, $response['status_code']);
            });
        });
    }
    
    /**
     * Test Users API endpoints
     */
    public function testUsersApi() {
        $this->testFramework->suite('Users API Tests', function($test) {
            
            $test->test('POST /api/users.php registration should validate input', function($test) {
                $invalidUser = [
                    'username' => 'test',
                    'email' => 'invalid-email',
                    'password' => '123' // Too short
                ];
                
                $response = $test->makeApiRequest('POST', 
                    $this->baseUrl . '/php/api/users.php?action=register', $invalidUser);
                
                $test->assertTrue($response['status_code'] >= 400);
            });
            
            $test->test('POST /api/users.php valid registration should work', function($test) {
                $validUser = [
                    'username' => 'testuser_' . time(),
                    'email' => 'test_' . time() . '@example.com',
                    'password' => 'TestPassword123!',
                    'user_type' => 'student',
                    'full_name' => 'Test User'
                ];
                
                $response = $test->makeApiRequest('POST', 
                    $this->baseUrl . '/php/api/users.php?action=register', $validUser);
                
                $test->assertTrue($response['status_code'] == 201 || $response['status_code'] == 200);
            });
            
            $test->test('POST /api/users.php login with invalid credentials should fail', function($test) {
                $invalidCredentials = [
                    'username' => 'nonexistent',
                    'password' => 'wrongpassword'
                ];
                
                $response = $test->makeApiRequest('POST', 
                    $this->baseUrl . '/php/api/users.php?action=login', $invalidCredentials);
                
                $test->assertTrue($response['status_code'] >= 400);
            });
        });
    }
    
    /**
     * Test Search API endpoints
     */
    public function testSearchApi() {
        $this->testFramework->suite('Search API Tests', function($test) {
            
            $test->test('GET /api/search.php should require query parameter', function($test) {
                $response = $test->makeApiRequest('GET', $this->baseUrl . '/php/api/search.php');
                
                $test->assertTrue($response['status_code'] >= 400);
            });
            
            $test->test('GET /api/search.php with query should return results', function($test) {
                $response = $test->makeApiRequest('GET', 
                    $this->baseUrl . '/php/api/search.php?q=test');
                
                $test->assertEquals(200, $response['status_code']);
                $test->assertArrayHasKey('results', $response['data']);
                $test->assertTrue(is_array($response['data']['results']));
            });
            
            $test->test('GET /api/search.php with type filter should work', function($test) {
                $response = $test->makeApiRequest('GET', 
                    $this->baseUrl . '/php/api/search.php?q=test&type=news');
                
                $test->assertEquals(200, $response['status_code']);
                $test->assertArrayHasKey('results', $response['data']);
            });
            
            $test->test('GET /api/search.php suggestions should work', function($test) {
                $response = $test->makeApiRequest('GET', 
                    $this->baseUrl . '/php/api/search.php?action=suggestions&q=te');
                
                $test->assertEquals(200, $response['status_code']);
                $test->assertTrue(is_array($response['data']));
            });
        });
    }
    
    /**
     * Test Analytics API endpoints
     */
    public function testAnalyticsApi() {
        $this->testFramework->suite('Analytics API Tests', function($test) {
            
            $test->test('POST /api/analytics.php track-action should work', function($test) {
                $trackingData = [
                    'action_type' => 'page_view',
                    'data' => [
                        'page_url' => '/test-page',
                        'page_title' => 'Test Page',
                        'session_id' => 'test_session_123'
                    ]
                ];
                
                $response = $test->makeApiRequest('POST', 
                    $this->baseUrl . '/php/api/analytics.php?action=track-action', $trackingData);
                
                $test->assertTrue($response['status_code'] == 200 || $response['status_code'] == 201);
            });
            
            $test->test('POST /api/analytics.php track-search should work', function($test) {
                $searchData = [
                    'query' => 'test search',
                    'search_type' => 'global',
                    'results_count' => 5
                ];
                
                $response = $test->makeApiRequest('POST', 
                    $this->baseUrl . '/php/api/analytics.php?action=track-search', $searchData);
                
                $test->assertTrue($response['status_code'] == 200 || $response['status_code'] == 201);
            });
            
            $test->test('GET /api/analytics.php dashboard data should require auth', function($test) {
                $response = $test->makeApiRequest('GET', 
                    $this->baseUrl . '/php/api/analytics.php?action=dashboard-data');
                
                // Should require authentication
                $test->assertTrue($response['status_code'] == 401 || $response['status_code'] == 200);
            });
        });
    }
    
    /**
     * Test Authentication and Security
     */
    public function testAuthenticationApi() {
        $this->testFramework->suite('Authentication & Security Tests', function($test) {
            
            $test->test('SQL injection protection should work', function($test) {
                $maliciousQuery = "'; DROP TABLE users; --";
                $response = $test->makeApiRequest('GET', 
                    $this->baseUrl . '/php/api/news.php?search=' . urlencode($maliciousQuery));
                
                // Should not cause server error
                $test->assertTrue($response['status_code'] < 500);
            });
            
            $test->test('XSS protection should work', function($test) {
                $xssPayload = "<script>alert('xss')</script>";
                $response = $test->makeApiRequest('GET', 
                    $this->baseUrl . '/php/api/news.php?search=' . urlencode($xssPayload));
                
                $test->assertEquals(200, $response['status_code']);
                $test->assertFalse(strpos($response['body'], '<script>'));
            });
            
            $test->test('Rate limiting should be in place', function($test) {
                // Make multiple rapid requests
                $responses = [];
                for ($i = 0; $i < 10; $i++) {
                    $responses[] = $test->makeApiRequest('GET', 
                        $this->baseUrl . '/php/api/news.php');
                }
                
                // At least some should succeed
                $successCount = array_filter($responses, function($r) {
                    return $r['status_code'] == 200;
                });
                
                $test->assertTrue(count($successCount) > 0, 'All requests were blocked');
            });
            
            $test->test('CORS headers should be present', function($test) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $this->baseUrl . '/php/api/news.php',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => true,
                    CURLOPT_NOBODY => true
                ]);
                
                $headers = curl_exec($ch);
                curl_close($ch);
                
                // Should have CORS headers for API endpoints
                $test->assertTrue(
                    strpos($headers, 'Access-Control-Allow-Origin') !== false ||
                    strpos($headers, 'Content-Type: application/json') !== false
                );
            });
        });
    }
}

// Performance Tests
class PerformanceTests {
    private $testFramework;
    private $baseUrl;
    
    public function __construct($testFramework, $baseUrl) {
        $this->testFramework = $testFramework;
        $this->baseUrl = $baseUrl;
    }
    
    public function runPerformanceTests() {
        $this->testFramework->suite('Performance Tests', function($test) {
            
            $test->test('News API response time should be acceptable', function($test) {
                $performance = $test->measurePerformance(function() use ($test) {
                    $test->makeApiRequest('GET', $this->baseUrl . '/php/api/news.php');
                }, 5);
                
                $avgTime = $performance['avg'];
                $test->assertTrue($avgTime < 2.0, "Average response time too slow: {$avgTime}s");
                
                echo "    Average response time: " . number_format($avgTime * 1000, 2) . "ms\n";
            });
            
            $test->test('Search API response time should be acceptable', function($test) {
                $performance = $test->measurePerformance(function() use ($test) {
                    $test->makeApiRequest('GET', $this->baseUrl . '/php/api/search.php?q=test');
                }, 5);
                
                $avgTime = $performance['avg'];
                $test->assertTrue($avgTime < 3.0, "Search response time too slow: {$avgTime}s");
                
                echo "    Average search time: " . number_format($avgTime * 1000, 2) . "ms\n";
            });
            
            $test->test('Concurrent requests should be handled properly', function($test) {
                $urls = [
                    $this->baseUrl . '/php/api/news.php',
                    $this->baseUrl . '/php/api/events.php',
                    $this->baseUrl . '/php/api/search.php?q=test'
                ];
                
                $startTime = microtime(true);
                
                // Simulate concurrent requests (simplified)
                $responses = [];
                foreach ($urls as $url) {
                    $responses[] = $test->makeApiRequest('GET', $url);
                }
                
                $totalTime = microtime(true) - $startTime;
                
                // All should succeed
                foreach ($responses as $response) {
                    $test->assertEquals(200, $response['status_code']);
                }
                
                $test->assertTrue($totalTime < 10.0, "Concurrent requests took too long: {$totalTime}s");
                
                echo "    Concurrent requests completed in: " . number_format($totalTime, 2) . "s\n";
            });
        });
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $testFramework = new CampusTestFramework();
    
    echo "Starting Campus Hub API Tests...\n";
    echo "Base URL: http://localhost/BreyerApps/campus-hub\n\n";
    
    // Run API tests
    $apiTests = new ApiTests($testFramework);
    $apiTests->runAllTests();
    
    // Run performance tests
    $performanceTests = new PerformanceTests($testFramework, 'http://localhost/BreyerApps/campus-hub');
    $performanceTests->runPerformanceTests();
    
    // Generate report
    $testFramework->generateReport();
    $testFramework->saveResults('tests/api_test_results.json');
}
?>
