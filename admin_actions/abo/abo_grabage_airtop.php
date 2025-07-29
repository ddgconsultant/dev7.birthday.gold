<?php
// abo_grabage_airtop.php - AIRTOP-powered age requirement extraction
// Uses AIRTOP's AI browser automation to intelligently analyze age requirements
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
    'processor' => 'abo_grabage_airtop',
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
session_tracking('abo_airtop_grabage_start', [
    'processor' => 'abo_grabage_airtop',
    'company_id' => $specific_company_id ?? 'auto',
    'timestamp' => date('Y-m-d H:i:s')
]);

try {
    // Get companies to process
    if ($specific_company_id) {
        // Check if this is a retrigger request
        $is_retrigger = isset($_GET['retrigger']) && $_GET['retrigger'] == '1';
        
        if ($is_retrigger) {
            // Track retrigger request
            session_tracking('abo_airtop_grabage_retrigger', [
                'company_id' => $specific_company_id,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            // For retrigger, allow completed, error, and attempted statuses
            $sql = "SELECT c.* FROM bg_companies c 
                    INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                    WHERE c.company_id = :company_id 
                    AND ca.type = 'onboarding_progress'
                    AND ca.name = 'abo_grabage_airtop'
                    AND ca.description IN ('pending', 'error', 'attempted', 'completed')
                    LIMIT 1";
        } else {
            $sql = "SELECT c.* FROM bg_companies c 
                    INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                    WHERE c.company_id = :company_id 
                    AND ca.type = 'onboarding_progress'
                    AND ca.name = 'abo_grabage_airtop'
                    AND ca.description IN ('pending', 'error', 'attempted')
                    LIMIT 1";
        }
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next company with pending age extraction
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.status IN ('approved_pending_data', 'pending_final_review')
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_grabage_airtop'
                AND ca.description = 'pending'
                ORDER BY c.create_dt ASC
                LIMIT 1";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        session_tracking('abo_airtop_grabage_no_companies', [
            'message' => 'No companies pending age extraction',
            'sql' => $sql,
            'params' => $params
        ]);
        $result['message'] = 'No companies pending age extraction';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    $result['processed'] = 1;
    $company_id = $company['company_id'];
    $company_name = $company['company_name'];
    $company_url = $company['company_url'] ?? '';
    
    // Track company found
    session_tracking('abo_airtop_grabage_company_found', [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'company_url' => $company_url,
        'company_status' => $company['status']
    ]);
    
    // Gather URLs to check for age information
    $urls_to_check = [];
    
    // Priority 1: Terms of Service URL
    $terms_sql = "SELECT description FROM bg_company_attributes 
                 WHERE company_id = :company_id 
                 AND type = 'url' 
                 AND name = 'terms' 
                 AND `grouping` = 'policies' 
                 AND status = 'active'
                 LIMIT 1";
    $terms_stmt = $database->query($terms_sql, ['company_id' => $company_id]);
    $terms_row = $terms_stmt->fetch(PDO::FETCH_ASSOC);
    if ($terms_row && !empty($terms_row['description'])) {
        $urls_to_check[] = $terms_row['description'];
    }
    
    // Priority 2: Privacy Policy URL
    $privacy_sql = "SELECT description FROM bg_company_attributes 
                   WHERE company_id = :company_id 
                   AND type = 'url' 
                   AND name = 'privacy' 
                   AND `grouping` = 'policies' 
                   AND status = 'active'
                   LIMIT 1";
    $privacy_stmt = $database->query($privacy_sql, ['company_id' => $company_id]);
    $privacy_row = $privacy_stmt->fetch(PDO::FETCH_ASSOC);
    if ($privacy_row && !empty($privacy_row['description'])) {
        $urls_to_check[] = $privacy_row['description'];
    }
    
    // Priority 3: Signup URL
    if (!empty($company['signup_url']) && $company['signup_url'] !== $website['apponlytag']) {
        $urls_to_check[] = $company['signup_url'];
    }
    
    // Priority 4: Rewards/Birthday program URL
    $rewards_sql = "SELECT description FROM bg_company_attributes 
                   WHERE company_id = :company_id 
                   AND type = 'data_collection' 
                   AND name = 'program_urls_found' 
                   AND status = 'active'
                   ORDER BY create_dt DESC
                   LIMIT 1";
    $rewards_stmt = $database->query($rewards_sql, ['company_id' => $company_id]);
    $rewards_row = $rewards_stmt->fetch(PDO::FETCH_ASSOC);
    if ($rewards_row) {
        $program_urls = json_decode($rewards_row['description'], true);
        if (!empty($program_urls) && is_array($program_urls)) {
            foreach ($program_urls as $url) {
                if (!empty($url) && !in_array($url, $urls_to_check)) {
                    $urls_to_check[] = $url;
                }
            }
        }
    }
    
    // Priority 5: Main website
    if (!empty($company_url) && !in_array($company_url, $urls_to_check)) {
        $urls_to_check[] = $company_url;
    }
    
    // Remove duplicates and empty values
    $urls_to_check = array_values(array_unique(array_filter($urls_to_check)));
    
    if (empty($urls_to_check)) {
        // No URLs to check, use defaults
        $age_requirements = [
            'minimum_age' => $bg_age_requirements_defaults['minimum_age'],
            'maximum_age' => $bg_age_requirements_defaults['maximum_age'],
            'source' => 'default_no_urls',
            'confidence' => 'low',
            'notes' => ['No URLs available to check for age requirements']
        ];
        
        // Store the result
        $database->beginTransaction();
        
        // Store the age requirements
        $age_sql = "INSERT INTO bg_company_attributes 
                   (company_id, type, name, description, status, create_dt)
                   VALUES 
                   (:company_id, 'age_requirements', 'birthday_program', :data, 'active', NOW())
                   ON DUPLICATE KEY UPDATE 
                   description = VALUES(description),
                   modify_dt = NOW()";
        $database->query($age_sql, [
            'company_id' => $company_id,
            'data' => json_encode($age_requirements)
        ]);
        
        // Update progress to completed
        $database->query("UPDATE bg_company_attributes 
                         SET description = 'completed', modify_dt = NOW() 
                         WHERE company_id = :company_id 
                         AND type = 'onboarding_progress' 
                         AND name = 'abo_grabage_airtop'", 
                         ['company_id' => $company_id]);
        
        $database->commit();
        
        $result['successful'] = 1;
        $result['message'] = "No URLs available - using default age requirements (0-150)";
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
                         AND name = 'abo_grabage_airtop'", 
                         ['company_id' => $company_id]);
        
        // Track AIRTOP session creation attempt
        session_tracking('abo_airtop_grabage_create_session', [
            'company_id' => $company_id,
            'api_url' => $airtopApiUrl,
            'urls_to_check' => count($urls_to_check)
        ]);
        
        // Create AIRTOP session
        $sessionId = createAirtopSession($system, $airtopApiUrl, $airtopApiKey);
        
        if (!$sessionId) {
            session_tracking('abo_airtop_grabage_session_failed', [
                'company_id' => $company_id,
                'error' => 'Failed to create AIRTOP session'
            ]);
            throw new Exception("Failed to create AIRTOP session");
        }
        
        // Track successful session creation
        session_tracking('abo_airtop_grabage_session_created', [
            'company_id' => $company_id,
            'session_id' => $sessionId
        ]);
        
        // Wait for session to be ready
        if (!waitForSessionReady($system, $airtopApiUrl, $airtopApiKey, $sessionId)) {
            terminateAirtopSession($system, $airtopApiUrl, $airtopApiKey, $sessionId);
            throw new Exception("AIRTOP session failed to become ready");
        }
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $airtopApiKey
        ];
        
        $age_findings = [];
        
        // Process each URL
        foreach ($urls_to_check as $index => $url) {
            if ($index >= 3) break; // Limit to first 3 URLs to save resources
            
            // Create window and navigate to URL
            $windowResponse = $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId . '/windows',
                $headers,
                ['url' => $url],
                'POST'
            );
            
            if (!isset($windowResponse['decoded']['data']['windowId'])) {
                continue; // Skip this URL if window creation fails
            }
            
            $windowId = $windowResponse['decoded']['data']['windowId'];
            
            // Track window creation
            session_tracking('abo_airtop_grabage_window_created', [
                'company_id' => $company_id,
                'window_id' => $windowId,
                'url' => $url,
                'url_index' => $index
            ]);
            
            // Wait for page to load
            sleep(5);
            
            // Create AI prompt for age requirement analysis
            $prompt = 'Analyze this page to find age requirements for birthday rewards, loyalty programs, or general terms of service. Look for minimum age requirements, maximum age limits, or age ranges. Common patterns include "must be 18", "13 years or older", "ages 21+", "children under 12", etc. Also check for references to "legal age", "age of majority", or "minors". Provide the minimum and maximum ages found, or use defaults of min=' . $bg_age_requirements_defaults['minimum_age'] . ' and max=' . $bg_age_requirements_defaults['maximum_age'] . ' if no specific ages are mentioned.';
            
            $jsonSchema = '{
  "type": "object",
  "properties": {
    "minimum_age": {
      "type": "integer",
      "description": "The minimum age requirement found. Use 0 if no minimum is specified."
    },
    "maximum_age": {
      "type": "integer",
      "description": "The maximum age limit found. Use 150 if no maximum is specified."
    },
    "age_ranges_found": {
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "min": {
            "type": "integer",
            "description": "Minimum age in the range"
          },
          "max": {
            "type": "integer",
            "description": "Maximum age in the range"
          },
          "context": {
            "type": "string",
            "description": "The text where this age range was found"
          },
          "applies_to": {
            "type": "string",
            "description": "What this age requirement applies to (e.g., birthday rewards, general terms, specific program)"
          }
        },
        "required": ["min", "max", "context", "applies_to"]
      },
      "description": "All age ranges or requirements found on the page"
    },
    "confidence": {
      "type": "string",
      "enum": ["high", "medium", "low"],
      "description": "Confidence level in the age requirements found"
    },
    "notes": {
      "type": "array",
      "items": {
        "type": "string"
      },
      "description": "Additional notes about age requirements or special conditions"
    }
  },
  "required": ["minimum_age", "maximum_age", "age_ranges_found", "confidence", "notes"],
  "additionalProperties": false,
  "$schema": "http://json-schema.org/draft-07/schema#"
}';
            
            // Build the request body
            $requestBody = [
                'prompt' => $prompt,
                'configuration' => [
                    'outputSchema' => $jsonSchema
                ]
            ];
            
            // Make the request
            $queryResponse = $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId . '/page-query',
                $headers,
                $requestBody,
                'POST'
            );
            
            // Track AI query response
            session_tracking('abo_airtop_grabage_ai_response', [
                'company_id' => $company_id,
                'url' => $url,
                'response_status' => $queryResponse['curl_info']['http_code'] ?? 'unknown',
                'has_error' => isset($queryResponse['error'])
            ]);
            
            if (isset($queryResponse['decoded']['data']['modelResponse'])) {
                $aiAnalysis = $queryResponse['decoded']['data']['modelResponse'];
                $ageData = json_decode($aiAnalysis, true);
                
                if ($ageData && isset($ageData['minimum_age']) && isset($ageData['maximum_age'])) {
                    $age_findings[] = [
                        'url' => $url,
                        'data' => $ageData
                    ];
                }
            }
        }
        
        // Always terminate session to free resources
        terminateAirtopSession($system, $airtopApiUrl, $airtopApiKey, $sessionId);
        
        // Process findings to determine final age requirements
        $final_min_age = $bg_age_requirements_defaults['minimum_age'];
        $final_max_age = $bg_age_requirements_defaults['maximum_age'];
        $confidence = 'low';
        $notes = [];
        $source = 'default';
        
        if (!empty($age_findings)) {
            // Find the most restrictive requirements
            $min_ages = [];
            $max_ages = [];
            
            foreach ($age_findings as $finding) {
                $data = $finding['data'];
                
                // Collect minimum ages (use highest/most restrictive)
                if ($data['minimum_age'] > 0) {
                    $min_ages[] = $data['minimum_age'];
                    $notes[] = "Found minimum age {$data['minimum_age']} at: " . basename(parse_url($finding['url'], PHP_URL_PATH));
                }
                
                // Collect maximum ages (use lowest/most restrictive)
                if ($data['maximum_age'] < $bg_age_requirements_defaults['maximum_age']) {
                    $max_ages[] = $data['maximum_age'];
                    $notes[] = "Found maximum age {$data['maximum_age']} at: " . basename(parse_url($finding['url'], PHP_URL_PATH));
                }
                
                // Add specific findings
                if (!empty($data['age_ranges_found'])) {
                    foreach ($data['age_ranges_found'] as $range) {
                        $notes[] = "Age range {$range['min']}-{$range['max']} for {$range['applies_to']}: \"{$range['context']}\"";
                    }
                }
                
                // Update confidence based on findings
                if ($data['confidence'] === 'high' && $confidence !== 'high') {
                    $confidence = 'high';
                } elseif ($data['confidence'] === 'medium' && $confidence === 'low') {
                    $confidence = 'medium';
                }
            }
            
            // Set final values
            if (!empty($min_ages)) {
                $final_min_age = max($min_ages); // Most restrictive
                $source = 'airtop_analysis';
            }
            if (!empty($max_ages)) {
                $final_max_age = min($max_ages); // Most restrictive
                $source = 'airtop_analysis';
            }
        } else {
            $notes[] = 'No specific age requirements found on checked pages - using defaults';
        }
        
        // Prepare final age requirements
        $age_requirements = [
            'minimum_age' => $final_min_age,
            'maximum_age' => $final_max_age,
            'source' => $source,
            'confidence' => $confidence,
            'notes' => $notes,
            'urls_checked' => count($urls_to_check),
            'airtop_analysis' => true
        ];
        
        // Store the age requirements
        $age_sql = "INSERT INTO bg_company_attributes 
                   (company_id, type, name, description, status, create_dt)
                   VALUES 
                   (:company_id, 'age_requirements', 'birthday_program', :data, 'active', NOW())
                   ON DUPLICATE KEY UPDATE 
                   description = VALUES(description),
                   modify_dt = NOW()";
        $database->query($age_sql, [
            'company_id' => $company_id,
            'data' => json_encode($age_requirements)
        ]);
        
        // Also store simplified min/max values
        $min_sql = "INSERT INTO bg_company_attributes 
                   (company_id, type, name, description, status, create_dt)
                   VALUES 
                   (:company_id, 'requirement', 'minimum_age', :age, 'active', NOW())
                   ON DUPLICATE KEY UPDATE 
                   description = VALUES(description),
                   modify_dt = NOW()";
        $database->query($min_sql, [
            'company_id' => $company_id,
            'age' => $final_min_age
        ]);
        
        $max_sql = "INSERT INTO bg_company_attributes 
                   (company_id, type, name, description, status, create_dt)
                   VALUES 
                   (:company_id, 'requirement', 'maximum_age', :age, 'active', NOW())
                   ON DUPLICATE KEY UPDATE 
                   description = VALUES(description),
                   modify_dt = NOW()";
        $database->query($max_sql, [
            'company_id' => $company_id,
            'age' => $final_max_age
        ]);
        
        // Store AIRTOP analysis metadata
        $metadata_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'age_analysis', 'airtop_results', :data, 'active', NOW())";
        $database->query($metadata_sql, [
            'company_id' => $company_id,
            'data' => json_encode([
                'findings' => $age_findings,
                'urls_checked' => $urls_to_check,
                'processed_at' => date('Y-m-d H:i:s')
            ])
        ]);
        
        
        // Update progress to completed
        $database->query("UPDATE bg_company_attributes 
                         SET description = 'completed', modify_dt = NOW() 
                         WHERE company_id = :company_id 
                         AND type = 'onboarding_progress' 
                         AND name = 'abo_grabage_airtop'", 
                         ['company_id' => $company_id]);
        
        $database->commit();
        
        // Track successful completion
        session_tracking('abo_airtop_grabage_completed', [
            'company_id' => $company_id,
            'company_name' => $company_name,
            'age_requirements' => "{$final_min_age}-{$final_max_age}",
            'confidence' => $confidence,
            'source' => $source,
            'urls_analyzed' => count($age_findings),
            'process_time' => time() - $_SERVER['REQUEST_TIME']
        ]);
        
        $result['successful'] = 1;
        $result['message'] = "Successfully extracted age requirements using AIRTOP AI";
        $result['age_requirements'] = $age_requirements;
        
    } catch (Exception $e) {
        $database->rollback();
        
        // Track error
        session_tracking('abo_airtop_grabage_error', [
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
                         AND name = 'abo_grabage_airtop'", 
                         ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['status'] = 'error';
        $result['errors'][] = $e->getMessage();
    }
    
} catch (Exception $e) {
    // Track fatal error
    session_tracking('abo_airtop_grabage_fatal_error', [
        'error_message' => $e->getMessage(),
        'error_trace' => $e->getTraceAsString()
    ]);
    
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
}

// Track final result
session_tracking('abo_airtop_grabage_complete', [
    'status' => $result['status'],
    'processed' => $result['processed'],
    'successful' => $result['successful'],
    'failed' => $result['failed'],
    'errors' => $result['errors'],
    'execution_time' => time() - $_SERVER['REQUEST_TIME']
]);

header('Content-Type: application/json');
echo json_encode($result);