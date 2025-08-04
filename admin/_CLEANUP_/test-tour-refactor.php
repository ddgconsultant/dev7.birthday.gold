<?php
/**
 * Comprehensive test for tour-compressed-complete.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type
header('Content-Type: text/html; charset=UTF-8');

// Include framework
$addClasses = ['sms', 'enrollment'];
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is admin
if (!$account->isadmin()) {
    die("Admin access required");
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Tour Refactor Test</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        .test-section { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Tour Refactor Test Suite</h1>
    
    <div class="test-section">
        <h2>1. Database Schema Check</h2>
        <?php
        try {
            // Check bg_company_locations columns
            $stmt = $database->query("SHOW COLUMNS FROM bg_company_locations");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<h3>bg_company_locations columns:</h3>";
            echo "<pre>" . implode("\n", $columns) . "</pre>";
            
            $required = ['latitude', 'longitude', 'phone', 'name'];
            $missing = array_diff($required, $columns);
            
            if ($missing) {
                echo "<p class='warning'>⚠️ Missing columns: " . implode(', ', $missing) . "</p>";
                echo "<p>SQL to add missing columns:</p>";
                echo "<pre>";
                if (in_array('name', $missing)) {
                    echo "ALTER TABLE bg_company_locations ADD COLUMN name VARCHAR(255) NULL AFTER company_id;\n";
                }
                if (in_array('latitude', $missing)) {
                    echo "ALTER TABLE bg_company_locations ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER country;\n";
                }
                if (in_array('longitude', $missing)) {
                    echo "ALTER TABLE bg_company_locations ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude;\n";
                }
                if (in_array('phone', $missing)) {
                    echo "ALTER TABLE bg_company_locations ADD COLUMN phone VARCHAR(20) NULL AFTER longitude;\n";
                }
                echo "</pre>";
            } else {
                echo "<p class='success'>✓ All required columns exist</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>2. SQL Query Test</h2>
        <?php
        try {
            $testDate = date('Y-m-d');
            $testUserId = $current_user_data['user_id'];
            
            // Test the main query from tour-compressed-complete.php
            $sql = "SELECT t.*, 
                    cl.location_id as cl_location_id,
                    cl.address as cl_address,
                    cl.city as cl_city,
                    cl.state as cl_state,
                    cl.zip_code as cl_zip_code,
                    c.company_name,
                    c.company_logo,
                    c.latitude as c_latitude,
                    c.longitude as c_longitude,
                    c.address as c_address,
                    c.city as c_city,
                    c.state as c_state,
                    c.zip_code as c_zip_code
                    FROM bg_user_tours t 
                    LEFT JOIN bg_companies c ON t.company_id = c.company_id
                    LEFT JOIN bg_company_locations cl ON t.location_id = cl.location_id 
                    WHERE t.calendar_dt = :date 
                    AND t.user_id = :user_id 
                    ORDER BY t.rank ASC";
            
            $stmt = $database->prepare($sql);
            $stmt->execute([':date' => $testDate, ':user_id' => $testUserId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p class='success'>✓ Main query executed successfully</p>";
            echo "<p>Rows returned: " . count($results) . "</p>";
            
            if (count($results) > 0) {
                echo "<h3>Sample row structure:</h3>";
                echo "<pre>" . print_r(array_keys($results[0]), true) . "</pre>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>Error Code: " . $e->getCode() . "</pre>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>3. Helper Functions Test</h2>
        <?php
        // Test if functions from tour-compressed-complete.php are available
        $functionsToTest = [
            'getUserAttribute' => 'function getUserAttribute($database, $userId, $type, $name)',
            'getCompanyLocations' => 'function getCompanyLocations($database, $companyId)', 
            'haversineDistance' => 'function haversineDistance($lat1, $lon1, $lat2, $lon2)'
        ];
        
        foreach ($functionsToTest as $func => $signature) {
            if (function_exists($func)) {
                echo "<p class='success'>✓ $signature exists</p>";
                
                // Test haversine function
                if ($func === 'haversineDistance') {
                    $distance = haversineDistance(39.7392, -104.9903, 40.7128, -74.0060); // Denver to NYC
                    echo "<p>Test: Denver to NYC = " . round($distance) . " miles (expected ~1600)</p>";
                }
            } else {
                echo "<p class='warning'>⚠️ $signature not found</p>";
            }
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>4. AJAX Handlers Test</h2>
        <?php
        $ajaxHandlers = [
            'tour-update-home.php',
            'tour-search-locations.php',
            'tour-save-location.php',
            'tour-send-phone.php'
        ];
        
        foreach ($ajaxHandlers as $handler) {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/myaccount/ajax/' . $handler;
            if (file_exists($path)) {
                echo "<p class='success'>✓ /myaccount/ajax/$handler exists</p>";
                
                // Check for syntax errors
                $output = shell_exec("php -l '$path' 2>&1");
                if (strpos($output, 'No syntax errors') !== false) {
                    echo "<p class='success'>  ✓ No syntax errors</p>";
                } else {
                    echo "<p class='error'>  ✗ Syntax error: " . htmlspecialchars($output) . "</p>";
                }
            } else {
                echo "<p class='error'>✗ /myaccount/ajax/$handler missing</p>";
            }
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>5. File Load Test</h2>
        <?php
        $testUrl = "https://{$_SERVER['HTTP_HOST']}/myaccount/tour-compressed-complete.php?date=" . date('Y-m-d');
        echo "<p>Testing URL: <a href='$testUrl' target='_blank'>$testUrl</a></p>";
        
        // Use cURL to test as logged-in user
        $ch = curl_init($testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=" . session_id());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p>HTTP Response Code: $httpCode</p>";
        
        if ($httpCode == 200) {
            echo "<p class='success'>✓ Page loads successfully</p>";
            
            // Check for errors in response
            if (preg_match('/(Fatal error|Parse error|Warning:|Notice:|Exception)/', $response, $matches)) {
                echo "<p class='error'>✗ PHP errors found in output: " . htmlspecialchars($matches[1]) . "</p>";
            } else {
                echo "<p class='success'>✓ No PHP errors in output</p>";
            }
            
            // Check for login redirect
            if (strpos($response, 'window.location.href=\'/login\'') !== false) {
                echo "<p class='warning'>⚠️ Page redirects to login (auth issue)</p>";
            }
        } else {
            echo "<p class='error'>✗ Page returned HTTP $httpCode</p>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Summary</h2>
        <p>Test completed at <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><a href="/myaccount/tour-compressed-complete.php?date=<?php echo date('Y-m-d'); ?>">Open tour-compressed-complete.php</a></p>
    </div>
</body>
</html>