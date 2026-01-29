<?php
/**
 * Add Item to Playlist Endpoint
 * Adds content or episode to playlist
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
if (empty($input['playlist_id'])) {
    Response::validationError(['playlist_id' => 'Playlist ID is required']);
}

if (empty($input['content_id']) && empty($input['episode_id'])) {
    Response::validationError([
        'content_id' => 'Content ID or episode ID is required',
        'episode_id' => 'Content ID or episode ID is required'
    ]);
}

$playlistId = (int)$input['playlist_id'];
$contentId = !empty($input['content_id']) ? (int)$input['content_id'] : null;
$episodeId = !empty($input['episode_id']) ? (int)$input['episode_id'] : null;

// Check if playlist exists and belongs to user
$playlist = $db->fetch(
    "SELECT id, user_id FROM playlists WHERE id = ?",
    [$playlistId]
);

if (!$playlist) {
    Response::error('Playlist not found', 404);
}

if ($playlist['user_id'] !== $user['id']) {
    Response::error('Forbidden. You can only add items to your own playlists', 403);
}

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

// Check if already in playlist
$existing = $db->fetch(
    "SELECT id FROM playlist_items 
    WHERE playlist_id = ? AND content_id = ? AND episode_id = ?",
    [$playlistId, $contentId ?? 0, $episodeId ?? 0]
);

if ($existing) {
    Response::error('Item already in playlist', 409);
}

// Get max order
$maxOrder = $db->fetch(
    "SELECT MAX(`order`) as max_order FROM playlist_items WHERE playlist_id = ?",
    [$playlistId]
);
$maxOrder = (int)($maxOrder['max_order'] ?? 0);

// Add item
$itemData = [
    'playlist_id' => $playlistId,
    'content_id' => $contentId,
    'episode_id' => $episodeId,
    'order' => $maxOrder + 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

$itemId = $db->insert('playlist_items', $itemData);

if ($itemId) {
    Response::success('Item added to playlist', [
        'item_id' => $itemId
    ], 201);
} else {
    Response::error('Failed to add item to playlist', 500);
}

