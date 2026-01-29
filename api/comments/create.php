<?php
/**
 * Create Comment Endpoint
 * User creates a comment on content or episode
 */

// Include configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Validation class (inline if not exists)
if (!class_exists('Validation')) {
    class Validation {
        public function required($value) { return !empty($value); }
        public function email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL) !== false; }
    }
}


// Set content type
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$db = Database::getInstance();
$auth = new Auth();
$validation = new Validation();

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

// Validate
if (empty($input['comment'])) {
    Response::validationError(['comment' => 'Comment text is required']);
}

if (empty($input['content_id']) && empty($input['episode_id'])) {
    Response::validationError([
        'content_id' => 'Content ID or episode ID is required',
        'episode_id' => 'Content ID or episode ID is required'
    ]);
}

$contentId = !empty($input['content_id']) ? (int)$input['content_id'] : null;
$episodeId = !empty($input['episode_id']) ? (int)$input['episode_id'] : null;

// Validate content exists if provided
if ($contentId) {
    $content = $db->fetch(
        "SELECT id FROM content WHERE id = ? AND is_active = 1",
        [$contentId]
    );
    if (!$content) {
        Response::error('Content not found', 404);
    }
}

// Validate episode exists if provided
if ($episodeId) {
    $episode = $db->fetch(
        "SELECT id FROM episodes WHERE id = ? AND is_active = 1",
        [$episodeId]
    );
    if (!$episode) {
        Response::error('Episode not found', 404);
    }
}

// Validate parent comment if reply
$parentId = !empty($input['parent_id']) ? (int)$input['parent_id'] : null;
if ($parentId) {
    $parent = $db->fetch(
        "SELECT id FROM comments WHERE id = ?",
        [$parentId]
    );
    if (!$parent) {
        Response::error('Parent comment not found', 404);
    }
}

// Create comment
$commentData = [
    'user_id' => $user['id'],
    'content_id' => $contentId,
    'episode_id' => $episodeId,
    'parent_id' => $parentId,
    'comment' => htmlspecialchars($input['comment']),
    'likes_count' => 0,
    'dislikes_count' => 0,
    'is_pinned' => 0,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

$commentId = $db->insert('comments', $commentData);

if ($commentId) {
    // Get created comment with user info
    $comment = $db->fetch(
        "SELECT c.*, u.name as user_name, u.id as user_id 
        FROM comments c 
        INNER JOIN users u ON c.user_id = u.id 
        WHERE c.id = ?",
        [$commentId]
    );
    
    Response::success('Comment created successfully', [
        'comment' => $comment
    ], 201);
} else {
    Response::error('Failed to create comment', 500);
}

