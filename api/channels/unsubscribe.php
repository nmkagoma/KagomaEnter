<?php
/**
 * Unsubscribe from Channel Endpoint
 * User unsubscribes from a channel
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
$channelId = (int)($input['channel_id'] ?? $_GET['channel_id'] ?? 0);

if ($channelId === 0) {
    Response::validationError(['channel_id' => 'Channel ID is required']);
}

// Check if channel exists
$channel = $db->fetch(
    "SELECT id, subscribers_count FROM channels WHERE id = ?",
    [$channelId]
);

if (!$channel) {
    Response::error('Channel not found', 404);
}

// Check subscription exists
$subscription = $db->fetch(
    "SELECT id FROM channel_subscriptions 
    WHERE user_id = ? AND channel_id = ?",
    [$user['id'], $channelId]
);

if (!$subscription) {
    Response::error('Not subscribed to this channel', 400);
}

// Unsubscribe
$deleted = $db->delete(
    "DELETE FROM channel_subscriptions 
    WHERE user_id = ? AND channel_id = ?",
    [$user['id'], $channelId]
);

if ($deleted) {
    // Decrement subscriber count
    $db->update('channels',
        ['subscribers_count' => max(0, $channel['subscribers_count'] - 1)],
        'id = ?',
        [$channelId]
    );
    
    Response::success('Unsubscribed successfully', null, 200);
} else {
    Response::error('Failed to unsubscribe', 500);
}

