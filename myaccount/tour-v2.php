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
                // Update existing location for this tour
                $updateQuery = "UPDATE bg_user_attributes 
                               SET description = :description, 
                                   string_value = :address,
                                   modify_dt = NOW() 
                               WHERE attribute_id = :attribute_id";
                $stmt = $database->prepare($updateQuery);
                $stmt->execute([
                    ':description' => $locationJson,
                    ':address' => $address,
                    ':attribute_id' => $existing['attribute_id']
                ]);
            } else {
                // Insert new location record
                $insertQuery = "INSERT INTO bg_user_attributes 
                               (user_id, type, name, description, status, category, string_value, start_dt) 
                               VALUES (:user_id, 'tour_locations', :name, :description, 'active', 'locations', :address, :start_dt)";
                $stmt = $database->prepare($insertQuery);
                $stmt->execute([
                    ':user_id' => $current_user_data['user_id'],
                    ':name' => $locationName,
                    ':description' => $locationJson,
                    ':address' => $address,
                    ':start_dt' => $tourDate
                ]);
            }
            
            // Also update the default home location
            $defaultLocationData = json_encode($locationData);
            $checkQuery = "SELECT attribute_id FROM bg_user_attributes 
                          WHERE user_id = :user_id 
                          AND type = 'tour_settings' 
                          AND name = 'default_home_location' 
                          AND status = 'active'";
            $stmt = $database->prepare($checkQuery);
            $stmt->execute([':user_id' => $current_user_data['user_id']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing
                $updateQuery = "UPDATE bg_user_attributes 
                               SET description = :description, 
                                   string_value = :address,
                                   modify_dt = NOW() 
                               WHERE attribute_id = :attribute_id";
                $stmt = $database->prepare($updateQuery);
                $stmt->execute([
                    ':description' => $defaultLocationData,
                    ':address' => $address,
                    ':attribute_id' => $existing['attribute_id']
                ]);
            } else {
                // Insert new
                $insertQuery = "INSERT INTO bg_user_attributes 
                               (user_id, type, name, description, status, category, string_value) 
                               VALUES (:user_id, 'tour_settings', 'default_home_location', :description, 'active', 'preferences', :address)";
                $stmt = $database->prepare($insertQuery);
                $stmt->execute([
                    ':user_id' => $current_user_data['user_id'],
                    ':description' => $defaultLocationData,
                    ':address' => $address
                ]);
            }
            
            // Release any non-forced locations for this tour date
            // First get all tours for this date to check attributes
            $checkQuery = "SELECT tour_id, attributes FROM bg_user_tours 
                          WHERE user_id = :user_id 
                          AND calendar_dt = :calendar_dt";
            $stmt = $database->prepare($checkQuery);
            $stmt->execute([
                ':user_id' => $current_user_data['user_id'],
                ':calendar_dt' => $tourDate
            ]);
            $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Update only non-forced locations
            foreach ($tours as $tour) {
                $attributes = !empty($tour['attributes']) ? json_decode($tour['attributes'], true) : [];
                if (!isset($attributes['force_location']) || !$attributes['force_location']) {
                    $releaseQuery = "UPDATE bg_user_tours 
                                   SET location_id = NULL 
                                   WHERE tour_id = :tour_id";
                    $stmt = $database->prepare($releaseQuery);
                    $stmt->execute([':tour_id' => $tour['tour_id']]);
                }
            }
            
            ob_end_clean();
            echo json_encode(['success' => true, 'address' => $address]);
            exit;
            
        case 'search_business_locations':
            $companyId = $_POST['company_id'] ?? '';
            $radius = $_POST['radius'] ?? 25;
            $homeLat = $_POST['home_lat'] ?? null;
            $homeLng = $_POST['home_lng'] ?? null;
            
            if (!$companyId || !$homeLat || !$homeLng) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }
            
            // Search for locations within radius using Haversine formula
            $query = "SELECT *, 
                      (3959 * acos(cos(radians(:lat1)) * cos(radians(latitude)) * 
                      cos(radians(longitude) - radians(:lng1)) + 
                      sin(radians(:lat2)) * sin(radians(latitude)))) AS distance 
                      FROM bg_company_locations 
                      WHERE company_id = :company_id 
                      AND status = 'active' 
                      AND latitude IS NOT NULL 
                      AND longitude IS NOT NULL 
                      HAVING distance < :radius 
                      ORDER BY distance";
            
            $stmt = $database->prepare($query);
            $stmt->execute([
                ':lat1' => $homeLat,
                ':lat2' => $homeLat,
                ':lng1' => $homeLng,
                ':company_id' => $companyId,
                ':radius' => $radius
            ]);
            
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format addresses for display
            foreach ($locations as &$loc) {
                $loc['full_address'] = trim($loc['address'] . ', ' . $loc['city'] . ', ' . $loc['state'] . ' ' . $loc['zip_code']);
                $loc['distance'] = round($loc['distance'], 1);
            }
            
            // If no locations found, get all locations for this company
            if (empty($locations)) {
                $fallbackQuery = "SELECT * FROM bg_company_locations 
                                 WHERE company_id = :company_id 
                                 AND status = 'active' 
                                 ORDER BY city, state 
                                 LIMIT 20";
                $stmt = $database->prepare($fallbackQuery);
                $stmt->execute([':company_id' => $companyId]);
                $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
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
            $tourDate = $_POST['tour_date'] ?? date('Y-m-d');
            $debug = isset($_GET['debug']) || isset($_POST['debug']);
            $previewOnly = isset($_POST['preview_only']) && $_POST['preview_only'];
            
            if (!$navigationUrl) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Missing navigation URL']);
                exit;
            }
            
            // Get user's phone number
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
            
            // Clean phone number (remove non-digits)
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
            
            // Ensure it has country code (assume US if 10 digits)
            if (strlen($phoneNumber) == 10) {
                $phoneNumber = '1' . $phoneNumber;
            }
            
            // Use app->getshortcode to shorten the URL
            $shortCodeData = $app->getshortcode($navigationUrl, 'tour_nav_' . $tourDate);
            
            if (!$shortCodeData || !isset($shortCodeData['shorturl'])) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Failed to create short URL']);
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
                    'phone_number' => substr($phoneNumber, 0, -4) . 'XXXX', // Partially hide for privacy
                    'shortcode_data' => $shortCodeData // Include full response for debugging
                ];
            }
            
            // Create SMS message
            $message = "Your Birthday Tour navigation link for " . date('M j, Y', strtotime($tourDate)) . ":\n" . $shortUrl . "\n\nTap to open in " . ($phoneType === 'iphone' || $phoneType === 'ios' ? 'Maps' : 'Google Maps');
            
            // If preview only mode, return without sending SMS
            if ($previewOnly) {
                ob_end_clean();
                $response = [
                    'success' => true, 
                    'message' => 'Preview mode - SMS not sent',
                    'preview' => [
                        'message' => $message,
                        'short_url' => $shortUrl,
                        'phone_number' => substr($phoneNumber, 0, -4) . 'XXXX'
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
                    $response = ['success' => true, 'message' => 'Navigation link sent to your phone!'];
                    if ($debug) {
                        $response['debug'] = $debugInfo;
                        $response['sms_result'] = $smsResult;
                    }
                    echo json_encode($response);
                    exit;
                } else {
                    // If SMS fails, still return the URL
                    ob_end_clean();
                    $response = ['success' => true, 'message' => 'Navigation URL created: ' . $shortUrl, 'url' => $shortUrl];
                    if ($debug) {
                        $response['debug'] = $debugInfo;
                        $response['sms_error'] = $smsResult['error'] ?? 'Unknown SMS error';
                    }
                    echo json_encode($response);
                    exit;
                }
                
            } catch (Exception $e) {
                ob_end_clean();
                $response = ['success' => false, 'message' => 'Failed to send SMS: ' . $e->getMessage()];
                if ($debug) {
                    $response['debug'] = $debugInfo;
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
        return null;
    }
    
    $earthRadius = 3959; // Miles
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;
    
    return round($distance, 1);
}

// Format the date
$dateObject = new DateTime($date);
$formattedDate = $dateObject->format('l, F j, Y');

// Get home address - check default home location, then fallback
$homeaddress = '10106 Atlanta Street, Parker, CO 80134'; // default fallback
$home_lat = null;
$home_lng = null;

// Get user's default home location
$homeData = getUserAttribute($database, $current_user_data['user_id'], 'tour_settings', 'default_home_location');

if ($homeData && !empty($homeData['description'])) {
    $locationData = json_decode($homeData['description'], true);
    if ($locationData) {
        $homeaddress = $locationData['address'] ?? $homeaddress;
        $home_lat = $locationData['lat'] ?? null;
        $home_lng = $locationData['lng'] ?? null;
    }
} else {
    // Check session as fallback
    $homeaddress = $_SESSION['tour_home_address'] ?? $homeaddress;
    $home_lat = $_SESSION['tour_home_lat'] ?? null;
    $home_lng = $_SESSION['tour_home_lng'] ?? null;
}

// Update session for this request
$_SESSION['tour_home_address'] = $homeaddress;
$_SESSION['tour_home_lat'] = $home_lat;
$_SESSION['tour_home_lng'] = $home_lng;

// Parse home address to get city, state, zip
$home_parts = array_map('trim', explode(',', $homeaddress));
$home_city = '';
$home_state = '';
$home_zip = '';

if (count($home_parts) >= 3) {
    // Assume format: street, city, state zip
    $home_city = $home_parts[1];
    $state_zip = trim($home_parts[2]);
    $state_zip_parts = explode(' ', $state_zip);
    if (count($state_zip_parts) >= 2) {
        $home_state = $state_zip_parts[0];
        $home_zip = $state_zip_parts[1];
    }
}

// Calculate distances for each business if we have home coordinates
foreach ($listofcompanies as &$company_item) {
    if (isset($company_item['data']) && $home_lat && $home_lng) {
        $business_lat = $company_item['data']['latitude'] ?? null;
        $business_lng = $company_item['data']['longitude'] ?? null;
        
        if ($business_lat && $business_lng) {
            $distance = calculateDistance($home_lat, $home_lng, $business_lat, $business_lng);
            $company_item['data']['distance_from_home'] = $distance;
            $company_item['data']['is_out_of_range'] = ($distance !== null && $distance > 100);
            
            // Update database if business is out of range
            if ($company_item['data']['is_out_of_range']) {
                $updateAttributes = [];
                
                // Get existing attributes
                $existingAttributes = !empty($company_item['attributes']) ? json_decode($company_item['attributes'], true) : [];
                if (is_array($existingAttributes)) {
                    $updateAttributes = $existingAttributes;
                }
                
                // Add out of range flag
                $updateAttributes['out_of_range'] = true;
                $updateAttributes['distance_miles'] = $distance;
                $updateAttributes['checked_date'] = date('Y-m-d');
                
                // Update database
                $updateQuery = "UPDATE bg_user_tours 
                               SET attributes = :attributes 
                               WHERE user_id = :user_id 
                               AND company_id = :company_id 
                               AND calendar_dt = :date";
                $stmt = $database->prepare($updateQuery);
                $stmt->execute([
                    ':attributes' => json_encode($updateAttributes),
                    ':user_id' => $current_user_data['user_id'],
                    ':company_id' => $company_item['company_id'],
                    ':date' => $date
                ]);
            }
        } else {
            // No coordinates available for business
            $company_item['data']['distance_from_home'] = null;
            $company_item['data']['is_out_of_range'] = false;
        }
    }
}

// Add styles
$additionalstyles = '<style>
/* Sortable styles */
.sortable_item {
    transition: all 0.2s ease;
}

/* Out of range business styles */
.sortable_item.out-of-range {
    opacity: 0.5;
    background-color: #f8f9fa;
}

.sortable_item.out-of-range .sortable_item_handle {
    display: none;
}

.out-of-range-badge {
    background-color: #dc3545;
    color: white;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    margin-left: 0.5rem;
}

.sortable_item:hover {
    background-color: #f8f9fa;
}

.sortable_item.ui-sortable-helper {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    background: white;
    opacity: 0.9;
}

.sortable_item_handle {
    cursor: grab;
    color: #6c757d;
    transition: color 0.2s ease;
}

.sortable_item_handle:hover {
    color: #495057;
}

.sortable_item_handle:active {
    cursor: grabbing;
    color: #212529;
}

#directions-panel {
    font-size: 0.875rem;
}

