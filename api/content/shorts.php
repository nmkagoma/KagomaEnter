<?php
/**
 * Shorts API Endpoint
 * Retrieves paginated short-form content for the dedicated Shorts page
 */

// Include configuration and required classes
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';

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

try {
    $db = Database::getInstance();

    // Get query parameters
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    // Validate parameters
    $limit = min(max($limit, 1), 50);
    $page = max($page, 1);

    $offset = ($page - 1) * $limit;

    // Get total count for shorts
    $totalCountSql = "SELECT COUNT(*) as count FROM content WHERE is_active = 1 AND content_type = 'short'";
    $totalResult = $db->fetch($totalCountSql, []);
    $total = $totalResult['count'] ?? 0;

    // Get shorts with pagination
    $sql = "SELECT 
        c.id, c.title, c.slug, c.description, c.thumbnail, c.thumbnail_url,
        c.video_url, c.video_path, c.duration, c.duration_minutes, 
        c.release_year, c.view_count, c.like_count, c.user_has_liked,
        c.is_premium, c.content_type, c.age_rating,
        c.created_at,
        (SELECT COUNT(*) FROM comments WHERE content_id = c.id) as comment_count
    FROM content c
    WHERE c.is_active = 1 AND c.content_type = 'short'
    ORDER BY c.created_at DESC
    LIMIT {$limit} OFFSET {$offset}";

    $shorts = [];
    $result = $db->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $shorts[] = $row;
        }
    }

    // Get genres separately for each short
    if (!empty($shorts)) {
        foreach ($shorts as &$short) {
            $genreSql = "SELECT g.name 
                        FROM content_genre cg 
                        JOIN genres g ON cg.genre_id = g.id 
                        WHERE cg.content_id = " . (int)$short['id'];
            $genreResult = $db->query($genreSql);
            if ($genreResult) {
                $genres = [];
                while ($g = $genreResult->fetch_assoc()) {
                    $genres[] = $g['name'];
                }
                $short['genres'] = implode(', ', $genres);
            } else {
                $short['genres'] = '';
            }
        }
    }

    // Build response data
    $data = [
        'shorts' => $shorts,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $limit > 0 ? ceil($total / $limit) : 0
        ]
    ];

    Response::success('Shorts retrieved successfully', $data, 200);

} catch (Exception $e) {
    error_log("Shorts API Error: " . $e->getMessage());
    Response::serverError('An error occurred while retrieving shorts');
} catch (Error $e) {
    error_log("Shorts API Fatal Error: " . $e->getMessage());
    Response::serverError('An error occurred while retrieving shorts');
}
