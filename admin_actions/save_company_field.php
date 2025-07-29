<?php
// save_company_field.php
// AJAX endpoint for updating individual company fields

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check authentication
if (!$account->isloggedin() || !$account->checkrole('admin')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$company_id = $_POST['company_id'] ?? null;
$field = $_POST['field'] ?? null;
$value = $_POST['value'] ?? '';

// Validate inputs
if (!$company_id || !$field) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Define allowed fields for security
$allowed_fields = [
    'company_name',
    'company_display_name',
    'category',
    'company_url',
    'signup_url',
    'info_url',
    'appgoogle',
    'appapple',
    'facebook',
    'twitter',
    'instagram',
    'tiktok',
    'youtube',
    'linkedin',
    'region_type',
    'status',
    'minimum_age',
    'maximum_age',
    'parent_company'
];

// Check if field is allowed
if (!in_array($field, $allowed_fields)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit;
}

// Clean the value
$value = trim($value);

// Special handling for certain fields
if (in_array($field, ['company_url', 'signup_url', 'info_url', 'appgoogle', 'appapple', 'facebook', 'twitter', 'instagram', 'tiktok', 'youtube', 'linkedin'])) {
    // Validate URLs
    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid URL format']);
        exit;
    }
}

// Update the database
try {
    $sql = "UPDATE bg_companies SET {$field} = :value, modify_dt = NOW() WHERE company_id = :company_id";
    $stmt = $database->prepare($sql);
    $stmt->execute([
        ':value' => $value,
        ':company_id' => $company_id
    ]);

    // Log the change
    $log_sql = "INSERT INTO bg_company_attributes (company_id, category, type, name, description, status, create_dt) 
                VALUES (:company_id, 'audit_log', 'field_update', :field, :description, 'active', NOW())";
    $log_stmt = $database->prepare($log_sql);
    $log_stmt->execute([
        ':company_id' => $company_id,
        ':field' => $field,
        ':description' => "Updated {$field} to: " . ($value ?: '[empty]') . " by user {$_SESSION['user']['user_id']}"
    ]);

    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Field updated successfully',
        'field' => $field,
        'value' => $value
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}