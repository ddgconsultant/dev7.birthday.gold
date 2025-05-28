<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#$test_U = 1201;



#-------------------------------------------------------------------------------
// Track the start of the process
session_tracking('Process started. Checking return URL...');
$session->set('force_error_message', 'No registration data found. Please sign up again.');
$returnurl = $_SERVER['REFERRER_URL'] ?? '/';


#-------------------------------------------------------------------------------
// Check if the referrer URL is empty or contains "checkout"
if (empty($returnurl) || strpos($returnurl, 'checkout') !== false) {
    $returnurl = '/';
    session_tracking('Return URL is either empty or contains "checkout". Setting return URL to "/".');
} else {
    session_tracking('Return URL set to: ' . $returnurl);
}



#-------------------------------------------------------------------------------
// Determine process ID and checkout type
if (isset($test_U)) {
    $processid = $test_U;
    $checkouttype = 'user';
    session_tracking('Test ID provided. Process ID set to ' . $processid . ' and checkout type set to "user".');
} else {
    $processid = $qik->decodeId($_REQUEST['u'] ?? $_REQUEST['t'] ?? '');
    session_tracking('No test ID provided. Process ID decoded to ' . $processid);

    if (isset($_REQUEST['u'])) {
        $checkouttype = 'user';
        session_tracking('Checkout type determined as "user" based on $_REQUEST["u"].');
    } elseif (isset($_REQUEST['t'])) {
        $checkouttype = 'transaction';
        session_tracking('Checkout type determined as "transaction" based on $_REQUEST["t"].');
    } else {
        $checkouttype = null;
        session_tracking('No valid $_REQUEST["u"] or $_REQUEST["t"] found. Checkout type set to null.');
    }
}

// If no valid checkout type is provided, handle the error
if ($checkouttype === null || $processid === null) {
    session_tracking('Invalid checkout type or process ID. Ending process.');
    $pagemessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Invalid checkout type or process ID.</div>';
    $transferpage['url'] = $returnurl;
    $transferpage['message'] = $pagemessage;
    $system->endpostpage($transferpage);
    exit;
}

session_tracking('Process ID: ' . $processid . ', Checkout Type: ' . $checkouttype . '. Proceeding to switch case.');




#-------------------------------------------------------------------------------
function convertamount($amount)
{
    // Ensure the amount is treated as a float
    $amount = (float)$amount;

    // If the amount is less than 100, it's likely in dollars, so convert it to cents
    if ($amount < 100 && fmod($amount, 1) == 0.00) {
        return (int)($amount * 100);
    }

    // If the amount is greater than or equal to 100 and has no decimals, assume it's already in cents
    if ($amount >= 100 && fmod($amount, 1) == 0.00) {
        return (int)$amount;
    }

    // If the amount has decimals (like 10.50), convert to cents
    return (int)($amount * 100);
}





#-------------------------------------------------------------------------------
// Initialize variables
$continue_tocheckoutform = true;
$presetamount = '';
$amount = 0;
$planname = '';
$plantagline = '';
$message1 = '';
$paymentdata = [];
$userregistrationdata = [];
$metadata = [];
$interval = '';
$displayline = true;
$terms = false;




