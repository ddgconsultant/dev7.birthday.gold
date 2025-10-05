<?php
// Cookie banner include file - simplified version
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize dismissal counter if not set
if (!isset($_SESSION['bdgold_banner_dismissals'])) {
    $_SESSION['bdgold_banner_dismissals'] = 0;
}

$dismissalCount = $_SESSION['bdgold_banner_dismissals'];
$showCloseButton = $dismissalCount < 3;
?>

<!-- Cookie Banner -->
<div id="cookieConsentBanner" class="cookie-banner position-fixed bottom-0 start-0 end-0 text-white" 
     data-cookie-consent="true" 
     data-nosnippet
     role="dialog" 
     aria-live="polite" 
     aria-label="Cookie Consent Banner"
     aria-describedby="cookie-description">
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

    <p id="cookie-description">
      <!-- Large screen text -->
      <span class="d-none d-sm-inline">
        We use cookies to make your Birthday.Gold experience sparkle! ✨ Just like how we help you collect birthday rewards, these cookies help us remember your preferences and improve your experience.
      </span>
      <!-- Small screen text -->
      <span class="d-inline d-sm-none">
        We use cookies to improve your Birthday.Gold experience and remember your preferences.
      </span>
    </p>
    <a href="/legalhub/cookies" class="text-warning text-decoration-underline">Manage your cookie preferences</a> or check the
    <a href="<?php echo $cookieinfolink; ?>" class="text-white text-decoration-underline" target="_blank">regulations</a> that require us to ask for your consent.

    <!-- Actions -->
    <div class="cookie-actions d-flex gap-2 flex-wrap mt-3">
  <button id="acceptAll" class="btn btn-warning text-dark flex-fill flex-sm-grow-0">
    <span class="d-none d-sm-inline">Accept All</span>
    <span class="d-inline d-sm-none">Accept</span>
  </button>
  <button id="rejectAll" class="btn btn-outline-warning flex-fill flex-sm-grow-0">
    <span class="d-none d-sm-inline">Reject All</span>
    <span class="d-inline d-sm-none">Reject</span>
  </button>
  <button id="managePreferences" class="btn btn-outline-warning flex-fill flex-sm-grow-0" onclick="window.location.href='/legalhub/cookies'">
    <span class="d-none d-sm-inline">Manage Preferences</span>
    <span class="d-inline d-sm-none">Manage</span>
  </button>
</div>



  </div>
</div>


<style>
.cookie-banner {
    background-color: rgb(62, 51, 5);
    z-index: 9999 !important;
    font-size: 0.9rem;
    transition: transform 0.3s ease-in-out;
    border-top: 3px solid #ffc107 !important;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3) !important;

    /* Squiggly top border */
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='12'><path d='M0,6 Q25,0 50,6 T100,6' fill='transparent' stroke='%23ffc107' stroke-width='7'/></svg>");
    background-repeat: repeat-x;
    background-position: top left;
    background-size: 100px 12px;
    padding-top: 12px;
    
    /* Make it resistant to hiding */
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
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
// Immediately ensure cookie banner is visible
(function() {
    const cookieBanner = document.getElementById('cookieConsentBanner');
    if (cookieBanner) {
        // Force visibility
        cookieBanner.style.display = 'block';
        cookieBanner.style.visibility = 'visible';
        cookieBanner.style.opacity = '1';
        cookieBanner.style.transform = 'translateY(0)';
        cookieBanner.classList.remove('hidden');
        
        // Prevent hiding by extensions
        Object.defineProperty(cookieBanner.style, 'display', {
            get: function() { return 'block'; },
            set: function(value) { 
                if (value !== 'none' && value !== 'hidden') {
                    this.setProperty('display', value);
                }
            }
        });
        
        // Attach close button event listener immediately
        const closeCookieBanner = document.getElementById('closeCookieBanner');
        if (closeCookieBanner) {
            closeCookieBanner.addEventListener('click', function() {
                // Hide the banner by setting transform directly
                cookieBanner.style.transform = 'translateY(100%)';
                
                // Increment dismissal counter via AJAX
                fetch('/ajax/increment_banner_dismissal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=increment_dismissal'
                }).catch(function(error) {
                    console.log('Error incrementing dismissal counter:', error);
                });
                
                // Automatically show again after 24 hours (only if under 3 dismissals)
                setTimeout(function() {
                    if (!document.cookie.includes('bdgold_lastSetDate')) {
                        cookieBanner.style.transform = 'translateY(0)';
                    }
                }, 24 * 60 * 60 * 1000); // 24 hours
            });
        }
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    const cookieBanner = document.getElementById('cookieConsentBanner');
    const closeCookieBanner = document.getElementById('closeCookieBanner');
    const acceptAll = document.getElementById('acceptAll');
    const rejectAll = document.getElementById('rejectAll');
    
    // Ensure banner is still visible after DOM load (but don't override if user just closed it)
    if (cookieBanner && !cookieBanner.classList.contains('hidden')) {
        cookieBanner.style.display = 'block';
        cookieBanner.style.visibility = 'visible';
        cookieBanner.style.opacity = '1';
    }
    
    // Close button functionality (increase counter) - MOVED TO IMMEDIATE FUNCTION ABOVE
    // if (closeCookieBanner) {
    //     console.log('Close button found, attaching event listener');
    //     closeCookieBanner.addEventListener('click', function() {
    //         console.log('Close button clicked!');
    //         cookieBanner.classList.add('hidden');
    //
    //         // Increment close count
    //         const currentCount = <?php echo $dismissalCount; ?>;
    //         console.log('Current count:', currentCount, 'New count:', currentCount + 1);
    //         document.cookie = "bdgold_banner_closed=" + (currentCount + 1) + 
    //                           "; path=/; max-age=31536000; secure; samesite=None; domain=birthday.gold";
    //         
    //         // Automatically show again after 24 hours
    //         setTimeout(function() {
    //             if (!document.cookie.includes('bdgold_lastSetDate')) {
    //                 cookieBanner.classList.remove('hidden');
    //             }
    //         }, 24 * 60 * 60 * 1000); // 24 hours
    //     });
    // } else {
    //     console.log('Close button NOT found!');
    // }
    
    // Cookie options
    const cookieOptions = "; path=/; max-age=31536000; secure; samesite=None; domain=birthday.gold";
    
    // Accept all cookies
    acceptAll.addEventListener('click', function() {
        setAllCookies(true, true, true);
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