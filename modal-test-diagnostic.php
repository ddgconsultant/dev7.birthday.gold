<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal Diagnostic - Exact Replication</title>
    
    <!-- Exact same CSS order as newsignup page -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/public/css/v7/theme.css" rel="stylesheet">
    <link href="/public/css/v7/bottom-nav-improved.css" rel="stylesheet">
    <link href="/public/css/v7/mobile-first.css" rel="stylesheet">
    <link href="/core/v7/css/main.css" rel="stylesheet">
    
    <style>
        .diagnostic-panel {
            position: fixed;
            bottom: 100px;
            right: 20px;
            background: #000;
            color: #0f0;
            padding: 15px;
            font-family: monospace;
            font-size: 12px;
            max-width: 400px;
            z-index: 9999;
            border: 1px solid #0f0;
        }
        .diag-error { color: #f00; }
        .diag-warning { color: #ff0; }
        .diag-success { color: #0f0; }
    </style>
</head>
<body>
    <!-- Header from newsignup -->
    <header class="top-header" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1030; background: white; height: 56px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div class="container">
            <h5>Header (z-index: 1030)</h5>
        </div>
    </header>
    
    <!-- More menu overlay -->
    <div class="more-menu-overlay"></div>
    
    <div class="container" style="margin-top: 80px; margin-bottom: 100px;">
        <h1>Modal Diagnostic Page</h1>
        <p>This page exactly replicates the CSS loading order from newsignup</p>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal">
                    Open Test Modal
                </button>
                
                <button type="button" class="btn btn-warning ms-2" onclick="showDiagnostics()">
                    Run Diagnostics
                </button>
            </div>
        </div>
        
        <div id="results" class="mt-4"></div>
    </div>
    
    <!-- Bottom nav -->
    <nav class="bottom-nav" style="position: fixed; bottom: 0; left: 0; right: 0; z-index: 1020; height: 64px; background: white; border-top: 1px solid #ddd;">
        <div class="container">
            <span>Bottom Nav (z-index: 1020)</span>
        </div>
    </nav>
    
    <!-- Modal matching newsignup structure -->
    <div class="modal fade" id="testModal" tabindex="-1" aria-labelledby="testModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testModalLabel">Test Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Testing modal with exact newsignup CSS configuration</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Diagnostic Panel -->
    <div class="diagnostic-panel" id="diagnosticPanel" style="display: none;">
        <div id="diagnosticOutput"></div>
    </div>
    
    <!-- Scripts in same order -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let diagnosticInterval;
        
        function showDiagnostics() {
            const panel = document.getElementById('diagnosticPanel');
            panel.style.display = 'block';
            
            // Start real-time monitoring
            diagnosticInterval = setInterval(updateDiagnostics, 100);
            
            // Stop after 10 seconds
            setTimeout(() => {
                clearInterval(diagnosticInterval);
            }, 10000);
        }
        
        function updateDiagnostics() {
            const output = document.getElementById('diagnosticOutput');
            let html = '<strong>REAL-TIME Z-INDEX MONITOR</strong><br><br>';
            
            // Check modal
            const modal = document.querySelector('.modal');
            const modalBackdrop = document.querySelector('.modal-backdrop');
            const moreMenuOverlay = document.querySelector('.more-menu-overlay');
            
            if (modal && modal.classList.contains('show')) {
                html += '<span class="diag-success">MODAL ACTIVE</span><br>';
                html += `Modal z-index: ${getComputedStyle(modal).zIndex}<br>`;
                
                if (modalBackdrop) {
                    html += `Backdrop z-index: ${getComputedStyle(modalBackdrop).zIndex}<br>`;
                    html += `Backdrop opacity: ${getComputedStyle(modalBackdrop).opacity}<br>`;
                }
            } else {
                html += '<span class="diag-warning">Modal not active</span><br>';
            }
            
            html += '<br>OTHER ELEMENTS:<br>';
            html += `Header z-index: ${getComputedStyle(document.querySelector('.top-header')).zIndex}<br>`;
            html += `Bottom nav z-index: ${getComputedStyle(document.querySelector('.bottom-nav')).zIndex}<br>`;
            html += `More menu z-index: ${getComputedStyle(moreMenuOverlay).zIndex}<br>`;
            
            // Check for any elements with very high z-index
            const allElements = document.querySelectorAll('*');
            const highZIndexElements = [];
            
            allElements.forEach(el => {
                const zIndex = parseInt(getComputedStyle(el).zIndex);
                if (zIndex > 2000) {
                    highZIndexElements.push({
                        element: el.className || el.tagName,
                        zIndex: zIndex
                    });
                }
            });
            
            if (highZIndexElements.length > 0) {
                html += '<br><span class="diag-error">HIGH Z-INDEX DETECTED:</span><br>';
                highZIndexElements.forEach(item => {
                    html += `${item.element}: ${item.zIndex}<br>`;
                });
            }
            
            output.innerHTML = html;
        }
        
        // Listen for modal events
        document.getElementById('testModal').addEventListener('show.bs.modal', function() {
            console.log('Modal show event fired');
            showDiagnostics();
        });
        
        document.getElementById('testModal').addEventListener('shown.bs.modal', function() {
            console.log('Modal shown event fired');
            
            // Final check
            const modal = document.querySelector('.modal.show');
            const backdrop = document.querySelector('.modal-backdrop');
            
            if (modal && backdrop) {
                const modalZ = parseInt(getComputedStyle(modal).zIndex);
                const backdropZ = parseInt(getComputedStyle(backdrop).zIndex);
                
                console.log('Final z-index values:');
                console.log('Modal:', modalZ);
                console.log('Backdrop:', backdropZ);
                
                // Check if anything is blocking
                const modalRect = modal.getBoundingClientRect();
                const elementAtCenter = document.elementFromPoint(
                    modalRect.left + modalRect.width / 2,
                    modalRect.top + modalRect.height / 2
                );
                
                if (elementAtCenter && !modal.contains(elementAtCenter)) {
                    console.error('Element blocking modal:', elementAtCenter);
                }
            }
        });
    </script>
</body>
</html>