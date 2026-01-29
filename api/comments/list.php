<?php
/**
 * List Comments Endpoint
 * Retrieves comments for content or episode
 */

// Include configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Set content type
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

try {
    // Initialize database and auth
    $db = Database::getInstance();
    $auth = new Auth();

    // Get query parameters
    $contentId = isset($_GET['content_id']) ? (int)$_GET['content_id'] : 0;
    $episodeId = isset($_GET['episode_id']) ? (int)$_GET['episode_id'] : 0;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest'; // latest, popular

    // Validate pagination
    $limit = min(max($limit, 1), 100); // Between 1-100
    $page = max($page, 1);
    $offset = ($page - 1) * $limit;

    if ($contentId === 0 && $episodeId === 0) {
        Response::validationError([
            'content_id' => 'Content ID or episode ID is required',
            'episode_id' => 'Content ID or episode ID is required'
        ]);
    }

    // Build query - comments table doesn't have is_active column
    $where = "WHERE c.parent_id IS NULL";
    $params = [];

    if ($contentId > 0) {
        $where .= " AND c.content_id = ?";
        $params[] = $contentId;
    }

    if ($episodeId > 0) {
        $where .= " AND c.episode_id = ?";
        $params[] = $episodeId;
    }

    // Get total count
    $total = $db->fetch(
        "SELECT COUNT(*) as count FROM comments c $where",
        $params
    );
    $total = $total['count'] ?? 0;

    // Get order by clause
    $orderBy = "c.created_at DESC";
    if ($sort === 'popular') {
        $orderBy = "c.likes_count DESC, c.created_at DESC";
    }

    // Build and execute main query
    $sql = "SELECT 
        c.id, c.user_id, c.content_id, c.episode_id, c.parent_id,
        c.comment, c.likes_count, c.dislikes_count, c.is_pinned,
        c.created_at, c.updated_at,
        u.name as user_name, u.id as user_id, u.avatar_url as user_avatar
    FROM comments c
    INNER JOIN users u ON c.user_id = u.id
    $where
    ORDER BY $orderBy, c.created_at DESC
    LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;

    $comments = $db->fetchAll($sql, $params);

    // Get reply counts and user likes for each comment
    foreach ($comments as &$comment) {
        // Get replies count
        $repliesCount = $db->fetch(
            "SELECT COUNT(*) as count FROM comments WHERE parent_id = ?",
            [$comment['id']]
        );
        $comment['replies_count'] = (int)($repliesCount['count'] ?? 0);
        
        // Get user avatar URL
        $comment['user_avatar'] = $comment['user_avatar'] ?? 'assets/images/default-avatar.png';
    }
    unset($comment); // Break reference

    // Get current user's likes (for authenticated users)
    $user = $auth->getCurrentUser();
    if ($user && !empty($comments)) {
        $commentIds = array_column($comments, 'id');
        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
        $userLikes = $db->fetchAll(
            "SELECT comment_id, type FROM comment_likes 
            WHERE user_id = ? AND comment_id IN ($placeholders)",
            array_merge([$user['id']], $commentIds)
        );
        
        $likesMap = [];
        foreach ($userLikes as $like) {
            $likesMap[$like['comment_id']] = $like['type'];
        }
        
        foreach ($comments as &$comment) {
            $comment['user_like'] = $likesMap[$comment['id']] ?? null;
        }
        unset($comment); // Break reference
    }

    Response::success('Comments retrieved successfully', [
        'comments' => $comments ?: [],
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $limit > 0 ? ceil($total / $limit) : 0
        ]
    ], 200);

} catch (Exception $e) {
    // Log the error for debugging
    error_log('Comments List API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    // Return proper error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load comments',
        'debug' => (defined('ENVIRONMENT') && ENVIRONMENT === 'development') ? $e->getMessage() : null
    ], JSON_PRETTY_PRINT);
    exit;
}

