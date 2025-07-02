<?php
$addClasses[] = 'sms';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Helper function to get user attribute
function getUserAttribute($database, $userId, $type, $name) {
    $query = "SELECT description, string_value, value FROM bg_user_attributes 
              WHERE user_id = :user_id 
              AND type = :type 
              AND name = :name 
              AND status = 'active' 
              LIMIT 1";
    $stmt = $database->prepare($query);
    $stmt->execute([
        ':user_id' => $userId,
        ':type' => $type,
        ':name' => $name
    ]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Note: Tour history tracking removed for simplicity
// Tours are tracked in bg_user_tours
// Business locations are stored in bg_company_locations
// User home location is stored as a preference in bg_user_attributes

// Helper function to get company locations
function getCompanyLocations($database, $companyId) {
    $query = "SELECT * FROM bg_company_locations 
              WHERE company_id = :company_id 
              AND status = 'active' 
              ORDER BY is_verified DESC, create_dt DESC";
    
    $stmt = $database->prepare($query);
    $stmt->execute([':company_id' => $companyId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Handle AJAX requests
if (isset($_POST['action'])) {
    // Check if headers were already sent
    if (headers_sent($file, $line)) {
        die(json_encode(['success' => false, 'message' => "Headers already sent in $file on line $line"]));
    }
    
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Start output buffering to catch any warnings
    ob_start();
    
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'update_home_location':
            $address = $_POST['address'] ?? '';
            $lat = $_POST['lat'] ?? null;
            $lng = $_POST['lng'] ?? null;
            
            // Store in session for immediate use
            $_SESSION['tour_home_address'] = $address;
            $_SESSION['tour_home_lat'] = $lat;
            $_SESSION['tour_home_lng'] = $lng;
            
            // Save as tour location
            $locationData = [
                'address' => $address,
                'lat' => $lat,
                'lng' => $lng,
                'city' => $_POST['city'] ?? '',
                'state' => $_POST['state'] ?? '',
                'zip' => $_POST['zip'] ?? ''
            ];
            
            // Get tour date
            $tourDate = $_POST['tour_date'] ?? date('Y-m-d');
            $locationName = 'start_' . $tourDate;
            
            // Store location with tour date
            $locationDataWithMeta = array_merge($locationData, [
                'type' => 'home',
                'tour_date' => $tourDate,
                'saved_at' => date('Y-m-d H:i:s')
            ]);
            $locationJson = json_encode($locationDataWithMeta);
            
            // Check if location already exists for this tour
            $checkQuery = "SELECT attribute_id FROM bg_user_attributes 
                          WHERE user_id = :user_id 
                          AND type = 'tour_locations' 
                          AND name = :name 
                          AND status = 'active'";
            $stmt = $database->prepare($checkQuery);
            $stmt->execute([
                ':user_id' => $current_user_data['user_id'],
                ':name' => $locationName
            ]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing
                $updateQuery = "UPDATE bg_user_attributes 
                               SET description = :description,
                                   modify_dt = NOW() 
                               WHERE attribute_id = :attribute_id";
                $stmt = $database->prepare($updateQuery);
                $stmt->execute([
                    ':description' => $locationJson,
                    ':attribute_id' => $existing['attribute_id']
                ]);
            } else {
                // Insert new
                $insertQuery = "INSERT INTO bg_user_attributes 
                               (user_id, type, name, category, description, status, create_dt, modify_dt) 
                               VALUES (:user_id, 'tour_locations', :name, 'tour', :description, 'active', NOW(), NOW())";
                $stmt = $database->prepare($insertQuery);
                $stmt->execute([
                    ':user_id' => $current_user_data['user_id'],
                    ':name' => $locationName,
                    ':description' => $locationJson
                ]);
            }
            
            ob_end_clean();
            echo json_encode(['success' => true]);
            exit;
            
        case 'geocode_address':
            $address = $_POST['address'] ?? '';
            $apiKey = $sitesettings['GOOGLEAPI']['browser_key'] ?? '';
            
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . $apiKey;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $result = $data['results'][0];
                $location = $result['geometry']['location'];
                
                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'lat' => $location['lat'],
                    'lng' => $location['lng'],
                    'formatted_address' => $result['formatted_address']
                ]);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Geocoding failed']);
            }
            exit;
            
        case 'reorder_tour':
            $order = $_POST['order'] ?? [];
            $tourDate = $_POST['tour_date'] ?? date('Y-m-d');
            
            // Update sort_order for each company
            foreach ($order as $index => $companyId) {
                $updateQuery = "UPDATE bg_user_tours 
                               SET sort_order = :sort_order,
                                   modify_dt = NOW() 
                               WHERE user_id = :user_id 
                               AND company_id = :company_id 
                               AND calendar_dt = :calendar_dt";
                $stmt = $database->prepare($updateQuery);
                $stmt->execute([
                    ':sort_order' => $index,
                    ':user_id' => $current_user_data['user_id'],
                    ':company_id' => $companyId,
                    ':calendar_dt' => $tourDate
                ]);
            }
            
            ob_end_clean();
            echo json_encode(['success' => true]);
            exit;
            
        case 'search_business_locations':
            $companyId = $_POST['company_id'] ?? '';
            $radius = $_POST['radius'] ?? 25;
            $homeLat = $_POST['home_lat'] ?? 39.7392;
            $homeLng = $_POST['home_lng'] ?? -104.9903;
            
            // Get all locations for this company
            $locations = getCompanyLocations($database, $companyId);
            
            // Calculate distances and filter by radius
            foreach ($locations as &$loc) {
                if ($loc['latitude'] && $loc['longitude']) {
                    $loc['distance'] = calculateDistance($homeLat, $homeLng, $loc['latitude'], $loc['longitude']);
                    $loc['full_address'] = trim($loc['address'] . ', ' . $loc['city'] . ', ' . $loc['state'] . ' ' . $loc['zip_code']);
                } else {
                    $loc['distance'] = PHP_FLOAT_MAX; // Put non-geocoded locations at the end
                }
            }
            
            // Sort by distance
            usort($locations, function($a, $b) {
                return $a['distance'] <=> $b['distance'];
            });
            
            // Filter by radius
            $filteredLocations = array_filter($locations, function($loc) use ($radius) {
                return $loc['distance'] <= $radius;
            });
            
            // If no geocoded locations in radius, return all for geocoding
            if (empty($filteredLocations)) {
                foreach ($locations as &$loc) {
                    $loc['full_address'] = trim($loc['address'] . ', ' . $loc['city'] . ', ' . $loc['state'] . ' ' . $loc['zip_code']);
                    $loc['distance'] = 'Unknown';
                }
            }
            
            ob_end_clean();
            echo json_encode(['success' => true, 'locations' => $locations, 'count' => count($locations)]);
            exit;
            
        case 'save_business_location':
            $companyId = $_POST['company_id'] ?? '';
            $companyName = $_POST['company_name'] ?? '';
            $address = $_POST['address'] ?? '';
            $lat = $_POST['lat'] ?? null;
            $lng = $_POST['lng'] ?? null;
            
            // Parse address to extract components
            $addressParts = array_map('trim', explode(',', $address));
            $city = '';
            $state = '';
            $zip = '';
            
            if (count($addressParts) >= 3) {
                // Assume format: street, city, state zip
                $city = $addressParts[1];
                $stateZip = trim($addressParts[2]);
                $stateZipParts = explode(' ', $stateZip);
                if (count($stateZipParts) >= 2) {
                    $state = $stateZipParts[0];
                    $zip = $stateZipParts[1];
                }
            }
            
            // Check if this location already exists for this company
            $checkQuery = "SELECT location_id FROM bg_company_locations 
                          WHERE company_id = :company_id 
                          AND address = :address 
                          AND status = 'active'";
            $stmt = $database->prepare($checkQuery);
            $stmt->execute([
                ':company_id' => $companyId,
                ':address' => $addressParts[0] ?? $address
            ]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing) {
                // Insert new location
                $insertQuery = "INSERT INTO bg_company_locations 
                               (company_id, source, address, city, state, zip_code, latitude, longitude, is_verified) 
                               VALUES (:company_id, 'tour_geocoding', :address, :city, :state, :zip, :lat, :lng, 1)";
                $stmt = $database->prepare($insertQuery);
                $stmt->execute([
                    ':company_id' => $companyId,
                    ':address' => $addressParts[0] ?? $address,
                    ':city' => $city,
                    ':state' => $state,
                    ':zip' => $zip,
                    ':lat' => $lat,
                    ':lng' => $lng
                ]);
                $locationId = $database->lastInsertId();
            } else {
                // Update existing location with coordinates
                $updateQuery = "UPDATE bg_company_locations 
                               SET latitude = :lat, 
                                   longitude = :lng, 
                                   is_verified = 1,
                                   modify_dt = NOW() 
                               WHERE location_id = :location_id";
                $stmt = $database->prepare($updateQuery);
                $stmt->execute([
                    ':lat' => $lat,
                    ':lng' => $lng,
                    ':location_id' => $existing['location_id']
                ]);
                $locationId = $existing['location_id'];
            }
            
            ob_end_clean();
            echo json_encode(['success' => true, 'location_id' => $locationId]);
            exit;
            
        case 'update_tour_location':
            try {
                $tourDate = $_POST['tour_date'] ?? date('Y-m-d');
                $companyId = $_POST['company_id'] ?? '';
                $locationId = $_POST['location_id'] ?? null;
                $forceLocation = $_POST['force_location'] ?? 0;
                
                if (!$companyId) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Missing company ID']);
                    exit;
                }
                
                if (!$locationId) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Missing location ID']);
                    exit;
                }
                
                // Create attributes JSON
                $attributes = json_encode([
                    'force_location' => (bool)$forceLocation,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => 'user'
                ]);
                
                // Update the tour with the selected location
                $updateQuery = "UPDATE bg_user_tours 
                               SET location_id = :location_id,
                                   attributes = :attributes,
                                   modify_dt = NOW() 
                               WHERE user_id = :user_id 
                               AND company_id = :company_id 
                               AND calendar_dt = :calendar_dt";
                
                $stmt = $database->prepare($updateQuery);
                $result = $stmt->execute([
                    ':location_id' => $locationId,
                    ':attributes' => $attributes,
                    ':user_id' => $current_user_data['user_id'],
                    ':company_id' => $companyId,
                    ':calendar_dt' => $tourDate
                ]);
                
                // Clear output buffer before sending response
                ob_end_clean();
                
                if ($result) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database update failed']);
                }
            } catch (Exception $e) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'send_to_phone':
            $navigationUrl = $_POST['navigation_url'] ?? '';
            $phoneType = $_POST['phone_type'] ?? '';
            $phoneTypeSource = $_POST['phone_type_source'] ?? 'unknown';
            $tourDate = $_POST['tour_date'] ?? date('Y-m-d');
            $debug = isset($_GET['debug']) || isset($_POST['debug']);
            $previewOnly = isset($_POST['preview_only']) && $_POST['preview_only'];
            
            if (!$navigationUrl) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Missing navigation URL']);
                exit;
            }
            
            // Check for test phone number in debug mode
            $testPhone = $_POST['test_phone'] ?? null;
            
            if ($debug && $testPhone) {
                // Use test phone number in debug mode
                $phoneNumber = $testPhone;
            } else {
                // Get user's phone number from profile
                $phoneQuery = "SELECT string_value FROM bg_user_attributes 
                              WHERE user_id = :user_id 
                              AND type = 'profile' 
                              AND name = 'profile_phone_number' 
                              AND status = 'active' 
                              LIMIT 1";
                $stmt = $database->prepare($phoneQuery);
                $stmt->execute([':user_id' => $current_user_data['user_id']]);
                $phoneData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$phoneData || empty($phoneData['string_value'])) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'No phone number found. Please update your profile.']);
                    exit;
                }
                
                $phoneNumber = $phoneData['string_value'];
            }
            
            // Clean phone number (remove non-digits)
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
            
            // Ensure it has country code (assume US if 10 digits)
            if (strlen($phoneNumber) == 10) {
                $phoneNumber = '1' . $phoneNumber;
            }
            
            // Use app->getshortcode to shorten the URL
            try {
                // Use the built-in method which now has SSL bypass and cURL fallback
                $shortCodeData = $app->getshortcode($navigationUrl, 'tour_nav_' . $tourDate);
                
                if (!$shortCodeData || !isset($shortCodeData['shorturl'])) {
                    ob_end_clean();
                    $errorMsg = 'Failed to create short URL';
                    if ($debug) {
                        $errorMsg .= ' - Navigation URL length: ' . strlen($navigationUrl);
                        $errorMsg .= "\n\nNavigation URL being shortened:\n" . $navigationUrl;
                        $errorMsg .= "\n\nFull API URL:\nhttps://bd.gold/api.php?url=" . urlencode($navigationUrl) . '&cust=' . urlencode('tour_nav_' . $tourDate);
                        if ($shortCodeData) {
                            $errorMsg .= "\n\nAPI Response: " . json_encode($shortCodeData);
                        }
                    }
                    echo json_encode(['success' => false, 'message' => $errorMsg]);
                    exit;
                }
            } catch (Exception $e) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'URL shortening error: ' . $e->getMessage()]);
                exit;
            }
            
            $shortUrl = $shortCodeData['shorturl'];
            
            // Build debug info
            $debugInfo = [];
            if ($debug) {
                // Create platform-specific URLs for debug
                $parsedUrl = parse_url($navigationUrl);
                parse_str($parsedUrl['query'] ?? '', $params);
                
                $appleMapsUrl = 'https://maps.apple.com/?saddr=' . ($params['saddr'] ?? '') . '&daddr=' . ($params['daddr'] ?? '');
                $googleMapsUrl = $navigationUrl;
                
                $debugInfo = [
                    'original_url' => $navigationUrl,
                    'shortened_url' => $shortUrl,
                    'apple_maps_url' => $appleMapsUrl,
                    'google_maps_url' => $googleMapsUrl,
                    'phone_type' => $phoneType,
                    'phone_type_source' => $phoneTypeSource,
                    'phone_number' => substr($phoneNumber, 0, -4) . 'XXXX', // Partially hide for privacy
                    'shortcode_data' => $shortCodeData // Include full response for debugging
                ];
            }
            
            // Create SMS message
            $message = "Your Birthday Tour navigation link for " . date('M j, Y', strtotime($tourDate)) . ":\n" . $shortUrl . "\n\nTap to open in " . ($phoneType === 'iphone' || $phoneType === 'ios' ? 'Maps' : 'Google Maps');
            
            // Format phone number for display (show last 4 digits)
            $displayPhone = '';
            if (strlen($phoneNumber) >= 10) {
                $displayPhone = '(' . substr($phoneNumber, -10, 3) . ') ' . substr($phoneNumber, -7, 3) . '-' . substr($phoneNumber, -4);
                if (strlen($phoneNumber) > 10) {
                    $displayPhone = '+' . substr($phoneNumber, 0, -10) . ' ' . $displayPhone;
                }
            } else {
                $displayPhone = $phoneNumber;
            }
            
            // If preview only mode, return without sending SMS
            if ($previewOnly) {
                ob_end_clean();
                $response = [
                    'success' => true, 
                    'message' => 'Preview mode - SMS not sent',
                    'preview' => [
                        'message' => $message,
                        'short_url' => $shortUrl,
                        'phone_number' => $displayPhone,
                        'raw_phone' => $phoneNumber
                    ]
                ];
                if ($debug) {
                    $response['debug'] = $debugInfo;
                }
                echo json_encode($response);
                exit;
            }
            
            // Send SMS using the SMS gateway
            try {
                // SMS class should already be loaded via $addClasses at top of file
                // Send SMS using the class method
                $smsResult = $sms->sendSingleMessage($phoneNumber, $message);
                
                if ($smsResult && isset($smsResult['status']) && $smsResult['status'] !== 'Failed') {
                    ob_end_clean();
                    $response = [
                        'success' => true, 
                        'message' => 'Navigation link sent to ' . $displayPhone . '!',
                        'phone_number' => $displayPhone,
                        'short_url' => $shortUrl,
                        'sms_status' => 'sent'
                    ];
                    if ($debug) {
                        $response['debug'] = $debugInfo;
                        $response['sms_result'] = $smsResult;
                        $response['message_text'] = $message;
                    }
                    echo json_encode($response);
                    exit;
                } else {
                    // If SMS fails, still return the URL
                    ob_end_clean();
                    $errorMessage = isset($smsResult['error']) ? $smsResult['error'] : 'SMS gateway returned failure status';
                    $response = [
                        'success' => true, 
                        'message' => 'Navigation URL created but SMS failed to send to ' . $displayPhone . '. URL: ' . $shortUrl,
                        'url' => $shortUrl,
                        'phone_number' => $displayPhone,
                        'sms_status' => 'failed',
                        'sms_error' => $errorMessage
                    ];
                    if ($debug) {
                        $response['debug'] = $debugInfo;
                        $response['sms_result'] = $smsResult;
                        $response['message_text'] = $message;
                    }
                    echo json_encode($response);
                    exit;
                }
                
            } catch (Exception $e) {
                ob_end_clean();
                $response = [
                    'success' => false, 
                    'message' => 'Failed to send SMS to ' . $displayPhone . ': ' . $e->getMessage(),
                    'phone_number' => $displayPhone,
                    'short_url' => $shortUrl,
                    'sms_status' => 'error'
                ];
                if ($debug) {
                    $response['debug'] = $debugInfo;
                    $response['message_text'] = $message;
                }
                echo json_encode($response);
                exit;
            }
    }
}

