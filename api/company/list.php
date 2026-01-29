<?php
/**
 * Public Company Listing API
 * 
 * Allows users to browse content providers
 * GET /api/company/list.php
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

// GET - List verified companies
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 12);
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? ''; // studio, distributor, producer, independent, network

$offset = ($page - 1) * $limit;

$where = "WHERE c.is_active = 1 AND c.verification_status = 'verified'";
$params = [];

if ($search) {
    $where .= " AND (c.name LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type) {
    $where .= " AND c.business_type = ?";
    $params[] = $type;
}

// Get total count
$total = $db->fetch("SELECT COUNT(*) as count FROM companies c $where", $params);
$total_count = $total['count'] ?? 0;

// Get companies with content stats
$sql = "
    SELECT 
        c.id,
        c.name,
        c.slug,
        c.logo,
        c.banner,
        c.description,
        c.business_type,
        c.country,
        c.content_count,
        c.total_views,
        c.created_at,
        CASE WHEN c.logo IS NOT NULL THEN 1 ELSE 0 END as has_logo
    FROM companies c
    $where
    ORDER BY c.content_count DESC, c.total_views DESC
    LIMIT $limit OFFSET $offset
";

$companies = $db->fetchAll($sql, $params);

// Get featured content from each company
foreach ($companies as &$company) {
    $featured = $db->fetchAll("
        SELECT id, title, slug, thumbnail_url, content_type, view_count, rating
        FROM content 
        WHERE company_id = ? AND is_active = 1
        ORDER BY view_count DESC
        LIMIT 3
    ", [$company['id']]);
    $company['featured_content'] = $featured ?: [];
    unset($company['has_logo']);
}

// Pagination info
$pagination = [
    'page' => $page,
    'limit' => $limit,
    'total' => $total_count,
    'pages' => ceil($total_count / $limit)
];

Response::success('Companies retrieved', [
    'companies' => $companies ?: [],
    'pagination' => $pagination
]);

