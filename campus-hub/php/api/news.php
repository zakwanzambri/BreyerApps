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
            ErrorLogger::logException($e, "News API - $method $action");
            ResponseHelper::error('Internal server error', 500);
        }
    }
    
    private function handleGet($action) {
        switch ($action) {
            case 'list':
                $this->getNewsList();
                break;
            case 'detail':
                $this->getNewsDetail();
                break;
            case 'featured':
                $this->getFeaturedNews();
                break;
            case 'recent':
                $this->getRecentNews();
                break;
            default:
                $this->sendError('Invalid action', 400);
                break;
        }
    }

    private function getFeaturedNews() {
        $limit = min(10, max(1, (int)($_GET['limit'] ?? 5)));
        
        $sql = "
            SELECT 
                n.id,
                n.title,
                n.summary,
                n.image_url,
                n.category,
                n.views,
                n.created_at,
                u.name as author_name
            FROM news n
            LEFT JOIN users u ON n.author_id = u.id
            WHERE n.is_featured = 1 AND n.status = 'published'
            ORDER BY n.created_at DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        $news = $stmt->fetchAll();
        
        foreach ($news as &$item) {
            $item['created_ago'] = timeAgo($item['created_at']);
        }
        
        ResponseHelper::success($news, 'Featured news retrieved successfully');
    }
    
    private function getRecentNews() {
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
        
        $sql = "
            SELECT 
                n.id,
                n.title,
                n.summary,
                n.image_url,
                n.category,
                n.views,
                n.created_at,
                u.name as author_name
            FROM news n
            LEFT JOIN users u ON n.author_id = u.id
            WHERE n.status = 'published'
            ORDER BY n.created_at DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        $news = $stmt->fetchAll();
        
        foreach ($news as &$item) {
            $item['created_ago'] = timeAgo($item['created_at']);
        }
        
        ResponseHelper::success($news, 'Recent news retrieved successfully');
    }
    
    private function getNewsByCategory() {
        $category = $_GET['category'] ?? '';
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
        
        if (!$category) {
            ResponseHelper::error('Category is required');
        }
        
        $sql = "
            SELECT 
                n.id,
                n.title,
                n.summary,
                n.image_url,
                n.category,
                n.views,
                n.created_at,
                u.name as author_name
            FROM news n
            LEFT JOIN users u ON n.author_id = u.id
            WHERE n.category = ? AND n.status = 'published'
            ORDER BY n.created_at DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$category, $limit]);
        $news = $stmt->fetchAll();
        
        foreach ($news as &$item) {
            $item['created_ago'] = timeAgo($item['created_at']);
        }
        
        ResponseHelper::success($news, "News in '$category' category retrieved successfully");
    }
    
    private function searchNews() {
        $searchTerm = $_GET['q'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        
        if (!$searchTerm) {
            ResponseHelper::error('Search term is required');
        }
        
        $searchFields = ['n.title', 'n.content', 'n.summary'];
        list($searchWhere, $searchParams) = SearchHelper::buildWhereClause($searchTerm, $searchFields);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM news n WHERE n.status = 'published' AND $searchWhere";
        $countParams = array_merge(['published'], $searchParams);
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetch()['total'];
        
        // Get search results
        $sql = "
            SELECT 
                n.id,
                n.title,
                n.summary,
                n.content,
                n.image_url,
                n.category,
                n.views,
                n.created_at,
                u.name as author_name
            FROM news n
            LEFT JOIN users u ON n.author_id = u.id
            WHERE n.status = 'published' AND $searchWhere
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params = array_merge($countParams, [$limit, $offset]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $news = $stmt->fetchAll();
        
        foreach ($news as &$item) {
            $item['created_ago'] = timeAgo($item['created_at']);
            $item['title'] = SearchHelper::highlightResults($item['title'], $searchTerm);
            $item['summary'] = SearchHelper::highlightResults($item['summary'], $searchTerm);
            $item['excerpt'] = SearchHelper::highlightResults(
                substr(strip_tags($item['content']), 0, 200) . '...', 
                $searchTerm
            );
        }
        
        ResponseHelper::paginated($news, $total, $page, $limit, "Search results for '$searchTerm'");
    }
    
    private function getCategories() {
        $sql = "
            SELECT 
                category,
                COUNT(*) as count
            FROM news 
            WHERE status = 'published'
            GROUP BY category
            ORDER BY count DESC, category ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll();
        
        ResponseHelper::success($categories, 'News categories retrieved successfully');
    }
    
    private function getNewsStats() {
        SessionManager::requireRole(['admin', 'staff']);
        
        $sql = "
            SELECT 
                COUNT(*) as total_news,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_count,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured_count,
                SUM(views) as total_views,
                AVG(views) as avg_views
            FROM news
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats = $stmt->fetch();
        
        // Get top categories
        $categorySql = "
            SELECT 
                category,
                COUNT(*) as count,
                SUM(views) as total_views
            FROM news 
            WHERE status = 'published'
            GROUP BY category
            ORDER BY count DESC
            LIMIT 10
        ";
        
        $categoryStmt = $this->db->prepare($categorySql);
        $categoryStmt->execute();
        $stats['top_categories'] = $categoryStmt->fetchAll();
        
        // Get recent activity
        $activitySql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM news
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ";
        
        $activityStmt = $this->db->prepare($activitySql);
        $activityStmt->execute();
        $stats['recent_activity'] = $activityStmt->fetchAll();
        
        ResponseHelper::success($stats, 'News statistics retrieved successfully');
    }
    
    private function createNews() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $rules = [
            'title' => ['required' => true, 'max_length' => 200],
            'content' => ['required' => true],
            'summary' => ['max_length' => 500],
            'category' => ['required' => true, 'max_length' => 50],
            'status' => ['custom' => function($value) {
                return in_array($value, ['draft', 'published']) ? true : 'Status must be draft or published';
            }]
        ];
        
        $errors = InputValidator::validate($input, $rules);
        if (!empty($errors)) {
            ResponseHelper::error('Validation failed', 400, $errors);
        }
        
        $data = InputValidator::sanitize($input);
        $currentUser = SessionManager::getCurrentUser();
        
        $sql = "
            INSERT INTO news (
                title, content, summary, category, author_id, 
                image_url, status, is_featured, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ";
        
        $params = [
            $data['title'],
            $data['content'],
            $data['summary'] ?? '',
            $data['category'],
            $currentUser['id'],
            $data['image_url'] ?? null,
            $data['status'] ?? 'draft',
            (int)($data['is_featured'] ?? 0)
        ];
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            $newsId = $this->db->lastInsertId();
            
            // Clear cache
            CacheHelper::clear();
            
            // Log activity
            ErrorLogger::log("News created: ID $newsId by user {$currentUser['id']}", 'INFO');
            
            ResponseHelper::success(['id' => $newsId], 'News created successfully', 201);
        } else {
            ErrorLogger::log("Failed to create news by user {$currentUser['id']}");
            ResponseHelper::error('Failed to create news', 500);
        }
    }
    
    private function updateNews() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            ResponseHelper::error('News ID is required');
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $rules = [
            'title' => ['max_length' => 200],
            'summary' => ['max_length' => 500],
            'category' => ['max_length' => 50],
            'status' => ['custom' => function($value) {
                return in_array($value, ['draft', 'published']) ? true : 'Status must be draft or published';
            }]
        ];
        
        $errors = InputValidator::validate($input, $rules);
        if (!empty($errors)) {
            ResponseHelper::error('Validation failed', 400, $errors);
        }
        
        $data = InputValidator::sanitize($input);
        $currentUser = SessionManager::getCurrentUser();
        
        // Check if news exists and user has permission
        $checkSql = "SELECT author_id FROM news WHERE id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([$id]);
        $news = $checkStmt->fetch();
        
        if (!$news) {
            ResponseHelper::error('News not found', 404);
        }
        
        // Only admin or original author can edit
        if ($currentUser['role'] !== 'admin' && $news['author_id'] != $currentUser['id']) {
            ResponseHelper::error('Insufficient permissions', 403);
        }
        
        $updateFields = [];
        $params = [];
        
        foreach (['title', 'content', 'summary', 'category', 'image_url', 'status', 'is_featured'] as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $field === 'is_featured' ? (int)$data[$field] : $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            ResponseHelper::error('No fields to update');
        }
        
        $updateFields[] = "updated_at = NOW()";
        $params[] = $id;
        
        $sql = "UPDATE news SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            // Clear cache
            CacheHelper::clear();
            
            // Log activity
            ErrorLogger::log("News updated: ID $id by user {$currentUser['id']}", 'INFO');
            
            ResponseHelper::success(null, 'News updated successfully');
        } else {
            ErrorLogger::log("Failed to update news ID $id by user {$currentUser['id']}");
            ResponseHelper::error('Failed to update news', 500);
        }
    }
    
    private function deleteNews() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            ResponseHelper::error('News ID is required');
        }
        
        $currentUser = SessionManager::getCurrentUser();
        
        // Check if news exists
        $checkSql = "SELECT id, image_url FROM news WHERE id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([$id]);
        $news = $checkStmt->fetch();
        
        if (!$news) {
            ResponseHelper::error('News not found', 404);
        }
        
        $sql = "DELETE FROM news WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$id])) {
            // Delete associated image file
            if ($news['image_url']) {
                FileUploadHelper::delete("../uploads/" . basename($news['image_url']));
            }
            
            // Clear cache
            CacheHelper::clear();
            
            // Log activity
            ErrorLogger::log("News deleted: ID $id by user {$currentUser['id']}", 'INFO');
            
            ResponseHelper::success(null, 'News deleted successfully');
        } else {
            ErrorLogger::log("Failed to delete news ID $id by user {$currentUser['id']}");
            ResponseHelper::error('Failed to delete news', 500);
        }
    }
    
    private function toggleFeatured() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            ResponseHelper::error('News ID is required');
        }
        
        $sql = "UPDATE news SET is_featured = NOT is_featured, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$id])) {
            // Clear cache
            CacheHelper::clear();
            
            ResponseHelper::success(null, 'Featured status toggled successfully');
        } else {
            ResponseHelper::error('Failed to toggle featured status', 500);
        }
    }
    
    private function publishNews() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            ResponseHelper::error('News ID is required');
        }
        
        $sql = "UPDATE news SET status = 'published', updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$id])) {
            // Clear cache
            CacheHelper::clear();
            
            ResponseHelper::success(null, 'News published successfully');
        } else {
            ResponseHelper::error('Failed to publish news', 500);
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
$api = new NewsAPI();
$api->handleRequest();

?>
                break;
            case 'search':
                $this->searchNews();
                break;
            case 'categories':
                $this->getCategories();
                break;
            case 'stats':
                $this->getNewsStats();
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
                $this->updateNews();
                break;
            case 'toggle-featured':
                $this->toggleFeatured();
                break;
            case 'publish':
                $this->publishNews();
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
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $category = $_GET['category'] ?? null;
        $status = $_GET['status'] ?? 'published';
        $searchTerm = $_GET['search'] ?? '';
        
        // Build cache key
        $cacheKey = "news_list_" . md5(serialize([
            'page' => $page,
            'limit' => $limit,
            'category' => $category,
            'status' => $status,
            'search' => $searchTerm
        ]));
        
        // Try cache first
        $cached = CacheHelper::get($cacheKey);
        if ($cached !== null) {
            ResponseHelper::success($cached, 'News retrieved from cache');
        }
        
        $whereConditions = ["n.status = ?"];
        $params = [$status];
        
        if ($category) {
            $whereConditions[] = "n.category = ?";
            $params[] = $category;
        }
        
        if ($searchTerm) {
            $searchFields = ['n.title', 'n.content', 'n.summary'];
            list($searchWhere, $searchParams) = SearchHelper::buildWhereClause($searchTerm, $searchFields);
            if ($searchWhere) {
                $whereConditions[] = $searchWhere;
                $params = array_merge($params, $searchParams);
            }
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM news n WHERE $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get news items
        $sql = "
            SELECT 
                n.id,
                n.title,
                n.summary,
                n.content,
                n.category,
                n.image_url,
                n.is_featured,
                n.status,
                n.views,
                n.created_at,
                n.updated_at,
                u.name as author_name,
                u.username as author_username
            FROM news n
            LEFT JOIN users u ON n.author_id = u.id
            WHERE $whereClause
            ORDER BY n.is_featured DESC, n.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $news = $stmt->fetchAll();
        
        // Process results
        foreach ($news as &$item) {
            $item['created_ago'] = timeAgo($item['created_at']);
            $item['updated_ago'] = timeAgo($item['updated_at']);
            $item['excerpt'] = substr(strip_tags($item['content']), 0, 200) . '...';
            
            if ($searchTerm) {
                $item['title'] = SearchHelper::highlightResults($item['title'], $searchTerm);
                $item['summary'] = SearchHelper::highlightResults($item['summary'], $searchTerm);
            }
        }
        
        $result = [
            'items' => $news,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => (int)$total,
                'items_per_page' => $limit,
                'has_next' => $page < ceil($total / $limit),
                'has_prev' => $page > 1
            ]
        ];
        
        // Cache the result
        CacheHelper::set($cacheKey, $result, 300); // 5 minutes
        
        ResponseHelper::success($result, 'News retrieved successfully');
    }
    
    private function getNewsDetail() {
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            ResponseHelper::error('News ID is required');
        }
        
        // Increment view count
        $updateViewsSql = "UPDATE news SET views = views + 1 WHERE id = ?";
        $updateStmt = $this->db->prepare($updateViewsSql);
        $updateStmt->execute([$id]);
        
        $sql = "
            SELECT 
                n.*,
                u.name as author_name,
                u.username as author_username
            FROM news n
            LEFT JOIN users u ON n.author_id = u.id
            WHERE n.id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $news = $stmt->fetch();
        
        if (!$news) {
            ResponseHelper::error('News not found', 404);
        }
        
        $news['created_ago'] = timeAgo($news['created_at']);
        $news['updated_ago'] = timeAgo($news['updated_at']);
        
        ResponseHelper::success($news, 'News detail retrieved successfully');
    }
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
