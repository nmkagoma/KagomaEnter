<?php
/**
 * Forgot Password Endpoint
 * Sends password reset email to user
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

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

// Validate email
if (empty($input['email'])) {
    Response::validationError(['email' => 'Email is required']);
}

if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    Response::validationError(['email' => 'Invalid email format']);
}

// Check if user exists
$user = $db->fetch(
    "SELECT id, name, email FROM users WHERE email = ?",
    [$input['email']]
);

if (!$user) {
    // Don't reveal if user exists or not
    Response::success('If an account exists with this email, a password reset link will be sent.', null, 200);
}

// Generate reset token
$resetToken = bin2hex(random_bytes(32));
$resetExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Save reset token to database
$db->update('users',
    [
        'remember_token' => $resetToken,
        'updated_at' => date('Y-m-d H:i:s')
    ],
    'id = ?',
    [$user['id']]
);

// In production, send email here
// For now, we'll return the reset link for testing
$resetLink = BASE_URL . 'api/auth/reset-password.php?token=' . $resetToken;

Response::success('If an account exists with this email, a password reset link will be sent.', [
    'reset_link' => $resetLink, // Remove in production
    'message' => 'In production, an email would be sent to ' . $user['email']
], 200);

