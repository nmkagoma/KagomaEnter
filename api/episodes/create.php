<?php
/**
 * Create Episode Endpoint
 * Creates a new episode for a series
 */

// Include configuration and stubs for IDE intellisense
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/stubs.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Validation.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Upload.php';

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
$upload = new Upload();

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

// Check if admin or uploader
if ($user['role'] !== 'admin' && $user['role'] !== 'uploader') {
    Response::error('Forbidden. Admin or uploader access required', 403);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$errors = [];

if (empty($input['content_id'])) {
    $errors['content_id'] = 'Content ID is required';
}

if (empty($input['title'])) {
    $errors['title'] = 'Title is required';
}

if (!isset($input['episode_number'])) {
    $errors['episode_number'] = 'Episode number is required';
}

if (!empty($errors)) {
    Response::validationError($errors);
}

// Check if content exists and is a series
$content = $db->fetch(
    "SELECT id, title, content_type FROM content WHERE id = ? AND is_active = 1",
    [$input['content_id']]
);

if (!$content) {
    Response::error('Content not found', 404);
}

if ($content['content_type'] !== 'series') {
    Response::error('Episodes can only be created for series content', 400);
}

// Check for duplicate episode number in same season
$existingEpisode = $db->fetch(
    "SELECT id FROM episodes 
    WHERE content_id = ? AND season_number = ? AND episode_number = ? AND is_active = 1",
    [$input['content_id'], $input['season_number'] ?? 1, $input['episode_number']]
);

if ($existingEpisode) {
    Response::error('Episode with this number already exists in the specified season', 409);
}

// Prepare episode data
$episodeData = [
    'content_id' => (int)$input['content_id'],
    'season_number' => (int)($input['season_number'] ?? 1),
    'episode_number' => (int)$input['episode_number'],
    'title' => htmlspecialchars($input['title']),
    'description' => !empty($input['description']) ? htmlspecialchars($input['description']) : null,
    'thumbnail' => !empty($input['thumbnail']) ? $input['thumbnail'] : null,
    'video_url' => !empty($input['video_url']) ? $input['video_url'] : null,
    'video_path' => !empty($input['video_path']) ? $input['video_path'] : null,
    'duration' => (int)($input['duration'] ?? 0),
    'view_count' => 0,
    'is_premium' => (int)($input['is_premium'] ?? 0),
    'is_active' => 1,
    'published_at' => !empty($input['published_at']) ? $input['published_at'] : date('Y-m-d H:i:s'),
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

// Insert episode
$episodeId = $db->insert('episodes', $episodeData);

if ($episodeId) {
    // Get the created episode
    $episode = $db->fetch("SELECT * FROM episodes WHERE id = ?", [$episodeId]);
    
    Response::success('Episode created successfully', [
        'episode' => $episode
    ], 201);
} else {
    Response::error('Failed to create episode', 500);
}

