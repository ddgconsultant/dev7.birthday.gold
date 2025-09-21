<?php
// abo_check_dependencies.php - Check if a processor's dependencies are met
// Created: 2025-01-31

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

/**
 * Check if a processor can run based on its dependencies
 * 
 * @param int $company_id Company to check
 * @param string $processor_key Processor to check (e.g., 'abo_grablocations_airtop')
 * @return array Status information
 */
function canProcessorRun($database, $company_id, $processor_key) {
    // Get processor configuration
    $config_sql = "SELECT config_data FROM bg_config 
                  WHERE config_type = 'automation_processor' 
                  AND config_key = :key";
    $stmt = $database->query($config_sql, ['key' => $processor_key]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        return [
            'can_run' => false,
            'reason' => 'Processor not configured'
        ];
    }
    
    $config_data = json_decode($config['config_data'], true);
    
    // Check if processor is enabled (default to false if not set)
    if (isset($config_data['enabled']) && !$config_data['enabled']) {
        return [
            'can_run' => false,
            'reason' => 'Processor is disabled'
        ];
    }
    
    // Check dependencies
    if (!empty($config_data['depends_on'])) {
        $dependency = $config_data['depends_on'];
        
        // Check if dependency has completed
        $dep_sql = "SELECT description as status 
                   FROM bg_company_attributes 
                   WHERE company_id = :company_id 
                   AND type = 'onboarding_progress'
                   AND name = :dependency";
        
        $stmt = $database->query($dep_sql, [
            'company_id' => $company_id,
            'dependency' => $dependency
        ]);
        $dep_status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$dep_status) {
            return [
                'can_run' => false,
                'reason' => "Dependency {$dependency} has not started"
            ];
        }
        
        if (!in_array($dep_status['status'], ['completed', 'skipped'])) {
            return [
                'can_run' => false,
                'reason' => "Dependency {$dependency} is {$dep_status['status']}"
            ];
        }
    }
    
    // Check if processor has already completed
    $status_sql = "SELECT description as status 
                  FROM bg_company_attributes 
                  WHERE company_id = :company_id 
                  AND type = 'onboarding_progress'
                  AND name = :processor";
    
    $stmt = $database->query($status_sql, [
        'company_id' => $company_id,
        'processor' => $processor_key
    ]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($status && in_array($status['status'], ['completed', 'in_progress'])) {
        return [
            'can_run' => false,
            'reason' => "Processor is already {$status['status']}"
        ];
    }
    
    return [
        'can_run' => true,
        'reason' => 'All dependencies met'
    ];
}

// If called directly, check a specific processor
if (isset($_GET['company_id']) && isset($_GET['processor'])) {
    $company_id = intval($_GET['company_id']);
    $processor = $_GET['processor'];
    
    $result = canProcessorRun($database, $company_id, $processor);
    
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
} else {
    // Show all processors and their status for a company
    $company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : 6271;
    
    // Get all processor configs
    $sql = "SELECT config_key, config_value, config_data 
           FROM bg_config 
           WHERE config_type = 'automation_processor'
           ORDER BY JSON_EXTRACT(config_data, '$.order')";
    
    $stmt = $database->query($sql);
    $processors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [
        'company_id' => $company_id,
        'processors' => []
    ];
    
    foreach ($processors as $proc) {
        $config = json_decode($proc['config_data'], true);
        
        // Get current status
        $status_sql = "SELECT description as status, modify_dt 
                      FROM bg_company_attributes 
                      WHERE company_id = :company_id 
                      AND type = 'onboarding_progress'
                      AND name = :processor";
        
        $stmt = $database->query($status_sql, [
            'company_id' => $company_id,
            'processor' => $proc['config_key']
        ]);
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $can_run = canProcessorRun($database, $company_id, $proc['config_key']);
        
        $result['processors'][] = [
            'order' => $config['order'] ?? null,
            'key' => $proc['config_key'],
            'name' => $proc['config_value'],
            'status' => $status['status'] ?? 'not_started',
            'last_run' => $status['modify_dt'] ?? null,
            'depends_on' => $config['depends_on'] ?? null,
            'can_run' => $can_run['can_run'],
            'reason' => $can_run['reason']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
}