<?php
/**
 * Create Playlist Endpoint
 * Creates a new playlist
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
if (empty($input['title'])) {
    Response::validationError(['title' => 'Playlist title is required']);
}

$title = htmlspecialchars($input['title']);
$description = !empty($input['description']) ? htmlspecialchars($input['description']) : null;
$isPublic = (int)($input['is_public'] ?? 0);

// Check for duplicate title for this user
$existing = $db->fetch(
    "SELECT id FROM playlists WHERE user_id = ? AND title = ?",
    [$user['id'], $title]
);

if ($existing) {
    Response::error('You already have a playlist with this title', 409);
}

// Create playlist
$playlistData = [
    'user_id' => $user['id'],
    'title' => $title,
    'description' => $description,
    'thumbnail' => null,
    'is_public' => $isPublic,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

$playlistId = $db->insert('playlists', $playlistData);

if ($playlistId) {
    $playlist = $db->fetch("SELECT * FROM playlists WHERE id = ?", [$playlistId]);
    
    Response::success('Playlist created successfully', [
        'playlist' => $playlist
    ], 201);
} else {
    Response::error('Failed to create playlist', 500);
}

