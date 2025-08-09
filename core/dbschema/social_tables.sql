-- Birthday Gold Social Module Database Schema
-- Created: 2025-01-31
-- Description: Tables for social networking features

-- ============================================
-- USER POSTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_user_posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `media_type` enum('text','image','video','audio','mixed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `media_data` json DEFAULT NULL,
  `visibility` enum('public','friends','private') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `status` enum('active','deleted','hidden','flagged') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `hashtags` json DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`post_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_visibility` (`visibility`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_status_created` (`user_id`,`status`,`created_at`),
  FULLTEXT KEY `idx_content_fulltext` (`content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COMMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `comment_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','deleted','hidden','flagged') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_parent_comment_id` (`parent_comment_id`),
  KEY `idx_status` (`status`),
  KEY `idx_post_status_created` (`post_id`,`status`,`created_at`),
  CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) REFERENCES `bg_user_posts` (`post_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_parent` FOREIGN KEY (`parent_comment_id`) REFERENCES `bg_social_comments` (`comment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LIKES TABLE (Posts)
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_likes` (
  `like_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`like_id`),
  UNIQUE KEY `idx_post_user` (`post_id`,`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_likes_post` FOREIGN KEY (`post_id`) REFERENCES `bg_user_posts` (`post_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COMMENT LIKES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_comment_likes` (
  `like_id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`like_id`),
  UNIQUE KEY `idx_comment_user` (`comment_id`,`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_comment_likes_comment` FOREIGN KEY (`comment_id`) REFERENCES `bg_social_comments` (`comment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BOOKMARKS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_bookmarks` (
  `bookmark_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`bookmark_id`),
  UNIQUE KEY `idx_post_user` (`post_id`,`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_user_status_created` (`user_id`,`status`,`created_at`),
  CONSTRAINT `fk_bookmarks_post` FOREIGN KEY (`post_id`) REFERENCES `bg_user_posts` (`post_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SHARES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_shares` (
  `share_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `share_text` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `share_type` enum('timeline','message','external') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'timeline',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`share_id`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_shares_post` FOREIGN KEY (`post_id`) REFERENCES `bg_user_posts` (`post_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- FOLLOWS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_follows` (
  `follow_id` int(11) NOT NULL AUTO_INCREMENT,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `status` enum('active','inactive','blocked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`follow_id`),
  UNIQUE KEY `idx_follower_following` (`follower_id`,`following_id`),
  KEY `idx_following_id` (`following_id`),
  KEY `idx_status` (`status`),
  KEY `idx_following_status` (`following_id`,`status`),
  KEY `idx_follower_status` (`follower_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MEDIA TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_media` (
  `media_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `media_type` enum('image','video','audio','document') COLLATE utf8mb4_unicode_ci NOT NULL,
  `media_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `media_metadata` json DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT '0',
  `status` enum('active','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`media_id`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_status` (`status`),
  KEY `idx_post_status_order` (`post_id`,`status`,`display_order`),
  CONSTRAINT `fk_media_post` FOREIGN KEY (`post_id`) REFERENCES `bg_user_posts` (`post_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ACTIVITY LOG TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_activity` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`activity_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_created` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_type` enum('like','comment','reply','follow','share','mention','comment_like') COLLATE utf8mb4_unicode_ci NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `from_user_id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_from_user_id` (`from_user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_user_read_created` (`user_id`,`is_read`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- HASHTAGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_hashtags` (
  `hashtag_id` int(11) NOT NULL AUTO_INCREMENT,
  `hashtag` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usage_count` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`hashtag_id`),
  UNIQUE KEY `idx_hashtag` (`hashtag`),
  KEY `idx_usage_count` (`usage_count`),
  KEY `idx_last_used` (`last_used_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- POST HASHTAGS JUNCTION TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_post_hashtags` (
  `post_hashtag_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `hashtag_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_hashtag_id`),
  UNIQUE KEY `idx_post_hashtag` (`post_id`,`hashtag_id`),
  KEY `idx_hashtag_id` (`hashtag_id`),
  CONSTRAINT `fk_post_hashtags_post` FOREIGN KEY (`post_id`) REFERENCES `bg_user_posts` (`post_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_post_hashtags_hashtag` FOREIGN KEY (`hashtag_id`) REFERENCES `bg_social_hashtags` (`hashtag_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER BLOCKS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_blocks` (
  `block_id` int(11) NOT NULL AUTO_INCREMENT,
  `blocker_id` int(11) NOT NULL,
  `blocked_id` int(11) NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`block_id`),
  UNIQUE KEY `idx_blocker_blocked` (`blocker_id`,`blocked_id`),
  KEY `idx_blocked_id` (`blocked_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- REPORT/FLAG TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `reporter_id` int(11) NOT NULL,
  `content_type` enum('post','comment','user') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_id` int(11) NOT NULL,
  `reason` enum('spam','inappropriate','harassment','violence','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','reviewed','resolved','dismissed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  KEY `idx_reporter_id` (`reporter_id`),
  KEY `idx_content_type_id` (`content_type`,`content_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Additional composite indexes for common queries
ALTER TABLE `bg_user_posts` ADD INDEX `idx_user_visibility_status` (`user_id`,`visibility`,`status`);
ALTER TABLE `bg_social_comments` ADD INDEX `idx_post_parent_status` (`post_id`,`parent_comment_id`,`status`);
ALTER TABLE `bg_social_likes` ADD INDEX `idx_post_status_created` (`post_id`,`status`,`created_at`);
ALTER TABLE `bg_social_notifications` ADD INDEX `idx_user_type_read` (`user_id`,`notification_type`,`is_read`);

-- ============================================
-- SAMPLE DATA FOR TESTING (Optional)
-- ============================================

-- Sample admin post
-- INSERT INTO `bg_user_posts` (`user_id`, `content`, `media_type`, `visibility`) 
-- VALUES (1, 'Welcome to Birthday Gold Social! Share your birthday experiences and connect with others.', 'text', 'public');

-- ============================================
-- NOTES
-- ============================================
-- 1. All tables use utf8mb4_unicode_ci for full emoji support
-- 2. Foreign keys ensure referential integrity
-- 3. Indexes optimized for common query patterns
-- 4. Status fields allow soft deletes
-- 5. JSON fields for flexible metadata storage
-- 6. Fulltext index on post content for search functionality