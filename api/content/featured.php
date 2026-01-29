<?php
/**
 * Get Featured Content Endpoint
 * Retrieves featured content for homepage
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
$limit = (int)($_GET['limit'] ?? 10);
$type = $_GET['type'] ?? null;

$offset = ($page - 1) * $limit;

// Build query
$where = "WHERE c.is_active = 1 AND c.is_featured = 1";
$params = [];

if ($type) {
    $where .= " AND c.content_type = ?";
    $params[] = $type;
}

// Get total count
$total = $db->fetch(
    "SELECT COUNT(*) as count FROM content c $where",
    $params
);
$total = $total['count'] ?? 0;

// Get featured content
$sql = "SELECT 
    c.id, c.title, c.slug, c.description, c.thumbnail, c.trailer_url,
    c.video_url, c.video_path, c.video_quality, c.duration, c.release_year,
    c.content_type, c.age_rating, c.rating, c.view_count, c.is_featured,
    c.is_premium, c.published_at,
    GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
FROM content c
LEFT JOIN content_genre cg ON c.id = cg.content_id
LEFT JOIN genres g ON cg.genre_id = g.id
$where
GROUP BY c.id
ORDER BY c.rating DESC, c.view_count DESC
LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

$featured = $db->fetchAll($sql, $params);

// Get content types for filtering
$contentTypes = $db->fetchAll(
    "SELECT id, name, slug, icon, color FROM content_types WHERE is_active = 1 ORDER BY sort_order"
);

Response::success('Featured content retrieved successfully', [
    'featured' => $featured,
    'content_types' => $contentTypes,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]
], 200);

