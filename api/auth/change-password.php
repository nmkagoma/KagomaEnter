<?php
/**
 * Change Password Endpoint
 * Allows authenticated users to change their password
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
$validation = new Validation();

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$errors = [];

if (empty($input['current_password'])) {
    $errors['current_password'] = 'Current password is required';
}

if (empty($input['new_password'])) {
    $errors['new_password'] = 'New password is required';
} elseif (strlen($input['new_password']) < 8) {
    $errors['new_password'] = 'New password must be at least 8 characters';
}

if ($input['new_password'] !== ($input['confirm_password'] ?? '')) {
    $errors['confirm_password'] = 'Passwords do not match';
}

if (!empty($errors)) {
    Response::validationError($errors);
}

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

// Change password
$result = $auth->changePassword(
    $user['id'],
    $input['current_password'],
    $input['new_password']
);

if ($result['success']) {
    Response::success($result['message'], null, 200);
} else {
    Response::error($result['message'], 400);
}

