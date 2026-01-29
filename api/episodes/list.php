<?php
/**
 * Episodes List Endpoint
 * Retrieves episodes for a specific content (series)
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
$contentId = (int)($_GET['content_id'] ?? 0);
$season = (int)($_GET['season'] ?? 0);
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 20);

$offset = ($page - 1) * $limit;

if ($contentId === 0) {
    Response::validationError(['content_id' => 'Content ID is required']);
}

// Check if content exists
$content = $db->fetch(
    "SELECT id, title, slug, content_type FROM content WHERE id = ? AND is_active = 1",
    [$contentId]
);

if (!$content) {
    Response::error('Content not found', 404);
}

// Only series can have episodes
if ($content['content_type'] !== 'series') {
    Response::error('Only series content can have episodes', 400);
}

// Build query
$where = "WHERE e.content_id = ? AND e.is_active = 1";
$params = [$contentId];

if ($season > 0) {
    $where .= " AND e.season_number = ?";
    $params[] = $season;
}

// Get total count
$total = $db->fetch(
    "SELECT COUNT(*) as count FROM episodes e $where",
    $params
);
$total = $total['count'] ?? 0;

// Get episodes
$sql = "SELECT 
    e.id, e.content_id, e.season_number, e.episode_number, e.title, 
    e.description, e.thumbnail, e.video_url, e.video_path, e.duration,
    e.view_count, e.is_premium, e.is_active, e.published_at
FROM episodes e
$where
ORDER BY e.season_number ASC, e.episode_number ASC
LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

$episodes = $db->fetchAll($sql, $params);

// Get seasons info
$seasons = $db->fetchAll(
    "SELECT DISTINCT season_number, 
     COUNT(*) as episode_count,
     MIN(episode_number) as first_episode,
     MAX(episode_number) as last_episode
    FROM episodes 
    WHERE content_id = ? AND is_active = 1
    GROUP BY season_number
    ORDER BY season_number",
    [$contentId]
);

Response::success('Episodes retrieved successfully', [
    'content' => [
        'id' => $content['id'],
        'title' => $content['title'],
        'slug' => $content['slug'],
        'content_type' => $content['content_type']
    ],
    'seasons' => $seasons,
    'episodes' => $episodes,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]
], 200);

