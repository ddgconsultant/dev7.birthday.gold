<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Testing flag - add ?test=1 to bypass user authentication
$test_mode = isset($_GET['test']) && $_GET['test'] == '1';

// Get user ID
$encoded_user_id = $_REQUEST['u'] ?? '';
$user_id = null;
$user_data = null;

// If in test mode, create mock user data
if ($test_mode) {
    $user_data = [
        'user_id' => 'TEST_USER',
        'first_name' => 'Richard',
        'last_name' => 'Test',
        'account_type' => 'individual',
        'account_plan' => 'premium',
        'email' => 'test@example.com'
    ];
    $user_id = 'TEST_USER';
}

// Try to decode user ID if provided (skip if in test mode)
if (!$test_mode && !empty($encoded_user_id)) {
    try {
        $user_id = $qik->decodeId($encoded_user_id);
        
        // Get user data
        $sql = "SELECT * FROM bg_users WHERE user_id = :user_id";
        $user_data = $database->getrow($sql, ['user_id' => $user_id]);
        
        // Ensure user is logged in
        if ($user_data && (!$session->get('logged_in') || $session->get('user_id') != $user_id)) {
            $session->set('user_id', $user_id);
            $session->set('logged_in', true);
            $session->set('account_type', $user_data['account_type']);
        }
    } catch (Exception $e) {
        // Continue anyway - we'll show a generic celebration
        error_log('[CELEBRATION] Failed to decode user ID: ' . $e->getMessage());
    }
}

// If we still don't have user data, try to get it from session (skip if in test mode)
if (!$test_mode && !$user_data && $session->get('logged_in')) {
    $user_id = $session->get('user_id');
    if ($user_id) {
        $sql = "SELECT * FROM bg_users WHERE user_id = :user_id";
        $user_data = $database->getrow($sql, ['user_id' => $user_id]);
    }
}

// Page setup
$pagetitle = 'Welcome to Birthday Gold!';
$page_title = 'Welcome to Birthday Gold!';

// Add celebration-specific styles - only for content area
$additionalstyles = '
<style>
/* Celebration content area only */
.celebration-content-area {
    background: linear-gradient(135deg, #28a745 0%, #20c997 50%, #17a2b8 100%);
    min-height: calc(100vh - 200px);
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    position: relative;
}

.celebration-container {
    text-align: center;
    color: white;
    max-width: 700px;
    width: 100%;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 25px;
    padding: 3rem 2rem;
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    text-shadow: 0 2px 8px rgba(0,0,0,0.3);
    animation: slideInUp 1s ease-out;
    margin: 2rem auto;
}

.celebration-icon {
    font-size: 5rem;
    margin-bottom: 1.5rem;
    animation: bounce 2s ease-in-out infinite;
}

.celebration-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    animation: fadeInUp 0.8s ease-out 0.3s both;
    color: white !important;
}

.celebration-subtitle {
    font-size: 1.5rem;
    margin-bottom: 2rem;
    animation: fadeInUp 0.8s ease-out 0.5s both;
    color: white !important;
}

.celebration-message {
    font-size: 1.1rem;
    margin-bottom: 2.5rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
    animation: fadeInUp 0.8s ease-out 0.7s both;
    background: rgba(255, 255, 255, 0.1);
    padding: 1.5rem;
    border-radius: 15px;
    backdrop-filter: blur(5px);
    color: white !important;
}

.next-steps {
    margin-bottom: 2rem;
    animation: fadeInUp 0.8s ease-out 0.8s both;
    background: rgba(255, 255, 255, 0.1);
    padding: 2rem;
    border-radius: 15px;
    backdrop-filter: blur(5px);
}

.next-steps h3 {
    margin-bottom: 1rem;
    font-size: 1.3rem;
    color: white !important;
}

.next-steps-list {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;
}

.next-steps-list li {
    padding: 0.5rem 0;
    padding-left: 2rem;
    position: relative;
    color: white !important;
}

.next-steps-list li:before {
    content: "✓";
    position: absolute;
    left: 0;
    font-weight: bold;
    font-size: 1.2rem;
    color: #ffd93d;
}

.celebration-button {
    background: white !important;
    color: #28a745 !important;
    border: none !important;
    padding: 1rem 3rem !important;
    font-size: 1.2rem !important;
    font-weight: 600 !important;
    border-radius: 50px !important;
    text-decoration: none !important;
    display: inline-block !important;
    transition: all 0.3s ease !important;
    animation: fadeInUp 0.8s ease-out 0.9s both;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
}

.celebration-button:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 15px 40px rgba(0,0,0,0.3) !important;
    color: #20c997 !important;
    background: #f8f9fa !important;
    text-decoration: none !important;
}

