-- Migration: Create studios, distributors, producers, networks tables
-- Run this SQL to add entity tables for movie production details

USE KagomaEnter;

-- Create studios table
CREATE TABLE IF NOT EXISTS `studios` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `logo` varchar(255) DEFAULT NULL,
    `website` varchar(255) DEFAULT NULL,
    `country` varchar(100) DEFAULT NULL,
    `founded_year` int(11) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `studios_slug_unique` (`slug`),
    UNIQUE KEY `studios_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create distributors table
CREATE TABLE IF NOT EXISTS `distributors` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `logo` varchar(255) DEFAULT NULL,
    `website` varchar(255) DEFAULT NULL,
    `country` varchar(100) DEFAULT NULL,
    `founded_year` int(11) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `distributors_slug_unique` (`slug`),
    UNIQUE KEY `distributors_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create producers table
CREATE TABLE IF NOT EXISTS `producers` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `logo` varchar(255) DEFAULT NULL,
    `website` varchar(255) DEFAULT NULL,
    `country` varchar(100) DEFAULT NULL,
    `founded_year` int(11) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `producers_slug_unique` (`slug`),
    UNIQUE KEY `producers_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create networks table
CREATE TABLE IF NOT EXISTS `networks` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `logo` varchar(255) DEFAULT NULL,
    `website` varchar(255) DEFAULT NULL,
    `country` varchar(100) DEFAULT NULL,
    `founded_year` int(11) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `networks_slug_unique` (`slug`),
    UNIQUE KEY `networks_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add entity columns to content table
ALTER TABLE `content` ADD COLUMN IF NOT EXISTS `studio_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `company_id`;
ALTER TABLE `content` ADD COLUMN IF NOT EXISTS `distributor_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `studio_id`;
ALTER TABLE `content` ADD COLUMN IF NOT EXISTS `producer_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `distributor_id`;
ALTER TABLE `content` ADD COLUMN IF NOT EXISTS `network_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `producer_id`;

-- Add foreign key constraints
ALTER TABLE `content` ADD CONSTRAINT IF NOT EXISTS `content_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `studios` (`id`) ON DELETE SET NULL;
ALTER TABLE `content` ADD CONSTRAINT IF NOT EXISTS `content_distributor_id_foreign` FOREIGN KEY (`distributor_id`) REFERENCES `distributors` (`id`) ON DELETE SET NULL;
ALTER TABLE `content` ADD CONSTRAINT IF NOT EXISTS `content_producer_id_foreign` FOREIGN KEY (`producer_id`) REFERENCES `producers` (`id`) ON DELETE SET NULL;
ALTER TABLE `content` ADD CONSTRAINT IF NOT EXISTS `content_network_id_foreign` FOREIGN KEY (`network_id`) REFERENCES `networks` (`id`) ON DELETE SET NULL;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_content_studio_id` ON `content` (`studio_id`);
CREATE INDEX IF NOT EXISTS `idx_content_distributor_id` ON `content` (`distributor_id`);
CREATE INDEX IF NOT EXISTS `idx_content_producer_id` ON `content` (`producer_id`);
CREATE INDEX IF NOT EXISTS `idx_content_network_id` ON `content` (`network_id`);

SELECT '✓ Entity tables created successfully!' AS status;
