<?PHP
// AJAX handler for sending test newsletter emails
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$test_email = isset($_POST['test_email']) ? trim($_POST['test_email']) : '';
$subject = isset($_POST['subject']) ? $_POST['subject'] : 'Test Newsletter';
$body = isset($_POST['body']) ? $_POST['body'] : '';
$category = isset($_POST['category']) ? $_POST['category'] : '';

if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
    // Create test user data
    $test_user = [
        'email' => $test_email,
        'first_name' => 'Test',
        'last_name' => 'User',
        'city' => 'Seattle',
        'birth_month' => date('n')
    ];
    
    // Replace placeholders
    $subject = str_replace('[[first_name]]', $test_user['first_name'], $subject);
    $subject = str_replace('[[city]]', $test_user['city'], $subject);
    
    $months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
               'July', 'August', 'September', 'October', 'November', 'December'];
    $birth_month_name = $months[$test_user['birth_month']];
    $subject = str_replace('[[birthday_month]]', $birth_month_name, $subject);
    
    // Replace body placeholders
    $body = str_replace('[[first_name]]', $test_user['first_name'], $body);
    $body = str_replace('[[city]]', $test_user['city'], $body);
    $body = str_replace('[[birthday_month]]', $birth_month_name, $body);
    
    // Add sample CTA block
    if (strpos($body, '[[CTA_BLOCK]]') !== false) {
        $cta_sample = '
        <div style="background: #f8f9fa; padding: 30px; margin: 30px 0; border-radius: 10px;">
            <h2 style="text-align: center; margin-bottom: 20px;">Birthday Rewards You Might Like</h2>
            <p style="text-align: center; color: #666;">
                <em>This is a sample CTA block. In the actual email, personalized brand recommendations will appear here based on the ' . $category . ' category.</em>
            </p>
        </div>';
        
        $body = str_replace('[[CTA_BLOCK]]', $cta_sample, $body);
    }
    
    // Add test footer
    $body .= '
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccc; font-size: 12px; color: #666; text-align: center;">
        <p><strong>TEST EMAIL</strong> - This is a test version of the newsletter</p>
        <p>Birthday Gold | 123 Main St, Seattle, WA 98101</p>
    </div>';
    
    // Send test email
    try {
        $email_details = [
            'to' => [$test_email, 'Test User'],
            'subject' => '[TEST] ' . $subject,
            'body' => $body,
            'from' => ['hello@birthday.gold', 'Birthday Gold'],
            'donottrack' => true
        ];
        
        $mail->sendmail($email_details);
        
        $response['success'] = true;
        $response['message'] = 'Test email sent successfully to ' . $test_email;
    } catch (Exception $e) {
        $response['message'] = 'Error sending test email: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid email address';
}

echo json_encode($response);
?>