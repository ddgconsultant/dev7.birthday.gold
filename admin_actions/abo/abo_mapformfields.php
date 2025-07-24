<?php
// abo_mapformfields.php - Automated form field mapping for signup forms
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
    'processor' => 'abo_mapformfields',
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => []
];

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

try {
    // Get companies to process
    if ($specific_company_id) {
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.company_id = :company_id 
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_mapformfields'
                AND ca.description IN ('pending', 'error', 'attempted')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company with pending form field mapping
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status IN ('approved_pending_data', 'pending_final_review')
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_mapformfields'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies pending form field mapping';
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
                        AND name = 'abo_mapformfields'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        // Check if mappings already exist
        $check_sql = "SELECT COUNT(*) as count FROM bg_form_field_mappings 
                     WHERE company_id = :company_id AND version_status = 'active'";
        $stmt = $database->query($check_sql, ['company_id' => $company_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing['count'] > 0) {
            // Create new version
            $version_sql = "SELECT MAX(version) as max_version FROM bg_form_field_mappings 
                           WHERE company_id = :company_id";
            $stmt = $database->query($version_sql, ['company_id' => $company_id]);
            $version_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $new_version = ($version_data['max_version'] ?? 0) + 1;
            
            // Deactivate old versions
            $deactivate_sql = "UPDATE bg_form_field_mappings 
                              SET version_status = 'inactive', modify_dt = NOW() 
                              WHERE company_id = :company_id AND version_status = 'active'";
            $database->query($deactivate_sql, ['company_id' => $company_id]);
        } else {
            $new_version = 1;
        }
        
        // Get signup form data if available
        $form_sql = "SELECT * FROM bg_company_attributes 
                    WHERE company_id = :company_id 
                    AND type = 'signup_form'
                    AND status = 'active'";
        $stmt = $database->query($form_sql, ['company_id' => $company_id]);
        $form_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $form_fields = [];
        if ($form_data && !empty($form_data['description'])) {
            $form_info = json_decode($form_data['description'], true);
            if (isset($form_info['fields']) && is_array($form_info['fields'])) {
                $form_fields = $form_info['fields'];
            }
        }
        
        $mappings_created = 0;
        $mapping_sql = "INSERT INTO bg_form_field_mappings 
                       (company_id, version, version_dt, version_status, user_field_name, 
                        website_field_name, fieldformattype, fieldformat, `rank`, status)
                       VALUES 
                       (:company_id, :version, NOW(), 'active', :user_field, 
                        :website_field, :format_type, :format, :rank, 'active')";
        
        // Process each standard field
        foreach ($standard_fields as $user_field => $field_config) {
            $matched = false;
            $website_field = '';
            $confidence = 0;
            $rank = 50;
            
            // Try to match with form fields if available
            if (!empty($form_fields)) {
                foreach ($form_fields as $form_field) {
                    $field_name = strtolower($form_field['name'] ?? '');
                    $field_id = strtolower($form_field['id'] ?? '');
                    $field_label = strtolower($form_field['label'] ?? '');
                    $field_type = strtolower($form_field['type'] ?? '');
                    $field_placeholder = strtolower($form_field['placeholder'] ?? '');
                    
                    // Check each pattern
                    foreach ($field_config['patterns'] as $pattern) {
                        $pattern_lower = strtolower($pattern);
                        
                        // Check various field attributes
                        if (strpos($field_name, $pattern_lower) !== false ||
                            strpos($field_id, $pattern_lower) !== false ||
                            strpos($field_label, $pattern_lower) !== false ||
                            strpos($field_placeholder, $pattern_lower) !== false) {
                            
                            // Additional type validation
                            if ($field_config['type'] === 'email' && $field_type !== 'email' && 
                                strpos($field_type, 'text') === false) {
                                continue;
                            }
                            
                            $matched = true;
                            $website_field = $form_field['name'] ?? $form_field['id'] ?? '';
                            
                            // Calculate confidence/rank based on match quality
                            if ($field_name === $pattern_lower || $field_id === $pattern_lower) {
                                $rank = 10; // Exact match
                            } elseif (strpos($field_name, $pattern_lower) === 0 || 
                                     strpos($field_id, $pattern_lower) === 0) {
                                $rank = 20; // Starts with pattern
                            } else {
                                $rank = 30; // Contains pattern
                            }
                            
                            break 2; // Found a match, stop searching
                        }
                    }
                }
            }
            
            // If no match found, use default mapping
            if (!$matched) {
                $website_field = $user_field; // Default to same as user field
                $rank = 50;
            }
            
            // Determine field format type
            $format_type = null;
            $format = null;
            
            if ($user_field === 'birthdate') {
                $format_type = 'date';
                $format = 'MM/DD/YYYY'; // Default format, can be customized
            } elseif ($user_field === 'profile_phone_number') {
                $format_type = 'phone';
                $format = '(XXX) XXX-XXXX';
            }
            
            // Insert mapping
            $database->query($mapping_sql, [
                'company_id' => $company_id,
                'version' => $new_version,
                'user_field' => $user_field,
                'website_field' => $website_field,
                'format_type' => $format_type,
                'format' => $format,
                'rank' => $rank
            ]);
            
            $mappings_created++;
        }
        
        // Store mapping summary
        $summary_data = [
            'version' => $new_version,
            'mappings_created' => $mappings_created,
            'automated' => true,
            'form_fields_analyzed' => count($form_fields),
            'mapping_method' => empty($form_fields) ? 'default' : 'pattern_matching',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $summary_sql = "INSERT INTO bg_company_attributes 
                       (company_id, type, name, description, status, create_dt)
                       VALUES 
                       (:company_id, 'form_mapping', 'summary', :data, 'active', NOW())
                       ON DUPLICATE KEY UPDATE 
                       description = VALUES(description),
                       modify_dt = NOW()";
        
        $database->query($summary_sql, [
            'company_id' => $company_id,
            'data' => json_encode($summary_data)
        ]);
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = 'completed', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_mapformfields'";
        $database->query($complete_sql, ['company_id' => $company_id]);
        
        $database->commit();
        
        $result['successful'] = 1;
        $result['data_collected'] = "Created {$mappings_created} field mappings (version {$new_version})";
        $result['mapping_summary'] = $summary_data;
        
    } catch (Exception $e) {
        $database->rollBack();
        
        // Update progress to error
        $error_sql = "UPDATE bg_company_attributes 
                     SET description = 'error', modify_dt = NOW() 
                     WHERE company_id = :company_id 
                     AND type = 'onboarding_progress' 
                     AND name = 'abo_mapformfields'";
        $database->query($error_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        error_log("ABO form field mapping error for company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    error_log("ABO form field mapping fatal error: " . $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);