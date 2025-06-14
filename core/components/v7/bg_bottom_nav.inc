<?php
#-------------------------------------------------------------------------------
# MOBILE BOTTOM NAVIGATION COMPONENT v7
# Compatible with existing Birthday.Gold v3 infrastructure
# Location: /core/components/v7/bg_bottom_nav.inc
#-------------------------------------------------------------------------------

// Get user authentication status
$isLoggedIn = $current_user_data['user_id'] ?? false;

// Get current page for active states
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Handle signup mode for kiosk/iframe contexts
$signupMode = $session->get('signupmode', '');
$isKioskMode = in_array($signupMode, ['tabletkiosk', 'upgrade']);

// Don't show bottom nav in kiosk mode or certain pages
$hideNavPages = ['login', 'register', 'validate-account', 'password-reset'];
if ($isKioskMode || in_array($currentPage, $hideNavPages)) {
    return;
}

// Get badge counts (integrate with existing notification system)
$rewardsBadgeCount = 0;
$browseBadgeCount = 0;

if ($isLoggedIn) {
    // Check for pending rewards or notifications
    try {
        $sql = "SELECT COUNT(*) as count FROM bg_user_rewards 
                WHERE user_id = :user_id AND status = 'pending' AND expires_at > NOW()";
        $result = $database->query($sql, [':user_id' => $current_user_data['user_id']]);
        $rewardsBadgeCount = $result[0]['count'] ?? 0;
        
        // Check for new businesses or offers
        $sql = "SELECT COUNT(*) as count FROM bg_companies 
                WHERE company_status = 'active' 
                AND created_datetime > DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $result = $database->query($sql);
        $browseBadgeCount = $result[0]['count'] ?? 0;
    } catch (Exception $e) {
        // Fail silently if database query fails
        error_log('Badge count error: ' . $e->getMessage());
    }
}

echo '
<!-- ===== MOBILE BOTTOM NAVIGATION v7 ===== -->';

if (!$isLoggedIn) {
    // Logged Out Navigation
    echo '
<nav class="bottom-nav" id="loggedOutNav">
    <div class="nav-items">
        <a href="/" class="nav-item">
            <i class="nav-icon bi bi-house"></i>
            <span class="nav-label">Home</span>
        </a>
        <a href="/discover" class="nav-item">
            <i class="nav-icon bi bi-gift"></i>
            <span class="nav-label">Browse</span>
        </a>
        <a href="/signup-mobile" class="nav-item cta">
            <i class="nav-icon bi bi-star"></i>
            <span class="nav-label">Sign Up</span>
        </a>
        <a href="/help" class="nav-item">
            <i class="nav-icon bi bi-question-circle"></i>
            <span class="nav-label">Help</span>
        </a>
        <a href="/login" class="nav-item">
            <i class="nav-icon bi bi-person"></i>
            <span class="nav-label">Account</span>
        </a>
    </div>
</nav>';
} else {
    // Logged In Navigation
    echo '
<nav class="bottom-nav" id="loggedInNav">
    <div class="nav-items">
        <a href="/" class="nav-item">
            <i class="nav-icon bi bi-house"></i>
            <span class="nav-label">Home</span>
        </a>
        <a href="/discover" class="nav-item">
            <i class="nav-icon bi bi-gift"></i>
            <span class="nav-label">Browse</span>';
    
    if ($browseBadgeCount > 0) {
        echo '
            <span class="nav-badge" id="browseBadge">' . ($browseBadgeCount > 9 ? '9+' : $browseBadgeCount) . '</span>';
    }
    
    echo '
        </a>
        <a href="/myaccount/rewards" class="nav-item cta">
            <i class="nav-icon bi bi-graph-up"></i>
            <span class="nav-label">My Rewards</span>';
    
    if ($rewardsBadgeCount > 0) {
        echo '
            <span class="nav-badge" id="rewardsBadge">' . ($rewardsBadgeCount > 9 ? '9+' : $rewardsBadgeCount) . '</span>';
    }
    
    echo '
        </a>
        <a href="/help" class="nav-item">
            <i class="nav-icon bi bi-question-circle"></i>
            <span class="nav-label">Help</span>
        </a>
        <a href="#" class="nav-item" onclick="toggleMoreMenu()">
            <i class="nav-icon bi bi-three-dots"></i>
            <span class="nav-label">More</span>
        </a>
    </div>
</nav>';
}

echo '
<!-- ===== MORE MENU OVERLAY ===== -->
<div class="more-menu-overlay" id="moreMenuOverlay">
    <div class="more-menu">
        <div class="more-menu-header">
            <h5 class="mb-0">More Features</h5>
            <button type="button" class="btn-close" onclick="toggleMoreMenu()" aria-label="Close"></button>
        </div>
        <div class="more-menu-content">
            <a href="/myaccount/" class="more-menu-item">
                <i class="more-icon bi bi-person"></i>
                <span>Profile & Settings</span>
            </a>';

// Only show billing for paid accounts
if ($isLoggedIn && ($current_user_data['account_plan'] ?? '') !== 'free') {
    echo '
            <a href="/myaccount/billing" class="more-menu-item">
                <i class="more-icon bi bi-credit-card"></i>
                <span>Billing & Subscription</span>
            </a>';
}

echo '
            <a href="/myaccount/referrals" class="more-menu-item">
                <i class="more-icon bi bi-people"></i>
                <span>Refer Friends</span>
            </a>
            <a href="/myaccount/history" class="more-menu-item">
                <i class="more-icon bi bi-bar-chart"></i>
                <span>Rewards History</span>
            </a>
            <a href="/myaccount/preferences" class="more-menu-item">
                <i class="more-icon bi bi-bell"></i>
                <span>Notification Settings</span>
            </a>
            <a href="/contact" class="more-menu-item">
                <i class="more-icon bi bi-headset"></i>
                <span>Contact Support</span>
            </a>
            <a href="/legalhub" class="more-menu-item">
                <i class="more-icon bi bi-file-text"></i>
                <span>Terms & Privacy</span>
            </a>';

if ($isLoggedIn) {
    echo '
            <a href="/logout" class="more-menu-item text-danger">
                <i class="more-icon bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>';
}

echo '
        </div>
    </div>
</div>';
?>