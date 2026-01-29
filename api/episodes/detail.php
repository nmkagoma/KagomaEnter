<?php
/**
 * Episode Detail Endpoint
 * Retrieves details for a specific episode
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

// Get episode ID
$episodeId = (int)($_GET['id'] ?? 0);

if ($episodeId === 0) {
    Response::validationError(['id' => 'Episode ID is required']);
}

// Get episode with content info
$sql = "SELECT 
    e.id, e.content_id, e.season_number, e.episode_number, e.title, 
    e.description, e.thumbnail, e.video_url, e.video_path, e.duration,
    e.view_count, e.is_premium, e.is_active, e.published_at,
    c.title as content_title, c.slug as content_slug, c.content_type,
    c.description as content_description
FROM episodes e
INNER JOIN content c ON e.content_id = c.id
WHERE e.id = ? AND e.is_active = 1 AND c.is_active = 1";

$episode = $db->fetch($sql, [$episodeId]);

if (!$episode) {
    Response::error('Episode not found', 404);
}

// Get previous and next episodes
$prevEpisode = $db->fetch(
    "SELECT id, title, episode_number FROM episodes 
    WHERE content_id = ? AND season_number = ? AND episode_number < ? 
    AND is_active = 1
    ORDER BY season_number DESC, episode_number DESC LIMIT 1",
    [$episode['content_id'], $episode['season_number'], $episode['episode_number']]
);

$nextEpisode = $db->fetch(
    "SELECT id, title, episode_number FROM episodes 
    WHERE content_id = ? AND season_number = ? AND episode_number > ? 
    AND is_active = 1
    ORDER BY season_number ASC, episode_number ASC LIMIT 1",
    [$episode['content_id'], $episode['season_number'], $episode['episode_number']]
);

// Get all episodes in current season for navigation
$seasonEpisodes = $db->fetchAll(
    "SELECT id, title, episode_number FROM episodes 
    WHERE content_id = ? AND season_number = ? AND is_active = 1
    ORDER BY episode_number",
    [$episode['content_id'], $episode['season_number']]
);

// Get episode comments count
$commentsCount = $db->fetch(
    "SELECT COUNT(*) as count FROM comments WHERE episode_id = ?",
    [$episodeId]
);

Response::success('Episode retrieved successfully', [
    'episode' => $episode,
    'navigation' => [
        'previous' => $prevEpisode,
        'next' => $nextEpisode
    ],
    'season_episodes' => $seasonEpisodes,
    'comments_count' => $commentsCount['count'] ?? 0
], 200);

