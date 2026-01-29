<?php
/**
 * List Channels Endpoint
 * Retrieves public channels
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

// Get query parameters
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 12);
$sort = $_GET['sort'] ?? 'subscribers'; // subscribers, recent

$offset = ($page - 1) * $limit;

// Get total count
$total = $db->fetch(
    "SELECT COUNT(*) as count FROM channels WHERE is_active = 1"
);
$total = $total['count'] ?? 0;

// Get channels
$orderBy = "c.subscribers_count DESC";
if ($sort === 'recent') {
    $orderBy = "c.created_at DESC";
}

$sql = "SELECT 
    c.id, c.user_id, c.name, c.slug, c.description, c.avatar, 
    c.banner, c.subscribers_count, c.is_verified, c.is_active,
    c.created_at, c.updated_at,
    u.name as owner_name
FROM channels c
INNER JOIN users u ON c.user_id = u.id
WHERE c.is_active = 1
ORDER BY $orderBy
LIMIT ? OFFSET ?";

$channels = $db->fetchAll($sql, [$limit, $offset]);

// Get current user's subscription status
$user = $auth->getCurrentUser();
if ($user) {
    $channelIds = array_column($channels, 'id');
    if (!empty($channelIds)) {
        $placeholders = implode(',', array_fill(0, count($channelIds), '?'));
        $userSubscriptions = $db->fetchAll(
            "SELECT channel_id FROM channel_subscriptions 
            WHERE user_id = ? AND channel_id IN ($placeholders)",
            array_merge([$user['id']], $channelIds)
        );
        
        $subscribedIds = array_column($userSubscriptions, 'channel_id');
        
        foreach ($channels as &$channel) {
            $channel['is_subscribed'] = in_array($channel['id'], $subscribedIds);
        }
    }
}

Response::success('Channels retrieved successfully', [
    'channels' => $channels,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]
], 200);

