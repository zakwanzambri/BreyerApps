<?php
/**
 * Full Integration Database Setup
 * Runs the enhanced schema and sample data
 */

require_once 'config/database.php';

echo "🚀 Setting up Full Integration Database...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Read and execute the enhanced schema
    $schemaFile = __DIR__ . '/database/full_integration_schema.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    echo "📊 Executing " . count($statements) . " SQL statements...\n\n";
    
    foreach ($statements as $index => $statement) {
        try {
            if (!empty(trim($statement))) {
                $db->query($statement);
                echo "✅ Statement " . ($index + 1) . " executed successfully\n";
            }
        } catch (Exception $e) {
            echo "⚠️  Statement " . ($index + 1) . " warning: " . $e->getMessage() . "\n";
            // Continue with other statements
        }
    }
    
    echo "\n🎉 Full Integration Database Setup Complete!\n\n";
    
    // Verify tables
    echo "📋 Verifying created tables:\n";
    $tables = [
        'users', 'programs', 'courses', 'enrollments', 
        'assignments', 'assignment_submissions', 'academic_events', 
        'notifications', 'user_preferences'
    ];
    
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    }
    
    // Sample data verification
    echo "\n📊 Sample data counts:\n";
    $dataTables = [
        'users' => 'Users',
        'programs' => 'Programs', 
        'courses' => 'Courses',
        'enrollments' => 'Enrollments',
        'assignments' => 'Assignments',
        'academic_events' => 'Academic Events',
        'notifications' => 'Notifications'
    ];
    
    foreach ($dataTables as $table => $label) {
        try {
            $result = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $result->fetch_assoc()['count'];
            echo "📈 $label: $count records\n";
        } catch (Exception $e) {
            echo "⚠️  Could not count $label: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎯 FULL INTEGRATION READY!\n";
    echo "👉 You can now use:\n";
    echo "   - Real-time dashboard data\n";
    echo "   - Personalized course content\n"; 
    echo "   - Dynamic notifications\n";
    echo "   - Academic calendar integration\n";
    echo "   - Cross-component communication\n\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up database: " . $e->getMessage() . "\n";
    exit(1);
}
?>
