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

// Get existing linked devices
$sql = "SELECT platform, device_id, created_at, last_used 
        FROM bg_assistant_tokens 
        WHERE user_id = :user_id 
        AND expires_at > NOW()
        ORDER BY created_at DESC";
$linkedDevices = $database->get_rows($sql, [':user_id' => $user_id]);

// Page setup
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

.platform-icon {
    width: 48px;
    height: 48px;
    margin-right: 1rem;
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

include $installpath . 'core/components/v3/bg_pagestart.inc';
include $installpath . 'core/components/v3/bg_header.inc';
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
        <h3>Linked Devices</h3>
        <?php foreach ($linkedDevices as $device): ?>
        <div class="linked-device">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong><?php echo ucfirst($device['platform']); ?></strong>
                    <?php if ($device['device_id']): ?>
                        <small class="text-muted">(<?php echo htmlspecialchars($device['device_id']); ?>)</small>
                    <?php endif; ?>
                    <br>
                    <small class="text-muted">
                        Linked: <?php echo date('M j, Y', strtotime($device['created_at'])); ?>
                        <?php if ($device['last_used']): ?>
                            | Last used: <?php echo date('M j, Y', strtotime($device['last_used'])); ?>
                        <?php endif; ?>
                    </small>
                </div>
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="unlink">
                    <input type="hidden" name="platform" value="<?php echo $device['platform']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Unlink</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Setup Instructions -->
    <div class="assistant-card">
        <h3>Link a New Voice Assistant</h3>
        <p class="text-muted">Connect your Google Assistant, Amazon Alexa, or Siri to access your Birthday Gold rewards hands-free.</p>
        
        <!-- Platform Tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#google">
                    <img src="/public/images/google-assistant-icon.png" alt="Google" class="platform-icon">
                    Google Assistant
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#alexa">
                    <img src="/public/images/alexa-icon.png" alt="Alexa" class="platform-icon">
                    Amazon Alexa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#siri">
                    <img src="/public/images/siri-icon.png" alt="Siri" class="platform-icon">
                    Siri
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content mt-4">
            <!-- Google Assistant -->
            <div id="google" class="tab-pane fade show active">
                <h4>Setup Google Assistant</h4>
                <ol class="mt-3">
                    <li class="mb-3">
                        <span class="step-number">1</span>
                        Say <strong>"Hey Google, talk to Birthday Gold"</strong>
                    </li>
                    <li class="mb-3">
                        <span class="step-number">2</span>
                        Google will ask you to link your account. Say <strong>"Yes"</strong>
                    </li>
                    <li class="mb-3">
                        <span class="step-number">3</span>
                        Open the Google Home app on your phone and complete the linking
                    </li>
                </ol>
                <hr>
                <p><strong>Alternative Method:</strong> Enter the code Google gives you:</p>
                <form method="post" class="mt-3">
                    <input type="hidden" name="action" value="verify_code">
                    <input type="hidden" name="platform" value="google">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" name="code" id="linkingCode" class="form-control" 
                                   placeholder="XXXX-XX" maxlength="7" required>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">Link Device</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Amazon Alexa -->
            <div id="alexa" class="tab-pane fade">
                <h4>Setup Amazon Alexa</h4>
                <ol class="mt-3">
                    <li class="mb-3">
                        <span class="step-number">1</span>
                        Open the Alexa app and search for <strong>"Birthday Gold"</strong> skill
                    </li>
                    <li class="mb-3">
                        <span class="step-number">2</span>
                        Enable the skill and tap <strong>"Link Account"</strong>
                    </li>
                    <li class="mb-3">
                        <span class="step-number">3</span>
                        Log in with your Birthday Gold account
                    </li>
                </ol>
                <hr>
                <p><strong>Alternative Method:</strong> Say "Alexa, open Birthday Gold" and enter the code:</p>
                <form method="post" class="mt-3">
                    <input type="hidden" name="action" value="verify_code">
                    <input type="hidden" name="platform" value="alexa">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" name="code" class="form-control" 
                                   placeholder="XXXX-XX" maxlength="7" required>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">Link Device</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Siri -->
            <div id="siri" class="tab-pane fade">
                <h4>Setup Siri Shortcuts</h4>
                <ol class="mt-3">
                    <li class="mb-3">
                        <span class="step-number">1</span>
                        Download the Birthday Gold app from the App Store
                    </li>
                    <li class="mb-3">
                        <span class="step-number">2</span>
                        Open the app and go to Settings > Siri Shortcuts
                    </li>
                    <li class="mb-3">
                        <span class="step-number">3</span>
                        Add the shortcuts you want to use with Siri
                    </li>
                </ol>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i> 
                    Siri integration requires the Birthday Gold iOS app (coming soon)
                </div>
            </div>
        </div>
    </div>

    <!-- What You Can Ask -->
    <div class="assistant-card">
        <h3>What You Can Ask</h3>
        <p>Once linked, try these commands:</p>
        <ul class="list-unstyled">
            <li><i class="fas fa-microphone text-primary"></i> "How many enrollments do I have?"</li>
            <li><i class="fas fa-microphone text-primary"></i> "What rewards am I enrolled in?"</li>
            <li><i class="fas fa-microphone text-primary"></i> "How many allocations do I have left?"</li>
            <li><i class="fas fa-microphone text-primary"></i> "What's my account status?"</li>
        </ul>
    </div>
</div>

<?php
include $installpath . 'core/components/v3/bg_footer.inc';
?>