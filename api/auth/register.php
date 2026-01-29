<?php
/**
 * Register Endpoint
 * Creates new user accounts
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
$validation = new Validation();
$auth = new Auth();

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$errors = [];

if (empty($input['name'])) {
    $errors['name'] = 'Full name is required';
}

if (empty($input['email'])) {
    $errors['email'] = 'Email is required';
}

if (empty($input['password'])) {
    $errors['password'] = 'Password is required';
}

if (!empty($errors)) {
    Response::validationError($errors);
}

// Validate name length
if (strlen($input['name']) < 2) {
    Response::validationError(['name' => 'Name must be at least 2 characters']);
}

// Validate email format
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    Response::validationError(['email' => 'Invalid email format']);
}

// Validate password strength
if (strlen($input['password']) < 8) {
    Response::validationError(['password' => 'Password must be at least 8 characters']);
}

// Check if email already exists
$existingUser = $db->fetch(
    "SELECT id FROM users WHERE email = ?",
    [$input['email']]
);

if ($existingUser) {
    Response::error('An account with this email already exists', 400);
}

// Hash password
$hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);

// Create user
$userId = $db->insert('users', [
    'name' => trim($input['name']),
    'email' => strtolower(trim($input['email'])),
    'password' => $hashedPassword,
    'subscription_plan' => 'free',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
]);

if (!$userId) {
    Response::error('Failed to create account. Please try again.', 500);
}

// Get created user
$user = $db->fetch(
    "SELECT id, name, email, avatar_url, subscription_plan, created_at FROM users WHERE id = ?",
    [$userId]
);

// Generate JWT token
$token = $auth->generateToken($userId);

// Remove password from response
unset($user['password']);

// Return success response with token
Response::success('Account created successfully', [
    'user' => $user,
    'token' => $token
], 201);

