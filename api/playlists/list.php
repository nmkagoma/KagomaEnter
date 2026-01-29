<?php
/**
 * List Playlists Endpoint
 * Retrieves user's playlists
 */

// Turn off error display for API responses
error_reporting(0);
ini_set('display_errors', 0);

// Include configuration
require_once __DIR__ . '/../../config/config.php';
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

try {
    $db = Database::getInstance();
    $auth = new Auth();

    // Get current user
    $user = $auth->getCurrentUser();

    if (!$user) {
        Response::error('Unauthorized', 401);
    }

    // Get query parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 12;
    $includePublic = isset($_GET['include_public']) && $_GET['include_public'] === 'true';

    $offset = ($page - 1) * $limit;

    // Build query
    $where = "WHERE p.user_id = ?";
    $params = [$user['id']];

    if ($includePublic) {
        $where = "WHERE p.is_public = 1 OR p.user_id = ?";
        $params = [$user['id']];
    }

    // Check if playlists table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'playlists'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        // Table doesn't exist, return empty playlists
        Response::success('Playlists retrieved successfully', [
            'playlists' => [],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => 0,
                'total_pages' => 1
            ]
        ], 200);
    }

    // Get total count
    $totalResult = $db->fetch("SELECT COUNT(*) as count FROM playlists p $where", $params);
    $total = $totalResult['count'] ?? 0;

    // Get playlists with item counts
    $sql = "SELECT 
        p.id, p.user_id, p.title, p.description, p.thumbnail, 
        p.is_public, p.created_at, p.updated_at,
        COUNT(pi.id) as item_count
    FROM playlists p
    LEFT JOIN playlist_items pi ON p.id = pi.playlist_id
    $where
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;

    $playlists = $db->fetchAll($sql, $params);

    Response::success('Playlists retrieved successfully', [
        'playlists' => $playlists ?: [],
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => max(1, ceil($total / $limit))
        ]
    ], 200);

} catch (Exception $e) {
    // Return error as JSON
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
    exit;
}

