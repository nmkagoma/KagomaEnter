<?php
/**
 * Company Dashboard API
 * 
 * Get analytics and overview for company content
 * GET /api/company/dashboard.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

// Authenticate
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$auth_header || !str_starts_with($auth_header, 'Bearer ')) {
    Response::error('Unauthorized', 401);
}

$token = str_replace('Bearer ', '', $auth_header);
$user = $auth->validateToken($token);

if (!$user || $user['role'] !== 'company') {
    Response::error('Access denied', 403);
}

// Get company
$company = $db->fetch("SELECT * FROM companies WHERE user_id = ? AND is_active = 1", [$user['id']]);
if (!$company) {
    Response::error('Company not found', 404);
}

// Get dashboard stats
$stats = [];

// Total content
$total_content = $db->fetch("SELECT COUNT(*) as count FROM content WHERE company_id = ?", [$company['id']]);
$stats['total_content'] = (int)($total_content['count'] ?? 0);

// Content by type
$by_type = $db->fetchAll("
    SELECT content_type, COUNT(*) as count 
    FROM content 
    WHERE company_id = ? 
    GROUP BY content_type
", [$company['id']]);
$stats['content_by_type'] = [];
foreach ($by_type as $row) {
    $stats['content_by_type'][$row['content_type']] = (int)$row['count'];
}

// Total views
$total_views = $db->fetch("SELECT SUM(view_count) as total FROM content WHERE company_id = ?", [$company['id']]);
$stats['total_views'] = (int)($total_views['total'] ?? 0);

// Average rating
$avg_rating = $db->fetch("SELECT AVG(rating) as avg FROM content WHERE company_id = ? AND rating > 0", [$company['id']]);
$stats['average_rating'] = round((float)($avg_rating['avg'] ?? 0), 1);

// Active content
$active = $db->fetch("SELECT COUNT(*) as count FROM content WHERE company_id = ? AND is_active = 1", [$company['id']]);
$stats['active_content'] = (int)($active['count'] ?? 0);

// Pending/inactive content
$inactive = $db->fetch("SELECT COUNT(*) as count FROM content WHERE company_id = ? AND is_active = 0", [$company['id']]);
$stats['inactive_content'] = (int)($inactive['count'] ?? 0);

// Recent content (last 5)
$recent = $db->fetchAll("
    SELECT id, title, slug, content_type, view_count, rating, is_active, created_at
    FROM content 
    WHERE company_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$company['id']]);

// Views trend (last 7 days - mock data for now)
$stats['views_trend'] = [
    ['date' => date('Y-m-d', strtotime('-6 days')), 'views' => rand(100, 500)],
    ['date' => date('Y-m-d', strtotime('-5 days')), 'views' => rand(100, 500)],
    ['date' => date('Y-m-d', strtotime('-4 days')), 'views' => rand(100, 500)],
    ['date' => date('Y-m-d', strtotime('-3 days')), 'views' => rand(100, 500)],
    ['date' => date('Y-m-d', strtotime('-2 days')), 'views' => rand(100, 500)],
    ['date' => date('Y-m-d', strtotime('-1 days')), 'views' => rand(100, 500)],
    ['date' => date('Y-m-d'), 'views' => rand(100, 500)]
];

// Top performing content
$top_content = $db->fetchAll("
    SELECT id, title, slug, content_type, view_count, rating
    FROM content 
    WHERE company_id = ? 
    ORDER BY view_count DESC 
    LIMIT 5
", [$company['id']]);

// Verification status info
$verification_status = [
    'status' => $company['verification_status'],
    'message' => match($company['verification_status']) {
        'verified' => 'Your account is verified. You can upload content.',
        'pending' => 'Your application is pending review.',
        'under_review' => 'Your application is being reviewed.',
        'rejected' => 'Your application was rejected. Please contact support.',
        default => 'Unknown status'
    }
];

Response::success('Dashboard data', [
    'company' => [
        'id' => $company['id'],
        'name' => $company['name'],
        'logo' => $company['logo'],
        'verification_status' => $company['verification_status']
    ],
    'stats' => $stats,
    'recent_content' => $recent ?: [],
    'top_content' => $top_content ?: [],
    'verification' => $verification_status
]);

