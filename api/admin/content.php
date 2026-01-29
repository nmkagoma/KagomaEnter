<?php
/**
 * Admin Content Management Endpoint
 * Enhanced content management with moderation features
 */

// Include configuration and stubs for IDE intellisense
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/stubs.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Validation.php';

// Set content type
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db = Database::getInstance();
$auth = new Auth();
$validation = new Validation();

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

if ($user['role'] !== 'admin') {
    Response::error('Forbidden. Admin access required', 403);
}

$method = $_SERVER['REQUEST_METHOD'];

// GET - List/Filter content
if ($method === 'GET') {
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $status = $_GET['status'] ?? 'all'; // all, active, inactive
    $type = $_GET['type'] ?? null;
    $search = $_GET['search'] ?? null;
    
    $offset = ($page - 1) * $limit;
    
    $where = "1=1";
    $params = [];
    
    if ($status === 'active') {
        $where .= " AND c.is_active = 1";
    } elseif ($status === 'inactive') {
        $where .= " AND c.is_active = 0";
    }
    
    if ($type) {
        $where .= " AND c.content_type = ?";
        $params[] = $type;
    }
    
    if ($search) {
        $where .= " AND (c.title LIKE ? OR c.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Get total count
    $total = $db->fetch(
        "SELECT COUNT(*) as count FROM content c WHERE $where",
        $params
    );
    $total = $total['count'] ?? 0;
    
    // Get content
    $sql = "SELECT 
        c.*,
        u.name as uploader_name,
        GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
    FROM content c
    LEFT JOIN users u ON c.uploaded_by = u.id
    LEFT JOIN content_genre cg ON c.id = cg.content_id
    LEFT JOIN genres g ON cg.genre_id = g.id
    WHERE $where
    GROUP BY c.id
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $content = $db->fetchAll($sql, $params);
    
    // Get content type distribution
    $typeDistribution = $db->fetchAll(
        "SELECT content_type, COUNT(*) as count 
        FROM content 
        WHERE is_active = 1 
        GROUP BY content_type"
    );
    
    Response::success('Content retrieved successfully', [
        'content' => $content,
        'type_distribution' => $typeDistribution,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ], 200);
}

// POST - Create content
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($input['title'])) {
        Response::validationError(['title' => 'Title is required']);
    }
    
    if (empty($input['description'])) {
        Response::validationError(['description' => 'Description is required']);
    }
    
    // Generate slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['title'])));
    $originalSlug = $slug;
    $counter = 1;
    
    while ($db->fetch("SELECT id FROM content WHERE slug = ?", [$slug])) {
        $slug = $originalSlug . '-' . $counter++;
    }
    
    // Validate content type
    $validTypes = ['movie', 'series', 'live', 'documentary', 'anime'];
    $contentType = $input['content_type'] ?? 'movie';
    if (!in_array($contentType, $validTypes)) {
        Response::validationError(['content_type' => 'Invalid content type']);
    }
    
    // Create content
    $contentData = [
        'title' => htmlspecialchars($input['title']),
        'slug' => $slug,
        'description' => htmlspecialchars($input['description']),
        'thumbnail' => $input['thumbnail'] ?? null,
        'trailer_url' => $input['trailer_url'] ?? null,
        'video_url' => $input['video_url'] ?? null,
        'video_path' => $input['video_path'] ?? null,
        'video_quality' => $input['video_quality'] ?? '1080p',
        'duration' => (int)($input['duration'] ?? 0),
        'release_year' => (int)($input['release_year'] ?? date('Y')),
        'content_type' => $contentType,
        'age_rating' => $input['age_rating'] ?? 'PG-13',
        'rating' => 0,
        'view_count' => 0,
        'is_featured' => (int)($input['is_featured'] ?? 0),
        'is_premium' => (int)($input['is_premium'] ?? 0),
        'is_active' => 1,
        'published_at' => $input['published_at'] ?? date('Y-m-d H:i:s'),
        'uploaded_by' => $user['id'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $contentId = $db->insert('content', $contentData);
    
    if ($contentId) {
        // Add genres
        if (!empty($input['genre_ids']) && is_array($input['genre_ids'])) {
            foreach ($input['genre_ids'] as $genreId) {
                $db->insert('content_genre', [
                    'content_id' => $contentId,
                    'genre_id' => (int)$genreId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        $content = $db->fetch("SELECT * FROM content WHERE id = ?", [$contentId]);
        
        Response::success('Content created successfully', [
            'content' => $content
        ], 201);
    } else {
        Response::error('Failed to create content', 500);
    }
}

// PUT - Update content
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $contentId = (int)($input['id'] ?? 0);
    
    if ($contentId === 0) {
        Response::validationError(['id' => 'Content ID is required']);
    }
    
    $content = $db->fetch("SELECT id FROM content WHERE id = ?", [$contentId]);
    
    if (!$content) {
        Response::error('Content not found', 404);
    }
    
    // Prepare update data
    $updateData = [];
    $allowedFields = [
        'title', 'description', 'thumbnail', 'trailer_url', 'video_url',
        'video_path', 'video_quality', 'duration', 'release_year',
        'content_type', 'age_rating', 'is_featured', 'is_premium', 'is_active'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            if (in_array($field, ['title', 'description', 'thumbnail', 'trailer_url', 'video_path'])) {
                $updateData[$field] = htmlspecialchars($input[$field]);
            } else {
                $updateData[$field] = (int)$input[$field];
            }
        }
    }
    
    // Handle published_at
    if (isset($input['published_at'])) {
        $updateData['published_at'] = $input['published_at'];
    }
    
    $updateData['updated_at'] = date('Y-m-d H:i:s');
    
    $updated = $db->update('content', $updateData, 'id = ?', [$contentId]);
    
    if ($updated) {
        // Update genres if provided
        if (isset($input['genre_ids']) && is_array($input['genre_ids'])) {
            // Remove existing genres
            $db->delete("DELETE FROM content_genre WHERE content_id = ?", [$contentId]);
            
            // Add new genres
            foreach ($input['genre_ids'] as $genreId) {
                $db->insert('content_genre', [
                    'content_id' => $contentId,
                    'genre_id' => (int)$genreId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        $updatedContent = $db->fetch(
            "SELECT c.*, u.name as uploader_name 
            FROM content c 
            LEFT JOIN users u ON c.uploaded_by = u.id 
            WHERE c.id = ?",
            [$contentId]
        );
        
        Response::success('Content updated successfully', [
            'content' => $updatedContent
        ], 200);
    } else {
        Response::error('Failed to update content', 500);
    }
}

// DELETE - Delete content
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $contentId = (int)($input['id'] ?? $_GET['id'] ?? 0);
    
    if ($contentId === 0) {
        Response::validationError(['id' => 'Content ID is required']);
    }
    
    $content = $db->fetch("SELECT id, title FROM content WHERE id = ?", [$contentId]);
    
    if (!$content) {
        Response::error('Content not found', 404);
    }
    
    // Soft delete
    $deleted = $db->update('content',
        [
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'id = ?',
        [$contentId]
    );
    
    if ($deleted) {
        Response::success('Content deleted successfully', [
            'content_id' => $contentId,
            'title' => $content['title']
        ], 200);
    } else {
        Response::error('Failed to delete content', 500);
    }
}

