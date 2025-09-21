<?php
/**
 * Search business locations for tour
 */

// Security check
if (!isset($database) || !isset($current_user_data)) {
    exit(json_encode(['success' => false, 'message' => 'Invalid request']));
}

$companyId = $_POST['company_id'] ?? 0;
$radius = intval($_POST['radius'] ?? 25);
$lat = floatval($_POST['lat'] ?? 0);
$lng = floatval($_POST['lng'] ?? 0);

if (!$companyId) {
    exit(json_encode(['success' => false, 'message' => 'Company ID required']));
}

try {
    // Get all locations for this company
    $sql = "SELECT location_id, address as name, address, city, state, zip_code, latitude, longitude 
            FROM bg_company_locations 
            WHERE company_id = :company_id 
            AND status = 'active'
            ORDER BY location_id DESC";
    $stmt = $database->prepare($sql);
    $stmt->execute([':company_id' => $companyId]);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = [];
    
    foreach ($locations as $location) {
        // Calculate distance if we have coordinates
        $distance = null;
        if ($lat && $lng && $location['latitude'] && $location['longitude']) {
            $distance = haversineDistance($lat, $lng, $location['latitude'], $location['longitude']);
            
            // Skip if outside radius
            if ($distance > $radius) continue;
        }
        
        // Format address
        $addressParts = [];
        if (!empty($location['address'])) $addressParts[] = $location['address'];
        if (!empty($location['city'])) $addressParts[] = $location['city'];
        if (!empty($location['state'])) $addressParts[] = $location['state'];
        if (!empty($location['zip_code'])) $addressParts[] = $location['zip_code'];
        
        $results[] = [
            'location_id' => $location['location_id'],
            'name' => $location['name'] ?: 'Location',
            'address' => implode(', ', $addressParts),
            'lat' => $location['latitude'],
            'lng' => $location['longitude'],
            'distance' => $distance ? round($distance, 1) : null
        ];
    }
    
    // Sort by distance if available
    if ($lat && $lng) {
        usort($results, function($a, $b) {
            if ($a['distance'] === null) return 1;
            if ($b['distance'] === null) return -1;
            return $a['distance'] - $b['distance'];
        });
    }
    
    echo json_encode(['success' => true, 'locations' => $results]);
    
} catch (Exception $e) {
    error_log("Error searching locations: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Search failed']);
}