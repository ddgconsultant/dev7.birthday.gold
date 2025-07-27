<?php
// abo_mapformfields_airtop.php - AIRTOP-powered form field mapping for signup forms
// Uses AIRTOP's AI browser automation to intelligently analyze and map form fields
$addClasses[]='ai';
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
    'processor' => 'abo_mapformfields_airtop',
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => []
];

// AIRTOP Configuration
$airtopApiKey = $sitesettings_ai['airtop']['apikey'] ?? '';
$airtopApiUrl = 'https://api.airtop.ai/api/v1/';

if (empty($airtopApiKey)) {
    $result['status'] = 'error';
    $result['errors'][] = 'AIRTOP API key not configured';
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Define standard user profile fields that need mapping
$standard_fields = [
    'profile_email' => ['patterns' => ['email', 'e-mail', 'mail', 'emailaddress', 'email_address'], 'type' => 'email'],
    'profile_first_name' => ['patterns' => ['firstname', 'first_name', 'fname', 'first', 'given_name', 'givenname'], 'type' => 'text'],
    'profile_last_name' => ['patterns' => ['lastname', 'last_name', 'lname', 'last', 'surname', 'family_name'], 'type' => 'text'],
    'birthdate' => ['patterns' => ['birthdate', 'birth_date', 'birthday', 'dob', 'dateofbirth', 'date_of_birth', 'bday'], 'type' => 'date'],
    'profile_phone_number' => ['patterns' => ['phone', 'phone_number', 'phonenumber', 'telephone', 'tel', 'mobile', 'cell'], 'type' => 'tel'],
    'profile_zip_code' => ['patterns' => ['zip', 'zipcode', 'zip_code', 'postal', 'postalcode', 'postal_code'], 'type' => 'text'],
    'profile_password' => ['patterns' => ['password', 'pass', 'pwd'], 'type' => 'password'],
    'profile_username' => ['patterns' => ['username', 'user_name', 'login', 'userid', 'user_id'], 'type' => 'text'],
    'profile_gender' => ['patterns' => ['gender', 'sex'], 'type' => 'select'],
    'profile_title' => ['patterns' => ['title', 'prefix', 'salutation'], 'type' => 'select'],
    'profile_mailing_address' => ['patterns' => ['address', 'street', 'address1', 'address_1', 'street_address'], 'type' => 'text'],
    'profile_city' => ['patterns' => ['city', 'town', 'locality'], 'type' => 'text'],
    'profile_state' => ['patterns' => ['state', 'province', 'region'], 'type' => 'select'],
    'profile_country' => ['patterns' => ['country', 'nation'], 'type' => 'select'],
    'profile_agree_terms' => ['patterns' => ['terms', 'agree', 'accept', 'tos', 'terms_conditions'], 'type' => 'checkbox'],
    'profile_agree_email' => ['patterns' => ['newsletter', 'marketing', 'promotional', 'email_opt', 'subscribe'], 'type' => 'checkbox'],
    'profile_agree_text' => ['patterns' => ['sms', 'text', 'mobile_opt', 'text_message'], 'type' => 'checkbox']
];

// Helper function to create AIRTOP session
function createAirtopSession($system, $airtopApiUrl, $airtopApiKey) {
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $airtopApiKey
    ];
    
    $response = $system->curlRequest(
        $airtopApiUrl . 'sessions',
        $headers,
        [],
        'POST'
    );
    
    if (isset($response['decoded']['data']['id'])) {
        return $response['decoded']['data']['id'];
    }
    
    return false;
}

// Helper function to wait for session to be ready
function waitForSessionReady($system, $airtopApiUrl, $airtopApiKey, $sessionId, $maxWait = 30) {
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $airtopApiKey
    ];
    
    for ($i = 0; $i < $maxWait / 2; $i++) {
        sleep(2);
        
        $response = $system->curlRequest(
            $airtopApiUrl . 'sessions/' . $sessionId,
            $headers,
            [],
            'GET'
        );
        
        if (isset($response['decoded']['data']['status']) && 
            in_array($response['decoded']['data']['status'], ['active', 'ready', 'running'])) {
            return true;
        }
    }
    
    return false;
}

