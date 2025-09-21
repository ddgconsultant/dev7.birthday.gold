-- Table for logging AI content generation
CREATE TABLE IF NOT EXISTS `bg_ai_generations` (
  `generation_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `generation_type` varchar(50) NOT NULL,
  `prompt` text,
  `response` text,
  `model` varchar(50) DEFAULT 'gpt-4o-mini',
  `tokens_used` int(11) DEFAULT NULL,
  `created_dt` datetime NOT NULL,
  PRIMARY KEY (`generation_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_generation_type` (`generation_type`),
  KEY `idx_created_dt` (`created_dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;