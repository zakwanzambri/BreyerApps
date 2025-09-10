<?php
/**
 * Campus Hub Database Tests
 * Tests for database operations, queries, and data integrity
 */

require_once 'TestFramework.php';
require_once '../php/config.php';

class DatabaseTests {
    private $testFramework;
    private $pdo;
    
    public function __construct($testFramework) {
        $this->testFramework = $testFramework;
        $this->pdo = $testFramework->createTestDatabase();
    }
    
    public function runAllTests() {
        $this->testDatabaseConnection();
        $this->testUserOperations();
        $this->testNewsOperations();
        $this->testEventOperations();
        $this->testSearchOperations();
        $this->testAnalyticsOperations();
        $this->testDataIntegrity();
        $this->testPerformance();
    }
    
    /**
     * Test database connection and basic operations
     */
    public function testDatabaseConnection() {
        $this->testFramework->suite('Database Connection Tests', function($test) {
            
            $test->test('Database connection should be established', function($test) {
                $test->assertNotNull($this->pdo);
                $test->assertInstanceOf('PDO', $this->pdo);
            });
            
            $test->test('Database should have required tables', function($test) {
                $requiredTables = [
                    'users', 'news', 'events', 'user_sessions', 
                    'search_analytics', 'user_behavior_tracking'
                ];
                
                foreach ($requiredTables as $table) {
                    $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
                    $stmt->execute([$table]);
                    $result = $stmt->fetch();
                    
                    $test->assertNotNull($result, "Required table '$table' not found");
                }
            });
            
            $test->test('Database character set should be UTF-8', function($test) {
                $stmt = $this->pdo->query("SELECT @@character_set_database as charset");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $test->assertTrue(
                    strpos($result['charset'], 'utf8') !== false,
                    "Database charset should be UTF-8, got: " . $result['charset']
                );
            });
        });
    }
    
