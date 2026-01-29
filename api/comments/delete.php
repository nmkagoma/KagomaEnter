<?php
/**
 * Delete Comment Endpoint
 * User deletes their own comment (or admin deletes any)
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
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Method not allowed', 405);
}

$db = Database::getInstance();
$auth = new Auth();

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

// Get comment ID
$input = json_decode(file_get_contents('php://input'), true);
$commentId = (int)($input['id'] ?? $_GET['id'] ?? 0);

if ($commentId === 0) {
    Response::validationError(['id' => 'Comment ID is required']);
}

// Check if comment exists
$comment = $db->fetch(
    "SELECT id, user_id FROM comments WHERE id = ?",
    [$commentId]
);

if (!$comment) {
    Response::error('Comment not found', 404);
}

if ($comment['user_id'] !== $user['id'] && $user['role'] !== 'admin') {
    Response::error('Forbidden. You can only delete your own comments', 403);
}

// Delete comment (cascades to replies due to foreign key)
$deleted = $db->delete(
    "DELETE FROM comments WHERE id = ? OR parent_id = ?",
    [$commentId, $commentId]
);

if ($deleted) {
    Response::success('Comment deleted successfully', [
        'deleted_count' => $deleted
    ], 200);
} else {
    Response::error('Failed to delete comment', 500);
}

