<?php
/**
 * Add Ahmad Syafiq to Campus Hub Database
 */

echo "➕ ADDING AHMAD SYAFIQ TO DATABASE\n";
echo "==================================\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=campus_hub_db', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Add Ahmad Syafiq as a new student
    $username = 'ahmad.syafiq';
    $email = 'ahmad.syafiq@campus.edu';
    $password = password_hash('syafiq123', PASSWORD_DEFAULT);
    $full_name = 'Ahmad Syafiq';
    $role = 'student';
    $student_id = 'S2025001';
    $program_id = 1; // Diploma IT
    $status = 'active';
    
    echo "📋 Creating user account for Ahmad Syafiq...\n";
    echo "Username: $username\n";
    echo "Email: $email\n";
    echo "Password: syafiq123\n";
    echo "Role: $role\n";
    echo "Student ID: $student_id\n";
    echo "Program: Diploma IT\n\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, full_name, role, student_id, program_id, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $username, $email, $password, $full_name, $role, $student_id, $program_id, $status
    ]);
    
    if ($result) {
        $user_id = $pdo->lastInsertId();
        echo "✅ Ahmad Syafiq successfully added to database!\n";
        echo "User ID: $user_id\n\n";
        
        // Verify the user was added
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        echo "✅ Verification - User Details:\n";
        echo "  ID: {$user['id']}\n";
        echo "  Username: {$user['username']}\n";
        echo "  Full Name: {$user['full_name']}\n";
        echo "  Email: {$user['email']}\n";
        echo "  Role: {$user['role']}\n";
        echo "  Student ID: {$user['student_id']}\n";
        echo "  Program ID: {$user['program_id']}\n";
        echo "  Status: {$user['status']}\n";
        echo "  Created: {$user['created_at']}\n\n";
        
        echo "🔐 Login Credentials for Ahmad Syafiq:\n";
        echo "URL: http://localhost/BreyerApps/campus-hub/user-login.html\n";
        echo "Username: ahmad.syafiq\n";
        echo "Password: syafiq123\n";
        echo "Role: Student\n";
        echo "Will redirect to: Student Dashboard\n";
        
    } else {
        echo "❌ Failed to add Ahmad Syafiq to database\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
