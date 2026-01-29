<?php
/**
 * Mark Notification as Read Endpoint
 * Marks a notification as read
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
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow PUT requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Method not allowed', 405);
}

$db = Database::getInstance();
$auth = new Auth();

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$notificationId = (int)($input['id'] ?? $_GET['id'] ?? 0);

if ($notificationId === 0) {
    Response::validationError(['id' => 'Notification ID is required']);
}

// Check if notification exists and belongs to user
$notification = $db->fetch(
    "SELECT id, user_id, is_read FROM notifications WHERE id = ?",
    [$notificationId]
);

if (!$notification) {
    Response::error('Notification not found', 404);
}

if ($notification['user_id'] !== $user['id']) {
    Response::error('Forbidden', 403);
}

// Mark as read
$updated = $db->update('notifications',
    [
        'is_read' => 1,
        'read_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    'id = ?',
    [$notificationId]
);

if ($updated) {
    Response::success('Notification marked as read', null, 200);
} else {
    Response::error('Failed to mark notification as read', 500);
}

