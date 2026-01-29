<?php
/**
 * Get My Channel Endpoint
 * Gets the authenticated user's channel
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

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

// Get user's channel
$channel = $db->fetch(
    "SELECT * FROM channels WHERE user_id = ? AND is_active = 1",
    [$user['id']]
);

if (!$channel) {
    Response::error('You do not have a channel yet. Create one first.', 404);
}

// Get channel stats
$stats = [
    'content_count' => 0,
    'total_views' => 0,
    'subscribers_count' => $channel['subscribers_count']
];

$contentStats = $db->fetch(
    "SELECT COUNT(*) as count, SUM(view_count) as total_views 
    FROM content WHERE uploaded_by = ? AND is_active = 1",
    [$user['id']]
);

if ($contentStats) {
    $stats['content_count'] = (int)$contentStats['count'];
    $stats['total_views'] = (int)($contentStats['total_views'] ?? 0);
}

// Get recent content
$recentContent = $db->fetchAll(
    "SELECT id, title, slug, thumbnail, content_type, rating, view_count, published_at
    FROM content 
    WHERE uploaded_by = ? AND is_active = 1
    ORDER BY published_at DESC
    LIMIT 10",
    [$user['id']]
);

Response::success('Channel retrieved successfully', [
    'channel' => $channel,
    'stats' => $stats,
    'recent_content' => $recentContent
], 200);