#-------------------------------------------------------------------------------
// Determine checkout type
switch ($checkouttype) {
        #------------------------------------
        #------------------------------------
    case 'user':
        // Process User Account
        session_tracking('Processing user account with ID: ' . $processid);
        $userregistrationdata = $account->getuserdata($processid, 'user_id', '*');


        if (!$userregistrationdata) {
            session_tracking('Unable to locate Sign Up Details: ' . $processid);
            $pagemessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Unable to locate Sign Up Details</div>';
            $transferpage['url'] = $returnurl;
            $transferpage['message'] = $pagemessage;
            $system->endpostpage($transferpage);
            exit;
        }

        // Example messaging and payment setup for a user
        $paymentsystemmode = $userregistrationdata['type'];
        #$paymentsystemmode='';
        #$planname = $userregistrationdata['account_plan'];

        $message1 = 'You are signing up for the ' . strtoupper($planname) . ' plan';
        $continue_tocheckoutform = true;



        $transactiondata = $account->getTransactionData($processid, 'user_id');
        if ($transactiondata) {
            $transactiondata = $transactiondata[0];
        } else {
            // Log the error for debugging
            session_tracking('Error: No pending transaction found for user with process ID: ', $processid);
            // Prepare transfer page data
            $transferpage['message'] = 'No pending transaction found for user.'; // Include the error message
            $transferpage['url'] = '/error'; // Redirect to the error page
            // End the request and redirect
            $system->endpostpage($transferpage);
        }




       # $planname = $userregistrationdata['account_plan']??'gold';
        $amount = $transactiondata['amount'] ?? 0;

        session_tracking('user Transaction data: ', $transactiondata);


        $amount = convertamount($amount);


        $plantagline = $userregistrationdata['first_name'] . ", your credit card will only be charged once in the amount of $" . number_format(($amount / 100), 2) . " today.";
        $interval = ''; // Set the interval for the life plan


        // default values if plan details not found
        $continue_tocheckoutform = false;



        $plandetails = $app->plandetail('detailsall_id', $transactiondata['product_id']);
        #  breakpoint($plandetails);
        if ($plandetails) {
            $planname = $plandetails['plan']['value'] ?? '';
            $continue_tocheckoutform = true;
            $location = $plandetails['stripe_paymentendpoint']['value'] ?? '';
            $plantagline .= !empty($plandetails['payment_tagline']['value']) ? '. ' . $plandetails['payment_tagline']['value']  : '';


            if ($userregistrationdata['account_type'] === 'giftcertificate') {
                $message1 = 'You are purchasing a Gift Certificate for a Lifetime plan';
                $plantagline .= " for the gift certificate.";
                $pickplanbutton = false;
            }
        }


        $metadata = [
            'bg_checkouttype' => 'user',
            'customer_user_id' => $userregistrationdata['user_id'],
            'customer_email' => $userregistrationdata['email'],
            'transaction_id' => $transactiondata['transaction_id'],
            'customer_plan' => $planname,
       #      'customer_plan_id' => $planname,
           'customer_planamount' => $amount,
            'customer_revenue' => $amount,
          #  'bg_userdata' => json_encode($userregistrationdata),
            'bg_transactiondata' => json_encode($transactiondata),
          #  'bg_plandetails' => $plandetails
        ];
        $currency = 'USD';
        $paymentintentparams = [
            'amount' => $amount,
            'currency' => $currency,
            'metadata' => $metadata
        ];
        break;

        #------------------------------------
        #------------------------------------
    case 'transaction':
        // Process transaction Record
        session_tracking('Processing transaction with ID: ' . $processid);
        $transactiondata = $account->getTransactionData($processid, 'transaction_id');
        if ($transactiondata) {
            $transactiondata = $transactiondata[0];
        } else {
            // Log the error for debugging
            session_tracking('Error: No pending transaction found for transaction_id with process ID: ', $processid);
            // Prepare transfer page data
            $transferpage['message'] = 'No pending transaction found for transaction_id.'; // Include the error message
            $transferpage['url'] = '/500'; // Redirect to the error page        
            // End the request and redirect
            $system->endpostpage($transferpage);
        }



        if (!$transactiondata) {
            session_tracking('Unable to locate transaction Details: ' . $processid);
            $pagemessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Unable to locate Transaction Details</div>';
            $transferpage['url'] = $returnurl;
            $transferpage['message'] = $pagemessage;
            $system->endpostpage($transferpage);
            exit;
        }

        // Example messaging and transaction setup for an transaction
        $amount = $transactiondata['amount'] ?? 0;
        $amount = convertamount($amount);

        #  breakpoint($amount);
        $message1 = 'You are paying for the ' . strtoupper($planname) . ' plan';
        $continue_tocheckoutform = true;
        $plantagline = "Your credit card will be charged $" . number_format(($amount / 100), 2) . " today.";
        $userregistrationdata = $account->getuserdata($transactiondata['user_id'], 'user_id', '*');
        $planname = $userregistrationdata['account_plan'] ?? '';
        $paymentsystemmode = $userregistrationdata['type'];



        $message1 = 'You are signing up for the ' . strtoupper($planname) . ' plan';
        $continue_tocheckoutform = true;
        $plantagline = $userregistrationdata['first_name'] . ", your credit card will be charged $" . number_format(($amount / 100), 2) . " today.  This is a one-time charge.";
        $interval = ''; // Set the interval for the life plan



}



