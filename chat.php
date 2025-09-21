<?PHP
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Page metadata
$pagedata['pagetitle'] = 'Chat with Birthday Gold Support';
$pagedata['metakeywords'] = 'Birthday Gold Chat, Customer Support, Live Chat, Help';
$pagedata['metadescriptions'] = 'Chat with Birthday Gold support team. Get instant help with your birthday rewards enrollment and account questions.';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<style>
/* Modern chat page styling */
.chat-page {
    background: #f8f9fa;
    
}

/* Additional margin for content header */
.content-header-dark {
    margin-bottom: 2rem;
}

/* Chat container styling */
.chat-wrapper {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    overflow: hidden;
    margin-bottom: 2rem;
}

.chat-container {
    width: 100%;
    height: 70vh;
    position: relative;
}

/* Iframe full size inside container */
.chat-container iframe {
    width: 100% !important;
    height: 100% !important;
    border: none !important;
}

/* Loading spinner */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

/* Alternative contact section */
.contact-alternatives {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.contact-method {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-radius: 8px;
    transition: background 0.2s;
}

.contact-method:hover {
    background: #f8f9fa;
}

.contact-method i {
    font-size: 2rem;
    color: #003366;
    margin-right: 1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .chat-container {
        height: 60vh;
        min-height: 400px;
    }
}
</style>

<div class="chat-page">
    <div class="content-header-dark">
        <div class="container">
            <h1>Live Support Chat</h1>
            <p class="lead">Connect with our support team instantly</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-12 col-lg-8 mb-4">
                <!-- Main Chat Container -->
                <div class="chat-wrapper">
                    <div class="chat-container">
                        <div class="loading-overlay" id="loading-indicator">
                            <div class="text-center">
                                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                                    <span class="visually-hidden">Loading chat...</span>
                                </div>
                                <p class="text-muted">Connecting to support...</p>
                            </div>
                        </div>
                        
                        <iframe 
                            id="chat-iframe"
                            src="https://chat.birthdaygold.cloud/"
                            frameborder="0"
                            sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                            loading="eager">
                        </iframe>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-lg-4">
                <!-- Alternative Contact Methods -->
                <div class="contact-alternatives">
                    <h4 class="mb-4">Need Help Another Way?</h4>
                    
                    <div class="contact-method mb-3">
                        <i class="bi bi-telephone-fill"></i>
                        <div>
                            <h6 class="mb-1">Call Us</h6>
                            <p class="mb-0 text-muted">Mon-Fri 9AM-5PM EST</p>
                            <a href="tel:877-234-6532" class="text-decoration-none fw-bold">877-234-6532</a>
                        </div>
                    </div>
                    
                    <div class="contact-method mb-3">
                        <i class="bi bi-envelope-fill"></i>
                        <div>
                            <h6 class="mb-1">Email Support</h6>
                            <p class="mb-0 text-muted">Response within 24 hours</p>
                            <a href="/contact" class="text-decoration-none fw-bold">Send Message</a>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <i class="bi bi-question-circle-fill"></i>
                        <div>
                            <h6 class="mb-1">Help Center</h6>
                            <p class="mb-0 text-muted">Browse FAQs and guides</p>
                            <a href="/help" class="text-decoration-none fw-bold">View Resources</a>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="text-muted small mb-2">Can't see the chat?</p>
                        <a href="https://chat.birthdaygold.cloud" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Open in New Window
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?PHP
// Don't load the footer chat widget on this page
$forcefalseenablechat = true;

// Add custom script to handle iframe loading
$footerattribute['postfooter'] = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    var iframe = document.getElementById("chat-iframe");
    var loadingIndicator = document.getElementById("loading-indicator");
    var loaded = false;

    // Hide loading spinner when iframe loads
    iframe.addEventListener("load", function() {
        loaded = true;
        if (loadingIndicator) {
            loadingIndicator.style.display = "none";
        }
    });

    // Timeout fallback - hide loader after 5 seconds
    setTimeout(function() {
        if (loadingIndicator) {
            loadingIndicator.style.display = "none";
        }
    }, 5000);

    // Handle iframe errors
    iframe.addEventListener("error", function() {
        if (loadingIndicator) {
            loadingIndicator.innerHTML = `
                <div class="text-center">
                    <i class="bi bi-wifi-off text-warning" style="font-size: 3rem;"></i>
                    <p class="mt-3">Unable to load chat at this time.</p>
                    <p class="text-muted small">Please try our alternative contact methods.</p>
                </div>
            `;
        }
    });
});
</script>
';

include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>