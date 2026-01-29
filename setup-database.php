<?php
// Database configuration - use environment variables in production
$servername = "localhost";
$username = "root";
$password = ""; // Should be loaded from environment in production
$dbname = "KagomaEnter_kiganjani";
$socket = "/opt/lampp/var/mysql/mysql.sock";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, 3306, $socket);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully\n";

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255),
    bio TEXT,
    role ENUM('user', 'admin') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Add more tables as needed...

// Close connection
$conn->close();
echo "Setup completed\n";
?>
