<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Testing flag - add ?test=1 to bypass user authentication
// Usage: ?test=1&uid=USER123&product_id=441&name=John
$test_mode = isset($_GET['test']) && $_GET['test'] == '1';

$user_id = null;
$user_data = null;
$product_id = null;

// If in test mode, create mock user data
if ($test_mode) {
    $test_uid = $_GET['uid'] ?? 'TEST_USER';
    $test_product_id = $_GET['product_id'] ?? 441; // Default to user_gold
    $test_first_name = $_GET['name'] ?? 'Richard';
    
    // Look up product details from bg_products
    $sql = "SELECT * FROM bg_products WHERE id = :product_id AND status = 'active'";
    $product_info = $database->getrow($sql, ['product_id' => $test_product_id]);
    $test_plan = $product_info['account_plan'] ?? 'user_gold';
    
    $user_data = [
        'user_id' => $test_uid,
        'first_name' => $test_first_name,
        'last_name' => 'Test',
        'account_type' => 'individual',
        'account_plan' => $test_plan,
        'email' => 'test@example.com'
    ];
    $user_id = $test_uid;
    $product_id = $test_product_id;
} else {
    // Production mode - get data from session
    $celebration_data = $session->get('celebration_data', []);
    
    if (empty($celebration_data)) {
        // No celebration data in session - redirect to account or login
        error_log('[CELEBRATION] No celebration data in session');
        if ($session->get('logged_in')) {
            header('Location: /myaccount/');
        } else {
            header('Location: /login');
        }
        exit();
    }
    
    $user_id = $celebration_data['user_id'] ?? null;
    $product_id = $celebration_data['product_id'] ?? null;
    
    if (!$user_id) {
        error_log('[CELEBRATION] No user_id in celebration data');
        header('Location: /login');
        exit();
    }
}

// Get user data from database (for both test and production mode)
if (!$test_mode && $user_id) {
    $sql = "SELECT * FROM bg_users WHERE user_id = :user_id";
    $user_data = $database->getrow($sql, ['user_id' => $user_id]);
    
    if (!$user_data) {
        error_log('[CELEBRATION] User not found: ' . $user_id);
        header('Location: /login');
        exit();
    }
    
    // Ensure user is logged in
    if (!$session->get('logged_in') || $session->get('user_id') != $user_id) {
        $session->set('user_id', $user_id);
        $session->set('logged_in', true);
        $session->set('account_type', $user_data['account_type']);
    }
    
    // Clear celebration data from session after successful use
    $session->unset('celebration_data');
}

// Load ProductManager to get proper product data and features
if (!class_exists('ProductManager')) {
    include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');
}
$productManager = new ProductManager($database, $qik);

// Get product-specific messaging from bg_product_features using ProductManager
$celebration_messages = [];
$product_data = null;

// First try to get product_id if not already set
if (!$product_id && $user_data && isset($user_data['account_plan'])) {
    // Look up product_id from account_plan for existing users
    $sql = "SELECT id, account_name FROM bg_products WHERE account_plan = :plan AND status = 'active' LIMIT 1";
    $product_info = $database->getrow($sql, ['plan' => $user_data['account_plan']]);
    $product_id = $product_info['id'] ?? null;
}

if ($product_id) {
    // Get product data with features using ProductManager
    $product_data = $productManager->getProduct($product_id);

    if ($product_data && isset($product_data['features'])) {
        foreach ($product_data['features'] as $feature) {
            if (strpos($feature['name'], 'celebration_') === 0) {
                $celebration_messages[$feature['name']] = $feature['value'];
            }
        }
    }
}

// If no product-specific messages found, try 'default' plan as fallback
if (empty($celebration_messages)) {
    $sql = "SELECT name, value FROM bg_product_features
            WHERE plan = 'default'
            AND status = 'active'
            AND name LIKE 'celebration_%'
            ORDER BY name";
    $messages = $database->getrows($sql, []);

    foreach ($messages as $message) {
        $celebration_messages[$message['name']] = $message['value'];
    }
}

// Default messages if none found in database - with correct branding
if (empty($celebration_messages)) {
    $celebration_messages = [
        'celebration_title' => 'Welcome to Birthday.Gold!',
        'celebration_subtitle' => 'Your account is ready{NAME}!',
        'celebration_message' => 'You\'re all set to start receiving amazing birthday rewards from hundreds of businesses. We\'ll automatically enroll you in birthday programs as your special day approaches.',
        'celebration_next_steps_title' => 'Your Next Steps:',
        'celebration_button_text' => 'Go to Your Dashboard'
    ];
}

