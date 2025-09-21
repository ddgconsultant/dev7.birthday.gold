<?php
#-------------------------------------------------------------------------------
# HEADER COMPONENT - CLEANED VERSION
# Compatible with existing Birthday.Gold v3 infrastructure
# Location: /core/components/bg_header.inc
#-------------------------------------------------------------------------------

if (!isset($newheader)) {
    echo '
    </head>
    <body' . ($is_iframe_mode ? ' class="iframe-mode"' : '') . '>
        <!-- Skip to main content for accessibility -->
        <a href="#main-content" class="sr-only sr-only-focusable">Skip to main content</a>
        
        <!-- JavaScript initialization -->
        <script>
        // Mobile viewport height fix (actually useful!)
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
            
            // Add mobile class for CSS targeting
            if (window.innerWidth <= 768) {
                document.body.classList.add("mobile-device");
            }
        });
        </script>
        
        <!-- Main content wrapper -->
        <main id="main-content" role="main" class="flex-grow-1">
    ';
}

if ($mode == 'dev') {
    echo "<!-- TRACE: TOP OF v7/bg_header.inc FILE -->\n";
}

// Initialize bodyclass if not set
if (!isset($bodyclass)) $bodyclass = '';

// Pre-compute flags and variables
$isLoggedIn = $current_user_data['user_id'] ?? false;
$tmpheadervar_isUserActive = $isLoggedIn && $account->isactive();
$tmpheadervar_isUserStaff = $tmpheadervar_isUserActive && $account->isstaff();
$tmpheadervar_isUserAdmin = $tmpheadervar_isUserActive && $account->isadmin();

// Get current page for active states
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Check if we're on the home page - ONLY the root index page
$isHomePage = ($scriptName === '/index.php' || 
               $currentPath === '/' || 
               $currentPath === '/index' ||
               $currentPath === '/index.php');

$isBrowsePage = in_array($currentPage, ['browse', 'rewards', 'browse-rewards']);
$isHelpPage = in_array($currentPage, ['help', 'support', 'faq']);
$isAccountPage = in_array($currentPage, ['account', 'profile', 'settings']);

// Handle signup mode for kiosk/iframe contexts
$signupMode = $session->get('signupmode', '');
$isKioskMode = in_array($signupMode, ['tabletkiosk', 'upgrade']);

// Admin mode detection
$current_url = $_SERVER['REQUEST_URI'];
$adminenabledpage = $adminenabledpage ?? false;
$isAdminPage = $adminenabledpage || (strpos($current_url, '/admin') !== false);
$navclass = $isAdminPage ? 'bg-danger-subtle' : '';

// Impersonation mode detection
$impersonator_enabled = false;
if ($account->isimpersonator()) {
    $returntouser = $session->get('impersonator', '');
    if (!empty($returntouser['user_id'])) {
        $impersonator_enabled = true;
        $navclass = 'admin-impersonation-bg';
    }
}

// Get user stats data upfront if user is active
$tmpheadervar_unreadMailCount = 0;
$tmpheadervar_rewardsCount = 0;
$tmpheadervar_profileCompletionPercent = 0;
$tmpheadervar_daysUntilBirthday = 0;
$tmpheadervar_hasEmailFeature = false;

if ($tmpheadervar_isUserActive) {
    include_once($dir['core_components'] . '/user_getaccountdetails.inc');
    
    // Get mail count
    $tmpheadervar_unreadMailCount = $featuremailcount['unread'] ?? 0;
    $tmpheadervar_hasEmailFeature = !empty($current_user_data['feature_email']);
    
    // Get rewards count - ensure null safety
    $tmpheadervar_rewardsCount = is_array($user_reward_results) ? count($user_reward_results) : 0;
    
    // Get profile completion
    $tmpheadervar_profileCompletionPercent = $profilecompletion['required_percentage'] ?? 0;
    
    // Get days until birthday
    $tmpheadervar_daysUntilBirthday = $till['days'] ?? 0;
}

if ($mode == 'dev') {
    echo '
<!-- ======================================================== -->
<!-- START OF HEADER v7 -->
<!-- ======================================================== -->
<!-- TRACE: We are in v7/bg_header.inc -->
<!-- TRACE: currentPage = "' . $currentPage . '" -->
<!-- TRACE: currentPath = "' . $currentPath . '" -->
<!-- TRACE: scriptName = "' . $scriptName . '" -->
<!-- TRACE: isHomePage = ' . ($isHomePage ? 'TRUE' : 'FALSE') . ' -->';
}

// Begin header output
echo '
<!-- ===== HEADER COMPONENT ===== -->';

