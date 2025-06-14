<?php
#-------------------------------------------------------------------------------
# ENHANCED MOBILE-FIRST FOOTER COMPONENT
# Compatible with existing Birthday.Gold v3 infrastructure
# Combines functionality from original footer with mobile-first optimizations
#-------------------------------------------------------------------------------

// Get user authentication status
$isLoggedIn = $current_user_data['user_id'] ?? false;

// Get current page for active states
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Handle signup mode for kiosk/iframe contexts
$signupMode = $session->get('signupmode', '');
$isKioskMode = in_array($signupMode, ['tabletkiosk', 'upgrade']);

// Determine if we should show bottom navigation
$showBottomNav = true;

// Hide bottom nav on certain pages or in kiosk mode
$hideNavPages = ['login', 'register', 'validate-account', 'password-reset'];
if (in_array($currentPage, $hideNavPages) || $isKioskMode) {
    $showBottomNav = false;
}

// Footer styling - combine original and enhanced styles
$additionalstyles .= "
<style>
/* FOOTER styles - combined original and enhanced ====================== */
:root {
    --primary-gold: #FFD700;
    --primary-orange: #FFA500;
    --primary-dark: #2c3e50;
    --gray-50: #f8f9fa;
    --gray-100: #e9ecef;
    --gray-200: #dee2e6;
    --gray-600: #6c757d;
    --gray-700: #495057;
    --gray-900: #212529;
}

.footer {
    padding: 20px 0 !important;
    font-size: 0.6rem !important;
    font-weight: 200 !important;
    color: #6c757d !important;
}

.footer-links a {
    color: white !important;
    font-size: 0.95rem !important;
    font-weight: 200 !important;
    text-decoration: none;
}

.footer-links a:hover {
    color: var(--secondary) !important;
    text-decoration: underline;
}

.footer-links li {
    margin-bottom: 0px !important;
    padding-bottom: 0 !important;
}

.footer-links li::before {
    content: '▸ ';
    color: var(--secondary) !important;
}

.icon-item {
    display: inline-block;
    padding: 0.5rem;
    border-radius: 0.25rem;
    text-align: center;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.btn-outline-light {
    border: 1px solid var(--bs-secondary);
    color: var(--bs-secondary);
    background-color: transparent;
    text-decoration: none;
}

.btn-outline-light:hover, .btn-outline-light:focus {
    background-color: var(--bs-secondary);
    color: #fff;
    text-decoration: none;
}

#emaillink a {
    color: var(--bs-secondary);
    text-decoration: none;
    font-size: 0.875rem !important; 
}

#emaillink a:hover, #emaillink a:focus {
    color: var(--bs-secondary);
    text-decoration: underline;
}

.btn-custom-secondary {
    background-color: var(--bs-secondary);
    border-color: var(--bs-secondary);
    color: #fff;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.btn-custom-secondary:hover, .btn-custom-secondary:focus {
    background-color: #b38f00;
    border-color: #b38f00;
    color: #fff;
}

/* Mobile-First Footer Enhancements ============================= */
.main-footer {
    background-color: #212529;
    color: white;
}

.footer-content {
    padding: 3rem 0 2rem;
}

.footer-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1.25rem;
    color: white;
}

.footer-text {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

.social-links {
    display: flex;
    gap: 0.75rem;
}

.social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-radius: 50%;
    font-size: 1rem;
    transition: all 0.2s ease;
}

.social-link:hover {
    background: var(--primary-gold);
    color: var(--primary-dark);
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 0.75rem;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: color 0.2s ease;
    font-size: 0.9rem;
}

.footer-links a:hover {
    color: var(--primary-gold);
}

.newsletter-signup input {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
}

.newsletter-signup input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.newsletter-signup .btn {
    background: var(--primary-gold);
    color: var(--primary-dark);
    border: none;
}

.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 1.5rem;
    margin-top: 2rem;
}

.copyright {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.85rem;
}

.footer-meta {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    color: rgba(255, 255, 255, 0.5);
}

