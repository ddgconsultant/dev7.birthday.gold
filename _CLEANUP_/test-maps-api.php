<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Simple test page for Google Maps API
?>
<!DOCTYPE html>
<html>
<head>
    <title>Google Maps API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .info { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #ffcccc; color: #cc0000; }
        .success { background: #ccffcc; color: #008800; }
        #map { height: 400px; margin: 20px 0; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Google Maps API Configuration Test</h1>
    
    <div class="info">
        <h2>Configuration Status:</h2>
        <?php if (!empty($sitesettings['GOOGLEAPI']['mainkey'])): ?>
            <p class="success">✓ API Key is configured (First 10 chars: <?php echo substr($sitesettings['GOOGLEAPI']['mainkey'], 0, 10); ?>...)</p>
        <?php else: ?>
            <p class="error">✗ API Key is NOT configured</p>
        <?php endif; ?>
        
        <?php if (!empty($sitesettings['GOOGLEAPI']['mapid'])): ?>
            <p class="success">✓ Map ID is configured: <?php echo $sitesettings['GOOGLEAPI']['mapid']; ?></p>
        <?php else: ?>
            <p class="info">ℹ Map ID not in config, using default: 9cd54b1058579fe87b380337</p>
        <?php endif; ?>
        
        <p><strong>Current Domain:</strong> <?php echo $_SERVER['HTTP_HOST']; ?></p>
        <p><strong>Protocol:</strong> <?php echo (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'HTTPS' : 'HTTP'; ?></p>
    </div>
    
    <div class="info">
        <h2>Required Google APIs:</h2>
        <p>Make sure these APIs are enabled in your Google Cloud Console:</p>
        <ul>
            <li>Maps JavaScript API</li>
            <li>Places API</li>
            <li>Geocoding API</li>
            <li>Directions API</li>
        </ul>
    </div>
    
    <div class="info">
        <h2>Test Map:</h2>
        <div id="map"></div>
        <div id="status"></div>
    </div>
    
    <script>
    // Error handler
    window.gm_authFailure = function() {
        document.getElementById('status').innerHTML = '<p class="error">Google Maps authentication failed! Check console for details.</p>';
        console.error('Authentication failure details:', arguments);
    };
    
    function initMap() {
        try {
            var map = new google.maps.Map(document.getElementById('map'), {
                center: {lat: 39.7392, lng: -104.9903},
                zoom: 12,
                mapId: '<?php echo $sitesettings['GOOGLEAPI']['mapid'] ?? '9cd54b1058579fe87b380337'; ?>' // Birthday Gold Tour Map ID for AdvancedMarkerElement
            });
            
            document.getElementById('status').innerHTML = '<p class="success">✓ Google Maps loaded successfully!</p>';
            
            // Test marker creation
            var marker = new google.maps.marker.AdvancedMarkerElement({
                position: {lat: 39.7392, lng: -104.9903},
                map: map,
                title: 'Test Marker'
            });
            
            document.getElementById('status').innerHTML += '<p class="success">✓ AdvancedMarkerElement created successfully!</p>';
            
        } catch (e) {
            document.getElementById('status').innerHTML = '<p class="error">Error: ' + e.message + '</p>';
            console.error('Map initialization error:', e);
        }
    }
    
    // Load Google Maps
    <?php if (!empty($sitesettings['GOOGLEAPI']['mainkey'])): ?>
    var script = document.createElement('script');
    script.src = 'https://maps.googleapis.com/maps/api/js?key=<?php echo $sitesettings['GOOGLEAPI']['mainkey']; ?>&libraries=places,marker&callback=initMap&v=weekly&loading=async';
    script.async = true;
    script.defer = true;
    
    script.onerror = function() {
        document.getElementById('status').innerHTML = '<p class="error">Failed to load Google Maps script!</p>';
        console.error('Script loading error');
    };
    
    document.head.appendChild(script);
    <?php else: ?>
    document.getElementById('status').innerHTML = '<p class="error">Cannot load map - API key not configured!</p>';
    <?php endif; ?>
    </script>
    
    <div class="info">
        <h2>Troubleshooting Steps:</h2>
        <ol>
            <li>Check browser console (F12) for specific error messages</li>
            <li>Verify API key is valid in Google Cloud Console</li>
            <li>Check that billing is enabled on your Google Cloud project</li>
            <li>Ensure this domain (<?php echo $_SERVER['HTTP_HOST']; ?>) is in the API key restrictions</li>
            <li>Verify all required APIs are enabled</li>
            <li><strong>Map ID:</strong> Using production Map ID: 9cd54b1058579fe87b380337</li>
        </ol>
    </div>
    
    <div class="info">
        <h2>Note on Map IDs:</h2>
        <p>To use AdvancedMarkerElement (new marker system), you need a Map ID.</p>
        <p><strong>Current Map ID:</strong> <?php echo $sitesettings['GOOGLEAPI']['mapid'] ?? '9cd54b1058579fe87b380337'; ?> (birthday-gold-www)</p>
        <p>This Map ID is configured for:</p>
        <ul>
            <li>JavaScript maps (Vector style)</li>
            <li>No tilt or rotation</li>
            <li>Optimized for tour route display</li>
        </ul>
    </div>
</body>
</html>