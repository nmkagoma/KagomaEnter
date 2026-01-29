<?php
/**
 * Verify Email Token Endpoint
 * Verifies email verification token and activates account
 */

// Include required files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
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

// Initialize classes
$db = Database::getInstance();
$auth = new Auth();

// Get request parameters
$token = $_GET['token'] ?? ($_POST['token'] ?? null);
$userId = $_GET['user_id'] ?? ($_POST['user_id'] ?? null);

// Validate required fields
if (empty($token)) {
    Response::error('Verification token is required', 400);
}

if (empty($userId)) {
    Response::error('User ID is required', 400);
}

// Get stored token
$storedToken = $db->fetch(
    "SELECT * FROM email_verification_tokens WHERE user_id = ? AND expires_at > NOW()",
    [$userId]
);

if (!$storedToken) {
    Response::error('Invalid or expired verification token', 400);
}

// Verify token
if (!hash_equals($storedToken['token'], hash('sha256', $token))) {
    Response::error('Invalid verification token', 400);
}

// Get user
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);

if (!$user) {
    Response::error('User not found', 404);
}

if ($user['email_verified_at']) {
    Response::error('Email is already verified', 400);
}

// Update user
$db->update('users',
    [
        'email_verified_at' => date('Y-m-d H:i:s'),
        'is_active' => 1,
        'updated_at' => date('Y-m-d H:i:s')
    ],
    'id = ?',
    [$userId]
);

// Delete used token
$db->delete('email_verification_tokens', 'user_id = ?', [$userId]);

// Generate JWT token for auto-login
$token = $auth->generateToken($userId);

// Get subscription
$subscription = $db->fetch(
    "SELECT type, status, starts_at, ends_at FROM subscriptions WHERE user_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1",
    [$userId]
);

$userData = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role'],
    'is_active' => 1,
    'email_verified_at' => date('Y-m-d H:i:s'),
    'avatar_url' => $user['avatar_url'] ?? null,
    'subscription_plan' => $user['subscription_plan'] ?? 'free',
    'subscription' => $subscription ? [
        'type' => $subscription['type'],
        'status' => $subscription['status'],
        'starts_at' => $subscription['starts_at'],
        'ends_at' => $subscription['ends_at']
    ] : null,
    'created_at' => $user['created_at'],
    'updated_at' => $user['updated_at']
];

Response::success('Email verified successfully!', [
    'user' => $userData,
    'token' => $token
], 200);
?>

