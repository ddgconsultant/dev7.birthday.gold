<?php
/**
 * Voice Assistant Setup Instructions
 * User-friendly guide for members to connect their voice assistants
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is active
$activeuser = $account->isactive();
if (empty($activeuser)) {
    header('Location: /login?return=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$pagetitle = 'Voice Assistant Setup Guide';
$additionalstyles = '
<style>
.instruction-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 2rem;
    margin-bottom: 2rem;
}

.platform-header {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f0f0f0;
}

.platform-icon {
    font-size: 3rem;
    margin-right: 1rem;
}

.step-card {
    background: #f8f9fa;
    border-left: 4px solid #007bff;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 0.5rem;
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
    margin-right: 1rem;
    font-weight: bold;
}

.command-example {
    background: #e9ecef;
    padding: 1rem;
    border-radius: 0.5rem;
    font-family: monospace;
    margin: 0.5rem 0;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-ready {
    background: #d4edda;
    color: #155724;
}

.status-coming-soon {
    background: #fff3cd;
    color: #856404;
}

.video-placeholder {
    background: #f0f0f0;
    border: 2px dashed #dee2e6;
    padding: 3rem;
    text-align: center;
    border-radius: 0.5rem;
    color: #6c757d;
}

.faq-item {
    border-bottom: 1px solid #e9ecef;
    padding: 1.5rem 0;
}

.faq-item:last-child {
    border-bottom: none;
}
</style>';

// Display page
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container mt-4">
    <h1 class="mb-4">Voice Assistant Setup Guide</h1>
    
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>New Feature!</strong> You can now use Google Assistant, Amazon Alexa, or Siri to check your Birthday Gold rewards, enrollments, and account status hands-free!
    </div>
    
    <!-- Quick Links -->
    <div class="instruction-card">
        <h3 class="mb-3">Quick Setup Links</h3>
        <div class="row">
            <div class="col-md-4 mb-3">
                <a href="/myaccount/assistant-link.php" class="btn btn-primary w-100">
                    <i class="bi bi-link-45deg me-2"></i>Link Your Device
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="#google" class="btn btn-outline-primary w-100">
                    <i class="bi bi-google me-2"></i>Google Setup
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="#alexa" class="btn btn-outline-primary w-100">
                    <i class="bi bi-alexa me-2"></i>Alexa Setup
                </a>
            </div>
        </div>
    </div>
    
    <!-- Google Assistant Instructions -->
    <div class="instruction-card" id="google">
        <div class="platform-header">
            <i class="bi bi-google platform-icon" style="color: #4285F4;"></i>
            <div>
                <h2 class="mb-0">Google Assistant</h2>
                <span class="status-badge status-ready">Ready to Use</span>
            </div>
        </div>
        
        <h4 class="mb-3">Setup Instructions</h4>
        
        <div class="step-card">
            <div class="d-flex align-items-start">
                <span class="step-number">1</span>
                <div class="flex-grow-1">
                    <h5>Say the Magic Words</h5>
                    <p>On any Google Assistant device (phone, Google Home, etc.), say:</p>
                    <div class="command-example">
                        "Hey Google, talk to Birthday Gold"
                    </div>
                </div>
            </div>
        </div>
        
        <div class="step-card">
            <div class="d-flex align-items-start">
                <span class="step-number">2</span>
                <div class="flex-grow-1">
                    <h5>Link Your Account</h5>
                    <p>Google will ask if you want to link your Birthday Gold account. Say <strong>"Yes"</strong>.</p>
                    <p>You'll see a card in the Google Home app to complete the linking.</p>
                </div>
            </div>
        </div>
        
        <div class="step-card">
            <div class="d-flex align-items-start">
                <span class="step-number">3</span>
                <div class="flex-grow-1">
                    <h5>Complete in Google Home App</h5>
                    <p>Open the <strong>Google Home</strong> app on your phone:</p>
                    <ul>
                        <li>Look for the account linking notification</li>
                        <li>Tap to sign in with your Birthday Gold account</li>
                        <li>Allow access when prompted</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="step-card">
            <div class="d-flex align-items-start">
                <span class="step-number">4</span>
                <div class="flex-grow-1">
                    <h5>Start Using Voice Commands!</h5>
                    <p>Once linked, try these commands:</p>
                    <div class="command-example mb-2">
                        "Hey Google, ask Birthday Gold how many enrollments I have"
                    </div>
                    <div class="command-example mb-2">
                        "Hey Google, ask Birthday Gold what rewards I have"
                    </div>
                    <div class="command-example">
                        "Hey Google, ask Birthday Gold about my allocations"
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-warning mt-3">
            <i class="bi bi-lightbulb-fill me-2"></i>
            <strong>Tip:</strong> If you have trouble linking, you can also use a <a href="/myaccount/assistant-link.php">manual linking code</a>.
        </div>
    </div>
    
    <!-- Alexa Instructions -->
    <div class="instruction-card" id="alexa">
        <div class="platform-header">
            <i class="bi bi-alexa platform-icon" style="color: #00CAFF;"></i>
            <div>
                <h2 class="mb-0">Amazon Alexa</h2>
                <span class="status-badge status-ready">Ready to Use</span>
            </div>
        </div>
        
        <h4 class="mb-3">Setup Instructions</h4>
        
        <div class="step-card">
            <div class="d-flex align-items-start">
                <span class="step-number">1</span>
                <div class="flex-grow-1">
                    <h5>Open the Alexa App</h5>
                    <p>On your phone, open the <strong>Amazon Alexa</strong> app.</p>
                    <p class="text-muted">Don't have it? Download from the App Store or Google Play.</p>
                </div>
            </div>
        </div>
        
        <div class="step-card">
            <div class="d-flex align-items-start">
                <span class="step-number">2</span>
                <div class="flex-grow-1">
                    <h5>Find Birthday Gold Skill</h5>
                    <p>In the Alexa app:</p>
                    <ul>
                        <li>Tap <strong>More</strong> → <strong>Skills & Games</strong></li>
                        <li>Search for <strong>"Birthday Gold"</strong></li>
                        <li>Tap on the Birthday Gold skill</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="step-card">
            <div class="d-flex align-items-start">
                <span class="step-number">3</span>
                <div class="flex-grow-1">
                    <h5>Enable and Link</h5>
                    <ul>
                        <li>Tap <strong>"Enable to Use"</strong></li>
                        <li>Sign in with your Birthday Gold account when prompted</li>
                        <li>Allow access to link your accounts</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="step-card">
            <div class="d-flex align-items-start">
                <span class="step-number">4</span>
                <div class="flex-grow-1">
                    <h5>Start Using Voice Commands!</h5>
                    <p>Once enabled, try these commands:</p>
                    <div class="command-example mb-2">
                        "Alexa, ask Birthday Gold how many enrollments I have"
                    </div>
                    <div class="command-example mb-2">
                        "Alexa, ask Birthday Gold for my rewards"
                    </div>
                    <div class="command-example">
                        "Alexa, ask Birthday Gold about my account"
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Siri Instructions -->
    <div class="instruction-card" id="siri">
        <div class="platform-header">
            <i class="bi bi-phone platform-icon" style="color: #000;"></i>
            <div>
                <h2 class="mb-0">Siri</h2>
                <span class="status-badge status-coming-soon">Coming Soon</span>
            </div>
        </div>
        
        <p>Siri integration requires our iOS app, which is currently in development. Stay tuned!</p>
        
        <div class="video-placeholder mt-3">
            <i class="bi bi-play-circle" style="font-size: 3rem;"></i>
            <p class="mt-2 mb-0">iOS App Preview Coming Soon</p>
        </div>
    </div>
    
    <!-- All Voice Commands -->
    <div class="instruction-card">
        <h3 class="mb-4">All Available Voice Commands</h3>
        
        <div class="row">
            <div class="col-md-6">
                <h5><i class="bi bi-card-list me-2"></i>Check Enrollments</h5>
                <ul class="list-unstyled">
                    <li class="command-example">"How many enrollments do I have?"</li>
                    <li class="command-example">"What's my enrollment count?"</li>
                    <li class="command-example">"How many programs am I in?"</li>
                </ul>
            </div>
            
            <div class="col-md-6">
                <h5><i class="bi bi-gift me-2"></i>View Rewards</h5>
                <ul class="list-unstyled">
                    <li class="command-example">"What rewards do I have?"</li>
                    <li class="command-example">"List my active rewards"</li>
                    <li class="command-example">"What programs am I enrolled in?"</li>
                </ul>
            </div>
            
            <div class="col-md-6 mt-3">
                <h5><i class="bi bi-coin me-2"></i>Check Allocations</h5>
                <ul class="list-unstyled">
                    <li class="command-example">"How many allocations do I have?"</li>
                    <li class="command-example">"What's my allocation balance?"</li>
                    <li class="command-example">"How many spots left?"</li>
                </ul>
            </div>
            
            <div class="col-md-6 mt-3">
                <h5><i class="bi bi-person-circle me-2"></i>Account Status</h5>
                <ul class="list-unstyled">
                    <li class="command-example">"What's my account status?"</li>
                    <li class="command-example">"What plan am I on?"</li>
                    <li class="command-example">"Tell me my account info"</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- FAQ -->
    <div class="instruction-card">
        <h3 class="mb-4">Frequently Asked Questions</h3>
        
        <div class="faq-item">
            <h5>Is my account information secure?</h5>
            <p>Yes! We use industry-standard OAuth2 for secure account linking. Your password is never shared with Google, Amazon, or Apple. You can revoke access anytime from your <a href="/myaccount/assistant-link.php">device management page</a>.</p>
        </div>
        
        <div class="faq-item">
            <h5>Can I use multiple devices?</h5>
            <p>Absolutely! Once you link your account, it works on all your devices signed into the same Google/Amazon account. This includes phones, smart speakers, smart displays, and more.</p>
        </div>
        
        <div class="faq-item">
            <h5>What if I can't find the Birthday Gold skill/action?</h5>
            <p>The feature is being rolled out gradually. If you can't find it yet, you can use our <a href="/myaccount/assistant-link.php">manual linking page</a> to get started.</p>
        </div>
        
        <div class="faq-item">
            <h5>Can I unlink my account later?</h5>
            <p>Yes! You can unlink your account at any time from:</p>
            <ul>
                <li>Birthday Gold: <a href="/myaccount/assistant-link.php">Voice Assistant Settings</a></li>
                <li>Google: Google Home app → Account → Linked accounts</li>
                <li>Alexa: Alexa app → Skills → Your Skills → Birthday Gold → Disable</li>
            </ul>
        </div>
        
        <div class="faq-item">
            <h5>Why isn't it understanding my commands?</h5>
            <p>Make sure to start with "ask Birthday Gold" or "talk to Birthday Gold" so the assistant knows to use our skill. Try speaking clearly and using the exact phrases shown above.</p>
        </div>
    </div>
    
    <!-- Support -->
    <div class="instruction-card">
        <h3 class="mb-3">Need Help?</h3>
        <p>If you're having trouble setting up voice assistants:</p>
        <div class="row">
            <div class="col-md-6">
                <a href="/myaccount/assistant-link.php" class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-gear me-2"></i>Manage Voice Assistants
                </a>
            </div>
            <div class="col-md-6">
                <a href="/contact" class="btn btn-outline-secondary w-100 mb-2">
                    <i class="bi bi-envelope me-2"></i>Contact Support
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>