<?php
/**
 * Migration: Create companies table
 * 
 * This migration adds support for content provider companies
 * that can upload and manage movies/series on the platform.
 */

require_once dirname(__DIR__, 2) . '/config/connection.php';

function migrate_up() {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Create companies table
    $sql = "
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    try {
        $pdo->exec($sql);
        echo "✓ Companies table created successfully\n";
        
        // Create company_users table for staff management
        $sql2 = "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($sql2);
        echo "✓ Company_users table created successfully\n";
        
        // Create content_applications table for new company signups
        $sql3 = "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($sql3);
        echo "✓ Content_applications table created successfully\n";
        
        // Add user_type column to users table if not exists
        $sql4 = "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `user_type` enum('individual','company') DEFAULT 'individual' AFTER `role`";
        try {
            $pdo->exec($sql4);
            echo "✓ User_type column added to users table\n";
        } catch (Exception $e) {
            echo "Note: user_type column may already exist\n";
        }
        
        // Add company_id column to content table if not exists
        $sql5 = "ALTER TABLE `content` ADD COLUMN IF NOT EXISTS `company_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `channel_id`";
        try {
            $pdo->exec($sql5);
            echo "✓ Company_id column added to content table\n";
            
            // Add foreign key
            $sql5fk = "ALTER TABLE `content` ADD CONSTRAINT `content_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL";
            $pdo->exec($sql5fk);
            echo "✓ Company_id foreign key added\n";
        } catch (Exception $e) {
            echo "Note: company_id column may already exist\n";
        }
        
        echo "\n✓ All company tables created successfully!\n";
        return true;
        
    } catch (Exception $e) {
        echo "✗ Migration failed: " . $e->getMessage() . "\n";
        return false;
    }
}

function migrate_down() {
    $db = new Database();
    $pdo = $db->getConnection();
    
    try {
        // Drop foreign keys first
        $pdo->exec("ALTER TABLE `content` DROP FOREIGN KEY IF EXISTS `content_company_id_foreign`");
        $pdo->exec("ALTER TABLE `company_users` DROP FOREIGN KEY IF EXISTS `company_users_company_id_foreign`");
        $pdo->exec("ALTER TABLE `company_users` DROP FOREIGN KEY IF EXISTS `company_users_user_id_foreign`");
        $pdo->exec("ALTER TABLE `content_applications` DROP FOREIGN KEY IF EXISTS `content_applications_company_id_foreign`");
        
        // Drop tables
        $pdo->exec("DROP TABLE IF EXISTS `content_applications`");
        $pdo->exec("DROP TABLE IF EXISTS `company_users`");
        $pdo->exec("DROP TABLE IF EXISTS `companies`");
        
        // Drop columns
        $pdo->exec("ALTER TABLE `content` DROP COLUMN IF EXISTS `company_id`");
        $pdo->exec("ALTER TABLE `users` DROP COLUMN IF EXISTS `user_type`");
        
        echo "✓ Migration rolled back successfully\n";
        return true;
        
    } catch (Exception $e) {
        echo "✗ Rollback failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run migration
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $action = $argv[1] ?? 'up';
    
    if ($action === 'up') {
        migrate_up();
    } else {
        migrate_down();
    }
}

