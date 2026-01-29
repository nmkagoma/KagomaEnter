<?php
/**
 * Remove from Favorites Endpoint
 * Removes content or episode from user's favorites
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

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$favoriteId = (int)($input['id'] ?? $_GET['id'] ?? 0);
$contentId = !empty($input['content_id']) ? (int)$input['content_id'] : null;
$episodeId = !empty($input['episode_id']) ? (int)$input['episode_id'] : null;

if ($favoriteId > 0) {
    // Delete by ID
    $deleted = $db->delete(
        "DELETE FROM favorites WHERE id = ? AND user_id = ?",
        [$favoriteId, $user['id']]
    );
} elseif ($contentId !== null || $episodeId !== null) {
    // Delete by content/episode
    $deleted = $db->delete(
        "DELETE FROM favorites WHERE user_id = ? AND content_id = ? AND episode_id = ?",
        [$user['id'], $contentId ?? 0, $episodeId ?? 0]
    );
} else {
    Response::validationError([
        'id' => 'Favorite ID or content/episode ID is required'
    ]);
}

if ($deleted) {
    Response::success('Removed from favorites', null, 200);
} else {
    Response::error('Favorite not found', 404);
}

