-- Migration: Create companies and content provider tables
-- Run this SQL to add company upload functionality to the platform

USE KagomaEnter;

-- Create companies table
CREATE TABLE IF NOT EXISTS `companies` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Associated user account',
    `name` varchar(255) NOT NULL COMMENT 'Company business name',
    `slug` varchar(255) NOT NULL COMMENT 'URL-friendly identifier',
    `email` varchar(255) NOT NULL COMMENT 'Contact email',
    `phone` varchar(50) DEFAULT NULL COMMENT 'Contact phone',
    `website` varchar(255) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `logo` varchar(255) DEFAULT NULL,
    `banner` varchar(255) DEFAULT NULL,
    `tax_id` varchar(100) DEFAULT NULL COMMENT 'Business registration/Tax ID',
    `business_type` enum('studio','distributor','producer','independent','network') DEFAULT 'independent',
    `country` varchar(100) DEFAULT NULL,
    `city` varchar(100) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `verification_status` enum('pending','under_review','verified','rejected') DEFAULT 'pending',
    `verification_notes` text DEFAULT NULL,
    `content_count` int(11) DEFAULT 0 COMMENT 'Number of content items',
    `total_views` bigint(20) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `verified_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `companies_slug_unique` (`slug`),
    UNIQUE KEY `companies_email_unique` (`email`),
    UNIQUE KEY `companies_tax_id_unique` (`tax_id`),
    KEY `companies_user_id_foreign` (`user_id`),
    KEY `companies_verification_status` (`verification_status`),
    CONSTRAINT `companies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create company_users table for staff management
CREATE TABLE IF NOT EXISTS `company_users` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` bigint(20) UNSIGNED NOT NULL,
    `user_id` bigint(20) UNSIGNED NOT NULL,
    `role` enum('owner','admin','editor','viewer') DEFAULT 'editor',
    `permissions` text DEFAULT NULL COMMENT 'JSON permissions array',
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `company_users_company_id_user_id_unique` (`company_id`, `user_id`),
    KEY `company_users_company_id_foreign` (`company_id`),
    KEY `company_users_user_id_foreign` (`user_id`),
    CONSTRAINT `company_users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `company_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create content_applications table for new company signups
CREATE TABLE IF NOT EXISTS `content_applications` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` bigint(20) UNSIGNED DEFAULT NULL,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `phone` varchar(50) DEFAULT NULL,
    `company_name` varchar(255) NOT NULL,
    `tax_id` varchar(100) DEFAULT NULL,
    `business_type` enum('studio','distributor','producer','independent','network') DEFAULT 'independent',
    `country` varchar(100) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `documents` text DEFAULT NULL COMMENT 'JSON array of uploaded document URLs',
    `status` enum('pending','approved','rejected') DEFAULT 'pending',
    `notes` text DEFAULT NULL,
    `submitted_at` timestamp NULL DEFAULT NULL,
    `reviewed_at` timestamp NULL DEFAULT NULL,
    `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `content_applications_company_id_foreign` (`company_id`),
    KEY `content_applications_status` (`status`),
    KEY `content_applications_email` (`email`),
    CONSTRAINT `content_applications_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add user_type column to users table (skip if already exists)
-- ALTER TABLE `users` ADD COLUMN `user_type` enum('individual','company') DEFAULT 'individual' AFTER `role`;

-- Add company_id column to content table (skip if already exists)
-- ALTER TABLE `content` ADD COLUMN `company_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `channel_id`;

-- Add foreign key for company_id (drop first if exists to avoid error)
ALTER TABLE `content` DROP FOREIGN KEY IF EXISTS `content_company_id_foreign`;
ALTER TABLE `content` ADD CONSTRAINT `content_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

-- Create index for faster queries
CREATE INDEX `idx_content_company_id` ON `content` (`company_id`);

-- Create index for company content listing
CREATE INDEX `idx_companies_verification` ON `companies` (`verification_status`, `is_active`);

SELECT 'Company tables created successfully!' AS status;

