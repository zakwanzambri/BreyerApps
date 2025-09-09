<?php
/**
 * Direct Login Test - Campus Hub Enhanced
 */

echo "🔐 DIRECT LOGIN VERIFICATION\n";
echo "============================\n\n";

// Include database config directly
require_once 'php/config.php';

try {
    $db = Database::getInstance();
    
    $username = 'admin';
    $password = 'admin123';
    
    echo "📋 Testing login for: $username\n\n";
    
    // Find user
    $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'";
    $user = $db->fetch($sql, [$username, $username]);
    
    if (!$user) {
        echo "❌ User not found or inactive\n";
        exit;
    }
    
    echo "✅ User found:\n";
    echo "  ID: {$user['id']}\n";
    echo "  Username: {$user['username']}\n";
    echo "  Email: {$user['email']}\n";
    echo "  Role: {$user['role']}\n";
    echo "  Status: {$user['status']}\n\n";
    
    // Check password
    $password_valid = false;
    
    if (!empty($user['password'])) {
        $password_valid = password_verify($password, $user['password']);
        echo "🔐 Password verification (password column): " . ($password_valid ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    }
    
    if (!$password_valid && !empty($user['password_hash'])) {
        $password_valid = password_verify($password, $user['password_hash']);
        echo "🔐 Password verification (password_hash column): " . ($password_valid ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    }
    
    if ($password_valid) {
        echo "\n🎉 LOGIN WOULD SUCCEED!\n";
        echo "✅ All authentication checks passed\n";
        echo "✅ User has admin role: " . ($user['role'] === 'admin' ? "YES" : "NO") . "\n";
        echo "✅ Account is active: " . ($user['status'] === 'active' ? "YES" : "NO") . "\n";
    } else {
        echo "\n❌ LOGIN WOULD FAIL!\n";
        echo "Password verification failed\n";
    }
    
    echo "\n🌐 Test in browser now:\n";
    echo "URL: http://localhost/BreyerApps/campus-hub/admin/login.html\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
