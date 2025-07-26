<?php
# Load the AI class for potential future integration
$addClasses[]='ai';

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PREP VARIABLES PAGE
#-------------------------------------------------------------------------------
// AIRTOP API Configuration from sitesettings
$airtopApiKey = $sitesettings_ai['airtop']['apikey'] ?? '';
$airtopApiUrl = 'https://api.airtop.ai/api/v1/'; // Fixed API path

// Test variables - Let's check Starbucks birthday rewards
$testUrl = 'https://www.starbucks.com/rewards';
$sessionId = '';
$windowId = '';
$errorMessage = '';
$successMessage = '';
$debugOutput = [];

// Example prompt for extracting birthday reward information
$defaultPrompt = "Look for information about birthday rewards on this page. Extract the following details if available: 1) What birthday reward do members get? 2) What tier/level is required? 3) When does the birthday reward expire? 4) Any special conditions or requirements? Please format the response clearly.";

#-------------------------------------------------------------------------------
# HANDLE PAGE ACTIONS
#-------------------------------------------------------------------------------
if ($app->formposted()) {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'run_example':
            // Run complete example in one click
            $exampleUrl = 'https://www.starbucks.com/rewards';
            $examplePrompt = "Look for information about birthday rewards on this page. Extract the following details if available: 1) What birthday reward do members get? 2) What tier/level is required? 3) When does the birthday reward expire? 4) Any special conditions or requirements? Please format the response clearly.";
            
            // Step 1: Create Session
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $airtopApiKey
            ];
            
            $sessionData = [];
            
            $sessionResponse = $system->curlRequest(
                $airtopApiUrl . 'sessions',
                $headers,
                $sessionData,
                'POST'
            );
            
            $debugOutput[] = [
                'action' => 'Create Session (Example)',
                'url' => $airtopApiUrl . 'sessions',
                'response' => $sessionResponse
            ];
            
            // Check for session limit error
            if (isset($sessionResponse['decoded']['httpStatus']) && $sessionResponse['decoded']['httpStatus'] === 400) {
                $errorMessage = 'Session limit reached. ' . ($sessionResponse['decoded']['message'] ?? 'Please terminate any active sessions and try again.');
                break;
            }
            
            // Check different possible response structures
            if (isset($sessionResponse['decoded']['data']['id'])) {
                $sessionId = $sessionResponse['decoded']['data']['id'];
            } elseif (isset($sessionResponse['decoded']['id'])) {
                $sessionId = $sessionResponse['decoded']['id'];
            } elseif (isset($sessionResponse['data']['id'])) {
                $sessionId = $sessionResponse['data']['id'];
            } else {
                $errorMessage = 'Failed to create session. Check debug output for response structure.';
                break;
            }
            $_SESSION['airtop_session_id'] = $sessionId;
            
            // Wait for session to be ready
            $maxAttempts = 15; // Increased to 30 seconds total
            $sessionReady = false;
            $lastStatus = 'unknown';
            
            for ($i = 0; $i < $maxAttempts; $i++) {
                sleep(2); // Wait 2 seconds between checks
                
                // Check session status
                $statusResponse = $system->curlRequest(
                    $airtopApiUrl . 'sessions/' . $sessionId,
                    $headers,
                    [],
                    'GET'
                );
                
                // Log the status check
                if ($i === 0 || $i === $maxAttempts - 1) { // Log first and last attempt
                    $debugOutput[] = [
                        'action' => 'Check Session Status (Attempt ' . ($i + 1) . ')',
                        'url' => $airtopApiUrl . 'sessions/' . $sessionId,
                        'response' => $statusResponse
                    ];
                }
                
                if (isset($statusResponse['decoded']['data']['status'])) {
                    $lastStatus = $statusResponse['decoded']['data']['status'];
                    // Check for various "ready" states
                    if ($lastStatus === 'active' || $lastStatus === 'ready' || $lastStatus === 'running') {
                        $sessionReady = true;
                        break;
                    }
                }
            }
            
            if (!$sessionReady) {
                // Let's try to proceed anyway - some states might still allow window creation
                $debugOutput[] = [
                    'action' => 'WARNING',
                    'message' => 'Session not active after ' . ($maxAttempts * 2) . ' seconds. Last status: ' . $lastStatus . '. Attempting to create window anyway.',
                    'response' => []
                ];
            }
            
            // Step 2: Create Window
            $windowData = [
                'url' => $exampleUrl
            ];
            
            $windowResponse = $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId . '/windows',
                $headers,
                $windowData,
                'POST'
            );
            
            $debugOutput[] = [
                'action' => 'Create Window (Example)',
                'url' => $airtopApiUrl . 'sessions/' . $sessionId . '/windows',
                'data' => $windowData,
                'response' => $windowResponse
            ];
            
            // Check different possible response structures for window - windowId is the correct field
            if (isset($windowResponse['decoded']['data']['windowId'])) {
                $windowId = $windowResponse['decoded']['data']['windowId'];
            } elseif (isset($windowResponse['decoded']['data']['id'])) {
                $windowId = $windowResponse['decoded']['data']['id'];
            } elseif (isset($windowResponse['decoded']['windowId'])) {
                $windowId = $windowResponse['decoded']['windowId'];
            } elseif (isset($windowResponse['data']['windowId'])) {
                $windowId = $windowResponse['data']['windowId'];
            } else {
                $errorMessage = 'Failed to create window. Check debug output for response structure.';
                break;
            }
            $_SESSION['airtop_window_id'] = $windowId;
            
            // Wait a moment for page to load
            sleep(3);
            
            // Step 3: Query Page
            $queryData = [
                'prompt' => $examplePrompt
            ];
            
            $queryResponse = $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId . '/page-query',
                $headers,
                $queryData,
                'POST'
            );
            
            $debugOutput[] = [
                'action' => 'Page Query (Example)',
                'url' => $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId . '/page-query',
                'data' => $queryData,
                'response' => $queryResponse
            ];
            
            // Check different possible response structures for query
            $resultFound = false;
            if (isset($queryResponse['decoded']['data']['modelResponse'])) {
                $_SESSION['airtop_last_result'] = $queryResponse['decoded']['data']['modelResponse'];
                $resultFound = true;
            } elseif (isset($queryResponse['decoded']['data']['result'])) {
                $_SESSION['airtop_last_result'] = $queryResponse['decoded']['data']['result'];
                $resultFound = true;
            } elseif (isset($queryResponse['decoded']['result'])) {
                $_SESSION['airtop_last_result'] = $queryResponse['decoded']['result'];
                $resultFound = true;
            } elseif (isset($queryResponse['data']['result'])) {
                $_SESSION['airtop_last_result'] = $queryResponse['data']['result'];
                $resultFound = true;
            } elseif (isset($queryResponse['decoded']['content'])) {
                $_SESSION['airtop_last_result'] = $queryResponse['decoded']['content'];
                $resultFound = true;
            } else {
                $errorMessage = 'Failed to query page. Check debug output for response structure.';
            }
            
            // Step 4: Always terminate session to free up resources
            if ($sessionId) {
                $terminateResponse = $system->curlRequest(
                    $airtopApiUrl . 'sessions/' . $sessionId,
                    $headers,
                    [],
                    'DELETE'
                );
                
                $debugOutput[] = [
                    'action' => 'Terminate Session (Cleanup)',
                    'url' => $airtopApiUrl . 'sessions/' . $sessionId,
                    'response' => $terminateResponse
                ];
                
                // Clear session variables
                unset($_SESSION['airtop_session_id']);
                unset($_SESSION['airtop_window_id']);
            }
            
            if ($resultFound) {
                $successMessage = 'Example completed successfully! Session has been terminated.';
            }
            break;
        case 'create_session':
            // Create AIRTOP session
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $airtopApiKey
            ];
            
            $data = [
                // Basic session configuration
            ];
            
            $response = $system->curlRequest(
                $airtopApiUrl . 'sessions',
                $headers,
                $data
            );
            
            $debugOutput[] = [
                'action' => 'Create Session',
                'url' => $airtopApiUrl . 'sessions',
                'response' => $response
            ];
            
            if (isset($response['decoded']['id'])) {
                $sessionId = $response['decoded']['id'];
                $_SESSION['airtop_session_id'] = $sessionId;
                $successMessage = 'Session created successfully! ID: ' . $sessionId;
            } else {
                $errorMessage = 'Failed to create session: ' . ($response['error'] ?? 'Unknown error');
            }
            break;
            
        case 'create_window':
            // Create window in existing session
            $sessionId = $_SESSION['airtop_session_id'] ?? '';
            if (!$sessionId) {
                $errorMessage = 'No active session. Please create a session first.';
                break;
            }
            
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $airtopApiKey
            ];
            
            $data = [
                'url' => $_POST['test_url'] ?? $testUrl
            ];
            
            $response = $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId . '/windows',
                $headers,
                $data
            );
            
            $debugOutput[] = [
                'action' => 'Create Window',
                'url' => $airtopApiUrl . 'sessions/' . $sessionId . '/windows',
                'data' => $data,
                'response' => $response
            ];
            
            if (isset($response['decoded']['windowId'])) {
                $windowId = $response['decoded']['windowId'];
                $_SESSION['airtop_window_id'] = $windowId;
                $successMessage = 'Window created successfully! ID: ' . $windowId;
            } else {
                $errorMessage = 'Failed to create window: ' . ($response['error'] ?? 'Unknown error');
            }
            break;
            
        case 'page_query':
            // Query the page content
            $sessionId = $_SESSION['airtop_session_id'] ?? '';
            $windowId = $_SESSION['airtop_window_id'] ?? '';
            
            if (!$sessionId || !$windowId) {
                $errorMessage = 'No active session or window. Please create them first.';
                break;
            }
            
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $airtopApiKey
            ];
            
            $data = [
                'prompt' => $_POST['query_prompt'] ?? 'Summarize this page in one paragraph'
            ];
            
            $response = $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId . '/page-query',
                $headers,
                $data
            );
            
            $debugOutput[] = [
                'action' => 'Page Query',
                'url' => $airtopApiUrl . 'sessions/' . $sessionId . '/windows/' . $windowId . '/page-query',
                'data' => $data,
                'response' => $response
            ];
            
            if (isset($response['decoded']['result'])) {
                $successMessage = 'Page query successful!';
                $_SESSION['airtop_last_result'] = $response['decoded']['result'];
            } else {
                $errorMessage = 'Failed to query page: ' . ($response['error'] ?? 'Unknown error');
            }
            break;
            
        case 'terminate_session':
            // Terminate the session
            $sessionId = $_SESSION['airtop_session_id'] ?? '';
            
            if (!$sessionId) {
                $errorMessage = 'No active session to terminate.';
                break;
            }
            
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $airtopApiKey
            ];
            
            $response = $system->curlRequest(
                $airtopApiUrl . 'sessions/' . $sessionId,
                $headers,
                [],
                'DELETE'
            );
            
            $debugOutput[] = [
                'action' => 'Terminate Session',
                'url' => $airtopApiUrl . 'sessions/' . $sessionId,
                'response' => $response
            ];
            
            unset($_SESSION['airtop_session_id']);
            unset($_SESSION['airtop_window_id']);
            $successMessage = 'Session terminated successfully!';
            break;
    }
}

