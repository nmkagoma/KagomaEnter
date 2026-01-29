<?php
/**
 * Create Content Endpoint
 * Handles uploading and creating new content (movies, series, shorts)
 */

// Error handling - ensure we always return JSON
error_reporting(E_ALL);
ini_set('display_errors', 1);

function returnJsonError($message, $file = '', $line = 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => $message,
        'debug' => [
            'file' => basename($file),
            'line' => $line
        ],  JSON_UNESCAPED_UNICODE
    ]);
    exit;
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    returnJsonError('PHP Error: ' . $errstr, $errfile, $errline);
});

set_exception_handler(function($exception) {
    returnJsonError('Exception: ' . $exception->getMessage(), $exception->getFile(), $exception->getLine());
});

// Check if file exists before requiring
$configPath = __DIR__ . '/../../config/config.php';
if (!file_exists($configPath)) {
    returnJsonError('Config file not found: ' . $configPath, __FILE__, __LINE__);
}
require_once $configPath;

$includesPath = __DIR__ . '/../../includes/';
if (!file_exists($includesPath . 'Database.php')) {
    returnJsonError('Database.php not found', __FILE__, __LINE__);
}
require_once $includesPath . 'Database.php';
require_once $includesPath . 'Response.php';
require_once $includesPath . 'Auth.php';
require_once $includesPath . 'Validation.php';

// Set content type
header('Content-Type: application/json; charset=utf-8');

// Enable CORS
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$db = Database::getInstance();
$auth = new Auth();

// Get current user
$user = $auth->getCurrentUser();

// Require authentication
if (!$user) {
    Response::error('Authentication required', 401);
}

// Get content type early for permission check
$contentType = isset($_POST['content_type']) ? strip_tags(trim($_POST['content_type'])) : 'short';

// For shorts, any authenticated user can create (like TikTok/YouTube Stories)
// For other content types (movies, series), require creator/admin role
if ($contentType !== 'short' && !in_array($user['role'], ['creator', 'admin', 'super_admin'])) {
    Response::error('You do not have permission to create this content type. Shorts are available to all users.', 403);
}

try {
    // Get form data - use $_POST directly instead of Validation::sanitize to avoid issues
    $title = isset($_POST['title']) ? htmlspecialchars(strip_tags(trim($_POST['title'])), ENT_QUOTES, 'UTF-8') : '';
    $description = isset($_POST['description']) ? htmlspecialchars(strip_tags(trim($_POST['description'])), ENT_QUOTES, 'UTF-8') : '';
    $genreIds = $_POST['genre_ids'] ?? [];
    $duration = isset($_POST['duration']) ? strip_tags(trim($_POST['duration'])) : '';
    $durationMinutes = isset($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : 0;
    
    // Convert duration like "15s" to just the numeric value for database
    $durationValue = (int)preg_replace('/[^0-9]/', '', $duration);
    
    // Validate required fields
    if (empty($title)) {
        Response::error('Title is required', 400);
    }
    
    if (empty($description)) {
        Response::error('Description is required', 400);
    }
    
    // Validate content type
    $validTypes = ['movie', 'series', 'short', 'documentary', 'anime', 'live'];
    if (!in_array($contentType, $validTypes)) {
        Response::error('Invalid content type', 400);
    }
    
    // Handle file uploads
    $thumbnailUrl = null;
    $videoUrl = null;
    $videoPath = null;
    
    // Upload thumbnail if provided
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $thumbnailResult = uploadImage($_FILES['thumbnail']);
        if ($thumbnailResult['success']) {
            $thumbnailUrl = $thumbnailResult['url'];
        } else {
            Response::error('Thumbnail upload failed: ' . $thumbnailResult['error'], 400);
        }
    }
    
    // Upload video if provided
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $videoResult = uploadVideo($_FILES['video']);
        if ($videoResult['success']) {
            $videoUrl = $videoResult['url'];
            $videoPath = $videoResult['path'];
        } else {
            Response::error('Video upload failed: ' . $videoResult['error'], 400);
        }
    }
    
    // For shorts, we need a video or at least a thumbnail
    if ($contentType === 'short' && empty($videoUrl) && empty($thumbnailUrl)) {
        Response::error('Shorts require a video or thumbnail', 400);
    }
    
    // Generate slug
    $slug = Validation::generateSlug($title);
    
    // Check for existing slug
    $existingSlug = $db->fetch(
        "SELECT id FROM content WHERE slug = ?",
        [$slug]
    );
    if ($existingSlug) {
        $slug = $slug . '-' . time();
    }
    
    // Prepare content data
    $now = date('Y-m-d H:i:s');
    $contentData = [
        'title' => $title,
        'slug' => $slug,
        'description' => $description,
        'content_type' => $contentType,
        'thumbnail' => $thumbnailUrl,
        'thumbnail_url' => $thumbnailUrl,
        'video_url' => $videoUrl,
        'video_path' => $videoPath,
        'duration' => $durationValue,
        'duration_minutes' => $durationMinutes,
        'user_id' => $user['id'],
        'channel_id' => $_POST['channel_id'] ?? null,
        'is_active' => $contentType === 'short' ? 1 : 0, // Shorts auto-publish, others need approval
        'is_premium' => 0,
        'is_featured' => 0,
        'view_count' => 0,
        'like_count' => 0,
        'comment_count' => 0,
        'published_at' => $now,
        'created_at' => $now,
        'updated_at' => $now
    ];
    
    // Insert content
    $contentId = $db->insert('content', $contentData);
    
    if (!$contentId) {
        Response::error('Failed to create content', 500);
    }
    
    // Add genres
    if (!empty($genreIds) && is_array($genreIds)) {
        foreach ($genreIds as $genreId) {
            $genreId = (int)$genreId;
            if ($genreId > 0) {
                $db->insert('content_genre', [
                    'content_id' => $contentId,
                    'genre_id' => $genreId
                ]);
            }
        }
    }
    
    // Log activity
    $db->insert('activity_log', [
        'user_id' => $user['id'],
        'action' => 'create_content',
        'item_type' => 'content',
        'item_id' => $contentId,
        'metadata' => json_encode(['title' => $title, 'type' => $contentType]),
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Get the created content
    $createdContent = $db->fetch(
        "SELECT * FROM content WHERE id = ?",
        [$contentId]
    );
    
    Response::success('Content created successfully', [
        'content' => $createdContent
    ], 201);
    
} catch (Exception $e) {
    error_log('Create content error: ' . $e->getMessage());
    Response::error('An error occurred while creating content', 500);
}

/**
 * Upload image file
 */
function uploadImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Validate file type
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP'];
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds 5MB limit'];
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../uploads/thumbnails';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'thumb_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = $uploadDir . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $baseUrl = BASE_URL;
        // Adjust URL path based on configuration
        $urlPath = '/KagomaEnter/uploads/thumbnails';
        return [
            'success' => true,
            'url' => $urlPath . '/' . $filename,
            'path' => $targetPath
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

/**
 * Upload video file
 */
function uploadVideo($file) {
    $allowedTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo'];
    $maxSize = 100 * 1024 * 1024; // 100MB
    
    // Validate file type
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: MP4, WebM, MOV, AVI'];
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds 100MB limit'];
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../uploads/videos';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'video_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = $uploadDir . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $baseUrl = BASE_URL;
        // Adjust URL path based on configuration
        $urlPath = '/KagomaEnter/uploads/videos';
        return [
            'success' => true,
            'url' => $urlPath . '/' . $filename,
            'path' => $targetPath
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

