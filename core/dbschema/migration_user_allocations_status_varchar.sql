-- Migration to change bg_user_allocations.status from ENUM to VARCHAR(32)
-- This provides more flexibility for status values
-- Date: 2025-08-03

ALTER TABLE `bg_user_allocations` 
MODIFY COLUMN `status` VARCHAR(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active';

-- Add index for status column for better query performance
ALTER TABLE `bg_user_allocations` ADD INDEX `idx_status` (`status`);