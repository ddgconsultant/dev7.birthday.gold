-- Initialize automation processor steps in bg_config
-- These will be copied to bg_company_attributes when a business is approved

-- Insert automation processor configurations
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    -- Process submission (categorization, duplicate check)
    ('automation_processor', 'processor_processsubmission', 'Process Submission', 
     '{"description": "Categorize business and check for duplicates", "scheduler": "scheduler--process-business-submissions.php", "frequency": "*/5 * * * *"}', 
     1, 1),
    
    -- Google app data collection
    ('automation_processor', 'processor_grabgoogleapp', 'Collect Google App Data', 
     '{"description": "Search for and extract Google Play app information", "scheduler": "scheduler--collect-business-data.php", "data_type": "google_app", "frequency": "*/10 * * * *"}', 
     2, 1),
    
    -- iOS app data collection
    ('automation_processor', 'processor_grabiosapp', 'Collect iOS App Data', 
     '{"description": "Search for and extract Apple App Store information", "scheduler": "scheduler--collect-business-data.php", "data_type": "ios_app", "frequency": "*/10 * * * *"}', 
     3, 1),
    
    -- Social media data collection
    ('automation_processor', 'processor_grabsocialmedia', 'Collect Social Media Data', 
     '{"description": "Extract social media links and handles", "scheduler": "scheduler--collect-business-data.php", "data_type": "social_media", "frequency": "*/10 * * * *"}', 
     4, 1),
    
    -- Metadata collection
    ('automation_processor', 'processor_grabmetadata', 'Collect Metadata', 
     '{"description": "Extract meta tags, contact info, and business details", "scheduler": "scheduler--collect-business-data.php", "data_type": "metadata", "frequency": "*/10 * * * *"}', 
     5, 1),
    
    -- Image collection
    ('automation_processor', 'processor_grabimages', 'Collect Images', 
     '{"description": "Extract logo and business images", "scheduler": "scheduler--collect-business-data.php", "data_type": "images", "frequency": "*/15 * * * *"}', 
     6, 1),
    
    -- Location data collection
    ('automation_processor', 'processor_grablocations', 'Collect Location Data', 
     '{"description": "Extract store locations and addresses", "scheduler": "scheduler--collect-business-data.php", "data_type": "locations", "frequency": "*/15 * * * *"}', 
     7, 1),
    
    -- Birthday program details
    ('automation_processor', 'processor_grabbirthday', 'Collect Birthday Program Details', 
     '{"description": "Extract birthday reward program specifics", "scheduler": "scheduler--collect-business-data.php", "data_type": "birthday_program", "frequency": "*/15 * * * *"}', 
     8, 1)
     
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `config_data` = VALUES(`config_data`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Insert automation processor status values
INSERT INTO `bg_config` (`config_type`, `config_key`, `config_value`, `config_data`, `display_order`, `is_active`)
VALUES 
    ('automation_status', 'pending', 'Pending', 
     '{"description": "Task not yet started", "icon": "bi-hourglass", "color": "secondary"}', 
     1, 1),
    
    ('automation_status', 'in_progress', 'In Progress', 
     '{"description": "Task currently being processed", "icon": "bi-arrow-repeat", "color": "primary"}', 
     2, 1),
    
    ('automation_status', 'completed', 'Completed', 
     '{"description": "Task completed successfully", "icon": "bi-check-circle", "color": "success"}', 
     3, 1),
    
    ('automation_status', 'error', 'Error', 
     '{"description": "Task encountered an error", "icon": "bi-exclamation-circle", "color": "danger"}', 
     4, 1),
    
    ('automation_status', 'skipped', 'Skipped', 
     '{"description": "Task skipped (not applicable)", "icon": "bi-dash-circle", "color": "warning"}', 
     5, 1)
     
ON DUPLICATE KEY UPDATE
    `config_value` = VALUES(`config_value`),
    `config_data` = VALUES(`config_data`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Create a view to easily monitor automation progress
CREATE OR REPLACE VIEW `bg_automation_progress` AS
SELECT 
    c.company_id,
    c.company_name,
    c.status as company_status,
    ca.name as processor_name,
    ca.description as processor_status,
    ca.create_dt as started_at,
    ca.update_dt as updated_at,
    CASE 
        WHEN ca.description = 'completed' THEN 1
        WHEN ca.description = 'error' THEN -1
        WHEN ca.description = 'in_progress' THEN 0.5
        ELSE 0
    END as progress_score
FROM bg_companies c
LEFT JOIN bg_company_attributes ca ON c.company_id = ca.company_id 
    AND ca.type = 'onboarding_progress'
    AND ca.status = 'active'
WHERE c.source = 'user_recommendation'
ORDER BY c.company_id, ca.create_dt;