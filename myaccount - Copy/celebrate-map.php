<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$headerattribute['additionalcss'] = '<link rel="stylesheet" href="/public/css/myaccount.css">';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');
?>
<style>
    @media print {
        body * {
            display: none;
        }

        #printContainer {
            display: block;
        }
    }
</style>

<div class="container-xl px-4 mt-4">
    <!-- Account page navigation-->

    <?PHP include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/nav-myaccount.php'); ?>




    <hr class="mt-0 mb-4">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- Billing card 1-->
            <div class="card h-100 border-start-lg border-start-primary">
                <div class="card-body">
                    <div class="small text-muted">Number of Tours</div>
                    <div class="h3">12</div>
                    <a class="text-arrow-icon small d-none" href="#!">
                        Switch to yearly billing
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <!-- Billing card 2-->
            <div class="card h-100 border-start-lg border-start-secondary">
                <div class="card-body">
                    <div class="small text-muted">Number of Businesses Enrolled</div>
                    <div class="h3">10</div>
                    <a class="text-arrow-icon small text-secondary d-none" href="#!">
                        View enrollment history
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <!-- Billing card 3-->
            <div class="card h-100 border-start-lg border-start-success">
                <div class="card-body">
                    <div class="small text-muted">Actions</div>
                    <div class="h4 d-flex align-items-center">Print Map</div>
                    <div class="h4 d-flex align-items-center">Print Steps</div>
                    <a class="text-arrow-icon small text-success d-none" href="#!">
                        Upgrade plan
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-right">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>


    <hr class="mt-0 mb-4">
    <div class="container">
        <div class="row">
            <!-- Payment methods card-->
            <div class="card card-header-actions mb-4">
                <div class="card-header">
                    Celebration Tour
                    <button class="btn btn-sm btn-primary" type="button">Add Company Stops</button>
                </div>
                <?PHP
                $homeaddress = '' . $current_user_data['profile_mailing_address'] . ', ' . $current_user_data['profile_city'] . ', ' . $current_user_data['profile_state'] . '  ' . $current_user_data['profile_zip_code'] . '';
                echo '        
        <div class="card-body px-0" id="sortable">
            <!-- Home location -->
            <div class="d-flex align-items-center justify-content-between px-4" data-location="' . $homeaddress . '">
                <div class="d-flex align-items-center">
                <i class="bi bi-buildings-fill h3"></i>
                    <div class="ms-4">
                        <div class="small">Your Home</div>
                        <div class="text-xs text-muted">' . $homeaddress . '</div>
                    </div>
                </div>
                <div class="ms-4 small">
                    <a href="#!">Edit Address</a>
                </div>
            </div>
            <hr>