// Get tour data - hardcoded to 2025-07-03 for testing
$date = '2025-07-03'; // $_GET['date'] ?? date('Y-m-d');

// Get tour businesses for this date
$checkEnrollmentQuery = "SELECT t.*, 
                        cl.location_id as cl_location_id,
                        cl.address as cl_address,
                        cl.city as cl_city,
                        cl.state as cl_state,
                        cl.zip_code as cl_zip_code,
                        cl.latitude as cl_latitude,
                        cl.longitude as cl_longitude
                        FROM bg_user_tours t 
                        LEFT JOIN bg_company_locations cl ON t.location_id = cl.location_id 
                        WHERE t.calendar_dt = :date 
                        AND t.user_id = :user_id";
$stmt = $database->prepare($checkEnrollmentQuery);
$stmt->execute([
    ':date' => $date,
    ':user_id' => $current_user_data['user_id']
]);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$listofcompanies = [];
foreach ($companies as $item_company) {  
    $company_data = $app->getcompany($item_company['company_id']);
    
    // Check if we have a specific location_id set in the tour (user selected a specific location)
    if (!empty($item_company['location_id']) && !empty($item_company['cl_latitude'])) {
        // Use the specific location from the tour (this is a user-selected location)
        if (!empty($item_company['cl_address'])) {
            $company_data['address'] = $item_company['cl_address'];
        }
        if (!empty($item_company['cl_city'])) {
            $company_data['city'] = $item_company['cl_city'];
        }
        if (!empty($item_company['cl_state'])) {
            $company_data['state'] = $item_company['cl_state'];
        }
        if (!empty($item_company['cl_zip_code'])) {
            $company_data['zip_code'] = $item_company['cl_zip_code'];
        }
        
        $company_data['latitude'] = $item_company['cl_latitude'];
        $company_data['longitude'] = $item_company['cl_longitude'];
        $company_data['has_verified_location'] = true;
        $company_data['location_id'] = $item_company['cl_location_id'];
        
        // Parse attributes JSON to check if location is forced
        $attributes = !empty($item_company['attributes']) ? json_decode($item_company['attributes'], true) : [];
        $company_data['is_forced_location'] = isset($attributes['force_location']) && $attributes['force_location'];
    } else {
        // Check if we have verified locations in bg_company_locations
        $verifiedLocation = null;
        if ($company_data) {
            $locationQuery = "SELECT * FROM bg_company_locations 
                             WHERE company_id = :company_id 
                             AND status = 'active' 
                             AND is_verified = 1 
                             AND latitude IS NOT NULL 
                             AND longitude IS NOT NULL 
                             ORDER BY modify_dt DESC 
                             LIMIT 1";
            $stmt = $database->prepare($locationQuery);
            $stmt->execute([':company_id' => $item_company['company_id']]);
            $verifiedLocation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If we have a verified location, update the company data
            if ($verifiedLocation) {
                // Only update address fields if they're not empty in the verified location
                if (!empty($verifiedLocation['address'])) {
                    $company_data['address'] = $verifiedLocation['address'];
                }
                if (!empty($verifiedLocation['city'])) {
                    $company_data['city'] = $verifiedLocation['city'];
                }
                if (!empty($verifiedLocation['state'])) {
                    $company_data['state'] = $verifiedLocation['state'];
                }
                if (!empty($verifiedLocation['zip_code'])) {
                    $company_data['zip_code'] = $verifiedLocation['zip_code'];
                }
                
                // Always update coordinates if available
                $company_data['latitude'] = $verifiedLocation['latitude'];
                $company_data['longitude'] = $verifiedLocation['longitude'];
                $company_data['has_verified_location'] = true;
                $company_data['location_id'] = $verifiedLocation['location_id'];
            }
        }
    }
    
    $listofcompanies[] = $item_company + ['data' => $company_data];
}

