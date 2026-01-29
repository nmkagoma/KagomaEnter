<?php
/**
 * Database Setup Script
 * Creates all tables and seeds initial data
 * Run: http://localhost/KagomaEnter/sql/setup.php
 */

// Include configuration
require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Database Setup - KagomaEnter</title>";
echo "<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; background: #1a1a2e; color: #eee; }
    h1 { color: #4361ee; }
    .success { color: #4cc9f0; }
    .error { color: #f72585; }
    .info { color: #ffd700; }
    .step { margin: 20px 0; padding: 15px; background: #16213e; border-radius: 8px; border-left: 4px solid #4361ee; }
    .log { background: #0f172a; padding: 10px; border-radius: 4px; font-family: monospace; margin: 5px 0; }
    .btn { display: inline-block; padding: 12px 24px; background: #4361ee; color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; }
    .btn:hover { background: #3a0ca3; }
    .btn-success { background: #4cc9f0; color: #0f172a; }
    .btn-danger { background: #f72585; }
</style></head><body>";
echo "<h1>🗄️ KagomaEnter Database Setup</h1>";
echo "<p>This script will create all necessary database tables.</p>";

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

try {
    // Create Users Table
    logStep("Creating users table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            avatar_url VARCHAR(500) DEFAULT NULL,
            banner_url VARCHAR(500) DEFAULT NULL,
            bio TEXT DEFAULT NULL,
            location VARCHAR(255) DEFAULT NULL,
            website VARCHAR(255) DEFAULT NULL,
            date_of_birth DATE DEFAULT NULL,
            preferences LONGTEXT DEFAULT NULL,
            role ENUM('user', 'creator', 'admin') DEFAULT 'user',
            is_verified TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            last_login DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Users table created");

    // Create Content Types Table
    logStep("Creating content_types table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS content_types (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT DEFAULT NULL,
            icon VARCHAR(50) DEFAULT NULL,
            color VARCHAR(20) DEFAULT NULL,
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Content types table created");

    // Create Genres Table
    logStep("Creating genres table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS genres (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT DEFAULT NULL,
            icon VARCHAR(50) DEFAULT NULL,
            color VARCHAR(20) DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Genres table created");

    // Create Content Table
    logStep("Creating content table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS content (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT DEFAULT NULL,
            content_type VARCHAR(50) NOT NULL DEFAULT 'movie',
            thumbnail VARCHAR(500) DEFAULT NULL,
            thumbnail_url VARCHAR(500) DEFAULT NULL,
            banner_url VARCHAR(500) DEFAULT NULL,
            trailer_url VARCHAR(500) DEFAULT NULL,
            video_url VARCHAR(500) DEFAULT NULL,
            video_path VARCHAR(500) DEFAULT NULL,
            video_quality ENUM('360p', '480p', '720p', '1080p', '4K') DEFAULT '1080p',
            duration INT UNSIGNED DEFAULT NULL,
            duration_minutes INT UNSIGNED DEFAULT NULL,
            release_year INT DEFAULT NULL,
            age_rating VARCHAR(10) DEFAULT 'TV-MA',
            rating DECIMAL(3,2) DEFAULT NULL,
            view_count INT UNSIGNED DEFAULT 0,
            like_count INT UNSIGNED DEFAULT 0,
            comment_count INT UNSIGNED DEFAULT 0,
            is_featured TINYINT(1) DEFAULT 0,
            is_premium TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            published_at DATETIME DEFAULT NULL,
            created_by INT UNSIGNED DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_type (content_type),
            INDEX idx_featured (is_featured),
            INDEX idx_active (is_active),
            INDEX idx_published (published_at),
            INDEX idx_created (created_at),
            INDEX idx_view_count (view_count)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Content table created");

    // Create Content Genre Mapping Table
    logStep("Creating content_genre table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS content_genre (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            content_id INT UNSIGNED NOT NULL,
            genre_id INT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_content_genre (content_id, genre_id),
            INDEX idx_content_id (content_id),
            INDEX idx_genre_id (genre_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Content genre table created");

    // Create Episodes Table
    logStep("Creating episodes table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS episodes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            content_id INT UNSIGNED NOT NULL,
            season_number INT UNSIGNED DEFAULT 1,
            episode_number INT UNSIGNED DEFAULT 1,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            thumbnail VARCHAR(500) DEFAULT NULL,
            video_url VARCHAR(500) DEFAULT NULL,
            video_path VARCHAR(500) DEFAULT NULL,
            duration INT UNSIGNED DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            air_date DATE DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_content_id (content_id),
            INDEX idx_season_episode (content_id, season_number, episode_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Episodes table created");

    // Create Seasons Table
    logStep("Creating seasons table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS seasons (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            content_id INT UNSIGNED NOT NULL,
            season_number INT UNSIGNED DEFAULT 1,
            title VARCHAR(255) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            thumbnail VARCHAR(500) DEFAULT NULL,
            episode_count INT UNSIGNED DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            air_date_start DATE DEFAULT NULL,
            air_date_end DATE DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_content_id (content_id),
            INDEX idx_season_number (content_id, season_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Seasons table created");

    // Create Favorites Table
    logStep("Creating favorites table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS favorites (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            content_id INT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_content (user_id, content_id),
            INDEX idx_user_id (user_id),
            INDEX idx_content_id (content_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Favorites table created");

    // Create Watch History Table
    logStep("Creating watch_history table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS watch_history (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            content_id INT UNSIGNED NOT NULL,
            episode_id INT UNSIGNED DEFAULT NULL,
            progress DECIMAL(5,2) DEFAULT 0,
            watched_duration INT UNSIGNED DEFAULT 0,
            is_completed TINYINT(1) DEFAULT 0,
            last_watched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_content_episode (user_id, content_id, IFNULL(episode_id, 0)),
            INDEX idx_user_id (user_id),
            INDEX idx_content_id (content_id),
            INDEX idx_last_watched (last_watched_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Watch history table created");

    // Create Watch Later Table
    logStep("Creating watch_later table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS watch_later (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            content_id INT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_content (user_id, content_id),
            INDEX idx_user_id (user_id),
            INDEX idx_content_id (content_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Watch later table created");

    // Create Ratings Table
    logStep("Creating ratings table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS ratings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            content_id INT UNSIGNED NOT NULL,
            rating TINYINT(1) UNSIGNED NOT NULL,
            review TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_content (user_id, content_id),
            INDEX idx_user_id (user_id),
            INDEX idx_content_id (content_id),
            INDEX idx_rating (rating)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Ratings table created");

    // Create Comments Table
    logStep("Creating comments table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS comments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            content_id INT UNSIGNED NOT NULL,
            parent_id INT UNSIGNED DEFAULT NULL,
            comment TEXT NOT NULL,
            like_count INT UNSIGNED DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_content_id (content_id),
            INDEX idx_parent_id (parent_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Comments table created");

    // Create Playlists Table
    logStep("Creating playlists table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS playlists (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            cover_image VARCHAR(500) DEFAULT NULL,
            is_public TINYINT(1) DEFAULT 1,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_public (is_public)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Playlists table created");

    // Create Playlist Items Table
    logStep("Creating playlist_items table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS playlist_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            playlist_id INT UNSIGNED NOT NULL,
            content_id INT UNSIGNED NOT NULL,
            position INT UNSIGNED DEFAULT 0,
            added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_playlist_content (playlist_id, content_id),
            INDEX idx_playlist_id (playlist_id),
            INDEX idx_content_id (content_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Playlist items table created");

    // Create Channels Table
    logStep("Creating channels table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS channels (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT DEFAULT NULL,
            avatar_url VARCHAR(500) DEFAULT NULL,
            banner_url VARCHAR(500) DEFAULT NULL,
            subscriber_count INT UNSIGNED DEFAULT 0,
            video_count INT UNSIGNED DEFAULT 0,
            total_views BIGINT UNSIGNED DEFAULT 0,
            is_verified TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Channels table created");

    // Create Channel Subscribers Table
    logStep("Creating channel_subscribers table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS channel_subscribers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            channel_id INT UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_channel (user_id, channel_id),
            INDEX idx_user_id (user_id),
            INDEX idx_channel_id (channel_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Channel subscribers table created");

    // Create Notifications Table
    logStep("Creating notifications table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT DEFAULT NULL,
            data JSON DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_read (is_read),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Notifications table created");

    // Create Content Likes Table
    logStep("Creating content_likes table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS content_likes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            content_id INT UNSIGNED NOT NULL,
            like_type ENUM('like', 'dislike') DEFAULT 'like',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_content (user_id, content_id),
            INDEX idx_user_id (user_id),
            INDEX idx_content_id (content_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Content likes table created");

    // Create Content Views Table
    logStep("Creating content_views table...");
    $db->query("
        CREATE TABLE IF NOT EXISTS content_views (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            content_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED DEFAULT NULL,
            episode_id INT UNSIGNED DEFAULT NULL,
            watch_duration INT UNSIGNED DEFAULT 0,
            completed TINYINT(1) DEFAULT 0,
            device_type VARCHAR(20) DEFAULT NULL,
            country_code VARCHAR(10) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_content_id (content_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    logLog("✓ Content views table created");

    // Seed default data
    logStep("Seeding default content types...");
    $db->query("INSERT IGNORE INTO content_types (name, slug, icon, color, sort_order) VALUES
        ('Movies', 'movie', 'fa-film', '#e50914', 1),
        ('TV Series', 'series', 'fa-tv', '#4a90d9', 2),
        ('Shorts', 'shorts', 'fa-bolt', '#ff6b6b', 3),
        ('Documentary', 'documentary', 'fa-film', '#228b22', 4),
        ('Anime', 'anime', 'fa-dragon', '#ff6347', 5),
        ('Live', 'live', 'fa-broadcast-tower', '#9b59b6', 6)
    ");
    logLog("✓ Content types seeded");

    logStep("Seeding default genres...");
    $db->query("INSERT IGNORE INTO genres (name, slug, icon, color) VALUES
        ('Action', 'action', 'fa-fist-raised', '#e50914'),
        ('Comedy', 'comedy', 'fa-laugh', '#ffa500'),
        ('Drama', 'drama', 'fa-theater-masks', '#4a90d9'),
        ('Horror', 'horror', 'fa-ghost', '#2d2d2d'),
        ('Romance', 'romance', 'fa-heart', '#ff69b4'),
        ('Sci-Fi', 'sci-fi', 'fa-rocket', '#00ced1'),
        ('Documentary', 'documentary', 'fa-film', '#228b22'),
        ('Anime', 'anime', 'fa-dragon', '#ff6347'),
        ('Thriller', 'thriller', 'fa-exclamation-triangle', '#2c3e50'),
        ('Fantasy', 'fantasy', 'fa-hat-wizard', '#9b59b6'),
        ('Mystery', 'mystery', 'fa-question-circle', '#8e44ad'),
        ('Animation', 'animation', 'fa-film', '#3498db')
    ");
    logLog("✓ Genres seeded");

    // Get counts
    $tables = ['users', 'content', 'genres', 'content_types', 'content_genre', 'episodes', 'seasons', 'favorites', 'watch_history'];
    $tableCounts = [];
    foreach ($tables as $table) {
        $count = $db->fetch("SELECT COUNT(*) as count FROM $table")['count'];
        $tableCounts[$table] = $count;
    }

    logStep("Setup completed successfully!", 'success');
    echo "<div class='step' style='background: linear-gradient(135deg, #4361ee, #7209b7); border-left-color: #4cc9f0;'>";
    echo "<h3 style='color: #4cc9f0; margin: 0;'>🎉 Database Setup Complete!</h3>";
    echo "<p style='margin: 15px 0 0 0;'><strong>Tables Created:</strong> " . count($tables) . "</p>";
    echo "<p><strong>Sample Users:</strong> " . $tableCounts['users'] . "</p>";
    echo "<p><strong>Content Items:</strong> " . $tableCounts['content'] . "</p>";
    echo "<p><strong>Genres:</strong> " . $tableCounts['genres'] . "</p>";
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='../sql/seed.php' class='btn btn-success'>🌱 Seed Sample Content</a>";
    echo "<a href='../index.html' class='btn'>🏠 Go to Homepage</a>";
    echo "<a href='../browse.html' class='btn'>🔍 Go to Browse</a>";
    echo "</div>";
    echo "</div>";

} catch (Exception $e) {
    logStep("Error: " . $e->getMessage(), 'error');
    echo "<div class='step' style='border-left-color: #f72585;'>";
    echo "<p>Please check your database configuration in <code>config/config.php</code></p>";
    echo "<p>Make sure MySQL is running and the database 'KagomaEnter' exists.</p>";
    echo "</div>";
}

echo "</body></html>";

