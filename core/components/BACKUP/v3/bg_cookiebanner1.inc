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
<div id="cookieConsentBanner" class="cookie-banner">
    <div class="cookie-content">
        <div class="cookie-header">
            <h5>🍪 Birthday Treats for Your Browser Too!</h5>
            <?php if ($showCloseButton): ?>
            <button id="closeCookieBanner" class="btn-close btn-close-white" aria-label="Close"></button>
            <?php endif; ?>
        </div>
        <p>We use cookies to make your Birthday.Gold experience sparkle! ✨ Just like how we help you collect birthday rewards, these cookies help us remember your preferences and improve your experience. <a href="/legalhub/cookies" class="cookie-link">Manage your cookie preferences</a> or check the <a href="https://gdpr-info.eu/art-6-gdpr/" class="cookie-link" target="_blank">regulations</a> that require us to ask for your consent.</p>
        
        <div class="cookie-controls">
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
        
        <div class="cookie-actions">
            <button id="acceptAll" class="cookie-btn primary-btn">Accept All</button>
            <button id="acceptSelected" class="cookie-btn secondary-btn">Accept Selected</button>
            <button id="rejectAll" class="cookie-btn secondary-btn">Reject All</button>
        </div>
    </div>
</div>

<style>
/* Cookie Banner Styles */
.cookie-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: #1e1e1e;
    color: #fff;
    z-index: 9999;
    border-top: 3px solid var(--bs-secondary);
    box-shadow: 0 -5px 10px rgba(0, 0, 0, 0.1);
    font-size: 0.9rem;
    transition: transform 0.3s ease-in-out;
}

.cookie-banner.hidden {
    transform: translateY(100%);
}

.cookie-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.25rem;
}

.cookie-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.cookie-header h5 {
    margin: 0;
    color: var(--bs-secondary);
}

.cookie-link {
    color: var(--bs-secondary);
    text-decoration: underline;
}

.cookie-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin: 1rem 0;
}

.form-check {
    margin-right: 1.5rem;
}

.form-check-input:checked {
    background-color: var(--bs-secondary);
    border-color: var(--bs-secondary);
}

.cookie-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.cookie-btn {
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-weight: 400;
    text-align: center;
    cursor: pointer;
    border: 1px solid transparent;
    font-size: 0.9rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
}

.primary-btn {
    background-color: var(--bs-secondary);
    color: #000;
}

.primary-btn:hover {
    background-color: #d9ad00;
}

.secondary-btn {
    background-color: transparent;
    border-color: var(--bs-secondary);
    color: var(--bs-secondary);
}

.secondary-btn:hover {
    background-color: rgba(255, 199, 0, 0.1);
}

@media (max-width: 768px) {
    .cookie-controls {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .form-check {
        margin-right: 0;
    }
    
    .cookie-actions {
        flex-direction: column;
    }
    
    .cookie-btn {
        margin-bottom: 0.5rem;
        width: 100%;
    }
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