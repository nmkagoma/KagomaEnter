<?php
/**
 * Admin Analytics Endpoint
 * Retrieves detailed analytics for the platform
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

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

if ($user['role'] !== 'admin') {
    Response::error('Forbidden. Admin access required', 403);
}

// Get time period
$period = $_GET['period'] ?? '30days'; // 7days, 30days, 90days, year
$days = 30;
switch ($period) {
    case '7days':
        $days = 7;
        break;
    case '30days':
        $days = 30;
        break;
    case '90days':
        $days = 90;
        break;
    case 'year':
        $days = 365;
        break;
}

// User analytics
$userStats = [
    'total' => 0,
    'new' => 0,
    'active' => 0,
    'by_role' => []
];

$totalUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
$userStats['total'] = (int)($totalUsers['count'] ?? 0);

$newUsers = $db->fetch(
    "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND is_active = 1",
    [$days]
);
$userStats['new'] = (int)($newUsers['count'] ?? 0);

// Try to get active users from user_sessions table
$activeUsers = $db->fetch(
    "SELECT COUNT(DISTINCT user_id) as count FROM user_sessions WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
);
$userStats['active'] = (int)($activeUsers['count'] ?? 0);

// Get users by role
$usersByRole = $db->fetchAll(
    "SELECT role, COUNT(*) as count FROM users WHERE is_active = 1 GROUP BY role"
);
$userStats['by_role'] = $usersByRole;

// Content analytics
$contentStats = [
    'total' => 0,
    'by_type' => [],
    'total_views' => 0,
    'featured' => 0
];

$totalContent = $db->fetch("SELECT COUNT(*) as count FROM content WHERE is_active = 1");
$contentStats['total'] = (int)($totalContent['count'] ?? 0);

$contentByType = $db->fetchAll(
    "SELECT content_type, COUNT(*) as count FROM content WHERE is_active = 1 GROUP BY content_type"
);
$contentStats['by_type'] = $contentByType;

$totalViews = $db->fetch("SELECT SUM(view_count) as total FROM content WHERE is_active = 1");
$contentStats['total_views'] = (int)($totalViews['total'] ?? 0);

$featuredContent = $db->fetch("SELECT COUNT(*) as count FROM content WHERE is_featured = 1 AND is_active = 1");
$contentStats['featured'] = (int)($featuredContent['count'] ?? 0);

// Engagement analytics
$engagement = [
    'total_ratings' => 0,
    'total_comments' => 0,
    'total_favorites' => 0,
    'total_playlists' => 0
];

// Check if ratings table exists
$ratings = $db->fetch("SELECT COUNT(*) as count FROM ratings");
$engagement['total_ratings'] = (int)($ratings['count'] ?? 0);

// Check if comments table exists
$comments = $db->fetch("SELECT COUNT(*) as count FROM comments");
$engagement['total_comments'] = (int)($comments['count'] ?? 0);

// Check if favorites table exists
$favorites = $db->fetch("SELECT COUNT(*) as count FROM favorites");
$engagement['total_favorites'] = (int)($favorites['count'] ?? 0);

// Check if playlists table exists
$playlists = $db->fetch("SELECT COUNT(*) as count FROM playlists");
$engagement['total_playlists'] = (int)($playlists['count'] ?? 0);

// Subscription analytics
$subscriptionStats = [
    'total_subscriptions' => 0,
    'by_plan' => [],
    'revenue' => 0
];

$totalSubs = $db->fetch("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
$subscriptionStats['total_subscriptions'] = (int)($totalSubs['count'] ?? 0);

$byPlan = $db->fetchAll(
    "SELECT 
        COALESCE(s.type, 'free') as plan_type,
        COUNT(*) as count,
        COALESCE(SUM(s.amount), 0) as revenue
    FROM subscriptions s
    WHERE s.status = 'active'
    GROUP BY s.type"
);
$subscriptionStats['by_plan'] = $byPlan;

$totalRevenue = $db->fetch(
    "SELECT COALESCE(SUM(amount), 0) as total FROM subscriptions WHERE status = 'active'"
);
$subscriptionStats['revenue'] = (float)($totalRevenue['total'] ?? 0);

// Content performance (top 10)
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

// Daily views for chart (last 30 days) - using watch_history table
$dailyViews = $db->fetchAll(
    "SELECT 
        DATE(watched_at) as date,
        COUNT(*) as views
    FROM watch_history
    WHERE watched_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY DATE(watched_at)
    ORDER BY date",
    [$days]
);

// User growth data (monthly for last 12 months)
$userGrowth = $db->fetchAll(
    "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as new_users
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month"
);

// Content added by month (last 12 months)
$contentGrowth = $db->fetchAll(
    "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as new_content
    FROM content
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month"
);

Response::success('Analytics retrieved successfully', [
    'period' => $period,
    'days' => $days,
    'users' => $userStats,
    'content' => $contentStats,
    'engagement' => $engagement,
    'subscriptions' => $subscriptionStats,
    'top_content' => $topContent,
    'daily_views' => $dailyViews,
    'user_growth' => $userGrowth,
    'content_growth' => $contentGrowth,
    'generated_at' => date('Y-m-d H:i:s')
], 200);
