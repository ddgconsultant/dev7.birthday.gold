<?php
// Test script to verify promo/referral code retention on form errors
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

echo "<h2>Test: Promo/Referral Code Retention</h2>";

// Check current session data
$signup_process = $session->get('signup_process_data', []);

echo "<h3>Current Session Data:</h3>";
echo "<pre>";
echo "Full signup_process_data:\n";
print_r($signup_process);
echo "\nPromo Code: " . ($signup_process['promo_code'] ?? 'Not set') . "\n";
echo "Referral Code: " . ($signup_process['referral_code'] ?? 'Not set') . "\n";
echo "</pre>";

// Check POST data if form was submitted
if (!empty($_POST)) {
    echo "<h3>POST Data:</h3>";
    echo "<pre>";
    echo "Promo Code: " . ($_POST['promo_code'] ?? 'Not set') . "\n";
    echo "Referral Code: " . ($_POST['referral_code'] ?? 'Not set') . "\n";
    echo "</pre>";
}

// Simulate form submission scenarios
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'set_valid':
            $signup_process['promo_code'] = 'TESTPROMO';
            $signup_process['referral_code'] = 'REF123';
            $session->set('signup_process_data', $signup_process);
            echo "<p style='color: green;'>✓ Set valid codes in session</p>";
            break;
            
        case 'set_plan':
            // Set a valid plan to test createaccount page
            $signup_process['account_plan_id'] = 1;
            $signup_process['account_type'] = 'user';
            $signup_process['account_plan'] = 'Basic';
            $signup_process['account_cost'] = 0;
            $signup_process['plandata'] = ['id' => 1, 'account_name' => 'Basic Plan', 'account_verification' => 'required'];
            $session->set('signup_process_data', $signup_process);
            echo "<p style='color: green;'>✓ Set valid plan in session</p>";
            break;
            
        case 'clear':
            unset($signup_process['promo_code']);
            unset($signup_process['referral_code']);
            $session->set('signup_process_data', $signup_process);
            echo "<p style='color: orange;'>✓ Cleared codes from session</p>";
            break;
            
        case 'clear_all':
            $session->set('signup_process_data', []);
            echo "<p style='color: red;'>✓ Cleared entire signup process data</p>";
            break;
    }
    
    // Refresh to show updated data
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

echo "<h3>Test Actions:</h3>";
echo "<p>";
echo "<a href='?action=set_plan' class='btn btn-sm btn-primary'>Set Valid Plan</a> | ";
echo "<a href='?action=set_valid' class='btn btn-sm btn-success'>Set Valid Codes</a> | ";
echo "<a href='?action=clear' class='btn btn-sm btn-warning'>Clear Codes</a> | ";
echo "<a href='?action=clear_all' class='btn btn-sm btn-danger'>Clear All</a> | ";
echo "<a href='/createaccount.php' class='btn btn-sm btn-info'>Go to Create Account</a>";
echo "</p>";

echo "<hr>";
echo "<h3>Testing Instructions:</h3>";
echo "<ol>";
echo "<li>Click 'Set Valid Plan' to ensure you can access the create account page</li>";
echo "<li>Click 'Set Valid Codes' to simulate having promo/referral codes (optional)</li>";
echo "<li>Go to Create Account page</li>";
echo "<li>Enter promo/referral codes in the form</li>";
echo "<li>Fill in the form with intentional errors (e.g., leave password blank)</li>";
echo "<li>Submit the form</li>";
echo "<li>Verify that promo/referral codes are retained in the form fields</li>";
echo "</ol>";

echo "<hr>";
echo "<h3>Direct Test Form:</h3>";
echo "<form method='POST' action='/createaccount.php'>";
echo "<input type='hidden' name='csrf_token' value='" . $session->get('csrf_token') . "'>";
echo "<p>Promo Code: <input type='text' name='promo_code' value='TESTCODE123'></p>";
echo "<p>Referral Code: <input type='text' name='referral_code' value='REF456'></p>";
echo "<p><button type='submit'>Submit to Create Account</button></p>";
echo "</form>";
?>