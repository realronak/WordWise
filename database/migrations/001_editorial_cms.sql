-- ==============================================================================
-- WordWise Editorial CMS Database Migration
-- Version: 001
-- Safe, non-destructive migration preserving existing articles and categories
-- ==============================================================================

USE `wordwise`;

-- 1. Enhance existing `categories` table
ALTER TABLE `categories` 
  ADD COLUMN IF NOT EXISTS `cat_slug` VARCHAR(255) NULL AFTER `cat_title`,
  ADD COLUMN IF NOT EXISTS `cat_description` TEXT NULL AFTER `cat_slug`;

-- Update slugs for existing categories
UPDATE `categories` SET `cat_slug` = LOWER(REPLACE(REPLACE(TRIM(`cat_title`), ' ', '-'), '&', 'and')) WHERE `cat_slug` IS NULL OR `cat_slug` = '';
ALTER TABLE `categories` MODIFY `cat_slug` VARCHAR(255) NOT NULL UNIQUE;

-- 2. Enhance existing `posts` table
ALTER TABLE `posts`
  ADD COLUMN IF NOT EXISTS `post_slug` VARCHAR(255) NULL AFTER `post_title`,
  ADD COLUMN IF NOT EXISTS `post_subtitle` VARCHAR(255) NULL AFTER `post_slug`,
  ADD COLUMN IF NOT EXISTS `post_views` INT NOT NULL DEFAULT 0 AFTER `post_comment_count`,
  ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `post_views`,
  ADD COLUMN IF NOT EXISTS `is_pinned` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_featured`,
  ADD COLUMN IF NOT EXISTS `scheduled_at` DATETIME NULL AFTER `is_pinned`,
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `scheduled_at`,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Populate slugs for existing posts
UPDATE `posts` SET `post_slug` = CONCAT('welcome-to-wordwise-on-omarchy-linux-', `post_id`) WHERE `post_id` = 1 AND (`post_slug` IS NULL OR `post_slug` = '');
UPDATE `posts` SET `post_slug` = CONCAT('next-generation-ai-assisted-programming-', `post_id`) WHERE `post_id` = 2 AND (`post_slug` IS NULL OR `post_slug` = '');
UPDATE `posts` SET `post_slug` = CONCAT('mastering-javascript-in-2026-', `post_id`) WHERE `post_id` = 3 AND (`post_slug` IS NULL OR `post_slug` = '');
-- Fallback for any other posts
UPDATE `posts` SET `post_slug` = CONCAT('article-', `post_id`) WHERE `post_slug` IS NULL OR `post_slug` = '';

ALTER TABLE `posts` MODIFY `post_slug` VARCHAR(255) NOT NULL UNIQUE;

-- Mark first post as featured
UPDATE `posts` SET `is_featured` = 1 WHERE `post_id` = 1;

-- 3. Create `users` table for secure authentication
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `user_role` ENUM('admin', 'editor', 'author', 'reader') NOT NULL DEFAULT 'author',
  `user_image` VARCHAR(255) NULL DEFAULT 'images/favicon.png',
  `bio` TEXT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial administrator account (admin / WordWise@2026!)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `user_role`, `bio`, `status`)
VALUES (
  'admin',
  'admin@wordwise.local',
  '$2y$12$c1u7bHXQDms0tJCxCnvnwemlwiGznd9l87Dh3lU6Kxy6Tbe7oBNt6',
  'Ronak Rathod',
  'admin',
  'Founder and Lead Editor at WordWise publication.',
  'active'
) ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- 4. Create `tags` and `post_tags` tables
CREATE TABLE IF NOT EXISTS `tags` (
  `tag_id` INT AUTO_INCREMENT PRIMARY KEY,
  `tag_name` VARCHAR(100) NOT NULL UNIQUE,
  `tag_slug` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_tags` (
  `post_id` INT NOT NULL,
  `tag_id` INT NOT NULL,
  PRIMARY KEY (`post_id`, `tag_id`),
  FOREIGN KEY (`post_id`) REFERENCES `posts`(`post_id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags`(`tag_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed initial tags
INSERT IGNORE INTO `tags` (`tag_name`, `tag_slug`) VALUES
('Architecture', 'architecture'),
('Linux', 'linux'),
('PHP', 'php'),
('Artificial Intelligence', 'artificial-intelligence'),
('JavaScript', 'javascript'),
('Minimalism', 'minimalism'),
('Typography', 'typography');

-- Link sample posts to initial tags
INSERT IGNORE INTO `post_tags` (`post_id`, `tag_id`)
SELECT 1, tag_id FROM `tags` WHERE `tag_slug` IN ('php', 'linux', 'architecture');
INSERT IGNORE INTO `post_tags` (`post_id`, `tag_id`)
SELECT 2, tag_id FROM `tags` WHERE `tag_slug` IN ('artificial-intelligence', 'architecture');
INSERT IGNORE INTO `post_tags` (`post_id`, `tag_id`)
SELECT 3, tag_id FROM `tags` WHERE `tag_slug` IN ('javascript');

-- 5. Create `media` library metadata table
CREATE TABLE IF NOT EXISTS `media` (
  `media_id` INT AUTO_INCREMENT PRIMARY KEY,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(100) NOT NULL,
  `file_size` INT NOT NULL,
  `alt_text` VARCHAR(255) NULL,
  `uploaded_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed existing repository images into media library
INSERT IGNORE INTO `media` (`file_name`, `file_path`, `file_type`, `file_size`, `alt_text`) VALUES
('img01.jpg', 'images/img01.jpg', 'image/jpeg', 96447, 'Editorial desk setup with notebook and typewriter'),
('imgAi02.jpg', 'images/imgAi02.jpg', 'image/jpeg', 43672, 'Neural network visualization and AI synthesis'),
('imgjs03.jpg', 'images/imgjs03.jpg', 'image/jpeg', 88531, 'Modern code editor and developer workspace');

-- 6. Create `settings` table for configurable CMS options
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(100) PRIMARY KEY,
  `setting_value` TEXT NULL,
  `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name', 'WordWise', 'general'),
('site_tagline', 'A minimal editorial journal for tech, ideas, and craft.', 'general'),
('site_description', 'WordWise is an independent, minimalist blogging platform dedicated to thoughtful essays on programming, technology, and design.', 'general'),
('default_theme', 'system', 'appearance'),
('posts_per_page', '6', 'reading'),
('social_twitter', 'https://twitter.com', 'social'),
('social_github', 'https://github.com/realronak/WordWise', 'social'),
('social_linkedin', 'https://linkedin.com', 'social'),
('author_name', 'Ronak Rathod', 'author'),
('author_bio', 'Engineer & writer interested in agentic AI, operating systems, and thoughtful software design.', 'author'),
('allow_registration', '0', 'auth')
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- 7. Create `article_views` table for genuine view tracking
CREATE TABLE IF NOT EXISTS `article_views` (
  `view_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NOT NULL,
  `ip_hash` CHAR(64) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `viewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`post_id`),
  INDEX (`ip_hash`, `post_id`),
  FOREIGN KEY (`post_id`) REFERENCES `posts`(`post_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Create `audit_logs` table for administrative activities
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `log_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial audit log
INSERT INTO `audit_logs` (`user_id`, `action`, `details`, `ip_address`)
VALUES (1, 'system_migration', 'Migrated database schema to WordWise Editorial CMS v1.0', '127.0.0.1');
