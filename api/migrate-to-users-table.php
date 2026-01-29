<?php
/**
 * Migration: Remove user_profiles table and move fields to users table
 * Run this script to migrate data and drop user_profiles table
 */

// Turn off error display
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Response.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Start transaction
    $db->getConnection()->begin_transaction();
    
    // Step 1: Add missing columns to users table (if not exist)
    $columnsToAdd = [
        'bio' => 'TEXT DEFAULT NULL',
        'location' => 'VARCHAR(255) DEFAULT NULL',
        'website' => 'VARCHAR(255) DEFAULT NULL',
        'date_of_birth' => 'DATE DEFAULT NULL',
        'preferences' => 'LONGTEXT DEFAULT NULL'
    ];
    
    // Check existing columns
    $existingColumns = $db->fetchAll("DESCRIBE users");
    $existingColumnNames = array_map(function($col) { return $col['Field']; }, $existingColumns);
    
    foreach ($columnsToAdd as $column => $definition) {
        if (!in_array($column, $existingColumnNames)) {
            $sql = "ALTER TABLE users ADD COLUMN {$column} {$definition}";
            if (!$db->getConnection()->query($sql)) {
                throw new Exception("Failed to add {$column}: " . $db->getConnection()->error);
            }
            echo "Added column: {$column}\n";
        } else {
            echo "Column already exists: {$column}\n";
        }
    }
    
    // Step 2: Migrate data from user_profiles to users
    echo "Migrating data from user_profiles to users...\n";
    
    $profiles = $db->fetchAll("SELECT * FROM user_profiles");
    foreach ($profiles as $profile) {
        $updateData = [];
        
        if (!empty($profile['bio'])) {
            $updateData['bio'] = $profile['bio'];
        }
        if (!empty($profile['location'])) {
            $updateData['location'] = $profile['location'];
        }
        if (!empty($profile['website'])) {
            $updateData['website'] = $profile['website'];
        }
        if (!empty($profile['date_of_birth'])) {
            $updateData['date_of_birth'] = $profile['date_of_birth'];
        }
        if (!empty($profile['preferences'])) {
            $updateData['preferences'] = $profile['preferences'];
        }
        
        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $db->update('users', $updateData, 'id = ?', [$profile['user_id']]);
            echo "Migrated profile for user {$profile['user_id']}\n";
        }
    }
    
    // Step 3: Drop user_profiles table
    echo "Dropping user_profiles table...\n";
    $db->getConnection()->query("DROP TABLE IF EXISTS user_profiles");
    echo "user_profiles table dropped successfully\n";
    
    // Commit transaction
    $db->getConnection()->commit();
    
    Response::success('Migration completed successfully. user_profiles table has been removed and data migrated to users.', [
        'profiles_migrated' => count($profiles)
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn = $db->getConnection();
    if ($conn) {
        $conn->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Migration failed',
        'error' => $e->getMessage()
    ]);
}
