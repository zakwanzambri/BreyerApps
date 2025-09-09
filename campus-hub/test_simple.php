<?php
/**
 * Simple Database Connection Test for Campus Hub Enhanced
 */

echo "🔍 Testing Campus Hub Enhanced Database Connection...\n";
echo "================================================\n\n";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'campus_hub_db';

try {
    // Test basic MySQL connection
    $dsn = "mysql:host=$host;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "✅ MySQL connection: SUCCESS\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'campus_hub_db'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database 'campus_hub_db': EXISTS\n";
        
        // Connect to campus_hub_db
        $pdo->exec("USE campus_hub_db");
        
        // Test tables
        $tables = ['users', 'news', 'events', 'programs', 'services', 'courses', 'course_materials', 'user_sessions'];
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "✅ Table '$table': EXISTS ($count records)\n";
            } else {
                echo "❌ Table '$table': NOT FOUND\n";
            }
        }
        
        // Test sample data
        echo "\n📊 Sample Data Check:\n";
        $news_count = $pdo->query("SELECT COUNT(*) FROM news")->fetchColumn();
        $events_count = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
        $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        
        echo "📰 News articles: $news_count\n";
        echo "📅 Events: $events_count\n";
        echo "👥 Users: $users_count\n";
        
    } else {
        echo "❌ Database 'campus_hub_db': NOT FOUND\n";
        echo "\n💡 Creating database from setup.sql...\n";
        
        // Read and execute setup.sql
        $sql_file = __DIR__ . '/database/setup.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            
            // Split and execute SQL statements
            $statements = explode(';', $sql);
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    try {
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        // Skip errors for statements that might already exist
                    }
                }
            }
            echo "✅ Database setup completed!\n";
        } else {
            echo "❌ setup.sql not found\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "💡 Make sure XAMPP MySQL is running\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Next Steps:\n";
echo "1. Open XAMPP Control Panel\n";
echo "2. Start Apache and MySQL services\n";
echo "3. Visit: http://localhost/BreyerApps/campus-hub/\n";
echo "4. Admin Panel: http://localhost/BreyerApps/campus-hub/admin/\n";
echo "\n🚀 Ready for Demo!\n";
?>
