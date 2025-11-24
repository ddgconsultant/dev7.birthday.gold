-- ============================================================================
-- TABLE: bg_contact_replies
-- PURPOSE: Store admin replies to contact form submissions
-- NOTES: Run this with an account that has CREATE TABLE permissions
-- ============================================================================

CREATE TABLE IF NOT EXISTS `bg_contact_replies` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `contact_message_id` bigint NOT NULL COMMENT 'References bg_sessiontracking.id for the original contact message',
  `session_id` varchar(255) DEFAULT NULL COMMENT 'Session ID from original contact message',
  `reply_to_email` varchar(255) NOT NULL COMMENT 'Email address to send reply to',
  `reply_subject` varchar(500) DEFAULT NULL COMMENT 'Subject line of reply email',
  `reply_message` text NOT NULL COMMENT 'Body of reply message',
  `admin_user_id` bigint DEFAULT NULL COMMENT 'Admin user ID who created the reply',
  `admin_username` varchar(255) DEFAULT NULL COMMENT 'Admin username who created the reply',
  `status` varchar(32) NOT NULL DEFAULT 'draft' COMMENT 'Reply status: draft, sent, or failed',
  `email_sent_dt` datetime DEFAULT NULL COMMENT 'When email was successfully sent',
  `email_error` text DEFAULT NULL COMMENT 'Error message if email failed to send',
  `original_message_data` json DEFAULT NULL COMMENT 'Copy of original contact form data for context',
  `create_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When reply was created',
  `update_dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When reply was last updated',
  PRIMARY KEY (`id`),
  KEY `contact_message_id` (`contact_message_id`),
  KEY `session_id` (`session_id`),
  KEY `reply_to_email` (`reply_to_email`),
  KEY `status` (`status`),
  KEY `create_dt` (`create_dt`),
  KEY `admin_user_id` (`admin_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin replies to contact form submissions';
