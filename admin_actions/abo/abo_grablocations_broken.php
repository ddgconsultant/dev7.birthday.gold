<?php
// abo_grablocations.php - Extract store locations and addresses
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
    'processor' => 'abo_grablocations',
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => []
];

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
            // Prioritize companies with locations that have missing data
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
        
        // Method 1: Scrape website for location information
        session_tracking('abo_grablocations_method1_start', [
            'company_id' => $company_id,
            'company_name' => $company_name,
            'company_url' => $company['company_url']
        ]);
        
        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Cache-Control: no-cache'
        ];
        
        $response = $system->curlRequest($company['company_url'], $headers, [], 'GET', [
            'timeout' => 30,
            'followlocation' => true,
            'maxredirs' => 5,
            'encoding' => 'gzip, deflate',
            'connecttimeout' => 10
        ]);
        
        if (!empty($response['error'])) {
            session_tracking('abo_grablocations_method1_error', [
                'company_id' => $company_id,
                'error' => $response['error'],
                'url' => $company['company_url'],
                'http_code' => $response['http_code'] ?? 0
            ]);
            $html = '';
            $httpCode = $response['http_code'] ?? 0;
        } else {
            $html = $response['raw'];
            $httpCode = $response['http_code'];
            
            session_tracking('abo_grablocations_method1_success', [
                'company_id' => $company_id,
                'html_length' => strlen($html),
                'url' => $company['company_url'],
                'http_code' => $httpCode,
                'effective_url' => $response['effective_url'],
                'total_time' => $response['total_time'],
                'redirect_count' => $response['redirect_count']
            ]);
        }
        
        if (!empty($html)) {
            // Look for common patterns that indicate location pages
            $location_patterns = [
                '/<a[^>]+href=["\']([^"\']*(?:locations?|stores?|find-?us|store-?locator|find-?a-?store)[^"\']*)["\'][^>]*>/i',
                '/<a[^>]+(?:locations?|stores?|find)[^>]+href=["\']([^"\']+)["\'][^>]*>/i'
            ];
            
            foreach ($location_patterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $locations_url = $matches[1];
                    
                    // Make URL absolute
                    $parsed_base = parse_url($company['company_url']);
                    if (!filter_var($locations_url, FILTER_VALIDATE_URL)) {
                        if (substr($locations_url, 0, 2) === '//') {
                            $locations_url = $parsed_base['scheme'] . ':' . $locations_url;
                        } elseif (substr($locations_url, 0, 1) === '/') {
                            $locations_url = $parsed_base['scheme'] . '://' . $parsed_base['host'] . $locations_url;
                        } else {
                            $locations_url = $parsed_base['scheme'] . '://' . $parsed_base['host'] . '/' . $locations_url;
                        }
                    }
                    
                    // Store the locations URL as an attribute
                    $attr_sql = "INSERT INTO bg_company_attributes 
                                (company_id, type, name, description, status, create_dt)
                                VALUES 
                                (:company_id, 'data_collection', 'locations_url', :url, 'active', NOW())
                                ON DUPLICATE KEY UPDATE description = VALUES(description), modify_dt = NOW()";
                    $database->query($attr_sql, [
                        'company_id' => $company_id,
                        'url' => $locations_url
                    ]);
                    
                    break;
                }
            }
            
            // Look for addresses in the HTML
            $address_patterns = [
                // US address pattern
                '/(\d+\s+[A-Za-z\s]+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr|Way|Court|Ct|Plaza|Place|Pl)\.?(?:\s+(?:Suite|Ste|Unit|Apt|#)\s*\w+)?),?\s*([A-Za-z\s]+),?\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)/i',
                // Simple city, state pattern
                '/([A-Za-z\s]+),\s*([A-Z]{2})\s+(\d{5})/i'
            ];
            
            foreach ($address_patterns as $pattern) {
                if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        if (count($match) >= 4) {
                            $location = [
                                'address' => trim($match[1]),
                                'city' => trim($match[count($match) - 3]),
                                'state' => trim($match[count($match) - 2]),
                                'zip_code' => trim($match[count($match) - 1]),
                                'source' => 'website_scrape'
                            ];
                            
                            // Validate it is not a PO Box
                            if (!preg_match('/P\.?O\.?\s*Box/i', $location['address'])) {
                                $locations_found[] = $location;
                            }
                        }
                    }
                }
            }
            
            // Look for structured data (JSON-LD)
            if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $jsonld_matches)) {
                foreach ($jsonld_matches[1] as $jsonld) {
                    try {
                        $data = json_decode($jsonld, true);
                        if ($data && isset($data['@type'])) {
                            // Check for LocalBusiness or Store
                            if (in_array($data['@type'], ['LocalBusiness', 'Store', 'Restaurant']) && isset($data['address'])) {
                                $addr = $data['address'];
                                if (is_array($addr) && isset($addr['streetAddress'])) {
                                    $location = [
                                        'address' => $addr['streetAddress'] ?? '',
                                        'city' => $addr['addressLocality'] ?? '',
                                        'state' => $addr['addressRegion'] ?? '',
                                        'zip_code' => $addr['postalCode'] ?? '',
                                        'country' => $addr['addressCountry'] ?? 'United States',
                                        'source' => 'structured_data'
                                    ];
                                    
                                    if (isset($data['telephone'])) {
                                        $location['phone_number'] = $data['telephone'];
                                    }
                                    
                                    if (isset($data['geo'])) {
                                        $location['latitude'] = $data['geo']['latitude'] ?? null;
                                        $location['longitude'] = $data['geo']['longitude'] ?? null;
                                    }
                                    
                                    if (isset($data['openingHours'])) {
                                        $location['business_hours'] = is_array($data['openingHours']) 
                                            ? implode(', ', $data['openingHours']) 
                                            : $data['openingHours'];
                                    }
                                    
                                    if (!empty($location['address'])) {
                                        $locations_found[] = $location;
                                    }
                                }
                            }
                            
                            // Check for Organization with multiple locations
                            if ($data['@type'] === 'Organization' && isset($data['location'])) {
                                $locations = is_array($data['location']) && isset($data['location'][0]) 
                                    ? $data['location'] 
                                    : [$data['location']];
                                    
                                foreach ($locations as $loc) {
                                    if (isset($loc['address'])) {
                                        $addr = $loc['address'];
                                        $location = [
                                            'address' => $addr['streetAddress'] ?? '',
                                            'city' => $addr['addressLocality'] ?? '',
                                            'state' => $addr['addressRegion'] ?? '',
                                            'zip_code' => $addr['postalCode'] ?? '',
                                            'country' => $addr['addressCountry'] ?? 'United States',
                                            'source' => 'structured_data'
                                        ];
                                        
                                        if (isset($loc['telephone'])) {
                                            $location['phone_number'] = $loc['telephone'];
                                        }
                                        
                                        if (!empty($location['address'])) {
                                            $locations_found[] = $location;
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // Invalid JSON, skip
                    }
                }
            }
        }
        
        // Method 2: Google Places API (if available)
        // Check if we have Google API key in config
        $google_api_key = $AICONFIG['google_maps_api_key'] ?? null;
        
        if ($google_api_key && count($locations_found) < 5) {
            // Search for company locations using Google Places Text Search
            $search_query = urlencode($company_name . ' locations');
            $google_url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query={$search_query}&key={$google_api_key}";
            
            $response = $system->curlRequest($google_url, [], [], 'GET', [
                'timeout' => 20,
                'connecttimeout' => 5
            ]);
            $google_response = $response['raw'] ?? '';
            $google_http_code = $response['http_code'] ?? 0;
            
            if ($google_http_code === 200) {
                $places_data = json_decode($google_response, true);
                
                if (isset($places_data['results']) && is_array($places_data['results'])) {
                    foreach ($places_data['results'] as $place) {
                        // Get place details for full address
                        if (isset($place['place_id'])) {
                            $details_url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$place['place_id']}&fields=formatted_address,formatted_phone_number,geometry,opening_hours&key={$google_api_key}";
                            
                            $details_resp = $system->curlRequest($details_url, [], [], 'GET', [
                                'timeout' => 15,
                                'connecttimeout' => 5
                            ]);
                            $details_response = $details_resp['raw'] ?? '';
                            
                            $details = json_decode($details_response, true);
                            if (isset($details['result'])) {
                                $place_detail = $details['result'];
                                
                                // Parse formatted address
                                $formatted_address = $place_detail['formatted_address'] ?? '';
                                $address_parts = explode(',', $formatted_address);
                                
                                if (count($address_parts) >= 3) {
                                    $location = [
                                        'address' => trim($address_parts[0]),
                                        'city' => trim($address_parts[1]),
                                        'source' => 'google_places'
                                    ];
                                    
                                    // Parse state and zip from last part
                                    $state_zip = trim($address_parts[2]);
                                    if (preg_match('/([A-Z]{2})\s+(\d{5})/', $state_zip, $sz_match)) {
                                        $location['state'] = $sz_match[1];
                                        $location['zip_code'] = $sz_match[2];
                                    }
                                    
                                    if (isset($place_detail['formatted_phone_number'])) {
                                        $location['phone_number'] = $place_detail['formatted_phone_number'];
                                    }
                                    
                                    if (isset($place_detail['geometry']['location'])) {
                                        $location['latitude'] = $place_detail['geometry']['location']['lat'];
                                        $location['longitude'] = $place_detail['geometry']['location']['lng'];
                                    }
                                    
                                    if (isset($place_detail['opening_hours']['weekday_text'])) {
                                        $location['business_hours'] = implode(', ', $place_detail['opening_hours']['weekday_text']);
                                    }
                                    
                                    $locations_found[] = $location;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Method 3: Google search for locations page if no locations found and no locations URL discovered
        if (empty($locations_found)) {
            // TODO: Consider using Airtop for complex location pages that require JavaScript rendering
            // or advanced parsing. Airtop can handle dynamic content and complex page structures
            // that our current regex-based approach might miss.
            // Check if we found a locations URL in Method 1
            $check_url_sql = "SELECT description FROM bg_company_attributes 
                             WHERE company_id = :company_id 
                             AND type = 'data_collection' 
                             AND name = 'locations_url' 
                             AND status = 'active'";
            $url_stmt = $database->query($check_url_sql, ['company_id' => $company_id]);
            $existing_locations_url = $url_stmt->fetchColumn();
            
            if (empty($existing_locations_url)) {
                // Try Google search to find locations page
                $domain = parse_url($company['company_url'], PHP_URL_HOST);
                
                // First try Google Custom Search API if we have credentials
                $google_cse_key = $configs['GOOGLE_CSE_API_KEY'] ?? '';
                $google_cse_cx = $configs['GOOGLE_CSE_CX'] ?? '';
                
                if (!empty($google_cse_key) && !empty($google_cse_cx)) {
                    // Search for location pages on this domain
                    $search_query = urlencode("site:{$domain} locations OR store locator OR find a store OR our locations");
                    $google_search_url = "https://www.googleapis.com/customsearch/v1?key={$google_cse_key}&cx={$google_cse_cx}&q={$search_query}&num=10";
                    
                    $search_resp = $system->curlRequest($google_search_url, [], [], 'GET', [
                        'timeout' => 20,
                        'connecttimeout' => 5
                    ]);
                    $search_response = $search_resp['raw'] ?? '';
                    $search_http_code = $search_resp['http_code'] ?? 0;
                    
                    if ($search_http_code === 200) {
                        $search_results = json_decode($search_response, true);
                        
                        if (isset($search_results['items']) && is_array($search_results['items'])) {
                            foreach ($search_results['items'] as $item) {
                                $found_url = $item['link'] ?? '';
                                
                                // Check if this URL looks like a locations page
                                if (preg_match('/\/(locations?|stores?|find-?(?:a-)?store|store-?locator|our-?locations?)/i', $found_url)) {
                                    // Verify the URL is accessible
                                    $headers = ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'];
                                    $locations_resp = $system->curlRequest($found_url, $headers, [], 'GET', [
                                        'timeout' => 30,
                                        'followlocation' => true,
                                        'maxredirs' => 5,
                                        'encoding' => 'gzip, deflate'
                                    ]);
                                    $locations_html = $locations_resp['raw'] ?? '';
                                    $http_code = $locations_resp['http_code'] ?? 0;
                                    
                                    if ($http_code === 200 && !empty($locations_html)) {
                                        // Save the discovered URL
                                        $attr_sql = "INSERT INTO bg_company_attributes 
                                                    (company_id, type, name, description, category, status, create_dt)
                                                    VALUES 
                                                    (:company_id, 'data_collection', 'locations_url_google_discovered', :url, 'location_discovery', 'active', NOW())";
                                        $database->query($attr_sql, [
                                            'company_id' => $company_id,
                                            'url' => $found_url
                                        ]);
                                        
                                        // Use AIRTOP to extract locations instead of regex parsing
                                        $airtopApiKey = $sitesettings_ai['airtop']['apikey'] ?? '';
                                        $airtopApiUrl = 'https://api.airtop.ai/api/v1/';
                                        
                                        if (!empty($airtopApiKey)) {
                                            session_tracking('abo_using_airtop_for_extraction', [
                                                'company_id' => $company_id,
                                                'url' => $found_url
                                            ]);
                                            
                                            try {
                                                // Create AIRTOP session
                                                $headers = [
                                                    'Content-Type: application/json',
                                                    'Authorization: Bearer ' . $airtopApiKey
                                                ];
                                                
                                                $sessionResponse = $system->curlRequest(
                                                    $airtopApiUrl . 'sessions',
                                                    $headers,
                                                    [],
                                                    'POST'
                                                );
                                                
                                                if (isset($sessionResponse['decoded']['data']['id'])) {
                                                    $sessionId = $sessionResponse['decoded']['data']['id'];
                                                    
                                                    // Wait for session to be ready
                                                    $sessionReady = false;
                                                    for ($i = 0; $i < 15; $i++) {
                                                        sleep(2);
                                                        $statusResponse = $system->curlRequest(
                                                            $airtopApiUrl . 'sessions/' . $sessionId,
                                                            $headers,
                                                            [],
                                                            'GET'
                                                        );
                                                        
                                                        if (isset($statusResponse['decoded']['data']['status']) && 
                                                            in_array($statusResponse['decoded']['data']['status'], ['active', 'ready', 'running'])) {
                                                            $sessionReady = true;
                                                            break;
                                                        }
                                                    }
                                                    
                                                    if ($sessionReady) {
                                                        // Create window with the discovered locations page
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
                                                            
                                                            // Extract locations with AIRTOP
                                                            $prompt = "Extract all store/business locations from this page. For each location, find the complete street address (with street number and name), city, state, ZIP code, phone number, and business hours. Return as structured JSON.";
                                                            
                                                            $jsonSchema = '{
                                                              "type": "object",
                                                              "properties": {
                                                                "locations": {
                                                                  "type": "array",
                                                                  "items": {
                                                                    "type": "object",
                                                                    "properties": {
                                                                      "name": {"type": "string"},
                                                                      "address": {"type": "string"},
                                                                      "city": {"type": "string"},
                                                                      "state": {"type": "string"},
                                                                      "zip_code": {"type": "string"},
                                                                      "phone": {"type": "string"},
                                                                      "hours": {"type": "string"}
                                                                    },
                                                                    "required": ["address", "city", "state"]
                                                                  }
                                                                }
                                                              },
                                                              "required": ["locations"]
                                                            }';
                                                            
                                                            // Send AI agent request
                                                            $agentResponse = $system->curlRequest(
                                                                $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId . '/ai/agent',
                                                                $headers,
                                                                [
                                                                    'prompt' => $prompt,
                                                                    'outputSchema' => json_decode($jsonSchema, true),
                                                                    'configuration' => [
                                                                        'maxCompletionTokens' => 16384,
                                                                        'temperature' => 0.1
                                                                    ]
                                                                ],
                                                                'POST'
                                                            );
                                                            
                                                            if (isset($agentResponse['decoded']['data']['modelResponse']['locations'])) {
                                                                $aiLocations = $agentResponse['decoded']['data']['modelResponse']['locations'];
                                                                
                                                                foreach ($aiLocations as $loc) {
                                                                    $location = [
                                                                        'name' => $loc['name'] ?? '',
                                                                        'address' => $loc['address'] ?? '',
                                                                        'city' => $loc['city'] ?? '',
                                                                        'state' => $loc['state'] ?? '',
                                                                        'zip_code' => $loc['zip_code'] ?? '',
                                                                        'phone_number' => $loc['phone'] ?? '',
                                                                        'business_hours' => $loc['hours'] ?? '',
                                                                        'source' => 'airtop_google_discovered: ' . $found_url
                                                                    ];
                                                                    
                                                                    // Only add if we have a proper street address
                                                                    if (!empty($location['address']) && 
                                                                        !empty($location['city']) &&
                                                                        !empty($location['state']) &&
                                                                        !preg_match('/P\.?O\.?\s*Box/i', $location['address']) &&
                                                                        preg_match('/\d+/', $location['address'])) {
                                                                        $locations_found[] = $location;
                                                                    }
                                                                }
                                                                
                                                                if ($debug) {
                                                                    $debug_output[] = [
                                                                        'action' => 'airtop_extracted_locations',
                                                                        'url' => $found_url,
                                                                        'count' => count($aiLocations),
                                                                        'sample' => array_slice($aiLocations, 0, 2)
                                                                    ];
                                                                }
                                                            }
                                                            
                                                            // Clean up - terminate session
                                                            $system->curlRequest(
                                                                $airtopApiUrl . 'sessions/' . $sessionId,
                                                                $headers,
                                                                [],
                                                                'DELETE'
                                                            );
                                                        }
                                                    }
                                                }
                                            } catch (Exception $e) {
                                                session_tracking('abo_airtop_error', [
                                                    'company_id' => $company_id,
                                                    'error' => $e->getMessage()
                                                ]);
                                            }
                                            
                                            // If AIRTOP extracted locations, skip the regex parsing
                                            if (!empty($locations_found)) {
                                                break;
                                            }
                                        }
                                        
                                        // Fallback to regex parsing if AIRTOP is not available or failed
                                        if (empty($locations_found)) {
                                            // Parse locations from the discovered page
                                            libxml_use_internal_errors(true);
                                            $dom = new DOMDocument();
                                            @$dom->loadHTML($locations_html);
                                            libxml_clear_errors();
                                            
                                            // Look for location containers (common patterns)
                                            $xpath = new DOMXPath($dom);
                                        
                                        // Try to find location blocks
                                        $location_blocks = [];
                                        
                                        // Common selectors for location containers
                                        $container_selectors = [
                                            '//div[contains(@class, "location")]',
                                            '//article[contains(@class, "location")]',
                                            '//div[contains(@class, "store")]',
                                            '//div[contains(@class, "theater")]',
                                            '//div[contains(@class, "branch")]'
                                        ];
                                        
                                        $container_search_results = [];
                                        foreach ($container_selectors as $selector) {
                                            $containers = $xpath->query($selector);
                                            $container_search_results[$selector] = $containers->length;
                                            if ($containers->length > 0) {
                                                foreach ($containers as $container) {
                                                    $location_blocks[] = $container;
                                                }
                                            }
                                        }
                                        
                                        // Track container search results
                                        session_tracking('abo_location_containers', [
                                            'url' => $found_url,
                                            'container_search_results' => $container_search_results,
                                            'total_containers_found' => count($location_blocks),
                                            'page_length' => strlen($locations_html),
                                            'company_id' => $company_id
                                        ]);
                                        
                                        // If no containers found, look for addresses directly
                                        if (empty($location_blocks)) {
                                            // Look for addresses in the entire page
                                            $full_address_pattern = '/(\d+\s+[A-Za-z\s]+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr|Way|Court|Ct|Plaza|Place|Pl)\.?(?:\s+(?:Suite|Ste|Unit|Apt|#)\s*\w+)?)[,\s]+([A-Za-z\s]+),?\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)/i';
                                            
                                            if (preg_match_all($full_address_pattern, $locations_html, $matches, PREG_SET_ORDER)) {
                                                foreach ($matches as $match) {
                                                    $location = [
                                                        'address' => trim($match[1]),
                                                        'city' => trim($match[2]),
                                                        'state' => trim($match[3]),
                                                        'zip_code' => trim($match[4]),
                                                        'source' => 'discovered_locations_page: ' . $found_url
                                                    ];
                                                    
                                                    // Try to find associated phone number (within reasonable proximity)
                                                    $context_start = max(0, strpos($locations_html, $match[0]) - 200);
                                                    $context_end = min(strlen($locations_html), strpos($locations_html, $match[0]) + strlen($match[0]) + 200);
                                                    $context = substr($locations_html, $context_start, $context_end - $context_start);
                                                    
                                                    // Phone pattern
                                                    if (preg_match('/\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $context, $phone_match)) {
                                                        $location['phone_number'] = preg_replace('/[^\d]/', '', $phone_match[0]);
                                                        $location['phone_number'] = substr($location['phone_number'], 0, 3) . '-' . substr($location['phone_number'], 3, 3) . '-' . substr($location['phone_number'], 6);
                                                    }
                                                    
                                                    // Validate it is not a PO Box
                                                    if (!preg_match('/P\.?O\.?\s*Box/i', $location['address'])) {
                                                        $locations_found[] = $location;
                                                    }
                                                }
                                            }
                                        } else {
                                            // Process each location container
                                            foreach ($location_blocks as $block_index => $block) {
                                                $block_html = $dom->saveHTML($block);
                                                
                                                // Initialize tracking array for this block
                                                $tracking_data = [
                                                    'block_index' => $block_index,
                                                    'raw_html' => substr($block_html, 0, 5000), // Limit size for tracking
                                                    'parsed_elements' => [],
                                                    'extraction_attempts' => []
                                                ];
                                                
                                                // Extract location name
                                                $location_name = '';
                                                if (preg_match('/<h[1-6][^>]*>([^<]+)<\/h[1-6]>/i', $block_html, $name_match)) {
                                                    $location_name = trim(strip_tags($name_match[1]));
                                                    $tracking_data['parsed_elements']['location_name'] = $location_name;
                                                } else {
                                                    $tracking_data['extraction_attempts'][] = 'No h1-h6 tags found for location name';
                                                }
                                                
                                                // Extract full address
                                                $full_address_pattern = '/(\d+\s+[A-Za-z\s]+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr|Way|Court|Ct|Plaza|Place|Pl)\.?(?:\s+(?:Suite|Ste|Unit|Apt|#)\s*\w+)?)[,\s]+([A-Za-z\s]+),?\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)/i';
                                                
                                                if (preg_match($full_address_pattern, $block_html, $addr_match)) {
                                                    $location = [
                                                        'name' => $location_name,
                                                        'address' => trim($addr_match[1]),
                                                        'city' => trim($addr_match[2]),
                                                        'state' => trim($addr_match[3]),
                                                        'zip_code' => trim($addr_match[4]),
                                                        'source' => 'discovered_locations_page: ' . $found_url
                                                    ];
                                                    
                                                    $tracking_data['parsed_elements']['address'] = $location['address'];
                                                    $tracking_data['parsed_elements']['city'] = $location['city'];
                                                    $tracking_data['parsed_elements']['state'] = $location['state'];
                                                    $tracking_data['parsed_elements']['zip_code'] = $location['zip_code'];
                                                    
                                                    // Extract phone number
                                                    if (preg_match('/\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $block_html, $phone_match)) {
                                                        $location['phone_number'] = preg_replace('/[^\d]/', '', $phone_match[0]);
                                                        $location['phone_number'] = substr($location['phone_number'], 0, 3) . '-' . substr($location['phone_number'], 3, 3) . '-' . substr($location['phone_number'], 6);
                                                        $tracking_data['parsed_elements']['phone_number'] = $location['phone_number'];
                                                    } else {
                                                        $tracking_data['extraction_attempts'][] = 'No phone number found with pattern';
                                                    }
                                                    
                                                    // Extract location-specific URL
                                                    if (preg_match('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(?:.*?(?:details|more|visit|view|location).*?)<\/a>/i', $block_html, $url_match)) {
                                                        $location_url = $url_match[1];
                                                        if (!filter_var($location_url, FILTER_VALIDATE_URL)) {
                                                            $parsed = parse_url($found_url);
                                                            if (substr($location_url, 0, 1) === '/') {
                                                                $location_url = $parsed['scheme'] . '://' . $parsed['host'] . $location_url;
                                                            } else {
                                                                $location_url = $parsed['scheme'] . '://' . $parsed['host'] . '/' . $location_url;
                                                            }
                                                        }
                                                        $location['location_url'] = $location_url;
                                                        $tracking_data['parsed_elements']['location_url'] = $location_url;
                                                    } else {
                                                        $tracking_data['extraction_attempts'][] = 'No location URL found with pattern';
                                                    }
                                                    
                                                    // Track this location parsing attempt
                                                    session_tracking('abo_location_parse', $tracking_data);
                                                    
                                                    // Validate it is not a PO Box
                                                    if (!preg_match('/P\.?O\.?\s*Box/i', $location['address'])) {
                                                        $locations_found[] = $location;
                                                    }
                                                } else {
                                                    // Track failed address extraction
                                                    $tracking_data['extraction_attempts'][] = 'Address pattern did not match';
                                                    $tracking_data['company_id'] = $company_id;
                                                    $tracking_data['url'] = $found_url;
                                                    session_tracking('abo_location_parse_failed', $tracking_data);
                                                }
                                            }
                                        }
                                        
                                        // If we found locations, stop searching
                                        if (!empty($locations_found)) {
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                        }
                    }
                }
                
                // Fallback: Try common URL patterns if Google Search did not work
                if (empty($locations_found)) {
                    // Try to find common location page URLs
                    $possible_urls = [
                        $company['company_url'] . '/locations',
                        $company['company_url'] . '/stores',
                        $company['company_url'] . '/store-locator',
                        $company['company_url'] . '/find-a-store',
                        $company['company_url'] . '/locations/all',
                        $company['company_url'] . '/our-locations',
                        str_replace('www.', 'locations.', $company['company_url'])
                    ];
                    
                    foreach ($possible_urls as $test_url) {
                        $headers = ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'];
                        $test_resp = $system->curlRequest($test_url, $headers, [], 'HEAD', [
                            'timeout' => 10,
                            'followlocation' => true,
                            'nobody' => true
                        ]);
                        $http_code = $test_resp['http_code'] ?? 0;
                        $final_url = $test_resp['effective_url'] ?? $test_url;
                        
                        if ($http_code === 200) {
                            // Found a valid locations page
                            $attr_sql = "INSERT INTO bg_company_attributes 
                                        (company_id, type, name, description, status, create_dt)
                                        VALUES 
                                        (:company_id, 'data_collection', 'locations_url_discovered', :url, 'active', NOW())";
                            $database->query($attr_sql, [
                                'company_id' => $company_id,
                                'url' => $final_url
                            ]);
                            
                            // Now scrape this page for locations
                            $headers = ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'];
                            $locations_resp = $system->curlRequest($final_url, $headers, [], 'GET', [
                                'timeout' => 30,
                                'followlocation' => true,
                                'encoding' => 'gzip, deflate'
                            ]);
                            $locations_html = $locations_resp['raw'] ?? '';
                            
                            if (!empty($locations_html)) {
                                // Look for addresses in the locations page
                                $address_patterns = [
                                    // US address pattern
                                    '/(\d+\s+[A-Za-z\s]+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr|Way|Court|Ct|Plaza|Place|Pl)\.?(?:\s+(?:Suite|Ste|Unit|Apt|#)\s*\w+)?),?\s*([A-Za-z\s]+),?\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)/i',
                                    // Simple city, state pattern
                                    '/([A-Za-z\s]+),\s*([A-Z]{2})\s+(\d{5})/i'
                                ];
                                
                                foreach ($address_patterns as $pattern) {
                                    if (preg_match_all($pattern, $locations_html, $matches, PREG_SET_ORDER)) {
                                        foreach ($matches as $match) {
                                            if (count($match) >= 4) {
                                                $location = [
                                                    'address' => trim($match[1]),
                                                    'city' => trim($match[count($match) - 3]),
                                                    'state' => trim($match[count($match) - 2]),
                                                    'zip_code' => trim($match[count($match) - 1]),
                                                    'source' => 'discovered_locations_page: ' . $final_url
                                                ];
                                                
                                                // Validate it is not a PO Box
                                                if (!preg_match('/P\.?O\.?\s*Box/i', $location['address'])) {
                                                    $locations_found[] = $location;
                                                }
                                            }
                                        }
                                
                                // Also check for structured data on the locations page
                                if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $locations_html, $jsonld_matches)) {
                                    foreach ($jsonld_matches[1] as $jsonld) {
                                        try {
                                            $data = json_decode($jsonld, true);
                                            // Process structured data similar to Method 1
                                            // ... (similar code to extract locations from JSON-LD)
                                        } catch (Exception $e) {
                                            // Ignore JSON parse errors
                                        }
                                    }
                                }
                            
                            // If we found locations, stop searching
                            if (!empty($locations_found)) {
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        // Deduplicate locations by address
        $unique_locations = [];
        $seen_addresses = [];
        
        foreach ($locations_found as $location) {
            $key = strtolower($location['address'] . '|' . $location['city'] . '|' . $location['state']);
            if (!isset($seen_addresses[$key])) {
                $seen_addresses[$key] = true;
                $unique_locations[] = $location;
            }
        }
        
        if ($debug) {
            $debug_output[] = [
                'action' => 'locations_found_summary',
                'total_locations_found' => count($locations_found),
                'unique_locations' => count($unique_locations),
                'sources_used' => array_unique(array_column($unique_locations, 'source')),
                'sample_locations' => array_slice($unique_locations, 0, 3)
            ];
        }
        
        // Track unique locations found
        session_tracking('abo_locations_summary', [
            'company_id' => $company_id,
            'company_name' => $company_name,
            'total_locations_found' => count($locations_found),
            'unique_locations' => count($unique_locations),
            'location_sources' => array_unique(array_column($unique_locations, 'source'))
        ]);
        
        // Save locations to database
        $locations_saved = 0;
        $locations_updated = 0;
        $locations_inserted = 0;
        foreach ($unique_locations as $location) {
            // Check if location already exists
            $check_sql = "SELECT location_id, zip_code, phone_number, latitude, longitude, 
                                business_hours, location_url, location_name, source 
                         FROM bg_company_locations 
                         WHERE company_id = :company_id 
                         AND address = :address 
                         AND city = :city 
                         AND state = :state";
            $check_params = [
                'company_id' => $company_id,
                'address' => $location['address'],
                'city' => $location['city'],
                'state' => $location['state'] ?? ''
            ];
            
            if ($debug) {
                $debug_output[] = [
                    'action' => 'check_existing_location',
                    'sql' => $check_sql,
                    'params' => $check_params,
                    'location_data' => $location
                ];
            }
            
            $check_stmt = $database->query($check_sql, $check_params);
            
            if ($check_stmt->rowCount() == 0) {
                // Insert new location
                $insert_sql = "INSERT INTO bg_company_locations 
                              (company_id, source, location_name, address, city, state, zip_code, country, 
                               phone_number, latitude, longitude, business_hours, location_url, is_verified, status)
                              VALUES 
                              (:company_id, :source, :location_name, :address, :city, :state, :zip_code, :country,
                               :phone_number, :latitude, :longitude, :business_hours, :location_url, 0, 'active')";
                
                $insert_params = [
                    'company_id' => $company_id,
                    'source' => $location['source'],
                    'location_name' => $location['name'] ?? null,
                    'address' => $location['address'],
                    'city' => $location['city'],
                    'state' => $location['state'] ?? null,
                    'zip_code' => $location['zip_code'] ?? null,
                    'country' => $location['country'] ?? 'United States',
                    'phone_number' => $location['phone_number'] ?? null,
                    'latitude' => $location['latitude'] ?? null,
                    'longitude' => $location['longitude'] ?? null,
                    'business_hours' => $location['business_hours'] ?? null,
                    'location_url' => $location['location_url'] ?? null
                ];
                
                $database->query($insert_sql, $insert_params);
                $locations_saved++;
                $locations_inserted++;
            } else {
                // Update existing location with additional details
                $existing_location = $check_stmt->fetch(PDO::FETCH_ASSOC);
                $location_id = $existing_location['location_id'];
                
                if ($debug) {
                    $debug_output[] = [
                        'action' => 'existing_location_found',
                        'location_id' => $location_id,
                        'existing_data' => $existing_location,
                        'new_data' => $location
                    ];
                }
                
                // Build update query for fields that might be missing
                $update_fields = [];
                $update_params = ['location_id' => $location_id];
                
                // Check each field and update if we have new data and existing is empty
                $field_checks = [];
                
                if (!empty($location['zip_code']) && empty($existing_location['zip_code'])) {
                    $update_fields[] = "zip_code = :zip_code";
                    $update_params['zip_code'] = $location['zip_code'];
                    $field_checks['zip_code'] = ['updated' => true, 'old' => $existing_location['zip_code'], 'new' => $location['zip_code']];
                } else {
                    $field_checks['zip_code'] = ['updated' => false, 'reason' => empty($location['zip_code']) ? 'new_empty' : 'existing_has_value', 'old' => $existing_location['zip_code'], 'new' => $location['zip_code'] ?? null];
                }
                
                if (!empty($location['phone_number']) && empty($existing_location['phone_number'])) {
                    $update_fields[] = "phone_number = :phone_number";
                    $update_params['phone_number'] = $location['phone_number'];
                    $field_checks['phone_number'] = ['updated' => true, 'old' => $existing_location['phone_number'], 'new' => $location['phone_number']];
                } else {
                    $field_checks['phone_number'] = ['updated' => false, 'reason' => empty($location['phone_number']) ? 'new_empty' : 'existing_has_value', 'old' => $existing_location['phone_number'], 'new' => $location['phone_number'] ?? null];
                }
                
                if (!empty($location['latitude']) && !empty($location['longitude']) && 
                    (empty($existing_location['latitude']) || empty($existing_location['longitude']))) {
                    $update_fields[] = "latitude = :latitude";
                    $update_fields[] = "longitude = :longitude";
                    $update_params['latitude'] = $location['latitude'];
                    $update_params['longitude'] = $location['longitude'];
                    $field_checks['coordinates'] = ['updated' => true, 'old_lat' => $existing_location['latitude'], 'old_lng' => $existing_location['longitude'], 'new_lat' => $location['latitude'], 'new_lng' => $location['longitude']];
                } else {
                    $field_checks['coordinates'] = ['updated' => false, 'reason' => (empty($location['latitude']) || empty($location['longitude'])) ? 'new_empty' : 'existing_has_value'];
                }
                
                if (!empty($location['business_hours']) && empty($existing_location['business_hours'])) {
                    $update_fields[] = "business_hours = :business_hours";
                    $update_params['business_hours'] = $location['business_hours'];
                    $field_checks['business_hours'] = ['updated' => true, 'old' => $existing_location['business_hours'], 'new' => $location['business_hours']];
                } else {
                    $field_checks['business_hours'] = ['updated' => false, 'reason' => empty($location['business_hours']) ? 'new_empty' : 'existing_has_value', 'old' => $existing_location['business_hours'], 'new' => $location['business_hours'] ?? null];
                }
                
                if (!empty($location['location_url']) && empty($existing_location['location_url'])) {
                    $update_fields[] = "location_url = :location_url";
                    $update_params['location_url'] = $location['location_url'];
                    $field_checks['location_url'] = ['updated' => true, 'old' => $existing_location['location_url'], 'new' => $location['location_url']];
                } else {
                    $field_checks['location_url'] = ['updated' => false, 'reason' => empty($location['location_url']) ? 'new_empty' : 'existing_has_value', 'old' => $existing_location['location_url'], 'new' => $location['location_url'] ?? null];
                }
                
                if (!empty($location['name']) && empty($existing_location['location_name'])) {
                    $update_fields[] = "location_name = :location_name";
                    $update_params['location_name'] = $location['name'];
                    $field_checks['location_name'] = ['updated' => true, 'old' => $existing_location['location_name'], 'new' => $location['name']];
                } else {
                    $field_checks['location_name'] = ['updated' => false, 'reason' => empty($location['name']) ? 'new_empty' : 'existing_has_value', 'old' => $existing_location['location_name'], 'new' => $location['name'] ?? null];
                }
                
                // Check if we have any real field updates (excluding source and timestamp)
                $real_updates = count($update_fields);
                
                // Always update source to track where latest data came from
                if (!isset($existing_location['source']) || strpos($existing_location['source'], $location['source']) === false) {
                    $update_fields[] = "source = CONCAT(IFNULL(source, ''), ', ', :new_source)";
                    $update_params['new_source'] = $location['source'];
                }
                
                // Only update timestamp if we have real field updates
                if ($real_updates > 0) {
                    $update_fields[] = "modify_dt = NOW()";
                }
                
                if (!empty($update_fields)) {
                    $update_sql = "UPDATE bg_company_locations SET " . implode(', ', $update_fields) . " WHERE location_id = :location_id";
                    
                    if ($debug) {
                        $debug_output[] = [
                            'action' => 'update_location',
                            'location_id' => $location_id,
                            'address' => $location['address'],
                            'field_checks' => $field_checks,
                            'update_fields' => $update_fields,
                            'update_sql' => $update_sql,
                            'update_params' => $update_params
                        ];
                    }
                    
                    $database->query($update_sql, $update_params);
                    $locations_saved++;
                    
                    // Only count as updated if we had real field updates
                    if ($real_updates > 0) {
                        $locations_updated++;
                    }
                    
                    session_tracking('abo_location_updated', [
                        'company_id' => $company_id,
                        'location_id' => $location_id,
                        'fields_updated' => array_keys($update_params),
                        'real_updates' => $real_updates,
                        'source' => $location['source']
                    ]);
                } else {
                    if ($debug) {
                        $debug_output[] = [
                            'action' => 'no_update_needed',
                            'location_id' => $location_id,
                            'address' => $location['address'],
                            'field_checks' => $field_checks,
                            'reason' => 'No fields needed updating'
                        ];
                    }
                }
            }
        }
        
        // Update status based on results
        if ($locations_saved > 0) {
            $status = 'completed';
            $result['successful'] = 1;
            
            // Build detailed message
            $message_parts = [];
            if ($locations_inserted > 0) {
                $message_parts[] = $locations_inserted . ' new locations added';
            }
            if ($locations_updated > 0) {
                $message_parts[] = $locations_updated . ' existing locations updated';
            }
            $result['data_collected'] = implode(', ', $message_parts);
            
            // Store summary as attribute
            $summary_sql = "INSERT INTO bg_company_attributes 
                           (company_id, type, name, description, status, create_dt)
                           VALUES 
                           (:company_id, 'data_collection', 'locations_summary', :summary, 'active', NOW())";
            $database->query($summary_sql, [
                'company_id' => $company_id,
                'summary' => json_encode([
                    'total_found' => count($locations_found),
                    'unique_locations' => count($unique_locations),
                    'new_inserted' => $locations_inserted,
                    'existing_updated' => $locations_updated,
                    'sources' => array_unique(array_column($unique_locations, 'source'))
                ])
            ]);
        } else if (count($locations_found) > 0) {
            // Found locations but they already existed and had no updates
            $status = 'completed';
            $result['successful'] = 1;
            $result['data_collected'] = 'All ' . count($unique_locations) . ' locations already in database with no new details';
        } else {
            // No locations found
            $status = 'attempted';
            $result['successful'] = 1;
            $result['data_collected'] = 'No locations found on website';
            
            // Log the attempt
            $attr_sql = "INSERT INTO bg_company_attributes 
                        (company_id, type, name, description, status, create_dt)
                        VALUES 
                        (:company_id, 'data_collection', 'locations_search', 'searched_none_found', 'active', NOW())";
            $database->query($attr_sql, ['company_id' => $company_id]);
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
        
        $result['successful'] = 1;
        
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