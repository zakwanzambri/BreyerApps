<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include_once '../config/database.php';

class UserAPI {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        
        try {
            switch ($action) {
                case 'get_all':
                    return $this->getAllUsers();
                case 'get_by_id':
                    return $this->getUserById();
                case 'create':
                    return $this->createUser();
                case 'update':
                    return $this->updateUser();
                case 'delete':
                    return $this->deleteUser();
                case 'get_stats':
                    return $this->getUserStats();
                default:
                    return $this->sendResponse(false, 'Invalid action', null, 400);
            }
        } catch (Exception $e) {
            return $this->sendResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function getAllUsers() {
        $query = "SELECT u.*, 
                         CASE 
                             WHEN u.role = 'student' THEN u.student_id
                             WHEN u.role = 'staff' THEN u.employee_id
                             ELSE NULL
                         END as identifier_id
                  FROM users u 
                  ORDER BY u.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert is_active to boolean
        foreach ($users as &$user) {
            $user['is_active'] = (bool)$user['is_active'];
            unset($user['password']); // Don't send passwords
        }
        
        return $this->sendResponse(true, 'Users retrieved successfully', $users);
    }
    
    private function getUserById() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            return $this->sendResponse(false, 'User ID is required', null, 400);
        }
        
        $query = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return $this->sendResponse(false, 'User not found', null, 404);
        }
        
        unset($user['password']); // Don't send password
        $user['is_active'] = (bool)$user['is_active'];
        
        return $this->sendResponse(true, 'User retrieved successfully', $user);
    }
    
    private function createUser() {
        // Get form data
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        $is_active = $_POST['status'] ?? '1';
        
        // Role-specific fields
        $student_id = $_POST['student_id'] ?? null;
        $program_id = $_POST['program_id'] ?? null;
        $employee_id = $_POST['employee_id'] ?? null;
        $department_id = $_POST['department_id'] ?? null;
        
        // Validation
        if (empty($full_name) || empty($email) || empty($username) || empty($password) || empty($role)) {
            return $this->sendResponse(false, 'All required fields must be filled', null, 400);
        }
        
        if (!in_array($role, ['student', 'staff', 'admin'])) {
            return $this->sendResponse(false, 'Invalid role specified', null, 400);
        }
        
        // Check if username or email already exists
        $checkQuery = "SELECT id FROM users WHERE username = ? OR email = ?";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$username, $email]);
        
        if ($checkStmt->fetch()) {
            return $this->sendResponse(false, 'Username or email already exists', null, 400);
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Insert user
            $query = "INSERT INTO users (full_name, email, username, password, role, is_active, 
                                        student_id, program_id, employee_id, department_id, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $full_name,
                $email,
                $username,
                $hashed_password,
                $role,
                $is_active,
                $student_id,
                $program_id,
                $employee_id,
                $department_id
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Generate IDs if not provided
            if ($role === 'student' && empty($student_id)) {
                $student_id = 'STU' . date('Y') . str_pad($userId, 3, '0', STR_PAD_LEFT);
                $updateQuery = "UPDATE users SET student_id = ? WHERE id = ?";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->execute([$student_id, $userId]);
            } elseif ($role === 'staff' && empty($employee_id)) {
                $employee_id = 'EMP' . date('Y') . str_pad($userId, 3, '0', STR_PAD_LEFT);
                $updateQuery = "UPDATE users SET employee_id = ? WHERE id = ?";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->execute([$employee_id, $userId]);
            }
            
            $this->db->commit();
            
            return $this->sendResponse(true, 'User created successfully', ['id' => $userId]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            return $this->sendResponse(false, 'Failed to create user: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function updateUser() {
        $user_id = $_POST['user_id'] ?? null;
        
        if (!$user_id) {
            return $this->sendResponse(false, 'User ID is required', null, 400);
        }
        
        // Get form data
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        $is_active = $_POST['status'] ?? '1';
        
        // Role-specific fields
        $student_id = $_POST['student_id'] ?? null;
        $program_id = $_POST['program_id'] ?? null;
        $employee_id = $_POST['employee_id'] ?? null;
        $department_id = $_POST['department_id'] ?? null;
        
        // Validation
        if (empty($full_name) || empty($email) || empty($username) || empty($role)) {
            return $this->sendResponse(false, 'All required fields must be filled', null, 400);
        }
        
        // Check if username or email already exists for other users
        $checkQuery = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$username, $email, $user_id]);
        
        if ($checkStmt->fetch()) {
            return $this->sendResponse(false, 'Username or email already exists', null, 400);
        }
        
        try {
            // Build update query
            $fields = [
                'full_name = ?',
                'email = ?',
                'username = ?',
                'role = ?',
                'is_active = ?',
                'student_id = ?',
                'program_id = ?',
                'employee_id = ?',
                'department_id = ?',
                'updated_at = NOW()'
            ];
            
            $params = [
                $full_name,
                $email,
                $username,
                $role,
                $is_active,
                $student_id,
                $program_id,
                $employee_id,
                $department_id
            ];
            
            // Add password update if provided
            if (!empty($password)) {
                $fields[] = 'password = ?';
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $params[] = $user_id; // For WHERE clause
            
            $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $this->sendResponse(true, 'User updated successfully');
            
        } catch (Exception $e) {
            return $this->sendResponse(false, 'Failed to update user: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function deleteUser() {
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        
        if (!$id) {
            return $this->sendResponse(false, 'User ID is required', null, 400);
        }
        
        // Check if user exists
        $checkQuery = "SELECT username FROM users WHERE id = ?";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$id]);
        $user = $checkStmt->fetch();
        
        if (!$user) {
            return $this->sendResponse(false, 'User not found', null, 404);
        }
        
        // Prevent deletion of admin user
        if ($user['username'] === 'admin') {
            return $this->sendResponse(false, 'Cannot delete the main admin user', null, 403);
        }
        
        try {
            $query = "DELETE FROM users WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            return $this->sendResponse(true, 'User deleted successfully');
            
        } catch (Exception $e) {
            return $this->sendResponse(false, 'Failed to delete user: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function getUserStats() {
        $query = "SELECT 
                    role,
                    COUNT(*) as count,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
                  FROM users 
                  GROUP BY role";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format stats
        $formatted_stats = [];
        foreach ($stats as $stat) {
            $formatted_stats[$stat['role']] = [
                'total' => (int)$stat['count'],
                'active' => (int)$stat['active_count']
            ];
        }
        
        // Add overall stats
        $totalQuery = "SELECT COUNT(*) as total FROM users";
        $totalStmt = $this->db->prepare($totalQuery);
        $totalStmt->execute();
        $totalResult = $totalStmt->fetch();
        
        $formatted_stats['overall'] = [
            'total' => (int)$totalResult['total']
        ];
        
        return $this->sendResponse(true, 'User statistics retrieved successfully', $formatted_stats);
    }
    
    private function sendResponse($success, $message, $data = null, $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}

// Initialize and handle request
$api = new UserAPI();
$api->handleRequest();
?>
