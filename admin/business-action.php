<?php
// business-action.php - Process admin actions for business submissions
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Admin access is handled by site-controller.php
// This page is in /admin/ directory which should have proper access control

// Validate request
if (!$app->formposted() || !isset($_POST['action']) || !isset($_POST['company_id'])) {
    $system->addmessage('error', 'Invalid request');
    header('Location: /admin/business-submissions.php');
    exit;
}

$company_id = intval($_POST['company_id']);
$action = $_POST['action'];

// Verify company exists and get current status
$check_sql = "SELECT company_id, company_name, status FROM bg_companies WHERE company_id = :id LIMIT 1";
$check_stmt = $database->query($check_sql, ['id' => $company_id]);
$company = $check_stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    $system->addmessage('error', 'Company not found');
    header('Location: /admin/business-submissions.php');
    exit;
}

try {
    $database->beginTransaction();
    
    switch ($action) {
        case 'approve':
            // Update company status
            $update_sql = "UPDATE bg_companies SET 
                          status = 'approved_pending_data', 
                          company_status = 'approved_pending_data' 
                          WHERE company_id = :id";
            $database->query($update_sql, ['id' => $company_id]);
            
            // Log approval action
            $log_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'approval', 'approved_by', :user_id, 'active', NOW())";
            $database->query($log_sql, [
                'company_id' => $company_id,
                'user_id' => $current_user_data['user_id']
            ]);
            
            // Log approval timestamp
            $time_sql = "INSERT INTO bg_company_attributes 
                         (company_id, type, name, description, status, create_dt)
                         VALUES 
                         (:company_id, 'approval', 'approved_at', :timestamp, 'active', NOW())";
            $database->query($time_sql, [
                'company_id' => $company_id,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            // Only add the initial processor step - processsubmission
            // Other steps will be added only after this completes successfully
            $initial_processor = 'abo_processsubmission';
            
            $attr_sql = "INSERT INTO bg_company_attributes 
                         (company_id, type, name, description, status, create_dt)
                         VALUES 
                         (:company_id, 'onboarding_progress', :processor_name, 'pending', 'active', NOW())";
            $database->query($attr_sql, [
                'company_id' => $company_id,
                'processor_name' => $initial_processor
            ]);
            
            // Log that we only added the initial step
            $log_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'metadata', 'onboarding_initialized', 'Initial processor added', 'active', NOW())";
            $database->query($log_sql, [
                'company_id' => $company_id
            ]);
            
            $database->commit();
            $system->addmessage('success', 'Business approved and automation steps initialized');
            break;
            
        case 'reject':
            // Update company status
            $update_sql = "UPDATE bg_companies SET 
                          status = 'rejected', 
                          company_status = 'rejected' 
                          WHERE company_id = :id";
            $database->query($update_sql, ['id' => $company_id]);
            
            // Log rejection action
            $log_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'approval', 'rejected_by', :user_id, 'active', NOW())";
            $database->query($log_sql, [
                'company_id' => $company_id,
                'user_id' => $current_user_data['user_id']
            ]);
            
            // Log rejection timestamp
            $time_sql = "INSERT INTO bg_company_attributes 
                         (company_id, type, name, description, status, create_dt)
                         VALUES 
                         (:company_id, 'approval', 'rejected_at', :timestamp, 'active', NOW())";
            $database->query($time_sql, [
                'company_id' => $company_id,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            // Optional: Log rejection reason if provided
            if (!empty($_POST['rejection_reason'])) {
                $reason_sql = "INSERT INTO bg_company_attributes 
                               (company_id, type, name, description, status, create_dt)
                               VALUES 
                               (:company_id, 'approval', 'rejection_reason', :reason, 'active', NOW())";
                $database->query($reason_sql, [
                    'company_id' => $company_id,
                    'reason' => $_POST['rejection_reason']
                ]);
            }
            
            $database->commit();
            $system->addmessage('info', 'Business submission rejected');
            break;
            
        case 'pending':
            // Return to pending review status
            $update_sql = "UPDATE bg_companies SET 
                          status = 'pending_review', 
                          company_status = 'pending_review' 
                          WHERE company_id = :id";
            $database->query($update_sql, ['id' => $company_id]);
            
            $database->commit();
            $system->addmessage('info', 'Business marked for review');
            break;
            
        default:
            $database->rollBack();
            $system->addmessage('error', 'Invalid action');
            break;
    }
    
} catch (Exception $e) {
    $database->rollBack();
    error_log("Business action error: " . $e->getMessage());
    $system->addmessage('error', 'An error occurred while processing your request');
}

// Redirect back to business submissions page
$redirect_status = $_POST['redirect_status'] ?? 'all';
header('Location: /admin/business-submissions.php?status=' . $redirect_status);
exit;