<?php
/**
 * Send Email Verification Endpoint
 * Sends verification email to user
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

// Get current user
$user = $auth->getCurrentUser();

if (!$user) {
    Response::error('Unauthorized', 401);
}

// Check if already verified
if ($user['email_verified_at']) {
    Response::error('Email is already verified', 400);
}

// Generate verification token
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', time() + EMAIL_VERIFY_EXPIRY);

// Store verification token
$db->delete('email_verification_tokens', 'user_id = ?', [$user['id']]);

$tokenData = [
    'user_id' => $user['id'],
    'token' => hash('sha256', $token),
    'expires_at' => $expiresAt,
    'created_at' => date('Y-m-d H:i:s')
];

$db->insert('email_verification_tokens', $tokenData);

// Build verification URL
$verifyUrl = BASE_URL . 'frontend/verify-email.html?token=' . urlencode($token) . '&user_id=' . $user['id'];

// Send verification email (simulated - in production use PHPMailer or similar)
$emailSent = sendVerificationEmail($user['email'], $user['name'], $verifyUrl);

if (!$emailSent) {
    Response::error('Failed to send verification email', 500);
}

Response::success('Verification email sent. Please check your inbox.', [
    'email' => maskEmail($user['email']),
    'expires_in' => EMAIL_VERIFY_EXPIRY
], 200);

/**
 * Send verification email (simulated)
 */
function sendVerificationEmail($email, $name, $verifyUrl) {
    $subject = 'Verify Your Email - KagomaEnter';
    
    $message = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #e50914; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px 20px; background: #f9f9f9; }
        .button { display: inline-block; padding: 15px 30px; background: #e50914; color: white; 
                  text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>KagomaEnter</h1>
        </div>
        <div class="content">
            <h2>Hi {$name},</h2>
            <p>Thank you for signing up! Please verify your email address to activate your account.</p>
            <p style="text-align: center;">
                <a href="{$verifyUrl}" class="button">Verify Email</a>
            </p>
            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #666;">{$verifyUrl}</p>
            <p>This link will expire in 24 hours.</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 KagomaEnter. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>',
        'Reply-To: ' . EMAIL_FROM
    ];
    
    // In production, use a proper mail library like PHPMailer
    // For development, we'll simulate success
    error_log("Verification email to {$email}");
    error_log("Verification URL: {$verifyUrl}");
    
    return @mail($email, $subject, $message, implode("\r\n", $headers));
}

/**
 * Mask email for display
 */
function maskEmail($email) {
    list($local, $domain) = explode('@', $email);
    
    if (strlen($local) > 2) {
        $local = substr($local, 0, 2) . str_repeat('*', strlen($local) - 2);
    }
    
    return $local . '@' . $domain;
}
?>