/* Minimal Footer for Mobile */
.minimal-footer {
    padding: 1rem 0;
    background: #212529;
}

.copyright-minimal {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
}

/* Bottom Navigation Styles - Mobile First */
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    z-index: 1020;
    height: 64px;
    padding-bottom: env(safe-area-inset-bottom);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
}

/* Reset any conflicting styles */
.bottom-nav * {
    box-sizing: border-box;
}

.bottom-nav .nav-item,
.bottom-nav .nav-icon,
.bottom-nav .nav-label {
    padding-left: 0 !important;
    padding-right: 0 !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
}

.nav-items {
    display: flex;
    justify-content: space-evenly;
    align-items: stretch;
    max-width: 600px;
    margin: 0 auto;
    padding: 0;
    height: 100%;
}

.nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #64748b;
    padding: 0;
    margin: 0;
    border-radius: 12px;
    transition: all 0.3s ease;
    flex: 1;
    min-width: 64px;
    max-width: 80px;
    position: relative;
    text-align: center;
}

.nav-item:hover {
    background: rgba(59, 130, 246, 0.08);
    color: #3b82f6;
    text-decoration: none;
}

.nav-item:hover .nav-icon {
    transform: scale(1.1);
}

.nav-item.active {
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.12);
}

.nav-item.active .nav-label {
    font-weight: 700;
}

.nav-item.cta {
    color: #3b82f6;
    font-weight: 600;
}

.nav-item.cta:hover {
    color: white;
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.nav-item.cta.active {
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.nav-item:active {
    transform: scale(0.95);
}

.nav-icon {
    font-size: 22px;
    line-height: 24px;
    margin: 0 auto 2px auto;
    display: block;
    text-align: center;
    width: 100%;
    height: 24px;
    transition: transform 0.2s ease;
}

/* Ensure Bootstrap Icons are centered */
.nav-icon.bi::before {
    display: inline-block;
    vertical-align: middle;
}

.nav-label {
    font-size: 11px;
    font-weight: 500;
    margin: 2px 0 0 0;
    display: block;
    width: 100%;
    text-align: center;
    transition: font-weight 0.2s ease;
}

.nav-badge {
    position: absolute;
    top: 2px;
    right: 8px;
    background: #ef4444;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 0.7rem;
    font-weight: 600;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
}

/* More Menu Styles */
.more-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 2000;
    display: none;
    align-items: flex-end;
}

.more-menu {
    background: white;
    width: 100%;
    border-radius: 20px 20px 0 0;
    max-height: 70vh;
    overflow-y: auto;
    padding-bottom: env(safe-area-inset-bottom);
}

.more-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 20px 10px;
    border-bottom: 1px solid #eee;
}

.more-menu-item {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    text-decoration: none;
    color: #333;
    border-bottom: 1px solid #f5f5f5;
}

.more-menu-item:hover {
    background: var(--gray-100);
    color: var(--primary-orange);
}

.more-icon {
    font-size: 1.2rem;
    margin-right: 15px;
    width: 24px;
    text-align: center;
}

/* Mobile body padding to account for bottom nav */
@media (max-width: 767px) {
    body {
        padding-bottom: 80px !important;
    }
}

/* Responsive Design */
@media (min-width: 768px) {
    .bottom-nav {
        display: none;
    }
    
    body {
        padding-bottom: 0 !important;
    }
}

/* Animation for loading indicator */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.spin {
    animation: spin 1s linear infinite;
}
</style>
";

// Handle sticky footer settings
if (!empty($nostickyfooter)) { 
    $additionalstyles .= "<style>
    /*  NO STICKY FOOTER */
    body {
        display: block !important;
        flex-direction: unset !important;
        min-height: unset !important;
    }
    .main-content {
        flex: unset !important;
    }
    </style>
    ";
    $nostickyfooter_tag = 'nostickyfooter';
} else {
    $nostickyfooter_tag = '';
}   

// Include bottom navigation component if needed
if ($showBottomNav) {
    include($dir['core_components'] . '/bg_bottom_nav.inc');
}

