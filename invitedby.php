<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

//-------------------------------------------------------------------------------
// PREP VARIABLES
//-------------------------------------------------------------------------------
// Handle direct referral code from query string (e.g., /invitedby?RDAVIS311)
$referralCode = '';
if (!empty($_SERVER['QUERY_STRING'])) {
    // Get the first parameter from query string (supports /invitedby?RDAVIS311 format)
    $queryParts = explode('&', $_SERVER['QUERY_STRING']);
    $referralCode = trim($queryParts[0]);
    
    // Check if it's a named parameter format (?referral=CODE)
    if (strpos($referralCode, '=') !== false) {
        // Parse named parameters
        parse_str($_SERVER['QUERY_STRING'], $params);
        $referralCode = $params['referral'] ?? $params['ref'] ?? $params['code'] ?? '';
    }
    
    // Clean up the code
    $referralCode = strtoupper(trim($referralCode));
}

$errormessage = '';
$referrer_user_id = null;

if (!empty($referralCode)) {
    // Validate the referral code format (letters and numbers, 4+ characters)
    if (preg_match('/^[A-Z0-9]{4,}$/', $referralCode)) {
        
        // Look up referral code in bg_user_attributes
        $sql = "SELECT user_id FROM bg_user_attributes 
                WHERE type = 'referralcode' 
                AND name = 'generated_code' 
                AND description = :code 
                AND status = 'active'";
        $stmt = $database->query($sql, ['code' => $referralCode]);
        $referrer_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($referrer_data) {
            $referrer_user_id = $referrer_data['user_id'];
            
            // Store referral code in session for later use during signup
            $session->set('pending_referral_code', $referralCode);
            $session->set('pending_referrer_id', $referrer_user_id);
            
            // Get referrer's name for welcome message
            $referrer_info = $account->getuserdata($referrer_user_id, 'user_id');
            $referrer_name = $referrer_info['first_name'] ?? 'Someone';
            
            $successmessage = "Welcome! You've been invited by {$referrer_name}. Sign up now to get started!";
        } else {
            $errormessage = 'Invalid or expired referral code.';
        }
    } else {
        $errormessage = 'Invalid referral code format. Please check the code and try again.';
    }
} else {
    $errormessage = 'No referral code provided.';
}


// Redirect to signup page with referral context
if ($referrer_user_id) {
    $transferpage['url'] = '/signup?referral=' . urlencode($referralCode);
    $transferpage['message'] = $successmessage;
} else {
    $transferpage['url'] = '/signup';
    $transferpage['message'] = $errormessage;
}

$system->endpostpage($transferpage);
exit;