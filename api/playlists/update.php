<?php
/**
 * Update Playlist Endpoint
 * Updates playlist details
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
$playlistId = (int)($input['id'] ?? $_GET['id'] ?? 0);

if ($playlistId === 0) {
    Response::validationError(['id' => 'Playlist ID is required']);
}

// Check if playlist exists and belongs to user
$playlist = $db->fetch(
    "SELECT id, user_id FROM playlists WHERE id = ?",
    [$playlistId]
);

if (!$playlist) {
    Response::error('Playlist not found', 404);
}

if ($playlist['user_id'] !== $user['id']) {
    Response::error('Forbidden. You can only update your own playlists', 403);
}

// Prepare update data
$updateData = [];
$allowedFields = ['title', 'description', 'thumbnail', 'is_public'];

foreach ($allowedFields as $field) {
    if (isset($input[$field])) {
        if (in_array($field, ['title', 'description', 'thumbnail'])) {
            $updateData[$field] = htmlspecialchars($input[$field]);
        } else {
            $updateData[$field] = (int)$input[$field];
        }
    }
}

$updateData['updated_at'] = date('Y-m-d H:i:s');

// Update playlist
$updated = $db->update('playlists', $updateData, 'id = ?', [$playlistId]);

if ($updated) {
    $updatedPlaylist = $db->fetch("SELECT * FROM playlists WHERE id = ?", [$playlistId]);
    
    Response::success('Playlist updated successfully', [
        'playlist' => $updatedPlaylist
    ], 200);
} else {
    Response::error('Failed to update playlist', 500);
}

