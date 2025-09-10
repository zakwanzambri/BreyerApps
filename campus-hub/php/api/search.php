<?php
/**
 * Search API Endpoints
 * Campus Hub Portal - Enhanced Version
 */

require_once 'config.php';

class SearchAPI {
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
                default:
                    ResponseHelper::error('Method not allowed', 405);
            }
        } catch (Exception $e) {
            ErrorLogger::logException($e, "Search API - $method $action");
            ResponseHelper::error('Internal server error', 500);
        }
    }
    
    private function handleGet($action) {
        switch ($action) {
            case 'global':
                $this->globalSearch();
                break;
            case 'news':
                $this->searchNews();
                break;
            case 'events':
                $this->searchEvents();
                break;
            case 'users':
                $this->searchUsers();
                break;
            case 'suggestions':
                $this->getSearchSuggestions();
                break;
            default:
                $this->globalSearch();
        }
    }
    
    private function globalSearch() {
        $query = $_GET['q'] ?? '';
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        
        if (strlen($query) < 2) {
            ResponseHelper::error('Search query must be at least 2 characters long');
        }
        
        $results = [
            'query' => $query,
            'total_results' => 0,
            'categories' => []
        ];
        
        // Search news
        $newsResults = $this->searchNewsInternal($query, min($limit, 10));
        if (!empty($newsResults)) {
            $results['categories']['news'] = [
                'name' => 'News',
                'count' => count($newsResults),
                'items' => $newsResults
            ];
            $results['total_results'] += count($newsResults);
        }
        
        // Search events
        $eventResults = $this->searchEventsInternal($query, min($limit, 10));
        if (!empty($eventResults)) {
            $results['categories']['events'] = [
                'name' => 'Events',
                'count' => count($eventResults),
                'items' => $eventResults
            ];
            $results['total_results'] += count($eventResults);
        }
        
        // Search users (if staff/admin)
        $currentUser = SessionManager::getCurrentUser();
        if ($currentUser && in_array($currentUser['role'], ['admin', 'staff'])) {
            $userResults = $this->searchUsersInternal($query, min($limit, 5));
            if (!empty($userResults)) {
                $results['categories']['users'] = [
                    'name' => 'Users',
                    'count' => count($userResults),
                    'items' => $userResults
                ];
                $results['total_results'] += count($userResults);
            }
        }
        
        ResponseHelper::success($results, "Global search results for '$query'");
    }
    
    private function searchNews() {
        $query = $_GET['q'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $category = $_GET['category'] ?? null;
        
        if (strlen($query) < 2) {
            ResponseHelper::error('Search query must be at least 2 characters long');
        }
        
        $searchFields = ['n.title', 'n.content', 'n.summary'];
        list($searchWhere, $searchParams) = SearchHelper::buildWhereClause($query, $searchFields);
        
        $whereConditions = ["n.status = 'published'", $searchWhere];
        $params = array_merge(['published'], $searchParams);
        
        if ($category) {
            $whereConditions[] = "n.category = ?";
            $params[] = $category;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM news n WHERE $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get results
        $sql = "
            SELECT 
                n.id,
                n.title,
                n.summary,
                n.category,
                n.image_url,
                n.views,
                n.created_at,
                u.name as author_name,
                MATCH(n.title, n.content, n.summary) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
            FROM news n
            LEFT JOIN users u ON n.author_id = u.id
            WHERE $whereClause
            ORDER BY relevance_score DESC, n.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $finalParams = array_merge([$query], $params, [$limit, $offset]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($finalParams);
        $news = $stmt->fetchAll();
        
        foreach ($news as &$item) {
            $item['created_ago'] = timeAgo($item['created_at']);
            $item['title'] = SearchHelper::highlightResults($item['title'], $query);
            $item['summary'] = SearchHelper::highlightResults($item['summary'], $query);
            $item['type'] = 'news';
        }
        
        ResponseHelper::paginated($news, $total, $page, $limit, "News search results for '$query'");
    }
    
    private function searchEvents() {
        $query = $_GET['q'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $type = $_GET['type'] ?? null;
        $date_from = $_GET['date_from'] ?? null;
        $date_to = $_GET['date_to'] ?? null;
        
        if (strlen($query) < 2) {
            ResponseHelper::error('Search query must be at least 2 characters long');
        }
        
        $searchFields = ['e.title', 'e.description', 'e.location'];
        list($searchWhere, $searchParams) = SearchHelper::buildWhereClause($query, $searchFields);
        
        $whereConditions = ["e.status IN ('active', 'approved')", $searchWhere];
        $params = $searchParams;
        
        if ($type) {
            $whereConditions[] = "e.event_type = ?";
            $params[] = $type;
        }
        
        if ($date_from) {
            $whereConditions[] = "e.start_date >= ?";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $whereConditions[] = "e.start_date <= ?";
            $params[] = $date_to;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM events e WHERE $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get results
        $sql = "
            SELECT 
                e.id,
                e.title,
                e.description,
                e.event_type,
                e.start_date,
                e.start_time,
                e.location,
                e.image_url,
                e.max_participants,
                u.name as organizer_name,
                COUNT(r.id) as registered_count,
                MATCH(e.title, e.description, e.location) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            LEFT JOIN event_registrations r ON e.id = r.event_id AND r.status = 'registered'
            WHERE $whereClause
            GROUP BY e.id
            ORDER BY relevance_score DESC, e.start_date ASC
            LIMIT ? OFFSET ?
        ";
        
        $finalParams = array_merge([$query], $params, [$limit, $offset]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($finalParams);
        $events = $stmt->fetchAll();
        
        foreach ($events as &$event) {
            $event['is_full'] = $event['max_participants'] > 0 && 
                               $event['registered_count'] >= $event['max_participants'];
            $event['spaces_left'] = max(0, $event['max_participants'] - $event['registered_count']);
            $event['days_until'] = (strtotime($event['start_date']) - strtotime(date('Y-m-d'))) / 86400;
            $event['title'] = SearchHelper::highlightResults($event['title'], $query);
            $event['description'] = SearchHelper::highlightResults($event['description'], $query);
            $event['location'] = SearchHelper::highlightResults($event['location'], $query);
            $event['type'] = 'event';
        }
        
        ResponseHelper::paginated($events, $total, $page, $limit, "Event search results for '$query'");
    }
    
    private function searchUsers() {
        SessionManager::requireRole(['admin', 'staff']);
        
        $query = $_GET['q'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $role = $_GET['role'] ?? null;
        
        if (strlen($query) < 2) {
            ResponseHelper::error('Search query must be at least 2 characters long');
        }
        
        $searchFields = ['u.name', 'u.username', 'u.email', 'u.student_id'];
        list($searchWhere, $searchParams) = SearchHelper::buildWhereClause($query, $searchFields);
        
        $whereConditions = ["u.status = 'active'", $searchWhere];
        $params = array_merge(['active'], $searchParams);
        
        if ($role) {
            $whereConditions[] = "u.role = ?";
            $params[] = $role;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM users u WHERE $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get results
        $sql = "
            SELECT 
                u.id,
                u.username,
                u.name,
                u.email,
                u.role,
                u.student_id,
                u.avatar_url,
                u.last_login,
                u.created_at
            FROM users u
            WHERE $whereClause
            ORDER BY u.name ASC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        foreach ($users as &$user) {
            $user['last_login_ago'] = $user['last_login'] ? timeAgo($user['last_login']) : 'Never';
            $user['created_ago'] = timeAgo($user['created_at']);
            $user['name'] = SearchHelper::highlightResults($user['name'], $query);
            $user['username'] = SearchHelper::highlightResults($user['username'], $query);
            $user['email'] = SearchHelper::highlightResults($user['email'], $query);
            $user['type'] = 'user';
        }
        
        ResponseHelper::paginated($users, $total, $page, $limit, "User search results for '$query'");
    }
    
    private function getSearchSuggestions() {
        $query = $_GET['q'] ?? '';
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
        
        if (strlen($query) < 2) {
            ResponseHelper::success([], 'Query too short for suggestions');
        }
        
        $suggestions = [];
        
        // Get news title suggestions
        $newsSql = "
            SELECT DISTINCT title as suggestion, 'news' as type
            FROM news 
            WHERE status = 'published' AND title LIKE ?
            ORDER BY views DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($newsSql);
        $stmt->execute(["%$query%", $limit / 2]);
        $suggestions = array_merge($suggestions, $stmt->fetchAll());
        
        // Get event title suggestions
        $eventsSql = "
            SELECT DISTINCT title as suggestion, 'event' as type
            FROM events 
            WHERE status IN ('active', 'approved') AND title LIKE ?
            ORDER BY start_date ASC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($eventsSql);
        $stmt->execute(["%$query%", $limit / 2]);
        $suggestions = array_merge($suggestions, $stmt->fetchAll());
        
        // Sort by relevance (exact matches first)
        usort($suggestions, function($a, $b) use ($query) {
            $aExact = stripos($a['suggestion'], $query) === 0 ? 1 : 0;
            $bExact = stripos($b['suggestion'], $query) === 0 ? 1 : 0;
            return $bExact - $aExact;
        });
        
        // Limit final results
        $suggestions = array_slice($suggestions, 0, $limit);
        
        ResponseHelper::success($suggestions, 'Search suggestions retrieved');
    }
    
    // Internal search methods for global search
    private function searchNewsInternal($query, $limit) {
        $searchFields = ['n.title', 'n.content', 'n.summary'];
        list($searchWhere, $searchParams) = SearchHelper::buildWhereClause($query, $searchFields);
        
        $sql = "
            SELECT 
                n.id,
                n.title,
                n.summary,
                n.category,
                n.image_url,
                n.created_at,
                u.name as author_name,
                'news' as type
            FROM news n
            LEFT JOIN users u ON n.author_id = u.id
            WHERE n.status = 'published' AND $searchWhere
            ORDER BY n.created_at DESC
            LIMIT ?
        ";
        
        $params = array_merge(['published'], $searchParams, [$limit]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        foreach ($results as &$item) {
            $item['created_ago'] = timeAgo($item['created_at']);
            $item['title'] = SearchHelper::highlightResults($item['title'], $query);
            $item['summary'] = SearchHelper::highlightResults($item['summary'], $query);
        }
        
        return $results;
    }
    
    private function searchEventsInternal($query, $limit) {
        $searchFields = ['e.title', 'e.description', 'e.location'];
        list($searchWhere, $searchParams) = SearchHelper::buildWhereClause($query, $searchFields);
        
        $sql = "
            SELECT 
                e.id,
                e.title,
                e.event_type,
                e.start_date,
                e.start_time,
                e.location,
                e.image_url,
                u.name as organizer_name,
                'event' as type
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            WHERE e.status IN ('active', 'approved') AND $searchWhere
            ORDER BY e.start_date ASC
            LIMIT ?
        ";
        
        $params = array_merge($searchParams, [$limit]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        foreach ($results as &$item) {
            $item['days_until'] = (strtotime($item['start_date']) - strtotime(date('Y-m-d'))) / 86400;
            $item['title'] = SearchHelper::highlightResults($item['title'], $query);
            $item['location'] = SearchHelper::highlightResults($item['location'], $query);
        }
        
        return $results;
    }
    
    private function searchUsersInternal($query, $limit) {
        $searchFields = ['u.name', 'u.username', 'u.email'];
        list($searchWhere, $searchParams) = SearchHelper::buildWhereClause($query, $searchFields);
        
        $sql = "
            SELECT 
                u.id,
                u.name,
                u.username,
                u.role,
                u.avatar_url,
                'user' as type
            FROM users u
            WHERE u.status = 'active' AND $searchWhere
            ORDER BY u.name ASC
            LIMIT ?
        ";
        
        $params = array_merge(['active'], $searchParams, [$limit]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        foreach ($results as &$item) {
            $item['name'] = SearchHelper::highlightResults($item['name'], $query);
            $item['username'] = SearchHelper::highlightResults($item['username'], $query);
        }
        
        return $results;
    }
}

// Handle the request
$api = new SearchAPI();
$api->handleRequest();

?>
