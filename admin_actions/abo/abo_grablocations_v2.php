<?php
// abo_grablocations_v2.php - Extract store locations and addresses (REFACTORED)
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
    'processor' => 'abo_grablocations_v2',
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => [],
    'locations_inserted' => 0,
    'locations_updated' => 0,
    'locations_skipped' => 0
];

// Track start of process
session_tracking('abo_grablocations_start', [
    'specific_company_id' => $specific_company_id,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
]);

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
        session_tracking('abo_grablocations_no_companies', $result);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    $result['processed'] = 1;
    $company_id = $company['company_id'];
    $company_name = $company['company_name'];
    
    session_tracking('abo_grablocations_company_found', [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'company_url' => $company['company_url'],
        'status' => $company['status']
    ]);
    
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
        $all_methods_tried = [];
        
        // Method 1: Scrape website for location information
        session_tracking('abo_grablocations_method1_start', [
            'company_id' => $company_id,
            'method' => 'website_scrape',
            'url' => $company['company_url']
        ]);
        
        $html = fetchWebPage($company['company_url'], $company_id, 'homepage');
        
        if (!empty($html)) {
            // Look for location page links
            $location_urls = findLocationPageUrls($html, $company['company_url'], $company_id);
            
            if (!empty($location_urls)) {
                session_tracking('abo_grablocations_location_urls_found', [
                    'company_id' => $company_id,
                    'urls_found' => $location_urls,
                    'count' => count($location_urls)
                ]);
                
                // Store discovered URLs for future use
                foreach ($location_urls as $loc_url) {
                    $attr_sql = "INSERT INTO bg_company_attributes 
                                (company_id, type, name, description, status, create_dt)
                                VALUES 
                                (:company_id, 'data_collection', 'locations_url', :url, 'active', NOW())
                                ON DUPLICATE KEY UPDATE description = VALUES(description), modify_dt = NOW()";
                    $database->query($attr_sql, [
                        'company_id' => $company_id,
                        'url' => $loc_url
                    ]);
                }
            }
            
            // Extract locations from homepage
            $homepage_locations = extractLocationsFromHtml($html, $company['company_url'], $company_id, 'homepage');
            $locations_found = array_merge($locations_found, $homepage_locations);
            
            // Look for structured data (JSON-LD)
            $structured_locations = extractStructuredDataLocations($html, $company_id);
            $locations_found = array_merge($locations_found, $structured_locations);
        }
        
        $all_methods_tried[] = 'homepage_scrape';
        
        // Method 2: Google Places API (if available and no locations found yet)
        if (empty($locations_found)) {
            session_tracking('abo_grablocations_method2_start', [
                'company_id' => $company_id,
                'method' => 'google_places_api',
                'reason' => 'no_locations_from_homepage'
            ]);
            
            $google_api_key = $configs['GOOGLE_PLACES_API_KEY'] ?? '';
            if (!empty($google_api_key)) {
                $google_locations = fetchGooglePlacesLocations($company_name, $google_api_key, $company_id);
                $locations_found = array_merge($locations_found, $google_locations);
                $all_methods_tried[] = 'google_places_api';
            } else {
                session_tracking('abo_grablocations_method2_skipped', [
                    'company_id' => $company_id,
                    'reason' => 'no_api_key'
                ]);
            }
        }
        
        // Method 3: Google search for locations page if no locations found
        if (empty($locations_found)) {
            session_tracking('abo_grablocations_method3_start', [
                'company_id' => $company_id,
                'method' => 'google_search_discovery',
                'reason' => 'no_locations_from_previous_methods'
            ]);
            
            $discovered_locations = discoverAndScrapeLocationPages($company, $company_id);
            $locations_found = array_merge($locations_found, $discovered_locations);
            $all_methods_tried[] = 'location_page_discovery';
        }
        
        // Deduplicate locations
        $unique_locations = deduplicateLocations($locations_found, $company_id);
        
        // Save locations to database with proper insert/update logic
        $save_results = saveLocationsToDatabase($unique_locations, $company_id, $database);
        
        $result['locations_inserted'] = $save_results['inserted'];
        $result['locations_updated'] = $save_results['updated'];
        $result['locations_skipped'] = $save_results['skipped'];
        $result['successful'] = $save_results['total_saved'];
        
        // Update progress status
        $final_status = $result['successful'] > 0 ? 'completed' : 'attempted';
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = :status, modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grablocations'";
        $database->query($progress_sql, [
            'company_id' => $company_id,
            'status' => $final_status
        ]);
        
        $database->commit();
        
        // Final tracking
        session_tracking('abo_grablocations_complete', [
            'company_id' => $company_id,
            'company_name' => $company_name,
            'methods_tried' => $all_methods_tried,
            'total_found' => count($locations_found),
            'unique_locations' => count($unique_locations),
            'inserted' => $result['locations_inserted'],
            'updated' => $result['locations_updated'],
            'skipped' => $result['locations_skipped'],
            'final_status' => $final_status
        ]);
        
    } catch (Exception $e) {
        $database->rollback();
        
        // Update progress to error
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = 'error', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grablocations'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        $result['status'] = 'error';
        $result['errors'][] = $e->getMessage();
        $result['failed'] = 1;
        
        session_tracking('abo_grablocations_error', [
            'company_id' => $company_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    
    session_tracking('abo_grablocations_fatal_error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// Output results
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);

// ===== HELPER FUNCTIONS =====

function fetchWebPage($url, $company_id, $context) {
    global $system;
    
    session_tracking('abo_fetch_webpage_start', [
        'company_id' => $company_id,
        'url' => $url,
        'context' => $context
    ]);
    
    // Use system curl with proper error handling
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    session_tracking('abo_fetch_webpage_result', [
        'company_id' => $company_id,
        'url' => $url,
        'http_code' => $httpCode,
        'final_url' => $finalUrl,
        'html_length' => strlen($html),
        'error' => $error,
        'context' => $context
    ]);
    
    return $httpCode === 200 ? $html : '';
}

function findLocationPageUrls($html, $base_url, $company_id) {
    $location_urls = [];
    
    // Patterns to find location page links
    $patterns = [
        '/<a[^>]+href=["\']([^"\']*(?:locations?|stores?|find-?us|store-?locator|find-?a-?store|our-?locations?)[^"\']*)["\'][^>]*>/i',
        '/<a[^>]+(?:locations?|stores?|find)[^>]+href=["\']([^"\']+)["\'][^>]*>/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $url) {
                $absolute_url = makeAbsoluteUrl($url, $base_url);
                if (!in_array($absolute_url, $location_urls)) {
                    $location_urls[] = $absolute_url;
                }
            }
        }
    }
    
    session_tracking('abo_location_urls_search', [
        'company_id' => $company_id,
        'base_url' => $base_url,
        'urls_found' => count($location_urls),
        'urls' => array_slice($location_urls, 0, 5) // Log first 5
    ]);
    
    return $location_urls;
}

function makeAbsoluteUrl($url, $base_url) {
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }
    
    $parsed = parse_url($base_url);
    
    if (substr($url, 0, 2) === '//') {
        return $parsed['scheme'] . ':' . $url;
    } elseif (substr($url, 0, 1) === '/') {
        return $parsed['scheme'] . '://' . $parsed['host'] . $url;
    } else {
        $path = isset($parsed['path']) ? dirname($parsed['path']) : '';
        if ($path === '/' || $path === '.') $path = '';
        return $parsed['scheme'] . '://' . $parsed['host'] . $path . '/' . $url;
    }
}

