<?php
/**
 * Voice Assistant Landing Page
 * Public page explaining the voice assistant feature
 */

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagetitle = 'Voice Assistant for Birthday Gold';
$additionalstyles = '
<style>
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4rem 0;
    margin-bottom: 3rem;
    border-radius: 0 0 2rem 2rem;
}

.feature-card {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    height: 100%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.platform-card {
    text-align: center;
    padding: 2rem;
    border: 2px solid #e9ecef;
    border-radius: 1rem;
    transition: all 0.3s ease;
}

.platform-card:hover {
    border-color: #007bff;
    background: #f8f9fa;
}

.platform-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.cta-section {
    background: #f8f9fa;
    padding: 3rem 0;
    border-radius: 2rem;
    margin: 3rem 0;
}

.command-bubble {
    display: inline-block;
    background: #e9ecef;
    padding: 0.75rem 1.5rem;
    border-radius: 2rem;
    margin: 0.25rem;
    font-family: monospace;
}
</style>';

// Display page
$bodycontentclass='';
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container text-center">
        <h1 class="display-4 mb-3">Voice Assistant for Birthday Gold</h1>
        <p class="lead mb-4">Check your rewards, enrollments, and account status hands-free with Google Assistant, Alexa, and Siri!</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <i class="bi bi-google" style="font-size: 3rem;"></i>
            <i class="bi bi-alexa" style="font-size: 3rem;"></i>
            <i class="bi bi-phone" style="font-size: 3rem;"></i>
        </div>
    </div>
</div>

<div class="container">
    <!-- How It Works -->
    <div class="text-center mb-5">
        <h2 class="mb-4">How It Works</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="bi bi-link-45deg text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">1. Link Your Account</h4>
                    <p>Connect your Birthday Gold account to your preferred voice assistant in just a few taps.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="bi bi-mic-fill text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">2. Ask Questions</h4>
                    <p>Use natural voice commands to check enrollments, rewards, and account information.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="bi bi-lightning-charge-fill text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">3. Get Instant Answers</h4>
                    <p>Receive immediate responses about your Birthday Gold account, hands-free!</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Example Commands -->
    <div class="text-center mb-5">
        <h2 class="mb-4">What You Can Ask</h2>
        <div class="mb-4">
            <span class="command-bubble">"How many enrollments do I have?"</span>
            <span class="command-bubble">"What rewards are active?"</span>
            <span class="command-bubble">"Check my allocations"</span>
            <span class="command-bubble">"What's my account status?"</span>
            <span class="command-bubble">"How many programs am I in?"</span>
        </div>
    </div>
    
    <!-- Platform Selection -->
    <div class="mb-5">
        <h2 class="text-center mb-4">Choose Your Assistant</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="platform-card">
                    <i class="bi bi-google platform-icon" style="color: #4285F4;"></i>
                    <h4>Google Assistant</h4>
                    <p class="text-muted mb-3">Works with Google Home, Android phones, and smart displays</p>
                    <div class="mb-3">
                        <span class="badge bg-success">Available Now</span>
                    </div>
                    <p class="small">Say "Hey Google, talk to Birthday Gold"</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="platform-card">
                    <i class="bi bi-alexa platform-icon" style="color: #00CAFF;"></i>
                    <h4>Amazon Alexa</h4>
                    <p class="text-muted mb-3">Works with Echo devices, Fire TV, and the Alexa app</p>
                    <div class="mb-3">
                        <span class="badge bg-success">Available Now</span>
                    </div>
                    <p class="small">Say "Alexa, open Birthday Gold"</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="platform-card">
                    <i class="bi bi-phone platform-icon" style="color: #000;"></i>
                    <h4>Siri</h4>
                    <p class="text-muted mb-3">Works with iPhone, iPad, Apple Watch, and HomePod</p>
                    <div class="mb-3">
                        <span class="badge bg-warning">Coming Soon</span>
                    </div>
                    <p class="small">iOS app in development</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- CTA Section -->
    <div class="cta-section text-center">
        <h2 class="mb-4">Ready to Get Started?</h2>
        <?php if ($account->isactive()): ?>
            <a href="/myaccount/assistant-instructions.php" class="btn btn-primary btn-lg me-3">
                <i class="bi bi-book me-2"></i>View Setup Guide
            </a>
            <a href="/myaccount/assistant-link.php" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-gear me-2"></i>Manage Devices
            </a>
        <?php else: ?>
            <p class="lead mb-4">Sign in to connect your voice assistant</p>
            <a href="/login?return=/voice" class="btn btn-primary btn-lg me-3">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </a>
            <a href="/register" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-person-plus me-2"></i>Create Account
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Features List -->
    <div class="row mt-5">
        <div class="col-md-6">
            <h3 class="mb-3">Features</h3>
            <ul class="list-unstyled">
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Check enrollment count instantly</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>View all active rewards</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Monitor allocation balance</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Get account status updates</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Works on all your devices</li>
            </ul>
        </div>
        <div class="col-md-6">
            <h3 class="mb-3">Privacy & Security</h3>
            <ul class="list-unstyled">
                <li class="mb-2"><i class="bi bi-shield-check-fill text-primary me-2"></i>Secure OAuth2 authentication</li>
                <li class="mb-2"><i class="bi bi-shield-check-fill text-primary me-2"></i>Your password is never shared</li>
                <li class="mb-2"><i class="bi bi-shield-check-fill text-primary me-2"></i>Revoke access anytime</li>
                <li class="mb-2"><i class="bi bi-shield-check-fill text-primary me-2"></i>No voice recordings stored</li>
                <li class="mb-2"><i class="bi bi-shield-check-fill text-primary me-2"></i>Industry-standard encryption</li>
            </ul>
        </div>
    </div>
</div>

<?php
$display_footertype='';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>