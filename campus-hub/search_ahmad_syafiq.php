<?php
/**
 * Search for Ahmad Syafiq in Campus Hub Database
 */

echo "🔍 SEARCHING FOR AHMAD SYAFIQ\n";
echo "=============================\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=campus_hub_db', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Search for Ahmad Syafiq by name
    echo "🔎 Searching for 'Ahmad Syafiq' in full_name...\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE full_name LIKE ?");
    $stmt->execute(['%Ahmad Syafiq%']);
    $results = $stmt->fetchAll();
    
    if ($results) {
        echo "✅ Found " . count($results) . " user(s) matching 'Ahmad Syafiq':\n\n";
        foreach ($results as $user) {
            echo "📋 User Details:\n";
            echo "  ID: {$user['id']}\n";
            echo "  Username: {$user['username']}\n";
            echo "  Full Name: {$user['full_name']}\n";
            echo "  Email: {$user['email']}\n";
            echo "  Role: {$user['role']}\n";
            echo "  Program: " . ($user['program_id'] ? "Program ID {$user['program_id']}" : "Not assigned") . "\n";
            echo "  Status: {$user['status']}\n";
            echo "  Created: {$user['created_at']}\n\n";
        }
    } else {
        echo "❌ No user found with name 'Ahmad Syafiq'\n\n";
        
        // Search for any 'Ahmad' or 'Syafiq'
        echo "🔎 Searching for users with 'Ahmad' or 'Syafiq'...\n";
        $stmt = $pdo->prepare("SELECT * FROM users WHERE full_name LIKE ? OR full_name LIKE ?");
        $stmt->execute(['%Ahmad%', '%Syafiq%']);
        $results = $stmt->fetchAll();
        
        if ($results) {
            echo "✅ Found " . count($results) . " user(s) with similar names:\n\n";
            foreach ($results as $user) {
                echo "  - {$user['full_name']} ({$user['username']}) - {$user['role']}\n";
            }
        } else {
            echo "❌ No users found with 'Ahmad' or 'Syafiq' in name\n";
        }
    }
    
    // Show all current users
    echo "\n📊 All Current Users in Database:\n";
    echo "=================================\n";
    $stmt = $pdo->query("SELECT id, username, full_name, role FROM users ORDER BY id");
    while ($user = $stmt->fetch()) {
        echo "{$user['id']}. {$user['full_name']} ({$user['username']}) - {$user['role']}\n";
    }
    
    echo "\n💡 To add Ahmad Syafiq as a new user:\n";
    echo "1. Use admin panel: http://localhost/BreyerApps/campus-hub/admin/users.html\n";
    echo "2. Login as admin: admin/admin123\n";
    echo "3. Click 'Add New User' and enter Ahmad Syafiq's details\n";
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}
?>
