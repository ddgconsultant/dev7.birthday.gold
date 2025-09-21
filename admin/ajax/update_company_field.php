<?php
/**
 * AJAX handler for updating individual company fields
 * Provides real-time saving functionality
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check admin access
if (!$account->checkrole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$company_id = $_POST['company_id'] ?? null;
$field = $_POST['field'] ?? null;
$value = $_POST['value'] ?? '';

if (!$company_id || !$field) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Define allowed fields and their validation rules
$allowed_fields = [
    // Basic company fields
    'company_name' => ['table' => 'bg_companies', 'type' => 'string', 'required' => true],
    'company_display_name' => ['table' => 'bg_companies', 'type' => 'string'],
    'category' => ['table' => 'bg_companies', 'type' => 'string'],
    'company_url' => ['table' => 'bg_companies', 'type' => 'url'],
    'signup_url' => ['table' => 'bg_companies', 'type' => 'url'],
    'info_url' => ['table' => 'bg_companies', 'type' => 'url'],
    'description' => ['table' => 'bg_companies', 'type' => 'text'],
    'minage' => ['table' => 'bg_companies', 'type' => 'integer', 'min' => 0, 'max' => 100],
    'maxage' => ['table' => 'bg_companies', 'type' => 'integer', 'min' => 0, 'max' => 250],
    
    // Business details (stored in bg_companies or bg_company_attributes)
    'ein' => ['table' => 'bg_companies', 'type' => 'string'],
    'dba_name' => ['table' => 'bg_companies', 'type' => 'string'],
    'founded_year' => ['table' => 'bg_companies', 'type' => 'integer', 'min' => 1800, 'max' => date('Y')],
    'business_type' => ['table' => 'bg_companies', 'type' => 'string'],
    
    // Contact information
    'primary_contact_name' => ['table' => 'bg_companies', 'type' => 'string'],
    'primary_contact_email' => ['table' => 'bg_companies', 'type' => 'email'],
    'primary_contact_phone' => ['table' => 'bg_companies', 'type' => 'phone'],
    'support_email' => ['table' => 'bg_companies', 'type' => 'email'],
    'support_phone' => ['table' => 'bg_companies', 'type' => 'phone'],
    
    // Social media
    'facebook' => ['table' => 'bg_companies', 'type' => 'url'],
    'twitter' => ['table' => 'bg_companies', 'type' => 'url'],
    'instagram' => ['table' => 'bg_companies', 'type' => 'url'],
    'tiktok' => ['table' => 'bg_companies', 'type' => 'url'],
    'youtube' => ['table' => 'bg_companies', 'type' => 'url'],
    'linkedin' => ['table' => 'bg_companies', 'type' => 'url'],
    
    // Birthday program settings
    'birthday_program_name' => ['table' => 'bg_companies', 'type' => 'string'],
    'enrollment_type' => ['table' => 'bg_companies', 'type' => 'string'],
    'advance_notice_days' => ['table' => 'bg_companies', 'type' => 'integer', 'min' => 0, 'max' => 60],
    'reward_validity_days' => ['table' => 'bg_companies', 'type' => 'integer', 'min' => 1, 'max' => 365],
    'verification_required' => ['table' => 'bg_companies', 'type' => 'boolean'],
    'multi_location_redemption' => ['table' => 'bg_companies', 'type' => 'boolean'],
    'online_redemption' => ['table' => 'bg_companies', 'type' => 'boolean']
];

// Check if field is allowed
if (!isset($allowed_fields[$field])) {
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit;
}

$field_config = $allowed_fields[$field];

// Validate value based on type
$validation_error = null;
switch ($field_config['type']) {
    case 'string':
        if (isset($field_config['required']) && $field_config['required'] && empty($value)) {
            $validation_error = 'This field is required';
        }
        break;
        
    case 'email':
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $validation_error = 'Invalid email address';
        }
        break;
        
    case 'url':
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $validation_error = 'Invalid URL';
        }
        break;
        
    case 'integer':
        if (!is_numeric($value)) {
            $validation_error = 'Must be a number';
        } elseif (isset($field_config['min']) && $value < $field_config['min']) {
            $validation_error = "Minimum value is {$field_config['min']}";
        } elseif (isset($field_config['max']) && $value > $field_config['max']) {
            $validation_error = "Maximum value is {$field_config['max']}";
        }
        break;
        
    case 'boolean':
        $value = $value ? 1 : 0;
        break;
}

if ($validation_error) {
    echo json_encode(['success' => false, 'message' => $validation_error]);
    exit;
}

try {
    // Check if column exists in the table
    $table = $field_config['table'];
    
    // For bg_companies table
    if ($table === 'bg_companies') {
        // First check if column exists
        $check_column_sql = "SHOW COLUMNS FROM bg_companies LIKE :field";
        $stmt = $database->query($check_column_sql, ['field' => $field]);
        
        if ($stmt->fetch()) {
            // Column exists, update directly
            $update_sql = "UPDATE bg_companies SET `$field` = :value, modify_dt = NOW() WHERE company_id = :company_id";
            $database->query($update_sql, [
                'value' => $value,
                'company_id' => $company_id
            ]);
        } else {
            // Column doesn't exist, store in bg_company_attributes
            $attr_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'company_detail', :name, :value, 'active', NOW())
                        ON DUPLICATE KEY UPDATE 
                        description = VALUES(description),
                        modify_dt = NOW()";
            
            $database->query($attr_sql, [
                'company_id' => $company_id,
                'name' => $field,
                'value' => $value
            ]);
        }
    }
    
    // Log the update
    $log_sql = "INSERT INTO bg_company_attributes 
                (company_id, type, name, description, status, create_dt)
                VALUES 
                (:company_id, 'audit_log', 'field_update', :details, 'active', NOW())";
    
    $log_details = json_encode([
        'field' => $field,
        'value' => $value,
        'updated_by' => $_SESSION['user']['user_id'] ?? 0,
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);
    
    $database->query($log_sql, [
        'company_id' => $company_id,
        'details' => $log_details
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Field updated successfully']);
    
} catch (Exception $e) {
    error_log("Error updating company field: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}