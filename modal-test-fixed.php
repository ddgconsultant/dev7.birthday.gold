<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal Test - With Fix Applied</title>
    
    <!-- Same CSS as newsignup -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/css/v7/theme.css" rel="stylesheet">
    <link href="/public/css/v7/bottom-nav-improved.css" rel="stylesheet">
    <link href="/public/css/v7/mobile-first.css" rel="stylesheet">
    <link href="/core/v7/css/main.css" rel="stylesheet">
    
    <!-- Modal Fix CSS -->
    <link href="/public/css/v7/modal-fix.css" rel="stylesheet">
    
    <style>
        body {
            padding: 50px;
            background: #f8f9fa;
        }
        .fix-status {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Modal Test - With Fix Applied</h1>
        
        <div class="fix-status">
            <strong>Fix Applied:</strong> modal-fix.css loaded<br>
            <small>Modal z-index: 1055, Backdrop z-index: 1050</small>
        </div>
        
        <p>This page includes the modal-fix.css file that should resolve the z-index issue.</p>
        
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal">
            Launch Test Modal
        </button>
        
        <hr class="my-4">
        
        <h3>To apply this fix to newsignup page:</h3>
        <ol>
            <li>Add this line to the newsignup page's &lt;head&gt; section:</li>
            <li><code>&lt;link href="/public/css/v7/modal-fix.css" rel="stylesheet"&gt;</code></li>
            <li>Or add the CSS rules directly to an existing CSS file</li>
        </ol>
    </div>
    
    <!-- Test Modal -->
    <div class="modal fade" id="testModal" tabindex="-1" aria-labelledby="testModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testModalLabel">Test Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>If you can see and interact with this modal properly, the fix is working!</p>
                    <div class="alert alert-success">
                        The modal should now appear above the dark backdrop.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Log z-index values when modal opens
        document.getElementById('testModal').addEventListener('shown.bs.modal', function () {
            const modal = document.querySelector('.modal');
            const backdrop = document.querySelector('.modal-backdrop');
            const dialog = document.querySelector('.modal-dialog');
            
            console.log('Z-index values after fix:');
            console.log('Modal:', getComputedStyle(modal).zIndex);
            console.log('Dialog:', getComputedStyle(dialog).zIndex);
            console.log('Backdrop:', backdrop ? getComputedStyle(backdrop).zIndex : 'No backdrop');
        });
    </script>
</body>
</html>