if ($isHomePage) {
    // Special header for index/home page
    if ($mode == 'dev') { 
        echo '
<!-- TRACE: ENTERING HOME PAGE HEADER SECTION -->';
    }
    
    echo '
<header class="top-header home-header '.$navclass.'">
    <div class="container-fluid h-100">
        <div class="row h-100 align-items-center">
            <!-- Logo/Brand -->
            <div class="col-auto">
                <a class="navbar-brand header-logo" href="/">
                    <img class="home-logo" src="//cdn.birthday.gold/public/images/logo/birthday.gold_logo-white.png" alt="Birthday Gold" style="height: 40px;">
                </a>
            </div>
            
            <!-- Desktop Navigation (right-aligned) -->
            <div class="col d-none d-lg-block">
                <nav class="center-nav">
                    <a href="/how-it-works" class="nav-link">How it Works</a>
                    <a href="/discover" class="nav-link">Browse Rewards</a>
                    <a href="/help" class="nav-link">Help</a>
                </nav>
            </div>
            
            <!-- Right Side - Auth buttons (always flush right) -->
            <div class="col-auto d-flex align-items-center justify-content-end ms-auto">';
    
    if (!$isLoggedIn) {
        echo '
                <a href="/signup" class="btn btn-secondary me-2 d-none d-sm-inline-block">Sign Up</a>
                <a href="/login" class="btn btn-primary">Login</a>';
    } else {
        // Still show avatar dropdown for logged in users on home page
        echo '
                <div class="dropdown">
                    <div class="avatar-placeholder rounded-circle" id="bg_avatarMenuMain" data-bs-toggle="dropdown" aria-expanded="false" role="button" 
                    style="background-image: url(' . ($current_user_data['avatar']??'') . ')"></div>
                    <ul class="dropdown-menu dropdown-menu-end pt-0" aria-labelledby="bg_avatarMenuMain">';
        
        // Include the dropdown menu content
        if ($tmpheadervar_isUserActive) {
            echo '
                        <li class="m-0 p-0">
                            <div class="dropdown-item p-0">
                                <div class="stats-menu-container my-0 p-0">
                                    <div class="stats-menu">';
                                        
                                        // Only show Mail icon if user has feature_email
                                        if (!empty($current_user_data['feature_email'])) {
                                            echo '
                                        <a href="/myaccount/mail" class="stats-menu-item">
                                            <div class="stats-menu-icon">
                                                <i class="bi bi-envelope"></i>
                                            </div>
                                            <div class="stats-menu-value">'.number_format($tmpheadervar_unreadMailCount).'</div>
                                            <div class="stats-menu-label">Mail</div>
                                        </a>';
                                        }
                                        
                                        echo '
                                        <a href="/myaccount/redeem" class="stats-menu-item">
                                            <div class="stats-menu-icon">
                                                <i class="bi bi-trophy"></i>
                                            </div>
                                            <div class="stats-menu-value">'.$tmpheadervar_rewardsCount.'</div>
                                            <div class="stats-menu-label">Rewards</div>
                                        </a>
                                        <div class="stats-menu-item">
                                            <div class="stats-menu-icon">
                                                <i class="bi bi-cake2"></i>
                                            </div>
                                            <div class="stats-menu-value">'.$tmpheadervar_daysUntilBirthday.'</div>
                                            <div class="stats-menu-label">Days</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider mt-0"></li>
                        <li><a class="dropdown-item text-black" href="/myaccount/"><i class="bi bi-person me-3"></i>Account Home</a></li>';
        }
        
        echo '
                        <li><a class="dropdown-item text-black" href="/myaccount/settings"><i class="bi bi-gear me-3"></i>Settings</a></li>';
       # breakpoint($current_user_data);
          // Show Marketing for users with a company
          if (!empty($current_user_data['company_listcount'])) {
            echo '<li><a class="dropdown-item text-black" href="/myaccount/marketing/"><i class="bi bi-megaphone me-3"></i>Marketing</a></li>';
        }
        
        if (($current_user_data['account_plan'] ?? '') !== 'free') {
            echo '
                        <li><a class="dropdown-item text-black" href="/myaccount/billing"><i class="bi bi-credit-card me-3"></i>Billing</a></li>';
        }
        
        if ($tmpheadervar_isUserStaff) {
            echo '
                        <li><a class="dropdown-item text-black" href="/staff"><i class="bi bi-person-badge me-3 text-warning"></i>Staff</a></li>';
        }
        
        if ($tmpheadervar_isUserAdmin) {
            echo '
                        <li><a class="dropdown-item text-black" href="/admin"><i class="bi bi-shield-lock me-3 text-danger"></i>Admin</a></li>';
        }
        
        echo '
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/logout"><i class="bi bi-box-arrow-right me-3"></i>Logout</a></li>';
        
        if ($impersonator_enabled) {
            echo '
                        <li><a class="dropdown-item text-danger" href="/myaccount/myaccount_actions/switch2user?id=' . $qik->encodeId($returntouser['user_id']) . '&aid=' . $qik->encodeId($returntouser['user_id']) . '&revertimpersonation=1&_token=' . $display->inputcsrf_token('tokenonly') . '">
                            <i class="bi bi-slash-circle-fill me-3"></i>Stop Impersonating
                        </a></li>';
        }
        
        echo '
                    </ul>
                </div>';
    }
    
    echo '
            </div>
        </div>
    </div>
</header>';
} else {
    // Regular header for all other pages
    if ($mode == 'dev') {
        echo '
<!-- TRACE: ENTERING REGULAR HEADER SECTION -->';
    }

    echo '
<header class="top-header '.$navclass.'">
    <div class="container-fluid h-100">
        <div class="row h-100 align-items-center">
            <!-- Logo/Brand - Smaller on mobile -->
            <div class="col-auto">
                <a class="navbar-brand header-logo" href="/">
                    <img class="mobile-logo" src="//cdn.birthday.gold/public/images/logo/bg_icon.png" alt="Company Icon" id="companyIcon" data-bs-toggle="tooltip" data-bs-placement="right" title="birthday.gold Home">
                    <img class="desktop-logo" src="//cdn.birthday.gold/public/images/logo/birthday.gold_logo.png" alt="Company Logo" id="companyLogo">
                </a>
            </div>
            
            <!-- Desktop Navigation (right-aligned) -->
            <div class="col d-none d-lg-block">
                <nav class="center-nav">
                    <a href="/how-it-works" class="nav-link">How it Works</a>
                    <a href="/discover" class="nav-link">Browse Rewards</a>
                    <a href="/help" class="nav-link">Help</a>
                </nav>
            </div>
            
            <!-- Right Side - Auth State (always flush right) -->
            <div class="col-auto d-flex align-items-center justify-content-end ms-auto">';

    // User Authentication UI
    if (!$isLoggedIn) {
        // Logged Out State
        echo '
                <a href="/login" class="login-btn btn-primary">Login</a>';
    } else {
        // Logged In State  
        echo '
                <div class="dropdown">
                    <div class="avatar-placeholder rounded-circle" id="bg_avatarMenuMain" data-bs-toggle="dropdown" aria-expanded="false" role="button" 
                    style="background-image: url(' . ($current_user_data['avatar']??'') . ')"></div>
                    <ul class="dropdown-menu dropdown-menu-end pt-0" aria-labelledby="bg_avatarMenuMain">';
        
        // User stats in the dropdown menu
        if ($tmpheadervar_isUserActive) {
            echo '
                        <li class="m-0 p-0">
                            <div class="dropdown-item p-0">
                                <div class="stats-menu-container my-0 p-0">
                                    <div class="stats-menu">';
                                        
                                        // Only show Mail icon if user has feature_email
                                        if (!empty($current_user_data['feature_email'])) {
                                            echo '
                                        <a href="/myaccount/mail" class="stats-menu-item">
                                            <div class="stats-menu-icon">
                                                <i class="bi bi-envelope"></i>
                                            </div>
                                            <div class="stats-menu-value">'.number_format($tmpheadervar_unreadMailCount).'</div>
                                            <div class="stats-menu-label">Mail</div>
                                        </a>';
                                        }
                                        
                                        echo '
                                        <a href="/myaccount/redeem" class="stats-menu-item">
                                            <div class="stats-menu-icon">
                                                <i class="bi bi-trophy"></i>
                                            </div>
                                            <div class="stats-menu-value">'.$tmpheadervar_rewardsCount.'</div>
                                            <div class="stats-menu-label">Rewards</div>
                                        </a>
                                        <div class="stats-menu-item">
                                            <div class="stats-menu-icon">
                                                <i class="bi bi-cake2"></i>
                                            </div>
                                            <div class="stats-menu-value">'.$tmpheadervar_daysUntilBirthday.'</div>
                                            <div class="stats-menu-label">Days</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider mt-0"></li>
                        <li><a class="dropdown-item text-black" href="/myaccount/"><i class="bi bi-person me-3"></i>Account Home</a></li>';
        }

        // Regular menu items
        echo '
                        <li><a class="dropdown-item text-black" href="/myaccount/settings"><i class="bi bi-gear me-3"></i>Settings</a></li>';
        
        // Show Marketing for users with a company
        if (!empty($current_user_data['company_listcount'])) {
            echo '<li><a class="dropdown-item text-black" href="/myaccount/marketing/"><i class="bi bi-megaphone me-3"></i>Marketing</a></li>';
        }
        
        
        // Only show billing for paid accounts
        if (($current_user_data['account_plan'] ?? '') !== 'free') {
            echo '
                        <li><a class="dropdown-item text-black" href="/myaccount/billing"><i class="bi bi-credit-card me-3"></i>Billing</a></li>';
        }
        
        // Staff/Admin links
        if ($tmpheadervar_isUserStaff) {
            echo '
                        <li><a class="dropdown-item text-black" href="/staff"><i class="bi bi-person-badge me-3 text-warning"></i>Staff</a></li>';
        }
        
        if ($tmpheadervar_isUserAdmin) {
            echo '
                        <li><a class="dropdown-item text-black" href="/admin"><i class="bi bi-shield-lock me-3 text-danger"></i>Admin</a></li>';
        }
        
        echo '
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/logout"><i class="bi bi-box-arrow-right me-3"></i>Logout</a></li>';
        
        // Impersonation link
        if ($impersonator_enabled) {
            echo '
                        <li><a class="dropdown-item text-danger" href="/myaccount/myaccount_actions/switch2user?id=' . $qik->encodeId($returntouser['user_id']) . '&aid=' . $qik->encodeId($returntouser['user_id']) . '&revertimpersonation=1&_token=' . $display->inputcsrf_token('tokenonly') . '">
                            <i class="bi bi-slash-circle-fill me-3"></i>Stop Impersonating
                        </a></li>';
        }
        
        echo '
                    </ul>
                </div>';
    }

    // Add signup exit button for kiosk mode
    if ($signupMode != '' && $isKioskMode) {
        echo '
                <a href="/logout" class="signup-exit-btn" title="Exit Signup">
                    <i class="bi bi-x-square text-info"></i>
                </a>';
    }

    echo '
            </div>
        </div>
    </div>
</header>';
} // End of else block for regular header

