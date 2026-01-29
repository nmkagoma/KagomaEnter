<?php
/**
 * Update Episode Endpoint
 * Updates an existing episode
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
$validation = new Validation();

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

// Get episode ID
$episodeId = (int)($input['id'] ?? $_GET['id'] ?? 0);

if ($episodeId === 0) {
    Response::validationError(['id' => 'Episode ID is required']);
}

// Check if episode exists
$episode = $db->fetch(
    "SELECT * FROM episodes WHERE id = ? AND is_active = 1",
    [$episodeId]
);

if (!$episode) {
    Response::error('Episode not found', 404);
}

// Prepare update data
$updateData = [];
$allowedFields = ['title', 'description', 'thumbnail', 'video_url', 'video_path', 'duration', 'is_premium', 'published_at'];

foreach ($allowedFields as $field) {
    if (isset($input[$field])) {
        if (in_array($field, ['title', 'description', 'thumbnail', 'video_url', 'video_path'])) {
            $updateData[$field] = htmlspecialchars($input[$field]);
        } elseif ($field === 'published_at') {
            $updateData[$field] = $input[$field];
        } else {
            $updateData[$field] = (int)$input[$field];
        }
    }
}

$updateData['updated_at'] = date('Y-m-d H:i:s');

// Handle season/episode number change
if (isset($input['season_number']) || isset($input['episode_number'])) {
    $seasonNum = (int)($input['season_number'] ?? $episode['season_number']);
    $episodeNum = (int)($input['episode_number'] ?? $episode['episode_number']);
    
    // Check for duplicate
    $existing = $db->fetch(
        "SELECT id FROM episodes 
        WHERE content_id = ? AND season_number = ? AND episode_number = ? 
        AND id != ? AND is_active = 1",
        [$episode['content_id'], $seasonNum, $episodeNum, $episodeId]
    );
    
    if ($existing) {
        Response::error('Episode with this number already exists in the specified season', 409);
    }
    
    $updateData['season_number'] = $seasonNum;
    $updateData['episode_number'] = $episodeNum;
}

// Update episode
$updated = $db->update('episodes', $updateData, 'id = ?', [$episodeId]);

if ($updated) {
    // Get updated episode
    $updatedEpisode = $db->fetch("SELECT * FROM episodes WHERE id = ?", [$episodeId]);
    
    Response::success('Episode updated successfully', [
        'episode' => $updatedEpisode
    ], 200);
} else {
    Response::error('Failed to update episode', 500);
}

