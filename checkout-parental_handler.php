<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');




#-------------------------------------------------------------------------------
# HANDLE THE FORM SUBMISSION
#-------------------------------------------------------------------------------
if ($app->formposted('GET')) {

  if (isset($_GET['redirect_status'])) {


    $listofminors= $session->get('parental_listofminors');


    switch ($_GET['redirect_status']) {
#-------------------------------------------------------------------------------
      case 'succeeded':
      case 'free':
        if ($_GET['redirect_status'] == 'succeeded') {
          // Get PaymentIntent ID from webhook data
          $intentId = $_GET['payment_intent'];

          // Set secret API key
          $STRIPE_API_SECRET_KEY = $STRIPECONFIG['STRIPE_SECRET'];

          // Initialize Stripe

          require_once('vendor/autoload.php');
          #$stripe = new \Stripe\StripeClient($STRIPE_API_SECRET_KEY);
          \Stripe\Stripe::setApiKey($STRIPE_API_SECRET_KEY);

          // Retrieve the PaymentIntent
          $intent = \Stripe\PaymentIntent::retrieve($intentId);

          // Get metadata
          $metadata = $intent->metadata;
          session_tracking('checkout-parental_handler-PAID', $metadata);
        }

        if ($_GET['redirect_status'] == 'free') {
          $userregistrationdata = $session->get('userregistrationdata');
          $metadata['customer_user_id'] = $userregistrationdata['user_id'];
          $metadata['customer_plan'] = 'free';
          $metadata['customer_revenue'] = 0;

          session_tracking('checkout-parental_handler-FREE', $metadata);
        }

        // Validate metadata structure
        if (
          isset($metadata)  &&
          isset($metadata['customer_user_id']) &&
          # isset($metadata['customer_email']) &&
          isset($metadata['customer_plan'])
        ) {

          # $userregistrationdata = $session->get('userregistrationdata', '');
          #  $userid=(isset($userregistrationdata['user_id']) ?  $userregistrationdata['user_id'] :'');
          #   if ($userid !== '') {

          $userid = $metadata['customer_user_id'];
          $updatefields = $updatefields1 = $updatefields2 = array();
          if ($userid !== '') {
           # breakpoint(json_decode($metadata['parental_listofminors']));
if  (!empty(json_decode($metadata['parental_listofminors']))) {
            $listofminors=implode(',', json_decode($metadata['parental_listofminors']));
            // Convert the list of minors to an array
$minorsArray = explode(',', $listofminors);

// Create placeholders for each item in the array
$placeholders = str_repeat('?,', count($minorsArray) - 1) . '?';

// Prepare your SQL statement with dynamic placeholders
$sql = "UPDATE bg_users SET status='active', modify_dt=NOW() WHERE status='pending' AND user_id IN ($placeholders) LIMIT 6";
$stmt = $database->prepare($sql);

// Execute the statement with the array of minors
$stmt->execute($minorsArray);


       /*      $sql='update bg_users set status="active" where feature_parental_id='.$userid.'';
            $sql='update bg_users set status="active", modify_dt=now() where status="pending" and user_id in (:listofminors) limit 6';
            $stmt = $database->prepare($sql);
            $stmt->execute([':listofminors'=>$listofminors] ); */
}
            $updatefields1=[
              'status'=>"active",
            ];
            $promodata =  $session->get('plan_promodata', '');
            if ($promodata != '') {

              $updatefields2 = ['account_promo' => $promodata['data']['code']];
            }

            $updatefields = array_merge($updatefields1, $updatefields2);

            session_tracking('checkout-parental_handler-UPDATINGACCOUNT:' . $userid, $updatefields);
            $account->updateSettings($userid, $updatefields);

            $account->login($userid, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');
            header('Location: /myaccount');
          }
        } else {
          $updatefields = array();
          session_tracking('checkout-parental_handler-PROBLEM', ['novalues']);
          ## FOR SOME REASON DATA DIDN'T GET CARRIED ACROSS... don't worry... get the info from the session.
       #   $userregistrationdata = $session->get('userregistrationdata');
        #  $userid = (!empty($userregistrationdata['user_id']) ? $userregistrationdata['user_id'] : '');
$current_user_data=$session->get('current_user_data');
        #  $plan_metadata = $session->get('plan_applyplan-metadata', '');
          $userid = $current_user_data['user_id'];
         # $plan = (!empty($plan_metadata['customer_plan']) ? $plan_metadata['customer_plan'] : '');

      #    $promodata =  $session->get('plan_promodata', '');
      #    if (!empty($promodata)) {
       #     $updatefields = ['account_promo' => $promodata['data']['code']];
       #   }

       #   if ($userid !== '' && $plan != '') {
            if (1==1) {
$listofminors=explode(',', json_decode($metadata['parental_listofminors']));
            $sql='update bg_users set status="active" where status="pending" and user_id in (:listofminors) limit 6';
            $stmt = $database->prepare($sql);
            $stmt->execute([':listofminors'=>$listofminors] );

            session_tracking('checkout-parental_handler-UPDATINGACCOUNT2:' . $userid,  $sql.print_r($listofminors, 1));

          #  $account->updateSettings($userid, $updatefields);
            $account->login($userid, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');

            header('Location: /myaccount');
          }


          ## GIVE UP AND GO TO LOGIN
          header('Location: /login');
        }

        break;
#-------------------------------------------------------------------------------
      case 'failed':
        header('location: /checkout-parental?redirect_status=failed');
        break;
    }
  }
}
