<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.productmanager.php');

// Page metadata
$pagedata['pagetitle'] = 'Pricing Plans - Birthday Gold';
$pagedata['metakeywords'] = 'Birthday Gold Pricing, Birthday Rewards Plans, Birthday Deals Pricing';
$pagedata['metadescriptions'] = 'Choose the perfect Birthday Gold plan for you. Free, Gold, and Lifetime options available. Start collecting birthday rewards from 500+ businesses!';

// Initialize ProductManager
$productManager = new ProductManager($database, $qik);

// Get the product version from site configuration
global $website;
$selectedVersion = $_REQUEST['version'] ?? $website['plan_version'];

// Default to individual account type
$selectedAccountType = $_REQUEST['account_type'] ?? 'individual';

// Get all available plans regardless of account type
$availablePlans = $productManager->getAllProductsWithFeatures($selectedVersion);
$planCount = count($availablePlans);

// Get available account types for the selector
$accountTypes = $productManager->getAvailableAccountTypes($selectedVersion);
$accountTypeConfig = $productManager->getAccountTypeConfig($selectedAccountType);

// Handle AJAX requests
if (isset($_REQUEST['ajax_action'])) {
    header('Content-Type: application/json');
    
    switch ($_REQUEST['ajax_action']) {
        case 'get_plans':
            $accountType = $_REQUEST['account_type'] ?? 'individual';
            $plans = $productManager->getProductsWithFeatures($accountType, $selectedVersion);
            
            // Format for frontend
            $response = [];
            foreach ($plans as $plan) {
                $response[] = [
                    'id' => $plan['encoded_id'],
                    'plan_code' => $plan['account_plan'],
                    'name' => $plan['account_name'],
                    'description' => $plan['description'],
                    'price' => $plan['price'],
                    'price_formatted' => $qik->convertamount($plan['price']),
                    'features' => array_column($plan['features'], 'value'),
                    'is_recommended' => (strpos(strtolower($plan['account_plan']), 'gold') !== false)
                ];
            }
            
            echo json_encode(['success' => true, 'plans' => $response, 'account_type' => $accountType]);
            exit;
    }
}

// Header flush setting for signup-style pages
$header_flush = true;

// Additional styles
$additionalstyles = '
<link href="/public/css/signup_styles.css" rel="stylesheet">
<style>
/* Override body background to match signup */
body {
    background-color: #f8f9fa !important;
}

/* Main container to match signup layout */
.signup-container {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 0 15px;
}

/* Match signup heading styles */
.signup-header {
    text-align: center;
    margin-bottom: 2rem;
}

.signup-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #212529;
}

.signup-header p {
    font-size: 1.2rem;
    color: #6c757d;
    margin-bottom: 0;
}

/* Match signup container styling */
.signup-form-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    padding: 2rem;
}

/* Headings to match signup */
.section-title {
    font-size: 1.1rem;
    margin-bottom: 1rem;
    color: #212529;
    font-weight: 600;
}

/* Plan selection button */
.btn-select-plan {
    width: 100%;
    padding: 0.875rem 1.5rem;
    font-size: 1rem;
    font-weight: 600;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 8px;
    transition: all 0.2s ease;
    cursor: pointer;
    margin-top: 2rem;
}

.btn-select-plan:hover:not(:disabled) {
    background: #5a6268;
    transform: translateY(-1px);
}

.btn-select-plan.active {
    background: #198754;
}

.btn-select-plan:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

/* Bottom links section */
.bottom-links {
    text-align: center;
    margin-top: 2rem;
    color: #6c757d;
}

.bottom-links a {
    color: #007bff;
    text-decoration: none;
}

.bottom-links a:hover {
    text-decoration: underline;
}

/* Section headers */
.plan-section {
    margin-bottom: 3rem;
}

