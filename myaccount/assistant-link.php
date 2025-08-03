<?php
/**
 * Voice Assistant Account Linking
 * Allows users to link their voice assistants (Google, Alexa, Siri) to their Birthday Gold account
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.assistant.php');

// Check if user is active
$activeuser = $account->isactive();
if (empty($activeuser)) {
    header('Location: /login?return=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Get user data
$current_user_data = $session->get('current_user_data');
$user_id = $current_user_data['user_id'];

// Initialize Assistant class
$assistant = new Assistant($database, $app, $account, $session);

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'verify_code') {
        $code = $_POST['code'] ?? '';
        $platform = $_POST['platform'] ?? '';
        
        if (!empty($code) && !empty($platform)) {
            $result = $assistant->verifyLinkingCode($code, $user_id, $platform);
            
            if ($result['success']) {
                $message = 'Your ' . ucfirst($platform) . ' device has been successfully linked!';
                $messageType = 'success';
                
                // Log the successful linking
                $app->session_tracking('assistant_linked', [
                    'user_id' => $user_id,
                    'platform' => $platform
                ]);
            } else {
                $message = 'Invalid or expired code. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

// Check for direct linking from OAuth flow
if (isset($_GET['platform']) && isset($_GET['code'])) {
    $platform = $_GET['platform'];
    $code = $_GET['code'];
    
    $result = $assistant->verifyLinkingCode($code, $user_id, $platform);
    
    if ($result['success']) {
        $message = 'Your ' . ucfirst($platform) . ' account has been successfully linked!';
        $messageType = 'success';
    }
}

// Get existing linked devices from bg_validations
$sql = "SELECT 
        SUBSTRING_INDEX(validation_rawdata, '|', 1) as platform,
        device_id,
        create_dt as created_at,
        validation_dt as last_used
        FROM bg_validations 
        WHERE user_id = :user_id 
        AND validation_type = 'voice_assistant_link'
        AND status = 'linked'
        AND expire_dt > NOW()
        ORDER BY create_dt DESC";
$linkedDevices = $database->getrows($sql, [':user_id' => $user_id]);

// Page setup - MUST be before includes
$pagetitle = 'Voice Assistant Setup';
$additionalstyles = '
<style>
.assistant-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 2rem;
    margin-bottom: 2rem;
}

.code-display {
    font-size: 2rem;
    font-weight: bold;
    letter-spacing: 0.5rem;
    color: #2c3e50;
    background: #f8f9fa;
    padding: 1rem 2rem;
    border-radius: 0.5rem;
    display: inline-block;
    margin: 1rem 0;
}

.linked-device {
    background: #e8f5e9;
    border-left: 4px solid #4caf50;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 0.25rem;
}

.step-number {
    background: #007bff;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.5rem;
}
</style>';

$additionalscripts = '
<script>
// Auto-format code input
document.addEventListener("DOMContentLoaded", function() {
    const codeInput = document.getElementById("linkingCode");
    if (codeInput) {
        codeInput.addEventListener("input", function(e) {
            let value = e.target.value.replace(/[^0-9]/g, "");
            if (value.length > 4) {
                value = value.slice(0, 4) + "-" + value.slice(4, 6);
            }
            e.target.value = value;
        });
    }
});

// Generate new code
function generateNewCode(platform) {
    fetch("/myaccount/ajax/assistant-generate-code.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: "platform=" + platform
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById("codeDisplay").textContent = data.code;
            document.getElementById("codeExpiry").textContent = "Code expires in " + data.expires_in + " minutes";
        }
    });
}
</script>';

// Display page
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container mt-4">
    <h1 class="mb-4">Voice Assistant Setup</h1>
    
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Linked Devices -->
    <?php if (!empty($linkedDevices)): ?>
    <div class="assistant-card">
        <h3 class="mb-3">Linked Devices</h3>
        <?php foreach ($linkedDevices as $device): ?>
        <div class="linked-device">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong><?php echo ucfirst($device['platform']); ?> Assistant</strong>
                    <br>
                    <small class="text-muted">Linked on <?php echo date('M j, Y', strtotime($device['created_at'])); ?></small>
                    <?php if ($device['last_used']): ?>
                    <br>
                    <small class="text-muted">Last used: <?php echo date('M j, Y g:i A', strtotime($device['last_used'])); ?></small>
                    <?php endif; ?>
                </div>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="unlink">
                    <input type="hidden" name="device_id" value="<?php echo htmlspecialchars($device['device_id']); ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Unlink</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Link New Device -->
    <div class="assistant-card">
        <h3 class="mb-4">Link a New Voice Assistant</h3>
        <p class="text-muted mb-4">Connect your Google Assistant, Amazon Alexa, or Siri to access your Birthday Gold rewards hands-free.</p>
        
        <!-- Platform Selection -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <button class="btn btn-outline-primary w-100 p-3" onclick="showInstructions('google')">
                    <i class="bi bi-google fs-1 mb-2 d-block"></i>
                    Google Assistant
                </button>
            </div>
            <div class="col-md-4 mb-3">
                <button class="btn btn-outline-primary w-100 p-3" onclick="showInstructions('alexa')">
                    <i class="bi bi-alexa fs-1 mb-2 d-block"></i>
                    Amazon Alexa
                </button>
            </div>
            <div class="col-md-4 mb-3">
                <button class="btn btn-outline-primary w-100 p-3" onclick="showInstructions('siri')">
                    <i class="bi bi-phone fs-1 mb-2 d-block"></i>
                    Siri
                </button>
            </div>
        </div>
        
        <!-- Instructions -->
        <div id="googleInstructions" class="platform-instructions" style="display:none;">
            <h4>Setup Google Assistant</h4>
            <ol class="mt-3">
                <li class="mb-2">
                    <span class="step-number">1</span>
                    Say "Hey Google, talk to Birthday Gold"
                </li>
                <li class="mb-2">
                    <span class="step-number">2</span>
                    Google will ask you to link your account. Say "Yes"
                </li>
                <li class="mb-2">
                    <span class="step-number">3</span>
                    Open the Google Home app on your phone and complete the linking
                </li>
            </ol>
            
            <div class="mt-4 p-3 bg-light rounded">
                <h5>Or use a linking code:</h5>
                <button class="btn btn-primary mt-2" onclick="generateNewCode('google')">Generate Code</button>
                <div id="codeDisplay" class="code-display" style="display:none;"></div>
                <div id="codeExpiry" class="text-muted mt-2"></div>
            </div>
        </div>
        
        <div id="alexaInstructions" class="platform-instructions" style="display:none;">
            <h4>Setup Amazon Alexa</h4>
            <ol class="mt-3">
                <li class="mb-2">
                    <span class="step-number">1</span>
                    Open the Alexa app on your phone
                </li>
                <li class="mb-2">
                    <span class="step-number">2</span>
                    Go to Skills & Games and search for "Birthday Gold"
                </li>
                <li class="mb-2">
                    <span class="step-number">3</span>
                    Enable the skill and link your account
                </li>
            </ol>
            
            <div class="mt-4 p-3 bg-light rounded">
                <h5>Or use a linking code:</h5>
                <button class="btn btn-primary mt-2" onclick="generateNewCode('alexa')">Generate Code</button>
            </div>
        </div>
        
        <div id="siriInstructions" class="platform-instructions" style="display:none;">
            <h4>Setup Siri</h4>
            <ol class="mt-3">
                <li class="mb-2">
                    <span class="step-number">1</span>
                    Download the Birthday Gold app from the App Store
                </li>
                <li class="mb-2">
                    <span class="step-number">2</span>
                    Open the app and go to Settings > Siri Integration
                </li>
                <li class="mb-2">
                    <span class="step-number">3</span>
                    Enable "Use with Siri" and link your account
                </li>
            </ol>
            
            <div class="mt-4 p-3 bg-light rounded">
                <h5>Or use a linking code:</h5>
                <button class="btn btn-primary mt-2" onclick="generateNewCode('siri')">Generate Code</button>
                <p class="mt-2 text-muted small">Enter this code in the iOS app's Siri settings</p>
            </div>
        </div>
        
        <!-- Manual Code Entry -->
        <div class="mt-4 p-3 bg-light rounded">
            <h5>Have a linking code?</h5>
            <form method="POST" class="mt-3">
                <input type="hidden" name="action" value="verify_code">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="platform" class="form-label">Platform</label>
                        <select name="platform" id="platform" class="form-select" required>
                            <option value="">Select...</option>
                            <option value="google">Google Assistant</option>
                            <option value="alexa">Amazon Alexa</option>
                            <option value="siri">Siri</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="linkingCode" class="form-label">Linking Code</label>
                        <input type="text" class="form-control" id="linkingCode" name="code" 
                               placeholder="XXXX-XX" maxlength="7" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Link Device</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Usage Examples -->
    <div class="assistant-card">
        <h3 class="mb-3">What You Can Ask</h3>
        <p class="text-muted">Once linked, try these voice commands:</p>
        <ul class="list-unstyled mt-3">
            <li class="mb-2"><i class="bi bi-mic-fill text-primary me-2"></i> "Hey Google, ask Birthday Gold how many enrollments I have"</li>
            <li class="mb-2"><i class="bi bi-mic-fill text-primary me-2"></i> "Alexa, ask Birthday Gold what rewards I'm enrolled in"</li>
            <li class="mb-2"><i class="bi bi-mic-fill text-primary me-2"></i> "How many allocations do I have left?"</li>
            <li class="mb-2"><i class="bi bi-mic-fill text-primary me-2"></i> "What's my account status?"</li>
        </ul>
    </div>
</div>

<script>
function showInstructions(platform) {
    // Hide all instructions
    document.querySelectorAll('.platform-instructions').forEach(el => {
        el.style.display = 'none';
    });
    
    // Show selected platform
    const instructionsEl = document.getElementById(platform + 'Instructions');
    if (instructionsEl) {
        instructionsEl.style.display = 'block';
    }
}
</script>

<?php
$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>