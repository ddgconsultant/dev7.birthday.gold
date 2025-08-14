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

$additionalstyles .= '<link href="' . cssUrl('/public/css/v3/theme.css') . '" rel="stylesheet" id="style-default">
<link rel="stylesheet" href="' . cssUrl('/public/css/homepage.css') . '">';
$bodycontentclass='class="d-flex"';
$header_flush = true; // Homepage should have content flush with header

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

<!-- Hero Section -->
<section class="hero">
    <!-- Floating Elements (loaded after page) -->
    <div class="floating-elements" id="floatingElements">
        <div class="float-element" style="left: 10%; animation-delay: 0s;">🎂</div>
        <div class="float-element" style="left: 30%; animation-delay: 5s;">🎉</div>
        <div class="float-element" style="left: 50%; animation-delay: 10s;">🎈</div>
        <div class="float-element" style="left: 70%; animation-delay: 15s;">🎊</div>
        <div class="float-element" style="left: 90%; animation-delay: 20s;">✨</div>
    </div>
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
            <span class="tagline-main">You Pick the Birthday Rewards,<br class="tagline-break"> We Take Care of Enrollment</span><br>
            No forms. No hassle. Just freebies.
        </p>
        
        <div class="hero-cta">
            <?php if (!empty($current_user_data['user_id'])): ?>
                <a href="/myaccount/rewards" class="btn-hero btn-hero-primary">
                    <span>Pick My Rewards</span>
                    <i class="bi bi-check2-circle"></i>
                </a>
                <a href="/discover" class="btn-hero btn-hero-secondary">
                    <i class="bi bi-search"></i>
                    <span>Discover Available Rewards</span>
                </a>
            <?php else: ?>
                <a href="/signup" class="btn-hero btn-hero-primary">
                    <span>Start Free Today</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
                <a href="#demo" class="btn-hero btn-hero-secondary">
                    <i class="bi bi-play-circle"></i>
                    <span>See How Simple It Is</span>
                </a>
            <?php endif; ?>
        </div>
        <?PHP
        
        echo '
        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-number">' . $website['numberofbiz'] . '+</div>
                <div class="stat-label">Popular Brands</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">$300+</div>
                <div class="stat-label">Average Savings</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">50K+</div>
                <div class="stat-label">Happy Members</div>
            </div>
        </div>
    </div>
