<?php
// abo_enrichlocations.php - Enrich location data with Google Places API
// Part of the Automation Business Onboarding (ABO) system
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get company ID - support both encoded and raw for debugging
$specific_company_id = null;
$specific_location_id = null;

if (isset($_GET['rawid'])) {
    // Debug mode - use raw ID directly
    $specific_company_id = intval($_GET['rawid']);
} elseif (isset($_GET['id'])) {
    // Production mode - decode the ID
    $encoded_id = $_GET['id'];
    $specific_company_id = $qik->decodeID($encoded_id);
}

if (isset($_GET['location_id'])) {
    $specific_location_id = intval($_GET['location_id']);
}

$result = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'processor' => 'abo_enrichlocations',
    'processed' => 0,
    'successful' => 0,
    'failed' => 0,
    'errors' => []
];

try {
    // Get locations to process
    if ($specific_location_id) {
        // Process specific location
        $sql = "SELECT l.*, c.company_name 
                FROM bg_company_locations l
                INNER JOIN bg_companies c ON l.company_id = c.company_id
                WHERE l.location_id = :location_id
                AND l.status = 'active'
                AND (l.is_verified = 0 OR l.business_hours IS NULL OR l.latitude IS NULL)
                LIMIT 1";
        $params = ['location_id' => $specific_location_id];
    } elseif ($specific_company_id) {
        // Process all unverified locations for specific company
        $sql = "SELECT l.*, c.company_name 
                FROM bg_company_locations l
                INNER JOIN bg_companies c ON l.company_id = c.company_id
                WHERE l.company_id = :company_id
                AND l.status = 'active'
                AND (l.is_verified = 0 OR l.business_hours IS NULL OR l.latitude IS NULL)
                ORDER BY l.create_dt ASC";
        $params = ['company_id' => $specific_company_id];
    } else {
        // Get next unverified location
        $sql = "SELECT l.*, c.company_name 
                FROM bg_company_locations l
                INNER JOIN bg_companies c ON l.company_id = c.company_id
                WHERE l.status = 'active'
                AND (l.is_verified = 0 OR l.business_hours IS NULL OR l.latitude IS NULL)
                ORDER BY l.create_dt ASC
                LIMIT 5";
        $params = [];
    }
    
    $stmt = $database->query($sql, $params);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($locations)) {
        $result['message'] = 'No locations pending enrichment';
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    // Get Google API key from config
    $google_api_key = $GOOGLECONFIG['api_key'] ?? null;
    
    if (!$google_api_key) {
        throw new Exception('Google API key not configured');
    }
    
    foreach ($locations as $location) {
        $result['processed']++;
        
        try {
            // Build search query
            $search_query = $location['company_name'];
            if (!empty($location['address'])) {
                $search_query .= ' ' . $location['address'];
            }
            if (!empty($location['city'])) {
                $search_query .= ' ' . $location['city'];
            }
            if (!empty($location['state'])) {
                $search_query .= ' ' . $location['state'];
            }
            if (!empty($location['zip_code'])) {
                $search_query .= ' ' . $location['zip_code'];
            }
            
            // Google Places Text Search API
            $search_url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?' . http_build_query([
                'query' => $search_query,
                'key' => $google_api_key
            ]);
            
            $ch = curl_init($search_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception("Google API HTTP error: $httpCode");
            }
            
            $search_data = json_decode($response, true);
            
            if ($search_data['status'] !== 'OK' || empty($search_data['results'])) {
                // Try without company name
                $search_query = trim($location['address'] . ' ' . $location['city'] . ' ' . $location['state'] . ' ' . $location['zip_code']);
                
                $search_url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?' . http_build_query([
                    'query' => $search_query,
                    'key' => $google_api_key
                ]);
                
                $ch = curl_init($search_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                
                $response = curl_exec($ch);
                curl_close($ch);
                
                $search_data = json_decode($response, true);
                
                if ($search_data['status'] !== 'OK' || empty($search_data['results'])) {
                    throw new Exception("No results found for location");
                }
            }
            
            // Get the first result (most relevant)
            $place = $search_data['results'][0];
            $place_id = $place['place_id'];
            
            // Get detailed place information
            $details_url = 'https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query([
                'place_id' => $place_id,
                'fields' => 'name,formatted_address,formatted_phone_number,international_phone_number,opening_hours,website,geometry,address_components,business_status,types',
                'key' => $google_api_key
            ]);
            
            $ch = curl_init($details_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $details_data = json_decode($response, true);
            
            if ($details_data['status'] !== 'OK' || empty($details_data['result'])) {
                throw new Exception("Could not get place details");
            }
            
            $place_details = $details_data['result'];
            
            // Extract address components
            $street_number = '';
            $route = '';
            $city = '';
            $state = '';
            $zip_code = '';
            $country = '';
            
            foreach ($place_details['address_components'] as $component) {
                $types = $component['types'];
                
                if (in_array('street_number', $types)) {
                    $street_number = $component['long_name'];
                } elseif (in_array('route', $types)) {
                    $route = $component['long_name'];
                } elseif (in_array('locality', $types)) {
                    $city = $component['long_name'];
                } elseif (in_array('administrative_area_level_1', $types)) {
                    $state = $component['short_name'];
                } elseif (in_array('postal_code', $types)) {
                    $zip_code = $component['long_name'];
                } elseif (in_array('country', $types)) {
                    $country = $component['long_name'];
                }
            }
            
            // Build complete address
            $complete_address = trim($street_number . ' ' . $route);
            
            // Format business hours
            $business_hours = null;
            if (!empty($place_details['opening_hours']['weekday_text'])) {
                $business_hours = json_encode($place_details['opening_hours']);
            }
            
            // Update location with enriched data
            $update_sql = "UPDATE bg_company_locations SET
                          address = :address,
                          city = :city,
                          state = :state,
                          zip_code = :zip_code,
                          country = :country,
                          phone_number = :phone_number,
                          latitude = :latitude,
                          longitude = :longitude,
                          business_hours = :business_hours,
                          google_place_id = :google_place_id,
                          is_verified = 1,
                          modify_dt = NOW()
                          WHERE location_id = :location_id";
            
            $update_params = [
                'address' => !empty($complete_address) ? $complete_address : $location['address'],
                'city' => !empty($city) ? $city : $location['city'],
                'state' => !empty($state) ? $state : $location['state'],
                'zip_code' => !empty($zip_code) ? $zip_code : $location['zip_code'],
                'country' => !empty($country) ? $country : 'United States',
                'phone_number' => $place_details['formatted_phone_number'] ?? $location['phone_number'],
                'latitude' => $place_details['geometry']['location']['lat'],
                'longitude' => $place_details['geometry']['location']['lng'],
                'business_hours' => $business_hours,
                'google_place_id' => $place_id,
                'location_id' => $location['location_id']
            ];
            
            $database->query($update_sql, $update_params);
            
            // Store additional attributes
            if (!empty($place_details['website'])) {
                $attr_sql = "INSERT INTO bg_company_locations_attributes 
                            (location_id, attribute_type, attribute_name, attribute_value, source, status, create_dt)
                            VALUES 
                            (:location_id, 'url', 'website', :value, 'google_places', 'active', NOW())
                            ON DUPLICATE KEY UPDATE
                            attribute_value = VALUES(attribute_value),
                            modify_dt = NOW()";
                $database->query($attr_sql, [
                    'location_id' => $location['location_id'],
                    'value' => $place_details['website']
                ]);
            }
            
            // Store business status
            if (!empty($place_details['business_status'])) {
                $attr_sql = "INSERT INTO bg_company_locations_attributes 
                            (location_id, attribute_type, attribute_name, attribute_value, source, status, create_dt)
                            VALUES 
                            (:location_id, 'status', 'business_status', :value, 'google_places', 'active', NOW())
                            ON DUPLICATE KEY UPDATE
                            attribute_value = VALUES(attribute_value),
                            modify_dt = NOW()";
                $database->query($attr_sql, [
                    'location_id' => $location['location_id'],
                    'value' => $place_details['business_status']
                ]);
            }
            
            // Track successful enrichment
            session_tracking('ABO location enriched', "Location {$location['location_id']} enriched with Google Places data");
            
            $result['successful']++;
            $result['enriched_locations'][] = [
                'location_id' => $location['location_id'],
                'company_name' => $location['company_name'],
                'address' => $update_params['address'],
                'city' => $update_params['city'],
                'state' => $update_params['state'],
                'google_place_id' => $place_id
            ];
            
        } catch (Exception $e) {
            $result['failed']++;
            $result['errors'][] = "Location {$location['location_id']}: " . $e->getMessage();
            session_tracking('ABO location enrichment error', "Location {$location['location_id']}: " . $e->getMessage());
        }
        
        // Rate limiting - Google Places API has quotas
        usleep(200000); // 200ms delay between requests
    }
    
    $result['message'] = "Processed {$result['processed']} locations: {$result['successful']} enriched, {$result['failed']} failed";
    
} catch (Exception $e) {
    $result['status'] = 'error';
    $result['errors'][] = $e->getMessage();
    session_tracking('ABO location enrichment fatal error', $e->getMessage());
}

// Output JSON response
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);