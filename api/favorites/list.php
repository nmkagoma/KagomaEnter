<?php
/**
 * Favorites List Endpoint
 * Retrieves user's favorite content
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
$limit = (int)($_GET['limit'] ?? 12);

$offset = ($page - 1) * $limit;

// Get total count
$total = $db->fetch(
    "SELECT COUNT(*) as count FROM favorites WHERE user_id = ?",
    [$user['id']]
);
$total = $total['count'] ?? 0;

// Get favorites with content info
$sql = "SELECT 
    f.id, f.content_id, f.episode_id, f.created_at,
    c.id as content_id, c.title, c.slug, c.description, c.thumbnail,
    c.content_type, c.age_rating, c.rating, c.release_year,
    e.id as episode_id, e.title as episode_title, e.episode_number, e.season_number
FROM favorites f
LEFT JOIN content c ON f.content_id = c.id
LEFT JOIN episodes e ON f.episode_id = e.id
WHERE f.user_id = ?
ORDER BY f.created_at DESC
LIMIT ? OFFSET ?";

$favorites = $db->fetchAll($sql, [$user['id'], $limit, $offset]);

Response::success('Favorites retrieved successfully', [
    'favorites' => $favorites,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]
], 200);

