<?php
/**
 * Get User Ratings Endpoint
 * Retrieves all ratings by the authenticated user
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
    "SELECT COUNT(*) as count FROM ratings WHERE user_id = ?",
    [$user['id']]
);
$total = $total['count'] ?? 0;

// Get user's ratings with content info
$sql = "SELECT 
    r.id, r.content_id, r.rating, r.review, r.created_at, r.updated_at,
    c.title as content_title, c.slug as content_slug, c.thumbnail,
    c.content_type, c.release_year
FROM ratings r
INNER JOIN content c ON r.content_id = c.id
WHERE r.user_id = ?
ORDER BY r.created_at DESC
LIMIT ? OFFSET ?";

$ratings = $db->fetchAll($sql, [$user['id'], $limit, $offset]);

// Get rating statistics for user
$userStats = $db->fetch(
    "SELECT 
        COUNT(*) as total_ratings,
        AVG(rating) as average_rating
    FROM ratings WHERE user_id = ?",
    [$user['id']]
);

Response::success('User ratings retrieved successfully', [
    'ratings' => $ratings,
    'statistics' => [
        'total_ratings' => (int)$userStats['total_ratings'],
        'average_rating' => round((float)($userStats['average_rating'] ?? 0), 2)
    ],
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]
], 200);

