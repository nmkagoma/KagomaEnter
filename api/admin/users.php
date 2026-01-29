<?php
/**
 * Admin Users Management Endpoint
 * Enhanced user management for admins
 */

// Include configuration and stubs for IDE intellisense
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/stubs.php';
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

$db = Database::getInstance();
$auth = new Auth();

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

if ($user['role'] !== 'admin') {
    Response::error('Forbidden. Admin access required', 403);
}

$method = $_SERVER['REQUEST_METHOD'];

// GET - List/Filter users
if ($method === 'GET') {
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $status = $_GET['status'] ?? 'all'; // all, active, inactive
    $role = $_GET['role'] ?? null;
    $search = $_GET['search'] ?? null;
    
    $offset = ($page - 1) * $limit;
    
    $where = "1=1";
    $params = [];
    
    if ($status === 'active') {
        $where .= " AND u.is_active = 1";
    } elseif ($status === 'inactive') {
        $where .= " AND u.is_active = 0";
    }
    
    if ($role) {
        $where .= " AND u.role = ?";
        $params[] = $role;
    }
    
    if ($search) {
        $where .= " AND (u.name LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Get total count
    $total = $db->fetch(
        "SELECT COUNT(*) as count FROM users u WHERE $where",
        $params
    );
    $total = $total['count'] ?? 0;
    
    // Get users with profile info
    $sql = "SELECT 
        u.id, u.name, u.email, u.role, u.is_active, 
        u.created_at, u.updated_at,
        up.avatar, up.location,
        (SELECT COUNT(*) FROM watch_histories WHERE user_id = u.id) as watch_count,
        (SELECT COUNT(*) FROM favorites WHERE user_id = u.id) as favorites_count,
        (SELECT COUNT(*) FROM playlists WHERE user_id = u.id) as playlists_count
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE $where
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $users = $db->fetchAll($sql, $params);
    
    // Get user statistics
    $userStats = [
        'total' => $total,
        'admins' => 0,
        'users' => 0,
        'active' => 0
    ];
    
    $admins = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $userStats['admins'] = (int)($admins['count'] ?? 0);
    
    $regularUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $userStats['users'] = (int)($regularUsers['count'] ?? 0);
    
    $activeUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
    $userStats['active'] = (int)($activeUsers['count'] ?? 0);
    
    Response::success('Users retrieved successfully', [
        'users' => $users,
        'statistics' => $userStats,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ], 200);
}

// POST - Create user (admin)
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate
    if (empty($input['name'])) {
        Response::validationError(['name' => 'Name is required']);
    }
    
    if (empty($input['email'])) {
        Response::validationError(['email' => 'Email is required']);
    }
    
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        Response::validationError(['email' => 'Invalid email format']);
    }
    
    if (empty($input['password'])) {
        Response::validationError(['password' => 'Password is required']);
    }
    
    if (strlen($input['password']) < 8) {
        Response::validationError(['password' => 'Password must be at least 8 characters']);
    }
    
    // Check if email exists
    $existing = $db->fetch(
        "SELECT id FROM users WHERE email = ?",
        [$input['email']]
    );
    
    if ($existing) {
        Response::error('Email already exists', 409);
    }
    
    // Create user
    $userData = [
        'name' => htmlspecialchars($input['name']),
        'email' => $input['email'],
        'password' => password_hash($input['password'], PASSWORD_DEFAULT),
        'role' => $input['role'] ?? 'user',
        'is_active' => (int)($input['is_active'] ?? 1),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $userId = $db->insert('users', $userData);
    
    if ($userId) {
        // Create profile
        $db->insert('user_profiles', [
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Create free subscription
        $db->insert('subscriptions', [
            'user_id' => $userId,
            'type' => 'free',
            'status' => 'active',
            'starts_at' => date('Y-m-d H:i:s'),
            'ends_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $newUser = $db->fetch("SELECT id, name, email, role, is_active FROM users WHERE id = ?", [$userId]);
        
        Response::success('User created successfully', [
            'user' => $newUser
        ], 201);
    } else {
        Response::error('Failed to create user', 500);
    }
}

// PUT - Update user
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = (int)($input['id'] ?? 0);
    
    if ($userId === 0) {
        Response::validationError(['id' => 'User ID is required']);
    }
    
    $existingUser = $db->fetch("SELECT id, email FROM users WHERE id = ?", [$userId]);
    
    if (!$existingUser) {
        Response::error('User not found', 404);
    }
    
    // Prepare update data
    $updateData = [];
    
    if (isset($input['name'])) {
        $updateData['name'] = htmlspecialchars($input['name']);
    }
    
    if (isset($input['email']) && $input['email'] !== $existingUser['email']) {
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            Response::validationError(['email' => 'Invalid email format']);
        }
        
        if ($db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$input['email'], $userId])) {
            Response::error('Email already exists', 409);
        }
        
        $updateData['email'] = $input['email'];
    }
    
    if (isset($input['role']) && in_array($input['role'], ['admin', 'user', 'uploader'])) {
        $updateData['role'] = $input['role'];
    }
    
    if (isset($input['is_active'])) {
        $updateData['is_active'] = (int)$input['is_active'];
    }
    
    if (!empty($input['password'])) {
        if (strlen($input['password']) < 8) {
            Response::validationError(['password' => 'Password must be at least 8 characters']);
        }
        $updateData['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
    }
    
    $updateData['updated_at'] = date('Y-m-d H:i:s');
    
    $updated = $db->update('users', $updateData, 'id = ?', [$userId]);
    
    if ($updated) {
        $updatedUser = $db->fetch("SELECT id, name, email, role, is_active FROM users WHERE id = ?", [$userId]);
        
        Response::success('User updated successfully', [
            'user' => $updatedUser
        ], 200);
    } else {
        Response::error('Failed to update user', 500);
    }
}

// DELETE - Delete user
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = (int)($input['id'] ?? $_GET['id'] ?? 0);
    
    if ($userId === 0) {
        Response::validationError(['id' => 'User ID is required']);
    }
    
    if ($userId === $user['id']) {
        Response::error('You cannot delete your own account', 400);
    }
    
    $targetUser = $db->fetch("SELECT id, name, email FROM users WHERE id = ?", [$userId]);
    
    if (!$targetUser) {
        Response::error('User not found', 404);
    }
    
    // Soft delete - deactivate user
    $deleted = $db->update('users',
        [
            'is_active' => 0,
            'email' => 'deleted_' . time() . '_' . $targetUser['email'], // Make email available
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'id = ?',
        [$userId]
    );
    
    if ($deleted) {
        Response::success('User deleted successfully', [
            'user_id' => $userId,
            'name' => $targetUser['name']
        ], 200);
    } else {
        Response::error('Failed to delete user', 500);
    }
}

