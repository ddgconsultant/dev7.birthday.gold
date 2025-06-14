<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal Z-Index Test - Step 4: Bottom Navigation</title>
    
    <!-- Bootstrap 5.3.3 CSS from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Site CSS files in order -->
    <link href="/public/css/v7/theme.css" rel="stylesheet">
    <link href="/public/css/v7/bottom-nav-improved.css" rel="stylesheet">
    <link href="/public/css/v7/mobile-first.css" rel="stylesheet">
    
    <style>
        body {
            padding-top: 100px;
            padding-bottom: 100px; /* Account for bottom nav */
            background: #f8f9fa;
        }
        .test-status {
            position: fixed;
            top: 70px;
            right: 10px;
            background: #fff;
            padding: 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 10;
        }
        .test-section {
            background: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .css-added {
            background: #d1ecf1;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        /* Simulate the header */
        .top-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background: white;
            height: 56px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            padding: 0 20px;
        }
        
        /* Simulate bottom nav */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            z-index: 1020;
            height: 64px;
            padding-bottom: env(safe-area-inset-bottom);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Simulated Header -->
    <header class="top-header">
        <h5 class="mb-0">Test Header (z-index: 1030)</h5>
    </header>
    
    <!-- Simulated Bottom Nav -->
    <nav class="bottom-nav">
        <span>Bottom Navigation (z-index: 1020)</span>
    </nav>
    
    <!-- More Menu Overlay (initially hidden) -->
    <div class="more-menu-overlay" id="moreMenuOverlay">
        <div class="more-menu">
            <div class="more-menu-header">
                <h5>More Menu</h5>
                <button type="button" class="btn-close" onclick="hideMoreMenu()"></button>
            </div>
            <div class="more-menu-content" style="padding: 20px;">
                <p>This is the more menu overlay.</p>
                <p>Z-index should be 1045 (lower than modal's 1050+)</p>
                <div class="alert alert-warning">
                    Try opening the modal while this overlay is visible!
                </div>
                <button class="btn btn-secondary" onclick="hideMoreMenu()">Close Menu</button>
            </div>
        </div>
    </div>

    <div class="test-status">
        <h6>Test Stage: 4 - Bottom Nav Added</h6>
        <small class="text-muted">All CSS + nav components</small>
    </div>

    <div class="container">
        <div class="test-section">
            <h1>Modal Z-Index Test Page</h1>
            <p class="lead">Testing modal with bottom navigation CSS</p>
            
            <hr>
            
            <h3>Step 4: Added Bottom Navigation CSS</h3>
            <div class="css-added">
                <strong>Added CSS files:</strong>
                <ul class="mb-0">
                    <li><code>/public/css/v7/bottom-nav-improved.css</code></li>
                    <li><code>/public/css/v7/mobile-first.css</code></li>
                </ul>
            </div>
            <p>This page now includes the bottom navigation CSS files. These contain the more-menu-overlay styles.</p>
            
            <div class="btn-group" role="group">
                <!-- Button trigger modal -->
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal">
                    Launch Test Modal
                </button>
                
                <!-- Button to show more menu overlay -->
                <button type="button" class="btn btn-warning" onclick="showMoreMenu()">
                    Show More Menu Overlay
                </button>
            </div>
            
            <div class="alert alert-danger mt-3">
                <strong>Test both buttons!</strong> The modal might be covered by the more-menu-overlay if z-index is still too high.
            </div>
            
            <!-- Test links to other stages -->
            <div class="mt-4">
                <h5>Test Progression:</h5>
                <ul>
                    <li><a href="modal-test.php">Step 1: Basic Bootstrap Modal</a></li>
                    <li><a href="modal-test-2.php">Step 2: Add Theme CSS</a></li>
                    <li><a href="modal-test-3.php">Step 3: Add Header</a></li>
                    <li><strong>Current: Add Bottom Navigation</strong></li>
                    <li><a href="modal-test-5.php">Step 5: Full Site Components</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="testModal" tabindex="-1" aria-labelledby="testModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testModalLabel">Test Modal - With All Navigation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This modal is now competing with the more-menu-overlay z-index.</p>
                    <div class="alert alert-warning">
                        <strong>Critical Test:</strong> If you can't interact with this modal, the more-menu-overlay z-index is too high!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showMoreMenu() {
            document.getElementById('moreMenuOverlay').classList.add('show');
        }
        
        function hideMoreMenu() {
            document.getElementById('moreMenuOverlay').classList.remove('show');
        }
        
        document.getElementById('testModal').addEventListener('shown.bs.modal', function () {
            const modal = document.querySelector('.modal');
            const backdrop = document.querySelector('.modal-backdrop');
            const moreMenu = document.querySelector('.more-menu-overlay');
            console.log('More Menu Overlay z-index:', window.getComputedStyle(moreMenu).zIndex);
            console.log('Modal z-index:', window.getComputedStyle(modal).zIndex);
            console.log('Backdrop z-index:', backdrop ? window.getComputedStyle(backdrop).zIndex : 'No backdrop');
            
            // Alert if z-index issue detected
            const moreMenuZ = parseInt(window.getComputedStyle(moreMenu).zIndex);
            const modalZ = parseInt(window.getComputedStyle(modal).zIndex);
            if (moreMenuZ > modalZ) {
                console.error('Z-INDEX ISSUE: more-menu-overlay (' + moreMenuZ + ') is higher than modal (' + modalZ + ')');
            }
        });
    </script>
</body>
</html>