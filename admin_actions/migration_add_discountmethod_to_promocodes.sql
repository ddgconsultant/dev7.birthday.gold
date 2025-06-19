-- Migration: Add discountmethod column to bg_promocodes table
-- Date: 2025-06-19
-- Purpose: Support different discount types (percentage, amount)

-- Add discountmethod column if it doesn't exist
ALTER TABLE `bg_promocodes` 
ADD COLUMN IF NOT EXISTS `discountmethod` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT 'percentage' 
AFTER `description`;

-- Update existing promo codes to have a discount method based on their current usage
-- If amount is 0-100, assume it's a percentage, otherwise assume it's cents
UPDATE `bg_promocodes` 
SET `discountmethod` = CASE 
    WHEN `amount` <= 100 THEN 'percentage'
    ELSE 'amount'
END
WHERE `discountmethod` IS NULL;

-- Add index on code for faster lookups
ALTER TABLE `bg_promocodes` ADD INDEX IF NOT EXISTS `idx_code` (`code`);

-- Add index on status and dates for efficient querying
ALTER TABLE `bg_promocodes` ADD INDEX IF NOT EXISTS `idx_status_dates` (`status`, `start_dt`, `end_dt`);