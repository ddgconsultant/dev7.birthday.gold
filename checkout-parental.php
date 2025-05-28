<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

$continue_tocheckoutform=true;
$continue_tomainprocess=false;

/* 
$userid=147;
$current_user_data=$account->getuserdata($userid, 'user_id', ['pending', 'active', 'validated']);
$session->set('current_user_data', $current_user_data);
 */


$current_user_data=$session->get('current_user_data');
$userregistrationdata=$current_user_data;
$userregistrationdata[':email']=$current_user_data['email'];

$sql='select user_id from bg_users where feature_parent_id="'.$current_user_data['user_id'].'" and `status`="pending" limit 6';

$stmt = $database->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$listofminors=[];
foreach($results as $row) {
  $listofminors[]=$row['user_id'];
}

$numberofminors=count($listofminors);
$continue_tocheckoutform = true;
$session->set('parental_listofminors', $listofminors);
$currency='USD';


#-------------------------------------------------------------------------------
# ENSURE A VALID REGISTRATION EXISTS IN SESSION
#-------------------------------------------------------------------------------
if (is_array($current_user_data)) $continue_tomainprocess=true;

## -- No valid registration information in session
#if ($userregistrationdata=='' && !$continue_tomainprocess) {  
  if (!$continue_tomainprocess) {  
$pagemessage='<div class="alert alert-danger alert-dismissible fade show" role="alert">Unable to locate Sign Up Details</div>';
$transferpage['url']='/signup';
#echo 'information submitted';
$transferpage['message']=$pagemessage;
$system->endpostpage($transferpage);

  $continue_tocheckoutform=false;
  #header('location: /signup');
  exit;   
}

##get user_id while we are testing if needed
if (!isset($userregistrationdata['user_id'])) {
  $userregistrationdata['user_id']=$app->getuserid($userregistrationdata[':email']);
}

#$STRIPECONFIG=$sitesettings['paymentgateway-stripe-live'];

$STRIPE_API_Publishable_key=$STRIPECONFIG['STRIPE_KEY'];
$STRIPE_API_SECRET_KEY=$STRIPECONFIG['STRIPE_SECRET'];


# https://stripe.com/docs/api/customers/create
# https://stripe.com/docs/api/payment_intents/create



require_once('vendor/autoload.php');

$stripe = new \Stripe\StripeClient($STRIPE_API_SECRET_KEY);

/* 
#$goldamount= 1000;
if (isset($_GET['plan'])) {$processselectedplan=$_GET['plan'];}
if (isset($userregistrationdata['plan'])) {$processselectedplan=$userregistrationdata['plan'];}
$promodata=$session->get('plan_promodata');
if (isset($promodata['data']['amount'])) {
  $amount['gold']= $promodata['data']['amount'];
}

$currency='USD';
if (isset($_GET['plan'])) {
    $plan=$processselectedplan;
    switch ($plan) {
        case 'free':
            $location = 'done';
            $planname='free';
            $continue_tocheckoutform = true;
            $amount = 0; // Set the amount for the free plan
            $interval = ''; // Set the interval for the free plan

            break;
        case 'gold':
            $planname='gold';
            $location = 'https://buy.stripe.com/cN26r4eyZ1027QYcMM';
            $continue_tocheckoutform = true;
            $plantagline=$userregistrationdata[':first_name'].", your credit card will be charged $".number_format(($amount[$plan]/100),2)." today.  And will automatically renew each year.  You can cancel at anytime.";
            $amount =  $amount[$plan]; // Set the amount for the gold plan
            $interval = 'year'; // Set the interval for the gold plan
            break;
        case 'life':
            $planname='life';
            $location = 'https://buy.stripe.com/eVa6r42Qh6km7QYbIJ';
            $continue_tocheckoutform = true;
            $plantagline=$userregistrationdata[':first_name'].", your credit card will be charged $".number_format(($amount[$plan]/100),2)." today.  And you'll never have to pay again.";
            $amount = $amount[$plan]; // Set the amount for the life plan
            $interval = ''; // Set the interval for the life plan
            break;
        default:
            $continue_tocheckoutform = false;
    }
$session->set('plan_applyplan', $_GET);
}
 */

 $interval='';
 $plancost=[0,2000,4000,5500,7000,8000,9000];
