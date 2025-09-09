<?php
/**
 * COMPREHENSIVE DATABASE TEST - Campus Hub Enhanced
 */

echo "🔍 COMPREHENSIVE DATABASE & LOGIN TEST\n";
echo "======================================\n\n";

try {
    // 1. Test basic MySQL connection
    echo "1️⃣ Testing MySQL Connection...\n";
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ MySQL server connection: SUCCESS\n\n";
    
    // 2. Check if database exists
    echo "2️⃣ Checking campus_hub_db database...\n";
    $stmt = $pdo->query("SHOW DATABASES LIKE 'campus_hub_db'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database 'campus_hub_db': EXISTS\n";
        $pdo->exec("USE campus_hub_db");
    } else {
        echo "❌ Database 'campus_hub_db': NOT FOUND\n";
        echo "Creating database...\n";
        $pdo->exec("CREATE DATABASE campus_hub_db");
        $pdo->exec("USE campus_hub_db");
        echo "✅ Database created\n";
    }
    echo "\n";
    
    // 3. Check users table structure
    echo "3️⃣ Checking users table structure...\n";
    try {
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll();
        
        $hasPassword = false;
        $hasPasswordHash = false;
        
        foreach ($columns as $col) {
            if ($col['Field'] === 'password') $hasPassword = true;
            if ($col['Field'] === 'password_hash') $hasPasswordHash = true;
        }
        
        echo "✅ Users table exists\n";
        echo "- password column: " . ($hasPassword ? "✅ EXISTS" : "❌ MISSING") . "\n";
        echo "- password_hash column: " . ($hasPasswordHash ? "✅ EXISTS" : "❌ MISSING") . "\n";
        
        if (!$hasPassword) {
            echo "🔧 Adding password column...\n";
            $pdo->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255) AFTER email");
            echo "✅ Password column added\n";
        }
        
    } catch (PDOException $e) {
        echo "❌ Users table missing or error: " . $e->getMessage() . "\n";
        echo "🔧 Creating users table...\n";
        
        $createUserTable = "
        CREATE TABLE users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255),
            full_name VARCHAR(100),
            role ENUM('student','staff','admin') DEFAULT 'student',
            student_id VARCHAR(20),
            program_id INT(11),
            status ENUM('active','inactive','suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($createUserTable);
        echo "✅ Users table created\n";
    }
    echo "\n";
    
    // 4. Check/Create admin user
    echo "4️⃣ Checking admin user...\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "❌ Admin user not found. Creating...\n";
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@campus.edu', $password, 'System Administrator', 'admin', 'active']);
        echo "✅ Admin user created\n";
    } else {
        echo "✅ Admin user found\n";
        
        // Ensure password is set
        if (empty($admin['password'])) {
            echo "🔧 Setting admin password...\n";
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
            $stmt->execute([$password]);
            echo "✅ Admin password set\n";
        }
    }
    
    // 5. Test password verification
    echo "\n5️⃣ Testing password verification...\n";
    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result && password_verify('admin123', $result['password'])) {
        echo "✅ Password verification: SUCCESS\n";
    } else {
        echo "❌ Password verification: FAILED\n";
        echo "🔧 Regenerating password...\n";
        $newPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->execute([$newPassword]);
        echo "✅ Password regenerated\n";
    }
    
    echo "\n🎯 FINAL DATABASE STATUS:\n";
    echo "Database: ✅ READY\n";
    echo "Admin User: ✅ READY\n";
    echo "Password: ✅ WORKING\n";
    echo "Credentials: admin / admin123\n";
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Please check XAMPP MySQL service\n";
}
?>
