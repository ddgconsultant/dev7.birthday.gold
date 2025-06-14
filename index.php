<?php 
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

#-------------------------------------------------------------------------------
# PAGE CONFIGURATION
#-------------------------------------------------------------------------------
$page_title = "Never Miss Another Birthday Reward - Birthday.Gold";
$page_description = "Automatically get enrolled in 500+ birthday reward programs. No forms, no hassle, just freebies. Join 50,000+ members saving $300+ annually.";
$page_keywords = "birthday rewards, birthday freebies, automatic enrollment, birthday programs";
$pagedata['pagetitle']='Birthday Rewards Enrollment - Birthday Gold';
$pagedata['metakeywords']='Birthday Rewards Enrollment, Birthday Rewards, Birthday Gold, Reward Enrollment';
$pagedata['metadescriptions']='Join Birthday Gold for easy Birthday Rewards Enrollment! Discover exclusive birthday rewards & perks. Sign up now for the best reward enrollment deals!';

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
#$bodyattribute['class'] = 'homepage-dark';

#-------------------------------------------------------------------------------
# START PAGE OUTPUT
#-------------------------------------------------------------------------------

$additionalstyles .= '<link href="/public/css/v3/theme.css" rel="stylesheet" id="style-default">
<link rel="stylesheet" href="/public/css/homepage.css">';
$bodyclass='class="d-flex"';



include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');

?>

<!-- section begin ============================-->
<?PHP
if (!empty($enableadminpageeditor)) {   $admin->admineditor('body-1'); }
### ADMIN PAGE EDITOR: START-body-1 ###
?>
<!-- Simple gradient background -->
<div class="page-bg"></div>

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
                    <span>Pick My Rewards</span>
                    <i class="bi bi-check2-circle"></i>
                </a>
                <a href="/discover" class="btn-hero btn-hero-secondary">
                    <i class="bi bi-search"></i>
                    <span>Discover Available Rewards</span>
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
        <?PHP
        
        echo '
        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-number">' . $website['numberofbiz'] . '+</div>
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
';


  $additionalstyles.='
<style > 
.logo-banner-sandbox { overflow: hidden; box-sizing: border-box; position: relative; width: 100%; padding-top: 1rem; height: 100px !important; }
.logo-banner-content-sandbox { display: flex; white-space: nowrap; animation: scroll-sandbox 45s linear infinite; align-items: center; height: 100%; }
/* Faster animation for mobile devices */
@media (max-width:767px) {
.logo-banner-content-sandbox {  animation-duration: 20s;         /* Adjust the duration as needed for mobile */ }
}
.logo-banner-content-sandbox img { height: 48px; margin: 0 40px; filter: brightness(0) invert(1); opacity: 0.9; }
@keyframes scroll-sandbox {
0% { transform: translateX(0); }
100% { transform: translateX(-100%); }
}
</style > <style > .managetext h1.fs-7 { font-size: 2.5rem;     /* Default font size */ }
.managetext p.lead { font-size: 1.5rem;     /* Default font size */ }
@media (max-width:576px) {
.managetext h1.fs-7 { font-size: 1.5rem; }
.managetext p.lead { font-size: 1rem; }
}
@media (min-width:576px) and (max-width:768px) {
.managetext h1.fs-7 { font-size: 2rem; }
.managetext p.lead { font-size: 1.25rem; }
}
@media (min-width:768px) {
.managetext h1.fs-7 { font-size: 2.5rem; }
.managetext p.lead { font-size: 1.5rem; }
}
</style >
';
$usecarousel=1;
$brands[0]='<div class="carousel-logos">
                    <div class="trust-logo">Starbucks</div>
                    <div class="trust-logo">Chipotle</div>
                    <div class="trust-logo">Sephora</div>
                    <div class="trust-logo">Target</div>
                    <div class="trust-logo">AMC</div>
                    <div class="trust-logo">Ulta Beauty</div>
                    <div class="trust-logo">Dunkin\'</div>
                    <div class="trust-logo">Baskin-Robbins</div>
                </div>';
$brands[1]='
<div class="carousel-logos logo-banner-sandbox mb-0  logo-banner-content-sandbox" id="logoBannerContentSandbox">
                <img src="/public/assets/img/logos/bwpng/dqmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/Sonic_Drive-In_2020.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/Godivamark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/KrispyKrememark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/smashburgermark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/nike_580b57fcd9996e24bc43c4f3.png" alt="Logo">    
    <img src="/public/assets/img/logos/bwpng/wingstopmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/atlantabreadmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/qdobamark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/crumblmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/applebees-logo-black-and-white.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/chilismark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/nothingbundtcakesmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/targetmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/wingstopmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/atlantabreadmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/qdobamark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/crumblmark.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/starbucks_58428cc1a6515b1e0ad75ab1.png" alt="Logo">
    <img src="/public/assets/img/logos/bwpng/baskin_robbins_279125.png" alt="Logo">
                </div>';
                ?>
<!-- Trust Section with Logo Carousel -->
<section class="trust-section">
    <div class="trust-container">
        <h3 class="trust-title">Featuring your favorite brands</h3>
        <div class="logo-carousel-wrapper">
            <div class="logo-carousel">
                <!-- First set of logos -->
<?PHP echo $brands[$usecarousel]; ?>
                <!-- Duplicate for seamless loop -->
                <?PHP echo $brands[$usecarousel]; ?>
            </div>
        </div>
    </div>
</section>


<?PHP
echo '
<!-- Features Section -->
<section class="features-section bg-white" id="how-it-works">
    <div class="features-container">
        <div class="section-header">
            <div class="section-badge">
                <i class="bi bi-magic"></i>
                <span>How It Works</span>
            </div>
            <h2 class="section-title">Birthday Rewards on Autopilot</h2>
            <p class="section-subtitle text-black">
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
                    Sign up once with your birthday and basic info. That\'s literally it.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-robot"></i>
                </div>
                <h3 class="feature-title">2. We Do The Work</h3>
                <p class="feature-description">
                    Our AI automatically enrolls you in ' . $website['numberofbiz'] . '+ birthday reward programs.
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
        ';
        ?>
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
   
            <a href="/signup" class="btn-hero btn-hero-primary">
                <span>Sign me up!</span>
                <i class="bi bi-check2-circle"></i>
            </a>

            <a href="/myaccount" class="btn-hero btn-hero-secondary">
            <i class="bi bi-key"></i>
                        <span>Log into My Account</span>               
            </a>
 
    </div>
</section>
<?PHP
### ADMIN PAGE EDITOR: END-body-1 ###
?>
<?php
$footerattribute['postfooter'] = '
<script>
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
include($dir['core_components'] . '/bg_bottom_nav.inc');
$app->outputpage();
?>