// Distance calculation function (Haversine formula)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
        return PHP_FLOAT_MAX;
    }
    
    $R = 3959; // Earth's radius in miles
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);
    
    $dLat = $lat2 - $lat1;
    $dLon = $lon2 - $lon1;
    
    $a = sin($dLat/2) * sin($dLat/2) + cos($lat1) * cos($lat2) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $R * $c;
    
    return round($distance, 1);
}

// Get home location for this tour
$locationName = 'start_' . $date;
$homeLocationData = getUserAttribute($database, $current_user_data['user_id'], 'tour_locations', $locationName);

// If not found, check for default home location
if (!$homeLocationData) {
    $defaultHome = getUserAttribute($database, $current_user_data['user_id'], 'tour_settings', 'default_home_location');
    if ($defaultHome && !empty($defaultHome['description'])) {
        $homeLocationData = $defaultHome;
    }
}

// Parse home location data
$homeaddress = '';
$home_lat = null;
$home_lng = null;

if ($homeLocationData && !empty($homeLocationData['description'])) {
    $locationJson = json_decode($homeLocationData['description'], true);
    if ($locationJson) {
        $homeaddress = $locationJson['address'] ?? '';
        $home_lat = $locationJson['lat'] ?? null;
        $home_lng = $locationJson['lng'] ?? null;
    }
}

// Fall back to session if no saved location
if (empty($homeaddress) && isset($_SESSION['tour_home_address'])) {
    $homeaddress = $_SESSION['tour_home_address'];
    $home_lat = $_SESSION['tour_home_lat'] ?? null;
    $home_lng = $_SESSION['tour_home_lng'] ?? null;
}

// Calculate distances for all businesses from home
foreach ($listofcompanies as &$company) {
    if (isset($company['data']) && $home_lat && $home_lng) {
        $businessLat = $company['data']['latitude'] ?? null;
        $businessLng = $company['data']['longitude'] ?? null;
        
        if ($businessLat && $businessLng) {
            $company['data']['distance_from_home'] = calculateDistance($home_lat, $home_lng, $businessLat, $businessLng);
            $company['data']['is_out_of_range'] = $company['data']['distance_from_home'] > 100;
        } else {
            $company['data']['distance_from_home'] = null;
            $company['data']['is_out_of_range'] = false;
        }
    }
}

// Page setup
$pagedata['pagetitle'] = $app->tagreplace('My Tour - {{bizname}}');
$additionalstyles = '
<link rel="stylesheet" href="/public/css/myaccount.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* Mobile-first responsive design */
:root {
    --primary-color: #1a73e8;
    --secondary-color: #f8f9fa;
    --border-color: #dadce0;
    --text-muted: #5f6368;
    --success-color: #34a853;
    --warning-color: #fbbc04;
    --danger-color: #ea4335;
    --out-of-range-bg: #f5f5f5;
    --out-of-range-text: #9e9e9e;
}

/* Base mobile styles */
.tour-container {
    padding: 10px;
    max-width: 100%;
}

/* Tab navigation - full width on mobile */
.nav-tabs {
    border-bottom: 2px solid var(--border-color);
    margin-bottom: 0;
    background: white;
    position: sticky;
    top: 0;
    z-index: 100;
}

.nav-tabs .nav-link {
    color: var(--text-muted);
    border: none;
    border-bottom: 3px solid transparent;
    padding: 12px 16px;
    font-weight: 500;
    text-align: center;
    flex: 1;
}

.nav-tabs .nav-link.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
    background: none;
}

.nav-tabs .nav-link:hover {
    border-color: transparent;
    color: var(--primary-color);
}

/* Tab content - full width on mobile */
.tab-content {
    padding: 0;
    background: white;
}

.tab-pane {
    padding: 15px 10px;
}

/* Business cards - mobile optimized */
.business-card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    margin-bottom: 12px;
    padding: 12px;
    position: relative;
    transition: all 0.2s ease;
}

.business-card.out-of-range {
    background: var(--out-of-range-bg);
    opacity: 0.7;
}

.business-card.out-of-range .business-name,
.business-card.out-of-range .business-address {
    color: var(--out-of-range-text);
}

.drag-handle {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    padding: 8px;
    color: var(--text-muted);
    cursor: grab;
}

.drag-handle:active {
    cursor: grabbing;
}

.business-info {
    margin-left: 30px;
}

.business-name {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 4px;
}

.business-address {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 4px;
}

.business-distance {
    font-size: 12px;
    color: var(--text-muted);
}

.badge {
    font-size: 11px;
    padding: 4px 8px;
    margin-left: 8px;
}

/* Home location card */
.home-card {
    background: var(--secondary-color);
    border: 2px solid var(--primary-color);
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 15px;
}

.home-card .home-icon {
    color: var(--primary-color);
    font-size: 20px;
    margin-right: 8px;
}

/* Buttons - mobile optimized */
.btn {
    padding: 10px 16px;
    font-size: 14px;
    border-radius: 4px;
    font-weight: 500;
    width: 100%;
    margin-bottom: 10px;
}

.btn-group-mobile {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.btn-group-mobile .btn {
    flex: 1;
    margin-bottom: 0;
}

/* Map container */
#map, #route-map {
    height: 400px;
    width: 100%;
    border-radius: 8px;
    margin-bottom: 15px;
}

/* Directions panel */
#directions-panel {
    background: var(--secondary-color);
    padding: 15px;
    border-radius: 8px;
    max-height: 600px;
    overflow-y: auto;
}

