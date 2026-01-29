<?php
/**
 * Remove Item from Playlist Endpoint
 * Removes item from playlist
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
$itemId = (int)($input['id'] ?? $_GET['id'] ?? 0);

if ($itemId === 0) {
    Response::validationError(['id' => 'Item ID is required']);
}

// Check if item exists and belongs to user's playlist
$item = $db->fetch(
    "SELECT pi.id, pi.playlist_id, p.user_id 
    FROM playlist_items pi 
    INNER JOIN playlists p ON pi.playlist_id = p.id 
    WHERE pi.id = ?",
    [$itemId]
);

if (!$item) {
    Response::error('Item not found', 404);
}

if ($item['user_id'] !== $user['id']) {
    Response::error('Forbidden. You can only remove items from your own playlists', 403);
}

// Delete item
$deleted = $db->delete(
    "DELETE FROM playlist_items WHERE id = ?",
    [$itemId]
);

if ($deleted) {
    Response::success('Item removed from playlist', null, 200);
} else {
    Response::error('Failed to remove item', 500);
}

