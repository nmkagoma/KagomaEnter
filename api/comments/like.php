<?php
/**
 * Like/Dislike Comment Endpoint
 * User likes or dislikes a comment
 */

// Include configuration and stubs for IDE intellisense
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/stubs.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Validation.php';
require_once __DIR__ . '/../../includes/Auth.php';

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
if (empty($input['comment_id'])) {
    Response::validationError(['comment_id' => 'Comment ID is required']);
}

if (empty($input['type']) || !in_array($input['type'], ['like', 'dislike'])) {
    Response::validationError(['type' => 'Type must be like or dislike']);
}

$commentId = (int)$input['comment_id'];
$type = $input['type'];

// Check if comment exists
$comment = $db->fetch(
    "SELECT id, likes_count, dislikes_count FROM comments WHERE id = ?",
    [$commentId]
);

if (!$comment) {
    Response::error('Comment not found', 404);
}

// Check existing like/dislike
$existing = $db->fetch(
    "SELECT id, type FROM comment_likes WHERE user_id = ? AND comment_id = ?",
    [$user['id'], $commentId]
);

if ($existing) {
    if ($existing['type'] === $type) {
        // Remove like/dislike (toggle off)
        $db->delete(
            "DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?",
            [$user['id'], $commentId]
        );
        
        if ($type === 'like') {
            $db->update('comments',
                ['likes_count' => max(0, $comment['likes_count'] - 1)],
                'id = ?',
                [$commentId]
            );
        } else {
            $db->update('comments',
                ['dislikes_count' => max(0, $comment['dislikes_count'] - 1)],
                'id = ?',
                [$commentId]
            );
        }
        
        Response::success(ucfirst($type) . ' removed', null, 200);
    } else {
        // Switch from like to dislike or vice versa
        $db->update('comment_likes',
            ['type' => $type, 'updated_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$existing['id']]
        );
        
        if ($type === 'like') {
            $db->update('comments',
                ['likes_count' => $comment['likes_count'] + 1, 
                 'dislikes_count' => max(0, $comment['dislikes_count'] - 1)],
                'id = ?',
                [$commentId]
            );
        } else {
            $db->update('comments',
                ['likes_count' => max(0, $comment['likes_count'] - 1), 
                 'dislikes_count' => $comment['dislikes_count'] + 1],
                'id = ?',
                [$commentId]
            );
        }
        
        Response::success('Changed to ' . $type, null, 200);
    }
} else {
    // Add new like/dislike
    $db->insert('comment_likes', [
        'user_id' => $user['id'],
        'comment_id' => $commentId,
        'type' => $type,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($type === 'like') {
        $db->update('comments',
            ['likes_count' => $comment['likes_count'] + 1],
            'id = ?',
            [$commentId]
        );
    } else {
        $db->update('comments',
            ['dislikes_count' => $comment['dislikes_count'] + 1],
            'id = ?',
            [$commentId]
        );
    }
    
    Response::success('Added ' . $type, null, 201);
}

