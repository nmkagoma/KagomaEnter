<?php
/**
 * Public Company Detail API
 * 
 * Get company profile and their content
 * GET /api/company/detail.php?slug=netflix
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/Database.php';
require_once dirname(__DIR__, 2) . '/includes/Response.php';

$db = new Database();

// Get company by slug or ID
$slug = $_GET['slug'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!$slug && !$id) {
    Response::error('Company slug or ID required', 400);
}

if ($slug) {
    $company = $db->fetch("SELECT * FROM companies WHERE slug = ? AND is_active = 1 AND verification_status = 'verified'", [$slug]);
} else {
    $company = $db->fetch("SELECT * FROM companies WHERE id = ? AND is_active = 1 AND verification_status = 'verified'", [$id]);
}

if (!$company) {
    Response::error('Company not found', 404);
}

// Get content
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 12);
$type = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'newest'; // newest, popular, rating

$offset = ($page - 1) * $limit;

$where = "WHERE company_id = ? AND is_active = 1";
$params = [$company['id']];

if ($type) {
    $where .= " AND content_type = ?";
    $params[] = $type;
}

// Sorting
$order_by = match($sort) {
    'popular' => 'view_count DESC',
    'rating' => 'rating DESC',
    'oldest' => 'created_at ASC',
    default => 'created_at DESC'
};

// Get total count
$total = $db->fetch("SELECT COUNT(*) as count FROM content $where", $params);
$total_count = $total['count'] ?? 0;

// Get content with genres
$sql = "
    SELECT c.*,
           GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
    FROM content c
    LEFT JOIN content_genre cg ON c.id = cg.content_id
    LEFT JOIN genres g ON cg.genre_id = g.id
    $where
    GROUP BY c.id
    ORDER BY $order_by
    LIMIT $limit OFFSET $offset
";

$content = $db->fetchAll($sql, $params);

// Get stats
$stats = [
    'total_content' => $company['content_count'],
    'total_views' => $company['total_views'],
    'by_type' => []
];

$by_type = $db->fetchAll("
    SELECT content_type, COUNT(*) as count
    FROM content
    WHERE company_id = ? AND is_active = 1
    GROUP BY content_type
", [$company['id']]);

foreach ($by_type as $row) {
    $stats['by_type'][$row['content_type']] = (int)$row['count'];
}

// Response
Response::success('Company details', [
    'company' => [
        'id' => $company['id'],
        'name' => $company['name'],
        'slug' => $company['slug'],
        'logo' => $company['logo'],
        'banner' => $company['banner'],
        'description' => $company['description'],
        'website' => $company['website'],
        'business_type' => $company['business_type'],
        'country' => $company['country'],
        'created_at' => $company['created_at']
    ],
    'stats' => $stats,
    'content' => $content ?: [],
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total_count,
        'pages' => ceil($total_count / $limit)
    ]
]);

