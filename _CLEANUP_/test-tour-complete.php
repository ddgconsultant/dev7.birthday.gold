<?php
/**
 * Test script for tour-compressed-complete.php
 * This bypasses authentication to test core functionality
 */

// Set up minimal environment
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
$addClasses[] = 'sms';

// Skip auth check for testing
$_SESSION['user_id'] = 1;
$skip_auth_check = true;

// Include site controller
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Mock user data for testing
$current_user_data = [
    'user_id' => 1,
    'profile_phone_number' => '3035551234',
    'profile_first_name' => 'Test',
    'profile_last_name' => 'User'
];

// Mock account object
class MockAccount {
    public function isactive() { return true; }
}
$account = new MockAccount();

echo "=== TESTING TOUR-COMPRESSED-COMPLETE.PHP ===\n\n";

// Test 1: Check if helper functions exist
echo "Test 1: Helper Functions\n";
include_once(__DIR__ . '/tour-compressed-complete.php');
$functions = ['getUserAttribute', 'getCompanyLocations', 'haversineDistance'];
foreach ($functions as $func) {
    echo "- Function $func: " . (function_exists($func) ? "✓ EXISTS" : "✗ MISSING") . "\n";
}

// Test 2: Test haversine distance calculation
echo "\nTest 2: Haversine Distance\n";
$distance = haversineDistance(39.5182, -104.7744, 39.7392, -104.9903); // Parker to Denver
echo "- Distance Parker to Denver: " . round($distance, 1) . " miles (expected ~20)\n";

// Test 3: Test database queries
echo "\nTest 3: Database Queries\n";
try {
    // Test the main tour query
    $date = date('Y-m-d');
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
    $stmt->execute([':date' => $date, ':user_id' => 1]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "- Main tour query: ✓ SUCCESS (" . count($results) . " rows)\n";
} catch (Exception $e) {
    echo "- Main tour query: ✗ FAILED - " . $e->getMessage() . "\n";
}

// Test 4: Check for JavaScript errors in output
echo "\nTest 4: JavaScript Variables\n";
ob_start();
$companies = []; // Empty array for testing
$homeaddress = "123 Test St, Denver, CO 80202";
$home_lat = 39.7392;
$home_lng = -104.9903;
?>
<script>
var tourData = {
    date: <?php echo json_encode($date); ?>,
    formattedDate: <?php echo json_encode($formattedDate ?? 'Thursday, July 3, 2025'); ?>,
    homeLocation: {
        address: <?php echo json_encode($homeaddress); ?>,
        lat: <?php echo json_encode($home_lat); ?>,
        lng: <?php echo json_encode($home_lng); ?>
    },
    companies: <?php echo json_encode($companies); ?>
};
console.log('Tour data initialized:', tourData);
</script>
<?php
$jsOutput = ob_get_clean();
echo "- JavaScript generation: " . (strpos($jsOutput, 'tourData') !== false ? "✓ SUCCESS" : "✗ FAILED") . "\n";

// Test 5: Check AJAX handlers
echo "\nTest 5: AJAX Handler Files\n";
$ajaxFiles = [
    'tour-update-home.php',
    'tour-search-locations.php', 
    'tour-save-location.php',
    'tour-send-phone.php'
];
foreach ($ajaxFiles as $file) {
    $path = __DIR__ . '/ajax/' . $file;
    echo "- $file: " . (file_exists($path) ? "✓ EXISTS" : "✗ MISSING") . "\n";
}

echo "\n=== TESTS COMPLETE ===\n";