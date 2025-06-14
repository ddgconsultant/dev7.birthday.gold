<?php 
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

#-------------------------------------------------------------------------------
# PAGE CONFIGURATION
#-------------------------------------------------------------------------------
$page_title = "Never Miss Another Birthday Reward - Birthday.Gold";
$page_description = "Automatically get enrolled in 500+ birthday reward programs. No forms, no hassle, just freebies. Join 50,000+ members saving $300+ annually.";
$page_keywords = "birthday rewards, birthday freebies, automatic enrollment, birthday programs";

#-------------------------------------------------------------------------------
# TEMPLATE/LAYOUT FLAGS - Try different variables
#-------------------------------------------------------------------------------
$template = 'default';          // Use default template
$layout = 'standard';           // Use standard layout
$page_template = 'default';     // Alternative template variable
$page_layout = 'standard';      // Alternative layout variable
$show_navigation = true;        // Show navigation
$include_header = true;         // Include header
$include_footer = true;         // Include footer

#-------------------------------------------------------------------------------
# HEADER/FOOTER FLAGS - Ensure standard header/footer are shown
#-------------------------------------------------------------------------------
$headerattribute['rawheader'] = false;  // FALSE = show standard header
$footerattribute['rawfooter'] = false;  // FALSE = show standard footer

#-------------------------------------------------------------------------------
# ADDITIONAL HEAD CONTENT
#-------------------------------------------------------------------------------
$headerattribute['additional_head'] = '
<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
';

#-------------------------------------------------------------------------------
# CUSTOM BODY CLASS
#-------------------------------------------------------------------------------
$bodyattribute['class'] = 'homepage-dark';

#-------------------------------------------------------------------------------
# ADDITIONAL CSS FOR HOMEPAGE - Include all critical styles inline
#-------------------------------------------------------------------------------
$additionalstyles = '
<style>
/* Homepage Dark Theme Critical Styles */
.homepage-dark {
    background: #0a0a0a;
    color: #fff;
}

/* Force dark theme header styles with maximum specificity */
body.homepage-dark header.top-header,
body.homepage-dark .top-header,
.homepage-dark header.top-header,
.homepage-dark .top-header {
    background: rgba(10, 10, 10, 0.95) !important;
    background-color: rgba(10, 10, 10, 0.95) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    z-index: 9999 !important;
}