// Get current session info from session storage
$sessionId = $_SESSION['airtop_session_id'] ?? '';
$windowId = $_SESSION['airtop_window_id'] ?? '';

#-------------------------------------------------------------------------------
# DISPLAY PAGE
#-------------------------------------------------------------------------------
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-dark no-rounded-corners">
    <div class="container">
        <h1>AIRTOP Birthday Rewards Extractor</h1>
        <p class="lead mb-4">Extract birthday reward information from any website using AI-powered browser automation</p>
    </div>
</div>

<?php
$additionalstyles .= '
<style>
.debug-output {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 1rem;
    margin-top: 1rem;
    font-family: monospace;
    font-size: 0.875rem;
    overflow-x: auto;
}
.status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
    font-weight: bold;
}
.status-active {
    background-color: #d4edda;
    color: #155724;
}
.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}
</style>
';

echo '    
<div class="main-content py-4 py-md-5 bg-light">
<div class="container" style="max-width: 1200px;">';

// Check if API key is configured
if (empty($airtopApiKey)) {
    echo '<div class="alert alert-warning" role="alert">
        <strong>Configuration Required:</strong> AIRTOP API key is not configured. Please add it to the config file under [airtop] apikey="your-key"
    </div>';
}

// Show processing status if in progress
if ($app->formposted() && $_POST['action'] === 'run_example' && empty($errorMessage) && empty($successMessage)) {
    echo '<div class="alert alert-info" role="alert">
        <div class="spinner-border spinner-border-sm me-2" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <strong>Processing...</strong> Creating browser session and extracting birthday rewards information. This may take up to 30 seconds.
    </div>';
    flush(); // Send output to browser immediately
}