#directions-panel .adp-directions {
    padding: 0;
}

#directions-panel .adp-placemark {
    background: #f8f9fa;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    border-radius: 0.25rem;
}

.sorting-active {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
}

/* Highlight the drop zone */
.ui-sortable-placeholder {
    border: 2px dashed #dee2e6;
    background-color: #f8f9fa;
    visibility: visible !important;
    height: 60px !important;
}

/* Map status styles */
.info {
    background: #f0f0f0;
    padding: 15px;
    border-radius: 5px;
    margin: 10px 0;
}
.error {
    background: #ffcccc;
    color: #cc0000;
    padding: 15px;
    border-radius: 5px;
    margin: 10px 0;
}
.success {
    background: #ccffcc;
    color: #008800;
    padding: 15px;
    border-radius: 5px;
    margin: 10px 0;
}

/* Fix Google autocomplete z-index for modals */
.pac-container {
    z-index: 10000 !important;
}

/* Hanging bullet styles for directions - timeline style */
#directions-panel ul {
    list-style: none;
    padding-left: 0;
    margin: 0;
}

#directions-panel li {
    position: relative;
    padding-left: 25px;
    margin-bottom: 8px;
    color: #5f6368;
}

#directions-panel li::before {
    content: "•";
    position: absolute;
    left: -20px;
    color: #1a73e8;
    font-size: 20px;
    line-height: 14px;
    font-weight: bold;
    background: white;
    z-index: 1;
}

/* Print styles */
@media print {
    /* Hide non-print elements */
    .bg_header,
    .bg_user_profileheader,
    nav,
    header,
    footer,
    .modal,
    .modal-backdrop,
    .btn,
    .card-header-actions,
    #sortable,
    #api-status,
    .row:not(.print-content) {
        display: none !important;
    }
    
    /* Hide any skip links or text before main content */
    body > :first-child:not(.container) {
        display: none !important;
    }
    
    /* Force print content to start at top of page */
    .print-content {
        page-break-before: avoid !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
    }
    
    /* Force page layout for print */
    @page {
        size: letter;
        margin: 0.5in;
    }
    
    /* Reset layout for proper printing */
    body {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }
    
    .container {
        width: 100% !important;
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .row.print-content {
        display: flex !important;
        flex-direction: column !important;
        margin: 0 !important;
        width: 100% !important;
    }
    
    .col-lg-4, .col-lg-8 {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        flex: none !important;
    }
    
    /* Style cards for print */
    .card {
        border: 1px solid #ddd !important;
        margin-bottom: 20px !important;
        break-inside: avoid;
    }
    
    /* Keep map header with map */
    .col-lg-8 .card {
        break-inside: avoid;
        page-break-inside: avoid;
    }
    
    .card-header {
        background: #f8f9fa !important;
        padding: 10px 15px !important;
        font-weight: bold !important;
        font-size: 14pt !important;
        border-bottom: 1px solid #ddd !important;
        break-after: avoid;
        page-break-after: avoid;
    }
    
    .card-body {
        max-height: none !important;
        overflow: visible !important;
        padding: 15px !important;
    }
    
    /* Show full directions without scroll */
    #directions-panel {
        max-height: none !important;
        overflow: visible !important;
        font-size: 11pt !important;
        line-height: 1.4 !important;
    }
    
    /* Directions on first page(s) - will naturally flow */
    .col-lg-4 {
        margin-bottom: 30px !important;
    }
    
    /* Map on new page after directions */
    .col-lg-8 {
        page-break-before: always;
    }
    
    /* Map sizing for print */
    #route-map {
        height: 700px !important;
        width: 100% !important;
    }
    
    /* Ensure map prints properly */
    .gm-style img {
        max-width: none !important;
    }
    
    /* Add title directly above directions card */
    .col-lg-4 .card::before {
        content: "Tour Directions for ' . $formattedDate . '";
        display: block;
        font-size: 20pt;
        font-weight: bold;
        margin-bottom: 20px;
        text-align: center;
        width: 100%;
    }
}
</style>';

// Page setup
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
#include($dir['core_components'] . '/bg_user_leftpanel.inc');
echo '<div class="container">';
// Start the myaccount layout
echo '<div class="col-12">';
?>

<div class="row">
    <div class="col-lg-8 mb-4">
        <!-- DATE card -->
        <div class="card h-100 border-start-lg border-start-primary">
            <div class="card-body">
                <div class="small text-muted">Your Tour:</div>
                <div class="h3 my-3"><?php echo $formattedDate; ?></div>
                Consists of <?php echo count($listofcompanies); ?> businesses
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <!-- ACTIONS card -->
        <div class="card h-100 border-start-lg border-start-success">
            <div class="card-body">
                <div class="small text-muted mb-4">Actions</div>
                <div class="text-center">
                    <button class="btn btn-primary me-2" onclick="sendToPhone()">
                        <i class="bi bi-phone"></i> Send to Phone
                    </button>
                    <button class="btn btn-secondary" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print/Download
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<hr class="mt-0 mb-4">

