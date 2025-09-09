<?php
/**
 * Events API Endpoints
 * Campus Hub Portal - Enhanced Version
 */

require_once '../config.php';

class EventsAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
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
    }
    
    private function handleGet($action) {
        switch ($action) {
            case 'calendar':
                $this->getCalendarEvents();
                break;
            case 'upcoming':
                $this->getUpcomingEvents();
                break;
            case 'by-date':
                $this->getEventsByDate();
                break;
            case 'by-type':
                $this->getEventsByType();
                break;
            case 'detail':
                $this->getEventDetail();
                break;
            default:
                $this->getCalendarEvents();
        }
    }
    
    private function handlePost($action) {
        SessionManager::requireRole(['admin', 'staff']);
        
        switch ($action) {
            case 'create':
                $this->createEvent();
                break;
            default:
                ResponseHelper::error('Invalid action');
        }
    }
    
    private function handlePut($action) {
        SessionManager::requireRole(['admin', 'staff']);
        
        switch ($action) {
            case 'update':
                $this->updateEvent();
                break;
            default:
                ResponseHelper::error('Invalid action');
        }
    }
    
    private function handleDelete($action) {
        SessionManager::requireRole(['admin']);
        
        switch ($action) {
            case 'delete':
                $this->deleteEvent();
                break;
            default:
                ResponseHelper::error('Invalid action');
        }
    }
    
    private function getCalendarEvents() {
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
        $limit = (int)($_GET['limit'] ?? 50);
        
        // Get events for the specified month/year
        $startDate = "{$year}-{$month}-01";
        $endDate = date("Y-m-t", strtotime($startDate));
        
        $sql = "SELECT e.*, p.program_name, u.full_name as created_by_name
                FROM events e
                LEFT JOIN programs p ON e.program_id = p.id
                LEFT JOIN users u ON e.created_by = u.id
                WHERE (e.start_date BETWEEN ? AND ? OR e.end_date BETWEEN ? AND ?)
                AND e.status IN ('scheduled', 'ongoing')
                ORDER BY e.start_date ASC, e.start_time ASC
                LIMIT ?";
        
        $events = $this->db->fetchAll($sql, [$startDate, $endDate, $startDate, $endDate, $limit]);
        
        // Format events for calendar display
        foreach ($events as &$event) {
            $event['start_date_formatted'] = Utils::formatDate($event['start_date'], 'M j, Y');
            $event['end_date_formatted'] = $event['end_date'] ? Utils::formatDate($event['end_date'], 'M j, Y') : null;
            $event['start_time_formatted'] = $event['start_time'] ? date('g:i A', strtotime($event['start_time'])) : null;
            $event['end_time_formatted'] = $event['end_time'] ? date('g:i A', strtotime($event['end_time'])) : null;
            $event['is_multiday'] = $event['end_date'] && $event['start_date'] !== $event['end_date'];
            $event['duration_days'] = $event['end_date'] ? 
                (strtotime($event['end_date']) - strtotime($event['start_date'])) / (60 * 60 * 24) + 1 : 1;
        }
        
        ResponseHelper::success($events);
    }
    
    private function getUpcomingEvents() {
        $limit = (int)($_GET['limit'] ?? 10);
        $days = (int)($_GET['days'] ?? 30);
        
        $endDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $sql = "SELECT e.*, p.program_name, u.full_name as created_by_name
                FROM events e
                LEFT JOIN programs p ON e.program_id = p.id
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.start_date >= CURDATE() AND e.start_date <= ?
                AND e.status IN ('scheduled', 'ongoing')
                ORDER BY e.start_date ASC, e.start_time ASC
                LIMIT ?";
        
        $events = $this->db->fetchAll($sql, [$endDate, $limit]);
        
        foreach ($events as &$event) {
            $event['start_date_formatted'] = Utils::formatDate($event['start_date'], 'M j, Y');
            $event['start_time_formatted'] = $event['start_time'] ? date('g:i A', strtotime($event['start_time'])) : 'All Day';
            $event['days_until'] = ceil((strtotime($event['start_date']) - time()) / (60 * 60 * 24));
            $event['is_today'] = $event['start_date'] === date('Y-m-d');
            $event['is_tomorrow'] = $event['start_date'] === date('Y-m-d', strtotime('+1 day'));
        }
        
        ResponseHelper::success($events);
    }
    
    private function getEventsByDate() {
        $date = $_GET['date'] ?? date('Y-m-d');
        
        if (!Validator::validateDate($date)) {
            ResponseHelper::error('Invalid date format. Use YYYY-MM-DD');
        }
        
        $sql = "SELECT e.*, p.program_name, u.full_name as created_by_name
                FROM events e
                LEFT JOIN programs p ON e.program_id = p.id
                LEFT JOIN users u ON e.created_by = u.id
                WHERE (? BETWEEN e.start_date AND IFNULL(e.end_date, e.start_date))
                AND e.status IN ('scheduled', 'ongoing')
                ORDER BY e.start_time ASC";
        
        $events = $this->db->fetchAll($sql, [$date]);
        
        foreach ($events as &$event) {
            $event['start_time_formatted'] = $event['start_time'] ? date('g:i A', strtotime($event['start_time'])) : 'All Day';
            $event['end_time_formatted'] = $event['end_time'] ? date('g:i A', strtotime($event['end_time'])) : null;
        }
        
        ResponseHelper::success($events);
    }
    
    private function getEventsByType() {
        $type = $_GET['type'] ?? '';
        $limit = (int)($_GET['limit'] ?? 20);
        
        if (!$type) {
            ResponseHelper::error('Event type parameter required');
        }
        
        $validTypes = ['academic', 'social', 'administrative', 'holiday'];
        if (!in_array($type, $validTypes)) {
            ResponseHelper::error('Invalid event type');
        }
        
        $sql = "SELECT e.*, p.program_name, u.full_name as created_by_name
                FROM events e
                LEFT JOIN programs p ON e.program_id = p.id
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.event_type = ? AND e.status IN ('scheduled', 'ongoing')
                ORDER BY e.start_date ASC
                LIMIT ?";
        
        $events = $this->db->fetchAll($sql, [$type, $limit]);
        
        foreach ($events as &$event) {
            $event['start_date_formatted'] = Utils::formatDate($event['start_date'], 'M j, Y');
            $event['start_time_formatted'] = $event['start_time'] ? date('g:i A', strtotime($event['start_time'])) : 'All Day';
        }
        
        ResponseHelper::success($events);
    }
    
    private function getEventDetail() {
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            ResponseHelper::error('Event ID required');
        }
        
        $sql = "SELECT e.*, p.program_name, u.full_name as created_by_name
                FROM events e
                LEFT JOIN programs p ON e.program_id = p.id
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.id = ?";
        
        $event = $this->db->fetch($sql, [$id]);
        
        if (!$event) {
            ResponseHelper::notFound('Event not found');
        }
        
        $event['start_date_formatted'] = Utils::formatDate($event['start_date'], 'l, M j, Y');
        $event['end_date_formatted'] = $event['end_date'] ? Utils::formatDate($event['end_date'], 'l, M j, Y') : null;
        $event['start_time_formatted'] = $event['start_time'] ? date('g:i A', strtotime($event['start_time'])) : 'All Day';
        $event['end_time_formatted'] = $event['end_time'] ? date('g:i A', strtotime($event['end_time'])) : null;
        
        ResponseHelper::success($event);
    }
    
    private function createEvent() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['title', 'start_date', 'event_type'];
        foreach ($required as $field) {
            if (!Validator::validateRequired($input[$field] ?? '')) {
                ResponseHelper::error("Field '{$field}' is required");
            }
        }
        
        $data = Validator::sanitize($input);
        
        // Validate dates
        if (!Validator::validateDate($data['start_date'])) {
            ResponseHelper::error('Invalid start date format');
        }
        
        if (isset($data['end_date']) && $data['end_date'] && !Validator::validateDate($data['end_date'])) {
            ResponseHelper::error('Invalid end date format');
        }
        
        // Validate event type
        $validTypes = ['academic', 'social', 'administrative', 'holiday'];
        if (!in_array($data['event_type'], $validTypes)) {
            ResponseHelper::error('Invalid event type');
        }
        
        $currentUser = SessionManager::getCurrentUser();
        
        $sql = "INSERT INTO events (title, description, event_type, start_date, end_date, start_time, end_time, location, target_audience, program_id, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['title'],
            $data['description'] ?? null,
            $data['event_type'],
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['start_time'] ?? null,
            $data['end_time'] ?? null,
            $data['location'] ?? null,
            $data['target_audience'] ?? 'all',
            $data['program_id'] ?? null,
            $data['status'] ?? 'scheduled',
            $currentUser['id']
        ];
        
        try {
            $this->db->execute($sql, $params);
            $eventId = $this->db->lastInsertId();
            
            ResponseHelper::success(['id' => $eventId], 'Event created successfully');
        } catch (Exception $e) {
            ResponseHelper::error('Failed to create event');
        }
    }
    
    private function updateEvent() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            ResponseHelper::error('Event ID required');
        }
        
        // Check if event exists
        $existing = $this->db->fetch("SELECT * FROM events WHERE id = ?", [$id]);
        if (!$existing) {
            ResponseHelper::notFound('Event not found');
        }
        
        $data = Validator::sanitize($input);
        
        $updateFields = [];
        $params = [];
        
        if (isset($data['title'])) {
            $updateFields[] = "title = ?";
            $params[] = $data['title'];
        }
        
        if (isset($data['description'])) {
            $updateFields[] = "description = ?";
            $params[] = $data['description'];
        }
        
        if (isset($data['start_date']) && Validator::validateDate($data['start_date'])) {
            $updateFields[] = "start_date = ?";
            $params[] = $data['start_date'];
        }
        
        if (isset($data['end_date'])) {
            if ($data['end_date'] && Validator::validateDate($data['end_date'])) {
                $updateFields[] = "end_date = ?";
                $params[] = $data['end_date'];
            } elseif (!$data['end_date']) {
                $updateFields[] = "end_date = NULL";
            }
        }
        
        if (isset($data['start_time'])) {
            $updateFields[] = "start_time = ?";
            $params[] = $data['start_time'] ?: null;
        }
        
        if (isset($data['end_time'])) {
            $updateFields[] = "end_time = ?";
            $params[] = $data['end_time'] ?: null;
        }
        
        if (isset($data['location'])) {
            $updateFields[] = "location = ?";
            $params[] = $data['location'];
        }
        
        if (isset($data['status'])) {
            $validStatuses = ['scheduled', 'ongoing', 'completed', 'cancelled'];
            if (in_array($data['status'], $validStatuses)) {
                $updateFields[] = "status = ?";
                $params[] = $data['status'];
            }
        }
        
        if (empty($updateFields)) {
            ResponseHelper::error('No valid fields to update');
        }
        
        $updateFields[] = "updated_at = NOW()";
        $params[] = $id;
        
        $sql = "UPDATE events SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        try {
            $this->db->execute($sql, $params);
            ResponseHelper::success(null, 'Event updated successfully');
        } catch (Exception $e) {
            ResponseHelper::error('Failed to update event');
        }
    }
    
    private function deleteEvent() {
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            ResponseHelper::error('Event ID required');
        }
        
        // Check if event exists
        $existing = $this->db->fetch("SELECT * FROM events WHERE id = ?", [$id]);
        if (!$existing) {
            ResponseHelper::notFound('Event not found');
        }
        
        try {
            $this->db->execute("DELETE FROM events WHERE id = ?", [$id]);
            ResponseHelper::success(null, 'Event deleted successfully');
        } catch (Exception $e) {
            ResponseHelper::error('Failed to delete event');
        }
    }
}

// Handle the request
try {
    $api = new EventsAPI();
    $api->handleRequest();
} catch (Exception $e) {
    ResponseHelper::error('API Error: ' . $e->getMessage(), 500);
}

?>
