<?php
/**
 * Database Migration Script
 * Adds missing columns for profile functionality
 */

// Use the same connection settings as Database class
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "KagomaEnter";
$socket = "/opt/lampp/var/mysql/mysql.sock";

$conn = new mysqli($host, $user, $pass, $dbname, 3306, $socket);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Running migrations...\n\n";

// Check current users table structure
$result = $conn->query("DESCRIBE users");
$existingColumns = [];
while ($row = $result->fetch_assoc()) {
    $existingColumns[] = $row['Field'];
}
echo "Existing columns: " . implode(", ", $existingColumns) . "\n\n";

// Add columns if they don't exist
if (!in_array('avatar', $existingColumns)) {
    $sql = "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER password";
    if ($conn->query($sql)) {
        echo "✓ Added avatar column to users table\n";
    } else {
        echo "✗ Error adding avatar: " . $conn->error . "\n";
    }
}

if (!in_array('username', $existingColumns)) {
    $sql = "ALTER TABLE users ADD COLUMN username VARCHAR(50) DEFAULT NULL AFTER email";
    if ($conn->query($sql)) {
        echo "✓ Added username column to users table\n";
    } else {
        echo "✗ Error adding username: " . $conn->error . "\n";
    }
}

if (!in_array('avatar_url', $existingColumns)) {
    $sql = "ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL AFTER avatar";
    if ($conn->query($sql)) {
        echo "✓ Added avatar_url column to users table\n";
    } else {
        echo "✗ Error adding avatar_url: " . $conn->error . "\n";
    }
}

if (!in_array('banner_url', $existingColumns)) {
    $sql = "ALTER TABLE users ADD COLUMN banner_url VARCHAR(255) DEFAULT NULL AFTER avatar_url";
    if ($conn->query($sql)) {
        echo "✓ Added banner_url column to users table\n";
    } else {
        echo "✗ Error adding banner_url: " . $conn->error . "\n";
    }
}

if (!in_array('bio', $existingColumns)) {
    $sql = "ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL AFTER banner_url";
    if ($conn->query($sql)) {
        echo "✓ Added bio column to users table\n";
    } else {
        echo "✗ Error adding bio: " . $conn->error . "\n";
    }
}

// Check playlists table
$result = $conn->query("DESCRIBE playlists");
$playlistColumns = [];
while ($row = $result->fetch_assoc()) {
    $playlistColumns[] = $row['Field'];
}

if (!in_array('updated_at', $playlistColumns)) {
    $sql = "ALTER TABLE playlists ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    if ($conn->query($sql)) {
        echo "✓ Added updated_at column to playlists table\n";
    } else {
        echo "✗ Error adding updated_at: " . $conn->error . "\n";
    }
}

if (!in_array('thumbnail', $playlistColumns)) {
    $sql = "ALTER TABLE playlists ADD COLUMN thumbnail VARCHAR(255) DEFAULT NULL AFTER description";
    if ($conn->query($sql)) {
        echo "✓ Added thumbnail column to playlists table\n";
    } else {
        echo "✗ Error adding thumbnail: " . $conn->error . "\n";
    }
}

// Create playlist_items table
$sql = "CREATE TABLE IF NOT EXISTS playlist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,
    content_id INT NOT NULL,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_playlist (playlist_id),
    INDEX idx_content (content_id)
)";
if ($conn->query($sql)) {
    echo "✓ Created playlist_items table\n";
} else {
    echo "✗ Error creating playlist_items: " . $conn->error . "\n";
}

$conn->close();
echo "\nMigrations complete!\n";
