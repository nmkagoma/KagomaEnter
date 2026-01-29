<?php
/**
 * Delete Content Endpoint
 * Soft deletes content by setting is_active to 0
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

// Check if admin
if ($user['role'] !== 'admin') {
    Response::error('Forbidden. Admin access required', 403);
}

// Get content ID from query string or body
$contentId = (int)($_GET['id'] ?? 0);
$input = json_decode(file_get_contents('php://input'), true);
if ($contentId === 0 && isset($input['id'])) {
    $contentId = (int)$input['id'];
}

if ($contentId === 0) {
    Response::validationError(['id' => 'Content ID is required']);
}

// Check if content exists
$content = $db->fetch(
    "SELECT id, title, thumbnail FROM content WHERE id = ?",
    [$contentId]
);

if (!$content) {
    Response::error('Content not found', 404);
}

// Soft delete - update is_active to 0
$deleted = $db->update('content',
    [
        'is_active' => 0,
        'updated_at' => date('Y-m-d H:i:s')
    ],
    'id = ?',
    [$contentId]
);

if ($deleted) {
    // Log the deletion
    error_log("Content deleted: ID {$contentId}, Title: {$content['title']}, By: {$user['id']}");
    
    Response::success('Content deleted successfully', [
        'content_id' => $contentId,
        'title' => $content['title']
    ], 200);
} else {
    Response::error('Failed to delete content', 500);
}

