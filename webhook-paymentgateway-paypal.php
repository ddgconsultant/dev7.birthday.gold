<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

require_once('vendor/autoload.php'); // Include the Stripe PHP library


$STRIPE_API_Publishable_key=$STRIPCONFIG['STRIPE_KEY'];
$STRIPE_API_SECRET_KEY=$STRIPCONFIG['STRIPE_SECRET'];
$STRIPE_WEBHOOK_SECRET_KEY=$STRIPCONFIG['STRIPE_WEBHOOK_SECRET'];

\Stripe\Stripe::setApiKey($STRIPE_API_SECRET_KEY);


$input = @file_get_contents('php://input');
session_tracking('webhookinput', $input);
try {
    $event = \Stripe\Webhook::constructEvent(
      $payload, $sig_header, $STRIPE_WEBHOOK_SECRET_KEY
    );
  } catch(\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
  } catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
  }



switch ($event->type) {
    case 'payment_intent.succeeded':
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
        $database->query('INSERT INTO bg_payment_history (payment_id, payment_status, payment_message, event_object, create_dt) VALUES (:payment_id, :payment_status, :payment_message, :event_object, now())');
      #  $database->bind(':payment_id', $paymentIntent->metadata->payment_id);
      $database->bind(':payment_id', 1);
      $database->bind(':payment_status', 'succeeded');
        $database->bind(':payment_message', null);
        $database->bind(':event_object', $input);
        $database->execute();
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
        $database->query('INSERT INTO bg_payment_history (payment_id, payment_status, payment_message, event_object, create_dt) VALUES (:payment_id, :payment_status, :payment_message, :event_object, now())');
      #  $database->bind(':payment_id', $paymentIntent->metadata->payment_id);
      $database->bind(':payment_id', 1);
       $database->bind(':payment_status', 'failed');
        $database->bind(':payment_message', $failureReason);
        $database->bind(':event_object', $input);
        $database->execute();
        break;
    case 'customer.subscription.updated':
        // Subscription updated event
        $subscription = $event->data->object;
        $status = $subscription->status;
        $customerId = $subscription->customer;
        $subscriptionId = $subscription->id;

        // Perform necessary actions for subscription updates
        // e.g., update database, send notification, etc.
        $database->query('UPDATE bg_payments SET subscription_status = :status WHERE subscription_id = :subscription_id');
        $database->bind(':status', $status);
        $database->bind(':subscription_id', $subscriptionId);
        $database->execute();
        break;
    // Add more cases to handle additional event types as needed
    default:
        // Handle other event types or log unrecognized events
        break;
}

http_response_code(200); // Respond with a 200 status code to acknowledge receipt of the webhook
