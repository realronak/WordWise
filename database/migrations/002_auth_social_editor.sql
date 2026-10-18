-- WordWise Feature Expansion Migration
-- Authentication, Social Providers, Structured Block Content, Revisions, and Autosave

-- 1. Extend users table for email verification, password reset, and remember-me tokens
ALTER TABLE `users`
  ADD COLUMN `email_verified_at` TIMESTAMP NULL AFTER `email`,
  ADD COLUMN `verification_token` VARCHAR(100) NULL AFTER `email_verified_at`,
  ADD COLUMN `password_reset_token` VARCHAR(100) NULL AFTER `verification_token`,
  ADD COLUMN `password_reset_expires_at` TIMESTAMP NULL AFTER `password_reset_token`,
  ADD COLUMN `remember_token` VARCHAR(100) NULL AFTER `password_reset_expires_at`,
  ADD INDEX `idx_verification_token` (`verification_token`),
  ADD INDEX `idx_reset_token` (`password_reset_token`),
  ADD INDEX `idx_remember_token` (`remember_token`);

-- Mark existing admin as verified
UPDATE `users` SET `email_verified_at` = NOW() WHERE `username` = 'admin';

-- 2. External OAuth providers table (for Google and future providers)
CREATE TABLE IF NOT EXISTS `user_providers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `provider` VARCHAR(50) NOT NULL,
  `provider_user_id` VARCHAR(255) NOT NULL,
  `provider_email` VARCHAR(100) NULL,
  `avatar_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_provider_uid` (`provider`, `provider_user_id`),
  UNIQUE KEY `uk_user_provider` (`user_id`, `provider`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Extend posts table for structured block JSON, excerpts, SEO, and optimistic locking
ALTER TABLE `posts`
  ADD COLUMN `post_content_json` LONGTEXT NULL AFTER `post_content`,
  ADD COLUMN `post_excerpt` TEXT NULL AFTER `post_subtitle`,
  ADD COLUMN `post_seo_title` VARCHAR(255) NULL AFTER `post_tags`,
  ADD COLUMN `post_seo_desc` TEXT NULL AFTER `post_seo_title`,
  ADD COLUMN `post_version` INT NOT NULL DEFAULT 1 AFTER `scheduled_at`;

-- 4. Revisions table for audit history & rollback
CREATE TABLE IF NOT EXISTS `post_revisions` (
  `revision_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NOT NULL,
  `user_id` INT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content_json` LONGTEXT NULL,
  `content_html` LONGTEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`post_id`) REFERENCES `posts`(`post_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Autosaves table for live distraction-free drafting
CREATE TABLE IF NOT EXISTS `post_autosaves` (
  `autosave_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NULL,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content_json` LONGTEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_user_post_autosave` (`user_id`, `post_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