/// rght now we can't handle multiple transactions at once
//we've made it this far... we have a valid transaction and now let's go get the plan details
$plandetails = $app->plandetail('detailsall_id', $transactiondata['product_id']);
if (!$plandetails) {
    
    $transferpage['url'] = '/500'; // Redirect to the error page        
    session_tracking('Unable to locate Plan Details for product: ' . $transactiondata['product_id']);
    $transferpage['message'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Unable to locate Plan Details</div>';
    $system->endpostpage($transferpage);
    exit;
}





// handle free accounts
if ($transactiondata['product_id']==311) {
    
    $userid = $transactiondata['user_id'];
    $updatefields = $updatefields1 = $updatefields2 = array();
    if ($userid !== '') {
      $updatefields1 = [
        'status' => 'active',
        'account_plan' => 'free',
        

      ];
     
      $updatefields = array_merge($updatefields1, $updatefields2);
      session_tracking('applyplan_handler-UPDATINGFREEACCOUNT:' . $userid, $updatefields);
      if (!empty($userid)) {
        $account->updateSettings($userid, $updatefields); 
      $account->login($userid, $sitesettings['app']['APP_AUTOLOGIN'], 'user_id');
      }
      header('Location: /myaccount/getstarted');
      exit;
    }


}















if (!$continue_tocheckoutform) {
    switch ($checkouttype) {
        case 'user':
            $pagemessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Invalid plan selected for user ID: ' . $processid . '</div>' . print_r(($transactiondata), true);
            break;
        case 'transaction':
            $pagemessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Invalid plan selected for transaction ID: ' . $processid . '</div>';
            break;
        default:
            $pagemessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Invalid plan selected.</div>';
            break;
    }
    session_tracking($pagemessage);
    $transferpage['url'] = '/error';
    $transferpage['message'] = $pagemessage;
    $system->endpostpage($transferpage);
    exit;
}


#--------------------------------------------------------------------------------------------------------------------------------------------------------------
#--------------------------------------------------------------------------------------------------------------------------------------------------------------
#--------------------------------------------------------------------------------------------------------------------------------------------------------------

// If checkout type is valid, proceed to set up payment details for Stripe
if ($continue_tocheckoutform) {
    require_once('vendor/autoload.php');







    #--------------------------------------------------------------------------------------------------------------------------------------------------------------
    #--------------------------------------------------------------------------------------------------------------------------------------------------------------
    if ($paymentsystemmode == 'test') {
        #You're temporarily blocked due to excessive requests. [71.33.250.241]
        $STRIPECONFIG = $sitesettings['paymentgateway-stripe-live'];
        $amount = 50;
        $tooltip = 'This is a test payment. $0.' . $amount . ' will be charged to your card.';
        $TESTMODETAG = '<span class="badge fs-5 bg-danger position-absolute top-0 end-0 m-3 blink" data-bs-toggle="tooltip" data-bs-placement="top" title="' . htmlspecialchars($tooltip) . '">TEST</span>';
    } else {
        $TESTMODETAG = '';
        $tooltip = '';
        $STRIPECONFIG = $sitesettings['paymentgateway-stripe-live'];
    }

    #--------------------------------------------------------------------------------------------------------------------------------------------------------------
    #--------------------------------------------------------------------------------------------------------------------------------------------------------------




    $STRIPE_API_Publishable_key = $STRIPECONFIG['STRIPE_KEY'];
    $STRIPE_API_SECRET_KEY = $STRIPECONFIG['STRIPE_SECRET'];

    $stripe = new \Stripe\StripeClient($STRIPE_API_SECRET_KEY);

    // Set up Stripe Payment Intent




    $currency = 'USD';
    $paymentintentparams = [
        'amount' => $amount,
        'currency' => $currency,
        'metadata' => $metadata
    ];

    // Add automatic payment methods if necessary
    $signupmode = $session->get('signupmode', $_GET['signupmode'] ?? '');
    if ($signupmode !== '') {
        $paymentintentparams['payment_method_types'] = ['card', 'cashapp'];
    } else {
        $paymentintentparams['automatic_payment_methods'] = ['enabled' => true];
    }








    #-------------------------------------------------------------------------------
    #-------------------------------------------------------------------------------
    # DISPLAY PAGE
    #-------------------------------------------------------------------------------
    include($dir['core_components'] . '/bg_pagestart.inc');
    include($dir['core_components'] . '/bg_header.inc');

    $additionalstyles .= '<style>
@keyframes blink {
0% { opacity: 1; }
50% { opacity: 0; }
100% { opacity: 1; }
}
.blink {
animation: blink 1s infinite;
}
</style>
<link href="/public/css/applyplan.css" rel="stylesheet">
<script src="https://js.stripe.com/v3/"></script>
';


    session_tracking('Readying the payment form for Stripe...', $paymentintentparams);
    try {
        $setupIntents = $stripe->paymentIntents->create($paymentintentparams);
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        session_tracking('Stripe error: ' . $e->getMessage());
        $pagemessage = '<div class="alert alert-danger">There was an error processing your payment. Please try again or contact support.</div>';
        $transferpage['url'] = '/checkout';
        $transferpage['message'] = $pagemessage;
        $system->endpostpage($transferpage);
        exit;
    }


    echo '           
<!-- Start -->
<div class="container-xxl main-content">
<div class="container text-center card bg-success-subtle"> ' . $TESTMODETAG . '
<div class="row justify-content-center my-5">             
<div class="col-lg">
<h1>Checkout <i class="bi bi-cart text-primary ms-4"></i></h1>
<h3 class="">' . $message1 . '</h3>
<p>' . $plantagline . '</p>
<div class="row">
<div class="col">
<form id="payment-form" data-secret="' . $setupIntents->client_secret . '" action="/applyplan" method="post">
' . $display->inputcsrf_token() . '
<input type="hidden" name="amount" value="' . $amount . '">
<input type="hidden" name="currency" value="' . $currency . '">
<input type="hidden" name="interval" value="' . $interval . '">
<input type="hidden" name="plan" value="' . $planname . '">
<div id="payment-element" class="mb-5">
<!-- Stripe card element will load here -->
</div>
<button type="submit" class="btn btn-success btn-block mb-4">Pay Now</button>
</form>
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
                theme: 'stripe'
            }
        };


        // Set up Stripe.js and Elements to use in checkout form, passing the client secret obtained in a previous step
        const elements = stripe.elements(options);

        // Create and mount the Payment Element
        const paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');


        const form = document.getElementById('payment-form');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const {
                error
            } = await stripe.confirmPayment({
                //`Elements` instance that was used to create the Payment Element
                elements,
                confirmParams: {
                    return_url: '<?= $website['fullurl']; ?>/checkout_finalize?_token=<?= $display->inputcsrf_token('tokenonly'); ?>',
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
echo $display->addmousetracking();
    include($dir['core_components'] . '/bg_footer.inc');
    $app->outputpage();



    session_tracking('Process completed-1.');
    exit;


    #-------------------------------------------------------------------------------
} else {
    // Invalid Plan or Checkout Type
    include($dir['core_components'] . '/bg_pagestart.inc');
    include($dir['core_components'] . '/bg_header.inc');
    echo '
<div class="container mt-5 main-content">
<h1>Invalid Plan or Checkout Type</h1>
<p>The plan or checkout type you selected is invalid. Please try again.</p>
<a class="btn btn-outline-primary border-2 mb-5 plan-button-button" href="/plans">Pick A Different Plan</a>
</div>
';


echo $display->addmousetracking();
    include($dir['core_components'] . '/bg_footer.inc');
    $app->outputpage();
    session_tracking('Process completed-2.');
    exit;
}
?>