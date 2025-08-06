-- Simple bg_referrals table schema
-- Minimal version for tracking referral counts

CREATE TABLE IF NOT EXISTS `bg_referrals` (
  `referral_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'The user who made the referral',
  `referred_email` varchar(255) DEFAULT NULL COMMENT 'Email of person referred',
  `status` enum('pending','completed') DEFAULT 'pending',
  `create_dt` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`referral_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- To execute this in your database:
-- 1. Run this SQL directly in your MySQL client
-- 2. Or use the command: mysql -u username -p birthday_gold_www < bg_referrals_simple.sql