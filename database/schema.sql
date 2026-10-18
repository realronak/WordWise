/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.20-12.3.3-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: wordwise
-- ------------------------------------------------------
-- Server version	12.3.3-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `article_views`
--

DROP TABLE IF EXISTS `article_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `article_views` (
  `view_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `ip_hash` char(64) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `viewed_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`view_id`),
  KEY `post_id` (`post_id`),
  KEY `ip_hash` (`ip_hash`,`post_id`),
  CONSTRAINT `1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `article_views`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `article_views` WRITE;
/*!40000 ALTER TABLE `article_views` DISABLE KEYS */;
INSERT INTO `article_views` VALUES
(1,1,'d4870b1dadc09ef5e3a572667f9f1e6701e61408a32b450529421d5058f7e368','Python-urllib/3.14','2026-10-17 17:25:28'),
(2,4,'d4870b1dadc09ef5e3a572667f9f1e6701e61408a32b450529421d5058f7e368','Python-urllib/3.14','2026-10-17 17:31:41'),
(3,1,'9a047c5e0495bf0458a80c3d3ec0fb9b52bf3d4c45e0befd94154cb92bf9da4f','Mozilla/5.0 (X11; Linux x86_64; rv:157.0) Gecko/20100101 Firefox/157.0','2026-10-17 17:55:01'),
(4,2,'610d981fc00f5feaf71864f1967d89b48888e55b43060033edba39aa63002a2d','Mozilla/5.0 (X11; Linux x86_64; rv:157.0) Gecko/20100101 Firefox/157.0','2026-10-18 07:16:57'),
(5,4,'75775d1cef61d4930c9710534c0f3eea6a5ac343ee482b82ee304915a3bd1f5f','curl/8.22.0','2026-10-18 07:53:48'),
(6,14,'75775d1cef61d4930c9710534c0f3eea6a5ac343ee482b82ee304915a3bd1f5f','curl/8.22.0','2026-10-18 08:15:18'),
(7,1,'610d981fc00f5feaf71864f1967d89b48888e55b43060033edba39aa63002a2d','Mozilla/5.0 (X11; Linux x86_64; rv:157.0) Gecko/20100101 Firefox/157.0','2026-10-18 08:16:16'),
(8,14,'610d981fc00f5feaf71864f1967d89b48888e55b43060033edba39aa63002a2d','Mozilla/5.0 (X11; Linux x86_64; rv:157.0) Gecko/20100101 Firefox/157.0','2026-10-18 08:16:22');
/*!40000 ALTER TABLE `article_views` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES
(1,1,'system_migration','Migrated database schema to WordWise Editorial CMS v1.0','127.0.0.1','2026-10-17 17:18:36'),
(2,1,'user_login','Successful login','127.0.0.1','2026-10-17 17:19:53'),
(3,1,'user_login','Successful login','::1','2026-10-17 17:29:58'),
(4,1,'user_login','Successful login','::1','2026-10-17 17:30:24'),
(5,1,'user_login','Successful login','::1','2026-10-17 17:30:36'),
(6,1,'user_login','Successful login','::1','2026-10-17 17:31:41'),
(7,1,'create_post','Created post #4 \'The Art of Typewriter Web Design\'','::1','2026-10-17 17:31:41'),
(8,1,'user_login','Successful login','::1','2026-10-17 17:32:28'),
(9,1,'create_post','Created post #5 \'Deep Dive into Agentic Workflows\'','::1','2026-10-17 17:32:28'),
(10,1,'user_login','Successful login','127.0.0.1','2026-10-17 17:56:43'),
(11,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 07:30:17'),
(12,NULL,'email_verified','Email verified successfully','127.0.0.1','2026-10-18 07:30:17'),
(13,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 07:30:17'),
(14,NULL,'password_reset_request','Password reset requested','127.0.0.1','2026-10-18 07:30:17'),
(15,NULL,'password_reset_success','Password reset completed','127.0.0.1','2026-10-18 07:30:18'),
(16,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 07:30:19'),
(17,NULL,'profile_update','Profile information updated','127.0.0.1','2026-10-18 07:30:19'),
(19,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 07:31:18'),
(20,NULL,'email_verified','Email verified successfully','127.0.0.1','2026-10-18 07:31:18'),
(21,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 07:31:19'),
(22,NULL,'password_reset_request','Password reset requested','127.0.0.1','2026-10-18 07:31:19'),
(23,NULL,'password_reset_success','Password reset completed','127.0.0.1','2026-10-18 07:31:19'),
(24,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 07:31:20'),
(25,NULL,'profile_update','Profile information updated','127.0.0.1','2026-10-18 07:31:20'),
(26,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 07:31:20'),
(27,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 07:31:59'),
(28,NULL,'email_verified','Email verified successfully','127.0.0.1','2026-10-18 07:31:59'),
(29,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 07:32:00'),
(30,NULL,'password_reset_request','Password reset requested','127.0.0.1','2026-10-18 07:32:00'),
(31,NULL,'password_reset_success','Password reset completed','127.0.0.1','2026-10-18 07:32:00'),
(32,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 07:32:01'),
(33,NULL,'profile_update','Profile information updated','127.0.0.1','2026-10-18 07:32:01'),
(34,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 07:32:01'),
(35,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 07:35:09'),
(36,NULL,'oauth_unlink','Unlinked google identity','127.0.0.1','2026-10-18 07:35:09'),
(38,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 07:36:15'),
(39,NULL,'oauth_unlink','Unlinked google identity','127.0.0.1','2026-10-18 07:36:15'),
(40,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 07:36:15'),
(41,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 07:40:44'),
(42,NULL,'oauth_unlink','Unlinked google identity','127.0.0.1','2026-10-18 07:40:45'),
(43,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 07:40:45'),
(44,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 07:40:55'),
(45,NULL,'email_verified','Email verified successfully','127.0.0.1','2026-10-18 07:40:55'),
(46,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 07:40:56'),
(47,NULL,'password_reset_request','Password reset requested','127.0.0.1','2026-10-18 07:40:56'),
(48,NULL,'password_reset_success','Password reset completed','127.0.0.1','2026-10-18 07:40:57'),
(49,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 07:40:57'),
(50,NULL,'profile_update','Profile information updated','127.0.0.1','2026-10-18 07:40:57'),
(51,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 07:40:57'),
(52,NULL,'publish_scheduled','Auto-published scheduled essay #7 \'Scheduled Essay\' (scheduled for 2026-10-18 07:47:54)','127.0.0.1','2026-10-18 07:52:54'),
(53,NULL,'publish_scheduled','Auto-published scheduled essay #9 \'Scheduled Essay\' (scheduled for 2026-10-18 07:48:28)','127.0.0.1','2026-10-18 07:53:28'),
(54,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 07:53:35'),
(55,NULL,'email_verified','Email verified successfully','127.0.0.1','2026-10-18 07:53:35'),
(56,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 07:53:36'),
(57,NULL,'password_reset_request','Password reset requested','127.0.0.1','2026-10-18 07:53:36'),
(58,NULL,'password_reset_success','Password reset completed','127.0.0.1','2026-10-18 07:53:36'),
(59,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 07:53:37'),
(60,NULL,'profile_update','Profile information updated','127.0.0.1','2026-10-18 07:53:37'),
(61,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 07:53:37'),
(62,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 07:53:37'),
(63,NULL,'oauth_unlink','Unlinked google identity','127.0.0.1','2026-10-18 07:53:38'),
(64,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 07:53:38'),
(65,NULL,'publish_scheduled','Auto-published scheduled essay #11 \'Scheduled Essay\' (scheduled for 2026-10-18 07:48:38)','127.0.0.1','2026-10-18 07:53:38'),
(66,1,'user_login','Successful login','::1','2026-10-18 07:53:59'),
(67,1,'user_login','Successful login','127.0.0.1','2026-10-18 07:56:09'),
(68,1,'user_logout','User logged out','127.0.0.1','2026-10-18 07:57:58'),
(69,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 08:05:35'),
(70,NULL,'oauth_unlink','Unlinked google identity','127.0.0.1','2026-10-18 08:05:35'),
(71,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 08:05:35'),
(72,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 08:06:10'),
(73,NULL,'oauth_unlink','Unlinked google identity','127.0.0.1','2026-10-18 08:06:10'),
(74,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 08:06:10'),
(75,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 08:06:49'),
(76,NULL,'oauth_unlink','Unlinked google identity','127.0.0.1','2026-10-18 08:06:49'),
(77,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 08:06:49'),
(78,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 08:07:05'),
(79,NULL,'oauth_unlink','Unlinked google identity','127.0.0.1','2026-10-18 08:07:06'),
(80,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 08:07:06'),
(81,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 08:07:13'),
(82,NULL,'email_verified','Email verified successfully','127.0.0.1','2026-10-18 08:07:13'),
(83,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 08:07:14'),
(84,NULL,'password_reset_request','Password reset requested','127.0.0.1','2026-10-18 08:07:14'),
(85,NULL,'password_reset_success','Password reset completed','127.0.0.1','2026-10-18 08:07:14'),
(86,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 08:07:15'),
(87,NULL,'profile_update','Profile information updated','127.0.0.1','2026-10-18 08:07:15'),
(88,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 08:07:15'),
(89,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 08:07:15'),
(90,NULL,'oauth_unlink','Unlinked google identity','127.0.0.1','2026-10-18 08:07:16'),
(91,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 08:07:16'),
(92,NULL,'publish_scheduled','Auto-published scheduled essay #13 \'Scheduled Essay\' (scheduled for 2026-10-18 08:02:16)','127.0.0.1','2026-10-18 08:07:16'),
(93,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 08:15:35'),
(94,NULL,'email_verified','Email verified successfully','127.0.0.1','2026-10-18 08:15:35'),
(95,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 08:15:35'),
(96,NULL,'password_reset_request','Password reset requested','127.0.0.1','2026-10-18 08:15:35'),
(97,NULL,'password_reset_success','Password reset completed','127.0.0.1','2026-10-18 08:15:35'),
(98,NULL,'user_login','Successful login','127.0.0.1','2026-10-18 08:15:36'),
(99,NULL,'profile_update','Profile information updated','127.0.0.1','2026-10-18 08:15:36'),
(100,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 08:15:36'),
(101,NULL,'user_register','New account registered','127.0.0.1','2026-10-18 08:15:36'),
(102,NULL,'oauth_unlink','Unlinked google identity','127.0.0.1','2026-10-18 08:15:36'),
(103,NULL,'user_logout','User logged out','127.0.0.1','2026-10-18 08:15:36'),
(104,NULL,'publish_scheduled','Auto-published scheduled essay #21 \'Scheduled Essay\' (scheduled for 2026-10-18 08:10:36)','127.0.0.1','2026-10-18 08:15:37'),
(105,1,'user_login','Successful login','127.0.0.1','2026-10-18 08:17:53');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `cat_id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_title` varchar(255) NOT NULL,
  `cat_slug` varchar(255) NOT NULL,
  `cat_description` text DEFAULT NULL,
  PRIMARY KEY (`cat_id`),
  UNIQUE KEY `cat_slug` (`cat_slug`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'PHP & Backend','php','Modern PHP 8.5 engineering, asynchronous concurrency, and zero-allocation database craft.'),
(2,'JavaScript','javascript',NULL),
(3,'Web Development','web-development',NULL),
(4,'Artificial Intelligence','artificial-intelligence','Autonomous agents, tool execution loops, and practical neural architectures.'),
(5,'Web Architecture','web-architecture','Durable systems, minimal software design, and resilient web infrastructure.'),
(7,'Editorial & Design','editorial-design','Typewriter typography, typographic rhythm, and restrained minimalist aesthetics.'),
(9,'Linux & DevOps','linux-devops','Omarchy Linux, sovereign self-hosting, Apache optimization, and UNIX craft.');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `media_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`media_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
INSERT INTO `media` VALUES
(1,'img01.jpg','images/img01.jpg','image/jpeg',96447,'Editorial desk setup with notebook and typewriter',NULL,'2026-10-17 17:18:36'),
(2,'imgAi02.jpg','images/imgAi02.jpg','image/jpeg',43672,'Neural network visualization and AI synthesis',NULL,'2026-10-17 17:18:36'),
(3,'imgjs03.jpg','images/imgjs03.jpg','image/jpeg',88531,'Modern code editor and developer workspace',NULL,'2026-10-17 17:18:36'),
(8,'img_minimalist_software.jpg','images/img_minimalist_software.jpg','image/jpeg',116783,'Minimalist wooden desk with laptop, notebook and mechanical pencil',1,'2026-10-18 08:13:14'),
(9,'img_php_modern.jpg','images/img_php_modern.jpg','image/jpeg',194775,'High-throughput server rack infrastructure glowing with network fiber lights',1,'2026-10-18 08:13:14'),
(10,'img_typewriter_editorial.jpg','images/img_typewriter_editorial.jpg','image/jpeg',157117,'Macro close-up of vintage manual typewriter mechanism and mechanical keys',1,'2026-10-18 08:13:14'),
(11,'img_ai_agents.jpg','images/img_ai_agents.jpg','image/jpeg',237538,'Neural network visualization displaying interconnected luminous knowledge nodes',1,'2026-10-18 08:13:14'),
(12,'img_linux_omarchy.jpg','images/img_linux_omarchy.jpg','image/jpeg',73739,'Omarchy Linux workstation displaying dual monochrome terminal windows',1,'2026-10-18 08:13:14'),
(13,'img_javascript_runtimes.jpg','images/img_javascript_runtimes.jpg','image/jpeg',150000,'Code on monitor in dark developer workspace',1,'2026-10-18 08:14:46');
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `post_autosaves`
--

DROP TABLE IF EXISTS `post_autosaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_autosaves` (
  `autosave_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content_json` longtext NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`autosave_id`),
  UNIQUE KEY `uk_user_post_autosave` (`user_id`,`post_id`),
  CONSTRAINT `1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_autosaves`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `post_autosaves` WRITE;
/*!40000 ALTER TABLE `post_autosaves` DISABLE KEYS */;
INSERT INTO `post_autosaves` VALUES
(4,NULL,1,'','{\"time\":1792310207676,\"blocks\":[{\"id\":\"9mVWqJXe1V\",\"type\":\"paragraph\",\"data\":{\"text\":\"/\"}}],\"version\":\"2.31.7\"}','2026-10-18 07:56:47');
/*!40000 ALTER TABLE `post_autosaves` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `post_revisions`
--

DROP TABLE IF EXISTS `post_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_revisions` (
  `revision_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content_json` longtext DEFAULT NULL,
  `content_html` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`revision_id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_revisions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `post_revisions` WRITE;
/*!40000 ALTER TABLE `post_revisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `post_revisions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `post_tags`
--

DROP TABLE IF EXISTS `post_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_tags` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_tags`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `post_tags` WRITE;
/*!40000 ALTER TABLE `post_tags` DISABLE KEYS */;
INSERT INTO `post_tags` VALUES
(1,1),
(2,1),
(4,1),
(5,1),
(14,1),
(1,2),
(18,2),
(1,3),
(15,3),
(2,4),
(3,5),
(4,6),
(14,6),
(4,7),
(16,7),
(5,11),
(17,11),
(14,15),
(14,16),
(15,18),
(15,19),
(15,20),
(15,21),
(16,23),
(16,24),
(16,25),
(16,26),
(17,28),
(17,29),
(17,30),
(17,31),
(18,33),
(18,34),
(18,35),
(18,36),
(18,37);
/*!40000 ALTER TABLE `post_tags` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_category_id` int(11) NOT NULL DEFAULT 0,
  `post_title` varchar(255) NOT NULL,
  `post_slug` varchar(255) NOT NULL,
  `post_subtitle` varchar(255) DEFAULT NULL,
  `post_excerpt` text DEFAULT NULL,
  `post_author` varchar(255) NOT NULL,
  `post_date` date NOT NULL,
  `post_image` text DEFAULT NULL,
  `post_content` text DEFAULT NULL,
  `post_content_json` longtext DEFAULT NULL,
  `post_tags` varchar(255) DEFAULT '',
  `post_seo_title` varchar(255) DEFAULT NULL,
  `post_seo_desc` text DEFAULT NULL,
  `post_comment_count` int(11) NOT NULL DEFAULT 0,
  `post_views` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `scheduled_at` datetime DEFAULT NULL,
  `post_version` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `post_status` varchar(255) NOT NULL DEFAULT 'published',
  PRIMARY KEY (`post_id`),
  UNIQUE KEY `post_slug` (`post_slug`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES
(1,1,'Welcome to WordWise on Omarchy Linux','welcome-to-wordwise-on-omarchy-linux-1',NULL,NULL,'Ronak','2026-10-17','img01.jpg','WordWise CMS is now running natively on Omarchy Linux with Apache, PHP 8.5, and MariaDB.',NULL,'php,linux,cms',NULL,NULL,0,3,1,0,NULL,1,'2026-10-17 17:18:36','2026-10-18 08:16:16','published'),
(2,4,'Next-Generation AI Assisted Programming','next-generation-ai-assisted-programming-2',NULL,NULL,'Ronak','2026-10-17','imgAi02.jpg','Exploring modern AI agent architectures and seamless Linux development workflows.',NULL,'ai,future',NULL,NULL,0,1,0,0,NULL,1,'2026-10-17 17:18:36','2026-10-18 07:16:57','published'),
(3,3,'Mastering JavaScript in 2026','mastering-javascript-in-2026-3',NULL,NULL,'Ronak','2026-10-17','imgjs03.jpg','Building responsive user interfaces and robust frontends with modern JavaScript.',NULL,'javascript,frontend',NULL,NULL,0,0,0,0,NULL,1,'2026-10-17 17:18:36','2026-10-18 08:13:53','published'),
(4,7,'The Art of Typewriter Web Design','the-art-of-typewriter-web-design','Examining minimalism, monospace typography, and digital restraint.',NULL,'Ronak Rathod','2026-10-17','img01.jpg','<h2>Introduction to Restraint</h2><p>Modern web interfaces have become visually cluttered. By returning to monospace typography and generous whitespace, we reclaim focus and legibility.</p><h2>Typographical Rhythm</h2><p>Typewriter aesthetics remind us that content is the interface.</p>',NULL,'typography, minimalism, architecture',NULL,NULL,0,2,0,0,NULL,1,'2026-10-17 17:31:41','2026-10-18 08:13:53','published'),
(5,4,'Deep Dive into Agentic Workflows','deep-dive-into-agentic-workflows','Building resilient AI agents for modern systems engineering.',NULL,'Ronak Rathod','2026-10-17','imgAi02.jpg','<h2>The Shift to Autonomy</h2><p>Agentic architectures represent a paradigm change from simple completion to goal-oriented multi-step reasoning.</p><h2>Observability and Verification</h2><p>Rigorous verification ensures reproducible system state.</p>',NULL,'ai, architecture',NULL,NULL,0,0,0,0,NULL,1,'2026-10-17 17:32:28','2026-10-17 17:32:28','published'),
(14,5,'The Philosophy of Minimalist Software: Why Less Is More in 2026','the-philosophy-of-minimalist-software','Rejecting accidental complexity in favor of durable foundations, native browser primitives, and zero-runtime bloat.','An essay exploring how modern web engineering became buried under thousands of transient dependencies, and why returning to semantic simplicity creates superior, lasting software.','Ronak Rathod','2026-10-18','img_minimalist_software.jpg','<p>We live in an era where deploying a simple text blog frequently requires multi-gigabyte build containers, three levels of virtual DOM reconciliation, and hundreds of transient npm dependencies that deprecate every six months. Somewhere along the journey toward developer convenience, we confused technological sophistication with architectural progress.</p>\n\n<h2 id=\"the-creeping-disease-of-accidental-complexity\">The Creeping Disease of Accidental Complexity</h2>\n\n<p>Software engineering distinguishes between two fundamental forms of difficulty: essential complexity (the intrinsic challenges of the problem domain) and accidental complexity (the self-inflicted friction introduced by our tooling, abstractions, and layers of indirection). In modern web development, accidental complexity vastly outweighs the essential.</p>\n\n<blockquote><p>There are two ways of constructing a software design: One way is to make it so simple that there are obviously no deficiencies, and the other way is to make it so complicated that there are no obvious deficiencies.</p><cite class=\"block mt-2 font-mono text-xs text-ink-500\">— C.A.R. Hoare, 1980 Turing Award Lecture</cite></blockquote>\n\n<h2 id=\"the-power-of-native-browser-primitives\">The Power of Native Browser Primitives</h2>\n\n<p>Every browser running today contains a high-performance, battle-tested layout engine, a hardware-accelerated CSS parser, and robust HTTP cache semantics. When we honor the native medium—using semantic HTML, compiled utility CSS, and native ES modules—our websites load instantaneously and remain functional indefinitely.</p>\n\n<div class=\"my-6 rounded overflow-hidden border border-paper-200 dark:border-paper-800 bg-[#0d1117] text-white font-mono text-xs not-prose\"><div class=\"px-4 py-2 bg-[#161b22] border-b border-[#30363d] flex items-center justify-between text-[11px] text-gray-400\"><span class=\"uppercase tracking-wider font-semibold\">php</span><button type=\"button\" onclick=\"navigator.clipboard.writeText(document.getElementById(\'code_59af0a93\').textContent); this.textContent=\'COPIED!\'; setTimeout(() => this.textContent=\'COPY\', 2000);\" class=\"hover:text-white transition-colors\">COPY</button></div><pre class=\"p-4 overflow-x-auto\"><code id=\"code_59af0a93\" class=\"language-php\">// Clean, parameterized data access without 10 layers of ORM abstraction\nfunction fetch_dispatch(PDO $pdo, string $slug): ?array {\n    $stmt = $pdo-&gt;prepare(&#039;SELECT post_id, post_title, post_content FROM posts WHERE post_slug = ? LIMIT 1&#039;);\n    $stmt-&gt;execute([$slug]);\n    return $stmt-&gt;fetch(PDO::FETCH_ASSOC) ?: null;\n}</code></pre></div>\n\n<div class=\"p-4 my-6 rounded-r-sm font-mono text-xs space-y-1.5 border-l-4 border-emerald-500 bg-emerald-500/10 text-emerald-900 dark:text-emerald-200\"><div class=\"font-bold uppercase tracking-wider text-[11px]\">Architectural Maxim</div><div>Every dependency you eliminate is a future security advisory you will never have to mitigate and a breaking update you will never have to debug.</div></div>\n\n<h3 id=\"principles-for-durable-digital-systems\">Principles for Durable Digital Systems</h3>\n\n<ul><li>Zero-Runtime Framework Lock-In: Templates produce standard semantic HTML that renders identically in any browser.</li><li>Self-Documenting Relational Schemas: MariaDB tables with explicit foreign keys and constraints over opaque document blobs.</li><li>Inspectable State: Storing readable structured JSON alongside pre-rendered HTML for instant reads.</li><li>Sovereign Infrastructure: Running on standard Linux environments without proprietary cloud lock-in.</li></ul>\n\n<hr class=\"my-10 border-paper-200 dark:border-paper-800\">\n\n<p>Minimalism is not about deprivation; it is about intentionality. When you strip away the superfluous, what remains is the pure essence of your work—words, typography, and ideas.</p>','{\"time\":1792311194000,\"blocks\":[{\"type\":\"paragraph\",\"data\":{\"text\":\"We live in an era where deploying a simple text blog frequently requires multi-gigabyte build containers, three levels of virtual DOM reconciliation, and hundreds of transient npm dependencies that deprecate every six months. Somewhere along the journey toward developer convenience, we confused technological sophistication with architectural progress.\"}},{\"type\":\"header\",\"data\":{\"text\":\"The Creeping Disease of Accidental Complexity\",\"level\":2}},{\"type\":\"paragraph\",\"data\":{\"text\":\"Software engineering distinguishes between two fundamental forms of difficulty: essential complexity (the intrinsic challenges of the problem domain) and accidental complexity (the self-inflicted friction introduced by our tooling, abstractions, and layers of indirection). In modern web development, accidental complexity vastly outweighs the essential.\"}},{\"type\":\"quote\",\"data\":{\"text\":\"There are two ways of constructing a software design: One way is to make it so simple that there are obviously no deficiencies, and the other way is to make it so complicated that there are no obvious deficiencies.\",\"caption\":\"C.A.R. Hoare, 1980 Turing Award Lecture\"}},{\"type\":\"header\",\"data\":{\"text\":\"The Power of Native Browser Primitives\",\"level\":2}},{\"type\":\"paragraph\",\"data\":{\"text\":\"Every browser running today contains a high-performance, battle-tested layout engine, a hardware-accelerated CSS parser, and robust HTTP cache semantics. When we honor the native medium—using semantic HTML, compiled utility CSS, and native ES modules—our websites load instantaneously and remain functional indefinitely.\"}},{\"type\":\"code\",\"data\":{\"language\":\"php\",\"code\":\"// Clean, parameterized data access without 10 layers of ORM abstraction\\nfunction fetch_dispatch(PDO $pdo, string $slug): ?array {\\n    $stmt = $pdo->prepare(\'SELECT post_id, post_title, post_content FROM posts WHERE post_slug = ? LIMIT 1\');\\n    $stmt->execute([$slug]);\\n    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;\\n}\"}},{\"type\":\"warning\",\"data\":{\"title\":\"Architectural Maxim\",\"message\":\"Every dependency you eliminate is a future security advisory you will never have to mitigate and a breaking update you will never have to debug.\",\"type\":\"tip\"}},{\"type\":\"header\",\"data\":{\"text\":\"Principles for Durable Digital Systems\",\"level\":3}},{\"type\":\"list\",\"data\":{\"style\":\"unordered\",\"items\":[\"Zero-Runtime Framework Lock-In: Templates produce standard semantic HTML that renders identically in any browser.\",\"Self-Documenting Relational Schemas: MariaDB tables with explicit foreign keys and constraints over opaque document blobs.\",\"Inspectable State: Storing readable structured JSON alongside pre-rendered HTML for instant reads.\",\"Sovereign Infrastructure: Running on standard Linux environments without proprietary cloud lock-in.\"]}},{\"type\":\"delimiter\",\"data\":[]},{\"type\":\"paragraph\",\"data\":{\"text\":\"Minimalism is not about deprivation; it is about intentionality. When you strip away the superfluous, what remains is the pure essence of your work—words, typography, and ideas.\"}}],\"version\":\"2.30.0\"}','minimalism, architecture, web-craft, engineering-philosophy','The Philosophy of Minimalist Software — WordWise','Discover why minimalist software design, semantic HTML, and zero-dependency architectures outlast bloated transient frameworks in 2026.',0,2,1,1,NULL,1,'2026-10-18 08:13:14','2026-10-18 08:16:22','published'),
(15,1,'Deep Dive: Building High-Performance Web Systems with Modern PHP 8.5','building-high-performance-systems-with-php-8','Exploring JIT compilation, OPcache preloading, typed fiber concurrency, and zero-allocation database pooling.','A technical investigation into modern PHP 8.5 internals, demonstrating how native typing, JIT performance, and prepared statements make PHP a formidable backend powerhouse.','Ronak Rathod','2026-10-18','img_php_modern.jpg','<p>The persistent stereotype of PHP as an unoptimized, dynamic scripting language belongs to a bygone decade. With the introduction of PHP 8.0 through 8.5, the Zend Engine underwent a fundamental renaissance: real Just-In-Time (JIT) compilation, strict static typing, property promotion, and native fibers.</p>\n\n<h2 id=\"opcache-preloading-memory-resident-code\">OPcache Preloading & Memory Resident Code</h2>\n\n<p>One of the most consequential advancements in modern PHP is OPcache Preloading. Introduced in PHP 7.4 and refined in subsequent releases, preloading compiles your entire application graph into persistent shared memory upon server boot, completely eliminating file resolution and compilation overhead on incoming HTTP requests.</p>\n\n<div class=\"my-6 rounded overflow-hidden border border-paper-200 dark:border-paper-800 bg-[#0d1117] text-white font-mono text-xs not-prose\"><div class=\"px-4 py-2 bg-[#161b22] border-b border-[#30363d] flex items-center justify-between text-[11px] text-gray-400\"><span class=\"uppercase tracking-wider font-semibold\">php</span><button type=\"button\" onclick=\"navigator.clipboard.writeText(document.getElementById(\'code_9e5f1f7a\').textContent); this.textContent=\'COPIED!\'; setTimeout(() => this.textContent=\'COPY\', 2000);\" class=\"hover:text-white transition-colors\">COPY</button></div><pre class=\"p-4 overflow-x-auto\"><code id=\"code_9e5f1f7a\" class=\"language-php\">// Strict typing and readonly class declarations in modern PHP\nfinal readonly class ArticlePayload {\n    public function __construct(\n        public int $id,\n        public string $title,\n        public string $slug,\n        public DateTimeImmutable $publishedAt,\n        public array $tags = []\n    ) {}\n}</code></pre></div>\n\n<div class=\"p-4 my-6 rounded-r-sm font-mono text-xs space-y-1.5 border-l-4 border-accent bg-paper-100 dark:bg-paper-900\"><div class=\"font-bold uppercase tracking-wider text-[11px]\">Production Optimization Tip</div><div>Ensure opcache.validate_timestamps=0 in production /etc/php/php.ini for maximum CPU efficiency. Use graceful Apache reloads (systemctl reload httpd) during deployments.</div></div>\n\n<h2 id=\"prepared-statements-connection-architecture\">Prepared Statements & Connection Architecture</h2>\n\n<p>When paired with MariaDB 12 over a local UNIX socket (`/run/mysqld/mysqld.sock`), PHP PDO prepared statements execute in single-digit microseconds. Because query parsing and plan caching happen once on the server, the database engine executes parameterized statements with optimal indexing and zero SQL injection vulnerability.</p>\n\n<blockquote><p>Simplicity is about subtracting the obvious and adding the meaningful.</p><cite class=\"block mt-2 font-mono text-xs text-ink-500\">— John Maeda, The Laws of Simplicity</cite></blockquote>','{\"time\":1792311194000,\"blocks\":[{\"type\":\"paragraph\",\"data\":{\"text\":\"The persistent stereotype of PHP as an unoptimized, dynamic scripting language belongs to a bygone decade. With the introduction of PHP 8.0 through 8.5, the Zend Engine underwent a fundamental renaissance: real Just-In-Time (JIT) compilation, strict static typing, property promotion, and native fibers.\"}},{\"type\":\"header\",\"data\":{\"text\":\"OPcache Preloading & Memory Resident Code\",\"level\":2}},{\"type\":\"paragraph\",\"data\":{\"text\":\"One of the most consequential advancements in modern PHP is OPcache Preloading. Introduced in PHP 7.4 and refined in subsequent releases, preloading compiles your entire application graph into persistent shared memory upon server boot, completely eliminating file resolution and compilation overhead on incoming HTTP requests.\"}},{\"type\":\"code\",\"data\":{\"language\":\"php\",\"code\":\"// Strict typing and readonly class declarations in modern PHP\\nfinal readonly class ArticlePayload {\\n    public function __construct(\\n        public int $id,\\n        public string $title,\\n        public string $slug,\\n        public DateTimeImmutable $publishedAt,\\n        public array $tags = []\\n    ) {}\\n}\"}},{\"type\":\"warning\",\"data\":{\"title\":\"Production Optimization Tip\",\"message\":\"Ensure opcache.validate_timestamps=0 in production /etc/php/php.ini for maximum CPU efficiency. Use graceful Apache reloads (systemctl reload httpd) during deployments.\",\"type\":\"info\"}},{\"type\":\"header\",\"data\":{\"text\":\"Prepared Statements & Connection Architecture\",\"level\":2}},{\"type\":\"paragraph\",\"data\":{\"text\":\"When paired with MariaDB 12 over a local UNIX socket (`/run/mysqld/mysqld.sock`), PHP PDO prepared statements execute in single-digit microseconds. Because query parsing and plan caching happen once on the server, the database engine executes parameterized statements with optimal indexing and zero SQL injection vulnerability.\"}},{\"type\":\"quote\",\"data\":{\"text\":\"Simplicity is about subtracting the obvious and adding the meaningful.\",\"caption\":\"John Maeda, The Laws of Simplicity\"}}],\"version\":\"2.30.0\"}','php, backend, performance, mariadb, optimization','High-Performance Web Systems with Modern PHP 8.5 — WordWise','A deep dive into PHP 8.5 features, JIT compilation, OPcache preloading, and high-throughput MariaDB prepared statements.',0,0,0,0,NULL,1,'2026-10-18 08:13:14','2026-10-18 08:13:14','published'),
(16,7,'Typography as Interface: Crafting the Typewriter Aesthetic','typography-as-interface-the-typewriter-aesthetic','The optical rhythm of JetBrains Mono, restrained margins, and the tactile honesty of mechanical publishing.','Examining the timeless beauty of typewriter-inspired digital publications, exploring how fixed-pitch typography establishes trust, structure, and calm reading focus.','Ronak Rathod','2026-10-18','img_typewriter_editorial.jpg','<p>In a digital landscape dominated by neon gradients, floating cards, and chaotic micro-interactions, the typewriter aesthetic offers an oasis of calm. It recalls an era when the written word stood on its own merits, unencumbered by visual gimmicks.</p>\n\n<h2 id=\"the-mechanical-honesty-of-fixed-pitch-glyphs\">The Mechanical Honesty of Fixed-Pitch Glyphs</h2>\n\n<p>Typewriter typography is built upon mechanical discipline: every character occupies the exact same horizontal unit of space. In digital interfaces, monospace type—specifically designed fonts like JetBrains Mono and IBM Plex Mono—communicates craftsmanship, technical rigor, and precision.</p>\n\n<blockquote><p>Good design is as little design as possible. Less, but better—because it concentrates on the essential aspects, and the products are not burdened with non-essentials.</p><cite class=\"block mt-2 font-mono text-xs text-ink-500\">— Dieter Rams, Ten Principles for Good Design</cite></blockquote>\n\n<h2 id=\"typographic-hierarchy-in-wordwise\">Typographic Hierarchy in WordWise</h2>\n\n<p>In WordWise, typography is not an afterthought; it is the core user interface. Monospace is utilized purposefully for structure, metadata, timestamps, and code, while generous line height and optimal reading measures ensure that long-form reading remains effortless.</p>\n\n<div class=\"my-6 rounded overflow-hidden border border-paper-200 dark:border-paper-800 bg-[#0d1117] text-white font-mono text-xs not-prose\"><div class=\"px-4 py-2 bg-[#161b22] border-b border-[#30363d] flex items-center justify-between text-[11px] text-gray-400\"><span class=\"uppercase tracking-wider font-semibold\">css</span><button type=\"button\" onclick=\"navigator.clipboard.writeText(document.getElementById(\'code_8b913db8\').textContent); this.textContent=\'COPIED!\'; setTimeout(() => this.textContent=\'COPY\', 2000);\" class=\"hover:text-white transition-colors\">COPY</button></div><pre class=\"p-4 overflow-x-auto\"><code id=\"code_8b913db8\" class=\"language-css\">/* Tailwind Editorial Reading Token Configuration */\n.max-w-reading {\n  max-width: 68ch;\n  line-height: 1.8;\n  letter-spacing: -0.011em;\n}\n.typewriter-badge {\n  font-family: &#039;JetBrains Mono&#039;, monospace;\n  border: 1px solid var(--border-color);\n  padding: 0.25rem 0.6rem;\n  text-transform: uppercase;\n}</code></pre></div>\n\n<div class=\"p-4 my-6 rounded-r-sm font-mono text-xs space-y-1.5 border-l-4 border-emerald-500 bg-emerald-500/10 text-emerald-900 dark:text-emerald-200\"><div class=\"font-bold uppercase tracking-wider text-[11px]\">Design Principle</div><div>Restraint is strength. When in doubt, increase whitespace, remove a border, and let the typography carry the weight of your message.</div></div>','{\"time\":1792311194000,\"blocks\":[{\"type\":\"paragraph\",\"data\":{\"text\":\"In a digital landscape dominated by neon gradients, floating cards, and chaotic micro-interactions, the typewriter aesthetic offers an oasis of calm. It recalls an era when the written word stood on its own merits, unencumbered by visual gimmicks.\"}},{\"type\":\"header\",\"data\":{\"text\":\"The Mechanical Honesty of Fixed-Pitch Glyphs\",\"level\":2}},{\"type\":\"paragraph\",\"data\":{\"text\":\"Typewriter typography is built upon mechanical discipline: every character occupies the exact same horizontal unit of space. In digital interfaces, monospace type—specifically designed fonts like JetBrains Mono and IBM Plex Mono—communicates craftsmanship, technical rigor, and precision.\"}},{\"type\":\"quote\",\"data\":{\"text\":\"Good design is as little design as possible. Less, but better—because it concentrates on the essential aspects, and the products are not burdened with non-essentials.\",\"caption\":\"Dieter Rams, Ten Principles for Good Design\"}},{\"type\":\"header\",\"data\":{\"text\":\"Typographic Hierarchy in WordWise\",\"level\":2}},{\"type\":\"paragraph\",\"data\":{\"text\":\"In WordWise, typography is not an afterthought; it is the core user interface. Monospace is utilized purposefully for structure, metadata, timestamps, and code, while generous line height and optimal reading measures ensure that long-form reading remains effortless.\"}},{\"type\":\"code\",\"data\":{\"language\":\"css\",\"code\":\"/* Tailwind Editorial Reading Token Configuration */\\n.max-w-reading {\\n  max-width: 68ch;\\n  line-height: 1.8;\\n  letter-spacing: -0.011em;\\n}\\n.typewriter-badge {\\n  font-family: \'JetBrains Mono\', monospace;\\n  border: 1px solid var(--border-color);\\n  padding: 0.25rem 0.6rem;\\n  text-transform: uppercase;\\n}\"}},{\"type\":\"warning\",\"data\":{\"title\":\"Design Principle\",\"message\":\"Restraint is strength. When in doubt, increase whitespace, remove a border, and let the typography carry the weight of your message.\",\"type\":\"tip\"}}],\"version\":\"2.30.0\"}','typography, design, jetbrains-mono, typewriter, aesthetics','Typography as Interface: The Typewriter Aesthetic — WordWise','Explore the visual theory and design craftsmanship behind typewriter-inspired web aesthetics and monospace editorial reading.',0,0,0,1,NULL,1,'2026-10-18 08:13:14','2026-10-18 08:13:14','published'),
(17,4,'Autonomous Agents in Production: Beyond the Chatbot Paradigm','autonomous-agents-in-production','Grounding intelligent models through live terminal execution loops, reactive messaging, and verified sandbox boundaries.','A practical overview of modern agentic workflows, moving from passive conversational models to autonomous agents capable of inspecting, compiling, and testing real systems.','Ronak Rathod','2026-10-18','img_ai_agents.jpg','<p>The initial wave of generative artificial intelligence presented models as chat interfaces—conversational assistants waiting for human prompts. However, the true inflection point occurs when models operate as autonomous agents equipped with tools: the ability to read codebases, execute commands in secure sandboxes, and inspect error logs.</p>\n\n<h2 id=\"the-grounding-loop-hypothesis-execution-and-verification\">The Grounding Loop: Hypothesis, Execution, and Verification</h2>\n\n<p>Unanchored language models hallucinate when asked to solve complex programming tasks in a single forward pass. An agentic workflow transforms this by establishing an iterative feedback loop: the agent hypothesizes a solution, applies surgical file changes, executes unit tests in a terminal, and inspects stderr output before declaring completion.</p>\n\n<div class=\"my-6 rounded overflow-hidden border border-paper-200 dark:border-paper-800 bg-[#0d1117] text-white font-mono text-xs not-prose\"><div class=\"px-4 py-2 bg-[#161b22] border-b border-[#30363d] flex items-center justify-between text-[11px] text-gray-400\"><span class=\"uppercase tracking-wider font-semibold\">json</span><button type=\"button\" onclick=\"navigator.clipboard.writeText(document.getElementById(\'code_4449d89c\').textContent); this.textContent=\'COPIED!\'; setTimeout(() => this.textContent=\'COPY\', 2000);\" class=\"hover:text-white transition-colors\">COPY</button></div><pre class=\"p-4 overflow-x-auto\"><code id=\"code_4449d89c\" class=\"language-json\">{\n  &quot;action&quot;: &quot;execute_verification&quot;,\n  &quot;target&quot;: &quot;tests/test_milestones4_6.php&quot;,\n  &quot;assertion_count&quot;: 24,\n  &quot;status&quot;: &quot;PASS&quot;,\n  &quot;feedback&quot;: &quot;All block renderers and concurrency locks verified.&quot;\n}</code></pre></div>\n\n<h3 id=\"the-three-hallmarks-of-resilient-agents\">The Three Hallmarks of Resilient Agents</h3>\n\n<ul><li>Deterministic State Persistence: Storing execution history in canonical JSONL transcripts rather than ephemeral chat memory.</li><li>Surgical Tool Calling: Favoring precise contiguous line replacements over whole-file rewrites.</li><li>Optimistic Verification: Running automated regression suites after every major milestone to detect subtle breakages early.</li></ul>\n\n<blockquote><p>Intelligence is not merely prediction; it is the capacity to recognize error, adjust parameters, and achieve an invariant goal despite environmental resistance.</p><cite class=\"block mt-2 font-mono text-xs text-ink-500\">— Antigravity Systems Engineering</cite></blockquote>','{\"time\":1792311194000,\"blocks\":[{\"type\":\"paragraph\",\"data\":{\"text\":\"The initial wave of generative artificial intelligence presented models as chat interfaces—conversational assistants waiting for human prompts. However, the true inflection point occurs when models operate as autonomous agents equipped with tools: the ability to read codebases, execute commands in secure sandboxes, and inspect error logs.\"}},{\"type\":\"header\",\"data\":{\"text\":\"The Grounding Loop: Hypothesis, Execution, and Verification\",\"level\":2}},{\"type\":\"paragraph\",\"data\":{\"text\":\"Unanchored language models hallucinate when asked to solve complex programming tasks in a single forward pass. An agentic workflow transforms this by establishing an iterative feedback loop: the agent hypothesizes a solution, applies surgical file changes, executes unit tests in a terminal, and inspects stderr output before declaring completion.\"}},{\"type\":\"code\",\"data\":{\"language\":\"json\",\"code\":\"{\\n  \\\"action\\\": \\\"execute_verification\\\",\\n  \\\"target\\\": \\\"tests/test_milestones4_6.php\\\",\\n  \\\"assertion_count\\\": 24,\\n  \\\"status\\\": \\\"PASS\\\",\\n  \\\"feedback\\\": \\\"All block renderers and concurrency locks verified.\\\"\\n}\"}},{\"type\":\"header\",\"data\":{\"text\":\"The Three Hallmarks of Resilient Agents\",\"level\":3}},{\"type\":\"list\",\"data\":{\"style\":\"unordered\",\"items\":[\"Deterministic State Persistence: Storing execution history in canonical JSONL transcripts rather than ephemeral chat memory.\",\"Surgical Tool Calling: Favoring precise contiguous line replacements over whole-file rewrites.\",\"Optimistic Verification: Running automated regression suites after every major milestone to detect subtle breakages early.\"]}},{\"type\":\"quote\",\"data\":{\"text\":\"Intelligence is not merely prediction; it is the capacity to recognize error, adjust parameters, and achieve an invariant goal despite environmental resistance.\",\"caption\":\"Antigravity Systems Engineering\"}}],\"version\":\"2.30.0\"}','ai, autonomous-agents, prompt-engineering, systems, automation','Autonomous Agents in Production — WordWise','Discover how autonomous AI agents execute tasks in production through live sandbox execution, reactive messaging, and self-correcting feedback loops.',0,0,0,0,NULL,1,'2026-10-18 08:13:14','2026-10-18 08:13:14','published'),
(18,9,'The Independent Hacker’s Guide to Omarchy Linux & Self-Hosted Sovereignty','the-independent-hackers-guide-to-omarchy-linux','Why running native MariaDB and Apache on Arch Linux yields sub-millisecond latency and true data ownership.','A practical manifesto for self-hosting your digital life and publishing platform on Omarchy Linux, escaping platform rent-seeking and reclaiming system craftsmanship.','Ronak Rathod','2026-10-18','img_linux_omarchy.jpg','<p>For years, developers have been conditioned to believe that running a website requires multi-tiered cloud infrastructure, serverless function cold boots, and expensive monthly subscriptions. Yet on a modern machine running Omarchy Linux (Arch Linux), a native Apache and MariaDB stack handles thousands of requests per second while consuming less than 200 megabytes of memory.</p>\n\n<h2 id=\"the-beauty-of-the-native-rolling-release\">The Beauty of the Native Rolling Release</h2>\n\n<p>Omarchy Linux provides pristine, unpatched upstream packages directly from developers. You get the latest PHP releases with bleeding-edge performance features, MariaDB with modern InnoDB storage engines, and modern systemd service orchestration right at your fingertips.</p>\n\n<div class=\"my-6 rounded overflow-hidden border border-paper-200 dark:border-paper-800 bg-[#0d1117] text-white font-mono text-xs not-prose\"><div class=\"px-4 py-2 bg-[#161b22] border-b border-[#30363d] flex items-center justify-between text-[11px] text-gray-400\"><span class=\"uppercase tracking-wider font-semibold\">bash</span><button type=\"button\" onclick=\"navigator.clipboard.writeText(document.getElementById(\'code_391a3bbe\').textContent); this.textContent=\'COPIED!\'; setTimeout(() => this.textContent=\'COPY\', 2000);\" class=\"hover:text-white transition-colors\">COPY</button></div><pre class=\"p-4 overflow-x-auto\"><code id=\"code_391a3bbe\" class=\"language-bash\"># Running high-throughput scheduled publication via systemd timer\n[Unit]\nDescription=WordWise Scheduled Publishing Runner\n\n[Service]\nType=oneshot\nUser=ron\nExecStart=/usr/bin/php /home/ron/Projects/WordWise/cron/publish_scheduled.php --json\n\n[Install]\nWantedBy=multi-user.target</code></pre></div>\n\n<div class=\"p-4 my-6 rounded-r-sm font-mono text-xs space-y-1.5 border-l-4 border-accent bg-paper-100 dark:bg-paper-900\"><div class=\"font-bold uppercase tracking-wider text-[11px]\">UNIX Philosophy in Action</div><div>Write programs that do one thing and do it well. Write programs to work together. Write programs to handle text streams, because that is a universal interface.</div></div>\n\n<p>When you host your own publishing platform, you own your database, your URLs, and your relationship with your readers. No algorithm can demote your essays, and no subscription change can lock you out of your archives.</p>','{\"time\":1792311194000,\"blocks\":[{\"type\":\"paragraph\",\"data\":{\"text\":\"For years, developers have been conditioned to believe that running a website requires multi-tiered cloud infrastructure, serverless function cold boots, and expensive monthly subscriptions. Yet on a modern machine running Omarchy Linux (Arch Linux), a native Apache and MariaDB stack handles thousands of requests per second while consuming less than 200 megabytes of memory.\"}},{\"type\":\"header\",\"data\":{\"text\":\"The Beauty of the Native Rolling Release\",\"level\":2}},{\"type\":\"paragraph\",\"data\":{\"text\":\"Omarchy Linux provides pristine, unpatched upstream packages directly from developers. You get the latest PHP releases with bleeding-edge performance features, MariaDB with modern InnoDB storage engines, and modern systemd service orchestration right at your fingertips.\"}},{\"type\":\"code\",\"data\":{\"language\":\"bash\",\"code\":\"# Running high-throughput scheduled publication via systemd timer\\n[Unit]\\nDescription=WordWise Scheduled Publishing Runner\\n\\n[Service]\\nType=oneshot\\nUser=ron\\nExecStart=/usr/bin/php /home/ron/Projects/WordWise/cron/publish_scheduled.php --json\\n\\n[Install]\\nWantedBy=multi-user.target\"}},{\"type\":\"warning\",\"data\":{\"title\":\"UNIX Philosophy in Action\",\"message\":\"Write programs that do one thing and do it well. Write programs to work together. Write programs to handle text streams, because that is a universal interface.\",\"type\":\"info\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"When you host your own publishing platform, you own your database, your URLs, and your relationship with your readers. No algorithm can demote your essays, and no subscription change can lock you out of your archives.\"}}],\"version\":\"2.30.0\"}','linux, omarchy, arch-linux, self-hosting, devops, apache','The Independent Hacker’s Guide to Omarchy Linux — WordWise','Learn how to configure native Apache, MariaDB, and PHP on Omarchy Linux for lightning-fast self-hosted publishing and sovereign computing.',0,0,0,0,NULL,1,'2026-10-18 08:13:14','2026-10-18 08:13:14','published'),
(19,2,'The Modern JavaScript Runtime Landscape: Node, Deno, and Bun in 2026','modern-javascript-runtime-landscape-2026','A comparative performance analysis of V8 vs JavaScriptCore, native TypeScript execution, and modern standard APIs.','Examining how the JavaScript runtime ecosystem evolved beyond monolithic Node.js toward web-standard fetch, streams, and instant native execution.','Ronak Rathod','2026-10-18','img_javascript_runtimes.jpg','<p>For over a decade, Node.js was the undisputed runtime powering the JavaScript server ecosystem. Today, the landscape is vibrant and fiercely competitive: Deno pioneered security sandbox defaults and native TypeScript, while Bun introduced a blindingly fast C++ and Zig-based architecture powered by WebKit’s JavaScriptCore.</p>\n\n<h2 id=\"convergence-on-universal-web-standards\">Convergence on Universal Web Standards</h2>\n\n<p>The single greatest victory for developers in 2026 is standard convergence: fetch, Request, Response, WebStreams, and WebSockets now work identically across browsers, Edge workers, and backend runtimes.</p>\n\n<div class=\"my-6 rounded overflow-hidden border border-paper-200 dark:border-paper-800 bg-[#0d1117] text-white font-mono text-xs not-prose\"><div class=\"px-4 py-2 bg-[#161b22] border-b border-[#30363d] flex items-center justify-between text-[11px] text-gray-400\"><span class=\"uppercase tracking-wider font-semibold\">javascript</span><button type=\"button\" onclick=\"navigator.clipboard.writeText(document.getElementById(\'code_f23e2caa\').textContent); this.textContent=\'COPIED!\'; setTimeout(() => this.textContent=\'COPY\', 2000);\" class=\"hover:text-white transition-colors\">COPY</button></div><pre class=\"p-4 overflow-x-auto\"><code id=\"code_f23e2caa\" class=\"language-javascript\">// Universal HTTP server pattern working across modern runtimes\nexport default {\n  port: 3000,\n  fetch(request) {\n    const url = new URL(request.url);\n    if (url.pathname === &quot;/api/health&quot;) {\n      return Response.json({ status: &quot;healthy&quot;, time: Date.now() });\n    }\n    return new Response(&quot;WordWise Universal Endpoint&quot;);\n  }\n};</code></pre></div>\n\n<div class=\"p-4 my-6 rounded-r-sm font-mono text-xs space-y-1.5 border-l-4 border-emerald-500 bg-emerald-500/10 text-emerald-900 dark:text-emerald-200\"><div class=\"font-bold uppercase tracking-wider text-[11px]\">Developer Recommendation</div><div>Target standard Web APIs (Request, Response, URLPattern) in your library code to ensure seamless portability across Node 22+, Deno, and Bun.</div></div>','{\"time\":1792311286000,\"blocks\":[{\"type\":\"paragraph\",\"data\":{\"text\":\"For over a decade, Node.js was the undisputed runtime powering the JavaScript server ecosystem. Today, the landscape is vibrant and fiercely competitive: Deno pioneered security sandbox defaults and native TypeScript, while Bun introduced a blindingly fast C++ and Zig-based architecture powered by WebKit\\u2019s JavaScriptCore.\"}},{\"type\":\"header\",\"data\":{\"text\":\"Convergence on Universal Web Standards\",\"level\":2}},{\"type\":\"paragraph\",\"data\":{\"text\":\"The single greatest victory for developers in 2026 is standard convergence: fetch, Request, Response, WebStreams, and WebSockets now work identically across browsers, Edge workers, and backend runtimes.\"}},{\"type\":\"code\",\"data\":{\"language\":\"javascript\",\"code\":\"// Universal HTTP server pattern working across modern runtimes\\nexport default {\\n  port: 3000,\\n  fetch(request) {\\n    const url = new URL(request.url);\\n    if (url.pathname === \\\"/api/health\\\") {\\n      return Response.json({ status: \\\"healthy\\\", time: Date.now() });\\n    }\\n    return new Response(\\\"WordWise Universal Endpoint\\\");\\n  }\\n};\"}},{\"type\":\"warning\",\"data\":{\"title\":\"Developer Recommendation\",\"message\":\"Target standard Web APIs (Request, Response, URLPattern) in your library code to ensure seamless portability across Node 22+, Deno, and Bun.\",\"type\":\"tip\"}}],\"version\":\"2.30.0\"}','javascript, nodejs, bun, deno, performance, web-standards','Modern JavaScript Runtimes in 2026 — WordWise','Compare Node.js, Deno, and Bun performance, web standards, and TypeScript execution in 2026.',0,0,0,0,NULL,1,'2026-10-18 08:14:46','2026-10-18 08:14:46','published');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
('allow_registration','0','auth','2026-10-18 08:15:36'),
('author_bio','Engineer & writer interested in agentic AI, operating systems, and thoughtful software design.','author','2026-10-17 17:18:36'),
('author_name','Ronak Rathod','author','2026-10-17 17:18:36'),
('default_theme','system','appearance','2026-10-17 17:18:36'),
('posts_per_page','6','reading','2026-10-17 17:18:36'),
('site_description','WordWise is an independent, minimalist blogging platform dedicated to thoughtful essays on programming, technology, and design.','general','2026-10-17 17:18:36'),
('site_name','WordWise','general','2026-10-17 17:18:36'),
('site_tagline','A minimal editorial journal for tech, ideas, and craft.','general','2026-10-17 17:18:36'),
('social_github','https://github.com/realronak/WordWise','social','2026-10-17 17:18:36'),
('social_linkedin','https://linkedin.com','social','2026-10-17 17:18:36'),
('social_twitter','https://twitter.com','social','2026-10-17 17:18:36');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(100) NOT NULL,
  `tag_slug` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `tag_name` (`tag_name`),
  UNIQUE KEY `tag_slug` (`tag_slug`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES
(1,'architecture','architecture','2026-10-17 17:18:36'),
(2,'linux','linux','2026-10-17 17:18:36'),
(3,'php','php','2026-10-17 17:18:36'),
(4,'Artificial Intelligence','artificial-intelligence','2026-10-17 17:18:36'),
(5,'JavaScript','javascript','2026-10-17 17:18:36'),
(6,'minimalism','minimalism','2026-10-17 17:18:36'),
(7,'typography','typography','2026-10-17 17:18:36'),
(11,'ai','ai','2026-10-17 17:32:28'),
(15,'web-craft','web-craft','2026-10-18 08:13:14'),
(16,'engineering-philosophy','engineering-philosophy','2026-10-18 08:13:14'),
(18,'backend','backend','2026-10-18 08:13:14'),
(19,'performance','performance','2026-10-18 08:13:14'),
(20,'mariadb','mariadb','2026-10-18 08:13:14'),
(21,'optimization','optimization','2026-10-18 08:13:14'),
(23,'design','design','2026-10-18 08:13:14'),
(24,'jetbrains-mono','jetbrains-mono','2026-10-18 08:13:14'),
(25,'typewriter','typewriter','2026-10-18 08:13:14'),
(26,'aesthetics','aesthetics','2026-10-18 08:13:14'),
(28,'autonomous-agents','autonomous-agents','2026-10-18 08:13:14'),
(29,'prompt-engineering','prompt-engineering','2026-10-18 08:13:14'),
(30,'systems','systems','2026-10-18 08:13:14'),
(31,'automation','automation','2026-10-18 08:13:14'),
(33,'omarchy','omarchy','2026-10-18 08:13:14'),
(34,'arch-linux','arch-linux','2026-10-18 08:13:14'),
(35,'self-hosting','self-hosting','2026-10-18 08:13:14'),
(36,'devops','devops','2026-10-18 08:13:14'),
(37,'apache','apache','2026-10-18 08:13:14');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_providers`
--

DROP TABLE IF EXISTS `user_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `provider` varchar(50) NOT NULL,
  `provider_user_id` varchar(255) NOT NULL,
  `provider_email` varchar(100) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_uid` (`provider`,`provider_user_id`),
  UNIQUE KEY `uk_user_provider` (`user_id`,`provider`),
  CONSTRAINT `1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_providers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_providers` WRITE;
/*!40000 ALTER TABLE `user_providers` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_providers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `verification_token` varchar(100) DEFAULT NULL,
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_expires_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `user_role` enum('admin','editor','author','reader') NOT NULL DEFAULT 'author',
  `user_image` varchar(255) DEFAULT 'images/favicon.png',
  `bio` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_verification_token` (`verification_token`),
  KEY `idx_reset_token` (`password_reset_token`),
  KEY `idx_remember_token` (`remember_token`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'admin','admin@wordwise.local','2026-10-18 07:22:44',NULL,NULL,NULL,'01cda7f85a7af3e6800c8ebafb5a9918d88e50b9655bf59a285f1b4baa4567ae','$2y$12$c1u7bHXQDms0tJCxCnvnwemlwiGznd9l87Dh3lU6Kxy6Tbe7oBNt6','Ronak Rathod','admin','images/favicon.png','Founder and Lead Editor at WordWise publication.','active','2026-10-17 17:18:36','2026-10-18 08:17:53');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-10-18 13:54:16
