-- Table for storing AI-generated message summaries
CREATE TABLE IF NOT EXISTS `bg_user_message_summaries` (
  `summary_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `summary_date` date NOT NULL COMMENT 'The date this summary covers',
  `summary_type` enum('daily','company') NOT NULL DEFAULT 'daily',
  `company_id` int(11) DEFAULT NULL COMMENT 'For company-type summaries',
  `message_count` int(11) NOT NULL DEFAULT 0,
  `message_ids` text COMMENT 'JSON array of message IDs included',
  `companies_included` text COMMENT 'JSON array of company IDs (for daily summaries)',
  `ai_summary` text NOT NULL COMMENT 'The AI-generated summary text',
  `offer_details` text COMMENT 'JSON array of specific offers extracted',
  `processing_status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `processing_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `processed_by` varchar(50) DEFAULT NULL COMMENT 'batch or realtime',
  PRIMARY KEY (`summary_id`),
  UNIQUE KEY `unique_daily_summary` (`user_id`, `summary_date`, `summary_type`, `company_id`),
  KEY `idx_user_date` (`user_id`, `summary_date`),
  KEY `idx_status` (`processing_status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores AI-generated summaries of user messages';

-- Index for efficient batch processing
ALTER TABLE `bg_user_message_summaries` ADD INDEX `idx_batch_processing` (`processing_status`, `created_at`);