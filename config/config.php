<?php
// Environment Configuration
define('ENVIRONMENT', 'development');

// Streaming Platform Configuration
define('APP_NAME', 'KagomaEnter');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/KagomaEnter/');
define('TIMEZONE', 'Africa/Dar_es_Salaam');

// Security
define('SECRET_KEY', 'your-secret-key-here-change-in-production');
define('JWT_SECRET', 'jwt-secret-key-here-change-in-production');
define('ENCRYPTION_KEY', 'encryption-key-here-32-chars');

// File Upload
define('MAX_FILE_SIZE', 500 * 1024 * 1024); // 500MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'mkv', 'avi', 'mov']);
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'KagomaEnter');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// API Configuration
define('API_RATE_LIMIT', 100); // requests per minute
define('API_VERSION', 'v1');
define('DEFAULT_PAGE_SIZE', 20);

// CORS Configuration
define('ALLOWED_ORIGINS', ['http://localhost:3000', 'http://localhost:5173']);
define('ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('ALLOWED_HEADERS', 'Content-Type, Authorization, X-Requested-With');

// Content Settings
define('DEFAULT_VIDEO_QUALITY', '1080p');
define('MAX_STREAM_BUFFER', 10 * 1024 * 1024); // 10MB
define('SUPPORTED_QUALITIES', ['360p', '480p', '720p', '1080p', '4K']);

// Social Login Configuration
define('GOOGLE_CLIENT_ID', 'your-google-client-id.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'your-google-client-secret');
define('GOOGLE_REDIRECT_URI', BASE_URL . 'api/auth/callback/google.php');

define('FACEBOOK_APP_ID', 'your-facebook-app-id');
define('FACEBOOK_APP_SECRET', 'your-facebook-app-secret');
define('FACEBOOK_REDIRECT_URI', BASE_URL . 'api/auth/callback/facebook.php');

// Email Configuration (for verification)
define('EMAIL_FROM', 'noreply@KagomaEnter.com');
define('EMAIL_FROM_NAME', 'KagomaEnter');
define('EMAIL_VERIFY_EXPIRY', 24 * 60 * 60); // 24 hours

// Session Configuration - moved to top of file to avoid headers already sent issues
// These settings should be configured in php.ini or .htaccess for production

// Error Reporting
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error Logging Configuration
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');

// Create logs directory if it doesn't exist
$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
?>
