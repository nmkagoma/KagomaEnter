<?php
/**
 * Quick script to add profile columns to users table
 * Run this in browser: http://localhost/KagomaEnter/api/quick-add-columns.php
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

echo "<h2>Adding profile columns to users table...</h2>";

$columns = [
    'bio' => 'TEXT DEFAULT NULL',
    'location' => 'VARCHAR(255) DEFAULT NULL',
    'website' => 'VARCHAR(255) DEFAULT NULL',
    'date_of_birth' => 'DATE DEFAULT NULL',
    'preferences' => 'LONGTEXT DEFAULT NULL'
];

foreach ($columns as $column => $type) {
    $result = $conn->query("SHOW COLUMNS FROM users LIKE '{$column}'");
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>✓ {$column} already exists</p>";
    } else {
        $sql = "ALTER TABLE users ADD COLUMN {$column} {$type}";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Added: {$column}</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding {$column}: " . $conn->error . "</p>";
        }
    }
}

echo "<h3>Current users table structure:</h3>";
$result = $conn->query("DESCRIBE users");
echo "<table border='1' cellpadding='8'><tr><th>Field</th><th>Type</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
}
echo "</table>";

$conn->close();
