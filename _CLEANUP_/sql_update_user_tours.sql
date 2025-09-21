-- Add force_location column to bg_user_tours table
ALTER TABLE `bg_user_tours` 
ADD COLUMN `force_location` TINYINT(1) DEFAULT 0 COMMENT 'If 1, keeps this location even when starting location changes' AFTER `location_id`;

-- Add index for faster lookups
ALTER TABLE `bg_user_tours` 
ADD INDEX `idx_user_date_company` (`user_id`, `calendar_dt`, `company_id`);