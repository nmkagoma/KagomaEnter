<?php
/**
 * Database Seed Script
 * Populates the database with sample content for testing
 * Run this file directly in your browser: http://localhost/KagomaEnter/sql/seed.php
 */

// Include configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Utils.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Database Seeder - KagomaEnter</title>";
echo "<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; background: #1a1a2e; color: #eee; }
    h1 { color: #4361ee; }
    .success { color: #4cc9f0; }
    .error { color: #f72585; }
    .info { color: #ffd700; }
    .step { margin: 20px 0; padding: 15px; background: #16213e; border-radius: 8px; border-left: 4px solid #4361ee; }
    .log { background: #0f172a; padding: 10px; border-radius: 4px; font-family: monospace; margin: 5px 0; }
    .progress { width: 100%; height: 20px; background: #333; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .progress-bar { height: 100%; background: linear-gradient(90deg, #4361ee, #4cc9f0); transition: width 0.3s; }
    a { color: #4cc9f0; }
</style></head><body>";
echo "<h1>🎬 KagomaEnter Database Seeder</h1>";
echo "<p>This script will populate your database with sample content for testing.</p>";

$db = Database::getInstance();

function logStep($message, $type = 'info') {
    $color = $type === 'success' ? '#4cc9f0' : ($type === 'error' ? '#f72585' : '#ffd700');
    echo "<div class='step'><span class='$type' style='color: $color'>●</span> $message</div>";
    flush();
    ob_flush();
}

function logLog($message) {
    echo "<div class='log'>$message</div>";
    flush();
    ob_flush();
}

// ============== SEED DATA ==============

logStep("Starting database seeding...");

// Seed Genres
logStep("Seeding genres...");
$genres = [
    ['name' => 'Action', 'slug' => 'action', 'icon' => 'fa-fist-raised', 'color' => '#e50914'],
    ['name' => 'Comedy', 'slug' => 'comedy', 'icon' => 'fa-laugh', 'color' => '#ffa500'],
    ['name' => 'Drama', 'slug' => 'drama', 'icon' => 'fa-theater-masks', 'color' => '#4a90d9'],
    ['name' => 'Horror', 'slug' => 'horror', 'icon' => 'fa-ghost', 'color' => '#2d2d2d'],
    ['name' => 'Romance', 'slug' => 'romance', 'icon' => 'fa-heart', 'color' => '#ff69b4'],
    ['name' => 'Sci-Fi', 'slug' => 'sci-fi', 'icon' => 'fa-rocket', 'color' => '#00ced1'],
    ['name' => 'Documentary', 'slug' => 'documentary', 'icon' => 'fa-film', 'color' => '#228b22'],
    ['name' => 'Anime', 'slug' => 'anime', 'icon' => 'fa-dragon', 'color' => '#ff6347'],
    ['name' => 'Thriller', 'slug' => 'thriller', 'icon' => 'fa-exclamation-triangle', 'color' => '#2c3e50'],
    ['name' => 'Fantasy', 'slug' => 'fantasy', 'icon' => 'fa-hat-wizard', 'color' => '#9b59b6'],
    ['name' => 'Mystery', 'slug' => 'mystery', 'icon' => 'fa-question-circle', 'color' => '#8e44ad'],
    ['name' => 'Animation', 'slug' => 'animation', 'icon' => 'fa-film', 'color' => '#3498db'],
];

$genreIds = [];
foreach ($genres as $genre) {
    // Check if exists
    $existing = $db->fetch("SELECT id FROM genres WHERE slug = ?", [$genre['slug']]);
    if (!$existing) {
        $db->insert('genres', $genre);
        logLog("✓ Added genre: {$genre['name']}");
    } else {
        $genreIds[$genre['slug']] = $existing['id'];
        logLog("✓ Genre exists: {$genre['name']}");
    }
    // Get ID
    $result = $db->fetch("SELECT id FROM genres WHERE slug = ?", [$genre['slug']]);
    $genreIds[$genre['slug']] = $result['id'];
}

// Seed Content Types
logStep("Seeding content types...");
$contentTypes = [
    ['name' => 'Movies', 'slug' => 'movie', 'icon' => 'fa-film', 'color' => '#e50914', 'sort_order' => 1],
    ['name' => 'TV Series', 'slug' => 'series', 'icon' => 'fa-tv', 'color' => '#4a90d9', 'sort_order' => 2],
    ['name' => 'Shorts', 'slug' => 'short', 'icon' => 'fa-bolt', 'color' => '#ff6b6b', 'sort_order' => 3],
];

foreach ($contentTypes as $type) {
    $existing = $db->fetch("SELECT id FROM content_types WHERE slug = ?", [$type['slug']]);
    if (!$existing) {
        $db->insert('content_types', $type);
        logLog("✓ Added content type: {$type['name']}");
    } else {
        logLog("✓ Content type exists: {$type['name']}");
    }
}

// Sample Movies
logStep("Seeding sample movies...");
$movies = [
    [
        'title' => 'The Last Kingdom',
        'slug' => 'the-last-kingdom',
        'description' => 'A epic historical drama following a noble Saxon warrior who is torn between loyalty to his Viking mentor and his King during the turbulent 10th century.',
        'content_type' => 'movie',
        'thumbnail' => 'https://picsum.photos/seed/movie1/400/225',
        'thumbnail_url' => 'https://picsum.photos/seed/movie1/400/225',
        'banner_url' => 'https://picsum.photos/seed/movie1/1200/400',
        'video_quality' => '1080p',
        'duration' => 145,
        'release_year' => 2024,
        'age_rating' => 'TV-MA',
        'rating' => 94.5,
        'view_count' => 1250000,
        'is_featured' => 1,
        'is_premium' => 0,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-1 week'))
    ],
    [
        'title' => 'Cyber Chase',
        'slug' => 'cyber-chase',
        'description' => 'In a world where reality and virtual reality blend, a team of hackers must save humanity from a rogue AI threatening to overwrite consciousness.',
        'content_type' => 'movie',
        'thumbnail' => 'https://picsum.photos/seed/movie2/400/225',
        'thumbnail_url' => 'https://picsum.photos/seed/movie2/400/225',
        'banner_url' => 'https://picsum.photos/seed/movie2/1200/400',
        'video_quality' => '4K',
        'duration' => 128,
        'release_year' => 2024,
        'age_rating' => 'PG-13',
        'rating' => 89.2,
        'view_count' => 890000,
        'is_featured' => 1,
        'is_premium' => 1,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
    ],
    [
        'title' => 'Love in Paris',
        'slug' => 'love-in-paris',
        'description' => 'A heartwarming romantic comedy about an American journalist who falls for a French chef during a romantic getaway to Paris.',
        'content_type' => 'movie',
        'thumbnail' => 'https://picsum.photos/seed/movie3/400/225',
        'thumbnail_url' => 'https://picsum.photos/seed/movie3/400/225',
        'banner_url' => 'https://picsum.photos/seed/movie3/1200/400',
        'video_quality' => '1080p',
        'duration' => 105,
        'release_year' => 2024,
        'age_rating' => 'PG',
        'rating' => 87.8,
        'view_count' => 650000,
        'is_featured' => 0,
        'is_premium' => 0,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
    ],
    [
        'title' => 'Dark Shadows',
        'slug' => 'dark-shadows',
        'description' => 'A horror anthology series exploring supernatural events, haunted houses, and the darkest corners of human psychology.',
        'content_type' => 'movie',
        'thumbnail' => 'https://picsum.photos/seed/movie4/400/225',
        'thumbnail_url' => 'https://picsum.photos/seed/movie4/400/225',
        'banner_url' => 'https://picsum.photos/seed/movie4/1200/400',
        'video_quality' => '1080p',
        'duration' => 118,
        'release_year' => 2024,
        'age_rating' => 'TV-MA',
        'rating' => 92.1,
        'view_count' => 780000,
        'is_featured' => 0,
        'is_premium' => 0,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
    ],
    [
        'title' => 'Comedy Gold',
        'slug' => 'comedy-gold',
        'description' => 'A laugh-out-loud comedy about a struggling comedian who accidentally becomes famous for all the wrong reasons.',
        'content_type' => 'movie',
        'thumbnail' => 'https://picsum.photos/seed/movie5/400/225',
        'thumbnail_url' => 'https://picsum.photos/seed/movie5/400/225',
        'banner_url' => 'https://picsum.photos/seed/movie5/1200/400',
        'video_quality' => '1080p',
        'duration' => 95,
        'release_year' => 2024,
        'age_rating' => 'PG-13',
        'rating' => 85.6,
        'view_count' => 520000,
        'is_featured' => 0,
        'is_premium' => 0,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
    ],
];

foreach ($movies as $movie) {
    $existing = $db->fetch("SELECT id FROM content WHERE slug = ?", [$movie['slug']]);
    if (!$existing) {
        $movie['created_at'] = date('Y-m-d H:i:s');
        $movie['updated_at'] = date('Y-m-d H:i:s');
        $contentId = $db->insert('content', $movie);
        logLog("✓ Added movie: {$movie['title']}");
        
        // Add random genres
        $randomGenres = array_rand($genreIds, min(2, count($genreIds)));
        if (!is_array($randomGenres)) $randomGenres = [$randomGenres];
        foreach ($randomGenres as $slug) {
            $db->insert('content_genre', [
                'content_id' => $contentId,
                'genre_id' => $genreIds[$slug],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    } else {
        logLog("✓ Movie exists: {$movie['title']}");
    }
}

// Sample TV Series
logStep("Seeding sample TV series...");
$series = [
    [
        'title' => 'Breaking Dawn',
        'slug' => 'breaking-dawn',
        'description' => 'A gripping crime drama following a detective investigating a series of mysterious disappearances in a small town with dark secrets.',
        'content_type' => 'series',
        'thumbnail' => 'https://picsum.photos/seed/series1/400/225',
        'thumbnail_url' => 'https://picsum.photos/seed/series1/400/225',
        'banner_url' => 'https://picsum.photos/seed/series1/1200/400',
        'video_quality' => '4K',
        'duration' => 55,
        'release_year' => 2024,
        'age_rating' => 'TV-MA',
        'rating' => 96.8,
        'view_count' => 3500000,
        'is_featured' => 1,
        'is_premium' => 1,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-1 month'))
    ],
    [
        'title' => 'Office Life',
        'slug' => 'office-life',
        'description' => 'A hilarious workplace comedy following the everyday lives of employees at a dysfunctional tech startup.',
        'content_type' => 'series',
        'thumbnail' => 'https://picsum.photos/seed/series2/400/225',
        'thumbnail_url' => 'https://picsum.photos/seed/series2/400/225',
        'banner_url' => 'https://picsum.photos/seed/series2/1200/400',
        'video_quality' => '1080p',
        'duration' => 30,
        'release_year' => 2024,
        'age_rating' => 'PG-13',
        'rating' => 91.2,
        'view_count' => 2100000,
        'is_featured' => 1,
        'is_premium' => 0,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-2 weeks'))
    ],
    [
        'title' => 'Space Frontier',
        'slug' => 'space-frontier',
        'description' => 'An epic sci-fi series following the crew of humanitys first interstellar mission as they explore unknown galaxies.',
        'content_type' => 'series',
        'thumbnail' => 'https://picsum.photos/seed/series3/400/225',
        'thumbnail_url' => 'https://picsum.photos/seed/series3/400/225',
        'banner_url' => 'https://picsum.photos/seed/series3/1200/400',
        'video_quality' => '4K',
        'duration' => 50,
        'release_year' => 2024,
        'age_rating' => 'PG-13',
        'rating' => 93.5,
        'view_count' => 2800000,
        'is_featured' => 1,
        'is_premium' => 1,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-10 days'))
    ],
    [
        'title' => 'Medical Mysteries',
        'slug' => 'medical-mysteries',
        'description' => 'A drama series following brilliant doctors solving rare and unusual medical cases while navigating their personal lives.',
        'content_type' => 'series',
        'thumbnail' => 'https://picsum.photos/seed/series4/400/225',
        'thumbnail_url' => 'https://picsum.photos/seed/series4/400/225',
        'banner_url' => 'https://picsum.photos/seed/series4/1200/400',
        'video_quality' => '1080p',
        'duration' => 45,
        'release_year' => 2024,
        'age_rating' => 'TV-14',
        'rating' => 89.9,
        'view_count' => 1650000,
        'is_featured' => 0,
        'is_premium' => 0,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-1 week'))
    ],
];

foreach ($series as $show) {
    $existing = $db->fetch("SELECT id FROM content WHERE slug = ?", [$show['slug']]);
    if (!$existing) {
        $show['created_at'] = date('Y-m-d H:i:s');
        $show['updated_at'] = date('Y-m-d H:i:s');
        $contentId = $db->insert('content', $show);
        logLog("✓ Added series: {$show['title']}");
        
        // Add seasons and episodes
        $seasonId = $db->insert('seasons', [
            'content_id' => $contentId,
            'season_number' => 1,
            'title' => 'Season 1',
            'episode_count' => 10,
            'is_active' => 1
        ]);
        
        // Add episodes
        for ($ep = 1; $ep <= 10; $ep++) {
            $db->insert('episodes', [
                'content_id' => $contentId,
                'season_number' => 1,
                'episode_number' => $ep,
                'title' => "Episode $ep",
                'description' => "In this episode, the story continues...",
                'duration' => rand(30, 60),
                'is_active' => 1,
                'air_date' => date('Y-m-d', strtotime("-$ep days"))
            ]);
        }
        
        // Add random genres
        $randomGenres = array_rand($genreIds, min(2, count($genreIds)));
        if (!is_array($randomGenres)) $randomGenres = [$randomGenres];
        foreach ($randomGenres as $slug) {
            $db->insert('content_genre', [
                'content_id' => $contentId,
                'genre_id' => $genreIds[$slug],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    } else {
        logLog("✓ Series exists: {$show['title']}");
    }
}

// Sample Shorts
logStep("Seeding sample shorts...");
$shorts = [
    [
        'title' => 'Quick Laugh',
        'slug' => 'quick-laugh',
        'description' => 'A hilarious 60-second comedy clip',
        'content_type' => 'short',
        'thumbnail' => 'https://picsum.photos/seed/short1/200/355',
        'thumbnail_url' => 'https://picsum.photos/seed/short1/200/355',
        'video_quality' => '1080p',
        'duration' => 60,
        'duration_minutes' => 1,
        'release_year' => 2024,
        'age_rating' => 'PG',
        'rating' => 88.5,
        'view_count' => 450000,
        'like_count' => 32000,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
    ],
    [
        'title' => 'Amazing Nature',
        'slug' => 'amazing-nature',
        'description' => 'Stunning wildlife footage from around the world',
        'content_type' => 'short',
        'thumbnail' => 'https://picsum.photos/seed/short2/200/355',
        'thumbnail_url' => 'https://picsum.photos/seed/short2/200/355',
        'video_quality' => '4K',
        'duration' => 90,
        'duration_minutes' => 1,
        'release_year' => 2024,
        'age_rating' => 'G',
        'rating' => 94.2,
        'view_count' => 680000,
        'like_count' => 45000,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
    ],
    [
        'title' => 'Tech Tips',
        'slug' => 'tech-tips',
        'description' => 'Quick tech tips to boost your productivity',
        'content_type' => 'short',
        'thumbnail' => 'https://picsum.photos/seed/short3/200/355',
        'thumbnail_url' => 'https://picsum.photos/seed/short3/200/355',
        'video_quality' => '1080p',
        'duration' => 45,
        'duration_minutes' => 1,
        'release_year' => 2024,
        'age_rating' => 'G',
        'rating' => 86.7,
        'view_count' => 320000,
        'like_count' => 18000,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))
    ],
    [
        'title' => 'Cooking Masterclass',
        'slug' => 'cooking-masterclass',
        'description' => 'Learn to make the perfect pasta in 90 seconds',
        'content_type' => 'short',
        'thumbnail' => 'https://picsum.photos/seed/short4/200/355',
        'thumbnail_url' => 'https://picsum.photos/seed/short4/200/355',
        'video_quality' => '1080p',
        'duration' => 90,
        'duration_minutes' => 1,
        'release_year' => 2024,
        'age_rating' => 'G',
        'rating' => 91.3,
        'view_count' => 520000,
        'like_count' => 38000,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
    ],
    [
        'title' => 'Dance Challenge',
        'slug' => 'dance-challenge',
        'description' => 'Join the viral dance challenge taking over social media',
        'content_type' => 'short',
        'thumbnail' => 'https://picsum.photos/seed/short5/200/355',
        'thumbnail_url' => 'https://picsum.photos/seed/short5/200/355',
        'video_quality' => '1080p',
        'duration' => 30,
        'duration_minutes' => 1,
        'release_year' => 2024,
        'age_rating' => 'PG',
        'rating' => 89.8,
        'view_count' => 1200000,
        'like_count' => 95000,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-6 hours'))
    ],
    [
        'title' => 'Fitness Quick Fix',
        'slug' => 'fitness-quick-fix',
        'description' => '5-minute workout you can do anywhere',
        'content_type' => 'short',
        'thumbnail' => 'https://picsum.photos/seed/short6/200/355',
        'thumbnail_url' => 'https://picsum.photos/seed/short6/200/355',
        'video_quality' => '1080p',
        'duration' => 300,
        'duration_minutes' => 5,
        'release_year' => 2024,
        'age_rating' => 'PG',
        'rating' => 87.5,
        'view_count' => 780000,
        'like_count' => 52000,
        'is_active' => 1,
        'published_at' => date('Y-m-d H:i:s', strtotime('-8 hours'))
    ],
];

foreach ($shorts as $short) {
    $existing = $db->fetch("SELECT id FROM content WHERE slug = ?", [$short['slug']]);
    if (!$existing) {
        $short['created_at'] = date('Y-m-d H:i:s');
        $short['updated_at'] = date('Y-m-d H:i:s');
        $db->insert('content', $short);
        logLog("✓ Added short: {$short['title']}");
    } else {
        logLog("✓ Short exists: {$short['title']}");
    }
}

// Get counts
$contentCount = $db->fetch("SELECT COUNT(*) as count FROM content")['count'];
$genresCount = $db->fetch("SELECT COUNT(*) as count FROM genres")['count'];

logStep("Seeding completed successfully!", 'success');
echo "<div class='step' style='background: linear-gradient(135deg, #4361ee, #7209b7); border-left-color: #4cc9f0;'>";
echo "<h3 style='color: #4cc9f0; margin: 0;'>🎉 Database seeded successfully!</h3>";
echo "<p style='margin: 10px 0 0 0;'>Total content: <strong>$contentCount</strong> items</p>";
echo "<p>Total genres: <strong>$genresCount</strong> items</p>";
echo "<p style='margin-top: 15px;'><a href='../index.html' style='color: #4cc9f0;'>→ Go to Homepage</a></p>";
echo "<p><a href='../browse.html' style='color: #4cc9f0;'>→ Go to Browse Page</a></p>";
echo "<p><a href='../shorts.html' style='color: #4cc9f0;'>→ Go to Shorts Page</a></p>";
echo "</div>";

echo "</body></html>";

