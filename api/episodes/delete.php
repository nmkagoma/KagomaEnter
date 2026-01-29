<?php
/**
 * Delete Episode Endpoint
 * Soft deletes an episode
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

// Check if admin or uploader
if ($user['role'] !== 'admin' && $user['role'] !== 'uploader') {
    Response::error('Forbidden. Admin or uploader access required', 403);
}

// Get episode ID
$episodeId = (int)($_GET['id'] ?? 0);
$input = json_decode(file_get_contents('php://input'), true);
if ($episodeId === 0 && isset($input['id'])) {
    $episodeId = (int)$input['id'];
}

if ($episodeId === 0) {
    Response::validationError(['id' => 'Episode ID is required']);
}

// Check if episode exists
$episode = $db->fetch(
    "SELECT id, title FROM episodes WHERE id = ? AND is_active = 1",
    [$episodeId]
);

if (!$episode) {
    Response::error('Episode not found', 404);
}

// Soft delete
$deleted = $db->update('episodes',
    [
        'is_active' => 0,
        'updated_at' => date('Y-m-d H:i:s')
    ],
    'id = ?',
    [$episodeId]
);

if ($deleted) {
    Response::success('Episode deleted successfully', [
        'episode_id' => $episodeId,
        'title' => $episode['title']
    ], 200);
} else {
    Response::error('Failed to delete episode', 500);
}

