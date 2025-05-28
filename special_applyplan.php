<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

$continue_tocheckoutform=true;
$continue_tomainprocess=false;


#-------------------------------------------------------------------------------
# ENSURE A VALID REGISTRATION EXISTS IN SESSION
#-------------------------------------------------------------------------------
$userregistrationdata=$session->get('userregistrationdata', '');
if (is_array($userregistrationdata)) $continue_tomainprocess=true;

## -- No valid registration information in session
if ($userregistrationdata=='' && !$continue_tomainprocess) {  
  $pagemessage='<div class="alert alert-success alert-dismissible fade show" role="alert">
 Unable to locate Registration Details
 <button type="button" class="close" data-dismiss="alert" aria-label="Close">
   <span aria-hidden="true">&times;</span>
 </button>
 </div>';
 $transferpage['url']='/signup';
#echo 'information submitted';
$transferpage['message']=$pagemessage;
$app->endpostpage($transferpage);

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



$currency='USD';
if (isset($_GET['plan'])) {
    $plan=$_GET['plan'];
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
            $plantagline=$userregistrationdata[':first_name'].", your credit card will be charged $5.00 today.  And will automatically renew each year.  You can cancel at anytime.";
            $amount = 0; // Set the amount for the gold plan
            $interval = 'year'; // Set the interval for the gold plan
            break;
        case 'life':
            $planname='life';
            $location = 'https://buy.stripe.com/eVa6r42Qh6km7QYbIJ';
            $continue_tocheckoutform = true;
            $plantagline=$userregistrationdata[':first_name'].", your credit card will be charged $30.00 today.  And you'll never have to pay again.";
            $amount = 3000; // Set the amount for the life plan
            $interval = ''; // Set the interval for the life plan
            break;
        default:
            $continue_tocheckoutform = false;
    }
}



#-------------------------------------------------------------------------------
# DISPLAY CHECKOUT FORM
#-------------------------------------------------------------------------------
if ($continue_tocheckoutform) {


// Set your secret key. Remember to switch to your live secret key in production.
// See your keys here: https://dashboard.stripe.com/apikeys


$metadata = [
  'customer_user_id' => $userregistrationdata['user_id'], 
  'customer_email' => $userregistrationdata[':email'],
  'customer_plan' => $planname,
  'customer_planointerval' => $interval,
  'customer_planamount'=>$amount,
  'customer_revenue'=>$amount,
];

$promodata =  $session->get('plan_promodata', '');
if ($promodata!='') {
$metadata=['promo_code'=>$promodata['data']['code']];
$amount=$promodata['data']['amount'];
$interval=$promodata['data']['interval'];
$metadata['promo_amount']=$amount;
$metadata['promo_interval']=$interval;
$metadata['customer_revenue']=$amount;
$plantagline=$userregistrationdata[':first_name'].", your credit card will be charged $".number_format(($amount*.01),2)." today.  And will automatically renew each year.  You can cancel at anytime.";           
}


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
                <h3 class="mb-4">You are signing up for the '.strtoupper($planname).' plan</h3>
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
      <a class="btn btn-outline-primary border-2 mb-5 plan-button-button" href="/plans">Pick A Different Plan</a>
    </div>
  </div>
</div>
</div>
</div>
</div>
</div>

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
      return_url: '<?= $website['fullurl']; ?>/applyplan_handler?_token=<?= $display->inputcsrf_token('tokenonly'); ?>',
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
