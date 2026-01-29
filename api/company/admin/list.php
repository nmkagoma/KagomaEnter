<?php
/**
 * Company Admin List API
 * 
 * Admin endpoint to list pending company applications
 * GET /api/company/admin/list.php?status=pending
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/Database.php';
require_once dirname(__DIR__, 3) . '/includes/Response.php';
require_once dirname(__DIR__, 3) . '/includes/Auth.php';

$db = new Database();
$auth = new Auth();

// Authenticate admin
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$auth_header || !str_starts_with($auth_header, 'Bearer ')) {
    Response::error('Unauthorized', 401);
}

$token = str_replace('Bearer ', '', $auth_header);
$user = $auth->validateToken($token);

if (!$user || $user['role'] !== 'admin') {
    Response::error('Access denied. Admin access required.', 403);
}

$status = $_GET['status'] ?? 'pending';
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 20);
$offset = ($page - 1) * $limit;

try {
    // Get applications
    $where = "1=1";
    $params = [];
    
    if ($status !== 'all') {
        $where .= " AND ca.status = ?";
        $params[] = $status;
    }
    
    // Count total
    $total = $db->fetch("SELECT COUNT(*) as count FROM content_applications ca WHERE $where", $params);
    $total_count = $total['count'];
    
    // Get applications with company details
    $sql = "
        SELECT ca.*, c.name as company_name, c.logo, c.verification_status
        FROM content_applications ca
        LEFT JOIN companies c ON ca.company_id = c.id
        WHERE $where
        ORDER BY ca.submitted_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $applications = $db->fetchAll($sql, $params);
    
    // Get company stats
    $stats = [
        'pending' => $db->fetch("SELECT COUNT(*) as count FROM content_applications WHERE status = 'pending'", [])['count'] ?? 0,
        'approved' => $db->fetch("SELECT COUNT(*) as count FROM content_applications WHERE status = 'approved'", [])['count'] ?? 0,
        'rejected' => $db->fetch("SELECT COUNT(*) as count FROM content_applications WHERE status = 'rejected'", [])['count'] ?? 0,
        'total_companies' => $db->fetch("SELECT COUNT(*) as count FROM companies WHERE is_active = 1", [])['count'] ?? 0
    ];
    
    Response::success('Applications retrieved', [
        'applications' => $applications ?: [],
        'stats' => $stats,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total_count,
            'pages' => ceil($total_count / $limit)
        ]
    ]);
    
} catch (Exception $e) {
    error_log('List applications error: ' . $e->getMessage());
    Response::error('Failed to retrieve applications', 500);
}

