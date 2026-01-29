<?php
/**
 * Logout Endpoint
 * Invalidates user token and logs them out
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

// Initialize classes
$db = Database::getInstance();
$auth = new Auth();

// Get current user
$user = $auth->getCurrentUser();

// If user is logged in, invalidate their token in database
if ($user) {
    $db->update('users',
        [
            'remember_token' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'id = ?',
        [$user['id']]
    );
}

// Note: JWT tokens are stateless, so we can't truly "invalidate" them
// without a token blacklist. However, we can clear the remember_token
// which is used for password reset and other secure operations.
// The frontend should remove the token from localStorage.

// Return success response
Response::success('You have been logged out successfully', null, 200);

