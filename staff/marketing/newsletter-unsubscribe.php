<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = "Unsubscribe from Newsletters";
$user_id = isset($_GET['u']) ? $qik->decodeId($_GET['u']) : 0;
$success = false;
$error = '';

// Get user information
$user = null;
if ($user_id > 0) {
    $user_sql = "SELECT user_id, email, first_name, last_name 
                FROM bg_users 
                WHERE user_id = :user_id";
    
    $user = $database->getrow($user_sql, ['user_id' => $user_id]);
}

// Handle unsubscribe confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm'])) {
    if ($user) {
        // Check if already unsubscribed
        $check_sql = "SELECT user_id FROM bg_unsubscribes WHERE user_id = :user_id";
        $existing = $database->getrow($check_sql, ['user_id' => $user_id]);
        
        if (!$existing) {
            // Add to unsubscribe table
            $unsub_sql = "INSERT INTO bg_unsubscribes (user_id, unsubscribed_dt) 
                         VALUES (:user_id, NOW())";
            
            $database->query($unsub_sql, ['user_id' => $user_id]);
            
            // Log the unsubscribe event
            $log_sql = "INSERT INTO bg_newsletter_events 
                       (campaign_id, user_id, event_type, event_dt) 
                       VALUES 
                       (0, :user_id, 'unsubscribe', NOW())";
            
            $database->query($log_sql, ['user_id' => $user_id]);
        }
        
        $success = true;
    } else {
        $error = 'Invalid unsubscribe link. Please contact support if you need assistance.';
    }
}

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '
<div class="container main-content mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">';

if ($success) {
    echo '
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    <h2 class="mt-3">Successfully Unsubscribed</h2>
                    <p class="mt-3">You have been unsubscribed from Birthday Gold newsletters.</p>
                    <p>We are sorry to see you go. You will no longer receive promotional emails from us.</p>
                    <p class="mt-4">
                        <a href="/" class="btn btn-primary">Return to Homepage</a>
                    </p>
                </div>
            </div>';
} elseif ($error) {
    echo '
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($error) . '
            </div>
            <div class="text-center">
                <a href="/support" class="btn btn-primary">Contact Support</a>
            </div>';
} elseif ($user) {
    // Check if already unsubscribed
    $check_sql = "SELECT unsubscribed_dt FROM bg_unsubscribes WHERE user_id = :user_id";
    $existing = $database->get_row($check_sql, ['user_id' => $user_id]);
    
    if ($existing) {
        echo '
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-info-circle text-info" style="font-size: 4rem;"></i>
                    <h2 class="mt-3">Already Unsubscribed</h2>
                    <p class="mt-3">You are already unsubscribed from Birthday Gold newsletters.</p>
                    <p>You unsubscribed on ' . date('F j, Y', strtotime($existing['unsubscribed_dt'])) . '</p>
                    <p class="mt-4">
                        <a href="/" class="btn btn-primary">Return to Homepage</a>
                    </p>
                </div>
            </div>';
    } else {
        echo '
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Unsubscribe from Newsletters</h4>
                </div>
                <div class="card-body">
                    <p>You are about to unsubscribe the following email address from Birthday Gold newsletters:</p>
                    
                    <div class="alert alert-light">
                        <strong>Email:</strong> ' . htmlspecialchars($user['email']) . '<br>
                        <strong>Name:</strong> ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '
                    </div>
                    
                    <p>Are you sure you want to unsubscribe? You will no longer receive:</p>
                    <ul>
                        <li>Birthday reward recommendations</li>
                        <li>New business announcements</li>
                        <li>Special offers and promotions</li>
                        <li>Account updates and reminders</li>
                    </ul>
                    
                    <form method="POST">
                        <div class="d-grid gap-2">
                            <button type="submit" name="confirm" value="1" class="btn btn-danger">
                                <i class="fas fa-times-circle"></i> Yes, Unsubscribe Me
                            </button>
                            <a href="/" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> No, Keep Me Subscribed
                            </a>
                        </div>
                    </form>
                </div>
            </div>';
    }
} else {
    echo '
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Invalid or expired unsubscribe link.
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5>Need to unsubscribe?</h5>
                    <p>If you are trying to unsubscribe from Birthday Gold newsletters, please:</p>
                    <ol>
                        <li>Check that you clicked the complete link from your email</li>
                        <li>Try copying and pasting the entire link into your browser</li>
                        <li>Contact our support team if you continue to have issues</li>
                    </ol>
                    <div class="text-center mt-4">
                        <a href="/support" class="btn btn-primary">Contact Support</a>
                    </div>
                </div>
            </div>';
}

echo '
        </div>
    </div>
</div>';

include($dir['core_components'] . '/bg_footer.inc');

$app->outputpage();
?>