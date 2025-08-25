<?php
$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Retrieve any messages
$transferpagedata = $system->startpostpage();

// Check if user is locked out from Ask Goldie
$isGoldieLocked = false;
$lockoutMinutesRemaining = 0;
if (isset($_SESSION['ask_goldie_lockout_until']) && $_SESSION['ask_goldie_lockout_until'] > time()) {
    $isGoldieLocked = true;
    $lockoutMinutesRemaining = ceil(($_SESSION['ask_goldie_lockout_until'] - time()) / 60);
}

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

// Feature flags
$showCommunityHelp = false; // Temporarily disabled

// Modern Minimalist CSS
$additionalstyles = '
<style>

/* Search Box - matching FAQ page */
.help-search {
    max-width: 600px;
    margin: -2rem auto 3rem;
    position: relative;
    z-index: 10;
}

/* AI Search Suggestions */
.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    max-height: 300px;
    overflow-y: auto;
    display: none;
    z-index: 100;
}

.search-suggestions.show {
    display: block;
}

.suggestion-item {
    padding: 1rem;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.2s ease;
    text-decoration: none;
    display: block;
    color: inherit;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover {
    background: #f8f9fa;
    text-decoration: none;
}

.suggestion-title {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.suggestion-desc {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
}

.search-loading {
    text-align: center;
    padding: 1rem;
    color: #6c757d;
}

.no-results {
    text-align: center;
    padding: 2rem 1rem;
    color: #6c757d;
}

.search-input {
    width: 100%;
    padding: 1rem 3rem 1rem 1.5rem;
    font-size: 1.125rem;
    border: 1px solid #dee2e6;
    border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.search-icon {
    position: absolute;
    right: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

/* Help Cards Grid */
.help-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
    align-items: stretch;
}

/* 2 column layout on larger screens for all sections */
@media (min-width: 768px) {
    .help-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

/* Help Card */
.help-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    border: 2px solid #e9ecef;
    transition: all 0.2s ease;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    height: 100%;
    position: relative;
    overflow: hidden;
}

/* Corner Banner for Featured Cards */
.corner-banner {
    position: absolute;
    top: 12px;
    right: -35px;
    background: #28a745;
    color: white;
    padding: 5px 35px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    transform: rotate(45deg);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    letter-spacing: 0.5px;
    line-height: 1;
}

/* Section-specific border colors */
.self-help-section .help-card {
    border-color: #f4e4bc; /* Subtle gold */
}

.self-help-section .help-card:hover {
    border-color: #e6d0a3; /* Darker gold on hover */
    box-shadow: 0 4px 12px rgba(244, 228, 188, 0.3);
}

.customer-service-section .help-card {
    border-color: rgba(102, 126, 234, 0.3); /* Subtle primary color */
    border-width: 2px;
}

.customer-service-section .help-card:hover {
    border-color: rgba(102, 126, 234, 0.5); /* Slightly darker on hover */
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.community-help-section .help-card {
    border-color: #d4edda; /* Subtle green */
}

.community-help-section .help-card:hover {
    border-color: #b1dfbb;
    box-shadow: 0 4px 12px rgba(212, 237, 218, 0.3);
}

.help-card:hover {
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

/* Section-specific icon colors */
.self-help-section .help-icon {
    background: #fef9ed; /* Light gold background */
    color: #d4a574; /* Gold color */
}

.customer-service-section .help-icon {
    background: #e7f3ff; /* Light primary background */
    color: var(--bs-primary);
}

.community-help-section .help-icon {
    background: #e6f4ea; /* Light green background */
    color: #5cb85c; /* Green color */
}

.help-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.help-card-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.5rem;
}

/* Phone number hover effect for Call Us and Text Us */
.customer-service-section .help-card p.text-muted small {
    transition: all 0.2s ease;
}

.customer-service-section .help-card:hover p.text-muted small {
    color: #4c63d2;
    font-weight: 600;
}

.help-content p:last-child {
    margin-bottom: 0;
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

/* After Hours Container */
.after-hours-container {
    grid-column: 1 / -1;
    border: 2px dashed #ffc107;
    border-radius: 12px;
    padding: 1rem;
    background-color: #fff8e1;
    margin-bottom: 1rem;
}

.after-hours-notice {
    text-align: center;
    color: #856404;
    font-weight: 500;
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.after-hours-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .after-hours-cards {
        grid-template-columns: repeat(2, 1fr);
    }
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


/* Responsive Grid */
@media (max-width: 767px) {
    .help-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

@media (min-width: 768px) {
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

<!-- Hero Section -->
<div class="content-header-dark no-rounded-corners">
    <div class="container">
        <h1>How can we help you?</h1>
        <p class="lead mb-4">Find answers to your questions or get in touch with our support team</p>
    </div>
</div>

<div class="container">
    <!-- Search Bar -->
    <div class="help-search">
        <div class="position-relative">
            <input 
                type="text" 
                class="search-input" 
                placeholder="Search for help..."
                id="helpSearch"
                autocomplete="off"
            >
            <i class="bi bi-search search-icon"></i>
            <div class="search-suggestions" id="searchSuggestions">
                <!-- AI suggestions will appear here -->
            </div>
        </div>
    </div>
    
    <?php if (!empty($transferpagedata['message'])): ?>
        <div class="alert-dismissible-wrapper" style="max-width: 600px; margin: 1rem auto 0;">
            <div class="position-relative">
                <?php 
                // Add dismiss button if not already present
                $messageContent = $transferpagedata['message'];
                if (strpos($messageContent, 'alert-dismissible') === false && strpos($messageContent, 'alert') !== false) {
                    $messageContent = str_replace('class="alert', 'class="alert alert-dismissible fade show', $messageContent);
                    $messageContent = str_replace('</div>', '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>', $messageContent);
                }
                echo $messageContent;
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="main-content py-4 py-md-5 bg-light">
    <div class="container" style="max-width: 1200px;">
        
        <!-- Self Help Section -->
        <div class="self-help-section">
            <div class="mb-4">
                <h2 class="h3 text-primary fw-bold border-bottom border-2 border-secondary d-inline-block pb-2 mb-2">Self Help Resources</h2>
                <p class="text-muted">Quick answers to common questions</p>
            </div>
            
            <div class="help-grid">
            <?php if ($isGoldieLocked): ?>
            <div class="help-card disabled" style="cursor: not-allowed; opacity: 0.6;">
                <div class="help-icon" style="padding: 0; background: transparent; border: none;">
                    <img src="/public/images/logo/goldie_72.png" alt="Goldie" style="width: 48px; height: 48px; filter: grayscale(50%);">
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">Ask Goldie</h3>
                    <p class="text-muted small mb-0 lh-base">Available again in <?php echo $qik->plural2($lockoutMinutesRemaining, 'minute'); ?></p>
                </div>
            </div>
            <?php else: ?>
            <a href="/ask-goldie" class="help-card">
                <span class="corner-banner">Fastest</span>
                <div class="help-icon" style="padding: 0; background: transparent; border: none;">
                    <img src="/public/images/logo/goldie_72.png" alt="Goldie" style="width: 48px; height: 48px;">
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">Ask Goldie</h3>
                    <p class="text-muted small mb-0 lh-base">Get instant answers about Birthday Gold from our AI assistant</p>
                </div>
            </a>
            <?php endif; ?>
            
            <a href="/faq" class="help-card">
                <div class="help-icon">
                    <i class="bi bi-question-circle"></i>
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">Frequently Asked Questions</h3>
                    <p class="text-muted small mb-0 lh-base">Get answers to common issues and questions</p>
                </div>
            </a>
            
            <a href="/how" class="help-card">
                <div class="help-icon">
                    <i class="bi bi-book"></i>
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">How It Works</h3>
                    <p class="text-muted small mb-0 lh-base">Learn how Birthday Gold celebrates you</p>
                </div>
            </a>
            
            <a href="/pricing" class="help-card">
                <div class="help-icon">
                    <i class="bi bi-tag"></i>
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">Plans and Pricing</h3>
                    <p class="text-muted small mb-0 lh-base">Find the plan that works best for you</p>
                </div>
            </a>
            </div>
        </div>
        
        <?php if ($showCommunityHelp): ?>
        <!-- Community Help Section -->
        <div class="community-help-section">
            <div class="mb-4 mt-5">
                <h2 class="h3 text-primary fw-bold border-bottom border-2 border-secondary d-inline-block pb-2 mb-2">Community Help</h2>
            <p class="text-muted">Connect with other Birthday Gold users</p>
        </div>
        
        <div class="help-grid">
            <a href="https://forum.birthdaygold.cloud" target="_blank" class="help-card">
                <div class="help-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">Community Forum</h3>
                    <p class="text-muted small mb-0 lh-base">Engage with other users on our forum</p>
                </div>
            </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Customer Service Section -->
        <div class="customer-service-section">
            <div class="mb-4 mt-5">
                <h2 class="h3 text-primary fw-bold border-bottom border-2 border-secondary d-inline-block pb-2 mb-2">Customer Service</h2>
            <p class="text-muted">Get direct help from our support team</p>
        </div>
        
        <?php
        // Display holiday alert if applicable
        if (!empty($businessHours['display']['alertMessage'])) {
            echo '<div class="alert alert-danger mb-4">' . $businessHours['display']['alertMessage'] . '</div>';
        }
        ?>
        
        <div class="help-grid">
            <a href="/contact" class="help-card">
                <div class="help-icon">
                    <i class="bi bi-envelope"></i>
                </div>
                <div class="help-content">
                    <h3 class="help-card-title">Contact Form</h3>
                    <p class="text-muted small mb-0 lh-base">Send us a message and we'll get back to you</p>
                </div>
            </a>
            
            <?php if (!empty($afterhourtag)): ?>
            <!-- After Hours Container -->
            <div class="after-hours-container">
                <div class="after-hours-notice">
                    <i class="bi bi-clock text-warning"></i>
                    <span class="ms-2">Available during business hours</span>
                </div>
                <div class="after-hours-cards">
            <?php endif; ?>
            
            <div class="<?php echo $disabledClass; ?>">
                <a href="/chat" target="chatwindow" class="help-card">
                    <div class="help-icon">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <div class="help-content">
                        <h3 class="help-card-title">Chat with Member Support</h3>
                        <p class="text-muted small mb-0 lh-base">Talk to a real person who can help with your account</p>
                    </div>
                </a>
            </div>
            
            <div class="<?php echo $disabledClass; ?>">
                <a href="tel:<?php echo str_replace('-', '', $bg_phonenumbers['tollfree_numbers']); ?>" class="help-card">
                    <div class="help-icon">
                        <i class="bi bi-telephone"></i>
                    </div>
                    <div class="help-content">
                        <h3 class="help-card-title">Call Us</h3>
                        <p class="text-muted small mb-0 lh-base">
                            <?php echo $bg_phonenumbers['tollfree']; ?> <small>(<?php echo $bg_phonenumbers['tollfree_numbers']; ?>)</small>
                            <br>Speak with us during office hours
                            <br><?php echo $workingHoursString; ?>
                        </p>
                    </div>
                </a>
            </div>
            
            <div class="<?php echo $disabledClass; ?>">
                <a href="sms:<?php echo str_replace('-', '', $bg_phonenumbers['text_numbers']); ?>" class="help-card">
                    <div class="help-icon">
                        <i class="bi bi-chat-text"></i>
                    </div>
                    <div class="help-content">
                        <h3 class="help-card-title">Text Us</h3>
                        <p class="text-muted small mb-0 lh-base">
                            <?php echo $bg_phonenumbers['text']; ?> <small>(<?php echo $bg_phonenumbers['text_numbers']; ?>)</small>
                            <br>Send us a text message
                            <br><?php echo $workingHoursString; ?>
                        </p>
                    </div>
                </a>
            </div>
            
            <?php if (!empty($afterhourtag)): ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        </div><!-- End customer-service-section -->
        
        <!-- Social Links -->
        <div class="bg-white rounded p-4 text-center mt-5 border">
            <h3 class="h5 fw-semibold text-dark mb-4">Connect with us on social media</h3>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
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
    // Auto-dismiss alerts after 14 seconds
    const alerts = document.querySelectorAll(".alert-dismissible");
    alerts.forEach(function(alert) {
        setTimeout(function() {
            // Check if alert still exists (not manually dismissed)
            if (alert && alert.parentNode) {
                // Trigger Bootstrap dismiss
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 14000); // 14 seconds
    });
    
    const searchInput = document.getElementById("helpSearch");
    const suggestionsContainer = document.getElementById("searchSuggestions");
    const helpCards = document.querySelectorAll(".help-card");
    let searchTimeout;
    let lastQuery = "";
    
    if (searchInput) {
        // AI-powered search with debouncing
        searchInput.addEventListener("input", function(e) {
            const query = e.target.value.trim();
            
            // Clear timeout if user is still typing
            clearTimeout(searchTimeout);
            
            // Hide suggestions if query is empty
            if (query.length === 0) {
                suggestionsContainer.classList.remove("show");
                suggestionsContainer.innerHTML = "";
                
                // Show all cards when search is cleared
                helpCards.forEach(card => {
                    card.style.display = "";
                });
                return;
            }
            
            // Also filter existing cards immediately for instant feedback
            const searchTerm = query.toLowerCase();
            helpCards.forEach(card => {
                const title = card.querySelector(".help-card-title").textContent.toLowerCase();
                const text = card.querySelector(".text-muted").textContent.toLowerCase();
                
                if (title.includes(searchTerm) || text.includes(searchTerm)) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
            
            // Only make AI request after user stops typing for 300ms
            searchTimeout = setTimeout(function() {
                if (query === lastQuery) return; // Avoid duplicate requests
                lastQuery = query;
                
                // Show loading state
                suggestionsContainer.innerHTML = \'<div class="search-loading"><i class="bi bi-search me-2"></i>Searching...</div>\';
                suggestionsContainer.classList.add("show");
                
                // Make AJAX request to AI search
                fetch("/myaccount/ajax/help-ai-search.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: "query=" + encodeURIComponent(query) + "&" + document.querySelector(\'input[name="csrf_token"]\')?.name + "=" + document.querySelector(\'input[name="csrf_token"]\')?.value
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.suggestions.length > 0) {
                        let html = "";
                        data.suggestions.forEach(suggestion => {
                            html += `
                                <a href="${suggestion.url}" class="suggestion-item">
                                    <div class="suggestion-title">${suggestion.title}</div>
                                    <p class="suggestion-desc">${suggestion.description}</p>
                                </a>
                            `;
                        });
                        suggestionsContainer.innerHTML = html;
                    } else {
                        suggestionsContainer.innerHTML = \'<div class="no-results"><i class="bi bi-search-x me-2"></i>No results found. Try different keywords or <a href="/contact">contact support</a>.</div>\';
                    }
                })
                .catch(error => {
                    console.error("Search error:", error);
                    // Fall back to showing filtered results
                    suggestionsContainer.classList.remove("show");
                });
            }, 300); // 300ms debounce
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener("click", function(e) {
            if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.classList.remove("show");
            }
        });
        
        // Show suggestions again when focusing on input with value
        searchInput.addEventListener("focus", function() {
            if (this.value.trim().length > 0 && suggestionsContainer.innerHTML) {
                suggestionsContainer.classList.add("show");
            }
        });
        
        // Handle enter key
        searchInput.addEventListener("keydown", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                const firstSuggestion = suggestionsContainer.querySelector(".suggestion-item");
                if (firstSuggestion) {
                    window.location.href = firstSuggestion.href;
                }
            }
        });
        
        // Focus search input on page load
        searchInput.focus();
    }
});
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
