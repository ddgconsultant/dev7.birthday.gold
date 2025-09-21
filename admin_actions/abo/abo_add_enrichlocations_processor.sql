-- Add abo_enrichlocations processor to bg_config
-- This processor enriches location data with Google Places API

INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    ('automation_processor', 'abo_enrichlocations', 'Enrich Location Data', 
     JSON_OBJECT(
         'description', 'Enrich location data with complete addresses and business details from Google Places',
         'scheduler_file', 'abo_enrichlocations.php',
         'frequency', '*/20 * * * *',
         'timeout', 600,
         'category', 'data_enrichment',
         'requires_api', 'google_places',
         'is_supplementary', true,
         'runs_after', 'abo_grablocations'
     ), 
     211, 1)
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `config_data` = VALUES(`config_data`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Create Google Places API configuration entry if not exists
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    ('api_configuration', 'google_places', 'Google Places API', 
     JSON_OBJECT(
         'description', 'Google Places API for location enrichment',
         'api_key_config', 'GOOGLECONFIG.api_key',
         'quota_per_day', 2500,
         'quota_per_second', 10,
         'enabled', true
     ), 
     800, 1)
ON DUPLICATE KEY UPDATE
    `updated_at` = CURRENT_TIMESTAMP;

-- Verify insertion
SELECT config_key, config_value, display_order, JSON_EXTRACT(config_data, '$.category') as category
FROM bg_config 
WHERE config_type = 'automation_processor' 
AND config_key = 'abo_enrichlocations';