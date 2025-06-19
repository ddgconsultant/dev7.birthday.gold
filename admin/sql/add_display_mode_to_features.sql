-- Add display_mode column to bg_product_features table
-- This column controls how features are displayed in the UI
-- Values: 'show' (default), 'hide', 'admin_only'

ALTER TABLE `bg_product_features` 
ADD COLUMN `display_mode` VARCHAR(20) DEFAULT 'show' 
AFTER `status`,
ADD INDEX `idx_display_mode` (`display_mode`);

-- Update existing features that should not be displayed to users
-- For example, internal features like 'allow_promos'
UPDATE `bg_product_features` 
SET `display_mode` = 'hide' 
WHERE `name` IN ('allow_promos', 'account_verification', 'redirect_url');

-- Admin-only features that should be visible in admin but not signup
UPDATE `bg_product_features` 
SET `display_mode` = 'admin_only' 
WHERE `name` IN ('display_grouping', 'display_grouping_status');

-- Note: All other features default to 'show' for backward compatibility