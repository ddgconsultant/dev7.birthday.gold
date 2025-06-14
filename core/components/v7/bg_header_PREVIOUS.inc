<?php
#-------------------------------------------------------------------------------
# ENHANCED HEADER COMPONENT
# Compatible with existing Birthday.Gold v3 infrastructure
# Location: /core/components/bg_header.inc
#-------------------------------------------------------------------------------

echo "<!-- TRACE: TOP OF v7/bg_header.inc FILE -->\n";

// Add required styles
if (!isset($bodyclass)) $bodyclass = '';
$additionalstyles .= '
<link rel="stylesheet" href="/public/css/' . $website['ui_version'] . '/bg_header.css?'.date('i').'">
<style>
/* Header visibility fix with proper z-index */
.top-header {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 1030 !important; /* Bootstrap navbar z-index, below modals */
    background: white !important;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.home-header {
    height: 70px !important;
}

body {
    padding-top: 70px !important;
}

/* Ensure main content doesn\'t cover header */
#main-content {
    position: relative !important;
    z-index: 1 !important;
}

/* Let Bootstrap handle modal display entirely */
</style>
';

// Pre-compute flags and variables to avoid redundant function calls
$isLoggedIn = $current_user_data['user_id'] ?? false;
$tmpheadervar_isUserActive = $isLoggedIn && $account->isactive();
$tmpheadervar_isUserStaff = $tmpheadervar_isUserActive && $account->isstaff();
$tmpheadervar_isUserAdmin = $tmpheadervar_isUserActive && $account->isadmin();

// Get current page for active states
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isHomePage = in_array($currentPage, ['index', 'home', '']);
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

// Get current page for active states
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Check if we're on the home page - be more thorough
$isHomePage = ($currentPage === 'index' || 
               $currentPage === '' || 
               $currentPath === '/' || 
               $currentPath === '/index' ||
               $currentPath === '/index.php' ||
               $scriptName === '/index.php');

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


echo '
<!-- ======================================================== -->
<!-- START OF HEADER v7 -->
<!-- ======================================================== -->
<!-- TRACE: We are in v7/bg_header.inc -->
<!-- TRACE: currentPage = "' . $currentPage . '" -->
<!-- TRACE: currentPath = "' . $currentPath . '" -->
<!-- TRACE: scriptName = "' . $scriptName . '" -->
<!-- TRACE: isHomePage = ' . ($isHomePage ? 'TRUE' : 'FALSE') . ' -->';

// Begin header output
echo '
<!-- ===== ENHANCED HEADER COMPONENT ===== -->';

