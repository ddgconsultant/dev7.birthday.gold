<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Force output before framework
ob_end_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Nav Tabs Diagnostic</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Load Bootstrap first -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Load the theme CSS based on ui_version -->
    <link href="/public/css/<?= $website['ui_version'] ?>/bg_theme.css" rel="stylesheet">
    <link href="/public/css/<?= $website['ui_version'] ?>/bg_header.css" rel="stylesheet">
    
    <style>
        .diagnostic-info {
            background: #f8f9fa;
            padding: 15px;
            margin: 20px;
            border: 1px solid #dee2e6;
            font-family: monospace;
        }
        .test-container {
            margin: 20px;
            padding: 20px;
            border: 2px solid #0d6efd;
        }
        /* Override to test */
        .override-test .nav-tabs {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
        }
        
        /* Remove all transforms and transitions that might affect layout */
        .override-test * {
            transform: none !important;
            transition: none !important;
        }
        
        /* Ensure body doesn't affect children */
        .override-test {
            display: block !important;
            flex-direction: initial !important;
        }
    </style>
</head>
<body>
    <div class="diagnostic-info">
        <h3>Configuration</h3>
        <p>UI Version: <strong><?= $website['ui_version'] ?></strong></p>
        <p>CSS Loaded: /public/css/<?= $website['ui_version'] ?>/bg_header.css</p>
    </div>

    <div class="test-container">
        <h2>Standard Nav Tabs</h2>
        <ul class="nav nav-tabs" id="standardTabs">
            <li class="nav-item">
                <button class="nav-link active">Tab 1</button>
            </li>
            <li class="nav-item">
                <button class="nav-link">Tab 2</button>
            </li>
            <li class="nav-item">
                <button class="nav-link">Tab 3</button>
            </li>
        </ul>
        <div id="standardInfo" class="diagnostic-info mt-3"></div>
    </div>

    <div class="test-container override-test">
        <h2>Nav Tabs with Override</h2>
        <ul class="nav nav-tabs" id="overrideTabs">
            <li class="nav-item">
                <button class="nav-link active">Tab 1</button>
            </li>
            <li class="nav-item">
                <button class="nav-link">Tab 2</button>
            </li>
            <li class="nav-item">
                <button class="nav-link">Tab 3</button>
            </li>
        </ul>
        <div id="overrideInfo" class="diagnostic-info mt-3"></div>
    </div>

    <div class="test-container">
        <h2>CSS Rules Applied to .nav-tabs</h2>
        <div id="cssRules" class="diagnostic-info"></div>
    </div>

    <script>
    function getAppliedStyles(element) {
        const styles = window.getComputedStyle(element);
        return {
            display: styles.display,
            flexDirection: styles.flexDirection,
            flexWrap: styles.flexWrap,
            width: styles.width,
            paddingLeft: styles.paddingLeft,
            transform: styles.transform,
            position: styles.position,
            boxSizing: styles.boxSizing
        };
    }

    function findCSSRules(selector) {
        const rules = [];
        for (let i = 0; i < document.styleSheets.length; i++) {
            try {
                const sheet = document.styleSheets[i];
                const cssRules = sheet.cssRules || sheet.rules;
                
                for (let j = 0; j < cssRules.length; j++) {
                    const rule = cssRules[j];
                    if (rule.selectorText && rule.selectorText.includes(selector)) {
                        rules.push({
                            selector: rule.selectorText,
                            styles: rule.style.cssText,
                            source: sheet.href || 'inline'
                        });
                    }
                }
            } catch (e) {
                // Cross-origin stylesheets will throw errors
            }
        }
        return rules;
    }

    window.addEventListener('load', function() {
        // Check standard tabs
        const standardTabs = document.getElementById('standardTabs');
        const standardStyles = getAppliedStyles(standardTabs);
        document.getElementById('standardInfo').innerHTML = 
            '<strong>Computed Styles:</strong><br>' + 
            JSON.stringify(standardStyles, null, 2).replace(/\n/g, '<br>');

        // Check override tabs
        const overrideTabs = document.getElementById('overrideTabs');
        const overrideStyles = getAppliedStyles(overrideTabs);
        document.getElementById('overrideInfo').innerHTML = 
            '<strong>Computed Styles:</strong><br>' + 
            JSON.stringify(overrideStyles, null, 2).replace(/\n/g, '<br>');

        // Find CSS rules
        const navTabsRules = findCSSRules('nav-tabs');
        const navRules = findCSSRules('.nav');
        const ulRules = findCSSRules('ul');
        
        let rulesHTML = '<strong>CSS Rules affecting nav-tabs:</strong><br>';
        
        [...navTabsRules, ...navRules, ...ulRules].forEach(rule => {
            rulesHTML += `<br><strong>${rule.selector}</strong> (${rule.source})<br>`;
            rulesHTML += `<pre>${rule.styles}</pre>`;
        });
        
        document.getElementById('cssRules').innerHTML = rulesHTML;

        // Check parent elements
        console.log('Body styles:', getAppliedStyles(document.body));
        console.log('Parent container styles:', getAppliedStyles(standardTabs.parentElement));
    });
    </script>
</body>
</html>