<?php
/**
 * Test create endpoint - simplified version
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

echo "Starting test...\n";

// Include required files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

echo "Files included successfully\n";

try {
    $db = Database::getInstance();
    echo "Database connected\n";
    
    // Check if Auth class exists
    require_once __DIR__ . '/../../includes/Auth.php';
    echo "Auth class included\n";
    
    $auth = new Auth();
    echo "Auth instance created\n";
    
    // Try to get current user
    $user = $auth->getCurrentUser();
    echo "User: " . ($user ? json_encode($user) : "null") . "\n";
    
    echo "\nTest completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

