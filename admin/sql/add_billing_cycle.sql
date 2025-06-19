-- Add billing_cycle column to bg_products table
ALTER TABLE `bg_products` 
ADD COLUMN `billing_cycle` VARCHAR(20) DEFAULT 'yearly' 
AFTER `price`,
ADD INDEX `idx_billing_cycle` (`billing_cycle`);

-- Update existing products based on common patterns
UPDATE `bg_products` 
SET `billing_cycle` = 'lifetime' 
WHERE `account_plan` LIKE '%lifetime%';

UPDATE `bg_products` 
SET `billing_cycle` = 'one_time' 
WHERE `account_type` = 'giftcertificate';

-- Note: You may want to review and update billing cycles for specific products
-- Possible values: 'one_time', 'monthly', 'yearly', 'lifetime'