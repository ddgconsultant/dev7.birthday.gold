<?php
// abo_grablocations.php - Extract store locations and addresses
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
    'processor' => 'abo_grablocations',
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => []
];

try {
    // Get companies to process
    if ($specific_company_id) {
        $sql = "SELECT c.* FROM bg_companies c 
                INNER JOIN bg_company_attributes ca ON c.company_id = ca.company_id
                WHERE c.company_id = :company_id 
                AND ca.type = 'onboarding_progress'
                AND ca.name = 'abo_grablocations'
                AND ca.description IN ('pending', 'error', 'attempted')
                LIMIT 1";
        $params = ['company_id' => $specific_company_id];
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
        $ch = curl_init($company['company_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && !empty($html)) {
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
            
            $ch = curl_init($google_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            $google_response = curl_exec($ch);
            $google_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($google_http_code === 200) {
                $places_data = json_decode($google_response, true);
                
                if (isset($places_data['results']) && is_array($places_data['results'])) {
                    foreach ($places_data['results'] as $place) {
                        // Get place details for full address
                        if (isset($place['place_id'])) {
                            $details_url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$place['place_id']}&fields=formatted_address,formatted_phone_number,geometry,opening_hours&key={$google_api_key}";
                            
                            $ch = curl_init($details_url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                            $details_response = curl_exec($ch);
                            curl_close($ch);
                            
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
                    
                    $ch = curl_init($google_search_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $search_response = curl_exec($ch);
                    $search_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($search_http_code === 200) {
                        $search_results = json_decode($search_response, true);
                        
                        if (isset($search_results['items']) && is_array($search_results['items'])) {
                            foreach ($search_results['items'] as $item) {
                                $found_url = $item['link'] ?? '';
                                
                                // Check if this URL looks like a locations page
                                if (preg_match('/\/(locations?|stores?|find-?(?:a-)?store|store-?locator|our-?locations?)/i', $found_url)) {
                                    // Verify the URL is accessible
                                    $ch = curl_init($found_url);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                                    
                                    $locations_html = curl_exec($ch);
                                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                    curl_close($ch);
                                    
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
                        $ch = curl_init($test_url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
                        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                        
                        curl_exec($ch);
                        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        $final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                        curl_close($ch);
                        
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
                            $ch = curl_init($final_url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                            
                            $locations_html = curl_exec($ch);
                            curl_close($ch);
                            
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
        foreach ($unique_locations as $location) {
            // Check if location already exists
            $check_sql = "SELECT location_id FROM bg_company_locations 
                         WHERE company_id = :company_id 
                         AND address = :address 
                         AND city = :city 
                         AND state = :state";
            $check_stmt = $database->query($check_sql, [
                'company_id' => $company_id,
                'address' => $location['address'],
                'city' => $location['city'],
                'state' => $location['state'] ?? ''
            ]);
            
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
            }
        }
        
        // Update status based on results
        if ($locations_saved > 0) {
            $status = 'completed';
            $result['successful'] = 1;
            $result['data_collected'] = $locations_saved . ' locations saved';
            
            // Store summary as attribute
            $summary_sql = "INSERT INTO bg_company_attributes 
                           (company_id, type, name, description, status, create_dt)
                           VALUES 
                           (:company_id, 'data_collection', 'locations_summary', :summary, 'active', NOW())";
            $database->query($summary_sql, [
                'company_id' => $company_id,
                'summary' => json_encode([
                    'total_found' => count($locations_found),
                    'unique_saved' => $locations_saved,
                    'sources' => array_unique(array_column($unique_locations, 'source'))
                ])
            ]);
        } else if (count($locations_found) > 0) {
            // Found locations but they already existed
            $status = 'completed';
            $result['successful'] = 1;
            $result['data_collected'] = 'All ' . count($unique_locations) . ' locations already in database';
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
            'saved' => $locations_saved
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

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);