-- SQL Insert statements for ABO (Automation Business Onboarding) configuration
-- This creates all the automation processor steps in bg_config

-- First, let's insert the automation processor configurations
-- These define each step in the business onboarding automation pipeline

INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    -- Core Processing Steps
    ('automation_processor', 'abo_processsubmission', 'Process Submission', 
     JSON_OBJECT(
         'description', 'Initial processing: categorize business and check for duplicates',
         'scheduler_file', 'abo_processsubmission.php',
         'frequency', '*/5 * * * *',
         'timeout', 300,
         'category', 'core_processing'
     ), 
     100, 1),
    
    -- Data Collection Steps
    ('automation_processor', 'abo_grabgoogleapp', 'Collect Google App Data', 
     JSON_OBJECT(
         'description', 'Search for and extract Google Play app information',
         'scheduler_file', 'abo_grabgoogleapp.php',
         'frequency', '*/10 * * * *',
         'timeout', 180,
         'category', 'data_collection'
     ), 
     200, 1),
    
    ('automation_processor', 'abo_grabiosapp', 'Collect iOS App Data', 
     JSON_OBJECT(
         'description', 'Search for and extract Apple App Store information',
         'scheduler_file', 'abo_grabiosapp.php',
         'frequency', '*/10 * * * *',
         'timeout', 180,
         'category', 'data_collection'
     ), 
     201, 1),
    
    ('automation_processor', 'abo_grabsocialmedia', 'Collect Social Media Data', 
     JSON_OBJECT(
         'description', 'Extract social media links and handles from website',
         'scheduler_file', 'abo_grabsocialmedia.php',
         'frequency', '*/10 * * * *',
         'timeout', 180,
         'category', 'data_collection'
     ), 
     202, 1),
    
    ('automation_processor', 'abo_grabmetadata', 'Collect Website Metadata', 
     JSON_OBJECT(
         'description', 'Extract meta tags, contact info, and business details',
         'scheduler_file', 'abo_grabmetadata.php',
         'frequency', '*/10 * * * *',
         'timeout', 180,
         'category', 'data_collection'
     ), 
     203, 1),
    
    ('automation_processor', 'abo_grabimages', 'Collect Business Images', 
     JSON_OBJECT(
         'description', 'Extract logo and business images from website',
         'scheduler_file', 'abo_grabimages.php',
         'frequency', '*/15 * * * *',
         'timeout', 300,
         'category', 'data_collection'
     ), 
     204, 1),
    
    ('automation_processor', 'abo_grablocations', 'Collect Location Data', 
     JSON_OBJECT(
         'description', 'Extract store locations and addresses',
         'scheduler_file', 'abo_grablocations.php',
         'frequency', '*/15 * * * *',
         'timeout', 300,
         'category', 'data_collection'
     ), 
     205, 1),
    
    ('automation_processor', 'abo_grabbirthday', 'Collect Birthday Program Details', 
     JSON_OBJECT(
         'description', 'Extract birthday reward program specifics and requirements',
         'scheduler_file', 'abo_grabbirthday.php',
         'frequency', '*/15 * * * *',
         'timeout', 300,
         'category', 'data_collection'
     ), 
     206, 1),
    
    -- AI Enhancement Steps
    ('automation_processor', 'abo_aienhance', 'AI Enhancement', 
     JSON_OBJECT(
         'description', 'Use AI to enhance and validate collected data',
         'scheduler_file', 'abo_aienhance.php',
         'frequency', '*/30 * * * *',
         'timeout', 600,
         'category', 'ai_processing'
     ), 
     300, 1),
    
    ('automation_processor', 'abo_aivalidate', 'AI Validation', 
     JSON_OBJECT(
         'description', 'AI-powered validation of birthday program details',
         'scheduler_file', 'abo_aivalidate.php',
         'frequency', '*/30 * * * *',
         'timeout', 300,
         'category', 'ai_processing'
     ), 
     301, 1),
    
    -- Final Processing Steps
    ('automation_processor', 'abo_finalize', 'Finalize Onboarding', 
     JSON_OBJECT(
         'description', 'Final validation and activation of business',
         'scheduler_file', 'abo_finalize.php',
         'frequency', '*/15 * * * *',
         'timeout', 180,
         'category', 'final_processing'
     ), 
     400, 1)
     
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `config_data` = VALUES(`config_data`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Insert automation status values
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    ('automation_status', 'pending', 'Pending', 
     JSON_OBJECT(
         'description', 'Task not yet started',
         'icon', 'bi-hourglass',
         'color', 'secondary',
         'badge_class', 'bg-secondary'
     ), 
     501, 1),
    
    ('automation_status', 'in_progress', 'In Progress', 
     JSON_OBJECT(
         'description', 'Task currently being processed',
         'icon', 'bi-arrow-repeat',
         'color', 'primary',
         'badge_class', 'bg-primary'
     ), 
     502, 1),
    
    ('automation_status', 'completed', 'Completed', 
     JSON_OBJECT(
         'description', 'Task completed successfully',
         'icon', 'bi-check-circle',
         'color', 'success',
         'badge_class', 'bg-success'
     ), 
     503, 1),
    
    ('automation_status', 'error', 'Error', 
     JSON_OBJECT(
         'description', 'Task encountered an error',
         'icon', 'bi-exclamation-circle',
         'color', 'danger',
         'badge_class', 'bg-danger'
     ), 
     504, 1),
    
    ('automation_status', 'skipped', 'Skipped', 
     JSON_OBJECT(
         'description', 'Task skipped (not applicable)',
         'icon', 'bi-dash-circle',
         'color', 'warning',
         'badge_class', 'bg-warning'
     ), 
     505, 1),
    
    ('automation_status', 'manual_review', 'Manual Review Required', 
     JSON_OBJECT(
         'description', 'Task requires manual intervention',
         'icon', 'bi-person-check',
         'color', 'info',
         'badge_class', 'bg-info'
     ), 
     506, 1)
     
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `config_data` = VALUES(`config_data`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Insert automation categories for grouping
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    ('automation_category', 'core_processing', 'Core Processing', 
     JSON_OBJECT(
         'description', 'Initial processing and validation steps',
         'icon', 'bi-gear',
         'color', 'primary'
     ), 
     601, 1),
    
    ('automation_category', 'data_collection', 'Data Collection', 
     JSON_OBJECT(
         'description', 'Automated data gathering from various sources',
         'icon', 'bi-database',
         'color', 'info'
     ), 
     602, 1),
    
    ('automation_category', 'ai_processing', 'AI Processing', 
     JSON_OBJECT(
         'description', 'AI-powered enhancement and validation',
         'icon', 'bi-cpu',
         'color', 'success'
     ), 
     603, 1),
    
    ('automation_category', 'final_processing', 'Final Processing', 
     JSON_OBJECT(
         'description', 'Final validation and activation steps',
         'icon', 'bi-check-square',
         'color', 'success'
     ), 
     604, 1)
     
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `config_data` = VALUES(`config_data`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Insert automation settings
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    ('automation_settings', 'max_retries', '3', 
     JSON_OBJECT(
         'description', 'Maximum number of retries for failed tasks',
         'min', 1,
         'max', 10
     ), 
     701, 1),
    
    ('automation_settings', 'retry_delay_minutes', '30', 
     JSON_OBJECT(
         'description', 'Minutes to wait before retrying a failed task',
         'min', 5,
         'max', 1440
     ), 
     702, 1),
    
    ('automation_settings', 'parallel_processing', 'false', 
     JSON_OBJECT(
         'description', 'Whether to process multiple companies in parallel',
         'type', 'boolean'
     ), 
     703, 1),
    
    ('automation_settings', 'auto_activate_threshold', '0.8', 
     JSON_OBJECT(
         'description', 'Minimum completion score to auto-activate a business',
         'min', 0.5,
         'max', 1.0,
         'type', 'float'
     ), 
     704, 1),
    
    ('automation_settings', 'stale_data_days', '30', 
     JSON_OBJECT(
         'description', 'Days before collected data is considered stale',
         'min', 7,
         'max', 365
     ), 
     705, 1)
     
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `config_data` = VALUES(`config_data`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- End of ABO configuration inserts
-- To execute: Run abo_setup.php or execute this SQL directly