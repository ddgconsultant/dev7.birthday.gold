<?php
// abo_grabhours_airtop.php - AIRTOP-enhanced business hours extraction
// Specifically designed to extract hours from location pages using AI
// Created: 2025-01-31

$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'task_processed' => false,
    'company_id' => null,
    'task_name' => 'abo_grabhours_airtop',
    'errors' => [],
    'company_name' => null,
    'task_status' => 'pending'
];

// Enable debug mode
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';
$debug_output = [];

if ($debug) {
    $debug_output[] = ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'Debug mode enabled'];
}

try {
    // Check for specific company ID (debug mode)
    $specific_company_id = null;
    if (isset($_REQUEST['rawid'])) {
        $specific_company_id = intval($_REQUEST['rawid']);
    } elseif (isset($_REQUEST['id'])) {
        $specific_company_id = $qik->decrypt($_REQUEST['id']);
    }
    
    if ($specific_company_id) {
        // Process specific company
        $sql = "SELECT c.*, ca.description as task_status 
                FROM bg_companies c
                LEFT JOIN bg_company_attributes ca ON c.company_id = ca.company_id 
                    AND ca.type = 'onboarding_progress' 
                    AND ca.name = 'abo_grabhours_airtop'
                WHERE c.company_id = :company_id";
        $stmt = $database->prepare($sql);
        $stmt->execute(['company_id' => $specific_company_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Get next company needing hours extraction
        $sql = "SELECT c.*, ca.description as task_status 
                FROM bg_companies c
                LEFT JOIN bg_company_attributes ca ON c.company_id = ca.company_id 
                    AND ca.type = 'onboarding_progress' 
                    AND ca.name = 'abo_grabhours_airtop'
                WHERE c.status IN ('approved_pending_data', 'active')
                    AND (ca.description IS NULL OR ca.description = 'pending' OR ca.description = 'error')
                    AND c.company_url IS NOT NULL
                ORDER BY c.modify_dt DESC
                LIMIT 1";
        $stmt = $database->prepare($sql);
        $stmt->execute();
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$company) {
        $result['message'] = 'No companies pending hours extraction';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    $result['company_id'] = $company['company_id'];
    $result['company_name'] = $company['company_name'];
    $result['task_processed'] = true;
    
    // Update status to in_progress
    $progress_sql = "INSERT INTO bg_company_attributes 
                    (company_id, type, name, description, status, create_dt, modify_dt)
                    VALUES (:company_id, 'onboarding_progress', 'abo_grabhours_airtop', 'in_progress', 'active', NOW(), NOW())
                    ON DUPLICATE KEY UPDATE description = 'in_progress', modify_dt = NOW()";
    $database->query($progress_sql, ['company_id' => $company['company_id']]);
    
    // Initialize hours data
    $hours_data = [];
    $locations_with_hours = [];
    $found_hours = false;
    $base_url = rtrim($company['company_url'], '/');
    
    // First check if we have crawled pages for this company
    $crawled_pages_sql = "SELECT * FROM bg_company_pages 
                         WHERE company_id = :company_id 
                         AND page_type IN ('hours', 'locations', 'contact')
                         AND status = 'active'
                         ORDER BY confidence_score DESC, 
                                FIELD(page_type, 'hours', 'locations', 'contact')
                         LIMIT 10";
    $stmt = $database->query($crawled_pages_sql, ['company_id' => $company['company_id']]);
    $crawled_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $urls_to_check = [];
    
    if (!empty($crawled_pages)) {
        // Use crawled pages
        if ($debug) {
            $debug_output[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Using ' . count($crawled_pages) . ' crawled pages from bg_company_pages'
            ];
        }
        foreach ($crawled_pages as $page) {
            $urls_to_check[] = $page['url'];
        }
    } else {
        // Fallback to guessing URLs if no crawled pages
        if ($debug) {
            $debug_output[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'No crawled pages found, using URL patterns'
            ];
        }
        $url_patterns = [
            '/locations',
            '/pages/locations',
            '/store-locator',
            '/find-us',
            '/hours',
            '/contact',
            '/visit-us',
            '/our-locations',
            '/stores'
        ];
        
        foreach ($url_patterns as $pattern) {
            $urls_to_check[] = $base_url . $pattern;
        }
    }
    
    // Initialize AIRTOP session
    $airtopApiUrl = 'https://api.airtop.ai/api/v1/';
    $airtopApiKey = $sitesettings_ai['airtop']['apikey'] ?? '';
    
    if ($debug) {
        $debug_output[] = ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'AIRTOP API URL: ' . $airtopApiUrl];
        $debug_output[] = ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'AIRTOP API Key exists: ' . (!empty($airtopApiKey) ? 'Yes' : 'No')];
    }
    
    if (empty($airtopApiKey)) {
        throw new Exception('AIRTOP API key not configured');
    }
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $airtopApiKey
    ];
    
    // Create AIRTOP session
    if ($debug) {
        $debug_output[] = ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'Creating AIRTOP session...'];
    }
    
    $sessionResponse = $system->curlRequest(
        $airtopApiUrl . 'sessions',
        $headers,
        [],
        'POST'
    );
    
    if ($debug) {
        $debug_output[] = [
            'timestamp' => date('Y-m-d H:i:s'), 
            'message' => 'Session response',
            'response' => $sessionResponse['decoded'] ?? $sessionResponse
        ];
    }
    
    if (!isset($sessionResponse['decoded']['data']['id'])) {
        throw new Exception('Failed to create AIRTOP session: ' . json_encode($sessionResponse));
    }
    
    $sessionId = $sessionResponse['decoded']['data']['id'];
    
    if ($debug) {
        $debug_output[] = ['timestamp' => date('Y-m-d H:i:s'), 'message' => 'Session created: ' . $sessionId];
    }
    
    // Wait for session to be ready (it starts in "initializing" state)
    $maxWaitTime = 30; // Maximum 30 seconds
    $waitInterval = 2; // Check every 2 seconds
    $sessionReady = false;
    
    for ($i = 0; $i < ($maxWaitTime / $waitInterval); $i++) {
        sleep($waitInterval);
        
        // Check session status
        $sessionStatusResponse = $system->curlRequest(
            $airtopApiUrl . 'sessions/' . $sessionId,
            $headers,
            [],
            'GET'
        );
        
        if ($debug && $i == 0) {
            $debug_output[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Waiting for session to be ready...'
            ];
        }
        
        if (isset($sessionStatusResponse['decoded']['data']['status'])) {
            $status = $sessionStatusResponse['decoded']['data']['status'];
            if (in_array($status, ['active', 'ready', 'running'])) {
                $sessionReady = true;
                if ($debug) {
                    $debug_output[] = [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'message' => 'Session is ready',
                        'status' => $status,
                        'wait_time' => ($i + 1) * $waitInterval . ' seconds'
                    ];
                }
                break;
            }
        }
    }
    
    if (!$sessionReady) {
        throw new Exception('Session failed to become ready after ' . $maxWaitTime . ' seconds');
    }
    
    try {
        // Try each URL
        foreach ($urls_to_check as $test_url) {
            
            if ($debug) {
                $debug_output[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => 'Creating window for URL: ' . $test_url
                ];
            }
            
            // Create window with the target URL
            $windowResponse = $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId . '/windows',
                $headers,
                ['url' => $test_url],
                'POST'
            );
            
            if ($debug) {
                $debug_output[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => 'Window creation response',
                    'url' => $test_url,
                    'response' => $windowResponse['decoded'] ?? $windowResponse,
                    'http_code' => $windowResponse['http_code'] ?? null,
                    'error' => $windowResponse['error'] ?? null
                ];
            }
            
            if (!isset($windowResponse['decoded']['data']['windowId'])) {
                if ($debug) {
                    $debug_output[] = [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'message' => 'Window creation failed for URL: ' . $test_url,
                        'reason' => 'No windowId in response'
                    ];
                }
                // Try next URL if window creation fails
                continue;
            }
            
            $windowId = $windowResponse['decoded']['data']['windowId'];
            
            if ($debug) {
                $debug_output[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => 'Window created successfully',
                    'windowId' => $windowId
                ];
            }
            
            // Wait for page to load
            sleep(5);
            
            // Enhanced prompt specifically for hours extraction
            $prompt = "Extract ALL business hours and location information from this page. 
                      For EACH location found, provide:
                      1. Location name or identifier
                      2. Full address (street, city, state, zip)
                      3. Phone number
                      4. Business hours for EACH day of the week
                      
                      Format the hours clearly, for example:
                      Monday: 8:00 am - 4:00 pm
                      Tuesday: 8:00 am - 4:00 pm
                      etc.
                      
                      If hours are listed as 'Monday - Sunday: 8:00 am - 4:00 pm', expand to show each day.
                      
                      Return as JSON array with fields:
                      - location_name
                      - address
                      - city
                      - state
                      - zip_code
                      - phone
                      - hours (as an object with keys: monday, tuesday, wednesday, thursday, friday, saturday, sunday)
                      
                      If no hours are found on this page, return an empty array.";
            
            if ($debug) {
                $debug_output[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => 'Querying page for hours',
                    'windowId' => $windowId,
                    'url' => $test_url
                ];
            }
            
            // Query the page
            $queryResponse = $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId . '/page-query',
                $headers,
                ['prompt' => $prompt],
                'POST'
            );
            
            if ($debug) {
                $debug_output[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => 'Page query response',
                    'has_model_response' => isset($queryResponse['decoded']['data']['modelResponse']),
                    'response_preview' => isset($queryResponse['decoded']['data']['modelResponse']) ? 
                        substr($queryResponse['decoded']['data']['modelResponse'], 0, 200) : null,
                    'error' => $queryResponse['error'] ?? null
                ];
            }
            
            if (isset($queryResponse['decoded']['data']['modelResponse'])) {
                $aiResponse = $queryResponse['decoded']['data']['modelResponse'];
                
                // Try to parse the AI response as JSON
                $extracted_data = json_decode($aiResponse, true);
                
                if (is_array($extracted_data) && !empty($extracted_data)) {
                    if ($debug) {
                        $debug_output[] = [
                            'timestamp' => date('Y-m-d H:i:s'),
                            'message' => 'Successfully parsed JSON response',
                            'locations_count' => count($extracted_data)
                        ];
                    }
                    
                    foreach ($extracted_data as $location) {
                        if (!empty($location['hours'])) {
                            $found_hours = true;
                            
                            // Format hours data
                            $formatted_hours = [];
                            if (is_array($location['hours'])) {
                                foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
                                    if (!empty($location['hours'][$day])) {
                                        $formatted_hours[$day] = $location['hours'][$day];
                                    }
                                }
                            }
                            
                            $location_data = [
                                'name' => $location['location_name'] ?? $location['address'] ?? 'Main Location',
                                'address' => $location['address'] ?? '',
                                'city' => $location['city'] ?? '',
                                'state' => $location['state'] ?? '',
                                'zip' => $location['zip_code'] ?? '',
                                'phone' => $location['phone'] ?? '',
                                'hours' => $formatted_hours,
                                'source_url' => $test_url
                            ];
                            
                            $locations_with_hours[] = $location_data;
                        }
                    }
                    
                    if ($found_hours) {
                        break; // Found hours, stop searching
                    }
                }
            }
            
            // Close the window after checking this URL
            if (isset($windowId)) {
                $system->curlRequest(
                    $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId,
                    $headers,
                    [],
                    'DELETE'
                );
            }
        }
        
        // If no hours found on dedicated pages, try homepage
        if (!$found_hours) {
            if ($debug) {
                $debug_output[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => 'No hours found on location pages, trying homepage',
                    'url' => $base_url
                ];
            }
            
            $windowResponse = $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId . '/windows',
                $headers,
                ['url' => $base_url],
                'POST'
            );
            
            if ($debug) {
                $debug_output[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => 'Homepage window creation response',
                    'has_windowId' => isset($windowResponse['decoded']['data']['windowId']),
                    'error' => $windowResponse['error'] ?? null
                ];
            }
            
            if (isset($windowResponse['decoded']['data']['windowId'])) {
                $windowId = $windowResponse['decoded']['data']['windowId'];
                
                sleep(5);
            
            $prompt = "Extract any business hours information from this page. 
                      Look for hours of operation, when they're open, or similar information.
                      Return as JSON with fields: location_name, hours (object with days as keys)";
            
            $queryResponse = $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId . '/page-query',
                $headers,
                ['prompt' => $prompt],
                'POST'
            );
            
                if (isset($queryResponse['decoded']['data']['modelResponse'])) {
                    $aiResponse = $queryResponse['decoded']['data']['modelResponse'];
                    $extracted_data = json_decode($aiResponse, true);
                    
                    if (!empty($extracted_data['hours'])) {
                        $found_hours = true;
                        $locations_with_hours[] = [
                            'name' => 'Main Location',
                            'hours' => $extracted_data['hours'],
                            'source_url' => $base_url
                        ];
                    }
                }
            }
        }
        
    } finally {
        // Always close the AIRTOP session
        if (isset($sessionId)) {
            $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId,
                $headers,
                [],
                'DELETE'
            );
        }
    }
    
    // Store the extracted hours data
    if ($found_hours && !empty($locations_with_hours)) {
        if ($debug) {
            $debug_output[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Hours extraction successful',
                'locations_found' => count($locations_with_hours),
                'sample_location' => $locations_with_hours[0] ?? null
            ];
        }
        // Store in bg_company_attributes (using description field for JSON data)
        $hours_json = json_encode($locations_with_hours);
        $store_sql = "INSERT INTO bg_company_attributes 
                     (company_id, type, name, description, status, create_dt, modify_dt)
                     VALUES (:company_id, 'business_hours', 'extracted_hours', :description, 'active', NOW(), NOW())
                     ON DUPLICATE KEY UPDATE description = :description2, modify_dt = NOW()";
        $database->query($store_sql, [
            'company_id' => $company['company_id'],
            'description' => $hours_json,
            'description2' => $hours_json
        ]);
        
        // Also update any existing locations in bg_company_locations
        if (count($locations_with_hours) == 1) {
            // Single location - update all locations with same hours
            $hours_string = json_encode($locations_with_hours[0]['hours']);
            $update_sql = "UPDATE bg_company_locations 
                          SET business_hours = :hours, modify_dt = NOW()
                          WHERE company_id = :company_id 
                          AND (business_hours IS NULL OR business_hours = '')";
            $database->query($update_sql, [
                'company_id' => $company['company_id'],
                'hours' => $hours_string
            ]);
        }
        
        $result['task_status'] = 'completed';
        $result['hours_found'] = count($locations_with_hours);
        $result['message'] = 'Successfully extracted hours for ' . count($locations_with_hours) . ' location(s)';
    } else {
        $result['task_status'] = 'completed';
        $result['message'] = 'No hours information found';
    }
    
    // Update task status
    $progress_sql = "UPDATE bg_company_attributes 
                    SET description = :status, modify_dt = NOW()
                    WHERE company_id = :company_id 
                    AND type = 'onboarding_progress' 
                    AND name = 'abo_grabhours_airtop'";
    $database->query($progress_sql, [
        'status' => $result['task_status'],
        'company_id' => $company['company_id']
    ]);
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    $result['task_status'] = 'error';
    
    // Update task status to error
    if (!empty($company['company_id'])) {
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = 'error', modify_dt = NOW()
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grabhours_airtop'";
        $database->query($progress_sql, [
            'company_id' => $company['company_id']
        ]);
    }
}

// Add debug output if enabled
if ($debug && !empty($debug_output)) {
    $result['debug'] = $debug_output;
}

// Output result
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);