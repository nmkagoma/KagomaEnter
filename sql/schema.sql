-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 22, 2026 at 08:15 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `KagomaEnter`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `item_type`, `item_id`, `metadata`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 15, 'create_content', 'content', 20, '{\"title\":\"PERN STACK\",\"type\":\"short\"}', NULL, NULL, '2026-01-20 21:55:26'),
(2, 15, 'create_content', 'content', 21, '{\"title\":\"Technical\",\"type\":\"short\"}', NULL, NULL, '2026-01-21 14:08:30');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `channels`
--

CREATE TABLE `channels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `subscribers_count` int(11) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `channel_subscriptions`
--

CREATE TABLE `channel_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `channel_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `content_id` bigint(20) UNSIGNED DEFAULT NULL,
  `episode_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `comment` text NOT NULL,
  `likes_count` int(11) NOT NULL DEFAULT 0,
  `dislikes_count` int(11) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment_likes`
--

CREATE TABLE `comment_likes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `comment_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('like','dislike') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `channel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `trailer_url` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `video_quality` varchar(255) NOT NULL DEFAULT '1080p',
  `file_size` bigint(20) NOT NULL DEFAULT 0,
  `duration` int(11) NOT NULL DEFAULT 0,
  `duration_minutes` tinyint(3) UNSIGNED DEFAULT NULL,
  `release_year` int(11) DEFAULT NULL,
  `content_type` enum('movie','series','live','documentary','anime','short') DEFAULT 'movie',
  `content_type_id` bigint(20) UNSIGNED DEFAULT NULL,
  `age_rating` enum('G','PG','PG-13','R','NC-17','TV-Y','TV-PG','TV-14','TV-MA') NOT NULL DEFAULT 'PG-13',
  `rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `like_count` int(10) UNSIGNED DEFAULT 0,
  `comment_count` int(11) DEFAULT 0,
  `user_has_liked` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_premium` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `published_at` timestamp NULL DEFAULT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `content`
--

