<?php
/**
 * Get Playlist Details Endpoint
 * Retrieves playlist details with items
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
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$db = Database::getInstance();
$auth = new Auth();

// Get playlist ID
$playlistId = (int)($_GET['id'] ?? 0);

if ($playlistId === 0) {
    Response::validationError(['id' => 'Playlist ID is required']);
}

// Get current user
$user = $auth->getCurrentUser();

// Get playlist
$sql = "SELECT 
    p.id, p.user_id, p.title, p.description, p.thumbnail, 
    p.is_public, p.created_at, p.updated_at,
    u.name as owner_name
FROM playlists p
INNER JOIN users u ON p.user_id = u.id
WHERE p.id = ?";

$playlist = $db->fetch($sql, [$playlistId]);

if (!$playlist) {
    Response::error('Playlist not found', 404);
}

// Check access
if (!$playlist['is_public'] && (!$user || $playlist['user_id'] !== $user['id'])) {
    Response::error('Forbidden. This playlist is private', 403);
}

// Get playlist items
$items = $db->fetchAll(
    "SELECT 
        pi.id, pi.playlist_id, pi.content_id, pi.episode_id, pi.`order`,
        pi.created_at,
        c.id as content_id, c.title as content_title, c.slug as content_slug,
        c.thumbnail as content_thumbnail, c.content_type, c.duration,
        e.id as episode_id, e.title as episode_title, 
        e.episode_number, e.season_number
    FROM playlist_items pi
    LEFT JOIN content c ON pi.content_id = c.id
    LEFT JOIN episodes e ON pi.episode_id = e.id
    WHERE pi.playlist_id = ?
    ORDER BY pi.`order` ASC, pi.created_at ASC",
    [$playlistId]
);

// Get owner info
$isOwner = $user && $playlist['user_id'] === $user['id'];

Response::success('Playlist retrieved successfully', [
    'playlist' => [
        'id' => $playlist['id'],
        'title' => $playlist['title'],
        'description' => $playlist['description'],
        'thumbnail' => $playlist['thumbnail'],
        'is_public' => $playlist['is_public'],
        'owner' => [
            'id' => $playlist['user_id'],
            'name' => $playlist['owner_name']
        ],
        'created_at' => $playlist['created_at'],
        'updated_at' => $playlist['updated_at']
    ],
    'items' => $items,
    'total_items' => count($items),
    'is_owner' => $isOwner
], 200);

