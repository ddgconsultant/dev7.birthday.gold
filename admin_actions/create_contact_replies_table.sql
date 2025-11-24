-- Migration Script: Create Contact Replies Table
-- This script creates the bg_contact_replies table for storing admin replies
-- to contact form messages.
--
-- Run this with: mysql -h [host] -u [user] -p [database] < create_contact_replies_table.sql

CREATE TABLE IF NOT EXISTS bg_contact_replies (
    reply_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    contact_event_id BIGINT NOT NULL COMMENT 'ID from bg_sessiontracking table',
    admin_user_id BIGINT NOT NULL COMMENT 'Admin user who sent the reply',
    admin_username VARCHAR(255) NOT NULL,
    reply_message LONGTEXT NOT NULL,
    recipient_email VARCHAR(255) DEFAULT NULL COMMENT 'Email address to send reply to',
    recipient_user_id BIGINT DEFAULT NULL COMMENT 'User ID if contact was from logged-in user',
    status ENUM('draft', 'sent', 'failed') DEFAULT 'draft',
    sent_via ENUM('email', 'notification', 'both', 'internal') DEFAULT 'internal',
    notification_id BIGINT DEFAULT NULL COMMENT 'ID from bg_user_notifications if sent as notification',
    email_status VARCHAR(50) DEFAULT NULL COMMENT 'Email sending status',
    create_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_dt DATETIME DEFAULT NULL,
    modify_dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    metadata TEXT DEFAULT NULL COMMENT 'JSON metadata for additional info',
    INDEX idx_contact_event (contact_event_id),
    INDEX idx_admin_user (admin_user_id),
    INDEX idx_recipient_user (recipient_user_id),
    INDEX idx_status (status),
    INDEX idx_create_dt (create_dt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Stores admin replies to contact form messages';