// Helper function to terminate AIRTOP session
function terminateAirtopSession($system, $airtopApiUrl, $airtopApiKey, $sessionId) {
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $airtopApiKey
    ];
    
    $system->curlRequest(
        $airtopApiUrl . 'sessions/' . $sessionId,
        $headers,
        [],
        'DELETE'
    );
}

// Track start of AIRTOP processor
$session->session_tracking('abo_airtop_start', [
    'processor' => 'abo_mapformfields_airtop',
    'company_id' => $specific_company_id ?? 'auto',
    'timestamp' => date('Y-m-d H:i:s')
]);

try {
    // Get companies to process
    if ($specific_company_id) {
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.company_id = :company_id 
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_mapformfields_airtop'
                AND ca.description IN ('pending', 'error', 'attempted')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company with pending form field mapping
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status IN ('approved_pending_data', 'pending_final_review')
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_mapformfields_airtop'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $session->session_tracking('abo_airtop_no_companies', [
            'message' => 'No companies pending form field mapping',
            'sql' => $sql,
            'params' => $params
        ]);
        $result['message'] = 'No companies pending form field mapping';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    $result['processed'] = 1;
    $company_id = $company['company_id'];
    $company_name = $company['company_name'];
    $signup_url = $company['signup_url'] ?? '';
    
    // Track company found
    $session->session_tracking('abo_airtop_company_found', [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'signup_url' => $signup_url,
        'company_status' => $company['status']
    ]);
    
    // Skip if no signup URL or if it's APP ONLY
    if (empty($signup_url) || $signup_url === $website['apponlytag']) {
        $database->query("UPDATE bg_company_attributes 
                         SET description = 'skipped', modify_dt = NOW() 
                         WHERE company_id = :company_id 
                         AND type = 'onboarding_progress' 
                         AND name = 'abo_mapformfields_airtop'", 
                         ['company_id' => $company_id]);
        
        $result['data_collected'] = "Skipped - No signup URL or APP ONLY company";
        $result['message'] = "Company $company_id skipped (no web form)";
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    try {
        $database->beginTransaction();
        
        // Update progress to in_progress
        $database->query("UPDATE bg_company_attributes 
                         SET description = 'in_progress', modify_dt = NOW() 
                         WHERE company_id = :company_id 
                         AND type = 'onboarding_progress' 
                         AND name = 'abo_mapformfields_airtop'", 
                         ['company_id' => $company_id]);
        
        // Track AIRTOP session creation attempt
        $session->session_tracking('abo_airtop_create_session', [
            'company_id' => $company_id,
            'api_url' => $airtopApiUrl
        ]);
        
        // Create AIRTOP session
        $sessionId = createAirtopSession($system, $airtopApiUrl, $airtopApiKey);
        
        if (!$sessionId) {
            $session->session_tracking('abo_airtop_session_failed', [
                'company_id' => $company_id,
                'error' => 'Failed to create AIRTOP session'
            ]);
            throw new Exception("Failed to create AIRTOP session");
        }
        
        // Track successful session creation
        $session->session_tracking('abo_airtop_session_created', [
            'company_id' => $company_id,
            'session_id' => $sessionId
        ]);
        
        // Wait for session to be ready
        if (!waitForSessionReady($system, $airtopApiUrl, $airtopApiKey, $sessionId)) {
            terminateAirtopSession($system, $airtopApiUrl, $airtopApiKey, $sessionId);
            throw new Exception("AIRTOP session failed to become ready");
        }
        
        // Create window and navigate to signup URL
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $airtopApiKey
        ];
        
        $windowResponse = $system->curlRequest(
            $airtopApiUrl . 'sessions/' . $sessionId . '/windows',
            $headers,
            ['url' => $signup_url],
            'POST'
        );
        
        if (!isset($windowResponse['decoded']['data']['windowId'])) {
            terminateAirtopSession($system, $airtopApiUrl, $airtopApiKey, $sessionId);
            throw new Exception("Failed to create window");
        }
        
        $windowId = $windowResponse['decoded']['data']['windowId'];
        
        // Track window creation
        $session->session_tracking('abo_airtop_window_created', [
            'company_id' => $company_id,
            'window_id' => $windowId,
            'signup_url' => $signup_url
        ]);
        
        // Wait for page to load
        sleep(5);
        
        // Track AI prompt preparation
        $session->session_tracking('abo_airtop_ai_prompt_start', [
            'company_id' => $company_id,
            'analyzing_url' => $signup_url
        ]);
        
        // Create AI prompt for form field analysis
        $prompt = "Analyze this signup/registration form and extract ALL form fields. For each field provide:
1. Field name/id attribute
2. Field type (text, email, password, select, checkbox, radio, etc)
3. Field label or placeholder text
4. Whether it's required
5. Any validation rules visible

Additionally, identify which fields map to these standard profile fields:
- Email address
- First name
- Last name
- Birth date/Birthday
- Phone number
- ZIP/Postal code
- Password
- Username
- Gender
- Title/Prefix
- Street address
- City
- State/Province
- Country
- Terms acceptance checkbox
- Email marketing opt-in
- SMS/Text opt-in

Format the response as a structured list with clear field mappings.";
        
        // Query the page
        $queryResponse = $system->curlRequest(
            $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId . '/page-query',
            $headers,
            ['prompt' => $prompt],
            'POST'
        );
        
        // Track AI query response
        $session->session_tracking('abo_airtop_ai_response', [
            'company_id' => $company_id,
            'response_status' => $queryResponse['curl_info']['http_code'] ?? 'unknown',
            'response_size' => strlen($queryResponse['response'] ?? ''),
            'has_error' => isset($queryResponse['error'])
        ]);
        
        $aiAnalysis = '';
        if (isset($queryResponse['decoded']['data']['modelResponse'])) {
            $aiAnalysis = $queryResponse['decoded']['data']['modelResponse'];
        }
        
        // Always terminate session to free resources
        terminateAirtopSession($system, $airtopApiUrl, $airtopApiKey, $sessionId);
        
        if (empty($aiAnalysis)) {
            $session->session_tracking('abo_airtop_empty_analysis', [
                'company_id' => $company_id,
                'error' => 'No AI analysis received'
            ]);
            throw new Exception("Failed to analyze form fields");
        }
        
        // Track AI analysis received
        $session->session_tracking('abo_airtop_analysis_received', [
            'company_id' => $company_id,
            'analysis_length' => strlen($aiAnalysis),
            'first_100_chars' => substr($aiAnalysis, 0, 100)
        ]);
        
        // Store the AI analysis
        $analysis_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'form_analysis', 'airtop_analysis', :analysis, 'active', NOW())";
        $database->query($analysis_sql, [
            'company_id' => $company_id,
            'analysis' => $aiAnalysis
        ]);
        
        // Track parsing start
        $session->session_tracking('abo_airtop_parsing_start', [
            'company_id' => $company_id,
            'parsing_method' => 'line_by_line_pattern_matching'
        ]);
        
        // Parse AI response to create field mappings
        $mappings = [];
        $lines = explode("\n", $aiAnalysis);
        $current_field = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Look for field mappings in the AI response
            foreach ($standard_fields as $profile_field => $field_info) {
                foreach ($field_info['patterns'] as $pattern) {
                    if (stripos($line, $pattern) !== false) {
                        // Extract field name/id from the line
                        if (preg_match('/(?:name|id)[\s=:"\']+([a-zA-Z0-9_\-]+)/i', $line, $matches)) {
                            $mappings[$profile_field] = [
                                'field_name' => $matches[1],
                                'field_type' => $field_info['type'],
                                'confidence' => 0.9,
                                'method' => 'airtop_ai'
                            ];
                            break 2;
                        }
                    }
                }
            }
        }
        
        // Get version number
        $version_sql = "SELECT MAX(version) as max_version FROM bg_form_field_mappings 
                       WHERE company_id = :company_id";
        $stmt = $database->query($version_sql, ['company_id' => $company_id]);
        $version_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_version = ($version_data['max_version'] ?? 0) + 1;
        
        // Deactivate old versions
        $database->query("UPDATE bg_form_field_mappings 
                         SET version_status = 'inactive', modify_dt = NOW() 
                         WHERE company_id = :company_id AND version_status = 'active'", 
                         ['company_id' => $company_id]);
        
        // Store the mappings
        foreach ($mappings as $profile_field => $mapping) {
            $insert_sql = "INSERT INTO bg_form_field_mappings 
                          (company_id, profile_field, form_field_name, form_field_type, 
                           mapping_confidence, mapping_method, version, version_status, create_dt)
                          VALUES 
                          (:company_id, :profile_field, :form_field_name, :form_field_type,
                           :confidence, :method, :version, 'active', NOW())";
            
            $database->query($insert_sql, [
                'company_id' => $company_id,
                'profile_field' => $profile_field,
                'form_field_name' => $mapping['field_name'],
                'form_field_type' => $mapping['field_type'],
                'confidence' => $mapping['confidence'],
                'method' => $mapping['method'],
                'version' => $new_version
            ]);
        }
        
        // Store mapping method
        $database->query("INSERT INTO bg_company_attributes 
                         (company_id, type, name, description, status, create_dt)
                         VALUES 
                         (:company_id, 'form_mapping_method', 'method', 'airtop_ai', 'active', NOW())
                         ON DUPLICATE KEY UPDATE 
                         description = 'airtop_ai',
                         modify_dt = NOW()", 
                         ['company_id' => $company_id]);
        
        // Update progress to completed
        $database->query("UPDATE bg_company_attributes 
                         SET description = 'completed', modify_dt = NOW() 
                         WHERE company_id = :company_id 
                         AND type = 'onboarding_progress' 
                         AND name = 'abo_mapformfields_airtop'", 
                         ['company_id' => $company_id]);
        
        $database->commit();
        
        // Track successful completion
        $session->session_tracking('abo_airtop_completed', [
            'company_id' => $company_id,
            'company_name' => $company_name,
            'mappings_created' => count($mappings),
            'version' => $new_version,
            'ai_analysis_length' => strlen($aiAnalysis),
            'process_time' => time() - $_SERVER['REQUEST_TIME']
        ]);
        
        $result['successful'] = 1;
        $result['message'] = "Successfully mapped " . count($mappings) . " form fields using AIRTOP AI";
        $result['mappings'] = $mappings;
        $result['ai_analysis'] = substr($aiAnalysis, 0, 500) . '...'; // First 500 chars
        
    } catch (Exception $e) {
        $database->rollback();
        
        // Track error
        $session->session_tracking('abo_airtop_error', [
            'company_id' => $company_id,
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
        ]);
        
        // Update progress to error
        $database->query("UPDATE bg_company_attributes 
                         SET description = 'error', modify_dt = NOW() 
                         WHERE company_id = :company_id 
                         AND type = 'onboarding_progress' 
                         AND name = 'abo_mapformfields_airtop'", 
                         ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['status'] = 'error';
        $result['errors'][] = $e->getMessage();
    }
    
} catch (Exception $e) {
    // Track fatal error
    $session->session_tracking('abo_airtop_fatal_error', [
        'error_message' => $e->getMessage(),
        'error_trace' => $e->getTraceAsString()
    ]);
    
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
}

// Track final result
$session->session_tracking('abo_airtop_complete', [
    'status' => $result['status'],
    'processed' => $result['processed'],
    'successful' => $result['successful'],
    'failed' => $result['failed'],
    'errors' => $result['errors'],
    'execution_time' => time() - $_SERVER['REQUEST_TIME']
]);

header('Content-Type: application/json');
echo json_encode($result);