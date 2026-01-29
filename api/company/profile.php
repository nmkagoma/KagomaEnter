<?php
/**
 * Company Profile API
 * 
 * GET  - Get company profile
 * PUT  - Update company profile
 * GET /api/company/profile.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__, 2) . '/config/connection.php';
require_once dirname(__DIR__, 2) . '/includes/Response.php';
require_once dirname(__DIR__, 2) . '/includes/Auth.php';

$db = new Database();
$auth = new Auth();

// Authenticate
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$auth_header || !str_starts_with($auth_header, 'Bearer ')) {
    Response::error('Unauthorized', 401);
}

$token = str_replace('Bearer ', '', $auth_header);
$user = $auth->validateToken($token);

if (!$user || $user['role'] !== 'company') {
    Response::error('Access denied', 403);
}

// Get company
$company = $db->fetch("SELECT * FROM companies WHERE user_id = ?", [$user['id']]);
if (!$company) {
    Response::error('Company not found', 404);
}

// GET - Return company profile
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    Response::success('Company profile', [
        'company' => [
            'id' => $company['id'],
            'name' => $company['name'],
            'slug' => $company['slug'],
            'email' => $company['email'],
            'phone' => $company['phone'],
            'website' => $company['website'],
            'description' => $company['description'],
            'logo' => $company['logo'],
            'banner' => $company['banner'],
            'business_type' => $company['business_type'],
            'country' => $company['country'],
            'city' => $company['city'],
            'address' => $company['address'],
            'tax_id' => substr($company['tax_id'] ?? '', 0, 4) . '****', // Masked
            'verification_status' => $company['verification_status'],
            'content_count' => $company['content_count'],
            'total_views' => $company['total_views'],
            'created_at' => $company['created_at']
        ]
    ]);
}

// PUT - Update company profile
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        Response::error('Invalid input', 400);
    }
    
    $update_data = [];
    $allowed_fields = ['phone', 'website', 'description', 'country', 'city', 'address'];
    
    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $update_data[$field] = trim($input[$field]);
        }
    }
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = dirname(__DIR__, 3) . '/uploads/company_logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed_image = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_ext, $allowed_image)) {
            $file_name = 'logo_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                $update_data['logo'] = '/KagomaEnter/uploads/company_logos/' . $file_name;
            }
        }
    }
    
    // Handle banner upload
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = dirname(__DIR__, 3) . '/uploads/company_banners/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
        $allowed_image = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_ext, $allowed_image)) {
            $file_name = 'banner_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['banner']['tmp_name'], $target_path)) {
                $update_data['banner'] = '/KagomaEnter/uploads/company_banners/' . $file_name;
            }
        }
    }
    
    if (!empty($update_data)) {
        $update_data['updated_at'] = date('Y-m-d H:i:s');
        $db->update('companies', $update_data, "id = {$company['id']}");
    }
    
    // Get updated profile
    $updated = $db->fetch("SELECT * FROM companies WHERE id = ?", [$company['id']]);
    
    Response::success('Profile updated successfully', [
        'company' => [
            'id' => $updated['id'],
            'name' => $updated['name'],
            'logo' => $updated['logo'],
            'banner' => $updated['banner'],
            'description' => $updated['description'],
            'website' => $updated['website'],
            'updated_at' => $updated['updated_at']
        ]
    ]);
}

