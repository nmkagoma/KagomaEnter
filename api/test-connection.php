<?php
/**
 * API Connection Test
 * Tests basic API functionality
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/stubs.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Response.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

// Test database connection
try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Test query
    $usersTable = $db->query("SHOW TABLES LIKE 'users'");
    
    $response = [
        'status' => 'success',
        'message' => 'API is working',
        'database' => [
            'connected' => true,
            'host' => DB_HOST,
            'database' => DB_NAME
        ],
        'tables' => [
            'users_exists' => $usersTable->num_rows > 0
        ],
        'server' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

