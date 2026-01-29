<?php
/**
 * Admin Dashboard Stats Endpoint
 * Retrieves key statistics for the admin dashboard
 * 
 * NOTE: This endpoint requires admin authentication for security.
 * For testing, you can add ?test=1 to view data without auth.
 */

// Include configuration and stubs for IDE intellisense
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/stubs.php';
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

$db = Database::getInstance();
$auth = new Auth();

// Check for test mode
$testMode = isset($_GET['test']);

// Get current user (optional for test mode)
$user = null;
if (!$testMode) {
    $user = $auth->getCurrentUser();
    if (!$user) {
        Response::error('Unauthorized - Please login first', 401);
    }
    if ($user['role'] !== 'admin') {
        Response::error('Forbidden. Admin access required', 403);
    }
}

// ========== User Statistics ==========
$users = [
    'total' => 0,
    'new_today' => 0,
    'new_week' => 0,
    'new_month' => 0,
    'active_today' => 0,
    'active_week' => 0
];

$totalUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
$users['total'] = (int)($totalUsers['count'] ?? 0);

$todayUsers = $db->fetch(
    "SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE() AND is_active = 1"
);
$users['new_today'] = (int)($todayUsers['count'] ?? 0);

$weekUsers = $db->fetch(
    "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_active = 1"
);
$users['new_week'] = (int)($weekUsers['count'] ?? 0);

$monthUsers = $db->fetch(
    "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_active = 1"
);
$users['new_month'] = (int)($monthUsers['count'] ?? 0);

// Active users (users who logged in or were active)
$todayActive = $db->fetch(
    "SELECT COUNT(DISTINCT user_id) as count FROM user_sessions WHERE DATE(last_activity) = CURDATE()"
);
$users['active_today'] = (int)($todayActive['count'] ?? 0);

$weekActive = $db->fetch(
    "SELECT COUNT(DISTINCT user_id) as count FROM user_sessions WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
);
$users['active_week'] = (int)($weekActive['count'] ?? 0);

// ========== Content Statistics ==========
$content = [
    'total' => 0,
    'by_type' => [],
    'total_views' => 0,
    'featured' => 0
];

$totalContent = $db->fetch("SELECT COUNT(*) as count FROM content WHERE is_active = 1");
$content['total'] = (int)($totalContent['count'] ?? 0);

$contentByType = $db->fetchAll(
    "SELECT content_type, COUNT(*) as count FROM content WHERE is_active = 1 GROUP BY content_type"
);
$content['by_type'] = $contentByType;

$totalViews = $db->fetch("SELECT SUM(view_count) as total FROM content WHERE is_active = 1");
$content['total_views'] = (int)($totalViews['total'] ?? 0);

$featuredContent = $db->fetch("SELECT COUNT(*) as count FROM content WHERE is_featured = 1 AND is_active = 1");
$content['featured'] = (int)($featuredContent['count'] ?? 0);

// ========== Views Statistics ==========
$views = [
    'total' => 0,
    'today' => 0,
    'week' => 0,
    'month' => 0,
    'daily' => []
];

$totalWatchHistory = $db->fetch("SELECT COUNT(*) as count FROM watch_history");
$views['total'] = (int)($totalWatchHistory['count'] ?? 0);

$todayViews = $db->fetch(
    "SELECT COUNT(*) as count FROM watch_history WHERE DATE(watched_at) = CURDATE()"
);
$views['today'] = (int)($todayViews['count'] ?? 0);

$weekViews = $db->fetch(
    "SELECT COUNT(*) as count FROM watch_history WHERE watched_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
);
$views['week'] = (int)($weekViews['count'] ?? 0);

$monthViews = $db->fetch(
    "SELECT COUNT(*) as count FROM watch_history WHERE watched_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
);
$views['month'] = (int)($monthViews['count'] ?? 0);

// Daily views for chart (last 30 days)
$dailyViews = $db->fetchAll(
    "SELECT 
        DATE(watched_at) as date,
        COUNT(*) as views
    FROM watch_history
    WHERE watched_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(watched_at)
    ORDER BY date"
);
$views['daily'] = $dailyViews;

// ========== Subscription/Revenue Statistics ==========
$subscriptions = [
    'total' => 0,
    'by_plan' => [],
    'revenue' => 0,
    'monthly_revenue' => []
];

$totalSubs = $db->fetch("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
$subscriptions['total'] = (int)($totalSubs['count'] ?? 0);

$byPlan = $db->fetchAll(
    "SELECT 
        s.type as plan_type,
        COUNT(*) as count,
        COALESCE(SUM(s.amount), 0) as revenue
    FROM subscriptions s
    WHERE s.status = 'active'
    GROUP BY s.type"
);
$subscriptions['by_plan'] = $byPlan;

$totalRevenue = $db->fetch(
    "SELECT COALESCE(SUM(amount), 0) as total FROM subscriptions WHERE status = 'active'"
);
$subscriptions['revenue'] = (float)($totalRevenue['total'] ?? 0);

// Monthly revenue for chart (last 12 months)
$monthlyRevenue = $db->fetchAll(
    "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COALESCE(SUM(amount), 0) as revenue
    FROM subscriptions
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month"
);
$subscriptions['monthly_revenue'] = $monthlyRevenue;

// ========== Engagement Statistics ==========
$engagement = [
    'favorites' => 0,
    'playlists' => 0,
    'comments' => 0,
    'ratings' => 0
];

$favorites = $db->fetch("SELECT COUNT(*) as count FROM favorites");
$engagement['favorites'] = (int)($favorites['count'] ?? 0);

$playlists = $db->fetch("SELECT COUNT(*) as count FROM playlists");
$engagement['playlists'] = (int)($playlists['count'] ?? 0);

$comments = $db->fetch("SELECT COUNT(*) as count FROM comments");
$engagement['comments'] = (int)($comments['count'] ?? 0);

$ratings = $db->fetch("SELECT COUNT(*) as count FROM ratings");
$engagement['ratings'] = (int)($ratings['count'] ?? 0);

// ========== Content Performance (Top 10) ==========
$topContent = $db->fetchAll(
    "SELECT 
        id, 
        title, 
        content_type, 
        view_count, 
        rating,
        thumbnail
    FROM content
    WHERE is_active = 1
    ORDER BY view_count DESC
    LIMIT 10"
);

// ========== Recent Activity ==========
$recentUsers = $db->fetchAll(
    "SELECT id, name, email, created_at 
    FROM users 
    WHERE is_active = 1 
    ORDER BY created_at DESC 
    LIMIT 5"
);

$recentContent = $db->fetchAll(
    "SELECT id, title, content_type, created_at, view_count 
    FROM content 
    WHERE is_active = 1 
    ORDER BY created_at DESC 
    LIMIT 5"
);

// Build response
$responseData = [
    'users' => $users,
    'content' => $content,
    'views' => $views,
    'subscriptions' => $subscriptions,
    'engagement' => $engagement,
    'top_content' => $topContent,
    'recent_users' => $recentUsers,
    'recent_content' => $recentContent,
    'generated_at' => date('Y-m-d H:i:s')
];

Response::success('Dashboard stats retrieved successfully', $responseData, 200);

