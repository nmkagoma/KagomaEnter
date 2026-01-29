<?php
/**
 * Get Notifications List Endpoint
 * Retrieves user's notifications
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

// Get query parameters
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 20);
$unreadOnly = ($_GET['unread_only'] ?? 'false') === 'true';

$offset = ($page - 1) * $limit;

// Build query
$where = "WHERE n.user_id = ?";
$params = [$user['id']];

if ($unreadOnly) {
    $where .= " AND n.is_read = 0";
}

// Get total count
$total = $db->fetch(
    "SELECT COUNT(*) as count FROM notifications n $where",
    $params
);
$total = $total['count'] ?? 0;

// Get unread count
$unreadCount = $db->fetch(
    "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
    [$user['id']]
);
$unreadCount = $unreadCount['count'] ?? 0;

// Get notifications
$sql = "SELECT 
    n.id, n.type, n.title, n.message, n.data, n.is_read,
    n.read_at, n.created_at
FROM notifications n
$where
ORDER BY n.created_at DESC
LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

$notifications = $db->fetchAll($sql, $params);

// Decode data JSON
foreach ($notifications as &$notification) {
    $notification['data'] = json_decode($notification['data'] ?? '{}', true);
}

Response::success('Notifications retrieved successfully', [
    'notifications' => $notifications,
    'unread_count' => $unreadCount,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]
], 200);

