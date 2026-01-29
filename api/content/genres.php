<?php
/**
 * Get Genres Endpoint
 * Retrieves all available genres for content filtering
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

// Get genres with content count
$sql = "SELECT 
    g.id, g.name, g.slug, g.description, g.icon, g.color,
    COUNT(DISTINCT cg.content_id) as content_count
FROM genres g
LEFT JOIN content_genre cg ON g.id = cg.genre_id
LEFT JOIN content c ON cg.content_id = c.id AND c.is_active = 1
GROUP BY g.id
ORDER BY g.name ASC";

$genres = $db->fetchAll($sql, []);

// Group by first letter for easier navigation
$groupedGenres = [];
foreach ($genres as $genre) {
    $letter = strtoupper(substr($genre['name'], 0, 1));
    if (!isset($groupedGenres[$letter])) {
        $groupedGenres[$letter] = [];
    }
    $groupedGenres[$letter][] = $genre;
}

// Get popular genre combinations (moods)
$moods = [
    ['name' => 'Exciting', 'slug' => 'exciting', 'icon' => '🔥'],
    ['name' => 'Emotional', 'slug' => 'emotional', 'icon' => '💫'],
    ['name' => 'Mind-Bending', 'slug' => 'mind-bending', 'icon' => '🧠'],
    ['name' => 'Feel-Good', 'slug' => 'feel-good', 'icon' => '😊'],
    ['name' => 'Dark', 'slug' => 'dark', 'icon' => '🌙'],
    ['name' => 'Inspiring', 'slug' => 'inspiring', 'icon' => '⭐']
];

Response::success('Genres retrieved successfully', [
    'genres' => $genres,
    'grouped' => $groupedGenres,
    'moods' => $moods
], 200);

