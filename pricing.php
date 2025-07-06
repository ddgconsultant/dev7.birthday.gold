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

/* Account type label */
.account-type-label {
    text-align: center;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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
        <p>Choose your account type and plan below. Takes less than 60 seconds!</p>
    </div>

    <!-- Form Container -->
    <div class="signup-form-container">
        <h3 class="section-title">Pick who this for:</h3>
        
        <!-- Account Type Selector -->
        <div class="account-type-selector" id="accountTypeSelector">
            <?php
            $displayedTypes = 0;
            foreach ($accountTypes as $accountType) {
                $config = $productManager->getAccountTypeConfig($accountType['account_type']);
                $isActive = ($accountType['account_type'] == $selectedAccountType) ? 'active' : '';
                
                // Only show first 3 types directly
                if ($displayedTypes < 3) {
                    echo '<button class="account-type-btn ' . $isActive . '" data-account-type="' . $accountType['account_type'] . '">
                            <i class="bi ' . $config['icon'] . '"></i><span>' . $config['short_label'] . '</span>
                          </button>';
                    $displayedTypes++;
                }
            }
            ?>
        </div>

        <!-- Context Info -->
        <div class="context-info" id="contextInfo">
            <div class="info-text">
                <i class="bi bi-info-circle info-icon"></i>
                <span id="contextText"><?php echo $accountTypeConfig['context_text']; ?></span>
            </div>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#accountTypeInfoModal" title="Learn more">
                <i class="bi bi-info-circle learn-more-icon d-inline d-md-none"></i>
                <span class="learn-more-text d-none d-md-inline">Learn More</span>
            </button>
        </div>

        <!-- Plan Selection -->
        <h3 class="section-title mt-5 pt-md-5 pt-sm-2">Pick the plan:</h3>

        <!-- All Plans Grid -->
        <div id="planGrid">
            <div class="row g-4 justify-content-center">
                
                <?php
                // Display all plans with appropriate column sizing
                // Use 3 columns for desktop, 1 for mobile
                $colClasses = 'col-12 col-md-4';
                
                foreach ($availablePlans as $plan):
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
                    } elseif ($plan['account_type'] == 'parental' || strpos($plan['account_plan'], 'family') !== false) {
                        $planIcon = 'bi-people';
                    } elseif ($plan['account_type'] == 'giftcertificate') {
                        $planIcon = 'bi-gift';
                    }
                    
                    // Add account type label for clarity
                    $accountTypeLabel = '';
                    switch($plan['account_type']) {
                        case 'user':
                            $accountTypeLabel = 'Individual';
                            break;
                        case 'parental':
                            $accountTypeLabel = 'Family';
                            break;
                        case 'business':
                            $accountTypeLabel = 'Business';
                            break;
                        case 'giftcertificate':
                            $accountTypeLabel = 'Gift';
                            break;
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
                                
                                <?php if ($accountTypeLabel): ?>
                                    <div class="account-type-label text-muted small mb-2"><?php echo $accountTypeLabel; ?></div>
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
            </div> <!-- End row -->
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
let selectedAccountType = pageData.currentAccountType;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set up account type switching
    setupAccountTypeSwitching();
    
    // Set up plan selection
    setupPlanSelection();
});

// Account type switching
function setupAccountTypeSwitching() {
    const accountTypeBtns = document.querySelectorAll('.account-type-btn');
    
    accountTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const accountType = this.getAttribute('data-account-type');
            if (accountType) {
                // Update selected account type
                selectedAccountType = accountType;
                
                // Update active button
                document.querySelectorAll('.account-type-btn').forEach(b => {
                    b.classList.remove('active');
                });
                this.classList.add('active');
                
                // Update context info
                updateContextInfo(accountType);
                
                // Update button state if plan is selected
                updateSelectButton();
            }
        });
    });
}

// Update context info based on account type
function updateContextInfo(accountType) {
    const contextTexts = {
        'individual': 'Perfect for individuals who want to celebrate their birthday with exclusive rewards',
        'user': 'Perfect for individuals who want to celebrate their birthday with exclusive rewards',
        'family': 'Manage birthday rewards for your entire family in one account',
        'parental': 'Manage birthday rewards for your entire family in one account',
        'gift': 'Give the gift of birthday rewards to someone special',
        'giftcertificate': 'Give the gift of birthday rewards to someone special',
        'business': 'Perfect for businesses wanting to offer birthday rewards'
    };
    
    const contextText = contextTexts[accountType] || 'Select the account type that best fits your needs';
    const contextElement = document.getElementById('contextText');
    if (contextElement) {
        contextElement.textContent = contextText;
    }
}

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
            // Use the plan's account type and the selected account type from the buttons
            // The plan's account type takes precedence
            const accountType = selectedPlan.accountType || selectedAccountType;
            
            // Determine the correct URL based on account type
            let signupUrl = '/signup';
            if (accountType === 'giftcertificate' || selectedAccountType === 'giftcertificate') {
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