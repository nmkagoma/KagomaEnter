<?php
/**
 * Update Channel Endpoint
 * Updates channel details
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
$channelId = (int)($input['id'] ?? 0);

// If no ID provided, get user's channel
if ($channelId === 0) {
    $channel = $db->fetch(
        "SELECT id FROM channels WHERE user_id = ? AND is_active = 1",
        [$user['id']]
    );
    if ($channel) {
        $channelId = $channel['id'];
    }
}

if ($channelId === 0) {
    Response::error('Channel not found', 404);
}

// Check if channel belongs to user
$channel = $db->fetch(
    "SELECT id, user_id FROM channels WHERE id = ? AND is_active = 1",
    [$channelId]
);

if (!$channel) {
    Response::error('Channel not found', 404);
}

if ($channel['user_id'] !== $user['id']) {
    Response::error('Forbidden. You can only update your own channel', 403);
}

// Prepare update data
$updateData = [];
$allowedFields = ['name', 'description', 'avatar', 'banner'];

foreach ($allowedFields as $field) {
    if (isset($input[$field])) {
        $updateData[$field] = htmlspecialchars($input[$field]);
    }
}

$updateData['updated_at'] = date('Y-m-d H:i:s');

// Update slug if name changed
if (!empty($input['name'])) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['name'])));
    $originalSlug = $slug;
    $counter = 1;
    
    while ($db->fetch("SELECT id FROM channels WHERE slug = ? AND id != ?", [$slug, $channelId])) {
        $slug = $originalSlug . '-' . $counter++;
    }
    
    $updateData['slug'] = $slug;
}

// Update channel
$updated = $db->update('channels', $updateData, 'id = ?', [$channelId]);

if ($updated) {
    $updatedChannel = $db->fetch(
        "SELECT c.*, u.name as owner_name 
        FROM channels c 
        INNER JOIN users u ON c.user_id = u.id 
        WHERE c.id = ?",
        [$channelId]
    );
    
    Response::success('Channel updated successfully', [
        'channel' => $updatedChannel
    ], 200);
} else {
    Response::error('Failed to update channel', 500);
}

