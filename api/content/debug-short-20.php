<?php
/**
 * Debug script to check if short ID 20 is returned by the API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

header('Content-Type: application/json');

echo "=== Debug: Checking Short ID 20 ===\n\n";

$db = Database::getInstance();

// Check if short 20 exists
echo "1. Checking if short ID 20 exists:\n";
$sql = "SELECT id, title, content_type, is_active, user_id FROM content WHERE id = 20";
$result = $db->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "   FOUND: " . json_encode($row, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "   NOT FOUND!\n\n";
}

// Check all active shorts
echo "2. All active shorts:\n";
$sql = "SELECT id, title, content_type, is_active FROM content WHERE is_active = 1 AND content_type = 'short' ORDER BY created_at DESC LIMIT 20";
$result = $db->query($sql);
if ($result && $result->num_rows > 0) {
    echo "   Found " . $result->num_rows . " shorts:\n";
    while ($row = $result->fetch_assoc()) {
        echo "   - ID {$row['id']}: {$row['title']} (is_active={$row['is_active']})\n";
    }
} else {
    echo "   No shorts found!\n";
}

echo "\n3. Checking shorts API query result:\n";
$limit = 10;
$offset = 0;
$sql = "SELECT 
    c.id, c.title, c.slug, c.description, c.thumbnail, c.thumbnail_url,
    c.video_url, c.video_path, c.duration, c.duration_minutes, 
    c.release_year, c.view_count, c.like_count, c.user_has_liked,
    c.is_premium, c.content_type, c.age_rating,
    c.created_at,
    (SELECT COUNT(*) FROM comments WHERE content_id = c.id) as comment_count
FROM content c
WHERE c.is_active = 1 AND c.content_type = 'short'
ORDER BY c.created_at DESC
LIMIT {$limit} OFFSET {$offset}";

$result = $db->query($sql);
if ($result && $result->num_rows > 0) {
    echo "   API returns {$result->num_rows} shorts:\n";
    while ($row = $result->fetch_assoc()) {
        $thumbnail = $row['thumbnail'] ?? $row['thumbnail_url'] ?? 'NULL';
        echo "   - ID {$row['id']}: {$row['title']}\n";
        echo "     thumbnail: {$thumbnail}\n";
        echo "     created_at: {$row['created_at']}\n\n";
    }
} else {
    echo "   Query returned no results!\n";
    echo "   Error: " . $db->error() . "\n";
}

echo "\n=== End Debug ===\n";