// Add universal content spacing after header
// This creates consistent spacing between header and content on all pages
// Can be disabled by setting $header_flush = true; before including bg_header.inc
if (!isset($header_flush) || !$header_flush) {
    echo '<div class="content-spacing-wrapper" style="padding-top: 2rem;"></div>';
}

// Add CSS for the spacing system
echo '<style>
/* Universal header spacing system */
.header-flush .content-spacing-wrapper {
    display: none !important;
}

/* Ensure body has no margin/padding that interferes */
body {
    margin: 0;
    padding: 0;
}

/* Optional: Add smooth transition when toggling */
.content-spacing-wrapper {
    transition: padding 0.3s ease;
}

/* Universal header improvements */
.top-header {
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Align navigation to the right */
.center-nav {
    text-align: right;
    display: flex;
    justify-content: flex-end;
    gap: 1.5rem;
    padding-right: 1rem;
}

/* Consistent button styling for login/signup */
.top-header .btn-primary,
.top-header .login-btn {
    background-color: #FFD700;
    border: 2px solid #FFD700;
    color: #000;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    border-radius: 25px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.top-header .btn-primary:hover,
.top-header .login-btn:hover {
    background-color: #FFA500;
    border-color: #FFA500;
    transform: translateY(-2px);
}

.top-header .btn-success {
    background-color: transparent;
    border: 2px solid #FFD700;
    color: #FFD700;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    border-radius: 25px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.top-header .btn-success:hover {
    background-color: #FFD700;
    color: #000;
    transform: translateY(-2px);
}

/* Home page specific - ensure buttons are visible on transparent header */
.home-header .btn-primary,
.home-header .login-btn,
.home-header .btn-success {
    color: #fff !important;
    border-color: rgba(255, 255, 255, 0.8) !important;
    background-color: transparent !important;
}

.home-header .btn-primary:hover,
.home-header .login-btn:hover {
    background-color: rgba(255, 255, 255, 0.2) !important;
    border-color: #fff !important;
}

.home-header .btn-success:hover {
    background-color: rgba(255, 255, 255, 0.2) !important;
    border-color: #fff !important;
}

/* Navigation links */
.nav-link {
    color: #333;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.nav-link:hover {
    color: #FFD700;
}

.home-header .nav-link {
    color: #fff !important;
}

.home-header .nav-link:hover {
    color: #FFD700 !important;
}
</style>';

?>