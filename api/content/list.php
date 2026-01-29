<?php
/**
 * Get Content List Endpoint
 * Retrieves all content with filtering and pagination
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
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 24;
    $type = isset($_GET['type']) ? $_GET['type'] : null;
    $genre = isset($_GET['genre']) ? $_GET['genre'] : null;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
    $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
    
    $offset = ($page - 1) * $limit;

    // Build WHERE clause with escaped values
    $where = "WHERE is_active = 1";
    
    // Filter by content type
    if ($type && in_array($type, ['movie', 'series', 'short', 'documentary', 'anime', 'live'])) {
        $where .= " AND content_type = '" . $db->escape($type) . "'";
    }

    // Filter by year
    if ($year) {
        $where .= " AND release_year = " . (int)$year;
    }

    // Filter by genre (using content_genre table)
    if ($genre) {
        $where .= " AND EXISTS (
            SELECT 1 FROM content_genre cg 
            JOIN genres g ON cg.genre_id = g.id 
            WHERE cg.content_id = content.id AND g.slug = '" . $db->escape($genre) . "'
        )";
    }

    // Determine sort order
    switch ($sort) {
        case 'popular':
            $orderBy = "view_count DESC";
            break;
        case 'rating':
            $orderBy = "rating DESC";
            break;
        case 'title':
            $orderBy = "title ASC";
            break;
        case 'newest':
            $orderBy = "COALESCE(published_at, created_at) DESC";
            break;
        case 'popularity':
        default:
            $orderBy = "(view_count + COALESCE(rating, 0) * 100 + is_featured * 500) DESC";
            break;
    }

    // Get total count
    $countSql = "SELECT COUNT(*) as count FROM content " . $where;
    $totalResult = $db->fetch($countSql);
    $total = $totalResult ? $totalResult['count'] : 0;

    // Get content list
    $sql = "SELECT 
        id, title, slug, description, 
        thumbnail, thumbnail_url,
        video_url, video_path, video_quality, 
        duration, duration_minutes, release_year,
        content_type, age_rating, rating,
        view_count, like_count, comment_count,
        is_featured, is_premium, published_at, created_at
    FROM content
    " . $where . "
    ORDER BY " . $orderBy . "
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $result = $db->query($sql);
    $content = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $content[] = $row;
        }
    }

    // Get genres for filter dropdown
    $genres = $db->fetchAll(
        "SELECT id, name, slug FROM genres ORDER BY name"
    );

    Response::success('Content retrieved successfully', [
        'content' => $content,
        'genres' => $genres,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $limit > 0 ? ceil($total / $limit) : 0
        ]
    ], 200);

} catch (Exception $e) {
    error_log("List API Error: " . $e->getMessage());
    Response::serverError('An error occurred while retrieving content');
}

