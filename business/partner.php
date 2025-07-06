<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page metadata
$pagedata['pagetitle'] = 'Become a Partner - Birthday Gold Business';
$pagedata['metakeywords'] = 'Birthday Gold Partner, Business Birthday Rewards, Birthday Marketing, Customer Retention, Birthday Promotions';
$pagedata['metadescriptions'] = 'Partner with Birthday Gold to offer birthday rewards to your customers. Increase customer loyalty and drive repeat business with our automated birthday marketing platform.';

// Header flush for better spacing
$header_flush = true;

// Additional styles
$additionalstyles = '
<link rel="stylesheet" href="/public/css/common-hero.css">
<style>
/* Partner page specific styles */
.partner-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #0f0f0f 50%, #16213e 100%);
    color: white;
    padding: 5rem 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.partner-hero::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

.partner-hero h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
}

.partner-hero p {
    font-size: 1.5rem;
    margin-bottom: 2rem;
    position: relative;
    z-index: 1;
    opacity: 0.9;
}

.btn-partner-cta {
    background: #198754;
    color: white;
    padding: 1rem 2.5rem;
    border-radius: 50px;
    font-size: 1.2rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
}

.btn-partner-cta:hover {
    background: #157347;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
    color: white;
}

/* Benefits section */
.benefits-section {
    padding: 4rem 0;
    background: #f8f9fa;
}

.benefit-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    height: 100%;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    text-align: center;
}

.benefit-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
}

.benefit-icon {
    font-size: 3rem;
    color: #198754;
    margin-bottom: 1rem;
}

.benefit-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #212529;
}

.benefit-text {
    color: #6c757d;
    line-height: 1.6;
}

/* How it works section */
.how-it-works-section {
    padding: 4rem 0;
}

.step-card {
    position: relative;
    padding: 2rem;
    margin-bottom: 2rem;
}

.step-number {
    position: absolute;
    left: -20px;
    top: 20px;
    width: 60px;
    height: 60px;
    background: #198754;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
}

.step-content {
    margin-left: 60px;
}

.step-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.step-description {
    color: #6c757d;
    line-height: 1.6;
}

