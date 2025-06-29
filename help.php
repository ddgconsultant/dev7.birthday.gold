<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

#-------------------------------------------------------------------------------
# PAGE SETUP
#-------------------------------------------------------------------------------
$page_title = "Help Center - Birthday Gold";
$page_description = "Get help with Birthday Gold - FAQs, guides, and customer support";

// Get business hours settings
$businessHours = $app->bg_businesshours();
$disabledClass = $businessHours['display']['disabledClass'];
$afterhourtag = $businessHours['display']['afterhourtag'];
$workingHoursString = $businessHours['display']['workingHoursString'];

// Modern Minimalist CSS
$additionalstyles = '
<style>
/* Modern Minimalist Help Center Styles */
.main-content {
    min-height: calc(100vh - 200px);
    padding: 2rem 1rem;
    background: #f8f9fa;
}

.help-container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

/* Header Section */
.help-header {
    text-align: center;
    margin-bottom: 3rem;
}

.help-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
}

.help-header p {
    font-size: 1.25rem;
    color: #6c757d;
    margin: 0;
}

/* Search Bar */
.search-box {
    max-width: 600px;
    margin: 0 auto 3rem;
}

.search-input {
    width: 100%;
    padding: 1rem 1.5rem;
    font-size: 1.125rem;
    border: 2px solid #dee2e6;
    border-radius: 50px;
    transition: all 0.2s ease;
    background: white;
}

.search-input:focus {
    outline: none;
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

/* Section Headers */
.section-header {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
}

.section-subtitle {
    font-size: 1rem;
    color: #6c757d;
}

/* Help Cards Grid */
.help-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

/* Help Card */
.help-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.help-card:hover {
    border-color: var(--bs-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
    text-decoration: none;
}

.help-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    background: #e7f3ff;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-primary);
    font-size: 1.25rem;
}

.help-content {
    flex: 1;
}

.help-card-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.help-card-text {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
    line-height: 1.5;
}

/* Business Hours Alert */
.business-hours-alert {
    background: #fff3cd;
    border: 1px solid #ffecb5;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    color: #664d03;
}

.business-hours-alert i {
    color: #f0ad4e;
    margin-right: 0.5rem;
}

/* Disabled state */
.disabled-content {
    opacity: 0.5;
    pointer-events: none;
    cursor: not-allowed;
}

.disabled-content .help-card {
    cursor: not-allowed;
}

.disabled-content .help-card:hover {
    transform: none;
    box-shadow: none;
    border-color: #e9ecef;
}

/* Social Links */
.social-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    margin-top: 3rem;
    border: 1px solid #e9ecef;
}

.social-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 1.5rem;
}

