<?php
/**
 * Upgrade Complete Page
 * Success page after plan upgrade
 */

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

// Page setup
$pagetitle = "Upgrade Complete!";

// Get the plan from URL
$upgraded_plan = $_GET['plan'] ?? '';

// Get current user data from global variable set by site-controller
$current_plan = $current_user_data['account_plan'] ?? '';
$current_product_id = $current_user_data['account_product_id'] ?? null;
$user_email = $current_user_data['email'] ?? '';

// Normalize plan name for display
$display_plan = ucfirst(str_replace(['user_', 'parental_', 'minor_', 'business_'], '', $current_plan));

// Get product details if we have a product ID
$product_name = $display_plan;
if ($current_product_id) {
    $sql = "SELECT account_name FROM bg_products WHERE id = :id AND status = 'active'";
    $stmt = $database->prepare($sql);
    $stmt->execute(['id' => $current_product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && !empty($result['account_name'])) {
        $product_name = $result['account_name'];
    }
}

// Add custom styles
$additionalstyles .= '<style>
.success-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
    text-align: center;
}

.success-icon {
    font-size: 5rem;
    color: #198754;
    margin-bottom: 1rem;
}

.success-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 3rem;
    margin-bottom: 2rem;
}

.plan-badge {
    display: inline-block;
    background: #198754;
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    margin: 1rem 0;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
    text-align: left;
}

.feature-item {
    display: flex;
    align-items: start;
    gap: 1rem;
}

.feature-icon {
    color: #198754;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.confirmation-details {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin: 2rem 0;
    text-align: left;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 2rem;
}

.receipt-info {
    background: #e8f5e9;
    border: 1px solid #c8e6c9;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 2rem;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-in {
    animation: fadeInUp 0.5s ease-out;
}

.confetti {
    position: fixed;
    width: 10px;
    height: 10px;
    position: absolute;
    animation: confetti-fall 3s linear;
    z-index: 9999;
}

@keyframes confetti-fall {
    to {
        transform: translateY(100vh) rotate(360deg);
        opacity: 0;
    }
}
</style>';

// Add celebration script
$additionalscripts .= '<script>
$(document).ready(function() {
    // Create confetti effect
    function createConfetti() {
        const colors = ["#667eea", "#764ba2", "#28a745", "#ffc107", "#dc3545"];
        for (let i = 0; i < 30; i++) {
            setTimeout(() => {
                const confetti = $("<div>").addClass("confetti");
                confetti.css({
                    left: Math.random() * 100 + "%",
                    top: "-10px",
                    background: colors[Math.floor(Math.random() * colors.length)],
                    animationDelay: Math.random() * 2 + "s",
                    animationDuration: Math.random() * 3 + 3 + "s"
                });
                $("body").append(confetti);
                setTimeout(() => confetti.remove(), 6000);
            }, i * 100);
        }
    }
    
    // Trigger confetti on page load
    createConfetti();
});
</script>';

// Include header
include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

echo '<div class="success-container">';
echo '<div class="success-card animate-in">';
echo '<i class="bi bi-check-circle-fill success-icon"></i>';
echo '<h1 class="mb-3">Upgrade Complete!</h1>';
echo '<p class="lead mb-4">Congratulations! You have successfully upgraded to the <strong>' . htmlspecialchars($product_name) . '</strong> plan.</p>';
echo '<div class="plan-badge">' . htmlspecialchars($product_name) . ' Plan</div>';
echo '<h3 class="mt-4 mb-3">Your New Features</h3>';
echo '<div class="features-grid">';

$features = [
    'Unlimited enrollments',
    'Priority customer support',
    'Ad-free experience',
    'Exclusive rewards and offers'
];

foreach ($features as $feature) {
    echo '<div class="feature-item">';
    echo '<i class="bi bi-check-circle-fill feature-icon"></i>';
    echo '<div><strong>' . $feature . '</strong></div>';
    echo '</div>';
}

echo '</div>';
echo '<div class="confirmation-details">';
echo '<h5 class="mb-3">Upgrade Details</h5>';
echo '<div class="row">';
echo '<div class="col-sm-6">';
echo '<strong>Upgrade Date:</strong><br>';
echo date('F j, Y');
echo '</div>';
echo '<div class="col-sm-6">';
echo '<strong>New Plan:</strong><br>';
echo htmlspecialchars($product_name);
echo '</div>';
echo '</div>';
echo '</div>';
echo '<div class="receipt-info">';
echo '<i class="bi bi-envelope-check me-2"></i>';
echo 'A confirmation email with your receipt has been sent to <strong>' . htmlspecialchars($user_email) . '</strong>';
echo '</div>';
echo '<div class="action-buttons">';
echo '<a href="/myaccount/" class="btn btn-primary btn-lg">';
echo '<i class="bi bi-speedometer2 me-2"></i>Go to Dashboard';
echo '</a>';
echo '<a href="/myaccount/plan-details" class="btn btn-outline-primary btn-lg">';
echo '<i class="bi bi-credit-card me-2"></i>View Plan Details';
echo '</a>';
echo '</div>';
echo '</div>'; // End success-card
echo '<div class="card">';
echo '<div class="card-body">';
echo '<h4 class="card-title">What is Next?</h4>';
echo '<ul class="text-start">';
echo '<li>Your new plan features are available immediately</li>';
echo '<li>Any unused time from your previous plan has been credited</li>';
echo '<li>Your next billing date remains the same</li>';
echo '<li>You can manage your subscription anytime from the billing page</li>';
echo '</ul>';
echo '</div>';
echo '</div>';

echo '</div>'; // End container

// Include footer
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();