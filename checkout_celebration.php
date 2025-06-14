<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Get user ID
$encoded_user_id = $_REQUEST['u'] ?? '';
$user_id = null;
$user_data = null;

// Try to decode user ID if provided
if (!empty($encoded_user_id)) {
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

// If we still don't have user data, try to get it from session
if (!$user_data && $session->get('logged_in')) {
    $user_id = $session->get('user_id');
    if ($user_id) {
        $sql = "SELECT * FROM bg_users WHERE user_id = :user_id";
        $user_data = $database->getrow($sql, ['user_id' => $user_id]);
    }
}

// Page setup
$pagetitle = 'Welcome to Birthday Gold!';
$bodyclass = 'class="celebration-page"';

// Additional styles for celebration
$additionalstyles = '
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body.celebration-page {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    padding-top: 0 !important; /* Override default body padding */
}

.celebration-container {
    text-align: center;
    color: white;
    position: relative;
    z-index: 10;
    padding: 2rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    max-width: 800px;
    margin: 0 auto;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.celebration-icon {
    font-size: 5rem;
    margin-bottom: 1.5rem;
    animation: bounce 1s ease-in-out;
}

.celebration-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    animation: fadeInUp 0.8s ease-out 0.3s both;
    background: rgba(255, 255, 255, 0.1);
    padding: 1rem 2rem;
    border-radius: 12px;
    backdrop-filter: blur(5px);
}

.celebration-subtitle {
    font-size: 1.5rem;
    margin-bottom: 2rem;
    animation: fadeInUp 0.8s ease-out 0.5s both;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    backdrop-filter: blur(5px);
    display: inline-block;
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
    border-radius: 10px;
    backdrop-filter: blur(5px);
    color: white;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.celebration-button {
    background: white;
    color: #28a745;
    border: none;
    padding: 1rem 3rem;
    font-size: 1.2rem;
    font-weight: 600;
    border-radius: 50px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    animation: fadeInUp 0.8s ease-out 0.9s both;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.celebration-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.3);
    color: #20c997;
    background: #f8f9fa;
}

/* Confetti animation */
.confetti {
    position: fixed;
    width: 10px;
    height: 10px;
    background: #f0f;
    animation: confetti-fall linear infinite;
    z-index: 5;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-30px);
    }
    60% {
        transform: translateY(-15px);
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
    0% {
        top: -10vh;
        transform: translateX(0) rotateZ(0deg);
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        top: 110vh;
        transform: translateX(100px) rotateZ(720deg);
        opacity: 0;
    }
}

/* Next Steps Section */
.next-steps {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    padding: 1.5rem;
    margin: 2rem auto;
    max-width: 600px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.next-steps h3 {
    margin-bottom: 1rem;
    font-size: 1.3rem;
    color: white;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
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
    color: white;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.next-steps-list li:before {
    content: "✓";
    position: absolute;
    left: 0;
    font-weight: bold;
    font-size: 1.2rem;
    color: white;
}

/* Hide header/nav on celebration page */
body.celebration-page .top-header,
body.celebration-page .main-navigation,
body.celebration-page header {
    display: none !important;
}

/* Responsive */
@media (max-width: 768px) {
    .celebration-title {
        font-size: 2rem;
    }
    .celebration-subtitle {
        font-size: 1.2rem;
    }
    .celebration-icon {
        font-size: 4rem;
    }
    .next-steps {
        margin: 1rem;
    }
}
</style>
';

// Skip header for celebration page
$ignoreheader = true;

include($dir['core_components'] . '/bg_pagestart.inc');
?>

<div class="celebration-container">
    <div class="celebration-icon">
        <i class="bi bi-check-circle-fill text-success"></i>
    </div>
    
    <h1 class="celebration-title">Welcome to Birthday Gold!</h1>
    
    <?php
    // Determine if this is a paid account
    $is_paid_account = true;
    if ($user_data) {
        // Check if user has a free plan
        $is_paid_account = !in_array($user_data['account_plan'], ['free', 'basic', '']);
    }
    ?>
    
    <p class="celebration-subtitle text-success">
        <?php if ($is_paid_account): ?>
            Your payment was successful<?php echo $user_data ? ', ' . htmlspecialchars($user_data['first_name']) : ''; ?>!
        <?php else: ?>
            Your account is ready<?php echo $user_data ? ', ' . htmlspecialchars($user_data['first_name']) : ''; ?>!
        <?php endif; ?>
    </p>
    
    <p class="celebration-message">
        <?php if ($is_paid_account): ?>
            You're all set to start receiving amazing birthday rewards from hundreds of businesses. 
            We'll automatically enroll you in birthday programs as your special day approaches.
        <?php else: ?>
            Welcome to the Birthday Gold community! 
            You can now start selecting birthday rewards from participating businesses.
        <?php endif; ?>
    </p>
    
    <!-- Next Steps Section -->
    <div class="next-steps">
        <h3><i class="bi bi-list-check me-2"></i>Your Next Steps:</h3>
        <ul class="next-steps-list text-primary">
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

<!-- Simple confetti effect -->
<script>
// Create confetti
function createConfetti() {
    const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24', '#f0932b', '#eb4d4b', '#6ab04c', '#ffffff', '#ffd93d'];
    
    // Create initial batch
    for (let i = 0; i < 100; i++) {
        createSingleConfetti(colors, i * 50);
    }
    
    // Continue creating confetti every few seconds
    setInterval(() => {
        for (let i = 0; i < 20; i++) {
            createSingleConfetti(colors, i * 100);
        }
    }, 3000);
}

function createSingleConfetti(colors, delay) {
    setTimeout(() => {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + 'vw';
        confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.width = (Math.random() * 10 + 5) + 'px';
        confetti.style.height = confetti.style.width;
        confetti.style.animationDuration = (Math.random() * 3 + 5) + 's';
        confetti.style.opacity = Math.random() * 0.8 + 0.2;
        document.body.appendChild(confetti);
        
        // Remove after animation completes
        setTimeout(() => confetti.remove(), 8000);
    }, delay);
}

// Start confetti on load
window.addEventListener('load', createConfetti);

// Add countdown timer
let timeLeft = 60;
const countdownEl = document.createElement('p');
countdownEl.className = 'countdown-timer';
countdownEl.style.cssText = 'position: fixed; bottom: 20px; right: 20px; color: white; font-size: 0.9rem; opacity: 0.7;';
document.body.appendChild(countdownEl);

function updateCountdown() {
    countdownEl.textContent = `Redirecting in ${timeLeft} seconds...`;
    timeLeft--;
    if (timeLeft < 0) {
        window.location.href = '<?php echo $redirect_url; ?>';
    }
}
updateCountdown();
setInterval(updateCountdown, 1000);

// Auto-redirect after 60 seconds
setTimeout(() => {
    window.location.href = '<?php echo $redirect_url; ?>';
}, 60000);
</script>

<?php
// No footer on celebration page
?>
</body>
</html>