<?php
// Include site controller for authentication and database access
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php';

// Admin access is handled by site-controller.php
// This file is in /admin_actions/ directory which should have proper access control

// Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Invalid request method</div>';
    header('Location: /admin/company-editor-main');
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['message'] = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Invalid security token</div>';
    header('Location: /admin/company-editor-main');
    exit;
}

// Get and validate form data
$company_id = intval($_POST['company_id'] ?? 0);
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$zip_code = trim($_POST['zip_code'] ?? '');
$location_id = intval($_POST['location_id'] ?? 0); // For updates

// Validate required fields
if (!$company_id || !$address || !$city || !$state) {
    $_SESSION['message'] = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Required fields are missing</div>';
    header('Location: /admin/company-editor-main?cid=' . $company_id . '#locations');
    exit;
}

// Verify company exists and user has access
$check_sql = "SELECT company_id FROM bg_companies WHERE company_id = :company_id";
$check_stmt = $database->prepare($check_sql);
$check_stmt->execute(['company_id' => $company_id]);
if (!$check_stmt->fetch()) {
    $_SESSION['message'] = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Invalid company</div>';
    header('Location: /admin/company-editor-main');
    exit;
}

try {
    $database->beginTransaction();
    
    if ($location_id) {
        // Update existing location
        $sql = "UPDATE bg_company_locations 
                SET address = :address,
                    city = :city,
                    state = :state,
                    zip_code = :zip_code,
                    modify_dt = NOW()
                WHERE location_id = :location_id 
                AND company_id = :company_id";
        
        $stmt = $database->prepare($sql);
        $stmt->execute([
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip_code' => $zip_code,
            'location_id' => $location_id,
            'company_id' => $company_id
        ]);
        
        $_SESSION['message'] = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Location updated successfully</div>';
        
    } else {
        // Insert new location
        $sql = "INSERT INTO bg_company_locations 
                (company_id, address, city, state, zip_code, status, create_dt, modify_dt) 
                VALUES 
                (:company_id, :address, :city, :state, :zip_code, 'active', NOW(), NOW())";
        
        $stmt = $database->prepare($sql);
        $stmt->execute([
            'company_id' => $company_id,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip_code' => $zip_code
        ]);
        
        $location_id = $database->lastInsertId();
        
        // Log the location creation
        $log_sql = "INSERT INTO bg_company_attributes 
                    (company_id, type, name, description, status, create_dt) 
                    VALUES 
                    (:company_id, 'location_added', 'admin_action', :description, 'active', NOW())";
        
        $log_stmt = $database->prepare($log_sql);
        $log_stmt->execute([
            'company_id' => $company_id,
            'description' => json_encode([
                'location_id' => $location_id,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zip_code' => $zip_code,
                'added_by' => $_SESSION['user']['user_id'] ?? 0
            ])
        ]);
        
        $_SESSION['message'] = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Location added successfully</div>';
    }
    
    $database->commit();
    
} catch (Exception $e) {
    $database->rollBack();
    error_log("Save location error: " . $e->getMessage());
    $_SESSION['message'] = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> An error occurred while saving the location</div>';
}

// Redirect back to company editor locations tab
header('Location: /admin/company-editor-main?cid=' . $company_id . '#locations');
exit;