<?php
#-------------------------------------------------------------------------------
# MOBILE-FIRST HEADER COMPONENT v7
# Compatible with existing Birthday.Gold v3 infrastructure
# Location: /core/components/v7/bg_header.inc
#-------------------------------------------------------------------------------

// Get user authentication status
$isLoggedIn = $current_user_data['user_id'] ?? false;
$userName = '';
$userInitials = '';

if ($isLoggedIn) {
    $firstName = $current_user_data['first_name'] ?? '';
    $lastName = $current_user_data['last_name'] ?? '';
    $userName = trim($firstName . ' ' . $lastName);
    
    // Generate initials for avatar
    $userInitials = '';
    if ($firstName) $userInitials .= strtoupper(substr($firstName, 0, 1));
    if ($lastName) $userInitials .= strtoupper(substr($lastName, 0, 1));
    if (!$userInitials) $userInitials = strtoupper(substr($userName ?: 'U', 0, 2));
}

// Get current page for active states
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isHomePage = in_array($currentPage, ['index', 'home', '']);
$isBrowsePage = in_array($currentPage, ['browse', 'rewards', 'browse-rewards']);
$isHelpPage = in_array($currentPage, ['help', 'support', 'faq']);
$isAccountPage = in_array($currentPage, ['account', 'profile', 'settings']);

// Handle signup mode for kiosk/iframe contexts
$signupMode = $session->get('signupmode', '');
$isKioskMode = in_array($signupMode, ['tabletkiosk', 'upgrade']);

echo '
<!-- ===== MOBILE-FIRST HEADER COMPONENT v7 ===== -->
<header class="top-header">
    <div class="container-fluid h-100">
        <div class="row h-100 align-items-center">
            <!-- Logo/Brand -->
            <div class="col-6">
                <a href="/" class="logo-text">Birthday.Gold</a>
            </div>
            
            <!-- Desktop Navigation (hidden on mobile) -->
            <div class="col d-none d-md-flex justify-content-center">
                <nav class="navbar-nav flex-row gap-4">
                    <a href="/how-it-works" class="nav-link">How it Works</a>
                    <a href="/browse-rewards" class="nav-link">Browse Rewards</a>
                    <a href="/help" class="nav-link">Help</a>
                </nav>
            </div>
            
            <!-- Right Side - Auth State -->
            <div class="col-6 col-md-auto d-flex justify-content-end align-items-center">';

if (!$isLoggedIn) {
    // Logged Out State
    echo '
                <div id="loggedOutHeader">
                    <a href="/login" class="login-btn">Login</a>
                </div>';
} else {
    // Logged In State  
    echo '
                <div id="loggedInHeader">
                    <div class="dropdown">
                        <button class="user-avatar dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                            ' . htmlspecialchars($userInitials) . '
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/myaccount/"><i class="bi bi-person me-2"></i>Account Home</a></li>
                            <li><a class="dropdown-item" href="/myaccount/settings"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            <li><a class="dropdown-item" href="/myaccount/preferences"><i class="bi bi-bell me-2"></i>Preferences</a></li>';
    
    // Only show billing for paid accounts
    if (($current_user_data['account_plan'] ?? '') !== 'free') {
        echo '
                            <li><a class="dropdown-item" href="/myaccount/billing"><i class="bi bi-credit-card me-2"></i>Billing</a></li>';
    }
    
    echo '
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>';
}

// Add signup exit button for kiosk mode
if ($signupMode != '' && $isKioskMode) {
    echo '
                <a href="/logout" class="signup-exit-btn ms-2" title="Exit Signup">
                    <i class="bi bi-x-square text-info"></i>
                </a>';
}

echo '
            </div>
        </div>
    </div>
</header>';
?>