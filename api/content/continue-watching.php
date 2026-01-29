<?php
/**
 * Continue Watching API Endpoint
 * Retrieves user's watch history with progress based on your existing database schema
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

try {
    $db = Database::getInstance();
    $auth = new Auth();

    // Require authentication
    if (!$auth->isAuthenticated()) {
        Response::error('Authentication required', 401);
    }

    $userId = $auth->getCurrentUserId();
    
    if (!$userId) {
        Response::error('User not found', 404);
    }

    // Get query parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
    
    $offset = ($page - 1) * $limit;

    // Get total count - using your watch_histories table
    $countSql = "SELECT COUNT(*) as count FROM watch_histories WHERE user_id = ? AND progress < duration";
    $totalResult = $db->fetch($countSql, [$userId]);
    $total = $totalResult['count'] ?? 0;

    // Get continue watching from your existing watch_histories table
    $sql = "SELECT 
        wh.id as history_id,
        wh.progress, 
        wh.duration,
        wh.watched_at,
        c.id, c.title, c.slug, c.description, 
        c.thumbnail, c.thumbnail_url,
        c.video_quality, c.duration as content_duration,
        c.release_year, c.age_rating, c.rating,
        c.content_type, c.is_premium
    FROM watch_histories wh
    JOIN content c ON wh.content_id = c.id
    WHERE wh.user_id = ? AND wh.progress < COALESCE(wh.duration, 0)
    ORDER BY wh.watched_at DESC
    LIMIT ? OFFSET ?";

    $items = [];
    $result = $db->query($sql, [$userId, $limit, $offset]);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }

    Response::success('Continue watching retrieved successfully', [
        'items' => $items,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $limit > 0 ? ceil($total / $limit) : 0
        ]
    ], 200);

} catch (Exception $e) {
    error_log("Continue Watching API Error: " . $e->getMessage());
    Response::serverError('An error occurred while retrieving continue watching');
}