<div class="row">
    <div class="col-lg-12 mb-4">
        <!-- CELEBRATION TOUR COMPANIES card-->
        <div class="card card-header-actions mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="mb-0">Celebration Tour</h2>
                <div>
                    <a href="/myaccount/tour-build?date=<?php echo $date; ?>" class="btn btn-sm btn-primary" type="button">Add More Businesses</a>
                </div>
            </div>
            <div class="card-body" id="sortable">
                <!-- Home location -->
                <div class="d-flex align-items-center justify-content-between px-4 bg-success bg-opacity-10 py-3 rounded" data-location="<?php echo $homeaddress; ?>">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-house-fill"></i>
                        <div class="ms-4">
                            <div class="small">Starting Tour Location</div>
                            <div class="text-xs text-muted">
                                <?php 
                                // Remove USA from display
                                $displayHomeAddress = preg_replace('/, USA$/', '', $homeaddress);
                                $displayHomeAddress = preg_replace('/, United States$/', '', $displayHomeAddress);
                                echo $displayHomeAddress;
                                
                                // Get user's profile mailing address
                                $profileAddress = getUserAttribute($database, $current_user_data['user_id'], 'profile', 'profile_mailing_address');
                                $profileCity = getUserAttribute($database, $current_user_data['user_id'], 'profile', 'profile_city');
                                $profileState = getUserAttribute($database, $current_user_data['user_id'], 'profile', 'profile_state');
                                $profileZip = getUserAttribute($database, $current_user_data['user_id'], 'profile', 'profile_zip_code');
                                
                                // Check if address components match (excluding state)
                                if ($profileAddress && $profileCity && $profileZip) {
                                    // Clean USA from homeaddress before parsing
                                    $cleanHomeAddress = preg_replace('/, USA$/', '', $homeaddress);
                                    $cleanHomeAddress = preg_replace('/, United States$/', '', $cleanHomeAddress);
                                    
                                    // Parse the tour starting address
                                    $addressParts = array_map('trim', explode(',', $cleanHomeAddress));
                                    $tourStreet = isset($addressParts[0]) ? strtolower(trim($addressParts[0])) : '';
                                    $tourCity = isset($addressParts[1]) ? strtolower(trim($addressParts[1])) : '';
                                    
                                    // Extract zip from the state/zip part
                                    $tourZip = '';
                                    if (isset($addressParts[2])) {
                                        $stateZipParts = explode(' ', trim($addressParts[2]));
                                        $tourZip = isset($stateZipParts[1]) ? trim($stateZipParts[1]) : '';
                                    }
                                    
                                    // Compare individual components - remove punctuation
                                    $profileStreet = preg_replace('/[^a-z0-9\s]/i', '', strtolower(trim($profileAddress['string_value'])));
                                    $profileCityLower = preg_replace('/[^a-z0-9\s]/i', '', strtolower(trim($profileCity['string_value'])));
                                    $profileZipTrim = trim($profileZip['string_value']);
                                    
                                    // Also remove punctuation from tour values
                                    $tourStreetClean = preg_replace('/[^a-z0-9\s]/i', '', $tourStreet);
                                    $tourCityClean = preg_replace('/[^a-z0-9\s]/i', '', $tourCity);
                                    
                                    $streetMatch = $profileStreet === $tourStreetClean;
                                    $cityMatch = $profileCityLower === $tourCityClean;
                                    $zipMatch = $profileZipTrim === $tourZip;
                                    
                                    // Debug output
                                    if (isset($_GET['debug'])) {
                                        echo '<br><small class="text-muted" style="font-size: 10px;">';
                                        echo 'Profile: [' . $profileStreet . '] [' . $profileCityLower . '] [' . $profileZipTrim . ']<br>';
                                        echo 'Tour: [' . $tourStreet . '] [' . $tourCity . '] [' . $tourZip . ']<br>';
                                        echo 'Matches: Street=' . ($streetMatch ? 'Y' : 'N') . ' City=' . ($cityMatch ? 'Y' : 'N') . ' Zip=' . ($zipMatch ? 'Y' : 'N');
                                        echo '</small>';
                                    }
                                    
                                    if ($streetMatch && $cityMatch && $zipMatch) {
                                        echo ' <span class="text-primary">(Your Home)</span>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="ms-4 small">
                        <a href="#!" onclick="openChangeLocationModal(event)">Change Location</a>
                    </div>
                </div>
                <hr>
                
                <?php
                // Display tour businesses
                foreach ($listofcompanies as $item_companyrow) {
                    $item_company = $item_companyrow['data'];
                    if (!empty($item_company)) {
                        // Debug - let us see what data we have
                        // echo "<!-- Debug: " . htmlspecialchars(json_encode($item_company)) . " -->";
                        
                        // Check if we have a full address
                        $hasFullAddress = !empty($item_company['address']) && strlen(trim($item_company['address'])) > 0;
                        
                        // Check if we have coordinates (which means location is already known)
                        $hasCoordinates = !empty($item_company['latitude']) && !empty($item_company['longitude']);
                        
                        // Debug output
                        if (isset($_GET['debug'])) {
                            echo "<!-- Company: " . $item_company['company_name'] . " -->\n";
                            echo "<!-- Has coords: " . ($hasCoordinates ? 'YES' : 'NO') . " -->\n";
                            echo "<!-- Lat: " . ($item_company['latitude'] ?? 'null') . " -->\n";
                            echo "<!-- Lng: " . ($item_company['longitude'] ?? 'null') . " -->\n";
                            echo "<!-- Location ID: " . ($item_company['location_id'] ?? 'null') . " -->\n";
                        }
                        
                        // Use home location city/state if business does not have them
                        $businessCity = !empty($item_company['city']) ? $item_company['city'] : $home_city;
                        $businessState = !empty($item_company['state']) ? $item_company['state'] : $home_state;
                        $businessZip = !empty($item_company['zip_code']) ? $item_company['zip_code'] : $home_zip;
                        
                        // Build the display address
                        if ($hasFullAddress) {
                            $companyaddress = $item_company['address'].', '.$businessCity.', '.$businessState.' '.$businessZip;
                        } else if ($businessCity && $businessState) {
                            $companyaddress = $businessCity.', '.$businessState.' '.$businessZip;
                        } else {
                            $companyaddress = 'Location pending';
                        }
                ?>
                <!-- Business location -->
                <div class="sortable_item <?php echo ($item_company['data']['is_out_of_range'] ?? false) ? 'out-of-range' : ''; ?>" 
                     data-company-id="<?php echo $item_company['company_id']; ?>" 
                     data-company-name="<?php echo htmlspecialchars($item_company['company_name']); ?>"
                     data-out-of-range="<?php echo ($item_company['data']['is_out_of_range'] ?? false) ? 'true' : 'false'; ?>">
                    <div class="d-flex align-items-center justify-content-between px-4" data-location="<?php echo $companyaddress; ?>">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']); ?>" style="width:32px" alt="" />  
                            <div class="ms-4">
                                <div class="small fw-bold">
                                    <?php echo $item_company['company_name']; ?>
                                    <?php if ($item_company['data']['is_out_of_range'] ?? false): ?>
                                        <span class="out-of-range-badge">
                                            <?php echo $item_company['data']['distance_from_home']; ?> miles away
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-muted company-address" id="address-<?php echo $item_company['company_id']; ?>">
                                    <?php if ($hasCoordinates && !$hasFullAddress): ?>
                                        <span class="text-success">📍 <?php echo $businessCity; ?>, <?php echo $businessState; ?> (Located)</span>
                                    <?php elseif (!$hasFullAddress): ?>
                                        <span class="text-warning">📍 Searching for location in <?php echo $home_city; ?></span>
                                    <?php else: ?>
                                        <?php echo $companyaddress; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($item_company['is_forced_location'])): ?>
                                        <span class="badge bg-info text-white ms-1">Pinned</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="ms-4 small">
                            <a href="javascript:void(0);" class="pick-location me-2" data-company-id="<?php echo $item_company['company_id']; ?>" data-company-name="<?php echo htmlspecialchars($item_company['company_name']); ?>">Pick Different Location</a>
                            <div class="btn btn-sm sortable_item_handle" title="Drag to reorder"><i class="bi bi-grip-vertical h4"></i></div>
                        </div>
                    </div>
                    <hr>
                </div>
                <?php
                    }
                }
                ?>
                
                <!-- Draw new map button -->
                <div style="text-align: center; margin-bottom: 20px;">
                    <p class="text-muted small mb-2"><i class="bi bi-grip-vertical"></i> Drag businesses to reorder your tour route</p>
                    <button class="btn btn-secondary draw_map" id="draw_map" style="display: none;" onclick="DrawNewMap()">
                        <i class="bi bi-arrow-clockwise"></i> Update Map with New Route
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden status div for API loading messages -->
<div id="api-status" class="info" style="display: none;">Loading Google Maps API...</div>

<!-- Location Picker Modal -->
<div class="modal fade" id="locationPickerModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pick Location for <span id="modal-business-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label">Search Radius from Starting Location</label>
                            <select class="form-select" id="radius-select">
                                <option value="5">5 miles</option>
                                <option value="25" selected>25 miles</option>
                                <option value="50">50 miles</option>
                                <option value="100">100 miles</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <button class="btn btn-primary w-100" onclick="searchBusinessLocations()">
                                <i class="bi bi-search"></i> Search Locations
                            </button>
                        </div>
                        <div id="location-results" class="list-group" style="max-height: 400px; overflow-y: auto;">
                            <!-- Search results will appear here -->
                        </div>
                        <div class="form-check mt-3" id="force-location-container" style="display: none;">
                            <input class="form-check-input" type="checkbox" id="force-location-check">
                            <label class="form-check-label" for="force-location-check">
                                <strong>Pin this location</strong><br>
                                <small class="text-muted">Keep this location even when starting location changes</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div id="location-map" style="height: 500px;"></div>
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

<div class="row print-content">
    <div class="col-lg-4 mb-4">
        <!-- STEPS CARD-->
        <div class="card h-100 border-start-lg border-start-secondary">
            <div class="card-header">Turn-by-Turn Directions</div>
            <div class="card-body" style="max-height: 800px; overflow-y: auto;">
                <div id="directions-panel"></div>
            </div>
        </div>
    </div>

    <!-- MAP card-->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">Map and Direction</div>
            <div class="card-body p-0">
                <!-- Map will be displayed here -->
                <div id="route-map" style="height: 800px;"></div>
            </div>
        </div>
    </div>
</div>


    <script>

    // Map initialization
    var routeMap;
    var directionsService;
    var directionsRenderer;
    
    // Tour locations from PHP data - Global array
    var locations = [
        {
            name: "Starting Tour Location",
            address: <?php echo json_encode($homeaddress); ?>,
            lat: <?php echo json_encode($home_lat); ?>,
            lng: <?php echo json_encode($home_lng); ?>,
            type: "home"
        }
        <?php 
        foreach ($listofcompanies as $item_companyrow) {
            $item_company = $item_companyrow['data'];
            if (!empty($item_company)) {
                // Check if we have a full address or just city/state
                $hasFullAddress = !empty($item_company['address']) && strlen(trim($item_company['address'])) > 0;
                
                // Use home location city/state if business doesn't have them
                $businessCity = !empty($item_company['city']) ? $item_company['city'] : $home_city;
                $businessState = !empty($item_company['state']) ? $item_company['state'] : $home_state;
                $businessZip = !empty($item_company['zip_code']) ? $item_company['zip_code'] : $home_zip;
                
                $companyaddress = $hasFullAddress ? 
                    $item_company['address'].', '.$businessCity.', '.$businessState.' '.$businessZip :
                    $businessCity.', '.$businessState.' '.$businessZip;
        ?>
        ,{
            name: <?php echo json_encode($item_company['company_name']); ?>,
            company_id: <?php echo json_encode($item_company['company_id']); ?>,
            address: <?php echo json_encode($companyaddress); ?>,
            needsGeocoding: <?php echo json_encode(!$hasCoordinates); ?>,
            lat: <?php echo json_encode($item_company['latitude'] ?? null); ?>,
            lng: <?php echo json_encode($item_company['longitude'] ?? null); ?>,
            city: <?php echo json_encode($businessCity); ?>,
            state: <?php echo json_encode($businessState); ?>,
            reward: <?php echo json_encode($item_company['description'] ?? ''); ?>,
            type: "business",
            isOutOfRange: <?php echo json_encode($item_company['is_out_of_range'] ?? false); ?>
        }
        <?php 
            }
        }
        ?>
    ];
    
    function initMap() {
        console.log('initMap() called');
        document.getElementById('api-status').className = 'success';
        document.getElementById('api-status').textContent = 'Google Maps API loaded successfully! Loading directions...';
        
        // Add a delay to ensure locations array is populated
        setTimeout(function() {
            // Check if we need to geocode any addresses
            var needsGeocoding = false;
            if (typeof locations !== 'undefined') {
                console.log('Checking locations in initMap:', locations);
                locations.forEach(function(loc) {
                    console.log('Location:', loc.name, 'needsGeocoding:', loc.needsGeocoding, 'address:', loc.address);
                    if (loc.needsGeocoding === true) {
                        needsGeocoding = true;
                    }
                });
            } else {
                console.error('Locations array not defined!');
            }
            
            console.log('Need geocoding?', needsGeocoding);
            
            if (needsGeocoding && typeof geocodeMissingAddresses === 'function') {
                // Geocode missing addresses first, then load directions
                console.log('Starting geocoding process...');
                geocodeMissingAddresses();
            } else {
                // No geocoding needed, load directions immediately
                console.log('No geocoding needed, loading directions directly');
                loadDirections();
            }
        }, 100); // Small delay to ensure everything is loaded
    }
    
    // Make initMap global
    window.initMap = initMap;
    
    // Function to save business location to database
    function saveTourBusinessLocation(companyId, companyName, address, lat, lng) {
        fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=save_business_location' +
                  '&company_id=' + encodeURIComponent(companyId) +
                  '&company_name=' + encodeURIComponent(companyName) +
                  '&address=' + encodeURIComponent(address) +
                  '&lat=' + lat +
                  '&lng=' + lng
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Business location saved with ID:', data.location_id);
            }
        })
        .catch(error => {
            console.error('Error saving business location:', error);
        });
    }
    
    // Error handler for authentication failures
    window.gm_authFailure = function() {
        console.error('Google Maps authentication failed!');
        document.getElementById('api-status').className = 'error';
        document.getElementById('api-status').innerHTML = `
            <strong>Authentication Failed!</strong><br>
            This usually means the API key is restricted and doesn't allow domain: ${window.location.hostname}<br>
            Please check the Google Cloud Console and add this domain to the allowed list.
        `;
    };
    
    // Handle Google Maps authentication errors
    window.gm_authFailure = function() {
        console.error('Google Maps authentication failed!');
        document.getElementById('api-status').className = 'error';
        document.getElementById('api-status').style.display = 'block';
        document.getElementById('api-status').innerHTML = `
            <strong>Google Maps Error:</strong> Authentication failed.<br>
            Possible causes:<br>
            • API key is invalid or expired<br>
            • This domain is not authorized<br>
            • Required APIs are not enabled (Maps JavaScript API, Places API, Geocoding API)<br>
            • Billing is not enabled on the Google Cloud project
        `;
    };
    
    // Load Google Maps
    function loadGoogleMaps() {
        console.log('Loading Google Maps API...');
        document.getElementById('api-status').textContent = 'Loading Google Maps API...';
        
        <?php if (empty($sitesettings['GOOGLEAPI']['mainkey'])): ?>
        console.error('Google Maps API key is not configured!');
        document.getElementById('api-status').className = 'error';
        document.getElementById('api-status').textContent = 'Error: Google Maps API key is not configured. Please contact support.';
        return;
        <?php endif; ?>
        
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=<?php echo $sitesettings['GOOGLEAPI']['mainkey']; ?>&libraries=places,marker&callback=initMap&v=weekly&loading=async';
        script.async = true;
        script.defer = true;
        
        script.onload = function() {
            console.log('Google Maps script loaded');
            document.getElementById('api-status').className = 'success';
            document.getElementById('api-status').textContent = 'Google Maps API loaded successfully!';
        };
        
        script.onerror = function() {
            console.error('Failed to load Google Maps script');
            document.getElementById('api-status').className = 'error';
            document.getElementById('api-status').textContent = 'Failed to load Google Maps API script - Check API key configuration';
            
            // Log the actual error for debugging
            console.error('API Key used:', '<?php echo substr($sitesettings['GOOGLEAPI']['mainkey'] ?? 'NOT_SET', 0, 10); ?>...');
        };
        
        document.head.appendChild(script);
    }
    
    // Check if google object exists
    setTimeout(function() {
        if (typeof google !== 'undefined') {
            console.log('Google object exists');
            if (typeof google.maps !== 'undefined') {
                console.log('Google Maps API is available');
            }
        } else {
            console.log('Google object not found');
        }
    }, 3000);
    
    // Start loading when page is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadGoogleMaps);
    } else {
        loadGoogleMaps();
    }
    </script>

    <script>
    // Function to geocode missing addresses
    function geocodeMissingAddresses() {
        console.log('Starting geocodeMissingAddresses...');
        var geocodePromises = [];
        var placesService = new google.maps.places.PlacesService(document.createElement('div'));
        
        console.log('Checking locations for geocoding needs:', locations);
        console.log('Home location:', locations[0]);
        
        locations.forEach(function(location, index) {
            console.log('Checking location:', location.name, 'needsGeocoding:', location.needsGeocoding, 'lat:', location.lat, 'lng:', location.lng);
            
            // Check if location needs geocoding
            if (location.needsGeocoding && location.type === 'business') {
                var promise = new Promise(function(resolve) {
                    // Use the starting tour location as search context
                    var homeLocation = locations[0]; // First location is always home
                    var searchContext = homeLocation.address || (homeLocation.city + ', ' + homeLocation.state);
                    var searchQuery = location.name + ' near ' + searchContext;
                    console.log('Searching for:', searchQuery);
                    
                    var request = {
                        query: searchQuery,
                        fields: ['name', 'geometry', 'formatted_address']
                    };
                    
                    placesService.textSearch(request, function(results, status) {
                        console.log('Search status for', location.name, ':', status);
                        if (status === google.maps.places.PlacesServiceStatus.OK && results.length > 0) {
                            // Use the first result
                            var place = results[0];
                            console.log('Found location for', location.name, ':', place.formatted_address);
                            console.log('New coordinates:', place.geometry.location.lat(), place.geometry.location.lng());
                            
                            // Remove USA from the end of the address
                            var cleanAddress = place.formatted_address;
                            cleanAddress = cleanAddress.replace(/, USA$/, '');
                            cleanAddress = cleanAddress.replace(/, United States$/, '');
                            
                            // Update the location
                            locations[index].address = cleanAddress;
                            locations[index].lat = place.geometry.location.lat();
                            locations[index].lng = place.geometry.location.lng();
                            locations[index].needsGeocoding = false;
                            
                            // Update the display on the page
                            updateBusinessDisplay(location.name, cleanAddress);
                            
                            // Save to database
                            console.log('Saving to database:', location.name, cleanAddress);
                            saveTourBusinessLocation(location.company_id, location.name, cleanAddress, 
                                                   place.geometry.location.lat(), place.geometry.location.lng());
                        } else {
                            console.error('Could not find location for', location.name);
                        }
                        resolve();
                    });
                });
                geocodePromises.push(promise);
            }
        });
        
        // Also geocode home if needed
        if (!locations[0].lat || !locations[0].lng) {
            var homePromise = new Promise(function(resolve) {
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({ address: locations[0].address }, function(results, status) {
                    if (status === 'OK') {
                        locations[0].lat = results[0].geometry.location.lat();
                        locations[0].lng = results[0].geometry.location.lng();
                        console.log('Geocoded home location');
                    } else {
                        console.error('Failed to geocode home address');
                    }
                    resolve();
                });
            });
            geocodePromises.push(homePromise);
        }
        
        // Wait for all geocoding to complete, then load directions
        console.log('Total geocoding promises:', geocodePromises.length);
        if (geocodePromises.length > 0) {
            Promise.all(geocodePromises).then(function() {
                console.log('All geocoding complete, loading directions...');
                console.log('Final locations state:', locations);
                loadDirections();
            });
        } else {
            // No geocoding needed, load directions immediately
            console.log('No geocoding needed, loading directions immediately');
            loadDirections();
        }
    }
    
    // Function to update business display with new address
    function updateBusinessDisplay(businessName, newAddress) {
        $('.sortable_item').each(function() {
            var nameElement = $(this).find('.small.fw-bold');
            if (nameElement.text() === businessName) {
                // Update the address display
                $(this).find('.text-xs.text-muted').html(newAddress);
                // Update the data-location attribute
                $(this).find('[data-location]').attr('data-location', newAddress);
                return false; // break the loop
            }
        });
    }
    
    // Create custom directions panel
    function createCustomDirectionsPanel(response, locations) {
        var panel = document.getElementById('directions-panel');
        var html = '<div style="padding: 10px;">';
       // html += '<h4 style="margin-bottom: 15px; color: #1a73e8;">Turn-by-Turn Directions</h4>';
        
        console.log('Creating directions for route with', response.routes[0].legs.length, 'legs');
        console.log('Locations:', locations.map(l => l.name));
        
        // Add each leg of the journey
        response.routes[0].legs.forEach(function(leg, index) {
            var fromLocation = locations[index];
            var toLocation = locations[index + 1];
            
            // For circular route, last leg returns to start
            if (!toLocation && index === response.routes[0].legs.length - 1) {
                toLocation = locations[0];
            }
            
            console.log('Leg', index, ':', fromLocation.name, '->', toLocation ? toLocation.name : 'undefined');
            
            html += '<div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">';
            
            // From section
            html += '<div style="margin-bottom: 10px;">';
            html += '<div style="font-weight: bold; color: #1a73e8; font-size: 16px;">';
            html += '<span style="background: #1a73e8; color: white; padding: 2px 8px; border-radius: 50%; margin-right: 8px;">' + (index + 1) + '</span>';
            html += fromLocation.name;
            html += '</div>';
            // Remove USA from address display
            var cleanStartAddress = leg.start_address.replace(/, USA$/, '').replace(/, United States$/, '');
            html += '<div style="color: #5f6368; font-size: 14px; margin-left: 35px; font-weight: bold;">' + cleanStartAddress + '</div>';
            html += '</div>';
            
            // Driving directions
            html += '<div style="margin-left: 35px; padding-left: 15px; border-left: 2px dashed #dadce0; position: relative;">';
            html += '<ul style="margin-top: 10px; font-size: 14px; position: relative;">';
            leg.steps.forEach(function(step, stepIndex) {
                // Strip HTML tags
                var instruction = step.instructions.replace(/<[^>]*>/g, '');
                
                // Check if this instruction contains "Destination will be"
                var destinationMatch = instruction.match(/(.*?)(Destination will be.*)/);
                
                if (destinationMatch) {
                    // Split into two parts: main instruction and destination part
                    var mainInstruction = destinationMatch[1].trim();
                    var destinationPart = destinationMatch[2];
                    
                    // Add main instruction if it exists
                    if (mainInstruction) {
                        html += '<li>';
                        html += mainInstruction;
                        html += ' <span style="color: #9aa0a6;">(' + step.distance.text + ')</span>';
                        html += '</li>';
                    }
                    
                    // Add destination part as separate line with green checkmark
                    html += '<li class="text-success" style="list-style: none; position: relative; padding-left: 25px;">';
                    html += '<span style="position: absolute; left: -7px; font-size: 16px;">✓</span>';
                    html += destinationPart;
                    html += '</li>';
                } else {
                    // Normal instruction without destination
                    html += '<li>';
                    html += instruction;
                    html += ' <span style="color: #9aa0a6;">(' + step.distance.text + ')</span>';
                    html += '</li>';
                }
            });
            html += '</ul>';
            
            // Distance and time at the end
            html += '<div style="background: white; padding: 8px; border-radius: 4px; margin-top: 10px;">';
            html += '<span style="color: #1a73e8; font-weight: bold;">' + leg.distance.text + '</span>';
            html += ' · ';
            html += '<span style="color: #5f6368;">' + leg.duration.text + '</span>';
            html += '</div>';
            
            html += '</div>';
            
            html += '</div>';
        });
        
        // Add final return to home section
        if (response.routes[0].legs.length === locations.length) {
            // This means we have a circular route returning to home
            var lastLeg = response.routes[0].legs[response.routes[0].legs.length - 1];
            var lastStopNumber = locations.length;
            
            html += '<div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">';
            html += '<div style="margin-bottom: 10px;">';
            html += '<div style="font-weight: bold; color: #1a73e8; font-size: 16px;">';
            html += '<span style="background: #1a73e8; color: white; padding: 2px 8px; border-radius: 50%; margin-right: 8px;">' + lastStopNumber + '</span>';
            html += 'Return to Home';
            html += '</div>';
            // Remove USA from address display
            var cleanEndAddress = lastLeg.end_address.replace(/, USA$/, '').replace(/, United States$/, '');
            html += '<div style="color: #5f6368; font-size: 14px; margin-left: 35px; font-weight: bold;">' + cleanEndAddress + '</div>';
            html += '</div>';
            html += '</div>';
        }
        
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
    
    function loadDirections() {
        console.log('Loading directions test...');
        
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            alert('Google Maps not loaded yet!');
            return;
        }
        
        // Initialize route map
        var routeMapElement = document.getElementById('route-map');
        routeMap = new google.maps.Map(routeMapElement, {
            zoom: 13,
            center: {lat: 39.7392, lng: -104.9903}, // Denver, CO
            mapId: '<?php echo $sitesettings['GOOGLEAPI']['mapid'] ?? '9cd54b1058579fe87b380337'; ?>' // Birthday Gold Tour Map ID for AdvancedMarkerElement
        });
        
        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
            map: routeMap,
            // Do not use default panel since we are creating custom one
            // panel: document.getElementById('directions-panel'),
            suppressMarkers: true  // We'll create custom numbered markers
        });
        
        console.log('Locations array:', locations);
        console.log('Number of locations:', locations.length);
        
        // Check if we have enough locations
        if (locations.length < 2) {
            console.error('Not enough locations for a tour');
            document.getElementById('directions-panel').innerHTML = '<div class="alert alert-warning">No businesses found for this date. Add businesses to your tour first.</div>';
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
            document.getElementById('directions-panel').innerHTML = '<div class="alert alert-warning">All businesses are more than 100 miles away. No route can be calculated.</div>';
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
        
        console.log('Waypoints:', waypoints);
        console.log('Route request:', request);
        console.log('Calculating route...');
        directionsService.route(request, function(response, status) {
            if (status === 'OK') {
                console.log('Route calculated successfully');
                directionsRenderer.setDirections(response);
                
                // Get the optimized waypoint order
                var waypointOrder = response.routes[0].waypoint_order;
                console.log('Optimized waypoint order:', waypointOrder);
                
                // Reorder locations based on optimization
                var optimizedLocations = [locations[0]]; // Start with home
                waypointOrder.forEach(function(waypointIndex) {
                    // waypoint_order refers to the waypoints array indices
                    // Map back to the original location index
                    var originalLocationIndex = includedLocationIndices[waypointIndex + 1];
                    optimizedLocations.push(locations[originalLocationIndex]);
                });
                
                console.log('Optimized route:', optimizedLocations.map(l => l.name));
                
                // Create custom directions panel with optimized order
                createCustomDirectionsPanel(response, optimizedLocations);
                
                // Add numbered markers for each location
                var bounds = new google.maps.LatLngBounds();
                
                // Create markers for the route
                for (var i = 0; i < optimizedLocations.length; i++) {
                    var location = optimizedLocations[i];
                    var markerPosition;
                    
                    // Get position from the route legs
                    if (i === 0) {
                        // Starting location
                        markerPosition = response.routes[0].legs[0].start_location;
                    } else {
                        // Get the end location of the previous leg (which is this location)
                        markerPosition = response.routes[0].legs[i - 1].end_location;
                    }
                    
                    // Create pin element for AdvancedMarkerElement
                    var pinBackground = document.createElement('div');
                    pinBackground.style.backgroundColor = (i === 0) ? '#4285F4' : '#EA4335';
                    pinBackground.style.width = '30px';
                    pinBackground.style.height = '30px';
                    pinBackground.style.borderRadius = '50%';
                    pinBackground.style.border = '2px solid white';
                    pinBackground.style.display = 'flex';
                    pinBackground.style.alignItems = 'center';
                    pinBackground.style.justifyContent = 'center';
                    pinBackground.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
                    
                    var pinLabel = document.createElement('div');
                    pinLabel.textContent = (i + 1).toString();
                    pinLabel.style.color = 'white';
                    pinLabel.style.fontSize = '14px';
                    pinLabel.style.fontWeight = 'bold';
                    
                    pinBackground.appendChild(pinLabel);
                    
                    // Create a simple numbered marker using AdvancedMarkerElement
                    var marker = new google.maps.marker.AdvancedMarkerElement({
                        position: markerPosition,
                        map: routeMap,
                        title: location.name,
                        content: pinBackground
                    });
                    
                    bounds.extend(markerPosition);
                    
                    // Add click listener for info window
                    (function(marker, location) {
                        var infoContent = '<div style="padding: 10px;">';
                        infoContent += '<h4 style="margin: 0 0 5px 0; color: #1a73e8;">' + location.name + '</h4>';
                        if (location.reward) {
                            infoContent += '<p style="margin: 5px 0; color: #5f6368;"><strong>Reward:</strong> ' + location.reward + '</p>';
                        }
                        infoContent += '<p style="margin: 5px 0; color: #5f6368; font-size: 0.9em;">' + location.address + '</p>';
                        infoContent += '</div>';
                        
                        var infoWindow = new google.maps.InfoWindow({
                            content: infoContent
                        });
                        
                        marker.addListener('click', function() {
                            infoWindow.open({
                                anchor: marker,
                                map: routeMap
                            });
                        });
                    })(marker, location);
                }
                
                // Fit map to show all markers
                routeMap.fitBounds(bounds);
                
                /* Old code
                optimizedLocations.forEach(function(location, index) {
                    var position;
                    
                    // For a circular route returning to home:
                    // - Home (index 0) is at the start of leg 0
                    // - Business at index i is at the start of leg i
                    // - The last position (return to home) is at the end of the last leg
                    
                    if (index === 0) {
                        // Home location - start of first leg
                        position = response.routes[0].legs[0].start_location;
                    } else if (index < response.routes[0].legs.length) {
                        // Business locations - start of their respective legs
                        position = response.routes[0].legs[index].start_location;
                    } else {
                        // This shouldn't happen in a circular route, but just in case
                        position = response.routes[0].legs[response.routes[0].legs.length - 1].end_location;
                    }
                    
                    console.log('Creating marker for:', location.name, 'at position:', position);
                    
                    // Create numbered marker
                    var markerColor = (location.type === 'start' || location.type === 'home') ? '4285F4' : 'EA4335';
                    var markerNumber = index + 1;
                    
                    var marker = new google.maps.Marker({
                        position: position,
                        map: routeMap,
                        title: location.name + '\n' + location.address,
                        icon: {
                            url: 'https://chart.googleapis.com/chart?chst=d_map_pin_letter_withshadow&chld=' + markerNumber + '|' + markerColor + '|FFFFFF',
                            scaledSize: new google.maps.Size(40, 60),
                            origin: new google.maps.Point(0, 0),
                            anchor: new google.maps.Point(20, 60)
                        }
                    });
                    
                    // Add info window with business name prominently displayed
                    var infoContent = '<div style="padding: 10px;">';
                    infoContent += '<h4 style="margin: 0 0 5px 0; color: #1a73e8;">' + location.name + '</h4>';
                    if (location.reward) {
                        infoContent += '<p style="margin: 5px 0; color: #5f6368;"><strong>Reward:</strong> ' + location.reward + '</p>';
                    }
                    infoContent += '<p style="margin: 5px 0; color: #5f6368; font-size: 0.9em;">' + location.address + '</p>';
                    infoContent += '</div>';
                    
                    var infoWindow = new google.maps.InfoWindow({
                        content: infoContent
                    });
                    
                    marker.addListener('click', function() {
                        infoWindow.open(routeMap, marker);
                    });
                });
                */
                
                // Log route details
                var route = response.routes[0];
                console.log('Total distance: ' + route.legs.reduce((sum, leg) => sum + leg.distance.value, 0) / 1000 + ' km');
                console.log('Total duration: ' + Math.round(route.legs.reduce((sum, leg) => sum + leg.duration.value, 0) / 60) + ' minutes');
                
            } else {
                console.error('Directions request failed:', status);
                alert('Directions request failed: ' + status);
            }
        });
    }
    </script>

