<?php
/**
 * Fix Admin User Table Structure - Campus Hub Enhanced
 */

echo "🔧 FIXING ADMIN USER TABLE STRUCTURE\n";
echo "====================================\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=campus_hub_db;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Connected to database\n\n";
    
    // Check current table structure
    echo "📊 Current users table structure:\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
    
    // Check if password column exists
    $has_password = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'password') {
            $has_password = true;
            break;
        }
    }
    
    if (!$has_password) {
        echo "❌ Password column missing! Adding it...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL AFTER email");
        echo "✅ Password column added successfully!\n\n";
    } else {
        echo "✅ Password column exists\n\n";
    }
    
    // Check if admin user exists and has proper data
    $stmt = $pdo->query("SELECT * FROM users WHERE username = 'admin'");
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "📋 Current admin user data:\n";
        foreach ($admin as $key => $value) {
            if ($key === 'password') {
                echo "- $key: " . (empty($value) ? '[EMPTY]' : '[HASHED]') . "\n";
            } else {
                echo "- $key: $value\n";
            }
        }
        echo "\n";
        
        // Update admin password if empty
        if (empty($admin['password'])) {
            echo "🔐 Setting admin password...\n";
            $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
            $stmt->execute([$password_hash]);
            echo "✅ Admin password set successfully!\n";
        }
    } else {
        echo "❌ No admin user found! Creating one...\n";
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute(['admin', 'admin@campus.edu', $password_hash, 'admin']);
        echo "✅ Admin user created successfully!\n";
    }
    
    // Final verification
    echo "\n🔍 Final verification:\n";
    $stmt = $pdo->prepare("SELECT username, email, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Admin user verified:\n";
        echo "  Username: {$admin['username']}\n";
        echo "  Email: {$admin['email']}\n";
        echo "  Role: {$admin['role']}\n";
        
        // Test password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result && password_verify('admin123', $result['password'])) {
            echo "✅ Password verification: SUCCESS\n";
        } else {
            echo "❌ Password verification: FAILED\n";
        }
    }
    
    echo "\n🎯 ADMIN LOGIN READY:\n";
    echo "URL: http://localhost/BreyerApps/campus-hub/admin/\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
