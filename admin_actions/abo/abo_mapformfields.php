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
        // Check if this is a retrigger request
        $is_retrigger = isset($_GET['retrigger']) && $_GET['retrigger'] == '1';
        
        if ($is_retrigger) {
            // For retrigger, allow completed, error, and attempted statuses
            $sql = "SELECT c.* FROM bg_companies c 
                    INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                    WHERE c.company_id = :company_id 
                    AND ca.type = 'onboarding_progress'
                    AND ca.name = 'abo_mapformfields'
                    AND ca.description IN ('pending', 'error', 'attempted', 'completed')
                    LIMIT 1";
        } else {
            $sql = "SELECT c.* FROM bg_companies c 
                    INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                    WHERE c.company_id = :company_id 
                    AND ca.type = 'onboarding_progress'
                    AND ca.name = 'abo_mapformfields'
                    AND ca.description IN ('pending', 'error', 'attempted')
                    LIMIT 1";
        }
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
        
        // Get signup URL
        $signup_url = $company['signup_url'] ?? '';
        
        // Skip if no signup URL or if it's APP ONLY
        if (empty($signup_url) || $signup_url === $website['apponlytag']) {
            // Update progress to skipped
            $skip_sql = "UPDATE bg_company_attributes 
                        SET description = 'skipped', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_mapformfields'";
            $database->query($skip_sql, ['company_id' => $company_id]);
            
            $database->commit();
            
            $result['data_collected'] = "Skipped - No signup URL or APP ONLY company";
            $result['message'] = "Company $company_id skipped (no web form)";
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
        
        // First, try to get stored HTML from database
        $html_sql = "SELECT description FROM bg_company_attributes 
                    WHERE company_id = :company_id 
                    AND type = 'signup_html'
                    AND status = 'active'
                    LIMIT 1";
        $stmt = $database->query($html_sql, ['company_id' => $company_id]);
        $stored_html = $stmt->fetchColumn();
        
        $html_content = '';
        $fetch_method = 'stored';
        
        if (!empty($stored_html)) {
            $html_content = $stored_html;
        } else {
            // Fetch HTML from URL
            try {
                // Set up context with headers to look like a real browser
                $context = stream_context_create([
                    'http' => [
                        'header' => [
                            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                            'Accept-Language: en-US,en;q=0.5',
                            'Accept-Encoding: gzip, deflate',
                            'Connection: keep-alive',
                            'Upgrade-Insecure-Requests: 1'
                        ],
                        'timeout' => 30,
                        'follow_location' => true
                    ]
                ]);
                
                $html_content = @file_get_contents($signup_url, false, $context);
                $fetch_method = 'fetched';
                
                if ($html_content === false) {
                    throw new Exception("Failed to fetch URL: $signup_url");
                }
                
                // Check if content is gzipped and decode if needed
                if (substr($html_content, 0, 2) === "\x1f\x8b") {
                    $html_content = gzdecode($html_content);
                }
                
                // Store the fetched HTML for future use
                $store_sql = "INSERT INTO bg_company_attributes 
                             (company_id, type, name, description, status, create_dt)
                             VALUES 
                             (:company_id, 'signup_html', 'raw_html', :html, 'active', NOW())
                             ON DUPLICATE KEY UPDATE 
                             description = VALUES(description),
                             modify_dt = NOW()";
                $database->query($store_sql, [
                    'company_id' => $company_id,
                    'html' => $html_content
                ]);
                
            } catch (Exception $e) {
                // Update progress to error
                $error_sql = "UPDATE bg_company_attributes 
                             SET description = 'error', modify_dt = NOW() 
                             WHERE company_id = :company_id 
                             AND type = 'onboarding_progress' 
                             AND name = 'abo_mapformfields'";
                $database->query($error_sql, ['company_id' => $company_id]);
                
                $database->commit();
                
                $result['failed'] = 1;
                $result['errors'][] = "Failed to fetch signup URL: " . $e->getMessage();
                $result['message'] = "Failed to process company $company_id";
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
            }
        }
        
        // Parse HTML to extract form fields
        $form_fields = [];
        if (!empty($html_content)) {
            // Create DOM parser
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML($html_content);
            libxml_clear_errors();
            
            // Find all input, select, and textarea elements
            $xpath = new DOMXPath($dom);
            $elements = $xpath->query('//input | //select | //textarea');
            
            foreach ($elements as $element) {
                $field = [
                    'tag' => $element->tagName,
                    'type' => $element->getAttribute('type') ?: 'text',
                    'name' => $element->getAttribute('name'),
                    'id' => $element->getAttribute('id'),
                    'placeholder' => $element->getAttribute('placeholder'),
                    'class' => $element->getAttribute('class'),
                    'required' => $element->hasAttribute('required'),
                    'value' => $element->getAttribute('value')
                ];
                
                // Try to find associated label
                $label_text = '';
                if ($field['id']) {
                    $label = $xpath->query("//label[@for='{$field['id']}']")->item(0);
                    if ($label) {
                        $label_text = trim($label->textContent);
                    }
                }
                
                // Also check for parent label
                if (empty($label_text)) {
                    $parent = $element->parentNode;
                    while ($parent && $parent->tagName !== 'form' && $parent->tagName !== 'body') {
                        if ($parent->tagName === 'label') {
                            $label_text = trim($parent->textContent);
                            break;
                        }
                        $parent = $parent->parentNode;
                    }
                }
                
                $field['label'] = $label_text;
                
                // Only include fields with names (they're the ones that get submitted)
                if (!empty($field['name'])) {
                    $form_fields[] = $field;
                }
            }
        }
        
        // AUTO-ESCALATION TO AIRTOP: If we found 0-2 form fields, escalate to AIRTOP
        if (count($form_fields) <= 2) {
            // Log the escalation
            session_tracking('ABO AIRTOP escalation', "Company $company_id - only " . count($form_fields) . " form fields found");
            
            // Mark the current HTML scraping task as completed (since we're escalating)
            $complete_sql = "UPDATE bg_company_attributes 
                            SET description = 'completed', modify_dt = NOW() 
                            WHERE company_id = :company_id 
                            AND type = 'onboarding_progress' 
                            AND name = 'abo_mapformfields'";
            $database->query($complete_sql, ['company_id' => $company_id]);
            
            // Create a new pending task for AIRTOP processor
            $airtop_task_sql = "INSERT INTO bg_company_attributes 
                               (company_id, type, name, description, status, create_dt)
                               VALUES 
                               (:company_id, 'onboarding_progress', 'abo_mapformfields_airtop', 'pending', 'active', NOW())
                               ON DUPLICATE KEY UPDATE
                               description = 'pending',
                               modify_dt = NOW()";
            $database->query($airtop_task_sql, ['company_id' => $company_id]);
            
            // Store escalation reason
            $escalation_data = [
                'reason' => 'insufficient_form_fields',
                'form_fields_found' => count($form_fields),
                'escalated_at' => date('Y-m-d H:i:s'),
                'original_method' => 'intelligent_pattern_matching',
                'escalated_to' => 'airtop_ai',
                'html_analyzed' => true,
                'signup_url' => $signup_url
            ];
            
            $escalation_sql = "INSERT INTO bg_company_attributes 
                              (company_id, type, name, description, status, create_dt)
                              VALUES 
                              (:company_id, 'form_mapping_escalation', 'reason', :data, 'active', NOW())";
            $database->query($escalation_sql, [
                'company_id' => $company_id,
                'data' => json_encode($escalation_data)
            ]);
            
            $database->commit();
            
            $result['successful'] = 1;
            $result['data_collected'] = "Escalated to AIRTOP due to insufficient form fields (" . count($form_fields) . " found)";
            $result['escalated'] = true;
            $result['escalation_reason'] = "Only " . count($form_fields) . " form fields found";
            $result['message'] = "HTML scraping found insufficient fields. Task escalated to AIRTOP processor.";
            $result['next_processor'] = "abo_mapformfields_airtop";
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
        
        $mappings_created = 0;
        $mapping_sql = "INSERT INTO bg_form_field_mappings 
                       (company_id, version, version_dt, version_status, user_field_name, 
                        website_field_name, fieldformattype, fieldformat, `rank`, status)
                       VALUES 
                       (:company_id, :version, NOW(), 'active', :user_field, 
                        :website_field, :format_type, :format, :rank, 'active')";
        
        // Store form fields for analysis
        $form_fields_data = [
            'url' => $signup_url,
            'fetch_method' => $fetch_method,
            'fields_found' => count($form_fields),
            'fields' => $form_fields,
            'analyzed_at' => date('Y-m-d H:i:s')
        ];
        
        $fields_sql = "INSERT INTO bg_company_attributes 
                      (company_id, type, name, description, status, create_dt)
                      VALUES 
                      (:company_id, 'signup_form', 'extracted_fields', :data, 'active', NOW())
                      ON DUPLICATE KEY UPDATE 
                      description = VALUES(description),
                      modify_dt = NOW()";
        
        $database->query($fields_sql, [
            'company_id' => $company_id,
            'data' => json_encode($form_fields_data)
        ]);
        
        // Process each standard field
        foreach ($standard_fields as $user_field => $field_config) {
            $matched = false;
            $website_field = '';
            $confidence = 0;
            $rank = 100; // Default rank for no match
            $match_details = [];
            
            // Try to match with form fields if available
            if (!empty($form_fields)) {
                $best_match = null;
                $best_rank = 100;
                
                foreach ($form_fields as $form_field) {
                    $field_name = strtolower($form_field['name'] ?? '');
                    $field_id = strtolower($form_field['id'] ?? '');
                    $field_label = strtolower($form_field['label'] ?? '');
                    $field_type = strtolower($form_field['type'] ?? '');
                    $field_placeholder = strtolower($form_field['placeholder'] ?? '');
                    $field_class = strtolower($form_field['class'] ?? '');
                    
                    // Skip hidden fields unless looking for them specifically
                    if ($field_type === 'hidden' && $field_config['type'] !== 'hidden') {
                        continue;
                    }
                    
                    // Check each pattern
                    foreach ($field_config['patterns'] as $pattern) {
                        $pattern_lower = strtolower($pattern);
                        $current_rank = 100;
                        $match_type = '';
                        
                        // Exact matches get highest priority
                        if ($field_name === $pattern_lower) {
                            $current_rank = 1;
                            $match_type = 'exact_name';
                        } elseif ($field_id === $pattern_lower) {
                            $current_rank = 2;
                            $match_type = 'exact_id';
                        } elseif ($field_name === 'user_' . $pattern_lower || $field_name === 'member_' . $pattern_lower) {
                            $current_rank = 5;
                            $match_type = 'prefixed_name';
                        }
                        // Check if field starts with pattern
                        elseif (strpos($field_name, $pattern_lower) === 0) {
                            $current_rank = 10;
                            $match_type = 'starts_with_name';
                        } elseif (strpos($field_id, $pattern_lower) === 0) {
                            $current_rank = 15;
                            $match_type = 'starts_with_id';
                        }
                        // Check if field contains pattern
                        elseif (strpos($field_name, $pattern_lower) !== false) {
                            $current_rank = 20;
                            $match_type = 'contains_name';
                        } elseif (strpos($field_id, $pattern_lower) !== false) {
                            $current_rank = 25;
                            $match_type = 'contains_id';
                        }
                        // Check label and placeholder
                        elseif (stripos($field_label, $pattern) !== false) {
                            $current_rank = 30;
                            $match_type = 'label_match';
                        } elseif (stripos($field_placeholder, $pattern) !== false) {
                            $current_rank = 35;
                            $match_type = 'placeholder_match';
                        }
                        
                        // Type validation
                        if ($current_rank < 100) {
                            // Validate field type matches expected type
                            if ($field_config['type'] === 'email' && $field_type !== 'email' && $field_type !== 'text') {
                                $current_rank += 20; // Penalize type mismatch
                            } elseif ($field_config['type'] === 'tel' && $field_type !== 'tel' && $field_type !== 'text') {
                                $current_rank += 20;
                            } elseif ($field_config['type'] === 'password' && $field_type !== 'password') {
                                $current_rank += 50; // Heavy penalty for password type mismatch
                            } elseif ($field_config['type'] === 'checkbox' && $field_type !== 'checkbox') {
                                $current_rank += 50;
                            } elseif ($field_config['type'] === 'date' && !in_array($field_type, ['date', 'text', 'select'])) {
                                $current_rank += 20;
                            }
                            
                            // Check if this is the best match so far
                            if ($current_rank < $best_rank) {
                                $best_rank = $current_rank;
                                $best_match = $form_field;
                                $match_details = [
                                    'match_type' => $match_type,
                                    'pattern' => $pattern,
                                    'field_name' => $form_field['name'],
                                    'field_id' => $form_field['id'],
                                    'field_type' => $form_field['type'],
                                    'confidence' => 100 - $current_rank
                                ];
                            }
                        }
                    }
                }
                
                // Use the best match found
                if ($best_match && $best_rank < 50) {
                    $matched = true;
                    $website_field = $best_match['name'];
                    $rank = $best_rank;
                }
            }
            
            // If no good match found, check if it's a required field
            if (!$matched && in_array($user_field, ['profile_email', 'profile_first_name', 'profile_last_name', 'birthdate'])) {
                // For critical fields, don't create a mapping if we can't find a match
                continue;
            } elseif (!$matched) {
                // For optional fields, create a placeholder mapping
                $website_field = '';
                $rank = 100;
            }
            
            // Determine field format type based on user field and detected form field
            $format_type = null;
            $format = null;
            
            // Analyze the matched field for format hints
            $field_type_hint = '';
            $field_name_lower = '';
            if ($matched && $best_match) {
                $field_type_hint = strtolower($best_match['type'] ?? '');
                $field_name_lower = strtolower($best_match['name'] ?? '');
            }
            
            // Set format based on user field type
            if ($user_field === 'birthdate') {
                $format_type = 'date';
                // Try to detect date format from field name or placeholder
                if ($matched && $best_match) {
                    $placeholder = strtolower($best_match['placeholder'] ?? '');
                    if (strpos($placeholder, 'mm/dd/yyyy') !== false || strpos($placeholder, 'mm-dd-yyyy') !== false) {
                        $format = 'm/d/Y';
                    } elseif (strpos($placeholder, 'dd/mm/yyyy') !== false || strpos($placeholder, 'dd-mm-yyyy') !== false) {
                        $format = 'd/m/Y';
                    } elseif (strpos($placeholder, 'yyyy-mm-dd') !== false) {
                        $format = 'Y-m-d';
                    } else {
                        $format = 'm/d/Y'; // Default US format
                    }
                } else {
                    $format = 'm/d/Y';
                }
            } elseif ($user_field === 'profile_phone_number') {
                $format_type = 'phone';
                // Detect phone format from placeholder or field attributes
                if ($matched && $best_match) {
                    $placeholder = $best_match['placeholder'] ?? '';
                    if (preg_match('/\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $placeholder)) {
                        $format = '(###) ###-####';
                    } elseif (preg_match('/\d{3}-\d{3}-\d{4}/', $placeholder)) {
                        $format = '###-###-####';
                    } elseif (preg_match('/\d{3}\.\d{3}\.\d{4}/', $placeholder)) {
                        $format = '###.###.####';
                    } else {
                        $format = '(###) ###-####'; // Default format
                    }
                } else {
                    $format = '(###) ###-####';
                }
            } elseif ($user_field === 'profile_state') {
                // Check if the field is a select/dropdown
                if ($field_type_hint === 'select' || strpos($field_name_lower, 'state') !== false) {
                    $format_type = 'state';
                    $format = 'code'; // Convert full state names to 2-letter codes
                }
            } elseif ($user_field === 'profile_country') {
                if ($field_type_hint === 'select' || strpos($field_name_lower, 'country') !== false) {
                    $format_type = 'country';
                    $format = 'code'; // Default to US
                }
            } elseif ($user_field === 'profile_gender') {
                if ($field_type_hint === 'select' || $field_type_hint === 'radio') {
                    $format_type = 'gender';
                    $format = 'uppercode'; // M or F
                }
            } elseif (strpos($user_field, 'profile_agree_') === 0) {
                // Agreement/checkbox fields
                if ($field_type_hint === 'checkbox') {
                    $format_type = 'tf->yn';
                    $format = 'uinitial'; // Y or N
                }
            } elseif ($user_field === 'profile_title') {
                if ($field_type_hint === 'select') {
                    $format_type = 'title';
                    $format = 'noperiod'; // Remove periods from Mr. Mrs. etc
                }
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
            'mapping_method' => empty($form_fields) ? 'no_form_found' : 'intelligent_pattern_matching',
            'signup_url' => $signup_url,
            'html_fetch_method' => $fetch_method,
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
        
        // Store mapping method
        $method_sql = "INSERT INTO bg_company_attributes 
                      (company_id, type, name, description, status, create_dt)
                      VALUES 
                      (:company_id, 'form_mapping_method', 'method', 'intelligent_pattern_matching', 'active', NOW())
                      ON DUPLICATE KEY UPDATE 
                      description = 'intelligent_pattern_matching',
                      modify_dt = NOW()";
        $database->query($method_sql, ['company_id' => $company_id]);
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = 'completed', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_mapformfields'";
        $database->query($complete_sql, ['company_id' => $company_id]);
        
        $database->commit();
        
        $result['successful'] = 1;
        $result['data_collected'] = "Created {$mappings_created} field mappings (version {$new_version}) from " . count($form_fields) . " form fields";
        $result['mapping_summary'] = $summary_data;
        $result['form_analysis'] = [
            'signup_url' => $signup_url,
            'form_fields_found' => count($form_fields),
            'fields_mapped' => $mappings_created,
            'fetch_method' => $fetch_method
        ];
        
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
        session_tracking('ABO form field mapping error', "Company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    session_tracking('ABO form field mapping fatal error', $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);