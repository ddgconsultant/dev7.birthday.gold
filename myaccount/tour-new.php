<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Get tour data
$date = $_GET['date'] ?? date('Y-m-d');

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
</style>';

// Get tour businesses for this date
$checkEnrollmentQuery = "SELECT * FROM bg_user_tours WHERE calendar_dt = :date AND user_id = ".$current_user_data['user_id']."";
$stmt = $database->prepare($checkEnrollmentQuery);
$stmt->execute([':date' => $date]);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$listofcompanies = [];
foreach ($companies as $item_company) {  
    $company_data = $app->getcompany($item_company['company_id']);    
    $listofcompanies[] = $item_company + ['data' => $company_data];
}

// Format the date
$dateObject = new DateTime($date);
$formattedDate = $dateObject->format('l, F j, Y');

// Get home address
$homeaddress = trim($current_user_data['profile_mailing_address'].', '.$current_user_data['profile_city'].', '.$current_user_data['profile_state'].' '.$current_user_data['profile_zip_code']);

// Page setup
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
include($dir['core_components'] . '/bg_user_profileheader.inc');
include($dir['core_components'] . '/bg_user_leftpanel.inc');

// Start the myaccount layout
echo '<div class="col-md-9 col-lg-9">';
?>

<div class="row">
    <div class="col-lg-8 mb-4">
        <!-- DATE card -->
        <div class="card h-100 border-start-lg border-start-primary">
            <div class="card-body">
                <div class="small text-muted">Your Tour:</div>
                <div class="h3 my-3"><?php echo $formattedDate; ?></div>
                Consists of <?php echo count($companies); ?> businesses
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
                        <a href="#!">Change Location</a>
                    </div>
                </div>
                <hr>
                
                <?php
                // Display tour businesses
                foreach ($listofcompanies as $item_companyrow) {
                    $item_company = $item_companyrow['data'];
                    if (!empty($item_company)) {
                        $companyaddress = !empty($item_company['address']) ? 
                            $item_company['address'].', '.$item_company['city'].', '.$item_company['state'].' '.$item_company['zip_code'] :
                            $current_user_data['profile_city'].', '.$current_user_data['profile_state'].' '.$current_user_data['profile_zip_code'];
                ?>
                <!-- Business location -->
                <div class="sortable_item">
                    <div class="d-flex align-items-center justify-content-between px-4" data-location="<?php echo $companyaddress; ?>">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']); ?>" style="width:32px" alt="" />  
                            <div class="ms-4">
                                <div class="small fw-bold"><?php echo $item_company['company_name']; ?></div>
                                <div class="text-xs text-muted"><?php echo $companyaddress; ?></div>
                            </div>
                        </div>
                        <div class="ms-4 small">
                            <div class="badge bg-light text-dark me-3 d-none">Closest Location</div>
                            <a href="#!" class="pick-location d-none">Pick Different Location</a>
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
                <div id="google_map" style="height: 800px;"></div>
            </div>
        </div>
    </div>
</div>

    <script>
    // Tour locations from PHP data
    var tourLocations = [
        {
            name: "Your Home",
            address: "<?php echo addslashes($homeaddress); ?>",
            type: "home"
        },
        <?php 
        foreach ($listofcompanies as $item_companyrow) {
            $item_company = $item_companyrow['data'];
            if (!empty($item_company)) {
                $companyaddress = !empty($item_company['address']) ? 
                    $item_company['address'].', '.$item_company['city'].', '.$item_company['state'].' '.$item_company['zip_code'] :
                    $current_user_data['profile_city'].', '.$current_user_data['profile_state'].' '.$current_user_data['profile_zip_code'];
        ?>
        {
            name: "<?php echo addslashes($item_company['company_name']); ?>",
            address: "<?php echo addslashes($companyaddress); ?>",
            description: "<?php echo addslashes($item_company['description'] ?? ''); ?>",
            type: "business"
        },
        <?php 
            }
        }
        ?>
    ];

    // Map initialization
    var routeMap;
    var directionsService;
    var directionsRenderer;
    
    function initMap() {
        console.log('initMap() called');
        // Remove the api-status div references since we don't have that element
        loadDirections();
    }
    
    // Make initMap global
    window.initMap = initMap;
    
    // Error handler for authentication failures
    window.gm_authFailure = function() {
        console.error('Google Maps authentication failed!');
        const mapDiv = document.getElementById('google_map');
        if (mapDiv) {
            mapDiv.innerHTML = '<div class="alert alert-danger m-3"><strong>Google Maps Authentication Failed</strong><br>The API key is not authorized for domain: ' + window.location.hostname + '<br><small>Please update the API key restrictions in Google Cloud Console to include this domain.</small></div>';
        }
    };
    
    // Load Google Maps
    function loadGoogleMaps() {
        console.log('Loading Google Maps API...');
        
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyCtO04U0nKVeodiS0TL12ib8MDEtwdANOM&libraries=places&callback=initMap';
        script.async = true;
        script.defer = true;
        
        script.onload = function() {
            console.log('Google Maps script loaded');
        };
        
        script.onerror = function() {
            console.error('Failed to load Google Maps script');
            const mapDiv = document.getElementById('google_map');
            if (mapDiv) {
                mapDiv.innerHTML = '<div class="alert alert-danger m-3">Failed to load Google Maps. Please check your internet connection.</div>';
            }
        };
        
        document.body.appendChild(script);
    }
    
    // Start loading when page is ready
    window.addEventListener('load', loadGoogleMaps);
    </script>

    <script>
    function loadDirections() {
        console.log('Loading directions...');
        
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            console.error('Google Maps not loaded');
            return;
        }
        
        // Initialize route map
        var mapElement = document.getElementById('google_map');
        if (!mapElement) {
            console.error('Map element not found');
            return;
        }
        
        // Center map on home location initially
        routeMap = new google.maps.Map(mapElement, {
            zoom: 13,
            center: {lat: 39.7392, lng: -104.9903} // Will be updated when route loads
        });
        
        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
            map: routeMap,
            panel: document.getElementById('directions-panel'),
            suppressMarkers: false
        });
        
        if (tourLocations.length < 2) {
            console.error('Not enough locations for a tour');
            document.getElementById('directions-panel').innerHTML = '<div class="alert alert-warning">Add businesses to create a tour route.</div>';
            return;
        }
        
        // Create waypoints from all businesses except the last one
        var waypoints = [];
        for (var i = 1; i < tourLocations.length; i++) {
            waypoints.push({
                location: tourLocations[i].address,
                stopover: true
            });
        }
        
        // Calculate route - return to home at the end
        var request = {
            origin: tourLocations[0].address,
            destination: tourLocations[0].address, // Return to home
            waypoints: waypoints,
            optimizeWaypoints: false, // Keep the order as saved
            travelMode: 'DRIVING'
        };
        
        console.log('Calculating route...');
        directionsService.route(request, function(response, status) {
            if (status === 'OK') {
                console.log('Route calculated successfully');
                directionsRenderer.setDirections(response);
                
                // Add custom markers with labels
                tourLocations.forEach(function(location, index) {
                    setTimeout(function() {
                        var position;
                        if (index === 0) {
                            position = response.routes[0].legs[0].start_location;
                        } else if (index < tourLocations.length) {
                            position = response.routes[0].legs[index - 1].end_location;
                        }
                        
                        if (position) {
                            var marker = new google.maps.Marker({
                                position: position,
                                map: routeMap,
                                title: location.name,
                                label: {
                                    text: (index + 1).toString(),
                                    color: 'white',
                                    fontSize: '12px',
                                    fontWeight: 'bold'
                                },
                                icon: {
                                    path: google.maps.SymbolPath.CIRCLE,
                                    scale: 20,
                                    fillColor: index === 0 ? '#4285F4' : '#EA4335',
                                    fillOpacity: 1,
                                    strokeColor: 'white',
                                    strokeWeight: 2
                                }
                            });
                            
                            // Add info window
                            var infoContent = '<div><strong>' + location.name + '</strong>';
                            if (location.description) {
                                infoContent += '<br><small>' + location.description + '</small>';
                            }
                            infoContent += '<br><small>' + location.address + '</small></div>';
                            
                            var infoWindow = new google.maps.InfoWindow({
                                content: infoContent
                            });
                            
                            marker.addListener('click', function() {
                                infoWindow.open(routeMap, marker);
                            });
                        }
                    }, 100 * index); // Stagger marker creation
                });
                
                // Log route details
                var route = response.routes[0];
                var totalDistance = route.legs.reduce((sum, leg) => sum + leg.distance.value, 0) / 1000;
                var totalDuration = Math.round(route.legs.reduce((sum, leg) => sum + leg.duration.value, 0) / 60);
                console.log('Total distance: ' + totalDistance.toFixed(1) + ' km');
                console.log('Total duration: ' + totalDuration + ' minutes');
                
            } else {
                console.error('Directions request failed:', status);
                document.getElementById('directions-panel').innerHTML = 
                    '<div class="alert alert-danger">Failed to calculate route: ' + status + '</div>';
            }
        });
    }
    </script>

