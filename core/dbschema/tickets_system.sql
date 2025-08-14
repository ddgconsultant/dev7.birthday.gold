-- Universal Ticket System Tables
-- Supports IT, Member Support, Legal, and any other ticket types

-- Main Tickets Table
CREATE TABLE IF NOT EXISTS `bg_tickets` (
  `ticket_id` bigint NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(30) NOT NULL,
  `ticket_type` varchar(50) NOT NULL COMMENT 'it_support, member_support, legal_review, hardware_request, bug_report, feature_request, etc.',
  `ticket_category` varchar(50) DEFAULT NULL COMMENT 'Sub-category within type',
  `user_id` bigint NOT NULL COMMENT 'User who created the ticket',
  `assigned_to` bigint DEFAULT NULL COMMENT 'User ticket is assigned to',
  `priority` enum('low','normal','high','critical') NOT NULL DEFAULT 'normal',
  `status` enum('open','in_progress','pending','resolved','closed','cancelled') NOT NULL DEFAULT 'open',
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `resolution` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL COMMENT 'Staff-only notes',
  `related_id` bigint DEFAULT NULL COMMENT 'Related record ID (company_id, enrollment_id, etc.)',
  `related_type` varchar(50) DEFAULT NULL COMMENT 'Type of related record',
  `created_dt` datetime NOT NULL,
  `modified_dt` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `assigned_dt` datetime DEFAULT NULL,
  `resolved_dt` datetime DEFAULT NULL,
  `closed_dt` datetime DEFAULT NULL,
  `due_dt` datetime DEFAULT NULL COMMENT 'SLA or deadline',
  `metadata` JSON DEFAULT NULL COMMENT 'Type-specific data in JSON format',
  PRIMARY KEY (`ticket_id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_ticket_type` (`ticket_type`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created` (`created_dt`),
  KEY `idx_type_status` (`ticket_type`, `status`),
  KEY `idx_related` (`related_type`, `related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Universal ticket system for all support types';

-- Ticket Comments/Updates
CREATE TABLE IF NOT EXISTS `bg_ticket_comments` (
  `comment_id` bigint NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `comment_type` enum('comment','status_change','assignment','resolution','internal_note') DEFAULT 'comment',
  `comment` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0 COMMENT 'Staff-only visibility',
  `attachments` JSON DEFAULT NULL COMMENT 'File attachment references',
  `created_dt` datetime NOT NULL,
  `modified_dt` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created` (`created_dt`),
  CONSTRAINT `fk_ticket_comment` FOREIGN KEY (`ticket_id`) REFERENCES `bg_tickets` (`ticket_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Comments and updates for all ticket types';

-- Ticket Attachments
CREATE TABLE IF NOT EXISTS `bg_ticket_attachments` (
  `attachment_id` bigint NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint NOT NULL,
  `comment_id` bigint DEFAULT NULL,
  `user_id` bigint NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `cdn_url` varchar(500) DEFAULT NULL,
  `uploaded_dt` datetime NOT NULL,
  PRIMARY KEY (`attachment_id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_comment_id` (`comment_id`),
  CONSTRAINT `fk_ticket_attachment` FOREIGN KEY (`ticket_id`) REFERENCES `bg_tickets` (`ticket_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ticket Tags (for categorization and searching)
CREATE TABLE IF NOT EXISTS `bg_ticket_tags` (
  `tag_id` bigint NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint NOT NULL,
  `tag_name` varchar(50) NOT NULL,
  `created_dt` datetime NOT NULL,
  PRIMARY KEY (`tag_id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_tag_name` (`tag_name`),
  UNIQUE KEY `unique_ticket_tag` (`ticket_id`, `tag_name`),
  CONSTRAINT `fk_ticket_tag` FOREIGN KEY (`ticket_id`) REFERENCES `bg_tickets` (`ticket_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ticket Templates (for common issues)
CREATE TABLE IF NOT EXISTS `bg_ticket_templates` (
  `template_id` bigint NOT NULL AUTO_INCREMENT,
  `ticket_type` varchar(50) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `priority` varchar(20) DEFAULT NULL,
  `tags` JSON DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_dt` datetime NOT NULL,
  PRIMARY KEY (`template_id`),
  KEY `idx_ticket_type` (`ticket_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ticket SLA Rules (Service Level Agreements)
CREATE TABLE IF NOT EXISTS `bg_ticket_sla` (
  `sla_id` bigint NOT NULL AUTO_INCREMENT,
  `ticket_type` varchar(50) NOT NULL,
  `priority` varchar(20) NOT NULL,
  `response_time_minutes` int DEFAULT NULL COMMENT 'Expected first response time',
  `resolution_time_minutes` int DEFAULT NULL COMMENT 'Expected resolution time',
  `escalation_time_minutes` int DEFAULT NULL COMMENT 'When to escalate',
  `escalate_to` varchar(100) DEFAULT NULL COMMENT 'Role or user to escalate to',
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`sla_id`),
  UNIQUE KEY `unique_type_priority` (`ticket_type`, `priority`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample SLA Rules
INSERT INTO bg_ticket_sla (ticket_type, priority, response_time_minutes, resolution_time_minutes, escalation_time_minutes, escalate_to) VALUES
('it_support', 'critical', 15, 240, 60, '@Richard'),
('it_support', 'high', 60, 480, 240, '@Richard'),
('it_support', 'normal', 240, 1440, 480, NULL),
('it_support', 'low', 1440, 2880, NULL, NULL),
('member_support', 'critical', 30, 120, 60, '@Support_Manager'),
('member_support', 'high', 120, 480, 240, '@Support_Manager'),
('member_support', 'normal', 480, 1440, NULL, NULL),
('member_support', 'low', 1440, 4320, NULL, NULL),
('legal_review', 'high', 1440, 10080, 4320, '@Liz'),
('legal_review', 'normal', 2880, 20160, NULL, NULL);

-- Views for common queries
CREATE OR REPLACE VIEW v_open_tickets AS
SELECT 
    t.ticket_id,
    t.ticket_number,
    t.ticket_type,
    t.priority,
    t.status,
    t.subject,
    t.created_dt,
    t.due_dt,
    u.profile_username as created_by,
    a.profile_username as assigned_to_username,
    TIMESTAMPDIFF(HOUR, t.created_dt, NOW()) as hours_open
FROM bg_tickets t
LEFT JOIN bg_users u ON t.user_id = u.user_id
LEFT JOIN bg_users a ON t.assigned_to = a.user_id
WHERE t.status IN ('open', 'in_progress', 'pending')
ORDER BY 
    FIELD(t.priority, 'critical', 'high', 'normal', 'low'),
    t.created_dt ASC;

CREATE OR REPLACE VIEW v_ticket_stats AS
SELECT 
    ticket_type,
    COUNT(*) as total_tickets,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
    AVG(TIMESTAMPDIFF(HOUR, created_dt, COALESCE(resolved_dt, NOW()))) as avg_resolution_hours
FROM bg_tickets
GROUP BY ticket_type;