/* Stats badges */
.stats-container {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.stat-badge {
    background: var(--secondary-color);
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 14px;
    white-space: nowrap;
    cursor: pointer;
}

.stat-badge i {
    margin-right: 5px;
}

/* Location picker modal */
.location-list-item {
    padding: 12px;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: background 0.2s;
}

.location-list-item:hover,
.location-list-item.active {
    background: var(--secondary-color);
}

.location-marker {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 10px;
}

/* Desktop styles */
@media (min-width: 768px) {
    .tour-container {
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .tab-pane {
        padding: 20px;
    }
    
    .business-card {
        padding: 16px;
    }
    
    .btn {
        width: auto;
    }
    
    .btn-group-mobile {
        justify-content: flex-start;
    }
    
    .stats-container {
        justify-content: flex-start;
    }
    
    /* Two column layout for desktop */
    .desktop-layout {
        display: flex;
        gap: 20px;
    }
    
    .desktop-layout .left-column {
        flex: 1;
        max-width: 400px;
    }
    
    .desktop-layout .right-column {
        flex: 2;
    }
    
    #map, #route-map {
        height: 600px;
    }
}

/* Loading states */
.loading {
    text-align: center;
    padding: 40px;
    color: var(--text-muted);
}

.loading i {
    font-size: 24px;
    margin-bottom: 10px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Sortable placeholder */
.sortable-placeholder {
    background: #e3f2fd;
    border: 2px dashed var(--primary-color);
    height: 80px;
    margin-bottom: 12px;
    border-radius: 8px;
}

/* Print styles */
@media print {
    .nav-tabs,
    .btn,
    .btn-group-mobile,
    .modal {
        display: none !important;
    }
    
    .business-card {
        page-break-inside: avoid;
    }
}
</style>';

// Initialize iframe mode variable
$is_iframe_mode = isset($_GET['iframe']) && $_GET['iframe'] == '1';

// Page includes
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
echo '<div class="container">';
?>

<!-- Page Title and Stats (shown on all screen sizes) -->
<div class="row mb-3">
    <div class="col-12">
        <h2 class="mb-3">
            <i class="fas fa-route"></i> My Birthday Tour
            <small class="text-muted d-block d-md-inline-block ms-md-2" style="font-size: 0.6em;">
                <?php echo date('l, F j, Y', strtotime($date)); ?>
            </small>
        </h2>
        
        <!-- Stats badges -->
        <div class="stats-container">
            <span class="stat-badge" data-bs-toggle="modal" data-bs-target="#statsModal">
                <i class="fas fa-store"></i> 
                <span id="business-count"><?php echo count($listofcompanies); ?></span> businesses
            </span>
            <?php 
            $outOfRangeCount = 0;
            foreach ($listofcompanies as $company) {
                if (isset($company['data']['is_out_of_range']) && $company['data']['is_out_of_range']) {
                    $outOfRangeCount++;
                }
            }
            if ($outOfRangeCount > 0): ?>
            <span class="stat-badge bg-warning text-dark" data-bs-toggle="modal" data-bs-target="#outOfRangeModal">
                <i class="fas fa-exclamation-triangle"></i> 
                <?php echo $outOfRangeCount; ?> out of range
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Mobile Layout (shown on small/medium screens) -->
<div class="tour-container d-lg-none">
    <!-- Mobile tab navigation -->
    <ul class="nav nav-tabs d-flex" role="tablist">
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link active w-100" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab">
                <i class="fas fa-list d-block d-md-inline"></i>
                <span class="d-none d-md-inline ms-1">List</span>
            </button>
        </li>
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link w-100" id="map-tab" data-bs-toggle="tab" data-bs-target="#map-view" type="button" role="tab">
                <i class="fas fa-map-marked-alt d-block d-md-inline"></i>
                <span class="d-none d-md-inline ms-1">Map</span>
            </button>
        </li>
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link w-100" id="directions-tab" data-bs-toggle="tab" data-bs-target="#directions" type="button" role="tab">
                <i class="fas fa-directions d-block d-md-inline"></i>
                <span class="d-none d-md-inline ms-1">Directions</span>
            </button>
        </li>
    </ul>
    
    <!-- Tab content -->
    <div class="tab-content">
        <!-- List View -->
        <div class="tab-pane fade show active" id="list" role="tabpanel">
            <!-- Home location -->
            <div class="home-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <i class="fas fa-home home-icon"></i>
                        <strong>Starting Tour Location</strong>
                        <div class="business-address mt-1" data-location="<?php echo htmlspecialchars($homeaddress); ?>">
                            <?php echo !empty($homeaddress) ? htmlspecialchars($homeaddress) : 'No starting location set'; ?>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" onclick="openChangeLocationModal(event)">
                        <i class="fas fa-edit"></i>
                        <span class="d-none d-md-inline">Change</span>
                    </button>
                </div>
            </div>
            
            <!-- Business list -->
            <div id="sortable-container">
                <?php foreach ($listofcompanies as $index => $company): 
                    $businessData = $company['data'] ?? [];
                    $isOutOfRange = $businessData['is_out_of_range'] ?? false;
                    $distance = $businessData['distance_from_home'] ?? null;
                    $hasVerifiedLocation = $businessData['has_verified_location'] ?? false;
                    $isForcedLocation = $businessData['is_forced_location'] ?? false;
                ?>
                <div class="business-card sortable_item <?php echo $isOutOfRange ? 'out-of-range' : ''; ?>" 
                     data-company-id="<?php echo $company['company_id']; ?>"
                     data-location="<?php echo htmlspecialchars($businessData['address'] ?? ''); ?>"
                     data-lat="<?php echo $businessData['latitude'] ?? ''; ?>"
                     data-lng="<?php echo $businessData['longitude'] ?? ''; ?>">
                    
                    <i class="fas fa-grip-vertical drag-handle"></i>
                    
                    <div class="business-info">
                        <div class="business-name">
                            <?php echo htmlspecialchars($businessData['business_name'] ?? 'Unknown Business'); ?>
                            <?php if ($isOutOfRange): ?>
                                <span class="badge bg-warning text-dark">Out of Range</span>
                            <?php endif; ?>
                            <?php if ($isForcedLocation): ?>
                                <span class="badge bg-info text-white">Pinned</span>
                            <?php endif; ?>
                        </div>
                        <div class="business-address">
                            <?php 
                            $fullAddress = trim(($businessData['address'] ?? '') . ', ' . 
                                              ($businessData['city'] ?? '') . ', ' . 
                                              ($businessData['state'] ?? '') . ' ' . 
                                              ($businessData['zip_code'] ?? ''));
                            $fullAddress = rtrim($fullAddress, ', ');
                            echo htmlspecialchars($fullAddress);
                            ?>
                        </div>
                        <?php if ($distance !== null): ?>
                        <div class="business-distance">
                            <i class="fas fa-route"></i> <?php echo $distance; ?> miles from start
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-2">
                            <button class="btn btn-sm btn-outline-secondary change-location-btn" 
                                    data-company-id="<?php echo $company['company_id']; ?>"
                                    data-company-name="<?php echo htmlspecialchars($businessData['business_name'] ?? ''); ?>">
                                <i class="fas fa-map-pin"></i> Change Location
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Action buttons -->
            <div class="btn-group-mobile mt-3">
                <button id="draw_map" class="btn btn-primary" onclick="DrawNewMap();" style="display:none;">
                    <i class="fas fa-sync"></i> Update Route
                </button>
                <button class="btn btn-success" onclick="sendToPhone()">
                    <i class="fas fa-mobile-alt"></i> Send to Phone
                </button>
                <button class="btn btn-secondary" onclick="showTestingLinks()">
                    <i class="fas fa-link"></i> Test Links
                </button>
            </div>
        </div>
        
        <!-- Map View -->
        <div class="tab-pane fade" id="map-view" role="tabpanel">
            <div id="map"></div>
            <div class="btn-group-mobile">
                <button class="btn btn-primary" onclick="recenterMap()">
                    <i class="fas fa-crosshairs"></i> Re-center Map
                </button>
            </div>
        </div>
        
        <!-- Directions View -->
        <div class="tab-pane fade" id="directions" role="tabpanel">
            <div id="route-map"></div>
            <div id="directions-panel">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Loading directions...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Desktop Layout (shown on large/xl screens) -->
<div class="d-none d-lg-block">
    <div class="row">
        <div class="col-lg-4">
            <!-- Left Panel with List and Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Tour Businesses</h5>
                </div>
                <div class="card-body">
                    <!-- Home Location Card -->
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">
                                    <i class="fas fa-home"></i> Starting Tour Location
                                </h6>
                                <p class="mb-0 small" data-location="<?php echo htmlspecialchars($homeaddress); ?>">
                                    <?php echo !empty($homeaddress) ? htmlspecialchars($homeaddress) : 'No starting location set'; ?>
                                </p>
                            </div>
                            <button class="btn btn-sm btn-outline-primary" onclick="openChangeLocationModal(event)">
                                <i class="fas fa-edit"></i> Change
                            </button>
                        </div>
                    </div>

                    <!-- Drag to Reorder Note -->
                    <div class="alert alert-light text-center">
                        <i class="fas fa-grip-vertical"></i> Drag businesses to reorder
                    </div>

                    <!-- Business List -->
                    <div id="sortable-container-desktop">
                        <?php foreach ($listofcompanies as $index => $company): 
                            $businessData = $company['data'] ?? [];
                            $isOutOfRange = $businessData['is_out_of_range'] ?? false;
                            $distance = $businessData['distance_from_home'] ?? null;
                            $hasVerifiedLocation = $businessData['has_verified_location'] ?? false;
                            $isForcedLocation = $businessData['is_forced_location'] ?? false;
                        ?>
                        <div class="card mb-2 sortable_item <?php echo $isOutOfRange ? 'out-of-range' : ''; ?>" 
                             data-company-id="<?php echo $company['company_id']; ?>"
                             data-location="<?php echo htmlspecialchars($businessData['address'] ?? ''); ?>"
                             data-lat="<?php echo $businessData['latitude'] ?? ''; ?>"
                             data-lng="<?php echo $businessData['longitude'] ?? ''; ?>">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-grip-vertical drag-handle me-2" style="cursor: move;"></i>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">
                                            <?php echo htmlspecialchars($businessData['business_name'] ?? 'Unknown Business'); ?>
                                            <?php if ($isOutOfRange): ?>
                                                <span class="badge bg-warning">Out of Range</span>
                                            <?php endif; ?>
                                            <?php if ($isForcedLocation): ?>
                                                <i class="fas fa-thumbtack text-primary" title="Pinned location"></i>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php 
                                            $fullAddress = trim(($businessData['address'] ?? '') . ', ' . 
                                                              ($businessData['city'] ?? '') . ', ' . 
                                                              ($businessData['state'] ?? '') . ' ' . 
                                                              ($businessData['zip_code'] ?? ''));
                                            $fullAddress = rtrim($fullAddress, ', ');
                                            echo htmlspecialchars($fullAddress);
                                            ?>
                                        </small>
                                        <?php if ($distance !== null): ?>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-route"></i> <?php echo $distance; ?> miles
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary change-location-btn" 
                                            data-company-id="<?php echo $company['company_id']; ?>"
                                            data-company-name="<?php echo htmlspecialchars($businessData['business_name'] ?? ''); ?>">
                                        <i class="fas fa-map-pin"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-3">
                        <button id="draw_map_desktop" class="btn btn-primary w-100 mb-2" onclick="DrawNewMap();">
                            <i class="fas fa-route"></i> Update Route
                        </button>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-outline-primary w-100" onclick="window.print();">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-primary w-100" onclick="document.getElementById('send-to-phone-form').style.display='block';">
                                    <i class="fas fa-mobile-alt"></i> Send to Phone
                                </button>
                            </div>
                        </div>

                        <!-- Send to Phone Form -->
                        <div id="send-to-phone-form" class="mt-2" style="display:none;">
                            <div class="alert alert-info">
                                <h6>Send to Phone</h6>
                                <p class="mb-2 small">Phone: <?php echo htmlspecialchars($current_user_data['phone'] ?? 'No phone'); ?></p>
                                <button class="btn btn-sm btn-success send-to-phone-btn w-100">
                                    <i class="fas fa-sms"></i> Send Navigation Link
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Right Panel with Map and Directions -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#desktop-map-view" type="button">
                                <i class="fas fa-map-marked-alt"></i> Map
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#desktop-directions" type="button">
                                <i class="fas fa-directions"></i> Directions
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="desktop-map-view">
                            <div id="map-desktop" style="height: 600px;"></div>
                        </div>
                        <div class="tab-pane fade" id="desktop-directions">
                            <div class="row">
                                <div class="col-12">
                                    <div id="route-map-desktop" style="height: 400px;"></div>
                                </div>
                                <div class="col-12">
                                    <div id="directions-panel-desktop" style="height: 400px; overflow-y: auto;" class="p-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Stats Modal -->
<div class="modal fade" id="statsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tour Statistics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Total Businesses:</strong> <?php echo count($listofcompanies); ?></p>
                <p><strong>In Range (≤100 miles):</strong> <?php echo count($listofcompanies) - $outOfRangeCount; ?></p>
                <p><strong>Out of Range:</strong> <?php echo $outOfRangeCount; ?></p>
                <p><strong>Tour Date:</strong> <?php echo date('F j, Y', strtotime($date)); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Out of Range Modal -->
<div class="modal fade" id="outOfRangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Out of Range Businesses</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">These businesses are more than 100 miles from your starting location and will be excluded from navigation:</p>
                <ul>
                <?php foreach ($listofcompanies as $company): 
                    if (isset($company['data']['is_out_of_range']) && $company['data']['is_out_of_range']): ?>
                    <li><?php echo htmlspecialchars($company['data']['business_name'] ?? 'Unknown'); ?> 
                        (<?php echo $company['data']['distance_from_home'] ?? 'Unknown'; ?> miles)</li>
                <?php endif; 
                endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Change Home Location Modal -->
<div class="modal fade" id="changeLocationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Starting Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="home-address-input" class="form-label">Enter your starting tour address</label>
                    <input type="text" class="form-control" id="home-address-input" 
                           placeholder="Enter address..." 
                           value="<?php echo htmlspecialchars($homeaddress); ?>">
                    <div class="form-text">Start typing and select from the suggestions</div>
                </div>
                <div id="home-location-map" style="height: 400px; width: 100%; display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-home-location">Update Location</button>
            </div>
        </div>
    </div>
</div>

<!-- Location Picker Modal -->
<div class="modal fade" id="locationPickerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Business Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Search Radius</label>
                            <select id="radius-select" class="form-select">
                                <option value="10">10 miles</option>
                                <option value="25" selected>25 miles</option>
                                <option value="50">50 miles</option>
                                <option value="100">100 miles</option>
                            </select>
                        </div>
                        <div id="location-results" style="max-height: 400px; overflow-y: auto;">
                            <!-- Results will be populated here -->
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div id="location-picker-map" style="height: 500px; width: 100%;"></div>
                    </div>
                </div>
                <div id="force-location-container" class="mt-3" style="display: none;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="force-location-check">
                        <label class="form-check-label" for="force-location-check">
                            <i class="fas fa-thumbtack"></i> Pin this location (use even if system finds a closer match)
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-location" disabled>Use Selected Location</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
<script>
// Google Maps configuration
var mapId = '<?php echo $sitesettings['GOOGLEAPI']['mapid'] ?? '9cd54b1058579fe87b380337'; ?>';

// Global variables
var map, routeMap;
var directionsService, directionsRenderer;
var locations = [];
var markers = [];
var locationPickerMap;
var locationMarkers = [];
var selectedLocation = null;
var currentCompanyId = null;

// Initialize locations array from PHP data
<?php foreach ($listofcompanies as $index => $company): 
    $businessData = $company['data'] ?? [];
?>
locations.push({
    name: <?php echo json_encode($businessData['business_name'] ?? 'Unknown Business'); ?>,
    address: <?php echo json_encode(trim(($businessData['address'] ?? '') . ', ' . 
                                       ($businessData['city'] ?? '') . ', ' . 
                                       ($businessData['state'] ?? '') . ' ' . 
                                       ($businessData['zip_code'] ?? ''))); ?>,
    lat: <?php echo !empty($businessData['latitude']) ? $businessData['latitude'] : 'null'; ?>,
    lng: <?php echo !empty($businessData['longitude']) ? $businessData['longitude'] : 'null'; ?>,
    company_id: <?php echo json_encode($company['company_id']); ?>,
    type: 'business',
    isOutOfRange: <?php echo ($businessData['is_out_of_range'] ?? false) ? 'true' : 'false'; ?>,
    distance: <?php echo $businessData['distance_from_home'] ?? 'null'; ?>
});
<?php endforeach; ?>

// Add home location at the beginning
locations.unshift({
    name: 'Starting Tour Location',
    address: <?php echo json_encode($homeaddress); ?>,
    lat: <?php echo !empty($home_lat) ? $home_lat : 'null'; ?>,
    lng: <?php echo !empty($home_lng) ? $home_lng : 'null'; ?>,
    type: 'home'
});

// Sortable functionality for both mobile and desktop
$(function() {
    // Initialize sortable for both containers
    $("#sortable-container, #sortable-container-desktop").sortable({
        handle: ".drag-handle",
        placeholder: "sortable-placeholder",
        connectWith: "#sortable-container, #sortable-container-desktop",
        update: function(event, ui) {
            // Show update button when order changes
            var drawBtn = document.getElementById('draw_map');
            var drawBtnDesktop = document.getElementById('draw_map_desktop');
            if (drawBtn) drawBtn.style.display = 'inline-block';
            if (drawBtnDesktop) drawBtnDesktop.style.display = 'inline-block';
            
            // Get new order from the updated container
            var order = [];
            $(this).find('.sortable_item').each(function() {
                order.push($(this).data('company-id'));
            });
            
            // Sync the order to the other container
            var otherContainer = this.id === 'sortable-container' ? '#sortable-container-desktop' : '#sortable-container';
            if ($(otherContainer).length) {
                $(otherContainer).empty();
                $(this).find('.sortable_item').each(function() {
                    $(otherContainer).append($(this).clone());
                });
            }
            
            // Update order in database
            $.ajax({
                url: window.location.pathname,
                method: 'POST',
                data: {
                    action: 'reorder_tour',
                    order: order,
                    tour_date: '<?php echo $date; ?>'
                }
            });
        }
    });
});

// Tab change handlers for mobile
var mapTab = document.getElementById('map-tab');
if (mapTab) {
    mapTab.addEventListener('shown.bs.tab', function() {
        if (!mapMobile) {
            initMapInstance('map', 'mobile');
        } else {
            google.maps.event.trigger(mapMobile, 'resize');
            if (locations.length > 0) {
                fitMapToBounds(mapMobile);
            }
        }
    });
}

var directionsTab = document.getElementById('directions-tab');
if (directionsTab) {
    directionsTab.addEventListener('shown.bs.tab', function() {
        loadDirections('mobile');
    });
}

// Initialize desktop maps when tabs are shown
$(document).ready(function() {
    // Desktop map tab handler
    $('[data-bs-target="#desktop-map-view"]').on('shown.bs.tab', function() {
        if (!mapDesktop) {
            initMapInstance('map-desktop', 'desktop');
        } else {
            google.maps.event.trigger(mapDesktop, 'resize');
            fitMapToBounds(mapDesktop);
        }
    });
    
    // Desktop directions tab handler
    $('[data-bs-target="#desktop-directions"]').on('shown.bs.tab', function() {
        loadDirections('desktop');
    });
    
    // Initialize desktop map if visible on load
    if ($('#desktop-map-view.active').length) {
        initMapInstance('map-desktop', 'desktop');
    }
});

// Initialize Google Maps
function initMap() {
    // This is called by Google Maps API callback
    console.log('Google Maps API loaded');
    
    // Check if we need to initialize map immediately
    if (document.getElementById('map-tab').classList.contains('active')) {
        initializeMap();
    }
}

// Global variables for maps
var mapMobile, mapDesktop, mapRouteMobile, mapRouteDesktop;

function initializeMap() {
    // Initialize mobile map if visible
    var mapElementMobile = document.getElementById('map');
    if (mapElementMobile && mapElementMobile.offsetParent !== null) {
        initMapInstance('map', 'mobile');
    }
    
    // Initialize desktop map if visible
    var mapElementDesktop = document.getElementById('map-desktop');
    if (mapElementDesktop && mapElementDesktop.offsetParent !== null) {
        initMapInstance('map-desktop', 'desktop');
    }
}

function initMapInstance(elementId, type) {
    var mapElement = document.getElementById(elementId);
    if (!mapElement) return;
    
    var centerLat = locations[0] && locations[0].lat ? locations[0].lat : 39.7392;
    var centerLng = locations[0] && locations[0].lng ? locations[0].lng : -104.9903;
    
    var mapInstance = new google.maps.Map(mapElement, {
        center: {lat: centerLat, lng: centerLng},
        zoom: 12,
        mapId: mapId
    });
    
    // Store map instance
    if (type === 'mobile') {
        mapMobile = mapInstance;
        map = mapInstance; // For backward compatibility
    } else {
        mapDesktop = mapInstance;
    }
    
    // Add markers
    addMarkersToMap(mapInstance);
}

function addMarkersToMap(mapInstance) {
    if (!mapInstance) mapInstance = map;
    
    // Clear existing markers
    markers.forEach(marker => marker.setMap(null));
    markers = [];
    
    locations.forEach((location, index) => {
        if (location.lat && location.lng) {
            var pinElement = document.createElement('div');
            
            if (location.type === 'home') {
                // Home marker
                pinElement.style.backgroundColor = '#4285F4';
                pinElement.style.width = '30px';
                pinElement.style.height = '30px';
                pinElement.style.borderRadius = '50%';
                pinElement.style.border = '3px solid white';
                pinElement.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
                pinElement.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:16px;">🏠</div>';
            } else {
                // Business marker
                var bgColor = location.isOutOfRange ? '#cccccc' : '#EA4335';
                pinElement.style.backgroundColor = bgColor;
                pinElement.style.width = '30px';
                pinElement.style.height = '30px';
                pinElement.style.borderRadius = '50%';
                pinElement.style.border = '2px solid white';
                pinElement.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
                pinElement.style.display = 'flex';
                pinElement.style.alignItems = 'center';
                pinElement.style.justifyContent = 'center';
                pinElement.style.fontWeight = 'bold';
                pinElement.style.color = 'white';
                pinElement.textContent = index.toString();
            }
            
            var marker = new google.maps.marker.AdvancedMarkerElement({
                position: {lat: parseFloat(location.lat), lng: parseFloat(location.lng)},
                map: mapInstance,
                title: location.name,
                content: pinElement
            });
            
            markers.push(marker);
        }
    });
    
    fitMapToBounds(mapInstance);
}

function fitMapToBounds(mapInstance) {
    if (!mapInstance) mapInstance = map;
    if (!mapInstance || markers.length === 0) return;
    
    var bounds = new google.maps.LatLngBounds();
    markers.forEach(marker => {
        bounds.extend(marker.position);
    });
    mapInstance.fitBounds(bounds);
}

function recenterMap() {
    if (map && locations.length > 0 && locations[0].lat && locations[0].lng) {
        map.setCenter({lat: parseFloat(locations[0].lat), lng: parseFloat(locations[0].lng)});
        map.setZoom(12);
    }
}

// Directions functionality
function loadDirections(type) {
    console.log('Loading directions for ' + type + '...');
    
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        console.error('Google Maps not loaded yet!');
        return;
    }
    
    var routeMapElement, directionsPanelElement, routeMapInstance;
    
    if (type === 'desktop') {
        routeMapElement = document.getElementById('route-map-desktop');
        directionsPanelElement = document.getElementById('directions-panel-desktop');
        routeMapInstance = mapRouteDesktop;
    } else {
        routeMapElement = document.getElementById('route-map');
        directionsPanelElement = document.getElementById('directions-panel');
        routeMapInstance = mapRouteMobile || routeMap;
    }
    
    // Initialize route map if needed
    if (!routeMapInstance && routeMapElement) {
        routeMapInstance = new google.maps.Map(routeMapElement, {
            zoom: 13,
            center: {lat: 39.7392, lng: -104.9903},
            mapId: mapId
        });
        
        if (type === 'desktop') {
            mapRouteDesktop = routeMapInstance;
        } else {
            mapRouteMobile = routeMapInstance;
            routeMap = routeMapInstance; // Backward compatibility
        }
        
        if (!directionsService) {
            directionsService = new google.maps.DirectionsService();
        }
        
        directionsRenderer = new google.maps.DirectionsRenderer({
            map: routeMapInstance,
            suppressMarkers: true,
            panel: directionsPanelElement
        });
    }
    
    // Check if we have enough locations
    if (locations.length < 2) {
        if (directionsPanelElement) {
            directionsPanelElement.innerHTML = '<div class="alert alert-warning">No businesses found for this date. Add businesses to your tour first.</div>';
        }
        return;
    }
    
    // Create waypoints from businesses (excluding out-of-range ones)
    var waypoints = [];
    var includedLocationIndices = [0]; // Always include home
    
    for (var i = 1; i < locations.length; i++) {
        if (!locations[i].isOutOfRange) {
            waypoints.push({
                location: locations[i].address,
                stopover: true
            });
            includedLocationIndices.push(i);
        }
    }
    
    // Check if we have any waypoints after filtering
    if (waypoints.length === 0) {
        if (directionsPanelElement) {
            directionsPanelElement.innerHTML = '<div class="alert alert-warning">All businesses are more than 100 miles away. No route can be calculated.</div>';
        }
        return;
    }
    
    // Calculate route - return to home at the end
    var request = {
        origin: locations[0].address,
        destination: locations[0].address, // Return to home
        waypoints: waypoints,
        optimizeWaypoints: true,
        travelMode: 'DRIVING'
    };
    
    directionsService.route(request, function(response, status) {
        if (status === 'OK') {
            directionsRenderer.setDirections(response);
            
            // Get the optimized waypoint order
            var waypointOrder = response.routes[0].waypoint_order;
            
            // Reorder locations based on optimization
            var optimizedLocations = [locations[0]]; // Start with home
            waypointOrder.forEach(function(waypointIndex) {
                var originalLocationIndex = includedLocationIndices[waypointIndex + 1];
                optimizedLocations.push(locations[originalLocationIndex]);
            });
            
            // Create custom directions panel
            createCustomDirectionsPanel(response, optimizedLocations, directionsPanelElement);
            
            // Add custom markers
            addRouteMarkers(optimizedLocations, routeMapInstance);
        } else {
            if (directionsPanelElement) {
                directionsPanelElement.innerHTML = '<div class="alert alert-danger">Directions request failed: ' + status + '</div>';
            }
        }
    });
}

function createCustomDirectionsPanel(response, locations, panelElement) {
    if (!panelElement) return;
    var panel = panelElement;
    var html = '<div class="directions-content">';
    
    // Add each leg of the journey
    response.routes[0].legs.forEach(function(leg, index) {
        var fromLocation = locations[index];
        var toLocation = locations[index + 1];
        
        // For circular route, last leg returns to start
        if (!toLocation && index === response.routes[0].legs.length - 1) {
            toLocation = locations[0];
        }
        
        html += '<div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">';
        
        // From section
        html += '<div style="margin-bottom: 10px;">';
        html += '<div style="font-weight: bold; color: #1a73e8; font-size: 16px;">';
        html += '<span style="background: #1a73e8; color: white; padding: 2px 8px; border-radius: 50%; margin-right: 8px;">' + (index + 1) + '</span>';
        html += fromLocation.name;
        html += '</div>';
        html += '<div style="color: #5f6368; font-size: 14px; margin-left: 35px;">' + leg.start_address.replace(/, USA$/, '') + '</div>';
        html += '</div>';
        
        // Distance and time
        html += '<div style="margin-left: 35px; padding: 10px; background: white; border-radius: 4px;">';
        html += '<span style="color: #1a73e8; font-weight: bold;">' + leg.distance.text + '</span>';
        html += ' · ';
        html += '<span style="color: #5f6368;">' + leg.duration.text + '</span>';
        html += '</div>';
        
        html += '</div>';
    });
    
    // Add total summary
    var totalDistance = response.routes[0].legs.reduce(function(sum, leg) { 
        return sum + leg.distance.value; 
    }, 0) / 1000;
    var totalDuration = Math.round(response.routes[0].legs.reduce(function(sum, leg) { 
        return sum + leg.duration.value; 
    }, 0) / 60);
    
    html += '<div style="margin-top: 20px; padding: 15px; background: #e8f0fe; border-radius: 8px;">';
    html += '<h5 style="margin: 0 0 10px 0; color: #1a73e8;">Tour Summary</h5>';
    html += '<div>Total Distance: <strong>' + totalDistance.toFixed(1) + ' km</strong></div>';
    html += '<div>Total Time: <strong>' + totalDuration + ' minutes</strong></div>';
    html += '<div>Stops: <strong>' + locations.length + ' locations</strong></div>';
    html += '</div>';
    html += '</div>';
    
    panel.innerHTML = html;
}

function addRouteMarkers(optimizedLocations, mapInstance) {
    if (!mapInstance) mapInstance = routeMap;
    
    optimizedLocations.forEach(function(location, index) {
        if (location.lat && location.lng) {
            var pinElement = document.createElement('div');
            
            if (location.type === 'home' && index === 0) {
                // Start marker
                pinElement.style.backgroundColor = '#34a853';
                pinElement.innerHTML = '<div style="color:white;font-weight:bold;text-align:center;line-height:30px;">START</div>';
                pinElement.style.padding = '5px 10px';
                pinElement.style.borderRadius = '15px';
            } else {
                // Stop marker
                pinElement.style.backgroundColor = '#ea4335';
                pinElement.style.width = '30px';
                pinElement.style.height = '30px';
                pinElement.style.borderRadius = '50%';
                pinElement.style.display = 'flex';
                pinElement.style.alignItems = 'center';
                pinElement.style.justifyContent = 'center';
                pinElement.style.color = 'white';
                pinElement.style.fontWeight = 'bold';
                pinElement.textContent = index.toString();
            }
            
            pinElement.style.border = '2px solid white';
            pinElement.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
            
            new google.maps.marker.AdvancedMarkerElement({
                position: {lat: parseFloat(location.lat), lng: parseFloat(location.lng)},
                map: mapInstance,
                title: location.name,
                content: pinElement
            });
        }
    });
}

// Update map after changes
function DrawNewMap() {
    // Update locations array from current DOM
    locations = [{
        name: 'Starting Tour Location',
        address: document.querySelector('[data-location]').getAttribute('data-location'),
        lat: <?php echo !empty($home_lat) ? $home_lat : 'null'; ?>,
        lng: <?php echo !empty($home_lng) ? $home_lng : 'null'; ?>,
        type: 'home'
    }];
    
    $('.sortable_item').each(function() {
        var $this = $(this);
        var businessName = $this.find('.business-name').text().trim();
        // Remove badges from business name
        businessName = businessName.replace(/Out of Range|Pinned/g, '').trim();
        
        locations.push({
            name: businessName,
            address: $this.attr('data-location'),
            lat: $this.attr('data-lat') || null,
            lng: $this.attr('data-lng') || null,
            company_id: $this.attr('data-company-id'),
            type: 'business',
            isOutOfRange: $this.hasClass('out-of-range'),
            distance: parseFloat($this.find('.business-distance').text().match(/[\d.]+/)?.[0]) || null
        });
    });
    
    // Geocode any missing addresses
    geocodeMissingAddresses();
}

// Geocoding functionality
function geocodeMissingAddresses() {
    var needsGeocoding = locations.filter(function(loc) {
        return (!loc.lat || !loc.lng) && loc.address;
    });
    
    if (needsGeocoding.length === 0) {
        // All addresses are geocoded, update both maps
        if (mapMobile) {
            addMarkersToMap(mapMobile);
        }
        if (mapDesktop) {
            addMarkersToMap(mapDesktop);
        }
        return;
    }
    
    // Geocode addresses sequentially
    var index = 0;
    function geocodeNext() {
        if (index >= needsGeocoding.length) {
            // Done geocoding, update both maps
            if (mapMobile) {
                addMarkersToMap(mapMobile);
            }
            if (mapDesktop) {
                addMarkersToMap(mapDesktop);
            }
            return;
        }
        
        var location = needsGeocoding[index];
        
        $.ajax({
            url: window.location.pathname,
            method: 'POST',
            data: {
                action: 'geocode_address',
                address: location.address
            },
            success: function(response) {
                if (response.success) {
                    // Update location in array
                    for (var i = 0; i < locations.length; i++) {
                        if (locations[i].address === location.address && locations[i].name === location.name) {
                            locations[i].lat = response.lat;
                            locations[i].lng = response.lng;
                            
                            // Update DOM
                            var selector = location.type === 'home' ? '.home-card' : 
                                         '[data-company-id="' + location.company_id + '"]';
                            $(selector).attr('data-lat', response.lat);
                            $(selector).attr('data-lng', response.lng);
                            
                            // Save to database if it's a business
                            if (location.type === 'business' && location.company_id) {
                                saveBusinessLocation(location.company_id, location.name, response.formatted_address, response.lat, response.lng);
                            }
                            
                            break;
                        }
                    }
                }
                
                index++;
                setTimeout(geocodeNext, 500); // Delay to avoid rate limits
            },
            error: function() {
                index++;
                setTimeout(geocodeNext, 500);
            }
        });
    }
    
    geocodeNext();
}

function saveBusinessLocation(companyId, companyName, address, lat, lng) {
    $.ajax({
        url: window.location.pathname,
        method: 'POST',
        data: {
            action: 'save_business_location',
            company_id: companyId,
            company_name: companyName,
            address: address,
            lat: lat,
            lng: lng
        }
    });
}

// Change location functionality
$(document).on('click', '.change-location-btn', function() {
    currentCompanyId = $(this).data('company-id');
    var companyName = $(this).data('company-name');
    
    $('#locationPickerModal .modal-title').text('Select Location for ' + companyName);
    $('#locationPickerModal').modal('show');
});

$('#locationPickerModal').on('shown.bs.modal', function() {
    if (!locationPickerMap) {
        var homeLat = locations[0].lat || 39.7392;
        var homeLng = locations[0].lng || -104.9903;
        
        locationPickerMap = new google.maps.Map(document.getElementById('location-picker-map'), {
            center: {lat: parseFloat(homeLat), lng: parseFloat(homeLng)},
            zoom: 10,
            mapId: mapId
        });
        
        // Add home marker
        var homePin = document.createElement('div');
        homePin.style.backgroundColor = '#4285F4';
        homePin.style.width = '30px';
        homePin.style.height = '30px';
        homePin.style.borderRadius = '50%';
        homePin.style.border = '3px solid white';
        homePin.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
        homePin.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:16px;">🏠</div>';
        
        new google.maps.marker.AdvancedMarkerElement({
            position: {lat: parseFloat(homeLat), lng: parseFloat(homeLng)},
            map: locationPickerMap,
            title: 'Starting Location',
            content: homePin
        });
    }
    
    searchBusinessLocations();
});

function searchBusinessLocations() {
    var homeLat = locations[0].lat ? parseFloat(locations[0].lat) : 39.7392;
    var homeLng = locations[0].lng ? parseFloat(locations[0].lng) : -104.9903;
    var radius = document.getElementById('radius-select').value || 25;
    
    document.getElementById('location-results').innerHTML = '<div class="text-center p-3">Loading locations...</div>';
    
    $.ajax({
        url: window.location.pathname,
        method: 'POST',
        data: {
            action: 'search_business_locations',
            company_id: currentCompanyId,
            radius: radius,
            home_lat: homeLat,
            home_lng: homeLng
        },
        dataType: 'json'
    })
    .done(function(data) {
        if (data.success && data.locations && data.locations.length > 0) {
            displayLocationResults(data.locations);
        } else {
            document.getElementById('location-results').innerHTML = '<div class="alert alert-warning">No locations found</div>';
        }
    })
    .fail(function(xhr, status, error) {
        document.getElementById('location-results').innerHTML = '<div class="alert alert-danger">Error loading locations</div>';
    });
}

function displayLocationResults(locations) {
    var resultsDiv = document.getElementById('location-results');
    resultsDiv.innerHTML = '';
    
    // Clear existing location markers
    locationMarkers.forEach(marker => {
        if (marker.getTitle() !== 'Starting Location') {
            marker.setMap(null);
        }
    });
    locationMarkers = [];
    
    var markerColors = ['#EA4335', '#FBBC04', '#34A853', '#4285F4', '#9C27B0'];
    var bounds = new google.maps.LatLngBounds();
    
    locations.forEach(function(location, index) {
        var color = markerColors[index % markerColors.length];
        
        var item = document.createElement('div');
        item.className = 'location-list-item';
        item.innerHTML = '<div class="d-flex align-items-center">' +
                        '<span class="location-marker" style="background-color: ' + color + '"></span>' +
                        '<div>' +
                        '<strong>' + location.full_address + '</strong><br>' +
                        '<small class="text-muted">' + location.distance + ' miles away</small>' +
                        '</div>' +
                        '</div>';
        
        item.onclick = function() {
            selectLocation(location, index);
            document.querySelectorAll('.location-list-item').forEach(el => el.classList.remove('active'));
            this.classList.add('active');
        };
        
        resultsDiv.appendChild(item);
        
        if (location.latitude && location.longitude) {
            var position = {lat: parseFloat(location.latitude), lng: parseFloat(location.longitude)};
            
            var locationPin = document.createElement('div');
            locationPin.style.backgroundColor = color;
            locationPin.style.width = '24px';
            locationPin.style.height = '24px';
            locationPin.style.borderRadius = '50%';
            locationPin.style.border = '2px solid white';
            locationPin.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
            
            var marker = new google.maps.marker.AdvancedMarkerElement({
                position: position,
                map: locationPickerMap,
                title: location.full_address,
                content: locationPin
            });
            
            bounds.extend(position);
            locationMarkers.push(marker);
            
            marker.addListener('click', function() {
                selectLocation(location, index);
                document.querySelectorAll('.location-list-item')[index].click();
            });
        }
    });
    
    locationPickerMap.fitBounds(bounds);
}

function selectLocation(location, index) {
    selectedLocation = {
        location_id: location.location_id,
        address: location.full_address,
        lat: parseFloat(location.latitude),
        lng: parseFloat(location.longitude)
    };
    
    document.getElementById('confirm-location').disabled = false;
    document.getElementById('force-location-container').style.display = 'block';
    
    locationPickerMap.setCenter({lat: selectedLocation.lat, lng: selectedLocation.lng});
    locationPickerMap.setZoom(15);
}

$(document).on('change', '#radius-select', function() {
    searchBusinessLocations();
});

$(document).on('click', '#confirm-location', function() {
    if (selectedLocation && currentCompanyId) {
        var forceLocation = document.getElementById('force-location-check').checked;
        
        fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=update_tour_location' +
                  '&company_id=' + encodeURIComponent(currentCompanyId) +
                  '&location_id=' + selectedLocation.location_id +
                  '&force_location=' + (forceLocation ? '1' : '0') +
                  '&tour_date=<?php echo $date; ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the display
                var businessElement = document.querySelector('.sortable_item[data-company-id="' + currentCompanyId + '"]');
                if (businessElement) {
                    businessElement.setAttribute('data-location', selectedLocation.address);
                    businessElement.setAttribute('data-lat', selectedLocation.lat);
                    businessElement.setAttribute('data-lng', selectedLocation.lng);
                    
                    var addressDiv = businessElement.querySelector('.business-address');
                    if (addressDiv) {
                        addressDiv.textContent = selectedLocation.address;
                    }
                    
                    // Update locations array
                    for (var i = 0; i < locations.length; i++) {
                        if (locations[i].company_id == currentCompanyId) {
                            locations[i].address = selectedLocation.address;
                            locations[i].lat = selectedLocation.lat;
                            locations[i].lng = selectedLocation.lng;
                            break;
                        }
                    }
                    
                    // Show update button
                    document.getElementById('draw_map').style.display = 'inline-block';
                }
                
                $('#locationPickerModal').modal('hide');
                selectedLocation = null;
                currentCompanyId = null;
            }
        });
    }
});

