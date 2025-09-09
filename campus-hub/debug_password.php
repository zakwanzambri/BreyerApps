<?php
/**
 * Debug Password Issue - Campus Hub Enhanced
 */

echo "🔍 DEBUGGING PASSWORD ISSUE\n";
echo "===========================\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=campus_hub_db;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Database connected\n\n";
    
    // Get admin user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "❌ Admin user not found!\n";
        exit;
    }
    
    echo "📋 Admin User Data:\n";
    echo "ID: {$admin['id']}\n";
    echo "Username: {$admin['username']}\n";
    echo "Email: {$admin['email']}\n";
    echo "Role: {$admin['role']}\n";
    echo "Status: {$admin['status']}\n";
    
    // Check both password columns
    echo "\n🔐 Password Analysis:\n";
    if (isset($admin['password'])) {
        if (empty($admin['password'])) {
            echo "❌ 'password' column: EMPTY\n";
        } else {
            echo "✅ 'password' column: " . substr($admin['password'], 0, 30) . "...\n";
        }
    }
    
    if (isset($admin['password_hash'])) {
        if (empty($admin['password_hash'])) {
            echo "❌ 'password_hash' column: EMPTY\n";
        } else {
            echo "✅ 'password_hash' column: " . substr($admin['password_hash'], 0, 30) . "...\n";
        }
    }
    
    // Test password verification with both columns
    $test_password = 'admin123';
    echo "\n🧪 Testing password 'admin123':\n";
    
    if (!empty($admin['password'])) {
        $verify1 = password_verify($test_password, $admin['password']);
        echo "password_verify with 'password' column: " . ($verify1 ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    }
    
    if (!empty($admin['password_hash'])) {
        $verify2 = password_verify($test_password, $admin['password_hash']);
        echo "password_verify with 'password_hash' column: " . ($verify2 ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    }
    
    // Generate fresh password hash
    echo "\n🔄 Generating fresh password hash:\n";
    $fresh_hash = password_hash('admin123', PASSWORD_DEFAULT);
    echo "New hash: " . substr($fresh_hash, 0, 30) . "...\n";
    
    $verify_fresh = password_verify('admin123', $fresh_hash);
    echo "Fresh hash verification: " . ($verify_fresh ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    
    // Update both password columns with fresh hash
    echo "\n🔧 Updating admin password...\n";
    $stmt = $pdo->prepare("UPDATE users SET password = ?, password_hash = ? WHERE username = 'admin'");
    $result = $stmt->execute([$fresh_hash, $fresh_hash]);
    
    if ($result) {
        echo "✅ Password updated successfully!\n";
        
        // Verify the update
        $stmt = $pdo->prepare("SELECT password, password_hash FROM users WHERE username = 'admin'");
        $stmt->execute();
        $updated = $stmt->fetch();
        
        echo "\n✅ Verification after update:\n";
        $verify_updated = password_verify('admin123', $updated['password']);
        echo "Updated password verification: " . ($verify_updated ? "✅ SUCCESS" : "❌ FAILED") . "\n";
        
    } else {
        echo "❌ Failed to update password\n";
    }
    
    echo "\n🎯 FINAL TEST CREDENTIALS:\n";
    echo "URL: http://localhost/BreyerApps/campus-hub/admin/login.html\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "\n💡 Try logging in now!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
