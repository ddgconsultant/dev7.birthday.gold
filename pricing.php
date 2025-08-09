<?PHP

$addClasses[] = 'productmanager';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page metadata
$pagedata['pagetitle'] = 'Plans, Pricing, and Other Details - Birthday Gold';
$pagedata['metakeywords'] = 'Birthday Gold Pricing, Birthday Rewards Plans, Birthday Deals Pricing';
$pagedata['metadescriptions'] = 'Choose the perfect Birthday Gold plan for you. Free, Gold, and Lifetime options available. Start collecting birthday rewards from 500+ businesses!';


// Get the product version from site configuration
global $website;
$selectedVersion = $_REQUEST['version'] ?? $website['plan_version'];

// Default to individual account type
$selectedAccountType = $_REQUEST['account_type'] ?? 'individual';

// Get all available plans regardless of account type
$availablePlans = $productmanager->getAllProductsWithFeatures($selectedVersion);
$planCount = count($availablePlans);

// Get available account types for the selector
$accountTypes = $productmanager->getAvailableAccountTypes($selectedVersion);
$accountTypeConfig = $productmanager->getAccountTypeConfig($selectedAccountType);

// Handle AJAX requests
if (isset($_REQUEST['ajax_action'])) {
    header('Content-Type: application/json');
    
    switch ($_REQUEST['ajax_action']) {
        case 'get_plans':
            $accountType = $_REQUEST['account_type'] ?? 'individual';
            $plans = $productmanager->getProductsWithFeatures($accountType, $selectedVersion);
            
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
$additionalstyles .= '
<link href="' . cssUrl('/public/css/signup_styles.css') . '" rel="stylesheet">
<style>
/* Extended header for pricing page */
.content-header-dark.pricing-header {
    padding: 6rem 0 4rem 0 !important;
}

/* Mobile adjustment */
@media (max-width: 768px) {
    .content-header-dark.pricing-header {
        padding: 3rem 0 2rem 0 !important;
    }
}

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

/* Hard header now handled by content-header-dark in bg_theme.css */

/* Section headers */
.plan-section {
    margin-bottom: 3rem;
}

.section-header {
    font-size: 1.75rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 3px solid #198754;
}

.section-caption {
    color: #6c757d;
    font-size: 1.1rem;
    margin-bottom: 2rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    
    .section-header {
        font-size: 1.5rem;
    }
    
    .section-caption {
        font-size: 1rem;
    }
    
    .signup-form-container {
        padding: 1.5rem;
    }
}

/* Section Separators */
.section-separator {
    margin: 4rem 0;
    text-align: center;
    position: relative;
}

.section-separator::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(to right, transparent, #dee2e6, transparent);
}

.section-separator-icon {
    background: #f8f9fa;
    display: inline-block;
    padding: 0 1.5rem;
    position: relative;
    z-index: 1;
}

.section-separator-icon i {
    font-size: 1.5rem;
    color: #198754;
}

/* Gift Certificate Section */
.gift-section {
    background: #e8f5e9;
    padding: 4rem 0;
    margin: 0;
    position: relative;
}

.gift-certificate-card {
    border: 2px solid #198754;
    border-radius: 12px;
    padding: 2rem;
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Eligibility Section */
.eligibility-section {
    background: #f3f4f6;
    padding: 4rem 0;
    margin: 0;
    position: relative;
}

/* Add alternating background for pricing sections */
.signup-container > .signup-form-container {
    background: white;
    margin-bottom: 0;
}

/* Make section separators blend better */
.section-separator {
    margin: 0;
    padding: 2rem 0;
    background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.5), transparent);
}

.section-separator-icon {
    background: white;
    border: 2px solid #dee2e6;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.eligibility-card {
    border-left: 4px solid #198754;
    padding-left: 1.5rem;
    margin-bottom: 2rem;
}

.eligibility-icon {
    width: 48px;
    height: 48px;
    background: #198754;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

/* Tab styles for eligibility section - modern style matching loginhistory */
.nav-tabs-modern {
    display: flex;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
    gap: 0;
    overflow: hidden;
    position: relative;
}

.nav-tab-item {
    flex: 0 0 auto;
    padding: 1rem 2rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    margin-bottom: -2px;
    transition: all 0.2s ease;
    background: none;
    border-radius: 0;
    position: relative;
    border: none;
    border-top: none;
    border-left: none;
    border-right: none;
    border-bottom: none;
    cursor: pointer;
}

.nav-tab-item::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 50%;
    right: 50%;
    height: 3px;
    background: #198754;
    transition: left 0.3s ease, right 0.3s ease;
    opacity: 0;
}

.nav-tab-item:hover {
    color: #495057;
    text-decoration: none;
    background: #f8f9fa;
}

.nav-tab-item:hover::after {
    left: 0;
    right: 0;
    opacity: 1;
}

.nav-tab-item.active {
    color: #198754;
    background: none;
}

.nav-tab-item.active::after {
    left: 0;
    right: 0;
    opacity: 1;
    background: #198754;
}

/* Bottom Section */
.bottom-section {
    background: #fff8e1;
    padding: 3rem 0;
    margin: 0;
}

/* Full width sections wrapper */
.pricing-wrapper {
    background: #fafbfc;
}

@media (max-width: 768px) {
    .gift-section,
    .eligibility-section,
    .bottom-section {
        padding: 3rem 0;
    }
    
    .nav-tab-item {
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
    }
}

/* Remove Bootstrap default styles that might interfere */
.nav-tabs {
    border-bottom: none;
}

.nav-link {
    border: none;
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-dark pricing-header">
    <div class="container">
        <h1>Plans, Pricing, and Other Details</h1>
        <p class="lead">Choose the perfect plan for your birthday rewards journey</p>
    </div>
</div>

<!-- Main Pricing Section -->
<div class="pricing-wrapper">
    <div class="signup-container">
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
            
            // Display sections in specific order with captions
            $sections = [
                'user' => [
                    'title' => 'Individual',
                    'caption' => 'Perfect for celebrating your own birthday with exclusive rewards'
                ],
                'parental' => [
                    'title' => 'Family',
                    'caption' => 'Manage birthday rewards for your entire family in one account'
                ],
                'giftcertificate' => [
                    'title' => 'Gift',
                    'caption' => 'Give the gift of birthday rewards to someone special'
                ]
            ];
            
            foreach ($sections as $accountType => $sectionInfo):
                if (!isset($plansByType[$accountType])) continue;
            ?>
                <!-- <?php echo $sectionInfo['title']; ?> Section -->
                <div class="plan-section mb-5">
                    <h3 class="section-header"><?php echo $sectionInfo['title']; ?></h3>
                    <p class="section-caption"><?php echo $sectionInfo['caption']; ?></p>
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

    <!-- Section Separator -->
    <div class="section-separator">
        <span class="section-separator-icon">
            <i class="bi bi-gift"></i>
        </span>
    </div>

    </div><!-- End signup-container for pricing plans -->
</div><!-- End wrapper for pricing section -->

<!-- Gift Certificate Section -->
<div class="gift-section">
    <div class="container">
        <h2 class="section-header text-center mb-3">Gold Plan Gift Certificate</h2>
        <p class="lead text-center mb-5">Give the gift of celebration with our Gold Plan Gift Certificate — perfect for making birthdays unforgettable.</p>
        <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    
                    <div class="gift-certificate-card">
                        <h3 class="h4 mb-3">Make Their Birthday Special</h3>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Exclusive Benefits</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Premium Services</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Personalized Experience</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Valid for One Full Year</li>
                        </ul>
                        <p class="text-muted mb-4">Perfect for friends, family, or anyone who deserves a special birthday celebration.</p>
                        <?php
                        // Find the gift certificate plan
                        $giftPlanId = '';
                        foreach ($availablePlans as $plan) {
                            if ($plan['account_type'] == 'giftcertificate' && strpos($plan['account_plan'], 'gold') !== false) {
                                $giftPlanId = $plan['encoded_id'];
                                break;
                            }
                        }
                        ?>
                        <a href="/signup?account_type=giftcertificate&plan=<?php echo $giftPlanId; ?>" class="btn btn-primary btn-lg w-100">Buy Gift Certificate</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="/public/images/sample-gc.jpg" class="img-fluid rounded shadow" alt="Gift Certificate Sample">
                </div>
            </div>
        </div>
    </div>

    <!-- Section Separator -->
    <div class="section-separator">
        <span class="section-separator-icon">
            <i class="bi bi-shield-check"></i>
        </span>
    </div>

    <!-- Eligibility Section -->
    <div class="eligibility-section">
        <div class="container">
            <h2 class="section-header text-center mb-4">Birthday Gold Eligibility</h2>
            
            <!-- Modern Tabs -->
            <nav class="nav-tabs-modern justify-content-center" id="eligibilityTab" role="tablist">
                <button class="nav-tab-item active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">General</button>
                <button class="nav-tab-item" id="guidelines-tab" data-bs-toggle="tab" data-bs-target="#guidelines" type="button" role="tab">Guidelines</button>
                <button class="nav-tab-item" id="usa-tab" data-bs-toggle="tab" data-bs-target="#usa" type="button" role="tab">USA Only</button>
            </nav>

            <!-- Tab Content -->
            <div class="tab-content" id="eligibilityTabContent">
                <!-- General Tab -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <div class="row g-4 mt-3">
                        <div class="col-md-4">
                            <div class="d-flex align-items-start">
                                <div class="eligibility-icon me-3">
                                    <i class="bi bi-gift-fill"></i>
                                </div>
                                <div>
                                    <h4>Exceptional Birthday Experience</h4>
                                    <p>Our Birthday Gold service is designed to provide an exceptional birthday experience for our users with exclusive deals, personalized gifts, and unforgettable experiences.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-start">
                                <div class="eligibility-icon me-3">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div>
                                    <h4>Something for Everyone</h4>
                                    <p>Birthday.Gold is for people of all ages looking to make their birthdays extra special. Our platform offers birthday rewards from over <?php echo $website['numberofbiz']; ?>+ <?php echo $website['biznames']; ?>.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-start">
                                <div class="eligibility-icon me-3">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div>
                                    <h4>Simple and Rewarding</h4>
                                    <p>Sign up to discover personalized birthday offers. From freebies to VIP experiences, explore our celebration map to find the best deals in your area.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Guidelines Tab -->
                <div class="tab-pane fade" id="guidelines" role="tabpanel">
                    <div class="mt-4">
                        <h3 class="mb-4">Important Guidelines and Limitations</h3>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="eligibility-card">
                                    <h5><i class="bi bi-calendar me-2"></i>Age Restrictions</h5>
                                    <p>A minimum age of 16 is required to sign up for a paid account. However, gift certificates can be enjoyed by users of any age. Birthday Gold enrollments are age-restricted, and businesses may require identification.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="eligibility-card">
                                    <h5><i class="bi bi-people me-2"></i>Parents with Children</h5>
                                    <p>Parents can sign up their children for Birthday Gold service. As the responsible party, parents manage their child account and may need to be present when redeeming rewards.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="eligibility-card">
                                    <h5><i class="bi bi-star me-2"></i>Honor Classes</h5>
                                    <p>Special privileges for military personnel, teachers, and medical professionals. Indicate your special honor class to receive distinctive rewards (ID may be required).</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="eligibility-card">
                                    <h5><i class="bi bi-list-check me-2"></i>Dietary Preferences</h5>
                                    <p>Indicate dietary preferences in your Enrollment Profile. While we work to provide suitable options, specific rewards depend on participating businesses.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- USA Only Tab -->
                <div class="tab-pane fade" id="usa" role="tabpanel">
                    <div class="mt-4">
                        <h3 class="mb-4">USA Only Geography</h3>
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="d-flex align-items-start mb-4">
                                    <div class="eligibility-icon me-3">
                                        <i class="bi bi-geo-alt-fill"></i>
                                    </div>
                                    <div>
                                        <h5>Exclusively Available in the United States</h5>
                                        <p>Currently, Birthday Gold service is exclusively available within the United States. We offer rewards from <?php echo $website['biznames']; ?> across the nation.</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start mb-4">
                                    <div class="eligibility-icon me-3">
                                        <i class="bi bi-shop"></i>
                                    </div>
                                    <div>
                                        <h5>USA-Based Businesses</h5>
                                        <p>We partner exclusively with USA-based <?php echo $website['biznames']; ?>, ensuring access to the best local and relevant rewards.</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start mb-4">
                                    <div class="eligibility-icon me-3">
                                        <i class="bi bi-map"></i>
                                    </div>
                                    <div>
                                        <h5>Local Rewards</h5>
                                        <p>Customize your rewards to businesses local to you. Enjoy exclusive deals from your favorite local shops, restaurants, and entertainment venues.</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start">
                                    <div class="eligibility-icon me-3">
                                        <i class="bi bi-compass"></i>
                                    </div>
                                    <div>
                                        <h5>Advanced Navigation</h5>
                                        <p>Our system considers your location to ensure convenient offers. All the best deals are just a short distance away!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div><!-- End eligibility section container -->
</div><!-- End eligibility section -->

<!-- Bottom Section -->
<div class="bottom-section">
    <div class="container">
        <!-- Bottom Links -->
        <div class="bottom-links">
            <p>Already have an account? <a href="/login">Sign in</a></p>
            <p>Have a gift certificate? <a href="/redeem">Redeem here</a></p>
        </div>
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
                        $config = $productmanager->getAccountTypeConfig($accountType['account_type']);
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