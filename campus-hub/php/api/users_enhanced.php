<?php
/**
 * Users API Endpoints
 * Campus Hub Portal - Enhanced Version
 */

require_once 'config.php';

class UsersAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet($action);
                    break;
                case 'POST':
                    $this->handlePost($action);
                    break;
                case 'PUT':
                    $this->handlePut($action);
                    break;
                case 'DELETE':
                    $this->handleDelete($action);
                    break;
                default:
                    ResponseHelper::error('Method not allowed', 405);
            }
        } catch (Exception $e) {
            ErrorLogger::logException($e, "Users API - $method $action");
            ResponseHelper::error('Internal server error', 500);
        }
    }
    
    private function handleGet($action) {
        switch ($action) {
            case 'list':
                $this->getUsersList();
                break;
            case 'profile':
                $this->getUserProfile();
                break;
            case 'search':
                $this->searchUsers();
                break;
            case 'stats':
                $this->getUserStats();
                break;
            case 'by-role':
                $this->getUsersByRole();
                break;
            case 'activity':
                $this->getUserActivity();
                break;
            default:
                $this->getUsersList();
        }
    }
    
    private function handlePost($action) {
        switch ($action) {
            case 'create':
                SessionManager::requireRole(['admin']);
                $this->createUser();
                break;
            case 'upload-avatar':
                SessionManager::requireAuth();
                $this->uploadAvatar();
                break;
            case 'change-password':
                SessionManager::requireAuth();
                $this->changePassword();
                break;
            default:
                ResponseHelper::error('Invalid action');
        }
    }
    
    private function handlePut($action) {
        SessionManager::requireAuth();
        
        switch ($action) {
            case 'update':
                $this->updateUser();
                break;
            case 'update-role':
                SessionManager::requireRole(['admin']);
                $this->updateUserRole();
                break;
            case 'activate':
                SessionManager::requireRole(['admin']);
                $this->activateUser();
                break;
            case 'deactivate':
                SessionManager::requireRole(['admin']);
                $this->deactivateUser();
                break;
            default:
                ResponseHelper::error('Invalid action');
        }
    }
    
    private function handleDelete($action) {
        SessionManager::requireRole(['admin']);
        
        switch ($action) {
            case 'delete':
                $this->deleteUser();
                break;
            default:
                ResponseHelper::error('Invalid action');
        }
    }
    
    private function getUsersList() {
        SessionManager::requireRole(['admin', 'staff']);
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $role = $_GET['role'] ?? null;
        $status = $_GET['status'] ?? 'active';
        $searchTerm = $_GET['search'] ?? '';
        
        $whereConditions = ["u.status = ?"];
        $params = [$status];
        
        if ($role) {
            $whereConditions[] = "u.role = ?";
            $params[] = $role;
        }
        
        if ($searchTerm) {
            $searchFields = ['u.name', 'u.username', 'u.email', 'u.student_id'];
            list($searchWhere, $searchParams) = SearchHelper::buildWhereClause($searchTerm, $searchFields);
            if ($searchWhere) {
                $whereConditions[] = $searchWhere;
                $params = array_merge($params, $searchParams);
            }
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM users u WHERE $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get users
        $sql = "
            SELECT 
                u.id,
                u.username,
                u.name,
                u.email,
                u.role,
                u.student_id,
                u.program_id,
                u.year_of_study,
                u.avatar_url,
                u.status,
                u.last_login,
                u.created_at,
                u.updated_at
            FROM users u
            WHERE $whereClause
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        // Process results
        foreach ($users as &$user) {
            $user['created_ago'] = timeAgo($user['created_at']);
            $user['last_login_ago'] = $user['last_login'] ? timeAgo($user['last_login']) : 'Never';
            
            // Remove sensitive information
            unset($user['password']);
            
            if ($searchTerm) {
                $user['name'] = SearchHelper::highlightResults($user['name'], $searchTerm);
                $user['username'] = SearchHelper::highlightResults($user['username'], $searchTerm);
                $user['email'] = SearchHelper::highlightResults($user['email'], $searchTerm);
            }
        }
        
        ResponseHelper::paginated($users, $total, $page, $limit, 'Users retrieved successfully');
    }
    
    private function getUserProfile() {
        $userId = (int)($_GET['id'] ?? 0);
        $currentUser = SessionManager::getCurrentUser();
        
        // If no ID provided, return current user's profile
        if (!$userId) {
            SessionManager::requireAuth();
            $userId = $currentUser['id'];
        }
        
        // Check permissions
        if ($userId != $currentUser['id'] && !in_array($currentUser['role'], ['admin', 'staff'])) {
            ResponseHelper::error('Insufficient permissions', 403);
        }
        
        $sql = "
            SELECT 
                u.id,
                u.username,
                u.name,
                u.email,
                u.role,
                u.student_id,
                u.program_id,
                u.year_of_study,
                u.avatar_url,
                u.status,
                u.last_login,
                u.created_at,
                u.updated_at,
                u.phone,
                u.address,
                u.date_of_birth,
                u.emergency_contact
            FROM users u
            WHERE u.id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            ResponseHelper::error('User not found', 404);
        }
        
        $user['created_ago'] = timeAgo($user['created_at']);
        $user['last_login_ago'] = $user['last_login'] ? timeAgo($user['last_login']) : 'Never';
        
        // Get user statistics if viewing own profile or admin
        if ($userId == $currentUser['id'] || $currentUser['role'] === 'admin') {
            // Get event registrations count
            $eventsSql = "SELECT COUNT(*) as count FROM event_registrations WHERE user_id = ? AND status = 'registered'";
            $eventsStmt = $this->db->prepare($eventsSql);
            $eventsStmt->execute([$userId]);
            $user['events_registered'] = $eventsStmt->fetch()['count'];
            
            // Get news authored (if staff/admin)
            if (in_array($user['role'], ['admin', 'staff'])) {
                $newsSql = "SELECT COUNT(*) as count FROM news WHERE author_id = ?";
                $newsStmt = $this->db->prepare($newsSql);
                $newsStmt->execute([$userId]);
                $user['news_authored'] = $newsStmt->fetch()['count'];
            }
        }
        
        // Remove password field
        unset($user['password']);
        
        ResponseHelper::success($user, 'User profile retrieved successfully');
    }
    
    private function searchUsers() {
        SessionManager::requireRole(['admin', 'staff']);
        
        $searchTerm = $_GET['q'] ?? '';
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        
        if (!$searchTerm) {
            ResponseHelper::error('Search term is required');
        }
        
        $searchFields = ['u.name', 'u.username', 'u.email', 'u.student_id'];
        list($searchWhere, $searchParams) = SearchHelper::buildWhereClause($searchTerm, $searchFields);
        
        $sql = "
            SELECT 
                u.id,
                u.username,
                u.name,
                u.email,
                u.role,
                u.student_id,
                u.avatar_url,
                u.status
            FROM users u
            WHERE u.status = 'active' AND $searchWhere
            ORDER BY u.name ASC
            LIMIT ?
        ";
        
        $params = array_merge(['active'], $searchParams, [$limit]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        foreach ($users as &$user) {
            $user['name'] = SearchHelper::highlightResults($user['name'], $searchTerm);
            $user['username'] = SearchHelper::highlightResults($user['username'], $searchTerm);
            $user['email'] = SearchHelper::highlightResults($user['email'], $searchTerm);
        }
        
        ResponseHelper::success($users, "Search results for '$searchTerm'");
    }
    
    private function getUserStats() {
        SessionManager::requireRole(['admin']);
        
        $sql = "
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff_count,
                SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as student_count,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
                SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as active_last_30_days
            FROM users
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats = $stmt->fetch();
        
        // Get registration activity
        $activitySql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as registrations
            FROM users
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ";
        
        $activityStmt = $this->db->prepare($activitySql);
        $activityStmt->execute();
        $stats['registration_activity'] = $activityStmt->fetchAll();
        
        // Get program distribution (for students)
        $programSql = "
            SELECT 
                program_id,
                COUNT(*) as count
            FROM users 
            WHERE role = 'student' AND program_id IS NOT NULL
            GROUP BY program_id
            ORDER BY count DESC
        ";
        
        $programStmt = $this->db->prepare($programSql);
        $programStmt->execute();
        $stats['program_distribution'] = $programStmt->fetchAll();
        
        ResponseHelper::success($stats, 'User statistics retrieved successfully');
    }
    
    private function getUsersByRole() {
        SessionManager::requireRole(['admin', 'staff']);
        
        $role = $_GET['role'] ?? '';
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
        
        if (!$role) {
            ResponseHelper::error('Role is required');
        }
        
        $sql = "
            SELECT 
                u.id,
                u.username,
                u.name,
                u.email,
                u.student_id,
                u.avatar_url,
                u.status,
                u.last_login
            FROM users u
            WHERE u.role = ? AND u.status = 'active'
            ORDER BY u.name ASC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$role, $limit]);
        $users = $stmt->fetchAll();
        
        foreach ($users as &$user) {
            $user['last_login_ago'] = $user['last_login'] ? timeAgo($user['last_login']) : 'Never';
        }
        
        ResponseHelper::success($users, "Users with role '$role' retrieved successfully");
    }
    
    private function getUserActivity() {
        $userId = (int)($_GET['id'] ?? 0);
        $currentUser = SessionManager::getCurrentUser();
        
        // If no ID provided, use current user
        if (!$userId) {
            SessionManager::requireAuth();
            $userId = $currentUser['id'];
        }
        
        // Check permissions
        if ($userId != $currentUser['id'] && $currentUser['role'] !== 'admin') {
            ResponseHelper::error('Insufficient permissions', 403);
        }
        
        $activities = [];
        
        // Get recent event registrations
        $eventsSql = "
            SELECT 
                'event_registration' as type,
                e.title as title,
                er.registered_at as created_at,
                e.id as reference_id
            FROM event_registrations er
            JOIN events e ON er.event_id = e.id
            WHERE er.user_id = ?
            ORDER BY er.registered_at DESC
            LIMIT 10
        ";
        
        $eventsStmt = $this->db->prepare($eventsSql);
        $eventsStmt->execute([$userId]);
        $activities = array_merge($activities, $eventsStmt->fetchAll());
        
        // Get authored news (if staff/admin)
        $user = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $user->execute([$userId]);
        $userRole = $user->fetch()['role'];
        
        if (in_array($userRole, ['admin', 'staff'])) {
            $newsSql = "
                SELECT 
                    'news_authored' as type,
                    title,
                    created_at,
                    id as reference_id
                FROM news
                WHERE author_id = ?
                ORDER BY created_at DESC
                LIMIT 10
            ";
            
            $newsStmt = $this->db->prepare($newsSql);
            $newsStmt->execute([$userId]);
            $activities = array_merge($activities, $newsStmt->fetchAll());
        }
        
        // Sort all activities by date
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Limit to 20 most recent
        $activities = array_slice($activities, 0, 20);
        
        foreach ($activities as &$activity) {
            $activity['created_ago'] = timeAgo($activity['created_at']);
        }
        
        ResponseHelper::success($activities, 'User activity retrieved successfully');
    }
    
    private function createUser() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $rules = [
            'username' => ['required' => true, 'min_length' => 3, 'max_length' => 50],
            'name' => ['required' => true, 'max_length' => 100],
            'email' => ['required' => true, 'type' => 'email'],
            'password' => ['required' => true, 'min_length' => 6],
            'role' => ['required' => true, 'custom' => function($value) {
                return in_array($value, ['admin', 'staff', 'student']) ? true : 'Invalid role';
            }]
        ];
        
        $errors = InputValidator::validate($input, $rules);
        if (!empty($errors)) {
            ResponseHelper::error('Validation failed', 400, $errors);
        }
        
        $data = InputValidator::sanitize($input);
        
        // Check if username or email already exists
        $checkSql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([$data['username'], $data['email']]);
        
        if ($checkStmt->fetch()) {
            ResponseHelper::error('Username or email already exists');
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $sql = "
            INSERT INTO users (
                username, name, email, password, role, student_id,
                program_id, year_of_study, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
        ";
        
        $params = [
            $data['username'],
            $data['name'],
            $data['email'],
            $hashedPassword,
            $data['role'],
            $data['student_id'] ?? null,
            $data['program_id'] ?? null,
            $data['year_of_study'] ?? null
        ];
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            $userId = $this->db->lastInsertId();
            
            // Log activity
            $currentUser = SessionManager::getCurrentUser();
            ErrorLogger::log("User created: ID $userId by admin {$currentUser['id']}", 'INFO');
            
            ResponseHelper::success(['id' => $userId], 'User created successfully', 201);
        } else {
            ErrorLogger::log("Failed to create user by admin {$currentUser['id']}");
            ResponseHelper::error('Failed to create user', 500);
        }
    }
    
    private function updateUser() {
        $userId = (int)($_GET['id'] ?? 0);
        $currentUser = SessionManager::getCurrentUser();
        
        // If no ID provided, update current user
        if (!$userId) {
            $userId = $currentUser['id'];
        }
        
        // Check permissions
        if ($userId != $currentUser['id'] && $currentUser['role'] !== 'admin') {
            ResponseHelper::error('Insufficient permissions', 403);
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $rules = [
            'name' => ['max_length' => 100],
            'email' => ['type' => 'email'],
            'phone' => ['max_length' => 20],
            'address' => ['max_length' => 200],
            'date_of_birth' => ['type' => 'date']
        ];
        
        $errors = InputValidator::validate($input, $rules);
        if (!empty($errors)) {
            ResponseHelper::error('Validation failed', 400, $errors);
        }
        
        $data = InputValidator::sanitize($input);
        
        // Check if email already exists (for other users)
        if (isset($data['email'])) {
            $checkSql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$data['email'], $userId]);
            
            if ($checkStmt->fetch()) {
                ResponseHelper::error('Email already exists');
            }
        }
        
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['name', 'email', 'phone', 'address', 'date_of_birth', 'emergency_contact'];
        if ($currentUser['role'] === 'admin') {
            $allowedFields[] = 'student_id';
            $allowedFields[] = 'program_id';
            $allowedFields[] = 'year_of_study';
        }
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            ResponseHelper::error('No fields to update');
        }
        
        $updateFields[] = "updated_at = NOW()";
        $params[] = $userId;
        
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            // Log activity
            ErrorLogger::log("User updated: ID $userId by user {$currentUser['id']}", 'INFO');
            
            ResponseHelper::success(null, 'User updated successfully');
        } else {
            ErrorLogger::log("Failed to update user ID $userId by user {$currentUser['id']}");
            ResponseHelper::error('Failed to update user', 500);
        }
    }
    
    private function changePassword() {
        $userId = (int)($_GET['id'] ?? 0);
        $currentUser = SessionManager::getCurrentUser();
        
        // If no ID provided, change current user's password
        if (!$userId) {
            $userId = $currentUser['id'];
        }
        
        // Check permissions
        if ($userId != $currentUser['id'] && $currentUser['role'] !== 'admin') {
            ResponseHelper::error('Insufficient permissions', 403);
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $rules = [
            'new_password' => ['required' => true, 'min_length' => 6]
        ];
        
        // If not admin changing another user's password, require current password
        if ($userId == $currentUser['id'] || $currentUser['role'] !== 'admin') {
            $rules['current_password'] = ['required' => true];
        }
        
        $errors = InputValidator::validate($input, $rules);
        if (!empty($errors)) {
            ResponseHelper::error('Validation failed', 400, $errors);
        }
        
        // Verify current password if required
        if (isset($input['current_password'])) {
            $checkSql = "SELECT password FROM users WHERE id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$userId]);
            $user = $checkStmt->fetch();
            
            if (!$user || !password_verify($input['current_password'], $user['password'])) {
                ResponseHelper::error('Current password is incorrect');
            }
        }
        
        $hashedPassword = password_hash($input['new_password'], PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$hashedPassword, $userId])) {
            // Log activity
            ErrorLogger::log("Password changed for user ID $userId by user {$currentUser['id']}", 'INFO');
            
            ResponseHelper::success(null, 'Password changed successfully');
        } else {
            ErrorLogger::log("Failed to change password for user ID $userId");
            ResponseHelper::error('Failed to change password', 500);
        }
    }
    
    private function updateUserRole() {
        $userId = (int)($_GET['id'] ?? 0);
        if (!$userId) {
            ResponseHelper::error('User ID is required');
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        if (!isset($input['role']) || !in_array($input['role'], ['admin', 'staff', 'student'])) {
            ResponseHelper::error('Valid role is required');
        }
        
        $sql = "UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$input['role'], $userId])) {
            $currentUser = SessionManager::getCurrentUser();
            ErrorLogger::log("User role updated: ID $userId to {$input['role']} by admin {$currentUser['id']}", 'INFO');
            
            ResponseHelper::success(null, 'User role updated successfully');
        } else {
            ResponseHelper::error('Failed to update user role', 500);
        }
    }
    
    private function activateUser() {
        $userId = (int)($_GET['id'] ?? 0);
        if (!$userId) {
            ResponseHelper::error('User ID is required');
        }
        
        $sql = "UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$userId])) {
            ResponseHelper::success(null, 'User activated successfully');
        } else {
            ResponseHelper::error('Failed to activate user', 500);
        }
    }
    
    private function deactivateUser() {
        $userId = (int)($_GET['id'] ?? 0);
        if (!$userId) {
            ResponseHelper::error('User ID is required');
        }
        
        $sql = "UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$userId])) {
            ResponseHelper::success(null, 'User deactivated successfully');
        } else {
            ResponseHelper::error('Failed to deactivate user', 500);
        }
    }
    
    private function deleteUser() {
        $userId = (int)($_GET['id'] ?? 0);
        if (!$userId) {
            ResponseHelper::error('User ID is required');
        }
        
        $currentUser = SessionManager::getCurrentUser();
        
        // Prevent self-deletion
        if ($userId == $currentUser['id']) {
            ResponseHelper::error('Cannot delete your own account');
        }
        
        // Get user info before deletion
        $userSql = "SELECT username, avatar_url FROM users WHERE id = ?";
        $userStmt = $this->db->prepare($userSql);
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();
        
        if (!$user) {
            ResponseHelper::error('User not found', 404);
        }
        
        // Delete related records first
        $this->db->prepare("DELETE FROM event_registrations WHERE user_id = ?")->execute([$userId]);
        
        // Delete user
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$userId])) {
            // Delete avatar file
            if ($user['avatar_url']) {
                FileUploadHelper::delete("../uploads/" . basename($user['avatar_url']));
            }
            
            // Log activity
            ErrorLogger::log("User deleted: {$user['username']} (ID $userId) by admin {$currentUser['id']}", 'INFO');
            
            ResponseHelper::success(null, 'User deleted successfully');
        } else {
            ErrorLogger::log("Failed to delete user ID $userId by admin {$currentUser['id']}");
            ResponseHelper::error('Failed to delete user', 500);
        }
    }
    
    private function uploadAvatar() {
        if (!isset($_FILES['avatar'])) {
            ResponseHelper::error('No avatar file provided');
        }
        
        $currentUser = SessionManager::getCurrentUser();
        
        try {
            $result = FileUploadHelper::upload($_FILES['avatar'], 'image', 2097152); // 2MB limit
            
            // Update user's avatar URL
            $sql = "UPDATE users SET avatar_url = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$result['url'], $currentUser['id']]);
            
            ResponseHelper::success($result, 'Avatar uploaded successfully');
        } catch (Exception $e) {
            ResponseHelper::error('Upload failed: ' . $e->getMessage());
        }
    }
}

// Handle the request
$api = new UsersAPI();
$api->handleRequest();

?>