.section-header {
    font-size: 1.5rem;
    font-weight: 700;
    color: #212529;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 3px solid #dee2e6;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .signup-header h1 {
        font-size: 2rem;
    }
    
    .signup-header p {
        font-size: 1.1rem;
    }
    
    .signup-form-container {
        padding: 1.5rem;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Main Container -->
<div class="signup-container">
    <!-- Header -->
    <div class="signup-header">
        <h1>Choose Your Perfect Plan 🎂</h1>
        <p>Select the plan that best fits your needs. Takes less than 60 seconds!</p>
    </div>

    <!-- Form Container -->
    <div class="signup-form-container">

        <!-- All Plans Organized by Type -->
        <div id="planGrid">
            <?php
            // Group plans by account type
            $plansByType = [];
            foreach ($availablePlans as $plan) {
                $plansByType[$plan['account_type']][] = $plan;
            }
            
            // Display sections in specific order
            $sections = [
                'user' => 'INDIVIDUAL',
                'parental' => 'FAMILY', 
                'giftcertificate' => 'GIFT'
            ];
            
            foreach ($sections as $accountType => $sectionTitle):
                if (!isset($plansByType[$accountType])) continue;
            ?>
                <!-- <?php echo $sectionTitle; ?> Section -->
                <div class="plan-section mb-5">
                    <h3 class="section-header"><?php echo $sectionTitle; ?></h3>
                    <div class="row g-4 justify-content-center">
                        <?php
                        foreach ($plansByType[$accountType] as $plan):
                            $isRecommended = (strpos(strtolower($plan['account_plan']), 'gold') !== false);
                            
                            // Dynamic icon based on plan name and account type
                            $planIcon = 'bi-award'; // default
                            if (strpos($plan['account_plan'], 'free') !== false) {
                                $planIcon = 'bi-person';
                            } elseif (strpos($plan['account_plan'], 'gold') !== false) {
                                $planIcon = 'bi-star-fill';
                            } elseif (strpos($plan['account_plan'], 'life') !== false) {
                                $planIcon = 'bi-infinity';
                            } elseif (strpos($plan['account_plan'], 'business') !== false) {
                                $planIcon = 'bi-building';
                            } elseif ($accountType == 'parental' || strpos($plan['account_plan'], 'family') !== false) {
                                $planIcon = 'bi-people';
                            } elseif ($accountType == 'giftcertificate') {
                                $planIcon = 'bi-gift';
                            }
                            
                            // Column sizing based on number of plans in this section
                            $plansInSection = count($plansByType[$accountType]);
                            $colClasses = 'col-12';
                            if ($plansInSection == 1) {
                                $colClasses = 'col-12 col-md-6 col-lg-4';
                            } elseif ($plansInSection == 2) {
                                $colClasses = 'col-12 col-md-6';
                            } else {
                                $colClasses = 'col-12 col-md-6 col-lg-4';
                            }
                        ?>
                            <div class="<?php echo $colClasses; ?>">
                                <div class="plan-card-wrapper h-100">
                                    <div class="plan-card h-100<?php echo $isRecommended ? ' recommended' : ''; ?>" 
                                         data-plan="<?php echo $plan['account_plan']; ?>" 
                                         data-plan-id="<?php echo $plan['encoded_id']; ?>"
                                         data-account-type="<?php echo $plan['account_type']; ?>"
                                         data-price="<?php echo $plan['price']; ?>">
                                        
                                        <?php if ($isRecommended): ?>
                                            <div class="recommended-badge">POPULAR</div>
                                        <?php endif; ?>
                                        
                                        <div class="plan-header">
                                            <div class="plan-icon">
                                                <i class="bi <?php echo $planIcon; ?>"></i>
                                            </div>
                                            <h3 class="plan-title"><?php echo htmlspecialchars($plan['account_name']); ?></h3>
                                        </div>
                                        <div class="plan-price"><?php echo $qik->convertamount($plan['price']); ?></div>
                                        <div class="plan-price-note">
                                            <?php
                                            if ($plan['price'] == 0) {
                                                echo 'Forever free';
                                            } elseif (strpos(strtolower($plan['account_plan']), 'life') !== false) {
                                                echo 'Lifetime access';
                                            } else {
                                                echo 'One-time payment';
                                            }
                                            ?>
                                        </div>
                                        
                                        <?php if (!empty($plan['features'])): ?>
                                            <ul class="plan-features">
                                                <?php foreach ($plan['features'] as $feature): ?>
                                                    <li><?php echo htmlspecialchars($feature['value']); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div> <!-- End planGrid -->

        <!-- Continue Button -->
        <button type="button" class="btn-select-plan" id="selectPlanBtn" disabled>
            Select a Plan to Continue
        </button>
    </div>

    <!-- Bottom Links -->
    <div class="bottom-links">
        <p>Already have an account? <a href="/login">Sign in</a></p>
        <p>Have a gift certificate? <a href="/register?giftcertificate">Redeem here</a></p>
    </div>
</div>

<!-- Account Type Information Modal -->
<div class="modal fade" id="accountTypeInfoModal" tabindex="-1" aria-labelledby="accountTypeInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountTypeInfoModalLabel">Account Type Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="accountTypeInfoContent">
                <div class="account-type-details">
                    <?php
                    foreach ($accountTypes as $accountType) {
                        $config = $productManager->getAccountTypeConfig($accountType['account_type']);
                        echo '<div class="mb-4">
                                <h6><i class="bi ' . $config['icon'] . ' me-2"></i>' . $config['label'] . '</h6>
                                <p>' . $config['description'] . '</p>
                                <small class="text-muted">Available plans: ' . $accountType['plan_count'] . '</small>
                              </div>';
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Store page data for JavaScript
const pageData = {
    currentAccountType: '<?php echo $selectedAccountType; ?>'
};

// Plan selection state
let selectedPlan = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set up plan selection
    setupPlanSelection();
});

