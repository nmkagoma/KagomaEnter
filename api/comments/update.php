<?php
/**
 * Update Comment Endpoint
 * User updates their own comment
 */

// Include configuration and stubs for IDE intellisense
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/stubs.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Set content type
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow PUT requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Method not allowed', 405);
}

$db = Database::getInstance();
$auth = new Auth();

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$commentId = (int)($input['id'] ?? $_GET['id'] ?? 0);

if ($commentId === 0) {
    Response::validationError(['id' => 'Comment ID is required']);
}

if (empty($input['comment'])) {
    Response::validationError(['comment' => 'Comment text is required']);
}

// Check if comment exists and belongs to user
$comment = $db->fetch(
    "SELECT id, user_id FROM comments WHERE id = ?",
    [$commentId]
);

if (!$comment) {
    Response::error('Comment not found', 404);
}

if ($comment['user_id'] !== $user['id'] && $user['role'] !== 'admin') {
    Response::error('Forbidden. You can only update your own comments', 403);
}

// Update comment
$updated = $db->update('comments',
    [
        'comment' => htmlspecialchars($input['comment']),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    'id = ?',
    [$commentId]
);

if ($updated) {
    // Get updated comment
    $updatedComment = $db->fetch(
        "SELECT c.*, u.name as user_name 
        FROM comments c 
        INNER JOIN users u ON c.user_id = u.id 
        WHERE c.id = ?",
        [$commentId]
    );
    
    Response::success('Comment updated successfully', [
        'comment' => $updatedComment
    ], 200);
} else {
    Response::error('Failed to update comment', 500);
}

