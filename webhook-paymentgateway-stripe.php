<?php 


include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
require_once('vendor/autoload.php'); // Include the Stripe PHP library
function write($text){
    $mode='sessiontracking';

    if ($mode=='sessiontracking') {
        session_tracking('webhookinput', $text);
    } else {
        $filename = "example.txt";
        $file = fopen($filename, "w");
        if ($file === false) {
            die("Unable to open file for writing.");
        }
        if (fwrite($file, $text) === false) {
            die("Unable to write to the file.");
        }
        fclose($file);
    }
}

$STRIPE_API_Publishable_key = $STRIPECONFIG['STRIPE_KEY'];
$STRIPE_API_SECRET_KEY = $STRIPECONFIG['STRIPE_SECRET'];
#$STRIPE_WEBHOOK_SECRET_KEY = 'whsec_Xae0yiKjadAkf8ZI5hMiAR6UCpktPgSp'; //$STRIPECONFIG['STRIPE_WEBHOOK_SECRET'];
$STRIPE_WEBHOOK_SECRET_KEY = $STRIPECONFIG['STRIPE_WEBHOOK_SECRET']; 
//$STRIPE_API_Publishable_key="pk_test_51NVhEjLsevs4ai2QX5D7QnmGQCXaskCAQA11fi1YUUXQc8fTdbws3pbet2d8bhVenX3EyoyKcE8lHLyE51Vtzi6B00FoKCnSjn";
//$STRIPE_API_SECRET_KEY="sk_test_51NVhEjLsevs4ai2QkoDQqxVxBoASl20LPDjEvUVii1sxbQ0UsbfqUpnA8NvMjgiVMmgBi9tlQFURNjfcq55CAviq00vpBoNklP";
//$STRIPE_WEBHOOK_SECRET_KEY="whsec_7xiBrbbTCOH40tXYy4JLmoRYjzPLcmRc";
write('4');

\Stripe\Stripe::setApiKey($STRIPE_API_SECRET_KEY);

write('5');
#-------------------------------------------------------------------------------
# https://stripe.com/docs/webhooks/quickstart
#
# RECEIVE EVENT FROM STRIPE
$bypass = false;
#-------------------------------------------------------------------------------
if (!$bypass) {
    $input = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE']??'';
    $event = null;
    session_tracking('webhookinput', $input);
    try {
        $event = \Stripe\Webhook::constructEvent(
            $input, $sig_header, $STRIPE_WEBHOOK_SECRET_KEY
        );

        write(json_encode($event));
        
    } catch (\UnexpectedValueException $e) {
        // Invalid payload
        http_response_code(400);
        exit();
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
        http_response_code(401);
        exit();
    }
    $event_type = $event->type;
    # http_response_code(200); exit;
}


#-------------------------------------------------------------------------------
# OBTAIN EVENT FROM SESSION TABLE - testing
#-------------------------------------------------------------------------------
if ($bypass) {
    $sessiontrackingid = 4431;

    $params = array(
        ':id' => $sessiontrackingid
    );
    $sql = 'select tracking_data as validation_id from bg_sessiontracking where id=:id';

    $stmt = $database->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $input = $results[0]['validation_id'];
    $event = json_decode($input);
}


