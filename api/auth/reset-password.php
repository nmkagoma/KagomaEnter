<?php
/**
 * Reset Password Endpoint
 * Resets user password using valid reset token
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$db = Database::getInstance();
$validation = new Validation();

// Get request body and query parameter
$input = json_decode(file_get_contents('php://input'), true);
$token = $_GET['token'] ?? $input['token'] ?? null;

// Validate token
if (empty($token)) {
    Response::validationError(['token' => 'Reset token is required']);
}

// Validate password
if (empty($input['password'])) {
    Response::validationError(['password' => 'New password is required']);
}

if (strlen($input['password']) < 8) {
    Response::validationError(['password' => 'Password must be at least 8 characters']);
}

if ($input['password'] !== ($input['password_confirmation'] ?? '')) {
    Response::validationError(['password_confirmation' => 'Passwords do not match']);
}

// Find user with this token
$user = $db->fetch(
    "SELECT id FROM users WHERE remember_token = ?",
    [$token]
);

if (!$user) {
    Response::error('Invalid or expired reset token', 400);
}

// Update password
$hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);

$updated = $db->update('users',
    [
        'password' => $hashedPassword,
        'remember_token' => null,
        'updated_at' => date('Y-m-d H:i:s')
    ],
    'id = ?',
    [$user['id']]
);

if ($updated) {
    Response::success('Password has been reset successfully. You can now login with your new password.', null, 200);
} else {
    Response::error('Failed to reset password', 500);
}

