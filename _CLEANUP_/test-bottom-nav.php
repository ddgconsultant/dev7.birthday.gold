<?php
/**
 * Test page for improved bottom navigation
 * This page demonstrates the enhanced mobile navigation with better styling
 */

// Include necessary files (adjust paths as needed)
require_once($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Set page title
$pagetitle = 'Bottom Navigation Test';

// Include header
include($_SERVER['DOCUMENT_ROOT'] . '/core/v7/header.inc');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bottom Navigation Test - Birthday Gold</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    
    <!-- Improved Bottom Nav CSS -->
    <link rel="stylesheet" href="/public/css/v7/bottom-nav-improved.css">
    
    <style>
        body {
            padding-bottom: 80px; /* Space for bottom nav */
            background-color: #f8f9fa;
        }
        
        .demo-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .demo-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .demo-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .color-swatch {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            margin: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 8px 0;
            display: flex;
            align-items: center;
        }
        
        .feature-list li i {
            color: #3b82f6;
            margin-right: 12px;
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="demo-header">
            <h1>Improved Bottom Navigation</h1>
            <p class="text-muted">Testing the enhanced mobile navigation design</p>
        </div>
        
        <div class="demo-card">
            <h2 class="h4 mb-4">Key Improvements</h2>
            <ul class="feature-list">
                <li><i class="bi bi-check-circle-fill"></i> Icons properly centered using flexbox</li>
                <li><i class="bi bi-check-circle-fill"></i> Consistent box sizes with equal spacing</li>
                <li><i class="bi bi-check-circle-fill"></i> Modern blue color scheme</li>
                <li><i class="bi bi-check-circle-fill"></i> Smooth animations and transitions</li>
                <li><i class="bi bi-check-circle-fill"></i> Better visual hierarchy</li>
                <li><i class="bi bi-check-circle-fill"></i> Improved accessibility</li>
            </ul>
        </div>
        
        <div class="demo-card">
            <h2 class="h4 mb-4">Color Palette</h2>
            <div class="mb-3">
                <span class="color-swatch" style="background: #3b82f6;"></span>
                <span class="color-swatch" style="background: #6366f1;"></span>
                <span class="color-swatch" style="background: #64748b;"></span>
                <span class="color-swatch" style="background: #ef4444;"></span>
                <span class="color-swatch" style="background: #f8f9fa;"></span>
            </div>
            <p class="text-muted mb-0">The new color scheme uses modern blue tones for better visual appeal and accessibility.</p>
        </div>
        
        <div class="demo-card">
            <h2 class="h4 mb-4">Navigation States</h2>
            <p><strong>Default:</strong> Gray icons with subtle hover effects</p>
            <p><strong>Active:</strong> Blue background with blue icons</p>
            <p><strong>CTA Button:</strong> Gradient background with white text</p>
            <p><strong>Badges:</strong> Red notification badges with shadow</p>
        </div>
        
        <div class="demo-card">
            <h2 class="h4 mb-4">Responsive Design</h2>
            <p>The navigation automatically adjusts for different screen sizes:</p>
            <ul>
                <li>Hidden on tablets and desktop (768px+)</li>
                <li>Smaller icons and text on small phones (380px-)</li>
                <li>Safe area padding for newer iPhones</li>
            </ul>
        </div>
        
        <div class="demo-card">
            <h2 class="h4 mb-4">Test Instructions</h2>
            <ol>
                <li>View this page on a mobile device or use browser dev tools</li>
                <li>Try clicking different navigation items</li>
                <li>Test the "More" menu overlay</li>
                <li>Check hover states and animations</li>
                <li>Verify icons are properly centered</li>
            </ol>
        </div>
    </div>
    
    <?php
    // Include the improved bottom navigation
    include($_SERVER['DOCUMENT_ROOT'] . '/core/components/v7/bg_bottom_nav_improved.inc');
    ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>