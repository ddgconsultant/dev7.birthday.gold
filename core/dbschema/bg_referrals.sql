-- bg_referrals table schema
-- This table tracks user referrals for the Birthday Gold platform

CREATE TABLE IF NOT EXISTS `bg_referrals` (
  `referral_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'The user who made the referral',
  `referred_user_id` int(11) DEFAULT NULL COMMENT 'The new user who was referred',
  `referral_code` varchar(50) DEFAULT NULL COMMENT 'Unique referral code used',
  `referral_email` varchar(255) DEFAULT NULL COMMENT 'Email of the person referred',
  `referral_status` enum('pending','completed','expired','cancelled') DEFAULT 'pending',
  `referral_type` varchar(50) DEFAULT 'standard' COMMENT 'Type of referral campaign',
  `reward_given` tinyint(1) DEFAULT 0 COMMENT 'Whether reward was given to referrer',
  `reward_amount` decimal(10,2) DEFAULT NULL COMMENT 'Amount or value of reward',
  `reward_type` varchar(50) DEFAULT NULL COMMENT 'Type of reward (credit, discount, etc)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of referral',
  `user_agent` text DEFAULT NULL COMMENT 'Browser user agent',
  `source` varchar(100) DEFAULT NULL COMMENT 'Source of referral (email, social, etc)',
  `campaign_id` int(11) DEFAULT NULL COMMENT 'Marketing campaign ID if applicable',
  `notes` text DEFAULT NULL,
  `create_dt` datetime DEFAULT current_timestamp(),
  `modify_dt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `complete_dt` datetime DEFAULT NULL COMMENT 'When referral was completed',
  PRIMARY KEY (`referral_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_referred_user_id` (`referred_user_id`),
  KEY `idx_referral_code` (`referral_code`),
  KEY `idx_referral_email` (`referral_email`),
  KEY `idx_status` (`referral_status`),
  KEY `idx_create_dt` (`create_dt`),
  KEY `idx_campaign` (`campaign_id`),
  CONSTRAINT `fk_referrals_user` FOREIGN KEY (`user_id`) REFERENCES `bg_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_referrals_referred_user` FOREIGN KEY (`referred_user_id`) REFERENCES `bg_users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for performance
CREATE INDEX idx_referrals_composite ON bg_referrals(user_id, referral_status, create_dt);

-- Sample data structure for reference
-- INSERT INTO bg_referrals (user_id, referral_email, referral_code, source) 
-- VALUES (123, 'friend@example.com', 'REF123ABC', 'email');