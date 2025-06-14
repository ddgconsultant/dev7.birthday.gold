<?php
#-------------------------------------------------------------------------------
# MOBILE BOTTOM NAVIGATION COMPONENT v7 - IMPROVED
# Enhanced styling with better centering and modern color scheme
# Location: /core/components/v7/bg_bottom_nav_improved.inc
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
<!-- ===== MOBILE BOTTOM NAVIGATION v7 IMPROVED ===== -->
<style>
/* Override existing bottom-nav styles with improved version */
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    z-index: 1020;
    padding: 8px 0 calc(8px + env(safe-area-inset-bottom, 0px));
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
}

.nav-items {
    display: flex;
    justify-content: space-evenly;
    align-items: stretch;
    max-width: 600px;
    margin: 0 auto;
    padding: 0 8px;
    height: 64px;
}

.nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #64748b;
    padding: 8px;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    min-width: 64px;
    position: relative;
    flex: 1;
    max-width: 80px;
}

.nav-item:hover {
    background: rgba(59, 130, 246, 0.08);
    color: #3b82f6;
    text-decoration: none;
}

.nav-item:active {
    transform: scale(0.95);
}

.nav-item.active {
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.12);
}

.nav-item.cta {
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    color: white;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.nav-item.cta:hover {
    background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.nav-icon {
    font-size: 22px;
    margin-bottom: 2px;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 24px;
    width: 24px;
}

.nav-label {
    font-size: 11px;
    font-weight: 500;
    line-height: 1.2;
    white-space: nowrap;
}

.nav-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #ef4444;
    color: white;
    border-radius: 10px;
    padding: 2px 5px;
    font-size: 10px;
    font-weight: 700;
    min-width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
}

/* More Menu Improvements */
.more-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 2000;
    display: none;
    align-items: flex-end;
    animation: fadeIn 0.2s ease;
}

.more-menu {
    background: white;
    width: 100%;
    border-radius: 24px 24px 0 0;
    max-height: 70vh;
    overflow-y: auto;
    padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 1rem);
    animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.more-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
    background: white;
    z-index: 10;
}

.more-menu-item {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.2s ease;
}

.more-menu-item:hover {
    background: #f9fafb;
    color: #3b82f6;
    padding-left: 1.75rem;
}

.more-menu-item.text-danger {
    color: #ef4444;
}

.more-menu-item.text-danger:hover {
    background: #fef2f2;
    color: #dc2626;
}

.more-icon {
    font-size: 20px;
    margin-right: 1rem;
    width: 24px;
    text-align: center;
    color: #9ca3af;
}

.more-menu-item:hover .more-icon {
    color: inherit;
}

/* Responsive adjustments */
@media (max-width: 380px) {
    .nav-label {
        font-size: 10px;
    }
    .nav-icon {
        font-size: 20px;
    }
}

