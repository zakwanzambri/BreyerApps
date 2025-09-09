<?php
// Test database connection for Campus Hub Enhanced
require_once 'php/config.php';

echo "🔍 Testing Campus Hub Enhanced Database Connection...\n";
echo "================================================\n\n";

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "✅ Database connection: SUCCESS\n";
    
    // Test if campus_hub_db exists
    $stmt = $connection->query("SHOW DATABASES LIKE 'campus_hub_db'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database 'campus_hub_db': EXISTS\n";
        
        // Test tables
        $connection->exec("USE campus_hub_db");
        $tables = ['users', 'news', 'events', 'programs', 'services', 'courses', 'course_materials', 'user_sessions'];
        
        foreach ($tables as $table) {
            $stmt = $connection->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $count = $connection->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "✅ Table '$table': EXISTS ($count records)\n";
            } else {
                echo "❌ Table '$table': NOT FOUND\n";
            }
        }
        
    } else {
        echo "❌ Database 'campus_hub_db': NOT FOUND\n";
        echo "💡 Run database/setup.sql to create the database\n";
    }
    
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "💡 Make sure XAMPP MySQL is running\n";
}

echo "\n🎯 Next Steps:\n";
echo "1. Open XAMPP Control Panel\n";
echo "2. Start Apache and MySQL services\n";
echo "3. Visit: http://localhost/BreyerApps/campus-hub/\n";
echo "4. Admin Panel: http://localhost/BreyerApps/campus-hub/admin/\n";
?>
