-- Add missing ABO processors to bg_config
-- This includes both regular and AIRTOP escalation processors
-- Execute this SQL to add all missing processors

-- Add abo_grabage processor
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    ('automation_processor', 'abo_grabage', 'Extract Age Requirements', 
     JSON_OBJECT(
         'description', 'Extract age requirements for birthday programs',
         'scheduler_file', 'abo_grabage.php',
         'frequency', '*/15 * * * *',
         'timeout', 300,
         'category', 'data_collection'
     ), 
     207, 1)
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `config_data` = VALUES(`config_data`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Add abo_grabterms processor
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    ('automation_processor', 'abo_grabterms', 'Extract Terms of Service', 
     JSON_OBJECT(
         'description', 'Extract terms of service and privacy policy URLs',
         'scheduler_file', 'abo_grabterms.php',
         'frequency', '*/15 * * * *',
         'timeout', 300,
         'category', 'data_collection'
     ), 
     208, 1)
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `config_data` = VALUES(`config_data`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Add abo_grabprivacy processor
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    ('automation_processor', 'abo_grabprivacy', 'Extract Privacy Policy', 
     JSON_OBJECT(
         'description', 'Extract privacy policy details and compliance info',
         'scheduler_file', 'abo_grabprivacy.php',
         'frequency', '*/15 * * * *',
         'timeout', 300,
         'category', 'data_collection'
     ), 
     209, 1)
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `config_data` = VALUES(`config_data`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Add abo_mapformfields processor
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    ('automation_processor', 'abo_mapformfields', 'Map Form Fields', 
     JSON_OBJECT(
         'description', 'Map signup form fields to user profile fields',
         'scheduler_file', 'abo_mapformfields.php',
         'frequency', '*/15 * * * *',
         'timeout', 300,
         'category', 'data_collection'
     ), 
     210, 1)
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `config_data` = VALUES(`config_data`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Add AIRTOP escalation processors
-- Add abo_mapformfields_airtop processor
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    ('automation_processor', 'abo_mapformfields_airtop', 'Map Form Fields (AIRTOP)', 
     JSON_OBJECT(
         'description', 'AI-powered form field mapping using AIRTOP browser automation',
         'scheduler_file', 'abo_mapformfields_airtop.php',
         'frequency', '*/30 * * * *',
         'timeout', 600,
         'category', 'ai_processing',
         'is_escalation', true,
         'escalation_from', 'abo_mapformfields',
         'allows_reprocessing', true,
         'reprocess_param', 'retrigger'
     ), 
     250, 1)
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `config_data` = VALUES(`config_data`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Add abo_grabage_airtop processor
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    ('automation_processor', 'abo_grabage_airtop', 'Extract Age Requirements (AIRTOP)', 
     JSON_OBJECT(
         'description', 'AI-powered age requirement extraction using AIRTOP browser automation',
         'scheduler_file', 'abo_grabage_airtop.php',
         'frequency', '*/30 * * * *',
         'timeout', 600,
         'category', 'ai_processing',
         'is_escalation', true,
         'escalation_from', 'abo_grabage',
         'allows_reprocessing', true,
         'reprocess_param', 'retrigger'
     ), 
     251, 1)
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `config_data` = VALUES(`config_data`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Verify insertion
SELECT config_key, config_value, display_order, JSON_EXTRACT(config_data, '$.category') as category
FROM bg_config 
WHERE config_type = 'automation_processor' 
AND config_key IN ('abo_grabage', 'abo_grabage_airtop', 'abo_mapformfields', 'abo_mapformfields_airtop', 'abo_grabterms', 'abo_grabprivacy')
ORDER BY display_order;