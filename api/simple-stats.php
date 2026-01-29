<?php
/**
 * Simple Stats API - Direct database access without authentication
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Direct database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "KagomaEnter";
$socket = "/opt/lampp/var/mysql/mysql.sock";

$conn = new mysqli($host, $user, $pass, $dbname, 3306, $socket);

if ($conn->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Connection failed: ' . $conn->connect_error
    ]));
}

$conn->set_charset("utf8mb4");

header('Content-Type: application/json');

// Get users count and recent users
$usersResult = $conn->query("SELECT id, name, email, role, is_active, created_at FROM users ORDER BY created_at DESC LIMIT 10");
$users = [];
while ($row = $usersResult->fetch_assoc()) {
    $users[] = $row;
}

// Get content count and top content
$contentResult = $conn->query("SELECT id, title, content_type, view_count, rating, thumbnail, is_active FROM content WHERE is_active = 1 ORDER BY view_count DESC LIMIT 10");
$content = [];
while ($row = $contentResult->fetch_assoc()) {
    $content[] = $row;
}

// Get stats
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1")->fetch_assoc()['count'];
$totalContent = $conn->query("SELECT COUNT(*) as count FROM content WHERE is_active = 1")->fetch_assoc()['count'];
$totalViews = $conn->query("SELECT SUM(view_count) as total FROM content WHERE is_active = 1")->fetch_assoc()['total'];
$totalViews = $totalViews ?: 0;

// Get content by type
$contentByTypeResult = $conn->query("SELECT content_type, COUNT(*) as count FROM content WHERE is_active = 1 GROUP BY content_type");
$contentByType = [];
while ($row = $contentByTypeResult->fetch_assoc()) {
    $contentByType[] = $row;
}

// Build response
$response = [
    'status' => 'success',
    'message' => 'Data retrieved successfully',
    'data' => [
        'users' => [
            'total' => (int)$totalUsers,
            'recent' => $users
        ],
        'content' => [
            'total' => (int)$totalContent,
            'by_type' => $contentByType,
            'top' => $content
        ],
        'views' => [
            'total' => (int)$totalViews
        ],
        'generated_at' => date('Y-m-d H:i:s')
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);

$conn->close();

