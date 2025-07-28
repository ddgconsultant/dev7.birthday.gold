-- Add abo_grabage and abo_grabage_airtop processors to bg_config
-- Execute this SQL to add the missing processors

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

-- Add abo_grabage_airtop processor (AIRTOP escalation)
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
SELECT config_key, config_value, display_order 
FROM bg_config 
WHERE config_type = 'automation_processor' 
AND config_key IN ('abo_grabage', 'abo_grabage_airtop');