<?php
// abo_grablocations_final.php - Extract store locations with proper patterns
// Part of the Automation Business Onboarding (ABO) system
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Initialize comprehensive result tracking
$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processor' => 'abo_grablocations_final',
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => [],
    'actions' => [],
    'locations' => [
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0
    ]
];

// Get company ID
$specific_company_id = null;
if (isset($_GET['rawid'])) {
    $specific_company_id = intval($_GET['rawid']);
    session_tracking('abo_grablocations_debug_mode', ['rawid' => $specific_company_id]);
} elseif (isset($_GET['id'])) {
    $encoded_id = $_GET['id'];
    $specific_company_id = $qik->decodeID($encoded_id);
    session_tracking('abo_grablocations_encoded_id', ['encoded' => $encoded_id, 'decoded' => $specific_company_id]);
}

// Track process start
session_tracking('abo_grablocations_start', [
    'specific_company_id' => $specific_company_id,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'ip_address' => $system->getipaddress()
]);

try {
    // Build query
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
        session_tracking('abo_grablocations_no_companies', $result);
        outputResult($result);
    }
    
    $result['processed'] = 1;
    $company_id = $company['company_id'];
    $company_name = $company['company_name'];
    $company_url = $company['company_url'];
    
    session_tracking('abo_grablocations_processing_company', [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'company_url' => $company_url,
        'status' => $company['status']
    ]);
    
    // Start transaction
    $database->beginTransaction();
    
    try {
        // Update to in_progress
        updateProgress($database, $company_id, 'in_progress');
        
        $all_locations = [];
        $methods_tried = [];
        
        // Method 1: Scrape homepage
        session_tracking('abo_method1_homepage_scrape', [
            'company_id' => $company_id,
            'url' => $company_url
        ]);
        
        $homepage_html = fetchPageWithSystem($system, $company_url, $company_id, 'homepage');
        
        if ($homepage_html['success']) {
            $html = $homepage_html['content'];
            
            // Extract locations directly from homepage
            $direct_locations = extractLocations($html, $company_url, $company_id, 'homepage_direct');
            if (!empty($direct_locations)) {
                $all_locations = array_merge($all_locations, $direct_locations);
                $methods_tried[] = 'homepage_direct';
                $result['actions'][] = sprintf("Found %d locations on homepage", count($direct_locations));
                session_tracking('abo_homepage_locations_found', [
                    'company_id' => $company_id,
                    'count' => count($direct_locations),
                    'sample' => array_slice($direct_locations, 0, 2)
                ]);
            }
            
            // Look for location page links
            $location_urls = findLocationUrls($html, $company_url, $company_id);
            
            if (!empty($location_urls)) {
                session_tracking('abo_location_urls_discovered', [
                    'company_id' => $company_id,
                    'urls' => $location_urls,
                    'count' => count($location_urls)
                ]);
                
                // Store discovered URLs
                foreach ($location_urls as $loc_url) {
                    storeLocationUrl($database, $company_id, $loc_url);
                }
                
                // Scrape each location page
                foreach ($location_urls as $loc_url) {
                    $loc_result = fetchPageWithSystem($system, $loc_url, $company_id, 'linked_locations_page');
                    if ($loc_result['success']) {
                        $loc_locations = extractLocations($loc_result['content'], $loc_url, $company_id, 'linked_page');
                        if (!empty($loc_locations)) {
                            foreach ($loc_locations as &$loc) {
                                $loc['source'] = 'scraped_locations_page: ' . $loc_url;
                            }
                            $all_locations = array_merge($all_locations, $loc_locations);
                            $result['actions'][] = sprintf("Found %d locations on %s", count($loc_locations), $loc_url);
                            session_tracking('abo_linked_page_locations', [
                                'company_id' => $company_id,
                                'url' => $loc_url,
                                'count' => count($loc_locations)
                            ]);
                        }
                    }
                }
                
                if (count($all_locations) > count($direct_locations)) {
                    $methods_tried[] = 'linked_location_pages';
                }
            }
            
            // Check structured data
            $structured = extractStructuredData($html, $company_id);
            if (!empty($structured)) {
                $all_locations = array_merge($all_locations, $structured);
                $methods_tried[] = 'structured_data';
                $result['actions'][] = sprintf("Found %d locations in structured data", count($structured));
                session_tracking('abo_structured_data_found', [
                    'company_id' => $company_id,
                    'count' => count($structured)
                ]);
            }
        } else {
            session_tracking('abo_homepage_fetch_failed', [
                'company_id' => $company_id,
                'url' => $company_url,
                'error' => $homepage_html['error']
            ]);
        }
        
        // Method 2: Google Places API
        if (empty($all_locations) && !empty($configs['GOOGLE_PLACES_API_KEY'])) {
            session_tracking('abo_method2_google_places', [
                'company_id' => $company_id,
                'company_name' => $company_name
            ]);
            
            $google_locations = fetchGooglePlaces($system, $company_name, $configs['GOOGLE_PLACES_API_KEY'], $company_id);
            if (!empty($google_locations)) {
                $all_locations = array_merge($all_locations, $google_locations);
                $methods_tried[] = 'google_places_api';
                $result['actions'][] = sprintf("Found %d locations via Google Places", count($google_locations));
            }
        }
        
        // Method 3: Google Search Discovery
        if (empty($all_locations) && !empty($configs['GOOGLE_CSE_KEY']) && !empty($configs['GOOGLE_CSE_CX'])) {
            session_tracking('abo_method3_google_search', [
                'company_id' => $company_id,
                'company_name' => $company_name
            ]);
            
            $discovered_url = discoverViaGoogleSearch($system, $company, $configs['GOOGLE_CSE_KEY'], $configs['GOOGLE_CSE_CX'], $company_id);
            
            if ($discovered_url) {
                storeLocationUrl($database, $company_id, $discovered_url);
                
                $discovered_result = fetchPageWithSystem($system, $discovered_url, $company_id, 'google_discovered');
                if ($discovered_result['success']) {
                    $discovered_locations = extractLocations($discovered_result['content'], $discovered_url, $company_id, 'discovered');
                    if (!empty($discovered_locations)) {
                        foreach ($discovered_locations as &$loc) {
                            $loc['source'] = 'discovered_locations_page: ' . $discovered_url;
                        }
                        $all_locations = array_merge($all_locations, $discovered_locations);
                        $methods_tried[] = 'google_search_discovery';
                        $result['actions'][] = sprintf("Found %d locations on discovered page: %s", 
                            count($discovered_locations), $discovered_url);
                    }
                }
            }
        }
        
        // Method 4: Try common URL patterns
        if (empty($all_locations)) {
            session_tracking('abo_method4_url_patterns', [
                'company_id' => $company_id
            ]);
            
            $pattern_locations = tryUrlPatterns($system, $company, $company_id, $database);
            if (!empty($pattern_locations)) {
                $all_locations = array_merge($all_locations, $pattern_locations);
                $methods_tried[] = 'url_pattern_testing';
                $result['actions'][] = sprintf("Found %d locations via URL patterns", count($pattern_locations));
            }
        }
        
        // Deduplicate
        $unique_locations = deduplicateLocations($all_locations, $company_id);
        
        session_tracking('abo_deduplication_complete', [
            'company_id' => $company_id,
            'total_found' => count($all_locations),
            'unique' => count($unique_locations),
            'duplicates' => count($all_locations) - count($unique_locations)
        ]);
        
        // Save to database
        $save_results = saveLocationsToDB($unique_locations, $company_id, $database);
        
        $result['successful'] = $save_results['total'];
        $result['locations'] = $save_results;
        $result['actions'][] = sprintf("Database operations: Inserted %d, Updated %d, Skipped %d", 
            $save_results['inserted'], 
            $save_results['updated'], 
            $save_results['skipped']
        );
        
        // Update final status
        $final_status = $result['successful'] > 0 ? 'completed' : 'attempted';
        updateProgress($database, $company_id, $final_status);
        
        // Commit
        $database->commit();
        
        // Final tracking
        session_tracking('abo_grablocations_complete', [
            'company_id' => $company_id,
            'company_name' => $company_name,
            'methods_tried' => $methods_tried,
            'locations_found' => count($all_locations),
            'unique_locations' => count($unique_locations),
            'database_results' => $save_results,
            'final_status' => $final_status,
            'actions' => $result['actions']
        ]);
        
    } catch (Exception $e) {
        $database->rollback();
        updateProgress($database, $company_id, 'error');
        
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
    
    session_tracking('abo_grablocations_fatal', [
        'error' => $e->getMessage()
    ]);
}