<script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Initialize sortable for tour reordering
    $("#sortable").sortable({
        handle: ".sortable_item_handle",
        axis: "y",
        items: ".sortable_item",
        start: function(event, ui) {
            $(this).addClass('sorting-active');
        },
        stop: function(event, ui) {
            $(this).removeClass('sorting-active');
            // Show the "Draw New Map" button after reordering
            const drawNewMapButton = document.getElementById("draw_map");
            if (drawNewMapButton) {
                drawNewMapButton.style.display = "inline-block";
            }
            
            // Update tourLocations array based on new order
            updateTourOrder();
        }
    });
});

// Function to update tour order based on sortable list
function updateTourOrder() {
    var newLocations = [{
        name: "Your Home",
        address: tourLocations[0].address,
        type: "home"
    }];
    
    $('.sortable_item').each(function() {
        var businessName = $(this).find('.small.fw-bold').text();
        // Find the business in original tourLocations
        for (var i = 1; i < tourLocations.length; i++) {
            if (tourLocations[i].name === businessName) {
                newLocations.push(tourLocations[i]);
                break;
            }
        }
    });
    
    tourLocations = newLocations;
}

// Function to reload map after reordering
function DrawNewMap() {
    loadDirections();
    // Hide the button after redrawing
    document.getElementById("draw_map").style.display = "none";
}
</script>

</div><!-- End col-md-9 -->
</div><!-- End row -->
</div><!-- End container -->

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>