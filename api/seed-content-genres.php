<?php
/**
 * Seed Content Genres Script
 * Associates movies with genres in the content_genre table
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();

// Define genre mappings based on content titles/descriptions
// Genres: 1=Action, 2=Comedy, 3=Drama, 4=Horror, 6=Sci-Fi, 7=Documentary, 16=Romance, 17=Thriller
$contentGenres = [
    // Movie 22: The Last Adventure - Action, Adventure, Thriller
    22 => [1, 17],  // Action, Thriller
    
    // Movie 23: Space Odyssey - Sci-Fi, Drama
    23 => [6, 3],   // Sci-Fi, Drama
    
    // Movie 24: Love in Paris - Romance, Drama
    24 => [16, 3],  // Romance, Drama
    
    // Movie 25: The Haunted Mansion - Horror, Thriller
    25 => [4, 17],  // Horror, Thriller
    
    // Movie 26: Tech Giants - Documentary
    26 => [7],      // Documentary
];

// Clear existing content_genre entries for these content items
$db->query("DELETE FROM content_genre WHERE content_id IN (" . implode(',', array_keys($contentGenres)) . ")");

// Insert new genre associations
$inserted = 0;
foreach ($contentGenres as $contentId => $genreIds) {
    foreach ($genreIds as $genreId) {
        $sql = "INSERT INTO content_genre (content_id, genre_id, created_at, updated_at) 
                VALUES ($contentId, $genreId, NOW(), NOW())";
        if ($db->query($sql)) {
            $inserted++;
            echo "Added genre $genreId to content $contentId\n";
        } else {
            echo "Failed to add genre $genreId to content $contentId: " . $db->error() . "\n";
        }
    }
}

echo "\nTotal genre associations inserted: $inserted\n";
echo "Done!\n";

