<?php
// abo_finalize.php - Final validation and activation of business
// Part of the Automation Business Onboarding (ABO) system
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get company ID - support both encoded and raw for debugging
$specific_company_id = null;

if (isset($_GET['rawid'])) {
    // Debug mode - use raw ID directly
    $specific_company_id = intval($_GET['rawid']);
} elseif (isset($_GET['id'])) {
    // Production mode - decode the ID
    $encoded_id = $_GET['id'];
    $specific_company_id = $qik->decodeID($encoded_id);
}

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processor' => 'abo_finalize',
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => []
];

try {
    // Get companies to process
    if ($specific_company_id) {
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.company_id = :company_id 
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_finalize'
                AND ca.description IN ('pending', 'error')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company ready for finalization
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status IN ('pending_final_review', 'approved_pending_data')
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_finalize'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies pending finalization';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    $result['processed'] = 1;
    $company_id = $company['company_id'];
    $company_name = $company['company_name'];
    
    try {
        $database->beginTransaction();
        
        // Update progress to in_progress
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = 'in_progress', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_finalize'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        // Check validation status
        $val_sql = "SELECT description FROM bg_company_attributes 
                    WHERE company_id = :company_id 
                    AND type = 'ai_validation' 
                    AND name = 'validation_score'
                    AND status = 'active'
                    ORDER BY create_dt DESC 
                    LIMIT 1";
        $stmt = $database->query($val_sql, ['company_id' => $company_id]);
        $validation_score = intval($stmt->fetchColumn());
        
        // Check all required tasks are completed
        $tasks_sql = "SELECT 
                        SUM(CASE WHEN ca.description = 'completed' THEN 1 ELSE 0 END) as completed,
                        COUNT(*) as total
                      FROM bg_config c
                      LEFT JOIN bg_company_attributes ca ON 
                        ca.company_id = :company_id 
                        AND ca.type = 'onboarding_progress'
                        AND ca.name = c.config_key
                      WHERE c.config_type = 'automation_processor'
                      AND c.is_active = 1
                      AND c.config_key != 'abo_finalize'";
        $stmt = $database->query($tasks_sql, ['company_id' => $company_id]);
        $task_status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $completion_rate = ($task_status['total'] > 0) 
            ? ($task_status['completed'] / $task_status['total']) * 100 
            : 0;
        
        // Determine if company is ready for activation
        $is_ready = false;
        $activation_notes = [];
        
        if ($validation_score >= 75 && $completion_rate >= 80) {
            $is_ready = true;
            $activation_notes[] = "High validation score ($validation_score%) and completion rate ($completion_rate%)";
        } elseif ($validation_score >= 60 && $completion_rate >= 90) {
            $is_ready = true;
            $activation_notes[] = "Acceptable validation score ($validation_score%) with high completion rate ($completion_rate%)";
        } else {
            $activation_notes[] = "Validation score: $validation_score%, Completion rate: $completion_rate%";
            $activation_notes[] = "Requires manual review before activation";
        }
        
        // Check for critical issues
        $issues_sql = "SELECT COUNT(*) FROM bg_company_attributes 
                      WHERE company_id = :company_id 
                      AND type = 'validation_issue' 
                      AND name = 'critical'
                      AND status = 'active'";
        $stmt = $database->query($issues_sql, ['company_id' => $company_id]);
        $critical_issues = intval($stmt->fetchColumn());
        
        if ($critical_issues > 0) {
            $is_ready = false;
            $activation_notes[] = "$critical_issues critical issues found";
        }
        
        // Store finalization results
        $finalize_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'finalization', :name, :data, 'active', NOW())
                        ON DUPLICATE KEY UPDATE 
                        description = VALUES(description),
                        modify_dt = NOW()";
        
        $finalization_data = [
            'validation_score' => $validation_score,
            'completion_rate' => $completion_rate,
            'critical_issues' => $critical_issues,
            'is_ready' => $is_ready,
            'notes' => $activation_notes,
            'finalized_at' => date('Y-m-d H:i:s')
        ];
        
        $database->query($finalize_sql, [
            'company_id' => $company_id,
            'name' => 'finalization_results',
            'data' => json_encode($finalization_data)
        ]);
        
        // Update company status based on finalization
        if ($is_ready) {
            // Ready for activation
            $status_sql = "UPDATE bg_companies 
                          SET status = 'active', 
                              enrollment_status = 'active',
                              modify_dt = NOW() 
                          WHERE company_id = :company_id";
            $database->query($status_sql, ['company_id' => $company_id]);
            
            // Create activation record
            $activate_sql = "INSERT INTO bg_company_attributes 
                            (company_id, type, name, description, status, create_dt)
                            VALUES 
                            (:company_id, 'activation', 'auto_activated', :notes, 'active', NOW())";
            $database->query($activate_sql, [
                'company_id' => $company_id,
                'notes' => json_encode([
                    'method' => 'abo_finalize',
                    'validation_score' => $validation_score,
                    'completion_rate' => $completion_rate,
                    'activated_at' => date('Y-m-d H:i:s')
                ])
            ]);
            
            $result['data_collected'] = "Company activated successfully";
        } else {
            // Needs manual review
            $status_sql = "UPDATE bg_companies 
                          SET status = 'pending_review',
                              modify_dt = NOW() 
                          WHERE company_id = :company_id";
            $database->query($status_sql, ['company_id' => $company_id]);
            
            $result['data_collected'] = "Company requires manual review";
        }
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = 'completed', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_finalize'";
        $database->query($complete_sql, ['company_id' => $company_id]);
        
        $database->commit();
        
        $result['successful'] = 1;
        $result['finalization_summary'] = $finalization_data;
        
    } catch (Exception $e) {
        $database->rollBack();
        
        // Update progress to error
        $error_sql = "UPDATE bg_company_attributes 
                     SET description = 'error', modify_dt = NOW() 
                     WHERE company_id = :company_id 
                     AND type = 'onboarding_progress' 
                     AND name = 'abo_finalize'";
        $database->query($error_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        session_tracking('ABO finalize error', "Company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    session_tracking('ABO finalize fatal error', $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
?>