<?php
/**
 * Social Login Endpoint
 * Handles Google and Facebook OAuth authentication
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

// Initialize classes
$db = Database::getInstance();
$auth = new Auth();

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($input['provider'])) {
    Response::validationError(['provider' => 'Provider is required (google or facebook)']);
}

$provider = strtolower($input['provider']);

if (!in_array($provider, ['google', 'facebook'])) {
    Response::validationError(['provider' => 'Invalid provider. Use google or facebook']);
}

if (empty($input['access_token'])) {
    Response::validationError(['access_token' => 'Access token is required']);
}

try {
    // Verify token with provider
    $providerData = null;
    
    if ($provider === 'google') {
        $providerData = verifyGoogleToken($input['access_token']);
    } elseif ($provider === 'facebook') {
        $providerData = verifyFacebookToken($input['access_token']);
    }
    
    if (!$providerData) {
        Response::error('Invalid access token', 401);
    }
    
    // Check if user exists
    $user = $db->fetch(
        "SELECT * FROM users WHERE email = ?",
        [$providerData['email']]
    );
    
    if (!$user) {
        // Create new user with social login
        $userId = createSocialUser($provider, $providerData);
        
        if (!$userId) {
            Response::error('Failed to create account', 500);
        }
        
        // Get created user
        $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        
        // Create user profile
        $profileData = [
            'user_id' => $userId,
            'avatar' => $providerData['avatar_url'] ?? null,
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
    }
    
    // Check if user is active
    if (!$user['is_active']) {
        Response::error('Your account has been deactivated. Please contact support.', 401);
    }
    
    // Update user info if changed
    if ($providerData['avatar_url'] && $providerData['avatar_url'] !== ($user['avatar_url'] ?? '')) {
        $db->update('users', 
            ['avatar_url' => $providerData['avatar_url'], 'updated_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$user['id']]
        );
    }
    
    // Update last login
    $db->update('users',
        ['updated_at' => date('Y-m-d H:i:s')],
        'id = ?',
        [$user['id']]
    );
    
    // Generate JWT token
    $token = $auth->generateToken($user['id']);
    
    // Get subscription
    $subscription = $db->fetch(
        "SELECT type, status, starts_at, ends_at FROM subscriptions WHERE user_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1",
        [$user['id']]
    );
    
    // Build response
    $userData = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'is_active' => $user['is_active'],
        'avatar_url' => $user['avatar_url'] ?? $providerData['avatar_url'] ?? null,
        'subscription_plan' => $user['subscription_plan'] ?? 'free',
        'subscription' => $subscription ? [
            'type' => $subscription['type'],
            'status' => $subscription['status'],
            'starts_at' => $subscription['starts_at'],
            'ends_at' => $subscription['ends_at']
        ] : null,
        'created_at' => $user['created_at'],
        'updated_at' => $user['updated_at']
    ];
    
    // Determine redirect URL based on role
    $redirectUrl = 'index.html';
    if ($user['role'] === 'admin') {
        $redirectUrl = 'admin/index.html';
    }
    
    Response::success('Login successful', [
        'user' => $userData,
        'token' => $token,
        'role' => $user['role'],
        'provider' => $provider,
        'redirect_url' => $redirectUrl
    ], 200);
    
} catch (Exception $e) {
    error_log('Social login error: ' . $e->getMessage());
    Response::error('Authentication failed. Please try again.', 500);
}

/**
 * Verify Google access token
 */
function verifyGoogleToken($accessToken) {
    // Google OAuth token info endpoint
    $url = 'https://www.googleapis.com/oauth2/v3/userinfo';
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['email'])) {
        return null;
    }
    
    return [
        'provider_id' => $data['sub'] ?? $data['id'] ?? null,
        'email' => $data['email'],
        'name' => $data['name'] ?? '',
        'first_name' => $data['given_name'] ?? '',
        'last_name' => $data['family_name'] ?? '',
        'avatar_url' => $data['picture'] ?? null,
        'verified_email' => $data['email_verified'] ?? false
    ];
}

/**
 * Verify Facebook access token
 */
function verifyFacebookToken($accessToken) {
    // Get app access token
    $appTokenUrl = sprintf(
        'https://graph.facebook.com/v18.0/oauth/access_token?client_id=%s&client_secret=%s&grant_type=client_credentials',
        FACEBOOK_APP_ID,
        FACEBOOK_APP_SECRET
    );
    
    $ch = curl_init($appTokenUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $appResponse = curl_exec($ch);
    curl_close($ch);
    
    $appData = json_decode($appResponse, true);
    
    if (!isset($appData['access_token'])) {
        return null;
    }
    
    $appAccessToken = $appData['access_token'];
    
    // Verify user access token
    $verifyUrl = sprintf(
        'https://graph.facebook.com/v18.0/debug_token?input_token=%s&access_token=%s',
        urlencode($accessToken),
        urlencode($appAccessToken)
    );
    
    $ch = curl_init($verifyUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $verifyResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    $verifyData = json_decode($verifyResponse, true);
    
    if (!isset($verifyData['data']) || !$verifyData['data']['is_valid']) {
        return null;
    }
    
    $userId = $verifyData['data']['user_id'];
    
    // Get user profile
    $profileUrl = sprintf(
        'https://graph.facebook.com/v18.0/%s?fields=id,name,email,picture&access_token=%s',
        $userId,
        urlencode($accessToken)
    );
    
    $ch = curl_init($profileUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $profileResponse = curl_exec($ch);
    curl_close($ch);
    
    $profileData = json_decode($profileResponse, true);
    
    if (!$profileData || !isset($profileData['email'])) {
        return null;
    }
    
    return [
        'provider_id' => $userId,
        'email' => $profileData['email'],
        'name' => $profileData['name'] ?? '',
        'first_name' => '',
        'last_name' => '',
        'avatar_url' => $profileData['picture']['data']['url'] ?? null,
        'verified_email' => true
    ];
}

/**
 * Create new user from social login data
 */
function createSocialUser($provider, $providerData) {
    global $db;
    
    $name = !empty($providerData['name']) ? $providerData['name'] : 
            trim($providerData['first_name'] . ' ' . $providerData['last_name']);
    
    if (empty($name)) {
        $name = explode('@', $providerData['email'])[0];
    }
    
    // Generate random password
    $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    
    $userData = [
        'name' => htmlspecialchars($name),
        'email' => $providerData['email'],
        'password' => $password,
        'role' => 'user',
        'is_active' => 1,
        'email_verified_at' => $providerData['verified_email'] ? date('Y-m-d H:i:s') : null,
        'avatar_url' => $providerData['avatar_url'] ?? null,
        'provider' => $provider,
        'provider_id' => $providerData['provider_id'],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    return $db->insert('users', $userData);
}
?>