/* Stats section */
.stats-section {
    background: #212529;
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.stat-item {
    margin-bottom: 2rem;
}

.stat-number {
    font-size: 3rem;
    font-weight: 700;
    color: #198754;
    display: block;
}

.stat-label {
    font-size: 1.2rem;
    opacity: 0.9;
}

/* CTA section */
.cta-section {
    background: linear-gradient(135deg, #198754 0%, #157347 100%);
    color: white;
    padding: 4rem 0;
    text-align: center;
}

.cta-section h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.cta-section p {
    font-size: 1.3rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.btn-white {
    background: white;
    color: #198754;
    padding: 1rem 2.5rem;
    border-radius: 50px;
    font-size: 1.2rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-white:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
    color: #198754;
}

/* Features grid */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.feature-item {
    display: flex;
    align-items: start;
    gap: 1rem;
}

.feature-icon {
    flex-shrink: 0;
    font-size: 1.5rem;
    color: #198754;
    margin-top: 0.25rem;
}

.feature-content h4 {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
}

.feature-content p {
    color: #6c757d;
    margin: 0;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .partner-hero h1 {
        font-size: 2.5rem;
    }
    
    .partner-hero p {
        font-size: 1.2rem;
    }
    
    .step-number {
        position: static;
        margin: 0 auto 1rem;
    }
    
    .step-content {
        margin-left: 0;
        text-align: center;
    }
    
    .stat-number {
        font-size: 2.5rem;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="partner-hero">
    <div class="container">
        <h1 class="text-white">Become a Birthday.Gold Partner</h1>
        <p>Join thousands of businesses offering birthday rewards to build customer loyalty</p>
        <a href="#apply-form" class="btn-partner-cta">Apply to Become a Partner</a>
    </div>
</div>

<!-- Benefits Section -->
<section class="benefits-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold">Why Partner with Birthday Gold?</h2>
            <p class="lead text-muted">Increase customer retention and drive repeat business</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="benefit-card">
                    <i class="bi bi-people-fill benefit-icon"></i>
                    <h3 class="benefit-title">Reach New Customers</h3>
                    <p class="benefit-text">Access our growing community of birthday celebrants actively looking for special offers and experiences.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-card">
                    <i class="bi bi-graph-up-arrow benefit-icon"></i>
                    <h3 class="benefit-title">Increase Revenue</h3>
                    <p class="benefit-text">Birthday customers spend 2.5x more on average and often bring friends and family along.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-card">
                    <i class="bi bi-heart-fill benefit-icon"></i>
                    <h3 class="benefit-title">Build Loyalty</h3>
                    <p class="benefit-text">Create memorable experiences that turn birthday visitors into lifelong customers.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold">How It Works</h2>
            <p class="lead text-muted">Get started in three simple steps</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3 class="step-title">Apply Online</h3>
                        <p class="step-description">Fill out our simple application form with your business details and the birthday offer you want to provide.</p>
                    </div>
                </div>
                
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3 class="step-title">Get Approved</h3>
                        <p class="step-description">Our team reviews your application and works with you to optimize your birthday offer for maximum impact.</p>
                    </div>
                </div>
                
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3 class="step-title">Start Receiving Customers</h3>
                        <p class="step-description">Your offer goes live on our platform and birthday celebrants start redeeming at your business.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <span class="stat-number">500+</span>
                    <span class="stat-label">Partner Businesses</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <span class="stat-number">50K+</span>
                    <span class="stat-label">Active Members</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <span class="stat-number">2.5x</span>
                    <span class="stat-label">Average Spend Increase</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <span class="stat-number">85%</span>
                    <span class="stat-label">Return Rate</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold">Partner Benefits & Features</h2>
            <p class="lead text-muted">Everything you need to succeed</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-item">
                <i class="bi bi-shield-check feature-icon"></i>
                <div class="feature-content">
                    <h4>Verified Birthdays</h4>
                    <p>We verify all birthdays to ensure legitimate redemptions and prevent fraud.</p>
                </div>
            </div>
            
            <div class="feature-item">
                <i class="bi bi-bar-chart feature-icon"></i>
                <div class="feature-content">
                    <h4>Analytics Dashboard</h4>
                    <p>Track redemptions, customer demographics, and ROI with our comprehensive analytics.</p>
                </div>
            </div>
            
            <div class="feature-item">
                <i class="bi bi-megaphone feature-icon"></i>
                <div class="feature-content">
                    <h4>Marketing Support</h4>
                    <p>We promote your business through our app, website, email campaigns, and social media.</p>
                </div>
            </div>
            
            <div class="feature-item">
                <i class="bi bi-calendar-check feature-icon"></i>
                <div class="feature-content">
                    <h4>Flexible Offers</h4>
                    <p>Create any type of birthday offer - discounts, free items, upgrades, or experiences.</p>
                </div>
            </div>
            
            <div class="feature-item">
                <i class="bi bi-headset feature-icon"></i>
                <div class="feature-content">
                    <h4>Dedicated Support</h4>
                    <p>Get help from our partner success team whenever you need it.</p>
                </div>
            </div>
            
            <div class="feature-item">
                <i class="bi bi-cash-coin feature-icon"></i>
                <div class="feature-content">
                    <h4>No Upfront Costs</h4>
                    <p>Join for free - we only succeed when you succeed.</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="/business/api" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-code-slash me-2"></i>View API Documentation
            </a>
        </div>
    </div>
</section>

<!-- Application Form Section -->
<section class="py-5 bg-light" id="apply-form">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Partner Application</h2>
                        <p class="text-center text-muted mb-5">Tell us about your business and birthday offer</p>
                        
                        <form action="/business/partner-submit.php" method="POST">
                            <?php echo $display->inputcsrf_token(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="business_name" class="form-label">Business Name *</label>
                                    <input type="text" class="form-control" id="business_name" name="business_name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="business_type" class="form-label">Business Type *</label>
                                    <select class="form-select" id="business_type" name="business_type" required>
                                        <option value="">Select Type</option>
                                        <option value="restaurant">Restaurant</option>
                                        <option value="retail">Retail Store</option>
                                        <option value="entertainment">Entertainment</option>
                                        <option value="beauty">Beauty & Spa</option>
                                        <option value="fitness">Fitness & Wellness</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="contact_name" class="form-label">Contact Name *</label>
                                    <input type="text" class="form-control" id="contact_name" name="contact_name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="contact_email" class="form-label">Contact Email *</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="contact_phone" class="form-label">Contact Phone *</label>
                                    <input type="tel" class="form-control" id="contact_phone" name="contact_phone" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="locations" class="form-label">Number of Locations</label>
                                    <select class="form-select" id="locations" name="locations">
                                        <option value="1">1 Location</option>
                                        <option value="2-5">2-5 Locations</option>
                                        <option value="6-10">6-10 Locations</option>
                                        <option value="11+">11+ Locations</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="website" name="website" placeholder="https://www.example.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="birthday_offer" class="form-label">Proposed Birthday Offer *</label>
                                <textarea class="form-control" id="birthday_offer" name="birthday_offer" rows="3" required 
                                          placeholder="Describe what you would like to offer birthday customers (e.g., 20% off, free dessert, etc.)"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="additional_info" class="form-label">Additional Information</label>
                                <textarea class="form-control" id="additional_info" name="additional_info" rows="3" 
                                          placeholder="Any other details you'd like to share about your business or offer"></textarea>
                            </div>
                            
                            <div class="form-check mb-4">
                                <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                                <label class="form-check-label" for="agree_terms">
                                    I agree to the Birthday Gold <a href="/legalhub/partnerterms" target="_blank">Partner Terms & Conditions</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-success btn-lg w-100">Submit Application</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2>Ready to Grow Your Business?</h2>
        <p>Join Birthday Gold today and start connecting with birthday celebrants</p>
        <a href="#apply-form" class="btn-white">Apply Now</a>
    </div>
</section>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>