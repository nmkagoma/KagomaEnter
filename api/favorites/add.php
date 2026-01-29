<?php
/**
 * Add to Favorites Endpoint
 * Adds content or episode to user's favorites
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

// Check if already in favorites
$existing = $db->fetch(
    "SELECT id FROM favorites WHERE user_id = ? AND content_id = ? AND episode_id = ?",
    [$user['id'], $contentId ?? 0, $episodeId ?? 0]
);

if ($existing) {
    Response::error('Already in favorites', 409);
}

// Add to favorites
$favoriteData = [
    'user_id' => $user['id'],
    'content_id' => $contentId,
    'episode_id' => $episodeId,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

$favoriteId = $db->insert('favorites', $favoriteData);

if ($favoriteId) {
    Response::success('Added to favorites', [
        'favorite_id' => $favoriteId
    ], 201);
} else {
    Response::error('Failed to add to favorites', 500);
}