@media (min-width: 768px) {
    .bottom-nav {
        display: none;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}
</style>';

if (!$isLoggedIn) {
    // Logged Out Navigation
    echo '
<nav class="bottom-nav" id="loggedOutNav">
    <div class="nav-items">
        <a href="/" class="nav-item ' . ($currentPage === 'index' ? 'active' : '') . '">
            <i class="nav-icon bi bi-house-fill"></i>
            <span class="nav-label">Home</span>
        </a>
        <a href="/browse-rewards" class="nav-item ' . ($currentPage === 'browse-rewards' ? 'active' : '') . '">
            <i class="nav-icon bi bi-gift-fill"></i>
            <span class="nav-label">Browse</span>
        </a>
        <a href="/signup-mobile" class="nav-item cta">
            <i class="nav-icon bi bi-star-fill"></i>
            <span class="nav-label">Sign Up</span>
        </a>
        <a href="/help" class="nav-item ' . ($currentPage === 'help' ? 'active' : '') . '">
            <i class="nav-icon bi bi-question-circle-fill"></i>
            <span class="nav-label">Help</span>
        </a>
        <a href="/login" class="nav-item ' . ($currentPage === 'login' ? 'active' : '') . '">
            <i class="nav-icon bi bi-person-fill"></i>
            <span class="nav-label">Account</span>
        </a>
    </div>
</nav>';
} else {
    // Logged In Navigation
    echo '
<nav class="bottom-nav" id="loggedInNav">
    <div class="nav-items">
        <a href="/" class="nav-item ' . ($currentPage === 'index' ? 'active' : '') . '">
            <i class="nav-icon bi bi-house-fill"></i>
            <span class="nav-label">Home</span>
        </a>
        <a href="/browse-rewards" class="nav-item ' . ($currentPage === 'browse-rewards' ? 'active' : '') . '">
            <i class="nav-icon bi bi-gift-fill"></i>
            <span class="nav-label">Browse</span>';
    
    if ($browseBadgeCount > 0) {
        echo '
            <span class="nav-badge" id="browseBadge">' . ($browseBadgeCount > 9 ? '9+' : $browseBadgeCount) . '</span>';
    }
    
    echo '
        </a>
        <a href="/myaccount/rewards" class="nav-item cta ' . (strpos($currentPage, 'rewards') !== false ? 'active' : '') . '">
            <i class="nav-icon bi bi-graph-up-arrow"></i>
            <span class="nav-label">My Rewards</span>';
    
    if ($rewardsBadgeCount > 0) {
        echo '
            <span class="nav-badge" id="rewardsBadge">' . ($rewardsBadgeCount > 9 ? '9+' : $rewardsBadgeCount) . '</span>';
    }
    
    echo '
        </a>
        <a href="/help" class="nav-item ' . ($currentPage === 'help' ? 'active' : '') . '">
            <i class="nav-icon bi bi-question-circle-fill"></i>
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
<div class="more-menu-overlay" id="moreMenuOverlay" onclick="closeMoreMenu(event)">
    <div class="more-menu" onclick="event.stopPropagation()">
        <div class="more-menu-header">
            <h5 class="mb-0">More Options</h5>
            <button type="button" class="btn-close" onclick="toggleMoreMenu()" aria-label="Close"></button>
        </div>
        <div class="more-menu-content">
            <a href="/myaccount/" class="more-menu-item">
                <i class="more-icon bi bi-person-circle"></i>
                <span>Profile & Settings</span>
            </a>';

// Only show billing for paid accounts
if ($isLoggedIn && ($current_user_data['account_plan'] ?? '') !== 'free') {
    echo '
            <a href="/myaccount/billing" class="more-menu-item">
                <i class="more-icon bi bi-credit-card-2-front"></i>
                <span>Billing & Subscription</span>
            </a>';
}

echo '
            <a href="/myaccount/referrals" class="more-menu-item">
                <i class="more-icon bi bi-people-fill"></i>
                <span>Refer Friends</span>
            </a>
            <a href="/myaccount/history" class="more-menu-item">
                <i class="more-icon bi bi-clock-history"></i>
                <span>Rewards History</span>
            </a>
            <a href="/myaccount/preferences" class="more-menu-item">
                <i class="more-icon bi bi-bell-fill"></i>
                <span>Notification Settings</span>
            </a>
            <a href="/contact" class="more-menu-item">
                <i class="more-icon bi bi-headset"></i>
                <span>Contact Support</span>
            </a>
            <a href="/terms" class="more-menu-item">
                <i class="more-icon bi bi-file-text-fill"></i>
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
</div>

<script>
function toggleMoreMenu() {
    const overlay = document.getElementById("moreMenuOverlay");
    overlay.style.display = overlay.style.display === "flex" ? "none" : "flex";
}

function closeMoreMenu(event) {
    if (event.target === event.currentTarget) {
        document.getElementById("moreMenuOverlay").style.display = "none";
    }
}

// Add smooth scrolling behavior for iOS
if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
    document.querySelector(".more-menu").style.webkitOverflowScrolling = "touch";
}
</script>';
?>