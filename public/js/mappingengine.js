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