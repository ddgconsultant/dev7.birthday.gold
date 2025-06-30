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
        
        // Automatically load directions
        loadDirections();
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
            panel: document.getElementById('directions-panel')
        });
        
        // Sample businesses in Denver offering birthday rewards
        var locations = [
            {
                name: "Home/Hotel",
                address: "1600 17th St, Denver, CO 80202", // Starting point in downtown Denver
                type: "start"
            },
            {
                name: "Starbucks",
                address: "1401 Lawrence St, Denver, CO 80204",
                reward: "Free birthday drink",
                type: "business"
            },
            {
                name: "Red Robin",
                address: "1620 Wazee St, Denver, CO 80202",
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
        
        // Create waypoints from businesses
        var waypoints = [];
        for (var i = 1; i < locations.length - 1; i++) {
            waypoints.push({
                location: locations[i].address,
                stopover: true
            });
        }
        
        // Calculate route
        var request = {
            origin: locations[0].address,
            destination: locations[locations.length - 1].address,
            waypoints: waypoints,
            optimizeWaypoints: true,
            travelMode: 'DRIVING'
        };
        
        console.log('Calculating route...');
        directionsService.route(request, function(response, status) {
            if (status === 'OK') {
                console.log('Route calculated successfully');
                directionsRenderer.setDirections(response);
                
                // Add markers for each location
                locations.forEach(function(location, index) {
                    var marker = new google.maps.Marker({
                        position: response.routes[0].legs[index] ? 
                            response.routes[0].legs[index].start_location : 
                            response.routes[0].legs[response.routes[0].legs.length - 1].end_location,
                        map: routeMap,
                        title: location.name,
                        label: (index + 1).toString()
                    });
                    
                    // Add info window
                    var infoContent = '<div><strong>' + location.name + '</strong>';
                    if (location.reward) {
                        infoContent += '<br>Reward: ' + location.reward;
                    }
                    infoContent += '<br>Address: ' + location.address + '</div>';
                    
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
</body>
</html>