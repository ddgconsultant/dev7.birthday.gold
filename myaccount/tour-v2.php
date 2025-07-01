<?php
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
            
            // Update the default home location
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
            
            echo json_encode(['success' => true, 'address' => $address]);
            exit;
            
        case 'get_tour_locations':
            $tour_companies = json_decode($_POST['companies'], true);
            $user_lat = $_POST['lat'] ?? null;
            $user_lng = $_POST['lng'] ?? null;
            
            // Get locations using the new App methods
            $locations = $app->getTourCompanyLocations($tour_companies, $user_lat, $user_lng);
            
            echo json_encode(['success' => true, 'locations' => $locations]);
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
            
            echo json_encode(['success' => true, 'location_id' => $locationId]);
            exit;
    }
}

// Get tour data - hardcoded to 2025-07-03 for testing
$date = '2025-07-03'; // $_GET['date'] ?? date('Y-m-d');

// Get tour businesses for this date
$checkEnrollmentQuery = "SELECT * FROM bg_user_tours WHERE calendar_dt = :date AND user_id = ".$current_user_data['user_id']."";
$stmt = $database->prepare($checkEnrollmentQuery);
$stmt->execute([':date' => $date]);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$listofcompanies = [];
foreach ($companies as $item_company) {  
    $company_data = $app->getcompany($item_company['company_id']);
    
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
    
    $listofcompanies[] = $item_company + ['data' => $company_data];
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

// Add styles
$additionalstyles = '<style>
/* Sortable styles */
.sortable_item {
    cursor: move;
    transition: all 0.2s ease;
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
                    <button class="btn btn-primary" onclick="window.print()">Download PDF</button>
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
                <div class="d-flex align-items-center justify-content-between px-4" data-location="<?php echo $homeaddress; ?>">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-house-fill"></i>
                        <div class="ms-4">
                            <div class="small">Your Home</div>
                            <div class="text-xs text-muted"><?php echo $homeaddress; ?></div>
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
                <div class="sortable_item" data-company-id="<?php echo $item_company['company_id']; ?>" data-company-name="<?php echo htmlspecialchars($item_company['company_name']); ?>">
                    <div class="d-flex align-items-center justify-content-between px-4" data-location="<?php echo $companyaddress; ?>">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']); ?>" style="width:32px" alt="" />  
                            <div class="ms-4">
                                <div class="small fw-bold"><?php echo $item_company['company_name']; ?></div>
                                <div class="text-xs text-muted company-address" id="address-<?php echo $item_company['company_id']; ?>">
                                    <?php if ($hasCoordinates && !$hasFullAddress): ?>
                                        <span class="text-success">📍 <?php echo $businessCity; ?>, <?php echo $businessState; ?> (Located)</span>
                                    <?php elseif (!$hasFullAddress): ?>
                                        <span class="text-warning">📍 Searching for location in <?php echo $home_city; ?></span>
                                    <?php else: ?>
                                        <?php echo $companyaddress; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="ms-4 small">
                            <a href="#!" class="pick-location me-2" onclick="pickLocation('<?php echo htmlspecialchars($item_company['company_name']); ?>', '<?php echo htmlspecialchars($item_company['city'] ?? ''); ?>', this)">Pick Different Location</a>
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
                    <p class="text-muted small mb-2"><i class="bi bi-info-circle"></i> Drag businesses to reorder your tour route</p>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pick Location for <span id="modal-business-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="location-search" placeholder="Search for a location...">
                </div>
                <div id="location-results" class="list-group mb-3" style="max-height: 300px; overflow-y: auto;">
                    <!-- Search results will appear here -->
                </div>
                <div id="location-map" style="height: 400px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-location" disabled>Use Selected Location</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
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
            name: "Your Home",
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
            type: "business"
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
    
    // Load Google Maps
    function loadGoogleMaps() {
        console.log('Loading Google Maps API...');
        document.getElementById('api-status').textContent = 'Loading Google Maps API...';
        
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=<?php echo $sitesettings['GOOGLEAPI']['mainkey']; ?>&libraries=places&callback=initMap';
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
            document.getElementById('api-status').textContent = 'Failed to load Google Maps API script';
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
    // Function to geocode missing addresses using server-side lookup
    function geocodeMissingAddresses() {
        console.log('Starting geocodeMissingAddresses...');
        
        // Collect companies that need geocoding
        var companiesNeedingLocation = [];
        locations.forEach(function(location, index) {
            if (location.needsGeocoding && location.type === 'business') {
                // Check if we already have coordinates from bg_company_locations
                if (location.lat && location.lng) {
                    console.log('Location already has coordinates:', location.name, location.lat, location.lng);
                    location.needsGeocoding = false;
                } else {
                    companiesNeedingLocation.push({
                        index: index,
                        company_id: location.company_id,
                        company_name: location.name,
                        city: location.city,
                        state: location.state
                    });
                }
            }
        });
        
        if (companiesNeedingLocation.length === 0) {
            // No geocoding needed, load directions immediately
            loadDirections();
            return;
        }
        
        // Get home location coordinates
        var homeLat = locations[0].lat || <?php echo $home_lat ?: 'null'; ?>;
        var homeLng = locations[0].lng || <?php echo $home_lng ?: 'null'; ?>;
        
        // If we don't have home coordinates, get them first
        if (!homeLat || !homeLng) {
            // Use Google Geocoding for home address
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({ address: locations[0].address }, function(results, status) {
                if (status === 'OK') {
                    homeLat = results[0].geometry.location.lat();
                    homeLng = results[0].geometry.location.lng();
                    locations[0].lat = homeLat;
                    locations[0].lng = homeLng;
                    
                    // Now fetch business locations
                    fetchBusinessLocations(companiesNeedingLocation, homeLat, homeLng);
                } else {
                    console.error('Failed to geocode home address');
                    loadDirections(); // Try to continue anyway
                }
            });
        } else {
            fetchBusinessLocations(companiesNeedingLocation, homeLat, homeLng);
        }
    }
    
    // Fetch business locations from server
    function fetchBusinessLocations(companiesNeedingLocation, homeLat, homeLng) {
        console.log('Fetching business locations from server...');
        
        // Prepare company data
        var companies = companiesNeedingLocation.map(function(item) {
            return {
                company_id: item.company_id,
                company_name: item.company_name,
                city: item.city,
                state: item.state
            };
        });
        
        // Call server to get locations
        fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_tour_locations&companies=' + encodeURIComponent(JSON.stringify(companies)) +
                  '&lat=' + homeLat + '&lng=' + homeLng
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.locations) {
                // Update our locations with the server results
                data.locations.forEach(function(serverLocation, idx) {
                    if (serverLocation) {
                        var originalIndex = companiesNeedingLocation[idx].index;
                        
                        // Build full address
                        var fullAddress = serverLocation.address + ', ' + 
                                        serverLocation.city + ', ' + 
                                        serverLocation.state + ' ' + 
                                        serverLocation.zip_code;
                        
                        locations[originalIndex].address = fullAddress;
                        locations[originalIndex].lat = parseFloat(serverLocation.latitude);
                        locations[originalIndex].lng = parseFloat(serverLocation.longitude);
                        locations[originalIndex].needsGeocoding = false;
                        
                        // Update the display
                        console.log('Updating display for:', locations[originalIndex].name, 'with address:', fullAddress);
                        updateBusinessDisplay(locations[originalIndex].name, fullAddress, serverLocation.company_id);
                        
                        // Save the business location to database
                        saveTourBusinessLocation(serverLocation.company_id, locations[originalIndex].name, fullAddress, 
                                               serverLocation.latitude, serverLocation.longitude);
                    }
                });
                
                console.log('All locations updated, loading directions...');
                loadDirections();
            } else {
                console.error('Failed to get locations from server');
                loadDirections(); // Try to continue anyway
            }
        })
        .catch(error => {
            console.error('Error fetching locations:', error);
            loadDirections(); // Try to continue anyway
        });
    }
    
    // Function to update business display with new address
    function updateBusinessDisplay(businessName, newAddress, companyId) {
        console.log('updateBusinessDisplay called with:', businessName, newAddress, companyId);
        
        // Strategy 1: Try to find by company ID (most reliable)
        if (companyId) {
            var addressElement = $('#address-' + companyId);
            if (addressElement.length > 0) {
                console.log('Found by ID! Updating address element');
                addressElement.html(newAddress);
                // Also update the data-location
                addressElement.closest('.sortable_item').find('[data-location]').attr('data-location', newAddress);
                return;
            }
        }
        
        // Strategy 2: Try to find by data attribute
        var sortableItem = $('.sortable_item[data-company-name="' + businessName + '"]');
        if (sortableItem.length > 0) {
            console.log('Found by data attribute! Updating...');
            var addressEl = sortableItem.find('.company-address');
            addressEl.html(newAddress);
            sortableItem.find('[data-location]').attr('data-location', newAddress);
            return;
        }
        
        // Strategy 3: Original method with improved selectors
        $('.sortable_item').each(function() {
            var $item = $(this);
            var nameElement = $item.find('.small.fw-bold').first();
            var currentName = nameElement.text().trim();
            
            if (currentName === businessName.trim()) {
                console.log('Found by text match! Updating...');
                // Find address element more reliably
                var addressEl = $item.find('.text-xs.text-muted.company-address');
                if (addressEl.length === 0) {
                    // Fallback to original selector
                    addressEl = $item.find('.text-xs.text-muted');
                }
                
                if (addressEl.length > 0) {
                    addressEl.html(newAddress);
                    $item.find('[data-location]').attr('data-location', newAddress);
                    console.log('Updated successfully');
                    return false;
                }
            }
        });
        
        console.error('Failed to update display for:', businessName);
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
            html += '<div style="color: #5f6368; font-size: 14px; margin-left: 35px; font-weight: bold;">' + leg.start_address + '</div>';
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
            html += '<div style="color: #5f6368; font-size: 14px; margin-left: 35px; font-weight: bold;">' + lastLeg.end_address + '</div>';
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
            center: {lat: 39.7392, lng: -104.9903} // Denver, CO
        });
        
        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
            map: routeMap,
            // Do not use default panel since we are creating custom one
            // panel: document.getElementById('directions-panel'),
            suppressMarkers: false
        });
        
        console.log('Locations array:', locations);
        console.log('Number of locations:', locations.length);
        
        // Check if we have enough locations
        if (locations.length < 2) {
            console.error('Not enough locations for a tour');
            document.getElementById('directions-panel').innerHTML = '<div class="alert alert-warning">No businesses found for this date. Add businesses to your tour first.</div>';
            return;
        }
        
        // Create waypoints from businesses
        var waypoints = [];
        for (var i = 1; i < locations.length; i++) {
            waypoints.push({
                location: locations[i].address,
                stopover: true
            });
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
                waypointOrder.forEach(function(originalIndex) {
                    // waypoint_order refers to the waypoints array, which starts at index 1 of locations
                    optimizedLocations.push(locations[originalIndex + 1]);
                });
                
                console.log('Optimized route:', optimizedLocations.map(l => l.name));
                
                // Create custom directions panel with optimized order
                createCustomDirectionsPanel(response, optimizedLocations);
                
                // Add markers for each location with business names
                optimizedLocations.forEach(function(location, index) {
                    var position;
                    if (index < response.routes[0].legs.length) {
                        position = response.routes[0].legs[index].start_location;
                    } else {
                        position = response.routes[0].legs[response.routes[0].legs.length - 1].end_location;
                    }
                    
                    var marker = new google.maps.Marker({
                        position: position,
                        map: routeMap,
                        title: location.name + '\n' + location.address,
                        label: {
                            text: (index + 1).toString(),
                            color: 'white',
                            fontSize: '12px',
                            fontWeight: 'bold'
                        },
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 20,
                            fillColor: location.type === 'start' || location.type === 'home' ? '#4285F4' : '#EA4335',
                            fillOpacity: 1,
                            strokeColor: 'white',
                            strokeWeight: 2
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
            items: ".sortable_item",
            cursor: "move",
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
    // Update locations based on new order
    var newLocations = [{
        name: "Your Home",
        address: locations[0].address,
        type: "home"
    }];
    
    $('.sortable_item').each(function() {
        var businessName = $(this).find('.small.fw-bold').text();
        // Find the business in original locations
        for (var i = 1; i < locations.length; i++) {
            if (locations[i].name === businessName) {
                newLocations.push(locations[i]);
                break;
            }
        }
    });
    
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

function pickLocation(businessName, city, element) {
    console.log('Picking location for:', businessName, 'in', city);
    
    // Store the current element and find the business index
    currentBusinessElement = element.closest('.sortable_item');
    var businessNameFromCard = currentBusinessElement.querySelector('.small.fw-bold').textContent;
    
    // Find the business in locations array
    for (var i = 1; i < locations.length; i++) {
        if (locations[i].name === businessNameFromCard) {
            currentBusinessIndex = i;
            break;
        }
    }
    
    // Set modal title
    document.getElementById('modal-business-name').textContent = businessName;
    
    // Show modal
    var modal = new bootstrap.Modal(document.getElementById('locationPickerModal'));
    modal.show();
    
    // Initialize map when modal is shown
    setTimeout(function() {
        if (!locationPickerMap) {
            locationPickerMap = new google.maps.Map(document.getElementById('location-map'), {
                zoom: 13,
                center: {lat: 39.7392, lng: -104.9903}
            });
        }
        
        // Search for the business
        searchBusiness(businessName + ' ' + city);
    }, 500);
}

function searchBusiness(query) {
    var service = new google.maps.places.PlacesService(locationPickerMap);
    var request = {
        query: query,
        fields: ['name', 'geometry', 'formatted_address', 'place_id']
    };
    
    service.textSearch(request, function(results, status) {
        if (status === google.maps.places.PlacesServiceStatus.OK) {
            displayLocationResults(results);
            if (results.length > 0) {
                locationPickerMap.setCenter(results[0].geometry.location);
            }
        }
    });
}

function displayLocationResults(results) {
    var resultsDiv = document.getElementById('location-results');
    resultsDiv.innerHTML = '';
    
    results.forEach(function(place, index) {
        var item = document.createElement('a');
        item.className = 'list-group-item list-group-item-action';
        item.href = '#';
        item.innerHTML = '<strong>' + place.name + '</strong><br><small>' + place.formatted_address + '</small>';
        
        item.onclick = function(e) {
            e.preventDefault();
            selectLocation(place);
            
            // Highlight selected
            document.querySelectorAll('#location-results .list-group-item').forEach(function(el) {
                el.classList.remove('active');
            });
            this.classList.add('active');
        };
        
        resultsDiv.appendChild(item);
        
        // Add marker to map
        var marker = new google.maps.Marker({
            position: place.geometry.location,
            map: locationPickerMap,
            title: place.name
        });
        
        marker.addListener('click', function() {
            selectLocation(place);
            document.querySelectorAll('#location-results .list-group-item')[index].click();
        });
    });
}

function selectLocation(place) {
    selectedLocation = {
        name: place.name,
        address: place.formatted_address,
        location: place.geometry.location
    };
    
    document.getElementById('confirm-location').disabled = false;
    locationPickerMap.setCenter(place.geometry.location);
    locationPickerMap.setZoom(15);
}

// Confirm location selection
document.getElementById('confirm-location').addEventListener('click', function() {
    if (selectedLocation && currentBusinessIndex > -1) {
        // Update the location in our array
        locations[currentBusinessIndex].address = selectedLocation.address;
        locations[currentBusinessIndex].lat = selectedLocation.location.lat();
        locations[currentBusinessIndex].lng = selectedLocation.location.lng();
        
        // Update the display
        var addressDiv = currentBusinessElement.querySelector('.text-xs.text-muted');
        if (addressDiv) {
            addressDiv.textContent = selectedLocation.address;
        }
        
        // Update the data-location attribute
        var locationDiv = currentBusinessElement.querySelector('[data-location]');
        if (locationDiv) {
            locationDiv.setAttribute('data-location', selectedLocation.address);
        }
        
        // Save the new location to database
        var companyId = currentBusinessElement.getAttribute('data-company-id');
        var companyName = locations[currentBusinessIndex].name;
        saveTourBusinessLocation(companyId, companyName, selectedLocation.address, 
                               selectedLocation.location.lat(), selectedLocation.location.lng());
        
        // Show update map button
        document.getElementById('draw_map').style.display = 'inline-block';
        
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('locationPickerModal')).hide();
        
        // Reset
        selectedLocation = null;
        currentBusinessElement = null;
        currentBusinessIndex = -1;
    }
});

// Search functionality
document.getElementById('location-search').addEventListener('input', function(e) {
    var query = e.target.value;
    if (query.length > 2) {
        searchBusiness(query);
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
                <h5 class="modal-title" id="changeLocationModalLabel">Change Home Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="home-address-input" class="form-label">Enter your home address</label>
                    <input type="text" class="form-control" id="home-address-input" 
                           placeholder="Enter address..." 
                           value="<?php echo htmlspecialchars($homeaddress); ?>">
                    <div class="form-text">Start typing and select from the suggestions</div>
                </div>
                <div id="home-location-map" style="height: 400px; width: 100%; display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-home-location">Update Home Location</button>
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
    if (!homeAutocomplete) {
        homeAutocomplete = new google.maps.places.Autocomplete(
            document.getElementById('home-address-input'),
            { types: ['address'] }
        );
        
        homeAutocomplete.addListener('place_changed', function() {
            var place = homeAutocomplete.getPlace();
            
            if (place.geometry) {
                selectedHomeLocation = {
                    address: place.formatted_address,
                    lat: place.geometry.location.lat(),
                    lng: place.geometry.location.lng()
                };
                
                // Show map
                document.getElementById('home-location-map').style.display = 'block';
                
                if (!homeLocationMap) {
                    homeLocationMap = new google.maps.Map(document.getElementById('home-location-map'), {
                        center: place.geometry.location,
                        zoom: 15
                    });
                }
                
                homeLocationMap.setCenter(place.geometry.location);
                
                if (homeLocationMarker) {
                    homeLocationMarker.setMap(null);
                }
                
                homeLocationMarker = new google.maps.Marker({
                    position: place.geometry.location,
                    map: homeLocationMap,
                    title: 'Home Location'
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
                  '&lat=' + selectedHomeLocation.lat + '&lng=' + selectedHomeLocation.lng
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the display
                document.querySelector('[data-location]').setAttribute('data-location', selectedHomeLocation.address);
                document.querySelector('.text-xs.text-muted').textContent = selectedHomeLocation.address;
                
                // Update our locations array
                locations[0].address = selectedHomeLocation.address;
                locations[0].lat = selectedHomeLocation.lat;
                locations[0].lng = selectedHomeLocation.lng;
                
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('changeLocationModal')).hide();
                
                // Trigger map redraw
                loadGoogleMaps();
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