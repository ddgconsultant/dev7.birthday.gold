<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set the response header to JSON
header('Content-Type: application/json');



// Log the incoming request for debugging
session_tracking("Received request data",  print_r($_REQUEST, true));


// Initialize response array
$response = [
    'status' => false,
    'resultmessage' => 'Invalid request' // Default message in case something goes wrong
];


#-------------------------------------------------------------------------------
# HANDLE THE PROMO CODE ATTEMPT
#-------------------------------------------------------------------------------
if (($app->formposted() && isset($_REQUEST['promocode']))  ) {
  session_tracking("Processing promo code: " , $_REQUEST['promocode']);

    $promocode = $_REQUEST['promocode'] ?? '';
    $promodata = $app->getpromocode($promocode);

    if (!$promodata['status']) {
        $response['status'] = false;
        $response['resultmessage'] = $promodata['resultmessage'];
    } else {
        $response['status'] = true;
        $response['resultmessage'] = $promodata['resultmessage'];

        // Optionally, you can include more data in the response
        $promodata['plan'] = 'gold';
        $session->set('plan_promodata', $promodata);
    }
    
    // Send the JSON response back to the client
   # echo json_encode($response);
   # return;
}

#-------------------------------------------------------------------------------
# HANDLE THE REFERRER VALIDATION ATTEMPT
#-------------------------------------------------------------------------------
if (($app->formposted() && isset($_REQUEST['referrer']))) {
  session_tracking("Processing referrer: " , $_REQUEST['referrer']);
    $referrer = $_REQUEST['referrer'] ?? '';
    $referrerdata = $app->getreferrer($referrer);

    if (!$referrerdata['status']) {
        $response['status'] = false;
        $response['resultmessage'] = $referrerdata['resultmessage'];
    } else {
        $response['status'] = true;
        $response['resultmessage'] = $referrerdata['resultmessage'];

        // Optionally, you can include more data in the response
        $session->set('referrer_data', $referrerdata);
    }
    
    // Send the JSON response back to the client
  #  echo json_encode($response);
  #  return;
}

#-------------------------------------------------------------------------------
# DEFAULT RESPONSE IF NO VALID INPUT
#-------------------------------------------------------------------------------
session_tracking("FINAL RESPONSE: " , $response);
echo json_encode($response);
return;


/*
#-------------------------------------------------------------------------------
# HANDLE THE PROMO CODE ATTEMPT
#-------------------------------------------------------------------------------
if ($app->formposted()) {

  $animatetag = ' ';
  $promocode = (isset($_REQUEST['promocode']) ? $_REQUEST['promocode'] : '');
  $promodata = $app->getpromocode($promocode);
  if (!$promodata['status']) {
    $invalidvalidpromo = true;
    $promofailedmessage = '<p class="text-danger">' . $promodata['resultmessage'] . ' <small>[' . $promocode . ']</small></p>';
  } else {
    $gotvalidpromo = true;
    $promodata['plan'] = 'gold';
    $session->set('plan_promodata', $promodata);
    $promosuccessmessage = '<p class="border-1 border-black text-success mt-0 pt-0">' . $promodata['resultmessage'] . '<br><small><a href="/plans"  class="text-danger">Remove</a></small></p>';
  }
}
  */