/* Ensure header is visible */
body.homepage-dark header.top-header {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Style all nav links in header */
body.homepage-dark .top-header a.nav-link,
body.homepage-dark .center-nav a,
body.homepage-dark .top-header .nav-link {
    color: rgba(255, 255, 255, 0.8) !important;
    font-weight: 500 !important;
}

body.homepage-dark .top-header a.nav-link:hover,
body.homepage-dark .center-nav a:hover,
body.homepage-dark .top-header .nav-link:hover {
    color: #FFD700 !important;
    text-decoration: none !important;
}

/* Style the login button specifically */
body.homepage-dark .top-header .login-btn,
body.homepage-dark .top-header a.login-btn,
body.homepage-dark a.login-btn {
    background: linear-gradient(135deg, #FFD700, #FFA500) !important;
    background-image: linear-gradient(135deg, #FFD700, #FFA500) !important;
    color: #1a1a2e !important;
    border: none !important;
    padding: 0.5rem 1.5rem !important;
    border-radius: 25px !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    display: inline-block !important;
    transition: all 0.3s ease !important;
}

/* Make the logo text visible */
body.homepage-dark .header-logo {
    position: relative;
}

body.homepage-dark .header-logo img {
    opacity: 0.1; /* Make original logos very faint */
}

body.homepage-dark .header-logo::after {
    content: "Birthday.Gold";
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #FFD700, #FFA500);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Ensure main content starts below header */
body.homepage-dark main#main-content {
    margin-top: 80px !important;
    position: relative !important;
}

.page-bg {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #1a1a2e 0%, #0f0f0f 50%, #16213e 100%);
    z-index: -2;
}

.hero {
    padding-top: 20px;
}

/* DEBUG: Force header to be visible with red background */
header.top-header {
    background: #ff0000 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 99999 !important;
    height: 70px !important;
    display: block !important;
    visibility: visible !important;
}

/* Then apply dark theme over it */
body.homepage-dark header.top-header {
    background: rgba(10, 10, 10, 0.95) !important;
}
</style>
<link rel="stylesheet" href="/public/css/homepage.css">
<link rel="stylesheet" href="/public/css/homepage2.css">
';

#-------------------------------------------------------------------------------
# START PAGE OUTPUT
#-------------------------------------------------------------------------------
include($dir['core_components'] . '/bg_pagestart.inc');
// Don't include header here - it's already in the page structure
?>

<!-- Simple gradient background -->
<div class="page-bg"></div>

<!-- Main content wrapper -->
<div class="main-content">

<!-- Floating Elements (loaded after page) -->
<div class="floating-elements" id="floatingElements">
    <div class="float-element" style="left: 10%; animation-delay: 0s;">🎂</div>
    <div class="float-element" style="left: 30%; animation-delay: 5s;">🎉</div>
    <div class="float-element" style="left: 50%; animation-delay: 10s;">🎈</div>
    <div class="float-element" style="left: 70%; animation-delay: 15s;">🎊</div>
    <div class="float-element" style="left: 90%; animation-delay: 20s;">✨</div>
</div>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-badge">
            <i class="bi bi-stars"></i>
            <span>Join 50,000+ Birthday VIPs</span>
        </div>
        
        <h1 class="hero-title">
            Never Miss Another<br>
            <span class="highlight">Birthday Reward</span>
        </h1>
        
        <p class="hero-subtitle">
            We automatically enroll you in hundreds of birthday programs.<br>
            No forms. No hassle. Just freebies.
        </p>
        
        <div class="hero-cta">
            <?php if (!empty($current_user_data['user_id'])): ?>
                <a href="/account/rewards" class="btn-hero btn-hero-primary">
                    <span>View My Rewards</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
                <a href="/browse" class="btn-hero btn-hero-secondary">
                    <i class="bi bi-search"></i>
                    <span>Browse All Rewards</span>
                </a>
            <?php else: ?>
                <a href="/signup-mobile" class="btn-hero btn-hero-primary">
                    <span>Start Free Today</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
                <a href="#demo" class="btn-hero btn-hero-secondary">
                    <i class="bi bi-play-circle"></i>
                    <span>Watch Demo</span>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-number">500+</div>
                <div class="stat-label">Restaurants</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">$300+</div>
                <div class="stat-label">Avg. Savings</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">50K+</div>
                <div class="stat-label">Happy Members</div>
            </div>
        </div>
    </div>
</section>

<!-- Trust Section with Logo Carousel -->
<section class="trust-section">
    <div class="trust-container">
        <h3 class="trust-title">Partnered with your favorite brands</h3>
        <div class="logo-carousel-wrapper">
            <div class="logo-carousel">
                <!-- First set of logos -->
                <div class="carousel-logos">
                    <div class="trust-logo">Starbucks</div>
                    <div class="trust-logo">Chipotle</div>
                    <div class="trust-logo">Sephora</div>
                    <div class="trust-logo">Target</div>
                    <div class="trust-logo">AMC</div>
                    <div class="trust-logo">Ulta Beauty</div>
                    <div class="trust-logo">Dunkin'</div>
                    <div class="trust-logo">Baskin-Robbins</div>
                </div>
                <!-- Duplicate for seamless loop -->
                <div class="carousel-logos">
                    <div class="trust-logo">Starbucks</div>
                    <div class="trust-logo">Chipotle</div>
                    <div class="trust-logo">Sephora</div>
                    <div class="trust-logo">Target</div>
                    <div class="trust-logo">AMC</div>
                    <div class="trust-logo">Ulta Beauty</div>
                    <div class="trust-logo">Dunkin'</div>
                    <div class="trust-logo">Baskin-Robbins</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section" id="how-it-works">
    <div class="features-container">
        <div class="section-header">
            <div class="section-badge">
                <i class="bi bi-magic"></i>
                <span>How It Works</span>
            </div>
            <h2 class="section-title">Birthday Rewards on Autopilot</h2>
            <p class="section-subtitle">
                Three simple steps to unlock hundreds of birthday freebies
            </p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-person-check"></i>
                </div>
                <h3 class="feature-title">1. Create Your Profile</h3>
                <p class="feature-description">
                    Sign up once with your birthday and basic info. That's literally it.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-robot"></i>
                </div>
                <h3 class="feature-title">2. We Do The Work</h3>
                <p class="feature-description">
                    Our AI automatically enrolls you in 500+ birthday reward programs.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-gift"></i>
                </div>
                <h3 class="feature-title">3. Enjoy Your Rewards</h3>
                <p class="feature-description">
                    Get notified when rewards are ready. Show your phone, get free stuff!
                </p>
            </div>
        </div>
        
        <!-- Phone Demo -->
        <div class="phone-demo" id="demo">
            <div class="phone-frame">
                <div class="phone-screen">
                    <div class="demo-content">
                        <div class="demo-header">
                            <div class="demo-logo">Birthday.Gold</div>
                            <p class="demo-subtitle">Your Birthday Month Rewards</p>
                        </div>
                        
                        <div class="demo-rewards">
                            <div class="demo-reward-card">
                                <div class="demo-reward-logo">☕</div>
                                <div class="demo-reward-info">
                                    <h6>Starbucks</h6>
                                    <p>Free Birthday Drink</p>
                                </div>
                            </div>
                            
                            <div class="demo-reward-card">
                                <div class="demo-reward-logo">🌯</div>
                                <div class="demo-reward-info">
                                    <h6>Chipotle</h6>
                                    <p>Free Burrito</p>
                                </div>
                            </div>
                            
                            <div class="demo-reward-card">
                                <div class="demo-reward-logo">💄</div>
                                <div class="demo-reward-info">
                                    <h6>Sephora</h6>
                                    <p>Birthday Gift Set</p>
                                </div>
                            </div>
                            
                            <div class="demo-reward-card">
                                <div class="demo-reward-logo">🎬</div>
                                <div class="demo-reward-info">
                                    <h6>AMC Theatres</h6>
                                    <p>Free Large Popcorn</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="cta-container">
        <h2 class="cta-title">
            Ready to Celebrate<br>
            <span class="highlight">Every Birthday?</span>
        </h2>
        <p class="cta-subtitle">
            Join thousands who never miss a birthday freebie again
        </p>
        <?php if (!empty($current_user_data['user_id'])): ?>
            <a href="/account/rewards" class="btn-hero btn-hero-primary">
                <span>View My Rewards</span>
                <i class="bi bi-arrow-right"></i>
            </a>
        <?php else: ?>
            <a href="/signup-mobile" class="btn-hero btn-hero-primary">
                <span>Start Your Free Account</span>
                <i class="bi bi-arrow-right"></i>
            </a>
        <?php endif; ?>
    </div>
</section>

</div><!-- End main-content -->

<?php
$footerattribute['postfooter'] = '
<script>
// Ensure header is styled on page load
document.addEventListener("DOMContentLoaded", function() {
    // Move header outside of main if needed
    const header = document.querySelector(".top-header");
    const main = document.querySelector("main");
    if (header && main && header.parentElement === main) {
        document.body.insertBefore(header, main);
    }
    
    // Apply dark theme to header
    if (header) {
        header.style.background = "rgba(10, 10, 10, 0.95)";
        header.style.borderBottom = "1px solid rgba(255, 255, 255, 0.1)";
        header.style.position = "fixed";
        header.style.top = "0";
        header.style.width = "100%";
        header.style.zIndex = "1000";
    }
});

// Header scroll effect for dark theme
window.addEventListener("scroll", () => {
    const header = document.querySelector(".top-header");
    if (header && window.scrollY > 50) {
        header.classList.add("scrolled");
    } else if (header) {
        header.classList.remove("scrolled");
    }
});

// Defer floating elements animation
window.addEventListener("load", () => {
    document.getElementById("floatingElements").classList.add("loaded");
});

// Simple fade-in for hero elements
const fadeElements = document.querySelectorAll(".hero-badge, .hero-title, .hero-subtitle, .hero-cta, .hero-stats");
fadeElements.forEach((el, index) => {
    el.style.opacity = "0";
    el.style.transform = "translateY(20px)";
    setTimeout(() => {
        el.style.transition = "all 0.6s ease-out";
        el.style.opacity = "1";
        el.style.transform = "translateY(0)";
    }, 100 * index);
});

// Lazy load phone demo
const phoneDemo = document.getElementById("demo");
if (phoneDemo) {
    phoneDemo.style.opacity = "0";
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.transition = "opacity 0.6s ease-out";
                entry.target.style.opacity = "1";
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    observer.observe(phoneDemo);
}
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>