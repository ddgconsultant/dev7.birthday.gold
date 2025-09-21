<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Minimal test page
$page_title = 'Nav Tabs Test';

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container mt-5">
    <h1>Nav Tabs Test</h1>
    
    <h2>Raw Bootstrap Nav Tabs</h2>
    <ul class="nav nav-tabs" id="testTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">Home</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">Profile</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab" aria-controls="contact" aria-selected="false">Contact</button>
        </li>
    </ul>
    
    <div class="tab-content" id="testTabContent">
        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">Home content</div>
        <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">Profile content</div>
        <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">Contact content</div>
    </div>
    
    <hr>
    
    <h2>Debug Info</h2>
    <p>UI Version: <?= $website['ui_version'] ?></p>
    <p>Style Version: <?= $styvers ?? 'not set' ?></p>
    
    <h3>Computed Styles (check in browser dev tools)</h3>
    <div id="debug-nav-tabs" class="nav nav-tabs">
        <div class="nav-item">Debug Nav Item</div>
    </div>
</div>

<script>
// Log computed styles for debugging
document.addEventListener('DOMContentLoaded', function() {
    const navTabs = document.querySelector('.nav-tabs');
    const styles = window.getComputedStyle(navTabs);
    console.log('Nav-tabs computed styles:');
    console.log('Display:', styles.display);
    console.log('Flex-direction:', styles.flexDirection);
    console.log('Padding-left:', styles.paddingLeft);
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>