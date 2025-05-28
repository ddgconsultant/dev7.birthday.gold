<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


if ($app->formposted('GET')) {

  if (isset($_GET['redirect_status'])) {

    switch ($_GET['redirect_status']) {
      case 'succeeded':
        case 'free':
          case 'gold':
          if ($_GET['redirect_status']=='succeeded') {
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
          }

          if ($_GET['redirect_status']=='free') {
$userregistrationdata=$session->get('userregistrationdata');
            $metadata['customer_user_id']=$userregistrationdata['user_id'];
            $metadata['customer_plan']='free';
            $metadata['customer_revenue']=0;

          }

          if ($_GET['redirect_status']=='gold') {
            $userregistrationdata=$session->get('userregistrationdata');
                        $metadata['customer_user_id']=$userregistrationdata['user_id'];
                        $metadata['customer_plan']='gold';
                        $metadata['customer_revenue']=0;
            
                      }

// Validate metadata structure
if(isset($metadata)  &&
  isset($metadata['customer_user_id']) && 
 # isset($metadata['customer_email']) &&
  isset($metadata['customer_plan'])) {

       # $userregistrationdata = $session->get('userregistrationdata', '');
      #  $userid=(isset($userregistrationdata['user_id']) ?  $userregistrationdata['user_id'] :'');
     #   if ($userid !== '') {


        
          $userid = $metadata['customer_user_id'];
          if ($userid !== '') {
            $updatefields=['status' => 'active',
            'account_plan' => $metadata['customer_plan'],
            'account_revenue' => [
              'type' => 'sql_expression',
              'expression' => "`account_revenue` + {$metadata['customer_revenue']}"
          ],
          
          ];
            $promodata =  $session->get('plan_promodata', '');
            if ($promodata!='') {

              $updatefields=['account_promo'=>$promodata['data']['code']];

            }


            $account->updateSettings($userid, $updatefields);
          
         $account->login($userid, $sitesettings['app']['APP_IMPERSONATEPASS'], 'user_id');
          header('Location: /myaccount');
          }
        } else {
         
          header('Location: /login');
        }
      
        break;

      case 'failed':
        header('location: /applyplan?redirect_status=failed');
        break;
    }
  }
}

