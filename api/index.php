<?php
/**
 * API Entry Point - KagomaEnter Streaming Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers - allow all origins for development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");
header("Access-Control-Max-Age: 86400");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include required files
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Get the endpoint from URL
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/api/', '', $uri);
$parts = explode('/', $uri);
$endpoint = $parts[0] ?? '';
$action = $parts[1] ?? '';

// Simple routing
$routes = [
    'auth' => [
        'login' => '/api/auth/login.php',
        'register' => '/api/auth/register.php',
        'logout' => '/api/auth/logout.php',
    ],
    'admin' => [
        'stats' => '/api/admin/stats.php',
        'analytics' => '/api/admin/analytics.php',
        'users' => '/api/admin/users.php',
        'content' => '/api/admin/content.php',
    ],
    'content' => [
        'list' => '/api/content/list.php',
        'featured' => '/api/content/featured.php',
        'trending' => '/api/content/trending.php',
        'detail' => '/api/content/detail.php',
    ],
    'genres' => [
        'list' => '/api/genres/list.php',
    ],
    'user' => [
        'profile' => '/api/user/profile.php',
        'history' => '/api/user/history.php',
    ],
];

// Route the request
if (isset($routes[$endpoint][$action])) {
    $file = __DIR__ . $routes[$endpoint][$action];
    if (file_exists($file)) {
        require_once $file;
        exit;
    }
}

// Health check endpoint
if ($endpoint === 'health' || $uri === 'health') {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $users = $result->fetch_assoc();
        
        $result = $conn->query("SELECT COUNT(*) as count FROM content");
        $content = $result->fetch_assoc();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'API is running',
            'data' => [
                'database' => 'connected',
                'users_count' => (int)$users['count'],
                'content_count' => (int)$content['count']
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Default response for unknown endpoints
echo json_encode([
    'status' => 'error',
    'message' => 'Endpoint not found',
    'available_endpoints' => [
        'health' => 'Check API health',
        'auth/login' => 'User login',
        'auth/register' => 'User registration',
        'admin/stats' => 'Admin dashboard stats',
        'admin/analytics' => 'Admin analytics',
        'admin/users' => 'Admin user management',
        'admin/content' => 'Admin content management',
        'content/list' => 'List all content',
        'content/featured' => 'Get featured content',
        'content/trending' => 'Get trending content',
    ]
]);
