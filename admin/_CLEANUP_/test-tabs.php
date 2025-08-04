<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Simple test page for Bootstrap tabs
$pagedata = [
    'pagetitle' => 'Test Bootstrap Tabs',
    'activepage' => 'admin'
];

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<div class="container mt-4">
    <h2>Test Bootstrap Tabs</h2>
    
    <!-- Basic Bootstrap Tabs -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab">Home</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">Profile</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab">Contact</button>
        </li>
    </ul>
    
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="home" role="tabpanel">
            <p class="mt-3">Home content</p>
        </div>
        <div class="tab-pane fade" id="profile" role="tabpanel">
            <p class="mt-3">Profile content</p>
        </div>
        <div class="tab-pane fade" id="contact" role="tabpanel">
            <p class="mt-3">Contact content</p>
        </div>
    </div>
    
    <hr class="my-5">
    
    <h3>Debug CSS Info</h3>
    <div id="debug-info"></div>
</div>

<script>
// Check computed styles on nav elements
document.addEventListener('DOMContentLoaded', function() {
    const nav = document.querySelector('.nav-tabs');
    const navItem = document.querySelector('.nav-item');
    const debugInfo = document.getElementById('debug-info');
    
    if (nav) {
        const navStyles = window.getComputedStyle(nav);
        const navItemStyles = window.getComputedStyle(navItem);
        
        debugInfo.innerHTML = `
            <h4>Nav Tabs Computed Styles:</h4>
            <pre>
Display: ${navStyles.display}
Flex Direction: ${navStyles.flexDirection}
Flex Wrap: ${navStyles.flexWrap}
            </pre>
            <h4>Nav Item Computed Styles:</h4>
            <pre>
Display: ${navItemStyles.display}
            </pre>
        `;
    }
});
</script>

<?php
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>