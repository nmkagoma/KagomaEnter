<?php
/**
 * Add profile columns to users table
 * Run this to add missing columns to the database
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: text/plain');

echo "Adding profile columns to users table...\n\n";

// Direct database connection
$conn = new mysqli('localhost', 'root', '', 'KagomaEnter');

if ($conn->connect_error) {
    echo "Error: Could not connect to database - " . $conn->connect_error . "\n";
    echo "\nMake sure MySQL is running and the database exists.\n";
    exit;
}

echo "Connected to database successfully.\n\n";

try {
    // Check current columns
    $result = $conn->query("DESCRIBE users");
    if (!$result) {
        throw new Exception("Could not describe users table: " . $conn->error);
    }
    
    $existingColumns = [];
    while ($row = $result->fetch_assoc()) {
        $existingColumns[] = $row['Field'];
    }
    echo "Existing columns: " . implode(', ', $existingColumns) . "\n\n";
    
    // Columns to add
    $columns = [
        'bio' => 'TEXT DEFAULT NULL',
        'location' => 'VARCHAR(255) DEFAULT NULL',
        'website' => 'VARCHAR(255) DEFAULT NULL',
        'date_of_birth' => 'DATE DEFAULT NULL',
        'preferences' => 'LONGTEXT DEFAULT NULL'
    ];
    
    foreach ($columns as $column => $type) {
        if (in_array($column, $existingColumns)) {
            echo "[OK] Column '{$column}' already exists\n";
            continue;
        }
        
        $sql = "ALTER TABLE users ADD COLUMN {$column} {$type}";
        
        if ($conn->query($sql)) {
            echo "[OK] Added column '{$column}'\n";
        } else {
            echo "[ERROR] Failed to add '{$column}': " . $conn->error . "\n";
        }
    }
    
    echo "\nDone! New table structure:\n";
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "  {$row['Field']} - {$row['Type']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    $conn->close();
}