// Display messages
if ($errorMessage) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        ' . htmlspecialchars($errorMessage) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

if ($successMessage) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
        ' . htmlspecialchars($successMessage) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

// Launch Example Box
echo '
<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-lightning"></i> Extract Birthday Rewards from Starbucks</h5>
    </div>
    <div class="card-body">
        <p class="lead">Click the button below to automatically:</p>
        <ol>
            <li>Navigate to Starbucks Rewards page</li>
            <li>Extract birthday reward information</li>
            <li>Display the results</li>
        </ol>
        <form method="POST" action="" class="mb-0">
            ' . $display->input_csrftoken() . '
            <button type="submit" name="action" value="run_example" class="btn btn-lg btn-primary w-100" ' . (!empty($airtopApiKey) ? '' : 'disabled') . '>
                <i class="bi bi-rocket-takeoff"></i> Launch AIRTOP Example
            </button>
        </form>
    </div>
</div>';

// Query Result Display
if (isset($_SESSION['airtop_last_result'])) {
    echo '
    <div class="card mt-4 border-success">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-check-circle"></i> Birthday Rewards Information Extracted</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-success mb-3">
                <i class="bi bi-gift"></i> <strong>Successfully extracted birthday reward details from Starbucks!</strong>
            </div>
            <div class="result-content" style="background: #f8f9fa; padding: 1.5rem; border-radius: 0.5rem; border-left: 4px solid #28a745;">
                <h6 class="text-success mb-3"><i class="bi bi-stars"></i> Starbucks Birthday Rewards:</h6>
                <div style="white-space: pre-wrap; line-height: 1.8; font-size: 1.05rem;">' . nl2br(htmlspecialchars($_SESSION['airtop_last_result'])) . '</div>
            </div>
            <div class="mt-3 text-muted">
                <small><i class="bi bi-info-circle"></i> This information was automatically extracted using AIRTOP\'s AI-powered browser automation.</small>
            </div>
        </div>
    </div>';
}

// Debug Output
if (!empty($debugOutput)) {
    echo '
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Debug Output</h5>
        </div>
        <div class="card-body">';
        
        foreach ($debugOutput as $debug) {
            echo '<h6>' . htmlspecialchars($debug['action']) . '</h6>';
            echo '<div class="debug-output">';
            echo '<strong>URL:</strong> ' . htmlspecialchars($debug['url']) . '<br>';
            if (isset($debug['data'])) {
                echo '<strong>Request Data:</strong><br>';
                echo '<pre>' . htmlspecialchars(json_encode($debug['data'], JSON_PRETTY_PRINT)) . '</pre>';
            }
            echo '<strong>Response:</strong><br>';
            echo '<pre>' . htmlspecialchars(json_encode($debug['response'], JSON_PRETTY_PRINT)) . '</pre>';
            echo '</div>';
        }
        
    echo '
        </div>
    </div>';
}

echo '
</div>
</div>';

$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();