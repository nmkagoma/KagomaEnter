<?php
/**
 * Get Content by Genre Endpoint
 * Retrieves content filtered by genre
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
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
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 12);
    $genreId = (int)($_GET['genre_id'] ?? 0);
    $genreSlug = $_GET['genre'] ?? null;
    $sort = $_GET['sort'] ?? 'latest';
    $type = $_GET['type'] ?? null;

    $offset = ($page - 1) * $limit;

    // Get genre info
    $genre = null;
    if ($genreId > 0) {
        $genreResult = $db->query("SELECT id, name, slug, description FROM genres WHERE id = " . $genreId);
        if ($genreResult && $genreResult->num_rows > 0) {
            $genre = $genreResult->fetch_assoc();
        }
    } elseif ($genreSlug) {
        $escapedSlug = $db->escape($genreSlug);
        $genreResult = $db->query("SELECT id, name, slug, description FROM genres WHERE slug = '$escapedSlug'");
        if ($genreResult && $genreResult->num_rows > 0) {
            $genre = $genreResult->fetch_assoc();
        }
    }

    // If genre not found, return empty results with success status
    if (!$genre) {
        // Return empty result instead of error
        Response::success('Genre not found, returning empty results', [
            'genre' => ['id' => 0, 'name' => $genreSlug, 'slug' => $genreSlug],
            'content' => [],
            'genres' => [],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => 0,
                'total_pages' => 0
            ]
        ], 200);
    }

    // Determine sort order
    $orderBy = "c.published_at DESC";
    switch ($sort) {
        case 'popular':
            $orderBy = "c.view_count DESC";
            break;
        case 'rating':
            $orderBy = "c.rating DESC";
            break;
        case 'latest':
        default:
            $orderBy = "c.published_at DESC";
            break;
    }

    // Build WHERE clause
    $where = "WHERE c.is_active = 1 AND cg.genre_id = " . (int)$genre['id'];
    $params = [];

    if ($type && in_array($type, ['movie', 'series', 'short', 'documentary', 'anime', 'live'])) {
        $where .= " AND c.content_type = ?";
        $params[] = $type;
    }

    // Get total count
    $countSql = "SELECT COUNT(DISTINCT c.id) as count FROM content c INNER JOIN content_genre cg ON c.id = cg.content_id $where";
    $total = 0;
    
    if (!empty($params)) {
        $stmt = $db->prepare($countSql);
        if ($stmt) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $row = $result->fetch_assoc()) {
                $total = $row['count'] ?? 0;
            }
            $stmt->close();
        }
    } else {
        $countResult = $db->query($countSql);
        if ($countResult && $countResult->num_rows > 0) {
            $total = $countResult->fetch_assoc()['count'] ?? 0;
        }
    }

    // Get content by genre
    $sql = "SELECT 
        c.id, c.title, c.slug, c.description, c.thumbnail, c.trailer_url,
        c.video_url, c.video_path, c.video_quality, c.duration, c.release_year,
        c.content_type, c.age_rating, c.rating, c.view_count, c.is_featured,
        c.is_premium, c.published_at,
        GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
    FROM content c
    INNER JOIN content_genre cg ON c.id = cg.content_id
    LEFT JOIN genres g ON cg.genre_id = g.id
    $where
    GROUP BY c.id
    ORDER BY $orderBy
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $content = [];
    if (!empty($params)) {
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $content[] = $row;
                }
                $result->free();
            }
            $stmt->close();
        }
    } else {
        $result = $db->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $content[] = $row;
            }
            $result->free();
        }
    }

    // Get all genres for sidebar
    $allGenres = [];
    $allGenresResult = $db->query("SELECT id, name, slug FROM genres ORDER BY name");
    if ($allGenresResult && $allGenresResult->num_rows > 0) {
        while ($row = $allGenresResult->fetch_assoc()) {
            $allGenres[] = $row;
        }
    }

    Response::success('Content retrieved successfully', [
        'genre' => $genre,
        'content' => $content,
        'genres' => $allGenres,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $limit > 0 ? ceil($total / $limit) : 0
        ]
    ], 200);

} catch (Exception $e) {
    error_log("ByGenre API Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    // Return a proper JSON error response
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while retrieving content by genre',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
    exit;
}