// Page setup
$pagetitle = $celebration_messages['celebration_title'] ?? 'Welcome to Birthday.Gold!';
$page_title = $celebration_messages['celebration_title'] ?? 'Welcome to Birthday.Gold!';

// Add celebration-specific styles in $additionalstyles
$additionalstyles = '
<style>
/* Celebration content area - fills container naturally */
.celebration-content-wrapper {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #28a745 0%, #20c997 50%, #17a2b8 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}


.celebration-container {
    text-align: center;
    color: white;
    max-width: 700px;
    width: 100%;
    background: rgba(0, 0, 0, 0.7);
    border-radius: 25px;
    padding: 3rem 2rem;
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    text-shadow: 0 2px 8px rgba(0,0,0,0.8);
    animation: slideInUp 1s ease-out;
}

.celebration-icon {
    font-size: 5rem;
    margin-bottom: 1.5rem;
    animation: bounce 2s ease-in-out infinite;
}

.celebration-title {
    font-size: 3rem !important;
    font-weight: 700 !important;
    margin-bottom: 1rem !important;
    animation: fadeInUp 0.8s ease-out 0.3s both;
    color: white !important;
}

.celebration-subtitle {
    font-size: 1.5rem !important;
    margin-bottom: 2rem !important;
    animation: fadeInUp 0.8s ease-out 0.5s both;
    color: white !important;
}

.celebration-message {
    font-size: 1.1rem !important;
    margin-bottom: 2.5rem !important;
    max-width: 600px;
    margin-left: auto !important;
    margin-right: auto !important;
    animation: fadeInUp 0.8s ease-out 0.7s both;
    background: rgba(0, 0, 0, 0.4) !important;
    padding: 1.5rem !important;
    border-radius: 15px !important;
    backdrop-filter: blur(5px);
    color: white !important;
    text-shadow: 0 2px 4px rgba(0,0,0,0.8) !important;
}

.next-steps {
    margin-bottom: 2rem !important;
    animation: fadeInUp 0.8s ease-out 0.8s both;
    background: rgba(0, 0, 0, 0.4) !important;
    padding: 2rem !important;
    border-radius: 15px !important;
    backdrop-filter: blur(5px);
}

.next-steps h3 {
    margin-bottom: 1rem !important;
    font-size: 1.3rem !important;
    color: white !important;
    text-shadow: 0 2px 4px rgba(0,0,0,0.8) !important;
}

.next-steps-list {
    list-style: none !important;
    padding: 0 !important;
    margin: 0 !important;
    text-align: left !important;
}

.next-steps-list li {
    padding: 0.5rem 0 !important;
    padding-left: 2rem !important;
    position: relative !important;
    color: white !important;
    text-shadow: 0 2px 4px rgba(0,0,0,0.8) !important;
}

.next-steps-list li:before {
    content: "✓";
    position: absolute !important;
    left: 0 !important;
    font-weight: bold !important;
    font-size: 1.2rem !important;
    color: #ffd93d !important;
}

.celebration-button {
    background: #28a745 !important;
    color: white !important;
    border: none !important;
    padding: 1rem 3rem !important;
    font-size: 1.2rem !important;
    font-weight: 600 !important;
    border-radius: 50px !important;
    text-decoration: none !important;
    display: inline-block !important;
    transition: all 0.3s ease !important;
    animation: fadeInUp 0.8s ease-out 0.9s both;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
}

.celebration-button:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 12px 35px rgba(0,0,0,0.3) !important;
    color: white !important;
    background: #20c997 !important;
    text-decoration: none !important;
}

/* Prevent button bounce by adding padding around hover area */
.celebration-button {
    position: relative !important;
}

.celebration-button::before {
    content: \'\';
    position: absolute !important;
    top: -5px !important;
    left: -5px !important;
    right: -5px !important;
    bottom: -5px !important;
    pointer-events: none !important;
}

/* Confetti - Maximum specificity to override site CSS */
body .confetti,
.confetti.confetti.confetti {
    position: fixed !important;
    width: 8px !important;
    height: 8px !important;
    z-index: 9999 !important;
    animation-name: confetti-fall !important;
    animation-duration: 3s !important;
    animation-timing-function: linear !important;
    animation-iteration-count: infinite !important;
    animation-fill-mode: none !important;
    animation-play-state: running !important;
    animation-delay: 0s !important;
    pointer-events: none !important;
}