// Home location change functionality
var homeAutocomplete;
var selectedHomeLocation = null;
var homeLocationMap = null;
var homeLocationMarker = null;

document.getElementById('changeLocationModal').addEventListener('shown.bs.modal', function() {
    var addressInput = document.getElementById('home-address-input');
    addressInput.focus();
    addressInput.select();
    
    if (!homeAutocomplete) {
        homeAutocomplete = new google.maps.places.Autocomplete(
            addressInput,
            { types: ['address'] }
        );
        
        homeAutocomplete.addListener('place_changed', function() {
            var place = homeAutocomplete.getPlace();
            
            if (place.geometry) {
                var cleanAddress = place.formatted_address.replace(/, USA$/, '').replace(/, United States$/, '');
                
                selectedHomeLocation = {
                    address: cleanAddress,
                    lat: place.geometry.location.lat(),
                    lng: place.geometry.location.lng()
                };
                
                document.getElementById('home-location-map').style.display = 'block';
                
                if (!homeLocationMap) {
                    homeLocationMap = new google.maps.Map(document.getElementById('home-location-map'), {
                        center: place.geometry.location,
                        zoom: 15,
                        mapId: mapId
                    });
                }
                
                homeLocationMap.setCenter(place.geometry.location);
                
                if (homeLocationMarker) {
                    homeLocationMarker.map = null;
                }
                
                var homePin = document.createElement('div');
                homePin.style.backgroundColor = '#4285F4';
                homePin.style.width = '30px';
                homePin.style.height = '30px';
                homePin.style.borderRadius = '50%';
                homePin.style.border = '3px solid white';
                homePin.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
                homePin.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:16px;">🏠</div>';
                
                homeLocationMarker = new google.maps.marker.AdvancedMarkerElement({
                    position: place.geometry.location,
                    map: homeLocationMap,
                    title: 'Home Location',
                    content: homePin
                });
            }
        });
    }
});

