-- Newsletter System Database Tables
-- Creates tables for newsletter campaigns, queue management, and tracking

-- Newsletter Campaigns Table
CREATE TABLE IF NOT EXISTS `bg_newsletter_campaigns` (
  `campaign_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` longtext NOT NULL,
  `cta_category` varchar(100) DEFAULT NULL,
  `recipient_criteria` JSON DEFAULT NULL,
  `send_dt` datetime DEFAULT NULL,
  `status` enum('draft','scheduled','queued','sending','completed','paused','cancelled') NOT NULL DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
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
  `queue_id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `scheduled_dt` datetime NOT NULL,
  `processed_dt` datetime DEFAULT NULL,
  `status` enum('pending','processing','sent','error') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `create_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modify_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`queue_id`),
  KEY `fk_newsletter_queue_campaign` (`campaign_id`),
  KEY `fk_newsletter_queue_user` (`user_id`),
  INDEX `idx_status_scheduled` (`status`, `scheduled_dt`),
  INDEX `idx_create_dt` (`create_dt`),
  CONSTRAINT `fk_newsletter_queue_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `bg_newsletter_campaigns` (`campaign_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_newsletter_queue_user` FOREIGN KEY (`user_id`) REFERENCES `bg_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter Events Table (for tracking opens, clicks, etc.)
CREATE TABLE IF NOT EXISTS `bg_newsletter_events` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `queue_id` int(11) DEFAULT NULL,
  `event_type` enum('sent','opened','clicked','bounced','unsubscribed','error') NOT NULL,
  `event_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `extra` JSON DEFAULT NULL,
  PRIMARY KEY (`event_id`),
  KEY `fk_newsletter_events_campaign` (`campaign_id`),
  KEY `fk_newsletter_events_user` (`user_id`),
  KEY `fk_newsletter_events_queue` (`queue_id`),
  INDEX `idx_event_type` (`event_type`),
  INDEX `idx_event_dt` (`event_dt`),
  CONSTRAINT `fk_newsletter_events_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `bg_newsletter_campaigns` (`campaign_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_newsletter_events_user` FOREIGN KEY (`user_id`) REFERENCES `bg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_newsletter_events_queue` FOREIGN KEY (`queue_id`) REFERENCES `bg_newsletter_queue` (`queue_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter Unsubscribes Table
CREATE TABLE IF NOT EXISTS `bg_newsletter_unsubscribes` (
  `unsubscribe_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `unsubscribe_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('active','resubscribed') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`unsubscribe_id`),
  UNIQUE KEY `unique_user_active` (`user_id`, `status`),
  KEY `fk_newsletter_unsubscribes_user` (`user_id`),
  KEY `fk_newsletter_unsubscribes_campaign` (`campaign_id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`),
  CONSTRAINT `fk_newsletter_unsubscribes_user` FOREIGN KEY (`user_id`) REFERENCES `bg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_newsletter_unsubscribes_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `bg_newsletter_campaigns` (`campaign_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;