outputResult($result);

// ===== HELPER FUNCTIONS =====

function outputResult($result) {
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

function updateProgress($database, $company_id, $status) {
    $sql = "UPDATE bg_company_attributes 
            SET description = :status, modify_dt = NOW() 
            WHERE company_id = :company_id 
            AND type = 'onboarding_progress' 
            AND name = 'abo_grablocations'";
    $database->query($sql, ['company_id' => $company_id, 'status' => $status]);
    
    session_tracking('abo_progress_updated', [
        'company_id' => $company_id,
        'new_status' => $status
    ]);
}

function fetchPageWithSystem($system, $url, $company_id, $context) {
    session_tracking('abo_fetch_page_start', [
        'company_id' => $company_id,
        'url' => $url,
        'context' => $context
    ]);
    
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ];
    
    $response = $system->curlRequest($url, $headers, [], 'GET');
    
    $result = [
        'success' => false,
        'content' => '',
        'error' => ''
    ];
    
    if (isset($response['raw']) && !isset($response['error'])) {
        $result['success'] = true;
        $result['content'] = $response['raw'];
        
        session_tracking('abo_fetch_page_success', [
            'company_id' => $company_id,
            'url' => $url,
            'context' => $context,
            'content_length' => strlen($response['raw'])
        ]);
    } else {
        $result['error'] = $response['error'] ?? 'Unknown error';
        
        session_tracking('abo_fetch_page_failed', [
            'company_id' => $company_id,
            'url' => $url,
            'context' => $context,
            'error' => $result['error']
        ]);
    }
    
    return $result;
}

