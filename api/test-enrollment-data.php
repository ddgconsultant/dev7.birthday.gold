<?php
// Test enrollment data endpoint for Chrome Extension testing
// This endpoint provides test data without requiring admin authentication

$addClasses = ['enrollment'];
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get parameters
$userId = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
$aid = isset($_GET['aid']) ? intval($_GET['aid']) : 0;
$companyId = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
$test_mode = true;

// Validate parameters
if ($userId == 0 || $companyId == 0) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Get admin details
$adminDetails = [];
if ($aid) {
    $adminDetails = $account->getuserdata($aid, 'user_id');
    if (!$adminDetails) {
        $adminDetails = [
            'user_id' => $aid,
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'username' => 'testadmin'
        ];
    }
}

// Get real user data
$userDetails = $account->getuserdata($userId, 'user_id');

if (!$userDetails) {
    // If no user found, create minimal test user data
    $userDetails = [
        'user_id' => $userId,
        'profile_first_name' => 'Test',
        'profile_last_name' => 'User',
        'profile_email' => 'test@example.com',
        'email' => 'test@example.com',
        'profile_phone_number' => '555-123-4567',
        'birthdate' => '1990-01-01',
        'profile_mailing_address' => '123 Test St',
        'profile_city' => 'Test City',
        'profile_state' => 'CA',
        'profile_zip_code' => '90210',
        'profile_username' => 'testuser',
        'username' => 'testuser'
    ];
}

// Build test registration list with the specific company
$test_sql = "SELECT uc.user_company_id, c.company_name, uc.user_id, uc.company_id, 
             COALESCE(uc.status, 'selected') as status, c.status as company_status, 
             SUBSTRING_INDEX(c.signup_url, '/', 3) AS signup_domain, c.signup_url, c.bgrab_domain
             FROM bg_companies c
             LEFT JOIN bg_user_companies uc ON c.company_id = uc.company_id AND uc.user_id = :userId
             WHERE c.company_id = :companyId 
             AND c.signup_url IS NOT NULL 
             AND c.signup_url != '' 
             AND c.signup_url != 'APP ONLY'
             LIMIT 1";

$stmt = $database->prepare($test_sql);
$stmt->execute(['userId' => $userId, 'companyId' => $companyId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

$registrationList = [];
if ($company) {
    // Ensure we have a user_company_id
    if (empty($company['user_company_id'])) {
        $company['user_company_id'] = 999999;
        $company['user_id'] = $userId;
        $company['status'] = 'selected';
    }
    
    // Get field mappings for the company
    $mapping_sql = "SELECT website_field_name, user_field_name, fieldformattype, fieldformat 
                    FROM bg_form_field_mappings 
                    WHERE status='active' AND version_status='active' 
                    AND company_id = :companyId 
                    ORDER BY `rank`";
    $stmt = $database->prepare($mapping_sql);
    $stmt->execute(['companyId' => $companyId]);
    $fieldMappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process field mappings
    $updatedFieldMappings = [];
    foreach ($fieldMappings as $key => $field) {
        $userFieldName = $field['user_field_name'];
        $value = '';
        
        if (strpos($userFieldName, 'FIXEDVALUE:') !== false) {
            $value = str_replace('FIXEDVALUE:', '', $userFieldName);
        } else {
            $value = $userDetails[$userFieldName] ?? '';
            if (empty($value)) {
                // Provide default test values
                if (strpos($userFieldName, 'agree') !== false) {
                    $value = 'true';
                } else {
                    $value = 'test-value';
                }
            }
        }
        
        $updatedFieldMappings[] = [
            'website_field_name' => $field['website_field_name'],
            'value' => $value,
            'keyorder' => $key + 1
        ];
    }
    
    $company['FIELDMAPPING'] = $updatedFieldMappings;
    $registrationList[] = $company;
}

// Generate a unique test email address
$timestamp = time();
$random = substr(md5(uniqid(rand(), true)), 0, 8);
$test_email = "test-{$userId}-{$random}@birthday-gold.xyz";

// Override sensitive fields with test data
$userDetails['profile_email'] = $test_email;
$userDetails['email'] = $test_email;
$userDetails['profile_username'] = "testuser_{$userId}_{$random}";
$userDetails['username'] = "testuser_{$userId}_{$random}";
$userDetails['profile_password'] = 'TestPass123!';

// Add test mode indicator
$userDetails['test_mode'] = true;
$userDetails['original_user_id'] = $userId;
$userDetails['test_email_domain'] = '@birthday-gold.xyz';

// Create output in the EXACT format the Chrome Extension expects
$output = [
    'ADMINDETAILS' => $adminDetails,
    'USERDETAILS' => $userDetails,
    'REGISTRATIONLIST' => $registrationList
];

// Set JSON header and output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow Chrome Extension access
echo json_encode($output);