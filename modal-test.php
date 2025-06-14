<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal Z-Index Test - Step 1: Basic Bootstrap</title>
    
    <!-- Bootstrap 5.3.3 CSS from CDN (same version as site) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
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
    </style>
</head>
<body>
    <div class="test-status">
        <h6>Test Stage: 1 - Basic Bootstrap</h6>
        <small class="text-muted">Pure Bootstrap modal test</small>
    </div>

    <div class="container">
        <div class="test-section">
            <h1>Modal Z-Index Test Page</h1>
            <p class="lead">Testing modal functionality with clean Bootstrap setup</p>
            
            <hr>
            
            <h3>Step 1: Basic Bootstrap Modal</h3>
            <p>This page uses only Bootstrap CDN with no custom CSS or components.</p>
            
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal">
                Launch Test Modal
            </button>
            
            <!-- Test links to other stages -->
            <div class="mt-4">
                <h5>Test Progression:</h5>
                <ul>
                    <li><strong>Current: Basic Bootstrap Modal</strong></li>
                    <li><a href="modal-test-2.php">Step 2: Add Theme CSS</a></li>
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
                    <h5 class="modal-title" id="testModalLabel">Test Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This is a test modal. If you can see this clearly without any overlay issues, the basic Bootstrap modal is working correctly.</p>
                    <div class="alert alert-info">
                        <strong>Check:</strong> Can you interact with this modal? Is the background properly dimmed?
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
        // Log when modal events fire for debugging
        document.getElementById('testModal').addEventListener('shown.bs.modal', function () {
            console.log('Modal shown successfully');
        });
        
        document.getElementById('testModal').addEventListener('hidden.bs.modal', function () {
            console.log('Modal hidden successfully');
        });
    </script>
</body>
</html>