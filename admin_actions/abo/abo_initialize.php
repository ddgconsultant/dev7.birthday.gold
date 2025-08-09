<?php
// abo_initialize.php - Initialize Automation Business Onboarding configuration
// This should be run once to set up all automation processors in bg_config
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// No authorization required - handled by site-controller.php

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processors_created' => 0,
    'statuses_created' => 0,
    'errors' => []
];

try {
    $database->beginTransaction();
    
    // Insert automation processor configurations
    $processors = [
        [
            'key' => 'abo_processsubmission',
            'value' => 'Process Submission',
            'data' => [
                'description' => 'Categorize business and check for duplicates',
                'scheduler' => 'abo_processsubmission.php',
                'frequency' => '*/5 * * * *'
            ],
            'order' => 1
        ],
        [
            'key' => 'abo_grabgoogleapp',
            'value' => 'Collect Google App Data',
            'data' => [
                'description' => 'Search for and extract Google Play app information',
                'scheduler' => 'abo_grabgoogleapp.php',
                'frequency' => '*/10 * * * *'
            ],
            'order' => 2
        ],
        [
            'key' => 'abo_grabiosapp',
            'value' => 'Collect iOS App Data',
            'data' => [
                'description' => 'Search for and extract Apple App Store information',
                'scheduler' => 'abo_grabiosapp.php',
                'frequency' => '*/10 * * * *'
            ],
            'order' => 3
        ],
        [
            'key' => 'abo_grabsocialmedia',
            'value' => 'Collect Social Media Data',
            'data' => [
                'description' => 'Extract social media links and handles',
                'scheduler' => 'abo_grabsocialmedia.php',
                'frequency' => '*/10 * * * *'
            ],
            'order' => 4
        ],
        [
            'key' => 'abo_grabmetadata',
            'value' => 'Collect Metadata',
            'data' => [
                'description' => 'Extract meta tags, contact info, and business details',
                'scheduler' => 'abo_grabmetadata.php',
                'frequency' => '*/10 * * * *'
            ],
            'order' => 5
        ],
        [
            'key' => 'abo_grabimages',
            'value' => 'Collect Images',
            'data' => [
                'description' => 'Extract logo and business images',
                'scheduler' => 'abo_grabimages.php',
                'frequency' => '*/15 * * * *'
            ],
            'order' => 6
        ],
        [
            'key' => 'abo_grablocations',
            'value' => 'Collect Location Data',
            'data' => [
                'description' => 'Extract store locations and addresses',
                'scheduler' => 'abo_grablocations.php',
                'frequency' => '*/15 * * * *'
            ],
            'order' => 7
        ],
        [
            'key' => 'abo_grabbirthday',
            'value' => 'Collect Birthday Program Details',
            'data' => [
                'description' => 'Extract birthday reward program specifics',
                'scheduler' => 'abo_grabbirthday.php',
                'frequency' => '*/15 * * * *'
            ],
            'order' => 8
        ]
    ];
    
    // Insert processors
    foreach ($processors as $processor) {
        $sql = "INSERT INTO bg_config 
                (config_type, config_key, config_value, config_data, display_order, `status`)
                VALUES 
                ('automation_processor', :key, :value, :data, :order, 'active')
                ON DUPLICATE KEY UPDATE
                config_value = VALUES(config_value),
                config_data = VALUES(config_data),
                display_order = VALUES(display_order),
                `status` = VALUES(`status`),
                updated_at = CURRENT_TIMESTAMP";
        
        $database->query($sql, [
            'key' => $processor['key'],
            'value' => $processor['value'],
            'data' => json_encode($processor['data']),
            'order' => $processor['order']
        ]);
        
        $result['processors_created']++;
    }
    
    // Insert automation status values
    $statuses = [
        [
            'key' => 'pending',
            'value' => 'Pending',
            'data' => [
                'description' => 'Task not yet started',
                'icon' => 'bi-hourglass',
                'color' => 'secondary'
            ],
            'order' => 1
        ],
        [
            'key' => 'in_progress',
            'value' => 'In Progress',
            'data' => [
                'description' => 'Task currently being processed',
                'icon' => 'bi-arrow-repeat',
                'color' => 'primary'
            ],
            'order' => 2
        ],
        [
            'key' => 'completed',
            'value' => 'Completed',
            'data' => [
                'description' => 'Task completed successfully',
                'icon' => 'bi-check-circle',
                'color' => 'success'
            ],
            'order' => 3
        ],
        [
            'key' => 'error',
            'value' => 'Error',
            'data' => [
                'description' => 'Task encountered an error',
                'icon' => 'bi-exclamation-circle',
                'color' => 'danger'
            ],
            'order' => 4
        ],
        [
            'key' => 'skipped',
            'value' => 'Skipped',
            'data' => [
                'description' => 'Task skipped (not applicable)',
                'icon' => 'bi-dash-circle',
                'color' => 'warning'
            ],
            'order' => 5
        ]
    ];
    
    // Insert statuses
    foreach ($statuses as $status) {
        $sql = "INSERT INTO bg_config 
                (config_type, config_key, config_value, config_data, display_order, `status`)
                VALUES 
                ('automation_status', :key, :value, :data, :order, 'active')
                ON DUPLICATE KEY UPDATE
                config_value = VALUES(config_value),
                config_data = VALUES(config_data),
                display_order = VALUES(display_order),
                `status` = VALUES(`status`),
                updated_at = CURRENT_TIMESTAMP";
        
        $database->query($sql, [
            'key' => $status['key'],
            'value' => $status['value'],
            'data' => json_encode($status['data']),
            'order' => $status['order']
        ]);
        
        $result['statuses_created']++;
    }
    
    // Create view for monitoring automation progress
    $view_sql = "CREATE OR REPLACE VIEW bg_automation_progress AS
                SELECT 
                    c.company_id,
                    c.company_name,
                    c.status as company_status,
                    ca.name as processor_name,
                    ca.description as processor_status,
                    ca.create_dt as started_at,
                    ca.modify_dt as updated_at,
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
                ORDER BY c.company_id, ca.create_dt";
    
    $database->query($view_sql);
    
    $database->commit();
    
    $result['message'] = "Successfully initialized {$result['processors_created']} processors and {$result['statuses_created']} status values";
    
} catch (Exception $e) {
    $database->rollBack();
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    session_tracking('ABO initialization error', $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result);