<script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
<script>
// Wait for page to fully load
window.addEventListener('load', function() {
    console.log('Initializing sortable...');
    
    // Check if jQuery and jQuery UI are loaded
    if (typeof jQuery !== 'undefined' && typeof jQuery.ui !== 'undefined') {
        // Initialize sortable for tour reordering
        jQuery("#sortable").sortable({
            handle: ".sortable_item_handle",
            axis: "y",
            items: ".sortable_item:not(.out-of-range)",
            start: function(event, ui) {
                jQuery(this).addClass('sorting-active');
                console.log('Started sorting');
            },
            stop: function(event, ui) {
                jQuery(this).removeClass('sorting-active');
                console.log('Stopped sorting');
                // Show the "Draw New Map" button after reordering
                const drawNewMapButton = document.getElementById("draw_map");
                if (drawNewMapButton) {
                    drawNewMapButton.style.display = "inline-block";
                }
            }
        });
        console.log('Sortable initialized');
    } else {
        console.error('jQuery or jQuery UI not loaded');
    }
});

// Function to reload map after reordering
function DrawNewMap() {
    console.log('DrawNewMap called');
    console.log('Original locations:', locations);
    
    // Update locations based on new order
    var newLocations = [{
        name: locations[0].name, // Keep the original name
        address: locations[0].address,
        lat: locations[0].lat,
        lng: locations[0].lng,
        type: "home"
    }];
    
    $('.sortable_item').each(function() {
        var $item = $(this);
        var isOutOfRange = $item.data('out-of-range') === 'true';
        
        console.log('Processing item, out of range:', isOutOfRange);
        
        // Skip out of range businesses
        if (isOutOfRange) {
            return true; // continue to next iteration
        }
        
        var businessName = $item.find('.small.fw-bold').text().trim();
        
        // Remove the distance badge text if present
        var distanceBadgeText = $item.find('.out-of-range-badge').text();
        if (distanceBadgeText) {
            businessName = businessName.replace(distanceBadgeText, '').trim();
        }
        
        console.log('Business name after cleanup:', businessName);
        
        // Find the business in original locations
        var found = false;
        for (var i = 1; i < locations.length; i++) {
            if (locations[i].name === businessName) {
                newLocations.push(locations[i]);
                found = true;
                console.log('Found business in locations:', locations[i]);
                break;
            }
        }
        
        if (!found) {
            console.log('Business not found in locations array:', businessName);
        }
    });
    
    console.log('New locations after reorder:', newLocations);
    
    // Update global locations array
    locations = newLocations;
    
    // Reload the map with new order
    loadDirections();
    
    // Hide the button after redrawing
    document.getElementById("draw_map").style.display = "none";
}

