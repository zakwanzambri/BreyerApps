<?php
/**
 * News API Endpoints
 * Campus Hub Portal - Enhanced Version
 */

require_once 'config.php';

class NewsAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_GET['action'] ?? '';
        
        switch ($method) {
            case 'GET':
                $this->handleGet($path);
                break;
            case 'POST':
                $this->handlePost($path);
                break;
            case 'PUT':
                $this->handlePut($path);
                break;
            case 'DELETE':
                $this->handleDelete($path);
                break;
            default:
                ResponseHelper::error('Method not allowed', 405);
        }
    }
    
    private function handleGet($action) {
        switch ($action) {
            case 'list':
                $this->getNewsList();
                break;
            case 'featured':
                $this->getFeaturedNews();
                break;
            case 'recent':
                $this->getRecentNews();
                break;
            case 'by-category':
                $this->getNewsByCategory();
                break;
            case 'detail':
                $this->getNewsDetail();
                break;
            default:
                $this->getNewsList();
        }
    }
    
    private function handlePost($action) {
        SessionManager::requireRole(['admin', 'staff']);
        
        switch ($action) {
            case 'create':
                $this->createNews();
                break;
            default:
                ResponseHelper::error('Invalid action');
        }
    }
    
    private function handlePut($action) {
        SessionManager::requireRole(['admin', 'staff']);
        
        switch ($action) {
            case 'update':
                $this->updateNews();
                break;
            default:
                ResponseHelper::error('Invalid action');
        }
    }
    
    private function handleDelete($action) {
        SessionManager::requireRole(['admin']);
        
        switch ($action) {
            case 'delete':
                $this->deleteNews();
                break;
            default:
                ResponseHelper::error('Invalid action');
        }
    }
    
    private function getNewsList() {
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = (int)($_GET['offset'] ?? 0);
        $category = $_GET['category'] ?? null;
        $search = $_GET['search'] ?? null;
        
        $where = ["status = 'published'"];
        $params = [];
        
        if ($category) {
            $where[] = "category = ?";
            $params[] = $category;
        }
        
        if ($search) {
            $where[] = "(title LIKE ? OR content LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT n.*, u.full_name as author_name 
                FROM news n 
                LEFT JOIN users u ON n.author_id = u.id 
                WHERE {$whereClause}
                ORDER BY n.featured DESC, n.publish_date DESC, n.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $news = $this->db->fetchAll($sql, $params);
        
        // Format dates and add time ago
        foreach ($news as &$item) {
            $item['publish_date_formatted'] = Utils::formatDate($item['publish_date'], 'M j, Y');
            $item['time_ago'] = Utils::timeAgo($item['publish_date']);
            $item['excerpt'] = $item['excerpt'] ?: substr(strip_tags($item['content']), 0, 150) . '...';
        }
        
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM news WHERE {$whereClause}";
        $countParams = array_slice($params, 0, -2); // Remove limit and offset
        $total = $this->db->fetch($countSql, $countParams)['total'];
        
        ResponseHelper::success([
            'news' => $news,
            'pagination' => [
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ]);
    }
    
    private function getFeaturedNews() {
        $sql = "SELECT n.*, u.full_name as author_name 
                FROM news n 
                LEFT JOIN users u ON n.author_id = u.id 
                WHERE n.status = 'published' AND n.featured = 1
                ORDER BY n.publish_date DESC 
                LIMIT 1";
        
        $news = $this->db->fetch($sql);
        
        if ($news) {
            $news['publish_date_formatted'] = Utils::formatDate($news['publish_date'], 'M j, Y');
            $news['time_ago'] = Utils::timeAgo($news['publish_date']);
        }
        
        ResponseHelper::success($news);
    }
    
    private function getRecentNews() {
        $limit = (int)($_GET['limit'] ?? 5);
        
        $sql = "SELECT n.*, u.full_name as author_name 
                FROM news n 
                LEFT JOIN users u ON n.author_id = u.id 
                WHERE n.status = 'published'
                ORDER BY n.publish_date DESC, n.created_at DESC 
                LIMIT ?";
        
        $news = $this->db->fetchAll($sql, [$limit]);
        
        foreach ($news as &$item) {
            $item['publish_date_formatted'] = Utils::formatDate($item['publish_date'], 'M j, Y');
            $item['time_ago'] = Utils::timeAgo($item['publish_date']);
            $item['excerpt'] = $item['excerpt'] ?: substr(strip_tags($item['content']), 0, 100) . '...';
        }
        
        ResponseHelper::success($news);
    }
    
    private function getNewsByCategory() {
        $category = $_GET['category'] ?? '';
        $limit = (int)($_GET['limit'] ?? 10);
        
        if (!$category) {
            ResponseHelper::error('Category parameter required');
        }
        
        $sql = "SELECT n.*, u.full_name as author_name 
                FROM news n 
                LEFT JOIN users u ON n.author_id = u.id 
                WHERE n.status = 'published' AND n.category = ?
                ORDER BY n.publish_date DESC 
                LIMIT ?";
        
        $news = $this->db->fetchAll($sql, [$category, $limit]);
        
        foreach ($news as &$item) {
            $item['publish_date_formatted'] = Utils::formatDate($item['publish_date'], 'M j, Y');
            $item['time_ago'] = Utils::timeAgo($item['publish_date']);
            $item['excerpt'] = $item['excerpt'] ?: substr(strip_tags($item['content']), 0, 150) . '...';
        }
        
        ResponseHelper::success($news);
    }
    
    private function getNewsDetail() {
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            ResponseHelper::error('News ID required');
        }
        
        $sql = "SELECT n.*, u.full_name as author_name 
                FROM news n 
                LEFT JOIN users u ON n.author_id = u.id 
                WHERE n.id = ? AND n.status = 'published'";
        
        $news = $this->db->fetch($sql, [$id]);
        
        if (!$news) {
            ResponseHelper::notFound('News article not found');
        }
        
        $news['publish_date_formatted'] = Utils::formatDate($news['publish_date'], 'M j, Y g:i A');
        $news['time_ago'] = Utils::timeAgo($news['publish_date']);
        
        ResponseHelper::success($news);
    }
    
    private function createNews() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['title', 'content', 'category'];
        foreach ($required as $field) {
            if (!Validator::validateRequired($input[$field] ?? '')) {
                ResponseHelper::error("Field '{$field}' is required");
            }
        }
        
        // Sanitize input
        $data = Validator::sanitize($input);
        
        // Validate category
        $validCategories = ['academic', 'events', 'campus', 'urgent'];
        if (!in_array($data['category'], $validCategories)) {
            ResponseHelper::error('Invalid category');
        }
        
        $currentUser = SessionManager::getCurrentUser();
        
        $sql = "INSERT INTO news (title, content, excerpt, category, author_id, featured, status, publish_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['title'],
            $data['content'],
            $data['excerpt'] ?? substr(strip_tags($data['content']), 0, 150) . '...',
            $data['category'],
            $currentUser['id'],
            $data['featured'] ?? false,
            $data['status'] ?? 'published',
            $data['publish_date'] ?? date('Y-m-d H:i:s')
        ];
        
        try {
            $this->db->execute($sql, $params);
            $newsId = $this->db->lastInsertId();
            
            ResponseHelper::success(['id' => $newsId], 'News article created successfully');
        } catch (Exception $e) {
            ResponseHelper::error('Failed to create news article');
        }
    }
    
    private function updateNews() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            ResponseHelper::error('News ID required');
        }
        
        // Check if news exists
        $existing = $this->db->fetch("SELECT * FROM news WHERE id = ?", [$id]);
        if (!$existing) {
            ResponseHelper::notFound('News article not found');
        }
        
        $data = Validator::sanitize($input);
        
        $updateFields = [];
        $params = [];
        
        if (isset($data['title'])) {
            $updateFields[] = "title = ?";
            $params[] = $data['title'];
        }
        
        if (isset($data['content'])) {
            $updateFields[] = "content = ?";
            $params[] = $data['content'];
        }
        
        if (isset($data['excerpt'])) {
            $updateFields[] = "excerpt = ?";
            $params[] = $data['excerpt'];
        }
        
        if (isset($data['category'])) {
            $validCategories = ['academic', 'events', 'campus', 'urgent'];
            if (in_array($data['category'], $validCategories)) {
                $updateFields[] = "category = ?";
                $params[] = $data['category'];
            }
        }
        
        if (isset($data['featured'])) {
            $updateFields[] = "featured = ?";
            $params[] = $data['featured'] ? 1 : 0;
        }
        
        if (isset($data['status'])) {
            $validStatuses = ['draft', 'published', 'archived'];
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
        
        $sql = "UPDATE news SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        try {
            $this->db->execute($sql, $params);
            ResponseHelper::success(null, 'News article updated successfully');
        } catch (Exception $e) {
            ResponseHelper::error('Failed to update news article');
        }
    }
    
    private function deleteNews() {
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            ResponseHelper::error('News ID required');
        }
        
        // Check if news exists
        $existing = $this->db->fetch("SELECT * FROM news WHERE id = ?", [$id]);
        if (!$existing) {
            ResponseHelper::notFound('News article not found');
        }
        
        try {
            $this->db->execute("DELETE FROM news WHERE id = ?", [$id]);
            ResponseHelper::success(null, 'News article deleted successfully');
        } catch (Exception $e) {
            ResponseHelper::error('Failed to delete news article');
        }
    }
}

// Handle the request
try {
    $api = new NewsAPI();
    $api->handleRequest();
} catch (Exception $e) {
    ResponseHelper::error('API Error: ' . $e->getMessage(), 500);
}

?>
