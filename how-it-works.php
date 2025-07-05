<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


$avg_reward_value = 300; // This can be changed easily

$pagedata['pagetitle']='How Birthday Gold Works - Get Birthday Rewards Automatically';
$pagedata['metakeywords']='How Birthday Gold Works, Birthday Rewards Process, Automatic Birthday Enrollment, Birthday Freebies System';
$pagedata['metadescriptions']='Learn how Birthday Gold automatically enrolls you in birthday reward programs from ' . $website['numberofbiz'] . '+ businesses. Simple 3-step process to get $' . $avg_reward_value . '+ in birthday treats!';



include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');


// Add animation libraries in head
$additionalstyles .= '
<script src="/public/js/waypoints.min.js"></script>
<script src="/public/js/jquery.counterup.min.js"></script>
<script src="/public/js/wow.min.js"></script>
';

$additionalstyles .= '
<style>
/* Keep only highlight styling for accents */
.highlight {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 800;
}

/* Hero Section */
.how-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #0f0f0f 50%, #16213e 100%);
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
    0%, 100% { transform: scale(1); opacity: 0.7; }
    50% { transform: scale(1.1); opacity: 0.5; }
}

.how-hero h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
    color: #fff;
    letter-spacing: 0.5px;
}

.how-hero .lead {
    font-size: 1.5rem;
    font-weight: 400;
    margin-bottom: 2rem;
    color: #fff;
    position: relative;
    z-index: 1;
    opacity: 1;
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
    font-weight: 800;
    display: block;
    color: #FFD700;
}

.stat-label {
    font-size: 1rem;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-weight: 500;
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

.step-1 .step-icon { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); }
.step-2 .step-icon { background: linear-gradient(135deg, #7209b7 0%, #f72585 100%); }
.step-3 .step-icon { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); }

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

.step-1 .step-content li::before { background: #FFD700; }
.step-2 .step-content li::before { background: #7209b7; }
.step-3 .step-content li::before { background: #FFD700; }

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
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #1a1a2e;
    border: none;
    padding: 1rem 3rem;
    border-radius: 50rem;
    font-size: 1.125rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
}

.btn-get-started:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 215, 0, 0.5);
    color: #1a1a2e;
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
        <h1 class="fade-in-up">Turn Your Birthday Into <span class="highlight">Pure Gold</span></h1>
        <p class="lead fade-in-up">The easiest way to collect <span style="color: #FFD700; font-weight: 600;">birthday rewards</span> from your favorite brands</p>
        
   <div class="stats fade-in-up">
    <div class="stat-item">
        <span class="stat-number counter">' . $website['numberofbiz'] . '+</span>
        <span class="stat-label">Businesses</span>
    </div>
    <div class="stat-item">
        <span class="stat-number counter">50000</span>
        <!-- Two spans: large and small screen -->
        <span class="stat-label d-none d-sm-inline">Happy Members</span>
        <span class="stat-label d-inline d-sm-none">Members</span>
    </div>
    <div class="stat-item">
        <span class="stat-number counter">$' . $avg_reward_value . '+</span>
        <!-- Two spans: large and small screen -->
        <span class="stat-label d-none d-sm-inline">Avg. Value</span>
        <span class="stat-label d-inline d-sm-none">Value</span>
    </div>
</div>

    </div>
</div>

<!-- Process Section -->
<div class="process-section">
    <div class="container">
        <div class="section-header">
            <h2>How It Works</h2>
            <p>Get started in <span class="highlight">minutes</span> with our simple 3-step process</p>
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
                            <li>Celebrate with treats worth $' . $avg_reward_value . '+</li>
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


// Initialize animations
$footerattribute['postfooter'] = '
<script>
// Wait for all scripts to load
window.addEventListener('load', function() {
    // Small delay to ensure all scripts are ready
    setTimeout(function() {
        // Debug checks
        console.log("jQuery loaded:", typeof jQuery !== "undefined");
        console.log("Waypoints loaded:", typeof jQuery.fn.waypoint !== "undefined");
        console.log("CounterUp loaded:", typeof jQuery.fn.counterUp !== "undefined");
        console.log("Counter elements found:", jQuery(".counter").length);
        
        // Initialize CounterUp
        if (typeof jQuery !== "undefined" && typeof jQuery.fn.counterUp !== "undefined") {
            jQuery(".counter").counterUp({
                delay: 10,
                time: 2000
            });
            console.log("CounterUp initialized successfully");
        } else {
            console.error("Required libraries not loaded");
        }
        
        // Initialize WOW.js if available
        if (typeof WOW !== "undefined") {
            new WOW().init();
            console.log("WOW.js initialized");
        }
    }, 100);
});

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