';



                ## GET LIST OF BUSINESSES ENROLLED:
                $companies = $account->getgoldlist($current_user_data['user_id'], "'active', 'success', 'existing'");
                #breakpoint($companies['sql']);
                $companylistoutput = '';

                foreach ($companies['data'] as $item_company) {
                    ## LOOP THROUGH ENROLLMENT LIST
                    if (!empty($company['address'])) {
                        $companyaddress = $item_company['address'] . ', ' . $item_company['city'] . ', ' . $item_company['state'] . '  ' . $item_company['zip_code'];
                    } else {
                        $companyaddress = $current_user_data['profile_city'] . ', ' . $current_user_data['profile_state'] . '  ' . $current_user_data['profile_zip_code'];
                    }
                    $companylistoutput .= '
            <!-- Other locations -->
            <div class="sortable_item">
                <div class="d-flex align-items-center justify-content-between px-4" data-location="' . $companyaddress . '">
                    <div class="d-flex align-items-center">
                    <img src="' . $display->companyimage($item_company['company_id'] . '/' . $item_company['company_logo']) . '" style="width:32px" alt="" />  
                        <div class="ms-4">
                            <div class="small fw-bold">' . $item_company['company_name'] . '</div>
                            <div class="text-xs text-muted">' . $companyaddress . '</div>
                        </div>
                    </div>
                    <div class="ms-4 small">
                        <div class="badge bg-light text-dark me-3">Closest Location</div>
                        <a href="#!" class="pick-location">Pick Different Location</a>
                        <div class="btn btn-sm sortable_item_handle"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 512 512">
                                <title>Reorder Item</title>
                                <line x1="96" y1="256" x2="416" y2="256" style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px"></line>
                                <line x1="96" y1="176" x2="416" y2="176" style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px"></line>
                                <line x1="96" y1="336" x2="416" y2="336" style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px"></line>
                            </svg></div>
                    </div>
                </div>
                <hr>
            </div>
            ';
                }

                echo $companylistoutput;

                ?>

                <!-- Draw new map -->
                <div style="text-align: center; margin-bottom: 20px;">
                    <button class="btn btn-secondary draw_map" id="draw_map" style="display: none;" onclick="DrawNewMap()">Draw New Map</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container  col-12">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <!-- Billing card 2-->
                <div class="card h-100 border-start-lg border-start-secondary">
                    <div class="card-body">

                        <div id="directions-panel"></div>
                    </div>
                </div>
            </div>

            <!-- MAP card-->
            <div class="col-lg-8 mb-8">
                <div class="card mb-4" id="printContainer">
                    <div class="card-header">Map and Direction</div>
                    <!-- Show directions in a panel -->
                    <!-- <div id="directions-panel"></div> -->
                    <div class="card card-header-actions mb-4">
                        <!-- Existing content -->
                        <div class="card-body p-0">
                            <!-- Map will be displayed here -->
                            <div id="google_map" style="height: 800px;"></div>
                        </div>
                    </div>
                </div>
                <!-- Print button -->
                <div style="text-align: center; margin-bottom: 20px;">
                    <button class="btn btn-primary print_map" onclick="printContent()">Download PDF</button>
                </div>
            </div>
        </div>
        
        <script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
        <script>
            let optimizeRoutes = true;

            function DrawNewMap() {
                // Set the flag to false to disable route optimization when drawing the new map
                optimizeRoutes = false;
                initMap();
            }
            jQuery(document).ready(function($) {
                $("#sortable").sortable({
                    handle: ".sortable_item_handle", // Use the "stoptag" element as the handle for sorting
                    axis: "y", // Restrict movement to the vertical direction
                    items: ".sortable_item", // Only allow sorting of divs with ids starting with "step"
                    stop: function(event, ui) {
                        // Show the "DRAW NEW MAP" button after reordering
                        const drawNewMapButton = document.getElementById("draw_map");
                        drawNewMapButton.style.display = "inline-block";
                    },
                });
            });

            function sendAPIDataToServer(requestPayload, responsePayload) {
                const csrfToken = "<?= $display->inputcsrf_token('tokenonly'); ?>"; // Replace with the actual PHP variable
                const url = "/api/track";
                const data = {
                    _token: csrfToken,
                    request: requestPayload,
                    response: responsePayload,
                };

                // Use fetch or any other AJAX method to send the data to the server
                fetch(url, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify(data),
                    })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error("Network response was not ok");
                        }
                        return response.json();
                    })
                    .then((data) => {
                        console.log("API data sent successfully:", data);
                    })
                    .catch((error) => {
                        console.error("Error sending API data:", error);
                    });
            }

            function printContent() {
                // Create a new window to hold the content to be printed
                const printWindow = window.open('', '_blank');

                // Get the content of the printContainer
                const printContainer = document.getElementById("printContainer").outerHTML;
                const printdirections = document.getElementById("directions-panel").outerHTML;

                // Set the content of the new window to the printContainer content
                printWindow.document.open();
                printWindow.document.write(printdirections);
                printWindow.document.write(printContainer);
                printWindow.document.close();

                // Wait for the new window to finish loading the content
                printWindow.onload = function() {
                    // Trigger the print dialog
                    printWindow.print();

                    // Close the new window after printing
                    printWindow.close();
                };
            }

            function drawArrowsAlongRoute(map, path) {
                // Define the arrow symbol with proper orientation
                const arrowSymbol = {
                    path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                };

                // Add arrows at regular intervals along the route
                for (let i = 0; i < path.length - 1; i++) {
                    const arrowPolyline = new google.maps.Polyline({
                        path: [path[i], path[i + 1]],
                        icons: [{
                            icon: arrowSymbol,
                            offset: '100%'
                        }],
                        strokeColor: 'blue',
                        strokeWeight: 1,
                        map: map,
                    });
                }
            }

            async function initMap() {
                const mapOptions = {
                    zoom: 10,
                };
                const map = new google.maps.Map(document.getElementById("google_map"), mapOptions);

                // Create a function to handle geocoding requests with async/await
                async function geocodeLocation(locationName) {
                    // console.log(locationName);
                    return new Promise((resolve, reject) => {
                        const geocoder = new google.maps.Geocoder();
                        geocoder.geocode({
                            address: locationName
                        }, (results, status) => {
                            if (status === google.maps.GeocoderStatus.OK) {
                                const locationLatLng = results[0].geometry.location;
                                const placeId = results[0].place_id;

                                // Use the Places API to fetch additional details
                                const placesService = new google.maps.places.PlacesService(document.createElement("div"));
                                placesService.getDetails({
                                    placeId
                                }, (place, status) => {
                                    if (status === google.maps.places.PlacesServiceStatus.OK) {
                                        const placeDetails = {
                                            name: place.name,
                                            address: place.formatted_address,
                                            phone: place.formatted_phone_number || "Not Available", // Set default value if phone number is not available
                                        };
                                        resolve({
                                            location: locationLatLng,
                                            details: placeDetails
                                        });
                                    } else {
                                        // If there was an error fetching additional details, resolve with the basic information
                                        const placeDetails = {
                                            name: locationName,
                                            address: results[0].formatted_address,
                                            phone: "Not Available", // Set default value for phone number
                                        };
                                        resolve({
                                            location: locationLatLng,
                                            details: placeDetails
                                        });
                                    }
                                });
                            } else {
                                // If geocoding failed, resolve with the basic information
                                const placeDetails = {
                                    name: locationName,
                                    address: "",
                                    phone: "Not Available", // Set default value for phone number
                                };
                                resolve({
                                    location: null,
                                    details: placeDetails
                                });
                            }
                        });
                    });
                }

                // Use async/await to handle geocoding requests and populate waypoints
                try {
                    const otherLocations = document.querySelectorAll("[data-location]");
                    const waypoints = [];
                    let homeLatLng;

                    for (const locationElement of otherLocations) {
                        const locationName = locationElement.dataset.location;
                        const smallTitle = locationElement.querySelector('.small').textContent;
                        const locationData = await geocodeLocation(smallTitle + ", " + locationName);
                        // console.log(locationData);

                        waypoints.push({
                            title: locationData.details.name,
                            location: locationData.location,
                            address: locationData.details.address,
                            phone: locationData.details.phone,
                        });
                        // console.log(waypoints);

                        if (!homeLatLng) {
                            homeLatLng = locationData.location; // Store home location's coordinates
                        }
                    }


                    if (waypoints.length > 0) {
                        // Now, all waypoints are populated, and we can proceed with DirectionsService.route()
                        let homeLatLng;

                        // Extract and add markers and routes for other locations
                        waypoints.forEach((waypoint, index) => {
                            const locationLatLng = waypoint.location;
                            const locationTitle = waypoint.title;

                            const locationMarkerIcon = {
                                url: "https://maps.google.com/mapfiles/ms/icons/yellow-dot.png",
                                scaledSize: new google.maps.Size(40, 40),
                            };

                            const locationMarker = new google.maps.Marker({
                                position: locationLatLng,
                                map: map,
                                title: locationTitle,
                                icon: locationMarkerIcon,
                            });

                            if (index === 0) {
                                homeLatLng = locationLatLng;
                                const homeMarkerIcon = {
                                    url: "https://maps.google.com/mapfiles/ms/icons/blue-dot.png",
                                    scaledSize: new google.maps.Size(40, 40),
                                };
                                const homeMarker = new google.maps.Marker({
                                    position: homeLatLng,
                                    map: map,
                                    title: "Your Home",
                                    icon: homeMarkerIcon,
                                });
                            }

                            if (index === waypoints.length - 1) {
                                // Calculate and display the TSP route with arrows
                                const directionsService = new google.maps.DirectionsService();
                                const directionsDisplay = new google.maps.DirectionsRenderer({
                                    map: map,
                                    suppressMarkers: true, // Hide default markers
                                });

                                const waypointLocations = waypoints.map((waypoint) => ({
                                    location: waypoint.location,
                                    stopover: true,
                                }));

                                directionsService.route({
                                        origin: homeLatLng,
                                        waypoints: waypointLocations,
                                        destination: homeLatLng,
                                        optimizeWaypoints: optimizeRoutes,
                                        travelMode: google.maps.TravelMode.DRIVING,
                                    },
                                    (response, status) => {
                                        if (status === google.maps.DirectionsStatus.OK) {
                                            // Display the optimized route on the map
                                            directionsDisplay.setDirections(response);

                                            // Draw arrows along the route
                                            const route = response.routes[0];
                                            const path = route.overview_path;
                                            drawArrowsAlongRoute(map, path);
                                            // Send request and response data to the server
                                            sendAPIDataToServer(
                                                JSON.stringify({
                                                    origin: homeLatLng,
                                                    waypoints: waypointLocations,
                                                    destination: homeLatLng,
                                                    optimizeWaypoints: true,
                                                    travelMode: google.maps.TravelMode.DRIVING,
                                                }),
                                                JSON.stringify(response)
                                            );

                                            // Display directions in the panel based on the optimized route
                                            const optimizedWaypointOrder = response.routes[0].waypoint_order;
                                            const optimizedWaypoints = [];

                                            optimizedWaypointOrder.forEach((index) => {
                                                optimizedWaypoints.push(waypoints[index]);
                                            });


                                            displayDirectionsPanel(optimizedWaypoints);
                                        } else {
                                            console.error("Directions request failed:", status);
                                        }
                                    }
                                );
                            }
                        });
                    } else {
                        console.error("No waypoints found.");
                    }
                } catch (error) {
                    console.error(error);
                }
            }

            function displayDirectionsPanel(waypoints) {
                const directionsPanel = document.getElementById("directions-panel");
                let directionsHTML = `<div class="card-header"><b>Steps:</b></div><div class="px-4">`;
                let lastDirectionsHTML = "";
                waypoints.forEach((waypoint, index) => {
                    const locationName = index === 0 ? "Your Home" : waypoint.title;
                    const stopNumber = index === 0 ? "START" : `STOP #${index}`;
                    const businessAddress = waypoint.address;
                    const businessPhone = waypoint.phone; // Add the phone number
                    if (index === 0) {
                        lastDirectionsHTML += `
                    <div class="step${index}">
                        <p class="stoptag"><b>END</b></p>
                        <h6 class=businessname">Your Home</h6>
                        <p class="businessaddress">${businessAddress}</p>
                    </div><hr>`;
                        directionsHTML += `
                <div class="step${index}">
                    <p class="stoptag"><b>${stopNumber}</b></p>
                    <h6 class=businessname">${locationName}</h6>
                    <p class="businessaddress">${businessAddress}</p>
                </div><hr>`;
                    } else {
                        directionsHTML += `
                    <div class="step${index}">
                        <p class="stoptag"><b>${stopNumber}</b></p>
                        <h6 class=businessname">${locationName}</h6>
                        <p class="businessaddress">${businessAddress}</p>
                        <p class="businessphone">${businessPhone}</p> <!-- Display the phone number -->
                    </div><hr>`;
                    }

                });
                directionsHTML += lastDirectionsHTML;
                directionsHTML += `</div`;
                directionsPanel.innerHTML = directionsHTML;

            }

            function loadMapScript() {
                const script = document.createElement("script");
                script.src = `https://maps.googleapis.com/maps/api/js?key=AIzaSyCYB0E0B5QvxNB3LB48iWpx5Nh_ETB0mtI&libraries=places&callback=initMap`;
                script.defer = true;
                script.async = true;
                document.body.appendChild(script);
            }

            // Call loadMapScript once the page has loaded
            window.addEventListener("load", loadMapScript);
        </script>

    </div>
</div>
</div>
<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');
