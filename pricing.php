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

// Get available account types and plans
$accountTypes = $productManager->getAvailableAccountTypes($selectedVersion);
$availablePlans = $productManager->getProductsWithFeatures($selectedAccountType, $selectedVersion);
$accountTypeConfig = $productManager->getAccountTypeConfig($selectedAccountType);
$planCount = count($availablePlans);

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

        <!-- Dynamic Plan Grid -->
        <div id="planGrid">
            <?php 
            // Determine container structure based on plan count
            if ($planCount == 1): 
            ?>
                <div class="row justify-content-center">
                    <div class="col-12 col-md-8">
            <?php else: ?>
                <div class="row g-4 justify-content-center">
            <?php endif; ?>
                
                <?php
                // Determine column classes for multiple cards
                $colClasses = '';
                if ($planCount == 2) {
                    $colClasses = 'col-12 col-md-6';
                } elseif ($planCount == 3) {
                    $colClasses = 'col-12 col-md-4';
                } else {
                    $colClasses = 'col-12 col-md-6';
                }
                
                foreach ($availablePlans as $plan):
                    $isRecommended = (strpos(strtolower($plan['account_plan']), 'gold') !== false);
                    
                    // Dynamic icon based on plan name
                    $planIcon = 'bi-award'; // default
                    if (strpos($plan['account_plan'], 'free') !== false) {
                        $planIcon = 'bi-person';
                    } elseif (strpos($plan['account_plan'], 'gold') !== false) {
                        $planIcon = 'bi-star-fill';
                    } elseif (strpos($plan['account_plan'], 'life') !== false) {
                        $planIcon = 'bi-infinity';
                    } elseif (strpos($plan['account_plan'], 'business') !== false) {
                        $planIcon = 'bi-building';
                    } elseif (strpos($plan['account_plan'], 'family') !== false) {
                        $planIcon = 'bi-people';
                    }
                    
                    // Use wrapper only for single plan
                    if ($planCount == 1): 
                ?>
                        <div class="plan-card-wrapper">
                            <div class="plan-card h-100<?php echo $isRecommended ? ' recommended' : ''; ?>" 
                                 data-plan="<?php echo $plan['account_plan']; ?>" 
                                 data-plan-id="<?php echo $plan['encoded_id']; ?>"
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
                <?php else: ?>
                    <div class="<?php echo $colClasses; ?>">
                        <div class="plan-card-wrapper h-100">
                            <div class="plan-card h-100<?php echo $isRecommended ? ' recommended' : ''; ?>" 
                                 data-plan="<?php echo $plan['account_plan']; ?>" 
                                 data-plan-id="<?php echo $plan['encoded_id']; ?>"
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
                <?php 
                    endif;
                endforeach; 
                ?>
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
    ajaxUrl: '<?php echo $_SERVER['PHP_SELF']; ?>',
    csrfToken: '<?php echo $session->get('csrf_token'); ?>',
    selectedVersion: '<?php echo $selectedVersion; ?>',
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
            if (accountType && accountType !== selectedAccountType) {
                switchAccountType(accountType);
            }
        });
    });
}

