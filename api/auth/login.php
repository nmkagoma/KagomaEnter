<?php
/**
 * Login Endpoint
 * Authenticates users and returns JWT token
 */

// Include required files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Validation.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Set content type
header('Content-Type: application/json');

// Enable CORS - allow all origins for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

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

if (empty($input['email'])) {
    $errors['email'] = 'Email is required';
}

if (empty($input['password'])) {
    $errors['password'] = 'Password is required';
}

if (!empty($errors)) {
    Response::validationError($errors);
}

// Validate email format
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    Response::validationError(['email' => 'Invalid email format']);
}

// Find user by email
$user = $db->fetch(
    "SELECT id, name, email, password, role, is_active, avatar_url, subscription_plan, created_at, updated_at FROM users WHERE email = ?",
    [$input['email']]
);

if (!$user) {
    Response::error('Invalid email or password', 401);
}

// Check if user is active
if (!$user['is_active']) {
    Response::error('Your account has been deactivated. Please contact support.', 401);
}

// Verify password
if (!password_verify($input['password'], $user['password'])) {
    Response::error('Invalid email or password', 401);
}

// Generate JWT token with role
$token = $auth->generateToken($user['id']);

// Update user's last login timestamp
$db->update('users',
    [
        'remember_token' => null,
        'updated_at' => date('Y-m-d H:i:s')
    ],
    'id = ?',
    [$user['id']]
);

// Get user's subscription details
$subscription = $db->fetch(
    "SELECT type, status, starts_at, ends_at FROM subscriptions WHERE user_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1",
    [$user['id']]
);

// Remove sensitive data from response
unset($user['password']);
unset($user['remember_token']);

// Build enhanced user response
$userData = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role'],
    'is_active' => $user['is_active'],
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

// Determine redirect URL based on role
$redirectUrl = 'index.html';
if ($user['role'] === 'admin') {
    $redirectUrl = 'admin/index.html'; // Admin dashboard
} elseif ($user['role'] === 'user') {
    $redirectUrl = 'index.html'; // Regular user home
}

// Return success response with token and role info
Response::success('Login successful', [
    'user' => $userData,
    'token' => $token,
    'role' => $user['role'],
    'redirect_url' => $redirectUrl
], 200);

