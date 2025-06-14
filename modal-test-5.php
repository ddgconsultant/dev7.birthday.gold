<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal Z-Index Test - Step 5: Z-Index Inspector</title>
    
    <!-- Bootstrap 5.3.3 CSS from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- All Site CSS files -->
    <link href="/public/css/v7/theme.css" rel="stylesheet">
    <link href="/public/css/v7/bottom-nav-improved.css" rel="stylesheet">
    <link href="/public/css/v7/mobile-first.css" rel="stylesheet">
    
    <style>
        body {
            padding: 50px;
            background: #f8f9fa;
        }
        .test-status {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #fff;
            padding: 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 10;
            max-width: 400px;
        }
        .test-section {
            background: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .z-index-inspector {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
        .z-index-item {
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .z-index-high {
            color: #dc3545;
            font-weight: bold;
        }
        .z-index-medium {
            color: #ffc107;
        }
        .z-index-low {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="test-status">
        <h6>Test Stage: 5 - Z-Index Inspector</h6>
        <small class="text-muted">Analyzing all z-index values</small>
        <div id="zIndexReport" class="mt-2"></div>
    </div>

    <div class="container">
        <div class="test-section">
            <h1>Modal Z-Index Inspector</h1>
            <p class="lead">Comprehensive z-index analysis</p>
            
            <hr>
            
            <h3>Step 5: Z-Index Conflict Detection</h3>
            <p>This page analyzes all elements with z-index values and identifies potential conflicts.</p>
            
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal">
                Launch Test Modal
            </button>
            
            <button type="button" class="btn btn-info ms-2" onclick="analyzeZIndexes()">
                Analyze All Z-Indexes
            </button>
            
            <div class="mt-4">
                <h5>Z-Index Analysis Results:</h5>
                <div id="analysisResults" class="z-index-inspector">
                    <p class="text-muted">Click "Analyze All Z-Indexes" to scan the page</p>
                </div>
            </div>
            
            <!-- Test links to other stages -->
            <div class="mt-4">
                <h5>Test Progression:</h5>
                <ul>
                    <li><a href="modal-test.php">Step 1: Basic Bootstrap Modal</a></li>
                    <li><a href="modal-test-2.php">Step 2: Add Theme CSS</a></li>
                    <li><a href="modal-test-3.php">Step 3: Add Header</a></li>
                    <li><a href="modal-test-4.php">Step 4: Add Bottom Navigation</a></li>
                    <li><strong>Current: Z-Index Inspector</strong></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="testModal" tabindex="-1" aria-labelledby="testModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testModalLabel">Test Modal - Z-Index Analysis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Use the analysis tool to check z-index values while this modal is open.</p>
                    <div id="modalZIndexInfo" class="alert alert-info"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="analyzeZIndexes()">Analyze Now</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function analyzeZIndexes() {
            const elements = document.querySelectorAll('*');
            const zIndexMap = new Map();
            
            elements.forEach(el => {
                const style = window.getComputedStyle(el);
                const zIndex = style.zIndex;
                const position = style.position;
                
                if (zIndex !== 'auto' && zIndex !== '0' && position !== 'static') {
                    const tagName = el.tagName.toLowerCase();
                    const className = el.className || 'no-class';
                    const key = `${tagName}.${className}`;
                    
                    if (!zIndexMap.has(zIndex)) {
                        zIndexMap.set(zIndex, []);
                    }
                    zIndexMap.get(zIndex).push({
                        element: key,
                        position: position,
                        display: style.display
                    });
                }
            });
            
            // Sort by z-index value
            const sorted = Array.from(zIndexMap.entries()).sort((a, b) => parseInt(b[0]) - parseInt(a[0]));
            
            let html = '<h6>Elements with Z-Index (highest to lowest):</h6>';
            sorted.forEach(([zIndex, elements]) => {
                const zValue = parseInt(zIndex);
                let colorClass = 'z-index-low';
                if (zValue >= 2000) colorClass = 'z-index-high';
                else if (zValue >= 1000) colorClass = 'z-index-medium';
                
                html += `<div class="z-index-item">`;
                html += `<strong class="${colorClass}">z-index: ${zIndex}</strong><br>`;
                elements.forEach(el => {
                    html += `<small>- ${el.element} (${el.position}${el.display === 'none' ? ', hidden' : ''})</small><br>`;
                });
                html += `</div>`;
            });
            
            document.getElementById('analysisResults').innerHTML = html;
            
            // Special modal check
            const modal = document.querySelector('.modal.show');
            const backdrop = document.querySelector('.modal-backdrop');
            if (modal) {
                const modalInfo = `
                    <strong>Current Modal Status:</strong><br>
                    Modal z-index: ${window.getComputedStyle(modal).zIndex}<br>
                    Backdrop z-index: ${backdrop ? window.getComputedStyle(backdrop).zIndex : 'No backdrop'}
                `;
                document.getElementById('modalZIndexInfo').innerHTML = modalInfo;
                document.getElementById('zIndexReport').innerHTML = modalInfo;
            }
        }
        
        // Auto-analyze when modal opens
        document.getElementById('testModal').addEventListener('shown.bs.modal', function () {
            setTimeout(analyzeZIndexes, 100);
        });
    </script>
</body>
</html>