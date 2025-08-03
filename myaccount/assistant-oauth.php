<?php
/**
 * OAuth2 Authorization Endpoint for Voice Assistant Account Linking
 * Handles the OAuth2 flow for Google Assistant and Alexa
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.assistant.php');

// OAuth2 parameters
$client_id = $_GET['client_id'] ?? '';
$redirect_uri = $_GET['redirect_uri'] ?? '';
$state = $_GET['state'] ?? '';
$response_type = $_GET['response_type'] ?? '';
$scope = $_GET['scope'] ?? '';

// Validate required parameters
if (empty($client_id) || empty($redirect_uri) || empty($state) || $response_type !== 'code') {
    die('Invalid OAuth2 request parameters');
}

// Determine platform from client_id
$platform = '';
if (strpos($client_id, 'google') !== false) {
    $platform = 'google';
} elseif (strpos($client_id, 'alexa') !== false || strpos($client_id, 'amazon') !== false) {
    $platform = 'alexa';
}

// Check if user is logged in
$activeuser = $account->isactive();
if (empty($activeuser)) {
    // Store OAuth parameters in session for after login
    $_SESSION['assistant_oauth'] = [
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'state' => $state,
        'scope' => $scope,
        'platform' => $platform
    ];
    
    // Redirect to login with return URL
    $return_url = urlencode('/myaccount/assistant-oauth.php?' . http_build_query($_GET));
    header('Location: /login?return=' . $return_url);
    exit;
}

// User is logged in
$current_user_data = $session->get('current_user_data');
$user_id = $current_user_data['user_id'];

// Initialize Assistant
$assistant = new Assistant($database, $app, $account, $session);

// Generate authorization code using validation system
$oauth_data = json_encode([
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'platform' => $platform
]);

$input = [
    'rawdata' => $oauth_data,
    'type' => 'oauth_code',
    'expireminutes' => 10,
    'user_id' => $user_id,
    'status' => 'pending'
];

$validation = $app->getvalidationcodes($input);
$auth_code = $validation['longcode'];

// The OAuth details are already stored in bg_validations by getvalidationcodes()
// No need for additional insert

// Log the authorization
$app->session_tracking('assistant_oauth_authorized', [
    'user_id' => $user_id,
    'platform' => $platform,
    'client_id' => $client_id
]);

// Show consent screen
if (!isset($_GET['consent'])) {
    $pagetitle = 'Link ' . ucfirst($platform) . ' Assistant';
    include $dir['core_components'] . '/bg_pagestart.inc';
    include $dir['core_components'] . '/bg_header.inc';
    ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Link <?php echo ucfirst($platform); ?> Assistant</h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if ($platform === 'google'): ?>
                                <i class="bi bi-google" style="font-size: 4rem; color: #4285F4;"></i>
                            <?php elseif ($platform === 'alexa'): ?>
                                <i class="bi bi-alexa" style="font-size: 4rem; color: #00CAFF;"></i>
                            <?php else: ?>
                                <i class="bi bi-mic-fill" style="font-size: 4rem; color: #007bff;"></i>
                            <?php endif; ?>
                        </div>
                        
                        <p><?php echo ucfirst($platform); ?> Assistant would like to:</p>
                        <ul>
                            <li>Access your enrollment count</li>
                            <li>View your active rewards</li>
                            <li>Check your allocation balance</li>
                            <li>Get your account status</li>
                        </ul>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill"></i> 
                            Your Birthday Gold password will not be shared with <?php echo ucfirst($platform); ?>.
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="?<?php echo http_build_query($_GET); ?>&consent=1" 
                               class="btn btn-primary">Allow Access</a>
                            <a href="<?php echo htmlspecialchars($redirect_uri); ?>?error=access_denied&state=<?php echo urlencode($state); ?>" 
                               class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    $display_footertype='';
    include($dir['core_components'] . '/bg_footer.inc');
    $app->outputpage();
    exit;
}

// User consented, redirect back with authorization code
$redirect_params = [
    'code' => $auth_code,
    'state' => $state
];

$redirect_url = $redirect_uri . '?' . http_build_query($redirect_params);
header('Location: ' . $redirect_url);
exit;
?>