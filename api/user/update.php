<?php
/**
 * User Update API - Fixed version with robust error handling
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

// Log file for debugging
$logFile = __DIR__ . '/update_errors.log';

function logError($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

function jsonResponse($status, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse('success', 'OK');
}

// Only allow PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse('error', 'Method not allowed. Use PUT.', null, 405);
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'KagomaEnter');
if ($conn->connect_error) {
    logError("Database connection failed: " . $conn->connect_error);
    jsonResponse('error', 'Database connection failed', null, 500);
}

// Get token from header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$authHeader || !preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
    logError("No authorization token provided");
    jsonResponse('error', 'Unauthorized - No token', null, 401);
}

$token = $matches[1];
$userId = 0;

// Verify token - try different tables
$stmt = $conn->prepare("SELECT user_id FROM user_tokens WHERE token = ? AND expires_at > NOW()");
if ($stmt) {
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $userId = $result->fetch_assoc()['user_id'];
    }
    $stmt->close();
}

if ($userId == 0) {
    $stmt = $conn->prepare("SELECT user_id FROM sessions WHERE session_token = ? AND expires_at > NOW()");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $userId = $result->fetch_assoc()['user_id'];
        }
        $stmt->close();
    }
}

// Allow demo token for testing
if ($userId == 0 && ($token === 'demo' || $token === 'test_token')) {
    $userId = 1;
}

if ($userId == 0) {
    logError("Invalid token: " . substr($token, 0, 20) . "...");
    jsonResponse('error', 'Invalid token', null, 401);
}

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    logError("User not found for ID: $userId");
    jsonResponse('error', 'User not found', null, 404);
}
$user = $result->fetch_assoc();
$stmt->close();

$avatarUrl = $user['avatar_url'] ?? '';
$bannerUrl = $user['banner_url'] ?? '';

// Upload directories
$uploadDir = __DIR__ . '/../../uploads/avatars/';
$bannerDir = __DIR__ . '/../../uploads/banners/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        logError("Failed to create avatar upload directory");
    }
}
if (!is_dir($bannerDir)) {
    if (!mkdir($bannerDir, 0755, true)) {
        logError("Failed to create banner upload directory");
    }
}

// Handle avatar upload - support both form POST and fetch/FormData
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    logError("Processing avatar upload via POST");
    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!isset($allowedTypes[$mimeType])) {
        jsonResponse('error', 'Invalid file type: ' . $mimeType, null, 400);
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        jsonResponse('error', 'File too large (max 5MB)', null, 400);
    }
    
    $extension = $allowedTypes[$mimeType];
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $avatarUrl = 'uploads/avatars/' . $filename;
        logError("Avatar uploaded successfully: $avatarUrl");
    } else {
        logError("Failed to move uploaded avatar file");
        jsonResponse('error', 'Failed to save avatar', null, 500);
    }
} elseif (isset($_FILES['avatar'])) {
    logError("Avatar upload error code: " . $_FILES['avatar']['error']);
}

// Handle banner upload - support both form POST and fetch/FormData
if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
    logError("Processing banner upload via POST");
    $file = $_FILES['banner'];
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!isset($allowedTypes[$mimeType])) {
        jsonResponse('error', 'Invalid banner type: ' . $mimeType, null, 400);
    }
    
    if ($file['size'] > 10 * 1024 * 1024) {
        jsonResponse('error', 'Banner too large (max 10MB)', null, 400);
    }
    
    $extension = $allowedTypes[$mimeType];
    $filename = 'banner_' . $userId . '_' . time() . '.' . $extension;
    $targetPath = $bannerDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $bannerUrl = 'uploads/banners/' . $filename;
        logError("Banner uploaded successfully: $bannerUrl");
    } else {
        logError("Failed to move uploaded banner file");
        jsonResponse('error', 'Failed to save banner', null, 500);
    }
} elseif (isset($_FILES['banner'])) {
    logError("Banner upload error code: " . $_FILES['banner']['error']);
}

// Get POST/PUT data - handle both form data and JSON
$data = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?? [];
    logError("Received JSON data: " . substr($input, 0, 500));
} elseif (strpos($contentType, 'multipart/form-data') !== false) {
    $data = $_POST;
    logError("Received form data with " . count($_POST) . " fields");
} else {
    $data = $_POST;
    logError("Received POST data with " . count($_POST) . " fields");
}

// Debug: log what data we received
logError("Data received: " . json_encode($data));

// Build update query
$updateFields = [];
$params = [];
$types = '';

// Add avatar_url if changed
if (!empty($avatarUrl)) {
    $updateFields[] = "avatar_url = ?";
    $params[] = $avatarUrl;
    $types .= 's';
}

// Add banner_url if changed
if (!empty($bannerUrl)) {
    $updateFields[] = "banner_url = ?";
    $params[] = $bannerUrl;
    $types .= 's';
}

// Handle text fields
$textFields = ['name', 'username', 'bio', 'location', 'website', 'date_of_birth'];
foreach ($textFields as $field) {
    if (isset($data[$field])) {
        $value = trim($data[$field]);
        if ($field === 'name' && empty($value)) continue;
        if ($field === 'username') {
            $value = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
            if (empty($value)) continue;
        }
        $updateFields[] = "$field = ?";
        $params[] = $value;
        $types .= 's';
    }
}

// Execute update
if (!empty($updateFields)) {
    $updateFields[] = "updated_at = NOW()";
    $params[] = $userId;
    $types .= 'i';
    
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    logError("Executing update SQL: " . substr($sql, 0, 200));
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            logError("Update failed: $error");
            jsonResponse('error', 'Update failed: ' . $error, null, 500);
        }
        $stmt->close();
        logError("Update executed successfully");
    } else {
        $error = $conn->error;
        logError("SQL prepare error: $error");
        jsonResponse('error', 'SQL error: ' . $error, null, 500);
    }
} else {
    logError("No fields to update");
}

// Fetch updated user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$updatedUser = $result->fetch_assoc();
$stmt->close();

$response = [
    'status' => 'success',
    'message' => 'Profile updated successfully',
    'data' => [
        'user' => [
            'id' => $updatedUser['id'],
            'name' => $updatedUser['name'],
            'email' => $updatedUser['email'],
            'username' => $updatedUser['username'] ?? '',
            'avatar_url' => $updatedUser['avatar_url'] ?? '',
            'avatar' => $updatedUser['avatar_url'] ?? '',
            'banner_url' => $updatedUser['banner_url'] ?? '',
            'bio' => $updatedUser['bio'] ?? '',
            'location' => $updatedUser['location'] ?? '',
            'website' => $updatedUser['website'] ?? '',
            'date_of_birth' => $updatedUser['date_of_birth'] ?? '',
            'subscription_plan' => $updatedUser['subscription_plan'] ?? 'free',
            'role' => $updatedUser['role'] ?? 'user',
            'is_active' => $updatedUser['is_active'] ?? 1
        ]
    ]
];

logError("Sending success response");
echo json_encode($response);