function extractLocationsFromHtml($html, $source_url, $company_id, $context) {
    $locations = [];
    
    session_tracking('abo_extract_locations_start', [
        'company_id' => $company_id,
        'source_url' => $source_url,
        'context' => $context,
        'html_length' => strlen($html)
    ]);
    
    // US address pattern
    $address_pattern = '/(\d+\s+[A-Za-z\s]+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr|Way|Court|Ct|Plaza|Place|Pl|Parkway|Pkwy)\.?(?:\s+(?:Suite|Ste|Unit|Apt|#)\s*\w+)?)[,\s]+([A-Za-z\s]+),?\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)/i';
    
    if (preg_match_all($address_pattern, $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $location = [
                'address' => trim($match[1]),
                'city' => trim($match[2]),
                'state' => trim($match[3]),
                'zip_code' => trim($match[4]),
                'source' => "scraped_page: $source_url"
            ];
            
            // Try to find phone number near this address
            $context_start = max(0, strpos($html, $match[0]) - 300);
            $context_end = min(strlen($html), strpos($html, $match[0]) + strlen($match[0]) + 300);
            $context_html = substr($html, $context_start, $context_end - $context_start);
            
            if (preg_match('/\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $context_html, $phone_match)) {
                $phone = preg_replace('/[^\d]/', '', $phone_match[0]);
                if (strlen($phone) === 10) {
                    $location['phone_number'] = substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
                }
            }
            
            // Skip PO Boxes
            if (!preg_match('/P\.?O\.?\s*Box/i', $location['address'])) {
                $locations[] = $location;
                
                session_tracking('abo_location_extracted', [
                    'company_id' => $company_id,
                    'context' => $context,
                    'location' => $location
                ]);
            }
        }
    }
    
    session_tracking('abo_extract_locations_complete', [
        'company_id' => $company_id,
        'context' => $context,
        'locations_found' => count($locations)
    ]);
    
    return $locations;
}

function extractStructuredDataLocations($html, $company_id) {
    $locations = [];
    
    if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $jsonld_matches)) {
        foreach ($jsonld_matches[1] as $jsonld) {
            try {
                $data = json_decode($jsonld, true);
                if ($data && isset($data['@type'])) {
                    // Process single location
                    if (in_array($data['@type'], ['LocalBusiness', 'Store', 'Restaurant']) && isset($data['address'])) {
                        $location = parseStructuredAddress($data);
                        if ($location) {
                            $location['source'] = 'structured_data';
                            $locations[] = $location;
                        }
                    }
                    
                    // Process organization with locations
                    if ($data['@type'] === 'Organization' && isset($data['location'])) {
                        if (is_array($data['location'])) {
                            foreach ($data['location'] as $loc) {
                                $location = parseStructuredAddress($loc);
                                if ($location) {
                                    $location['source'] = 'structured_data';
                                    $locations[] = $location;
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                session_tracking('abo_structured_data_error', [
                    'company_id' => $company_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    session_tracking('abo_structured_data_complete', [
        'company_id' => $company_id,
        'locations_found' => count($locations)
    ]);
    
    return $locations;
}

function parseStructuredAddress($data) {
    if (!isset($data['address'])) return null;
    
    $addr = $data['address'];
    if (!is_array($addr)) return null;
    
    $location = [
        'address' => $addr['streetAddress'] ?? '',
        'city' => $addr['addressLocality'] ?? '',
        'state' => $addr['addressRegion'] ?? '',
        'zip_code' => $addr['postalCode'] ?? '',
        'country' => $addr['addressCountry'] ?? 'United States'
    ];
    
    if (empty($location['address'])) return null;
    
    // Add optional fields
    if (isset($data['name'])) {
        $location['name'] = $data['name'];
    }
    
    if (isset($data['telephone'])) {
        $location['phone_number'] = $data['telephone'];
    }
    
    if (isset($data['geo'])) {
        $location['latitude'] = $data['geo']['latitude'] ?? null;
        $location['longitude'] = $data['geo']['longitude'] ?? null;
    }
    
    return $location;
}

function fetchGooglePlacesLocations($company_name, $api_key, $company_id) {
    $locations = [];
    
    // URL encode the company name
    $query = urlencode($company_name);
    $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query={$query}&key={$api_key}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        
        if (isset($data['results']) && is_array($data['results'])) {
            foreach ($data['results'] as $place) {
                if (isset($place['place_id'])) {
                    // Get detailed place information
                    $details = fetchGooglePlaceDetails($place['place_id'], $api_key);
                    if ($details) {
                        $details['source'] = 'google_places_api';
                        $locations[] = $details;
                    }
                }
            }
        }
    }
    
    session_tracking('abo_google_places_complete', [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'locations_found' => count($locations)
    ]);
    
    return $locations;
}

function fetchGooglePlaceDetails($place_id, $api_key) {
    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$place_id}&fields=formatted_address,formatted_phone_number,geometry,opening_hours,name&key={$api_key}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    if (!isset($data['result'])) return null;
    
    $result = $data['result'];
    $location = [];
    
    // Parse formatted address
    if (isset($result['formatted_address'])) {
        $parts = explode(',', $result['formatted_address']);
        if (count($parts) >= 3) {
            $location['address'] = trim($parts[0]);
            $location['city'] = trim($parts[1]);
            
            // Parse state and zip
            $state_zip = trim($parts[2]);
            if (preg_match('/([A-Z]{2})\s+(\d{5})/', $state_zip, $match)) {
                $location['state'] = $match[1];
                $location['zip_code'] = $match[2];
            }
        }
    }
    
    if (isset($result['name'])) {
        $location['name'] = $result['name'];
    }
    
    if (isset($result['formatted_phone_number'])) {
        $location['phone_number'] = $result['formatted_phone_number'];
    }
    
    if (isset($result['geometry']['location'])) {
        $location['latitude'] = $result['geometry']['location']['lat'];
        $location['longitude'] = $result['geometry']['location']['lng'];
    }
    
    return empty($location['address']) ? null : $location;
}

function discoverAndScrapeLocationPages($company, $company_id) {
    $locations = [];
    $domain = parse_url($company['company_url'], PHP_URL_HOST);
    
    // Common location page URLs to try
    $urls_to_try = [
        $company['company_url'] . '/locations',
        $company['company_url'] . '/stores',
        $company['company_url'] . '/store-locator',
        $company['company_url'] . '/find-a-store',
        $company['company_url'] . '/our-locations',
        $company['company_url'] . '/locations/all',
        str_replace('www.', 'locations.', $company['company_url'])
    ];
    
    session_tracking('abo_discovery_start', [
        'company_id' => $company_id,
        'urls_to_try' => count($urls_to_try)
    ]);
    
    foreach ($urls_to_try as $url) {
        // Quick HEAD request to check if URL exists
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            session_tracking('abo_discovery_url_found', [
                'company_id' => $company_id,
                'url' => $url,
                'http_code' => $httpCode
            ]);
            
            // Fetch and parse the page
            $html = fetchWebPage($url, $company_id, 'discovered_location_page');
            if ($html) {
                $page_locations = extractLocationsFromHtml($html, $url, $company_id, 'discovered_page');
                
                // Update source to include discovered URL
                foreach ($page_locations as &$loc) {
                    $loc['source'] = 'discovered_locations_page: ' . $url;
                }
                
                $locations = array_merge($locations, $page_locations);
                
                // If we found locations, stop trying other URLs
                if (!empty($page_locations)) {
                    break;
                }
            }
        }
    }
    
    session_tracking('abo_discovery_complete', [
        'company_id' => $company_id,
        'locations_found' => count($locations)
    ]);
    
    return $locations;
}

function deduplicateLocations($locations, $company_id) {
    $unique = [];
    $seen = [];
    
    foreach ($locations as $location) {
        // Create a unique key based on address
        $key = strtolower(trim($location['address']) . '|' . trim($location['city']) . '|' . trim($location['state'] ?? ''));
        
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $unique[] = $location;
        }
    }
    
    session_tracking('abo_deduplication', [
        'company_id' => $company_id,
        'total_locations' => count($locations),
        'unique_locations' => count($unique),
        'duplicates_removed' => count($locations) - count($unique)
    ]);
    
    return $unique;
}

function saveLocationsToDatabase($locations, $company_id, $database) {
    $results = [
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'total_saved' => 0
    ];
    
    foreach ($locations as $location) {
        // Check if location exists
        $check_sql = "SELECT location_id, source, phone_number, location_url 
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
        
        $check_stmt = $database->query($check_sql, $check_params);
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing location if we have new data
            $updates = [];
            $update_params = ['location_id' => $existing['location_id']];
            
            // Check what needs updating
            if (!empty($location['phone_number']) && $existing['phone_number'] != $location['phone_number']) {
                $updates[] = 'phone_number = :phone_number';
                $update_params['phone_number'] = $location['phone_number'];
            }
            
            if (!empty($location['location_url']) && $existing['location_url'] != $location['location_url']) {
                $updates[] = 'location_url = :location_url';
                $update_params['location_url'] = $location['location_url'];
            }
            
            if (!empty($location['name']) && empty($existing['location_name'])) {
                $updates[] = 'location_name = :location_name';
                $update_params['location_name'] = $location['name'];
            }
            
            // Always update source to track where latest data came from
            $updates[] = 'source = :source';
            $update_params['source'] = $location['source'];
            
            if (!empty($updates)) {
                $update_sql = "UPDATE bg_company_locations SET " . implode(', ', $updates) . ", modify_dt = NOW() WHERE location_id = :location_id";
                $database->query($update_sql, $update_params);
                $results['updated']++;
                
                session_tracking('abo_location_updated', [
                    'company_id' => $company_id,
                    'location_id' => $existing['location_id'],
                    'updates' => $updates,
                    'location' => $location
                ]);
            } else {
                $results['skipped']++;
            }
        } else {
            // Insert new location
            $insert_sql = "INSERT INTO bg_company_locations 
                          (company_id, source, location_name, address, city, state, zip_code, country, 
                           phone_number, latitude, longitude, business_hours, location_url, is_verified, status, create_dt)
                          VALUES 
                          (:company_id, :source, :location_name, :address, :city, :state, :zip_code, :country,
                           :phone_number, :latitude, :longitude, :business_hours, :location_url, 0, 'active', NOW())";
            
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
            $results['inserted']++;
            
            session_tracking('abo_location_inserted', [
                'company_id' => $company_id,
                'location' => $location
            ]);
        }
    }
    
    $results['total_saved'] = $results['inserted'] + $results['updated'];
    
    session_tracking('abo_save_complete', [
        'company_id' => $company_id,
        'results' => $results
    ]);
    
    return $results;
}