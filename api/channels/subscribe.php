<?php
/**
 * Subscribe to Channel Endpoint
 * User subscribes to a channel
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

if (empty($input['channel_id'])) {
    Response::validationError(['channel_id' => 'Channel ID is required']);
}

$channelId = (int)$input['channel_id'];

// Check if channel exists
$channel = $db->fetch(
    "SELECT id, user_id, name FROM channels WHERE id = ? AND is_active = 1",
    [$channelId]
);

if (!$channel) {
    Response::error('Channel not found', 404);
}

// Can't subscribe to own channel
if ($channel['user_id'] === $user['id']) {
    Response::error('You cannot subscribe to your own channel', 400);
}

// Check if already subscribed
$existing = $db->fetch(
    "SELECT id FROM channel_subscriptions 
    WHERE user_id = ? AND channel_id = ?",
    [$user['id'], $channelId]
);

if ($existing) {
    Response::error('Already subscribed to this channel', 409);
}

// Subscribe
$subscriptionData = [
    'user_id' => $user['id'],
    'channel_id' => $channelId,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

$subscriptionId = $db->insert('channel_subscriptions', $subscriptionData);

if ($subscriptionId) {
    // Increment subscriber count
    $db->update('channels',
        ['subscribers_count' => $channel['subscribers_count'] + 1],
        'id = ?',
        [$channelId]
    );
    
    // Create notification for channel owner
    $db->insert('notifications', [
        'user_id' => $channel['user_id'],
        'type' => 'subscription',
        'title' => 'New Subscriber',
        'message' => $user['name'] . ' subscribed to your channel',
        'data' => json_encode(['channel_id' => $channelId, 'subscriber_id' => $user['id']]),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    Response::success('Subscribed successfully', [
        'subscription_id' => $subscriptionId
    ], 201);
} else {
    Response::error('Failed to subscribe', 500);
}