// Location picker functionality
var locationPickerMap;
var selectedLocation = null;
var currentBusinessElement = null;
var currentBusinessIndex = -1;
var currentCompanyId = null;
var locationMarkers = [];

// Simple jQuery click handler for all pick location links
$(document).on('click', '.pick-location', function(e) {
    e.preventDefault();
    
    var $this = $(this);
    currentCompanyId = $this.data('company-id');
    var businessName = $this.data('company-name');
    
    console.log('Pick location clicked:', businessName, 'ID:', currentCompanyId);
    
    // Set modal title
    $('#modal-business-name').text(businessName);
    
    // Clear previous results
    $('#location-results').empty();
    $('#force-location-check').prop('checked', false);
    $('#force-location-container').hide();
    $('#confirm-location').prop('disabled', true);
    
    // Show modal
    $('#locationPickerModal').modal('show');
    
    // Search for locations when modal opens
    $('#locationPickerModal').off('shown.bs.modal').on('shown.bs.modal', function() {
        // Initialize map if needed
        if (!locationPickerMap) {
            locationPickerMap = new google.maps.Map(document.getElementById('location-map'), {
                zoom: 10,
                center: {lat: 39.7392, lng: -104.9903},
                mapId: '<?php echo $sitesettings['GOOGLEAPI']['mapid'] ?? '9cd54b1058579fe87b380337'; ?>' // Birthday Gold Tour Map ID for AdvancedMarkerElement
            });
        }
        
        searchBusinessLocations();
    });
});

