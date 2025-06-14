<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal Z-Index Test - Step 2: Theme CSS</title>
    
    <!-- Bootstrap 5.3.3 CSS from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Site Theme CSS -->
    <link href="/public/css/v7/theme.css" rel="stylesheet">
    
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
    </style>
</head>
<body>
    <div class="test-status">
        <h6>Test Stage: 2 - Theme CSS Added</h6>
        <small class="text-muted">Bootstrap + theme.css</small>
    </div>

    <div class="container">
        <div class="test-section">
            <h1>Modal Z-Index Test Page</h1>
            <p class="lead">Testing modal with theme.css added</p>
            
            <hr>
            
            <h3>Step 2: Added Theme CSS</h3>
            <div class="css-added">
                <strong>Added:</strong> <code>/public/css/v7/theme.css</code>
            </div>
            <p>This page now includes the main theme CSS file. Check if the modal still works correctly.</p>
            
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal">
                Launch Test Modal
            </button>
            
            <!-- Test links to other stages -->
            <div class="mt-4">
                <h5>Test Progression:</h5>
                <ul>
                    <li><a href="modal-test.php">Step 1: Basic Bootstrap Modal</a></li>
                    <li><strong>Current: Add Theme CSS</strong></li>
                    <li><a href="modal-test-3.php">Step 3: Add Header</a></li>
                    <li><a href="modal-test-4.php">Step 4: Add Bottom Navigation</a></li>
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
                    <h5 class="modal-title" id="testModalLabel">Test Modal - With Theme CSS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This modal now has theme.css loaded. Check for any z-index or overlay issues.</p>
                    <div class="alert alert-warning">
                        <strong>Testing:</strong> Is the modal still accessible? Any overlay problems?
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
        // Check for any z-index conflicts
        document.getElementById('testModal').addEventListener('shown.bs.modal', function () {
            const modal = document.querySelector('.modal');
            const backdrop = document.querySelector('.modal-backdrop');
            console.log('Modal z-index:', window.getComputedStyle(modal).zIndex);
            console.log('Backdrop z-index:', backdrop ? window.getComputedStyle(backdrop).zIndex : 'No backdrop');
        });
    </script>
</body>
</html>