.social-links {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.social-link {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 2px solid;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    text-decoration: none;
    color: white;
}

/* Social Media Brand Colors */
.social-link[title="Twitter"] {
    background: #000000;
    border-color: #000000;
}

.social-link[title="Facebook"] {
    background: #1877f2;
    border-color: #1877f2;
}

.social-link[title="Instagram"] {
    background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
    border-color: #dc2743;
}

.social-link[title="LinkedIn"] {
    background: #0077b5;
    border-color: #0077b5;
}

.social-link[title="TikTok"] {
    background: #000000;
    border-color: #000000;
}

.social-link[title="YouTube"] {
    background: #ff0000;
    border-color: #ff0000;
}

.social-link[title="Pinterest"] {
    background: #bd081c;
    border-color: #bd081c;
}

.social-link:hover {
    transform: translateY(-2px) scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    opacity: 0.9;
}

/* Holiday Alert */
.holiday-alert {
    background: #f8d7da;
    border: 1px solid #f5c2c7;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    color: #842029;
}

/* Responsive */
@media (max-width: 767px) {
    .help-header h1 {
        font-size: 2rem;
    }
    
    .help-header p {
        font-size: 1rem;
    }
    
    .help-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .search-input {
        font-size: 1rem;
        padding: 0.875rem 1.25rem;
    }
}

@media (min-width: 768px) {
    .main-content {
        padding: 3rem 2rem;
    }
    
    .help-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 992px) {
    .help-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>
';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="main-content">
    <div class="help-container">
        <!-- Header -->
        <div class="help-header">
            <h1>How can we help you?</h1>
            <p>Find answers to your questions or get in touch with our support team</p>
        </div>
        
        <!-- Search Bar -->
        <div class="search-box">
            <input 
                type="text" 
                class="search-input" 
                placeholder="Search for help..."
                id="helpSearch"
            >
        </div>
        
        <!-- Self Help Section -->
        <div class="section-header">
            <h2 class="section-title">Self Help Resources</h2>
            <p class="section-subtitle">Quick answers to common questions</p>
        </div>
        
        <div class="help-grid">
            <a href="/faq" class="help-card">
                <div class="help-icon">
                    <i class="bi bi-question-circle"></i>
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">Frequently Asked Questions</h3>
                    <p class="help-card-text">Get answers to common issues and questions</p>
                </div>
            </a>
            
            <a href="/how" class="help-card">
                <div class="help-icon">
                    <i class="bi bi-book"></i>
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">How It Works</h3>
                    <p class="help-card-text">Learn how Birthday Gold celebrates you</p>
                </div>
            </a>
            
            <a href="/plans" class="help-card">
                <div class="help-icon">
                    <i class="bi bi-tag"></i>
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">Plans and Pricing</h3>
                    <p class="help-card-text">Find the plan that works best for you</p>
                </div>
            </a>
        </div>
        
        <!-- Community Help Section -->
        <div class="section-header" style="margin-top: 3rem;">
            <h2 class="section-title">Community Help</h2>
            <p class="section-subtitle">Connect with other Birthday Gold users</p>
        </div>
        
        <div class="help-grid">
            <a href="https://forum.birthdaygold.cloud" target="_blank" class="help-card">
                <div class="help-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">Community Forum</h3>
                    <p class="help-card-text">Engage with other users on our forum</p>
                </div>
            </a>
        </div>
        
        <!-- Customer Service Section -->
        <div class="section-header" style="margin-top: 3rem;">
            <h2 class="section-title">Customer Service</h2>
            <p class="section-subtitle">Get direct help from our support team</p>
        </div>
        
        <?php
        // Display holiday alert if applicable
        if (!empty($businessHours['display']['alertMessage'])) {
            echo '<div class="holiday-alert">' . $businessHours['display']['alertMessage'] . '</div>';
        }
        
        // Display after hours alert if applicable
        if (!empty($afterhourtag)) {
            echo '<div class="business-hours-alert">';
            echo '<i class="bi bi-clock"></i> ';
            echo strip_tags($afterhourtag, '<strong>');
            echo '</div>';
        }
        ?>
        
        <div class="help-grid <?php echo $disabledClass; ?>">
            <a href="/chat" target="chatwindow" class="help-card">
                <div class="help-icon">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">Chat with an Agent</h3>
                    <p class="help-card-text">Get online help fast with co-browsing available</p>
                </div>
            </a>
            
            <a href="tel:877-234-6532" class="help-card">
                <div class="help-icon">
                    <i class="bi bi-telephone"></i>
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">Call / Text Us</h3>
                    <p class="help-card-text">Speak with us during office hours<br><?php echo $workingHoursString; ?></p>
                </div>
            </a>
            
            <a href="/contact" class="help-card">
                <div class="help-icon">
                    <i class="bi bi-envelope"></i>
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">Contact Form</h3>
                    <p class="help-card-text">Send us a message and we'll get back to you</p>
                </div>
            </a>
        </div>
        
        <!-- Social Links -->
        <div class="social-section">
            <h3 class="social-title">Connect with us on social media</h3>
            <div class="social-links">
                <a href="https://twitter.com/birthday_gold" target="_blank" class="social-link" title="Twitter">
                    <i class="bi bi-twitter-x"></i>
                </a>
                <a href="https://www.facebook.com/birthdaygold/" target="_blank" class="social-link" title="Facebook">
                    <i class="bi bi-facebook"></i>
                </a>
                <a href="https://www.instagram.com/birthday_gold/" target="_blank" class="social-link" title="Instagram">
                    <i class="bi bi-instagram"></i>
                </a>
                <a href="https://www.linkedin.com/company/birthdaygold" target="_blank" class="social-link" title="LinkedIn">
                    <i class="bi bi-linkedin"></i>
                </a>
                <a href="https://www.tiktok.com/@birthday.gold" target="_blank" class="social-link" title="TikTok">
                    <i class="bi bi-tiktok"></i>
                </a>
                <a href="https://www.youtube.com/@birthdaygold" target="_blank" class="social-link" title="YouTube">
                    <i class="bi bi-youtube"></i>
                </a>
                <a href="https://www.pinterest.com/birthdaygold/" target="_blank" class="social-link" title="Pinterest">
                    <i class="bi bi-pinterest"></i>
                </a>
            </div>
        </div>
    </div>
</div>


<?php
$footerattribute['postfooter'] = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("helpSearch");
    const helpCards = document.querySelectorAll(".help-card");
    
    if (searchInput) {
        searchInput.addEventListener("input", function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            helpCards.forEach(card => {
                const title = card.querySelector(".help-card-title").textContent.toLowerCase();
                const text = card.querySelector(".help-card-text").textContent.toLowerCase();
                
                if (title.includes(searchTerm) || text.includes(searchTerm)) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
            
            // Show/hide section headers based on visible cards
            const sections = document.querySelectorAll(".section-header");
            sections.forEach(section => {
                const nextGrid = section.nextElementSibling;
                if (nextGrid && nextGrid.classList.contains("help-grid")) {
                    const visibleCards = nextGrid.querySelectorAll(".help-card:not([style*=\"display: none\"])");
                    if (visibleCards.length === 0) {
                        section.style.display = "none";
                    } else {
                        section.style.display = "";
                    }
                }
            });
        });
        
        // Focus search input on page load
        searchInput.focus();
    }
});
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
