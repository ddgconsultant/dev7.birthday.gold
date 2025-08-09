<?php
// abo_config_setup.php - Configure ABO processors in correct execution order
// Created: 2025-01-31

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processors_configured' => 0,
    'errors' => []
];

try {
    // Define processors in execution order
    $processors = [
        // Phase 1: Initial Processing
        [
            'order' => 1,
            'key' => 'abo_processsubmission',
            'name' => 'Process Submission',
            'description' => 'Initial validation and categorization of submitted businesses',
            'scheduler' => 'abo_processsubmission.php',
            'frequency' => '*/5 * * * *',
            'enabled' => true
        ],
        
        // Phase 2: Site Discovery (MUST BE FIRST after submission)
        [
            'order' => 2,
            'key' => 'abo_crawlpages_dom',
            'name' => 'Crawl Website Pages',
            'description' => 'DOM-based crawler to discover all pages on company website',
            'scheduler' => 'abo_crawlpages_dom.php',
            'frequency' => '*/5 * * * *',
            'enabled' => true
        ],
        
        // Phase 3: Location Extraction (depends on crawled pages)
        [
            'order' => 3,
            'key' => 'abo_grablocations_airtop',
            'name' => 'Extract Locations',
            'description' => 'Use AIRTOP to extract location details from discovered location pages',
            'scheduler' => 'abo_grablocations_airtop.php',
            'frequency' => '*/10 * * * *',
            'enabled' => true,
            'depends_on' => 'abo_crawlpages_dom'
        ],
        
        // Phase 4: Hours Extraction (depends on crawled pages)
        [
            'order' => 4,
            'key' => 'abo_grabhours_airtop',
            'name' => 'Extract Business Hours',
            'description' => 'Use AIRTOP to extract hours from location/contact pages',
            'scheduler' => 'abo_grabhours_airtop.php',
            'frequency' => '*/10 * * * *',
            'enabled' => true,
            'depends_on' => 'abo_crawlpages_dom'
        ],
        
        // Phase 5: Birthday/Rewards Program Discovery
        [
            'order' => 5,
            'key' => 'abo_grabbirthday',
            'name' => 'Find Birthday Program',
            'description' => 'Extract birthday reward program details from signup/rewards pages',
            'scheduler' => 'abo_grabbirthday.php',
            'frequency' => '*/15 * * * *',
            'enabled' => true,
            'depends_on' => 'abo_crawlpages_dom'
        ],
        
        // Phase 6: Signup Form Mapping
        [
            'order' => 6,
            'key' => 'abo_mapformfields',
            'name' => 'Map Signup Forms',
            'description' => 'Analyze signup/rewards pages and map form fields for automation',
            'scheduler' => 'abo_mapformfields.php',
            'frequency' => '*/20 * * * *',
            'enabled' => true,
            'depends_on' => 'abo_crawlpages_dom'
        ],
        
        // Phase 7: Social Media Extraction
        [
            'order' => 7,
            'key' => 'abo_grabsocialmedia',
            'name' => 'Extract Social Media',
            'description' => 'Find social media links and profiles',
            'scheduler' => 'abo_grabsocialmedia.php',
            'frequency' => '*/10 * * * *',
            'enabled' => true,
            'depends_on' => 'abo_crawlpages_dom'
        ],
        
        // Phase 8: Metadata & Additional Info
        [
            'order' => 8,
            'key' => 'abo_grabmetadata',
            'name' => 'Extract Metadata',
            'description' => 'Extract meta tags, structured data, and business info',
            'scheduler' => 'abo_grabmetadata.php',
            'frequency' => '*/10 * * * *',
            'enabled' => true
        ],
        
        // Phase 9: Images & Logos
        [
            'order' => 9,
            'key' => 'abo_grabimages',
            'name' => 'Extract Images',
            'description' => 'Find and store company logos and images',
            'scheduler' => 'abo_grabimages.php',
            'frequency' => '*/15 * * * *',
            'enabled' => true
        ],
        
        // Phase 10: Mobile Apps (optional)
        [
            'order' => 10,
            'key' => 'abo_grabgoogleapp',
            'name' => 'Find Google Play App',
            'description' => 'Search for Android app on Google Play Store',
            'scheduler' => 'abo_grabgoogleapp.php',
            'frequency' => '*/10 * * * *',
            'enabled' => false
        ],
        
        [
            'order' => 11,
            'key' => 'abo_grabiosapp',
            'name' => 'Find iOS App',
            'description' => 'Search for iOS app on App Store',
            'scheduler' => 'abo_grabiosapp.php',
            'frequency' => '*/10 * * * *',
            'enabled' => false
        ],
        
        // Phase 11: AI Enhancement
        [
            'order' => 12,
            'key' => 'abo_aienhance',
            'name' => 'AI Enhancement',
            'description' => 'Use AI to enhance and verify extracted data',
            'scheduler' => 'abo_aienhance.php',
            'frequency' => '*/30 * * * *',
            'enabled' => true
        ],
        
        // Phase 12: Validation
        [
            'order' => 13,
            'key' => 'abo_aivalidate',
            'name' => 'AI Validation',
            'description' => 'Validate all collected data and determine automation readiness',
            'scheduler' => 'abo_aivalidate.php',
            'frequency' => '*/30 * * * *',
            'enabled' => true
        ],
        
        // Phase 13: Final Processing
        [
            'order' => 14,
            'key' => 'abo_finalize',
            'name' => 'Finalize Processing',
            'description' => 'Final validation and status update',
            'scheduler' => 'abo_finalize.php',
            'frequency' => '*/30 * * * *',
            'enabled' => true
        ]
    ];
    
    // Store each processor configuration
    foreach ($processors as $processor) {
        $config_data = [
            'order' => $processor['order'],
            'description' => $processor['description'],
            'scheduler' => $processor['scheduler'],
            'frequency' => $processor['frequency'],
            'enabled' => $processor['enabled'],
            'depends_on' => $processor['depends_on'] ?? null
        ];
        
        // Check if config already exists
        $check_sql = "SELECT config_id FROM bg_config 
                     WHERE config_type = 'automation_processor' 
                     AND config_key = :key";
        $stmt = $database->query($check_sql, ['key' => $processor['key']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing
            $update_sql = "UPDATE bg_config 
                          SET config_value = :name,
                              config_data = :data
                          WHERE config_id = :id";
            
            $database->query($update_sql, [
                'name' => $processor['name'],
                'data' => json_encode($config_data),
                'id' => $existing['config_id']
            ]);
            
            $result['message'] = 'Updated existing processor configurations';
        } else {
            // Insert new
            $insert_sql = "INSERT INTO bg_config 
                          (config_type, config_key, config_value, config_data, status)
                          VALUES 
                          ('automation_processor', :key, :name, :data, 'active')";
            
            $database->query($insert_sql, [
                'key' => $processor['key'],
                'name' => $processor['name'],
                'data' => json_encode($config_data)
            ]);
            
            $result['message'] = 'Created new processor configurations';
        }
        
        $result['processors_configured']++;
    }
    
    // Also create the dependency check function
    $dependency_check = "
    -- Function to check if dependencies are met for a processor
    -- A processor can run if:
    -- 1. It has no dependencies, OR
    -- 2. All its dependencies have status 'completed' or 'skipped'
    
    SELECT 
        p.key,
        p.depends_on,
        CASE 
            WHEN p.depends_on IS NULL THEN 'ready'
            WHEN dep.status IN ('completed', 'skipped') THEN 'ready'
            ELSE 'waiting'
        END as can_run
    FROM processors p
    LEFT JOIN processor_status dep ON p.depends_on = dep.processor_key
    ";
    
    $result['dependency_info'] = 'Processors with depends_on will wait for their dependencies to complete';
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
}

// Display result
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);