#-------------------------------------------------------------------------------
# HANDLE THE EVENT
#-------------------------------------------------------------------------------
switch ($event->type) {
    #  case 'payment_intent.succeeded':
  case ($event->type === 'payment_intent.succeeded'):
    case (strpos($event->type, '.succeeded') !== false):
write(json_encode($event));
        session_tracking('payment_intent.succeeded', $input);
        // Payment succeeded event
        $paymentIntent = $event->data->object;
        $amount = $paymentIntent->amount / 100; // Convert amount from cents to dollars
        $currency = $paymentIntent->currency;
        $customer = $paymentIntent->customer;
        $paymentMethod = $paymentIntent->payment_method;

        // Perform necessary actions for successful payment
        // e.g., update database, send confirmation email, etc.
        // Example of updating the database:
        #  $database->query('UPDATE bg_payments SET payment_status = "succeeded" WHERE payment_intent_id = :payment_intent_id');
        #  $database->bind(':payment_intent_id', $paymentIntent->id);
        #  $database->execute();

        // Insert payment details into payment history table
        $stmt = $database->prepare('INSERT INTO bg_payment_history ( event_id, event_type, payment_status, payment_message, event_object, create_dt) VALUES (:event_id, :event_type, :payment_status, :payment_message, :event_object, now())');
        $params = array(
            ':event_id' => $event->id,
            ':event_type' => $event->type,
            ':payment_status' => 'succeeded',
            ':payment_message' => null,
            ':event_object' => $input
        );
        $stmt->execute($params);
        break;
    case 'charge.succeeded':
        session_tracking('charge.succeeded', $input);
        // Payment succeeded event
        $paymentIntent = $event->data->object;
        $amount = $paymentIntent->amount / 100; // Convert amount from cents to dollars
        $currency = $paymentIntent->currency;
        $customer = $paymentIntent->customer;
        $paymentMethod = $paymentIntent->payment_method;

        // Perform necessary actions for successful payment
        // e.g., update database, send confirmation email, etc.
        // Example of updating the database:
        #  $database->query('UPDATE bg_payments SET payment_status = "succeeded" WHERE payment_intent_id = :payment_intent_id');
        #  $database->bind(':payment_intent_id', $paymentIntent->id);
        #  $database->execute();

        // Insert payment details into payment history table
        $stmt = $database->prepare('INSERT INTO bg_payment_history ( payment_status, payment_message, event_object, create_dt) VALUES (:payment_status, :payment_message, :event_object, now())');
        $params = array(
            ':payment_status' => 'succeeded',
            ':payment_message' => null,
            ':event_object' => $input
        );
        $stmt->execute($params);
        break;
    case 'payment_intent.payment_failed':
        // Payment failed event
        $paymentIntent = $event->data->object;
        $failureReason = $paymentIntent->last_payment_error ? $paymentIntent->last_payment_error->message : '';

        // Perform necessary actions for failed payment
        // e.g., send notification email, update user's status, etc.
        // Example of updating the database:
        #   $database->query('UPDATE bg_payments SET payment_status = "failed", payment_message = :failure_reason WHERE payment_intent_id = :payment_intent_id');
        #   $database->bind(':failure_reason', $failureReason);
        #   $database->bind(':payment_intent_id', $paymentIntent->id);
        #   $database->execute();

        // Insert payment details into payment history table
        $stmt = $database->prepare('INSERT INTO bg_payment_history (payment_status, payment_message, event_object, create_dt) VALUES (:payment_status, :payment_message, :event_object, now())');
        $params = array(
            ':payment_status' => 'succeeded',
            ':payment_message' => null,
            ':event_object' => $input
        );
        $stmt->execute($params);
        break;
    case 'customer.subscription.updated':
        // Subscription updated event
        $subscription = $event->data->object;
        $status = $subscription->status;
        $customerId = $subscription->customer;
        $subscriptionId = $subscription->id;

        // Perform necessary actions for subscription updates
        // e.g., update database, send notification, etc.
        $stmt = $database->prepare('UPDATE bg_payments SET subscription_status = :status WHERE subscription_id = :subscription_id');
        $database->bind(':status', $status);
        $database->bind(':subscription_id', $subscriptionId);
        $params = array(
            ':status' => 'succeeded',
            ':subscription_id' => $subscriptionId
        );
        $stmt->execute($params);
        break;
    // Add more cases to handle additional event types as needed
    default:
        // Handle other event types or log unrecognized events
          $stmt = $database->prepare('INSERT INTO bg_payment_history (payment_status, payment_message, event_object, create_dt) VALUES (:payment_status, :payment_message, :event_object, now())');
           $params = array(
               ':payment_status' => $event->type,
               ':payment_message' => null,
               ':event_object' => $event->data
           );
           $stmt->execute($params);
         #  session_tracking('payment-default inserted', "");
        break;
}
session_tracking($event->type, "end");
http_response_code(200); // Respond with a 200 status code to acknowledge receipt of the webhook