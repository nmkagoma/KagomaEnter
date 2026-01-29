<?php
/**
 * Company Registration API
 * 
 * Allows businesses to register as content providers
 * POST /api/company/register.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/Database.php';
require_once dirname(__DIR__, 2) . '/includes/Response.php';
require_once dirname(__DIR__, 2) . '/includes/Auth.php';
require_once dirname(__DIR__, 2) . '/includes/Validation.php';

$db = new Database();
$auth = new Auth();
$validation = new Validation();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// Validate required fields
$required = ['company_name', 'email', 'password', 'name', 'phone', 'tax_id'];
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
$company_name = trim($input['company_name']);
$email = strtolower(trim($input['email']));
$password = $input['password'];
$name = trim($input['name']);
$phone = trim($input['phone'] ?? '');
$tax_id = trim($input['tax_id'] ?? '');
$business_type = $input['business_type'] ?? 'independent';
$country = trim($input['country'] ?? '');
$description = trim($input['description'] ?? '');
$website = trim($input['website'] ?? '');

// Validate email format
if (!$validation->email($email)) {
    Response::validationError(['email' => 'Invalid email format']);
}

// Validate password strength
if (strlen($password) < 8) {
    Response::validationError(['password' => 'Password must be at least 8 characters']);
}

// Check if email already exists in users
$existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
if ($existing) {
    Response::error('Email already registered. Please login or use a different email.', 409);
}

// Check if company email already exists
$existing_company = $db->fetch("SELECT id FROM companies WHERE email = ?", [$email]);
if ($existing_company) {
    Response::error('Company email already registered. Please contact support.', 409);
}

// Check if tax_id already exists
$existing_tax = $db->fetch("SELECT id FROM companies WHERE tax_id = ?", [$tax_id]);
if ($existing_tax) {
    Response::validationError(['tax_id' => 'Tax ID already registered']);
}

// Generate slug from company name
$slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $company_name));
$slug = preg_replace('/-+/', '-', $slug);
$base_slug = $slug;
$counter = 1;
while ($db->fetch("SELECT id FROM companies WHERE slug = ?", [$slug])) {
    $slug = $base_slug . '-' . $counter++;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Begin transaction
$db->beginTransaction();

try {
    // Create user account
    $user_id = $db->insert('users', [
        'name' => $name,
        'email' => $email,
        'password' => $hashed_password,
        'role' => 'company',
        'user_type' => 'company',
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    if (!$user_id) {
        throw new Exception('Failed to create user account');
    }

    // Create company profile
    $company_id = $db->insert('companies', [
        'user_id' => $user_id,
        'name' => $company_name,
        'slug' => $slug,
        'email' => $email,
        'phone' => $phone,
        'website' => $website,
        'description' => $description,
        'tax_id' => $tax_id,
        'business_type' => $business_type,
        'country' => $country,
        'verification_status' => 'pending',
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    if (!$company_id) {
        throw new Exception('Failed to create company profile');
    }

    // Create company user relationship (owner)
    $db->insert('company_users', [
        'company_id' => $company_id,
        'user_id' => $user_id,
        'role' => 'owner',
        'permissions' => json_encode(['upload', 'edit', 'delete', 'analytics', 'settings']),
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    // Create content application for admin review
    $db->insert('content_applications', [
        'company_id' => $company_id,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'company_name' => $company_name,
        'tax_id' => $tax_id,
        'business_type' => $business_type,
        'country' => $country,
        'description' => $description,
        'documents' => json_encode([]),
        'status' => 'pending',
        'submitted_at' => date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    $db->commit();

    // Generate auth token
    $token = $auth->generateToken($user_id);

    Response::success('Company registration submitted for review. You will be notified once approved.', [
        'user' => [
            'id' => $user_id,
            'name' => $name,
            'email' => $email,
            'role' => 'company'
        ],
        'company' => [
            'id' => $company_id,
            'name' => $company_name,
            'slug' => $slug,
            'verification_status' => 'pending'
        ],
        'token' => $token
    ], 201);

} catch (Exception $e) {
    $db->rollback();
    error_log('Company registration error: ' . $e->getMessage());
    Response::error('Registration failed. Please try again later.', 500);
}