function searchBusinessLocations() {
    console.log('searchBusinessLocations called');
    console.log('currentCompanyId:', currentCompanyId);
    console.log('Home location:', locations[0]);
    
    if (!currentCompanyId) {
        document.getElementById('location-results').innerHTML = '<div class="alert alert-danger">No company selected</div>';
        return;
    }
    
    // Get home coordinates - make sure they're numbers
    var homeLat = locations[0].lat ? parseFloat(locations[0].lat) : 39.7392;
    var homeLng = locations[0].lng ? parseFloat(locations[0].lng) : -104.9903;
    var radius = document.getElementById('radius-select').value || 25;
    
    console.log('Using coordinates:', homeLat, homeLng, 'Radius:', radius);
    
    // Show loading
    document.getElementById('location-results').innerHTML = '<div class="text-center p-3">Loading locations...</div>';
    
    // Make AJAX call to current page
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
        console.log('API Response:', data);
        if (data.success && data.locations && data.locations.length > 0) {
            displayLocationResults(data.locations);
        } else if (data.success && data.locations && data.locations.length === 0) {
            document.getElementById('location-results').innerHTML = '<div class="alert alert-warning">No locations found within ' + radius + ' miles for this company.</div>';
        } else if (data.message) {
            document.getElementById('location-results').innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        } else {
            document.getElementById('location-results').innerHTML = '<div class="alert alert-warning">No locations found</div>';
        }
    })
    .fail(function(xhr, status, error) {
        console.error('AJAX error:', status, error);
        console.error('Response:', xhr.responseText);
        document.getElementById('location-results').innerHTML = '<div class="alert alert-danger">Error loading locations: ' + error + '</div>';
    });
}

