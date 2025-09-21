-- Partner Applications Table
-- Stores business partner applications for Birthday Gold

CREATE TABLE IF NOT EXISTS `bg_partner_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_name` varchar(255) NOT NULL,
  `business_type` varchar(50) NOT NULL,
  `contact_name` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `locations` varchar(20) DEFAULT '1',
  `website` varchar(255) DEFAULT NULL,
  `birthday_offer` text NOT NULL,
  `additional_info` text,
  `status` enum('pending','reviewing','approved','rejected','active') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_notes` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_business_type` (`business_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_contact_email` (`contact_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key for reviewer if users table exists
-- ALTER TABLE `bg_partner_applications` 
-- ADD CONSTRAINT `fk_partner_reviewer` 
-- FOREIGN KEY (`reviewed_by`) REFERENCES `bg_users`(`user_id`) ON DELETE SET NULL;