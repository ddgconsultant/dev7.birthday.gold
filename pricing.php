<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/core/classes/class.productmanager.php');

// Page metadata
$pagedata['pagetitle'] = 'Pricing Plans - Birthday Gold';
$pagedata['metakeywords'] = 'Birthday Gold Pricing, Birthday Rewards Plans, Birthday Deals Pricing';
$pagedata['metadescriptions'] = 'Choose the perfect Birthday Gold plan for you. Free, Gold, and Lifetime options available. Start collecting birthday rewards from 500+ businesses!';

// Initialize ProductManager
$productManager = new ProductManager($database, $qik);

// Get available plans for different account types
$individualPlans = $productManager->getProductsWithFeatures('individual', 'v3');
$familyPlans = $productManager->getProductsWithFeatures('family', 'v3');
$giftPlans = $productManager->getProductsWithFeatures('gift', 'v3');

// Additional styles
$additionalstyles = '
<link rel="stylesheet" href="/public/css/common-hero.css">
<link href="/public/css/signup_styles.css" rel="stylesheet">
<style>
/* Pricing Page Specific Styles */
.pricing-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #0f0f0f 50%, #16213e 100%);
    color: white;
    padding: 4rem 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.pricing-hero::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

.pricing-hero h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
    color: #fff;
    letter-spacing: 0.5px;
}

.pricing-hero p {
    font-size: 1.5rem;
    font-weight: 400;
    margin-bottom: 2rem;
    color: #fff;
    position: relative;
    z-index: 1;
    opacity: 1;
}

/* Section Headers - Matching help.php style */
.section-header {
    margin-bottom: 1rem;
}

.section-header:not(:first-child) {
    margin-top: 3rem;
}

.section-title {
    font-size: 1.75rem;
    color: var(--bs-primary);
    font-weight: 700;
    border-bottom: 2px solid var(--bs-secondary);
    display: inline-block;
    padding-bottom: 0.5rem;
    margin-bottom: 0.5rem;
}

.section-description {
    color: #6c757d;
    margin-bottom: 2rem;
}

/* Main content area */
.main-content {
    background: #f8f9fa;
    padding: 3rem 0;
}

/* Override some signup styles for pricing page */
.plan-card {
    cursor: default !important;
}

.plan-card:hover:not(.selected) {
    background: white !important;
    border-color: #e9ecef !important;
}

.plan-card.recommended:hover {
    border-color: #a7c1a6 !important;
}

/* Price styling for pricing page */
.plan-price {
    font-size: 2.5rem !important;
    margin: 0.5rem 0 !important;
}

/* Call to action button */
.btn-get-plan {
    width: 100%;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
    margin-top: 1rem;
}

.btn-get-plan.btn-primary {
    background: var(--bs-primary);
    border: none;
    color: white;
}

.btn-get-plan.btn-warning {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    border: none;
    color: #1a1a2e;
}

.btn-get-plan:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Category Icons */
.category-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #e8f5e8;
    border-radius: 50%;
    margin-right: 0.75rem;
    font-size: 1.25rem;
    color: var(--bs-primary);
}

/* FAQ Accordion */
.faq-accordion .accordion-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 1rem;
    overflow: hidden;
}

.faq-accordion .accordion-button {
    font-weight: 600;
    font-size: 1.1rem;
    padding: 1.25rem;
}

.faq-accordion .accordion-button:not(.collapsed) {
    background: #e8f5e8;
    color: var(--bs-primary);
}

.faq-accordion .accordion-body {
    padding: 1.25rem;
    font-size: 1rem;
    line-height: 1.6;
}

/* CTA Section */
.cta-section {
    background: white;
    padding: 4rem 0;
    text-align: center;
    border-radius: 12px;
    margin-top: 3rem;
}

.cta-section h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #212529;
}

.cta-section p {
    font-size: 1.25rem;
    color: #6c757d;
    margin-bottom: 2rem;
}

