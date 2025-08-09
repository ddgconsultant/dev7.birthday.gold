<?php
// Social Module Footer Component
// This handles the footer for social pages with the correct navigation

// Close main content wrapper
echo '</main><!-- Close main content wrapper -->';

// Include bottom navigation - use social nav for social pages
$currentPath = $_SERVER['REQUEST_URI'];
if (strpos($currentPath, '/social') !== false) {
    // Include social bottom navigation
    if (file_exists($dir['core_components'] . '/bg_bottom_nav_social.inc')) {
        include($dir['core_components'] . '/bg_bottom_nav_social.inc');
    }
} else {
    // Include regular bottom navigation
    if (file_exists($dir['core_components'] . '/bg_bottom_nav.inc')) {
        include($dir['core_components'] . '/bg_bottom_nav.inc');
    }
}

// Include the rest of the footer content
?>

<!-- ===== ENHANCED MOBILE-FIRST FOOTER ===== -->
<footer class="footer mt-auto">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Footer content can go here if needed -->
            </div>
        </div>
    </div>
</footer>

<!-- More Menu JavaScript -->
<script>
function toggleMoreMenu() {
    const overlay = document.getElementById('moreMenuOverlay');
    if (overlay) {
        overlay.style.display = overlay.style.display === 'flex' ? 'none' : 'flex';
    }
}

// Close more menu when clicking overlay
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('moreMenuOverlay');
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    }
});
</script>

</body>
</html>