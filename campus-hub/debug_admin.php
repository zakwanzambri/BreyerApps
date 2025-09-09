<?php
/**
 * Debug Admin Login Issue - Campus Hub Enhanced
 */

echo "🔍 DEBUGGING ADMIN LOGIN ISSUE\n";
echo "==============================\n\n";

try {
    // Test MySQL connection
    $pdo = new PDO('mysql:host=localhost;dbname=campus_hub_db;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ MySQL Connection: SUCCESS\n\n";
    
    // Check users table
    echo "📊 Checking users table:\n";
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "❌ NO USERS FOUND! Table is empty.\n";
        echo "💡 Creating admin user...\n\n";
        
        // Create admin user
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute(['admin', 'admin@campus.edu', $admin_password, 'admin']);
        
        echo "✅ Admin user created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
        echo "Role: admin\n\n";
    } else {
        echo "Found " . count($users) . " users:\n";
        foreach ($users as $user) {
            echo "- ID: {$user['id']}, Username: {$user['username']}, Role: {$user['role']}\n";
        }
        echo "\n";
    }
    
    // Test admin login specifically
    echo "🔐 Testing admin login:\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Admin user found in database\n";
        echo "Username: {$admin['username']}\n";
        echo "Email: {$admin['email']}\n";
        echo "Role: {$admin['role']}\n";
        
        // Test password verification
        $test_password = 'admin123';
        if (password_verify($test_password, $admin['password'])) {
            echo "✅ Password verification: SUCCESS\n";
        } else {
            echo "❌ Password verification: FAILED\n";
            echo "💡 Updating password...\n";
            
            // Update password
            $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
            $stmt->execute([$new_hash]);
            echo "✅ Password updated successfully!\n";
        }
    } else {
        echo "❌ Admin user not found!\n";
        echo "💡 Creating admin user...\n";
        
        // Create admin user
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute(['admin', 'admin@campus.edu', $admin_password, 'admin']);
        
        echo "✅ Admin user created!\n";
    }
    
    echo "\n🎯 LOGIN CREDENTIALS:\n";
    echo "URL: http://localhost/BreyerApps/campus-hub/admin/\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure XAMPP MySQL service is running\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🔧 Next: Test the login at admin panel\n";
?>