document.getElementById('confirm-home-location').addEventListener('click', function() {
    if (selectedHomeLocation) {
        fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=update_home_location&address=' + encodeURIComponent(selectedHomeLocation.address) +
                  '&lat=' + selectedHomeLocation.lat + '&lng=' + selectedHomeLocation.lng +
                  '&tour_date=' + encodeURIComponent('<?php echo $date; ?>')
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the display
                document.querySelector('[data-location]').setAttribute('data-location', selectedHomeLocation.address);
                document.querySelector('.home-card .business-address').textContent = selectedHomeLocation.address;
                
                // Update locations array
                locations[0].address = selectedHomeLocation.address;
                locations[0].lat = selectedHomeLocation.lat;
                locations[0].lng = selectedHomeLocation.lng;
                
                // Mark businesses for re-geocoding
                locations.forEach(function(location, index) {
                    if (location.type === 'business' && index > 0) {
                        location.needsGeocoding = true;
                        location.lat = null;
                        location.lng = null;
                    }
                });
                
                $('#changeLocationModal').modal('hide');
                
                // Re-geocode all businesses
                geocodeMissingAddresses();
            }
        });
    }
});

function openChangeLocationModal(event) {
    event.preventDefault();
    
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var modal = new bootstrap.Modal(document.getElementById('changeLocationModal'));
        modal.show();
    }
}

