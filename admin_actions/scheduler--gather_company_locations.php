<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Configuration
$debug = isset($argv[1]) && $argv[1] == '--debug';
$limit = isset($argv[2]) ? intval($argv[2]) : 50; // Process X companies per run
$google_api_key = $sitesettings['GOOGLEAPI']['mainkey'];

echo "Starting company location gathering process...\n";
echo "Debug mode: " . ($debug ? "ON" : "OFF") . "\n";
echo "Processing limit: $limit companies\n\n";

// Get companies that need location data
// Priority: Companies with enrollments but no locations
$query = "SELECT DISTINCT c.company_id, c.company_name, c.city, c.state, c.country,
          COUNT(DISTINCT uc.user_id) as enrolled_users,
          COUNT(DISTINCT cl.location_id) as existing_locations,
          MAX(cl.modify_dt) as last_location_update
          FROM bg_companies c
          LEFT JOIN bg_user_companies uc ON c.company_id = uc.company_id 
            AND uc.status IN ('active', 'pending', 'selected')
          LEFT JOIN bg_company_locations cl ON c.company_id = cl.company_id 
            AND cl.status = 'active'
          WHERE c.status = 'finalized'
          GROUP BY c.company_id
          HAVING enrolled_users > 0 AND (existing_locations = 0 OR last_location_update < DATE_SUB(NOW(), INTERVAL 90 DAY))
          ORDER BY enrolled_users DESC, existing_locations ASC
          LIMIT :limit";

$stmt = $database->prepare($query);
$stmt->execute(['limit' => $limit]);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($companies) . " companies to process\n\n";

$processed = 0;
$locations_added = 0;
$errors = 0;

foreach ($companies as $company) {
    echo "Processing: " . $company['company_name'] . " (ID: " . $company['company_id'] . ")\n";
    echo "  - Enrolled users: " . $company['enrolled_users'] . "\n";
    echo "  - Existing locations: " . $company['existing_locations'] . "\n";
    
    // Build search query
    $search_query = $company['company_name'];
    if (!empty($company['city']) && !empty($company['state'])) {
        $search_query .= " near " . $company['city'] . ", " . $company['state'];
    } elseif (!empty($company['state'])) {
        $search_query .= " in " . $company['state'];
    }
    
    if ($debug) {
        echo "  - Search query: $search_query\n";
    }
    
    // Call Google Places Text Search API
    $url = "https://maps.googleapis.com/maps/api/place/textsearch/json";
    $params = [
        'query' => $search_query,
        'key' => $google_api_key,
        'fields' => 'place_id,name,formatted_address,geometry,types,rating,user_ratings_total'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        echo "  ERROR: Google API returned HTTP $http_code\n";
        $errors++;
        continue;
    }
    
    $data = json_decode($response, true);
    
    if ($data['status'] !== 'OK' || empty($data['results'])) {
        echo "  WARNING: No results found\n";
        if ($debug) {
            echo "  API Status: " . $data['status'] . "\n";
        }
        continue;
    }
    
    // Process results - can be multiple locations for chains
    $locations_for_company = 0;
    foreach ($data['results'] as $index => $place) {
        // Skip if we already have this place_id
        $check_stmt = $database->prepare("SELECT location_id FROM bg_company_locations_attributes 
                                         WHERE attribute_key = 'google_place_id' 
                                         AND attribute_value = :place_id");
        $check_stmt->execute(['place_id' => $place['place_id']]);
        if ($check_stmt->fetch()) {
            if ($debug) {
                echo "  - Skipping duplicate place_id: " . $place['place_id'] . "\n";
            }
            continue;
        }
        
        // Parse address components
        $address_parts = explode(',', $place['formatted_address']);
        $address = trim($address_parts[0] ?? '');
        $city = trim($address_parts[1] ?? '');
        $state_zip = trim($address_parts[2] ?? '');
        
        // Extract state and zip
        preg_match('/([A-Z]{2})\s*(\d{5}(?:-\d{4})?)/', $state_zip, $matches);
        $state = $matches[1] ?? '';
        $zip = $matches[2] ?? '';
        
        // Insert location
        $insert_stmt = $database->prepare("INSERT INTO bg_company_locations 
            (company_id, source, address, city, state, zip_code, country, 
             latitude, longitude, is_verified, status) 
            VALUES (:company_id, :source, :address, :city, :state, :zip, :country,
                    :lat, :lng, :verified, :status)");
        
        $insert_result = $insert_stmt->execute([
            'company_id' => $company['company_id'],
            'source' => 'google_places_api',
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'country' => 'United States',
            'lat' => $place['geometry']['location']['lat'],
            'lng' => $place['geometry']['location']['lng'],
            'verified' => 1,
            'status' => 'active'
        ]);
        
        if ($insert_result) {
            $location_id = $database->lastInsertId();
            $locations_for_company++;
            $locations_added++;
            
            // Store Google-specific attributes
            $attributes = [
                'google_place_id' => $place['place_id'],
                'google_place_name' => $place['name'],
                'google_place_types' => json_encode($place['types'] ?? []),
                'google_formatted_address' => $place['formatted_address']
            ];
            
            if (isset($place['rating'])) {
                $attributes['google_rating'] = $place['rating'];
                $attributes['google_user_ratings_total'] = $place['user_ratings_total'] ?? 0;
            }
            
            foreach ($attributes as $key => $value) {
                $attr_stmt = $database->prepare("INSERT INTO bg_company_locations_attributes
                    (location_id, attribute_key, attribute_value, attribute_type, source)
                    VALUES (:location_id, :key, :value, :type, :source)");
                
                $attr_stmt->execute([
                    'location_id' => $location_id,
                    'key' => $key,
                    'value' => (string)$value,
                    'type' => is_numeric($value) ? 'number' : (is_array($value) ? 'json' : 'string'),
                    'source' => 'google_places_api'
                ]);
            }
            
            if ($debug) {
                echo "  + Added location: $address, $city, $state\n";
            }
        }
        
        // Limit locations per company to prevent abuse
        if ($locations_for_company >= 10) {
            if ($debug) {
                echo "  - Reached max locations (10) for this company\n";
            }
            break;
        }
    }
    
    echo "  = Added $locations_for_company locations\n\n";
    $processed++;
    
    // Rate limiting - Google allows 100 requests per second but be conservative
    usleep(500000); // 0.5 seconds between requests
}

// Summary
echo "\n=== SUMMARY ===\n";
echo "Companies processed: $processed\n";
echo "Locations added: $locations_added\n";
echo "Errors: $errors\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";

// Log to system
$system->addactivity('scheduler', 'Company location gathering completed', [
    'processed' => $processed,
    'locations_added' => $locations_added,
    'errors' => $errors
]);