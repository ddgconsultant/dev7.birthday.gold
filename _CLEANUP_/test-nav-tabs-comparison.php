<?php
// Toggle between v3 and v7
$test_version = isset($_GET['version']) ? $_GET['version'] : $website['ui_version'];
$original_ui_version = $website['ui_version'];

// Temporarily set the UI version for testing
$website['ui_version'] = $test_version;
$dir['blade'] = $dir['core'] . '/' . $website['ui_version'];
$dir['core_components'] = $dir['core'] . '/components/' . $website['ui_version'];

include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

$page_title = 'Nav Tabs Comparison Test';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Nav Tabs Test - Version <?= $test_version ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Version-specific theme CSS -->
    <link href="/public/css/<?= $test_version ?>/bg_theme.css" rel="stylesheet">
    
    <!-- Let's also check if theme.css is loaded -->
    <link href="/public/css/<?= $test_version ?>/theme.css" rel="stylesheet">
    
    <style>
        body { padding: 20px; }
        .debug-info { 
            background: #f8f9fa; 
            padding: 15px; 
            margin: 20px 0; 
            border-radius: 5px;
        }
        .version-switch {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="version-switch">
        <a href="?version=v3" class="btn btn-sm <?= $test_version == 'v3' ? 'btn-primary' : 'btn-outline-primary' ?>">v3</a>
        <a href="?version=v7" class="btn btn-sm <?= $test_version == 'v7' ? 'btn-primary' : 'btn-outline-primary' ?>">v7</a>
    </div>

    <div class="container">
        <h1>Nav Tabs Test - Version <?= $test_version ?></h1>
        
        <div class="debug-info">
            <h5>Debug Information:</h5>
            <ul>
                <li>UI Version: <strong><?= $test_version ?></strong></li>
                <li>Theme CSS Path: <code>/public/css/<?= $test_version ?>/bg_theme.css</code></li>
                <li>Theme.css Path: <code>/public/css/<?= $test_version ?>/theme.css</code></li>
                <li>Original UI Version: <?= $original_ui_version ?></li>
            </ul>
        </div>
        
        <h2>Bootstrap Nav Tabs Test</h2>
        
        <!-- Standard Bootstrap Nav Tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Home</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">Profile</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact-tab-pane" type="button" role="tab" aria-controls="contact-tab-pane" aria-selected="false">Contact</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link disabled" id="disabled-tab" data-bs-toggle="tab" data-bs-target="#disabled-tab-pane" type="button" role="tab" aria-controls="disabled-tab-pane" aria-selected="false" disabled>Disabled</button>
            </li>
        </ul>
        
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab" tabindex="0">
                <p class="mt-3">Home tab content. This is the first tab.</p>
            </div>
            <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                <p class="mt-3">Profile tab content. This is the second tab.</p>
            </div>
            <div class="tab-pane fade" id="contact-tab-pane" role="tabpanel" aria-labelledby="contact-tab" tabindex="0">
                <p class="mt-3">Contact tab content. This is the third tab.</p>
            </div>
            <div class="tab-pane fade" id="disabled-tab-pane" role="tabpanel" aria-labelledby="disabled-tab" tabindex="0">
                <p class="mt-3">Disabled tab content.</p>
            </div>
        </div>
        
        <hr class="my-5">
        
        <h2>Computed Styles Debug</h2>
        <div id="computed-styles" class="debug-info">
            <p>Open browser developer tools and inspect the nav-tabs element to see computed styles.</p>
        </div>
        
        <hr class="my-5">
        
        <h2>CSS Variable Test</h2>
        <div class="debug-info">
            <p>Testing CSS variables used by nav-tabs:</p>
            <div id="css-vars"></div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Debug script to check computed styles
    document.addEventListener('DOMContentLoaded', function() {
        const navTabs = document.querySelector('.nav-tabs');
        const navLink = document.querySelector('.nav-link');
        const styles = window.getComputedStyle(navTabs);
        const linkStyles = window.getComputedStyle(navLink);
        
        // Log to console
        console.log('Nav Tabs Computed Styles:');
        console.log('Display:', styles.display);
        console.log('Border-bottom:', styles.borderBottom);
        console.log('--falcon-nav-tabs-border-width:', styles.getPropertyValue('--falcon-nav-tabs-border-width'));
        console.log('--falcon-nav-tabs-border-color:', styles.getPropertyValue('--falcon-nav-tabs-border-color'));
        
        console.log('\nNav Link Computed Styles:');
        console.log('Display:', linkStyles.display);
        console.log('Border:', linkStyles.border);
        console.log('Border-radius:', linkStyles.borderRadius);
        
        // Display CSS variables
        const rootStyles = getComputedStyle(document.documentElement);
        const cssVarsDiv = document.getElementById('css-vars');
        const falconVars = [
            '--falcon-border-width',
            '--falcon-border-color',
            '--falcon-border-radius',
            '--falcon-body-bg',
            '--falcon-emphasis-color'
        ];
        
        let varsHtml = '<ul>';
        falconVars.forEach(varName => {
            const value = rootStyles.getPropertyValue(varName);
            varsHtml += `<li><code>${varName}</code>: ${value || '<span style="color: red;">NOT DEFINED</span>'}</li>`;
        });
        varsHtml += '</ul>';
        cssVarsDiv.innerHTML = varsHtml;
    });
    </script>
</body>
</html>