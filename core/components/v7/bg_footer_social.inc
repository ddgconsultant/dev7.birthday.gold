<?php
#-------------------------------------------------------------------------------
# SOCIAL MODULE FOOTER WITH NAVIGATION SWITCHER
# Switches between main nav and social nav based on current page
# Location: /core/components/v7/bg_footer_social.inc
#-------------------------------------------------------------------------------

// Get user authentication status
$isLoggedIn = $current_user_data['user_id'] ?? false;

// Get current page for active states
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = $_SERVER['REQUEST_URI'];

// Handle signup mode for kiosk/iframe contexts
$signupMode = $session->get('signupmode', '');
$isKioskMode = in_array($signupMode, ['tabletkiosk', 'upgrade']);

// Determine if we should show bottom navigation
$showBottomNav = !$isKioskMode;

// Check if we're in the social module
$isInSocialModule = strpos($currentPath, '/social') !== false;

// Include the rest of the standard footer content
include($dir['core_components'] . '/bg_footer.inc');

// But override the bottom nav inclusion
if ($showBottomNav) {
    if ($isInSocialModule) {
        // Use social navigation for social pages
        include($dir['core_components'] . '/bg_bottom_nav_social.inc');
    } else {
        // Use regular navigation for other pages
        include($dir['core_components'] . '/bg_bottom_nav.inc');
    }
}
?>