// Switch account type and load new plans
function switchAccountType(accountType) {
    // Update selected account type
    selectedAccountType = accountType;
    
    // Update active button
    document.querySelectorAll('.account-type-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`.account-type-btn[data-account-type="${accountType}"]`).classList.add('active');
    
    // Show loading state
    document.getElementById('planGrid').innerHTML = '<div class="plans-loading"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Loading plans...</p></div>';
    
    // Reset selection
    selectedPlan = null;
    updateSelectButton();
    
    // Fetch new plans via AJAX
    fetch(`${pageData.ajaxUrl}?ajax_action=get_plans&account_type=${accountType}&version=${pageData.selectedVersion}`, {
        method: 'GET',
        headers: {
            'X-CSRF-Token': pageData.csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updatePlansDisplay(data.plans, accountType);
            updateContextInfo(accountType);
        }
    })
    .catch(error => {
        console.error('Error loading plans:', error);
        document.getElementById('planGrid').innerHTML = '<div class="alert alert-danger">Error loading plans. Please try again.</div>';
    });
}

// Update plans display
function updatePlansDisplay(plans, accountType) {
    const planGrid = document.getElementById('planGrid');
    let html = '';
    
    // Determine layout based on plan count
    if (plans.length === 1) {
        html = '<div class="row justify-content-center"><div class="col-12 col-md-8">';
    } else {
        html = '<div class="row g-4 justify-content-center">';
    }
    
    // Determine column classes
    let colClasses = '';
    if (plans.length === 2) {
        colClasses = 'col-12 col-md-6';
    } else if (plans.length === 3) {
        colClasses = 'col-12 col-md-4';
    } else {
        colClasses = 'col-12 col-md-6';
    }
    
    // Generate plan cards
    plans.forEach(plan => {
        // Dynamic icon
        let planIcon = 'bi-award';
        if (plan.plan_code.includes('free')) {
            planIcon = 'bi-person';
        } else if (plan.plan_code.includes('gold')) {
            planIcon = 'bi-star-fill';
        } else if (plan.plan_code.includes('life')) {
            planIcon = 'bi-infinity';
        } else if (plan.plan_code.includes('family')) {
            planIcon = 'bi-people';
        }
        
        // Features list
        let featuresHtml = '';
        if (plan.features && plan.features.length > 0) {
            featuresHtml = '<ul class="plan-features">';
            plan.features.forEach(feature => {
                featuresHtml += `<li>${feature}</li>`;
            });
            featuresHtml += '</ul>';
        }
        
        // Price note
        let priceNote = 'One-time payment';
        if (plan.price == 0) {
            priceNote = 'Forever free';
        } else if (plan.plan_code.includes('life')) {
            priceNote = 'Lifetime access';
        }
        
        // Build card HTML
        if (plans.length === 1) {
            html += `
                <div class="plan-card-wrapper">
                    <div class="plan-card h-100${plan.is_recommended ? ' recommended' : ''}" 
                         data-plan="${plan.plan_code}" 
                         data-plan-id="${plan.id}"
                         data-price="${plan.price}">
                        ${plan.is_recommended ? '<div class="recommended-badge">POPULAR</div>' : ''}
                        <div class="plan-header">
                            <div class="plan-icon">
                                <i class="bi ${planIcon}"></i>
                            </div>
                            <h3 class="plan-title">${plan.name}</h3>
                        </div>
                        <div class="plan-price">${plan.price_formatted}</div>
                        <div class="plan-price-note">${priceNote}</div>
                        ${featuresHtml}
                    </div>
                </div>`;
        } else {
            html += `
                <div class="${colClasses}">
                    <div class="plan-card-wrapper h-100">
                        <div class="plan-card h-100${plan.is_recommended ? ' recommended' : ''}" 
                             data-plan="${plan.plan_code}" 
                             data-plan-id="${plan.id}"
                             data-price="${plan.price}">
                            ${plan.is_recommended ? '<div class="recommended-badge">POPULAR</div>' : ''}
                            <div class="plan-header">
                                <div class="plan-icon">
                                    <i class="bi ${planIcon}"></i>
                                </div>
                                <h3 class="plan-title">${plan.name}</h3>
                            </div>
                            <div class="plan-price">${plan.price_formatted}</div>
                            <div class="plan-price-note">${priceNote}</div>
                            ${featuresHtml}
                        </div>
                    </div>
                </div>`;
        }
    });
    
    html += '</div>';
    planGrid.innerHTML = html;
    
    // Re-setup plan selection for new cards
    setupPlanSelection();
}

// Update context info based on account type
function updateContextInfo(accountType) {
    const contextTexts = {
        'individual': 'Perfect for individuals who want to celebrate their birthday with exclusive rewards',
        'family': 'Manage birthday rewards for your entire family in one account',
        'gift': 'Give the gift of birthday rewards to someone special',
        'business': 'Perfect for businesses wanting to offer birthday rewards',
        'parental': 'Parents can manage birthday rewards for their children'
    };
    
    const contextText = contextTexts[accountType] || 'Select the account type that best fits your needs';
    document.getElementById('contextText').textContent = contextText;
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
            // Determine the correct URL based on account type
            let signupUrl = '/signup';
            if (selectedAccountType === 'gift') {
                signupUrl = '/register?giftcertificate';
            }
            
            // Redirect with selected plan and account type
            window.location.href = `${signupUrl}?account_type=${selectedAccountType}&plan=${selectedPlan.id}`;
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