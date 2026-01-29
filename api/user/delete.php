<?php
/**
 * User Delete Endpoint
 * Delete user account (requires password confirmation)
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
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);

    // Require password confirmation
    if (empty($input['password'])) {
        Response::error('Password is required to delete account', 400);
    }

    // Verify password
    $userData = $db->fetch("SELECT password FROM users WHERE id = ?", [$user['id']]);
    
    if (!$userData || !password_verify($input['password'], $userData['password'])) {
        Response::error('Incorrect password', 401);
    }

    // Delete user's related data first (foreign key constraints)
    // Delete watch history
    $db->delete('watch_histories', 'user_id = ?', [$user['id']]);
    
    // Delete watch later
    $db->delete('watch_later', 'user_id = ?', [$user['id']]);
    
    // Delete favorites
    $db->delete('favorites', 'user_id = ?', [$user['id']]);
    
    // Delete ratings
    $db->delete('ratings', 'user_id = ?', [$user['id']]);
    
    // Delete comments
    $db->delete('comments', 'user_id = ?', [$user['id']]);
    
    // Delete playlists
    $playlists = $db->fetchAll("SELECT id FROM playlists WHERE user_id = ?", [$user['id']]);
    foreach ($playlists as $playlist) {
        $db->delete('playlist_items', 'playlist_id = ?', [$playlist['id']]);
    }
    $db->delete('playlists', 'user_id = ?', [$user['id']]);
    
    // Delete notifications
    $db->delete('notifications', 'user_id = ?', [$user['id']]);
    
    // Delete subscriptions
    $db->delete('subscriptions', 'user_id = ?', [$user['id']]);
    
    // Delete channel subscriptions
    $db->delete('channel_subscriptions', 'user_id = ?', [$user['id']]);
    
    // Finally delete the user
    $deleted = $db->delete('users', 'id = ?', [$user['id']]);

    if ($deleted) {
        // Clear auth tokens
        Response::success('Account deleted successfully', [
            'deleted_user_id' => $user['id']
        ], 200);
    } else {
        Response::error('Failed to delete account', 500);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
    exit;
}
