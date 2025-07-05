<?php
// Cookie banner include file
// Initialize cookie session counter if not already set
if (!isset($_COOKIE['bdgold_banner_closed'])) {
    setcookie("bdgold_banner_closed", "0", [
        'expires' => time() + (86400 * 365),
        'path' => '/',
        'secure' => true,
        'samesite' => 'None',
        'httponly' => false,
        'domain' => 'birthday.gold'
    ]);
}

// Get current count of times banner was closed
$closeCount = isset($_COOKIE['bdgold_banner_closed']) ? intval($_COOKIE['bdgold_banner_closed']) : 0;

// Determine if close button should be shown
$showCloseButton = $closeCount < 3; // Hide close button after 3 closes
?>

<!-- Cookie Consent Banner -->
<div id="cookieConsentBanner" class="cookie-banner position-fixed bottom-0 start-0 end-0 text-white">
    <div class="cookie-content mx-auto" style="max-width: 1200px;">
    <div class="cookie-header d-flex justify-content-between align-items-center mb-3">
    <!-- Large screen heading -->
    <h5 class="d-none d-sm-block mb-0 text-warning">🍪 Birthday Treats for Your Browser Too!</h5>
    <!-- Small screen heading -->
    <h5 class="d-block d-sm-none mb-0 text-warning">🍪 Browser Cookies</h5>

    <?php if ($showCloseButton): ?>
    <button id="closeCookieBanner" class="btn-close btn-close-white" aria-label="Close"></button>
    <?php endif; ?>
</div>

<p>
    <!-- Large screen text -->
    <span class="d-none d-sm-inline">
        We use cookies to make your Birthday.Gold experience sparkle! ✨ Just like how we help you collect birthday rewards, these cookies help us remember your preferences and improve your experience.
    </span>
    <!-- Small screen text -->
    <span class="d-inline d-sm-none">
        We use cookies to improve your Birthday.Gold experience and remember your preferences.
    </span>
    <a href="/legalhub/cookies" class="text-warning text-decoration-underline">Manage your cookie preferences</a> or check the
    <a href="https://gdpr-info.eu/art-6-gdpr/" class="text-warning text-decoration-underline" target="_blank">regulations</a> that require us to ask for your consent.
</p>

        <div class="cookie-controls mt-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="requiredCookie" checked disabled>
                <label class="form-check-label" for="requiredCookie">Required Cookies</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="analyticsCookie" checked>
                <label class="form-check-label" for="analyticsCookie">Analytics Cookies</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="performanceCookie" checked>
                <label class="form-check-label" for="performanceCookie">Performance Cookies</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="advertisingCookie" checked>
                <label class="form-check-label" for="advertisingCookie">Advertising Cookies</label>
            </div>
        </div>
        
        <div class="cookie-actions d-flex gap-2 flex-wrap mt-3">
            <button id="acceptAll" class="btn btn-warning text-dark">Accept All</button>
            <button id="acceptSelected" class="btn btn-outline-warning">Accept Selected</button>
            <button id="rejectAll" class="btn btn-outline-warning">Reject All</button>
        </div>
    </div>
</div>

<style>
.cookie-banner {
    background-color: rgb(62, 51, 5);
    z-index: 9999;
    font-size: 0.9rem;
    transition: transform 0.3s ease-in-out;

    /* Squiggly top border */
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='12'><path d='M0,6 Q25,0 50,6 T100,6' fill='transparent' stroke='%23ffc107' stroke-width='7'/></svg>");
    background-repeat: repeat-x;
    background-position: top left;
    background-size: 100px 12px;
    padding-top: 12px;
}

.cookie-banner.hidden {
    transform: translateY(100%);
}

.cookie-content {
    padding: 1.25rem;
}

/* Custom button hover states */
.btn-warning:hover {
    background-color: #d9ad00;
}

.btn-outline-warning:hover {
    background-color: rgba(255, 199, 0, 0.1);
}
</style>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const cookieBanner = document.getElementById('cookieConsentBanner');
    const closeCookieBanner = document.getElementById('closeCookieBanner');
    const acceptAll = document.getElementById('acceptAll');
    const acceptSelected = document.getElementById('acceptSelected');
    const rejectAll = document.getElementById('rejectAll');
    const analyticsCookie = document.getElementById('analyticsCookie');
    const performanceCookie = document.getElementById('performanceCookie');
    const advertisingCookie = document.getElementById('advertisingCookie');
    
    // Close button functionality (increase counter)
    if (closeCookieBanner) {
        closeCookieBanner.addEventListener('click', function() {
            cookieBanner.classList.add('hidden');
            
            // Increment close count
            const currentCount = <?php echo $closeCount; ?>;
            document.cookie = "bdgold_banner_closed=" + (currentCount + 1) + 
                              "; path=/; max-age=31536000; secure; samesite=None; domain=birthday.gold";
            
            // Automatically show again after 24 hours
            setTimeout(function() {
                if (!document.cookie.includes('bdgold_lastSetDate')) {
                    cookieBanner.classList.remove('hidden');
                }
            }, 24 * 60 * 60 * 1000); // 24 hours
        });
    }
    
    // Cookie options
    const cookieOptions = "; path=/; max-age=31536000; secure; samesite=None; domain=birthday.gold";
    
    // Accept all cookies
    acceptAll.addEventListener('click', function() {
        setAllCookies(true, true, true);
        cookieBanner.classList.add('hidden');
    });
    
    // Accept selected cookies
    acceptSelected.addEventListener('click', function() {
        setAllCookies(analyticsCookie.checked, performanceCookie.checked, advertisingCookie.checked);
        cookieBanner.classList.add('hidden');
    });
    
    // Reject all except necessary
    rejectAll.addEventListener('click', function() {
        setAllCookies(false, false, false);
        cookieBanner.classList.add('hidden');
    });
    
    // Function to set cookie preferences
    function setAllCookies(analytics, performance, advertising) {
        // Set individual cookie preference flags
        document.cookie = "bdgold_analyticsCookie=" + (analytics ? "enabled" : "disabled") + cookieOptions;
        document.cookie = "bdgold_performanceCookie=" + (performance ? "enabled" : "disabled") + cookieOptions;
        document.cookie = "bdgold_advertisingCookie=" + (advertising ? "enabled" : "disabled") + cookieOptions;
        
        // Set the last set date
        document.cookie = "bdgold_lastSetDate=" + Math.floor(Date.now() / 1000) + cookieOptions;
        
        // Refresh page to apply settings
        window.location.reload();
    }
});
</script>