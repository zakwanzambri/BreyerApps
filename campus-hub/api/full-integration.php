<?php
/**
 * Full Integration API - Campus Hub
 * Real-time data endpoints for personalized content
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once '../config/database.php';

class FullIntegrationAPI {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($action) {
                case 'user-dashboard':
                    return $this->getUserDashboard();
                case 'user-courses':
                    return $this->getUserCourses();
                case 'user-assignments':
                    return $this->getUserAssignments();
                case 'user-grades':
                    return $this->getUserGrades();
                case 'academic-calendar':
                    return $this->getAcademicCalendar();
                case 'notifications':
                    return $this->getNotifications();
                case 'personalized-news':
                    return $this->getPersonalizedNews();
                case 'course-details':
                    return $this->getCourseDetails();
                case 'upcoming-deadlines':
                    return $this->getUpcomingDeadlines();
                case 'academic-progress':
                    return $this->getAcademicProgress();
                case 'campus-events':
                    return $this->getCampusEvents();
                case 'mark-notification-read':
                    return $this->markNotificationRead();
                default:
                    throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    private function getUserDashboard() {
        $userId = $this->getUserId();
        
        // Get user info with program details
        $userQuery = "
            SELECT u.*, p.name as program_name, p.description as program_description
            FROM users u 
            LEFT JOIN programs p ON u.program_id = p.id 
            WHERE u.id = ?
        ";
        $userStmt = $this->db->prepare($userQuery);
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $user = $userStmt->get_result()->fetch_assoc();
        
        // Get course count
        $courseCountQuery = "
            SELECT COUNT(*) as total_courses 
            FROM enrollments e 
            WHERE e.student_id = ? AND e.status = 'active'
        ";
        $courseStmt = $this->db->prepare($courseCountQuery);
        $courseStmt->bind_param("i", $userId);
        $courseStmt->execute();
        $courseCount = $courseStmt->get_result()->fetch_assoc()['total_courses'];
        
        // Get pending assignments
        $assignmentQuery = "
            SELECT COUNT(*) as pending_assignments 
            FROM assignments a
            JOIN enrollments e ON e.course_id = a.course_id
            LEFT JOIN assignment_submissions sub ON sub.assignment_id = a.id AND sub.student_id = ?
            WHERE e.student_id = ? AND e.status = 'active' 
            AND a.due_date > NOW() AND sub.id IS NULL
        ";
        $assignStmt = $this->db->prepare($assignmentQuery);
        $assignStmt->bind_param("ii", $userId, $userId);
        $assignStmt->execute();
        $pendingAssignments = $assignStmt->get_result()->fetch_assoc()['pending_assignments'];
        
        // Get unread notifications
        $notificationQuery = "
            SELECT COUNT(*) as unread_notifications 
            FROM notifications 
            WHERE user_id = ? AND is_read = FALSE
        ";
        $notifStmt = $this->db->prepare($notificationQuery);
        $notifStmt->bind_param("i", $userId);
        $notifStmt->execute();
        $unreadNotifications = $notifStmt->get_result()->fetch_assoc()['unread_notifications'];
        
        // Get current GPA
        $gpaQuery = "
            SELECT AVG(gpa_points) as current_gpa 
            FROM enrollments 
            WHERE student_id = ? AND gpa_points IS NOT NULL
        ";
        $gpaStmt = $this->db->prepare($gpaQuery);
        $gpaStmt->bind_param("i", $userId);
        $gpaStmt->execute();
        $currentGPA = $gpaStmt->get_result()->fetch_assoc()['current_gpa'] ?? 0;
        
        return $this->successResponse([
            'user' => $user,
            'stats' => [
                'total_courses' => (int)$courseCount,
                'pending_assignments' => (int)$pendingAssignments,
                'unread_notifications' => (int)$unreadNotifications,
                'current_gpa' => round($currentGPA, 2)
            ]
        ]);
    }
    
    private function getUserCourses() {
        $userId = $this->getUserId();
        
        $query = "
            SELECT c.*, e.grade, e.gpa_points, e.status as enrollment_status,
                   u.full_name as lecturer_name
            FROM courses c
            JOIN enrollments e ON e.course_id = c.id
            LEFT JOIN users u ON u.id = c.lecturer_id
            WHERE e.student_id = ?
            ORDER BY c.semester ASC, c.course_name ASC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        
        return $this->successResponse($courses);
    }
    
    private function getUserAssignments() {
        $userId = $this->getUserId();
        $limit = $_GET['limit'] ?? 10;
        
        $query = "
            SELECT a.*, c.course_name, c.course_code,
                   sub.marks_obtained, sub.status as submission_status,
                   sub.submission_date, sub.feedback
            FROM assignments a
            JOIN courses c ON c.id = a.course_id
            JOIN enrollments e ON e.course_id = c.id
            LEFT JOIN assignment_submissions sub ON sub.assignment_id = a.id AND sub.student_id = ?
            WHERE e.student_id = ? AND e.status = 'active'
            ORDER BY a.due_date ASC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iii", $userId, $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        
        return $this->successResponse($assignments);
    }
    
    private function getUpcomingDeadlines() {
        $userId = $this->getUserId();
        
        $query = "
            SELECT a.title, a.due_date, c.course_name, c.course_code,
                   DATEDIFF(a.due_date, NOW()) as days_remaining
            FROM assignments a
            JOIN courses c ON c.id = a.course_id
            JOIN enrollments e ON e.course_id = c.id
            LEFT JOIN assignment_submissions sub ON sub.assignment_id = a.id AND sub.student_id = ?
            WHERE e.student_id = ? AND e.status = 'active' 
            AND a.due_date > NOW() AND sub.id IS NULL
            ORDER BY a.due_date ASC
            LIMIT 5
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $deadlines = [];
        while ($row = $result->fetch_assoc()) {
            $deadlines[] = $row;
        }
        
        return $this->successResponse($deadlines);
    }
    
    private function getAcademicCalendar() {
        $userId = $this->getUserId();
        
        // Get user's program
        $programQuery = "SELECT program_id FROM users WHERE id = ?";
        $programStmt = $this->db->prepare($programQuery);
        $programStmt->bind_param("i", $userId);
        $programStmt->execute();
        $programId = $programStmt->get_result()->fetch_assoc()['program_id'];
        
        $query = "
            SELECT * FROM academic_events 
            WHERE (program_id IS NULL OR program_id = ?) 
            AND event_date >= CURDATE()
            ORDER BY event_date ASC
            LIMIT 20
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        
        return $this->successResponse($events);
    }
    
    private function getNotifications() {
        $userId = $this->getUserId();
        $limit = $_GET['limit'] ?? 10;
        
        $query = "
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        return $this->successResponse($notifications);
    }
    
    private function getPersonalizedNews() {
        $userId = $this->getUserId();
        
        // Get user's program
        $programQuery = "SELECT program_id FROM users WHERE id = ?";
        $programStmt = $this->db->prepare($programQuery);
        $programStmt->bind_param("i", $userId);
        $programStmt->execute();
        $programId = $programStmt->get_result()->fetch_assoc()['program_id'];
        
        // Get program-specific and general news
        $query = "
            SELECT n.*, p.name as program_name 
            FROM news_events n
            LEFT JOIN programs p ON n.program_filter = p.id
            WHERE n.program_filter IS NULL OR n.program_filter = ?
            ORDER BY n.created_at DESC
            LIMIT 10
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $news = [];
        while ($row = $result->fetch_assoc()) {
            $news[] = $row;
        }
        
        return $this->successResponse($news);
    }
    
    private function getAcademicProgress() {
        $userId = $this->getUserId();
        
        // Get completed vs total courses
        $progressQuery = "
            SELECT 
                COUNT(*) as total_enrolled,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                AVG(CASE WHEN gpa_points IS NOT NULL THEN gpa_points ELSE NULL END) as avg_gpa
            FROM enrollments 
            WHERE student_id = ?
        ";
        
        $stmt = $this->db->prepare($progressQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $progress = $stmt->get_result()->fetch_assoc();
        
        // Get semester breakdown
        $semesterQuery = "
            SELECT c.semester, COUNT(*) as course_count,
                   AVG(e.gpa_points) as semester_gpa
            FROM enrollments e
            JOIN courses c ON c.id = e.course_id
            WHERE e.student_id = ?
            GROUP BY c.semester
            ORDER BY c.semester
        ";
        
        $semStmt = $this->db->prepare($semesterQuery);
        $semStmt->bind_param("i", $userId);
        $semStmt->execute();
        $semesterResult = $semStmt->get_result();
        
        $semesters = [];
        while ($row = $semesterResult->fetch_assoc()) {
            $semesters[] = $row;
        }
        
        return $this->successResponse([
            'overall_progress' => $progress,
            'semester_breakdown' => $semesters
        ]);
    }
    
    private function markNotificationRead() {
        $userId = $this->getUserId();
        $notificationId = $_POST['notification_id'] ?? null;
        
        if (!$notificationId) {
            throw new Exception('Notification ID required');
        }
        
        $query = "
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE id = ? AND user_id = ?
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $notificationId, $userId);
        $stmt->execute();
        
        return $this->successResponse(['message' => 'Notification marked as read']);
    }
    
    private function getUserId() {
        // Get user ID from token or session
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
            // Validate token and get user ID
            // For now, decode from token or use session
        }
        
        // Fallback: get from GET parameter (for development)
        $userId = $_GET['user_id'] ?? null;
        
        if (!$userId) {
            throw new Exception('Authentication required');
        }
        
        return $userId;
    }
    
    private function successResponse($data) {
        return [
            'success' => true,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function errorResponse($message) {
        http_response_code(400);
        return [
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// Initialize and handle request
$api = new FullIntegrationAPI();
$response = $api->handleRequest();

echo json_encode($response, JSON_PRETTY_PRINT);
?>
