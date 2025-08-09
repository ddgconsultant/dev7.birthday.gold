<?php
#-------------------------------------------------------------------------------
# SOCIAL MODULE BOTTOM NAVIGATION COMPONENT v7
# For use in /social/* pages
# Location: /core/components/v7/bg_bottom_nav_social.inc
#-------------------------------------------------------------------------------

// Get user authentication status
$isLoggedIn = $current_user_data['user_id'] ?? false;

// Get current page for active states
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = $_SERVER['REQUEST_URI'];

// Don't show on desktop - mobile only
echo '
<!-- ===== SOCIAL BOTTOM NAVIGATION v7 ===== -->
<nav class="bottom-nav social-nav" id="socialBottomNav">
    <div class="nav-items">
        <a href="/social/" class="nav-item' . ($currentPage === 'index' && strpos($currentPath, '/social') !== false ? ' active' : '') . '">
            <i class="nav-icon bi bi-house"></i>
            <span class="nav-label">Home</span>
        </a>
        <a href="/social/search" class="nav-item' . ($currentPage === 'search' ? ' active' : '') . '">
            <i class="nav-icon bi bi-search"></i>
            <span class="nav-label">Search</span>
        </a>
        <a href="/social/create" class="nav-item cta' . ($currentPage === 'create' ? ' active' : '') . '">
            <i class="nav-icon bi bi-plus-circle"></i>
            <span class="nav-label">Create</span>
        </a>
        <a href="/social/activity" class="nav-item' . ($currentPage === 'activity' ? ' active' : '') . '">
            <i class="nav-icon bi bi-bookmark"></i>
            <span class="nav-label">Activity</span>
        </a>
        <a href="/social/settings" class="nav-item' . ($currentPage === 'settings' ? ' active' : '') . '">
            <i class="nav-icon bi bi-gear"></i>
            <span class="nav-label">Settings</span>
        </a>
    </div>
</nav>

<style>
/* Social nav specific styles */
.social-nav {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
}

.social-nav .nav-item.cta {
    position: relative;
}

.social-nav .nav-item.cta .nav-icon {
    font-size: 1.5rem;
    color: var(--primary);
}

/* Hide on desktop - this is mobile only */
@media (min-width: 992px) {
    .social-nav {
        display: none !important;
    }
}

/* Desktop navigation bar (horizontal) for social */
@media (min-width: 992px) {
    .social-desktop-nav {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 1rem 0;
        background: var(--gray-50);
        border-bottom: 1px solid var(--gray-200);
        position: sticky;
        top: 60px;
        z-index: 100;
    }
    
    .social-desktop-nav .nav-items {
        display: flex;
        gap: 2rem;
    }
    
    .social-desktop-nav .nav-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        transition: all 0.2s;
        text-decoration: none;
        color: var(--gray-700);
    }
    
    .social-desktop-nav .nav-item:hover {
        background: var(--gray-100);
    }
    
    .social-desktop-nav .nav-item.active {
        background: var(--primary);
        color: white;
    }
}
</style>';

// Add desktop navigation for social pages
if (strpos($currentPath, '/social') !== false) {
    echo '
    <!-- Desktop Social Navigation -->
    <div class="social-desktop-nav d-none d-lg-flex">
        <div class="nav-items">
            <a href="/social/" class="nav-item' . ($currentPage === 'index' ? ' active' : '') . '">
                <i class="bi bi-house"></i>
                <span>Home</span>
            </a>
            <a href="/social/search" class="nav-item' . ($currentPage === 'search' ? ' active' : '') . '">
                <i class="bi bi-search"></i>
                <span>Search</span>
            </a>
            <a href="/social/create" class="nav-item' . ($currentPage === 'create' ? ' active' : '') . '">
                <i class="bi bi-plus-circle"></i>
                <span>Create Post</span>
            </a>
            <a href="/social/activity" class="nav-item' . ($currentPage === 'activity' ? ' active' : '') . '">
                <i class="bi bi-bookmark"></i>
                <span>Activity</span>
            </a>
            <a href="/social/settings" class="nav-item' . ($currentPage === 'settings' ? ' active' : '') . '">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
        </div>
    </div>';
}
?>