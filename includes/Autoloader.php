<?php
/**
 * Autoloader for KagomaEnter API
 * This file automatically loads all class files when they are referenced
 * It also helps IDE intellisense recognize the classes
 */

// Define the base directory for the includes folder
define('INCLUDES_PATH', dirname(__DIR__) . '/includes');

// Register the autoloader function
spl_autoload_register(function ($className) {
    // List of classes and their file paths
    $classMap = [
        'Database'    => INCLUDES_PATH . '/Database.php',
        'Response'    => INCLUDES_PATH . '/Response.php',
        'Auth'        => INCLUDES_PATH . '/Auth.php',
        'Validation'  => INCLUDES_PATH . '/Validation.php',
        'Upload'      => INCLUDES_PATH . '/Upload.php',
        'Utils'       => INCLUDES_PATH . '/Utils.php',
        'RateLimiter' => INCLUDES_PATH . '/RateLimiter.php',
    ];
    
    // Check if the class is in our map
    if (isset($classMap[$className])) {
        $filePath = $classMap[$className];
        if (file_exists($filePath)) {
            require_once $filePath;
            return true;
        }
    }
    
    return false;
});

// Load configuration first
require_once dirname(__DIR__) . '/config/config.php';

