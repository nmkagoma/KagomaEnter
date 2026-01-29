<?php
/**
 * Delete Playlist Endpoint
 * Deletes a playlist
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

// Get playlist ID
$input = json_decode(file_get_contents('php://input'), true);
$playlistId = (int)($input['id'] ?? $_GET['id'] ?? 0);

if ($playlistId === 0) {
    Response::validationError(['id' => 'Playlist ID is required']);
}

// Check if playlist exists and belongs to user
$playlist = $db->fetch(
    "SELECT id, user_id, title FROM playlists WHERE id = ?",
    [$playlistId]
);

if (!$playlist) {
    Response::error('Playlist not found', 404);
}

if ($playlist['user_id'] !== $user['id'] && $user['role'] !== 'admin') {
    Response::error('Forbidden. You can only delete your own playlists', 403);
}

// Delete playlist (cascade deletes items)
$deleted = $db->delete(
    "DELETE FROM playlists WHERE id = ?",
    [$playlistId]
);

if ($deleted) {
    Response::success('Playlist deleted successfully', [
        'playlist_id' => $playlistId,
        'title' => $playlist['title']
    ], 200);
} else {
    Response::error('Failed to delete playlist', 500);
}

