<?php
/**
 * New Releases API Endpoint
 * Retrieves newly released content based on your existing database schema
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';

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

    // Get parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 24;
    $type = isset($_GET['type']) ? $_GET['type'] : null;
    $offset = ($page - 1) * $limit;

    // Build WHERE clause based on your existing schema
    $where = "WHERE c.is_active = 1";
    $params = [];

    if ($type && in_array($type, ['movie', 'series', 'short', 'documentary', 'anime', 'live'])) {
        $where .= " AND c.content_type = ?";
        $params[] = $type;
    }

    // Get total count - use prepared statement for count query
    $countSql = "SELECT COUNT(*) as count FROM content c $where";
    if (!empty($params)) {
        $totalResult = $db->fetch($countSql, $params);
    } else {
        $totalResult = $db->query($countSql);
        if ($totalResult && $totalResult->num_rows > 0) {
            $totalResult = $totalResult->fetch_assoc();
        } else {
            $totalResult = ['count' => 0];
        }
    }
    $total = $totalResult ? $totalResult['count'] : 0;

    // Get new releases - sorted by published_at or created_at (your schema uses both)
    // Use prepared statement to properly bind the type parameter
    $sql = "SELECT 
        c.id, c.title, c.slug, c.description, 
        c.thumbnail, c.thumbnail_url,
        c.video_url, c.video_quality, c.duration, c.duration_minutes,
        c.release_year, c.age_rating, c.rating,
        c.view_count, c.like_count, c.comment_count,
        c.is_featured, c.is_premium, c.content_type,
        c.published_at, c.created_at
    FROM content c
    $where
    ORDER BY COALESCE(c.published_at, c.created_at) DESC
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    // Execute with prepared statement if params exist, otherwise direct query
    $releases = [];
    if (!empty($params)) {
        // Use prepared statement
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $releases[] = $row;
                }
                $result->free();
            }
            $stmt->close();
        }
    } else {
        // No params needed, use direct query
        $result = $db->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $releases[] = $row;
            }
            $result->free();
        }
    }

    Response::success('New releases retrieved successfully', [
        'releases' => $releases,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $limit > 0 ? ceil($total / $limit) : 0
        ]
    ], 200);

} catch (Exception $e) {
    error_log("New Releases API Error: " . $e->getMessage());
    Response::serverError('An error occurred while retrieving new releases');
}