/* Confetti */
.confetti {
    position: fixed;
    width: 8px;
    height: 8px;
    z-index: 9999;
    animation: confetti-fall linear infinite;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(100px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-20px);
    }
    60% {
        transform: translateY(-10px);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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

/* Responsive */
@media (max-width: 768px) {
    .celebration-title {
        font-size: 2rem !important;
    }
    .celebration-subtitle {
        font-size: 1.2rem !important;
    }
    .celebration-icon {
        font-size: 4rem !important;
    }
    .celebration-container {
        padding: 2rem 1rem !important;
        margin: 1rem !important;
    }
}
</style>
';

// Set body class for celebration styling
$bodyclass = 'class="celebration-page"';

// Define variables needed by header
$is_iframe_mode = false;

// Include header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="celebration-content-area">
    <div class="celebration-container">
        <div class="celebration-icon">
    background: rgba(255, 255, 255, 0.15);
    border-radius: 25px;
    padding: 3rem 2rem;
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    text-shadow: 0 2px 8px rgba(0,0,0,0.3);
    animation: slideInUp 1s ease-out;
    margin: 2rem auto;
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
    background: rgba(255, 255, 255, 0.1) !important;
    padding: 1.5rem !important;
    border-radius: 15px !important;
    backdrop-filter: blur(5px);
    color: white !important;
}

.next-steps {
    margin-bottom: 2rem !important;
    animation: fadeInUp 0.8s ease-out 0.8s both;
    background: rgba(255, 255, 255, 0.1) !important;
    padding: 2rem !important;
    border-radius: 15px !important;
    backdrop-filter: blur(5px);
}

.next-steps h3 {
    margin-bottom: 1rem !important;
    font-size: 1.3rem !important;
    color: white !important;
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
    background: white !important;
    color: #28a745 !important;
    border: none !important;
    padding: 1rem 3rem !important;
    font-size: 1.2rem !important;
    font-weight: 600 !important;
    border-radius: 50px !important;
    text-decoration: none !important;
    display: inline-block !important;
    transition: all 0.3s ease !important;
    animation: fadeInUp 0.8s ease-out 0.9s both;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
}

.celebration-button:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 15px 40px rgba(0,0,0,0.3) !important;
    color: #20c997 !important;
    background: #f8f9fa !important;
    text-decoration: none !important;
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

<div class="celebration-container">
    <div class="celebration-icon">
        <i class="bi bi-check-circle-fill text-warning"></i>
    </div>
    
    <h1 class="celebration-title">Welcome to Birthday Gold!</h1>
    
    <?php
    // Determine if this is a paid account
    $is_paid_account = true;
    if ($user_data) {
        // Check if user has a free plan
        $is_paid_account = !in_array($user_data['account_plan'] ?? '', ['free', 'basic', '']);
    }
    ?>
    
    <p class="celebration-subtitle">
        <?php if ($is_paid_account): ?>
            Your payment was successful<?php echo $user_data ? ', ' . htmlspecialchars($user_data['first_name']) : ''; ?>!
        <?php else: ?>
            Welcome<?php echo $user_data ? ', ' . htmlspecialchars($user_data['first_name']) : ''; ?>!
        <?php endif; ?>
    </p>
    
    <div class="celebration-message">
        <p>You're all set to start receiving amazing birthday rewards from hundreds of businesses. We'll automatically enroll you in birthday programs as your special day approaches.</p>
    </div>
    
    <div class="next-steps">
        <h3>Your Next Steps:</h3>
        <ul class="next-steps-list">
            <?php if ($user_data && $user_data['account_type'] === 'parental'): ?>
                <li>Add your children's profiles to start earning their rewards</li>
                <li>Upload verification documents for each child</li>
                <li>Select birthday rewards for the whole family</li>
                <li>Check your email for important account information</li>
            <?php else: ?>
                <li>Complete your profile with a photo and preferences</li>
                <li>Browse and select your favorite birthday reward programs</li>
                <li>Verify your account to unlock all features</li>
                <li>Check your email for tips and special offers</li>
            <?php endif; ?>
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
        Go to Your Dashboard <i class="bi bi-arrow-right-circle ms-2"></i>
    </a>
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

// Start confetti when page loads
document.addEventListener('DOMContentLoaded', function() {
    createConfetti();
});

// Add some sparkle effect on mouse move
document.addEventListener('mousemove', function(e) {
    if (Math.random() > 0.95) {
        const sparkle = document.createElement('div');
        sparkle.className = 'confetti';
        sparkle.style.left = e.clientX + 'px';
        sparkle.style.top = e.clientY + 'px';
        sparkle.style.position = 'fixed';
        sparkle.style.backgroundColor = '#ffd93d';
        sparkle.style.animationDuration = '1s';
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
</script>

</div> <!-- Close celebration-page-wrapper -->

<?php
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>