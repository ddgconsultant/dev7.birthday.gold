-- Table: bg_company_pages
-- Purpose: Store discovered pages from company websites with their interpreted content type
-- Created: 2025-01-31

CREATE TABLE IF NOT EXISTS `bg_company_pages` (
  `page_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `page_type` varchar(100) DEFAULT NULL COMMENT 'Interpreted content type: locations, hours, contact, signup, menu, about, careers, etc',
  `page_title` varchar(255) DEFAULT NULL COMMENT 'Page title from HTML',
  `meta_description` text DEFAULT NULL COMMENT 'Meta description tag content',
  `meta_keywords` text DEFAULT NULL COMMENT 'Meta keywords if present',
  `confidence_score` decimal(3,2) DEFAULT NULL COMMENT 'Confidence score for page_type interpretation (0.00-1.00)',
  `crawl_processor` varchar(50) DEFAULT NULL COMMENT 'Processor that discovered this page',
  `crawl_depth` tinyint(2) DEFAULT 0 COMMENT 'How many links deep from homepage',
  `parent_url` varchar(500) DEFAULT NULL COMMENT 'URL of the page that linked to this one',
  `status` enum('active','inactive','error') DEFAULT 'active',
  `create_dt` datetime DEFAULT NULL,
  `modify_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`page_id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_page_type` (`page_type`),
  KEY `idx_url` (`url`(255)),
  KEY `idx_company_type` (`company_id`, `page_type`),
  KEY `idx_crawl_processor` (`crawl_processor`),
  UNIQUE KEY `unique_company_url` (`company_id`, `url`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraint to bg_companies
ALTER TABLE `bg_company_pages` 
ADD CONSTRAINT `fk_pages_company` 
FOREIGN KEY (`company_id`) 
REFERENCES `bg_companies`(`company_id`) 
ON DELETE CASCADE;