<?php
#-------------------------------------------------------------------------------
# MOBILE-OPTIMIZED FOOTER COMPONENT v7
# Compatible with existing Birthday.Gold v3 infrastructure
# Location: /core/components/v7/bg_footer.inc
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

// Include bottom navigation component if needed
if ($showBottomNav) {
    include($dir['core_components'] . '/v7/bg_bottom_nav.inc');
}

echo '
<!-- ===== MOBILE-OPTIMIZED FOOTER v7 ===== -->
<footer class="main-footer">';

// Only show full footer on desktop or specific pages
if (!$showBottomNav || $currentPage === 'help' || $currentPage === 'terms' || $currentPage === 'privacy') {
    echo '
    <div class="footer-content">
        <div class="container">
            <div class="row g-4">
                <!-- Company Info -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="footer-section">
                        <h6 class="footer-title">Birthday.Gold</h6>
                        <p class="footer-text">Automatic birthday rewards from hundreds of businesses. One profile, endless freebies.</p>
                        <div class="social-links">
                            <a href="#" class="social-link" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                            <a href="#" class="social-link" aria-label="Twitter"><i class="bi bi-twitter"></i></a>
                            <a href="#" class="social-link" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
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
                        </ul>
                    </div>
                </div>
                
                <!-- Legal -->
                <div class="col-12 col-md-6 col-lg-2">
                    <div class="footer-section">
                        <h6 class="footer-title">Legal</h6>
                        <ul class="footer-links">
                            <li><a href="/terms">Terms of Service</a></li>
                            <li><a href="/privacy">Privacy Policy</a></li>
                            <li><a href="/cookies">Cookie Policy</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Business -->
                <div class="col-12 col-lg-3">
                    <div class="footer-section">
                        <h6 class="footer-title">For Businesses</h6>
                        <ul class="footer-links">
                            <li><a href="/business/partner">Become a Partner</a></li>
                            <li><a href="/business/api">API Documentation</a></li>
                            <li><a href="/business/enterprise">Enterprise Solutions</a></li>
                        </ul>
                        <div class="newsletter-signup mt-3">
                            <p class="small text-muted mb-2">Business Updates</p>
                            <div class="input-group input-group-sm">
                                <input type="email" class="form-control" placeholder="Business email">
                                <button class="btn btn-outline-primary" type="button">
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
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
    // Minimal footer for mobile with bottom nav
    echo '
    <div class="minimal-footer">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="copyright-minimal mb-0">© ' . date('Y') . ' Birthday.Gold</p>
                </div>
            </div>
        </div>
    </div>';
}

echo '
</footer>';

// Add any footer scripts that were set
if (isset($footerattribute['postfooter']) && $footerattribute['postfooter']) {
    echo $footerattribute['postfooter'];
}

// Add Bootstrap JS if not already included
if (!isset($bootstrap_js_included)) {
    echo '
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>';
    $bootstrap_js_included = true;
}

// Core mobile navigation JavaScript
echo '
<script>
// Mobile navigation enhancements
document.addEventListener("DOMContentLoaded", function() {
    // Handle active states for bottom navigation
    const currentPath = window.location.pathname;
    const navItems = document.querySelectorAll(".bottom-nav .nav-item");
    
    navItems.forEach(item => {
        const href = item.getAttribute("href");
        if (href && currentPath.includes(href.replace("/", ""))) {
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
    
    // Smooth scroll for anchor links
    document.querySelectorAll(\'a[href^="#"]\').forEach(anchor => {
        anchor.addEventListener("click", function(e) {
            const href = this.getAttribute("href");
            if (href !== "#" && href.length > 1) {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });
                }
            }
        });
    });
    
    // Handle form validation feedback
    const forms = document.querySelectorAll("form[data-validate]");
    forms.forEach(form => {
        form.addEventListener("submit", function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                
                // Focus first invalid field
                const firstInvalid = form.querySelector(":invalid");
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
            form.classList.add("was-validated");
        });
    });
});

// Global functions for navigation
function toggleMoreMenu() {
    const overlay = document.getElementById("moreMenuOverlay");
    if (overlay) {
        const isVisible = overlay.style.display === "flex";
        overlay.style.display = isVisible ? "none" : "flex";
    }
}

// Show loading state for navigation
function showLoading(element) {
    if (element) {
        const originalContent = element.innerHTML;
        element.innerHTML = \'<i class="bi bi-arrow-clockwise spin me-2"></i>Loading...\';
        element.disabled = true;
        
        // Restore after delay or on page load
        setTimeout(() => {
            element.innerHTML = originalContent;
            element.disabled = false;
        }, 2000);
    }
}

// Add CSS for loading animation
const style = document.createElement("style");
style.textContent = `
    .spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>';

// Mobile-specific performance optimizations
echo '
<script>
// Preload critical pages for faster navigation
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
?>