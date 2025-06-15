<?php
/**
 * Upgrade Complete Page
 * Shows confirmation of successful plan upgrade
 */

include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'].'/core/classes/class.productmanager.php');

// Check if user is logged in
if (!$account->isactive()) {
    header('Location: /login');
    exit();
}

$confirmationId = $_GET['id'] ?? '';

if (empty($confirmationId)) {
    header('Location: /myaccount/billing');
    exit();
}

// Get upgrade details from database
$sql = "SELECT * FROM bg_plan_upgrades 
        WHERE upgrade_session_id = :session_id 
        AND user_id = :user_id
        ORDER BY created_at DESC
        LIMIT 1";

$upgradeRecord = $database->getrow($sql, [
    'session_id' => $confirmationId,
    'user_id' => $account->getUserId()
]);

if (!$upgradeRecord) {
    $errormessage = 'Upgrade confirmation not found';
    $session->set('errormessage', $errormessage);
    header('Location: /myaccount/billing');
    exit();
}

// Initialize product manager to get plan details
$productManager = new ProductManager($database, $qik);
$newProduct = $productManager->getProduct($upgradeRecord['to_product_id']);

// Get user's updated details
$userData = $database->getrow(
    "SELECT * FROM bg_users WHERE user_id = :user_id",
    ['user_id' => $account->getUserId()]
);

// Page configuration
$pagedata = [
    'pagetitle' => 'Upgrade Complete!',
    'activepage' => 'billing'
];

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<style>
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
</style>

<div class="success-container">
    <div class="success-card animate-in">
        <i class="bi bi-check-circle-fill success-icon"></i>
        
        <h1 class="mb-3">Upgrade Complete!</h1>
        
        <p class="lead mb-4">
            Congratulations! You've successfully upgraded to the <strong><?php echo htmlspecialchars($newProduct['name']); ?></strong> plan.
        </p>
        
        <div class="plan-badge">
            <?php echo htmlspecialchars($newProduct['name']); ?>
        </div>
        
        <!-- New Features Available -->
        <h3 class="mt-4 mb-3">Your New Features</h3>
        <div class="features-grid">
            <?php 
            $features = $newProduct['features'] ?? [];
            foreach ($features as $feature): 
                if (strpos($feature['name'], '_sys_') === 0) continue;
            ?>
            <div class="feature-item">
                <i class="bi bi-check-circle-fill feature-icon"></i>
                <div>
                    <strong><?php echo htmlspecialchars($feature['display_value'] ?? $feature['value']); ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Confirmation Details -->
        <div class="confirmation-details">
            <h5 class="mb-3">Upgrade Details</h5>
            <div class="row">
                <div class="col-sm-6">
                    <strong>Confirmation ID:</strong><br>
                    <code><?php echo htmlspecialchars($confirmationId); ?></code>
                </div>
                <div class="col-sm-6">
                    <strong>Upgrade Date:</strong><br>
                    <?php echo date('F j, Y', strtotime($upgradeRecord['created_at'])); ?>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-sm-6">
                    <strong>Previous Plan:</strong><br>
                    <?php echo htmlspecialchars($upgradeRecord['from_plan']); ?>
                </div>
                <div class="col-sm-6">
                    <strong>New Plan:</strong><br>
                    <?php echo htmlspecialchars($upgradeRecord['to_plan']); ?>
                </div>
            </div>
        </div>
        
        <!-- Receipt Info -->
        <div class="receipt-info">
            <i class="bi bi-envelope-check me-2"></i>
            A confirmation email with your receipt has been sent to <strong><?php echo htmlspecialchars($userData['email']); ?></strong>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="/myaccount/" class="btn btn-primary btn-lg">
                <i class="bi bi-speedometer2 me-2"></i>Go to Dashboard
            </a>
            <a href="/myaccount/billing" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-credit-card me-2"></i>View Billing
            </a>
            <?php if ($newProduct['account_type'] === 'parental'): ?>
            <a href="/myaccount/family" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-people me-2"></i>Manage Family Members
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- What's Next Section -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">What's Next?</h4>
            <ul class="text-start">
                <li>Your new plan features are available immediately</li>
                <li>Any unused time from your previous plan has been credited</li>
                <li>Your next billing date remains the same</li>
                <li>You can manage your subscription anytime from the billing page</li>
            </ul>
        </div>
    </div>
</div>

<script>
// Confetti animation for celebration
document.addEventListener('DOMContentLoaded', function() {
    // Simple confetti effect
    const colors = ['#198754', '#ffc107', '#0dcaf0', '#6f42c1'];
    const confettiCount = 100;
    
    for (let i = 0; i < confettiCount; i++) {
        const confetti = document.createElement('div');
        confetti.style.cssText = `
            position: fixed;
            width: 10px;
            height: 10px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            left: ${Math.random() * 100}%;
            top: -10px;
            opacity: ${Math.random()};
            transform: rotate(${Math.random() * 360}deg);
            animation: fall ${3 + Math.random() * 2}s linear;
            z-index: 9999;
        `;
        document.body.appendChild(confetti);
        
        setTimeout(() => confetti.remove(), 5000);
    }
    
    // Add CSS animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fall {
            to {
                transform: translateY(100vh) rotate(360deg);
            }
        }
    `;
    document.head.appendChild(style);
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>