</section>
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
                    Select from over '. $website['numberofbiz'] . '+ Birthday Reward Programs, and Birthday.Gold takes care of the rest.
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
        <!-- Animated Demo Carousel -->
        <div class="demo-carousel-container" id="demo">
            <!-- Phone Frame -->
            <div class="demo-phone-frame">
                <!-- Phone Notch/Speaker -->
                <div class="phone-notch"></div>
                
                <!-- Phone Screen -->
                <div class="demo-carousel">
                    <!-- Screen 1: Sign Up -->
                <div class="demo-screen active">
                    <div class="demo-device">
                        <div class="demo-device-content">
                            <div class="demo-step-indicator">Step 1 of 3</div>
                            <div class="demo-icon">👤</div>
                            <h3>Quick Sign Up</h3>
                            <div class="demo-form-preview">
                                <div class="form-field">Name: John Smith</div>
                                <div class="form-field">Birthday: March 15</div>
                                <div class="form-field">Email: john@email.com</div>
                            </div>
                            <p class="demo-description">One-time registration.<br>Takes less than 60 seconds!</p>
                        </div>
                    </div>
                </div>

                <!-- Screen 2: Select Rewards -->
                <div class="demo-screen">
                    <div class="demo-device">
                        <div class="demo-device-content">
                            <div class="demo-step-indicator">Step 2 of 3</div>
                            <div class="demo-icon">✨</div>
                            <h3>Choose Your Rewards</h3>
                            <div class="demo-brand-grid">
                                <div class="brand-tile selected">Starbucks ☕</div>
                                <div class="brand-tile selected">Chipotle 🌯</div>
                                <div class="brand-tile selected">Target 🎯</div>
                                <div class="brand-tile">Sephora 💄</div>
                                <div class="brand-tile">Nike 👟</div>
                                <div class="brand-tile">Amazon 📦</div>
                            </div>
                            <p class="demo-description">Pick from <?php echo $website['numberofbiz']; ?>+ brands.<br>We handle all enrollments!</p>
                        </div>
                    </div>
                </div>

                <!-- Screen 3: Enjoy Rewards -->
                <div class="demo-screen">
                    <div class="demo-device">
                        <div class="demo-device-content">
                            <div class="demo-step-indicator">Step 3 of 3</div>
                            <div class="demo-icon">🎉</div>
                            <h3>Birthday Month Arrives!</h3>
                            <div class="demo-notifications">
                                <div class="notification-card">
                                    <span class="notif-icon">🎂</span>
                                    <div class="notif-text">
                                        <strong>Happy Birthday Month!</strong>
                                        <small>15 rewards are ready</small>
                                    </div>
                                </div>
                                <div class="reward-preview">
                                    <div class="reward-item">☕ Starbucks - Free Drink</div>
                                    <div class="reward-item">🌯 Chipotle - Free Burrito</div>
                                    <div class="reward-item">🎬 AMC - Free Popcorn</div>
                                    <div class="reward-item">+ 12 more rewards...</div>
                                </div>
                            </div>
                            <p class="demo-description">Average member saves $300+<br>in birthday rewards!</p>
                        </div>
                    </div>
                </div>
                </div>
                
                <!-- Progress Bar (inside phone) -->
                <div class="demo-progress">
                    <div class="demo-progress-bar"></div>
                </div>
            </div>
            
            <!-- Navigation Dots (outside phone) -->
            <div class="demo-nav">
                <span class="demo-dot active" data-slide="0"></span>
                <span class="demo-dot" data-slide="1"></span>
                <span class="demo-dot" data-slide="2"></span>
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
   
            <a href="/signup" class="btn-hero btn-hero-primary mb-4">
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
    const floatingEl = document.getElementById("floatingElements");
    if (floatingEl) {
        floatingEl.classList.add("loaded");
    }
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

// Demo Carousel Animation
const demoCarousel = document.querySelector(".demo-carousel");
if (demoCarousel) {
    const screens = document.querySelectorAll(".demo-screen");
    const dots = document.querySelectorAll(".demo-dot");
    const progressBar = document.querySelector(".demo-progress-bar");
    let currentSlide = 0;
    let autoPlayInterval;
    let progressInterval;

    function showSlide(index) {
        // Reset all screens
        screens.forEach((screen, i) => {
            screen.classList.remove("active", "prev");
            if (i < index) {
                screen.classList.add("prev");
            }
        });
        
        // Set active screen
        screens[index].classList.add("active");
        
        // Update dots
        dots.forEach((dot, i) => {
            dot.classList.toggle("active", i === index);
        });
        
        // Reset progress bar
        if (progressBar) {
            progressBar.style.animation = "none";
            setTimeout(() => {
                progressBar.style.animation = "progress 6s linear infinite";
            }, 10);
        }
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % screens.length;
        showSlide(currentSlide);
    }

    // Auto-play carousel
    function startAutoPlay() {
        autoPlayInterval = setInterval(nextSlide, 6000);
    }

    function stopAutoPlay() {
        clearInterval(autoPlayInterval);
    }

    // Click on dots to navigate
    dots.forEach((dot, index) => {
        dot.addEventListener("click", () => {
            currentSlide = index;
            showSlide(currentSlide);
            stopAutoPlay();
            startAutoPlay(); // Restart auto-play
        });
    });

    // Pause on hover
    demoCarousel.addEventListener("mouseenter", stopAutoPlay);
    demoCarousel.addEventListener("mouseleave", startAutoPlay);

    // Start when demo is visible
    const demoObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                startAutoPlay();
                demoObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });
    
    demoObserver.observe(demoCarousel);
}

// Lazy load demo section
const demoSection = document.getElementById("demo");
if (demoSection) {
    demoSection.style.opacity = "0";
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.transition = "opacity 0.6s ease-out";
                entry.target.style.opacity = "1";
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    observer.observe(demoSection);
}
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
include($dir['core_components'] . '/bg_bottom_nav.inc');
$app->outputpage();
?>