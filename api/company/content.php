<?php
/**
 * Company Content Management API
 * 
 * GET  - List company's content
 * PUT  - Update content
 * DELETE - Delete content
 * GET /api/company/content.php?id=123
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/Database.php';
require_once dirname(__DIR__, 2) . '/includes/Response.php';
require_once dirname(__DIR__, 2) . '/includes/Auth.php';

$db = new Database();
$auth = new Auth();

// Get Authorization header
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$auth_header || !str_starts_with($auth_header, 'Bearer ')) {
    Response::error('Unauthorized', 401);
}

$token = str_replace('Bearer ', '', $auth_header);
$user = $auth->validateToken($token);

if (!$user) {
    Response::error('Invalid token', 401);
}

// Get company
$company = $db->fetch("SELECT * FROM companies WHERE user_id = ? AND is_active = 1", [$user['id']]);
if (!$company || $company['verification_status'] !== 'verified') {
    Response::error('Company not verified', 403);
}

// GET - List content
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $type = $_GET['type'] ?? '';
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    $where = "WHERE company_id = ?";
    $params = [(int)$company['id']];
    
    if ($type) {
        $where .= " AND content_type = ?";
        $params[] = $type;
    }
    
    if ($search) {
        $where .= " AND (title LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status === 'active') {
        $where .= " AND is_active = 1";
    } elseif ($status === 'inactive') {
        $where .= " AND is_active = 0";
    }
    
    // Get total count
    $total = $db->fetch("SELECT COUNT(*) as count FROM content $where", $params);
    $total_count = $total['count'];
    
    // Get content
    $sql = "SELECT c.*, 
            GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
            FROM content c
            LEFT JOIN content_genre cg ON c.id = cg.content_id
            LEFT JOIN genres g ON cg.genre_id = g.id
            $where
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT $limit OFFSET $offset";
    
    $content = $db->fetchAll($sql, $params);
    
    Response::success('Content retrieved', [
        'content' => $content ?: [],
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total_count,
            'pages' => ceil($total_count / $limit)
        ]
    ]);
}

// PUT - Update content
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        Response::error('Invalid input', 400);
    }
    
    $content_id = (int)($input['id'] ?? 0);
    if (!$content_id) {
        Response::error('Content ID required', 400);
    }
    
    // Verify content belongs to company
    $content = $db->fetch("SELECT id FROM content WHERE id = ? AND company_id = ?", [$content_id, (int)$company['id']]);
    if (!$content) {
        Response::error('Content not found or access denied', 404);
    }
    
    $update_data = [];
    $allowed_fields = ['title', 'description', 'age_rating', 'is_premium', 'is_active', 'trailer_url'];
    
    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $update_data[$field] = $input[$field];
        }
    }
    
    if (!empty($update_data)) {
        $update_data['updated_at'] = date('Y-m-d H:i:s');
        $db->update('content', $update_data, "id = $content_id");
    }
    
    // Update genres if provided
    if (isset($input['genres']) && is_array($input['genres'])) {
        $db->query("DELETE FROM content_genre WHERE content_id = $content_id");
        foreach ($input['genres'] as $genre_id) {
            $db->insert('content_genre', [
                'content_id' => $content_id,
                'genre_id' => (int)$genre_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    // Get updated content
    $updated = $db->fetch("SELECT * FROM content WHERE id = ?", [$content_id]);
    
    Response::success('Content updated', ['content' => $updated]);
}

// DELETE - Delete content
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $content_id = (int)($_GET['id'] ?? 0);
    if (!$content_id) {
        Response::error('Content ID required', 400);
    }
    
    // Verify ownership
    $content = $db->fetch("SELECT id, video_path, thumbnail FROM content WHERE id = ? AND company_id = ?", [$content_id, (int)$company['id']]);
    if (!$content) {
        Response::error('Content not found', 404);
    }
    
    // Delete file if exists
    if ($content['video_path'] && file_exists($content['video_path'])) {
        @unlink($content['video_path']);
    }
    
    // Delete content (foreign keys will cascade)
    $db->query("DELETE FROM content WHERE id = $content_id");
    
    // Update company content count
    $db->query("UPDATE companies SET content_count = GREATEST(0, content_count - 1) WHERE id = " . (int)$company['id']);
    
    Response::success('Content deleted successfully');
}

