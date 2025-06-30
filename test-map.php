<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Simple test page for Google Maps
?>
<!DOCTYPE html>
<html>
<head>
    <title>Google Maps Test Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        #map {
            height: 500px;
            width: 100%;
            border: 2px solid #ccc;
            margin: 20px 0;
        }
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
        pre {
            background: #f5f5f5;
            padding: 10px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Google Maps API Test Page</h1>
    
    <div class="info">
        <h3>Test Information:</h3>
        <p><strong>Domain:</strong> <?php echo $_SERVER['HTTP_HOST']; ?></p>
        <p><strong>Protocol:</strong> <?php echo $_SERVER['REQUEST_SCHEME']; ?></p>
        <p><strong>API Key:</strong> AIzaSyCtO04U0nKVeodiS0TL12ib8MDEtwdANOM</p>
    </div>

    <h2>Birthday Tour Navigation Test</h2>
    <div id="api-status" class="info">Loading Google Maps API...</div>
    
    <div style="display: flex; margin-top: 20px; gap: 20px;">
        <div style="flex: 1;">
            <h3>Turn-by-Turn Directions:</h3>
            <div id="directions-panel" style="height: 600px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: white;"></div>
        </div>
        <div style="flex: 2;">
            <h3>Route Map:</h3>
            <div id="route-map" style="height: 600px; border: 1px solid #ccc;"></div>
        </div>
    </div>

    <script>

    // Map initialization
    var routeMap;
    var directionsService;
    var directionsRenderer;
    
    function initMap() {
        console.log('initMap() called');
        document.getElementById('api-status').className = 'success';
        document.getElementById('api-status').textContent = 'Google Maps API loaded successfully! Loading directions...';
        
        // We'll call this from loadDirections after locations are defined
        // loadDirections();
    }
    
    // Make initMap global
    window.initMap = initMap;
    
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
        script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyCtO04U0nKVeodiS0TL12ib8MDEtwdANOM&libraries=places&callback=initMap';
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
    // Create custom directions panel
    function createCustomDirectionsPanel(response, locations) {
        var panel = document.getElementById('directions-panel');
        var html = '<div style="padding: 10px;">';
        html += '<h4 style="margin-bottom: 15px; color: #1a73e8;">Turn-by-Turn Directions</h4>';
        
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
            html += '<div style="margin-left: 35px; padding-left: 15px; border-left: 2px dashed #dadce0;">';
            html += '<div style="margin-top: 10px; font-size: 14px;">';
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
                        html += '<div style="margin-bottom: 5px; padding-left: 10px; color: #5f6368;">';
                        html += '<span style="color: #9aa0a6;">• </span>';
                        html += mainInstruction;
                        html += ' <span style="color: #9aa0a6;">(' + step.distance.text + ')</span>';
                        html += '</div>';
                    }
                    
                    // Add destination part as separate line with green checkmark
                    html += '<div class="text-success" style="margin-bottom: 5px; padding-left: 10px;">';
                    html += '<span>✓ </span>';
                    html += destinationPart;
                    html += '</div>';
                } else {
                    // Normal instruction without destination
                    html += '<div style="margin-bottom: 5px; padding-left: 10px; color: #5f6368;">';
                    html += '<span style="color: #9aa0a6;">• </span>';
                    html += instruction;
                    html += ' <span style="color: #9aa0a6;">(' + step.distance.text + ')</span>';
                    html += '</div>';
                }
            });
            html += '</div>';
            
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
    
    // Geocode missing addresses
    function geocodeMissingAddresses(locations, callback) {
        console.log('Starting geocodeMissingAddresses...');
        var geocodePromises = [];
        var placesService = new google.maps.places.PlacesService(document.createElement('div'));
        
        locations.forEach(function(location, index) {
            if (location.needsGeocoding && location.type === 'business') {
                var promise = new Promise(function(resolve) {
                    var searchQuery = location.name + ' near ' + location.city + ', ' + location.state;
                    console.log('Searching for:', searchQuery);
                    
                    var request = {
                        query: searchQuery,
                        fields: ['name', 'geometry', 'formatted_address']
                    };
                    
                    placesService.textSearch(request, function(results, status) {
                        if (status === google.maps.places.PlacesServiceStatus.OK && results.length > 0) {
                            // Use the first result
                            var place = results[0];
                            console.log('Found location for', location.name, ':', place.formatted_address);
                            
                            // Update the location
                            locations[index].address = place.formatted_address;
                            locations[index].needsGeocoding = false;
                            
                            // Update display
                            document.getElementById('api-status').innerHTML += '<br>✓ Found: ' + location.name + ' at ' + place.formatted_address;
                        } else {
                            console.error('Could not find location for', location.name);
                            // Use city as fallback
                            locations[index].address = location.city + ', ' + location.state;
                        }
                        resolve();
                    });
                });
                geocodePromises.push(promise);
            }
        });
        
        // Wait for all geocoding to complete
        Promise.all(geocodePromises).then(function() {
            console.log('All geocoding complete');
            callback(locations);
        });
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
            // We'll create a custom panel instead
            // panel: document.getElementById('directions-panel')
        });
        
        // Sample businesses - some with addresses, some without
        var locations = [
            {
                name: "Home",
                address: "10106 Atlanta Street, Parker, CO 80134",
                type: "start"
            },
            {
                name: "Starbucks",
                address: "1401 Lawrence St, Denver, CO 80204",
                reward: "Free birthday drink",
                type: "business"
            },
            {
                name: "Famous Dave's Bar-B-Que",
                address: null, // No address, will need to geocode
                city: "Aurora",
                state: "CO",
                needsGeocoding: true,
                reward: "Free birthday dessert",
                type: "business"
            },
            {
                name: "Red Robin",
                address: null, // No address, will need to geocode
                city: "Parker", 
                state: "CO",
                needsGeocoding: true,
                reward: "Free birthday burger",
                type: "business"
            },
            {
                name: "Baskin-Robbins",
                address: "500 16th St Mall, Denver, CO 80202",
                reward: "Free birthday ice cream",
                type: "business"
            }
        ];
        
        // Check if we need to geocode any addresses
        var needsGeocoding = locations.some(function(loc) {
            return loc.needsGeocoding === true;
        });
        
        if (needsGeocoding) {
            console.log('Some locations need geocoding...');
            document.getElementById('api-status').innerHTML += '<br>Searching for business locations...';
            
            geocodeMissingAddresses(locations, function(updatedLocations) {
                console.log('Geocoding complete, now calculating route...');
                calculateRoute(updatedLocations);
            });
        } else {
            calculateRoute(locations);
        }
    }
    
    function calculateRoute(locations) {
        // Create waypoints from businesses
        var waypoints = [];
        for (var i = 1; i < locations.length - 1; i++) {
            waypoints.push({
                location: locations[i].address,
                stopover: true
            });
        }
        
        // Calculate route - return to home at end (circular route)
        var request = {
            origin: locations[0].address,
            destination: locations[0].address, // Return to start
            waypoints: waypoints,
            optimizeWaypoints: true,
            travelMode: 'DRIVING'
        };
        
        console.log('Calculating route...');
        console.log('Waypoints:', waypoints);
        
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
                            fillColor: location.type === 'start' ? '#4285F4' : '#EA4335',
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
    
    // Call loadDirections when map is initialized
    window.initMap = function() {
        console.log('initMap() called');
        document.getElementById('api-status').className = 'success';
        document.getElementById('api-status').textContent = 'Google Maps API loaded successfully! Loading directions...';
        
        // Now load directions
        loadDirections();
    }
    </script>
</body>
</html>