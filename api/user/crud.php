<?php
/**
 * User CRUD Endpoint
 * Handles all CRUD operations: Create, Read, Update, Delete for user profile
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
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Only allow authenticated requests for most operations
$db = Database::getInstance();
$auth = new Auth();
$user = $auth->getCurrentUser();

// Handle GET (Read profile)
if ($method === 'GET') {
    if (!$user) {
        Response::error('Unauthorized', 401);
    }

    // Fetch full user data
    $userData = $db->fetch("SELECT * FROM users WHERE id = ?", [$user['id']]);

    if (!$userData) {
        Response::error('User not found', 404);
    }

    // Build profile response
    $profile = [
        'id' => (int)$userData['id'],
        'name' => $userData['name'] ?? '',
        'email' => $userData['email'] ?? '',
        'username' => $userData['username'] ?? '',
        'avatar_url' => $userData['avatar_url'] ?? '',
        'avatar' => $userData['avatar'] ?? $userData['avatar_url'] ?? '',
        'banner_url' => $userData['banner_url'] ?? '',
        'bio' => $userData['bio'] ?? '',
        'location' => $userData['location'] ?? '',
        'website' => $userData['website'] ?? '',
        'date_of_birth' => $userData['date_of_birth'] ?? null,
        'subscription_plan' => $userData['subscription_plan'] ?? 'free',
        'role' => $userData['role'] ?? 'user',
        'is_active' => (bool)$userData['is_active'],
        'email_verified_at' => $userData['email_verified_at'] ?? null,
        'created_at' => $userData['created_at'] ?? null,
        'updated_at' => $userData['updated_at'] ?? null
    ];

    Response::success('Profile retrieved successfully', [
        'user' => $profile
    ], 200);
}

// Handle POST (Create - not applicable for own profile, but could be admin only)
if ($method === 'POST') {
    Response::error('Use /api/auth/register.php to create new users', 405);
}

// Handle PUT (Update profile)
if ($method === 'PUT') {
    if (!$user) {
        Response::error('Unauthorized', 401);
    }

    // Check if multipart request (file upload)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // Handle file upload + form data
        $updateData = [];
        $avatarUrl = null;
        $bannerUrl = null;

        // Handle avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024;

            if (!in_array($file['type'], $allowedTypes)) {
                Response::error('Invalid avatar file type. Allowed: JPEG, PNG, GIF, WebP', 400);
            }
            if ($file['size'] > $maxSize) {
                Response::error('Avatar file size must be less than 5MB', 400);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $extension;
            $uploadDir = UPLOAD_PATH . 'avatars/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destination = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $avatarUrl = 'uploads/avatars/' . $filename;
                $updateData['avatar_url'] = $avatarUrl;
                $updateData['avatar'] = $avatarUrl;
            }
        }

        // Handle banner upload
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['banner'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 10 * 1024 * 1024;

            if (!in_array($file['type'], $allowedTypes)) {
                Response::error('Invalid banner file type. Allowed: JPEG, PNG, GIF, WebP', 400);
            }
            if ($file['size'] > $maxSize) {
                Response::error('Banner file size must be less than 10MB', 400);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'banner_' . $user['id'] . '_' . time() . '.' . $extension;
            $uploadDir = UPLOAD_PATH . 'banners/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destination = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $bannerUrl = 'uploads/banners/' . $filename;
                $updateData['banner_url'] = $bannerUrl;
            }
        }

        // Handle text fields
        if (isset($_POST['name']) && !empty($_POST['name'])) {
            $updateData['name'] = htmlspecialchars(trim($_POST['name']));
        }
        if (isset($_POST['username']) && !empty($_POST['username'])) {
            // Check username availability
            $existing = $db->fetch("SELECT id FROM users WHERE username = ? AND id != ?", 
                [$_POST['username'], $user['id']]);
            if ($existing) {
                Response::error('Username is already taken', 409);
            }
            $updateData['username'] = htmlspecialchars(trim($_POST['username']));
        }
        if (isset($_POST['bio'])) {
            $updateData['bio'] = htmlspecialchars(trim($_POST['bio'] ?? ''));
        }
        if (isset($_POST['location'])) {
            $updateData['location'] = htmlspecialchars(trim($_POST['location']));
        }
        if (isset($_POST['website'])) {
            $updateData['website'] = filter_var(trim($_POST['website']), FILTER_SANITIZE_URL);
        }
        if (isset($_POST['date_of_birth'])) {
            $updateData['date_of_birth'] = $_POST['date_of_birth'];
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        if (!empty($updateData) && count($updateData) > 1) {
            $db->update('users', $updateData, 'id = ?', [$user['id']]);
        }

        $updatedUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$user['id']]);

        Response::success('Profile updated successfully', [
            'user' => $updatedUser,
            'avatar_url' => $avatarUrl,
            'banner_url' => $bannerUrl
        ], 200);

    } else {
        // Handle JSON request
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input)) {
            Response::error('No data provided', 400);
        }

        $updateData = [];

        if (isset($input['name'])) {
            $updateData['name'] = htmlspecialchars(trim($input['name']));
        }
        if (isset($input['username'])) {
            $existing = $db->fetch("SELECT id FROM users WHERE username = ? AND id != ?", 
                [$input['username'], $user['id']]);
            if ($existing) {
                Response::error('Username is already taken', 409);
            }
            $updateData['username'] = htmlspecialchars(trim($input['username']));
        }
        if (isset($input['bio'])) {
            $updateData['bio'] = htmlspecialchars(trim($input['bio']));
        }
        if (isset($input['location'])) {
            $updateData['location'] = htmlspecialchars(trim($input['location']));
        }
        if (isset($input['website'])) {
            $updateData['website'] = filter_var(trim($input['website']), FILTER_SANITIZE_URL);
        }
        if (isset($input['date_of_birth'])) {
            $updateData['date_of_birth'] = $input['date_of_birth'];
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        if (count($updateData) <= 1) {
            Response::error('No valid fields to update', 400);
        }

        $db->update('users', $updateData, 'id = ?', [$user['id']]);

        $updatedUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$user['id']]);

        Response::success('Profile updated successfully', [
            'user' => $updatedUser
        ], 200);
    }
}

// Handle DELETE (Delete account)
if ($method === 'DELETE') {
    if (!$user) {
        Response::error('Unauthorized', 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['password'])) {
        Response::error('Password is required to delete account', 400);
    }

    // Verify password
    $userData = $db->fetch("SELECT password FROM users WHERE id = ?", [$user['id']]);
    
    if (!$userData || !password_verify($input['password'], $userData['password'])) {
        Response::error('Incorrect password', 401);
    }

    // Delete related data
    $db->delete('watch_histories', 'user_id = ?', [$user['id']]);
    $db->delete('watch_later', 'user_id = ?', [$user['id']]);
    $db->delete('favorites', 'user_id = ?', [$user['id']]);
    $db->delete('ratings', 'user_id = ?', [$user['id']]);
    $db->delete('comments', 'user_id = ?', [$user['id']]);
    $db->delete('notifications', 'user_id = ?', [$user['id']]);
    $db->delete('subscriptions', 'user_id = ?', [$user['id']]);
    $db->delete('channel_subscriptions', 'user_id = ?', [$user['id']]);
    
    $playlists = $db->fetchAll("SELECT id FROM playlists WHERE user_id = ?", [$user['id']]);
    foreach ($playlists as $playlist) {
        $db->delete('playlist_items', 'playlist_id = ?', [$playlist['id']]);
    }
    $db->delete('playlists', 'user_id = ?', [$user['id']]);

    $deleted = $db->delete('users', 'id = ?', [$user['id']]);

    if ($deleted) {
        Response::success('Account deleted successfully', [
            'deleted_user_id' => $user['id']
        ], 200);
    } else {
        Response::error('Failed to delete account', 500);
    }
}
