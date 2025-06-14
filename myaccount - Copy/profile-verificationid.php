<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



#-------------------------------------------------------------------------------
# PREP VARIABLES
#-------------------------------------------------------------------------------


  // Set up the verification attribute
  $user_id = $current_user_data['user_id'];
  $input = [
      'type' => 'verification',
      'name' => 'id_upload',
      'status' => 'enabled',
      'category' => 'verification',
      'description' => 'User verification ID upload enabled',
      'grouping' => 'profile'
  ];

  // Set the attribute
   $account->setUserAttribute($user_id, $input);
$session->set('enable_verificationid_upload', 'true');
  // Redirect to profile-images page
  #breakpoint($input);
  header('Location: /myaccount/profile-images');
  exit;

/*
} catch (Exception $e) {
  // Log error and redirect with error message
  error_log("Error enabling verification ID upload: " . $e->getMessage());
  $_SESSION['error'] = "Unable to enable ID verification at this time. Please try again later.";
  header('Location: /myaccount/account');
  exit;
}
*/