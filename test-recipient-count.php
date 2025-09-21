<?php
// Test the recipient count system directly
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

echo "<h1 style='background:yellow;padding:20px;'>TESTING RECIPIENT COUNT SYSTEM</h1>";

// Test 1: Check if marketing class exists
echo "<h2>Test 1: Marketing Class</h2>";
if (class_exists('Marketing')) {
    echo "<p style='color:green;font-size:20px;'>✅ Marketing class exists</p>";
} else {
    echo "<p style='color:red;font-size:20px;'>❌ Marketing class NOT found</p>";
}

// Test 2: Check if marketing object is available
echo "<h2>Test 2: Marketing Object</h2>";
if (isset($marketing) && is_object($marketing)) {
    echo "<p style='color:green;font-size:20px;'>✅ Marketing object is available</p>";
} else {
    echo "<p style='color:red;font-size:20px;'>❌ Marketing object NOT available</p>";
}

// Test 3: Test the getRecipientCount method
echo "<h2>Test 3: getRecipientCount Method</h2>";
if (method_exists($marketing, 'getRecipientCount')) {
    echo "<p style='color:green;font-size:20px;'>✅ getRecipientCount method exists</p>";
    
    // Test with "all" token
    $test_tokens = [
        ['type' => 'all', 'label' => 'All Active Users', 'value' => 'all']
    ];
    
    echo "<p>Testing with 'all' token...</p>";
    try {
        $count = $marketing->getRecipientCount($test_tokens);
        echo "<p style='background:lightgreen;padding:10px;font-size:24px;'>Result: <strong>$count</strong> recipients found!</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red;font-size:20px;'>❌ getRecipientCount method NOT found</p>";
}

// Test 4: Direct database test
echo "<h2>Test 4: Direct Database Query</h2>";
try {
    $sql = "SELECT COUNT(*) as count FROM bg_users WHERE status = 'active'";
    $result = $database->getrow($sql);
    echo "<p style='background:lightblue;padding:10px;font-size:20px;'>Direct query result: <strong>" . $result['count'] . "</strong> active users</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Database error: " . $e->getMessage() . "</p>";
}

// Test 5: Test AJAX endpoint with curl
echo "<h2>Test 5: AJAX Endpoint</h2>";
echo "<p>Testing /myaccount/marketing/ajax/newsletter-recipients-count.php</p>";

// Show current user status
if ($account->isactive()) {
    echo "<p style='color:green;'>✅ User is logged in (ID: " . $current_user_data['user_id'] . ")</p>";
} else {
    echo "<p style='color:red;'>❌ User is NOT logged in</p>";
}

echo "<hr>";
echo "<h2 style='background:orange;padding:10px;'>JavaScript Test Section</h2>";
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/public/js/recipient-token-field-simple.js?v=<?php echo time(); ?>"></script>

<div id="testResults" style="background:black;color:lime;padding:20px;font-family:monospace;">
    <h3>JavaScript Test Results:</h3>
</div>

<script>
$(document).ready(function() {
    $('#testResults').append('<div>✅ jQuery is loaded</div>');
    
    if (typeof updateRecipientCount === 'function') {
        $('#testResults').append('<div>✅ updateRecipientCount function exists</div>');
    } else {
        $('#testResults').append('<div style="color:red;">❌ updateRecipientCount function NOT found</div>');
    }
    
    if (typeof addToken === 'function') {
        $('#testResults').append('<div>✅ addToken function exists</div>');
    } else {
        $('#testResults').append('<div style="color:red;">❌ addToken function NOT found</div>');
    }
    
    // Test AJAX call
    $('#testResults').append('<div>📡 Testing AJAX endpoint...</div>');
    
    $.ajax({
        url: '/myaccount/marketing/ajax/newsletter-recipients-count.php',
        type: 'POST',
        data: {
            tokens: JSON.stringify([{type: 'all', label: 'All', value: 'all'}]),
            debug: 1
        },
        success: function(response) {
            $('#testResults').append('<div style="color:lime;">✅ AJAX SUCCESS! Response: ' + JSON.stringify(response) + '</div>');
        },
        error: function(xhr, status, error) {
            $('#testResults').append('<div style="color:red;">❌ AJAX ERROR: ' + status + ' - ' + error + '</div>');
            $('#testResults').append('<div style="color:yellow;">Response: ' + xhr.responseText + '</div>');
        }
    });
});
</script>

<?php
$app->outputpage();
?>