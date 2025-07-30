<?php
// abo_grablocations_v3.php - Extract store locations and addresses (FINAL REFACTOR)
// Part of the Automation Business Onboarding (ABO) system
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Initialize result tracking
$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processor' => 'abo_grablocations_v3',
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => [],
    'actions' => []
];

// Get company ID - support both encoded and raw
$specific_company_id = null;
if (isset($_GET['rawid'])) {
    $specific_company_id = intval($_GET['rawid']);
} elseif (isset($_GET['id'])) {
    $specific_company_id = $qik->decodeID($_GET['id']);
}

// Track the start of this process
session_tracking('abo_grablocations_start', [
    'specific_company_id' => $specific_company_id,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
]);

try {
    // Build query to get company
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
        session_tracking('abo_grablocations_no_companies', ['result' => $result]);
        outputResult($result);
    }
    
    $result['processed'] = 1;
    $company_id = $company['company_id'];
    $company_name = $company['company_name'];
    
    // Track company found
    session_tracking('abo_grablocations_company_found', [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'company_url' => $company['company_url']
    ]);
    
    // Begin transaction
    $database->beginTransaction();
    
    try {
        // Update progress to in_progress
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = 'in_progress', modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grablocations'";
        $database->query($progress_sql, ['company_id' => $company_id]);
        
        // Initialize location collection
        $all_locations = [];
        $methods_used = [];
        
        // Method 1: Scrape homepage for locations
        session_tracking('abo_method1_start', [
            'company_id' => $company_id,
            'url' => $company['company_url']
        ]);
        
        $homepage_html = fetchPage($company['company_url'], $company_id, 'homepage');
        
        if ($homepage_html) {
            // Extract locations from homepage
            $homepage_locations = extractLocationsFromHtml($homepage_html, $company['company_url'], $company_id, 'homepage');
            if (!empty($homepage_locations)) {
                $all_locations = array_merge($all_locations, $homepage_locations);
                $methods_used[] = 'homepage_direct';
                $result['actions'][] = sprintf("Found %d locations on homepage", count($homepage_locations));
            }
            
            // Look for location page links
            $location_urls = findLocationPageUrls($homepage_html, $company['company_url'], $company_id);
            
            if (!empty($location_urls)) {
                // Store discovered URLs
                foreach ($location_urls as $url) {
                    storeDiscoveredUrl($database, $company_id, $url);
                }
                
                // Scrape location pages
                foreach ($location_urls as $url) {
                    $location_html = fetchPage($url, $company_id, 'linked_location_page');
                    if ($location_html) {
                        $page_locations = extractLocationsFromHtml($location_html, $url, $company_id, 'location_page');
                        if (!empty($page_locations)) {
                            foreach ($page_locations as &$loc) {
                                $loc['source'] = 'scraped_locations_page: ' . $url;
                            }
                            $all_locations = array_merge($all_locations, $page_locations);
                            $methods_used[] = 'linked_location_pages';
                            $result['actions'][] = sprintf("Found %d locations on %s", count($page_locations), $url);
                        }
                    }
                }
            }
            
            // Check for structured data
            $structured_locations = extractStructuredData($homepage_html, $company_id);
            if (!empty($structured_locations)) {
                $all_locations = array_merge($all_locations, $structured_locations);
                $methods_used[] = 'structured_data';
                $result['actions'][] = sprintf("Found %d locations in structured data", count($structured_locations));
            }
        } else {
            session_tracking('abo_homepage_fetch_failed', [
                'company_id' => $company_id,
                'url' => $company['company_url']
            ]);
        }
        
        // Method 2: Google Places API (if available and needed)
        if (empty($all_locations) && !empty($configs['GOOGLE_PLACES_API_KEY'])) {
            session_tracking('abo_method2_start', [
                'company_id' => $company_id,
                'reason' => 'no_locations_from_homepage'
            ]);
            
            $google_locations = fetchGooglePlacesLocations($company_name, $configs['GOOGLE_PLACES_API_KEY'], $company_id);
            if (!empty($google_locations)) {
                $all_locations = array_merge($all_locations, $google_locations);
                $methods_used[] = 'google_places_api';
                $result['actions'][] = sprintf("Found %d locations via Google Places API", count($google_locations));
            }
        }
        
        // Method 3: Google Search for location pages (if still no locations)
        if (empty($all_locations) && !empty($configs['GOOGLE_CSE_KEY']) && !empty($configs['GOOGLE_CSE_CX'])) {
            session_tracking('abo_method3_start', [
                'company_id' => $company_id,
                'reason' => 'no_locations_from_previous_methods'
            ]);
            
            $discovered_url = discoverLocationPageViaGoogle($company, $configs['GOOGLE_CSE_KEY'], $configs['GOOGLE_CSE_CX'], $company_id);
            
            if ($discovered_url) {
                // Store discovered URL
                storeDiscoveredUrl($database, $company_id, $discovered_url);
                
                // Scrape the discovered page
                $discovered_html = fetchPage($discovered_url, $company_id, 'google_discovered_page');
                if ($discovered_html) {
                    $discovered_locations = extractLocationsFromHtml($discovered_html, $discovered_url, $company_id, 'discovered_page');
                    if (!empty($discovered_locations)) {
                        foreach ($discovered_locations as &$loc) {
                            $loc['source'] = 'discovered_locations_page: ' . $discovered_url;
                        }
                        $all_locations = array_merge($all_locations, $discovered_locations);
                        $methods_used[] = 'google_search_discovery';
                        $result['actions'][] = sprintf("Found %d locations on discovered page: %s", count($discovered_locations), $discovered_url);
                    }
                }
            }
        }
        
        // Method 4: Try common URL patterns as last resort
        if (empty($all_locations)) {
            session_tracking('abo_method4_start', [
                'company_id' => $company_id,
                'reason' => 'no_locations_from_any_method'
            ]);
            
            $pattern_locations = tryCommonLocationUrls($company, $company_id, $database);
            if (!empty($pattern_locations)) {
                $all_locations = array_merge($all_locations, $pattern_locations);
                $methods_used[] = 'url_pattern_discovery';
                $result['actions'][] = sprintf("Found %d locations via URL patterns", count($pattern_locations));
            }
        }
        
        // Deduplicate locations
        $unique_locations = deduplicateLocations($all_locations, $company_id);
        
        // Save locations to database
        $save_results = saveLocations($unique_locations, $company_id, $database);
        
        // Update result
        $result['successful'] = $save_results['total_saved'];
        $result['actions'][] = sprintf("Inserted: %d, Updated: %d, Skipped: %d locations", 
            $save_results['inserted'], 
            $save_results['updated'], 
            $save_results['skipped']
        );
        
        // Determine final status
        $final_status = $result['successful'] > 0 ? 'completed' : 'attempted';
        
        // Update progress
        $progress_sql = "UPDATE bg_company_attributes 
                        SET description = :status, modify_dt = NOW() 
                        WHERE company_id = :company_id 
                        AND type = 'onboarding_progress' 
                        AND name = 'abo_grablocations'";
        $database->query($progress_sql, [
            'company_id' => $company_id,
            'status' => $final_status
        ]);
        
        // Commit transaction
        $database->commit();
        
        // Final tracking
        session_tracking('abo_grablocations_complete', [
            'company_id' => $company_id,
            'company_name' => $company_name,
            'methods_used' => $methods_used,
            'total_found' => count($all_locations),
            'unique_locations' => count($unique_locations),
            'saved' => $save_results,
            'final_status' => $final_status
        ]);
        
    } catch (Exception $e) {
        $database->rollback();
        
        // Update to error status
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

// Output result
outputResult($result);

// ===== HELPER FUNCTIONS =====

function outputResult($result) {
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

function fetchPage($url, $company_id, $context) {
    session_tracking('abo_fetch_page', [
        'company_id' => $company_id,
        'url' => $url,
        'context' => $context
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    session_tracking('abo_fetch_result', [
        'company_id' => $company_id,
        'context' => $context,
        'http_code' => $httpCode,
        'final_url' => $finalUrl,
        'html_length' => strlen($html),
        'success' => $httpCode === 200
    ]);
    
    return $httpCode === 200 ? $html : '';
}

function findLocationPageUrls($html, $base_url, $company_id) {
    $urls = [];
    
    // Patterns to find location pages
    $patterns = [
        '/<a[^>]+href=[\"\']([^\"\']*)[\"\'][^>]*>(?:[^<]*(?:locations?|stores?|find))[^<]*<\/a>/i',
        '/<a[^>]+href=[\"\']([^\"\']*(?:locations?|stores?|find-?us|store-?locator|find-?a-?store|our-?locations?)[^\"\']*)[\"\'][^>]*>/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $url) {
                $absolute_url = makeAbsoluteUrl($url, $base_url);
                if (!in_array($absolute_url, $urls) && isValidLocationUrl($absolute_url, $base_url)) {
                    $urls[] = $absolute_url;
                }
            }
        }
    }
    
    session_tracking('abo_location_urls_found', [
        'company_id' => $company_id,
        'base_url' => $base_url,
        'urls_found' => count($urls),
        'urls' => array_slice($urls, 0, 5)
    ]);
    
    return $urls;
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

function isValidLocationUrl($url, $base_url) {
    $parsed_url = parse_url($url);
    $parsed_base = parse_url($base_url);
    
    // Must be same domain
    if ($parsed_url['host'] !== $parsed_base['host']) {
        return false;
    }
    
    // Avoid certain patterns
    $avoid_patterns = ['/careers', '/jobs', '/contact', '/about', '/privacy', '/terms', '/login', '/signup'];
    foreach ($avoid_patterns as $pattern) {
        if (stripos($url, $pattern) !== false) {
            return false;
        }
    }
    
    return true;
}

function extractLocationsFromHtml($html, $source_url, $company_id, $context) {
    $locations = [];
    
    session_tracking('abo_extract_start', [
        'company_id' => $company_id,
        'source_url' => $source_url,
        'context' => $context
    ]);
    
    // US address pattern
    $address_pattern = '/(\d+\s+[A-Za-z\s]+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr|Way|Court|Ct|Plaza|Place|Pl|Parkway|Pkwy)\.?(?:\s+(?:Suite|Ste|Unit|Apt|#)\s*\w+)?)[,\s]+([A-Za-z\s]+),?\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)/i';
    
    if (preg_match_all($address_pattern, $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            // Skip PO Boxes
            if (preg_match('/P\.?O\.?\s*Box/i', $match[1])) {
                continue;
            }
            
            $location = [
                'address' => trim($match[1]),
                'city' => trim($match[2]),
                'state' => trim($match[3]),
                'zip_code' => trim($match[4]),
                'source' => "scraped_page: $source_url"
            ];
            
            // Try to find phone number near address
            $context_start = max(0, strpos($html, $match[0]) - 300);
            $context_end = min(strlen($html), strpos($html, $match[0]) + strlen($match[0]) + 300);
            $context_html = substr($html, $context_start, $context_end - $context_start);
            
            if (preg_match('/\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $context_html, $phone_match)) {
                $phone = preg_replace('/[^\d]/', '', $phone_match[0]);
                if (strlen($phone) === 10) {
                    $location['phone_number'] = substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
                }
            }
            
            $locations[] = $location;
            
            session_tracking('abo_location_found', [
                'company_id' => $company_id,
                'context' => $context,
                'location' => $location
            ]);
        }
    }
    
    session_tracking('abo_extract_complete', [
        'company_id' => $company_id,
        'context' => $context,
        'locations_found' => count($locations)
    ]);
    
    return $locations;
}

function extractStructuredData($html, $company_id) {
    $locations = [];
    
    if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $jsonld_matches)) {
        foreach ($jsonld_matches[1] as $jsonld) {
            try {
                $data = json_decode($jsonld, true);
                if ($data && isset($data['@type'])) {
                    $locations = array_merge($locations, parseStructuredDataItem($data));
                }
            } catch (Exception $e) {
                session_tracking('abo_structured_data_error', [
                    'company_id' => $company_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    return $locations;
}

function parseStructuredDataItem($data) {
    $locations = [];
    
    // Handle single location
    if (in_array($data['@type'], ['LocalBusiness', 'Store', 'Restaurant']) && isset($data['address'])) {
        $location = parseAddress($data['address']);
        if ($location) {
            if (isset($data['name'])) $location['name'] = $data['name'];
            if (isset($data['telephone'])) $location['phone_number'] = $data['telephone'];
            $location['source'] = 'structured_data';
            $locations[] = $location;
        }
    }
    
    // Handle organization with locations
    if ($data['@type'] === 'Organization' && isset($data['location'])) {
        if (is_array($data['location'])) {
            foreach ($data['location'] as $loc) {
                if (isset($loc['address'])) {
                    $location = parseAddress($loc['address']);
                    if ($location) {
                        if (isset($loc['name'])) $location['name'] = $loc['name'];
                        if (isset($loc['telephone'])) $location['phone_number'] = $loc['telephone'];
                        $location['source'] = 'structured_data';
                        $locations[] = $location;
                    }
                }
            }
        }
    }
    
    return $locations;
}

function parseAddress($addr) {
    if (!is_array($addr) || empty($addr['streetAddress'])) {
        return null;
    }
    
    return [
        'address' => $addr['streetAddress'] ?? '',
        'city' => $addr['addressLocality'] ?? '',
        'state' => $addr['addressRegion'] ?? '',
        'zip_code' => $addr['postalCode'] ?? '',
        'country' => $addr['addressCountry'] ?? 'United States'
    ];
}

function fetchGooglePlacesLocations($company_name, $api_key, $company_id) {
    $locations = [];
    
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
        'locations_found' => count($locations)
    ]);
    
    return $locations;
}

function fetchGooglePlaceDetails($place_id, $api_key) {
    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$place_id}&fields=formatted_address,formatted_phone_number,geometry,name&key={$api_key}";
    
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
    
    if (isset($result['name'])) $location['name'] = $result['name'];
    if (isset($result['formatted_phone_number'])) $location['phone_number'] = $result['formatted_phone_number'];
    if (isset($result['geometry']['location'])) {
        $location['latitude'] = $result['geometry']['location']['lat'];
        $location['longitude'] = $result['geometry']['location']['lng'];
    }
    
    return empty($location['address']) ? null : $location;
}

function discoverLocationPageViaGoogle($company, $google_cse_key, $google_cse_cx, $company_id) {
    $search_query = urlencode($company['company_name'] . ' locations stores');
    $google_search_url = "https://www.googleapis.com/customsearch/v1?key={$google_cse_key}&cx={$google_cse_cx}&q={$search_query}&num=10";
    
    session_tracking('abo_google_search', [
        'company_id' => $company_id,
        'search_query' => $search_query
    ]);
    
    $ch = curl_init($google_search_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['items']) && is_array($data['items'])) {
            $company_domain = parse_url($company['company_url'], PHP_URL_HOST);
            
            foreach ($data['items'] as $item) {
                if (isset($item['link'])) {
                    $result_domain = parse_url($item['link'], PHP_URL_HOST);
                    
                    // Must be same domain and contain location-related keywords
                    if ($result_domain === $company_domain && 
                        preg_match('/\/(locations?|stores?|find-?(?:a-)?store|store-?locator|our-?locations?)/i', $item['link'])) {
                        
                        session_tracking('abo_google_found_url', [
                            'company_id' => $company_id,
                            'found_url' => $item['link']
                        ]);
                        
                        return $item['link'];
                    }
                }
            }
        }
    }
    
    return null;
}

function tryCommonLocationUrls($company, $company_id, $database) {
    $locations = [];
    $base_url = rtrim($company['company_url'], '/');
    
    $urls_to_try = [
        $base_url . '/locations',
        $base_url . '/stores',
        $base_url . '/store-locator',
        $base_url . '/find-a-store',
        $base_url . '/our-locations',
        $base_url . '/locations/all'
    ];
    
    foreach ($urls_to_try as $url) {
        // Quick check if URL exists
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        
        if ($httpCode === 200) {
            session_tracking('abo_url_pattern_found', [
                'company_id' => $company_id,
                'url' => $url,
                'final_url' => $finalUrl
            ]);
            
            // Store discovered URL
            storeDiscoveredUrl($database, $company_id, $finalUrl);
            
            // Fetch and parse
            $html = fetchPage($finalUrl, $company_id, 'url_pattern_discovery');
            if ($html) {
                $page_locations = extractLocationsFromHtml($html, $finalUrl, $company_id, 'pattern_discovered');
                if (!empty($page_locations)) {
                    foreach ($page_locations as &$loc) {
                        $loc['source'] = 'discovered_locations_page: ' . $finalUrl;
                    }
                    $locations = array_merge($locations, $page_locations);
                    break; // Found locations, stop trying
                }
            }
        }
    }
    
    return $locations;
}

function storeDiscoveredUrl($database, $company_id, $url) {
    $attr_sql = "INSERT INTO bg_company_attributes 
                (company_id, type, name, description, status, create_dt)
                VALUES 
                (:company_id, 'data_collection', 'locations_url', :url, 'active', NOW())
                ON DUPLICATE KEY UPDATE description = VALUES(description), modify_dt = NOW()";
    
    $database->query($attr_sql, [
        'company_id' => $company_id,
        'url' => $url
    ]);
    
    session_tracking('abo_stored_url', [
        'company_id' => $company_id,
        'url' => $url
    ]);
}

function deduplicateLocations($locations, $company_id) {
    $unique = [];
    $seen = [];
    $duplicates = 0;
    
    foreach ($locations as $location) {
        // Create unique key
        $key = strtolower(trim($location['address']) . '|' . trim($location['city']) . '|' . trim($location['state'] ?? ''));
        
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $unique[] = $location;
        } else {
            $duplicates++;
        }
    }
    
    session_tracking('abo_deduplication', [
        'company_id' => $company_id,
        'total' => count($locations),
        'unique' => count($unique),
        'duplicates' => $duplicates
    ]);
    
    return $unique;
}

function saveLocations($locations, $company_id, $database) {
    $results = [
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'total_saved' => 0
    ];
    
    foreach ($locations as $location) {
        // Check if exists
        $check_sql = "SELECT location_id FROM bg_company_locations 
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
        
        $existing = $database->query($check_sql, $check_params)->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update if we have new data
            $updates = [];
            $update_params = ['location_id' => $existing['location_id']];
            
            if (!empty($location['phone_number'])) {
                $updates[] = 'phone_number = :phone_number';
                $update_params['phone_number'] = $location['phone_number'];
            }
            
            if (!empty($location['name'])) {
                $updates[] = 'location_name = :location_name';
                $update_params['location_name'] = $location['name'];
            }
            
            $updates[] = 'source = :source';
            $update_params['source'] = $location['source'];
            
            if (!empty($updates)) {
                $update_sql = "UPDATE bg_company_locations SET " . implode(', ', $updates) . ", modify_dt = NOW() WHERE location_id = :location_id";
                $database->query($update_sql, $update_params);
                $results['updated']++;
                
                session_tracking('abo_location_updated', [
                    'company_id' => $company_id,
                    'location_id' => $existing['location_id'],
                    'updates' => count($updates)
                ]);
            } else {
                $results['skipped']++;
            }
        } else {
            // Insert new
            $insert_sql = "INSERT INTO bg_company_locations 
                          (company_id, source, location_name, address, city, state, zip_code, country, 
                           phone_number, latitude, longitude, is_verified, status, create_dt)
                          VALUES 
                          (:company_id, :source, :location_name, :address, :city, :state, :zip_code, :country,
                           :phone_number, :latitude, :longitude, 0, 'active', NOW())";
            
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
                'longitude' => $location['longitude'] ?? null
            ];
            
            $database->query($insert_sql, $insert_params);
            $results['inserted']++;
            
            session_tracking('abo_location_inserted', [
                'company_id' => $company_id,
                'address' => $location['address'],
                'city' => $location['city']
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