-- Birthday Gold Social Module Database Schema (SIMPLIFIED)
-- Created: 2025-01-31
-- Description: Consolidated tables for social networking features

-- ============================================
-- POSTS TABLE (includes all content types)
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_posts` (
  `post_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `parent_post_id` bigint(20) DEFAULT NULL,  -- For comments/replies (NULL = main post)
  `post_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'post',  -- post, comment, share
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `media_urls` json DEFAULT NULL,  -- Array of media URLs with metadata
  `hashtags` json DEFAULT NULL,    -- Array of hashtags
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visibility` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',  -- public, friends, private
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',  -- active, deleted, hidden, flagged
  `like_count` bigint(20) NOT NULL DEFAULT '0',  -- Denormalized for performance
  `comment_count` bigint(20) NOT NULL DEFAULT '0',  -- Denormalized for performance
  `share_count` bigint(20) NOT NULL DEFAULT '0',  -- Denormalized for performance
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`post_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_parent_post_id` (`parent_post_id`),
  KEY `idx_post_type` (`post_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_type_status` (`user_id`,`post_type`,`status`),
  FULLTEXT KEY `idx_content_fulltext` (`content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INTERACTIONS TABLE (likes, bookmarks, etc.)
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_interactions` (
  `interaction_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `interaction_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,  -- like, bookmark, share, report
  `metadata` json DEFAULT NULL,  -- Extra data (e.g., report reason, share text)
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',  -- active, inactive
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`interaction_id`),
  UNIQUE KEY `idx_user_post_type` (`user_id`,`post_id`,`interaction_type`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_interaction_type` (`interaction_type`),
  KEY `idx_status` (`status`),
  KEY `idx_post_type_status` (`post_id`,`interaction_type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER RELATIONSHIPS TABLE (follows, blocks)
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_relationships` (
  `relationship_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,  -- The user initiating the relationship
  `target_user_id` bigint(20) NOT NULL,  -- The target user
  `relationship_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,  -- follow, block, mute
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',  -- active, inactive
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`relationship_id`),
  UNIQUE KEY `idx_user_target_type` (`user_id`,`target_user_id`,`relationship_type`),
  KEY `idx_target_user_id` (`target_user_id`),
  KEY `idx_relationship_type` (`relationship_type`),
  KEY `idx_status` (`status`),
  KEY `idx_target_type_status` (`target_user_id`,`relationship_type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_notifications` (
  `notification_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,  -- Recipient
  `from_user_id` bigint(20) NOT NULL,  -- Who triggered it
  `notification_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,  -- like, comment, follow, etc.
  `related_id` bigint(20) DEFAULT NULL,  -- Post ID or other relevant ID
  `message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_user_read_created` (`user_id`,`is_read`,`created_at`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ACTIVITY LOG TABLE (optional - could use bg_sessiontracking)
-- ============================================
CREATE TABLE IF NOT EXISTS `bg_social_activity` (
  `activity_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `activity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `related_id` bigint(20) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`activity_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_user_created` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TRIGGERS FOR DENORMALIZED COUNTS
-- ============================================

DELIMITER $$

-- Update like_count when likes are added/removed
CREATE TRIGGER update_like_count_insert
AFTER INSERT ON bg_social_interactions
FOR EACH ROW
BEGIN
    IF NEW.interaction_type = 'like' AND NEW.status = 'active' THEN
        UPDATE bg_social_posts 
        SET like_count = like_count + 1 
        WHERE post_id = NEW.post_id;
    END IF;
END$$

CREATE TRIGGER update_like_count_update
AFTER UPDATE ON bg_social_interactions
FOR EACH ROW
BEGIN
    IF NEW.interaction_type = 'like' THEN
        IF OLD.status = 'active' AND NEW.status = 'inactive' THEN
            UPDATE bg_social_posts 
            SET like_count = GREATEST(0, like_count - 1)
            WHERE post_id = NEW.post_id;
        ELSEIF OLD.status = 'inactive' AND NEW.status = 'active' THEN
            UPDATE bg_social_posts 
            SET like_count = like_count + 1 
            WHERE post_id = NEW.post_id;
        END IF;
    END IF;
END$$

-- Update comment_count when comments are added
CREATE TRIGGER update_comment_count_insert
AFTER INSERT ON bg_social_posts
FOR EACH ROW
BEGIN
    IF NEW.post_type = 'comment' AND NEW.parent_post_id IS NOT NULL THEN
        UPDATE bg_social_posts 
        SET comment_count = comment_count + 1 
        WHERE post_id = NEW.parent_post_id;
    END IF;
END$$

-- Update share_count when shares are added
CREATE TRIGGER update_share_count_insert
AFTER INSERT ON bg_social_interactions
FOR EACH ROW
BEGIN
    IF NEW.interaction_type = 'share' AND NEW.status = 'active' THEN
        UPDATE bg_social_posts 
        SET share_count = share_count + 1 
        WHERE post_id = NEW.post_id;
    END IF;
END$$

DELIMITER ;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Additional composite indexes for common queries
ALTER TABLE `bg_social_posts` ADD INDEX `idx_parent_type_status` (`parent_post_id`,`post_type`,`status`);
ALTER TABLE `bg_social_interactions` ADD INDEX `idx_user_type_status` (`user_id`,`interaction_type`,`status`);
ALTER TABLE `bg_social_relationships` ADD INDEX `idx_user_type_status` (`user_id`,`relationship_type`,`status`);

-- ============================================
-- VIEWS FOR COMMON QUERIES (Optional)
-- ============================================

-- Comprehensive post view with user info and interaction counts
CREATE OR REPLACE VIEW v_social_posts_full AS
SELECT 
    p.post_id,
    p.user_id,
    p.parent_post_id,
    p.post_type,
    p.content,
    p.media_urls,
    p.hashtags,
    p.location,
    p.visibility,
    p.status,
    p.like_count,
    p.comment_count,
    p.share_count,
    p.created_at,
    p.updated_at,
    u.username,
    u.first_name,
    u.last_name,
    -- Get avatar from bg_user_attributes where it's stored as profile_image
    -- Falls back to generated avatar if not found
    COALESCE(
        (SELECT description FROM bg_user_attributes 
         WHERE user_id = u.user_id 
         AND type = 'profile_image' 
         AND name = 'avatar' 
         AND status = 'active' 
         AND category = 'primary'
         LIMIT 1),
        CONCAT('/avatars/', u.user_id, '.png')
    ) as avatar_url,
    u.email,
    CONCAT(u.first_name, ' ', u.last_name) as full_name,
    -- Parent post info (for shares/comments)
    parent.content as parent_content,
    parent_user.username as parent_username,
    -- Calculate time ago (for recent posts)
    CASE 
        WHEN p.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'Just now'
        WHEN p.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, p.created_at, NOW()), 'h ago')
        WHEN p.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN CONCAT(TIMESTAMPDIFF(DAY, p.created_at, NOW()), 'd ago')
        ELSE DATE_FORMAT(p.created_at, '%b %d, %Y')
    END as time_ago
FROM bg_social_posts p
JOIN bg_users u ON p.user_id = u.user_id
LEFT JOIN bg_social_posts parent ON p.parent_post_id = parent.post_id
LEFT JOIN bg_users parent_user ON parent.user_id = parent_user.user_id;

-- View for user's personalized feed (posts from people they follow)
CREATE OR REPLACE VIEW v_social_feed_following AS
SELECT 
    pf.*,
    -- Check if current session user has interacted
    -- (Note: You'll need to pass session user_id in WHERE clause when querying)
    EXISTS(
        SELECT 1 FROM bg_social_interactions i 
        WHERE i.post_id = pf.post_id 
        AND i.interaction_type = 'like' 
        AND i.status = 'active'
    ) as has_likes,
    EXISTS(
        SELECT 1 FROM bg_social_interactions i 
        WHERE i.post_id = pf.post_id 
        AND i.interaction_type = 'bookmark' 
        AND i.status = 'active'
    ) as has_bookmarks
FROM v_social_posts_full pf
WHERE pf.post_type = 'post' 
    AND pf.status = 'active'
    AND (pf.visibility = 'public' OR pf.visibility = 'friends');

-- View for trending posts (high engagement in last 24-48 hours)
CREATE OR REPLACE VIEW v_social_trending AS
SELECT 
    p.*,
    (p.like_count * 3 + p.comment_count * 2 + p.share_count * 4) as engagement_score,
    -- Recent engagement weight
    (SELECT COUNT(*) FROM bg_social_interactions i 
     WHERE i.post_id = p.post_id 
     AND i.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as recent_interactions
FROM v_social_posts_full p
WHERE p.post_type = 'post'
    AND p.status = 'active'
    AND p.visibility = 'public'
    AND p.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY engagement_score DESC, recent_interactions DESC;

-- View for user profile stats
CREATE OR REPLACE VIEW v_social_user_stats AS
SELECT 
    u.user_id,
    u.username,
    u.first_name,
    u.last_name,
    -- Get avatar from bg_user_attributes where it's stored
    COALESCE(
        (SELECT description FROM bg_user_attributes 
         WHERE user_id = u.user_id 
         AND type = 'profile_image' 
         AND name = 'avatar' 
         AND status = 'active' 
         AND category = 'primary'
         LIMIT 1),
        CONCAT('/avatars/', u.user_id, '.png')
    ) as avatar_url,
    (SELECT COUNT(*) FROM bg_social_posts WHERE user_id = u.user_id AND status = 'active' AND post_type = 'post') as post_count,
    (SELECT COUNT(*) FROM bg_social_posts WHERE user_id = u.user_id AND status = 'active' AND post_type = 'comment') as comment_count,
    (SELECT COUNT(*) FROM bg_social_relationships WHERE target_user_id = u.user_id AND relationship_type = 'follow' AND status = 'active') as follower_count,
    (SELECT COUNT(*) FROM bg_social_relationships WHERE user_id = u.user_id AND relationship_type = 'follow' AND status = 'active') as following_count,
    (SELECT SUM(like_count) FROM bg_social_posts WHERE user_id = u.user_id AND status = 'active') as total_likes_received,
    u.create_dt as member_since
FROM bg_users u
WHERE u.status = 'active';

-- ============================================
-- NOTES ON CONSOLIDATION
-- ============================================
-- 1. Posts and comments merged into single table (using parent_post_id)
-- 2. All interactions (likes, bookmarks, shares, reports) in one table
-- 3. All user relationships (follows, blocks, mutes) in one table  
-- 4. Denormalized counts in posts table with triggers for performance
-- 5. JSON fields for flexible metadata storage
-- 6. Reduced from 14 tables to 5 tables

-- ============================================
-- PERFORMANCE OPTIMIZATION RECOMMENDATIONS
-- ============================================
-- 1. Avatar Performance: Currently avatars are stored in bg_user_attributes table
--    For better performance in social feeds, consider:
--    a) Adding index: ALTER TABLE bg_user_attributes ADD INDEX idx_user_avatar (user_id, type, name, status, category);
--    b) OR caching avatar URL in bg_users.avatar field when uploaded:
--       UPDATE bg_users SET avatar = (SELECT description FROM bg_user_attributes 
--       WHERE user_id = X AND type = 'profile_image' AND name = 'avatar' 
--       AND status = 'active' AND category = 'primary' LIMIT 1)
--
-- 2. Consider adding these indexes for better performance:
--    ALTER TABLE bg_social_posts ADD INDEX idx_visibility_status_created (visibility, status, created_at DESC);
--    ALTER TABLE bg_user_attributes ADD INDEX idx_profile_images (user_id, type, name, status, category);
--
-- 3. For heavy social activity, consider partitioning bg_social_posts by created_at

-- ============================================
-- BG_CONFIG REFERENCE VALUES
-- ============================================
-- These values should be added to bg_config for reference:

-- Post Types
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`) VALUES 
('social_post_type', 'post', 'Main post content'),
('social_post_type', 'comment', 'Comment on a post'),
('social_post_type', 'share', 'Shared post');

-- Visibility Levels
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`) VALUES 
('social_visibility', 'public', 'Visible to everyone'),
('social_visibility', 'friends', 'Visible to friends/followers only'),
('social_visibility', 'private', 'Visible only to poster');

-- Status Types
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`) VALUES 
('social_status', 'active', 'Active and visible'),
('social_status', 'deleted', 'Soft deleted by user'),
('social_status', 'hidden', 'Hidden from public view'),
('social_status', 'flagged', 'Flagged for moderation'),
('social_status', 'inactive', 'Inactive/disabled');

-- Interaction Types
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`) VALUES 
('social_interaction', 'like', 'Like a post'),
('social_interaction', 'bookmark', 'Save for later'),
('social_interaction', 'share', 'Share to timeline'),
('social_interaction', 'report', 'Report content');

-- Relationship Types
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`) VALUES 
('social_relationship', 'follow', 'Following user'),
('social_relationship', 'block', 'Blocked user'),
('social_relationship', 'mute', 'Muted user');

-- Notification Types
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`) VALUES 
('social_notification', 'like', 'Someone liked your post'),
('social_notification', 'comment', 'Someone commented on your post'),
('social_notification', 'reply', 'Someone replied to your comment'),
('social_notification', 'follow', 'Someone followed you'),
('social_notification', 'share', 'Someone shared your post'),
('social_notification', 'mention', 'Someone mentioned you'),
('social_notification', 'comment_like', 'Someone liked your comment');

-- ============================================
-- MIGRATION NOTES
-- ============================================
-- 1. All ENUMs replaced with VARCHAR fields
-- 2. Foreign keys removed for flexibility
-- 3. Reference values stored in bg_config
-- 4. If you need hashtag trending, you can:
--    a. Extract from JSON field with queries
--    b. Use a materialized view
--    c. Add a simple bg_social_hashtags table later if needed