    /**
     * Test user-related database operations
     */
    public function testUserOperations() {
        $this->testFramework->suite('User Database Operations', function($test) {
            
            $test->test('User creation should work correctly', function($test) {
                $userData = [
                    'username' => 'test_user_' . time(),
                    'email' => 'test_' . time() . '@example.com',
                    'password_hash' => password_hash('testpass123', PASSWORD_DEFAULT),
                    'user_type' => 'student',
                    'full_name' => 'Test User',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, password_hash, user_type, full_name, created_at) 
                    VALUES (:username, :email, :password_hash, :user_type, :full_name, :created_at)
                ");
                
                $result = $stmt->execute($userData);
                $test->assertTrue($result, "User insertion failed");
                
                $userId = $this->pdo->lastInsertId();
                $test->assertTrue($userId > 0, "User ID should be positive");
                
                // Cleanup
                $this->pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            });
            
            $test->test('User login validation should work', function($test) {
                // Create test user
                $username = 'login_test_' . time();
                $password = 'testpass123';
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, password_hash, user_type, created_at) 
                    VALUES (?, ?, ?, 'student', NOW())
                ");
                $stmt->execute([$username, $username . '@example.com', $passwordHash]);
                $userId = $this->pdo->lastInsertId();
                
                // Test login
                $loginStmt = $this->pdo->prepare("
                    SELECT id, username, password_hash FROM users 
                    WHERE username = ? OR email = ?
                ");
                $loginStmt->execute([$username, $username]);
                $user = $loginStmt->fetch(PDO::FETCH_ASSOC);
                
                $test->assertNotNull($user, "User not found");
                $test->assertTrue(password_verify($password, $user['password_hash']), "Password verification failed");
                
                // Cleanup
                $this->pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            });
            
            $test->test('User data constraints should be enforced', function($test) {
                // Test unique username constraint
                $username = 'unique_test_' . time();
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, password_hash, user_type, created_at) 
                    VALUES (?, ?, ?, 'student', NOW())
                ");
                
                // First insertion should succeed
                $result1 = $stmt->execute([$username, $username . '1@example.com', 'hash1']);
                $test->assertTrue($result1, "First user insertion should succeed");
                $userId1 = $this->pdo->lastInsertId();
                
                // Second insertion with same username should fail
                try {
                    $stmt->execute([$username, $username . '2@example.com', 'hash2']);
                    $test->assertTrue(false, "Duplicate username should not be allowed");
                } catch (PDOException $e) {
                    $test->assertTrue(true, "Duplicate username correctly rejected");
                }
                
                // Cleanup
                $this->pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId1]);
            });
        });
    }
    
    /**
     * Test news-related database operations
     */
    public function testNewsOperations() {
        $this->testFramework->suite('News Database Operations', function($test) {
            
            $test->test('News creation and retrieval should work', function($test) {
                // Create test user first
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, password_hash, user_type, created_at) 
                    VALUES ('news_author_test', 'news_author@example.com', 'hash', 'admin', NOW())
                ");
                $stmt->execute();
                $authorId = $this->pdo->lastInsertId();
                
                // Create news item
                $newsData = [
                    'title' => 'Test News Article',
                    'content' => 'This is test news content with sufficient length to test the content field.',
                    'author_id' => $authorId,
                    'status' => 'published',
                    'category' => 'general',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO news (title, content, author_id, status, category, created_at) 
                    VALUES (:title, :content, :author_id, :status, :category, :created_at)
                ");
                
                $result = $stmt->execute($newsData);
                $test->assertTrue($result, "News insertion failed");
                
                $newsId = $this->pdo->lastInsertId();
                
                // Retrieve and verify
                $retrieveStmt = $this->pdo->prepare("SELECT * FROM news WHERE id = ?");
                $retrieveStmt->execute([$newsId]);
                $news = $retrieveStmt->fetch(PDO::FETCH_ASSOC);
                
                $test->assertNotNull($news, "News item not found");
                $test->assertEquals($newsData['title'], $news['title']);
                $test->assertEquals($newsData['content'], $news['content']);
                
                // Cleanup
                $this->pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$newsId]);
                $this->pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$authorId]);
            });
            
            $test->test('News search functionality should work', function($test) {
                // Create test data
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, password_hash, user_type, created_at) 
                    VALUES ('search_author_test', 'search_author@example.com', 'hash', 'admin', NOW())
                ");
                $stmt->execute();
                $authorId = $this->pdo->lastInsertId();
                
                $newsItems = [
                    ['title' => 'Campus Technology Update', 'content' => 'New software implementation'],
                    ['title' => 'Student Activities Fair', 'content' => 'Join us for activities'],
                    ['title' => 'Technology Workshop', 'content' => 'Learn new technologies']
                ];
                
                $newsIds = [];
                foreach ($newsItems as $item) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO news (title, content, author_id, status, created_at) 
                        VALUES (?, ?, ?, 'published', NOW())
                    ");
                    $stmt->execute([$item['title'], $item['content'], $authorId]);
                    $newsIds[] = $this->pdo->lastInsertId();
                }
                
                // Test search
                $searchStmt = $this->pdo->prepare("
                    SELECT * FROM news 
                    WHERE (title LIKE ? OR content LIKE ?) AND status = 'published'
                ");
                $searchTerm = '%technology%';
                $searchStmt->execute([$searchTerm, $searchTerm]);
                $results = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $test->assertTrue(count($results) >= 2, "Search should find technology-related news");
                
                // Cleanup
                foreach ($newsIds as $id) {
                    $this->pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$id]);
                }
                $this->pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$authorId]);
            });
        });
    }
    
    /**
     * Test event-related database operations
     */
    public function testEventOperations() {
        $this->testFramework->suite('Event Database Operations', function($test) {
            
            $test->test('Event date queries should work correctly', function($test) {
                // Create test user
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, password_hash, user_type, created_at) 
                    VALUES ('event_creator_test', 'event_creator@example.com', 'hash', 'admin', NOW())
                ");
                $stmt->execute();
                $creatorId = $this->pdo->lastInsertId();
                
                // Create events with different dates
                $events = [
                    ['title' => 'Past Event', 'event_date' => '2023-01-01 10:00:00'],
                    ['title' => 'Future Event 1', 'event_date' => '2025-01-01 10:00:00'],
                    ['title' => 'Future Event 2', 'event_date' => '2025-02-01 10:00:00']
                ];
                
                $eventIds = [];
                foreach ($events as $event) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO events (title, description, event_date, location, created_by, created_at) 
                        VALUES (?, 'Test description', ?, 'Test Location', ?, NOW())
                    ");
                    $stmt->execute([$event['title'], $event['event_date'], $creatorId]);
                    $eventIds[] = $this->pdo->lastInsertId();
                }
                
                // Test future events query
                $futureStmt = $this->pdo->prepare("
                    SELECT * FROM events WHERE event_date > NOW()
                ");
                $futureStmt->execute();
                $futureEvents = $futureStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $test->assertTrue(count($futureEvents) >= 2, "Should find future events");
                
                // Cleanup
                foreach ($eventIds as $id) {
                    $this->pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
                }
                $this->pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$creatorId]);
            });
        });
    }
    
    /**
     * Test search analytics operations
     */
    public function testSearchOperations() {
        $this->testFramework->suite('Search Analytics Operations', function($test) {
            
            $test->test('Search analytics tracking should work', function($test) {
                $searchData = [
                    'query' => 'test search query',
                    'search_type' => 'global',
                    'results_count' => 5,
                    'search_time' => date('Y-m-d H:i:s'),
                    'user_id' => null,
                    'ip_address' => '127.0.0.1'
                ];
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO search_analytics (query, search_type, results_count, search_time, user_id, ip_address) 
                    VALUES (:query, :search_type, :results_count, :search_time, :user_id, :ip_address)
                ");
                
                $result = $stmt->execute($searchData);
                $test->assertTrue($result, "Search analytics insertion failed");
                
                $searchId = $this->pdo->lastInsertId();
                
                // Verify retrieval
                $retrieveStmt = $this->pdo->prepare("SELECT * FROM search_analytics WHERE id = ?");
                $retrieveStmt->execute([$searchId]);
                $search = $retrieveStmt->fetch(PDO::FETCH_ASSOC);
                
                $test->assertNotNull($search, "Search analytics not found");
                $test->assertEquals($searchData['query'], $search['query']);
                
                // Cleanup
                $this->pdo->prepare("DELETE FROM search_analytics WHERE id = ?")->execute([$searchId]);
            });
        });
    }
    
    /**
     * Test analytics operations
     */
    public function testAnalyticsOperations() {
        $this->testFramework->suite('Analytics Database Operations', function($test) {
            
            $test->test('User behavior tracking should work', function($test) {
                $behaviorData = [
                    'session_id' => 'test_session_' . time(),
                    'user_id' => null,
                    'action_type' => 'page_view',
                    'page_url' => '/test-page',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Test User Agent'
                ];
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO user_behavior_tracking 
                    (session_id, user_id, action_type, page_url, timestamp, ip_address, user_agent) 
                    VALUES (:session_id, :user_id, :action_type, :page_url, :timestamp, :ip_address, :user_agent)
                ");
                
                $result = $stmt->execute($behaviorData);
                $test->assertTrue($result, "Behavior tracking insertion failed");
                
                $trackingId = $this->pdo->lastInsertId();
                
                // Cleanup
                $this->pdo->prepare("DELETE FROM user_behavior_tracking WHERE id = ?")->execute([$trackingId]);
            });
        });
    }
    
    /**
     * Test data integrity and relationships
     */
    public function testDataIntegrity() {
        $this->testFramework->suite('Data Integrity Tests', function($test) {
            
            $test->test('Foreign key constraints should be enforced', function($test) {
                // Try to create news with non-existent author
                try {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO news (title, content, author_id, status, created_at) 
                        VALUES ('Test', 'Content', 99999, 'published', NOW())
                    ");
                    $stmt->execute();
                    $test->assertTrue(false, "Foreign key constraint should prevent invalid author_id");
                } catch (PDOException $e) {
                    $test->assertTrue(true, "Foreign key constraint correctly enforced");
                }
            });
            
            $test->test('Required fields should be enforced', function($test) {
                // Try to create user without required fields
                try {
                    $stmt = $this->pdo->prepare("INSERT INTO users (username) VALUES (?)");
                    $stmt->execute(['incomplete_user']);
                    $test->assertTrue(false, "Should require email field");
                } catch (PDOException $e) {
                    $test->assertTrue(true, "Required fields correctly enforced");
                }
            });
        });
    }
    
    /**
     * Test database performance
     */
    public function testPerformance() {
        $this->testFramework->suite('Database Performance Tests', function($test) {
            
            $test->test('News query performance should be acceptable', function($test) {
                $performance = $test->measurePerformance(function() {
                    $stmt = $this->pdo->prepare("
                        SELECT n.*, u.username as author_name 
                        FROM news n 
                        JOIN users u ON n.author_id = u.id 
                        WHERE n.status = 'published' 
                        ORDER BY n.created_at DESC 
                        LIMIT 20
                    ");
                    $stmt->execute();
                    $stmt->fetchAll();
                }, 10);
                
                $avgTime = $performance['avg'];
                $test->assertTrue($avgTime < 0.1, "News query too slow: {$avgTime}s");
                
                echo "    Average query time: " . number_format($avgTime * 1000, 2) . "ms\n";
            });
            
            $test->test('Search query performance should be acceptable', function($test) {
                $performance = $test->measurePerformance(function() {
                    $stmt = $this->pdo->prepare("
                        SELECT * FROM news 
                        WHERE (title LIKE ? OR content LIKE ?) AND status = 'published'
                        ORDER BY created_at DESC
                    ");
                    $stmt->execute(['%test%', '%test%']);
                    $stmt->fetchAll();
                }, 10);
                
                $avgTime = $performance['avg'];
                $test->assertTrue($avgTime < 0.2, "Search query too slow: {$avgTime}s");
                
                echo "    Average search time: " . number_format($avgTime * 1000, 2) . "ms\n";
            });
        });
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $testFramework = new CampusTestFramework();
    
    echo "Starting Campus Hub Database Tests...\n\n";
    
    try {
        $databaseTests = new DatabaseTests($testFramework);
        $databaseTests->runAllTests();
        
        $testFramework->generateReport();
        $testFramework->saveResults('tests/database_test_results.json');
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "Make sure the test database is configured and accessible.\n";
        exit(1);
    }
}
?>
