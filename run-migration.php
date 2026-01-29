<?php
/**
 * Standalone Migration Runner
 * Run this file to create company tables
 */

$host = 'localhost';
$dbname = 'KagomaEnter';
$user = 'root';
$pass = '';
$socket = '/opt/lampp/var/mysql/mysql.sock';

try {
    $pdo = new PDO("mysql:host=$host;unix_socket=$socket;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database\n\n";
    
    // Create companies table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `companies` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` bigint(20) UNSIGNED DEFAULT NULL,
        `name` varchar(255) NOT NULL,
        `slug` varchar(255) NOT NULL,
        `email` varchar(255) NOT NULL,
        `phone` varchar(50) DEFAULT NULL,
        `website` varchar(255) DEFAULT NULL,
        `description` text DEFAULT NULL,
        `logo` varchar(255) DEFAULT NULL,
        `banner` varchar(255) DEFAULT NULL,
        `tax_id` varchar(100) DEFAULT NULL,
        `business_type` enum('studio','distributor','producer','independent','network') DEFAULT 'independent',
        `country` varchar(100) DEFAULT NULL,
        `city` varchar(100) DEFAULT NULL,
        `address` text DEFAULT NULL,
        `verification_status` enum('pending','under_review','verified','rejected') DEFAULT 'pending',
        `verification_notes` text DEFAULT NULL,
        `content_count` int(11) DEFAULT 0,
        `total_views` bigint(20) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp NULL DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL,
        `verified_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `companies_slug_unique` (`slug`),
        UNIQUE KEY `companies_email_unique` (`email`),
        KEY `companies_user_id_foreign` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Companies table created\n";
    
    // Create company_users table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `company_users` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `company_id` bigint(20) UNSIGNED NOT NULL,
        `user_id` bigint(20) UNSIGNED NOT NULL,
        `role` enum('owner','admin','editor','viewer') DEFAULT 'editor',
        `permissions` text DEFAULT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp NULL DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `company_users_company_id_user_id_unique` (`company_id`, `user_id`),
        KEY `company_users_company_id_foreign` (`company_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Company_users table created\n";
    
    // Create content_applications table
    $pdo->exec("
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
        `documents` text DEFAULT NULL,
        `status` enum('pending','approved','rejected') DEFAULT 'pending',
        `notes` text DEFAULT NULL,
        `submitted_at` timestamp NULL DEFAULT NULL,
        `reviewed_at` timestamp NULL DEFAULT NULL,
        `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `content_applications_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Content_applications table created\n";
    
    // Add user_type column
    try {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `user_type` enum('individual','company') DEFAULT 'individual'");
        echo "✓ user_type column added to users\n";
    } catch (PDOException $e) {
        echo "ℹ user_type column already exists\n";
    }
    
    // Add company_id column to content
    try {
        $pdo->exec("ALTER TABLE `content` ADD COLUMN `company_id` bigint(20) UNSIGNED DEFAULT NULL");
        echo "✓ company_id column added to content\n";
    } catch (PDOException $e) {
        echo "ℹ company_id column already exists\n";
    }
    
    echo "\n✅ All company tables created successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