// Start footer output
echo '<!-- ===== ENHANCED MOBILE-FIRST FOOTER ===== -->';

// Choose footer type based on settings
switch ($display_footertype ?? 'default') {
    //------------------------------------
    case 'none':
        // NO FOOTER
        echo '
        <div class="row m-0 p-0"></div>
        <!-- No Footer -->
        <footer class="bg-dark text-white">         
        </footer>
        ';
        break;

    //------------------------------------
    case 'min':
        // COPYRIGHT FOOTER
        echo '
        <div class="row my-4"></div>
        <!-- Min Footer -->
        <footer class="bg-dark text-white">
            <div class="container-fluid">
                <div class="container">
                    <div class="row pt-2 pb-2">
                        <h5 class="text-white fs-3">
                            birthday.gold
                            <span style="font-size: 0.85rem; color: #aaa;">
                                <span class="px-5"></span>
                                <a href="/" class="text-secondary text-decoration-none">HOME</a>
                                <span class="px-5">|</span>
                                <a href="/help" class="text-secondary text-decoration-none">HELP</a>
                                <span class="px-5"></span>
                                <span>Copyright &copy;' . date('Y') . '</span>
                            </span>
                        </h5>
                    </div>
                </div>
            </div>
        </footer>
        ';
        break;

    //------------------------------------
    // DEFAULT FOOTER - ENHANCED WITH MOBILE-FIRST APPROACH
    default:
        echo '<footer class="main-footer">';
        
        // Only show full footer on desktop or specific pages
        if (!$showBottomNav || $currentPage === 'help' || $currentPage === 'terms' || $currentPage === 'privacy') {
            echo '
            <div class="footer-content">
                <div class="container">
                    <div class="row g-4">
                        <!-- Company Info -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="footer-section">
                                <h6 class="footer-title">Birthday.Gold</h6>';
            
            // Get footer monthly message
            $CONTENT_footermessage = $session->get('CONTENT_footermessage', '');
            if (empty($CONTENT_footermessage)) {
                $CONTENT_footermessage = $database->fetchOne('SELECT CONCAT(id, "/", `rank`) id, "db" source, content FROM bg_content WHERE category="footer" AND type="message" AND `rank`=MONTH(NOW()) AND `status`="active"');
                if (!$CONTENT_footermessage) {
                    $CONTENT_footermessage = [
                        'id' => "0",
                        'source' => "default",
                        'content' => 'Help us share the birthday magic - follow, tag, and spread the word about <span class="birthdaygold">@birthday.gold</span>! We want to make birthdays fun, easy and full of free treats for everyone. Follow us for deals and steals.'
                    ];
                }
                $session->set('CONTENT_footermessage', $CONTENT_footermessage);
            } else {
                $CONTENT_footermessage['source'] = 'ses';
            }
            
            echo '<p class="footer-text">' . $CONTENT_footermessage['content'] . '</p>';
            
            // Social links
            echo '
                                <div class="social-links">
                                    <a href="https://x.com/birthday_gold" class="social-link" aria-label="Twitter"><i class="bi bi-twitter"></i></a>
                                    <a href="https://www.facebook.com/birthdaygold/" class="social-link" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                                    <a href="https://www.instagram.com/birthday_gold/" class="social-link" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                                    <a href="https://www.tiktok.com/@birthday.gold" class="social-link" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Links -->
                        <div class="col-6 col-md-3 col-lg-2">
                            <div class="footer-section">
                                <h6 class="footer-title">Quick Links</h6>
                                <ul class="footer-links">
                                    <li><a href="/how-it-works">How it Works</a></li>
                                    <li><a href="/browse-rewards">Browse Rewards</a></li>
                                    <li><a href="/pricing">Pricing</a></li>';
            
            // Show different links based on login status
            if (!$isLoggedIn) {
                echo '
                                    <li><a href="/signup-mobile">Sign Up</a></li>
                                    <li><a href="/login">Login</a></li>';
            } else {
                echo '
                                    <li><a href="/myaccount/">My Account</a></li>
                                    <li><a href="/myaccount/rewards">My Rewards</a></li>';
            }
            
            echo '
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Support -->
                        <div class="col-6 col-md-3 col-lg-2">
                            <div class="footer-section">
                                <h6 class="footer-title">Support</h6>
                                <ul class="footer-links">
                                    <li><a href="/help">Help Center</a></li>
                                    <li><a href="/faq">FAQ</a></li>
                                    <li><a href="/contact">Contact Us</a></li>
                                    <li><a href="/feedback">Feedback</a></li>
                                    <li><a href="/about">About</a></li>
                                    <li><a href="/careers">Careers</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Legal -->
                        <div class="col-6 col-md-3 col-lg-2">
                            <div class="footer-section">
                                <h6 class="footer-title">Legal</h6>
                                <ul class="footer-links">
                                    <li><a href="/terms">Terms of Service</a></li>
                                    <li><a href="/privacy">Privacy Policy</a></li>
                                    <li><a href="/cookies">Cookie Policy</a></li>
                                    <li><a href="/legalhub">Legal Hub</a></li>
                                    <li><a href="/system-status" target="sswindow">System Status</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Newsletter / Business -->
                        <div class="col-6 col-md-3 col-lg-3">';
                        
            // Only show newsletter on non-account pages
            if (!strpos($website['fulluri']['uri'], 'myaccount') && !strpos($website['fulluri']['uri'], 'mail')) {
                echo '
                            <div class="footer-section">
                                <h6 class="footer-title">Newsletter</h6>
                                <div class="newsletter-signup">
                                    <form action="/newsletter" method="post">
                                        ' . $display->inputcsrf_token() . '
                                        <div class="input-group input-group-sm mb-3">
                                            <input type="email" class="form-control" name="email" placeholder="Your email" aria-label="Email address">
                                            <button class="btn btn-outline-light" type="submit">
                                                <i class="bi bi-arrow-right"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <h6 class="footer-title mt-4">For Businesses</h6>
                                <ul class="footer-links">
                                    <li><a href="/business/partner">Become a Partner</a></li>
                                    <li><a href="/business/api">API Documentation</a></li>
                                </ul>
                            </div>';
            } else {
                // Show different content for account pages
                echo '
                            <div class="footer-section">
                                <h6 class="footer-title">Contact Us</h6>
                                <p class="footer-text">
                                    <i class="bi bi-envelope me-2"></i><span id="emaillink"></span>
                                </p>
                                
                                <h6 class="footer-title mt-4">For Businesses</h6>
                                <ul class="footer-links">
                                    <li><a href="/business/partner">Become a Partner</a></li>
                                    <li><a href="/business/api">API Documentation</a></li>
                                </ul>
                            </div>';
            }
            
            echo '
                        </div>
                    </div>
                    
                    <!-- Bottom Bar -->
                    <div class="footer-bottom">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-6">
                                <p class="copyright mb-0">© ' . date('Y') . ' Birthday.Gold. All rights reserved.</p>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="footer-meta">
                                    <span class="text-muted small">v7.0 Mobile-First</span>';
            
            // Show user status in footer for logged in users
            if ($isLoggedIn) {
                $planName = $current_user_data['account_plan'] ?? 'Free';
                echo '
                                    <span class="text-muted small ms-3">
                                        <i class="bi bi-person-badge me-1"></i>' . ucfirst($planName) . ' Plan
                                    </span>';
            }
            
            echo '
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
        } else {
            // No footer on mobile when bottom nav is shown
            // Copyright info available in More menu and legal pages
        }
        
        echo '</footer>';
        
        // Email setup script
        echo '
        <script src="/public/js/email.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof mail2 === "function") {
                    mail2("info", "ibtrdhyag?lod", -2, "", "Click to Email us");
                }
            });
        </script>';
        break;
}

