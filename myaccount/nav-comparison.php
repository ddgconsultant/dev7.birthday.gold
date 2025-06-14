<?php
/**
 * Navigation comparison page - Before and After
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Comparison - Birthday Gold</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        
        .comparison-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .comparison-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .comparison-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .comparison-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .comparison-header {
            padding: 20px;
            text-align: center;
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        .comparison-header.before {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .comparison-header.after {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .nav-preview {
            position: relative;
            height: 80px;
            background: white;
            border-top: 1px solid #e5e7eb;
        }
        
        /* Original Navigation Styles */
        .nav-original {
            display: flex;
            justify-content: space-around;
            align-items: center;
            height: 100%;
            padding: 0 10px;
        }
        
        .nav-original .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #6c757d;
            padding: 8px 12px;
            min-width: 60px;
            position: relative;
        }
        
        .nav-original .nav-item.active {
            color: #198754;
            background: #fff8e1;
        }
        
        .nav-original .nav-item.cta {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #212529;
            border-radius: 12px;
        }
        
        .nav-original .nav-icon {
            font-size: 1.3rem;
            margin-bottom: 4px;
        }
        
        .nav-original .nav-label {
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Improved Navigation Styles */
        .nav-improved {
            display: flex;
            justify-content: space-evenly;
            align-items: stretch;
            height: 100%;
            padding: 8px;
        }
        
        .nav-improved .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #64748b;
            padding: 8px;
            border-radius: 12px;
            transition: all 0.3s ease;
            flex: 1;
            max-width: 80px;
            position: relative;
        }
        
        .nav-improved .nav-item:hover {
            background: rgba(59, 130, 246, 0.08);
            color: #3b82f6;
        }
        
        .nav-improved .nav-item.active {
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.12);
        }
        
        .nav-improved .nav-item.cta {
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
            color: white;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        
        .nav-improved .nav-icon {
            font-size: 22px;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 24px;
            width: 24px;
        }
        
        .nav-improved .nav-label {
            font-size: 11px;
            font-weight: 500;
            line-height: 1.2;
        }
        
        .feature-comparison {
            padding: 20px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .feature-item:last-child {
            border-bottom: none;
        }
        
        .feature-icon {
            margin-right: 12px;
            font-size: 20px;
        }
        
        .feature-icon.bad {
            color: #ef4444;
        }
        
        .feature-icon.good {
            color: #10b981;
        }
        
        .issues-list, .improvements-list {
            list-style: none;
            padding: 0;
            margin: 20px;
        }
        
        .issues-list li, .improvements-list li {
            padding: 8px 0;
            display: flex;
            align-items: center;
        }
        
        .issues-list li i {
            color: #ef4444;
            margin-right: 8px;
        }
        
        .improvements-list li i {
            color: #10b981;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="comparison-container">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold">Navigation Comparison</h1>
            <p class="lead text-muted">Before and after improvements to the mobile bottom navigation</p>
        </div>
        
        <div class="comparison-grid">
            <!-- Before -->
            <div class="comparison-card">
                <div class="comparison-header before">
                    <i class="bi bi-x-circle"></i> Before (Original)
                </div>
                
                <div class="nav-preview">
                    <div class="nav-original">
                        <a href="#" class="nav-item active">
                            <i class="nav-icon bi bi-house"></i>
                            <span class="nav-label">Home</span>
                        </a>
                        <a href="#" class="nav-item">
                            <i class="nav-icon bi bi-gift"></i>
                            <span class="nav-label">Browse</span>
                        </a>
                        <a href="#" class="nav-item cta">
                            <i class="nav-icon bi bi-star"></i>
                            <span class="nav-label">Sign Up</span>
                        </a>
                        <a href="#" class="nav-item">
                            <i class="nav-icon bi bi-question-circle"></i>
                            <span class="nav-label">Help</span>
                        </a>
                        <a href="#" class="nav-item">
                            <i class="nav-icon bi bi-person"></i>
                            <span class="nav-label">Account</span>
                        </a>
                    </div>
                </div>
                
                <ul class="issues-list">
                    <li><i class="bi bi-x-circle-fill"></i> Icons not properly centered</li>
                    <li><i class="bi bi-x-circle-fill"></i> Inconsistent box sizes</li>
                    <li><i class="bi bi-x-circle-fill"></i> Gold/orange color scheme dated</li>
                    <li><i class="bi bi-x-circle-fill"></i> Poor visual hierarchy</li>
                    <li><i class="bi bi-x-circle-fill"></i> No hover states</li>
                </ul>
            </div>
            
            <!-- After -->
            <div class="comparison-card">
                <div class="comparison-header after">
                    <i class="bi bi-check-circle"></i> After (Improved)
                </div>
                
                <div class="nav-preview">
                    <div class="nav-improved">
                        <a href="#" class="nav-item active">
                            <i class="nav-icon bi bi-house-fill"></i>
                            <span class="nav-label">Home</span>
                        </a>
                        <a href="#" class="nav-item">
                            <i class="nav-icon bi bi-gift-fill"></i>
                            <span class="nav-label">Browse</span>
                        </a>
                        <a href="#" class="nav-item cta">
                            <i class="nav-icon bi bi-star-fill"></i>
                            <span class="nav-label">Sign Up</span>
                        </a>
                        <a href="#" class="nav-item">
                            <i class="nav-icon bi bi-question-circle-fill"></i>
                            <span class="nav-label">Help</span>
                        </a>
                        <a href="#" class="nav-item">
                            <i class="nav-icon bi bi-person-fill"></i>
                            <span class="nav-label">Account</span>
                        </a>
                    </div>
                </div>
                
                <ul class="improvements-list">
                    <li><i class="bi bi-check-circle-fill"></i> Perfect icon centering with flexbox</li>
                    <li><i class="bi bi-check-circle-fill"></i> Uniform box sizes and spacing</li>
                    <li><i class="bi bi-check-circle-fill"></i> Modern blue color palette</li>
                    <li><i class="bi bi-check-circle-fill"></i> Clear visual hierarchy</li>
                    <li><i class="bi bi-check-circle-fill"></i> Smooth hover animations</li>
                </ul>
            </div>
        </div>
        
        <div class="mt-5">
            <div class="comparison-card">
                <h3 class="text-center py-4">Technical Improvements</h3>
                <div class="feature-comparison">
                    <div class="feature-item">
                        <i class="feature-icon good bi bi-check-circle-fill"></i>
                        <div>
                            <strong>Flexbox Layout:</strong> Replaced float-based layout with modern flexbox for better alignment
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="feature-icon good bi bi-check-circle-fill"></i>
                        <div>
                            <strong>CSS Variables:</strong> Added custom properties for easy theme customization
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="feature-icon good bi bi-check-circle-fill"></i>
                        <div>
                            <strong>Accessibility:</strong> Added focus states and keyboard navigation support
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="feature-icon good bi bi-check-circle-fill"></i>
                        <div>
                            <strong>Dark Mode:</strong> Automatic dark mode support with media queries
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="feature-icon good bi bi-check-circle-fill"></i>
                        <div>
                            <strong>Performance:</strong> GPU-accelerated animations and reduced repaints
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <a href="/myaccount/test-bottom-nav.php" class="btn btn-primary btn-lg">
                <i class="bi bi-eye"></i> View Live Demo
            </a>
        </div>
    </div>
</body>
</html>