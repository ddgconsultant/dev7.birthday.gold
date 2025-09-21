<?php
// fix_missing_processsubmission.php - Add missing abo_processsubmission record for company 6231
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set headers for JSON output
header('Content-Type: application/json');

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'company_id' => 6231,
    'action' => null,
    'message' => null,
    'debug' => []
];

try {
    // First check if the record already exists
    $check_sql = "SELECT * FROM bg_company_attributes 
                  WHERE company_id = :company_id 
                  AND type = 'onboarding_progress' 
                  AND name = 'abo_processsubmission'";
    
    $check_stmt = $database->query($check_sql, ['company_id' => 6231]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Record already exists
        $result['action'] = 'no_action';
        $result['message'] = 'Record already exists with status: ' . $existing['description'];
        $result['existing_record'] = $existing;
    } else {
        // Insert the missing record
        $insert_sql = "INSERT INTO bg_company_attributes 
                       (company_id, type, name, description, status, create_dt, modify_dt)
                       VALUES 
                       (:company_id, 'onboarding_progress', 'abo_processsubmission', 'pending', 'active', NOW(), NOW())";
        
        $database->query($insert_sql, ['company_id' => 6231]);
        
        $result['action'] = 'record_created';
        $result['message'] = 'Successfully created missing abo_processsubmission record with pending status';
        
        // Verify the insert
        $verify_stmt = $database->query($check_sql, ['company_id' => 6231]);
        $created = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        $result['created_record'] = $created;
    }
    
    // Get company details for context
    $company_sql = "SELECT company_id, company_name, status, create_dt FROM bg_companies WHERE company_id = :company_id";
    $company_stmt = $database->query($company_sql, ['company_id' => 6231]);
    $company = $company_stmt->fetch(PDO::FETCH_ASSOC);
    $result['company_info'] = $company;
    
    // Get all onboarding progress records for this company
    $progress_sql = "SELECT name, description as status, create_dt, modify_dt 
                     FROM bg_company_attributes 
                     WHERE company_id = :company_id 
                     AND type = 'onboarding_progress'
                     ORDER BY name";
    
    $progress_stmt = $database->query($progress_sql, ['company_id' => 6231]);
    $progress_records = $progress_stmt->fetchAll(PDO::FETCH_ASSOC);
    $result['all_progress_records'] = $progress_records;
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['message'] = 'Error: ' . $e->getMessage();
    $result['error_trace'] = $e->getTraceAsString();
}

// Output formatted JSON
echo json_encode($result, JSON_PRETTY_PRINT);
?>