function displayLocationResults(locations) {
    var resultsDiv = document.getElementById('location-results');
    resultsDiv.innerHTML = '';
    
    // Clear existing location markers (but keep home marker)
    locationMarkers = locationMarkers.filter(marker => {
        if (marker.getTitle() !== 'Starting Location') {
            marker.setMap(null);
            return false;
        }
        return true;
    });
    
    if (locations.length === 0) {
        resultsDiv.innerHTML = '<div class="alert alert-info">No locations found in this radius</div>';
        return;
    }
    
    // Define colors for markers (up to 10 different colors)
    var markerColors = ['#EA4335', '#FBBC04', '#34A853', '#4285F4', '#9C27B0', 
                       '#FF5722', '#795548', '#607D8B', '#E91E63', '#00BCD4'];
    
    // Create bounds to fit all markers
    var bounds = new google.maps.LatLngBounds();
    // Add home location to bounds
    if (window.locations && window.locations[0] && window.locations[0].lat && window.locations[0].lng) {
        bounds.extend({lat: parseFloat(window.locations[0].lat), lng: parseFloat(window.locations[0].lng)});
    }
    
    locations.forEach(function(location, index) {
        var color = markerColors[index % markerColors.length];
        
        var item = document.createElement('a');
        item.className = 'list-group-item list-group-item-action';
        item.href = '#';
        item.setAttribute('data-location-index', index);
        item.innerHTML = '<div class="d-flex justify-content-between align-items-center">' +
                        '<div class="d-flex align-items-start">' +
                        '<div style="width: 20px; height: 20px; background-color: ' + color + '; border-radius: 50%; margin-right: 10px; flex-shrink: 0;"></div>' +
                        '<div>' +
                        '<strong>' + location.full_address + '</strong><br>' +
                        '<small class="text-muted">' + location.distance + ' miles away</small>' +
                        '</div>' +
                        '</div>' +
                        '</div>';
        
        item.onclick = function(e) {
            e.preventDefault();
            selectLocation(location, index);
            
            // Highlight selected
            document.querySelectorAll('#location-results .list-group-item').forEach(function(el) {
                el.classList.remove('active');
            });
            this.classList.add('active');
        };
        
        resultsDiv.appendChild(item);
        
        // Add marker to map
        var position = {lat: parseFloat(location.latitude), lng: parseFloat(location.longitude)};
        // Create pin element for location picker
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
        
        // Store the marker reference
        marker.locationIndex = index;
        location.marker = marker;
        
        bounds.extend(position);
        locationMarkers.push(marker);
        
        marker.addListener('click', function() {
            selectLocation(location, index);
            document.querySelectorAll('#location-results .list-group-item')[index].click();
        });
    });
    
    // Fit map to show all markers
    locationPickerMap.fitBounds(bounds);
}

function selectLocation(location, index) {
    selectedLocation = {
        location_id: location.location_id,
        address: location.full_address,
        lat: parseFloat(location.latitude),
        lng: parseFloat(location.longitude)
    };
    
    console.log('Location selected:', selectedLocation);
    
    // Enable confirm button and show force location option
    document.getElementById('confirm-location').disabled = false;
    document.getElementById('force-location-container').style.display = 'block';
    
    // Animate all markers back to normal size
    locationMarkers.forEach(function(marker) {
        if (marker.getTitle() !== 'Starting Location') {
            marker.setAnimation(null);
            // Reset to normal size
            var icon = marker.getIcon();
            icon.scale = 10;
            marker.setIcon(icon);
        }
    });
    
    // Animate selected marker
    if (location.marker) {
        location.marker.setAnimation(google.maps.Animation.BOUNCE);
        // Make selected marker larger
        var icon = location.marker.getIcon();
        icon.scale = 15;
        location.marker.setIcon(icon);
        
        // Stop bouncing after 2 seconds
        setTimeout(function() {
            location.marker.setAnimation(null);
        }, 2000);
    }
    
    locationPickerMap.setCenter({lat: selectedLocation.lat, lng: selectedLocation.lng});
    locationPickerMap.setZoom(15);
}

// Handle radius change
$(document).on('change', '#radius-select', function() {
    searchBusinessLocations();
});

// Confirm location selection
$(document).on('click', '#confirm-location', function() {
    console.log('Confirm clicked. selectedLocation:', selectedLocation, 'currentCompanyId:', currentCompanyId);
    
    if (selectedLocation && currentCompanyId) {
        var forceLocation = document.getElementById('force-location-check').checked;
        
        // Update the tour with the selected location
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
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text(); // Get text first to debug
        })
        .then(text => {
            console.log('Response text:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    console.log('Location updated successfully');
                
                // Find the business element by company ID
                var businessElement = document.querySelector('.sortable_item[data-company-id="' + currentCompanyId + '"]');
                if (businessElement) {
                    // Update the display
                    var addressDiv = businessElement.querySelector('.text-xs.text-muted');
                    if (addressDiv) {
                        var html = selectedLocation.address;
                        if (forceLocation) {
                            html += ' <span class="badge bg-info text-white ms-1">Forced</span>';
                        }
                        addressDiv.innerHTML = html;
                    }
                    
                    // Update the data-location attribute
                    var locationDiv = businessElement.querySelector('[data-location]');
                    if (locationDiv) {
                        locationDiv.setAttribute('data-location', selectedLocation.address);
                    }
                }
                
                // Update locations array if we can find the business
                for (var i = 0; i < locations.length; i++) {
                    if (locations[i].company_id == currentCompanyId) {
                        locations[i].address = selectedLocation.address;
                        locations[i].lat = selectedLocation.lat;
                        locations[i].lng = selectedLocation.lng;
                        break;
                    }
                }
                
                // Show update map button
                var drawMapBtn = document.getElementById('draw_map');
                if (drawMapBtn) {
                    drawMapBtn.style.display = 'inline-block';
                }
                
                // Close modal using jQuery
                $('#locationPickerModal').modal('hide');
                
                // Reset
                selectedLocation = null;
                currentCompanyId = null;
                } else {
                    alert('Failed to update location: ' + (data.message || 'Unknown error'));
                }
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                console.error('Response was:', text);
                alert('Error: Invalid response from server. Check console for details.');
            }
        })
        .catch(error => {
            console.error('Error updating location:', error);
            alert('Error updating location: ' + error.message);
        });
    }
});

</script>

</div><!-- End col-md-9 -->
</div><!-- End row -->
</div><!-- End container -->

<?php if (isset($_GET['debug'])): ?>
<!-- Debug: Show tour companies and locations -->
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h5>Tour Debug Info for <?php echo $date; ?></h5>
        </div>
        <div class="card-body">
            <h6>Companies on Tour:</h6>
            <pre><?php echo json_encode($listofcompanies, JSON_PRETTY_PRINT); ?></pre>
            
            <h6>Home Location:</h6>
            <pre><?php echo json_encode([
                'address' => $homeaddress,
                'lat' => $home_lat,
                'lng' => $home_lng
            ], JSON_PRETTY_PRINT); ?></pre>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Bootstrap JS if not already loaded -->