function findLocationUrls($html, $base_url, $company_id) {
    $urls = [];
    
    $patterns = [
        '/<a[^>]+href=[\"\']([^\"\']*(?:locations?|stores?|find-?us|store-?locator|find-?a-?store|our-?locations?)[^\"\']*)[\"\'][^>]*>/i',
        '/<a[^>]*>(?:[^<]*(?:locations?|stores?|find))[^<]*<\/a>/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            // For the second pattern, we need to extract href differently
            if (strpos($pattern, 'href=') === false) {
                // Extract href from the matched <a> tags
                foreach ($matches[0] as $match) {
                    if (preg_match('/href=[\"\']([^\"\']+)[\"\']/', $match, $href_match)) {
                        $urls[] = makeAbsolute($href_match[1], $base_url);
                    }
                }
            } else {
                foreach ($matches[1] as $url) {
                    $urls[] = makeAbsolute($url, $base_url);
                }
            }
        }
    }
    
    // Filter and validate
    $valid_urls = [];
    $domain = parse_url($base_url, PHP_URL_HOST);
    
    foreach (array_unique($urls) as $url) {
        if (parse_url($url, PHP_URL_HOST) === $domain) {
            // Avoid non-location pages
            $avoid = ['/careers', '/jobs', '/contact', '/about', '/privacy', '/terms'];
            $skip = false;
            foreach ($avoid as $pattern) {
                if (stripos($url, $pattern) !== false) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip) {
                $valid_urls[] = $url;
            }
        }
    }
    
    session_tracking('abo_location_urls_found', [
        'company_id' => $company_id,
        'total_found' => count($urls),
        'valid_urls' => count($valid_urls),
        'urls' => array_slice($valid_urls, 0, 5)
    ]);
    
    return $valid_urls;
}

function makeAbsolute($url, $base) {
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }
    
    $parsed = parse_url($base);
    
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

function extractLocations($html, $source_url, $company_id, $context) {
    $locations = [];
    
    // US address pattern
    $pattern = '/(\d+\s+[A-Za-z\s]+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr|Way|Court|Ct|Plaza|Place|Pl|Parkway|Pkwy)\.?(?:\s+(?:Suite|Ste|Unit|Apt|#)\s*\w+)?)[,\s]+([A-Za-z\s]+),?\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)/i';
    
    if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
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
            
            // Find phone
            $start = max(0, strpos($html, $match[0]) - 300);
            $end = min(strlen($html), strpos($html, $match[0]) + strlen($match[0]) + 300);
            $nearby = substr($html, $start, $end - $start);
            
            if (preg_match('/\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $nearby, $phone)) {
                $digits = preg_replace('/[^\d]/', '', $phone[0]);
                if (strlen($digits) === 10) {
                    $location['phone_number'] = substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6);
                }
            }
            
            $locations[] = $location;
            
            session_tracking('abo_location_extracted', [
                'company_id' => $company_id,
                'context' => $context,
                'address' => $location['address'],
                'city' => $location['city']
            ]);
        }
    }
    
    return $locations;
}

