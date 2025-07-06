<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page metadata
$pagedata['pagetitle'] = 'Pricing Plans - Birthday Gold';
$pagedata['metakeywords'] = 'Birthday Gold Pricing, Birthday Rewards Plans, Birthday Deals Pricing';
$pagedata['metadescriptions'] = 'Choose the perfect Birthday Gold plan for you. Free, Gold, and Lifetime options available. Start collecting birthday rewards from 500+ businesses!';

// Get product manager instance
$productManager = new ProductManager($database);

// Get available plans for 'individual' account type (most common)
$availablePlans = $productManager->getProductsWithFeatures('individual', 'v3');

// Additional styles
$additionalstyles = '
<link rel="stylesheet" href="/public/css/common-hero.css">
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

/* Pricing Section */
.pricing-section {
    padding: 4rem 0;
    background: #f8f9fa;
}

/* Plan Cards */
.plan-card-wrapper {
    height: 100%;
    padding: 4px;
    border-radius: 16px;
    background: white;
    transition: all 0.3s ease;
}

.plan-card-wrapper:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.plan-card-wrapper.recommended {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.plan-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    position: relative;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.plan-card.recommended {
    border-color: #FFD700;
}

.recommended-badge {
    position: absolute;
    top: -12px;
    right: 20px;
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #1a1a2e;
    padding: 4px 16px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.plan-header {
    text-align: center;
    margin-bottom: 2rem;
}

.plan-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 1rem;
    background: #f8f9fa;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: var(--bs-primary);
}

.plan-card.recommended .plan-icon {
    background: #fff8e1;
    color: #FFA500;
}

.plan-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
}

.plan-price {
    font-size: 3rem;
    font-weight: 800;
    color: var(--bs-primary);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.plan-card.recommended .plan-price {
    color: #FFA500;
}

.plan-price-note {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 2rem;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 2rem;
    flex-grow: 1;
}

.plan-features li {
    padding: 0.75rem 0;
    padding-left: 2rem;
    position: relative;
    color: #495057;
    font-size: 0.95rem;
    border-bottom: 1px solid #f8f9fa;
}

.plan-features li:last-child {
    border-bottom: none;
}

.plan-features li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #28a745;
    font-weight: bold;
    font-size: 1.1rem;
}

.plan-action {
    margin-top: auto;
}

