<?php
/**
 * Get Content Ratings Endpoint
 * Retrieves ratings for a specific content
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

// Get content ID
$contentId = (int)($_GET['content_id'] ?? 0);

if ($contentId === 0) {
    Response::validationError(['content_id' => 'Content ID is required']);
}

// Get content info
$content = $db->fetch(
    "SELECT id, title, slug, rating FROM content WHERE id = ?",
    [$contentId]
);

if (!$content) {
    Response::error('Content not found', 404);
}

// Get rating statistics
$stats = $db->fetch(
    "SELECT 
        COUNT(*) as total_ratings,
        AVG(rating) as average_rating,
        SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive_ratings,
        SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as negative_ratings
    FROM ratings WHERE content_id = ?",
    [$contentId]
);

// Get rating distribution
$distribution = $db->fetchAll(
    "SELECT rating, COUNT(*) as count 
    FROM ratings WHERE content_id = ? 
    GROUP BY rating 
    ORDER BY rating DESC",
    [$contentId]
);

// Get recent reviews
$reviews = $db->fetchAll(
    "SELECT r.id, r.rating, r.review, r.created_at, 
    u.id as user_id, u.name as user_name
    FROM ratings r
    INNER JOIN users u ON r.user_id = u.id
    WHERE r.content_id = ? AND r.review IS NOT NULL
    ORDER BY r.created_at DESC
    LIMIT 10",
    [$contentId]
);

// Get user's rating if authenticated
$userRating = null;
$user = $auth->getCurrentUser();
if ($user) {
    $userRating = $db->fetch(
        "SELECT rating, review, created_at FROM ratings 
        WHERE user_id = ? AND content_id = ?",
        [$user['id'], $contentId]
    );
}

Response::success('Ratings retrieved successfully', [
    'content' => [
        'id' => $content['id'],
        'title' => $content['title'],
        'slug' => $content['slug']
    ],
    'statistics' => [
        'total_ratings' => (int)$stats['total_ratings'],
        'average_rating' => round((float)($stats['average_rating'] ?? 0), 2),
        'positive_ratings' => (int)$stats['positive_ratings'],
        'negative_ratings' => (int)$stats['negative_ratings']
    ],
    'distribution' => $distribution,
    'reviews' => $reviews,
    'user_rating' => $userRating
], 200);

