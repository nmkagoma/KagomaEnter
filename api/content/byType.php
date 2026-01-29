<?php
/**
 * Get Content by Type Endpoint
 * Retrieves content filtered by content type
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
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

    // Get query parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 12;
    $type = isset($_GET['type']) ? $_GET['type'] : null;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
    
    $offset = ($page - 1) * $limit;

    // Validate content type
    $validTypes = ['movie', 'series', 'short', 'documentary', 'anime', 'live'];
    if (!$type || !in_array($type, $validTypes)) {
        Response::error('Invalid content type. Valid types: ' . implode(', ', $validTypes), 400);
    }

    // Get content type info
    $contentType = $db->fetch(
        "SELECT id, name, slug, description, icon, color FROM content_types WHERE slug = ?",
        [$type]
    );

    // Determine sort order
    switch ($sort) {
        case 'popular':
            $orderBy = "view_count DESC";
            break;
        case 'rating':
            $orderBy = "rating DESC";
            break;
        case 'newest':
        case 'latest':
        default:
            $orderBy = "COALESCE(published_at, created_at) DESC";
            break;
    }

    // Get total count
    $countSql = "SELECT COUNT(*) as count FROM content WHERE is_active = 1 AND content_type = ?";
    $totalResult = $db->fetch($countSql, [$type]);
    $total = $totalResult ? $totalResult['count'] : 0;

    // Get content by type
    $sql = "SELECT 
        id, title, slug, description, 
        thumbnail, thumbnail_url,
        video_url, video_path, video_quality, 
        duration, duration_minutes, release_year,
        content_type, age_rating, rating,
        view_count, like_count, comment_count,
        is_featured, is_premium, published_at, created_at
    FROM content
    WHERE is_active = 1 AND content_type = '" . $db->escape($type) . "'
    ORDER BY " . $orderBy . "
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $result = $db->query($sql);
    $content = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $content[] = $row;
        }
    }

    // Get all content types for navigation
    $allTypes = $db->fetchAll(
        "SELECT id, name, slug, icon, color FROM content_types WHERE is_active = 1 ORDER BY sort_order"
    );

    Response::success('Content retrieved successfully', [
        'content_type' => $contentType ?: ['slug' => $type, 'name' => ucfirst($type)],
        'content' => $content,
        'content_types' => $allTypes,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $limit > 0 ? ceil($total / $limit) : 0
        ]
    ], 200);

} catch (Exception $e) {
    error_log("ByType API Error: " . $e->getMessage());
    Response::serverError('An error occurred while retrieving content');
}