@keyframes slideInUp {
    from { opacity: 0; transform: translateY(100px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-20px); }
    60% { transform: translateY(-10px); }
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes confetti-fall {
    from {
        top: -10px;
        transform: rotate(0deg);
    }
    to {
        top: 100vh;
        transform: rotate(360deg);
    }
}

@media (max-width: 768px) {
    .celebration-title { font-size: 2rem !important; }
    .celebration-subtitle { font-size: 1.2rem !important; }
    .celebration-icon { font-size: 4rem !important; }
    .celebration-container { padding: 2rem 1rem !important; margin: 1rem !important; }
}
</style>
';

// Define variables needed by header
$is_iframe_mode = false;

// Add celebration-page class to body
$bodyclass = 'class="celebration-page"';

// Include header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>
<div class="main-content">
<div class="celebration-content-wrapper">
    <div class="celebration-container">
        <div class="celebration-icon">
            <i class="bi bi-check-circle-fill text-warning"></i>
        </div>
        
        <h1 class="celebration-title"><?php echo htmlspecialchars($celebration_messages['celebration_title'] ?? 'Welcome to Birthday Gold!'); ?></h1>
        
        <?php
        // Determine if this is a paid account
        $is_paid_account = true;
        if ($user_data) {
            // Check if user has a free plan
            $is_paid_account = !in_array($user_data['account_plan'] ?? '', ['free', 'basic', '']);
        }
        
        // Process subtitle with name replacement
        $subtitle = $celebration_messages['celebration_subtitle'] ?? 'Your payment was successful{NAME}!';
        $name_part = $user_data ? ', ' . htmlspecialchars($user_data['first_name']) : '';
        $subtitle = str_replace('{NAME}', $name_part, $subtitle);
        ?>
        
        <p class="celebration-subtitle">
            <?php echo $subtitle; ?>
        </p>
        
        <div class="celebration-message">
            <p><?php echo htmlspecialchars($celebration_messages['celebration_message'] ?? 'You\'re all set to start receiving amazing birthday rewards from hundreds of businesses. We\'ll automatically enroll you in birthday programs as your special day approaches.'); ?></p>
        </div>
        
        <div class="next-steps">
            <h3><?php echo htmlspecialchars($celebration_messages['celebration_next_steps_title'] ?? 'Your Next Steps:'); ?></h3>
            <ul class="next-steps-list">
                <?php 
                // Check for plan-specific next steps
                $next_steps_found = false;
                for ($i = 1; $i <= 10; $i++) {
                    $step_key = "celebration_next_step_{$i}";
                    if (isset($celebration_messages[$step_key]) && !empty($celebration_messages[$step_key])) {
                        echo '<li>' . htmlspecialchars($celebration_messages[$step_key]) . '</li>';
                        $next_steps_found = true;
                    }
                }
                
                // Fallback to account type-based steps if no plan-specific steps found
                if (!$next_steps_found) {
                    if ($user_data && $user_data['account_type'] === 'parental'): ?>
                        <li>Add your children's profiles to start earning their rewards</li>
                        <li>Upload verification documents for each child</li>
                        <li>Select birthday rewards for the whole family</li>
                        <li>Check your email for important account information</li>
                    <?php else: ?>
                        <li>Complete your profile with a photo and preferences</li>
                        <li>Browse and select your favorite birthday reward programs</li>
                        <li>Verify your account to unlock all features</li>
                        <li>Check your email for tips and special offers</li>
                    <?php endif;
                }
                ?>
            </ul>
        </div>
        
        <?php
        // Determine redirect URL
        $redirect_url = '/myaccount/';
        if ($user_data && $user_data['account_type'] === 'parental') {
            $redirect_url = '/myaccount/parental-mode.php';
        }
        ?>
        
        <a href="<?php echo $redirect_url; ?>" class="celebration-button">
            <?php echo htmlspecialchars($celebration_messages['celebration_button_text'] ?? 'Go to Your Dashboard'); ?> <i class="bi bi-arrow-right-circle ms-2"></i>
        </a>
    </div>
</div>
</div>
<script>
// Create confetti animation
function createConfetti() {
    const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24', '#f0932b', '#eb4d4b', '#6ab04c', '#ffffff', '#ffd93d'];
    
    // Create initial batch
    for (let i = 0; i < 150; i++) {
        createSingleConfetti(colors, i * 30);
    }
    
    // Continue creating confetti
    setInterval(() => {
        for (let i = 0; i < 30; i++) {
            createSingleConfetti(colors, i * 50);
        }
    }, 2000);
}

function createSingleConfetti(colors, delay) {
    setTimeout(() => {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + '%';
        
        // Set background color with !important to override site CSS
        const color = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.setProperty('background-color', color, 'important');
        confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
        
        // Force animation properties with maximum specificity
        const duration = (Math.random() * 3 + 2) + 's';
        confetti.style.setProperty('animation-duration', duration, 'important');
        confetti.style.setProperty('animation-name', 'confetti-fall', 'important');
        confetti.style.setProperty('animation-timing-function', 'linear', 'important');
        confetti.style.setProperty('animation-iteration-count', 'infinite', 'important');
        confetti.style.setProperty('animation-fill-mode', 'none', 'important');
        confetti.style.setProperty('animation-play-state', 'running', 'important');
        confetti.style.setProperty('animation-delay', '0s', 'important');
        confetti.style.opacity = Math.random() * 0.8 + 0.2;
        
        document.body.appendChild(confetti);
        
        // Remove confetti after animation
        setTimeout(() => {
            if (confetti.parentNode) {
                confetti.parentNode.removeChild(confetti);
            }
        }, parseFloat(duration) * 1000 + 1000);
    }, delay);
}

// Auto-forward timer with smart pausing
let autoForwardTimer;
let autoForwardCountdown = 10; // 10 seconds
let isPaused = false;
let lastMouseMove = 0;

function startAutoForward() {
    const dashboardButton = document.querySelector('.celebration-button');
    if (!dashboardButton) return;
    
    autoForwardTimer = setInterval(() => {
        if (isPaused) return;
        
        autoForwardCountdown--;
        
        if (autoForwardCountdown <= 0) {
            clearInterval(autoForwardTimer);
            window.location.href = dashboardButton.href;
        } else {
            // Update button text with countdown
            const buttonText = dashboardButton.innerHTML;
            if (!buttonText.includes('(')) {
                dashboardButton.innerHTML = buttonText + ' <span style="opacity: 0.7;">(' + autoForwardCountdown + 's)</span>';
            } else {
                dashboardButton.innerHTML = buttonText.replace(/\(\d+s\)/, '(' + autoForwardCountdown + 's)');
            }
        }
    }, 1000);
}

function pauseAutoForward() {
    isPaused = true;
    const dashboardButton = document.querySelector('.celebration-button');
    if (dashboardButton) {
        // Remove countdown from button text
        dashboardButton.innerHTML = dashboardButton.innerHTML.replace(/ <span[^>]*>\(\d+s\)<\/span>/, '');
    }
}

function resumeAutoForward() {
    isPaused = false;
    autoForwardCountdown = Math.max(5, autoForwardCountdown); // Reset to at least 5 seconds
}

// Start confetti and auto-forward when page loads
document.addEventListener('DOMContentLoaded', function() {
    createConfetti();
    
    // Start auto-forward after initial animations complete
    setTimeout(() => {
        startAutoForward();
    }, 3000);
    
    // Pause/resume based on button hover
    const dashboardButton = document.querySelector('.celebration-button');
    if (dashboardButton) {
        dashboardButton.addEventListener('mouseenter', pauseAutoForward);
        dashboardButton.addEventListener('mouseleave', resumeAutoForward);
    }
});

// Pause on mouse movement, resume after inactivity
document.addEventListener('mousemove', function(e) {
    lastMouseMove = Date.now();
    
    // Pause auto-forward on mouse movement
    if (!isPaused) {
        pauseAutoForward();
        
        // Resume after 3 seconds of no movement
        setTimeout(() => {
            if (Date.now() - lastMouseMove >= 3000) {
                resumeAutoForward();
            }
        }, 3000);
    }
    
    // Add sparkle effect
    if (Math.random() > 0.95) {
        const sparkle = document.createElement('div');
        sparkle.className = 'confetti';
        sparkle.style.left = e.clientX + 'px';
        sparkle.style.top = e.clientY + 'px';
        sparkle.style.position = 'fixed';
        sparkle.style.setProperty('background-color', '#ffd93d', 'important');
        sparkle.style.setProperty('animation-duration', '1s', 'important');
        sparkle.style.opacity = '0.8';
        sparkle.style.transform = 'scale(0.5)';
        
        document.body.appendChild(sparkle);
        
        setTimeout(() => {
            if (sparkle.parentNode) {
                sparkle.parentNode.removeChild(sparkle);
            }
        }, 1000);
    }
});

// Pause on any click or key press
document.addEventListener('click', pauseAutoForward);
document.addEventListener('keydown', pauseAutoForward);

// Clean up timer on page unload
window.addEventListener('beforeunload', function() {
    if (autoForwardTimer) {
        clearInterval(autoForwardTimer);
    }
});
</script>

<?php
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>