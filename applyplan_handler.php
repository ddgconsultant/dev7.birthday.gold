<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


#-------------------------------------------------------------------------------
# HANDLE THE FORM SUBMISSION
#-------------------------------------------------------------------------------
if ($app->formposted('GET')) {

  if (isset($_GET['redirect_status'])) {

      // Collect and prepare transaction data
      $userid = $_GET['user_id'] ?? null;
      $intentId = $_GET['payment_intent'] ?? null; 
      $plan_type = $_GET['plan'] ?? null; // Assuming plan details are passed via GET parameters
      $revenue = ($_GET['redirect_status'] == 'succeeded' || $_GET['redirect_status'] == 'free') ? ($_GET['amount'] ?? 0.00) : 0.00;
      $status = $_GET['redirect_status'];

      // Transaction logging
      $sql = "INSERT INTO bg_transaction (user_id, payment_intent_id, plan_type, revenue, status, create_dt, modify_dt)
              VALUES (:user_id, :payment_intent_id, :plan_type, :revenue, :status, NOW(), NOW())";
      $stmt = $database->prepare($sql);
      $stmt->execute([
          ':user_id' => $userid,
          ':payment_intent_id' => $intentId,
          ':plan_type' => $plan_type,
          ':revenue' => $revenue,
          ':status' => $status
      ]);


            
      $sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, `description`, `status`, `rank`, create_dt, modify_dt)
      VALUES (:user_id, 'converted', 'timeline', NOW(), 'active', 300, NOW(), NOW())";
      $stmt = $database->query($sql, [':user_id' => $userid]);

      
      $sql = "INSERT INTO bg_user_attributes (user_id, `type`, `name`, `description`, `status`, `rank`, create_dt, modify_dt)
      VALUES (:user_id, 'confirmed', 'timeline', NOW(), 'active', 500, NOW(), NOW())";
      $stmt = $database->query($sql, [':user_id' => $userid]);


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
          session_tracking('applyplan_handler-PAID', $metadata);




        }

        if ($_GET['redirect_status'] == 'free') {
          $userregistrationdata = $session->get('userregistrationdata');
          $metadata['customer_user_id'] = $userregistrationdata['user_id'];
          $metadata['customer_plan'] = 'free';
          $metadata['customer_revenue'] = 0;

          session_tracking('applyplan_handler-FREE', $metadata);
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
            $account->updateSettings($userid, $updatefields);


          

          
            $account->login($userid, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');
            header('Location: /myaccount/');
            exit;
          }
        } else {
          $updatefields = array();
          session_tracking('applyplan_handler-PROBLEM', ['novalues']);
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

            $account->updateSettings($userid, $updatefields);
            $account->login($userid, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');

            header('Location: /myaccount/');
            exit;
          }


          ## GIVE UP AND GO TO LOGIN
          header('Location: /login');
        }

        break;
#-------------------------------------------------------------------------------
      case 'failed':
        header('location: /applyplan?redirect_status=failed');
        break;
    }
  }
}
