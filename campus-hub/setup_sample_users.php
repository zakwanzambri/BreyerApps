<?php
/**
 * Setup Sample Users - Campus Hub Enhanced
 * Create students and staff users for testing
 */

echo "👥 SETTING UP SAMPLE USERS\n";
echo "==========================\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=campus_hub_db;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Database connected\n\n";
    
    // Sample users data
    $users = [
        // Students
        [
            'username' => 'student1',
            'email' => 'ahmad.ibrahim@student.campus.edu',
            'password' => 'student123',
            'full_name' => 'Ahmad Ibrahim',
            'role' => 'student',
            'student_id' => 'STU2025001',
            'program_id' => 1
        ],
        [
            'username' => 'student2',
            'email' => 'siti.aminah@student.campus.edu',
            'password' => 'student123',
            'full_name' => 'Siti Aminah',
            'role' => 'student',
            'student_id' => 'STU2025002',
            'program_id' => 2
        ],
        [
            'username' => 'student3',
            'email' => 'muthu.krishnan@student.campus.edu',
            'password' => 'student123',
            'full_name' => 'Muthu Krishnan',
            'role' => 'student',
            'student_id' => 'STU2025003',
            'program_id' => 3
        ],
        [
            'username' => 'student4',
            'email' => 'lim.wei.ming@student.campus.edu',
            'password' => 'student123',
            'full_name' => 'Lim Wei Ming',
            'role' => 'student',
            'student_id' => 'STU2025004',
            'program_id' => 4
        ],
        [
            'username' => 'student5',
            'email' => 'nurul.huda@student.campus.edu',
            'password' => 'student123',
            'full_name' => 'Nurul Huda',
            'role' => 'student',
            'student_id' => 'STU2025005',
            'program_id' => 5
        ],
        
        // Staff members
        [
            'username' => 'lecturer1',
            'email' => 'dr.hassan@staff.campus.edu',
            'password' => 'staff123',
            'full_name' => 'Dr. Hassan Abdullah',
            'role' => 'staff',
            'student_id' => null,
            'program_id' => null
        ],
        [
            'username' => 'lecturer2',
            'email' => 'prof.lim@staff.campus.edu',
            'password' => 'staff123',
            'full_name' => 'Prof. Lim Soo Hoon',
            'role' => 'staff',
            'student_id' => null,
            'program_id' => null
        ],
        [
            'username' => 'counselor1',
            'email' => 'ms.farah@staff.campus.edu',
            'password' => 'staff123',
            'full_name' => 'Ms. Farah Zainal',
            'role' => 'staff',
            'student_id' => null,
            'program_id' => null
        ]
    ];
    
    // Insert users
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, full_name, role, student_id, program_id, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ON DUPLICATE KEY UPDATE 
        password = VALUES(password),
        full_name = VALUES(full_name),
        updated_at = NOW()
    ");
    
    $created = 0;
    $updated = 0;
    
    foreach ($users as $user) {
        // Check if user exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$user['username'], $user['email']]);
        $exists = $checkStmt->fetch();
        
        // Hash password
        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
        
        // Insert/Update user
        $stmt->execute([
            $user['username'],
            $user['email'], 
            $hashedPassword,
            $user['full_name'],
            $user['role'],
            $user['student_id'],
            $user['program_id']
        ]);
        
        if ($exists) {
            $updated++;
            echo "🔄 Updated: {$user['full_name']} ({$user['role']})\n";
        } else {
            $created++;
            echo "✅ Created: {$user['full_name']} ({$user['role']})\n";
        }
    }
    
    echo "\n📊 Summary:\n";
    echo "✅ Created: $created new users\n";
    echo "🔄 Updated: $updated existing users\n";
    
    // Show user statistics
    $stats = [
        'students' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
        'staff' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn(),
        'admin' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
        'total' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()
    ];
    
    echo "\n👥 User Statistics:\n";
    echo "📚 Students: {$stats['students']}\n";
    echo "👨‍🏫 Staff: {$stats['staff']}\n"; 
    echo "👨‍💼 Admin: {$stats['admin']}\n";
    echo "📊 Total: {$stats['total']}\n";
    
    echo "\n🔐 Sample Login Credentials:\n";
    echo "Students: student1/student123, student2/student123, etc.\n";
    echo "Staff: lecturer1/staff123, lecturer2/staff123, etc.\n";
    echo "Admin: admin/admin123\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
