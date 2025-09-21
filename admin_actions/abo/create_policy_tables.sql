-- Create tables for policy tracking and content storage

-- Table to track policy versions and changes
CREATE TABLE IF NOT EXISTS `bg_company_policies` (
  `policy_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `policy_type` varchar(50) NOT NULL COMMENT 'terms, privacy, cookies, refund, etc',
  `policy_name` varchar(255) NOT NULL,
  `content_hash` varchar(64) DEFAULT NULL COMMENT 'SHA256 hash of content',
  `version` int(11) NOT NULL DEFAULT 1,
  `status` enum('active','changed','archived','verified') NOT NULL DEFAULT 'active',
  `last_verified` datetime DEFAULT NULL,
  `create_dt` datetime NOT NULL,
  `modify_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`policy_id`),
  KEY `company_id` (`company_id`),
  KEY `policy_type` (`policy_type`),
  KEY `status` (`status`),
  KEY `content_hash` (`content_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to store actual policy content
CREATE TABLE IF NOT EXISTS `bg_company_policy_content` (
  `content_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `policy_type` varchar(50) NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_hash` varchar(64) NOT NULL COMMENT 'SHA256 hash for deduplication',
  `create_dt` datetime NOT NULL,
  PRIMARY KEY (`content_id`),
  UNIQUE KEY `unique_content_hash` (`content_hash`),
  KEY `company_id` (`company_id`),
  KEY `policy_type` (`policy_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
ALTER TABLE `bg_company_policies` 
ADD INDEX `company_policy_type` (`company_id`, `policy_type`, `status`);

-- Example queries:

-- Get current active policies for a company
-- SELECT * FROM bg_company_policies 
-- WHERE company_id = 6231 AND status = 'active';

-- Get policy history
-- SELECT * FROM bg_company_policies 
-- WHERE company_id = 6231 AND policy_type = 'terms' 
-- ORDER BY version DESC;

-- Get policy content
-- SELECT p.*, c.content 
-- FROM bg_company_policies p
-- JOIN bg_company_policy_content c ON p.content_hash = c.content_hash
-- WHERE p.policy_id = 123;