<?php
/**
 * Get Shorts Preview Endpoint
 * Retrieves short-form content for homepage preview (duration < 5 min)
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
$limit = (int)($_GET['limit'] ?? 6);
$page = (int)($_GET['page'] ?? 1);

$offset = ($page - 1) * $limit;

// Get total count for shorts
$total = $db->fetch(
    "SELECT COUNT(*) as count FROM content WHERE is_active = 1 AND content_type = 'short' AND duration IS NOT NULL AND duration_minutes <= 5",
    []
);
$total = $total['count'] ?? 0;

// Get shorts preview - latest shorts with high engagement
$sql = "SELECT 
    c.id, c.title, c.slug, c.description, c.thumbnail, c.thumbnail_url,
    c.video_url, c.video_path, c.duration, c.duration_minutes, 
    c.release_year, c.view_count, c.like_count, c.is_premium,
    GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
FROM content c
LEFT JOIN content_genre cg ON c.id = cg.content_id
LEFT JOIN genres g ON cg.genre_id = g.id
WHERE c.is_active = 1 
    AND c.content_type = 'short' 
    AND (c.duration_minutes IS NULL OR c.duration_minutes <= 5)
GROUP BY c.id
ORDER BY c.view_count DESC, c.like_count DESC, c.published_at DESC
LIMIT ? OFFSET ?";

$shorts = $db->fetchAll($sql, [$limit, $offset]);

// Get shorts count by genre for filtering
$genreStats = $db->fetchAll(
    "SELECT 
        g.name, g.slug, COUNT(*) as count
    FROM content c
    LEFT JOIN content_genre cg ON c.id = cg.content_id
    LEFT JOIN genres g ON cg.genre_id = g.id
    WHERE c.is_active = 1 AND c.content_type = 'short'
    GROUP BY g.id
    ORDER BY count DESC
    LIMIT 10"
);

Response::success('Shorts preview retrieved successfully', [
    'shorts' => $shorts,
    'genres' => $genreStats,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]
], 200);

