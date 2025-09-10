<?php
/**
 * Events API Endpoints
 * Campus Hub Portal - Enhanced Version
 */

require_once '../../config/database.php';

class EventsAPI {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
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
            ErrorLogger::logException($e, "Events API - $method $action");
            ResponseHelper::error('Internal server error', 500);
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
            case 'detail':
                $this->getEventDetail();
                break;
            case 'by-date':
                $this->getEventsByDate();
                break;
            case 'by-type':
                $this->getEventsByType();
                break;
            case 'search':
                $this->searchEvents();
                break;
            case 'types':
                $this->getEventTypes();
                break;
            case 'stats':
                $this->getEventStats();
                break;
            case 'my-events':
                $this->getMyEvents();
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
            case 'register':
                $this->registerForEvent();
                break;
            case 'upload-image':
                $this->uploadImage();
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
            case 'cancel':
                $this->cancelEvent();
                break;
            case 'approve':
                $this->approveEvent();
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
            case 'unregister':
                $this->unregisterFromEvent();
                break;
            default:
                ResponseHelper::error('Invalid action');
        }
    }
    
    private function getCalendarEvents() {
        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
        
        $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $sql = "
            SELECT 
                e.id,
                e.title,
                e.description,
                e.event_type,
                e.start_date,
                e.end_date,
                e.start_time,
                e.end_time,
                e.location,
                e.image_url,
                e.max_participants,
                e.status,
                u.name as organizer_name,
                COUNT(r.id) as registered_count
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            LEFT JOIN event_registrations r ON e.id = r.event_id AND r.status = 'registered'
            WHERE e.start_date BETWEEN ? AND ? 
            AND e.status IN ('active', 'approved')
            GROUP BY e.id
            ORDER BY e.start_date ASC, e.start_time ASC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $limit]);
        $events = $stmt->fetchAll();
        
        foreach ($events as &$event) {
            $event['is_full'] = $event['max_participants'] > 0 && 
                               $event['registered_count'] >= $event['max_participants'];
            $event['spaces_left'] = max(0, $event['max_participants'] - $event['registered_count']);
            $event['start_datetime'] = $event['start_date'] . ' ' . $event['start_time'];
            $event['end_datetime'] = $event['end_date'] . ' ' . $event['end_time'];
        }
        
        ResponseHelper::success($events, 'Calendar events retrieved successfully');
    }
    
    private function getEventsByDate() {
        $date = $_GET['date'] ?? date('Y-m-d');
        
        if (!strtotime($date)) {
            ResponseHelper::error('Invalid date format');
        }
        
        $sql = "
            SELECT 
                e.id,
                e.title,
                e.description,
                e.event_type,
                e.start_date,
                e.end_date,
                e.start_time,
                e.end_time,
                e.location,
                e.image_url,
                e.max_participants,
                u.name as organizer_name,
                COUNT(r.id) as registered_count
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            LEFT JOIN event_registrations r ON e.id = r.event_id AND r.status = 'registered'
            WHERE e.start_date = ? AND e.status IN ('active', 'approved')
            GROUP BY e.id
            ORDER BY e.start_time ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$date]);
        $events = $stmt->fetchAll();
        
        foreach ($events as &$event) {
            $event['is_full'] = $event['max_participants'] > 0 && 
                               $event['registered_count'] >= $event['max_participants'];
            $event['spaces_left'] = max(0, $event['max_participants'] - $event['registered_count']);
        }
        
        ResponseHelper::success($events, "Events for $date retrieved successfully");
    }
    
    private function getEventsByType() {
        $type = $_GET['type'] ?? '';
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        
        if (!$type) {
            ResponseHelper::error('Event type is required');
        }
        
        $sql = "
            SELECT 
                e.id,
                e.title,
                e.description,
                e.event_type,
                e.start_date,
                e.end_date,
                e.start_time,
                e.end_time,
                e.location,
                e.image_url,
                e.max_participants,
                u.name as organizer_name,
                COUNT(r.id) as registered_count
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            LEFT JOIN event_registrations r ON e.id = r.event_id AND r.status = 'registered'
            WHERE e.event_type = ? AND e.status IN ('active', 'approved')
            GROUP BY e.id
            ORDER BY e.start_date ASC, e.start_time ASC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type, $limit]);
        $events = $stmt->fetchAll();
        
        foreach ($events as &$event) {
            $event['is_full'] = $event['max_participants'] > 0 && 
                               $event['registered_count'] >= $event['max_participants'];
            $event['spaces_left'] = max(0, $event['max_participants'] - $event['registered_count']);
        }
        
        ResponseHelper::success($events, "Events of type '$type' retrieved successfully");
    }
    
    private function searchEvents() {
        $searchTerm = $_GET['q'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        
        if (!$searchTerm) {
            ResponseHelper::error('Search term is required');
        }
        
        $searchFields = ['e.title', 'e.description', 'e.location'];
        list($searchWhere, $searchParams) = SearchHelper::buildWhereClause($searchTerm, $searchFields);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM events e WHERE e.status IN ('active', 'approved') AND $searchWhere";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($searchParams);
        $total = $countStmt->fetch()['total'];
        
        // Get search results
        $sql = "
            SELECT 
                e.id,
                e.title,
                e.description,
                e.event_type,
                e.start_date,
                e.end_date,
                e.start_time,
                e.end_time,
                e.location,
                e.image_url,
                e.max_participants,
                u.name as organizer_name,
                COUNT(r.id) as registered_count
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            LEFT JOIN event_registrations r ON e.id = r.event_id AND r.status = 'registered'
            WHERE e.status IN ('active', 'approved') AND $searchWhere
            GROUP BY e.id
            ORDER BY e.start_date ASC, e.start_time ASC
            LIMIT ? OFFSET ?
        ";
        
        $params = array_merge($searchParams, [$limit, $offset]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();
        
        foreach ($events as &$event) {
            $event['is_full'] = $event['max_participants'] > 0 && 
                               $event['registered_count'] >= $event['max_participants'];
            $event['spaces_left'] = max(0, $event['max_participants'] - $event['registered_count']);
            $event['title'] = SearchHelper::highlightResults($event['title'], $searchTerm);
            $event['description'] = SearchHelper::highlightResults($event['description'], $searchTerm);
            $event['location'] = SearchHelper::highlightResults($event['location'], $searchTerm);
        }
        
        ResponseHelper::paginated($events, $total, $page, $limit, "Search results for '$searchTerm'");
    }
    
    private function getEventTypes() {
        $sql = "
            SELECT 
                event_type,
                COUNT(*) as count
            FROM events 
            WHERE status IN ('active', 'approved')
            GROUP BY event_type
            ORDER BY count DESC, event_type ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $types = $stmt->fetchAll();
        
        ResponseHelper::success($types, 'Event types retrieved successfully');
    }
    
    private function getEventStats() {
        SessionManager::requireRole(['admin', 'staff']);
        
        $sql = "
            SELECT 
                COUNT(*) as total_events,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                SUM(CASE WHEN start_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_count,
                AVG(max_participants) as avg_capacity
            FROM events
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats = $stmt->fetch();
        
        // Get registration stats
        $regSql = "
            SELECT 
                COUNT(*) as total_registrations,
                COUNT(DISTINCT user_id) as unique_participants
            FROM event_registrations 
            WHERE status = 'registered'
        ";
        
        $regStmt = $this->db->prepare($regSql);
        $regStmt->execute();
        $regStats = $regStmt->fetch();
        
        $stats = array_merge($stats, $regStats);
        
        // Get top event types
        $typeSql = "
            SELECT 
                event_type,
                COUNT(*) as count,
                AVG(max_participants) as avg_capacity
            FROM events 
            WHERE status IN ('active', 'approved')
            GROUP BY event_type
            ORDER BY count DESC
            LIMIT 10
        ";
        
        $typeStmt = $this->db->prepare($typeSql);
        $typeStmt->execute();
        $stats['top_types'] = $typeStmt->fetchAll();
        
        ResponseHelper::success($stats, 'Event statistics retrieved successfully');
    }
    
    private function getMyEvents() {
        SessionManager::requireAuth();
        $currentUser = SessionManager::getCurrentUser();
        
        $sql = "
            SELECT 
                e.id,
                e.title,
                e.description,
                e.event_type,
                e.start_date,
                e.end_date,
                e.start_time,
                e.end_time,
                e.location,
                e.image_url,
                r.status as registration_status,
                r.registered_at
            FROM events e
            INNER JOIN event_registrations r ON e.id = r.event_id
            WHERE r.user_id = ? AND r.status = 'registered'
            ORDER BY e.start_date ASC, e.start_time ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$currentUser['id']]);
        $events = $stmt->fetchAll();
        
        foreach ($events as &$event) {
            $event['days_until'] = (strtotime($event['start_date']) - strtotime(date('Y-m-d'))) / 86400;
            $event['is_past'] = strtotime($event['start_date']) < strtotime(date('Y-m-d'));
        }
        
        ResponseHelper::success($events, 'My events retrieved successfully');
    }
    
    private function createEvent() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $rules = [
            'title' => ['required' => true, 'max_length' => 200],
            'description' => ['required' => true],
            'event_type' => ['required' => true, 'max_length' => 50],
            'start_date' => ['required' => true, 'type' => 'date'],
            'end_date' => ['required' => true, 'type' => 'date'],
            'start_time' => ['required' => true],
            'end_time' => ['required' => true],
            'location' => ['required' => true, 'max_length' => 200],
            'max_participants' => ['type' => 'numeric']
        ];
        
        $errors = InputValidator::validate($input, $rules);
        if (!empty($errors)) {
            ResponseHelper::error('Validation failed', 400, $errors);
        }
        
        // Additional validation
        if (strtotime($input['end_date']) < strtotime($input['start_date'])) {
            ResponseHelper::error('End date cannot be before start date');
        }
        
        $data = InputValidator::sanitize($input);
        $currentUser = SessionManager::getCurrentUser();
        
        $sql = "
            INSERT INTO events (
                title, description, event_type, start_date, end_date,
                start_time, end_time, location, max_participants,
                organizer_id, image_url, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ";
        
        $params = [
            $data['title'],
            $data['description'],
            $data['event_type'],
            $data['start_date'],
            $data['end_date'],
            $data['start_time'],
            $data['end_time'],
            $data['location'],
            (int)($data['max_participants'] ?? 0),
            $currentUser['id'],
            $data['image_url'] ?? null,
            $currentUser['role'] === 'admin' ? 'active' : 'pending'
        ];
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            $eventId = $this->db->lastInsertId();
            
            // Clear cache
            CacheHelper::clear();
            
            // Log activity
            ErrorLogger::log("Event created: ID $eventId by user {$currentUser['id']}", 'INFO');
            
            ResponseHelper::success(['id' => $eventId], 'Event created successfully', 201);
        } else {
            ErrorLogger::log("Failed to create event by user {$currentUser['id']}");
            ResponseHelper::error('Failed to create event', 500);
        }
    }
    
    private function updateEvent() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            ResponseHelper::error('Event ID is required');
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $rules = [
            'title' => ['max_length' => 200],
            'event_type' => ['max_length' => 50],
            'start_date' => ['type' => 'date'],
            'end_date' => ['type' => 'date'],
            'location' => ['max_length' => 200],
            'max_participants' => ['type' => 'numeric']
        ];
        
        $errors = InputValidator::validate($input, $rules);
        if (!empty($errors)) {
            ResponseHelper::error('Validation failed', 400, $errors);
        }
        
        $data = InputValidator::sanitize($input);
        $currentUser = SessionManager::getCurrentUser();
        
        // Check if event exists and user has permission
        $checkSql = "SELECT organizer_id FROM events WHERE id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([$id]);
        $event = $checkStmt->fetch();
        
        if (!$event) {
            ResponseHelper::error('Event not found', 404);
        }
        
        // Only admin or original organizer can edit
        if ($currentUser['role'] !== 'admin' && $event['organizer_id'] != $currentUser['id']) {
            ResponseHelper::error('Insufficient permissions', 403);
        }
        
        $updateFields = [];
        $params = [];
        
        foreach (['title', 'description', 'event_type', 'start_date', 'end_date', 
                  'start_time', 'end_time', 'location', 'max_participants', 'image_url'] as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $field === 'max_participants' ? (int)$data[$field] : $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            ResponseHelper::error('No fields to update');
        }
        
        $updateFields[] = "updated_at = NOW()";
        $params[] = $id;
        
        $sql = "UPDATE events SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            // Clear cache
            CacheHelper::clear();
            
            // Log activity
            ErrorLogger::log("Event updated: ID $id by user {$currentUser['id']}", 'INFO');
            
            ResponseHelper::success(null, 'Event updated successfully');
        } else {
            ErrorLogger::log("Failed to update event ID $id by user {$currentUser['id']}");
            ResponseHelper::error('Failed to update event', 500);
        }
    }
    
    private function deleteEvent() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            ResponseHelper::error('Event ID is required');
        }
        
        $currentUser = SessionManager::getCurrentUser();
        
        // Check if event exists
        $checkSql = "SELECT id, image_url FROM events WHERE id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([$id]);
        $event = $checkStmt->fetch();
        
        if (!$event) {
            ResponseHelper::error('Event not found', 404);
        }
        
        // Delete event registrations first
        $deleteRegSql = "DELETE FROM event_registrations WHERE event_id = ?";
        $deleteRegStmt = $this->db->prepare($deleteRegSql);
        $deleteRegStmt->execute([$id]);
        
        // Delete event
        $sql = "DELETE FROM events WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$id])) {
            // Delete associated image file
            if ($event['image_url']) {
                FileUploadHelper::delete("../uploads/" . basename($event['image_url']));
            }
            
            // Clear cache
            CacheHelper::clear();
            
            // Log activity
            ErrorLogger::log("Event deleted: ID $id by user {$currentUser['id']}", 'INFO');
            
            ResponseHelper::success(null, 'Event deleted successfully');
        } else {
            ErrorLogger::log("Failed to delete event ID $id by user {$currentUser['id']}");
            ResponseHelper::error('Failed to delete event', 500);
        }
    }
    
    private function registerForEvent() {
        SessionManager::requireAuth();
        $currentUser = SessionManager::getCurrentUser();
        
        $eventId = (int)($_POST['event_id'] ?? $_GET['event_id'] ?? 0);
        if (!$eventId) {
            ResponseHelper::error('Event ID is required');
        }
        
        // Check if event exists and is active
        $eventSql = "
            SELECT 
                e.id, e.title, e.max_participants, e.start_date,
                COUNT(r.id) as registered_count
            FROM events e
            LEFT JOIN event_registrations r ON e.id = r.event_id AND r.status = 'registered'
            WHERE e.id = ? AND e.status = 'active'
            GROUP BY e.id
        ";
        
        $eventStmt = $this->db->prepare($eventSql);
        $eventStmt->execute([$eventId]);
        $event = $eventStmt->fetch();
        
        if (!$event) {
            ResponseHelper::error('Event not found or not active', 404);
        }
        
        // Check if event is full
        if ($event['max_participants'] > 0 && $event['registered_count'] >= $event['max_participants']) {
            ResponseHelper::error('Event is full');
        }
        
        // Check if user is already registered
        $checkSql = "SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([$eventId, $currentUser['id']]);
        
        if ($checkStmt->fetch()) {
            ResponseHelper::error('Already registered for this event');
        }
        
        // Register user
        $regSql = "
            INSERT INTO event_registrations (event_id, user_id, status, registered_at)
            VALUES (?, ?, 'registered', NOW())
        ";
        
        $regStmt = $this->db->prepare($regSql);
        
        if ($regStmt->execute([$eventId, $currentUser['id']])) {
            // Clear cache
            CacheHelper::clear();
            
            // Log activity
            ErrorLogger::log("User {$currentUser['id']} registered for event $eventId", 'INFO');
            
            ResponseHelper::success(null, 'Successfully registered for event');
        } else {
            ErrorLogger::log("Failed to register user {$currentUser['id']} for event $eventId");
            ResponseHelper::error('Failed to register for event', 500);
        }
    }
    
    private function unregisterFromEvent() {
        SessionManager::requireAuth();
        $currentUser = SessionManager::getCurrentUser();
        
        $eventId = (int)($_GET['event_id'] ?? 0);
        if (!$eventId) {
            ResponseHelper::error('Event ID is required');
        }
        
        $sql = "DELETE FROM event_registrations WHERE event_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$eventId, $currentUser['id']])) {
            // Clear cache
            CacheHelper::clear();
            
            // Log activity
            ErrorLogger::log("User {$currentUser['id']} unregistered from event $eventId", 'INFO');
            
            ResponseHelper::success(null, 'Successfully unregistered from event');
        } else {
            ResponseHelper::error('Failed to unregister from event', 500);
        }
    }
    
    private function cancelEvent() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            ResponseHelper::error('Event ID is required');
        }
        
        $sql = "UPDATE events SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$id])) {
            // Clear cache
            CacheHelper::clear();
            
            ResponseHelper::success(null, 'Event cancelled successfully');
        } else {
            ResponseHelper::error('Failed to cancel event', 500);
        }
    }
    
    private function approveEvent() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            ResponseHelper::error('Event ID is required');
        }
        
        $sql = "UPDATE events SET status = 'active', updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$id])) {
            // Clear cache
            CacheHelper::clear();
            
            ResponseHelper::success(null, 'Event approved successfully');
        } else {
            ResponseHelper::error('Failed to approve event', 500);
        }
    }
    
    private function uploadImage() {
        if (!isset($_FILES['image'])) {
            ResponseHelper::error('No image file provided');
        }
        
        try {
            $result = FileUploadHelper::upload($_FILES['image'], 'image', 5242880); // 5MB limit
            ResponseHelper::success($result, 'Image uploaded successfully');
        } catch (Exception $e) {
            ResponseHelper::error('Upload failed: ' . $e->getMessage());
        }
    }
}