INSERT INTO `content` (`id`, `user_id`, `channel_id`, `title`, `slug`, `description`, `thumbnail`, `thumbnail_url`, `trailer_url`, `video_url`, `video_path`, `video_quality`, `file_size`, `duration`, `duration_minutes`, `release_year`, `content_type`, `content_type_id`, `age_rating`, `rating`, `view_count`, `like_count`, `comment_count`, `user_has_liked`, `is_featured`, `is_premium`, `is_active`, `published_at`, `uploaded_by`, `created_at`, `updated_at`) VALUES
(20, 15, NULL, 'PERN STACK', 'pern-stack', 'TUTORIAL', NULL, NULL, NULL, '/KagomaEnter/uploads/videos/video_1768946126_fc1abf3a.webm', '/opt/lampp/htdocs/KagomaEnter/api/content/../../uploads/videos/video_1768946126_fc1abf3a.webm', '1080p', 0, 3, 3, NULL, 'short', NULL, 'PG-13', 0.00, 0, 0, 0, 0, 0, 0, 1, '2026-01-20 21:55:26', NULL, '2026-01-20 22:22:38', '2026-01-20 22:22:38'),
(21, 15, NULL, 'Technical', 'technical', 'become geek in tech', NULL, NULL, NULL, '/KagomaEnter/uploads/videos/video_1769004510_1fc9d737.webm', '/opt/lampp/htdocs/KagomaEnter/api/content/../../uploads/videos/video_1769004510_1fc9d737.webm', '1080p', 0, 15, 0, NULL, 'short', NULL, 'PG-13', 0.00, 0, 0, 0, 0, 0, 0, 1, '2026-01-21 14:08:30', NULL, '2026-01-21 14:08:30', '2026-01-21 14:08:30'),
(22, NULL, NULL, 'The Last Adventure', 'the-last-adventure', 'Epic journey of three friends seeking legendary treasure.', NULL, NULL, NULL, NULL, NULL, '1080p', 0, 7200, NULL, 2024, 'movie', NULL, 'PG-13', 4.50, 15000, 0, 0, 0, 1, 0, 1, NULL, NULL, NULL, NULL),
(23, NULL, NULL, 'Space Odyssey', 'space-odyssey', 'Humanity explores the farthest reaches of the galaxy.', NULL, NULL, NULL, NULL, NULL, '4K', 0, 9000, NULL, 2024, 'movie', NULL, 'PG-13', 4.80, 25000, 0, 0, 0, 1, 1, 1, NULL, NULL, NULL, NULL),
(24, NULL, NULL, 'Love in Paris', 'love-in-paris', 'Two strangers meet in the City of Light.', NULL, NULL, NULL, NULL, NULL, '1080p', 0, 5400, NULL, 2023, 'movie', NULL, 'PG', 4.20, 12000, 0, 0, 0, 0, 0, 1, NULL, NULL, NULL, NULL),
(25, NULL, NULL, 'The Haunted Mansion', 'the-haunted-mansion', 'A family discovers their home is haunted.', NULL, NULL, NULL, NULL, NULL, '1080p', 0, 6300, NULL, 2024, 'movie', NULL, 'PG-13', 4.00, 18000, 0, 0, 0, 0, 0, 1, NULL, NULL, NULL, NULL),
(26, NULL, NULL, 'Tech Giants', 'tech-giants', 'Rise of technology companies documentary.', NULL, NULL, NULL, NULL, NULL, '1080p', 0, 5400, NULL, 2024, 'documentary', NULL, 'PG', 4.60, 8000, 0, 0, 0, 0, 0, 1, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `content_genre`
--

CREATE TABLE `content_genre` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `content_id` bigint(20) UNSIGNED NOT NULL,
  `genre_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_likes`
--

CREATE TABLE `content_likes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `content_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_types`
--

CREATE TABLE `content_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT 'film',
  `color` varchar(255) DEFAULT '#3B82F6',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `content_types`
--

INSERT INTO `content_types` (`id`, `name`, `slug`, `description`, `icon`, `color`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Movie', 'movie', 'Feature films and movies', 'film', '#EF4444', 0, 1, '2026-01-12 08:01:33', '2026-01-12 09:15:18'),
(2, 'Series', 'series', 'TV series and shows', 'tv', '#3B82F6', 1, 2, '2026-01-12 08:01:33', '2026-01-12 08:01:33'),
(3, 'Documentary', 'documentary', 'Documentary films and content', 'documentary', '#10B981', 1, 3, '2026-01-12 08:01:33', '2026-01-12 08:01:33'),
(4, 'Anime', 'anime', 'Anime series and movies', 'anime', '#8B5CF6', 1, 4, '2026-01-12 08:01:33', '2026-01-12 08:01:33'),
(5, 'Live TV', 'live', 'Live streaming content', 'live', '#F59E0B', 1, 5, '2026-01-12 08:01:33', '2026-01-12 08:01:33');

-- --------------------------------------------------------

--
-- Table structure for table `email_verification_tokens`
--

CREATE TABLE `email_verification_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `episodes`
--

CREATE TABLE `episodes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `content_id` bigint(20) UNSIGNED NOT NULL,
  `season_number` int(11) NOT NULL DEFAULT 1,
  `episode_number` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `is_premium` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `content_id` bigint(20) UNSIGNED DEFAULT NULL,
  `episode_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

CREATE TABLE `genres` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `genres`
--

INSERT INTO `genres` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Action', 'action', NULL, '2026-01-11 16:49:18', '2026-01-11 16:49:18'),
(2, 'Comedy', 'comedy', NULL, '2026-01-11 16:49:18', '2026-01-11 16:49:18'),
(3, 'Drama', 'drama', NULL, '2026-01-11 16:49:18', '2026-01-11 16:49:18'),
(4, 'Horror', 'horror', NULL, '2026-01-11 16:49:18', '2026-01-11 16:49:18'),
(6, 'Sci-Fi', 'sci-fi', NULL, '2026-01-11 16:49:18', '2026-01-11 16:49:18'),
(7, 'Documentary', 'documentary', NULL, '2026-01-11 16:49:18', '2026-01-11 16:49:18'),
(8, 'Anime', 'anime', NULL, '2026-01-11 16:49:18', '2026-01-11 16:49:18'),
(9, 'Web Technology', 'web-technology', NULL, '2026-01-12 06:53:50', '2026-01-12 06:53:50'),
(16, 'Romance', 'romance', 'Romantic relationships and love stories', NULL, NULL),
(17, 'Thriller', 'thriller', 'Suspenseful and exciting narratives', NULL, NULL),
(19, 'Animation', 'animation', 'Animated content', NULL, NULL),
(20, 'Fantasy', 'fantasy', 'Magical and mythical elements', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(6, '0001_01_01_000000_create_users_table', 1),
(7, '0001_01_01_000001_create_cache_table', 1),
(8, '0001_01_01_000002_create_jobs_table', 1),
(9, '2026_01_11_143157_create_complete_database_schema', 1),
(10, '2024_10_11_000001_create_personal_access_tokens_table', 2),
(11, '2026_01_12_120000_create_content_types_table', 3),
(12, '2026_01_12_125212_rename_watch_history_table', 4),
(13, '2026_01_12_125554_add_is_active_to_users_table', 5);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('new_content','subscription','system','promotion') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`abilities`)),
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 1, 'auth_token', '6e78d216a6387272d73762edb054ea7b8b9b5dee31a448ed896f2b2105be962f', '[\"*\"]', NULL, NULL, '2026-01-11 20:43:06', '2026-01-11 20:43:06'),
(2, 'App\\Models\\User', 2, 'auth_token', '2529083f5f30795f6dc11e78bd5834eca6c1d3a5302f34972735219c33194ff6', '[\"*\"]', NULL, NULL, '2026-01-11 20:43:59', '2026-01-11 20:43:59'),
(3, 'App\\Models\\User', 1, 'auth_token', '888f42afaccca74189430e72e6323312a48c975a6d7eddf7769f4004d5e8aee8', '[\"*\"]', NULL, NULL, '2026-01-11 21:10:54', '2026-01-11 21:10:54'),
(4, 'App\\Models\\User', 1, 'auth_token', '4f2dec5510806bbd467960a3327d1da0856e5884a1d181fa247d6c2df2340f43', '[\"*\"]', NULL, NULL, '2026-01-11 21:23:26', '2026-01-11 21:23:26'),
(5, 'App\\Models\\User', 1, 'auth_token', '899e0ba40dcfd6e4d12b90434dc98828dd27613bbf8aa31aeb80a16b10adeada', '[\"*\"]', NULL, NULL, '2026-01-11 21:27:05', '2026-01-11 21:27:05'),
(6, 'App\\Models\\User', 1, 'auth_token', '39798511e6609578889ca8b12c98a9049015f8158e4b50b5dd93705411c6040d', '[\"*\"]', NULL, NULL, '2026-01-11 21:27:59', '2026-01-11 21:27:59'),
(7, 'App\\Models\\User', 1, 'auth_token', '3250abac7e5477e2494108ee1a5a144378322350648f6ee5decea3ca9d8ccae6', '[\"*\"]', NULL, NULL, '2026-01-11 21:31:26', '2026-01-11 21:31:26'),
(8, 'App\\Models\\User', 1, 'auth_token', '9848f086d6b91e5a9ea85faadd59b02ffe55dd0e5e0f5c22e3a29f03fc8cf3f2', '[\"*\"]', NULL, NULL, '2026-01-11 21:33:59', '2026-01-11 21:33:59'),
(9, 'App\\Models\\User', 1, 'auth_token', '2a73087c5e43867f8ccd0a2fddb5beb1b375cd0499416c47bab385ab96db0fe1', '[\"*\"]', NULL, NULL, '2026-01-11 21:39:47', '2026-01-11 21:39:47'),
(10, 'App\\Models\\User', 1, 'auth_token', 'eec9f94f11202e0f094e9246fb499333c66fbf653548704ed6bc749dba6a2d34', '[\"*\"]', NULL, NULL, '2026-01-11 21:39:59', '2026-01-11 21:39:59'),
(11, 'App\\Models\\User', 1, 'auth_token', '2017d07305e5d0425ebac4fbe191f02da908a32b00bad74088d37cee99cde721', '[\"*\"]', NULL, NULL, '2026-01-12 10:53:19', '2026-01-12 10:53:19'),
(12, 'App\\Models\\User', 1, 'auth_token', 'c5601cc473352c3cef98858d37c794125d5b7ab4bb968f835201aefe938b36c1', '[\"*\"]', NULL, NULL, '2026-01-12 13:40:51', '2026-01-12 13:40:51');

-- --------------------------------------------------------

--
-- Table structure for table `playlists`
--

CREATE TABLE `playlists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `playlist_items`
--

CREATE TABLE `playlist_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `playlist_id` bigint(20) UNSIGNED NOT NULL,
  `content_id` bigint(20) UNSIGNED DEFAULT NULL,
  `episode_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `content_id` bigint(20) UNSIGNED NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `review` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('MfhMMaATnT9lfNgOQ3SgwRrBE68jWJyMwTZToR9n', NULL, '127.0.0.1', 'curl/8.5.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMGNteGVJVHp3MHVRSmVjNjFqWjI4cWZCYmI3SHlXeXEzSTZPZUwweCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1768233575),
('pQ9sgyP09jtLVfNdvbmD9lOChqGvVM6tUxLIquQn', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNmZqbVFsS3JiTlExYlY3ZEZvWDNzRk1QWWJuQXU5Yk1qdnRpelpKViI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9fQ==', 1768222671);

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('free','basic','premium','family') NOT NULL DEFAULT 'free',
  `status` enum('active','canceled','expired','pending') NOT NULL DEFAULT 'pending',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `user_id`, `plan_id`, `type`, `status`, `starts_at`, `ends_at`, `trial_ends_at`, `created_at`, `updated_at`) VALUES
(5, 13, NULL, 'free', 'active', '2026-01-14 22:19:59', '2026-02-13 22:19:59', NULL, '2026-01-14 22:19:59', '2026-01-14 22:19:59'),
(7, 19, NULL, 'free', 'active', '2026-01-21 21:07:17', '2026-02-20 21:07:17', NULL, '2026-01-21 21:07:17', '2026-01-21 21:07:17');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(8,2) NOT NULL,
  `interval` enum('monthly','yearly') NOT NULL DEFAULT 'monthly',
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `max_devices` int(11) NOT NULL DEFAULT 1,
  `video_quality` enum('SD','HD','FHD','4K') NOT NULL DEFAULT 'HD',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `name`, `slug`, `description`, `price`, `interval`, `features`, `max_devices`, `video_quality`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Free', 'free', 'Basic access with ads', 0.00, 'monthly', '\"[\\\"SD Quality\\\",\\\"Limited Content\\\",\\\"With Ads\\\",\\\"1 Device\\\"]\"', 1, 'SD', 1, '2026-01-11 16:49:18', '2026-01-11 16:49:18'),
(2, 'Basic', 'basic', 'Standard streaming experience', 9.99, 'monthly', '\"[\\\"HD Quality\\\",\\\"Full Content Library\\\",\\\"No Ads\\\",\\\"2 Devices\\\"]\"', 2, 'HD', 1, '2026-01-11 16:49:18', '2026-01-11 16:49:18'),
(3, 'Premium', 'premium', 'Best streaming experience', 14.99, 'monthly', '\"[\\\"4K Quality\\\",\\\"Full Content + Premium\\\",\\\"No Ads\\\",\\\"4 Devices\\\",\\\"Offline Downloads\\\"]\"', 4, '4K', 1, '2026-01-11 16:49:18', '2026-01-11 16:49:18'),
(4, 'Free', '', 'Basic free access', 0.00, 'monthly', NULL, 1, 'HD', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `banner_url` varchar(255) DEFAULT NULL,
  `subscription_plan` varchar(50) DEFAULT 'free',
  `role` varchar(255) NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `preferences` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `username`, `avatar_url`, `banner_url`, `subscription_plan`, `role`, `is_active`, `email_verified_at`, `password`, `avatar`, `remember_token`, `created_at`, `updated_at`, `bio`, `location`, `website`, `date_of_birth`, `preferences`) VALUES
(1, 'Updated Display Name', 'saimalyethomas@gmail.com', 'newusername', 'uploads/avatars/new_upload.png', 'uploads/banners/banner_1_1769031998.png', 'free', 'admin', 1, NULL, '$2y$12$eS7nX5.c6Dsv2lOcj7TBn.imv2Bt47s6czgBYj66Mfzzm1G9upNPq', NULL, NULL, '2026-01-11 16:49:17', '2026-01-21 22:08:51', NULL, NULL, NULL, NULL, NULL),
(2, 'Jumanne Mashaka', 'j4@gmail.com', NULL, NULL, NULL, 'free', 'user', 1, NULL, '$2y$12$aI4rH3azZ49w5t7i3wiODuC/lQbjfe1/h7wzyqjQyv0UzCppMhhEa', NULL, NULL, '2026-01-11 16:49:18', '2026-01-12 09:57:11', NULL, NULL, NULL, NULL, NULL),
(13, 'Facebook User', 'fb.user1768429199@facebook.com', NULL, NULL, NULL, 'free', 'user', 1, NULL, '$2y$10$FlMOISN8JyeAbC7hT33C6OLcp4dJZ65zCrUC4bvooEKvHInnkMS7m', NULL, NULL, '2026-01-14 22:19:59', '2026-01-14 22:19:59', NULL, NULL, NULL, NULL, NULL),
(14, 'Chichy Saimalye', 'saimalye@gmail.com', NULL, NULL, NULL, 'free', 'user', 1, NULL, '$2y$10$T4Xzz/PNy/ZylHS6M0fqje0yKb9IhhLDYNemMiHVN9xn4t9DmP/R2', NULL, NULL, '2026-01-17 22:00:47', '2026-01-17 22:03:19', NULL, NULL, NULL, NULL, NULL),
(15, 'Teo Majiyamoto', 'teo@gmail.com', 'tectec', NULL, NULL, 'free', 'user', 1, NULL, '$2y$10$fCGFUpO7fyMGOa2ZnHZ00OW8XJ2ggoiDB8fxj4BGVm9hNtqWuoy7W', NULL, NULL, '2026-01-17 22:10:02', '2026-01-22 06:47:16', 'mrtec', NULL, NULL, NULL, NULL),
(18, 'John Doe', 'john@example.com', NULL, NULL, NULL, 'free', 'user', 1, NULL, '$2y$10$XWoszfdTestDE7RuHxl5QO6HSxrZRkybgMA6fzthJlik3ERZvAB46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'Google User', 'google.user1769029637@gmail.com', NULL, NULL, NULL, 'free', 'user', 1, NULL, '$2y$10$iwuSXsSAGBaG55WDRr/d..8kX2nOQPtOhmHxTpSCJMSCvSE3scFhC', NULL, NULL, '2026-01-21 21:07:17', '2026-01-21 21:11:59', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `watch_histories`
--

CREATE TABLE `watch_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `content_id` bigint(20) UNSIGNED DEFAULT NULL,
  `episode_id` bigint(20) UNSIGNED DEFAULT NULL,
  `progress` int(11) NOT NULL DEFAULT 0 COMMENT 'in seconds',
  `duration` int(11) NOT NULL DEFAULT 0 COMMENT 'in seconds',
  `watched_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `watch_history`
--

CREATE TABLE `watch_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content_id` int(11) DEFAULT NULL,
  `episode_id` int(11) DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `completed` tinyint(1) DEFAULT 0,
  `last_watched_at` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `watch_later`
--

CREATE TABLE `watch_later` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `content_id` bigint(20) UNSIGNED DEFAULT NULL,
  `episode_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `channels`
--
ALTER TABLE `channels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `channels_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `channels_slug_unique` (`slug`);

--
-- Indexes for table `channel_subscriptions`
--
ALTER TABLE `channel_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `channel_subscriptions_user_id_channel_id_unique` (`user_id`,`channel_id`),
  ADD KEY `channel_subscriptions_channel_id_foreign` (`channel_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comments_user_id_foreign` (`user_id`),
  ADD KEY `comments_content_id_foreign` (`content_id`),
  ADD KEY `comments_episode_id_foreign` (`episode_id`),
  ADD KEY `comments_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `comment_likes_user_id_comment_id_unique` (`user_id`,`comment_id`),
  ADD KEY `comment_likes_comment_id_foreign` (`comment_id`);

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_slug_unique` (`slug`),
  ADD KEY `content_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `content_content_type_id_foreign` (`content_type_id`);

--
-- Indexes for table `content_genre`
--
ALTER TABLE `content_genre`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_genre_content_id_foreign` (`content_id`),
  ADD KEY `content_genre_genre_id_foreign` (`genre_id`);

--
-- Indexes for table `content_likes`
--
ALTER TABLE `content_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`content_id`,`user_id`),
  ADD KEY `idx_content_id` (`content_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `content_types`
--
ALTER TABLE `content_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_types_slug_unique` (`slug`);

--
-- Indexes for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_verification_tokens_user_id_foreign` (`user_id`);

--
-- Indexes for table `episodes`
--
ALTER TABLE `episodes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `episodes_content_id_foreign` (`content_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `favorites_user_id_foreign` (`user_id`),
  ADD KEY `favorites_content_id_foreign` (`content_id`),
  ADD KEY `favorites_episode_id_foreign` (`episode_id`);

--
-- Indexes for table `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `genres_slug_unique` (`slug`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_foreign` (`user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `playlists`
--
ALTER TABLE `playlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `playlists_user_id_foreign` (`user_id`);

--
-- Indexes for table `playlist_items`
--
ALTER TABLE `playlist_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `playlist_items_playlist_id_foreign` (`playlist_id`),
  ADD KEY `playlist_items_content_id_foreign` (`content_id`),
  ADD KEY `playlist_items_episode_id_foreign` (`episode_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ratings_user_id_content_id_unique` (`user_id`,`content_id`),
  ADD KEY `ratings_content_id_foreign` (`content_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscriptions_user_id_foreign` (`user_id`),
  ADD KEY `subscriptions_plan_id_foreign` (`plan_id`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subscription_plans_slug_unique` (`slug`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `watch_histories`
--
ALTER TABLE `watch_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `watch_history_user_id_foreign` (`user_id`),
  ADD KEY `watch_history_content_id_foreign` (`content_id`),
  ADD KEY `watch_history_episode_id_foreign` (`episode_id`);

--
-- Indexes for table `watch_history`
--
ALTER TABLE `watch_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `watch_later`
--
ALTER TABLE `watch_later`
  ADD PRIMARY KEY (`id`),
  ADD KEY `watch_later_user_id_foreign` (`user_id`),
  ADD KEY `watch_later_content_id_foreign` (`content_id`),
  ADD KEY `watch_later_episode_id_foreign` (`episode_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `channels`
--
ALTER TABLE `channels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `channel_subscriptions`
--
ALTER TABLE `channel_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comment_likes`
--
ALTER TABLE `comment_likes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `content_genre`
--
ALTER TABLE `content_genre`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `content_likes`
--
ALTER TABLE `content_likes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `content_types`
--
ALTER TABLE `content_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `episodes`
--
ALTER TABLE `episodes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `genres`
--
ALTER TABLE `genres`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `playlists`
--
ALTER TABLE `playlists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `playlist_items`
--
ALTER TABLE `playlist_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `watch_histories`
--
ALTER TABLE `watch_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `watch_history`
--
ALTER TABLE `watch_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `watch_later`
--
ALTER TABLE `watch_later`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `channels`
--
ALTER TABLE `channels`
  ADD CONSTRAINT `channels_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `channel_subscriptions`
--
ALTER TABLE `channel_subscriptions`
  ADD CONSTRAINT `channel_subscriptions_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `channel_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_content_id_foreign` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `episodes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD CONSTRAINT `comment_likes_comment_id_foreign` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content`
--
ALTER TABLE `content`
  ADD CONSTRAINT `content_content_type_id_foreign` FOREIGN KEY (`content_type_id`) REFERENCES `content_types` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `content_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `content_genre`
--
ALTER TABLE `content_genre`
  ADD CONSTRAINT `content_genre_content_id_foreign` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_genre_genre_id_foreign` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `episodes`
--
ALTER TABLE `episodes`
  ADD CONSTRAINT `episodes_content_id_foreign` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_content_id_foreign` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `episodes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `playlists`
--
ALTER TABLE `playlists`
  ADD CONSTRAINT `playlists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `playlist_items`
--
ALTER TABLE `playlist_items`
  ADD CONSTRAINT `playlist_items_content_id_foreign` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `playlist_items_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `episodes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `playlist_items_playlist_id_foreign` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_content_id_foreign` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `watch_histories`
--
ALTER TABLE `watch_histories`
  ADD CONSTRAINT `watch_history_content_id_foreign` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `watch_history_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `episodes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `watch_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `watch_later`
--
ALTER TABLE `watch_later`
  ADD CONSTRAINT `watch_later_content_id_foreign` FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `watch_later_episode_id_foreign` FOREIGN KEY (`episode_id`) REFERENCES `episodes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `watch_later_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;