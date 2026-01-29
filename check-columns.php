<?php
// Simple script to check if columns were added
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "Checking users table columns...\n\n";

$result = $conn->query("DESCRIBE users");
echo "Current columns in users table:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}

echo "\nChecking for profile columns:\n";
$columns = ['bio', 'location', 'website', 'date_of_birth', 'preferences'];
foreach ($columns as $col) {
    $result = $conn->query("SHOW COLUMNS FROM users LIKE '{$col}'");
    if ($result->num_rows > 0) {
        echo "  ✓ {$col} EXISTS\n";
    } else {
        echo "  ✗ {$col} MISSING\n";
    }
}