// Testing links functionality
function showTestingLinks() {
    if (locations.length < 2) {
        alert('Please add businesses to your tour first.');
        return;
    }
    
    var tourLocations = locations.filter(function(loc, index) {
        return index === 0 || !loc.isOutOfRange;
    });
    
    if (tourLocations.length < 2) {
        alert('No in-range businesses found for navigation.');
        return;
    }
    
    var origin = encodeURIComponent(tourLocations[0].address);
    var destination = encodeURIComponent(tourLocations[tourLocations.length - 1].address);
    var waypoints = [];
    
    for (var i = 1; i < tourLocations.length - 1; i++) {
        waypoints.push(encodeURIComponent(tourLocations[i].address));
    }
    
    var appleMapsUrl = 'https://maps.apple.com/?saddr=' + origin + '&daddr=' + destination;
    var allStops = [origin].concat(waypoints).concat([destination]);
    var googleMapsUrl = 'https://www.google.com/maps/dir/' + allStops.join('/');
    
    var modal = $('<div class="modal fade" tabindex="-1">');
    var modalDialog = $('<div class="modal-dialog modal-lg">').appendTo(modal);
    var modalContent = $('<div class="modal-content">').appendTo(modalDialog);
    
    var modalHeader = $('<div class="modal-header">').appendTo(modalContent);
    $('<h5 class="modal-title">Navigation Testing Links</h5>').appendTo(modalHeader);
    $('<button type="button" class="btn-close" data-bs-dismiss="modal"></button>').appendTo(modalHeader);
    
    var modalBody = $('<div class="modal-body">').appendTo(modalContent);
    
    $('<h6>Apple Maps URL</h6>').appendTo(modalBody);
    var appleInput = $('<input type="text" class="form-control mb-2" readonly>').val(appleMapsUrl).appendTo(modalBody);
    
    $('<h6>Google Maps URL</h6>').appendTo(modalBody);
    var googleInput = $('<input type="text" class="form-control mb-2" readonly>').val(googleMapsUrl).appendTo(modalBody);
    
    modal.appendTo('body');
    modal.modal('show');
    
    modal.on('hidden.bs.modal', function() {
        modal.remove();
    });
}

