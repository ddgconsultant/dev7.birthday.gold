<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$pagedata['pagetitle']='Birthday Freebies Service Online - Birthday Gold';
$pagedata['metakeywords']='Birthday Freebies, Birthday Freebies Online, Birthday Freebies Near Me, Birthday Freebies Service, Freebies on Birthday';
$pagedata['metadescriptions']='Get the best Birthday Freebies Online & Near Me! Enjoy exclusive Freebies on Birthday with our top Birthday Freebies Service. Sign up now!';

// About Page Styles
$additionalstyles = '
<style>

/* Content Section - Compact */
.about-content {
    padding: 2rem 0;
}

.about-image-wrapper {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    height: 100%;
    max-height: 900px; /* Reduced by 40% from 350px */
}

.about-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.about-image-wrapper:hover img {
    transform: scale(1.05);
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
    background: var(--bs-secondary);
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

/* Mission Box - Improved readability */
.mission-box {
    background: linear-gradient(135deg, var(--bs-primary) 0%, #0b5ed7 100%);
    padding: 1.5rem;
    border-radius: 12px;
    margin-top: 1.5rem;
}


/* Responsive */
@media (max-width: 768px) {
    .about-image-wrapper {
        min-height: 250px;
        margin-bottom: 1.5rem;
    }
    
    /* Single column grid on mobile */
    .feature-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    /* Compact features on mobile */
    .feature-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }
    
    .feature-text h3 {
        font-size: 0.9rem;
    }
    
    .feature-text p {
        font-size: 0.8rem;
    }
    
    .about-text {
        padding: 1rem !important;
    }
    
    .mission-box {
        padding: 1rem;
    }
    
    .mission-box h4 {
        font-size: 1.1rem;
    }
    
    .mission-box p {
        font-size: 0.9rem !important;
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<!-- Hero Section -->
<div class="content-header-dark">
    <div class="container">
        <h1>About Birthday.Gold</h1>
        <p class="lead">Making birthdays more special since 2023</p>
    </div>
</div>

<!-- Main Content -->
<div class="container about-content">
    <div class="row align-items-center g-4">
        <div class="col-lg-5">
            <div class="about-image-wrapper">
                <img src="/public/images/IMG_6318.jpg" alt="Birthday celebration">
            </div>
        </div>
        <div class="col-lg-7">
            <div class="about-text p-4">
                <span class="badge bg-primary px-3 py-2 mb-3">Our Story</span>
                
                <?PHP
                if (!empty($enableadminpageeditor)) { $admin->admineditor('body-1'); }
                ### ADMIN PAGE EDITOR: START-body-1 ###
                echo '
                <h2 class="h2 fw-bold mb-3">Celebrate Your Birthday with Us!</h2>
                <p class="fs-5 text-muted lh-base mb-3">At Birthday.Gold, our mission is simple: we want everyone to enjoy their birthdays to the fullest. It all started when we saw someone joyfully celebrating with birthday freebies, but they did not share how they got them. We realized that not everyone knows the ins and outs of signing up for these perks or navigating the process to claim them. It is more than just walking into a business and asking for your birthday treat—it can be a bit complicated.</p>
                <p class="fs-5 text-muted lh-base mb-4">That is why we are here to make it easy for you. With Birthday.Gold, all you have to do is sign up for our service, select your favorite businesses, and we will handle the rest. You will receive notifications, a handy map of where to go, and all that is left for you to do is celebrate and enjoy!</p>
                ';
                ### ADMIN PAGE EDITOR: END-body-1 ###
                ?>
                
                <div class="feature-grid my-lg-5">
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
                
                <!-- Mission Box -->
                <div class="mission-box text-white my-lg-5">
                    <h4 class="h4 fw-bold text-white mb-3">Our Mission</h4>
                    <p class="text-white fs-5 lh-base mb-3 fst-italic" style="font-family: 'Georgia', 'Times New Roman', serif; letter-spacing: 0.5px;">To make every birthday special by connecting people with amazing birthday rewards from their favorite local businesses.</p>
                </div>
                
                <div class="mt-4">
                    <a href="/signup" class="btn btn-primary btn-lg px-4 me-3">Start Free</a>
                    <a href="/how" class="btn btn-outline-primary btn-lg px-4">Learn More</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?PHP
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();