<?php
/**
 * Authentication API
 * Campus Hub Portal - Enhanced Version
 */

require_once '../config.php';

class AuthAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        switch ($method) {
            case 'POST':
                $this->handlePost($action);
                break;
            case 'GET':
                $this->handleGet($action);
                break;
            case 'DELETE':
                $this->handleDelete($action);
                break;
            default:
                ResponseHelper::error('Method not allowed', 405);
        }
    }
    
    private function handlePost($action) {
        switch ($action) {
            case 'login':
                $this->login();
                break;
            case 'register':
                $this->register();
                break;
            case 'change-password':
                $this->changePassword();
                break;
            default:
                ResponseHelper::error('Invalid action');
        }
    }
    
    private function handleGet($action) {
        switch ($action) {
            case 'me':
                $this->getCurrentUser();
                break;
            case 'check':
                $this->checkAuth();
                break;
            default:
                ResponseHelper::error('Invalid action');
        }
    }
    
    private function handleDelete($action) {
        switch ($action) {
            case 'logout':
                $this->logout();
                break;
            default:
                ResponseHelper::error('Invalid action');
        }
    }
    
    private function login() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $username = Validator::sanitize($input['username'] ?? '');
        $password = $input['password'] ?? '';
        
        if (!Validator::validateRequired($username) || !Validator::validateRequired($password)) {
            ResponseHelper::error('Username and password are required');
        }
        
        // Find user by username or email
        $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'";
        $user = $this->db->fetch($sql, [$username, $username]);
        
        if (!$user || !Utils::verifyPassword($password, $user['password_hash'])) {
            ResponseHelper::error('Invalid credentials', 401);
        }
        
        // Create session
        SessionManager::set('user_id', $user['id']);
        SessionManager::set('user_role', $user['role']);
        SessionManager::set('login_time', time());
        
        // Generate session token for API access
        $token = Utils::generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $this->db->execute(
            "INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)",
            [$user['id'], $token, $expiresAt]
        );
        
        // Clean up old sessions
        $this->db->execute(
            "DELETE FROM user_sessions WHERE user_id = ? AND expires_at < NOW()",
            [$user['id']]
        );
        
        // Prepare response data
        $userData = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'student_id' => $user['student_id'],
            'program_id' => $user['program_id']
        ];
        
        // Get program name if applicable
        if ($user['program_id']) {
            $program = $this->db->fetch(
                "SELECT program_name FROM programs WHERE id = ?",
                [$user['program_id']]
            );
            $userData['program_name'] = $program['program_name'] ?? null;
        }
        
        ResponseHelper::success([
            'user' => $userData,
            'token' => $token,
            'expires_at' => $expiresAt
        ], 'Login successful');
    }
    
    private function register() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['username', 'email', 'password', 'full_name'];
        foreach ($required as $field) {
            if (!Validator::validateRequired($input[$field] ?? '')) {
                ResponseHelper::error("Field '{$field}' is required");
            }
        }
        
        $data = Validator::sanitize($input);
        
        // Validate email format
        if (!Validator::validateEmail($data['email'])) {
            ResponseHelper::error('Invalid email format');
        }
        
        // Validate password strength
        if (!Validator::validatePassword($input['password'])) {
            ResponseHelper::error('Password must be at least 6 characters with letters and numbers');
        }
        
        // Check if username or email already exists
        $existing = $this->db->fetch(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$data['username'], $data['email']]
        );
        
        if ($existing) {
            ResponseHelper::error('Username or email already exists');
        }
        
        // Validate program_id if provided
        if (!empty($data['program_id'])) {
            $program = $this->db->fetch(
                "SELECT id FROM programs WHERE id = ? AND status = 'active'",
                [$data['program_id']]
            );
            if (!$program) {
                ResponseHelper::error('Invalid program selected');
            }
        }
        
        $passwordHash = Utils::hashPassword($input['password']);
        
        $sql = "INSERT INTO users (username, email, password_hash, full_name, role, student_id, program_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['username'],
            $data['email'],
            $passwordHash,
            $data['full_name'],
            $data['role'] ?? 'student',
            $data['student_id'] ?? null,
            $data['program_id'] ?? null
        ];
        
        try {
            $this->db->execute($sql, $params);
            $userId = $this->db->lastInsertId();
            
            ResponseHelper::success([
                'user_id' => $userId,
                'message' => 'Registration successful. Please login to continue.'
            ], 'User registered successfully');
        } catch (Exception $e) {
            ResponseHelper::error('Registration failed. Please try again.');
        }
    }
    
    private function getCurrentUser() {
        SessionManager::requireLogin();
        
        $user = SessionManager::getCurrentUser();
        
        if (!$user) {
            ResponseHelper::unauthorized('User not found');
        }
        
        // Get program information if applicable
        if ($user['program_id']) {
            $program = $this->db->fetch(
                "SELECT program_name, program_code FROM programs WHERE id = ?",
                [$user['program_id']]
            );
            $user['program_name'] = $program['program_name'] ?? null;
            $user['program_code'] = $program['program_code'] ?? null;
        }
        
        ResponseHelper::success($user);
    }
    
    private function checkAuth() {
        $isLoggedIn = SessionManager::isLoggedIn();
        $user = $isLoggedIn ? SessionManager::getCurrentUser() : null;
        
        ResponseHelper::success([
            'authenticated' => $isLoggedIn,
            'user' => $user
        ]);
    }
    
    private function logout() {
        $userId = SessionManager::get('user_id');
        
        if ($userId) {
            // Remove all sessions for this user
            $this->db->execute(
                "DELETE FROM user_sessions WHERE user_id = ?",
                [$userId]
            );
        }
        
        SessionManager::destroy();
        
        ResponseHelper::success(null, 'Logout successful');
    }
    
    private function changePassword() {
        SessionManager::requireLogin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';
        
        if (!Validator::validateRequired($currentPassword) || 
            !Validator::validateRequired($newPassword) || 
            !Validator::validateRequired($confirmPassword)) {
            ResponseHelper::error('All password fields are required');
        }
        
        if ($newPassword !== $confirmPassword) {
            ResponseHelper::error('New passwords do not match');
        }
        
        if (!Validator::validatePassword($newPassword)) {
            ResponseHelper::error('New password must be at least 6 characters with letters and numbers');
        }
        
        $currentUser = SessionManager::getCurrentUser();
        
        // Verify current password
        $user = $this->db->fetch(
            "SELECT password_hash FROM users WHERE id = ?",
            [$currentUser['id']]
        );
        
        if (!Utils::verifyPassword($currentPassword, $user['password_hash'])) {
            ResponseHelper::error('Current password is incorrect');
        }
        
        $newPasswordHash = Utils::hashPassword($newPassword);
        
        try {
            $this->db->execute(
                "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
                [$newPasswordHash, $currentUser['id']]
            );
            
            ResponseHelper::success(null, 'Password changed successfully');
        } catch (Exception $e) {
            ResponseHelper::error('Failed to change password');
        }
    }
}

// Handle the request
try {
    $api = new AuthAPI();
    $api->handleRequest();
} catch (Exception $e) {
    ResponseHelper::error('API Error: ' . $e->getMessage(), 500);
}

?>