<script>
if (typeof bootstrap === 'undefined') {
    var bootstrapScript = document.createElement('script');
    bootstrapScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js';
    document.head.appendChild(bootstrapScript);
}
</script>

<!-- Change Home Location Modal -->
<div class="modal fade" id="changeLocationModal" tabindex="-1" aria-labelledby="changeLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeLocationModalLabel">Change Starting Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

<script>
// Home location change functionality
var homeAutocomplete;
var selectedHomeLocation = null;
var homeLocationMap = null;
var homeLocationMarker = null;

// Wait for Bootstrap to load
function initializeHomeLocationModal() {
    // Check if Bootstrap is loaded
    if (typeof bootstrap === 'undefined') {
        setTimeout(initializeHomeLocationModal, 100);
        return;
    }
    
    // Initialize when modal opens
    document.getElementById('changeLocationModal').addEventListener('shown.bs.modal', function() {
    // Select all text in the input field when modal opens
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
                // Remove USA from the end of the address
                var cleanAddress = place.formatted_address;
                cleanAddress = cleanAddress.replace(/, USA$/, '');
                cleanAddress = cleanAddress.replace(/, United States$/, '');
                
                selectedHomeLocation = {
                    address: cleanAddress,
                    lat: place.geometry.location.lat(),
                    lng: place.geometry.location.lng()
                };
                
                // Show map
                document.getElementById('home-location-map').style.display = 'block';
                
                if (!homeLocationMap) {
                    homeLocationMap = new google.maps.Map(document.getElementById('home-location-map'), {
                        center: place.geometry.location,
                        zoom: 15,
                        mapId: '<?php echo $sitesettings['GOOGLEAPI']['mapid'] ?? '9cd54b1058579fe87b380337'; ?>' // Birthday Gold Tour Map ID for AdvancedMarkerElement
                    });
                }
                
                homeLocationMap.setCenter(place.geometry.location);
                
                if (homeLocationMarker) {
                    homeLocationMarker.map = null;
                }
                
                // Create home pin element
                var homePin = document.createElement('div');
                homePin.style.backgroundColor = '#4285F4';
                homePin.style.width = '30px';
                homePin.style.height = '30px';
                homePin.style.borderRadius = '50%';
                homePin.style.border = '2px solid white';
                homePin.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
                homePin.style.display = 'flex';
                homePin.style.alignItems = 'center';
                homePin.style.justifyContent = 'center';
                
                var homeIcon = document.createElement('div');
                homeIcon.textContent = '🏠';
                homeIcon.style.fontSize = '16px';
                
                homePin.appendChild(homeIcon);
                
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

    // Confirm home location update
    document.getElementById('confirm-home-location').addEventListener('click', function() {
    if (selectedHomeLocation) {
        // Update via AJAX
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
                // Remove USA from display address
                var displayAddress = selectedHomeLocation.address.replace(/, USA$/, '').replace(/, United States$/, '');
                
                // Update the display
                document.querySelector('[data-location]').setAttribute('data-location', selectedHomeLocation.address);
                document.querySelector('.text-xs.text-muted').textContent = displayAddress;
                
                // Update our locations array
                locations[0].address = selectedHomeLocation.address;
                locations[0].lat = selectedHomeLocation.lat;
                locations[0].lng = selectedHomeLocation.lng;
                
                // Mark all business locations as needing re-geocoding since search context changed
                console.log('Home location changed. Marking businesses for re-geocoding...');
                console.log('Current locations:', locations);
                
                locations.forEach(function(location, index) {
                    if (location.type === 'business' && index > 0) {
                        console.log('Marking for re-geocoding:', location.name);
                        location.needsGeocoding = true;
                        // Clear existing coordinates to force re-geocoding
                        location.lat = null;
                        location.lng = null;
                    }
                });
                
                console.log('Updated locations array:', locations);
                
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('changeLocationModal')).hide();
                
                // Re-geocode all businesses with new home location context
                console.log('Calling geocodeMissingAddresses...');
                geocodeMissingAddresses();
            }
        });
    }
    });
}

// Initialize modal when page loads
window.addEventListener('load', initializeHomeLocationModal);

// Manual open function as fallback
function openChangeLocationModal(event) {
    event.preventDefault();
    
    // Try Bootstrap modal first
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var modal = new bootstrap.Modal(document.getElementById('changeLocationModal'));
        modal.show();
    } else {
        // Fallback - show modal manually
        var modalEl = document.getElementById('changeLocationModal');
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        modalEl.setAttribute('aria-hidden', 'false');
        
        // Add backdrop
        var backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);
        
        // Add close handlers
        modalEl.querySelector('.btn-close').onclick = function() {
            closeChangeLocationModal();
        };
        modalEl.querySelector('[data-bs-dismiss="modal"]').onclick = function() {
            closeChangeLocationModal();
        };
    }
}

function closeChangeLocationModal() {
    var modalEl = document.getElementById('changeLocationModal');
    modalEl.classList.remove('show');
    modalEl.style.display = 'none';
    modalEl.setAttribute('aria-hidden', 'true');
    
    // Remove backdrop
    var backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
}

// Send to phone functionality
function sendToPhone() {
    console.log('Send to phone clicked');
    
    // Check if we have locations
    if (locations.length < 2) {
        alert('Please add businesses to your tour first.');
        return;
    }
    
    // Filter out out-of-range businesses
    var tourLocations = locations.filter(function(loc, index) {
        return index === 0 || !loc.isOutOfRange; // Include home and in-range businesses
    });
    
    if (tourLocations.length < 2) {
        alert('No in-range businesses found for navigation.');
        return;
    }
    
    // Build waypoints string for navigation URL
    var origin = encodeURIComponent(tourLocations[0].address);
    var destination = encodeURIComponent(tourLocations[tourLocations.length - 1].address);
    var waypoints = [];
    
    // Add intermediate stops as waypoints
    for (var i = 1; i < tourLocations.length - 1; i++) {
        waypoints.push(encodeURIComponent(tourLocations[i].address));
    }
    
    // Determine phone type and build appropriate URL
    var phoneType = '<?php echo $current_user_data['profile_phone_type'] ?? 'unknown'; ?>';
    var navigationUrl = '';
    
    if (phoneType === 'iphone' || phoneType === 'ios') {
        // Apple Maps URL format
        navigationUrl = 'https://maps.apple.com/?saddr=' + origin + '&daddr=' + destination;
        if (waypoints.length > 0) {
            // Apple Maps doesn't support waypoints in URL, so we'll use Google Maps as fallback
            navigationUrl = 'https://maps.google.com/maps?saddr=' + origin + '&daddr=' + destination;
            if (waypoints.length > 0) {
                navigationUrl += '&waypoints=' + waypoints.join('|');
            }
            navigationUrl += '&dirflg=d'; // Driving directions
        }
    } else {
        // Google Maps URL format (Android and fallback)
        navigationUrl = 'https://maps.google.com/maps?saddr=' + origin + '&daddr=' + destination;
        if (waypoints.length > 0) {
            navigationUrl += '&waypoints=' + waypoints.join('|');
        }
        navigationUrl += '&dirflg=d'; // Driving directions
    }
    
    console.log('Navigation URL:', navigationUrl);
    console.log('Phone type:', phoneType);
    
    // Check for debug mode
    var isDebug = window.location.search.includes('debug');
    
    // Send to server to shorten and text
    $.ajax({
        url: '/myaccount/tour-v2.php' + (isDebug ? '?debug=1' : ''),
        method: 'POST',
        data: {
            action: 'send_to_phone',
            navigation_url: navigationUrl,
            phone_type: phoneType,
            tour_date: '<?php echo $date; ?>',
            debug: isDebug ? 1 : 0
        },
        success: function(response) {
            if (response.debug) {
                console.log('=== SEND TO PHONE DEBUG INFO ===');
                console.log('Original URL:', response.debug.original_url);
                console.log('Shortened URL:', response.debug.shortened_url);
                console.log('Apple Maps URL:', response.debug.apple_maps_url);
                console.log('Google Maps URL:', response.debug.google_maps_url);
                console.log('Phone Type:', response.debug.phone_type);
                console.log('Phone Number:', response.debug.phone_number);
                console.log('Shortcode Data:', response.debug.shortcode_data);
                if (response.sms_result) {
                    console.log('SMS Result:', response.sms_result);
                }
                if (response.sms_error) {
                    console.log('SMS Error:', response.sms_error);
                }
            }
            
            if (response.preview) {
                console.log('=== SMS PREVIEW ===');
                console.log('Message:', response.preview.message);
                console.log('Short URL:', response.preview.short_url);
                console.log('Phone:', response.preview.phone_number);
            }
            
            if (response.success) {
                alert(response.message);
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

<?php
// Footer breaks Google Maps, so we skip it
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