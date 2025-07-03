<?php
include($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php');

$pagetitle = 'Confetti Debug Test';
$page_title = 'Confetti Debug Test';
$is_iframe_mode = false;

include($dir['core_components'] . '/bg_pagestart.inc');
include($dir['core_components'] . '/bg_header.inc');
?>

<style>
.confetti {
    position: fixed !important;
    width: 10px !important;
    height: 10px !important;
    background: red !important;
    z-index: 9999 !important;
    animation: confetti-fall linear infinite !important;
}

@keyframes confetti-fall {
    from {
        top: -10px;
        transform: rotate(0deg);
    }
    to {
        top: 100vh;
        transform: rotate(360deg);
    }
}

.test-info {
    background: white;
    padding: 20px;
    margin: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>

<div class="test-info">
    <h1>Confetti Debug Test (With Site Framework)</h1>
    <p>If you see red squares falling, confetti works with the site CSS.</p>
    <p>If they stick at top, the site CSS is preventing animation.</p>
    <button onclick="addConfetti()">Add Confetti</button>
    <button onclick="inspectConfetti()">Inspect Confetti</button>
    <button onclick="checkCSS()">Check CSS Conflicts</button>
    <div id="debug-output" style="margin-top: 20px; font-family: monospace; font-size: 12px;"></div>
</div>

<script>
function addConfetti() {
    const confetti = document.createElement('div');
    confetti.className = 'confetti';
    confetti.style.left = Math.random() * 100 + '%';
    confetti.style.backgroundColor = '#ff0000';
    confetti.style.animationDuration = '3s';
    
    document.body.appendChild(confetti);
    console.log('Added confetti element:', confetti);
    
    setTimeout(() => {
        if (confetti.parentNode) {
            confetti.parentNode.removeChild(confetti);
        }
    }, 3500);
}

function inspectConfetti() {
    const confettiElements = document.querySelectorAll('.confetti');
    const output = document.getElementById('debug-output');
    let html = `<strong>Found ${confettiElements.length} confetti elements:</strong><br>`;
    
    confettiElements.forEach((el, i) => {
        const computed = window.getComputedStyle(el);
        html += `<div style="margin: 10px 0; padding: 10px; background: #f5f5f5;">
            <strong>Confetti ${i}:</strong><br>
            Position: ${computed.position}<br>
            Top: ${computed.top}<br>
            Animation: ${computed.animation}<br>
            Animation Name: ${computed.animationName}<br>
            Animation Duration: ${computed.animationDuration}<br>
            Transform: ${computed.transform}<br>
            Z-Index: ${computed.zIndex}<br>
        </div>`;
    });
    
    output.innerHTML = html;
    console.log('Confetti inspection complete');
}

function checkCSS() {
    const output = document.getElementById('debug-output');
    const allStylesheets = Array.from(document.styleSheets);
    
    let html = `<strong>Loaded Stylesheets (${allStylesheets.length}):</strong><br>`;
    
    allStylesheets.forEach((sheet, i) => {
        try {
            html += `${i + 1}. ${sheet.href || 'Inline styles'}<br>`;
        } catch (e) {
            html += `${i + 1}. [Cross-origin stylesheet]<br>`;
        }
    });
    
    // Check for any CSS rules that might affect position or animation
    html += `<br><strong>Checking for conflicting CSS rules...</strong><br>`;
    
    const testEl = document.createElement('div');
    testEl.className = 'confetti';
    testEl.style.visibility = 'hidden';
    document.body.appendChild(testEl);
    
    const computed = window.getComputedStyle(testEl);
    html += `Test element computed styles:<br>
        Position: ${computed.position}<br>
        Animation: ${computed.animation}<br>
        Top: ${computed.top}<br>`;
    
    document.body.removeChild(testEl);
    
    output.innerHTML = html;
}

// Auto-start
document.addEventListener('DOMContentLoaded', function() {
    console.log('Starting debug confetti test with site framework');
    setTimeout(() => {
        for (let i = 0; i < 3; i++) {
            setTimeout(() => addConfetti(), i * 1000);
        }
    }, 1000);
});
</script>

<?php
$display_footertype='min';
include($dir['core_components'] . '/bg_footer.inc');
$app->outputpage();
?>