function extractStructuredData($html, $company_id) {
    $locations = [];
    
    if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
        foreach ($matches[1] as $json) {
            try {
                $data = json_decode($json, true);
                if ($data && isset($data['@type'])) {
                    $extracted = parseStructuredItem($data);
                    $locations = array_merge($locations, $extracted);
                }
            } catch (Exception $e) {
                session_tracking('abo_structured_parse_error', [
                    'company_id' => $company_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    return $locations;
}

function parseStructuredItem($data) {
    $locations = [];
    
    if (in_array($data['@type'], ['LocalBusiness', 'Store', 'Restaurant']) && isset($data['address'])) {
        $loc = parseAddressObject($data['address']);
        if ($loc) {
            if (isset($data['name'])) $loc['name'] = $data['name'];
            if (isset($data['telephone'])) $loc['phone_number'] = $data['telephone'];
            $loc['source'] = 'structured_data';
            $locations[] = $loc;
        }
    }
    
    if ($data['@type'] === 'Organization' && isset($data['location'])) {
        if (is_array($data['location'])) {
            foreach ($data['location'] as $item) {
                if (isset($item['address'])) {
                    $loc = parseAddressObject($item['address']);
                    if ($loc) {
                        if (isset($item['name'])) $loc['name'] = $item['name'];
                        if (isset($item['telephone'])) $loc['phone_number'] = $item['telephone'];
                        $loc['source'] = 'structured_data';
                        $locations[] = $loc;
                    }
                }
            }
        }
    }
    
    return $locations;
}

function parseAddressObject($addr) {
    if (!is_array($addr) || empty($addr['streetAddress'])) {
        return null;
    }
    
    return [
        'address' => $addr['streetAddress'],
        'city' => $addr['addressLocality'] ?? '',
        'state' => $addr['addressRegion'] ?? '',
        'zip_code' => $addr['postalCode'] ?? ''
    ];
}

function storeLocationUrl($database, $company_id, $url) {
    $sql = "INSERT INTO bg_company_attributes 
            (company_id, type, name, description, status, create_dt)
            VALUES 
            (:company_id, 'data_collection', 'locations_url', :url, 'active', NOW())
            ON DUPLICATE KEY UPDATE description = VALUES(description), modify_dt = NOW()";
    
    $database->query($sql, ['company_id' => $company_id, 'url' => $url]);
    
    session_tracking('abo_stored_location_url', [
        'company_id' => $company_id,
        'url' => $url
    ]);
}

function fetchGooglePlaces($system, $company_name, $api_key, $company_id) {
    $locations = [];
    $query = urlencode($company_name);
    $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query={$query}&key={$api_key}";
    
    $response = $system->curlRequest($url, [], [], 'GET');
    
    if (isset($response['decoded']['results'])) {
        foreach ($response['decoded']['results'] as $place) {
            if (isset($place['place_id'])) {
                $details = fetchPlaceDetails($system, $place['place_id'], $api_key);
                if ($details) {
                    $details['source'] = 'google_places_api';
                    $locations[] = $details;
                }
            }
        }
    }
    
    session_tracking('abo_google_places_results', [
        'company_id' => $company_id,
        'company_name' => $company_name,
        'locations_found' => count($locations)
    ]);
    
    return $locations;
}

function fetchPlaceDetails($system, $place_id, $api_key) {
    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$place_id}&fields=formatted_address,formatted_phone_number,geometry,name&key={$api_key}";
    
    $response = $system->curlRequest($url, [], [], 'GET');
    
    if (!isset($response['decoded']['result'])) {
        return null;
    }
    
    $result = $response['decoded']['result'];
    $location = [];
    
    if (isset($result['formatted_address'])) {
        $parts = explode(',', $result['formatted_address']);
        if (count($parts) >= 3) {
            $location['address'] = trim($parts[0]);
            $location['city'] = trim($parts[1]);
            
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

function discoverViaGoogleSearch($system, $company, $cse_key, $cse_cx, $company_id) {
    $query = urlencode($company['company_name'] . ' locations stores');
    $url = "https://www.googleapis.com/customsearch/v1?key={$cse_key}&cx={$cse_cx}&q={$query}&num=10";
    
    $response = $system->curlRequest($url, [], [], 'GET');
    
    if (isset($response['decoded']['items'])) {
        $company_domain = parse_url($company['company_url'], PHP_URL_HOST);
        
        foreach ($response['decoded']['items'] as $item) {
            if (isset($item['link'])) {
                $item_domain = parse_url($item['link'], PHP_URL_HOST);
                
                if ($item_domain === $company_domain && 
                    preg_match('/\/(locations?|stores?|find-?(?:a-)?store|store-?locator)/i', $item['link'])) {
                    
                    session_tracking('abo_google_search_found', [
                        'company_id' => $company_id,
                        'discovered_url' => $item['link']
                    ]);
                    
                    return $item['link'];
                }
            }
        }
    }
    
    return null;
}

function tryUrlPatterns($system, $company, $company_id, $database) {
    $locations = [];
    $base = rtrim($company['company_url'], '/');
    
    $patterns = [
        '/locations',
        '/stores',
        '/store-locator',
        '/find-a-store',
        '/our-locations',
        '/locations/all'
    ];
    
    foreach ($patterns as $pattern) {
        $test_url = $base . $pattern;
        
        // Quick HEAD request to check existence
        $headers = ['User-Agent: Mozilla/5.0'];
        $response = $system->curlRequest($test_url, $headers, [], 'HEAD');
        
        if (!isset($response['error'])) {
            session_tracking('abo_url_pattern_hit', [
                'company_id' => $company_id,
                'pattern' => $pattern,
                'url' => $test_url
            ]);
            
            storeLocationUrl($database, $company_id, $test_url);
            
            $page_result = fetchPageWithSystem($system, $test_url, $company_id, 'pattern_test');
            if ($page_result['success']) {
                $found = extractLocations($page_result['content'], $test_url, $company_id, 'pattern');
                if (!empty($found)) {
                    foreach ($found as &$loc) {
                        $loc['source'] = 'discovered_locations_page: ' . $test_url;
                    }
                    $locations = array_merge($locations, $found);
                    break;
                }
            }
        }
    }
    
    return $locations;
}

function deduplicateLocations($locations, $company_id) {
    $unique = [];
    $seen = [];
    
    foreach ($locations as $loc) {
        $key = strtolower(trim($loc['address']) . '|' . trim($loc['city']) . '|' . trim($loc['state'] ?? ''));
        
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $unique[] = $loc;
        }
    }
    
    session_tracking('abo_deduplication', [
        'company_id' => $company_id,
        'total' => count($locations),
        'unique' => count($unique),
        'removed' => count($locations) - count($unique)
    ]);
    
    return $unique;
}

function saveLocationsToDB($locations, $company_id, $database) {
    $results = [
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'total' => 0
    ];
    
    foreach ($locations as $loc) {
        // Check existence
        $check = "SELECT location_id FROM bg_company_locations 
                 WHERE company_id = :company_id 
                 AND address = :address 
                 AND city = :city 
                 AND state = :state";
        
        $existing = $database->query($check, [
            'company_id' => $company_id,
            'address' => $loc['address'],
            'city' => $loc['city'],
            'state' => $loc['state'] ?? ''
        ])->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update if we have new info
            $updates = [];
            $params = ['location_id' => $existing['location_id']];
            
            if (!empty($loc['phone_number'])) {
                $updates[] = 'phone_number = :phone';
                $params['phone'] = $loc['phone_number'];
            }
            
            if (!empty($loc['name'])) {
                $updates[] = 'location_name = :name';
                $params['name'] = $loc['name'];
            }
            
            $updates[] = 'source = :source';
            $params['source'] = $loc['source'];
            
            if (!empty($updates)) {
                $update = "UPDATE bg_company_locations SET " . implode(', ', $updates) . ", modify_dt = NOW() WHERE location_id = :location_id";
                $database->query($update, $params);
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
            $insert = "INSERT INTO bg_company_locations 
                      (company_id, source, location_name, address, city, state, zip_code, 
                       phone_number, latitude, longitude, is_verified, status, create_dt)
                      VALUES 
                      (:company_id, :source, :name, :address, :city, :state, :zip, 
                       :phone, :lat, :lng, 0, 'active', NOW())";
            
            $database->query($insert, [
                'company_id' => $company_id,
                'source' => $loc['source'],
                'name' => $loc['name'] ?? null,
                'address' => $loc['address'],
                'city' => $loc['city'],
                'state' => $loc['state'] ?? null,
                'zip' => $loc['zip_code'] ?? null,
                'phone' => $loc['phone_number'] ?? null,
                'lat' => $loc['latitude'] ?? null,
                'lng' => $loc['longitude'] ?? null
            ]);
            
            $results['inserted']++;
            
            session_tracking('abo_location_inserted', [
                'company_id' => $company_id,
                'address' => $loc['address'],
                'city' => $loc['city']
            ]);
        }
    }
    
    $results['total'] = $results['inserted'] + $results['updated'];
    
    session_tracking('abo_save_results', [
        'company_id' => $company_id,
        'results' => $results
    ]);
    
    return $results;
}