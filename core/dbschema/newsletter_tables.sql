-- Newsletter System Database Tables
-- Creates tables for newsletter campaigns, queue management, and tracking

-- Newsletter Campaigns Table
CREATE TABLE IF NOT EXISTS `bg_newsletter_campaigns` (
  `campaign_id` bigint NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` longtext NOT NULL,
  `cta_category` varchar(100) DEFAULT NULL,
  `recipient_criteria` JSON DEFAULT NULL,
  `send_dt` datetime DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `created_by` bigint DEFAULT NULL,
  `create_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modify_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `queued_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`campaign_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_send_dt` (`send_dt`),
  INDEX `idx_create_dt` (`create_dt`),
  INDEX `idx_recipient_criteria` ((CAST(recipient_criteria AS CHAR(100))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter Queue Table (for batch processing)
CREATE TABLE IF NOT EXISTS `bg_newsletter_queue` (
  `queue_id` bigint NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `scheduled_dt` datetime NOT NULL,
  `processed_dt` datetime DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `create_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modify_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`queue_id`),
  INDEX `idx_campaign_id` (`campaign_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status_scheduled` (`status`, `scheduled_dt`),
  INDEX `idx_create_dt` (`create_dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter Events Table (for tracking opens, clicks, etc.)
CREATE TABLE IF NOT EXISTS `bg_newsletter_events` (
  `event_id` bigint NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `queue_id` bigint DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `extra` JSON DEFAULT NULL,
  PRIMARY KEY (`event_id`),
  INDEX `idx_campaign_id` (`campaign_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_queue_id` (`queue_id`),
  INDEX `idx_event_type` (`event_type`),
  INDEX `idx_event_dt` (`event_dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter Unsubscribes Table
CREATE TABLE IF NOT EXISTS `bg_newsletter_unsubscribes` (
  `unsubscribe_id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `email` varchar(255) NOT NULL,
  `campaign_id` bigint DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `unsubscribe_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`unsubscribe_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_campaign_id` (`campaign_id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;