// Add footer scripts
if (!empty($footerattribute['postfooter'])) {
    echo '
    <!-- ===============================================-->
    <!--    JavaScript from footerjs - footerattribute[postfooter]  -->
    <!-- ===============================================-->
    ' . $footerattribute['postfooter'];
    unset($footerattribute['postfooter']);
}

// Include standard JavaScript libraries
echo '
<!-- ===============================================-->
<!--    JavaScripts - Core Libraries -->
<!-- ===============================================-->
<script src="/public/assets/vendors/anchorjs/anchor.min.js"></script>
<script src="/public/assets/vendors/is/is.min.js"></script>';

// Conditionally include flatpickr
if (!empty($enableflatpickr)) {
    echo '
<script src="/public/assets/js/flatpickr.js"></script>';
}

// Conditionally include FontAwesome
if (!empty($fascript) || (isset($nofa) && $nofa === false)) {
    echo '
<script src="/public/assets/vendors/fontawesome/all.min.js"></script>';
}

// Handle chat system
if ($mode != 'dev') {
    $enablechat = true;
} else {
    $enablechat = false;
}
if ($enablechat === false) {
    $forcefalseenablechat = true;
}
include($_SERVER['DOCUMENT_ROOT'] . '/core/' . $website['ui_version'] . '/footer_chatsystem.inc');
echo $footeroutput;

