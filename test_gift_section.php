<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Set up test environment
$values = $_POST;
$errors = [];
$signup_process = [
    'account_type' => 'giftcertificate',
    'account_plan_id' => 1,
    'plandata' => ['account_name' => 'Gift Certificate Test']
];

// Include the section
echo "<h2>Testing Gift Certificate Section</h2>";
echo "<div style='border: 2px solid #ccc; padding: 20px; margin: 20px;'>";

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/core/forms/signup/section_gift_certificate.inc')) {
    include($_SERVER['DOCUMENT_ROOT'] . '/core/forms/signup/section_gift_certificate.inc');
} else {
    echo "<p style='color: red;'>ERROR: Gift certificate section file not found!</p>";
}

echo "</div>";

// Show current session data
echo "<h3>Session Data</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>