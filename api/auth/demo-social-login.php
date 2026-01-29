<?php
/**
 * Demo Social Login
 * This is a mock login for testing purposes only
 * In production, use real OAuth credentials from Google/Facebook
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

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($input['provider'])) {
    Response::error('Provider is required', 400);
}

$provider = strtolower($input['provider']);

// Demo mode - create mock user
$db = Database::getInstance();
$auth = new Auth();

// Demo users for each provider
$demoUsers = [
    'google' => [
        'name' => 'Google User',
        'email' => 'google.user' . time() . '@gmail.com',
        'avatar_url' => 'https://ui-avatars.com/api/?name=Google+User&background=4285f4&color=fff'
    ],
    'facebook' => [
        'name' => 'Facebook User',
        'email' => 'fb.user' . time() . '@facebook.com',
        'avatar_url' => 'https://ui-avatars.com/api/?name=Facebook+User&background=1877f2&color=fff'
    ]
];

if (!isset($demoUsers[$provider])) {
    Response::error('Invalid provider. Use google or facebook', 400);
}

$demoUser = $demoUsers[$provider];

// Check if user already exists
$existingUser = $db->fetch(
    "SELECT * FROM users WHERE email = ?",
    [$demoUser['email']]
);

if ($existingUser) {
    // User exists, log them in
    $user = $existingUser;
} else {
    // Create new user
    $userData = [
        'name' => $demoUser['name'],
        'email' => $demoUser['email'],
        'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
        'role' => 'user',
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $userId = $db->insert('users', $userData);
    
    if (!$userId) {
        Response::error('Failed to create user', 500);
    }
    
    // Create user profile with avatar
    $profileData = [
        'user_id' => $userId,
        'avatar' => $demoUser['avatar_url'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    $db->insert('user_profiles', $profileData);
    
    // Create free subscription
    $subscriptionData = [
        'user_id' => $userId,
        'type' => 'free',
        'status' => 'active',
        'starts_at' => date('Y-m-d H:i:s'),
        'ends_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    $db->insert('subscriptions', $subscriptionData);
    
    // Get the created user
    $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
}

// Generate JWT token
$token = $auth->generateToken($user['id']);

// Remove password from response
unset($user['password']);
unset($user['remember_token']);

// Build user response
$userData = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role'],
    'is_active' => $user['is_active'],
    'avatar_url' => $demoUser['avatar_url'],
    'subscription_plan' => 'free',
    'subscription' => [
        'type' => 'free',
        'status' => 'active',
        'starts_at' => date('Y-m-d H:i:s'),
        'ends_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
    ],
    'created_at' => $user['created_at'],
    'updated_at' => $user['updated_at']
];

// Determine redirect URL based on role
$redirectUrl = 'index.html';
if ($user['role'] === 'admin') {
    $redirectUrl = 'admin/index.html';
}

// Return success response
Response::success('Login successful', [
    'user' => $userData,
    'token' => $token,
    'role' => $user['role'],
    'redirect_url' => $redirectUrl,
    'provider' => $provider,
    'demo_mode' => true
], 200);

