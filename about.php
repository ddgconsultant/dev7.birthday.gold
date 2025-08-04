<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagedata['pagetitle']='Birthday Freebies Service Online - Birthday Gold';
$pagedata['metakeywords']='Birthday Freebies, Birthday Freebies Online, Birthday Freebies Near Me, Birthday Freebies Service, Freebies on Birthday';
$pagedata['metadescriptions']='Get the best Birthday Freebies Online & Near Me! Enjoy exclusive Freebies on Birthday with our top Birthday Freebies Service. Sign up now!';

// About Page Styles
$additionalstyles = '
<style>
/* Hero Section - Similar to login page */
.about-hero {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 5rem 0;
    margin-bottom: 0;
    position: relative;
    overflow: hidden;
}

.about-hero::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(13, 110, 253, 0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.3; }
}

.hero-image-wrapper {
    position: relative;
    overflow: hidden;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    height: 400px;
}

.hero-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.hero-image-wrapper:hover img {
    transform: scale(1.05);
}

.hero-content h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 1rem;
}

.hero-badge {
    display: inline-block;
    background: var(--bs-primary);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 50rem;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 1rem;
}

/* Feature grid - matching login page */
.feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin: 2rem 0;
}

.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.feature-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    background: rgba(13, 110, 253, 0.1);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-primary);
    font-size: 1.25rem;
}

.feature-text h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.feature-text p {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
    line-height: 1.4;
}

/* Mission Section */
.mission-section {
    background: linear-gradient(135deg, #131c35 0%, #0f0f0f 50%, #1a1a2e 100%);
    color: white;
    padding: 6rem 0;
    margin-top: 0;
    margin-bottom: 0;
    position: relative;
    overflow: hidden;
}

.mission-section::before {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
    animation: pulse 6s ease-in-out infinite;
}

.mission-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
}

.mission-content h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 2rem;
    background: linear-gradient(135deg, #FFD700, #FFA500);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.mission-content p {
    font-size: 1.25rem;
    line-height: 1.8;
    opacity: 0.9;
    font-family: Georgia, serif;
}

/* Other Links Section */
.links-section {
    background: #f8f9fa;
    padding: 4rem 0;
}

.links-title {
    text-align: center;
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 3rem;
    color: #212529;
}

.links-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    max-width: 1000px;
    margin: 0 auto;
}

.link-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    text-decoration: none;
    color: #212529;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.link-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    color: #212529;
    text-decoration: none;
}

.link-card i {
    font-size: 2.5rem;
    color: var(--bs-primary);
    margin-bottom: 1rem;
    display: block;
}

.link-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.link-card p {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
}

/* CTA Buttons */
.hero-cta {
    margin-top: 2rem;
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
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
}

.btn-get-started:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 215, 0, 0.5);
    color: #1a1a2e;
}

/* Responsive */
@media (max-width: 768px) {
    .about-hero {
        padding: 3rem 0;
    }
    
    .hero-image-wrapper {
        height: 250px;
        margin-bottom: 2rem;
    }
    
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .feature-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .mission-content h2 {
        font-size: 2rem;
    }
    
    .mission-content p {
        font-size: 1.1rem;
    }
    
    .links-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<section class="about-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <div class="hero-image-wrapper">
                    <img src="/public/images/IMG_6318.jpg" alt="Birthday celebration">
                </div>
            </div>
            <div class="col-lg-7">
                <div class="hero-content">
                    <span class="hero-badge">Our Story</span>
                    
                    <?PHP
                    if (!empty($enableadminpageeditor)) { $admin->admineditor('body-1'); }
                    ### ADMIN PAGE EDITOR: START-body-1 ###
                    echo '
                    <h1>Celebrate Your Birthday with Us!</h1>
                    <p class="lead text-muted">At Birthday.Gold, our mission is simple: we want everyone to enjoy their birthdays to the fullest. It all started when we saw someone joyfully celebrating with birthday freebies, but they did not share how they got them.</p>
                    <p class="text-muted">We realized that not everyone knows the ins and outs of signing up for these perks or navigating the process to claim them. That is why we are here to make it easy for you.</p>
                    ';
                    ### ADMIN PAGE EDITOR: END-body-1 ###
                    ?>
                    
                    <div class="feature-grid">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <div class="feature-text">
                                <h3>Easy Sign Up</h3>
                                <p>Quick registration to start collecting birthday rewards</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-shop"></i>
                            </div>
                            <div class="feature-text">
                                <h3>Select Businesses</h3>
                                <p>Choose from <?php echo $website['biznames']; ?>+ birthday reward programs</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-bell"></i>
                            </div>
                            <div class="feature-text">
                                <h3>Smart Notifications</h3>
                                <p>Get reminders and maps to claim your rewards</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-gift"></i>
                            </div>
                            <div class="feature-text">
                                <h3>Celebrate & Save</h3>
                                <p>Enjoy free treats and discounts all month long</p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (empty($current_user_data['user_id'])): ?>
                    <div class="hero-cta">
                        <a href="/signup" class="btn-get-started">Get Started Free</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission Section -->
<section class="mission-section">
    <div class="container">
        <div class="mission-content">
            <h2>Our Mission</h2>
            <p>To make every birthday special by connecting people with amazing birthday rewards from their favorite local businesses. We believe everyone deserves to feel celebrated on their special day, and we are here to make that happen - one birthday at a time.</p>
        </div>
    </div>
</section>

<!-- Other Links Section -->
<section class="links-section">
    <div class="container">
        <h2 class="links-title">Explore More</h2>
        <div class="links-grid">
            <a href="/careers" class="link-card">
                <i class="bi bi-briefcase"></i>
                <h3>Careers</h3>
                <p>Join our team and help us celebrate birthdays everywhere</p>
            </a>
            
            <a href="/blog" class="link-card">
                <i class="bi bi-journal-text"></i>
                <h3>Blog</h3>
                <p>Tips, stories, and birthday celebration ideas</p>
            </a>
            
            <a href="/faq" class="link-card">
                <i class="bi bi-question-circle"></i>
                <h3>FAQ</h3>
                <p>Get answers to common questions about Birthday.Gold</p>
            </a>
            
            <a href="/contact" class="link-card">
                <i class="bi bi-envelope"></i>
                <h3>Contact Us</h3>
                <p>We would love to hear from you</p>
            </a>
            
            <a href="/business/partner" class="link-card">
                <i class="bi bi-building"></i>
                <h3>Partner With Us</h3>
                <p>Add your business to our birthday rewards network</p>
            </a>
            
            <a href="/legalhub" class="link-card">
                <i class="bi bi-shield-check"></i>
                <h3>Legal Hub</h3>
                <p>Privacy, terms, and your data protection</p>
            </a>
        </div>
    </div>
</section>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();