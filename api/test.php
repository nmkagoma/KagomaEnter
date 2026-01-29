<?php
/**
 * Simple test endpoint to check PHP and database connection
 */

header('Content-Type: application/json');

// Test 1: Database connection
try {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "KagomaEnter";
    $socket = "/opt/lampp/var/mysql/mysql.sock";
    
    $conn = new mysqli($host, $user, $pass, $dbname, 3306, $socket);
    
    if ($conn->connect_error) {
        $response['database'] = ['status' => 'error', 'message' => $conn->connect_error];
    } else {
        $conn->set_charset("utf8mb4");
        $response['database'] = ['status' => 'success', 'message' => 'Connected successfully'];
        
        // Test 2: Users table
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetch_assoc();
        $response['users_count'] = (int)$row['count'];
        
        // Test 3: Content table
        $result = $conn->query("SELECT COUNT(*) as count FROM content WHERE is_active = 1");
        $row = $result->fetch_assoc();
        $response['content_count'] = (int)$row['count'];
        
        $conn->close();
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);

