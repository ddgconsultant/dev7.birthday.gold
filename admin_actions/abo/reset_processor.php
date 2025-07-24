<?php
// reset_processor.php - Reset a specific ABO processor back to pending status
// Part of the Automation Business Onboarding (ABO) system
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set headers for JSON output
header('Content-Type: application/json');

// Initialize result array
$result = [
    'status' => 'error',
    'timestamp' => date('Y-m-d H:i:s'),
    'message' => '',
    'data' => []
];

// Get parameters
$company_id = null;
$processor_name = null;

// Handle company_id - support multiple formats
if (isset($_REQUEST['company_id'])) {
    $company_id = intval($_REQUEST['company_id']);
} elseif (isset($_REQUEST['rawid'])) {
    $company_id = intval($_REQUEST['rawid']);
} elseif (isset($_REQUEST['id'])) {
    $company_id = $qik->decodeID($_REQUEST['id']);
}

// Get processor name
if (isset($_REQUEST['processor_name'])) {
    $processor_name = trim($_REQUEST['processor_name']);
} elseif (isset($_REQUEST['processor'])) {
    $processor_name = trim($_REQUEST['processor']);
}

// Validate parameters
if (!$company_id) {
    $result['message'] = 'Missing or invalid company_id parameter. Use ?company_id=XXX or ?rawid=XXX';
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

if (!$processor_name) {
    $result['message'] = 'Missing processor_name parameter. Use ?processor_name=abo_grabgoogleapp';
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

try {
    // Begin transaction
    $database->beginTransaction();
    
    // Get company details
    $company_sql = "SELECT company_id, company_name, status FROM bg_companies WHERE company_id = :company_id";
    $company_stmt = $database->query($company_sql, ['company_id' => $company_id]);
    $company = $company_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $database->rollBack();
        $result['message'] = 'Company not found with ID: ' . $company_id;
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }
    
    $result['data']['company'] = $company;
    
    // Check if the processor exists in the onboarding progress
    $check_sql = "SELECT attribute_id, description as current_status, modify_dt 
                  FROM bg_company_attributes 
                  WHERE company_id = :company_id 
                  AND type = 'onboarding_progress' 
                  AND name = :processor_name";
    
    $check_stmt = $database->query($check_sql, [
        'company_id' => $company_id,
        'processor_name' => $processor_name
    ]);
    
    $progress_record = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$progress_record) {
        $database->rollBack();
        $result['message'] = 'Processor progress record not found for processor: ' . $processor_name;
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }
    
    $result['data']['previous_status'] = $progress_record['current_status'];
    $result['data']['last_modified'] = $progress_record['modify_dt'];
    
    // Reset the processor status to pending
    $reset_sql = "UPDATE bg_company_attributes 
                  SET description = 'pending', 
                      modify_dt = NOW() 
                  WHERE company_id = :company_id 
                  AND type = 'onboarding_progress' 
                  AND name = :processor_name";
    
    $database->query($reset_sql, [
        'company_id' => $company_id,
        'processor_name' => $processor_name
    ]);
    
    // Clear any existing data collected by this processor
    $cleared_data = [];
    
    // Processor-specific data cleanup
    switch ($processor_name) {
        case 'abo_grabgoogleapp':
            // Clear Google app data
            $delete_app_data_sql = "DELETE FROM bg_company_attributes 
                                   WHERE company_id = :company_id 
                                   AND type = 'data_collection' 
                                   AND name IN ('google_app_id', 'google_app_url', 'google_app_search')";
            
            $delete_stmt = $database->query($delete_app_data_sql, ['company_id' => $company_id]);
            $cleared_data['google_app_attributes'] = $delete_stmt->rowCount();
            
            // Clear from main company record - appgoogle field
            $clear_company_sql = "UPDATE bg_companies 
                                 SET appgoogle = NULL,
                                     modify_dt = NOW()
                                 WHERE company_id = :company_id";
            
            $database->query($clear_company_sql, ['company_id' => $company_id]);
            $cleared_data['company_appgoogle'] = 'cleared';
            break;
            
        case 'abo_grabinstagramprofile':
            // Clear Instagram data
            $delete_ig_data_sql = "DELETE FROM bg_company_attributes 
                                  WHERE company_id = :company_id 
                                  AND type = 'data_collection' 
                                  AND name IN ('instagram_username', 'instagram_profile_url', 'instagram_search')";
            
            $delete_stmt = $database->query($delete_ig_data_sql, ['company_id' => $company_id]);
            $cleared_data['instagram_attributes'] = $delete_stmt->rowCount();
            break;
            
        case 'abo_grabfacebookprofile':
            // Clear Facebook data
            $delete_fb_data_sql = "DELETE FROM bg_company_attributes 
                                  WHERE company_id = :company_id 
                                  AND type = 'data_collection' 
                                  AND name IN ('facebook_page_url', 'facebook_page_id', 'facebook_search')";
            
            $delete_stmt = $database->query($delete_fb_data_sql, ['company_id' => $company_id]);
            $cleared_data['facebook_attributes'] = $delete_stmt->rowCount();
            break;
            
        case 'abo_processsubmission':
            // Clear submission processing data
            $delete_sub_data_sql = "DELETE FROM bg_company_attributes 
                                   WHERE company_id = :company_id 
                                   AND type = 'data_collection' 
                                   AND name LIKE 'submission_%'";
            
            $delete_stmt = $database->query($delete_sub_data_sql, ['company_id' => $company_id]);
            $cleared_data['submission_attributes'] = $delete_stmt->rowCount();
            break;
            
        default:
            // Generic cleanup for unknown processors
            $delete_generic_sql = "DELETE FROM bg_company_attributes 
                                  WHERE company_id = :company_id 
                                  AND type = 'data_collection' 
                                  AND create_dt >= (
                                      SELECT modify_dt 
                                      FROM bg_company_attributes 
                                      WHERE company_id = :company_id2 
                                      AND type = 'onboarding_progress' 
                                      AND name = :processor_name
                                      LIMIT 1
                                  )";
            
            $delete_stmt = $database->query($delete_generic_sql, [
                'company_id' => $company_id,
                'company_id2' => $company_id,
                'processor_name' => $processor_name
            ]);
            $cleared_data['generic_attributes'] = $delete_stmt->rowCount();
    }
    
    $result['data']['cleared_data'] = $cleared_data;
    
    // Commit transaction
    $database->commit();
    
    // Success
    $result['status'] = 'success';
    $result['message'] = sprintf(
        'Successfully reset processor "%s" for company "%s" (ID: %d) from "%s" to "pending"',
        $processor_name,
        $company['company_name'],
        $company_id,
        $progress_record['current_status']
    );
    
    // Log the reset action
    $log_sql = "INSERT INTO bg_company_attributes 
                (company_id, type, name, description, status, create_dt)
                VALUES 
                (:company_id, 'audit_log', 'processor_reset', :details, 'active', NOW())";
    
    $log_details = json_encode([
        'processor' => $processor_name,
        'previous_status' => $progress_record['current_status'],
        'reset_by' => $_SESSION['user']['user_id'] ?? 'system',
        'cleared_data' => $cleared_data
    ]);
    
    $database->query($log_sql, [
        'company_id' => $company_id,
        'details' => $log_details
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    try {
        $database->rollBack();
    } catch (Exception $rollbackException) {
        // Transaction might not have been started
    }
    
    $result['message'] = 'Database error: ' . $e->getMessage();
    $result['data']['error_details'] = [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
}

// Output result
echo json_encode($result, JSON_PRETTY_PRINT);