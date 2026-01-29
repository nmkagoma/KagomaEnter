<?php
/**
 * Verify Token Endpoint
 * Validates JWT token and returns user data
 */

// Include required files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Validation.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Set content type
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET and POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Initialize classes
$db = Database::getInstance();
$auth = new Auth();

// Get current user from token
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Invalid or expired token', 401);
}

// Get fresh user data from database (to ensure account is still active)
$freshUser = $db->fetch(
    "SELECT id, name, email, avatar_url, subscription_plan, created_at FROM users WHERE id = ?",
    [$user['id']]
);

if (!$freshUser) {
    Response::error('User not found', 401);
}

// Remove password from response
unset($freshUser['password']);

// Return success response with user data
Response::success('Token is valid', [
    'user' => $freshUser
], 200);