// Include more standard libraries
echo '
<!-- ===============================================-->
<!--    Core JavaScripts -->
<!-- ===============================================-->
<script src="/public/assets/vendors/lodash/lodash.min.js"></script>
<script src="/public/assets/vendors/list.js/list.min.js"></script>
<script src="/public/assets/vendors/simplebar/simplebar.min.js"></script>
<script src="/public/assets/js/theme.js"></script>';

// Handle dismissible alerts
if ($session->get('footerjs_dismiss_alert') === 'true' || isset($dismissible_user_notification)) {
    echo '
<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".alert .btn-close").forEach(function(button) {
        button.addEventListener("click", function() {
            // Get the encrypted message ID from the data attribute
            const messageId = button.getAttribute("data-message-id");
            
            if (messageId) { // Only send a request if messageId is present
                // Send an AJAX request to the PHP script
                fetch("/siteactions/dismissmessage?midtag=" + encodeURIComponent(messageId), {
                    method: "GET",
                    headers: {
                        "Content-Type": "application/json"
                    }
                })
                .then(response => response.text())
                .then(data => {
                    console.log("Message status updated:", data);
                })
                .catch(error => console.error("Error:", error));
            }
        });
    });
});
</script>';
}

// Initialize Bootstrap components
if (empty($override_bstooltips)) {
    echo '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Initialize all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll(\'[data-bs-toggle="tooltip"]\'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // DO NOT auto-initialize all modals - this breaks modal functionality
    // Modals should be initialized on-demand when needed
    // var modalTriggerList = [].slice.call(document.querySelectorAll(\'.modal\'));
    // var modalList = modalTriggerList.map(function (modalTriggerEl) {
    //     return new bootstrap.Modal(modalTriggerEl);
    // });

    // Initialize all accordions (if needed)
    var accordionTriggerList = [].slice.call(document.querySelectorAll(\'.accordion\'));
    var accordionList = accordionTriggerList.map(function (accordionTriggerEl) {
        return new bootstrap.Collapse(accordionTriggerEl, {
            toggle: false
        });
    });
});
</script>';
}

$session->unset('footerjs_dismiss_alert');

// Include Typed.js and Bootstrap
$vers = '5.3.3';
echo '
<script src="/public/assets/vendors/typed.js/typed.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@' . $vers . '/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle body scroll lock when menu opens
const menu = document.querySelector(".bg_mega-menu");
const menuButton = document.querySelector("#menu-button");

if (menuButton && menu) {
    menuButton.addEventListener("click", function() {
        menu.classList.toggle("menu-open");
        document.body.classList.toggle("scroll-lock");
    });
}
</script>';

// Add bottom footer scripts if any
if (!empty($footerattribute['bottomfooter'])) {
    echo '
<!-- ===============================================-->
<!--  footerattribute[bottomfooter]  -->
<!-- ===============================================-->
' . $footerattribute['bottomfooter'];
    unset($footerattribute['bottomfooter']);
}

