<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');



$pagedata['pagetitle']='How Birthday Gold Works - Get Birthday Rewards Automatically';
$pagedata['metakeywords']='How Birthday Gold Works, Birthday Rewards Process, Automatic Birthday Enrollment, Birthday Freebies System';
$pagedata['metadescriptions']='Learn how Birthday Gold automatically enrolls you in birthday reward programs from 500+ businesses. Simple 3-step process to get $500+ in birthday treats!';



include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


$additionalstyles .= '
<style>
/* Hero Section */
.how-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4rem 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.how-hero::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.3; }
}

.how-hero h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
}

.how-hero .lead {
    font-size: 1.5rem;
    font-weight: 300;
    margin-bottom: 2rem;
    opacity: 0.95;
    position: relative;
    z-index: 1;
}

.how-hero .stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
    margin: 2rem 0;
    position: relative;
    z-index: 1;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    display: block;
}

.stat-label {
    font-size: 1rem;
    opacity: 0.9;
}

/* Process Section */
.process-section {
    padding: 5rem 0;
    background: #f8f9fa;
}

.section-header {
    text-align: center;
    margin-bottom: 4rem;
}

.section-header h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 1rem;
}

.section-header p {
    font-size: 1.25rem;
    color: #6c757d;
}

/* Step Cards */
.step-card {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    height: 100%;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.step-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
}

.step-number {
    position: absolute;
    top: -20px;
    right: 20px;
    font-size: 6rem;
    font-weight: 900;
    opacity: 0.05;
    line-height: 1;
}

.step-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    font-size: 2rem;
    color: white;
    position: relative;
    z-index: 1;
}

.step-1 .step-icon { background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%); }
.step-2 .step-icon { background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); }
.step-3 .step-icon { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }

.step-card h3 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #212529;
}

.step-content {
    color: #495057;
}

.step-content ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.step-content li {
    position: relative;
    padding-left: 2rem;
    margin-bottom: 1rem;
    line-height: 1.6;
}

.step-content li::before {
    content: "✓";
    position: absolute;
    left: 0;
    top: 0;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: bold;
    color: white;
}

.step-1 .step-content li::before { background: #f59e0b; }
.step-2 .step-content li::before { background: #3b82f6; }
.step-3 .step-content li::before { background: #22c55e; }

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 4rem 0;
    text-align: center;
}

.cta-section h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #212529;
}

.cta-section p {
    font-size: 1.25rem;
    color: #6c757d;
    margin-bottom: 2rem;
}

.btn-get-started {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 1rem 3rem;
    border-radius: 50rem;
    font-size: 1.125rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-get-started:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    color: white;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .how-hero h1 {
        font-size: 2rem;
    }
    
    .how-hero .lead {
        font-size: 1.125rem;
    }
    
    .how-hero .stats {
        gap: 2rem;
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .section-header h2 {
        font-size: 2rem;
    }
    
    .step-card {
        margin-bottom: 2rem;
    }
    
    .step-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
}

/* Animations */
.fade-in-up {
    opacity: 0;
    transform: translateY(30px);
    animation: fadeInUp 0.6s ease-out forwards;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.step-card {
    animation-delay: 0.2s;
}

.step-card:nth-child(2) .step-card {
    animation-delay: 0.4s;
}

.step-card:nth-child(3) .step-card {
    animation-delay: 0.6s;
}
</style>
';
$columntag='col-xl-4';

echo '
<!-- Hero Section -->
<div class="how-hero">
    <div class="container">
        <h1 class="fade-in-up">Turn Your Birthday Into Pure Gold</h1>
        <p class="lead fade-in-up">The easiest way to collect birthday rewards from your favorite brands</p>
        
        <div class="stats fade-in-up">
            <div class="stat-item">
                <span class="stat-number">' . $website['numberofbiz'] . '+</span>
                <span class="stat-label">Businesses</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">50K+</span>
                <span class="stat-label">Happy Members</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">$500+</span>
                <span class="stat-label">Avg. Value</span>
            </div>
        </div>
    </div>
</div>

<!-- Process Section -->
<div class="process-section">
    <div class="container">
        <div class="section-header">
            <h2>How It Works</h2>
            <p>Get started in minutes with our simple 3-step process</p>
        </div>
        
        <div class="row g-4">
            <!-- Step 1 -->
            <div class="'.$columntag.'">
                <div class="step-card step-1 fade-in-up">
                    <span class="step-number">1</span>
                    <div class="step-icon">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <h3>Sign Up & Set Preferences</h3>
                    <div class="step-content">
                        <ul>
                            <li>Create your free account in seconds</li>
                            <li>Add your birthday and preferences</li>
                            <li>Choose between Free or Premium plans</li>
                            <li>Set dietary restrictions and interests</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Step 2 -->
            <div class="'.$columntag.'">
                <div class="step-card step-2 fade-in-up">
                    <span class="step-number">2</span>
                    <div class="step-icon">
                        <i class="bi bi-ui-checks-grid"></i>
                    </div>
                    <h3>Select Your Rewards</h3>
                    <div class="step-content">
                        <ul>
                            <li>Browse ' . $website['numberofbiz'] . '+ available businesses</li>
                            <li>Pick rewards that match your interests</li>
                            <li>We handle all the enrollments for you</li>
                            <li>Track enrollment status in real-time</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Step 3 -->
            <div class="'.$columntag.'">
                <div class="step-card step-3 fade-in-up">
                    <span class="step-number">3</span>
                    <div class="step-icon">
                        <i class="bi bi-gift-fill"></i>
                    </div>
                    <h3>Enjoy Your Birthday</h3>
                    <div class="step-content">
                        <ul>
                            <li>Get reminders before rewards arrive</li>
                            <li>Access all rewards in one dashboard</li>
                            <li>Redeem at stores or online easily</li>
                            <li>Celebrate with treats worth $500+</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="cta-section">
    <div class="container">
        <h2>Ready to Make Your Birthday Special?</h2>
        <p>Join thousands who celebrate their birthday the Birthday.Gold way</p>
        <a href="/signup" class="btn-get-started">Get Started Free</a>
    </div>
</div>
';


// Add scroll animations
$footerattribute['postfooter'] = '
<script>
// Fade in animation on scroll
document.addEventListener("DOMContentLoaded", function() {
    const fadeElements = document.querySelectorAll(".fade-in-up");
    
    const fadeInObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = "running";
                fadeInObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    fadeElements.forEach(el => {
        el.style.animationPlayState = "paused";
        fadeInObserver.observe(el);
    });
});
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