// Plan selection
function setupPlanSelection() {
    const planCards = document.querySelectorAll('.plan-card');
    
    planCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove previous selections
            document.querySelectorAll('.plan-card').forEach(c => {
                c.classList.remove('selected');
                // Remove checkmark from wrapper
                const wrapper = c.closest('.plan-card-wrapper');
                const checkmark = wrapper.querySelector('.plan-checkmark-badge');
                if (checkmark) {
                    checkmark.remove();
                }
            });
            
            // Add selection to this card
            this.classList.add('selected');
            selectedPlan = {
                id: this.getAttribute('data-plan-id'),
                code: this.getAttribute('data-plan'),
                accountType: this.getAttribute('data-account-type'),
                price: this.getAttribute('data-price')
            };
            
            // Add checkmark
            const wrapper = this.closest('.plan-card-wrapper');
            const checkmark = document.createElement('div');
            checkmark.className = 'plan-checkmark-badge';
            checkmark.innerHTML = '✓';
            wrapper.appendChild(checkmark);
            
            // Update button
            updateSelectButton();
        });
    });
}

// Update select button state
function updateSelectButton() {
    const btn = document.getElementById('selectPlanBtn');
    if (selectedPlan) {
        btn.disabled = false;
        btn.classList.add('active');
        btn.textContent = 'Continue to Sign Up';
        
        // Add click handler
        btn.onclick = function() {
            // Use the plan's account type
            const accountType = selectedPlan.accountType;
            
            // Determine the correct URL based on account type
            let signupUrl = '/signup';
            if (accountType === 'giftcertificate') {
                signupUrl = '/register?giftcertificate';
            }
            
            // Redirect with selected plan and account type
            window.location.href = `${signupUrl}?account_type=${accountType}&plan=${selectedPlan.id}`;
        };
    } else {
        btn.disabled = true;
        btn.classList.remove('active');
        btn.textContent = 'Select a Plan to Continue';
        btn.onclick = null;
    }
}
</script>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>