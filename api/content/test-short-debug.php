<?php
/**
 * Debug script for shorts API
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

header('Content-Type: application/json');

echo "=== DEBUG SHORTS API ===\n\n";

try {
    $db = Database::getInstance();

    // Check all shorts
    echo "1. All shorts in database:\n";
    $all = $db->fetchAll("SELECT id, title, content_type, duration_minutes, is_active FROM content WHERE content_type = 'short' ORDER BY id DESC LIMIT 10");
    foreach ($all as $row) {
        echo "   ID: {$row['id']}, Title: {$row['title']}, Duration: {$row['duration_minutes']}, Active: {$row['is_active']}\n";
    }

    echo "\n2. Filtered shorts (is_active=1 AND duration_minutes <= 5):\n";
    $filtered = $db->fetchAll("SELECT id, title, content_type, duration_minutes, is_active FROM content WHERE is_active=1 AND content_type='short' AND (duration_minutes IS NULL OR duration_minutes <= 5) ORDER BY id DESC LIMIT 10");
    foreach ($filtered as $row) {
        echo "   ID: {$row['id']}, Title: {$row['title']}, Duration: {$row['duration_minutes']}\n";
    }

    echo "\n3. Short with ID 20:\n";
    $short20 = $db->fetch("SELECT id, title, duration_minutes, is_active FROM content WHERE id = 20");
    echo "   " . json_encode($short20) . "\n";

    // Check if short 20 passes the filter
    $passesFilter = ($short20['is_active'] == 1 && ($short20['duration_minutes'] === null || $short20['duration_minutes'] <= 5));
    echo "   Passes filter: " . ($passesFilter ? "YES" : "NO") . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
