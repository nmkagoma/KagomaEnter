<?php
/**
 * Create Channel Endpoint
 * Creates a new channel for the user
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

// Check if user already has a channel
$existingChannel = $db->fetch(
    "SELECT id FROM channels WHERE user_id = ? AND is_active = 1",
    [$user['id']]
);

if ($existingChannel) {
    Response::error('You already have a channel. Update it instead.', 409);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

// Validate
if (empty($input['name'])) {
    Response::validationError(['name' => 'Channel name is required']);
}

if (strlen($input['name']) < 3) {
    Response::validationError(['name' => 'Channel name must be at least 3 characters']);
}

// Generate slug
$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['name'])));
$originalSlug = $slug;
$counter = 1;

while ($db->fetch("SELECT id FROM channels WHERE slug = ?", [$slug])) {
    $slug = $originalSlug . '-' . $counter++;
}

// Create channel
$channelData = [
    'user_id' => $user['id'],
    'name' => htmlspecialchars($input['name']),
    'slug' => $slug,
    'description' => !empty($input['description']) ? htmlspecialchars($input['description']) : null,
    'avatar' => !empty($input['avatar']) ? $input['avatar'] : null,
    'banner' => !empty($input['banner']) ? $input['banner'] : null,
    'subscribers_count' => 0,
    'is_verified' => 0,
    'is_active' => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

$channelId = $db->insert('channels', $channelData);

if ($channelId) {
    $channel = $db->fetch("SELECT * FROM channels WHERE id = ?", [$channelId]);
    
    Response::success('Channel created successfully', [
        'channel' => $channel
    ], 201);
} else {
    Response::error('Failed to create channel', 500);
}

