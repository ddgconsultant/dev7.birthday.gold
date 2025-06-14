<?php
#-------------------------------------------------------------------------------
# ENHANCED PAGE START COMPONENT v7 - Complete Merged Version
# Compatible with existing Birthday.Gold v3 infrastructure
# Location: /core/components/bg_pagestart.inc
#-------------------------------------------------------------------------------

// Start output buffering with gzip compression (preserved from original)
ob_start('ob_gzhandler');

// Ensure we have the basic variables needed
$page_title = $page_title ?? 'Birthday.Gold - Automatic Birthday Rewards';
$meta_description = $meta_description ?? 'One profile automatically enrolls you in hundreds of birthday reward programs. Get free desserts, drinks, and gifts without the paperwork.';
$meta_keywords = $meta_keywords ?? 'birthday rewards, birthday freebies, automatic enrollment, restaurant deals, retail discounts';

// Get current page for specific optimizations
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$is_mobile_page = in_array($current_page, ['signup-mobile', 'validate-account', 'business-rewards-selection']);

// Enhanced SEO based on page type
if ($current_page === 'signup-mobile') {
    $page_title = 'Sign Up - Birthday.Gold';
    $meta_description = 'Create your Birthday.Gold account and start getting automatic birthday rewards from hundreds of businesses. Choose your plan and get started today.';
} elseif ($current_page === 'validate-account') {
    $page_title = 'Verify Account - Birthday.Gold';
    $meta_description = 'Verify your Birthday.Gold account to complete registration and start receiving birthday rewards.';
} elseif ($current_page === 'business-rewards-selection') {
    $page_title = 'Select Rewards - Birthday.Gold';
    $meta_description = 'Choose from hundreds of businesses offering birthday freebies and member rewards. Customize your Birthday.Gold experience.';
}

// Check if we're in signup mode for iframe/kiosk contexts
$signup_mode = $session->get('signupmode', '') ?? '';
$is_iframe_mode = in_array($signup_mode, ['tabletkiosk', 'upgrade']);

// Handle raw header mode for special contexts (preserved from original logic)
$raw_header = $headerattribute['rawheader'] ?? false;

// Preserve original ignoretrueheader logic
if (empty($ignoretrueheader)) {
    echo '<!DOCTYPE html>
<html data-bs-theme="light" lang="' . ($pagelang ?? 'en-US') . '" dir="ltr" class="h-100">
<head>
';
}

echo '
<!-- ======================================================== -->
<!-- START OF PAGESTART v7 -->
<!-- ======================================================== -->';

// Prevent caching (preserved from original)
#header('Cache-Control: no-store, no-cache, must-revalidate');
#header('Pragma: no-cache');
#header('Expires: 0');
#header("Content-Security-Policy: frame-ancestors 'self'");

// Initialize additional styles variable (preserved from original)
if (!isset($additionalstyles)) $additionalstyles = '';

// Server identification (preserved from original)
$webservername = gethostname() ?: 'unk';

// Use enhanced page title or fallback to original logic
$final_page_title = isset($pagedata['pagetitle']) ? $display->pagename($pagedata['pagetitle']) : $page_title;

echo '
<title>' . htmlspecialchars($final_page_title) . '</title>

<!-- Core Metadata (Enhanced) -->   
<meta name="ahthn" content="' . strtolower($webservername) . '"> 
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta property="og:site_name" content="Birthday Gold">

<!-- Mobile & PWA Meta Tags -->
<meta name="theme-color" content="#198754">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Birthday.Gold">
<meta name="msapplication-TileColor" content="#198754">
<meta name="msapplication-config" content="/browserconfig.xml">
';

// Production mode logic with HAProxy detection (preserved from original)
if ($mode == 'production') {

    function getHAProxyIP()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $haProxyIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
            // If there are multiple IPs in the header, take the first one
            $haProxyIP = explode(',', $haProxyIP)[0];
            $ip = trim(str_replace('.', chr(rand(65, 90)), $haProxyIP));
            return $ip;
        }
        return '0';
    }

    function getWebserverIP()
    {
        if (!empty($_SERVER['SERVER_ADDR'])) {
            $ip = trim(str_replace('.', chr(rand(65, 90)), $_SERVER['SERVER_ADDR']));
            return $ip;
        }
        return '0';
    }

    $haProxyIP = getHAProxyIP();
    $webserverIP = getWebserverIP();
    echo '<meta name="bginfdata" content="' . $haProxyIP . '-' . $webserverIP . '">';

    echo '<meta name="banner" ddg_alt="v3pageheader" content="' . str_repeat("\n", 30) . '
Celebrate You!!