.btn-select-plan {
    width: 100%;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-select-plan.btn-primary {
    background: var(--bs-primary);
    border: none;
}

.btn-select-plan.btn-warning {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    border: none;
    color: #1a1a2e;
}

.btn-select-plan:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* FAQ Section */
.faq-section {
    padding: 4rem 0;
}

.faq-section h2 {
    font-size: 2.5rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 3rem;
}

/* Comparison Table */
.comparison-section {
    padding: 4rem 0;
    background: white;
}

.comparison-table {
    max-width: 1000px;
    margin: 0 auto;
}

.comparison-table table {
    width: 100%;
}

.comparison-table th {
    background: #f8f9fa;
    padding: 1rem;
    font-weight: 600;
    text-align: center;
    border: 1px solid #dee2e6;
}

.comparison-table td {
    padding: 1rem;
    text-align: center;
    border: 1px solid #dee2e6;
}

.comparison-table .feature-name {
    text-align: left;
    font-weight: 500;
}

.check-icon {
    color: #28a745;
    font-size: 1.25rem;
}

.x-icon {
    color: #dc3545;
    font-size: 1.25rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .pricing-hero h1 {
        font-size: 2rem;
    }
    
    .pricing-hero p {
        font-size: 1.2rem;
    }
    
    .plan-price {
        font-size: 2.5rem;
    }
    
    .comparison-section {
        display: none; /* Hide comparison table on mobile */
    }
}

/* CTA Section */
.cta-section {
    background: #f8f9fa;
    padding: 4rem 0;
    text-align: center;
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

<!-- Pricing Plans Section -->
<div class="pricing-section">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <?php
            // Display plans
            foreach ($availablePlans as $plan):
                $isRecommended = (strpos(strtolower($plan['account_plan']), 'gold') !== false);
                
                // Determine column width based on number of plans
                $colClass = count($availablePlans) == 2 ? 'col-md-6' : 'col-md-4';
                
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
            <div class="col-12 <?php echo $colClass; ?>">
                <div class="plan-card-wrapper<?php echo $isRecommended ? ' recommended' : ''; ?>">
                    <div class="plan-card h-100<?php echo $isRecommended ? ' recommended' : ''; ?>">
                        
                        <?php if ($isRecommended): ?>
                            <div class="recommended-badge">MOST POPULAR</div>
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
                            <a href="/signup?plan=<?php echo $plan['encoded_id']; ?>" 
                               class="btn btn-select-plan <?php echo $isRecommended ? 'btn-warning' : 'btn-primary'; ?>">
                                Get Started
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Comparison Table Section (Desktop Only) -->
<div class="comparison-section">
    <div class="container">
        <h2 class="text-center mb-4">Compare Plans</h2>
        <div class="comparison-table table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="feature-name">Features</th>
                        <th>Free</th>
                        <th style="background: #fff8e1;">Gold <span class="badge bg-warning text-dark">Popular</span></th>
                        <th>Lifetime</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="feature-name">Birthday Rewards Access</td>
                        <td><i class="bi bi-check-circle-fill check-icon"></i></td>
                        <td><i class="bi bi-check-circle-fill check-icon"></i></td>
                        <td><i class="bi bi-check-circle-fill check-icon"></i></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Number of Businesses</td>
                        <td>Limited</td>
                        <td><?php echo $website['biznames']; ?>+</td>
                        <td><?php echo $website['biznames']; ?>+</td>
                    </tr>
                    <tr>
                        <td class="feature-name">Auto-Enrollment</td>
                        <td><i class="bi bi-x-circle-fill x-icon"></i></td>
                        <td><i class="bi bi-check-circle-fill check-icon"></i></td>
                        <td><i class="bi bi-check-circle-fill check-icon"></i></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Mobile Reminders</td>
                        <td><i class="bi bi-x-circle-fill x-icon"></i></td>
                        <td><i class="bi bi-check-circle-fill check-icon"></i></td>
                        <td><i class="bi bi-check-circle-fill check-icon"></i></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Priority Support</td>
                        <td><i class="bi bi-x-circle-fill x-icon"></i></td>
                        <td><i class="bi bi-check-circle-fill check-icon"></i></td>
                        <td><i class="bi bi-check-circle-fill check-icon"></i></td>
                    </tr>
                    <tr>
                        <td class="feature-name">Lifetime Updates</td>
                        <td><i class="bi bi-x-circle-fill x-icon"></i></td>
                        <td><i class="bi bi-x-circle-fill x-icon"></i></td>
                        <td><i class="bi bi-check-circle-fill check-icon"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="faq-section">
    <div class="container">
        <h2>Frequently Asked Questions</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="pricingFAQ">
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                What's included in the Free plan?
                            </button>
                        </h3>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                The Free plan gives you access to select birthday rewards. You can browse available offers and manually sign up for the ones you want. Perfect for trying out Birthday Gold!
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
                                What's special about the Lifetime plan?
                            </button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                The Lifetime plan is a one-time payment that gives you all Gold benefits forever, plus any new features we add in the future. It's perfect for those who want to lock in their access and never worry about renewals.
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
                                Absolutely! You can start with the Free plan and upgrade to Gold or Lifetime anytime. Your account data and preferences will carry over seamlessly.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="cta-section">
    <div class="container">
        <h2>Ready to Start Collecting Birthday Rewards?</h2>
        <p>Join thousands of members who never miss their birthday treats</p>
        <a href="/signup" class="btn btn-primary btn-lg">Get Started Now</a>
    </div>
</div>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>