<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

//-------------------------------------------------------------------------------
// PREP VARIABLES
//-------------------------------------------------------------------------------
$referralCode = isset($_GET['referral']) ? $_GET['referral'] : '';

if (!empty($referralCode)) {
    // Validate the referral code format
    if (preg_match('/^[A-Z0-9]{5}$/', $referralCode)) {
        $stmt = $database->prepare("SELECT user_id FROM bg_user_attributes WHERE type='referral_code' AND `status`='A' AND name = ?");
        $stmt->bind_param("s", $referralCode);
        $stmt->execute();
        #$stmt->store_result();

        if ($stmt->num_rows > 0) {
           # $stmt->bind_result($user_id);
            while ($stmt->fetch()) {
                // Process your row data here
                $errormessage= 'Referral code belongs to user ID: ' . $user_id;
            }
        } else {
            $errormessage= 'Referral code not found.';
        }
        $stmt->close();
    } else {
        $errormessage= 'Invalid referral code format.';
    }
} else {
    $errormessage= 'No referral code provided.';
}

// Handling the referral relationship after user signs up
if (isset($newUserId) && !empty($referralCode)) {
    $result = $database->query("SELECT user_id FROM bg_user_attributes WHERE type='referral_code' AND `status`='A' AND name = '$referralCode'");
    if ($result && $result->num_rows > 0) {
        $referrer = $result->fetch_object();
        if ($referrer && isset($referrer->user_id)) {
            $referrerId = $referrer->user_id;
            $database->query("INSERT INTO referrals (referrer_id, referred_id) VALUES ('$referrerId', '$newUserId')");
        } else {
            $errormessage= 'Error: Referrer not found.';
        }
    } else {
        $errormessage= 'Error: Referrer not found.';
    }
}

// Set session variable for referral user ID if not already set
if ($session->get('referral_userid', '') == '') {
    $session->set('referral_userid', isset($current_user_data['user_id']) ? $current_user_data['user_id'] : '');
}

$transferpage['url'] = '/';
$transferpage['message'] = $errormessage;
$qik->endpostpage($transferpage);
exit;