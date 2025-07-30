<?php
// abo_grablocations_airtop.php - Extract store locations with AIRTOP integration
// Part of the Automation Business Onboarding (ABO) system
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

// Enable debug mode
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';
$debug_output = [];

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processor' => 'abo_grablocations_airtop',
    'processed' => 0,
    'successful' => 0,  
    'failed' => 0,
    'errors' => []
];

// AIRTOP configuration
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

try {
    // Get companies to process
    if ($specific_company_id) {
        // Check if this is a retrigger/reprocess request
        $is_retrigger = isset($_GET['retrigger']) && $_GET['retrigger'] == '1';
        $allow_reprocessing = isset($_GET['reprocess']) && $_GET['reprocess'] == '1';
        
        if ($is_retrigger || $allow_reprocessing) {
            // Track retrigger/reprocess request
            session_tracking('abo_grablocations_reprocess', [
                'company_id' => $specific_company_id,
                'type' => $is_retrigger ? 'retrigger' : 'reprocess',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            // For retrigger/reprocess, allow all statuses including completed
            $sql = "SELECT c.* FROM bg_companies c 
                    INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                    WHERE c.company_id = :company_id 
                    AND ca.type = 'onboarding_progress'
                    AND ca.name = 'abo_grablocations'
                    LIMIT 1";
        } else {
            // Normal processing - only pending, error, or attempted
            $sql = "SELECT c.* FROM bg_companies c 
                    INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                    WHERE c.company_id = :company_id 
                    AND ca.type = 'onboarding_progress'
                    AND ca.name = 'abo_grablocations'
                    AND ca.description IN ('pending', 'error', 'attempted')
                    LIMIT 1";
        }
        $params = ['company_id' => $specific_company_id];
    } else {
        // Check if we are in enrichment mode
        $enrichment_mode = isset($_GET['enrich']) && $_GET['enrich'] == '1';
        
        if ($enrichment_mode) {
            // Get completed companies that might benefit from location enrichment
            $sql = "SELECT DISTINCT c.* FROM bg_companies c 
                    INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                    INNER JOIN bg_company_locations cl ON c.company_id = cl.company_id
                    WHERE ca.type = 'onboarding_progress'
                    AND ca.name = 'abo_grablocations'
                    AND ca.description = 'completed'
                    AND (
                        cl.phone_number IS NULL OR cl.phone_number = '' OR
                        cl.zip_code IS NULL OR cl.zip_code = '' OR
                        cl.business_hours IS NULL OR cl.business_hours = '' OR
                        cl.latitude IS NULL OR cl.longitude IS NULL
                    )
                    ORDER BY c.modify_dt ASC
                    LIMIT 1";
            
            session_tracking('abo_grablocations_enrichment_mode', [
                'message' => 'Running in enrichment mode to update existing locations'
            ]);
        } else {
            // Get next company with pending location collection
            $sql = "SELECT c.* FROM bg_companies c 
                    INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                    WHERE c.status = 'approved_pending_data'
                    AND ca.type = 'onboarding_progress'
                    AND ca.name = 'abo_grablocations'
                    AND ca.description = 'pending'
                    ORDER BY c.create_dt ASC
                    LIMIT 1";
        }
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $result['message'] = 'No companies pending location collection';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    $result['processed'] = 1;
    $company_id = $company['company_id'];
    $company_name = $company['company_name'];
    $company_url = $company['company_url'] ?? '';
    
    if ($debug) {
        $debug_output['company'] = [
            'id' => $company_id,
            'name' => $company_name,
            'url' => $company_url
        ];
    }
    
    try {
        $database->beginTransaction();
        
        // Update progress to in_progress
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = 'in_progress', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grablocations'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        $locations_found = [];
        
        // Try Google search to find locations page
        $domain = parse_url($company_url, PHP_URL_HOST);
        
        // First try Google Custom Search API if we have credentials
        $google_cse_key = $configs['GOOGLE_CSE_API_KEY'] ?? '';
        $google_cse_cx = $configs['GOOGLE_CSE_CX'] ?? '';
        
        if (!empty($google_cse_key) && !empty($google_cse_cx)) {
            // Search for location pages on this domain
            $search_query = urlencode("site:{$domain} locations OR store locator OR find a store OR our locations");
            $google_search_url = "https://www.googleapis.com/customsearch/v1?key={$google_cse_key}&cx={$google_cse_cx}&q={$search_query}&num=10";
            
            if ($debug) {
                $debug_output['google_search'] = [
                    'domain' => $domain,
                    'query' => $search_query,
                    'url' => $google_search_url
                ];
            }
            
            $search_resp = $system->curlRequest($google_search_url, [], [], 'GET', [
                'timeout' => 20,
                'connecttimeout' => 5
            ]);
            $search_response = $search_resp['raw'] ?? '';
            $search_http_code = $search_resp['http_code'] ?? 0;
            
            if ($search_http_code === 200) {
                $search_results = json_decode($search_response, true);
                
                if ($debug) {
                    $debug_output['google_results'] = [
                        'http_code' => $search_http_code,
                        'total_results' => $search_results['searchInformation']['totalResults'] ?? 0,
                        'items_count' => count($search_results['items'] ?? [])
                    ];
                }
                
                if (isset($search_results['items']) && is_array($search_results['items'])) {
                    foreach ($search_results['items'] as $item) {
                        $found_url = $item['link'] ?? '';
                        
                        // Check if this URL looks like a locations page
                        if (preg_match('/\/(locations?|stores?|find-?(?:a-)?store|store-?locator|our-?locations?)/i', $found_url)) {
                            
                            // Store discovered URL
                            $store_url_sql = "INSERT INTO bg_company_attributes 
                                            (company_id, type, name, description, status, create_dt)
                                            VALUES 
                                            (:company_id, 'data_collection', 'locations_url', :url, 'active', NOW())
                                            ON DUPLICATE KEY UPDATE description = VALUES(description), modify_dt = NOW()";
                            $database->query($store_url_sql, ['company_id' => $company_id, 'url' => $found_url]);
                            
                            // Now use AIRTOP to extract locations from the discovered URL
                            session_tracking('abo_airtop_triggered', [
                                'company_id' => $company_id,
                                'company_name' => $company_name,
                                'locations_url' => $found_url
                            ]);
                            
                            try {
                                // Create AIRTOP session
                                $sessionId = createAirtopSession($system, $airtopApiUrl, $airtopApiKey);
                                
                                if ($sessionId) {
                                    // Wait for session to be ready
                                    if (waitForSessionReady($system, $airtopApiUrl, $airtopApiKey, $sessionId)) {
                                        // Create window and navigate to locations URL
                                        $headers = [
                                            'Content-Type: application/json',
                                            'Authorization: Bearer ' . $airtopApiKey
                                        ];
                                        
                                        $windowResponse = $system->curlRequest(
                                            $airtopApiUrl . 'sessions/' . $sessionId . '/windows',
                                            $headers,
                                            ['url' => $found_url],
                                            'POST'
                                        );
                                        
                                        if (isset($windowResponse['decoded']['data']['windowId'])) {
                                            $windowId = $windowResponse['decoded']['data']['windowId'];
                                            
                                            // Wait for page to load
                                            sleep(5);
                                            
                                            // Prepare prompt for AIRTOP
                                            $prompt = "Extract all store/location addresses from this page. For each location, provide:
                                                      1. Full street address
                                                      2. City
                                                      3. State/Province
                                                      4. ZIP/Postal code
                                                      5. Phone number (if available)
                                                      6. Store hours (if available)
                                                      Return as JSON array with fields: address, city, state, zip_code, phone_number, business_hours";
                                            
                                            // Query the page
                                            $queryResponse = $system->curlRequest(
                                                $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId . '/page-query',
                                                $headers,
                                                ['prompt' => $prompt],
                                                'POST'
                                            );
                                            
                                            if (isset($queryResponse['decoded']['data']['modelResponse'])) {
                                                $aiResponse = $queryResponse['decoded']['data']['modelResponse'];
                                                
                                                // Try to parse the AI response as JSON
                                                $extracted_locations = json_decode($aiResponse, true);
                                                
                                                if (is_array($extracted_locations)) {
                                                    foreach ($extracted_locations as $loc) {
                                                        if (!empty($loc['address']) && !empty($loc['city'])) {
                                                            $location = [
                                                                'address' => $loc['address'],
                                                                'city' => $loc['city'],
                                                                'state' => $loc['state'] ?? '',
                                                                'zip_code' => $loc['zip_code'] ?? '',
                                                                'phone_number' => $loc['phone_number'] ?? '',
                                                                'business_hours' => $loc['business_hours'] ?? '',
                                                                'source' => 'airtop_extracted: ' . $found_url
                                                            ];
                                                            
                                                            // Validate it's not a PO Box
                                                            if (!preg_match('/P\.?O\.?\s*Box/i', $location['address'])) {
                                                                $locations_found[] = $location;
                                                            }
                                                        }
                                                    }
                                                    
                                                    session_tracking('abo_airtop_success', [
                                                        'company_id' => $company_id,
                                                        'locations_extracted' => count($locations_found),
                                                        'url' => $found_url
                                                    ]);
                                                }
                                            }
                                        }
                                        
                                        // Always terminate session
                                        terminateAirtopSession($system, $airtopApiUrl, $airtopApiKey, $sessionId);
                                    }
                                }
                            } catch (Exception $e) {
                                session_tracking('abo_airtop_error', [
                                    'company_id' => $company_id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                            
                            // If AIRTOP extracted locations, we're done
                            if (!empty($locations_found)) {
                                break;
                            }
                        }
                    }
                }
            } else {
                if ($debug) {
                    $debug_output['google_error'] = 'HTTP code: ' . $search_http_code;
                }
            }
        } else {
            if ($debug) {
                $debug_output['google_config'] = 'Missing Google CSE credentials';
            }
            
            // Fallback: Try common URL patterns
            session_tracking('abo_airtop_url_pattern_fallback', [
                'company_id' => $company_id,
                'reason' => 'No Google CSE credentials'
            ]);
            
            $base_url = rtrim($company_url, '/');
            $patterns = [
                '/locations',
                '/stores', 
                '/store-locator',
                '/find-a-store',
                '/our-locations',
                '/locations/all'
            ];
            
            foreach ($patterns as $pattern) {
                $test_url = $base_url . $pattern;
                
                // Quick HEAD request to check existence
                $headers = ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'];
                $response = $system->curlRequest($test_url, $headers, [], 'HEAD', [
                    'timeout' => 10,
                    'followlocation' => true
                ]);
                
                if (!isset($response['error']) && isset($response['http_code']) && $response['http_code'] == 200) {
                    // Found a valid locations page
                    $found_url = $test_url;
                    
                    if ($debug) {
                        $debug_output['url_pattern_found'] = $found_url;
                    }
                    
                    // Store discovered URL
                    $store_url_sql = "INSERT INTO bg_company_attributes 
                                    (company_id, type, name, description, status, create_dt)
                                    VALUES 
                                    (:company_id, 'data_collection', 'locations_url', :url, 'active', NOW())
                                    ON DUPLICATE KEY UPDATE description = VALUES(description), modify_dt = NOW()";
                    $database->query($store_url_sql, ['company_id' => $company_id, 'url' => $found_url]);
                    
                    // Use AIRTOP to extract locations
                    session_tracking('abo_airtop_triggered_pattern', [
                        'company_id' => $company_id,
                        'company_name' => $company_name,
                        'locations_url' => $found_url,
                        'pattern' => $pattern
                    ]);
                    
                    try {
                        // Create AIRTOP session
                        $sessionId = createAirtopSession($system, $airtopApiUrl, $airtopApiKey);
                        
                        if ($sessionId) {
                            // Wait for session to be ready
                            if (waitForSessionReady($system, $airtopApiUrl, $airtopApiKey, $sessionId)) {
                                // Create window and navigate to locations URL
                                $headers = [
                                    'Content-Type: application/json',
                                    'Authorization: Bearer ' . $airtopApiKey
                                ];
                                
                                $windowResponse = $system->curlRequest(
                                    $airtopApiUrl . 'sessions/' . $sessionId . '/windows',
                                    $headers,
                                    ['url' => $found_url],
                                    'POST'
                                );
                                
                                if (isset($windowResponse['decoded']['data']['windowId'])) {
                                    $windowId = $windowResponse['decoded']['data']['windowId'];
                                    
                                    // Wait for page to load
                                    sleep(5);
                                    
                                    // Prepare prompt for AIRTOP
                                    $prompt = "Extract all store/location addresses from this page. For each location, provide:
                                              1. Full street address
                                              2. City
                                              3. State/Province
                                              4. ZIP/Postal code
                                              5. Phone number (if available)
                                              6. Store hours (if available)
                                              Return as JSON array with fields: address, city, state, zip_code, phone_number, business_hours";
                                    
                                    // Query the page
                                    $queryResponse = $system->curlRequest(
                                        $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId . '/page-query',
                                        $headers,
                                        ['prompt' => $prompt],
                                        'POST'
                                    );
                                    
                                    if (isset($queryResponse['decoded']['data']['modelResponse'])) {
                                        $aiResponse = $queryResponse['decoded']['data']['modelResponse'];
                                        
                                        // Try to parse the AI response as JSON
                                        $extracted_locations = json_decode($aiResponse, true);
                                        
                                        if (is_array($extracted_locations)) {
                                            foreach ($extracted_locations as $loc) {
                                                if (!empty($loc['address']) && !empty($loc['city'])) {
                                                    $location = [
                                                        'address' => $loc['address'],
                                                        'city' => $loc['city'],
                                                        'state' => $loc['state'] ?? '',
                                                        'zip_code' => $loc['zip_code'] ?? '',
                                                        'phone_number' => $loc['phone_number'] ?? '',
                                                        'business_hours' => $loc['business_hours'] ?? '',
                                                        'source' => 'airtop_extracted_pattern: ' . $found_url
                                                    ];
                                                    
                                                    // Validate it's not a PO Box
                                                    if (!preg_match('/P\.?O\.?\s*Box/i', $location['address'])) {
                                                        $locations_found[] = $location;
                                                    }
                                                }
                                            }
                                            
                                            session_tracking('abo_airtop_pattern_success', [
                                                'company_id' => $company_id,
                                                'locations_extracted' => count($locations_found),
                                                'url' => $found_url
                                            ]);
                                        }
                                    }
                                }
                                
                                // Always terminate session
                                terminateAirtopSession($system, $airtopApiUrl, $airtopApiKey, $sessionId);
                            }
                        }
                    } catch (Exception $e) {
                        session_tracking('abo_airtop_pattern_error', [
                            'company_id' => $company_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    // If locations found, break
                    if (!empty($locations_found)) {
                        break;
                    }
                }
            }
        }
        
        // Initialize variables for tracking
        $unique_locations = [];
        $locations_saved = 0;
        $locations_inserted = 0;
        $locations_updated = 0;
        
        // Process locations if found
        if (!empty($locations_found)) {
            // Deduplicate
            $seen = [];
            
            foreach ($locations_found as $location) {
                $key = strtolower(trim($location['address'] . '|' . $location['city'] . '|' . ($location['state'] ?? '')));
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $unique_locations[] = $location;
                }
            }
            
            // Save to database
            foreach ($unique_locations as $location) {
                // Check if location already exists
                $check_sql = "SELECT location_id, phone_number, zip_code, business_hours 
                            FROM bg_company_locations 
                            WHERE company_id = :company_id 
                            AND address = :address 
                            AND city = :city";
                
                $check_params = [
                    'company_id' => $company_id,
                    'address' => $location['address'],
                    'city' => $location['city']
                ];
                
                if (!empty($location['state'])) {
                    $check_sql .= " AND state = :state";
                    $check_params['state'] = $location['state'];
                }
                
                $stmt = $database->query($check_sql, $check_params);
                $existing_location = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing_location) {
                    // Update existing location with new data
                    $updates = [];
                    $update_params = ['location_id' => $existing_location['location_id']];
                    $needs_update = false;
                    
                    if (empty($existing_location['phone_number']) && !empty($location['phone_number'])) {
                        $updates[] = 'phone_number = :phone_number';
                        $update_params['phone_number'] = $location['phone_number'];
                        $needs_update = true;
                    }
                    
                    if (empty($existing_location['zip_code']) && !empty($location['zip_code'])) {
                        $updates[] = 'zip_code = :zip_code';
                        $update_params['zip_code'] = $location['zip_code'];
                        $needs_update = true;
                    }
                    
                    if (empty($existing_location['business_hours']) && !empty($location['business_hours'])) {
                        $updates[] = 'business_hours = :business_hours';
                        $update_params['business_hours'] = $location['business_hours'];
                        $needs_update = true;
                    }
                    
                    if ($needs_update) {
                        $updates[] = 'source = :source';
                        $update_params['source'] = $location['source'];
                        $updates[] = 'modify_dt = NOW()';
                        
                        $update_sql = "UPDATE bg_company_locations SET " . implode(', ', $updates) . 
                                    " WHERE location_id = :location_id";
                        $database->query($update_sql, $update_params);
                        $locations_updated++;
                    }
                } else {
                    // Insert new location
                    $insert_sql = "INSERT INTO bg_company_locations 
                                (company_id, source, address, city, state, zip_code, 
                                 phone_number, business_hours, is_verified, status, create_dt)
                                VALUES 
                                (:company_id, :source, :address, :city, :state, :zip_code,
                                 :phone_number, :business_hours, 0, 'active', NOW())";
                    
                    $database->query($insert_sql, [
                        'company_id' => $company_id,
                        'source' => $location['source'],
                        'address' => $location['address'],
                        'city' => $location['city'],
                        'state' => $location['state'] ?? null,
                        'zip_code' => $location['zip_code'] ?? null,
                        'phone_number' => $location['phone_number'] ?? null,
                        'business_hours' => $location['business_hours'] ?? null
                    ]);
                    
                    $locations_inserted++;
                }
                
                $locations_saved++;
            }
            
            $status = 'completed';
            $result['successful'] = 1;
            $result['data_collected'] = "Found {$locations_saved} locations (inserted: {$locations_inserted}, updated: {$locations_updated})";
        } else {
            $status = 'attempted';
            $result['successful'] = 1;
            $result['data_collected'] = 'No locations found with AIRTOP extraction';
        }
        
        // Update progress status
        $complete_sql = "UPDATE bg_company_attributes 
                        SET description = :status, modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grablocations'";
        $database->query($complete_sql, [
            'status' => $status,
            'company_id' => $company_id
        ]);
        
        $database->commit();
        
        // Add location details to result
        $result['locations_summary'] = [
            'found' => count($locations_found),
            'unique' => count($unique_locations),
            'saved' => $locations_saved,
            'inserted' => $locations_inserted,
            'updated' => $locations_updated
        ];
        
    } catch (Exception $e) {
        $database->rollBack();
        
        // Update progress to error
        $error_sql = "UPDATE bg_company_attributes 
                     SET description = 'error', modify_dt = NOW() 
                     WHERE company_id = :company_id 
                     AND type = 'onboarding_progress' 
                     AND name = 'abo_grablocations'";
        $database->query($error_sql, ['company_id' => $company_id]);
        
        $result['failed'] = 1;
        $result['errors'][] = "Company $company_id: " . $e->getMessage();
        session_tracking('ABO grab locations error', "Company $company_id: " . $e->getMessage());
    }
    
    $result['message'] = "Processed {$result['processed']} company: {$result['successful']} successful, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    session_tracking('ABO grab locations fatal error', $e->getMessage());
}

// Add debug output if enabled
if ($debug && !empty($debug_output)) {
    $result['debug'] = $debug_output;
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);