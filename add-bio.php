<?php
// Quick script to add bio column
$conn = new mysqli('localhost', 'root', '', 'KagomaEnter', 3306, '/opt/lampp/var/mysql/mysql.sock');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL AFTER banner_url";
if ($conn->query($sql)) {
    echo "✓ Added bio column successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
