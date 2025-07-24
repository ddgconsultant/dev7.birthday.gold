<?php
// abo_setup.php - Execute SQL to set up ABO configuration in bg_config
// This is a one-time setup script
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// No authorization required - handled by site-controller.php

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processors_created' => 0,
    'statuses_created' => 0,
    'categories_created' => 0,
    'settings_created' => 0,
    'errors' => []
];

try {
    $database->beginTransaction();
    
    // 1. Insert automation processors
    $processors = [
        // Core Processing
        [
            'key' => 'abo_processsubmission',
            'value' => 'Process Submission',
            'data' => [
                'description' => 'Initial processing: categorize business and check for duplicates',
                'scheduler_file' => 'abo_processsubmission.php',
                'frequency' => '*/5 * * * *',
                'timeout' => 300,
                'category' => 'core_processing'
            ],
            'order' => 100
        ],
        // Data Collection
        [
            'key' => 'abo_grabgoogleapp',
            'value' => 'Collect Google App Data',
            'data' => [
                'description' => 'Search for and extract Google Play app information',
                'scheduler_file' => 'abo_grabgoogleapp.php',
                'frequency' => '*/10 * * * *',
                'timeout' => 180,
                'category' => 'data_collection'
            ],
            'order' => 200
        ],
        [
            'key' => 'abo_grabiosapp',
            'value' => 'Collect iOS App Data',
            'data' => [
                'description' => 'Search for and extract Apple App Store information',
                'scheduler_file' => 'abo_grabiosapp.php',
                'frequency' => '*/10 * * * *',
                'timeout' => 180,
                'category' => 'data_collection'
            ],
            'order' => 201
        ],
        [
            'key' => 'abo_grabsocialmedia',
            'value' => 'Collect Social Media Data',
            'data' => [
                'description' => 'Extract social media links and handles from website',
                'scheduler_file' => 'abo_grabsocialmedia.php',
                'frequency' => '*/10 * * * *',
                'timeout' => 180,
                'category' => 'data_collection'
            ],
            'order' => 202
        ],
        [
            'key' => 'abo_grabmetadata',
            'value' => 'Collect Website Metadata',
            'data' => [
                'description' => 'Extract meta tags, contact info, and business details',
                'scheduler_file' => 'abo_grabmetadata.php',
                'frequency' => '*/10 * * * *',
                'timeout' => 180,
                'category' => 'data_collection'
            ],
            'order' => 203
        ],
        [
            'key' => 'abo_grabimages',
            'value' => 'Collect Business Images',
            'data' => [
                'description' => 'Extract logo and business images from website',
                'scheduler_file' => 'abo_grabimages.php',
                'frequency' => '*/15 * * * *',
                'timeout' => 300,
                'category' => 'data_collection'
            ],
            'order' => 204
        ],
        [
            'key' => 'abo_grablocations',
            'value' => 'Collect Location Data',
            'data' => [
                'description' => 'Extract store locations and addresses',
                'scheduler_file' => 'abo_grablocations.php',
                'frequency' => '*/15 * * * *',
                'timeout' => 300,
                'category' => 'data_collection'
            ],
            'order' => 205
        ],
        [
            'key' => 'abo_grabbirthday',
            'value' => 'Collect Birthday Program Details',
            'data' => [
                'description' => 'Extract birthday reward program specifics and requirements',
                'scheduler_file' => 'abo_grabbirthday.php',
                'frequency' => '*/15 * * * *',
                'timeout' => 300,
                'category' => 'data_collection'
            ],
            'order' => 206
        ],
        [
            'key' => 'abo_grabterms',
            'value' => 'Collect Terms and Conditions',
            'data' => [
                'description' => 'Extract terms of service and conditions from website',
                'scheduler_file' => 'abo_grabterms.php',
                'frequency' => '*/15 * * * *',
                'timeout' => 300,
                'category' => 'data_collection'
            ],
            'order' => 207
        ],
        [
            'key' => 'abo_grabprivacy',
            'value' => 'Collect Privacy Policy',
            'data' => [
                'description' => 'Extract privacy policy information',
                'scheduler_file' => 'abo_grabprivacy.php',
                'frequency' => '*/15 * * * *',
                'timeout' => 300,
                'category' => 'data_collection'
            ],
            'order' => 208
        ],
        [
            'key' => 'abo_grabage',
            'value' => 'Collect Age Requirements',
            'data' => [
                'description' => 'Extract birthday reward age requirements and restrictions',
                'scheduler_file' => 'abo_grabage.php',
                'frequency' => '*/15 * * * *',
                'timeout' => 300,
                'category' => 'data_collection'
            ],
            'order' => 209
        ],
        [
            'key' => 'abo_grabhours',
            'value' => 'Collect Business Hours',
            'data' => [
                'description' => 'Extract business hours and location details',
                'scheduler_file' => 'abo_grabhours.php',
                'frequency' => '*/15 * * * *',
                'timeout' => 300,
                'category' => 'data_collection'
            ],
            'order' => 210
        ],
        // AI Enhancement
        [
            'key' => 'abo_aienhance',
            'value' => 'AI Enhancement',
            'data' => [
                'description' => 'Use AI to enhance and validate collected data',
                'scheduler_file' => 'abo_aienhance.php',
                'frequency' => '*/30 * * * *',
                'timeout' => 600,
                'category' => 'ai_processing'
            ],
            'order' => 300
        ],
        [
            'key' => 'abo_aivalidate',
            'value' => 'AI Validation',
            'data' => [
                'description' => 'AI-powered validation of birthday program details',
                'scheduler_file' => 'abo_aivalidate.php',
                'frequency' => '*/30 * * * *',
                'timeout' => 300,
                'category' => 'ai_processing'
            ],
            'order' => 301
        ],
        // Final Processing
        [
            'key' => 'abo_finalize',
            'value' => 'Finalize Onboarding',
            'data' => [
                'description' => 'Final validation and activation of business',
                'scheduler_file' => 'abo_finalize.php',
                'frequency' => '*/15 * * * *',
                'timeout' => 180,
                'category' => 'final_processing'
            ],
            'order' => 400
        ]
    ];
    
    foreach ($processors as $processor) {
        $sql = "INSERT INTO bg_config 
                (config_type, config_key, config_value, config_data, display_order, is_active)
                VALUES 
                ('automation_processor', :key, :value, :data, :order, 1)
                ON DUPLICATE KEY UPDATE
                config_value = VALUES(config_value),
                config_data = VALUES(config_data),
                display_order = VALUES(display_order),
                is_active = VALUES(is_active),
                updated_at = CURRENT_TIMESTAMP";
        
        $database->query($sql, [
            'key' => $processor['key'],
            'value' => $processor['value'],
            'data' => json_encode($processor['data']),
            'order' => $processor['order']
        ]);
        
        $result['processors_created']++;
    }
    
    // 2. Insert automation statuses
    $statuses = [
        ['key' => 'pending', 'value' => 'Pending', 'icon' => 'bi-hourglass', 'color' => 'secondary'],
        ['key' => 'in_progress', 'value' => 'In Progress', 'icon' => 'bi-arrow-repeat', 'color' => 'primary'],
        ['key' => 'completed', 'value' => 'Completed', 'icon' => 'bi-check-circle', 'color' => 'success'],
        ['key' => 'attempted', 'value' => 'Attempted', 'icon' => 'bi-check', 'color' => 'warning'],
        ['key' => 'error', 'value' => 'Error', 'icon' => 'bi-exclamation-circle', 'color' => 'danger'],
        ['key' => 'skipped', 'value' => 'Skipped', 'icon' => 'bi-dash-circle', 'color' => 'warning'],
        ['key' => 'manual_review', 'value' => 'Manual Review Required', 'icon' => 'bi-person-check', 'color' => 'info']
    ];
    
    $order = 501;
    foreach ($statuses as $status) {
        $sql = "INSERT INTO bg_config 
                (config_type, config_key, config_value, config_data, display_order, is_active)
                VALUES 
                ('automation_status', :key, :value, :data, :order, 1)
                ON DUPLICATE KEY UPDATE
                config_value = VALUES(config_value),
                config_data = VALUES(config_data),
                display_order = VALUES(display_order),
                updated_at = CURRENT_TIMESTAMP";
        
        $data = [
            'description' => $status['value'],
            'icon' => $status['icon'],
            'color' => $status['color'],
            'badge_class' => 'bg-' . $status['color']
        ];
        
        $database->query($sql, [
            'key' => $status['key'],
            'value' => $status['value'],
            'data' => json_encode($data),
            'order' => $order++
        ]);
        
        $result['statuses_created']++;
    }
    
    // 3. Insert automation categories
    $categories = [
        ['key' => 'core_processing', 'value' => 'Core Processing', 'icon' => 'bi-gear', 'desc' => 'Initial processing and validation steps'],
        ['key' => 'data_collection', 'value' => 'Data Collection', 'icon' => 'bi-database', 'desc' => 'Automated data gathering from various sources'],
        ['key' => 'ai_processing', 'value' => 'AI Processing', 'icon' => 'bi-cpu', 'desc' => 'AI-powered enhancement and validation'],
        ['key' => 'final_processing', 'value' => 'Final Processing', 'icon' => 'bi-check-square', 'desc' => 'Final validation and activation steps']
    ];
    
    $order = 601;
    foreach ($categories as $category) {
        $sql = "INSERT INTO bg_config 
                (config_type, config_key, config_value, config_data, display_order, is_active)
                VALUES 
                ('automation_category', :key, :value, :data, :order, 1)
                ON DUPLICATE KEY UPDATE
                config_value = VALUES(config_value),
                config_data = VALUES(config_data),
                display_order = VALUES(display_order),
                updated_at = CURRENT_TIMESTAMP";
        
        $data = [
            'description' => $category['desc'],
            'icon' => $category['icon'],
            'color' => 'primary'
        ];
        
        $database->query($sql, [
            'key' => $category['key'],
            'value' => $category['value'],
            'data' => json_encode($data),
            'order' => $order++
        ]);
        
        $result['categories_created']++;
    }
    
    // 4. Insert automation settings
    $settings = [
        ['key' => 'max_retries', 'value' => '3', 'desc' => 'Maximum number of retries for failed tasks', 'min' => 1, 'max' => 10],
        ['key' => 'retry_delay_minutes', 'value' => '30', 'desc' => 'Minutes to wait before retrying a failed task', 'min' => 5, 'max' => 1440],
        ['key' => 'parallel_processing', 'value' => 'false', 'desc' => 'Whether to process multiple companies in parallel', 'type' => 'boolean'],
        ['key' => 'auto_activate_threshold', 'value' => '0.8', 'desc' => 'Minimum completion score to auto-activate a business', 'min' => 0.5, 'max' => 1.0, 'type' => 'float'],
        ['key' => 'stale_data_days', 'value' => '30', 'desc' => 'Days before collected data is considered stale', 'min' => 7, 'max' => 365]
    ];
    
    $order = 701;
    foreach ($settings as $setting) {
        $sql = "INSERT INTO bg_config 
                (config_type, config_key, config_value, config_data, display_order, is_active)
                VALUES 
                ('automation_settings', :key, :value, :data, :order, 1)
                ON DUPLICATE KEY UPDATE
                config_value = VALUES(config_value),
                config_data = VALUES(config_data),
                display_order = VALUES(display_order),
                updated_at = CURRENT_TIMESTAMP";
        
        $data = ['description' => $setting['desc']];
        if (isset($setting['min'])) $data['min'] = $setting['min'];
        if (isset($setting['max'])) $data['max'] = $setting['max'];
        if (isset($setting['type'])) $data['type'] = $setting['type'];
        
        $database->query($sql, [
            'key' => $setting['key'],
            'value' => $setting['value'],
            'data' => json_encode($data),
            'order' => $order++
        ]);
        
        $result['settings_created']++;
    }
    
    
    $database->commit();
    
    $result['message'] = sprintf(
        "ABO setup completed: %d processors, %d statuses, %d categories, %d settings",
        $result['processors_created'],
        $result['statuses_created'],
        $result['categories_created'],
        $result['settings_created']
    );
    
} catch (Exception $e) {
    $database->rollBack();
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    error_log("ABO setup error: " . $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);