// Handle the request
$api = new EventsAPI();
$api->handleRequest();

?>
        
        foreach ($events as &$event) {
            $event['is_full'] = $event['max_participants'] > 0 && 
                               $event['registered_count'] >= $event['max_participants'];
            $event['spaces_left'] = max(0, $event['max_participants'] - $event['registered_count']);
            $event['start_datetime'] = $event['start_date'] . ' ' . $event['start_time'];
            $event['end_datetime'] = $event['end_date'] . ' ' . $event['end_time'];
        }
        
        ResponseHelper::success($events, 'Calendar events retrieved successfully');
    }
    
    private function getUpcomingEvents() {
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
        $days = min(365, max(1, (int)($_GET['days'] ?? 30)));
        
        $endDate = date('Y-m-d', strtotime("+$days days"));
        
        $sql = "
            SELECT 
                e.id,
                e.title,
                e.description,
                e.event_type,
                e.start_date,
                e.end_date,
                e.start_time,
                e.end_time,
                e.location,
                e.image_url,
                e.max_participants,
                e.status,
                u.name as organizer_name,
                COUNT(r.id) as registered_count
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            LEFT JOIN event_registrations r ON e.id = r.event_id AND r.status = 'registered'
            WHERE e.start_date >= CURDATE() 
            AND e.start_date <= ?
            AND e.status IN ('active', 'approved')
            GROUP BY e.id
            ORDER BY e.start_date ASC, e.start_time ASC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$endDate, $limit]);
        $events = $stmt->fetchAll();
        
        foreach ($events as &$event) {
            $event['is_full'] = $event['max_participants'] > 0 && 
                               $event['registered_count'] >= $event['max_participants'];
            $event['spaces_left'] = max(0, $event['max_participants'] - $event['registered_count']);
            $event['days_until'] = (strtotime($event['start_date']) - strtotime(date('Y-m-d'))) / 86400;
        }
        
        ResponseHelper::success($events, 'Upcoming events retrieved successfully');
    }
    
    private function getEventDetail() {
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            ResponseHelper::error('Event ID is required');
        }
        
        $sql = "
            SELECT 
                e.*,
                u.name as organizer_name,
                u.username as organizer_username,
                COUNT(r.id) as registered_count
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            LEFT JOIN event_registrations r ON e.id = r.event_id AND r.status = 'registered'
            WHERE e.id = ?
            GROUP BY e.id
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $event = $stmt->fetch();
        
        if (!$event) {
            ResponseHelper::error('Event not found', 404);
        }
        
        $event['is_full'] = $event['max_participants'] > 0 && 
                           $event['registered_count'] >= $event['max_participants'];
        $event['spaces_left'] = max(0, $event['max_participants'] - $event['registered_count']);
        
        // Check if current user is registered
        if (SessionManager::isLoggedIn()) {
            $currentUser = SessionManager::getCurrentUser();
            $regSql = "SELECT status FROM event_registrations WHERE event_id = ? AND user_id = ?";
            $regStmt = $this->db->prepare($regSql);
            $regStmt->execute([$id, $currentUser['id']]);
            $registration = $regStmt->fetch();
            $event['user_registration_status'] = $registration['status'] ?? null;
        }
        
        ResponseHelper::success($event, 'Event detail retrieved successfully');
    }
        
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
