<?php
/**
 * User Profile Endpoint
 * Returns ALL user data from the users table
 */

// Turn off error display for API responses
error_reporting(0);
ini_set('display_errors', 0);

// Include configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Set content type
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

try {
    $db = Database::getInstance();
    $auth = new Auth();

    // Get current user
    $user = $auth->getCurrentUser();

    if (!$user) {
        Response::error('Unauthorized', 401);
    }

    // Fetch ALL user data from database
    $userData = $db->fetch("SELECT * FROM users WHERE id = ?", [$user['id']]);

    if (!$userData) {
        Response::error('User not found', 404);
    }

    // Return ALL fields from users table
    $profile = [
        // Primary fields
        'id' => (int)$userData['id'],
        'name' => $userData['name'] ?? '',
        'email' => $userData['email'] ?? '',
        'username' => $userData['username'] ?? null,
        
        // Profile fields
        'avatar_url' => $userData['avatar_url'] ?? '',
        'avatar' => $userData['avatar'] ?? $userData['avatar_url'] ?? '',
        'banner_url' => $userData['banner_url'] ?? '',
        'bio' => $userData['bio'] ?? '',
        'location' => $userData['location'] ?? '',
        'website' => $userData['website'] ?? '',
        'date_of_birth' => $userData['date_of_birth'] ?? null,
        'preferences' => $userData['preferences'] ?? null,
        
        // Account fields
        'subscription_plan' => $userData['subscription_plan'] ?? 'free',
        'role' => $userData['role'] ?? 'user',
        'is_active' => (bool)($userData['is_active'] ?? true),
        'email_verified_at' => $userData['email_verified_at'] ?? null,
        'remember_token' => $userData['remember_token'] ?? null,
        
        // Timestamps
        'created_at' => $userData['created_at'] ?? null,
        'updated_at' => $userData['updated_at'] ?? null,
        
        // Stats (derived data)
        'subscribers_count' => 0,
        'following_count' => 0,
        'total_views' => 0
    ];

    // Try to get subscriber count from channels table
    try {
        $result = $db->fetch(
            "SELECT COUNT(*) as count FROM channels WHERE user_id = ?",
            [$user['id']]
        );
        if ($result) {
            $profile['subscribers_count'] = (int)$result['count'];
        }
    } catch (Exception $e) {
        // Table might not exist
    }

    Response::success('Profile retrieved successfully', [
        'user' => $profile
    ], 200);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
    exit;
}
