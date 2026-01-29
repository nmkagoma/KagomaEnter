<?php
/**
 * Get Channel Details Endpoint
 * Retrieves channel details with content
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

// Get channel ID or slug
$channelId = (int)($_GET['id'] ?? 0);
$slug = $_GET['slug'] ?? null;

if ($channelId === 0 && !$slug) {
    Response::validationError(['id' => 'Channel ID or slug is required']);
}

// Get channel
if ($channelId > 0) {
    $channel = $db->fetch(
        "SELECT c.*, u.name as owner_name 
        FROM channels c 
        INNER JOIN users u ON c.user_id = u.id 
        WHERE c.id = ? AND c.is_active = 1",
        [$channelId]
    );
} else {
    $channel = $db->fetch(
        "SELECT c.*, u.name as owner_name 
        FROM channels c 
        INNER JOIN users u ON c.user_id = u.id 
        WHERE c.slug = ? AND c.is_active = 1",
        [$slug]
    );
}

if (!$channel) {
    Response::error('Channel not found', 404);
}

// Get current user
$user = $auth->getCurrentUser();
$isOwner = $user && $channel['user_id'] === $user['id'];
$isSubscribed = false;

if ($user && !$isOwner) {
    $subscription = $db->fetch(
        "SELECT id FROM channel_subscriptions 
        WHERE user_id = ? AND channel_id = ?",
        [$user['id'], $channel['id']]
    );
    $isSubscribed = $subscription !== false;
}

// Get channel content
$content = $db->fetchAll(
    "SELECT id, title, slug, thumbnail, content_type, rating, view_count
    FROM content 
    WHERE uploaded_by = ? AND is_active = 1
    ORDER BY published_at DESC
    LIMIT 20",
    [$channel['user_id']]
);

Response::success('Channel retrieved successfully', [
    'channel' => [
        'id' => $channel['id'],
        'name' => $channel['name'],
        'slug' => $channel['slug'],
        'description' => $channel['description'],
        'avatar' => $channel['avatar'],
        'banner' => $channel['banner'],
        'subscribers_count' => $channel['subscribers_count'],
        'is_verified' => $channel['is_verified'],
        'owner' => [
            'id' => $channel['user_id'],
            'name' => $channel['owner_name']
        ],
        'created_at' => $channel['created_at']
    ],
    'content' => $content,
    'user_status' => [
        'is_owner' => $isOwner,
        'is_subscribed' => $isSubscribed
    ]
], 200);

