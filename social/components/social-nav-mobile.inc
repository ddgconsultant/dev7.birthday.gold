<?php
// Social Mobile Navigation Component
// Include this on all social pages for consistent bottom navigation

// Get current page for active state
$currentPageName = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Mobile Bottom Navigation for Social -->
<nav class="social-nav-mobile">
    <a href="/social/" class="social-nav-mobile-item<?php echo ($currentPageName === 'index' ? ' active' : ''); ?>">
        <i class="bi bi-house-door-fill"></i>
        <span>Home</span>
    </a>
    <a href="/social/search" class="social-nav-mobile-item<?php echo ($currentPageName === 'search' ? ' active' : ''); ?>">
        <i class="bi bi-search"></i>
        <span>Search</span>
    </a>
    <a href="/social/create" class="social-nav-mobile-item<?php echo ($currentPageName === 'create' ? ' active' : ''); ?>">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Create</span>
    </a>
    <a href="/social/activity" class="social-nav-mobile-item<?php echo ($currentPageName === 'activity' ? ' active' : ''); ?>">
        <i class="bi bi-bookmark-fill"></i>
        <span>Activity</span>
    </a>
    <a href="/social/settings" class="social-nav-mobile-item<?php echo ($currentPageName === 'settings' ? ' active' : ''); ?>">
        <i class="bi bi-gear-fill"></i>
        <span>Settings</span>
    </a>
</nav>

<style>
/* Mobile Bottom Navigation */
.social-nav-mobile {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid var(--gray-200, #dee2e6);
    box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
    z-index: 1020;
    padding: 0.5rem 0;
}

@media (max-width: 991.98px) {
    .social-nav-mobile {
        display: flex;
        justify-content: space-around;
        align-items: center;
    }
    
    /* Add padding to body to account for bottom nav */
    body {
        padding-bottom: 60px;
    }
}

.social-nav-mobile-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem;
    text-decoration: none;
    color: var(--gray-600, #6c757d);
    font-size: 0.75rem;
    transition: all 0.2s;
}

.social-nav-mobile-item:hover {
    color: var(--primary, #007bff);
}

.social-nav-mobile-item.active {
    color: var(--primary, #007bff);
}

.social-nav-mobile-item i {
    font-size: 1.25rem;
}

.social-nav-mobile-item span {
    font-size: 0.65rem;
}
</style>