dP       oo            dP   dP             dP                                        dP       dP 
88                     88   88             88                                        88       88 
88d888b. dP 88d888b. d8888P 88d888b. .d888b88 .d8888b. dP    dP    .d8888b. .d8888b. 88 .d888b88 
88   `88 88 88   `88   88   88   `88 88   `88 88   `88 88    88    88   `88 88   `88 88 88   `88 
88.  .88 88 88         88   88    88 88.  .88 88.  .88 88.  .88 -- 88.  .88 88.  .88 88 88.  .88 
88Y8888  dP dP         dP   dP    dP `88888P8 `88888P8 `8888P88 88 `8888P88 `88888P  dP `88888P8 
                                                            .88         .88                      
                                                        d8888P      d8888P     

Copyright (c) ' . date('Y') . '
' . str_repeat("\n", 80) . '">';
} else {
    echo '<meta name="banner" content="


- Non Production environment - [ ' . $site . ' ]


">';
}

// Robots meta (preserved from original)
if ($mode != 'production') {
    echo '<meta name="robots" content="noindex">
';
} else {
    echo '<meta name="robots" content="index, follow">
';
}

echo '<meta name="creator" content="php_pageheader-' . date('r') . '">
';

// Bootstrap version handling (preserved from original)
$v = 6;
switch ($v) {
    case 1: $vers = '5.3.3'; $styvers = 'v3'; break;
    case 2: $vers = '5.3.0'; $styvers = ''; break;    
    case 3: $vers = '5.0.0'; $styvers = ''; break;
    case 4: $vers = '5.0.0'; $styvers = 'v3'; break;
    case 5: $vers = '5.3.3'; $styvers = 'v3'; break;
    case 6: $vers = '5.3.0'; $styvers = 'v3'; break;
}

// SEO Meta Tags (enhanced but preserving original fallbacks)
$metakeywords = $pagedata['metakeywords'] ?? $meta_keywords ?? 'Birthday Freebies, Birthday Rewards, Personalized Birthday Offers, Birthday Celebration Map, Exclusive Birthday Perks, Birthday Gold Registration, Birthday Coupons and Vouchers, VIP Birthday Experiences, Year-Round Birthday Deals, Birthday Celebration Itinerary, Unique Birthday Experiences';
$metadescriptions = $pagedata['metadescriptions'] ?? $meta_description ?? 'Unlock a world of birthday perks with birthday.gold! Register, personalize, and celebrate with exclusive offers from over ' . ($website['numberofbiz'] ?? '100') . '+  brands. Discover freebies, VIP experiences, and unique celebrations tailored just for you.';

echo '
<!-- SEO & Social Media Meta Tags -->
<meta name="author" content="Birthday.Gold">
<meta name="keywords" content="' . htmlspecialchars($metakeywords) . '">
<meta name="description" content="' . htmlspecialchars($metadescriptions) . '">

<!-- Open Graph / Social Media -->
<meta property="og:title" content="' . htmlspecialchars($final_page_title) . '">
<meta property="og:description" content="' . htmlspecialchars($metadescriptions) . '">
<meta property="og:image" content="https://birthday.gold/logo.png">
<meta property="og:url" content="' . htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) . '">
<meta property="og:type" content="website">
<meta property="og:locale" content="en_US">
<meta property="og:site_name" content="Birthday Gold">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@birthdaygold">
<meta name="twitter:creator" content="@birthdaygold">
<meta name="twitter:title" content="' . htmlspecialchars($final_page_title) . '">
<meta name="twitter:description" content="' . htmlspecialchars($metadescriptions) . '">
<meta name="twitter:image" content="https://birthday.gold/logo.png">
<meta name="twitter:image:alt" content="Birthday Gold Logo">
<meta name="twitter:domain" content="birthday.gold">

<!-- Favicon (preserved original path) -->
<link href="/public/images/favicons/favicon.ico" rel="icon">

<!-- Enhanced Favicon Set -->
<link rel="apple-touch-icon" sizes="180x180" href="/public/images/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/public/images/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/public/images/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">
<link rel="mask-icon" href="/public/images/safari-pinned-tab.svg" color="#198754">
<link rel="shortcut icon" href="/favicon.ico">

<!-- Preconnect to external resources -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net">
<link rel="preconnect" href="https://code.jquery.com">

<!-- Google Web Fonts (preserved from original) -->
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

<!-- Bootstrap & CSS (preserved original structure) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="/public/css/' . $styvers . '/bg_theme.css" rel="stylesheet">  

<!-- Preload critical resources -->
<link rel="preload" href="/public/css/' . $styvers . '/bg_theme.css" as="style">
<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/fonts/bootstrap-icons.woff2" as="font" type="font/woff2" crossorigin>

<!-- jQuery (preserved from original) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
';

// Critical CSS and mobile optimizations
echo '
<!-- Critical CSS for above-the-fold content -->
<style>
/* Critical above-the-fold styles */
body { 
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "DM Sans", sans-serif;
    margin: 0;
    padding: 0;
    background: #f8f9fa;
    padding-bottom: 90px; /* Space for mobile bottom nav */
}

