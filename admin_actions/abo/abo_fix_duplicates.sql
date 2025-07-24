-- Remove duplicate AI Validation entries in bg_config
-- This keeps the entry with the lower config_id (first inserted)

-- First, identify duplicates
SELECT config_type, config_key, COUNT(*) as count
FROM bg_config 
WHERE config_type = 'automation_processor'
GROUP BY config_type, config_key
HAVING COUNT(*) > 1;

-- Delete duplicate entries, keeping the one with the lowest config_id
DELETE c1 FROM bg_config c1
INNER JOIN bg_config c2 
WHERE 
    c1.config_id > c2.config_id 
    AND c1.config_type = c2.config_type 
    AND c1.config_key = c2.config_key
    AND c1.config_type = 'automation_processor';

-- Verify no duplicates remain
SELECT config_type, config_key, COUNT(*) as count
FROM bg_config 
WHERE config_type = 'automation_processor'
GROUP BY config_type, config_key
HAVING COUNT(*) > 1;