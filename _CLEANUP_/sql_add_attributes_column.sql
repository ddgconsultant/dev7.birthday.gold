-- Add attributes column to bg_user_tours table for storing JSON data
ALTER TABLE `bg_user_tours` 
ADD COLUMN `attributes` VARCHAR(2000) DEFAULT NULL COMMENT 'JSON array of additional attributes' AFTER `location_id`;

-- Example of what can be stored in attributes:
-- {"force_location": true, "custom_notes": "Customer prefers this location", "verified": true}