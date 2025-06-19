<?php
// Direct test without any framework
?>
<!DOCTYPE html>
<html>
<head>
    <title>Nav Tabs Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Load v3 CSS -->
    <link href="/public/css/v3/bg_theme.css" rel="stylesheet">  
    <link href="/public/css/v3/bg_header.css" rel="stylesheet">
    
    <style>
        .test-section {
            margin: 50px;
            padding: 20px;
            border: 2px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="test-section">
        <h2>Test with V3 CSS</h2>
        <ul class="nav nav-tabs" id="v3Tabs">
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
    </div>
    
    <!-- Now load v7 CSS to see what breaks -->
    <link href="/public/css/v7/bg_header.css" rel="stylesheet">
    
    <div class="test-section">
        <h2>Test with V7 CSS Added</h2>
        <ul class="nav nav-tabs" id="v7Tabs">
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
    </div>
    
    <script>
        // Log what's happening
        setTimeout(() => {
            const v3Tabs = document.querySelector('#v3Tabs');
            const v7Tabs = document.querySelector('#v7Tabs');
            
            console.log('V3 Tabs computed style:');
            console.log('Display:', getComputedStyle(v3Tabs).display);
            console.log('Flex-direction:', getComputedStyle(v3Tabs).flexDirection);
            
            console.log('\nV7 Tabs computed style:');
            console.log('Display:', getComputedStyle(v7Tabs).display);
            console.log('Flex-direction:', getComputedStyle(v7Tabs).flexDirection);
            
            // Check body styles
            console.log('\nBody computed style:');
            console.log('Display:', getComputedStyle(document.body).display);
            console.log('Flex-direction:', getComputedStyle(document.body).flexDirection);
        }, 100);
    </script>
</body>
</html>