// Send to phone functionality
function sendToPhone() {
    console.log('Send to phone clicked');
    
    if (locations.length < 2) {
        alert('Please add businesses to your tour first.');
        return;
    }
    
    var tourLocations = locations.filter(function(loc, index) {
        return index === 0 || !loc.isOutOfRange;
    });
    
    if (tourLocations.length < 2) {
        alert('No in-range businesses found for navigation.');
        return;
    }
    
    var origin = encodeURIComponent(tourLocations[0].address);
    var waypoints = [];
    
    for (var i = 1; i < tourLocations.length; i++) {
        waypoints.push(encodeURIComponent(tourLocations[i].address));
    }
    
    // Determine phone type
    var phoneType = '<?php echo $current_user_data['profile_phone_type'] ?? 'unknown'; ?>';
    var phoneTypeSource = 'profile';
    
    if (phoneType === 'unknown') {
        var userAgent = navigator.userAgent.toLowerCase();
        if (/iphone|ipad|ipod/.test(userAgent)) {
            phoneType = 'iphone';
            phoneTypeSource = 'user_agent';
        } else if (/android/.test(userAgent)) {
            phoneType = 'android';
            phoneTypeSource = 'user_agent';
        } else {
            phoneType = 'android';
            phoneTypeSource = 'default';
        }
    }
    
    var navigationUrl = '';
    if (phoneType === 'iphone' || phoneType === 'ios') {
        if (waypoints.length > 1) {
            // Use Google Maps for multi-stop on iPhone
            var allStops = [origin].concat(waypoints);
            navigationUrl = 'https://www.google.com/maps/dir/' + allStops.join('/');
        } else {
            // Simple A to B - use Apple Maps
            navigationUrl = 'https://maps.apple.com/?saddr=' + origin + '&daddr=' + waypoints[0];
        }
    } else {
        // Google Maps for Android
        var allStops = [origin].concat(waypoints);
        navigationUrl = 'https://www.google.com/maps/dir/' + allStops.join('/');
    }
    
    var isDebug = window.location.search.includes('debug');
    
    // In debug mode, allow custom phone number
    var testPhoneNumber = null;
    if (isDebug) {
        var customPhone = prompt('Debug Mode: Enter a phone number to test SMS sending (or leave blank to use your profile number):');
        if (customPhone && customPhone.trim()) {
            testPhoneNumber = customPhone.trim();
            console.log('Using test phone number:', testPhoneNumber);
        }
    }
    
    $.ajax({
        url: '/myaccount/tour.php' + (isDebug ? '?debug=1' : ''),
        method: 'POST',
        data: {
            action: 'send_to_phone',
            navigation_url: navigationUrl,
            phone_type: phoneType,
            phone_type_source: phoneTypeSource,
            tour_date: '<?php echo $date; ?>',
            debug: isDebug ? 1 : 0,
            test_phone: testPhoneNumber
        },
        success: function(response) {
            if (response.success) {
                var message = response.message;
                
                if (response.phone_number) {
                    message += '\n\nPhone: ' + response.phone_number;
                }
                
                if (response.sms_status === 'sent') {
                    message += '\nSMS Status: ✓ Sent successfully';
                } else if (response.sms_status === 'failed') {
                    message += '\nSMS Status: ✗ Failed';
                    if (response.sms_error) {
                        message += '\nError: ' + response.sms_error;
                    }
                }
                
                if (response.url) {
                    message += '\n\nYou can manually copy this link:\n' + response.url;
                }
                
                alert(message);
            } else {
                alert(response.message || 'Failed to send navigation link. Please try again.');
            }
        },
        error: function() {
            alert('Failed to send navigation link. Please try again.');
        }
    });
}
</script>

<!-- Load Google Maps API -->
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $sitesettings['GOOGLEAPI']['browser_key'] ?? ''; ?>&libraries=places,marker&callback=initMap&v=weekly"></script>

<?php
// Footer breaks Google Maps, so we skip it and just output the page
$app->outputpage();

/* 
LOCATION DATA STORAGE:

1. TOURS (bg_user_tours):
   - Stores which companies are on a user's tour for specific dates
   - Links users to companies via company_id and calendar_dt

2. BUSINESS LOCATIONS (bg_company_locations):
   - Permanent storage of all company locations
   - source: 'tour_geocoding' when discovered via tour
   - is_verified: 1 when geocoded with lat/lng
   - Can have multiple locations per company
   - Shared across all users

3. USER HOME LOCATION (bg_user_attributes):
   - Default home location:
     - type: 'tour_settings'
     - name: 'default_home_location'
     - category: 'preferences'
     - description: JSON with address, lat, lng, city, state, zip
   - Applied to all tours for this user
*/
?>