// Include analytics script based on mode
switch ($mode) {
    case 'dev':
        echo '<script data-host="http://a.bd.gold" data-dnt="false" data-mode="dev" src="https://a.bd.gold/js/script.js" id="ZwSg9rf6GA" async defer></script>';
        break;
    default:
        echo '<script data-host="http://a.bd.gold" data-dnt="false" src="https://a.bd.gold/js/script.js" id="ZwSg9rf6GA" async defer></script>';
        break;
}

// Mobile-specific enhancements from v7
echo '
<!-- Mobile-First Optimizations -->
<script>
// Mobile navigation enhancements
document.addEventListener("DOMContentLoaded", function() {
    // Handle active states for bottom navigation
    const currentPath = window.location.pathname;
    const navItems = document.querySelectorAll(".bottom-nav .nav-item");
    
    navItems.forEach(item => {
        // Remove any server-side active classes first
        item.classList.remove("active");
        
        const href = item.getAttribute("href");
        if (!href || href === "#") return;
        
        // Exact match for home
        if (href === "/" && currentPath === "/") {
            item.classList.add("active");
        }
        // For other pages, check if current path starts with href
        else if (href !== "/" && currentPath.startsWith(href)) {
            item.classList.add("active");
        }
    });
    
    // Handle more menu overlay
    const moreMenuOverlay = document.getElementById("moreMenuOverlay");
    if (moreMenuOverlay) {
        // Close on overlay click
        moreMenuOverlay.addEventListener("click", function(e) {
            if (e.target === moreMenuOverlay) {
                moreMenuOverlay.style.display = "none";
            }
        });
        
        // Close on escape key
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape" && moreMenuOverlay.style.display === "flex") {
                moreMenuOverlay.style.display = "none";
            }
        });
    }
    
    // Handle viewport height for mobile browsers
    function setViewportHeight() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty("--vh", `${vh}px`);
    }
    
    setViewportHeight();
    window.addEventListener("resize", setViewportHeight);
    window.addEventListener("orientationchange", setViewportHeight);
});

// Global functions for navigation
function toggleMoreMenu() {
    const overlay = document.getElementById("moreMenuOverlay");
    if (overlay) {
        const isVisible = overlay.style.display === "flex";
        overlay.style.display = isVisible ? "none" : "flex";
    }
}

// Performance optimizations
if ("requestIdleCallback" in window) {
    requestIdleCallback(() => {
        const criticalPages = ["/myaccount/", "/browse-rewards", "/help"];
        criticalPages.forEach(page => {
            const link = document.createElement("link");
            link.rel = "prefetch";
            link.href = page;
            document.head.appendChild(link);
        });
    });
}

// Service worker registration for offline functionality (optional)
if ("serviceWorker" in navigator && window.location.protocol === "https:") {
    navigator.serviceWorker.register("/sw.js").catch(() => {
        // Service worker not available, continue normally
    });
}

// PWA installation prompt (if applicable)
let deferredPrompt;
window.addEventListener("beforeinstallprompt", (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Show install button if on mobile
    if (window.innerWidth <= 768) {
        const installBtn = document.getElementById("installPWA");
        if (installBtn) {
            installBtn.style.display = "block";
            installBtn.addEventListener("click", () => {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(() => {
                    deferredPrompt = null;
                    installBtn.style.display = "none";
                });
            });
        }
    }
});
</script>';

// Cookie consent banner logic
$cookiePreferencesSet = isset($_COOKIE['bdgold_lastSetDate']);

// Include cookie banner if preferences aren't set
if (!$cookiePreferencesSet) {
    // Set the cookie banner version if not already defined elsewhere
    if (!isset($cookiebannerversion)) {
        // Random selection between version 1 and 2
        $cookiebannerversion = rand(1, 2);
    }

    // Include the appropriate cookie banner based on version
    $bannerFile = 'bg_cookiebanner' . $cookiebannerversion . '.inc';
    include($_SERVER['DOCUMENT_ROOT'] . '/core/components/' . $website['ui_version'] . '/' . $bannerFile);
}

echo '
</body>
</html>';
?>