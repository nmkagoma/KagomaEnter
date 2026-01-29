<?php
/**
 * Company Content Upload API
 * 
 * Allows verified companies to upload movies/series
 * POST /api/company/upload.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/Database.php';
require_once dirname(__DIR__, 2) . '/includes/Response.php';
require_once dirname(__DIR__, 2) . '/includes/Auth.php';

$db = new Database();
$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get Authorization header
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$auth_header || !str_starts_with($auth_header, 'Bearer ')) {
    Response::error('Unauthorized. Please provide a valid token.', 401);
}

$token = str_replace('Bearer ', '', $auth_header);
$user = $auth->validateToken($token);

if (!$user) {
    Response::error('Invalid or expired token', 401);
}

// Check if user is a company
if ($user['role'] !== 'company') {
    Response::error('Access denied. Company account required.', 403);
}

// Get company profile
$company = $db->fetch("SELECT * FROM companies WHERE user_id = ? AND is_active = 1", [$user['id']]);
if (!$company) {
    Response::error('Company profile not found', 404);
}

// Check verification status
if ($company['verification_status'] !== 'verified') {
    Response::error('Your company account must be verified before uploading content. Status: ' . $company['verification_status'], 403);
}

// Get input data (JSON or FormData)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// Validate required fields
$required = ['title', 'description', 'content_type'];
$errors = [];

foreach ($required as $field) {
    if (empty($input[$field])) {
        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
    }
}

if (!empty($errors)) {
    Response::validationError($errors);
}

// Sanitize input
$title = trim($input['title']);
$description = trim($input['description']);
$content_type = $input['content_type']; // movie, series, documentary, anime
$age_rating = $input['age_rating'] ?? 'PG-13';
$release_year = $input['release_year'] ?? date('Y');
$genres = $input['genres'] ?? [];
$trailer_url = trim($input['trailer_url'] ?? '');
$is_premium = isset($input['is_premium']) ? (int)$input['is_premium'] : 0;

// Get entity IDs
$studio_id = $input['studio_id'] ? (int)$input['studio_id'] : null;
$distributor_id = $input['distributor_id'] ? (int)$input['distributor_id'] : null;
$producer_id = $input['producer_id'] ? (int)$input['producer_id'] : null;
$network_id = $input['network_id'] ? (int)$input['network_id'] : null;

// Validate entity IDs if provided
if ($studio_id && !$db->fetch("SELECT id FROM studios WHERE id = ? AND is_active = 1", [$studio_id])) {
    Response::error('Invalid studio ID', 400);
}
if ($distributor_id && !$db->fetch("SELECT id FROM distributors WHERE id = ? AND is_active = 1", [$distributor_id])) {
    Response::error('Invalid distributor ID', 400);
}
if ($producer_id && !$db->fetch("SELECT id FROM producers WHERE id = ? AND is_active = 1", [$producer_id])) {
    Response::error('Invalid producer ID', 400);
}
if ($network_id && !$db->fetch("SELECT id FROM networks WHERE id = ? AND is_active = 1", [$network_id])) {
    Response::error('Invalid network ID', 400);
}

// Generate slug
$slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $title));
$slug = preg_replace('/-+/', '-', $slug);
$base_slug = $slug;
$counter = 1;
while ($db->fetch("SELECT id FROM content WHERE slug = ?", [$slug])) {
    $slug = $base_slug . '-' . $counter++;
}

// Handle file uploads
$thumbnail_url = null;
$video_url = null;
$video_path = null;
$file_size = 0;
$duration = 0;

// Handle video upload if present
if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = dirname(__DIR__, 3) . '/uploads/videos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
    $allowed_video = ['mp4', 'webm', 'mkv', 'mov'];
    
    if (!in_array($file_ext, $allowed_video)) {
        Response::error('Invalid video format. Allowed: MP4, WebM, MKV, MOV', 400);
    }
    
    $file_name = 'video_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
    $target_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['video']['tmp_name'], $target_path)) {
        $video_url = '/KagomaEnter/uploads/videos/' . $file_name;
        $video_path = $target_path;
        $file_size = $_FILES['video']['size'];
        $duration = 0; // Would need ffprobe to get duration
    } else {
        Response::error('Failed to upload video', 400);
    }
}

// Handle thumbnail upload if present
if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = dirname(__DIR__, 3) . '/uploads/thumbnails/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
    $allowed_image = ['jpg', 'jpeg', 'png', 'webp'];
    
    if (!in_array($file_ext, $allowed_image)) {
        Response::error('Invalid image format. Allowed: JPG, PNG, WebP', 400);
    }
    
    $file_name = 'thumb_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
    $target_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $target_path)) {
        $thumbnail_url = '/KagomaEnter/uploads/thumbnails/' . $file_name;
    } else {
        Response::error('Failed to upload thumbnail', 400);
    }
}

try {
    // Insert content
    $content_id = $db->insert('content', [
        'user_id' => $user['id'],
        'company_id' => $company['id'],
        'studio_id' => $studio_id,
        'distributor_id' => $distributor_id,
        'producer_id' => $producer_id,
        'network_id' => $network_id,
        'title' => $title,
        'slug' => $slug,
        'description' => $description,
        'thumbnail' => $thumbnail_url,
        'thumbnail_url' => $thumbnail_url,
        'trailer_url' => $trailer_url,
        'video_url' => $video_url,
        'video_path' => $video_path,
        'video_quality' => '1080p',
        'file_size' => $file_size,
        'duration' => $duration,
        'duration_minutes' => floor($duration / 60),
        'release_year' => $release_year,
        'content_type' => $content_type,
        'age_rating' => $age_rating,
        'is_premium' => $is_premium,
        'is_active' => 1,
        'uploaded_by' => $user['id'],
        'published_at' => date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    if (!$content_id) {
        throw new Exception('Failed to create content');
    }

    // Add genres
    if (!empty($genres) && is_array($genres)) {
        foreach ($genres as $genre_id) {
            $db->insert('content_genre', [
                'content_id' => $content_id,
                'genre_id' => (int)$genre_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    // Update company content count
    $db->query("UPDATE companies SET content_count = content_count + 1 WHERE id = " . (int)$company['id']);

    // Get the created content with entity details
    $content = $db->fetch("
        SELECT c.*,
               s.name as studio_name,
               d.name as distributor_name,
               p.name as producer_name,
               n.name as network_name
        FROM content c
        LEFT JOIN studios s ON c.studio_id = s.id
        LEFT JOIN distributors d ON c.distributor_id = d.id
        LEFT JOIN producers p ON c.producer_id = p.id
        LEFT JOIN networks n ON c.network_id = n.id
        WHERE c.id = ?
    ", [$content_id]);

    Response::success('Content uploaded successfully', [
        'content' => $content,
        'company' => [
            'id' => $company['id'],
            'name' => $company['name']
        ]
    ], 201);

} catch (Exception $e) {
    error_log('Content upload error: ' . $e->getMessage());
    Response::error('Failed to upload content. Please try again.', 500);
}