if ($numberofminors>6) $numberofminors=6;
 $amount=($plancost[$numberofminors]+4000);
 $plantagline=$current_user_data['first_name'].", your credit card will be charged $".number_format(( $amount /100),2)." today.  And you'll never pay this again.";
 $plantagline.='<p>You are being charged for your account and '.$qik->number2word($numberofminors).' minor '.$qik->plural('account', $numberofminors).'.</p>';
          
 #echo $numberofminors; exit;

#-------------------------------------------------------------------------------
# DISPLAY CHECKOUT FORM
#-------------------------------------------------------------------------------
if ($continue_tocheckoutform) {


// Set your secret key. Remember to switch to your live secret key in production.
// See your keys here: https://dashboard.stripe.com/apikeys
$metadata=$metadata_promo=array();
$metadata_init = [
  'customer_user_id' => $userregistrationdata['user_id'], 
  'customer_email' => $userregistrationdata[':email'],
  'customer_plan' => 'parental_plan',
  'customer_planointerval' => $interval,
  'customer_planamount'=>$amount,
  'customer_revenue'=>$amount,

  'parental_listofminors'=>json_encode($listofminors),
  'parental_count'=>count($listofminors),
];
/* 
$promodata =  $session->get('plan_promodata', '');
if ($promodata!='') {
  $amount=$promodata['data']['amount'];
  $interval=$promodata['data']['interval'];

$metadata_promo=['promo_code'=>$promodata['data']['code']];
$metadata_promo['promo_amount']=$amount;
$metadata_promo['promo_interval']=$interval;
$metadata_promo['customer_revenue']=$amount;
$plantagline=$userregistrationdata[':first_name'].", your credit card will be charged $".number_format(($amount*.01),2)." today.  And will automatically renew each year.  You can cancel at anytime.";           
$metadata_promo['promo_tagline']=$plantagline;
}
 */
$metadata = array_merge($metadata_init, $metadata_promo);
 
$session->set('plan_applyplan-metadata', $metadata);
$plan='parental';
$setupIntents=$stripe->paymentIntents->create([
  'amount' => $amount,
  'currency' => 'usd',
  'automatic_payment_methods' => ['enabled' => true],
  'metadata' => $metadata  
]);



  $headerattribute['additionalcss'] = '<link href="/public/css/applyplan.css" rel="stylesheet">
  <script src="https://js.stripe.com/v3/"></script>';
  include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');

echo '
<!-- Start -->
<div class="container-xxl py-6">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg">
                <i class="bi bi-cart display-1 text-primary" title="'.$STRIPECONFIG['STRIPE_MODE'].'"></i>
                <h1 class="display-1">Checkout</h1>
                <h3 class="mb-4">You are checking out the parental plan</h3>
  <p>'.$plantagline.'</p>
  <div class="row">
    <div class="col">
    <form id="payment-form" data-secret="'.$setupIntents->client_secret.'" action="/applyplan" method="post">
       '.$display->inputcsrf_token().'
        <input type="hidden" name="amount" value="' . $amount . '">
        <input type="hidden" name="currency" value="' . $currency . '">
        <input type="hidden" name="interval" value="' . $interval . '">
        <input type="hidden" name="plan" value="' . $plan . '">
       
        <div id="payment-element" class="mb-5">
          <!-- Stripe card element will load here -->
        </div>
        
        <!-- Submit button -->
        <button type="submit" class="btn btn-success btn-block mb-4">
        Pay Now
        </button>

      </form>
      <a class="btn btn-outline-primary border-2 mb-5 plan-button-button" href="/myaccount/setup-parental">Edit List</a>
    </div>
  </div>
</div>
</div>
</div>
</div>
</div>

<div class="row text-center"><div class="text-center"><span>Powered by</span></div><div title="Stripe" class="SVGInline SVGInline--cleaned SVG Logo Icon-color Icon-color--white Box-root Flex-flex" style="margin-top: -5.89px; transform: translateY(3.8px);"><svg class="SVGInline-svg SVGInline--cleaned-svg SVG-svg Logo-svg Icon-color-svg Icon-color--white-svg" height="16" width="38" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 150"><path fill-rule="evenodd" d="M360 77.4c0 2.4-.2 7.6-.2 8.9h-48.9c1.1 11.8 9.7 15.2 19.4 15.2 9.9 0 17.7-2.1 24.5-5.5v20c-6.8 3.8-15.8 6.5-27.7 6.5-24.4 0-41.4-15.2-41.4-45.3 0-25.4 14.4-45.6 38.2-45.6 23.7 0 36.1 20.2 36.1 45.8zm-49.4-9.5h25.8c0-11.3-6.5-16-12.6-16-6.3 0-13.2 4.7-13.2 16zm-63.5-36.3c17.5 0 34 15.8 34.1 44.8 0 31.7-16.3 46.1-34.2 46.1-8.8 0-14.1-3.7-17.7-6.3l-.1 28.3-25 5.3V33.2h22l1.3 6.2c3.5-3.2 9.8-7.8 19.6-7.8zm-6 68.9c9.2 0 15.4-10 15.4-23.4 0-13.1-6.3-23.3-15.4-23.3-5.7 0-9.3 2-11.9 4.9l.1 37.1c2.4 2.6 5.9 4.7 11.8 4.7zm-71.3-74.8V5.3L194.9 0v20.3l-25.1 5.4zm0 7.6h25.1v87.5h-25.1V33.3zm-26.9 7.4c5.9-10.8 17.6-8.6 20.8-7.4v23c-3.1-1.1-13.1-2.5-19 5.2v59.3h-25V33.3h21.6l1.6 7.4zm-50-29.1l-.1 21.7h19v21.3h-19v35.5c0 14.8 15.8 10.2 19 8.9v20.3c-3.3 1.8-9.3 3.3-17.5 3.3-14.8 0-25.9-10.9-25.9-25.7l.1-80.1 24.4-5.2zM25.3 58.7c0 11.2 38.1 5.9 38.2 35.7 0 17.9-14.3 28.2-35.1 28.2-8.6 0-18-1.7-27.3-5.7V93.1c8.4 4.6 19 8 27.3 8 5.6 0 9.6-1.5 9.6-6.1 0-11.9-38-7.5-38-35.1 0-17.7 13.5-28.3 33.8-28.3 8.3 0 16.5 1.3 24.8 4.6v23.5c-7.6-4.1-17.2-6.4-24.8-6.4-5.3 0-8.5 1.5-8.5 5.4z"></path></svg></div></div></div>
';
?>
<script>
// Set your Stripe public key
const stripe = Stripe('<?PHP echo $STRIPE_API_Publishable_key; ?>');


const options = {
  clientSecret: '<?= $setupIntents->client_secret ?>',
  // Fully customizable with appearance API.
  appearance: {
  theme: 'stripe'}
};


// Set up Stripe.js and Elements to use in checkout form, passing the client secret obtained in a previous step
const elements = stripe.elements(options);

// Create and mount the Payment Element
const paymentElement = elements.create('payment');
paymentElement.mount('#payment-element');


const form = document.getElementById('payment-form');

form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const {error} = await stripe.confirmPayment({
    //`Elements` instance that was used to create the Payment Element
    elements,
    confirmParams: {
      return_url: '<?= $website['fullurl']; ?>/checkout-parental_handler?_token=<?= $display->inputcsrf_token('tokenonly'); ?>',
    },
  });

  if (error) {
    // This point will only be reached if there is an immediate error when
    // confirming the payment. Show error to your customer (for example, payment
    // details incomplete)
    const messageContainer = document.querySelector('#error-message');
    messageContainer.textContent = error.message;
  } else {
    // Your customer will be redirected to your `return_url`. For some payment
    // methods like iDEAL, your customer will be redirected to an intermediate
    // site first to authorize the payment, then redirected to the `return_url`.
  }
});
</script>


<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');
#header('location: '.$location);
exit;


} 



#-------------------------------------------------------------------------------
# PLAN NOT DETECTED - fail message
#-------------------------------------------------------------------------------
    include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header.php');
        echo '
<div class="container mt-5">
<h1>Invalid Plan</h1>
<div class="row">
  <div class="col-md-6">
<p>The plan you selected is invalid. Please choose one of the following plans:</p>

<ul>
  <li>free</li>
  <li>gold</li> 
  <li>life</li>
</ul> 

<a class="btn btn-outline-primary border-2 mb-5 plan-button-button" href="/plans">Pick A Different Plan</a>
</div>
</div>

</div>
';

include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer.php');
exit;
