-- IT Support Ticket System Tables

-- Support Tickets Table
CREATE TABLE IF NOT EXISTS `bg_support_tickets` (
  `ticket_id` bigint NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(20) NOT NULL,
  `user_id` bigint NOT NULL,
  `ticket_type` varchar(50) NOT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'open',
  `assigned_to` bigint DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `resolution` text,
  `created_dt` datetime NOT NULL,
  `modified_dt` datetime DEFAULT NULL,
  `resolved_dt` datetime DEFAULT NULL,
  `closed_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`ticket_id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created` (`created_dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ticket Comments/Updates
CREATE TABLE IF NOT EXISTS `bg_support_ticket_comments` (
  `comment_id` bigint NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `comment` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_dt` datetime NOT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hardware Requests Table
CREATE TABLE IF NOT EXISTS `bg_hardware_requests` (
  `request_id` bigint NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint DEFAULT NULL,
  `user_id` bigint NOT NULL,
  `hardware_type` varchar(50) NOT NULL,
  `reason` varchar(100) NOT NULL,
  `notes` text,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `approved_by` bigint DEFAULT NULL,
  `approved_dt` datetime DEFAULT NULL,
  `fulfilled_dt` datetime DEFAULT NULL,
  `created_dt` datetime NOT NULL,
  PRIMARY KEY (`request_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;