/* No plans message */
.no-plans-message {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 12px;
    color: #6c757d;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .pricing-hero h1 {
        font-size: 2rem;
    }
    
    .pricing-hero p {
        font-size: 1.2rem;
    }
    
    .section-title {
        font-size: 1.5rem;
    }
    
    .category-icon {
        width: 30px;
        height: 30px;
        font-size: 1rem;
        margin-right: 0.5rem;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Pricing Hero Section -->
<div class="pricing-hero">
    <div class="container text-center">
        <h1>Choose Your Plan</h1>
        <p>Start collecting birthday rewards from <?php echo $website['biznames']; ?>+ businesses</p>
    </div>
</div>

<!-- Main Content Area -->
<div class="main-content">
    <div class="container" style="max-width: 1000px;">
        
        <!-- Just Me Plans Section -->
        <div class="section-header">
            <h2 class="section-title">
                <span class="category-icon"><i class="bi bi-person"></i></span>
                Just Me
            </h2>
            <p class="section-description">Perfect for individuals who want to celebrate their own birthday</p>
        </div>
        
        <!-- Individual Plan Cards -->
        <?php if (!empty($individualPlans)): ?>
        <div id="individualPlanGrid">
            <div class="row g-4 justify-content-center mb-5">
                <?php
                // Determine column classes based on plan count
                $colClasses = '';
                if (count($individualPlans) == 2) {
                    $colClasses = 'col-12 col-md-6';
                } elseif (count($individualPlans) == 3) {
                    $colClasses = 'col-12 col-md-4';
                } else {
                    $colClasses = 'col-12 col-md-6 col-lg-4';
                }
                
                foreach ($individualPlans as $plan):
                    $isRecommended = (strpos(strtolower($plan['account_plan']), 'gold') !== false);
                    
                    // Dynamic icon based on plan name
                    $planIcon = 'bi-award'; // default
                    if (strpos($plan['account_plan'], 'free') !== false) {
                        $planIcon = 'bi-person';
                    } elseif (strpos($plan['account_plan'], 'gold') !== false) {
                        $planIcon = 'bi-star-fill';
                    } elseif (strpos($plan['account_plan'], 'life') !== false) {
                        $planIcon = 'bi-infinity';
                    }
                ?>
                <div class="<?php echo $colClasses; ?>">
                    <div class="plan-card-wrapper h-100">
                        <div class="plan-card h-100<?php echo $isRecommended ? ' recommended' : ''; ?>">
                            
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
                                    echo 'One-time payment • Lifetime access';
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
                            
                            <div class="plan-action">
                                <a href="/signup?account_type=individual&plan=<?php echo $plan['encoded_id']; ?>" 
                                   class="btn btn-get-plan <?php echo $isRecommended ? 'btn-warning' : 'btn-primary'; ?>">
                                    Get Started
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="no-plans-message">
            <p>No individual plans available at this time.</p>
        </div>
        <?php endif; ?>
        
        <!-- My Family Plans Section -->
        <div class="section-header">
            <h2 class="section-title">
                <span class="category-icon"><i class="bi bi-people"></i></span>
                My Family
            </h2>
            <p class="section-description">Share the birthday joy with your loved ones</p>
        </div>
        
        <!-- Family Plan Cards -->
        <?php if (!empty($familyPlans)): ?>
        <div id="familyPlanGrid">
            <div class="row g-4 justify-content-center mb-5">
                <?php
                // Determine column classes based on plan count
                $colClasses = '';
                if (count($familyPlans) == 2) {
                    $colClasses = 'col-12 col-md-6';
                } elseif (count($familyPlans) == 3) {
                    $colClasses = 'col-12 col-md-4';
                } else {
                    $colClasses = 'col-12 col-md-6 col-lg-4';
                }
                
                foreach ($familyPlans as $plan):
                    $isRecommended = (strpos(strtolower($plan['account_plan']), 'gold') !== false);
                    
                    // Dynamic icon based on plan name
                    $planIcon = 'bi-people-fill';
                ?>
                <div class="<?php echo $colClasses; ?>">
                    <div class="plan-card-wrapper h-100">
                        <div class="plan-card h-100<?php echo $isRecommended ? ' recommended' : ''; ?>">
                            
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
                                    echo 'One-time payment • Lifetime access';
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
                            
                            <div class="plan-action">
                                <a href="/signup?account_type=family&plan=<?php echo $plan['encoded_id']; ?>" 
                                   class="btn btn-get-plan <?php echo $isRecommended ? 'btn-warning' : 'btn-primary'; ?>">
                                    Get Started
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="no-plans-message">
            <p>No family plans available at this time.</p>
        </div>
        <?php endif; ?>
        
        <!-- Gift Certificate Plans Section -->
        <div class="section-header">
            <h2 class="section-title">
                <span class="category-icon"><i class="bi bi-gift"></i></span>
                Gift Certificate
            </h2>
            <p class="section-description">Give the gift of birthday rewards to someone special</p>
        </div>
        
        <!-- Gift Plan Cards -->
        <?php if (!empty($giftPlans)): ?>
        <div id="giftPlanGrid">
            <div class="row g-4 justify-content-center mb-5">
                <?php
                // Determine column classes based on plan count
                $colClasses = '';
                if (count($giftPlans) == 2) {
                    $colClasses = 'col-12 col-md-6';
                } elseif (count($giftPlans) == 3) {
                    $colClasses = 'col-12 col-md-4';
                } else {
                    $colClasses = 'col-12 col-md-6 col-lg-4';
                }
                
                foreach ($giftPlans as $plan):
                    $isRecommended = (strpos(strtolower($plan['account_plan']), 'gold') !== false);
                    
                    // Dynamic icon based on plan name
                    $planIcon = 'bi-gift-fill';
                ?>
                <div class="<?php echo $colClasses; ?>">
                    <div class="plan-card-wrapper h-100">
                        <div class="plan-card h-100<?php echo $isRecommended ? ' recommended' : ''; ?>">
                            
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
                                    echo 'One-time payment • Lifetime access';
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
                            
                            <div class="plan-action">
                                <a href="/register?giftcertificate&plan=<?php echo $plan['encoded_id']; ?>" 
                                   class="btn btn-get-plan <?php echo $isRecommended ? 'btn-warning' : 'btn-primary'; ?>">
                                    Purchase Gift
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="no-plans-message">
            <p>No gift certificate plans available at this time.</p>
        </div>
        <?php endif; ?>
        
        <!-- FAQ Section -->
        <div class="section-header mt-5">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <p class="section-description">Get answers about our pricing plans</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion faq-accordion" id="pricingFAQ">
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                What's the difference between individual and family plans?
                            </button>
                        </h3>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                Individual plans are perfect for celebrating your own birthday with rewards from <?php echo $website['biznames']; ?>+ businesses. Family plans allow you to manage birthday rewards for multiple family members from one account, making it easy to ensure everyone in your family enjoys their special day!
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Why is Gold the most popular plan?
                            </button>
                        </h3>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                Gold gives you automatic enrollment in <?php echo $website['biznames']; ?>+ birthday reward programs, saving you hours of time. You'll get mobile reminders so you never miss a reward, plus priority customer support. Most members save the cost of Gold in just their first birthday month!
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                How do gift certificates work?
                            </button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                Gift certificates are the perfect birthday gift! When you purchase a gift certificate, you'll receive a unique code that the recipient can redeem to activate their Birthday Gold membership. They'll get all the benefits of the plan you choose, and you'll be the hero who gave them a year of birthday rewards!
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Can I upgrade later?
                            </button>
                        </h3>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                Absolutely! You can start with any plan and upgrade anytime. Your account data and preferences will carry over seamlessly. If you have an individual plan and want to add family members, or if you want to upgrade from Free to Gold, it's just a few clicks away!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- CTA Section -->
        <div class="cta-section">
            <h2>Ready to Start Collecting Birthday Rewards?</h2>
            <p>Join thousands of members who never miss their birthday treats</p>
            <a href="/signup" class="btn btn-primary btn-lg">Get Started Now</a>
        </div>
        
    </div>
</div>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>