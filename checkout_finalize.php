<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


#-------------------------------------------------------------------------------
# HANDLE THE FORM SUBMISSION
#-------------------------------------------------------------------------------
if ($app->formposted('GET')) {

  if (isset($_GET['redirect_status'])) {

    session_tracking('applyplan_handler-GET', $_GET);

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
          session_tracking('applyplan_handler-INTENT', $intent);
          session_tracking('applyplan_handler-METADATA', $metadata);


          $userregistrationdata = $session->get('userregistrationdata');
          $userid = (!empty($userregistrationdata['user_id']) ? $userregistrationdata['user_id'] : '');




          if (!isset($metadata['customer_plan'])) {
            $metadata['customer_plan'] = $metadata['payment_plan'];
          } else {
            $metadata['customer_plan'] = 'gold-forced';
          }

          session_tracking('applyplan_handler-PAID', $metadata);
        }



                #===============================================================================
        if ($_GET['redirect_status'] == 'free') {
          $userregistrationdata = $session->get('userregistrationdata');
          $metadata['customer_user_id'] = $userregistrationdata['user_id'];
          $metadata['customer_plan'] = 'free';
          $metadata['customer_revenue'] = 0;

          session_tracking('applyplan_handler-FREE', $metadata);
        }





        #===============================================================================
        // Validate metadata structure
        if (
          isset($metadata)  &&
          isset($metadata['customer_user_id']) &&
          # isset($metadata['customer_email']) &&
          isset($metadata['customer_plan'])
        ) {
          session_tracking('applyplan_handler-PREPPING:' . $userid, $metadata);

          # $userregistrationdata = $session->get('userregistrationdata', '');
          #  $userid=(isset($userregistrationdata['user_id']) ?  $userregistrationdata['user_id'] :'');
          #   if ($userid !== '') {
if (!empty($metadata['customer_user_id']))           $userid = $metadata['customer_user_id'];
          $updatefields = $updatefields1 = $updatefields2 = array();
          if ($userid !== '') {
            $updatefields1 = [
              'status' => 'active',
              'account_plan' => $metadata['customer_plan'],
              'account_revenue' => [
                'type' => 'sql_expression',
                'expression' => "`account_revenue` + {$metadata['customer_revenue']}"
              ],

            ];
            $promodata =  $session->get('plan_promodata', '');
            if ($promodata != '') {

              $updatefields2 = ['account_promo' => $promodata['data']['code']];
            }

            $updatefields = array_merge($updatefields1, $updatefields2);

            session_tracking('applyplan_handler-UPDATINGACCOUNT:' . $userid, $updatefields);
            if (!empty($userid)) {
              $account->updateSettings($userid, $updatefields); 
            $account->login($userid, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');
            }
            header('Location: /myaccount');
            exit;
          }
        }
        #--------------------------------
        else {
          $updatefields = array();
          session_tracking('applyplan_handler-PROBLEM', [['novalues'], $metadata]);
          ## FOR SOME REASON DATA DIDN'T GET CARRIED ACROSS... don't worry... get the info from the session.
          $userregistrationdata = $session->get('userregistrationdata');
          $userid = (!empty($userregistrationdata['user_id']) ? $userregistrationdata['user_id'] : '');

          $plan_metadata = $session->get('plan_applyplan-metadata', '');

          $plan = (!empty($plan_metadata['customer_plan']) ? $plan_metadata['customer_plan'] : '');

          $promodata =  $session->get('plan_promodata', '');
          if (!empty($promodata)) {
            $updatefields = ['account_promo' => $promodata['data']['code']];
          }

          if ($userid !== '' && $plan != '') {
            $updatefields = [
              'status' => 'active',
              'account_plan' => $plan,
              'account_revenue' => [
                'type' => 'sql_expression',
                'expression' => "`account_revenue` + {$plan_metadata['customer_revenue']}"
              ],
            ];
            session_tracking('applyplan_handler-UPDATINGACCOUNT2:' . $userid, $updatefields);
            if (!empty($userid)) {
            $account->updateSettings($userid, $updatefields);
            $account->login($userid, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');
            }
            header('Location: /myaccount');
            exit;
          }


          ## GIVE UP AND GO TO myaccount
          session_tracking('applyplan_handler-GIVEUPREDIRECT', $metadata);
          $updatefields1 = [
            'status' => 'active'
          ];

          session_tracking('applyplan_handler-UPDATINGACCOUNT-GIVEUPREDIRECT:' . $userid, $updatefields);
          if (!empty($userid)) {
          $account->updateSettings($userid, $updatefields1);
          $account->login($userid, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');
          }
          # header('Location: /myaccount');

          # $errormessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Something really bad happened while completing your account setup.  Our team has been notified and they will fix it so that you can log in.  Sorry for the inconvenience. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
          $errormessage = '';
          # $transferpagedata['message'] = $errormessage;
          $transferpagedata['url'] = '/myaccount';
          $transferpagedata = $system->endpostpage($transferpagedata);

          # header('Location: /login');
          exit;
        }

        break;
        #-------------------------------------------------------------------------------
      case 'failed':
        header('location: /applyplan?redirect_status=failed');
        break;
    }
  }
}