.top-header { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1030;
    height: 60px;
    border-bottom: 1px solid #e9ecef;
}

.main-content { 
    margin-top: 60px;
}

/* Original dropdown fixes (preserved) */
.dropdown-menu {
    z-index: 1050 !important; /* Ensure the dropdown appears above other elements */
    position: absolute !important; /* Force positioning to prevent clipping */
}

.btn-group {
    position: relative; /* Make sure the dropdown is positioned relative to this */
}

.table-responsive {
    overflow: visible !important; /* Ensure the table\'s container does not clip the dropdown */
}

/* Loading animation */
.page-loading {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e9ecef;
    border-top: 4px solid #198754;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Hide loading once page is ready */
.page-loaded .page-loading {
    display: none;
}

/* Mobile optimizations */
@media (max-width: 768px) {
    body.mobile-device {
        -webkit-text-size-adjust: 100%;
        -webkit-tap-highlight-color: transparent;
    }
    
    .container-main {
        max-width: 480px;
        margin: 0 auto;
        min-height: 100vh;
        background: white;
        box-shadow: 0 0 0 1px rgba(0,0,0,0.05);
    }
}

/* Iframe mode styles */
.iframe-mode {
    margin: 0;
    padding: 0;
}

.iframe-mode .top-header {
    position: relative;
    height: auto;
}

.iframe-mode .main-content {
    margin-top: 0;
}
</style>
';

// Include any additional page-specific styles (preserved from original)
if (isset($additionalstyles)) {
    echo $additionalstyles;
}

// Schema.org structured data
echo '
<!-- Schema.org structured data -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebApplication",
    "name": "Birthday.Gold",
    "description": "' . addslashes($metadescriptions) . '",
    "url": "' . $_SERVER['HTTP_HOST'] . '",
    "applicationCategory": "LifestyleApplication",
    "operatingSystem": "Web Browser",
    "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "USD",
        "description": "Free tier available"
    },
    "featureList": [
        "Automatic birthday reward enrollment",
        "Hundreds of participating businesses",
        "Mobile-first design",
        "Email and SMS notifications"
    ]
}
</script>
';

// Analytics and tracking (if enabled)
if (isset($analytics_enabled) && $analytics_enabled) {
    echo '
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag("js", new Date());
    gtag("config", "GA_MEASUREMENT_ID");
</script>';
}

echo '
</head>
<body' . ($is_iframe_mode ? ' class="iframe-mode"' : '') . '>
    <!-- Page Loading Indicator Removed -->
    
    <!-- Skip to main content for accessibility 
    <a href="#main-content" class="sr-only sr-only-focusable">Skip to main content</a>
    -->
    
    <!-- Enhanced JavaScript initialization -->
    <script>
    // Mobile viewport height fix
    function setViewportHeight() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty("--vh", `${vh}px`);
    }

    // Set initial viewport height
    setViewportHeight();

    // Update on resize and orientation change
    window.addEventListener("resize", setViewportHeight);
    window.addEventListener("orientationchange", setViewportHeight);

    // Mark page as loaded when DOM is ready
    document.addEventListener("DOMContentLoaded", function() {
        document.body.classList.add("page-loaded");
        
        // Initialize tooltips if Bootstrap is loaded
        if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
            var tooltips = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"tooltip\"]"));
            tooltips.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        
        // Initialize mobile-specific features
        if (window.innerWidth <= 768) {
            // Add mobile class for CSS targeting
            document.body.classList.add("mobile-device");
            
            // Disable zoom on form inputs (iOS Safari)
            let viewport = document.querySelector("meta[name=\"viewport\"]");
            if (viewport) {
                document.addEventListener("touchstart", function() {
                    viewport.content = "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no";
                });
                
                // Re-enable zoom after form interaction
                document.addEventListener("touchend", function() {
                    setTimeout(function() {
                        viewport.content = "width=device-width, initial-scale=1.0, user-scalable=yes";
                    }, 500);
                });
            }
        }
        
        // Performance monitoring
        if ("performance" in window && "mark" in window.performance) {
            window.performance.mark("birthday-gold-page-interactive");
        }
    });

    // Service Worker registration for PWA functionality
    if ("serviceWorker" in navigator && window.location.protocol === "https:") {
        window.addEventListener("load", function() {
            navigator.serviceWorker.register("/sw.js").then(function(registration) {
                console.log("ServiceWorker registration successful");
            }).catch(function(err) {
                console.log("ServiceWorker registration failed");
            });
        });
    }

    // Global error handling
    window.addEventListener("error", function(e) {
        // Log client-side errors for debugging
        console.error("Global error:", e.error);
    });
    </script>
    
    <!-- Main content wrapper -->
    <main id="main-content" role="main">
';
?>