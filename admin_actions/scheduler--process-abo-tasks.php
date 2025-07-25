<?php
// scheduler--process-abo-tasks.php - Process pending ABO (Automated Business Onboarding) tasks
// Runs every 3 minutes to find and execute the next pending task
// URL: /admin_actions/scheduler--process-abo-tasks.php

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set execution limits
set_time_limit(300); // 5 minutes max
ini_set('memory_limit', '256M');

// Output as JSON for monitoring
header('Content-Type: application/json');

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'task_processed' => false,
    'company_id' => null,
    'task_name' => null,
    'errors' => []
];

try {
    // Get tracking from bg_config to avoid stuck tasks
    $tracking_sql = "SELECT config_value, config_data 
                     FROM bg_config 
                     WHERE config_type = 'abo_tracking' 
                     AND config_key = 'last_processed'";
    $tracking_stmt = $database->query($tracking_sql);
    $tracking = $tracking_stmt->fetch(PDO::FETCH_ASSOC);
    
    $last_processed = null;
    if ($tracking) {
        $tracking_data = json_decode($tracking['config_data'], true);
        $last_processed = [
            'company_id' => $tracking_data['company_id'] ?? null,
            'task_name' => $tracking_data['task_name'] ?? null,
            'timestamp' => $tracking_data['timestamp'] ?? null
        ];
        
        // Skip if same task was processed within last 10 minutes (stuck prevention)
        if ($last_processed['timestamp'] && 
            strtotime($last_processed['timestamp']) > strtotime('-10 minutes')) {
            $skip_company = $last_processed['company_id'];
            $skip_task = $last_processed['task_name'];
        }
    }
    
    // Get all automation processors in order
    $processors_sql = "SELECT config_key, config_value, config_data, display_order 
                       FROM bg_config 
                       WHERE config_type = 'automation_processor' 
                       AND is_active = 1 
                       ORDER BY display_order";
    $processors_stmt = $database->query($processors_sql);
    $processors = $processors_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Find next pending task
    $task_sql = "
        SELECT 
            c.company_id,
            c.company_name,
            c.status as company_status,
            p.config_key as task_name,
            p.config_value as task_display_name,
            p.config_data as task_config,
            p.display_order,
            COALESCE(ca.description, 'pending') as task_status,
            ca.modify_dt as last_update
        FROM bg_companies c
        CROSS JOIN (
            SELECT * FROM bg_config 
            WHERE config_type = 'automation_processor' 
            AND is_active = 1
        ) p
        LEFT JOIN bg_company_attributes ca ON 
            c.company_id = ca.company_id 
            AND ca.type = 'onboarding_progress'
            AND ca.name = p.config_key
            AND ca.status = 'active'
        WHERE 
            c.status IN ('pending_review', 'processing', 'active')
            AND c.source = 'user_recommendation'
            AND (ca.description IS NULL OR ca.description = 'pending')
            " . (isset($skip_company) ? "AND NOT (c.company_id = :skip_company AND p.config_key = :skip_task)" : "") . "
        ORDER BY 
            c.create_dt ASC,  -- Process older companies first
            p.display_order ASC  -- Process tasks in order
        LIMIT 1";
    
    $params = [];
    if (isset($skip_company)) {
        $params = [
            'skip_company' => $skip_company,
            'skip_task' => $skip_task
        ];
    }
    
    $task_stmt = $database->query($task_sql, $params);
    $task = $task_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        $result['message'] = 'No pending tasks found';
        echo json_encode($result);
        exit(0);
    }
    
    // Update tracking
    $database->beginTransaction();
    
    $tracking_data = [
        'company_id' => $task['company_id'],
        'task_name' => $task['task_name'],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $update_tracking_sql = "INSERT INTO bg_config 
                            (config_type, config_key, config_value, config_data, is_active)
                            VALUES 
                            ('abo_tracking', 'last_processed', :value, :data, 1)
                            ON DUPLICATE KEY UPDATE
                            config_value = VALUES(config_value),
                            config_data = VALUES(config_data),
                            updated_at = CURRENT_TIMESTAMP";
    
    $database->query($update_tracking_sql, [
        'value' => "{$task['company_name']} - {$task['task_display_name']}",
        'data' => json_encode($tracking_data)
    ]);
    
    // Mark task as in_progress
    $progress_sql = "INSERT INTO bg_company_attributes 
                     (company_id, type, name, description, status, create_dt)
                     VALUES 
                     (:company_id, 'onboarding_progress', :task_name, 'in_progress', 'active', NOW())
                     ON DUPLICATE KEY UPDATE
                     description = 'in_progress',
                     modify_dt = NOW()";
    
    $database->query($progress_sql, [
        'company_id' => $task['company_id'],
        'task_name' => $task['task_name']
    ]);
    
    $database->commit();
    
    // Process the task
    $result['task_processed'] = true;
    $result['company_id'] = $task['company_id'];
    $result['task_name'] = $task['task_name'];
    $result['company_name'] = $task['company_name'];
    
    // Get task configuration
    $task_config = json_decode($task['task_config'], true);
    $scheduler_file = $task_config['scheduler'] ?? null;
    
    if (!$scheduler_file) {
        throw new Exception("No scheduler file configured for task: {$task['task_name']}");
    }
    
    // Execute the specific task processor
    $processor_path = __DIR__ . '/' . $scheduler_file;
    if (!file_exists($processor_path)) {
        throw new Exception("Processor file not found: $scheduler_file");
    }
    
    // Set parameters for the processor
    $_GET['company_id'] = $task['company_id'];
    $_GET['task_name'] = $task['task_name'];
    $_GET['auto_mode'] = true;
    
    // Include and execute the processor
    ob_start();
    $processor_result = include($processor_path);
    $processor_output = ob_get_clean();
    
    // Check if task completed successfully
    if ($processor_result === true || strpos($processor_output, 'STATUS: SUCCESS') !== false) {
        // Mark task as completed
        $complete_sql = "UPDATE bg_company_attributes 
                         SET description = 'completed', modify_dt = NOW()
                         WHERE company_id = :company_id 
                         AND type = 'onboarding_progress'
                         AND name = :task_name";
        
        $database->query($complete_sql, [
            'company_id' => $task['company_id'],
            'task_name' => $task['task_name']
        ]);
        
        $result['task_status'] = 'completed';
        $result['message'] = "Successfully processed {$task['task_display_name']} for {$task['company_name']}";
        
    } else {
        // Mark task as error
        $error_sql = "UPDATE bg_company_attributes 
                      SET description = 'error', modify_dt = NOW()
                      WHERE company_id = :company_id 
                      AND type = 'onboarding_progress'
                      AND name = :task_name";
        
        $database->query($error_sql, [
            'company_id' => $task['company_id'],
            'task_name' => $task['task_name']
        ]);
        
        // Store error details
        $error_detail_sql = "INSERT INTO bg_company_attributes 
                             (company_id, type, name, description, status, create_dt)
                             VALUES 
                             (:company_id, 'onboarding_error', :error_name, :error_desc, 'active', NOW())";
        
        $database->query($error_detail_sql, [
            'company_id' => $task['company_id'],
            'error_name' => $task['task_name'] . '_error',
            'error_desc' => substr($processor_output, 0, 500)
        ]);
        
        $result['task_status'] = 'error';
        $result['errors'][] = "Task failed - check logs for details";
    }
    
    // Add processor output to result for debugging
    if (isset($_GET['debug'])) {
        $result['processor_output'] = $processor_output;
    }
    
} catch (Exception $e) {
    // Try to rollback if we have an active transaction
    try {
        $database->rollBack();
    } catch (Exception $rollbackException) {
        // Transaction might not have been started, ignore
    }
    
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    error_log("ABO task processor error: " . $e->getMessage());
    
    // Try to mark task as error if we have task info
    if (isset($task['company_id']) && isset($task['task_name'])) {
        try {
            $error_sql = "UPDATE bg_company_attributes 
                          SET description = 'error', modify_dt = NOW()
                          WHERE company_id = :company_id 
                          AND type = 'onboarding_progress'
                          AND name = :task_name";
            
            $database->query($error_sql, [
                'company_id' => $task['company_id'],
                'task_name' => $task['task_name']
            ]);
        } catch (Exception $e2) {
            // Ignore secondary errors
        }
    }
}

// Output result
echo json_encode($result, JSON_PRETTY_PRINT);

// Exit with appropriate code for monitoring
exit($result['status'] === 'success' ? 0 : 1);
?>