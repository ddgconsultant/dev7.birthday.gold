<?php
// initialize_company_progress.php - Initialize all ABO onboarding progress records for a company
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set headers for JSON output
header('Content-Type: application/json');

// Get company ID from request
$company_id = null;
if (isset($_GET['company_id'])) {
    $company_id = intval($_GET['company_id']);
} elseif (isset($_GET['rawid'])) {
    $company_id = intval($_GET['rawid']);
} elseif (isset($_GET['id'])) {
    $company_id = $qik->decodeID($_GET['id']);
}

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'company_id' => $company_id,
    'processors_initialized' => 0,
    'processors_skipped' => 0,
    'errors' => []
];

if (!$company_id) {
    $result['status'] = 'error';
    $result['message'] = 'No company ID provided. Use ?company_id=XXX or ?rawid=XXX';
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

try {
    // Get company details
    $company_sql = "SELECT company_id, company_name, status FROM bg_companies WHERE company_id = :company_id";
    $company_stmt = $database->query($company_sql, ['company_id' => $company_id]);
    $company = $company_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['status'] = 'error';
        $result['message'] = 'Company not found';
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }
    
    $result['company'] = $company;
    
    // Get all automation processors from bg_config
    $processors_sql = "SELECT config_key, config_value, config_data 
                       FROM bg_config 
                       WHERE config_type = 'automation_processor' 
                       AND `status` = 'active'
                       ORDER BY display_order";
    
    $processors_stmt = $database->query($processors_sql);
    $processors = $processors_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result['total_processors'] = count($processors);
    
    // Begin transaction
    $database->beginTransaction();
    
    foreach ($processors as $processor) {
        $processor_key = $processor['config_key'];
        $processor_name = $processor['config_value'];
        
        // Check if progress record already exists
        $check_sql = "SELECT attribute_id FROM bg_company_attributes 
                      WHERE company_id = :company_id 
                      AND type = 'onboarding_progress' 
                      AND name = :processor_key";
        
        $check_stmt = $database->query($check_sql, [
            'company_id' => $company_id,
            'processor_key' => $processor_key
        ]);
        
        if ($check_stmt->fetch()) {
            // Record already exists
            $result['processors_skipped']++;
            $result['skipped'][] = $processor_key;
        } else {
            // Insert new progress record
            $insert_sql = "INSERT INTO bg_company_attributes 
                           (company_id, type, name, description, status, create_dt, modify_dt)
                           VALUES 
                           (:company_id, 'onboarding_progress', :processor_key, 'pending', 'active', NOW(), NOW())";
            
            $database->query($insert_sql, [
                'company_id' => $company_id,
                'processor_key' => $processor_key
            ]);
            
            $result['processors_initialized']++;
            $result['initialized'][] = $processor_key;
        }
    }
    
    // Commit transaction
    $database->commit();
    
    // Get final state of all progress records
    $progress_sql = "SELECT name, description as status, create_dt, modify_dt 
                     FROM bg_company_attributes 
                     WHERE company_id = :company_id 
                     AND type = 'onboarding_progress'
                     ORDER BY name";
    
    $progress_stmt = $database->query($progress_sql, ['company_id' => $company_id]);
    $result['final_progress_state'] = $progress_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result['message'] = sprintf(
        'Initialized %d processors, skipped %d existing records',
        $result['processors_initialized'],
        $result['processors_skipped']
    );
    
} catch (Exception $e) {
    $database->rollBack();
    $result['status'] = 'error';
    $result['message'] = 'Error: ' . $e->getMessage();
    $result['error_trace'] = $e->getTraceAsString();
}

// Output formatted JSON
echo json_encode($result, JSON_PRETTY_PRINT);
?>