if ($isHomePage) {
    // Special header for index/home page
    echo '
<!-- TRACE: ENTERING HOME PAGE HEADER SECTION -->
<header class="top-header home-header '.$navclass.'" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1030; background: white; height: 70px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <div class="container-fluid h-100">
        <div class="row h-100 align-items-center">
            <!-- Logo/Brand - Full size on home page -->
            <div class="col-8 col-md-6">
                <a class="navbar-brand header-logo" href="/">
                    <img class="home-logo" src="//cdn.birthday.gold/public/images/logo/birthday.gold_logo.png" alt="Birthday Gold" style="height: 50px;">
                </a>
            </div>
            
            <!-- Right Side - Signup/Login only -->
            <div class="col-4 col-md-6 d-flex justify-content-end align-items-center">';
    
    if (!$isLoggedIn) {
        echo '
                <a href="/signup-mobile" class="btn btn-primary me-2 d-none d-md-inline-block">Sign Up</a>
                <a href="/login" class="login-btn">Login</a>';
    } else {
        // Still show avatar dropdown for logged in users on home page
        echo '
                <div class="dropdown">
                    <div class="avatar-placeholder rounded-circle" id="bg_avatarMenuMain" data-bs-toggle="dropdown" aria-expanded="false" role="button" 
                    style="background-image: url(' . ($current_user_data['avatar']??'') . ')"></div>
                    <ul class="dropdown-menu dropdown-menu-end pt-0" aria-labelledby="bg_avatarMenuMain">';
        
        // Include the same dropdown menu content
        if ($tmpheadervar_isUserActive) {
            echo '
                        <li class="m-0 p-0">
                            <div class="dropdown-item p-0">
                                <div class="stats-menu-container my-0 p-0">
                                    <div class="stats-menu">
                                        <a href="/myaccount/mail" class="stats-menu-item">
                                            <div class="stats-menu-icon">
                                                <i class="bi bi-envelope"></i>
                                            </div>
                                            <div class="stats-menu-value">'.number_format($tmpheadervar_unreadMailCount).'</div>
                                            <div class="stats-menu-label">Mail</div>
                                        </a>
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
                        <li><a class="dropdown-item" href="/myaccount/"><i class="bi bi-person me-3"></i>Account Home</a></li>';
        }
        
        echo '
                        <li><a class="dropdown-item" href="/myaccount/settings"><i class="bi bi-gear me-3"></i>Settings</a></li>
                        <li><a class="dropdown-item" href="/myaccount/preferences"><i class="bi bi-bell me-3"></i>Preferences</a></li>';
        
        if (($current_user_data['account_plan'] ?? '') !== 'free') {
            echo '
                        <li><a class="dropdown-item" href="/myaccount/billing"><i class="bi bi-credit-card me-3"></i>Billing</a></li>';
        }
        
        if ($tmpheadervar_isUserStaff) {
            echo '
                        <li><a class="dropdown-item" href="/staff"><i class="bi bi-person-badge me-3 text-warning"></i>Staff</a></li>';
        }
        
        if ($tmpheadervar_isUserAdmin) {
            echo '
                        <li><a class="dropdown-item" href="/admin"><i class="bi bi-shield-lock me-3 text-danger"></i>Admin</a></li>';
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
    echo '
<!-- TRACE: ENTERING REGULAR HEADER SECTION -->
<header class="top-header '.$navclass.'" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1030; background: white; height: 56px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <div class="container-fluid h-100">
        <div class="row h-100 align-items-center">
            <!-- Logo/Brand - Smaller on mobile -->
            <div class="col-6 col-md-3">
                <a class="navbar-brand header-logo" href="/">
                    <img class="mobile-logo" src="//cdn.birthday.gold/public/images/logo/bg_icon.png" alt="Company Icon" id="companyIcon" data-bs-toggle="tooltip" data-bs-placement="right" title="birthday.gold Home">
                    <img class="desktop-logo" src="//cdn.birthday.gold/public/images/logo/birthday.gold_logo.png" alt="Company Logo" id="companyLogo">
                </a>
            </div>
            
            <!-- Desktop Navigation (centered) -->
            <div class="col-md-6 d-none d-md-block">
                <nav class="center-nav">
                    <a href="/how-it-works" class="nav-link">How it Works</a>
                    <a href="/discover" class="nav-link">Browse Rewards</a>
                    <a href="/help" class="nav-link">Help</a>
                </nav>
            </div>
            
            <!-- Right Side - Auth State -->
            <div class="col-6 col-md-3 d-flex justify-content-end align-items-center">';

// User Authentication UI
if (!$isLoggedIn) {
    // Logged Out State
    echo '
                <a href="/login" class="login-btn">Login</a>';
} else {
    // Logged In State  
    echo '
                <div class="dropdown">
                    <div class="avatar-placeholder rounded-circle" id="bg_avatarMenuMain" data-bs-toggle="dropdown" aria-expanded="false" role="button" 
                    style="background-image: url(' . ($current_user_data['avatar']??'') . ')"></div>
                    <ul class="dropdown-menu dropdown-menu-end pt-0" aria-labelledby="bg_avatarMenuMain">
                  ';
    
    // User stats in the dropdown menu - For ALL screen sizes
    if ($tmpheadervar_isUserActive) {
        echo '
                        <li class="m-0 p-0">
                            <div class="dropdown-item p-0">
                                <div class="stats-menu-container my-0 p-0">
                                    <div class="stats-menu">
                                        <a href="/myaccount/mail" class="stats-menu-item">
                                            <div class="stats-menu-icon">
                                                <i class="bi bi-envelope"></i>
                                            </div>
                                            <div class="stats-menu-value">'.number_format($tmpheadervar_unreadMailCount).'</div>
                                            <div class="stats-menu-label">Mail</div>
                                        </a>
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
                              <li><a class="dropdown-item" href="/myaccount/"><i class="bi bi-person me-3"></i>Account Home</a></li>';
                              
    }
    
    // Regular menu items
    echo '
                        <li><a class="dropdown-item" href="/myaccount/settings"><i class="bi bi-gear me-3"></i>Settings</a></li>
                        <li><a class="dropdown-item" href="/myaccount/preferences"><i class="bi bi-bell me-3"></i>Preferences</a></li>';
    
    // Only show billing for paid accounts
    if (($current_user_data['account_plan'] ?? '') !== 'free') {
        echo '
                        <li><a class="dropdown-item" href="/myaccount/billing"><i class="bi bi-credit-card me-3"></i>Billing</a></li>';
    }
    
    // Staff/Admin links
    if ($tmpheadervar_isUserStaff) {
        echo '
                        <li><a class="dropdown-item" href="/staff"><i class="bi bi-person-badge me-3 text-warning"></i>Staff</a></li>';
    }
    
    if ($tmpheadervar_isUserAdmin) {
        echo '
                        <li><a class="dropdown-item" href="/admin"><i class="bi bi-shield-lock me-3 text-danger"></i>Admin</a></li>';
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

if (!isset($bodycontentclass)) $bodycontentclass = 'main-content';
/*
// Add spacing div for fixed header
if (!isset($bodycontentclass)) $bodycontentclass = 'main-content';
echo '
<div class="'.$bodycontentclass.'"></div>';
*/
?>