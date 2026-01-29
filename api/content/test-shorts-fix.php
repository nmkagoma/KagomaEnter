<?php
/**
 * Simple test to check shorts API fix
 */

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "KagomaEnter";
$socket = "/opt/lampp/var/mysql/mysql.sock";

$conn = new mysqli($host, $user, $pass, $dbname, 3306, $socket);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

header('Content-Type: application/json');

echo json_encode([
    'status' => 'testing',
    'message' => 'Shorts API test',
    'time' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT) . "\n\n";

// Get shorts
$sql = "SELECT id, title FROM content WHERE is_active=1 AND content_type='short' ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "Found {$result->num_rows} shorts:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - ID: {$row['id']}, Title: {$row['title']}\n";
    }
} else {
    echo "No shorts found or query failed\n";
    echo "Error: " . $conn->error . "\n";
}

$conn->close();

